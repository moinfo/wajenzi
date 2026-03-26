import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _materialRequestsProvider =
    FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/material-requests');
  return _extractListPayload(response.data);
});

final _materialRequestDetailProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/material-requests/$id');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

final _procurementDashboardProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/procurement/dashboard');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

final _supplierQuotationsProvider =
    FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/procurement/supplier-quotations');
  return _extractListPayload(response.data);
});

final _supplierQuotationDetailProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/procurement/supplier-quotations/$id');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

final _purchasesProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/procurement/purchases');
  return response.data['data'] as List? ?? [];
});

final _purchaseDetailProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/procurement/purchases/$id');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

final _inspectionsProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/procurement/inspections');
  return response.data['data'] as List? ?? [];
});

final _inspectionDetailProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/procurement/inspections/$id');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

List<dynamic> _extractListPayload(dynamic responseData) {
  if (responseData is List) return responseData;
  if (responseData is! Map) return const [];

  final root = Map<String, dynamic>.from(responseData as Map);
  final data = root['data'];
  if (data is List) return data;
  if (data is Map) {
    final nested = Map<String, dynamic>.from(data as Map);
    final nestedData = nested['data'];
    if (nestedData is List) return nestedData;
  }
  return const [];
}

class ProcurementScreen extends ConsumerWidget {
  const ProcurementScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final requestsAsync = ref.watch(_materialRequestsProvider);
    final dashboardAsync = ref.watch(_procurementDashboardProvider);
    final quotationsAsync = ref.watch(_supplierQuotationsProvider);
    final purchasesAsync = ref.watch(_purchasesProvider);
    final inspectionsAsync = ref.watch(_inspectionsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ununuzi' : 'Procurement'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_materialRequestsProvider);
          ref.invalidate(_procurementDashboardProvider);
          ref.invalidate(_supplierQuotationsProvider);
          ref.invalidate(_purchasesProvider);
          ref.invalidate(_inspectionsProvider);
          await Future.wait([
            ref.refresh(_materialRequestsProvider.future),
            ref.refresh(_procurementDashboardProvider.future),
            ref.refresh(_supplierQuotationsProvider.future),
            ref.refresh(_purchasesProvider.future),
            ref.refresh(_inspectionsProvider.future),
          ]);
        },
        child: requestsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () {
              ref.invalidate(_materialRequestsProvider);
              ref.invalidate(_procurementDashboardProvider);
              ref.invalidate(_supplierQuotationsProvider);
              ref.invalidate(_purchasesProvider);
              ref.invalidate(_inspectionsProvider);
            },
          ),
          data: (requests) {
            final dashboard = dashboardAsync.valueOrNull ?? const <String, dynamic>{};
            final quotations =
                (quotationsAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final purchases =
                (purchasesAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final inspections =
                (inspectionsAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final pendingDashboardRequests =
                (dashboard['pending_material_requests'] as List? ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final recentComparisons =
                (dashboard['recent_comparisons'] as List? ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final recentPurchases =
                (dashboard['recent_purchases'] as List? ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final recentInspections =
                (dashboard['recent_inspections'] as List? ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final activeProjects =
                (dashboard['active_projects'] as List? ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final lowStockItems =
                (dashboard['low_stock_items'] as List? ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final pendingActions =
                dashboard['pending_actions'] as Map<String, dynamic>? ?? const {};

            if (requests.isEmpty &&
                quotations.isEmpty &&
                purchases.isEmpty &&
                inspections.isEmpty &&
                pendingDashboardRequests.isEmpty &&
                recentComparisons.isEmpty &&
                recentPurchases.isEmpty &&
                recentInspections.isEmpty &&
                activeProjects.isEmpty &&
                lowStockItems.isEmpty &&
                !dashboardAsync.isLoading) {
              return ListView(
                children: [
                  const SizedBox(height: 120),
                  Icon(Icons.inventory_2_outlined,
                      size: 56, color: Colors.grey[300]),
                  const SizedBox(height: 12),
                  Center(
                    child: Text(
                      isSwahili ? 'Hakuna data ya ununuzi' : 'No procurement data',
                      style: const TextStyle(color: AppColors.textSecondary),
                    ),
                  ),
                ],
              );
            }

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                if (dashboard.isNotEmpty)
                  _DashboardSummary(
                    dashboard: dashboard,
                    isDarkMode: isDarkMode,
                    isSwahili: isSwahili,
                  ),
                if (pendingActions.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Hatua Zinazohitajika' : 'Actions Required',
                  ),
                  _ActionRequiredCard(
                    actions: pendingActions,
                    isDarkMode: isDarkMode,
                    isSwahili: isSwahili,
                  ),
                ],
                if (activeProjects.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Miradi Inayoendelea' : 'Active Projects',
                  ),
                  ...activeProjects.take(6).map(
                    (project) => _ActionListCard(
                      title: project['name']?.toString() ?? 'Project',
                      subtitle:
                          '${project['material_requests_count'] ?? 0} requests',
                      trailing:
                          '${project['boqs_count'] ?? 0} BOQs',
                      badgeColor: const Color(0xFF3B82F6),
                      isDarkMode: isDarkMode,
                    ),
                  ),
                ],
                if (lowStockItems.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Tahadhari ya Stock Ndogo' : 'Low Stock Alerts',
                  ),
                  ...lowStockItems.take(6).map(
                    (item) => _ActionListCard(
                      title: item['item_name']?.toString() ?? 'Item',
                      subtitle: item['project_name']?.toString() ?? '',
                      trailing: _formatNumber(item['quantity_available']),
                      badgeColor: _stockColor(item['stock_status']?.toString()),
                      isDarkMode: isDarkMode,
                    ),
                  ),
                ],
                if (pendingDashboardRequests.isNotEmpty || requests.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Maombi ya Vifaa' : 'Material Requests',
                  ),
                  ...(pendingDashboardRequests.isNotEmpty
                          ? pendingDashboardRequests
                          : requests.cast<Map<String, dynamic>>())
                      .take(8)
                      .map(
                        (req) => _RequestCard(
                          request: req,
                          isDarkMode: isDarkMode,
                          onTap: () => _showMaterialRequestDetails(
                            context,
                            ref,
                            req['id'] as int?,
                          ),
                        ),
                      ),
                ],
                if (recentComparisons.isNotEmpty || quotations.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Mlinganisho wa Hivi Karibuni' : 'Recent Comparisons',
                  ),
                  ...(recentComparisons.isNotEmpty ? recentComparisons : quotations).take(5).map(
                    (quotation) => _CompactRecordCard(
                      title: quotation['comparison_number'] as String? ??
                          quotation['quotation_number'] as String? ??
                          'Comparison',
                      subtitle: quotation['supplier_name'] as String? ??
                          quotation['supplier']?['name'] as String? ??
                          quotation['project_name'] as String? ??
                          quotation['material_request']?['project_name'] as String? ??
                          '',
                      status: quotation['status'] as String? ?? '',
                      meta: [
                        _metaLabel(
                          Icons.calendar_today_rounded,
                          _formatDate(
                            quotation['created_at'] as String? ??
                                quotation['delivery_date'] as String?,
                          ),
                        ),
                      ],
                      color: const Color(0xFF3B82F6),
                      isDarkMode: isDarkMode,
                      onTap: () => _showSupplierQuotationDetails(
                        context,
                        ref,
                        quotation['id'] as int?,
                      ),
                    ),
                  ),
                ],
                if (recentPurchases.isNotEmpty || purchases.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Manunuzi ya Hivi Karibuni' : 'Recent Purchases',
                  ),
                  ...(recentPurchases.isNotEmpty ? recentPurchases : purchases).take(5).map(
                    (purchase) => _CompactRecordCard(
                      title: purchase['purchase_number'] as String? ?? 'Purchase',
                      subtitle: purchase['supplier_name'] as String? ??
                          purchase['supplier']?['name'] as String? ??
                          purchase['project']?['project_name'] as String? ??
                          purchase['project_name'] as String? ??
                          '',
                      status: purchase['status'] as String? ?? '',
                      meta: [
                        _metaLabel(
                          Icons.attach_money_rounded,
                          _formatCurrency(purchase['total_amount']),
                        ),
                        _metaLabel(
                          Icons.local_shipping_rounded,
                          _formatDate(purchase['delivery_date'] as String?),
                        ),
                      ],
                      color: const Color(0xFF27AE60),
                      isDarkMode: isDarkMode,
                      onTap: () => _showPurchaseDetails(
                        context,
                        ref,
                        purchase['id'] as int?,
                      ),
                    ),
                  ),
                ],
                if (recentInspections.isNotEmpty || inspections.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Ukaguzi wa Vifaa' : 'Material Inspections',
                  ),
                  ...(recentInspections.isNotEmpty ? recentInspections : inspections).take(5).map(
                    (inspection) => _CompactRecordCard(
                      title:
                          inspection['inspection_number'] as String? ?? 'Inspection',
                      subtitle: inspection['project_name'] as String? ??
                          inspection['supplier_receiving']?['supplier_name']
                              as String? ??
                          '',
                      status: inspection['status'] as String? ?? '',
                      meta: [
                        _metaLabel(
                          Icons.event_rounded,
                          _formatDate(
                            inspection['inspection_date'] as String? ??
                                inspection['created_at'] as String?,
                          ),
                        ),
                        _metaLabel(
                          Icons.fact_check_rounded,
                          _titleCase(inspection['overall_result'] as String?),
                        ),
                      ],
                      color: const Color(0xFF8B5CF6),
                      isDarkMode: isDarkMode,
                      onTap: () => _showInspectionDetails(
                        context,
                        ref,
                        inspection['id'] as int?,
                      ),
                    ),
                  ),
                ],
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _RequestCard extends StatelessWidget {
  final Map<String, dynamic> request;
  final bool isDarkMode;
  final VoidCallback? onTap;

  const _RequestCard({
    required this.request,
    required this.isDarkMode,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final requestNumber = request['request_number'] as String? ?? '';
    final projectName = request['project_name'] as String? ??
        request['project']?['project_name'] as String? ??
        '';
    final status = request['status'] as String? ?? '';
    final createdAt = request['created_at'] as String?;
    final itemCount = request['items_count'] ?? request['items']?.length ?? 0;

    String? dateStr;
    if (createdAt != null) {
      try {
        dateStr = DateFormat('dd MMM yyyy').format(DateTime.parse(createdAt));
      } catch (_) {}
    }

    Color statusColor;
    switch (status.toLowerCase()) {
      case 'approved':
        statusColor = const Color(0xFF27AE60);
        break;
      case 'pending':
      case 'submitted':
        statusColor = const Color(0xFFF59E0B);
        break;
      case 'rejected':
        statusColor = const Color(0xFFEF4444);
        break;
      default:
        statusColor = const Color(0xFF95A5A6);
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.08)
              : Colors.grey.withValues(alpha: 0.12),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: const Color(0xFF7C3AED).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    Icons.inventory_2_rounded,
                    color: Color(0xFF7C3AED),
                    size: 22,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        requestNumber.isNotEmpty ? requestNumber : 'Material Request',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 2),
                      if (projectName.isNotEmpty)
                        Text(
                          projectName,
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          if (itemCount > 0) ...[
                            Icon(
                              Icons.list_rounded,
                              size: 12,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              '$itemCount items',
                              style: TextStyle(
                                fontSize: 11,
                                color: isDarkMode
                                    ? Colors.white38
                                    : AppColors.textHint,
                              ),
                            ),
                          ],
                          if (dateStr != null) ...[
                            if (itemCount > 0) const SizedBox(width: 10),
                            Icon(
                              Icons.calendar_today_rounded,
                              size: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              dateStr,
                              style: TextStyle(
                                fontSize: 11,
                                color: isDarkMode
                                    ? Colors.white38
                                    : AppColors.textHint,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        _titleCase(status),
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: statusColor,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Icon(
                      Icons.arrow_forward_ios_rounded,
                      size: 14,
                      color: isDarkMode ? Colors.white38 : AppColors.textHint,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DashboardSummary extends StatelessWidget {
  final Map<String, dynamic> dashboard;
  final bool isDarkMode;
  final bool isSwahili;

  const _DashboardSummary({
    required this.dashboard,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final materialRequests =
        dashboard['material_requests'] as Map<String, dynamic>? ?? {};
    final quotations = dashboard['quotations'] as Map<String, dynamic>? ?? {};
    final purchases = dashboard['purchases'] as Map<String, dynamic>? ?? {};
    final inspections = dashboard['inspections'] as Map<String, dynamic>? ?? {};
    final pendingActions =
        dashboard['pending_actions'] as Map<String, dynamic>? ?? {};

    final cards = [
      _SummaryCardData(
        label: isSwahili ? 'Maombi Yote' : 'Total Requests',
        value: '${materialRequests['total'] ?? 0}',
        subtitle:
            '${materialRequests['pending'] ?? 0} ${isSwahili ? 'pending' : 'pending'}',
        color: const Color(0xFF2563EB),
        icon: Icons.inventory_2_rounded,
      ),
      _SummaryCardData(
        label: isSwahili ? 'Nukuu Zote' : 'Total Quotations',
        value: '${quotations['total'] ?? 0}',
        subtitle:
            '${pendingActions['comparisons_pending_approval'] ?? 0} ${isSwahili ? 'comparisons' : 'comparisons pending'}',
        color: const Color(0xFF0EA5E9),
        icon: Icons.request_quote_rounded,
      ),
      _SummaryCardData(
        label: isSwahili ? 'Delivery Pending' : 'Pending Deliveries',
        value: '${pendingActions['deliveries_pending_inspection'] ?? 0}',
        subtitle: isSwahili ? 'Zinasubiri ukaguzi' : 'Awaiting inspection',
        color: const Color(0xFFF59E0B),
        icon: Icons.local_shipping_rounded,
      ),
      _SummaryCardData(
        label: isSwahili ? 'Ukaguzi Umekamilika' : 'Completed Inspections',
        value: '${inspections['approved'] ?? 0}',
        subtitle:
            '${inspections['pending'] ?? 0} ${isSwahili ? 'pending' : 'pending approval'}',
        color: const Color(0xFF16A34A),
        icon: Icons.fact_check_rounded,
      ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            gradient: const LinearGradient(
              colors: [Color(0xFF0F172A), Color(0xFF1E3A8A)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                isSwahili ? 'Dashibodi ya Ununuzi' : 'Procurement Dashboard',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                isSwahili
                    ? 'Muhtasari wa maombi, nukuu, delivery na ukaguzi.'
                    : 'Overview of requests, quotations, deliveries, and inspections.',
                style: const TextStyle(color: Colors.white70),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 136,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: cards.length,
            separatorBuilder: (_, _) => const SizedBox(width: 10),
            itemBuilder: (context, index) => _SummaryCard(
              data: cards[index],
              isDarkMode: isDarkMode,
            ),
          ),
        ),
      ],
    );
  }
}

class _SummaryCardData {
  final String label;
  final String value;
  final String subtitle;
  final Color color;
  final IconData icon;

  const _SummaryCardData({
    required this.label,
    required this.value,
    required this.subtitle,
    required this.color,
    required this.icon,
  });
}

class _SummaryCard extends StatelessWidget {
  final _SummaryCardData data;
  final bool isDarkMode;

  const _SummaryCard({
    required this.data,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 148,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: data.color.withValues(alpha: 0.18),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(data.icon, size: 18, color: data.color),
          const SizedBox(height: 10),
          Text(
            data.value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          Text(
            data.label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            data.subtitle,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 10,
              color: isDarkMode ? Colors.white38 : AppColors.textHint,
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionRequiredCard extends StatelessWidget {
  final Map<String, dynamic> actions;
  final bool isDarkMode;
  final bool isSwahili;

  const _ActionRequiredCard({
    required this.actions,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final items = [
      (
        isSwahili ? 'Maombi yanayosubiri approval' : 'Requests Pending Approval',
        '${actions['requests_pending_approval'] ?? 0}',
        const Color(0xFFF59E0B),
      ),
      (
        isSwahili ? 'Yanahitaji nukuu' : 'Requests Needing Quotations',
        '${actions['requests_needing_quotations'] ?? 0}',
        const Color(0xFF0EA5E9),
      ),
      (
        isSwahili ? 'Tayari kwa comparison' : 'Ready for Comparison',
        '${actions['requests_ready_for_comparison'] ?? 0}',
        const Color(0xFF2563EB),
      ),
      (
        isSwahili ? 'Comparisons pending' : 'Comparisons Pending Approval',
        '${actions['comparisons_pending_approval'] ?? 0}',
        const Color(0xFFF97316),
      ),
      (
        isSwahili ? 'Delivery zinasubiri ukaguzi' : 'Deliveries Pending Inspection',
        '${actions['deliveries_pending_inspection'] ?? 0}',
        const Color(0xFFDC2626),
      ),
      (
        isSwahili ? 'Ukaguzi pending approval' : 'Inspections Pending Approval',
        '${actions['inspections_pending_approval'] ?? 0}',
        const Color(0xFFCA8A04),
      ),
    ];

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.08)
              : Colors.grey.withValues(alpha: 0.12),
        ),
      ),
      child: Column(
        children: items
            .map(
              (item) => Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  border: Border(
                    bottom: BorderSide(
                      color: isDarkMode
                          ? Colors.white.withValues(alpha: 0.06)
                          : Colors.grey.withValues(alpha: 0.08),
                    ),
                  ),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        item.$1,
                        style: TextStyle(
                          fontSize: 13,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      item.$2,
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: item.$3,
                      ),
                    ),
                  ],
                ),
              ),
            )
            .toList(),
      ),
    );
  }
}

class _ActionListCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final String trailing;
  final Color badgeColor;
  final bool isDarkMode;

  const _ActionListCard({
    required this.title,
    required this.subtitle,
    required this.trailing,
    required this.badgeColor,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.08)
              : Colors.grey.withValues(alpha: 0.12),
        ),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                if (subtitle.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 12,
                      color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: badgeColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              trailing,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                color: badgeColor,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;

  const _SectionHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 18, bottom: 10),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _CompactRecordCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final String status;
  final List<Widget> meta;
  final Color color;
  final bool isDarkMode;
  final VoidCallback? onTap;

  const _CompactRecordCard({
    required this.title,
    required this.subtitle,
    required this.status,
    required this.meta,
    required this.color,
    required this.isDarkMode,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.08)
              : Colors.grey.withValues(alpha: 0.12),
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode ? Colors.white : AppColors.textPrimary,
                            ),
                          ),
                          if (subtitle.isNotEmpty) ...[
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
                        ],
                      ),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: _statusColor(status).withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            _titleCase(status),
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: _statusColor(status),
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: 14,
                          color: isDarkMode ? Colors.white38 : AppColors.textHint,
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 10,
                  runSpacing: 6,
                  children: meta,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

Widget _metaLabel(IconData icon, String value) {
  if (value.isEmpty) return const SizedBox.shrink();
  return Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(icon, size: 12, color: AppColors.textHint),
      const SizedBox(width: 4),
      Text(
        value,
        style: const TextStyle(
          fontSize: 11,
          color: AppColors.textHint,
        ),
      ),
    ],
  );
}

Color _statusColor(String? status) {
  switch ((status ?? '').toLowerCase()) {
    case 'approved':
    case 'completed':
    case 'delivered':
    case 'accepted':
      return const Color(0xFF27AE60);
    case 'pending':
    case 'submitted':
    case 'draft':
      return const Color(0xFFF59E0B);
    case 'rejected':
    case 'cancelled':
      return const Color(0xFFEF4444);
    default:
      return const Color(0xFF64748B);
  }
}

String _formatDate(String? value) {
  if (value == null || value.isEmpty) return '';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(value));
  } catch (_) {
    return value;
  }
}

String _formatCurrency(dynamic amount) {
  if (amount == null) return '';
  final value = amount is num ? amount.toDouble() : double.tryParse('$amount');
  if (value == null) return '$amount';
  return 'TZS ${NumberFormat('#,##0.##', 'en').format(value)}';
}

String _formatNumber(dynamic value) {
  if (value == null) return '0';
  final number = value is num ? value.toDouble() : double.tryParse('$value');
  if (number == null) return '$value';
  return NumberFormat('#,##0.##', 'en').format(number);
}

String _titleCase(String? value) {
  if (value == null || value.isEmpty) return '';
  return value
      .replaceAll('_', ' ')
      .split(' ')
      .where((part) => part.isNotEmpty)
      .map((part) => '${part[0].toUpperCase()}${part.substring(1).toLowerCase()}')
      .join(' ');
}

String _formatDays(dynamic value) {
  if (value == null || '$value'.isEmpty) return '';
  return '$value days';
}

Color _stockColor(String? status) {
  switch ((status ?? '').toLowerCase()) {
    case 'out_of_stock':
      return const Color(0xFFDC2626);
    case 'low_stock':
      return const Color(0xFFF59E0B);
    default:
      return const Color(0xFF16A34A);
  }
}

void _showMaterialRequestDetails(
  BuildContext context,
  WidgetRef ref,
  int? requestId,
) {
  if (requestId == null) return;
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_materialRequestDetailProvider(requestId));
        return _ProcurementDetailSheet(
          title: 'Material Request',
          detailAsync: detailAsync,
          builder: (detail) {
            final items = (detail['items'] as List? ?? const <dynamic>[]);
            final requesterName = detail['requester']?['name'] as String? ?? '';
            return [
              _DetailRow(label: 'Request Number', value: '${detail['request_number'] ?? '-'}'),
              _DetailRow(
                label: 'Project',
                value: detail['project']?['project_name'] as String? ??
                    detail['project_name'] as String? ??
                    '-',
              ),
              _DetailRow(label: 'Status', value: _titleCase(detail['status'] as String?)),
              _DetailRow(label: 'Priority', value: _titleCase(detail['priority'] as String?)),
              _DetailRow(label: 'Requested By', value: requesterName.isEmpty ? '-' : requesterName),
              _DetailRow(label: 'Needed By', value: _formatDate(detail['needed_by_date'] as String?)),
              _DetailRow(label: 'Created', value: _formatDate(detail['created_at'] as String?)),
              if ((detail['description'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Description',
                  child: Text(detail['description'] as String),
                ),
              if ((detail['notes'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Notes',
                  child: Text(detail['notes'] as String),
                ),
              if (items.isNotEmpty)
                _DetailSection(
                  title: 'Items',
                  child: Column(
                    children: items.map<Widget>((item) {
                      final map = item as Map<String, dynamic>;
                      final materialName = map['material_name'] as String? ?? 'Material';
                      final quantity = map['quantity']?.toString() ?? '-';
                      final unit = map['unit'] as String? ?? '';
                      return _DetailRow(
                        label: materialName,
                        value: '$quantity${unit.isEmpty ? '' : ' $unit'}',
                        trailing: _formatCurrency(map['estimated_cost']),
                      );
                    }).toList(),
                  ),
                ),
            ];
          },
        );
      },
    ),
  );
}

void _showSupplierQuotationDetails(
  BuildContext context,
  WidgetRef ref,
  int? quotationId,
) {
  if (quotationId == null) return;
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_supplierQuotationDetailProvider(quotationId));
        return _ProcurementDetailSheet(
          title: 'Supplier Quotation',
          detailAsync: detailAsync,
          builder: (detail) {
            final items = (detail['items'] as List? ?? const <dynamic>[]);
            return [
              _DetailRow(label: 'Quotation Number', value: '${detail['quotation_number'] ?? '-'}'),
              _DetailRow(
                label: 'Supplier',
                value: detail['supplier']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Material Request',
                value: detail['material_request']?['request_number'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Project',
                value: detail['material_request']?['project_name'] as String? ?? '-',
              ),
              _DetailRow(label: 'Status', value: _titleCase(detail['status'] as String?)),
              _DetailRow(label: 'Delivery Date', value: _formatDate(detail['delivery_date'] as String?)),
              _DetailRow(label: 'Validity', value: _formatDays(detail['validity_days'])),
              _DetailRow(label: 'Total Amount', value: _formatCurrency(detail['total_amount'])),
              if ((detail['notes'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Notes',
                  child: Text(detail['notes'] as String),
                ),
              if (items.isNotEmpty)
                _DetailSection(
                  title: 'Quoted Items',
                  child: Column(
                    children: items.map<Widget>((item) {
                      final map = item as Map<String, dynamic>;
                      final itemName = map['item_name'] as String? ??
                          map['material_name'] as String? ??
                          'Item';
                      final quantity = map['quantity']?.toString() ?? '-';
                      final unitPrice = _formatCurrency(
                        map['unit_price'] ?? map['price'],
                      );
                      return _DetailRow(
                        label: itemName,
                        value: '$quantity ${map['unit'] ?? ''}'.trim(),
                        trailing: unitPrice,
                      );
                    }).toList(),
                  ),
                ),
            ];
          },
        );
      },
    ),
  );
}

void _showPurchaseDetails(
  BuildContext context,
  WidgetRef ref,
  int? purchaseId,
) {
  if (purchaseId == null) return;
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_purchaseDetailProvider(purchaseId));
        return _ProcurementDetailSheet(
          title: 'Purchase',
          detailAsync: detailAsync,
          builder: (detail) {
            final items = (detail['purchase_items'] as List? ?? const <dynamic>[]);
            return [
              _DetailRow(label: 'Purchase Number', value: '${detail['purchase_number'] ?? '-'}'),
              _DetailRow(
                label: 'Supplier',
                value: detail['supplier']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Project',
                value: detail['project']?['project_name'] as String? ?? '-',
              ),
              _DetailRow(label: 'Status', value: _titleCase(detail['status'] as String?)),
              _DetailRow(label: 'Delivery Date', value: _formatDate(detail['delivery_date'] as String?)),
              _DetailRow(label: 'Total Amount', value: _formatCurrency(detail['total_amount'])),
              if ((detail['notes'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Notes',
                  child: Text(detail['notes'] as String),
                ),
              if (items.isNotEmpty)
                _DetailSection(
                  title: 'Purchase Items',
                  child: Column(
                    children: items.map<Widget>((item) {
                      final map = item as Map<String, dynamic>;
                      final materialName = map['material']?['name'] as String? ??
                          map['material_name'] as String? ??
                          'Item';
                      final quantity = map['quantity']?.toString() ?? '-';
                      final amount = _formatCurrency(
                        map['total_price'] ?? map['amount'] ?? map['unit_price'],
                      );
                      return _DetailRow(
                        label: materialName,
                        value: '$quantity ${map['unit'] ?? ''}'.trim(),
                        trailing: amount,
                      );
                    }).toList(),
                  ),
                ),
              if (detail['delivery'] is Map<String, dynamic>)
                _DetailSection(
                  title: 'Delivery',
                  child: Column(
                    children: [
                      _DetailRow(
                        label: 'Reference',
                        value: detail['delivery']['delivery_number'] as String? ?? '-',
                      ),
                      _DetailRow(
                        label: 'Status',
                        value: _titleCase(detail['delivery']['status'] as String?),
                      ),
                    ],
                  ),
                ),
            ];
          },
        );
      },
    ),
  );
}

void _showInspectionDetails(
  BuildContext context,
  WidgetRef ref,
  int? inspectionId,
) {
  if (inspectionId == null) return;
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_inspectionDetailProvider(inspectionId));
        return _ProcurementDetailSheet(
          title: 'Inspection',
          detailAsync: detailAsync,
          builder: (detail) {
            return [
              _DetailRow(label: 'Inspection Number', value: '${detail['inspection_number'] ?? '-'}'),
              _DetailRow(label: 'Project', value: detail['project_name'] as String? ?? '-'),
              _DetailRow(
                label: 'Supplier',
                value: detail['supplier_receiving']?['supplier_name'] as String? ?? '-',
              ),
              _DetailRow(label: 'Inspector', value: detail['inspector_name'] as String? ?? '-'),
              _DetailRow(label: 'Status', value: _titleCase(detail['status'] as String?)),
              _DetailRow(label: 'Inspection Date', value: _formatDate(detail['inspection_date'] as String?)),
              _DetailRow(label: 'Result', value: _titleCase(detail['overall_result'] as String?)),
              _DetailRow(
                label: 'Condition',
                value: _titleCase(detail['overall_condition'] as String?),
              ),
              _DetailRow(
                label: 'Delivered Qty',
                value: '${detail['quantity_delivered'] ?? '-'}',
              ),
              _DetailRow(
                label: 'Accepted Qty',
                value: '${detail['quantity_accepted'] ?? '-'}',
              ),
              _DetailRow(
                label: 'Rejected Qty',
                value: '${detail['quantity_rejected'] ?? '-'}',
              ),
              if ((detail['notes'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Inspection Notes',
                  child: Text(detail['notes'] as String),
                ),
            ];
          },
        );
      },
    ),
  );
}

class _ProcurementDetailSheet extends ConsumerWidget {
  final String title;
  final AsyncValue<Map<String, dynamic>> detailAsync;
  final List<Widget> Function(Map<String, dynamic>) builder;

  const _ProcurementDetailSheet({
    required this.title,
    required this.detailAsync,
    required this.builder,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.85,
      ),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF101827) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: detailAsync.when(
          loading: () => const Padding(
            padding: EdgeInsets.all(32),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _SheetHandle(isDarkMode: isDarkMode),
                const SizedBox(height: 12),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 24),
                const Icon(Icons.error_outline, color: AppColors.error, size: 40),
                const SizedBox(height: 12),
                Text(
                  '$error',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          data: (detail) => SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _SheetHandle(isDarkMode: isDarkMode),
                const SizedBox(height: 12),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 18),
                ...builder(detail),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SheetHandle extends StatelessWidget {
  final bool isDarkMode;

  const _SheetHandle({required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        width: 44,
        height: 5,
        decoration: BoxDecoration(
          color: isDarkMode ? Colors.white24 : Colors.black12,
          borderRadius: BorderRadius.circular(999),
        ),
      ),
    );
  }
}

class _DetailSection extends StatelessWidget {
  final String title;
  final Widget child;

  const _DetailSection({
    required this.title,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 10),
          child,
        ],
      ),
    );
  }
}

class _DetailRow extends ConsumerWidget {
  final String label;
  final String value;
  final String? trailing;

  const _DetailRow({
    required this.label,
    required this.value,
    this.trailing,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 5,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white60 : AppColors.textSecondary,
              ),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            flex: 7,
            child: Text(
              value.isEmpty ? '-' : value,
              textAlign: TextAlign.right,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
          if (trailing != null && trailing!.isNotEmpty) ...[
            const SizedBox(width: 12),
            Flexible(
              child: Text(
                trailing!,
                textAlign: TextAlign.right,
                style: TextStyle(
                  fontSize: 12,
                  color: isDarkMode ? Colors.white70 : AppColors.textHint,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
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
