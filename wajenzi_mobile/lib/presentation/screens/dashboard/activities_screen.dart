import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/datasources/remote/staff_dashboard_api.dart';
import '../../providers/settings_provider.dart';
import '../../providers/staff_dashboard_provider.dart';

/// Provider that fetches the activities list from the API.
final _activitiesProvider =
    FutureProvider.autoDispose<List<DashboardActivity>>((ref) async {
  final api = ref.watch(staffDashboardApiProvider);
  return api.fetchActivities();
});

class ActivitiesScreen extends ConsumerWidget {
  const ActivitiesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final activitiesAsync = ref.watch(_activitiesProvider);
    final dashboardState = ref.watch(staffDashboardProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    // Summary counts from cached dashboard data
    final summary = dashboardState.valueOrNull?.activitiesSummary;

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Shughuli za Mradi' : 'Project Activities'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_activitiesProvider.future),
        child: activitiesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorBody(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_activitiesProvider),
          ),
          data: (activities) => _Body(
            activities: activities,
            summary: summary,
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
          ),
        ),
      ),
    );
  }
}

// ─── Body ────────────────────────────────────────

class _Body extends StatelessWidget {
  final List<DashboardActivity> activities;
  final ActivitiesSummary? summary;
  final bool isSwahili;
  final bool isDarkMode;

  const _Body({
    required this.activities,
    this.summary,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        // ─── Status chips row ──────────────
        if (summary != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 20),
            child: Row(
              children: [
                _StatusChip(
                  count: summary!.overdue,
                  label: isSwahili ? 'ZILIZOCHELEWA' : 'OVERDUE',
                  icon: Icons.warning_rounded,
                  color: const Color(0xFFE74C3C),
                  bgColor: const Color(0xFFFDEDED),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.dueToday,
                  label: isSwahili ? 'LEO' : 'DUE TODAY',
                  icon: Icons.adjust_rounded,
                  color: const Color(0xFFE67E22),
                  bgColor: const Color(0xFFFEF5E7),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.pending,
                  label: isSwahili ? 'ZINASUBIRI' : 'PENDING',
                  icon: Icons.calendar_today_rounded,
                  color: const Color(0xFF27AE60),
                  bgColor: const Color(0xFFEAFAF1),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.inProgress,
                  label: isSwahili ? 'ZINAENDELEA' : 'IN PROGRESS',
                  icon: Icons.sync_rounded,
                  color: const Color(0xFF2980B9),
                  bgColor: const Color(0xFFEBF5FB),
                ),
              ],
            ),
          ),

        // ─── Activity cards ────────────────
        if (activities.isEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 60),
            child: Center(
              child: Column(
                children: [
                  Icon(Icons.assignment_outlined,
                      size: 56, color: Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna shughuli kwa sasa'
                        : 'No activities at the moment',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
          )
        else
          ...activities.map((a) => _ActivityCard(
                activity: a,
                isDarkMode: isDarkMode,
              )),

        const SizedBox(height: 80),
      ],
    );
  }
}

// ─── Status Chip ─────────────────────────────────

class _StatusChip extends StatelessWidget {
  final int count;
  final String label;
  final IconData icon;
  final Color color;
  final Color bgColor;

  const _StatusChip({
    required this.count,
    required this.label,
    required this.icon,
    required this.color,
    required this.bgColor,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, size: 14, color: color),
                const SizedBox(width: 4),
                Text(
                  '$count',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 8,
                fontWeight: FontWeight.w600,
                color: color,
                letterSpacing: 0.5,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Activity Card ───────────────────────────────

class _ActivityCard extends StatelessWidget {
  final DashboardActivity activity;
  final bool isDarkMode;

  const _ActivityCard({
    required this.activity,
    required this.isDarkMode,
  });

  Color get _statusColor {
    if (activity.isOverdue) return const Color(0xFFE74C3C);
    switch (activity.status) {
      case 'in_progress':
        return const Color(0xFF2980B9);
      case 'completed':
        return const Color(0xFF27AE60);
      case 'pending':
      default:
        return const Color(0xFF95A5A6);
    }
  }

  Color get _borderColor {
    if (activity.isOverdue) return const Color(0xFFE74C3C).withValues(alpha: 0.3);
    switch (activity.status) {
      case 'in_progress':
        return const Color(0xFF2980B9).withValues(alpha: 0.3);
      case 'completed':
        return const Color(0xFF27AE60).withValues(alpha: 0.3);
      default:
        return Colors.grey.withValues(alpha: 0.15);
    }
  }

  String get _statusLabel {
    if (activity.isOverdue) return 'Overdue';
    switch (activity.status) {
      case 'in_progress':
        return 'In Progress';
      case 'completed':
        return 'Completed';
      case 'pending':
        return 'Pending';
      default:
        return activity.status ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    // Parse start date for the date badge
    DateTime? startDate;
    try {
      if (activity.startDate != null) {
        startDate = DateTime.parse(activity.startDate!);
      }
    } catch (_) {}

    // Parse end date for the due chip
    DateTime? endDate;
    try {
      if (activity.endDate != null) {
        endDate = DateTime.parse(activity.endDate!);
      }
    } catch (_) {}

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _borderColor, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Date badge ──
            if (startDate != null)
              Container(
                width: 54,
                padding: const EdgeInsets.symmetric(vertical: 8),
                decoration: BoxDecoration(
                  color: _statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Column(
                  children: [
                    Text(
                      '${startDate.day}',
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: _statusColor,
                        height: 1,
                      ),
                    ),
                    Text(
                      DateFormat('MMM').format(startDate).toUpperCase(),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: _statusColor,
                      ),
                    ),
                  ],
                ),
              ),
            if (startDate != null) const SizedBox(width: 14),

            // ── Content ──
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title row
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          '${activity.activityCode ?? ''}: ${activity.name ?? ''}',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      // Due chip or status badge
                      if (activity.isOverdue || activity.status == 'in_progress')
                        Container(
                          margin: const EdgeInsets.only(left: 8),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: _statusColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (activity.isOverdue)
                                Padding(
                                  padding: const EdgeInsets.only(right: 4),
                                  child: Icon(Icons.error_rounded,
                                      size: 12, color: _statusColor),
                                ),
                              Text(
                                _statusLabel,
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: _statusColor,
                                ),
                              ),
                            ],
                          ),
                        )
                      else if (endDate != null)
                        Container(
                          margin: const EdgeInsets.only(left: 8),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? Colors.white10
                                : Colors.grey[100],
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            DateFormat('dd MMM').format(endDate),
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                              color: isDarkMode
                                  ? Colors.white60
                                  : AppColors.textSecondary,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),

                  // Assigned to
                  if (activity.assignedTo != null)
                    Text(
                      '- ${activity.assignedTo}',
                      style: TextStyle(
                        fontSize: 13,
                        color: isDarkMode
                            ? Colors.white60
                            : AppColors.textSecondary,
                      ),
                    ),

                  // Phase
                  if (activity.phase != null) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(
                          Icons.layers_rounded,
                          size: 14,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          activity.phase!,
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Error Body ──────────────────────────────────

class _ErrorBody extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorBody({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          error.toString(),
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}
