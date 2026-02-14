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
            SizedBox(
              height: 90,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: data.pendingApprovals.items.length,
                separatorBuilder: (_, __) => const SizedBox(width: 10),
                itemBuilder: (context, index) {
                  final item = data.pendingApprovals.items[index];
                  if (item.count == 0) return const SizedBox.shrink();
                  return _ApprovalChip(
                    item: item,
                    isDarkMode: isDarkMode,
                  );
                },
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
            ],
          ),
          const SizedBox(height: 24),

          // ─── Follow-ups Summary ───────────────────────
          _SectionHeader(
            title: isSwahili ? 'Ufuatiliaji' : 'Follow-ups',
          ),
          const SizedBox(height: 12),
          _GlassContainer(
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
          const SizedBox(height: 24),

          // ─── Project Progress ─────────────────────────
          if (data.projectProgress.projects.isNotEmpty) ...[
            _SectionHeader(
              title: isSwahili ? 'Maendeleo ya Miradi' : 'Project Progress',
              badge:
                  '${data.projectProgress.overallPercentage.toStringAsFixed(0)}%',
            ),
            const SizedBox(height: 12),
            // Overall progress bar
            _GlassContainer(
              isDarkMode: isDarkMode,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isSwahili ? 'Jumla' : 'Overall',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                        Text(
                          '${data.projectProgress.completed}/${data.projectProgress.totalActivities}',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: data.projectProgress.overallPercentage / 100,
                        minHeight: 8,
                        backgroundColor: isDarkMode
                            ? Colors.white12
                            : AppColors.primary.withValues(alpha: 0.12),
                        valueColor: const AlwaysStoppedAnimation<Color>(
                            AppColors.primary),
                      ),
                    ),
                    const SizedBox(height: 12),
                    // Per-project progress
                    ...data.projectProgress.projects.map(
                      (p) => _ProjectProgressRow(
                        project: p,
                        isDarkMode: isDarkMode,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],

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

// ─── Approval Chip (horizontal scroll) ───────────

class _ApprovalChip extends StatelessWidget {
  final ApprovalItem item;
  final bool isDarkMode;

  const _ApprovalChip({required this.item, required this.isDarkMode});

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

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: SizedBox(
        width: 110,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Icon(
                    _iconForType(item.icon),
                    size: 20,
                    color: AppColors.warning,
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.warning.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '${item.count}',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.bold,
                        color: AppColors.warning,
                      ),
                    ),
                  ),
                ],
              ),
              const Spacer(),
              Text(
                item.label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
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

// ─── Project Progress Row ────────────────────────

class _ProjectProgressRow extends StatelessWidget {
  final ProjectProgressItem project;
  final bool isDarkMode;

  const _ProjectProgressRow({
    required this.project,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  project.name ?? 'Project',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Text(
                '${project.percentage.toStringAsFixed(0)}%',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white : AppColors.primary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          ClipRRect(
            borderRadius: BorderRadius.circular(3),
            child: LinearProgressIndicator(
              value: project.percentage / 100,
              minHeight: 6,
              backgroundColor: isDarkMode
                  ? Colors.white10
                  : AppColors.primary.withValues(alpha: 0.1),
              valueColor: AlwaysStoppedAnimation<Color>(
                project.overdue > 0 ? AppColors.warning : AppColors.primary,
              ),
            ),
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              _MiniChip('${project.completed} done', AppColors.success),
              const SizedBox(width: 6),
              if (project.inProgress > 0)
                _MiniChip('${project.inProgress} active', AppColors.info),
              if (project.overdue > 0) ...[
                const SizedBox(width: 6),
                _MiniChip('${project.overdue} late', AppColors.error),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _MiniChip extends StatelessWidget {
  final String label;
  final Color color;

  const _MiniChip(this.label, this.color);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 9, fontWeight: FontWeight.w500, color: color),
      ),
    );
  }
}
