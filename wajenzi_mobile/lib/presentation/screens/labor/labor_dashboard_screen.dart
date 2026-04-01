import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final laborDashboardProjectFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);

final _laborDashboardProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final projectId = ref.watch(laborDashboardProjectFilterProvider);
      final response = await api.get(
        '/labor/dashboard',
        queryParameters: {if (projectId != null) 'project_id': projectId},
      );
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class LaborDashboardScreen extends ConsumerWidget {
  const LaborDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final dashboardAsync = ref.watch(_laborDashboardProvider);
    final selectedProject = ref.watch(laborDashboardProjectFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Dashibodi ya Labor' : 'Labor Dashboard'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_laborDashboardProvider),
        child: dashboardAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _LaborErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_laborDashboardProvider),
          ),
          data: (payload) {
            final stats = payload['stats'] as Map<String, dynamic>? ?? const {};
            final projects = (payload['projects'] as List? ?? const [])
                .cast<dynamic>();
            final actions =
                payload['actions_required'] as Map<String, dynamic>? ??
                const {};
            final nearEnd =
                (payload['contracts_nearing_end'] as List? ?? const [])
                    .cast<dynamic>();
            final overdue = (payload['overdue_contracts'] as List? ?? const [])
                .cast<dynamic>();
            final recentRequests =
                (payload['recent_requests'] as List? ?? const [])
                    .cast<dynamic>();
            final recentContracts =
                (payload['recent_contracts'] as List? ?? const [])
                    .cast<dynamic>();
            final recentInspections =
                (payload['recent_inspections'] as List? ?? const [])
                    .cast<dynamic>();

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Mradi' : 'Project',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<int?>(
                        value: selectedProject,
                        isExpanded: true,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF252540)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'Miradi Yote' : 'All Projects',
                            ),
                          ),
                          ...projects.map(
                            (project) => DropdownMenuItem<int?>(
                              value: project['id'] as int?,
                              child: Text(
                                project['project_name'] as String? ?? '-',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          ref
                                  .read(
                                    laborDashboardProjectFilterProvider
                                        .notifier,
                                  )
                                  .state =
                              value;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  children: [
                    SizedBox(
                      width: 160,
                      child: _StatCard(
                        title: isSwahili ? 'Mikataba Hai' : 'Active Contracts',
                        value: '${stats['active_contracts'] ?? 0}',
                        subtitle:
                            '${_formatCurrency(_toDouble(stats['active_contract_value']))} TZS',
                        icon: Icons.assignment_turned_in,
                        color: const Color(0xFF2563EB),
                        isDarkMode: isDarkMode,
                      ),
                    ),
                    SizedBox(
                      width: 160,
                      child: _StatCard(
                        title: isSwahili
                            ? 'Maombi Pending'
                            : 'Pending Requests',
                        value: '${stats['pending_requests'] ?? 0}',
                        subtitle: isSwahili
                            ? 'Yanasubiri idhini'
                            : 'Awaiting approval',
                        icon: Icons.pending_actions,
                        color: const Color(0xFFF59E0B),
                        isDarkMode: isDarkMode,
                      ),
                    ),
                    SizedBox(
                      width: 160,
                      child: _StatCard(
                        title: isSwahili ? 'Malipo Due' : 'Payments Due',
                        value: '${stats['pending_payment_phases'] ?? 0}',
                        subtitle:
                            '${_formatCurrency(_toDouble(stats['pending_payment_amount']))} TZS',
                        icon: Icons.payments_outlined,
                        color: const Color(0xFF0891B2),
                        isDarkMode: isDarkMode,
                      ),
                    ),
                    SizedBox(
                      width: 160,
                      child: _StatCard(
                        title: isSwahili ? 'Mikataba Imekamilika' : 'Completed',
                        value: '${stats['completed_contracts'] ?? 0}',
                        subtitle:
                            '${_formatCurrency(_toDouble(stats['paid_amount']))} TZS',
                        icon: Icons.task_alt,
                        color: const Color(0xFF16A34A),
                        isDarkMode: isDarkMode,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  title: isSwahili ? 'Actions Required' : 'Actions Required',
                  child: Column(
                    children: [
                      _ActionRow(
                        label: isSwahili
                            ? 'Maombi Yanayosubiri'
                            : 'Requests Pending Approval',
                        count: '${actions['pending_requests'] ?? 0}',
                        color: const Color(0xFFF59E0B),
                        isDarkMode: isDarkMode,
                      ),
                      _ActionRow(
                        label: isSwahili
                            ? 'Ukaguzi Pending'
                            : 'Inspections Pending',
                        count: '${actions['pending_inspections'] ?? 0}',
                        color: const Color(0xFF0891B2),
                        isDarkMode: isDarkMode,
                      ),
                      _ActionRow(
                        label: isSwahili ? 'Malipo Due' : 'Payments Due',
                        count: '${actions['payments_due'] ?? 0}',
                        color: const Color(0xFF2563EB),
                        isDarkMode: isDarkMode,
                        isLast: true,
                      ),
                    ],
                  ),
                ),
                if (nearEnd.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _SectionCard(
                    isDarkMode: isDarkMode,
                    title: isSwahili
                        ? 'Mikataba Inakaribia Mwisho'
                        : 'Contracts Nearing End',
                    child: Column(
                      children: nearEnd
                          .map(
                            (item) => _TimelineRow(
                              title: item['contract_number'] as String? ?? '-',
                              subtitle: item['artisan_name'] as String? ?? '-',
                              meta:
                                  '${item['days_remaining'] ?? 0} ${isSwahili ? 'days' : 'days'}',
                              color: const Color(0xFFF59E0B),
                              isDarkMode: isDarkMode,
                            ),
                          )
                          .toList(),
                    ),
                  ),
                ],
                if (overdue.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _SectionCard(
                    isDarkMode: isDarkMode,
                    title: isSwahili
                        ? 'Mikataba Iliyochelewa'
                        : 'Overdue Contracts',
                    child: Column(
                      children: overdue
                          .map(
                            (item) => _TimelineRow(
                              title: item['contract_number'] as String? ?? '-',
                              subtitle: item['artisan_name'] as String? ?? '-',
                              meta:
                                  '${item['days_overdue'] ?? 0} ${isSwahili ? 'days overdue' : 'days overdue'}',
                              color: const Color(0xFFDC2626),
                              isDarkMode: isDarkMode,
                            ),
                          )
                          .toList(),
                    ),
                  ),
                ],
                const SizedBox(height: 16),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  title: isSwahili ? 'Recent Requests' : 'Recent Requests',
                  child: recentRequests.isEmpty
                      ? _EmptyText(
                          text: isSwahili
                              ? 'Hakuna maombi ya karibuni'
                              : 'No recent requests',
                          isDarkMode: isDarkMode,
                        )
                      : Column(
                          children: recentRequests
                              .map(
                                (item) => _StatusListRow(
                                  title:
                                      item['request_number'] as String? ?? '-',
                                  subtitle:
                                      item['artisan_name'] as String? ??
                                      item['work_description'] as String? ??
                                      '-',
                                  trailing: (item['status'] as String? ?? '-')
                                      .toUpperCase(),
                                  badgeColor: _badgeColor(
                                    item['status_badge_class'] as String?,
                                  ),
                                  isDarkMode: isDarkMode,
                                ),
                              )
                              .toList(),
                        ),
                ),
                const SizedBox(height: 16),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  title: isSwahili ? 'Recent Contracts' : 'Recent Contracts',
                  child: recentContracts.isEmpty
                      ? _EmptyText(
                          text: isSwahili
                              ? 'Hakuna mikataba ya karibuni'
                              : 'No recent contracts',
                          isDarkMode: isDarkMode,
                        )
                      : Column(
                          children: recentContracts
                              .map(
                                (item) => _ContractRow(
                                  item: Map<String, dynamic>.from(item as Map),
                                  isDarkMode: isDarkMode,
                                ),
                              )
                              .toList(),
                        ),
                ),
                const SizedBox(height: 16),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  title: isSwahili
                      ? 'Recent Inspections'
                      : 'Recent Inspections',
                  child: recentInspections.isEmpty
                      ? _EmptyText(
                          text: isSwahili
                              ? 'Hakuna ukaguzi wa karibuni'
                              : 'No recent inspections',
                          isDarkMode: isDarkMode,
                        )
                      : Column(
                          children: recentInspections
                              .map(
                                (item) => _InspectionRow(
                                  item: Map<String, dynamic>.from(item as Map),
                                  isDarkMode: isDarkMode,
                                ),
                              )
                              .toList(),
                        ),
                ),
                const SizedBox(height: 90),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final bool isDarkMode;
  final String? title;
  final Widget child;

  const _SectionCard({
    required this.isDarkMode,
    required this.child,
    this.title,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (title != null) ...[
            Text(
              title!,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
          ],
          child,
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final String subtitle;
  final IconData icon;
  final Color color;
  final bool isDarkMode;

  const _StatCard({
    required this.title,
    required this.value,
    required this.subtitle,
    required this.icon,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color),
          const SizedBox(height: 10),
          Text(
            value,
            style: TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            title,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: TextStyle(
              fontSize: 11,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionRow extends StatelessWidget {
  final String label;
  final String count;
  final Color color;
  final bool isDarkMode;
  final bool isLast;

  const _ActionRow({
    required this.label,
    required this.count,
    required this.color,
    required this.isDarkMode,
    this.isLast = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(bottom: isLast ? 0 : 12),
      margin: EdgeInsets.only(bottom: isLast ? 0 : 12),
      decoration: BoxDecoration(
        border: isLast
            ? null
            : Border(
                bottom: BorderSide(
                  color: isDarkMode ? Colors.white12 : Colors.black12,
                ),
              ),
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
          Text(
            count,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _TimelineRow extends StatelessWidget {
  final String title;
  final String subtitle;
  final String meta;
  final Color color;
  final bool isDarkMode;

  const _TimelineRow({
    required this.title,
    required this.subtitle,
    required this.meta,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 10,
            height: 10,
            margin: const EdgeInsets.only(top: 5),
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
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
          const SizedBox(width: 12),
          Flexible(
            child: Text(
              meta,
              textAlign: TextAlign.end,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: color,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusListRow extends StatelessWidget {
  final String title;
  final String subtitle;
  final String trailing;
  final Color badgeColor;
  final bool isDarkMode;

  const _StatusListRow({
    required this.title,
    required this.subtitle,
    required this.trailing,
    required this.badgeColor,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white54
                        : AppColors.textSecondary,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: badgeColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              trailing,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: badgeColor,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ContractRow extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;

  const _ContractRow({required this.item, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  item['contract_number'] as String? ?? '-',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: _badgeColor(
                    item['status_badge_class'] as String?,
                  ).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  (item['status'] as String? ?? '-').toUpperCase(),
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: _badgeColor(item['status_badge_class'] as String?),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            item['artisan_name'] as String? ?? '-',
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 6),
          Wrap(
            spacing: 12,
            runSpacing: 6,
            children: [
              _MiniInfo(
                label: 'Paid',
                value:
                    '${_toDouble(item['payment_progress']).toStringAsFixed(0)}%',
                dark: isDarkMode,
              ),
              _MiniInfo(
                label: 'Amount',
                value: _formatCurrency(_toDouble(item['total_amount'])),
                dark: isDarkMode,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _InspectionRow extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;

  const _InspectionRow({required this.item, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            item['inspection_number'] as String? ?? '-',
            style: TextStyle(
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            '${item['contract_number'] ?? '-'} • ${item['artisan_name'] ?? '-'}',
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _Badge(
                label: (item['inspection_type'] as String? ?? '-')
                    .toUpperCase(),
                color: _badgeColor(item['type_badge_class'] as String?),
              ),
              _Badge(
                label:
                    '${_toDouble(item['completion_percentage']).toStringAsFixed(1)}%',
                color: const Color(0xFF2563EB),
              ),
              _Badge(
                label: (item['result'] as String? ?? '-').toUpperCase(),
                color: _badgeColor(item['result_badge_class'] as String?),
              ),
              _Badge(
                label: (item['status'] as String? ?? '-').toUpperCase(),
                color: _badgeColor(item['status_badge_class'] as String?),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  final String label;
  final Color color;

  const _Badge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _MiniInfo extends StatelessWidget {
  final String label;
  final String value;
  final bool dark;

  const _MiniInfo({
    required this.label,
    required this.value,
    required this.dark,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: dark ? Colors.white54 : AppColors.textSecondary,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: dark ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ],
    );
  }
}

class _EmptyText extends StatelessWidget {
  final String text;
  final bool isDarkMode;

  const _EmptyText({required this.text, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: TextStyle(
        color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
      ),
    );
  }
}

class _LaborErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _LaborErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
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
          '$error',
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

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatCurrency(double amount) {
  return NumberFormat('#,##0.00', 'en_US').format(amount);
}

Color _badgeColor(String? badgeClass) {
  return switch (badgeClass) {
    'success' => const Color(0xFF16A34A),
    'warning' => const Color(0xFFF59E0B),
    'danger' => const Color(0xFFDC2626),
    'info' => const Color(0xFF0891B2),
    _ => const Color(0xFF6B7280),
  };
}
