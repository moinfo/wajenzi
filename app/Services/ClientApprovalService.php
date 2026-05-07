<?php

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\BillingPayment;
use App\Models\Lead;
use App\Models\Project;
use App\Models\ProjectClient;
use App\Models\ProjectType;
use App\Services\ProjectScheduleService;
use Illuminate\Support\Facades\Log;

class ClientApprovalService
{
    /**
     * Auto-approve all workflow steps for a client on their first payment
     *
     * @param int $clientId
     * @param BillingPayment|null $currentPayment
     * @return bool
     */
    public static function autoApproveOnFirstPayment($clientId, ?BillingPayment $currentPayment = null): bool
    {
        try {
            $client = ProjectClient::with('approvalStatus')->find($clientId);
            if (!$client) {
                return false;
            }

            // Check if client is already approved - no need to auto-approve
            if ($client->approvalStatus && $client->isApprovalCompleted()) {
                // Just update status to PAID if needed
                if (!in_array($client->status, ['PAID', 'COMPLETED'])) {
                    $client->status = 'PAID';
                    $client->save();
                }
                return true;
            }

            // Check if client already has PAID or COMPLETED status
            if (in_array($client->status, ['PAID', 'COMPLETED'])) {
                return true;
            }

            // Check if this is the first completed payment for this client
            $query = BillingPayment::where('client_id', $clientId)
                ->where('status', 'completed');

            if ($currentPayment) {
                $query->where('id', '!=', $currentPayment->id);
            }

            $previousPayments = $query->count();

            // If this is NOT the first payment, skip auto-approval
            if ($previousPayments > 0) {
                return false;
            }

            // Auto-approve all workflow steps (only if not already approved)
            self::approveAllWorkflowSteps($client);

            // Update client status to PAID
            $client->status = 'PAID';
            $client->save();

            // Create project schedule and assign architect
            self::createProjectScheduleForClient($clientId, $currentPayment);

            Log::info("Auto-approved all workflow steps for client #{$clientId} on first payment");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to auto-approve client workflow: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve all pending workflow steps for a client
     *
     * @param ProjectClient $client
     * @return void
     */
    public static function approveAllWorkflowSteps(ProjectClient $client): void
    {
        // First, ensure the client has an approval status record
        if (!$client->approvalStatus) {
            return;
        }

        // Submit for approval if not yet submitted
        if (!$client->isSubmitted()) {
            try {
                $client->submit(auth()->user());
                $client->refresh();
            } catch (\Exception $e) {
                Log::warning("Could not submit client for approval: " . $e->getMessage());
            }
        }

        // Approve each step until completion
        $maxIterations = 20; // Safety limit to prevent infinite loops
        $iterations = 0;

        while (!$client->isApprovalCompleted() && $iterations < $maxIterations) {
            $nextStep = $client->nextApprovalStep();
            if (!$nextStep) {
                break;
            }

            try {
                $client->approve('Auto-approved on first payment', auth()->user());
                $client->refresh();
            } catch (\Exception $e) {
                Log::warning("Could not approve step: " . $e->getMessage());
                break;
            }

            $iterations++;
        }
    }

    /**
     * Create project schedule for a client on first payment.
     * Also converts the lead to a project in Design Phase.
     */
    protected static function createProjectScheduleForClient($clientId, ?BillingPayment $payment = null): void
    {
        try {
            // Resolve the lead from the payment document, or fall back to client's lead
            $leadId = null;
            if ($payment && $payment->document_id) {
                $document = BillingDocument::find($payment->document_id);
                if ($document && $document->lead_id) {
                    $leadId = $document->lead_id;
                }
            }
            if (!$leadId) {
                $leadId = Lead::where('client_id', $clientId)->value('id');
            }

            if (!$leadId) {
                Log::warning("No lead found for client #{$clientId}, cannot create project schedule");
                return;
            }

            $lead = Lead::find($leadId);

            // Create a project if the lead doesn't have one yet
            if (!$lead->project_id) {
                $project = self::createProjectFromLead($lead);
                if ($project) {
                    $lead->project_id = $project->id;
                }
            } else {
                // Advance an existing pending project to design_phase
                Project::where('id', $lead->project_id)
                    ->where('status', 'pending')
                    ->update(['status' => 'design_phase']);
            }

            // Mark the lead as converted
            $lead->status = 'converted';
            $lead->save();

            // Auto-assign architect and create the design schedule
            $schedule = ProjectScheduleService::assignArchitectOnFirstPayment($leadId);

            if ($schedule) {
                Log::info("Project schedule #{$schedule->id} created for lead #{$leadId} on first payment");
            }

        } catch (\Exception $e) {
            Log::error("Failed to create project schedule for client #{$clientId}: " . $e->getMessage());
        }
    }

    /**
     * Auto-create a project in Design Phase from a converted lead.
     */
    private static function createProjectFromLead(Lead $lead): ?Project
    {
        $projectType = ProjectType::whereRaw('LOWER(name) LIKE ?', ['%design%'])->first()
            ?? ProjectType::first();

        if (!$projectType) {
            Log::warning("No project type found; cannot auto-create project for lead #{$lead->id}");
            return null;
        }

        $nextId = (Project::max('id') ?? 0) + 1;

        $project = Project::create([
            'document_number' => 'PCT/' . $nextId . '/' . date('Y'),
            'project_name'    => $lead->name . ' — Design Project',
            'client_id'       => $lead->client_id,
            'project_type_id' => $projectType->id,
            'status'          => 'design_phase',
            'start_date'      => now()->toDateString(),
            'salesperson_id'  => $lead->salesperson_id,
            'contract_value'  => $lead->estimated_value,
            'create_by_id'    => auth()->id() ?? 1,
        ]);

        Log::info("Auto-created project #{$project->id} (design_phase) from lead #{$lead->id}");

        return $project;
    }
}
