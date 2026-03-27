import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../widgets/common/loading_widget.dart';

final _settingsCatalogProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/settings/catalog');
  final payload = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final data = payload['data'] is Map<String, dynamic>
      ? payload['data'] as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['settings'] as List? ?? const [];
});

class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final settingsAsync = ref.watch(_settingsCatalogProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Settings'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_settingsCatalogProvider),
        child: settingsAsync.when(
          loading: () => const LoadingWidget(message: 'Loading settings...'),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              const Text(
                'Failed to load settings',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              Text(
                error.toString(),
                textAlign: TextAlign.center,
              ),
            ],
          ),
          data: (settingsPayload) {
            final settings = settingsPayload
                .whereType<Map>()
                .map((item) => Map<String, dynamic>.from(item))
                .where((item) {
                  if (_query.trim().isEmpty) return true;
                  final q = _query.toLowerCase();
                  return (item['name'] ?? '').toString().toLowerCase().contains(q) ||
                      (item['route'] ?? '').toString().toLowerCase().contains(q);
                })
                .toList();

            return CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF4461E9), Color(0xFF32CD32)],
                        begin: Alignment.centerLeft,
                        end: Alignment.centerRight,
                      ),
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Settings',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'Manage the same settings modules available on web.',
                          style: TextStyle(
                            color: Colors.white70,
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                    child: TextField(
                      controller: _searchController,
                      onChanged: (value) => setState(() => _query = value),
                      textInputAction: TextInputAction.search,
                      decoration: InputDecoration(
                        hintText: 'Search settings...',
                        prefixIcon: const Icon(Icons.search),
                        filled: true,
                        fillColor: const Color(0xFFF4F6F8),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                  ),
                ),
                if (settings.isEmpty)
                  const SliverFillRemaining(
                    hasScrollBody: false,
                    child: _EmptySettingsView(),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                    sliver: SliverLayoutBuilder(
                      builder: (context, constraints) {
                        final width = constraints.crossAxisExtent;
                        final crossAxisCount = width >= 1100
                            ? 4
                            : width >= 760
                            ? 3
                            : 2;

                        return SliverGrid(
                          delegate: SliverChildBuilderDelegate(
                            (context, index) {
                              final item = settings[index];
                              return _SettingsModuleCard(
                                title: (item['name'] ?? '').toString(),
                                icon: _mapSettingsIcon((item['route'] ?? '').toString()),
                                onTap: () => _openSetting(item),
                              );
                            },
                            childCount: settings.length,
                          ),
                          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: crossAxisCount,
                            mainAxisSpacing: 14,
                            crossAxisSpacing: 14,
                            childAspectRatio: width >= 760 ? 1.28 : 0.98,
                          ),
                        );
                      },
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }

  String? _resolveSettingsDestination(Map<String, dynamic> item) {
    final route = (item['route'] ?? '').toString();
    const map = <String, String>{
      'hr_settings_allowances': '/allowances',
      'hr_settings_process_approval_flows': '/settings/process-approval-flows',
      'hr_settings_process_approval_flow_steps': '/settings/process-approval-flow-steps',
      'allowance_subscriptions': '/settings/allowance-subscriptions',
      'hr_settings_deduction_settings': '/settings/deduction-settings',
      'hr_settings_service_interesteds': '/settings/service-interesteds',
      'hr_settings_lead_statuses': '/settings/lead-statuses',
      'hr_settings_staff_salary': '/staff-salaries',
      'hr_settings_advance_salary': '/advance-salaries',
      'hr_settings_staff_loan': '/staff-loans',
      'hr_settings_deductions': '/deductions',
      'hr_settings_deduction_subscriptions': '/deduction-subscriptions',
      'hr_settings_departments': '/settings/departments',
      'hr_settings_banks': '/staff-bank-details',
      'hr_settings_lead_sources': '/settings/lead-sources',
      'hr_settings_service_types': '/settings/service-types',
      'hr_settings_project_statuses': '/settings/project-statuses',
      'hr_settings_project_types_settings': '/project-types',
      'hr_settings_approvals': '/approvals',
      'hr_settings_statutory_payments': '/statutory-payments',
      'hr_settings_building_types': '/building-types',
      'hr_settings_boq_item_categories': '/boq-item-categories',
      'hr_settings_construction_stages': '/construction-stages',
      'hr_settings_activities': '/settings-activities',
      'hr_settings_sub_activities': '/settings-sub-activities',
      'hr_settings_boq_items': '/boq-items',
      'hr_settings_boq_templates': '/boq-templates',
      'hr_settings_attendance_types': '/attendance-types',
    };

    return map[route];
  }

  Future<void> _openSetting(Map<String, dynamic> item) async {
    final destination = _resolveSettingsDestination(item);
    if (destination != null) {
      if (!mounted) return;
      context.push(destination);
      return;
    }

    final url = item['url']?.toString().trim() ?? '';
    if (url.isNotEmpty) {
      if (!mounted) return;
      context.push(
        '/portal-webview',
        extra: {
          'title': item['name']?.toString() ?? 'Setting',
          'url': url,
        },
      );
      return;
    }

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('${item['name'] ?? 'Setting'} is not available right now.'),
      ),
    );
  }

  IconData _mapSettingsIcon(String route) {
    final normalized = route.toLowerCase();
    if (normalized.contains('allowance')) return Icons.payments_outlined;
    if (normalized.contains('salary')) return Icons.account_balance_wallet_outlined;
    if (normalized.contains('loan')) return Icons.request_quote_outlined;
    if (normalized.contains('deduction')) return Icons.remove_circle_outline;
    if (normalized.contains('department')) return Icons.apartment_outlined;
    if (normalized.contains('bank')) return Icons.account_balance_outlined;
    if (normalized.contains('lead')) return Icons.track_changes_outlined;
    if (normalized.contains('project')) return Icons.business_center_outlined;
    if (normalized.contains('asset')) return Icons.inventory_2_outlined;
    if (normalized.contains('system')) return Icons.settings_suggest_outlined;
    if (normalized.contains('user')) return Icons.groups_outlined;
    if (normalized.contains('role') || normalized.contains('permission')) {
      return Icons.admin_panel_settings_outlined;
    }
    if (normalized.contains('supplier')) return Icons.local_shipping_outlined;
    if (normalized.contains('expense')) return Icons.receipt_long_outlined;
    if (normalized.contains('approval')) return Icons.approval_outlined;
    if (normalized.contains('statutory')) return Icons.gavel_outlined;
    if (normalized.contains('building')) return Icons.home_work_outlined;
    if (normalized.contains('boq')) return Icons.view_list_outlined;
    if (normalized.contains('construction')) return Icons.layers_outlined;
    if (normalized.contains('activities')) return Icons.build_outlined;
    return Icons.settings_outlined;
  }
}

class _SettingsModuleCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final VoidCallback onTap;

  const _SettingsModuleCard({
    required this.title,
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        borderRadius: BorderRadius.circular(22),
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: const Color(0xFFE5EAF0)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 24,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: const Color(0xFF4461E9).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(
                  icon,
                  color: const Color(0xFF4461E9),
                  size: 26,
                ),
              ),
              const SizedBox(height: 16),
              Expanded(
                child: Text(
                  title,
                  maxLines: 4,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 15,
                    height: 1.35,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1F2D3D),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Open setting',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Color(0xFF16A085),
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptySettingsView extends StatelessWidget {
  const _EmptySettingsView();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.settings_outlined,
              size: 60,
              color: Colors.black.withValues(alpha: 0.12),
            ),
            const SizedBox(height: 12),
            const Text(
              'No settings found',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      ),
    );
  }
}
