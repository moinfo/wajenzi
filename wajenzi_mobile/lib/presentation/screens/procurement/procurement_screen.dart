import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _materialRequestSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _materialRequestFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final _supplierQuotationSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _supplierQuotationFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final _purchaseSearchProvider = StateProvider.autoDispose<String>((ref) => '');
final _purchaseFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final _recordDeliverySearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _recordDeliveryFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final _supplierReceivingSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _supplierReceivingFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final _materialInspectionSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _materialInspectionFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final _quotationComparisonSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _quotationComparisonFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _materialRequestsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_materialRequestSearchProvider);
  final filter = ref.watch(_materialRequestFilterProvider);
  final queryParams = <String, dynamic>{'per_page': 100};
  if (search.isNotEmpty) queryParams['search'] = search;
  if (filter != null) queryParams['status'] = filter;
  final response = await api.get(
    '/material-requests',
    queryParameters: queryParams,
  );
  return _extractListPayload(response.data);
});

final _materialRequestDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
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

final _supplierQuotationsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_supplierQuotationSearchProvider);
  final filter = ref.watch(_supplierQuotationFilterProvider);
  final queryParams = <String, dynamic>{'per_page': 100};
  if (search.isNotEmpty) queryParams['search'] = search;
  if (filter != null) queryParams['status'] = filter;
  final response = await api.get(
    '/procurement/supplier-quotations',
    queryParameters: queryParams,
  );
  return _extractListPayload(response.data);
});

final _supplierQuotationDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/procurement/supplier-quotations/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

final _quotationComparisonsProvider = FutureProvider.autoDispose<List<dynamic>>(
  (ref) async {
    final api = ref.watch(apiClientProvider);
    final search = ref.watch(_quotationComparisonSearchProvider);
    final filter = ref.watch(_quotationComparisonFilterProvider);
    final queryParams = <String, dynamic>{'per_page': 100};
    if (search.isNotEmpty) queryParams['search'] = search;
    if (filter != null) queryParams['status'] = filter;
    final response = await api.get(
      '/procurement/quotation-comparisons',
      queryParameters: queryParams,
    );
    return _extractListPayload(response.data);
  },
);

final _quotationComparisonDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/procurement/quotation-comparisons/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

final _purchasesProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/procurement/purchases',
    queryParameters: {'per_page': 100},
  );
  return _extractListPayload(response.data);
});

final _purchaseOrdersProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_purchaseSearchProvider);
  final filter = ref.watch(_purchaseFilterProvider);
  final queryParams = <String, dynamic>{
    'per_page': 100,
    'procurement_only': true,
  };
  if (search.isNotEmpty) queryParams['search'] = search;
  if (filter != null) queryParams['status'] = filter;
  final response = await api.get(
    '/procurement/purchases',
    queryParameters: queryParams,
  );
  return _extractListPayload(response.data);
});

final _pendingDeliveriesProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_recordDeliverySearchProvider);
  final filter = ref.watch(_recordDeliveryFilterProvider);
  final queryParams = <String, dynamic>{'per_page': 100};
  if (search.isNotEmpty) queryParams['search'] = search;
  if (filter != null) queryParams['status'] = filter;
  final response = await api.get(
    '/procurement/pending-deliveries',
    queryParameters: queryParams,
  );
  return _extractListPayload(response.data);
});

final _receivingsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_supplierReceivingSearchProvider);
  final filter = ref.watch(_supplierReceivingFilterProvider);
  final queryParams = <String, dynamic>{'per_page': 100};
  if (search.isNotEmpty) queryParams['search'] = search;
  if (filter != null) queryParams['status'] = filter;
  final response = await api.get(
    '/procurement/receivings',
    queryParameters: queryParams,
  );
  return _extractListPayload(response.data);
});

final _purchaseDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/procurement/purchases/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

final _receivingDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/procurement/receivings/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

final _inspectionsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_materialInspectionSearchProvider);
  final filter = ref.watch(_materialInspectionFilterProvider);
  final queryParams = <String, dynamic>{'per_page': 100};
  if (search.isNotEmpty) queryParams['search'] = search;
  if (filter != null) queryParams['status'] = filter;
  final response = await api.get(
    '/procurement/inspections',
    queryParameters: queryParams,
  );
  return _extractListPayload(response.data);
});

final _inspectionPendingReceivingsProvider =
    FutureProvider.autoDispose<List<dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/procurement/inspections',
        queryParameters: {'per_page': 100},
      );
      final data = response.data['data'];
      if (data is Map && data['pending_receivings'] is List) {
        return data['pending_receivings'] as List;
      }
      return const [];
    });

final _inspectionDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/procurement/inspections/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

final _projectsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/projects',
        queryParameters: {'per_page': 100},
      );
      final items = _extractListPayload(response.data);
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
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

Widget _buildComparisonSearchAndFilter(
  BuildContext context,
  WidgetRef ref,
  bool isSwahili,
  bool isDarkMode,
  String search,
  String? filter,
) {
  return Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      boxShadow: [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.05),
          blurRadius: 4,
          offset: const Offset(0, 2),
        ),
      ],
    ),
    child: Column(
      children: [
        TextField(
          onChanged: (value) =>
              ref.read(_quotationComparisonSearchProvider.notifier).state =
                  value,
          decoration: InputDecoration(
            hintText: isSwahili
                ? 'Tafuta mlinganisho...'
                : 'Search comparisons...',
            prefixIcon: const Icon(Icons.search),
            suffixIcon: search.isNotEmpty
                ? IconButton(
                    icon: const Icon(Icons.clear),
                    onPressed: () =>
                        ref
                                .read(
                                  _quotationComparisonSearchProvider.notifier,
                                )
                                .state =
                            '',
                  )
                : null,
            filled: true,
            fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide.none,
            ),
          ),
        ),
        const SizedBox(height: 12),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: [
              _buildFilterChip(
                isSwahili ? 'Zote' : 'All',
                filter == null,
                () =>
                    ref
                            .read(_quotationComparisonFilterProvider.notifier)
                            .state =
                        null,
                isDarkMode,
                Colors.grey,
              ),
              const SizedBox(width: 8),
              _buildFilterChip(
                isSwahili ? 'Inasubiri' : 'Pending',
                filter == 'pending',
                () =>
                    ref
                            .read(_quotationComparisonFilterProvider.notifier)
                            .state =
                        'pending',
                isDarkMode,
                Colors.orange,
              ),
              const SizedBox(width: 8),
              _buildFilterChip(
                isSwahili ? 'Imepitiwa' : 'Approved',
                filter == 'approved',
                () =>
                    ref
                            .read(_quotationComparisonFilterProvider.notifier)
                            .state =
                        'approved',
                isDarkMode,
                Colors.green,
              ),
              const SizedBox(width: 8),
              _buildFilterChip(
                isSwahili ? 'Imefutiliwa' : 'Rejected',
                filter == 'rejected',
                () =>
                    ref
                            .read(_quotationComparisonFilterProvider.notifier)
                            .state =
                        'rejected',
                isDarkMode,
                Colors.red,
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

Widget _buildFilterChip(
  String label,
  bool isSelected,
  VoidCallback onTap,
  bool isDarkMode,
  Color color,
) {
  return GestureDetector(
    onTap: onTap,
    child: Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: isSelected
            ? color
            : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100]),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: isSelected ? color : Colors.transparent),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: isSelected
              ? Colors.white
              : (isDarkMode ? Colors.white54 : Colors.grey[600]),
        ),
      ),
    ),
  );
}

class ProcurementScreen extends ConsumerWidget {
  final bool materialRequestsOnly;
  final bool supplierQuotationsOnly;
  final bool quotationComparisonsOnly;
  final bool purchaseOrdersOnly;
  final bool recordDeliveriesOnly;
  final bool supplierReceivingsOnly;
  final bool materialInspectionsOnly;

  const ProcurementScreen({
    super.key,
    this.materialRequestsOnly = false,
    this.supplierQuotationsOnly = false,
    this.quotationComparisonsOnly = false,
    this.purchaseOrdersOnly = false,
    this.recordDeliveriesOnly = false,
    this.supplierReceivingsOnly = false,
    this.materialInspectionsOnly = false,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final requestsAsync = ref.watch(_materialRequestsProvider);
    final dashboardAsync = ref.watch(_procurementDashboardProvider);
    final quotationsAsync = ref.watch(_supplierQuotationsProvider);
    final comparisonsAsync = ref.watch(_quotationComparisonsProvider);
    final purchaseOrdersAsync = ref.watch(_purchaseOrdersProvider);
    final pendingDeliveriesAsync = ref.watch(_pendingDeliveriesProvider);
    final receivingsAsync = ref.watch(_receivingsProvider);
    final pendingInspectionReceivingsAsync = ref.watch(
      _inspectionPendingReceivingsProvider,
    );
    final purchasesAsync = ref.watch(_purchasesProvider);
    final inspectionsAsync = ref.watch(_inspectionsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final materialRequestSearch = ref.watch(_materialRequestSearchProvider);
    final materialRequestFilter = ref.watch(_materialRequestFilterProvider);
    final quotationSearch = ref.watch(_supplierQuotationSearchProvider);
    final quotationFilter = ref.watch(_supplierQuotationFilterProvider);
    final comparisonSearch = ref.watch(_quotationComparisonSearchProvider);
    final comparisonFilter = ref.watch(_quotationComparisonFilterProvider);
    final purchaseSearch = ref.watch(_purchaseSearchProvider);
    final purchaseFilter = ref.watch(_purchaseFilterProvider);
    final recordDeliverySearch = ref.watch(_recordDeliverySearchProvider);
    final recordDeliveryFilter = ref.watch(_recordDeliveryFilterProvider);
    final supplierReceivingSearch = ref.watch(_supplierReceivingSearchProvider);
    final supplierReceivingFilter = ref.watch(_supplierReceivingFilterProvider);
    final materialInspectionSearch = ref.watch(
      _materialInspectionSearchProvider,
    );
    final materialInspectionFilter = ref.watch(
      _materialInspectionFilterProvider,
    );
    final primaryAsync = quotationComparisonsOnly
        ? comparisonsAsync
        : materialInspectionsOnly
        ? inspectionsAsync
        : supplierReceivingsOnly
        ? receivingsAsync
        : recordDeliveriesOnly
        ? pendingDeliveriesAsync
        : purchaseOrdersOnly
        ? purchaseOrdersAsync
        : supplierQuotationsOnly
        ? quotationsAsync
        : requestsAsync;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(
          materialRequestsOnly
              ? (isSwahili ? 'Maombi ya Vifaa' : 'Material Requests')
              : supplierQuotationsOnly
              ? (isSwahili ? 'Nukuu za Wasambazaji' : 'Supplier Quotations')
              : quotationComparisonsOnly
              ? (isSwahili ? 'Mlinganisho wa Nukuu' : 'Quotation Comparisons')
              : purchaseOrdersOnly
              ? (isSwahili ? 'Maagizo ya Ununuzi' : 'Purchase Orders')
              : recordDeliveriesOnly
              ? (isSwahili ? 'Rekodi Uwasilishaji' : 'Record Deliveries')
              : supplierReceivingsOnly
              ? (isSwahili ? 'Supplier Receivings' : 'Supplier Receivings')
              : materialInspectionsOnly
              ? (isSwahili ? 'Ukaguzi wa Vifaa' : 'Material Inspections')
              : (isSwahili ? 'Ununuzi' : 'Procurement'),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_materialRequestsProvider);
          ref.invalidate(_procurementDashboardProvider);
          ref.invalidate(_supplierQuotationsProvider);
          ref.invalidate(_quotationComparisonsProvider);
          ref.invalidate(_purchaseOrdersProvider);
          ref.invalidate(_pendingDeliveriesProvider);
          ref.invalidate(_receivingsProvider);
          ref.invalidate(_inspectionPendingReceivingsProvider);
          ref.invalidate(_purchasesProvider);
          ref.invalidate(_inspectionsProvider);
          await Future.wait([
            ref.refresh(_materialRequestsProvider.future),
            ref.refresh(_procurementDashboardProvider.future),
            ref.refresh(_supplierQuotationsProvider.future),
            ref.refresh(_quotationComparisonsProvider.future),
            ref.refresh(_purchaseOrdersProvider.future),
            ref.refresh(_pendingDeliveriesProvider.future),
            ref.refresh(_receivingsProvider.future),
            ref.refresh(_inspectionPendingReceivingsProvider.future),
            ref.refresh(_purchasesProvider.future),
            ref.refresh(_inspectionsProvider.future),
          ]);
        },
        child: primaryAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () {
              ref.invalidate(_materialRequestsProvider);
              ref.invalidate(_procurementDashboardProvider);
              ref.invalidate(_supplierQuotationsProvider);
              ref.invalidate(_quotationComparisonsProvider);
              ref.invalidate(_purchaseOrdersProvider);
              ref.invalidate(_pendingDeliveriesProvider);
              ref.invalidate(_receivingsProvider);
              ref.invalidate(_inspectionPendingReceivingsProvider);
              ref.invalidate(_purchasesProvider);
              ref.invalidate(_inspectionsProvider);
            },
          ),
          data: (requests) {
            final dashboard =
                dashboardAsync.valueOrNull ?? const <String, dynamic>{};
            final quotations =
                (quotationsAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final comparisons =
                (comparisonsAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final purchaseOrders =
                (purchaseOrdersAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final pendingDeliveries =
                (pendingDeliveriesAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final receivings =
                (receivingsAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final pendingInspectionReceivings =
                (pendingInspectionReceivingsAsync.valueOrNull ??
                        const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final purchases = (purchasesAsync.valueOrNull ?? const <dynamic>[])
                .cast<Map<String, dynamic>>();
            final inspections =
                (inspectionsAsync.valueOrNull ?? const <dynamic>[])
                    .cast<Map<String, dynamic>>();
            final pendingDashboardRequests =
                (dashboard['pending_material_requests'] as List? ??
                        const <dynamic>[])
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
                dashboard['pending_actions'] as Map<String, dynamic>? ??
                const {};

            if (requests.isEmpty &&
                comparisons.isEmpty &&
                purchaseOrders.isEmpty &&
                pendingDeliveries.isEmpty &&
                receivings.isEmpty &&
                pendingInspectionReceivings.isEmpty &&
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
                  Icon(
                    Icons.inventory_2_outlined,
                    size: 56,
                    color: Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Center(
                    child: Text(
                      quotationComparisonsOnly
                          ? (isSwahili
                                ? 'Hakuna mlinganisho wa nukuu'
                                : 'No quotation comparisons')
                          : purchaseOrdersOnly
                          ? (isSwahili
                                ? 'Hakuna maagizo ya ununuzi'
                                : 'No purchase orders')
                          : recordDeliveriesOnly
                          ? (isSwahili
                                ? 'Hakuna deliveries zinazosubiri'
                                : 'No deliveries pending')
                          : supplierReceivingsOnly
                          ? (isSwahili
                                ? 'Hakuna supplier receivings'
                                : 'No supplier receivings')
                          : materialInspectionsOnly
                          ? (isSwahili
                                ? 'Hakuna ukaguzi wa vifaa'
                                : 'No material inspections')
                          : isSwahili
                          ? 'Hakuna data ya ununuzi'
                          : 'No procurement data',
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
                if (!materialRequestsOnly &&
                    !supplierQuotationsOnly &&
                    !quotationComparisonsOnly &&
                    !purchaseOrdersOnly &&
                    !recordDeliveriesOnly &&
                    !supplierReceivingsOnly &&
                    !materialInspectionsOnly &&
                    dashboard.isNotEmpty)
                  _DashboardSummary(
                    dashboard: dashboard,
                    isDarkMode: isDarkMode,
                    isSwahili: isSwahili,
                  ),
                if (!materialRequestsOnly &&
                    !supplierQuotationsOnly &&
                    !quotationComparisonsOnly &&
                    !purchaseOrdersOnly &&
                    !recordDeliveriesOnly &&
                    !supplierReceivingsOnly &&
                    !materialInspectionsOnly &&
                    pendingActions.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili
                        ? 'Hatua Zinazohitajika'
                        : 'Actions Required',
                  ),
                  _ActionRequiredCard(
                    actions: pendingActions,
                    isDarkMode: isDarkMode,
                    isSwahili: isSwahili,
                  ),
                ],
                if (!materialRequestsOnly &&
                    !supplierQuotationsOnly &&
                    !quotationComparisonsOnly &&
                    !purchaseOrdersOnly &&
                    !recordDeliveriesOnly &&
                    !supplierReceivingsOnly &&
                    !materialInspectionsOnly &&
                    activeProjects.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili
                        ? 'Miradi Inayoendelea'
                        : 'Active Projects',
                  ),
                  ...activeProjects
                      .take(6)
                      .map(
                        (project) => _ActionListCard(
                          title: project['name']?.toString() ?? 'Project',
                          subtitle:
                              '${project['material_requests_count'] ?? 0} requests',
                          trailing: '${project['boqs_count'] ?? 0} BOQs',
                          badgeColor: const Color(0xFF3B82F6),
                          isDarkMode: isDarkMode,
                        ),
                      ),
                ],
                if (!materialRequestsOnly &&
                    !supplierQuotationsOnly &&
                    !quotationComparisonsOnly &&
                    !purchaseOrdersOnly &&
                    !recordDeliveriesOnly &&
                    !supplierReceivingsOnly &&
                    !materialInspectionsOnly &&
                    lowStockItems.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili
                        ? 'Tahadhari ya Stock Ndogo'
                        : 'Low Stock Alerts',
                  ),
                  ...lowStockItems
                      .take(6)
                      .map(
                        (item) => _ActionListCard(
                          title: item['item_name']?.toString() ?? 'Item',
                          subtitle: item['project_name']?.toString() ?? '',
                          trailing: _formatNumber(item['quantity_available']),
                          badgeColor: _stockColor(
                            item['stock_status']?.toString(),
                          ),
                          isDarkMode: isDarkMode,
                        ),
                      ),
                ],
                if (!supplierQuotationsOnly &&
                    !quotationComparisonsOnly &&
                    !purchaseOrdersOnly &&
                    !recordDeliveriesOnly &&
                    !supplierReceivingsOnly &&
                    !materialInspectionsOnly &&
                    (pendingDashboardRequests.isNotEmpty ||
                        requests.isNotEmpty)) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Maombi ya Vifaa' : 'Material Requests',
                  ),
                  ...((materialRequestsOnly
                          ? requests.cast<Map<String, dynamic>>()
                          : (pendingDashboardRequests.isNotEmpty
                                ? pendingDashboardRequests
                                : requests.cast<Map<String, dynamic>>()))
                      .map(
                        (req) => _RequestCard(
                          request: req,
                          isDarkMode: isDarkMode,
                          isSwahili: isSwahili,
                          onEdit: () => _showMaterialRequestForm(
                            context,
                            ref,
                            request: req,
                            isSwahili: isSwahili,
                          ),
                          onDelete: () => _deleteMaterialRequest(
                            context,
                            ref,
                            req['id'] as int?,
                            isSwahili: isSwahili,
                          ),
                          onSubmit: () => _submitMaterialRequest(
                            context,
                            ref,
                            req['id'] as int?,
                            isSwahili: isSwahili,
                          ),
                          onTap: () => _showMaterialRequestDetails(
                            context,
                            ref,
                            req['id'] as int?,
                          ),
                        ),
                      )),
                ],
                if (quotationComparisonsOnly) ...[
                  _buildComparisonSearchAndFilter(
                    context,
                    ref,
                    isSwahili,
                    isDarkMode,
                    comparisonSearch,
                    comparisonFilter,
                  ),
                ],
                if (purchaseOrdersOnly) ...[
                  _buildComparisonSearchAndFilter(
                    context,
                    ref,
                    isSwahili,
                    isDarkMode,
                    purchaseSearch,
                    purchaseFilter,
                  ),
                ],
                if (recordDeliveriesOnly) ...[
                  _buildComparisonSearchAndFilter(
                    context,
                    ref,
                    isSwahili,
                    isDarkMode,
                    recordDeliverySearch,
                    recordDeliveryFilter,
                  ),
                ],
                if (supplierReceivingsOnly) ...[
                  _buildComparisonSearchAndFilter(
                    context,
                    ref,
                    isSwahili,
                    isDarkMode,
                    supplierReceivingSearch,
                    supplierReceivingFilter,
                  ),
                ],
                if (materialInspectionsOnly) ...[
                  _buildComparisonSearchAndFilter(
                    context,
                    ref,
                    isSwahili,
                    isDarkMode,
                    materialInspectionSearch,
                    materialInspectionFilter,
                  ),
                ],
                if (quotationComparisonsOnly ||
                    (!purchaseOrdersOnly &&
                        !recordDeliveriesOnly &&
                        !supplierReceivingsOnly &&
                        !materialInspectionsOnly &&
                        (recentComparisons.isNotEmpty ||
                            quotations.isNotEmpty))) ...[
                  _SectionHeader(
                    title: quotationComparisonsOnly
                        ? (isSwahili
                              ? 'Milinganisho ya Nukuu'
                              : 'Quotation Comparisons')
                        : supplierQuotationsOnly
                        ? (isSwahili
                              ? 'Nukuu za Wasambazaji'
                              : 'Supplier Quotations')
                        : (isSwahili
                              ? 'Mlinganisho wa Hivi Karibuni'
                              : 'Recent Comparisons'),
                  ),
                  ...(quotationComparisonsOnly
                          ? comparisons
                          : ((supplierQuotationsOnly
                                    ? quotations
                                    : (recentComparisons.isNotEmpty
                                          ? recentComparisons
                                          : quotations))
                                .take(
                                  supplierQuotationsOnly
                                      ? quotations.length
                                      : 5,
                                )))
                      .map(
                        (record) => _CompactRecordCard(
                          title:
                              record['comparison_number'] as String? ??
                              record['quotation_number'] as String? ??
                              'Comparison',
                          subtitle: quotationComparisonsOnly
                              ? [
                                      record['material_request_number']
                                          as String?,
                                      record['project_name'] as String?,
                                      record['selected_supplier_name']
                                          as String?,
                                    ]
                                    .whereType<String>()
                                    .where((value) => value.isNotEmpty)
                                    .join(' • ')
                              : record['supplier_name'] as String? ??
                                    record['supplier']?['name'] as String? ??
                                    record['project_name'] as String? ??
                                    record['material_request']?['project_name']
                                        as String? ??
                                    '',
                          status: record['status'] as String? ?? '',
                          meta: quotationComparisonsOnly
                              ? [
                                  _metaLabel(
                                    Icons.calendar_today_rounded,
                                    _formatDate(
                                      record['comparison_date'] as String? ??
                                          record['created_at'] as String?,
                                    ),
                                  ),
                                  _metaLabel(
                                    Icons.attach_money_rounded,
                                    _formatCurrency(record['selected_amount']),
                                  ),
                                  _metaLabel(
                                    Icons.format_list_numbered_rounded,
                                    '${record['quotation_count'] ?? 0} quotes',
                                  ),
                                ]
                              : [
                                  _metaLabel(
                                    Icons.calendar_today_rounded,
                                    _formatDate(
                                      record['created_at'] as String? ??
                                          record['delivery_date'] as String?,
                                    ),
                                  ),
                                ],
                          color: const Color(0xFF3B82F6),
                          isDarkMode: isDarkMode,
                          menuActions: supplierQuotationsOnly
                              ? _supplierQuotationActions(
                                  record,
                                  isSwahili: isSwahili,
                                  onEdit: () => _showSupplierQuotationForm(
                                    context,
                                    ref,
                                    quotation: record,
                                    isSwahili: isSwahili,
                                  ),
                                  onDelete: () => _deleteSupplierQuotation(
                                    context,
                                    ref,
                                    record['id'] as int?,
                                    isSwahili: isSwahili,
                                  ),
                                )
                              : null,
                          onTap: () => quotationComparisonsOnly
                              ? _showQuotationComparisonDetails(
                                  context,
                                  ref,
                                  record['id'] as int?,
                                )
                              : _showSupplierQuotationDetails(
                                  context,
                                  ref,
                                  record['id'] as int?,
                                ),
                        ),
                      ),
                ],
                if (purchaseOrdersOnly ||
                    (!materialRequestsOnly &&
                        !supplierQuotationsOnly &&
                        !quotationComparisonsOnly &&
                        !recordDeliveriesOnly &&
                        !supplierReceivingsOnly &&
                        !materialInspectionsOnly &&
                        (recentPurchases.isNotEmpty ||
                            purchases.isNotEmpty))) ...[
                  _SectionHeader(
                    title: purchaseOrdersOnly
                        ? (isSwahili ? 'Maagizo ya Ununuzi' : 'Purchase Orders')
                        : (isSwahili
                              ? 'Manunuzi ya Hivi Karibuni'
                              : 'Recent Purchases'),
                  ),
                  ...((purchaseOrdersOnly
                              ? purchaseOrders
                              : (recentPurchases.isNotEmpty
                                    ? recentPurchases
                                    : purchases))
                          .take(purchaseOrdersOnly ? purchaseOrders.length : 5))
                      .map(
                        (purchase) => _CompactRecordCard(
                          title:
                              purchase['document_number'] as String? ??
                              purchase['purchase_number'] as String? ??
                              'Purchase',
                          subtitle: purchaseOrdersOnly
                              ? [
                                      purchase['project']?['name'] as String?,
                                      purchase['supplier']?['name'] as String?,
                                      purchase['material_request']?['request_number']
                                          as String?,
                                    ]
                                    .whereType<String>()
                                    .where((value) => value.isNotEmpty)
                                    .join(' • ')
                              : purchase['supplier_name'] as String? ??
                                    purchase['supplier']?['name'] as String? ??
                                    purchase['project']?['project_name']
                                        as String? ??
                                    purchase['project_name'] as String? ??
                                    purchase['project']?['name'] as String? ??
                                    '',
                          status: purchase['status'] as String? ?? '',
                          meta: purchaseOrdersOnly
                              ? [
                                  _metaLabel(
                                    Icons.attach_money_rounded,
                                    _formatCurrency(purchase['total_amount']),
                                  ),
                                  _metaLabel(
                                    Icons.receipt_long_rounded,
                                    '${purchase['purchase_items_count'] ?? 0} items',
                                  ),
                                  _metaLabel(
                                    Icons.event_rounded,
                                    _formatDate(purchase['date'] as String?),
                                  ),
                                ]
                              : [
                                  _metaLabel(
                                    Icons.attach_money_rounded,
                                    _formatCurrency(purchase['total_amount']),
                                  ),
                                  _metaLabel(
                                    Icons.local_shipping_rounded,
                                    _formatDate(
                                      purchase['delivery_date'] as String?,
                                    ),
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
                if (!materialRequestsOnly &&
                    !supplierQuotationsOnly &&
                    !quotationComparisonsOnly &&
                    !purchaseOrdersOnly &&
                    !recordDeliveriesOnly &&
                    !supplierReceivingsOnly &&
                    !materialInspectionsOnly &&
                    (recentInspections.isNotEmpty ||
                        inspections.isNotEmpty)) ...[
                  _SectionHeader(
                    title: isSwahili
                        ? 'Ukaguzi wa Vifaa'
                        : 'Material Inspections',
                  ),
                  ...(recentInspections.isNotEmpty
                          ? recentInspections
                          : inspections)
                      .take(5)
                      .map(
                        (inspection) => _CompactRecordCard(
                          title:
                              inspection['inspection_number'] as String? ??
                              'Inspection',
                          subtitle:
                              inspection['project_name'] as String? ??
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
                              _titleCase(
                                inspection['overall_result'] as String?,
                              ),
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
                if (recordDeliveriesOnly) ...[
                  _SectionHeader(
                    title: isSwahili
                        ? 'Manunuzi Yanayosubiri Delivery'
                        : 'Purchase Orders Awaiting Delivery',
                  ),
                  ...pendingDeliveries.map(
                    (delivery) => _CompactRecordCard(
                      title:
                          delivery['document_number'] as String? ??
                          'Purchase Order',
                      subtitle:
                          [
                                delivery['project']?['name'] as String?,
                                delivery['supplier']?['name'] as String?,
                                delivery['material_request']?['request_number']
                                    as String?,
                              ]
                              .whereType<String>()
                              .where((value) => value.isNotEmpty)
                              .join(' - '),
                      status: delivery['status'] as String? ?? '',
                      meta: [
                        _metaLabel(
                          Icons.receipt_long_rounded,
                          '${delivery['purchase_items_count'] ?? 0} items',
                        ),
                        _metaLabel(
                          Icons.check_circle_outline_rounded,
                          '${delivery['fully_received_count'] ?? 0} received',
                        ),
                        _metaLabel(
                          Icons.pending_actions_rounded,
                          '${delivery['pending_count'] ?? 0} pending',
                        ),
                      ],
                      color: const Color(0xFF0EA5E9),
                      isDarkMode: isDarkMode,
                      onTap: () => _showRecordDeliveryForm(
                        context,
                        ref,
                        delivery['id'] as int?,
                        isSwahili: isSwahili,
                      ),
                    ),
                  ),
                ],
                if (supplierReceivingsOnly) ...[
                  _SectionHeader(
                    title: isSwahili
                        ? 'Supplier Receivings'
                        : 'Supplier Receivings',
                  ),
                  ...receivings.map(
                    (receiving) => _CompactRecordCard(
                      title:
                          receiving['receiving_number'] as String? ??
                          'Receiving',
                      subtitle:
                          [
                                receiving['purchase']?['document_number']
                                    as String?,
                                receiving['project']?['name'] as String?,
                                receiving['supplier']?['name'] as String?,
                              ]
                              .whereType<String>()
                              .where((value) => value.isNotEmpty)
                              .join(' - '),
                      status: receiving['status'] as String? ?? '',
                      meta: [
                        _metaLabel(
                          Icons.event_rounded,
                          _formatDate(receiving['date'] as String?),
                        ),
                        _metaLabel(
                          Icons.local_shipping_rounded,
                          receiving['delivery_note_number'] as String? ?? '-',
                        ),
                        _metaLabel(
                          Icons.inventory_2_rounded,
                          _formatNumber(receiving['quantity_delivered']),
                        ),
                      ],
                      color: const Color(0xFFF59E0B),
                      isDarkMode: isDarkMode,
                      onTap: () => _showReceivingDetails(
                        context,
                        ref,
                        receiving['id'] as int?,
                      ),
                    ),
                  ),
                ],
                if (materialInspectionsOnly &&
                    pendingInspectionReceivings.isNotEmpty) ...[
                  _SectionHeader(
                    title: isSwahili
                        ? 'Deliveries Zinasubiri Ukaguzi'
                        : 'Deliveries Pending Inspection',
                  ),
                  ...pendingInspectionReceivings.map(
                    (receiving) => _CompactRecordCard(
                      title:
                          receiving['receiving_number'] as String? ??
                          'Receiving',
                      subtitle:
                          [
                                receiving['supplier_name'] as String?,
                                receiving['project_name'] as String?,
                                receiving['purchase_number'] as String?,
                              ]
                              .whereType<String>()
                              .where((value) => value.isNotEmpty)
                              .join(' - '),
                      status: 'pending',
                      meta: [
                        _metaLabel(
                          Icons.event_rounded,
                          _formatDate(receiving['delivery_date'] as String?),
                        ),
                        _metaLabel(
                          Icons.inventory_2_rounded,
                          _formatNumber(receiving['quantity_delivered']),
                        ),
                        _metaLabel(
                          Icons.fact_check_rounded,
                          _titleCase(receiving['condition'] as String?),
                        ),
                      ],
                      color: const Color(0xFFF59E0B),
                      isDarkMode: isDarkMode,
                      onTap: () => _showReceivingDetails(
                        context,
                        ref,
                        receiving['id'] as int?,
                      ),
                    ),
                  ),
                ],
                if (materialInspectionsOnly) ...[
                  _SectionHeader(
                    title: isSwahili ? 'Ukaguzi Wote' : 'All Inspections',
                  ),
                  ...inspections.map(
                    (inspection) => _CompactRecordCard(
                      title:
                          inspection['inspection_number'] as String? ??
                          'Inspection',
                      subtitle:
                          [
                                inspection['project_name'] as String?,
                                inspection['boq_item']?['description']
                                    as String?,
                                inspection['supplier_receiving']?['supplier_name']
                                    as String?,
                              ]
                              .whereType<String>()
                              .where((value) => value.isNotEmpty)
                              .join(' - '),
                      status: inspection['status'] as String? ?? '',
                      meta: [
                        _metaLabel(
                          Icons.event_rounded,
                          _formatDate(inspection['inspection_date'] as String?),
                        ),
                        _metaLabel(
                          Icons.check_circle_outline_rounded,
                          '${_formatNumber(inspection['quantity_accepted'])} accepted',
                        ),
                        _metaLabel(
                          Icons.percent_rounded,
                          '${_formatNumber(inspection['acceptance_rate'])}%',
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
      floatingActionButton: materialRequestsOnly || supplierQuotationsOnly
          ? Padding(
              padding: const EdgeInsets.only(bottom: 80),
              child: FloatingActionButton(
                heroTag: 'procurement_fab',
                onPressed: () {
                  if (materialRequestsOnly) {
                    _showMaterialRequestForm(
                      context,
                      ref,
                      isSwahili: isSwahili,
                    );
                  } else if (supplierQuotationsOnly) {
                    _showSupplierQuotationForm(
                      context,
                      ref,
                      isSwahili: isSwahili,
                    );
                  }
                },
                child: const Icon(Icons.add),
              ),
            )
          : null,
    );
  }
}

class _RequestCard extends StatelessWidget {
  final Map<String, dynamic> request;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback? onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;
  final VoidCallback? onSubmit;

  const _RequestCard({
    required this.request,
    required this.isDarkMode,
    required this.isSwahili,
    this.onTap,
    this.onEdit,
    this.onDelete,
    this.onSubmit,
  });

  @override
  Widget build(BuildContext context) {
    final requestNumber = request['request_number'] as String? ?? '';
    final projectName =
        request['project_name'] as String? ??
        request['project']?['project_name'] as String? ??
        '';
    final status = request['status'] as String? ?? '';
    final createdAt = request['created_at'] as String?;
    final itemCount = request['items_count'] ?? request['items']?.length ?? 0;
    final purpose = request['purpose'] as String? ?? '';
    final priority = _titleCase(request['priority'] as String?);
    final canEdit = ['draft', 'rejected'].contains(status.toLowerCase());
    final canDelete = status.toLowerCase() == 'draft';
    final canSubmit = ['draft', 'rejected'].contains(status.toLowerCase());

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
              crossAxisAlignment: CrossAxisAlignment.start,
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
                        requestNumber.isNotEmpty
                            ? requestNumber
                            : 'Material Request',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
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
                      if (purpose.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          purpose,
                          style: TextStyle(
                            fontSize: 11,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      const SizedBox(height: 6),
                      Wrap(
                        spacing: 10,
                        runSpacing: 6,
                        children: [
                          if (itemCount > 0) ...[
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
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
                            ),
                          ],
                          if (dateStr != null) ...[
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
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
                            ),
                          ],
                          if (priority.isNotEmpty)
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.flag_rounded,
                                  size: 11,
                                  color: isDarkMode
                                      ? Colors.white38
                                      : AppColors.textHint,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  priority,
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: isDarkMode
                                        ? Colors.white38
                                        : AppColors.textHint,
                                  ),
                                ),
                              ],
                            ),
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
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
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
                    if (canEdit || canDelete || canSubmit)
                      PopupMenuButton<String>(
                        icon: Icon(
                          Icons.more_vert_rounded,
                          size: 18,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        onSelected: (value) {
                          if (value == 'edit') onEdit?.call();
                          if (value == 'delete') onDelete?.call();
                          if (value == 'submit') onSubmit?.call();
                        },
                        itemBuilder: (context) => [
                          if (canEdit)
                            PopupMenuItem(
                              value: 'edit',
                              child: Text(isSwahili ? 'Hariri' : 'Edit'),
                            ),
                          if (canSubmit)
                            PopupMenuItem(
                              value: 'submit',
                              child: Text(isSwahili ? 'Wasilisha' : 'Submit'),
                            ),
                          if (canDelete)
                            PopupMenuItem(
                              value: 'delete',
                              child: Text(isSwahili ? 'Futa' : 'Delete'),
                            ),
                        ],
                      )
                    else
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
            itemBuilder: (context, index) =>
                _SummaryCard(data: cards[index], isDarkMode: isDarkMode),
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

  const _SummaryCard({required this.data, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 148,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: data.color.withValues(alpha: 0.18)),
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
        isSwahili
            ? 'Maombi yanayosubiri approval'
            : 'Requests Pending Approval',
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
        isSwahili
            ? 'Delivery zinasubiri ukaguzi'
            : 'Deliveries Pending Inspection',
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
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
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
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
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
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: badgeColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              trailing,
              style: TextStyle(fontWeight: FontWeight.w700, color: badgeColor),
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
        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
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
  final List<_CardMenuAction>? menuActions;

  const _CompactRecordCard({
    required this.title,
    required this.subtitle,
    required this.status,
    required this.meta,
    required this.color,
    required this.isDarkMode,
    this.onTap,
    this.menuActions,
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
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
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
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
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
                        if (menuActions != null && menuActions!.isNotEmpty)
                          PopupMenuButton<String>(
                            icon: Icon(
                              Icons.more_vert_rounded,
                              size: 18,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                            onSelected: (value) {
                              for (final action in menuActions!) {
                                if (action.value == value) {
                                  action.onSelected();
                                  return;
                                }
                              }
                            },
                            itemBuilder: (context) => menuActions!
                                .map(
                                  (action) => PopupMenuItem<String>(
                                    value: action.value,
                                    child: Text(action.label),
                                  ),
                                )
                                .toList(),
                          )
                        else
                          Icon(
                            Icons.arrow_forward_ios_rounded,
                            size: 14,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Wrap(spacing: 10, runSpacing: 6, children: meta),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _CardMenuAction {
  final String value;
  final String label;
  final VoidCallback onSelected;

  const _CardMenuAction({
    required this.value,
    required this.label,
    required this.onSelected,
  });
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
        style: const TextStyle(fontSize: 11, color: AppColors.textHint),
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
      .map(
        (part) => '${part[0].toUpperCase()}${part.substring(1).toLowerCase()}',
      )
      .join(' ');
}

String _formatDays(dynamic value) {
  if (value == null || '$value'.isEmpty) return '';
  return '$value days';
}

Future<void> _deleteMaterialRequest(
  BuildContext context,
  WidgetRef ref,
  int? requestId, {
  required bool isSwahili,
}) async {
  if (requestId == null) return;
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(isSwahili ? 'Thibitisha' : 'Confirm'),
      content: Text(
        isSwahili
            ? 'Unataka kufuta ombi hili la vifaa?'
            : 'Do you want to delete this material request?',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx, false),
          child: Text(isSwahili ? 'Hapana' : 'Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(ctx, true),
          child: Text(isSwahili ? 'Futa' : 'Delete'),
        ),
      ],
    ),
  );

  if (confirmed != true) return;

  try {
    final api = ref.read(apiClientProvider);
    await api.delete('/material-requests/$requestId');
    ref.invalidate(_materialRequestsProvider);
    ref.invalidate(_procurementDashboardProvider);
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('$e'), backgroundColor: Colors.red),
      );
    }
  }
}

Future<void> _submitMaterialRequest(
  BuildContext context,
  WidgetRef ref,
  int? requestId, {
  required bool isSwahili,
}) async {
  if (requestId == null) return;

  try {
    final api = ref.read(apiClientProvider);
    await api.post('/material-requests/$requestId/submit');
    ref.invalidate(_materialRequestsProvider);
    ref.invalidate(_procurementDashboardProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Ombi limewasilishwa kwa idhini.'
                : 'Request submitted for approval.',
          ),
        ),
      );
    }
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('$e'), backgroundColor: Colors.red),
      );
    }
  }
}

Future<void> _showMaterialRequestForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? request,
  required bool isSwahili,
}) async {
  final projects = await ref.read(_projectsProvider.future);
  if (!context.mounted) return;

  final isEdit = request != null;
  final projectMap = request?['project'] is Map<String, dynamic>
      ? request!['project'] as Map<String, dynamic>
      : null;
  int? selectedProjectId =
      request?['project_id'] as int? ?? projectMap?['id'] as int?;
  final purposeCtrl = TextEditingController(
    text:
        request?['purpose'] as String? ??
        request?['description'] as String? ??
        '',
  );
  final requiredDateCtrl = TextEditingController(
    text:
        request?['required_date'] as String? ??
        request?['needed_by_date'] as String? ??
        '',
  );
  String priority = (request?['priority'] as String? ?? 'medium').toLowerCase();
  final items = ((request?['items'] as List?) ?? [])
      .map(
        (item) => Map<String, TextEditingController>.from({
          'material_name': TextEditingController(
            text:
                (item as Map<String, dynamic>)['description'] as String? ??
                item['material_name'] as String? ??
                '',
          ),
          'quantity': TextEditingController(
            text:
                item['quantity_requested']?.toString() ??
                item['quantity']?.toString() ??
                '',
          ),
          'unit': TextEditingController(text: item['unit'] as String? ?? ''),
        }),
      )
      .toList();
  final itemControllers = items.isEmpty
      ? [_newMaterialItemControllers()]
      : items;
  final formKey = GlobalKey<FormState>();

  await showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    builder: (ctx) => StatefulBuilder(
      builder: (ctx, setState) => Padding(
        padding: EdgeInsets.fromLTRB(
          20,
          16,
          20,
          MediaQuery.of(ctx).viewInsets.bottom + 100,
        ),
        child: Form(
          key: formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey[400],
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        isEdit
                            ? (isSwahili ? 'Hariri Ombi' : 'Edit Request')
                            : (isSwahili ? 'Ombi Jipya' : 'New Request'),
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value: selectedProjectId,
                  items: projects
                      .map(
                        (project) => DropdownMenuItem<int>(
                          value: project['id'] as int,
                          child: Text(
                            project['project_name'] as String? ??
                                project['name'] as String? ??
                                'Project',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) =>
                      setState(() => selectedProjectId = value),
                  decoration: const InputDecoration(labelText: 'Project *'),
                  validator: (value) => value == null ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: purposeCtrl,
                  minLines: 2,
                  maxLines: 4,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Madhumuni *' : 'Purpose *',
                  ),
                  validator: (value) =>
                      value == null || value.trim().isEmpty ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: requiredDateCtrl,
                  readOnly: true,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Tarehe Inayohitajika'
                        : 'Required Date',
                  ),
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: ctx,
                      initialDate:
                          DateTime.tryParse(requiredDateCtrl.text) ??
                          DateTime.now(),
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2035),
                    );
                    if (picked != null) {
                      requiredDateCtrl.text = DateFormat(
                        'yyyy-MM-dd',
                      ).format(picked);
                    }
                  },
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: priority,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kipaumbele' : 'Priority',
                  ),
                  items: const ['low', 'medium', 'high', 'urgent']
                      .map(
                        (value) => DropdownMenuItem(
                          value: value,
                          child: Text(_titleCase(value)),
                        ),
                      )
                      .toList(),
                  onChanged: (value) =>
                      setState(() => priority = value ?? 'medium'),
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili ? 'Vitu vinavyohitajika' : 'Requested Items',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 10),
                ...List.generate(itemControllers.length, (index) {
                  final item = itemControllers[index];
                  return _MaterialItemEditor(
                    index: index,
                    controllers: item,
                    canRemove: itemControllers.length > 1,
                    isSwahili: isSwahili,
                    onRemove: () =>
                        setState(() => itemControllers.removeAt(index)),
                  );
                }),
                OutlinedButton.icon(
                  onPressed: () => setState(
                    () => itemControllers.add(_newMaterialItemControllers()),
                  ),
                  icon: const Icon(Icons.add_rounded),
                  label: Text(isSwahili ? 'Ongeza Kitu' : 'Add Item'),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () async {
                      if (!formKey.currentState!.validate()) return;
                      final payload = {
                        if (selectedProjectId != null)
                          'project_id': selectedProjectId,
                        'purpose': purposeCtrl.text.trim(),
                        'required_date': requiredDateCtrl.text.trim().isEmpty
                            ? null
                            : requiredDateCtrl.text.trim(),
                        'priority': priority,
                        'items': itemControllers
                            .map(
                              (item) => {
                                'material_name': item['material_name']!.text
                                    .trim(),
                                'quantity':
                                    double.tryParse(
                                      item['quantity']!.text.trim(),
                                    ) ??
                                    0,
                                'unit': item['unit']!.text.trim(),
                              },
                            )
                            .where(
                              (item) =>
                                  (item['material_name'] as String).isNotEmpty,
                            )
                            .toList(),
                      };

                      try {
                        final api = ref.read(apiClientProvider);
                        if (isEdit) {
                          await api.put(
                            '/material-requests/${request['id']}',
                            data: payload,
                          );
                        } else {
                          await api.post('/material-requests', data: payload);
                        }
                        ref.invalidate(_materialRequestsProvider);
                        ref.invalidate(_procurementDashboardProvider);
                        if (ctx.mounted) Navigator.pop(ctx);
                      } catch (e) {
                        if (ctx.mounted) {
                          ScaffoldMessenger.of(ctx).showSnackBar(
                            SnackBar(
                              content: Text('$e'),
                              backgroundColor: Colors.red,
                            ),
                          );
                        }
                      }
                    },
                    child: Text(isSwahili ? 'Hifadhi' : 'Save'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}

Map<String, TextEditingController> _newMaterialItemControllers() => {
  'material_name': TextEditingController(),
  'quantity': TextEditingController(),
  'unit': TextEditingController(),
};

List<_CardMenuAction> _supplierQuotationActions(
  Map<String, dynamic> quotation, {
  required bool isSwahili,
  required VoidCallback onEdit,
  required VoidCallback onDelete,
}) {
  final status = (quotation['status'] as String? ?? '').toLowerCase();
  final canEdit = status != 'selected';
  final canDelete = status != 'selected';
  return [
    if (canEdit)
      _CardMenuAction(
        value: 'edit',
        label: isSwahili ? 'Hariri' : 'Edit',
        onSelected: onEdit,
      ),
    if (canDelete)
      _CardMenuAction(
        value: 'delete',
        label: isSwahili ? 'Futa' : 'Delete',
        onSelected: onDelete,
      ),
  ];
}

Future<void> _deleteSupplierQuotation(
  BuildContext context,
  WidgetRef ref,
  int? quotationId, {
  required bool isSwahili,
}) async {
  if (quotationId == null) return;
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(isSwahili ? 'Thibitisha' : 'Confirm'),
      content: Text(
        isSwahili
            ? 'Unataka kufuta nukuu hii?'
            : 'Do you want to delete this quotation?',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx, false),
          child: Text(isSwahili ? 'Hapana' : 'Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(ctx, true),
          child: Text(isSwahili ? 'Futa' : 'Delete'),
        ),
      ],
    ),
  );
  if (confirmed != true) return;

  try {
    final api = ref.read(apiClientProvider);
    await api.delete('/procurement/supplier-quotations/$quotationId');
    ref.invalidate(_supplierQuotationsProvider);
    ref.invalidate(_procurementDashboardProvider);
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('$e'), backgroundColor: Colors.red),
      );
    }
  }
}

Future<void> _showSupplierQuotationForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? quotation,
  required bool isSwahili,
}) async {
  final api = ref.read(apiClientProvider);
  final response = await api.get(
    '/procurement/supplier-quotations/reference-data',
  );
  final data = response.data['data'] as Map<String, dynamic>? ?? {};
  final suppliers = (data['suppliers'] as List? ?? const <dynamic>[])
      .cast<Map<String, dynamic>>();
  final materialRequests =
      (data['material_requests'] as List? ?? const <dynamic>[])
          .cast<Map<String, dynamic>>();
  if (!context.mounted) return;

  final isEdit = quotation != null;
  int? selectedSupplierId = quotation?['supplier_id'] as int?;
  int? selectedRequestId = quotation?['material_request_id'] as int?;
  final quotationDateCtrl = TextEditingController(
    text:
        quotation?['quotation_date'] as String? ??
        DateFormat('yyyy-MM-dd').format(DateTime.now()),
  );
  final validUntilCtrl = TextEditingController(
    text: quotation?['valid_until'] as String? ?? '',
  );
  final deliveryDaysCtrl = TextEditingController(
    text: quotation?['delivery_time_days']?.toString() ?? '',
  );
  final paymentTermsCtrl = TextEditingController(
    text: quotation?['payment_terms'] as String? ?? '',
  );
  final vatCtrl = TextEditingController(
    text: quotation?['vat_amount']?.toString() ?? '',
  );
  final notesCtrl = TextEditingController(
    text: quotation?['notes'] as String? ?? '',
  );
  final formKey = GlobalKey<FormState>();

  List<Map<String, TextEditingController>> buildItemControllers(
    int? requestId,
  ) {
    final selectedRequest = materialRequests.firstWhere(
      (item) => item['id'] == requestId,
      orElse: () => <String, dynamic>{},
    );
    final requestItems =
        (selectedRequest['items'] as List? ?? const <dynamic>[])
            .cast<Map<String, dynamic>>();
    final quotationItems = (quotation?['items'] as List? ?? const <dynamic>[])
        .cast<Map<String, dynamic>>();

    return requestItems.map((requestItem) {
      final matched = quotationItems.cast<Map<String, dynamic>?>().firstWhere(
        (item) => item?['material_request_item_id'] == requestItem['id'],
        orElse: () => null,
      );
      return {
        'material_request_item_id': TextEditingController(
          text: '${requestItem['id']}',
        ),
        'description': TextEditingController(
          text:
              matched?['description'] as String? ??
              requestItem['description'] as String? ??
              '',
        ),
        'quantity': TextEditingController(
          text:
              matched?['quantity']?.toString() ??
              requestItem['quantity_requested']?.toString() ??
              '',
        ),
        'unit': TextEditingController(
          text:
              matched?['unit'] as String? ??
              requestItem['unit'] as String? ??
              '',
        ),
        'unit_price': TextEditingController(
          text: matched?['unit_price']?.toString() ?? '',
        ),
        'boq_item_id': TextEditingController(
          text: '${requestItem['boq_item_id'] ?? ''}',
        ),
      };
    }).toList();
  }

  var itemControllers = buildItemControllers(selectedRequestId);

  await showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    builder: (ctx) => StatefulBuilder(
      builder: (ctx, setState) => Padding(
        padding: EdgeInsets.fromLTRB(
          20,
          16,
          20,
          MediaQuery.of(ctx).viewInsets.bottom + 100,
        ),
        child: Form(
          key: formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey[400],
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        isEdit
                            ? (isSwahili ? 'Hariri Nukuu' : 'Edit Quotation')
                            : (isSwahili ? 'Nukuu Mpya' : 'New Quotation'),
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value: selectedRequestId,
                  isExpanded: true,
                  items: materialRequests
                      .map(
                        (item) => DropdownMenuItem<int>(
                          value: item['id'] as int,
                          child: Text(
                            '${item['request_number'] ?? ''} ${item['project_name'] ?? ''}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) {
                    setState(() {
                      selectedRequestId = value;
                      itemControllers = buildItemControllers(value);
                    });
                  },
                  decoration: const InputDecoration(
                    labelText: 'Material Request *',
                  ),
                  validator: (value) => value == null ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<int>(
                  value: selectedSupplierId,
                  items: suppliers
                      .map(
                        (supplier) => DropdownMenuItem<int>(
                          value: supplier['id'] as int,
                          child: Text(
                            supplier['name'] as String? ?? 'Supplier',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) =>
                      setState(() => selectedSupplierId = value),
                  decoration: const InputDecoration(labelText: 'Supplier *'),
                  validator: (value) => value == null ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: quotationDateCtrl,
                  readOnly: true,
                  decoration: const InputDecoration(
                    labelText: 'Quotation Date *',
                  ),
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: ctx,
                      initialDate:
                          DateTime.tryParse(quotationDateCtrl.text) ??
                          DateTime.now(),
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2035),
                    );
                    if (picked != null) {
                      quotationDateCtrl.text = DateFormat(
                        'yyyy-MM-dd',
                      ).format(picked);
                    }
                  },
                  validator: (value) =>
                      value == null || value.trim().isEmpty ? 'Required' : null,
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: validUntilCtrl,
                        readOnly: true,
                        decoration: const InputDecoration(
                          labelText: 'Valid Until',
                        ),
                        onTap: () async {
                          final picked = await showDatePicker(
                            context: ctx,
                            initialDate:
                                DateTime.tryParse(validUntilCtrl.text) ??
                                DateTime.now(),
                            firstDate: DateTime(2020),
                            lastDate: DateTime(2035),
                          );
                          if (picked != null) {
                            validUntilCtrl.text = DateFormat(
                              'yyyy-MM-dd',
                            ).format(picked);
                          }
                        },
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: deliveryDaysCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Delivery Days',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: paymentTermsCtrl,
                  decoration: const InputDecoration(labelText: 'Payment Terms'),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: vatCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'VAT Amount'),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: notesCtrl,
                  minLines: 2,
                  maxLines: 4,
                  decoration: const InputDecoration(labelText: 'Notes'),
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili ? 'Vipengee vya Nukuu' : 'Quotation Items',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 10),
                ...List.generate(itemControllers.length, (index) {
                  final item = itemControllers[index];
                  return _QuotationItemEditor(
                    index: index,
                    controllers: item,
                    isSwahili: isSwahili,
                  );
                }),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () async {
                      if (!formKey.currentState!.validate()) return;
                      final payload = {
                        'material_request_id': selectedRequestId,
                        'supplier_id': selectedSupplierId,
                        'quotation_date': quotationDateCtrl.text.trim(),
                        'valid_until': validUntilCtrl.text.trim().isEmpty
                            ? null
                            : validUntilCtrl.text.trim(),
                        'delivery_time_days': int.tryParse(
                          deliveryDaysCtrl.text.trim(),
                        ),
                        'payment_terms': paymentTermsCtrl.text.trim(),
                        'vat_amount': double.tryParse(vatCtrl.text.trim()) ?? 0,
                        'notes': notesCtrl.text.trim(),
                        'items': itemControllers.map((item) {
                          return {
                            'material_request_item_id': int.tryParse(
                              item['material_request_item_id']!.text.trim(),
                            ),
                            'description': item['description']!.text.trim(),
                            'quantity':
                                double.tryParse(
                                  item['quantity']!.text.trim(),
                                ) ??
                                0,
                            'unit': item['unit']!.text.trim(),
                            'unit_price':
                                double.tryParse(
                                  item['unit_price']!.text.trim(),
                                ) ??
                                0,
                            'boq_item_id': int.tryParse(
                              item['boq_item_id']!.text.trim(),
                            ),
                          };
                        }).toList(),
                      };

                      try {
                        if (isEdit) {
                          await api.put(
                            '/procurement/supplier-quotations/${quotation['id']}',
                            data: payload,
                          );
                        } else {
                          await api.post(
                            '/procurement/supplier-quotations',
                            data: payload,
                          );
                        }
                        ref.invalidate(_supplierQuotationsProvider);
                        ref.invalidate(_procurementDashboardProvider);
                        if (ctx.mounted) Navigator.pop(ctx);
                      } catch (e) {
                        if (ctx.mounted) {
                          ScaffoldMessenger.of(ctx).showSnackBar(
                            SnackBar(
                              content: Text('$e'),
                              backgroundColor: Colors.red,
                            ),
                          );
                        }
                      }
                    },
                    child: Text(isSwahili ? 'Hifadhi' : 'Save'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
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
        final detailAsync = ref.watch(
          _materialRequestDetailProvider(requestId),
        );
        return _ProcurementDetailSheet(
          title: 'Material Request',
          detailAsync: detailAsync,
          builder: (detail) {
            final items = (detail['items'] as List? ?? const <dynamic>[]);
            final requesterName = detail['requester']?['name'] as String? ?? '';
            return [
              _DetailRow(
                label: 'Request Number',
                value: '${detail['request_number'] ?? '-'}',
              ),
              _DetailRow(
                label: 'Project',
                value:
                    detail['project']?['project_name'] as String? ??
                    detail['project_name'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Status',
                value: _titleCase(detail['status'] as String?),
              ),
              _DetailRow(
                label: 'Priority',
                value: _titleCase(detail['priority'] as String?),
              ),
              _DetailRow(
                label: 'Requested By',
                value: requesterName.isEmpty ? '-' : requesterName,
              ),
              _DetailRow(
                label: 'Needed By',
                value: _formatDate(
                  detail['required_date'] as String? ??
                      detail['needed_by_date'] as String?,
                ),
              ),
              _DetailRow(
                label: 'Created',
                value: _formatDate(detail['created_at'] as String?),
              ),
              if ((detail['purpose'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Purpose',
                  child: Text(detail['purpose'] as String),
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
                      final materialName =
                          map['description'] as String? ??
                          map['material_name'] as String? ??
                          'Material';
                      final quantity =
                          map['quantity_requested']?.toString() ??
                          map['quantity']?.toString() ??
                          '-';
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
        final detailAsync = ref.watch(
          _supplierQuotationDetailProvider(quotationId),
        );
        return _ProcurementDetailSheet(
          title: 'Supplier Quotation',
          detailAsync: detailAsync,
          builder: (detail) {
            final items = (detail['items'] as List? ?? const <dynamic>[]);
            return [
              _DetailRow(
                label: 'Quotation Number',
                value: '${detail['quotation_number'] ?? '-'}',
              ),
              _DetailRow(
                label: 'Supplier',
                value: detail['supplier']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Material Request',
                value:
                    detail['material_request']?['request_number'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Project',
                value:
                    detail['material_request']?['project_name'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Status',
                value: _titleCase(detail['status'] as String?),
              ),
              _DetailRow(
                label: 'Quotation Date',
                value: _formatDate(detail['quotation_date'] as String?),
              ),
              _DetailRow(
                label: 'Valid Until',
                value: _formatDate(detail['valid_until'] as String?),
              ),
              _DetailRow(
                label: 'Delivery Days',
                value: _formatDays(detail['delivery_time_days']),
              ),
              _DetailRow(
                label: 'Total Amount',
                value: _formatCurrency(detail['total_amount']),
              ),
              _DetailRow(
                label: 'VAT Amount',
                value: _formatCurrency(detail['vat_amount']),
              ),
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
                      final itemName =
                          map['item_name'] as String? ??
                          map['material_name'] as String? ??
                          'Item';
                      final quantity = map['quantity']?.toString() ?? '-';
                      final unitPrice = _formatCurrency(
                        map['unit_price'] ?? map['price'] ?? map['total_price'],
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

void _showQuotationComparisonDetails(
  BuildContext context,
  WidgetRef ref,
  int? comparisonId,
) {
  if (comparisonId == null) return;
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(
          _quotationComparisonDetailProvider(comparisonId),
        );
        return _ProcurementDetailSheet(
          title: 'Quotation Comparison',
          detailAsync: detailAsync,
          builder: (detail) {
            final quotations =
                (detail['quotations'] as List? ?? const <dynamic>[]);
            final selectedQuotation =
                detail['selected_quotation'] as Map<String, dynamic>?;
            return [
              _DetailRow(
                label: 'Comparison Number',
                value: '${detail['comparison_number'] ?? '-'}',
              ),
              _DetailRow(
                label: 'Material Request',
                value:
                    detail['material_request']?['request_number'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Project',
                value:
                    detail['material_request']?['project_name'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Status',
                value: _titleCase(detail['status'] as String?),
              ),
              _DetailRow(
                label: 'Comparison Date',
                value: _formatDate(detail['comparison_date'] as String?),
              ),
              _DetailRow(
                label: 'Prepared By',
                value: detail['prepared_by']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Approved By',
                value: detail['approved_by']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Approved Date',
                value: _formatDate(detail['approved_date'] as String?),
              ),
              _DetailRow(
                label: 'Selected Supplier',
                value: selectedQuotation?['supplier_name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Selected Quote',
                value: selectedQuotation?['quotation_number'] as String? ?? '-',
                trailing: _formatCurrency(selectedQuotation?['grand_total']),
              ),
              _DetailRow(
                label: 'Quotation Count',
                value: '${detail['quotation_count'] ?? 0}',
              ),
              _DetailRow(
                label: 'Average Price',
                value: _formatCurrency(detail['average_quotation_price']),
              ),
              _DetailRow(
                label: 'Price Variance',
                value: _formatCurrency(detail['price_variance']),
              ),
              _DetailRow(
                label: 'Savings',
                value: _formatCurrency(detail['savings']),
              ),
              if ((detail['recommendation_reason'] as String?)?.isNotEmpty ??
                  false)
                _DetailSection(
                  title: 'Recommendation Reason',
                  child: Text(detail['recommendation_reason'] as String),
                ),
              if (quotations.isNotEmpty)
                _DetailSection(
                  title: 'Compared Quotations',
                  child: Column(
                    children: quotations.map<Widget>((quotation) {
                      final map = quotation as Map<String, dynamic>;
                      final label =
                          '${map['quotation_number'] ?? 'Quotation'}${(map['is_selected'] == true) ? ' (Selected)' : ''}';
                      final value =
                          [
                                map['supplier_name'] as String?,
                                _formatDate(map['quotation_date'] as String?),
                              ]
                              .whereType<String>()
                              .where((part) => part.isNotEmpty)
                              .join(' • ');
                      return _DetailRow(
                        label: label,
                        value: value.isEmpty ? '-' : value,
                        trailing: _formatCurrency(map['grand_total']),
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

Future<void> _showRecordDeliveryForm(
  BuildContext context,
  WidgetRef ref,
  int? purchaseId, {
  required bool isSwahili,
}) async {
  if (purchaseId == null) return;
  final api = ref.read(apiClientProvider);
  final purchaseDetail = await ref.read(
    _purchaseDetailProvider(purchaseId).future,
  );
  final items = (purchaseDetail['purchase_items'] as List? ?? const <dynamic>[])
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();

  if (!context.mounted) return;

  final formKey = GlobalKey<FormState>();
  final deliveryNoteCtrl = TextEditingController();
  final dateCtrl = TextEditingController(
    text: DateFormat('yyyy-MM-dd').format(DateTime.now()),
  );
  final descriptionCtrl = TextEditingController();
  String condition = 'good';
  final quantityControllers = <int, TextEditingController>{};

  for (final item in items) {
    final itemId = item['id'] as int?;
    if (itemId == null) continue;
    final quantity = ((item['quantity'] as num?) ?? 0).toDouble();
    final received = ((item['quantity_received'] as num?) ?? 0).toDouble();
    final pending = (quantity - received).clamp(0, quantity);
    quantityControllers[itemId] = TextEditingController(
      text: pending > 0
          ? pending.toStringAsFixed(pending == pending.roundToDouble() ? 0 : 2)
          : '0',
    );
  }

  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (ctx) => StatefulBuilder(
      builder: (ctx, setState) => Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(ctx).size.height * 0.92,
        ),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Form(
            key: formKey,
            child: SingleChildScrollView(
              padding: EdgeInsets.fromLTRB(
                20,
                12,
                20,
                24 + MediaQuery.of(ctx).viewInsets.bottom,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _SheetHandle(isDarkMode: false),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          isSwahili ? 'Rekodi Delivery' : 'Record Delivery',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(ctx),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    purchaseDetail['document_number'] as String? ??
                        'PO-${purchaseDetail['id'] ?? purchaseId}',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: deliveryNoteCtrl,
                    decoration: InputDecoration(
                      labelText: isSwahili
                          ? 'Namba ya Delivery Note *'
                          : 'Delivery Note Number *',
                    ),
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Required'
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: dateCtrl,
                    decoration: InputDecoration(
                      labelText: isSwahili
                          ? 'Tarehe ya Delivery *'
                          : 'Delivery Date *',
                    ),
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Required'
                        : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: condition,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Hali *' : 'Condition *',
                    ),
                    items: const [
                      DropdownMenuItem(value: 'good', child: Text('Good')),
                      DropdownMenuItem(
                        value: 'partial_damage',
                        child: Text('Partial Damage'),
                      ),
                      DropdownMenuItem(
                        value: 'damaged',
                        child: Text('Damaged'),
                      ),
                    ],
                    onChanged: (value) =>
                        setState(() => condition = value ?? 'good'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: descriptionCtrl,
                    maxLines: 3,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Maelezo' : 'Notes / Description',
                    ),
                  ),
                  const SizedBox(height: 18),
                  Text(
                    isSwahili ? 'Vipengee vya Delivery' : 'Delivery Items',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 10),
                  ...items.map((item) {
                    final itemId = item['id'] as int?;
                    final quantity = ((item['quantity'] as num?) ?? 0)
                        .toDouble();
                    final received = ((item['quantity_received'] as num?) ?? 0)
                        .toDouble();
                    final pending = (quantity - received).clamp(0, quantity);
                    final controller = quantityControllers[itemId]!;
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: Colors.grey.withValues(alpha: 0.2),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item['description'] as String? ??
                                item['material_name'] as String? ??
                                'Item',
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 8),
                          Wrap(
                            spacing: 12,
                            runSpacing: 8,
                            children: [
                              _metaLabel(
                                Icons.shopping_bag_rounded,
                                '${_formatNumber(quantity)} ${item['unit'] ?? ''}'
                                    .trim(),
                              ),
                              _metaLabel(
                                Icons.check_circle_rounded,
                                '${_formatNumber(received)} received',
                              ),
                              _metaLabel(
                                Icons.pending_rounded,
                                '${_formatNumber(pending)} pending',
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            controller: controller,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            decoration: InputDecoration(
                              labelText: isSwahili
                                  ? 'Kiasi kinachowasili'
                                  : 'Qty Delivering',
                            ),
                            validator: (value) {
                              final parsed = double.tryParse(value ?? '');
                              if (parsed == null || parsed < 0)
                                return 'Invalid quantity';
                              if (parsed > pending) {
                                return 'Cannot exceed pending qty';
                              }
                              return null;
                            },
                          ),
                        ],
                      ),
                    );
                  }),
                  const SizedBox(height: 8),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () async {
                        if (!formKey.currentState!.validate()) return;

                        final payloadItems = items
                            .map((item) {
                              final itemId = item['id'] as int?;
                              if (itemId == null) return null;
                              return {
                                'purchase_item_id': itemId,
                                'quantity':
                                    double.tryParse(
                                      quantityControllers[itemId]!.text.trim(),
                                    ) ??
                                    0,
                              };
                            })
                            .whereType<Map<String, dynamic>>()
                            .toList();

                        try {
                          await api.post(
                            '/procurement/purchases/$purchaseId/deliveries',
                            data: {
                              'delivery_note_number': deliveryNoteCtrl.text
                                  .trim(),
                              'date': dateCtrl.text.trim(),
                              'condition': condition,
                              'description': descriptionCtrl.text.trim(),
                              'items': payloadItems,
                            },
                          );
                          ref.invalidate(_pendingDeliveriesProvider);
                          ref.invalidate(_purchaseOrdersProvider);
                          ref.invalidate(_purchasesProvider);
                          ref.invalidate(_purchaseDetailProvider(purchaseId));
                          if (ctx.mounted) Navigator.pop(ctx);
                        } catch (e) {
                          if (ctx.mounted) {
                            ScaffoldMessenger.of(ctx).showSnackBar(
                              SnackBar(
                                content: Text('$e'),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
                        }
                      },
                      icon: const Icon(Icons.local_shipping_rounded),
                      label: Text(
                        isSwahili ? 'Hifadhi Delivery' : 'Record Delivery',
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    ),
  );
}

void _showReceivingDetails(
  BuildContext context,
  WidgetRef ref,
  int? receivingId,
) {
  if (receivingId == null) return;
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_receivingDetailProvider(receivingId));
        return _ProcurementDetailSheet(
          title: 'Supplier Receiving',
          detailAsync: detailAsync,
          builder: (detail) {
            final items =
                (detail['purchase_items'] as List? ?? const <dynamic>[]);
            final inspections =
                (detail['inspections'] as List? ?? const <dynamic>[]);
            return [
              _DetailRow(
                label: 'Receiving Number',
                value: detail['receiving_number'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Purchase Order',
                value: detail['purchase']?['document_number'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Project',
                value: detail['project']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Supplier',
                value: detail['supplier']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Delivery Date',
                value: _formatDate(detail['date'] as String?),
              ),
              _DetailRow(
                label: 'Delivery Note',
                value: detail['delivery_note_number'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Condition',
                value: _titleCase(
                  (detail['condition'] as String?)?.replaceAll('_', ' '),
                ),
              ),
              _DetailRow(
                label: 'Status',
                value: _titleCase(detail['status'] as String?),
              ),
              _DetailRow(
                label: 'Qty Ordered',
                value: _formatNumber(detail['quantity_ordered']),
              ),
              _DetailRow(
                label: 'Qty Delivered',
                value: _formatNumber(detail['quantity_delivered']),
              ),
              _DetailRow(
                label: 'Received By',
                value: detail['received_by']?['name'] as String? ?? '-',
              ),
              if ((detail['description'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Notes',
                  child: Text(detail['description'] as String),
                ),
              if (items.isNotEmpty)
                _DetailSection(
                  title: 'Purchase Order Items',
                  child: Column(
                    children: items.map<Widget>((item) {
                      final map = item as Map<String, dynamic>;
                      return _DetailRow(
                        label:
                            map['description'] as String? ??
                            map['boq_item']?['description'] as String? ??
                            'Item',
                        value:
                            '${_formatNumber(map['quantity'])} ${map['unit'] ?? ''}'
                                .trim(),
                        trailing:
                            '${_formatNumber(map['quantity_received'])} received - ${_titleCase(map['status'] as String?)}',
                      );
                    }).toList(),
                  ),
                ),
              if (inspections.isNotEmpty)
                _DetailSection(
                  title: 'Inspections',
                  child: Column(
                    children: inspections.map<Widget>((inspection) {
                      final map = inspection as Map<String, dynamic>;
                      return _DetailRow(
                        label:
                            map['inspection_number'] as String? ?? 'Inspection',
                        value: _formatDate(map['inspection_date'] as String?),
                        trailing:
                            '${_formatNumber(map['quantity_accepted'])} accepted - ${_titleCase(map['overall_result'] as String?)}',
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
            final items =
                (detail['purchase_items'] as List? ?? const <dynamic>[]);
            return [
              _DetailRow(
                label: 'Purchase Number',
                value:
                    '${detail['document_number'] ?? detail['purchase_number'] ?? 'PO-${detail['id'] ?? '-'}'}',
              ),
              _DetailRow(
                label: 'Supplier',
                value: detail['supplier']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Project',
                value:
                    detail['project']?['name'] as String? ??
                    detail['project']?['project_name'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Material Request',
                value:
                    detail['material_request']?['request_number'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Comparison',
                value:
                    detail['quotation_comparison']?['comparison_number']
                        as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Status',
                value: _titleCase(detail['status'] as String?),
              ),
              _DetailRow(
                label: 'Date',
                value: _formatDate(detail['date'] as String?),
              ),
              _DetailRow(
                label: 'Expected Delivery',
                value: _formatDate(detail['expected_delivery_date'] as String?),
              ),
              _DetailRow(
                label: 'Payment Terms',
                value: detail['payment_terms'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Created By',
                value: detail['user']?['name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Subtotal',
                value: _formatCurrency(detail['amount_vat_exc']),
              ),
              _DetailRow(
                label: 'VAT Amount',
                value: _formatCurrency(detail['vat_amount']),
              ),
              _DetailRow(
                label: 'Total Amount',
                value: _formatCurrency(detail['total_amount']),
              ),
              if ((detail['notes'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Notes',
                  child: Text(detail['notes'] as String),
                ),
              if ((detail['delivery_address'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Delivery Address',
                  child: Text(detail['delivery_address'] as String),
                ),
              if (items.isNotEmpty)
                _DetailSection(
                  title: 'Purchase Items',
                  child: Column(
                    children: items.map<Widget>((item) {
                      final map = item as Map<String, dynamic>;
                      final materialName =
                          map['description'] as String? ??
                          map['boq_item']?['description'] as String? ??
                          map['material']?['name'] as String? ??
                          map['material_name'] as String? ??
                          'Item';
                      final quantity = map['quantity']?.toString() ?? '-';
                      final amount = _formatCurrency(
                        map['total_price'] ??
                            map['amount'] ??
                            map['unit_price'],
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
                        value:
                            detail['delivery']['delivery_number'] as String? ??
                            '-',
                      ),
                      _DetailRow(
                        label: 'Status',
                        value: _titleCase(
                          detail['delivery']['status'] as String?,
                        ),
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
              _DetailRow(
                label: 'Inspection Number',
                value: '${detail['inspection_number'] ?? '-'}',
              ),
              _DetailRow(
                label: 'Project',
                value: detail['project_name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'BOQ Item',
                value: detail['boq_item']?['description'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Supplier',
                value:
                    detail['supplier_receiving']?['supplier_name'] as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Receiving Number',
                value:
                    detail['supplier_receiving']?['receiving_number']
                        as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Purchase Order',
                value:
                    detail['supplier_receiving']?['purchase_number']
                        as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Delivery Note',
                value:
                    detail['supplier_receiving']?['delivery_note_number']
                        as String? ??
                    '-',
              ),
              _DetailRow(
                label: 'Inspector',
                value: detail['inspector_name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Verifier',
                value: detail['verifier_name'] as String? ?? '-',
              ),
              _DetailRow(
                label: 'Status',
                value: _titleCase(detail['status'] as String?),
              ),
              _DetailRow(
                label: 'Inspection Date',
                value: _formatDate(detail['inspection_date'] as String?),
              ),
              _DetailRow(
                label: 'Result',
                value: _titleCase(detail['overall_result'] as String?),
              ),
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
              _DetailRow(
                label: 'Acceptance Rate',
                value: '${_formatNumber(detail['acceptance_rate'])}%',
              ),
              _DetailRow(
                label: 'Stock Updated',
                value: (detail['stock_updated'] == true) ? 'Yes' : 'No',
              ),
              if ((detail['rejection_reason'] as String?)?.isNotEmpty ?? false)
                _DetailSection(
                  title: 'Rejection Reason',
                  child: Text(detail['rejection_reason'] as String),
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
                const Icon(
                  Icons.error_outline,
                  color: AppColors.error,
                  size: 40,
                ),
                const SizedBox(height: 12),
                Text(
                  '$error',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: isDarkMode
                        ? Colors.white70
                        : AppColors.textSecondary,
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
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        title,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                    ),
                    IconButton(
                      icon: Icon(
                        Icons.close,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
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

class _MaterialItemEditor extends StatelessWidget {
  final int index;
  final Map<String, TextEditingController> controllers;
  final bool canRemove;
  final bool isSwahili;
  final VoidCallback onRemove;

  const _MaterialItemEditor({
    required this.index,
    required this.controllers,
    required this.canRemove,
    required this.isSwahili,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${isSwahili ? 'Kitu' : 'Item'} ${index + 1}',
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
              ),
              if (canRemove)
                IconButton(
                  onPressed: onRemove,
                  icon: const Icon(Icons.delete_outline_rounded),
                ),
            ],
          ),
          TextFormField(
            controller: controllers['material_name'],
            decoration: InputDecoration(
              labelText: isSwahili ? 'Jina la Kitu *' : 'Material Name *',
            ),
            validator: (value) =>
                value == null || value.trim().isEmpty ? 'Required' : null,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: controllers['quantity'],
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kiasi *' : 'Quantity *',
                  ),
                  validator: (value) =>
                      value == null || value.trim().isEmpty ? 'Required' : null,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: controllers['unit'],
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kipimo' : 'Unit',
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _QuotationItemEditor extends StatelessWidget {
  final int index;
  final Map<String, TextEditingController> controllers;
  final bool isSwahili;

  const _QuotationItemEditor({
    required this.index,
    required this.controllers,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${isSwahili ? 'Kipengee' : 'Item'} ${index + 1}',
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: controllers['description'],
            decoration: const InputDecoration(labelText: 'Description'),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: controllers['quantity'],
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kiasi *' : 'Quantity *',
                  ),
                  validator: (value) =>
                      value == null || value.trim().isEmpty ? 'Required' : null,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: TextFormField(
                  controller: controllers['unit'],
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kipimo' : 'Unit',
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: controllers['unit_price'],
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Bei kwa Kipimo *' : 'Unit Price *',
            ),
            validator: (value) =>
                value == null || value.trim().isEmpty ? 'Required' : null,
          ),
        ],
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

  const _DetailSection({required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700),
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

  const _DetailRow({required this.label, required this.value, this.trailing});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 13,
              color: isDarkMode ? Colors.white60 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value.isEmpty ? '-' : value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          if (trailing != null && trailing!.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              trailing!,
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white70 : AppColors.textHint,
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
