import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/datasources/remote/staff_dashboard_api.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../providers/staff_dashboard_provider.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(staffDashboardProvider.notifier).fetchDashboard();
    });
  }

  String _formatCurrency(double amount) {
    if (amount >= 1e9) {
      return 'TZS ${(amount / 1e9).toStringAsFixed(1)}B';
    } else if (amount >= 1e6) {
      return 'TZS ${(amount / 1e6).toStringAsFixed(1)}M';
    } else if (amount >= 1e3) {
      return 'TZS ${(amount / 1e3).toStringAsFixed(0)}K';
    }
    final formatter = NumberFormat('#,##0', 'en');
    return 'TZS ${formatter.format(amount)}';
  }

  @override
  Widget build(BuildContext context) {
    final dashboardState = ref.watch(staffDashboardProvider);
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => Scaffold.of(context).openDrawer(),
        ),
        title: Text(isSwahili ? 'Dashibodi' : 'Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {},
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () =>
            ref.read(staffDashboardProvider.notifier).fetchDashboard(),
        child: dashboardState.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _buildErrorView(error, isSwahili),
          data: (data) => _buildContent(
            context, data, user?.name ?? 'User', isSwahili, isDarkMode,
          ),
        ),
      ),
    );
  }

  Widget _buildErrorView(Object error, bool isSwahili) {
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
            onPressed: () =>
                ref.read(staffDashboardProvider.notifier).fetchDashboard(),
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }

  Widget _buildContent(
    BuildContext context,
    StaffDashboardData data,
    String userName,
    bool isSwahili,
    bool isDarkMode,
  ) {
    final firstName = userName.split(' ').first;

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Welcome header
          Text(
            isSwahili ? 'Karibu tena, $firstName' : 'Welcome back, $firstName',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
          ),
          const SizedBox(height: 4),
          Text(
            isSwahili
                ? 'Hapa kuna muhtasari wa biashara yako'
                : "Here's your business overview",
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppColors.textSecondary,
                ),
          ),
          const SizedBox(height: 20),

          // ─── Stat Cards (2x2 grid) ────────────────────
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Mapato (Mwezi)' : 'Revenue (MTD)',
                  value: _formatCurrency(data.stats.totalRevenue),
                  icon: Icons.trending_up_rounded,
                  color: AppColors.success,
                  isDarkMode: isDarkMode,
                  badge: data.stats.revenueChangePercent != 0
                      ? '${data.stats.revenueChangePercent > 0 ? '+' : ''}${data.stats.revenueChangePercent.toStringAsFixed(1)}%'
                      : null,
                  badgeColor: data.stats.revenueChangePercent >= 0
                      ? AppColors.success
                      : AppColors.error,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Miradi Hai' : 'Active Projects',
                  value: '${data.stats.activeProjects}',
                  icon: Icons.folder_open_rounded,
                  color: AppColors.secondary,
                  isDarkMode: isDarkMode,
                  badge: data.stats.newProjectsThisMonth > 0
                      ? '+${data.stats.newProjectsThisMonth}'
                      : null,
                  badgeColor: AppColors.success,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Wafanyakazi' : 'Team Members',
                  value: '${data.stats.teamMembers.total}',
                  icon: Icons.people_rounded,
                  color: AppColors.info,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Bajeti (%)' : 'Budget Used',
                  value: '${data.stats.budgetUtilization.percentage}%',
                  icon: Icons.account_balance_wallet_rounded,
                  color: data.stats.budgetUtilization.percentage > 90
                      ? AppColors.error
                      : AppColors.warning,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // ─── Pending Approvals ────────────────────────
          if (data.pendingApprovals.total > 0) ...[
            _SectionHeader(
              title: isSwahili ? 'Idhini Zinazosubiri' : 'Pending Approvals',
              badge: '${data.pendingApprovals.total}',
            ),
            const SizedBox(height: 12),
            _GlassContainer(
              isDarkMode: isDarkMode,
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Column(
                  children: data.pendingApprovals.items
                      .where((item) => item.count > 0)
                      .map((item) => _ApprovalRow(
                            item: item,
                            isDarkMode: isDarkMode,
                            isSwahili: isSwahili,
                          ))
                      .toList(),
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],

          // ─── Activities & Invoices Summary Row ────────
          Row(
            children: [
              Expanded(
                child: GestureDetector(
                  onTap: () => context.push('/dashboard/activities'),
                  child: _MiniSummaryCard(
                    title: isSwahili ? 'Shughuli' : 'Activities',
                    icon: Icons.assignment_rounded,
                    color: AppColors.secondary,
                    isDarkMode: isDarkMode,
                    rows: [
                      _SummaryRow(
                        isSwahili ? 'Zilizochelewa' : 'Overdue',
                        data.activitiesSummary.overdue,
                        AppColors.error,
                      ),
                      _SummaryRow(
                        isSwahili ? 'Zinaendelea' : 'In Progress',
                        data.activitiesSummary.inProgress,
                        AppColors.info,
                      ),
                      _SummaryRow(
                        isSwahili ? 'Zinasubiri' : 'Pending',
                        data.activitiesSummary.pending,
                        AppColors.warning,
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: GestureDetector(
                  onTap: () => context.push('/dashboard/invoices'),
                  child: _MiniSummaryCard(
                    title: isSwahili ? 'Ankara' : 'Invoices',
                    icon: Icons.receipt_long_rounded,
                    color: AppColors.warning,
                    isDarkMode: isDarkMode,
                    rows: [
                      _SummaryRow(
                        isSwahili ? 'Zilizochelewa' : 'Overdue',
                        data.invoicesSummary.overdue,
                        AppColors.error,
                      ),
                      _SummaryRow(
                        isSwahili ? 'Leo' : 'Due Today',
                        data.invoicesSummary.dueToday,
                        AppColors.warning,
                      ),
                      _SummaryRow(
                        isSwahili ? 'Zinakuja' : 'Upcoming',
                        data.invoicesSummary.upcoming,
                        AppColors.info,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // ─── Follow-ups Summary ───────────────────────
          _SectionHeader(
            title: isSwahili ? 'Ufuatiliaji' : 'Follow-ups',
          ),
          const SizedBox(height: 12),
          GestureDetector(
            onTap: () => context.push('/dashboard/followups'),
            child: _GlassContainer(
              isDarkMode: isDarkMode,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    _FollowupPill(
                      label: isSwahili ? 'Zilizochelewa' : 'Overdue',
                      count: data.followupSummary.overdue,
                      color: AppColors.error,
                    ),
                    _FollowupPill(
                      label: isSwahili ? 'Leo' : 'Today',
                      count: data.followupSummary.today,
                      color: AppColors.warning,
                    ),
                    _FollowupPill(
                      label: isSwahili ? 'Zinakuja' : 'Upcoming',
                      count: data.followupSummary.upcoming,
                      color: AppColors.info,
                    ),
                    _FollowupPill(
                      label: isSwahili ? 'Zimekamilika' : 'Done',
                      count: data.followupSummary.completedThisMonth,
                      color: AppColors.success,
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 24),

          // ─── Project Progress ─────────────────────────
          if (data.projectProgress.projects.isNotEmpty) ...[
            _SectionHeader(
              title: isSwahili ? 'Maendeleo ya Miradi' : 'Project Progress',
              badge: '${data.projectProgress.projects.length} ${isSwahili ? 'Hai' : 'Active'}',
            ),
            const SizedBox(height: 12),
            // Overall ring + status counts
            _GlassContainer(
              isDarkMode: isDarkMode,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    // Circular progress ring
                    SizedBox(
                      width: 110,
                      height: 110,
                      child: CustomPaint(
                        painter: _RingPainter(
                          percentage: data.projectProgress.overallPercentage / 100,
                          isDarkMode: isDarkMode,
                        ),
                        child: Center(
                          child: Text(
                            '${data.projectProgress.overallPercentage.toStringAsFixed(0)}%',
                            style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                              color: isDarkMode ? Colors.white : AppColors.textPrimary,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    // Status count grid
                    Expanded(
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: _StatusCountBox(
                                  count: data.projectProgress.completed,
                                  label: isSwahili ? 'Zimekamilika' : 'Completed',
                                  color: const Color(0xFF27AE60),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: _StatusCountBox(
                                  count: data.projectProgress.inProgress,
                                  label: isSwahili ? 'Zinaendelea' : 'In Progress',
                                  color: const Color(0xFF3B82F6),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: _StatusCountBox(
                                  count: data.projectProgress.pending,
                                  label: isSwahili ? 'Zinasubiri' : 'Pending',
                                  color: const Color(0xFFF59E0B),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: _StatusCountBox(
                                  count: data.projectProgress.overdue,
                                  label: isSwahili ? 'Zilizochelewa' : 'Overdue',
                                  color: const Color(0xFFEF4444),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            // Per-project cards
            ...data.projectProgress.projects.map(
              (p) => _ProjectProgressCard(
                project: p,
                isDarkMode: isDarkMode,
              ),
            ),
            const SizedBox(height: 24),
          ],

          // ─── Calendar ─────────────────────────────────
          _SectionHeader(
            title: isSwahili ? 'Kalenda' : 'Calendar',
          ),
          const SizedBox(height: 12),
          _CalendarWidget(isDarkMode: isDarkMode, isSwahili: isSwahili),
          const SizedBox(height: 24),

          // Bottom spacing for nav bar
          const SizedBox(height: 80),
        ],
      ),
    );
  }
}

// ─── Glass Container ─────────────────────────────

class _GlassContainer extends StatelessWidget {
  final Widget child;
  final bool isDarkMode;
  final EdgeInsetsGeometry? margin;

  const _GlassContainer({
    required this.child,
    required this.isDarkMode,
    this.margin,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.15)
              : Colors.white.withValues(alpha: 0.6),
        ),
        boxShadow: [
          BoxShadow(
            color:
                Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.06),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: isDarkMode
                    ? [
                        Colors.white.withValues(alpha: 0.08),
                        Colors.white.withValues(alpha: 0.04),
                      ]
                    : [
                        Colors.white.withValues(alpha: 0.75),
                        Colors.white.withValues(alpha: 0.55),
                      ],
              ),
            ),
            child: child,
          ),
        ),
      ),
    );
  }
}

// ─── Stat Card ───────────────────────────────────

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;
  final bool isDarkMode;
  final String? badge;
  final Color? badgeColor;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
    required this.isDarkMode,
    this.badge,
    this.badgeColor,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: isDarkMode
                          ? Colors.white70
                          : AppColors.textSecondary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(icon, size: 18, color: color),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Flexible(
                  child: Text(
                    value,
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (badge != null) ...[
                  const SizedBox(width: 6),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: (badgeColor ?? AppColors.success)
                          .withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      badge!,
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: badgeColor ?? AppColors.success,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Section Header ──────────────────────────────

class _SectionHeader extends StatelessWidget {
  final String title;
  final String? badge;

  const _SectionHeader({required this.title, this.badge});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
        if (badge != null) ...[
          const SizedBox(width: 8),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              badge!,
              style: const TextStyle(
                color: AppColors.primary,
                fontWeight: FontWeight.bold,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ],
    );
  }
}

// ─── Approval Row (vertical list) ────────────────

class _ApprovalRow extends StatelessWidget {
  final ApprovalItem item;
  final bool isDarkMode;
  final bool isSwahili;

  const _ApprovalRow({
    required this.item,
    required this.isDarkMode,
    required this.isSwahili,
  });

  IconData _iconForType(String icon) {
    switch (icon) {
      case 'inventory':
        return Icons.inventory_2_rounded;
      case 'receipt':
        return Icons.receipt_rounded;
      case 'payments':
        return Icons.payments_rounded;
      case 'location_on':
        return Icons.location_on_rounded;
      case 'description':
        return Icons.description_rounded;
      default:
        return Icons.pending_actions_rounded;
    }
  }

  Color _colorForType(String icon) {
    switch (icon) {
      case 'inventory':
        return const Color(0xFF7C3AED); // purple
      case 'receipt':
        return const Color(0xFF3B82F6); // blue
      case 'payments':
        return const Color(0xFFEF4444); // red
      case 'location_on':
        return const Color(0xFF10B981); // green
      case 'description':
        return const Color(0xFFF59E0B); // amber
      default:
        return const Color(0xFF6B7280);
    }
  }

  @override
  Widget build(BuildContext context) {
    final color = _colorForType(item.icon);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.05)
              : Colors.grey.withValues(alpha: 0.04),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isDarkMode
                ? Colors.white.withValues(alpha: 0.08)
                : Colors.grey.withValues(alpha: 0.12),
          ),
        ),
        child: Row(
          children: [
            // Icon box
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: color.withValues(alpha: isDarkMode ? 0.2 : 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                _iconForType(item.icon),
                size: 22,
                color: color,
              ),
            ),
            const SizedBox(width: 12),
            // Label + subtitle
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.label,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    isSwahili ? 'Inahitaji umakini wako' : 'Requires your attention',
                    style: TextStyle(
                      fontSize: 12,
                      color: isDarkMode
                          ? Colors.white54
                          : AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            // Count badge
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: const Color(0xFFEF4444),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Center(
                child: Text(
                  '${item.count}',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Mini Summary Card (Activities / Invoices) ───

class _SummaryRow {
  final String label;
  final int count;
  final Color color;

  _SummaryRow(this.label, this.count, this.color);
}

class _MiniSummaryCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final Color color;
  final bool isDarkMode;
  final List<_SummaryRow> rows;

  const _MiniSummaryCard({
    required this.title,
    required this.icon,
    required this.color,
    required this.isDarkMode,
    required this.rows,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 18, color: color),
                const SizedBox(width: 6),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ...rows.map((row) => Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        row.label,
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white60
                              : AppColors.textSecondary,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: row.count > 0
                              ? row.color.withValues(alpha: 0.12)
                              : (isDarkMode ? Colors.white10 : Colors.grey[100]),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          '${row.count}',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: row.count > 0
                                ? row.color
                                : AppColors.textSecondary,
                          ),
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }
}

// ─── Follow-up Pill ──────────────────────────────

class _FollowupPill extends StatelessWidget {
  final String label;
  final int count;
  final Color color;

  const _FollowupPill({
    required this.label,
    required this.count,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: count > 0
                  ? color.withValues(alpha: 0.12)
                  : Colors.grey.withValues(alpha: 0.08),
              shape: BoxShape.circle,
            ),
            alignment: Alignment.center,
            child: Text(
              '$count',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: count > 0 ? color : AppColors.textSecondary,
              ),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(
              fontSize: 10,
              color: AppColors.textSecondary,
            ),
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

// ─── Ring Painter ────────────────────────────────

class _RingPainter extends CustomPainter {
  final double percentage;
  final bool isDarkMode;

  _RingPainter({required this.percentage, required this.isDarkMode});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2 - 8;
    const strokeWidth = 10.0;

    // Track
    final trackPaint = Paint()
      ..color = isDarkMode ? Colors.white12 : const Color(0xFFE8EDF2)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;
    canvas.drawCircle(center, radius, trackPaint);

    // Progress arc
    if (percentage > 0) {
      final progressPaint = Paint()
        ..color = const Color(0xFF3B82F6)
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round;
      const startAngle = -1.5708; // -PI/2 (top)
      final sweepAngle = 2 * 3.14159265 * percentage;
      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        startAngle,
        sweepAngle,
        false,
        progressPaint,
      );
    }
  }

  @override
  bool shouldRepaint(_RingPainter oldDelegate) =>
      oldDelegate.percentage != percentage || oldDelegate.isDarkMode != isDarkMode;
}

// ─── Status Count Box ────────────────────────────

class _StatusCountBox extends StatelessWidget {
  final int count;
  final String label;
  final Color color;
  final bool isDarkMode;

  const _StatusCountBox({
    required this.count,
    required this.label,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
      decoration: BoxDecoration(
        color: isDarkMode
            ? color.withValues(alpha: 0.08)
            : color.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Column(
        children: [
          Text(
            '$count',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 9,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white60 : AppColors.textSecondary,
            ),
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

// ─── Project Progress Card ───────────────────────

class _ProjectProgressCard extends StatelessWidget {
  final ProjectProgressItem project;
  final bool isDarkMode;

  const _ProjectProgressCard({
    required this.project,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final total = project.completed + project.inProgress + project.pending + project.overdue;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: _GlassContainer(
        isDarkMode: isDarkMode,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Project name + percentage badge
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          project.name ?? 'Project',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (project.leadName != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            project.leadName!,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF27AE60).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '${project.percentage.toStringAsFixed(0)}%',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF27AE60),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              // Multi-segment progress bar
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: SizedBox(
                  height: 8,
                  child: total > 0
                      ? Row(
                          children: [
                            if (project.completed > 0)
                              Expanded(
                                flex: project.completed,
                                child: Container(color: const Color(0xFF27AE60)),
                              ),
                            if (project.inProgress > 0)
                              Expanded(
                                flex: project.inProgress,
                                child: Container(color: const Color(0xFF3B82F6)),
                              ),
                            if (project.pending > 0)
                              Expanded(
                                flex: project.pending,
                                child: Container(
                                  color: isDarkMode
                                      ? Colors.white12
                                      : const Color(0xFFE8EDF2),
                                ),
                              ),
                            if (project.overdue > 0)
                              Expanded(
                                flex: project.overdue,
                                child: Container(color: const Color(0xFFEF4444)),
                              ),
                          ],
                        )
                      : Container(
                          color: isDarkMode
                              ? Colors.white12
                              : const Color(0xFFE8EDF2),
                        ),
                ),
              ),
              const SizedBox(height: 8),
              // Status icon counts
              Row(
                children: [
                  _StatusIconCount(
                    icon: Icons.check_circle,
                    count: project.completed,
                    color: const Color(0xFF27AE60),
                  ),
                  const SizedBox(width: 12),
                  _StatusIconCount(
                    icon: Icons.sync_rounded,
                    count: project.inProgress,
                    color: const Color(0xFF3B82F6),
                  ),
                  const SizedBox(width: 12),
                  _StatusIconCount(
                    icon: Icons.radio_button_unchecked,
                    count: project.pending,
                    color: isDarkMode ? Colors.white38 : const Color(0xFF9CA3AF),
                  ),
                  if (project.overdue > 0) ...[
                    const SizedBox(width: 12),
                    _StatusIconCount(
                      icon: Icons.warning_rounded,
                      count: project.overdue,
                      color: const Color(0xFFEF4444),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusIconCount extends StatelessWidget {
  final IconData icon;
  final int count;
  final Color color;

  const _StatusIconCount({
    required this.icon,
    required this.count,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 3),
        Text(
          '$count',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: color,
          ),
        ),
      ],
    );
  }
}

// ─── Calendar Widget ─────────────────────────────

class _CalendarWidget extends ConsumerStatefulWidget {
  final bool isDarkMode;
  final bool isSwahili;

  const _CalendarWidget({
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  ConsumerState<_CalendarWidget> createState() => _CalendarWidgetState();
}

class _CalendarWidgetState extends ConsumerState<_CalendarWidget> {
  late int _month;
  late int _year;
  CalendarData? _calendarData;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _month = now.month;
    _year = now.year;
    _fetchCalendar();
  }

  Future<void> _fetchCalendar() async {
    setState(() => _loading = true);
    try {
      final api = ref.read(staffDashboardApiProvider);
      final data = await api.fetchCalendar(month: _month, year: _year);
      if (mounted) setState(() { _calendarData = data; _loading = false; });
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _prevMonth() {
    setState(() {
      _month--;
      if (_month < 1) { _month = 12; _year--; }
    });
    _fetchCalendar();
  }

  void _nextMonth() {
    setState(() {
      _month++;
      if (_month > 12) { _month = 1; _year++; }
    });
    _fetchCalendar();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = widget.isDarkMode;
    final isSw = widget.isSwahili;
    final now = DateTime.now();
    final firstDay = DateTime(_year, _month, 1);
    final daysInMonth = DateTime(_year, _month + 1, 0).day;
    final startWeekday = firstDay.weekday % 7; // Sunday = 0

    final monthName = DateFormat('MMMM yyyy').format(firstDay);

    return _GlassContainer(
      isDarkMode: isDark,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            // ── Month navigation row ──
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                GestureDetector(
                  onTap: _prevMonth,
                  child: Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white10 : Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.chevron_left_rounded,
                        size: 20,
                        color: isDark ? Colors.white70 : AppColors.textPrimary),
                  ),
                ),
                const SizedBox(width: 12),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  decoration: BoxDecoration(
                    color: const Color(0xFF3B82F6),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    monthName,
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                GestureDetector(
                  onTap: _nextMonth,
                  child: Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white10 : Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.chevron_right_rounded,
                        size: 20,
                        color: isDark ? Colors.white70 : AppColors.textPrimary),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // ── Day headers ──
            Row(
              children: ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT']
                  .map((d) => Expanded(
                        child: Center(
                          child: Text(
                            d,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: isDark ? Colors.white38 : AppColors.textHint,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),
                      ))
                  .toList(),
            ),
            const SizedBox(height: 8),

            // ── Calendar grid ──
            if (_loading)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: Center(
                    child: SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )),
              )
            else
              _buildGrid(isDark, now, startWeekday, daysInMonth),

            const SizedBox(height: 12),
            // ── Legend ──
            Wrap(
              spacing: 12,
              runSpacing: 6,
              alignment: WrapAlignment.center,
              children: [
                _LegendDot(
                    color: const Color(0xFF3B82F6),
                    label: isSw ? 'Leo' : 'Today'),
                _LegendDot(
                    color: const Color(0xFF10B981),
                    label: isSw ? 'Ufuatiliaji' : 'Follow-up'),
                _LegendDot(
                    color: const Color(0xFF60A5FA),
                    label: isSw ? 'Shughuli' : 'Activity'),
                _LegendDot(
                    color: const Color(0xFFF59E0B),
                    label: isSw ? 'Ankara' : 'Invoice'),
                _LegendDot(
                    color: const Color(0xFFEF4444),
                    label: isSw ? 'Zilizochelewa' : 'Overdue'),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _showDayEvents(
    BuildContext context,
    String dateStr,
    DateTime date,
    CalendarDayEvents dayEvents,
    bool isDark,
  ) {
    final isSw = widget.isSwahili;
    final allEvents = [
      ...dayEvents.followups,
      ...dayEvents.activities,
      ...dayEvents.invoices,
    ];
    final formattedDate = DateFormat('dd MMM yyyy').format(date);

    showModalBottomSheet(
      context: context,
      useRootNavigator: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Handle bar
            Container(
              margin: const EdgeInsets.only(top: 10),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: isDark ? Colors.white24 : Colors.grey[300],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 12),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFF2C3E50),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      formattedDate,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF27AE60),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '${allEvents.length} ${isSw ? 'matukio' : 'events'}',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            // Event groups
            Flexible(
              child: ListView(
                shrinkWrap: true,
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
                children: [
                  if (dayEvents.followups.isNotEmpty) ...[
                    _EventGroupHeader(
                      icon: Icons.phone_callback_rounded,
                      label: isSw ? 'UFUATILIAJI' : 'FOLLOW-UPS',
                      color: const Color(0xFF10B981),
                      isDarkMode: isDark,
                    ),
                    ...dayEvents.followups.map((e) => _EventRow(
                          event: e,
                          color: const Color(0xFF10B981),
                          isDarkMode: isDark,
                        )),
                    const SizedBox(height: 12),
                  ],
                  if (dayEvents.activities.isNotEmpty) ...[
                    _EventGroupHeader(
                      icon: Icons.assignment_rounded,
                      label: isSw ? 'SHUGHULI' : 'ACTIVITIES',
                      color: const Color(0xFF60A5FA),
                      isDarkMode: isDark,
                    ),
                    ...dayEvents.activities.map((e) => _EventRow(
                          event: e,
                          color: const Color(0xFF60A5FA),
                          isDarkMode: isDark,
                        )),
                    const SizedBox(height: 12),
                  ],
                  if (dayEvents.invoices.isNotEmpty) ...[
                    _EventGroupHeader(
                      icon: Icons.receipt_long_rounded,
                      label: isSw ? 'ANKARA ZINAZOTAKIWA' : 'INVOICES DUE',
                      color: const Color(0xFFF59E0B),
                      isDarkMode: isDark,
                    ),
                    ...dayEvents.invoices.map((e) => _EventRow(
                          event: e,
                          color: const Color(0xFFF59E0B),
                          isDarkMode: isDark,
                        )),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGrid(bool isDark, DateTime now, int startWeekday, int daysInMonth) {
    final events = _calendarData?.events ?? {};
    final rows = <Widget>[];
    int day = 1 - startWeekday;

    while (day <= daysInMonth) {
      final cells = <Widget>[];
      for (int col = 0; col < 7; col++) {
        if (day < 1 || day > daysInMonth) {
          cells.add(const Expanded(child: SizedBox(height: 44)));
        } else {
          final dateStr =
              '$_year-${_month.toString().padLeft(2, '0')}-${day.toString().padLeft(2, '0')}';
          final dayEvents = events[dateStr];
          final isToday =
              day == now.day && _month == now.month && _year == now.year;

          final capturedDay = day;
          cells.add(Expanded(
            child: GestureDetector(
              onTap: (dayEvents != null && !dayEvents.isEmpty)
                  ? () => _showDayEvents(
                        context,
                        dateStr,
                        DateTime(_year, _month, capturedDay),
                        dayEvents,
                        isDark,
                      )
                  : null,
              child: _CalendarDay(
                day: capturedDay,
                isToday: isToday,
                events: dayEvents,
                isDarkMode: isDark,
              ),
            ),
          ));
        }
        day++;
      }
      rows.add(Row(children: cells));
    }
    return Column(children: rows);
  }
}

class _CalendarDay extends StatelessWidget {
  final int day;
  final bool isToday;
  final CalendarDayEvents? events;
  final bool isDarkMode;

  const _CalendarDay({
    required this.day,
    required this.isToday,
    this.events,
    required this.isDarkMode,
  });

  Color? get _borderColor {
    if (events == null || events!.isEmpty) return null;
    // Priority: overdue (red) > followup (green) > activity (blue) > invoice (orange)
    if (events!.followups.any((e) => e.status == 'overdue')) {
      return const Color(0xFFEF4444);
    }

    if (events!.invoices.any((e) => e.status == 'overdue')) {
      return const Color(0xFFEF4444);
    }
    if (events!.followups.isNotEmpty) return const Color(0xFF10B981);
    if (events!.activities.isNotEmpty) return const Color(0xFF60A5FA);
    if (events!.invoices.isNotEmpty) return const Color(0xFFF59E0B);
    return null;
  }

  List<Color> get _dots {
    if (events == null || events!.isEmpty) return [];
    final dots = <Color>[];
    if (events!.followups.isNotEmpty) dots.add(const Color(0xFF10B981));
    if (events!.activities.isNotEmpty) dots.add(const Color(0xFF60A5FA));
    if (events!.invoices.isNotEmpty) dots.add(const Color(0xFFF59E0B));
    return dots;
  }

  @override
  Widget build(BuildContext context) {
    final border = _borderColor;
    final dots = _dots;

    return Container(
      height: 44,
      margin: const EdgeInsets.all(1),
      decoration: BoxDecoration(
        color: isToday
            ? const Color(0xFF3B82F6)
            : isDarkMode
                ? Colors.transparent
                : Colors.transparent,
        borderRadius: BorderRadius.circular(8),
        border: !isToday && border != null
            ? Border.all(color: border, width: 1.5)
            : null,
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(
            '$day',
            style: TextStyle(
              fontSize: 14,
              fontWeight: isToday ? FontWeight.bold : FontWeight.w500,
              color: isToday
                  ? Colors.white
                  : isDarkMode
                      ? Colors.white70
                      : AppColors.textPrimary,
            ),
          ),
          if (dots.isNotEmpty && !isToday)
            Padding(
              padding: const EdgeInsets.only(top: 2),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: dots
                    .take(3)
                    .map((c) => Container(
                          width: 5,
                          height: 5,
                          margin: const EdgeInsets.symmetric(horizontal: 1),
                          decoration: BoxDecoration(
                            color: c,
                            shape: BoxShape.circle,
                          ),
                        ))
                    .toList(),
              ),
            ),
        ],
      ),
    );
  }
}

class _LegendDot extends StatelessWidget {
  final Color color;
  final String label;

  const _LegendDot({required this.color, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 4),
        Text(
          label,
          style: const TextStyle(fontSize: 10, color: AppColors.textSecondary),
        ),
      ],
    );
  }
}

// ─── Calendar Event Group Header ─────────────────

class _EventGroupHeader extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final bool isDarkMode;

  const _EventGroupHeader({
    required this.icon,
    required this.label,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 16, color: isDarkMode ? Colors.white54 : Colors.grey),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.8,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Calendar Event Row ──────────────────────────

class _EventRow extends StatelessWidget {
  final CalendarEvent event;
  final Color color;
  final bool isDarkMode;

  const _EventRow({
    required this.event,
    required this.color,
    required this.isDarkMode,
  });

  Color get _dotColor {
    if (event.status == 'overdue' || event.status == 'completed') {
      return event.status == 'overdue'
          ? const Color(0xFFEF4444)
          : const Color(0xFF27AE60);
    }
    return color;
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6, left: 4),
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(
              color: _dotColor,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              event.name,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
          if (event.status != null)
            Container(
              margin: const EdgeInsets.only(left: 8),
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: _dotColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                event.status!,
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  color: _dotColor,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

