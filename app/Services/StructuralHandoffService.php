<?php

namespace App\Services;

use App\Mail\StructuralHandoffMail;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\ProjectStructuralDesign;
use App\Models\ProjectStructuralDesignStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StructuralHandoffService
{
    /**
     * Called from ProjectScheduleActivity::onApprovalCompleted() when B7 is approved.
     * Creates the structural design record and notifies engineers.
     */
    public static function triggerFromActivity(ProjectScheduleActivity $activity): void
    {
        try {
            DB::beginTransaction();

            $schedule = ProjectSchedule::find($activity->project_schedule_id);
            if (!$schedule) {
                Log::warning("StructuralHandoff: no schedule found for activity #{$activity->id}");
                return;
            }

            // Resolve the project from the schedule's lead
            $lead    = $schedule->lead;
            $project = $lead?->project;

            if (!$project) {
                Log::warning("StructuralHandoff: no project found for schedule #{$schedule->id}");
                return;
            }

            // Prevent duplicate structural design records
            if (ProjectStructuralDesign::where('project_id', $project->id)->exists()) {
                Log::info("StructuralHandoff: structural design already exists for project #{$project->id}");
                return;
            }

            $engineer = self::findStructuralEngineer();

            $design = ProjectStructuralDesign::create([
                'project_id'               => $project->id,
                'triggered_by_activity_id' => $activity->id,
                'assigned_engineer_id'     => $engineer?->id,
                'status'                   => 'pending',
                'created_by'               => auth()->id() ?? 1,
            ]);

            // Seed the 3 standard stages
            foreach (ProjectStructuralDesignStage::defaultStages() as $stageData) {
                ProjectStructuralDesignStage::create(array_merge(
                    $stageData,
                    ['structural_design_id' => $design->id, 'status' => 'pending']
                ));
            }

            // Advance project status to show structural phase
            $project->update(['status' => 'structural_phase']);

            DB::commit();

            Log::info("StructuralHandoff: created design #{$design->id} for project #{$project->id}");

            // Notify structural engineers
            self::notifyEngineers($design);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("StructuralHandoff failed: " . $e->getMessage());
        }
    }

    /**
     * Find the structural engineer with the least active assignments.
     */
    private static function findStructuralEngineer(): ?User
    {
        return User::whereHas('roles', fn($q) => $q->whereIn('name', [
                'Structural Engineer',
                'Engineer',
                'Managing Director',
            ]))
            ->withCount(['structuralDesigns as active_designs' => fn($q) =>
                $q->whereNotIn('status', ['approved', 'rejected'])
            ])
            ->orderBy('active_designs')
            ->first();
    }

    private static function notifyEngineers(ProjectStructuralDesign $design): void
    {
        try {
            $engineers = User::whereHas('roles', fn($q) => $q->whereIn('name', [
                'Structural Engineer',
                'Engineer',
            ]))->get();

            foreach ($engineers as $engineer) {
                if ($engineer->email) {
                    Mail::to($engineer->email)->send(new StructuralHandoffMail($design));
                }
            }
        } catch (\Exception $e) {
            Log::error("StructuralHandoff notification failed: " . $e->getMessage());
        }
    }
}
