<?php

namespace App\Services;

use App\Models\BillingPayment;
use App\Models\ProjectClient;
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
}
