<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rate semantics change: previously each rate was a 0..100 percentage of the
 * row's weight (contribution = rate/100 × weight). Going forward each rate IS
 * the row's weighted contribution directly, capped at weight_snapshot.
 *
 * Convert existing rows: new_rate = old_rate × weight_snapshot / 100.
 * Recompute the review-level totals to match.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            DB::statement("
                UPDATE kpi_review_ratings
                   SET self_rate       = ROUND(self_rate       * weight_snapshot / 100, 2),
                       supervisor_rate = ROUND(supervisor_rate * weight_snapshot / 100, 2),
                       overall_rate    = ROUND(overall_rate    * weight_snapshot / 100, 2)
                 WHERE weight_snapshot IS NOT NULL
                   AND weight_snapshot > 0
                   AND (self_rate IS NOT NULL OR supervisor_rate IS NOT NULL OR overall_rate IS NOT NULL)
            ");

            // Recompute review-level totals as a straight sum of the new rates.
            DB::statement("
                UPDATE kpi_reviews r
                  LEFT JOIN (
                    SELECT kpi_review_id,
                           ROUND(SUM(COALESCE(self_rate,       0)), 2) AS self_total,
                           ROUND(SUM(COALESCE(supervisor_rate, 0)), 2) AS sup_total,
                           ROUND(SUM(COALESCE(overall_rate,    0)), 2) AS ovr_total
                      FROM kpi_review_ratings
                     GROUP BY kpi_review_id
                  ) t ON t.kpi_review_id = r.id
                  SET r.total_self_score       = t.self_total,
                      r.total_supervisor_score = t.sup_total,
                      r.total_overall_score    = t.ovr_total,
                      r.grade_label = CASE
                          WHEN t.ovr_total IS NULL THEN r.grade_label
                          WHEN t.ovr_total >= 90 THEN 'Excellent'
                          WHEN t.ovr_total >= 80 THEN 'Very Good'
                          WHEN t.ovr_total >= 70 THEN 'Good'
                          WHEN t.ovr_total >= 60 THEN 'Average'
                          WHEN t.ovr_total >= 50 THEN 'Poor'
                          ELSE 'Ungraded'
                      END
            ");
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            // Reverse: old_rate = new_rate × 100 / weight_snapshot
            DB::statement("
                UPDATE kpi_review_ratings
                   SET self_rate       = ROUND(self_rate       * 100 / weight_snapshot, 2),
                       supervisor_rate = ROUND(supervisor_rate * 100 / weight_snapshot, 2),
                       overall_rate    = ROUND(overall_rate    * 100 / weight_snapshot, 2)
                 WHERE weight_snapshot IS NOT NULL
                   AND weight_snapshot > 0
                   AND (self_rate IS NOT NULL OR supervisor_rate IS NOT NULL OR overall_rate IS NOT NULL)
            ");
        });
    }
};
