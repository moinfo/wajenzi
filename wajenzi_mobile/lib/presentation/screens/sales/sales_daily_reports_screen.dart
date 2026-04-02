import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _salesDailyReportsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

class _SalesDailyReportFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final String? status;

  const _SalesDailyReportFilter({this.startDate, this.endDate, this.status});

  _SalesDailyReportFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    String? status,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearStatus = false,
  }) {
    return _SalesDailyReportFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      status: clearStatus ? null : (status ?? this.status),
    );
  }
}

final _salesDailyReportsFilterProvider =
    StateProvider.autoDispose<_SalesDailyReportFilter>(
      (ref) => const _SalesDailyReportFilter(),
    );

final _salesDailyReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final filter = ref.watch(_salesDailyReportsFilterProvider);
      try {
        final response = await api.get(
          '/sales-daily-reports',
          queryParameters: {
            'per_page': 100,
            if (filter.startDate != null)
              'start_date': DateFormat('yyyy-MM-dd').format(filter.startDate!),
            if (filter.endDate != null)
              'end_date': DateFormat('yyyy-MM-dd').format(filter.endDate!),
            if (filter.status != null && filter.status!.isNotEmpty)
              'status': filter.status,
          },
        );
        final payload = response.data['data'];
        final items = switch (payload) {
          {'data': List data} => data,
          List data => data,
          _ => const [],
        };
        final meta = response.data['meta'] as Map<String, dynamic>? ?? const {};
        final normalized = _normalizeSalesDailyReports(items);
        return {
          'items': normalized,
          'meta': {
            ...meta,
            'api_total': _asInt(meta['total']) ?? normalized.length,
            'visible_total': normalized.length,
          },
          'unavailable_on_live': false,
        };
      } catch (e) {
        if ('$e'.contains('404')) {
          return {
            'items': const <Map<String, dynamic>>[],
            'meta': const <String, dynamic>{},
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _salesDailyReportDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/sales-daily-reports/$id');
      return _normalizeSalesDailyReport(
        response.data['data'] as Map<String, dynamic>? ?? const {},
      );
    });

String _salesDailyReportErrorMessage(Object error, bool isSwahili) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) return message;
    }
  }
  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class SalesDailyReportsScreen extends ConsumerWidget {
  const SalesDailyReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final reportsAsync = ref.watch(_salesDailyReportsProvider);
    final filter = ref.watch(_salesDailyReportsFilterProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref
        .watch(_salesDailyReportsSearchProvider)
        .trim()
        .toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Ripoti za Mauzo' : 'Sales Daily Reports'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_salesDailyReportsProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) => ref
                          .read(_salesDailyReportsSearchProvider.notifier)
                          .state = value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta ripoti za mauzo...'
                            : 'Search sales reports...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () => ref
                                    .read(
                                      _salesDailyReportsSearchProvider.notifier,
                                    )
                                    .state = '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    _SalesDailyReportFilters(
                      filter: filter,
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                    ),
                  ],
                ),
              ),
            ),
            reportsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _SalesDailyReportErrorView(
                  error: _salesDailyReportErrorMessage(e, isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_salesDailyReportsProvider),
                ),
              ),
              data: (payload) {
                if (payload['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: _SalesDailyReportEmptyState(
                      isSwahili: isSwahili,
                      message: isSwahili
                          ? 'Sales Daily Reports haipatikani kwenye live API kwa sasa'
                          : 'Sales Daily Reports is not available on the live API right now',
                    ),
                  );
                }

                final allItems = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final meta = payload['meta'] as Map<String, dynamic>;
                final items = search.isEmpty
                    ? allItems
                    : allItems.where((item) {
                        final haystack = [
                          item['report_number'] ?? '',
                          item['prepared_by_name'] ?? '',
                          item['notes'] ?? '',
                          item['challenges'] ?? '',
                          item['next_steps'] ?? '',
                          item['status'] ?? '',
                          item['report_date'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (items.isEmpty) {
                  return SliverFillRemaining(
                    child: _SalesDailyReportEmptyState(
                      isSwahili: isSwahili,
                      message: allItems.isEmpty
                          ? (isSwahili
                              ? 'Hakuna ripoti za mauzo'
                              : 'No sales daily reports found')
                          : (isSwahili
                              ? 'Hakuna matokeo yanayolingana'
                              : 'No reports match your search'),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isSwahili
                                  ? 'Chanzo: Sales Daily Reports API'
                                  : 'Source: Sales Daily Reports API',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: isDarkMode
                                    ? Colors.white70
                                    : AppColors.textSecondary,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: _StatChip(
                                    label: isSwahili ? 'API Jumla' : 'API Total',
                                    value:
                                        '${meta['api_total'] ?? items.length}',
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: _StatChip(
                                    label: isSwahili ? 'Inaonekana' : 'Visible',
                                    value:
                                        '${meta['visible_total'] ?? items.length}',
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: _StatChip(
                                    label: isSwahili ? 'Rasimu' : 'Draft',
                                    value:
                                        '${items.where((e) => (e['status'] as String?) == 'draft').length}',
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      ...items.map(
                        (item) => _SalesDailyReportCard(
                          item: item,
                          isSwahili: isSwahili,
                          isDarkMode: isDarkMode,
                          onTap: () => _showSalesDailyReportDetail(
                            context,
                            ref,
                            item,
                          ),
                        ),
                      ),
                    ]),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

Future<void> _showSalesDailyReportDetail(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> item,
) async {
  final id = _asInt(item['id']);
  if (id == null) return;
  showModalBottomSheet(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (ctx) => _SalesDailyReportDetailSheet(reportId: id, seed: item),
  );
}

class _SalesDailyReportFilters extends ConsumerWidget {
  final _SalesDailyReportFilter filter;
  final bool isSwahili;
  final bool isDarkMode;

  const _SalesDailyReportFilters({
    required this.filter,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ExpansionTile(
      title: Text(isSwahili ? 'Vichungi' : 'Filters'),
      initiallyExpanded:
          filter.startDate != null || filter.endDate != null || filter.status != null,
      childrenPadding: const EdgeInsets.fromLTRB(0, 0, 0, 8),
      backgroundColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      collapsedBackgroundColor: isDarkMode
          ? const Color(0xFF2A2A3E)
          : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      collapsedShape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
      ),
      children: [
        DropdownButtonFormField<String?>(
          value: filter.status,
          decoration: InputDecoration(
            labelText: isSwahili ? 'Hali' : 'Status',
          ),
          items: const [
            DropdownMenuItem(value: null, child: Text('All')),
            DropdownMenuItem(value: 'draft', child: Text('Draft')),
            DropdownMenuItem(value: 'pending', child: Text('Pending')),
            DropdownMenuItem(value: 'approved', child: Text('Approved')),
            DropdownMenuItem(value: 'rejected', child: Text('Rejected')),
          ],
          onChanged: (value) => ref
              .read(_salesDailyReportsFilterProvider.notifier)
              .state = filter.copyWith(status: value, clearStatus: value == null),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.startDate ?? DateTime.now(),
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now(),
                  );
                  if (picked != null) {
                    ref.read(_salesDailyReportsFilterProvider.notifier).state =
                        filter.copyWith(startDate: picked);
                  }
                },
                child: _DateTile(
                  label: isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                  value: filter.startDate,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.endDate ?? DateTime.now(),
                    firstDate: filter.startDate ?? DateTime(2020),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null) {
                    ref.read(_salesDailyReportsFilterProvider.notifier).state =
                        filter.copyWith(endDate: picked);
                  }
                },
                child: _DateTile(
                  label: isSwahili ? 'Tarehe ya Mwisho' : 'End Date',
                  value: filter.endDate,
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _DateTile extends StatelessWidget {
  final String label;
  final DateTime? value;

  const _DateTile({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          const Icon(Icons.calendar_today, size: 20),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[600])),
              Text(
                value != null ? DateFormat('dd MMM yyyy').format(value!) : '-',
                style: const TextStyle(fontSize: 14),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SalesDailyReportCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _SalesDailyReportCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final accent = const Color(0xFF1ABC9C);
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: accent.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      isSwahili ? 'Ripoti ya Mauzo' : 'Sales Report',
                      style: TextStyle(
                        color: accent,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  const Spacer(),
                  Text(
                    item['report_date'] as String? ?? '-',
                    style: TextStyle(
                      fontSize: 12,
                      color:
                          isDarkMode ? Colors.white54 : AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                item['report_number'] as String? ??
                    (isSwahili ? 'Ripoti ya Mauzo' : 'Sales Daily Report'),
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                item['summary'] as String? ?? '-',
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(Icons.person_outline, size: 16, color: accent),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      item['prepared_by_name'] as String? ?? '-',
                      style: TextStyle(
                        fontSize: 12,
                        color:
                            isDarkMode ? Colors.white70 : AppColors.textPrimary,
                      ),
                    ),
                  ),
                  Text(
                    (item['status'] as String? ?? '-').toUpperCase(),
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: accent,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Sales: ${_formatMoney(_toDouble(item['total_sales']))}',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                  ),
                  Expanded(
                    child: Text(
                      'Collections: ${_formatMoney(_toDouble(item['total_collections']))}',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SalesDailyReportDetailSheet extends ConsumerWidget {
  final int reportId;
  final Map<String, dynamic> seed;

  const _SalesDailyReportDetailSheet({
    required this.reportId,
    required this.seed,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncValue = ref.watch(_salesDailyReportDetailProvider(reportId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      height: MediaQuery.of(context).size.height * 0.86,
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: asyncValue.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _SalesDailyReportErrorView(
            error: _salesDailyReportErrorMessage(e, isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_salesDailyReportDetailProvider(reportId)),
          ),
          data: (report) {
            final detail = {...seed, ...report};
            final followups =
                (detail['lead_followups'] as List?)?.cast<Map<String, dynamic>>() ??
                    const [];
            final activities =
                (detail['sales_activities'] as List?)?.cast<Map<String, dynamic>>() ??
                    const [];
            final concerns =
                (detail['client_concerns'] as List?)?.cast<Map<String, dynamic>>() ??
                    const [];

            return ListView(
              padding: const EdgeInsets.all(20),
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        detail['report_number'] as String? ??
                            (isSwahili
                                ? 'Ripoti ya Mauzo'
                                : 'Sales Daily Report'),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                _DetailCard(
                  title: isSwahili ? 'Report Information' : 'Report Information',
                  isDarkMode: isDarkMode,
                  children: [
                    _DetailRow(
                      label: isSwahili ? 'Tarehe' : 'Date',
                      value: detail['report_date'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Aliyeandaa' : 'Prepared by',
                      value: detail['prepared_by_name'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Hali' : 'Status',
                      value: (detail['status'] as String? ?? '-').toUpperCase(),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Mauzo' : 'Total Sales',
                      value: _formatMoney(_toDouble(detail['total_sales'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Makusanyo' : 'Collections',
                      value:
                          _formatMoney(_toDouble(detail['total_collections'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Wateja Wapya' : 'New Customers',
                      value: '${detail['new_customers'] ?? 0}',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Ziara' : 'Visits Made',
                      value: '${detail['visits_made'] ?? 0}',
                      isDarkMode: isDarkMode,
                    ),
                  ],
                ),
                _SalesDailyReportActionCard(
                  detail: detail,
                  reportId: reportId,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                ),
                _DetailCard(
                  title: '1. DAILY SUMMARY',
                  isDarkMode: isDarkMode,
                  children: [
                    _Paragraph(
                      text: (detail['notes'] as String?)?.trim().isNotEmpty == true
                          ? detail['notes'] as String
                          : (isSwahili
                              ? 'Hakuna muhtasari wa siku uliorekodiwa.'
                              : 'No daily summary recorded for this date.'),
                      isDarkMode: isDarkMode,
                    ),
                  ],
                ),
                _DetailCard(
                  title: '2. LEAD FOLLOW-UPS & INTERACTIONS',
                  isDarkMode: isDarkMode,
                  children: followups.isNotEmpty
                      ? followups
                          .map(
                            (f) => _ListTileBlock(
                              title: f['lead_name']?.toString() ??
                                  f['details_discussion']?.toString() ??
                                  '-',
                              subtitle: [
                                f['outcome']?.toString() ?? '',
                                f['next_step']?.toString() ?? '',
                              ].where((e) => e.isNotEmpty).join(' | '),
                              trailing: f['followup_date']?.toString() ?? '',
                              isDarkMode: isDarkMode,
                            ),
                          )
                          .toList()
                      : [
                          _Paragraph(
                            text: isSwahili
                                ? 'Hakuna lead follow-ups zilizorekodiwa kwa tarehe hii.'
                                : 'No lead follow-ups recorded for this date.',
                            isDarkMode: isDarkMode,
                          ),
                        ],
                ),
                _DetailCard(
                  title: '3. SALES ACTIVITY',
                  isDarkMode: isDarkMode,
                  children: [
                    Text(
                      '3.1 Summary of Daily Sales made, Invoice generated, payment made...etc.',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (activities.isNotEmpty) ...activities
                      .map(
                        (a) => _ListTileBlock(
                          title: a['activity']?.toString() ??
                              a['invoice_no']?.toString() ??
                              '-',
                          subtitle:
                              'Invoice: ${a['invoice_no'] ?? '-'} | Amount: ${_formatMoney(_toDouble(a['invoice_sum']))}',
                          trailing:
                              (a['status']?.toString() ?? '-').toUpperCase(),
                          isDarkMode: isDarkMode,
                        ),
                      ),
                    if (activities.isEmpty)
                      _Paragraph(
                        text: isSwahili
                            ? 'Hakuna sales activities zilizorekodiwa kwa tarehe hii.'
                            : 'No sales activities recorded for this date.',
                        isDarkMode: isDarkMode,
                      ),
                  ],
                ),
                _DetailCard(
                  title: '4. ISSUES OR CLIENT CONCERNS',
                  isDarkMode: isDarkMode,
                  children: concerns.isNotEmpty
                      ? concerns
                          .map(
                            (c) => _ListTileBlock(
                              title: c['client_name']?.toString() ?? '-',
                              subtitle: [
                                c['issue_concern']?.toString() ?? '',
                                c['action_taken']?.toString() ?? '',
                              ].where((e) => e.isNotEmpty).join(' | '),
                              trailing: '',
                              isDarkMode: isDarkMode,
                            ),
                          )
                          .toList()
                      : [
                          _Paragraph(
                            text: isSwahili
                                ? 'Hakuna client concerns zilizoripotiwa kwa tarehe hii.'
                                : 'No client concerns reported for this date.',
                            isDarkMode: isDarkMode,
                          ),
                        ],
                ),
                _DetailCard(
                  title: '5. NOTES & RECOMMENDATIONS',
                  isDarkMode: isDarkMode,
                  children: [
                    _Paragraph(
                      text: (detail['next_steps'] as String?)?.trim().isNotEmpty == true
                          ? detail['next_steps'] as String
                          : 'Use this section for any important observations, client preferences, or suggestions for team coordination.',
                      isDarkMode: isDarkMode,
                    ),
                    if ((detail['challenges'] as String?)?.trim().isNotEmpty == true) ...[
                      const SizedBox(height: 10),
                      _Paragraph(
                        text: detail['challenges'] as String,
                        isDarkMode: isDarkMode,
                      ),
                    ],
                  ],
                ),
                _DetailCard(
                  title: 'Approvals',
                  isDarkMode: isDarkMode,
                  children: [
                    _ApprovalRow(
                      role: 'Business Development Manager',
                      isDarkMode: isDarkMode,
                    ),
                    _ApprovalRow(
                      role: 'Managing Director',
                      isDarkMode: isDarkMode,
                    ),
                    _ApprovalRow(
                      role: 'Chief Executive Officer',
                      isDarkMode: isDarkMode,
                    ),
                  ],
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _SalesDailyReportActionCard extends ConsumerWidget {
  final Map<String, dynamic> detail;
  final int reportId;
  final bool isSwahili;
  final bool isDarkMode;

  const _SalesDailyReportActionCard({
    required this.detail,
    required this.reportId,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canSubmit = detail['can_submit'] == true;
    final canApprove = detail['can_approve'] == true;
    final canReject = detail['can_reject'] == true;
    final canReturn = detail['can_return'] == true;
    final showActionCard = canSubmit || canApprove || canReject || canReturn;

    if (!showActionCard) {
      return const SizedBox.shrink();
    }

    final positiveLabel = _approvalActionLabel(detail, isSwahili);

    return _DetailCard(
      title: isSwahili ? 'Vitendo' : 'Actions',
      isDarkMode: isDarkMode,
      children: [
        if (canApprove)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(
              isSwahili
                  ? 'Unaweza kukagua ripoti hii kutoka kwenye app.'
                  : 'You can review this report from the mobile app.',
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
          ),
        if (canSubmit)
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () =>
                  _submitSalesDailyReport(context, ref, reportId, isSwahili),
              icon: const Icon(Icons.send_rounded),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.success,
                foregroundColor: Colors.white,
              ),
              label: Text(isSwahili ? 'Wasilisha' : 'Submit'),
            ),
          ),
        if (canApprove || canReject || canReturn) ...[
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              if (canReject)
                OutlinedButton.icon(
                  onPressed: () =>
                      _rejectSalesDailyReport(context, ref, reportId, isSwahili),
                  icon: const Icon(Icons.close_rounded),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.error,
                    side: const BorderSide(color: AppColors.error),
                  ),
                  label: Text(isSwahili ? 'Kataa' : 'Reject'),
                ),
              if (canApprove || canReject)
                OutlinedButton.icon(
                  onPressed: () => _showReturnUnavailable(context, isSwahili),
                  icon: const Icon(Icons.reply_rounded),
                  label: Text(isSwahili ? 'Rudisha' : 'Return'),
                ),
              if (canApprove)
                ElevatedButton.icon(
                  onPressed: () =>
                      _approveSalesDailyReport(context, ref, reportId, isSwahili),
                  icon: const Icon(Icons.check_circle_rounded),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.success,
                    foregroundColor: Colors.white,
                  ),
                  label: Text(positiveLabel),
                ),
            ],
          ),
          if (!canReturn)
            Padding(
              padding: const EdgeInsets.only(top: 10),
              child: Text(
                isSwahili
                    ? 'Return bado haipo kwenye mobile API ya sales daily reports.'
                    : 'Return is not exposed by the sales daily reports mobile API yet.',
                style: TextStyle(
                  fontSize: 12,
                  color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              ),
            ),
        ],
      ],
    );
  }
}

class _DetailCard extends StatelessWidget {
  final String title;
  final bool isDarkMode;
  final List<Widget> children;

  const _DetailCard({
    required this.title,
    required this.isDarkMode,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode
            ? const Color(0xFF252540)
            : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Paragraph extends StatelessWidget {
  final String text;
  final bool isDarkMode;

  const _Paragraph({required this.text, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 13,
        color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
      ),
    );
  }
}

class _ListTileBlock extends StatelessWidget {
  final String title;
  final String subtitle;
  final String trailing;
  final bool isDarkMode;

  const _ListTileBlock({
    required this.title,
    required this.subtitle,
    required this.trailing,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode
            ? Colors.white.withValues(alpha: 0.04)
            : Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
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
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                if (subtitle.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: TextStyle(
                      fontSize: 12,
                      color:
                          isDarkMode ? Colors.white70 : AppColors.textSecondary,
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (trailing.isNotEmpty) ...[
            const SizedBox(width: 8),
            Text(
              trailing,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _ApprovalRow extends StatelessWidget {
  final String role;
  final bool isDarkMode;

  const _ApprovalRow({required this.role, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              role,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
          Text(
            'Date',
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

String _approvalActionLabel(Map<String, dynamic> detail, bool isSwahili) {
  final action = (detail['next_approval_action'] as String? ?? '').trim();
  if (action.isNotEmpty) {
    final normalized = action.toLowerCase();
    if (normalized == 'check') {
      return isSwahili ? 'Kagua' : 'Check';
    }
    if (normalized == 'approve') {
      return isSwahili ? 'Idhinisha' : 'Approve';
    }
    return '${action[0].toUpperCase()}${action.substring(1)}';
  }

  final status = (detail['status'] as String? ?? '').toLowerCase();
  if (status == 'pending') {
    return isSwahili ? 'Kagua' : 'Check';
  }
  return isSwahili ? 'Idhinisha' : 'Approve';
}

Future<void> _submitSalesDailyReport(
  BuildContext context,
  WidgetRef ref,
  int reportId,
  bool isSwahili,
) async {
  final api = ref.read(apiClientProvider);
  final messenger = ScaffoldMessenger.of(context);

  try {
    await api.post('/sales-daily-reports/$reportId/submit');
    ref.invalidate(_salesDailyReportsProvider);
    ref.invalidate(_salesDailyReportDetailProvider(reportId));
    messenger.showSnackBar(
      SnackBar(
        content: Text(
          isSwahili
              ? 'Ripoti imewasilishwa kwa idhini.'
              : 'Report submitted for approval.',
        ),
      ),
    );
  } catch (error) {
    messenger.showSnackBar(
      SnackBar(
        content: Text(_salesDailyReportErrorMessage(error, isSwahili)),
        backgroundColor: AppColors.error,
      ),
    );
  }
}

Future<void> _approveSalesDailyReport(
  BuildContext context,
  WidgetRef ref,
  int reportId,
  bool isSwahili,
) async {
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: Text(isSwahili ? 'Thibitisha' : 'Confirm'),
      content: Text(
        isSwahili
            ? 'Una uhakika unataka kukagua/kuidhinisha ripoti hii?'
            : 'Are you sure you want to check/approve this report?',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
        ),
        ElevatedButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(isSwahili ? 'Endelea' : 'Continue'),
        ),
      ],
    ),
  );

  if (confirmed != true) return;

  final api = ref.read(apiClientProvider);
  final messenger = ScaffoldMessenger.of(context);

  try {
    await api.post('/sales-daily-reports/$reportId/approve');
    ref.invalidate(_salesDailyReportsProvider);
    ref.invalidate(_salesDailyReportDetailProvider(reportId));
    messenger.showSnackBar(
      SnackBar(
        content: Text(
          isSwahili ? 'Ripoti imeidhinishwa.' : 'Report approved successfully.',
        ),
      ),
    );
  } catch (error) {
    messenger.showSnackBar(
      SnackBar(
        content: Text(_salesDailyReportErrorMessage(error, isSwahili)),
        backgroundColor: AppColors.error,
      ),
    );
  }
}

Future<void> _rejectSalesDailyReport(
  BuildContext context,
  WidgetRef ref,
  int reportId,
  bool isSwahili,
) async {
  final controller = TextEditingController();
  final reason = await showDialog<String>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: Text(isSwahili ? 'Sababu ya kukataa' : 'Reject reason'),
      content: TextField(
        controller: controller,
        maxLines: 4,
        decoration: InputDecoration(
          hintText: isSwahili
              ? 'Andika sababu ya kukataa'
              : 'Write the reason for rejection',
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext),
          child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
        ),
        ElevatedButton(
          onPressed: () =>
              Navigator.pop(dialogContext, controller.text.trim()),
          style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
          child: Text(isSwahili ? 'Kataa' : 'Reject'),
        ),
      ],
    ),
  );
  controller.dispose();

  if (reason == null || reason.isEmpty) return;

  final api = ref.read(apiClientProvider);
  final messenger = ScaffoldMessenger.of(context);

  try {
    await api.post(
      '/sales-daily-reports/$reportId/reject',
      data: {'reason': reason},
    );
    ref.invalidate(_salesDailyReportsProvider);
    ref.invalidate(_salesDailyReportDetailProvider(reportId));
    messenger.showSnackBar(
      SnackBar(
        content: Text(
          isSwahili ? 'Ripoti imekataliwa.' : 'Report rejected successfully.',
        ),
      ),
    );
  } catch (error) {
    messenger.showSnackBar(
      SnackBar(
        content: Text(_salesDailyReportErrorMessage(error, isSwahili)),
        backgroundColor: AppColors.error,
      ),
    );
  }
}

void _showReturnUnavailable(BuildContext context, bool isSwahili) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(
        isSwahili
            ? 'Return bado haijapatikana kwenye mobile API ya ripoti hii.'
            : 'Return is not available on this report mobile API yet.',
      ),
    ),
  );
}

class _SalesDailyReportEmptyState extends StatelessWidget {
  final bool isSwahili;
  final String message;

  const _SalesDailyReportEmptyState({
    required this.isSwahili,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.assessment_outlined, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            message,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 16, color: Colors.grey[600]),
          ),
        ],
      ),
    );
  }
}

class _SalesDailyReportErrorView extends StatelessWidget {
  final String error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SalesDailyReportErrorView({
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
          error,
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

class _StatChip extends StatelessWidget {
  final String label;
  final String value;

  const _StatChip({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF3498DB).withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

List<Map<String, dynamic>> _normalizeSalesDailyReports(dynamic raw) {
  if (raw is! List) return const [];
  return raw
      .whereType<Map>()
      .map((item) => _normalizeSalesDailyReport(Map<String, dynamic>.from(item)))
      .toList();
}

Map<String, dynamic> _normalizeSalesDailyReport(Map<String, dynamic> raw) {
  final report = Map<String, dynamic>.from(raw);
  final status = report['status']?.toString().toLowerCase() ?? 'draft';
  final preparedBy = report['prepared_by_user'] is Map
      ? Map<String, dynamic>.from(report['prepared_by_user'] as Map)
      : report['preparedBy'] is Map
          ? Map<String, dynamic>.from(report['preparedBy'] as Map)
          : <String, dynamic>{};

  List<Map<String, dynamic>> toMapList(dynamic value) {
    if (value is! List) return const [];
    return value.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
  }

  return {
    ...report,
    'id': _asInt(report['id']) ?? report['id'],
    'report_number': report['report_number']?.toString() ??
        report['document_number']?.toString() ??
        'SDR-${report['id'] ?? '-'}',
    'report_date': report['report_date']?.toString() ?? '',
    'prepared_by_name': preparedBy['name']?.toString() ??
        report['prepared_by_name']?.toString() ??
        '-',
    'status': status,
    'total_sales': _toDouble(report['total_sales']),
    'total_collections': _toDouble(report['total_collections']),
    'new_customers': _asInt(report['new_customers']) ?? 0,
    'visits_made': _asInt(report['visits_made']) ?? 0,
    'can_edit': report['can_edit'] ?? status == 'draft',
    'can_submit': report['can_submit'] ?? status == 'draft',
    'can_approve': report['can_approve'] ?? status == 'pending',
    'can_reject': report['can_reject'] ?? status == 'pending',
    'can_return': report['can_return'] ?? false,
    'next_approval_action':
        report['next_approval_action']?.toString() ??
        report['approval_action']?.toString() ??
        (status == 'pending' ? 'check' : ''),
    'summary': _buildSalesReportSummary(report),
    'notes': report['notes']?.toString() ??
        report['daily_summary']?.toString() ??
        '',
    'challenges': report['challenges']?.toString() ?? '',
    'next_steps': report['next_steps']?.toString() ??
        report['notes_recommendations']?.toString() ??
        '',
    'lead_followups': toMapList(report['lead_followups']),
    'sales_activities': toMapList(report['sales_activities']),
    'client_concerns': toMapList(report['client_concerns']),
  };
}

String _buildSalesReportSummary(Map<String, dynamic> report) {
  final notes = report['notes']?.toString().trim();
  final nextSteps = report['next_steps']?.toString().trim();
  final challenges = report['challenges']?.toString().trim();
  if (notes != null && notes.isNotEmpty) return notes;
  if (nextSteps != null && nextSteps.isNotEmpty) return nextSteps;
  if (challenges != null && challenges.isNotEmpty) return challenges;
  return 'Sales: ${_formatMoney(_toDouble(report['total_sales']))}, Collections: ${_formatMoney(_toDouble(report['total_collections']))}';
}

int? _asInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse('$value');
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatMoney(double amount) {
  if (amount <= 0) return 'TZS 0';
  return 'TZS ${NumberFormat('#,##0.00', 'en_US').format(amount)}';
}
