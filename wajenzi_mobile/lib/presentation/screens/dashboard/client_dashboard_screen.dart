import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/auth_provider.dart';
import '../../providers/client_dashboard_provider.dart';
import '../../providers/settings_provider.dart';
import '../../../data/datasources/remote/client_api.dart';

class ClientDashboardScreen extends ConsumerStatefulWidget {
  const ClientDashboardScreen({super.key});

  @override
  ConsumerState<ClientDashboardScreen> createState() => _ClientDashboardScreenState();
}

class _ClientDashboardScreenState extends ConsumerState<ClientDashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(clientDashboardProvider.notifier).fetchDashboard();
    });
  }

  String _formatCurrency(double amount) {
    if (amount >= 1e9) {
      return 'TZS ${(amount / 1e9).toStringAsFixed(1)}B';
    } else if (amount >= 1e6) {
      return 'TZS ${(amount / 1e6).toStringAsFixed(1)}M';
    } else {
      final formatter = NumberFormat('#,##0', 'en');
      return 'TZS ${formatter.format(amount)}';
    }
  }

  String _formatFullCurrency(double amount) {
    final formatter = NumberFormat('#,##0', 'en');
    return 'TZS ${formatter.format(amount)}';
  }

  @override
  Widget build(BuildContext context) {
    final dashboardState = ref.watch(clientDashboardProvider);
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    final firstName = user?.name.split(' ').first ?? 'User';

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
        onRefresh: () => ref.read(clientDashboardProvider.notifier).fetchDashboard(),
        child: dashboardState.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _buildErrorView(error, isSwahili),
          data: (data) => _buildDashboardContent(
            context, data, firstName, isSwahili, isDarkMode,
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
                ref.read(clientDashboardProvider.notifier).fetchDashboard(),
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }

  Widget _buildDashboardContent(
    BuildContext context,
    ClientDashboardData data,
    String firstName,
    bool isSwahili,
    bool isDarkMode,
  ) {
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
                ? 'Hapa kuna muhtasari wa miradi yako ya ujenzi'
                : "Here's an overview of your construction projects",
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppColors.textSecondary,
                ),
          ),
          const SizedBox(height: 20),

          // Stat cards 2x2
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Miradi Yote' : 'Total Projects',
                  value: '${data.totalProjects}',
                  icon: Icons.folder_rounded,
                  color: AppColors.secondary,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Miradi Hai' : 'Active Projects',
                  value: '${data.activeProjects}',
                  icon: Icons.engineering_rounded,
                  color: AppColors.success,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Thamani ya Mkataba' : 'Contract Value',
                  value: _formatCurrency(data.totalContractValue),
                  icon: Icons.account_balance_rounded,
                  color: AppColors.info,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  title: isSwahili ? 'Jumla Ankara' : 'Total Invoiced',
                  value: _formatCurrency(data.totalInvoiced),
                  icon: Icons.receipt_long_rounded,
                  color: AppColors.warning,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Your Projects header with count badge
          Row(
            children: [
              Text(
                isSwahili ? 'Miradi Yako' : 'Your Projects',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${data.projects.length}',
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Project cards
          if (data.projects.isEmpty)
            _GlassContainer(
              isDarkMode: isDarkMode,
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Center(
                  child: Text(
                    isSwahili ? 'Hakuna miradi bado' : 'No projects yet',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ),
              ),
            )
          else
            ...data.projects.map((project) => GestureDetector(
                  onTap: () => context.push(
                    '/project/${project.id}',
                    extra: project.projectName,
                  ),
                  child: _ProjectCard(
                    project: project,
                    isSwahili: isSwahili,
                    isDarkMode: isDarkMode,
                    formatCurrency: _formatFullCurrency,
                  ),
                )),

          // Bottom spacing for nav bar
          const SizedBox(height: 100),
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
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.06),
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

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
    required this.isDarkMode,
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
                      color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
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
            Text(
              value,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Project Card ────────────────────────────────

class _ProjectCard extends StatelessWidget {
  final ClientProject project;
  final bool isSwahili;
  final bool isDarkMode;
  final String Function(double) formatCurrency;

  const _ProjectCard({
    required this.project,
    required this.isSwahili,
    required this.isDarkMode,
    required this.formatCurrency,
  });

  Color _statusColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'active':
      case 'in_progress':
        return AppColors.success;
      case 'completed':
        return AppColors.info;
      case 'on_hold':
      case 'pending':
        return AppColors.warning;
      case 'cancelled':
        return AppColors.error;
      default:
        return AppColors.draft;
    }
  }

  String _statusLabel(String? status) {
    if (status == null) return '';
    return status.replaceAll('_', ' ').split(' ').map((w) {
      if (w.isEmpty) return w;
      return '${w[0].toUpperCase()}${w.substring(1)}';
    }).join(' ');
  }

  String _formatDate(String? date) {
    if (date == null) return '—';
    try {
      final parsed = DateTime.parse(date);
      return DateFormat('dd MMM yyyy').format(parsed);
    } catch (_) {
      return date;
    }
  }

  @override
  Widget build(BuildContext context) {
    final statusColor = _statusColor(project.status);

    return _GlassContainer(
      isDarkMode: isDarkMode,
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Name + status badge row
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        project.projectName,
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      if (project.documentNumber != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          project.documentNumber!,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    _statusLabel(project.status),
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: statusColor,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Date range
            Row(
              children: [
                Icon(Icons.calendar_today_rounded,
                    size: 14, color: AppColors.textSecondary),
                const SizedBox(width: 6),
                Text(
                  '${_formatDate(project.startDate)} — ${_formatDate(project.expectedEndDate)}',
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Contract value
            Row(
              children: [
                Icon(Icons.payments_rounded,
                    size: 14, color: AppColors.textSecondary),
                const SizedBox(width: 6),
                Text(
                  formatCurrency(project.contractValue),
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Count chips
            Wrap(
              spacing: 8,
              runSpacing: 6,
              children: [
                _CountChip(
                  icon: Icons.list_alt_rounded,
                  label: isSwahili ? 'BOQ' : 'BOQ',
                  count: project.boqsCount,
                ),
                _CountChip(
                  icon: Icons.receipt_rounded,
                  label: isSwahili ? 'Ankara' : 'Invoices',
                  count: project.invoicesCount,
                ),
                _CountChip(
                  icon: Icons.description_rounded,
                  label: isSwahili ? 'Ripoti' : 'Reports',
                  count: project.dailyReportsCount,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Count Chip ──────────────────────────────────

class _CountChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final int count;

  const _CountChip({
    required this.icon,
    required this.label,
    required this.count,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.primary),
          const SizedBox(width: 4),
          Text(
            '$count $label',
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }
}
