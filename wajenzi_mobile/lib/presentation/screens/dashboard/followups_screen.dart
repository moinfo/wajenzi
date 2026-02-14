import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/datasources/remote/staff_dashboard_api.dart';
import '../../providers/settings_provider.dart';
import '../../providers/staff_dashboard_provider.dart';

final _followupsProvider =
    FutureProvider.autoDispose<List<DashboardFollowup>>((ref) async {
  final api = ref.watch(staffDashboardApiProvider);
  return api.fetchFollowups();
});

class FollowupsScreen extends ConsumerWidget {
  const FollowupsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final followupsAsync = ref.watch(_followupsProvider);
    final dashboardState = ref.watch(staffDashboardProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    final summary = dashboardState.valueOrNull?.followupSummary;

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ufuatiliaji' : 'Follow-up To-Do'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_followupsProvider.future),
        child: followupsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorBody(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_followupsProvider),
          ),
          data: (followups) => _Body(
            followups: followups,
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
  final List<DashboardFollowup> followups;
  final StatusSummary? summary;
  final bool isSwahili;
  final bool isDarkMode;

  const _Body({
    required this.followups,
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
                  count: summary!.today,
                  label: isSwahili ? 'LEO' : 'TODAY',
                  icon: Icons.adjust_rounded,
                  color: const Color(0xFFE67E22),
                  bgColor: const Color(0xFFFEF5E7),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.upcoming,
                  label: isSwahili ? 'ZINAKUJA' : 'UPCOMING',
                  icon: Icons.calendar_today_rounded,
                  color: const Color(0xFF2980B9),
                  bgColor: const Color(0xFFEBF5FB),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.completedThisMonth,
                  label: isSwahili ? 'ZIMEKAMILIKA' : 'COMPLETED',
                  icon: Icons.check_circle_rounded,
                  color: const Color(0xFF27AE60),
                  bgColor: const Color(0xFFEAFAF1),
                ),
              ],
            ),
          ),

        // ─── Follow-up cards ────────────────
        if (followups.isEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 60),
            child: Center(
              child: Column(
                children: [
                  Icon(Icons.check_circle_rounded,
                      size: 56, color: Colors.green[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna ufuatiliaji uliopangwa'
                        : 'No follow-ups scheduled',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
          )
        else
          ...followups.map((f) => _FollowupCard(
                followup: f,
                isDarkMode: isDarkMode,
                isSwahili: isSwahili,
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

// ─── Follow-up Card ──────────────────────────────

class _FollowupCard extends StatelessWidget {
  final DashboardFollowup followup;
  final bool isDarkMode;
  final bool isSwahili;

  const _FollowupCard({
    required this.followup,
    required this.isDarkMode,
    required this.isSwahili,
  });

  Color get _statusColor {
    if (followup.isOverdue) return const Color(0xFFE74C3C);
    switch (followup.status) {
      case 'completed':
        return const Color(0xFF27AE60);
      case 'pending':
      default:
        return const Color(0xFF2980B9);
    }
  }

  Color get _borderColor {
    if (followup.isOverdue) {
      return const Color(0xFFE74C3C).withValues(alpha: 0.3);
    }
    switch (followup.status) {
      case 'completed':
        return const Color(0xFF27AE60).withValues(alpha: 0.3);
      default:
        return Colors.grey.withValues(alpha: 0.15);
    }
  }

  @override
  Widget build(BuildContext context) {
    DateTime? fDate;
    try {
      if (followup.followupDate != null) {
        fDate = DateTime.parse(followup.followupDate!);
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
            if (fDate != null)
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
                      '${fDate.day}',
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: _statusColor,
                        height: 1,
                      ),
                    ),
                    Text(
                      DateFormat('MMM').format(fDate).toUpperCase(),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: _statusColor,
                      ),
                    ),
                  ],
                ),
              ),
            if (fDate != null) const SizedBox(width: 14),

            // ── Content ──
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Lead name + status
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          followup.leadName ?? '',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      if (followup.isOverdue)
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
                              Icon(Icons.error_rounded,
                                  size: 12, color: _statusColor),
                              const SizedBox(width: 4),
                              Text(
                                isSwahili ? 'Imechelewa' : 'Overdue',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: _statusColor,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),

                  // Client name
                  if (followup.clientName != null) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(
                          Icons.person_rounded,
                          size: 14,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          followup.clientName!,
                          style: TextStyle(
                            fontSize: 13,
                            color: isDarkMode
                                ? Colors.white60
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ],

                  // Details
                  if (followup.details != null &&
                      followup.details!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      followup.details!,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white38
                            : AppColors.textHint,
                      ),
                    ),
                  ],

                  // Next step
                  if (followup.nextStep != null &&
                      followup.nextStep!.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Icon(
                          Icons.arrow_forward_rounded,
                          size: 14,
                          color: _statusColor,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            followup.nextStep!,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: _statusColor,
                            ),
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
