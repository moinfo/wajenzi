<?php

namespace App\Services;

use App\Models\ArchitectBonusTask;
use App\Models\BonusWeightConfig;

class BonusCalculationService
{
    const UNIT_VALUE = 10000; // 1 unit = 10,000 TZS
    const SP_CAP = 1.1;      // Schedule Performance cap

    /**
     * Calculate and update bonus for a task.
     */
    public static function calculate(ArchitectBonusTask $task): ArchitectBonusTask
    {
        // Must have actual completion date and scoring data
        if (!$task->actual_completion_date || $task->design_quality_score === null || $task->client_revisions === null) {
            return $task;
        }

        $scheduledDays = $task->scheduled_days;
        $actualDays = $task->actual_days;

        // No bonus if delay exceeds 50% of scheduled duration
        if ($task->isExcessiveDelay()) {
            $task->update([
                'schedule_performance' => $scheduledDays / $actualDays,
                'client_approval_efficiency' => 1 / max(1, $task->client_revisions),
                'performance_score' => 0,
                'final_units' => 0,
                'bonus_amount' => 0,
                'status' => 'no_bonus',
            ]);
            return $task->fresh();
        }

        // Schedule Performance (SP) - capped at 1.1
        $sp = min($scheduledDays / $actualDays, self::SP_CAP);

        // Design Quality (DQ) - already provided (0.4 - 1.0)
        $dq = $task->design_quality_score;

        // Client Approval Efficiency (CA)
        $ca = 1 / max(1, $task->client_revisions);

        // Get configurable weights
        $weights = BonusWeightConfig::getWeights();
        $wSchedule = $weights['schedule'] ?? 0.40;
        $wQuality = $weights['quality'] ?? 0.40;
        $wClient = $weights['client'] ?? 0.20;

        // Performance Score
        $ps = ($wSchedule * $sp) + ($wQuality * $dq) + ($wClient * $ca);

        // Final Units (capped at max_units)
        $finalUnits = min($task->max_units * $ps, $task->max_units);
        $finalUnitsRounded = round($finalUnits);

        // Bonus Amount
        $bonusAmount = $finalUnitsRounded * self::UNIT_VALUE;

        $task->update([
            'schedule_performance' => round($sp, 3),
            'client_approval_efficiency' => round($ca, 3),
            'performance_score' => round($ps, 3),
            'final_units' => $finalUnitsRounded,
            'bonus_amount' => $bonusAmount,
            'status' => 'scored',
        ]);

        return $task->fresh();
    }
}
