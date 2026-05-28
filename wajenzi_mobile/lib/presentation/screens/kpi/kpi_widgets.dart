import 'package:flutter/material.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/models/kpi_common.dart';

/// A small colored status chip for a review status.
class KpiStatusChip extends StatelessWidget {
  final String status;
  final String label;

  const KpiStatusChip({super.key, required this.status, required this.label});

  @override
  Widget build(BuildContext context) {
    final color = kpiStatusColor(status);
    final text = label.isEmpty ? status : label;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(
        text,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

/// A compact score tile (e.g. Self / Supervisor / Overall).
class KpiScoreTile extends StatelessWidget {
  final String label;
  final double score;
  final bool highlight;

  const KpiScoreTile({
    super.key,
    required this.label,
    required this.score,
    this.highlight = false,
  });

  @override
  Widget build(BuildContext context) {
    final color = highlight ? kpiGradeColor(score) : AppColors.brandBlue;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Column(
        children: [
          Text(
            score.toStringAsFixed(1),
            style: AppType.display(20, weight: FontWeight.w800, color: color),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: Theme.of(context)
                  .textTheme
                  .bodyMedium
                  ?.color
                  ?.withValues(alpha: 0.8),
            ),
          ),
        ],
      ),
    );
  }
}

/// A small grade pill (Excellent / Good / ...).
class KpiGradePill extends StatelessWidget {
  final double score;
  final String? labelOverride;

  const KpiGradePill({super.key, required this.score, this.labelOverride});

  @override
  Widget build(BuildContext context) {
    final color = kpiGradeColor(score);
    final label =
        (labelOverride != null && labelOverride!.isNotEmpty)
            ? labelOverride!
            : kpiGradeLabel(score);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

/// 4-step progress indicator: Self -> Supervisor -> MD -> CEO.
class KpiStageProgress extends StatelessWidget {
  /// Number of completed stages (0..4).
  final int completed;

  const KpiStageProgress({super.key, required this.completed});

  static const _stages = ['Self', 'Supervisor', 'MD', 'CEO'];

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        for (int i = 0; i < _stages.length; i++) ...[
          _dot(context, i),
          if (i < _stages.length - 1)
            Expanded(
              child: Container(
                height: 3,
                margin: const EdgeInsets.symmetric(horizontal: 2),
                color: i < completed
                    ? AppColors.brandGreen
                    : Colors.grey.withValues(alpha: 0.3),
              ),
            ),
        ],
      ],
    );
  }

  Widget _dot(BuildContext context, int i) {
    final done = i < completed;
    final active = i == completed;
    final color = done
        ? AppColors.brandGreen
        : active
            ? AppColors.brandYellow
            : Colors.grey.withValues(alpha: 0.4);
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 26,
          height: 26,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
          child: Center(
            child: done
                ? const Icon(Icons.check, size: 15, color: Colors.white)
                : Text(
                    '${i + 1}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          _stages[i],
          style: TextStyle(
            fontSize: 10,
            fontWeight: active ? FontWeight.w700 : FontWeight.w500,
            color: Theme.of(context)
                .textTheme
                .bodyMedium
                ?.color
                ?.withValues(alpha: 0.9),
          ),
        ),
      ],
    );
  }
}
