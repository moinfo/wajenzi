import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/services/export_service.dart';
import '../../../core/router/app_router.dart';
import '../../../core/utils/permissions.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/date_filter_bar.dart';
import '../../widgets/common/motion.dart';

/// The web page's filters: Start Date, End Date, Status, Prepared By.
class _SalesDailyReportFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final String? status;
  final String? userId;

  const _SalesDailyReportFilter({
    this.startDate,
    this.endDate,
    this.status,
    this.userId,
  });

  /// Query for the API (ISO dates).
  Map<String, dynamic> toApiQuery() => {
        if (startDate != null) 'start_date': DateFilterBar.format(startDate),
        if (endDate != null) 'end_date': DateFilterBar.format(endDate),
        if (status != null && status!.isNotEmpty) 'status': status,
        if (userId != null && userId!.isNotEmpty) 'user_id': userId,
      };

  /// Query for the summary downloads: the web's export reads dates as d/m/Y.
  Map<String, dynamic> toExportQuery() => {
        if (startDate != null) 'start_date': DateFormat('dd/MM/yyyy').format(startDate!),
        if (endDate != null) 'end_date': DateFormat('dd/MM/yyyy').format(endDate!),
        if (status != null && status!.isNotEmpty) 'status': status,
        if (userId != null && userId!.isNotEmpty) 'user_id': userId,
      };
}

final _salesDailyReportsFilterProvider =
    StateProvider.autoDispose<_SalesDailyReportFilter>(
      (ref) => _SalesDailyReportFilter(
        startDate: defaultStartDate(),
        endDate: today(),
      ),
    );

final _salesDailyReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final filter = ref.watch(_salesDailyReportsFilterProvider);
      try {
        final response = await api.get(
          '/sales-daily-reports',
          queryParameters: {'per_page': 100, ...filter.toApiQuery()},
        );
        final payload = response.data['data'];
        final items = switch (payload) {
          {'data': List data} => data,
          List data => data,
          _ => const [],
        };
        return {
          'items': _normalizeSalesDailyReports(items),
          'summary': Map<String, dynamic>.from(
            response.data['summary'] as Map? ?? const {},
          ),
          'filters': Map<String, dynamic>.from(
            response.data['filters'] as Map? ?? const {},
          ),
          'meta': Map<String, dynamic>.from(
            response.data['meta'] as Map? ?? const {},
          ),
          'unavailable_on_live': false,
        };
      } catch (e) {
        if ('$e'.contains('404')) {
          return {
            'items': const <Map<String, dynamic>>[],
            'summary': const <String, dynamic>{},
            'filters': const <String, dynamic>{},
            'meta': const <String, dynamic>{},
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

/// The top information (summary cards, by-preparer table, the users of the
/// Prepared By filter). It does not read the list's filters, so it is always
/// there whatever dates are chosen below.
final _salesDailyReportsSummaryProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final response = await ref
          .watch(apiClientProvider)
          .get('/sales-daily-reports', queryParameters: {'per_page': 1});
      return {
        'summary': Map<String, dynamic>.from(
          response.data['summary'] as Map? ?? const {},
        ),
        'filters': Map<String, dynamic>.from(
          response.data['filters'] as Map? ?? const {},
        ),
      };
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

/// The web's Sales Daily Reports page, in the web's order: the four summary
/// cards, "Invoices Written - by Preparer" with its two downloads, the Filters
/// card, and the Reports List.
class SalesDailyReportsScreen extends ConsumerWidget {
  const SalesDailyReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final topAsync = ref.watch(_salesDailyReportsSummaryProvider);
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Ripoti za Mauzo' : 'Sales Daily Reports'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_salesDailyReportsSummaryProvider);
          ref.invalidate(_salesDailyReportsProvider);
        },
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
          children: [
            // Top information: never filtered by the dates below.
            topAsync.when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => _SalesDailyReportErrorView(
                error: _salesDailyReportErrorMessage(e, isSwahili),
                isSwahili: isSwahili,
                onRetry: () => ref.invalidate(_salesDailyReportsSummaryProvider),
              ),
              data: (top) {
                final summary = top['summary'] as Map<String, dynamic>;
                final byUser = (summary['by_user'] as List? ?? const [])
                    .cast<Map<String, dynamic>>();
                return Column(
                  children: [
                    _SdrSummaryCards(summary: summary, isSwahili: isSwahili),
                    const SizedBox(height: 16),
                    _SdrByPreparerCard(byUser: byUser, isSwahili: isSwahili),
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            _SdrFiltersCard(
              canViewAll:
                  (topAsync.valueOrNull?['filters'] as Map?)?['can_view_all'] == true,
              users: ((topAsync.valueOrNull?['filters'] as Map?)?['users'] as List? ??
                      const [])
                  .cast<Map<String, dynamic>>(),
              isSwahili: isSwahili,
            ),
            const SizedBox(height: 16),
            // Only this list follows Start Date / End Date / Status / Prepared By.
            const _SdrListSection(),
          ],
        ),
      ),
    );
  }
}

/// The Reports List with its own loading state, so changing a filter never
/// reloads the cards above it.
class _SdrListSection extends ConsumerWidget {
  const _SdrListSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(_salesDailyReportsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);

    return async.when(
      loading: () => _SdrCard(
        title: isSwahili ? 'Orodha ya Ripoti' : 'Reports List',
        child: const Padding(
          padding: EdgeInsets.all(32),
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
      error: (e, _) => _SdrCard(
        title: isSwahili ? 'Orodha ya Ripoti' : 'Reports List',
        child: _SalesDailyReportErrorView(
          error: _salesDailyReportErrorMessage(e, isSwahili),
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_salesDailyReportsProvider),
        ),
      ),
      data: (payload) => _SdrReportsList(
        items: (payload['items'] as List).cast<Map<String, dynamic>>(),
        isSwahili: isSwahili,
      ),
    );
  }
}
String _sdrMoney(dynamic v) {
  final n = double.tryParse('$v') ?? 0;
  return NumberFormat('#,##0.00', 'en').format(n);
}

/// A white card with a title row, like the web's blocks.
class _SdrCard extends StatelessWidget {
  final String title;
  final Widget? trailing;
  final Widget child;

  const _SdrCard({required this.title, this.trailing, required this.child});

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    return FadeSlideIn(
      child: Container(
      decoration: BoxDecoration(
        color: dark ? const Color(0xFF1E1E2E) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: dark ? Colors.white12 : const Color(0xFFE3E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                ),
                if (trailing != null) ...[
                  const SizedBox(height: 10),
                  trailing!,
                ],
              ],
            ),
          ),
          Divider(height: 1, color: dark ? Colors.white12 : const Color(0xFFE3E8F0)),
          Padding(padding: const EdgeInsets.all(12), child: child),
        ],
      ),
    ));
  }
}

class _SdrStat extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final String? sub;

  /// When set, the value counts up to this number instead of showing [value].
  final double? number;
  final int decimals;

  const _SdrStat({
    required this.label,
    required this.value,
    required this.color,
    this.sub,
    this.number,
    this.decimals = 0,
  });

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
      decoration: BoxDecoration(
        color: dark ? const Color(0xFF1E1E2E) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: dark ? Colors.white12 : const Color(0xFFE3E8F0)),
      ),
      child: Column(
        children: [
          Text(
            label.toUpperCase(),
            style: TextStyle(fontSize: 11, letterSpacing: 0.4, color: AppColors.textSoft(context)),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 6),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: number == null
                ? Text(value, style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: color))
                : AnimatedNumber(
                    value: number!,
                    decimals: decimals,
                    style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: color),
                  ),
          ),
          if (sub != null) ...[
            const SizedBox(height: 4),
            Text(sub!, style: TextStyle(fontSize: 11, color: AppColors.textSoft(context))),
          ],
        ],
      ),
    );
  }
}

class _SdrSummaryCards extends StatelessWidget {
  final Map<String, dynamic> summary;
  final bool isSwahili;
  const _SdrSummaryCards({required this.summary, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _SdrStat(
                label: isSwahili ? 'Ripoti' : 'Reports',
                value: '${summary['reports_count'] ?? 0}',
                number: (double.tryParse('${summary['reports_count']}') ?? 0),
                color: const Color(0xFF3B8EDB),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _SdrStat(
                label: isSwahili ? 'Ankara Zilizoandikwa' : 'Invoices Written',
                value: '${summary['invoices_count'] ?? 0}',
                number: (double.tryParse('${summary['invoices_count']}') ?? 0),
                color: const Color(0xFF1FB6D1),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _SdrStat(
                label: isSwahili ? 'Jumla ya Ankara' : 'Invoice Total',
                value: _sdrMoney(summary['invoices_total']),
                number: (double.tryParse('${summary['invoices_total']}') ?? 0),
                decimals: 2,
                color: const Color(0xFF8BC34A),
                sub: '${isSwahili ? 'Imelipwa' : 'Paid'}: ${_sdrMoney(summary['paid_total'])}',
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _SdrStat(
                label: isSwahili ? 'Jumla Isiyolipwa' : 'Unpaid Total',
                value: _sdrMoney(summary['unpaid_total']),
                number: (double.tryParse('${summary['unpaid_total']}') ?? 0),
                decimals: 2,
                color: const Color(0xFFEF4444),
                sub: '${isSwahili ? 'Kidogo' : 'Partial'}: ${_sdrMoney(summary['partial_total'])}',
              ),
            ),
          ],
        ),
      ],
    );
  }
}

/// Table header cell style used by the two tables.
DataColumn _sdrColumn(String label, {bool numeric = false}) => DataColumn(
      numeric: numeric,
      label: Text(
        label.toUpperCase(),
        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12),
      ),
    );

Widget _sdrTable({required List<DataColumn> columns, required List<DataRow> rows}) {
  return SingleChildScrollView(
    scrollDirection: Axis.horizontal,
    child: ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: DataTable(
        headingRowColor: const WidgetStatePropertyAll(Color(0xFF39A3C8)),
        headingRowHeight: 46,
        dataRowMinHeight: 46,
        dataRowMaxHeight: 62,
        columnSpacing: 22,
        border: TableBorder.symmetric(inside: const BorderSide(color: Color(0xFFE3E8F0))),
        columns: columns,
        rows: rows,
      ),
    ),
  );
}

class _SdrByPreparerCard extends ConsumerWidget {
  final List<Map<String, dynamic>> byUser;
  final bool isSwahili;
  const _SdrByPreparerCard({required this.byUser, required this.isSwahili});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // The web has exactly two buttons here: Download Excel and Download PDF.
    Widget button(String label, IconData icon, Color color, String path) => FilledButton.icon(
          style: FilledButton.styleFrom(
            backgroundColor: color,
            visualDensity: VisualDensity.compact,
            padding: const EdgeInsets.symmetric(horizontal: 12),
          ),
          onPressed: () => downloadExport(context, ref, path),
          icon: Icon(icon, size: 16),
          label: Text(label),
        );

    return _SdrCard(
      title: isSwahili ? 'Ankara Zilizoandikwa — kwa Mwandaaji' : 'Invoices Written — by Preparer',
      trailing: Wrap(
        spacing: 6,
        children: [
          button('Download Excel', Icons.table_chart_outlined, const Color(0xFF4CAF50), 'sales-daily-reports/summary/excel'),
          button('Download PDF', Icons.picture_as_pdf_outlined, const Color(0xFFEF4444), 'sales-daily-reports/summary/pdf'),
        ],
      ),
      child: byUser.isEmpty
          ? Padding(
              padding: const EdgeInsets.all(16),
              child: Center(child: Text(isSwahili ? 'Hakuna data' : 'No data')),
            )
          : _sdrTable(
              columns: [
                _sdrColumn('#'),
                _sdrColumn(isSwahili ? 'Mwandaaji' : 'Prepared By'),
                _sdrColumn(isSwahili ? 'Idadi ya Ankara' : 'Invoices Count', numeric: true),
                _sdrColumn(isSwahili ? 'Jumla ya Ankara' : 'Invoice Total', numeric: true),
                _sdrColumn(isSwahili ? 'Imelipwa' : 'Paid', numeric: true),
                _sdrColumn(isSwahili ? 'Haijalipwa' : 'Unpaid', numeric: true),
              ],
              rows: [
                for (var i = 0; i < byUser.length; i++)
                  DataRow(cells: [
                    DataCell(Text('${i + 1}', style: const TextStyle(color: Color(0xFF3B8EDB)))),
                    DataCell(Text('${byUser[i]['user_name']}')),
                    DataCell(Text('${byUser[i]['invoice_count']}', style: const TextStyle(color: Color(0xFF14A3A8), fontWeight: FontWeight.w700))),
                    DataCell(Text(_sdrMoney(byUser[i]['invoice_total']), style: const TextStyle(color: Color(0xFF14A3A8), fontWeight: FontWeight.w700))),
                    DataCell(Text(_sdrMoney(byUser[i]['paid_total']), style: const TextStyle(color: Color(0xFF7CB342), fontWeight: FontWeight.w700))),
                    DataCell(Text(_sdrMoney(byUser[i]['unpaid_total']), style: const TextStyle(color: Color(0xFFEF4444), fontWeight: FontWeight.w700))),
                  ]),
              ],
            ),
    );
  }
}

/// Filters card: Start Date, End Date, Status, Prepared By (only with
/// "View All Daily Reports"), then Filter, Clear and New Report.
class _SdrFiltersCard extends ConsumerStatefulWidget {
  final bool canViewAll;
  final List<Map<String, dynamic>> users;
  final bool isSwahili;

  const _SdrFiltersCard({
    required this.canViewAll,
    required this.users,
    required this.isSwahili,
  });

  @override
  ConsumerState<_SdrFiltersCard> createState() => _SdrFiltersCardState();
}

class _SdrFiltersCardState extends ConsumerState<_SdrFiltersCard> {
  late _SalesDailyReportFilter _draft = ref.read(_salesDailyReportsFilterProvider);

  @override
  Widget build(BuildContext context) {
    final sw = widget.isSwahili;
    InputDecoration deco(String label) => InputDecoration(
          isDense: true,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
        );

    return _SdrCard(
      title: sw ? 'Vichungi' : 'Filters',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          DateFilterBar(
            padding: EdgeInsets.zero,
            start: _draft.startDate,
            end: _draft.endDate,
            startLabel: sw ? 'Tarehe ya Kuanza' : 'Start Date',
            endLabel: sw ? 'Tarehe ya Mwisho' : 'End Date',
            onStart: (d) => setState(() => _draft = _SalesDailyReportFilter(
                  startDate: d, endDate: _draft.endDate, status: _draft.status, userId: _draft.userId)),
            onEnd: (d) => setState(() => _draft = _SalesDailyReportFilter(
                  startDate: _draft.startDate, endDate: d, status: _draft.status, userId: _draft.userId)),
          ),
          const SizedBox(height: 10),
          LabeledField(
            label: sw ? 'Hali' : 'Status',
            child: DropdownButtonFormField<String?>(
            isExpanded: true,
            initialValue: _draft.status,
            decoration: deco(sw ? 'Hali' : 'Status'),
            items: [
              DropdownMenuItem<String?>(value: null, child: Text(sw ? 'Hali Zote' : 'All Status')),
              for (final st in const ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'])
                DropdownMenuItem<String?>(value: st, child: Text(st[0] + st.substring(1).toLowerCase())),
            ],
            onChanged: (v) => setState(() => _draft = _SalesDailyReportFilter(
                  startDate: _draft.startDate, endDate: _draft.endDate, status: v, userId: _draft.userId)),
          ),
          ),
          if (widget.canViewAll) ...[
            const SizedBox(height: 10),
            LabeledField(
              label: sw ? 'Imeandaliwa Na' : 'Prepared By',
              child: DropdownButtonFormField<String?>(
              isExpanded: true,
              initialValue: widget.users.any((u) => '${u['id']}' == _draft.userId) ? _draft.userId : null,
              decoration: deco(sw ? 'Imeandaliwa Na' : 'Prepared By'),
              items: [
                DropdownMenuItem<String?>(value: null, child: Text(sw ? 'Watumiaji Wote' : 'All Users')),
                for (final u in widget.users)
                  DropdownMenuItem<String?>(
                    value: '${u['id']}',
                    child: Text('${u['name']}', overflow: TextOverflow.ellipsis),
                  ),
              ],
              onChanged: (v) => setState(() => _draft = _SalesDailyReportFilter(
                    startDate: _draft.startDate, endDate: _draft.endDate, status: _draft.status, userId: v)),
            ),
            ),
          ],
          const SizedBox(height: 14),
          Row(
            children: [
              FilledButton(
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF3B8EDB),
                  foregroundColor: Colors.white,
                ),
                onPressed: () => ref.read(_salesDailyReportsFilterProvider.notifier).state = _draft,
                child: Text(sw ? 'Chuja' : 'Filter'),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: () {
                  // Clear goes back to the default: the current month.
                  final reset = _SalesDailyReportFilter(
                    startDate: defaultStartDate(),
                    endDate: today(),
                  );
                  setState(() => _draft = reset);
                  ref.read(_salesDailyReportsFilterProvider.notifier).state = reset;
                },
                child: Text(sw ? 'Futa' : 'Clear'),
              ),
              const Spacer(),
              FilledButton.icon(
                style: FilledButton.styleFrom(backgroundColor: const Color(0xFF4CAF50)),
                onPressed: () => _showSalesDailyReportForm(context, ref),
                icon: const Icon(Icons.add, size: 18),
                label: Text(sw ? 'Ripoti Mpya' : 'New Report'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SdrBadge extends StatelessWidget {
  final String text;
  final Color color;
  const _SdrBadge(this.text, this.color);

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(14)),
        child: Text(text, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12)),
      );
}

/// The Reports List table: Date, Prepared By, Department, Lead Follow-ups,
/// Sales Activities, Client Concerns, Approvals, Status, Actions.
class _SdrReportsList extends ConsumerWidget {
  final List<Map<String, dynamic>> items;
  final bool isSwahili;
  const _SdrReportsList({required this.items, required this.isSwahili});

  Color _statusColor(String status) => switch (status.toUpperCase()) {
        'APPROVED' => const Color(0xFF16A34A),
        'REJECTED' => const Color(0xFFDC2626),
        'SUBMITTED' || 'PENDING' => const Color(0xFF06B6D4),
        _ => const Color(0xFF4B5563),
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sw = isSwahili;
    final canEdit = hasPermission(ref, 'Edit Sales');
    final canDelete = hasPermission(ref, 'Delete Sales');

    Widget action(IconData icon, Color color, VoidCallback onTap, String tip) => Padding(
          padding: const EdgeInsets.only(right: 4),
          child: Tooltip(
            message: tip,
            child: InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(4),
              child: Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(4)),
                child: Icon(icon, size: 16, color: Colors.white),
              ),
            ),
          ),
        );

    return _SdrCard(
      title: sw ? 'Orodha ya Ripoti' : 'Reports List',
      child: items.isEmpty
          ? Padding(
              padding: const EdgeInsets.all(24),
              child: Center(child: Text(sw ? 'Hakuna ripoti za mauzo' : 'No sales daily reports found')),
            )
          : _sdrTable(
              columns: [
                _sdrColumn(sw ? 'Tarehe' : 'Date'),
                _sdrColumn(sw ? 'Mwandaaji' : 'Prepared By'),
                _sdrColumn(sw ? 'Idara' : 'Department'),
                _sdrColumn(sw ? 'Ufuatiliaji' : 'Lead Follow-ups'),
                _sdrColumn(sw ? 'Shughuli' : 'Sales Activities'),
                _sdrColumn(sw ? 'Malalamiko' : 'Client Concerns'),
                _sdrColumn(sw ? 'Idhini' : 'Approvals'),
                _sdrColumn(sw ? 'Hali' : 'Status'),
                _sdrColumn(sw ? 'Vitendo' : 'Actions'),
              ],
              rows: [
                for (final r in items)
                  DataRow(cells: [
                    DataCell(
                      Text(
                        _sdrDate(r['report_date']),
                        style: const TextStyle(color: Color(0xFF3B8EDB), fontWeight: FontWeight.w700),
                      ),
                      onTap: () => _showSalesDailyReportDetail(context, ref, r),
                    ),
                    DataCell(Text('${r['prepared_by_name'] ?? '-'}')),
                    DataCell(Text('${r['department_name'] ?? '-'}')),
                    DataCell(_SdrBadge('${r['followups_count'] ?? 0}', const Color(0xFF06A3C2))),
                    DataCell(Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _SdrBadge('${r['activities_count'] ?? 0}', const Color(0xFF3B9DDB)),
                        Text('Total: ${_sdrMoney(r['activities_total'])}', style: TextStyle(fontSize: 10, color: AppColors.textSoft(context))),
                      ],
                    )),
                    DataCell(_SdrBadge('${r['concerns_count'] ?? 0}', const Color(0xFFE08A00))),
                    DataCell(Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        for (final step in (r['approvals'] as List? ?? const []).whereType<Map>())
                          Tooltip(
                            message: '${step['role_name']}: ${step['action']}',
                            child: Padding(
                              padding: const EdgeInsets.only(right: 4),
                              child: Icon(
                                '${step['action']}'.toLowerCase().contains('approv')
                                    ? Icons.check_circle
                                    : '${step['action']}'.toLowerCase().contains('reject')
                                        ? Icons.cancel
                                        : Icons.schedule,
                                size: 22,
                                color: '${step['action']}'.toLowerCase().contains('approv')
                                    ? const Color(0xFF16A34A)
                                    : '${step['action']}'.toLowerCase().contains('reject')
                                        ? const Color(0xFFDC2626)
                                        : const Color(0xFFFFC857),
                              ),
                            ),
                          ),
                      ],
                    )),
                    DataCell(_SdrBadge('${r['approval_status'] ?? 'PENDING'}'.toUpperCase(), _statusColor('${r['approval_status'] ?? ''}'))),
                    DataCell(Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        action(Icons.visibility, const Color(0xFF4CAF50), () => _showSalesDailyReportDetail(context, ref, r), 'View'),
                        if (canEdit)
                          action(Icons.edit, const Color(0xFF3B9DDB), () => _showSalesDailyReportForm(context, ref, report: r), 'Edit'),
                        if (canDelete)
                          action(Icons.close, const Color(0xFFEF4444), () => _deleteSalesDailyReport(context, ref, r, isSwahili), 'Delete'),
                      ],
                    )),
                  ]),
              ],
            ),
    );
  }
}

String _sdrDate(dynamic raw) {
  final d = DateTime.tryParse('$raw');
  return d == null ? '$raw' : DateFormat('MMM d, yyyy').format(d);
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
                _SalesDailyReportCrudCard(
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

Future<void> _showSalesDailyReportForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? report,
}) async {
  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => _SalesDailyReportFormSheet(report: report),
  );

  if (result == true) {
    ref.invalidate(_salesDailyReportsProvider);
    final id = report == null ? null : _asInt(report['id']);
    if (id != null) {
      ref.invalidate(_salesDailyReportDetailProvider(id));
    }
  }
}

Future<void> _deleteSalesDailyReport(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> report,
  bool isSwahili,
  {bool closeOnSuccess = false}
) async {
  final reportId = _asInt(report['id']);
  if (reportId == null) return;

  final status = (report['status'] as String? ?? '').toLowerCase();
  if (status != 'draft') {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          isSwahili
              ? 'Ni ripoti za rasimu pekee zinaweza kufutwa.'
              : 'Only draft reports can be deleted.',
        ),
        backgroundColor: AppColors.error,
      ),
    );
    return;
  }

  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: Text(isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete'),
      content: Text(
        isSwahili
            ? 'Una uhakika unataka kufuta ripoti hii ya mauzo?'
            : 'Are you sure you want to delete this sales daily report?',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(
            isSwahili ? 'Futa' : 'Delete',
            style: const TextStyle(color: Colors.red),
          ),
        ),
      ],
    ),
  );

  if (confirmed != true) return;

  try {
    final api = ref.read(apiClientProvider);
    await api.delete('/sales-daily-reports/$reportId');
    ref.invalidate(_salesDailyReportsProvider);
    ref.invalidate(_salesDailyReportDetailProvider(reportId));
    if (context.mounted) {
      if (closeOnSuccess) {
        Navigator.of(context).pop();
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Ripoti imefutwa kikamilifu.'
                : 'Report deleted successfully.',
          ),
        ),
      );
    }
  } catch (error) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_salesDailyReportErrorMessage(error, isSwahili)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _SalesDailyReportCrudCard extends ConsumerWidget {
  final Map<String, dynamic> detail;
  final int reportId;
  final bool isSwahili;
  final bool isDarkMode;

  const _SalesDailyReportCrudCard({
    required this.detail,
    required this.reportId,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canEdit = detail['can_edit'] == true;
    final canDelete = (detail['status'] as String? ?? '').toLowerCase() == 'draft';

    if (!canEdit && !canDelete) {
      return const SizedBox.shrink();
    }

    return _DetailCard(
      title: isSwahili ? 'Usimamizi' : 'Manage',
      isDarkMode: isDarkMode,
      children: [
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            if (canEdit)
              OutlinedButton.icon(
                onPressed: () {
                  Navigator.pop(context);
                  _showSalesDailyReportForm(context, ref, report: detail);
                },
                icon: const Icon(Icons.edit_outlined),
                label: Text(isSwahili ? 'Hariri' : 'Edit'),
              ),
            if (canDelete)
              OutlinedButton.icon(
                onPressed: () => _deleteSalesDailyReport(
                  context,
                  ref,
                  detail,
                  isSwahili,
                  closeOnSuccess: true,
                ),
                icon: const Icon(Icons.delete_outline),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.error,
                  side: const BorderSide(color: AppColors.error),
                ),
                label: Text(isSwahili ? 'Futa' : 'Delete'),
              ),
          ],
        ),
      ],
    );
  }
}

class _SalesDailyReportFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? report;

  const _SalesDailyReportFormSheet({this.report});

  @override
  ConsumerState<_SalesDailyReportFormSheet> createState() =>
      _SalesDailyReportFormSheetState();
}

class _SalesDailyReportFormSheetState
    extends ConsumerState<_SalesDailyReportFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _dateController;
  late final TextEditingController _notesController;
  late final TextEditingController _nextStepsController;
  late final TextEditingController _challengesController;
  bool _saving = false;

  bool get _isEditing => widget.report != null;

  @override
  void initState() {
    super.initState();
    _dateController = TextEditingController(
      text: widget.report?['report_date']?.toString() ??
          DateFormat('yyyy-MM-dd').format(DateTime.now()),
    );
    _notesController = TextEditingController(
      text: widget.report?['notes']?.toString() ?? '',
    );
    _nextStepsController = TextEditingController(
      text: widget.report?['next_steps']?.toString() ?? '',
    );
    _challengesController = TextEditingController(
      text: widget.report?['challenges']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _dateController.dispose();
    _notesController.dispose();
    _nextStepsController.dispose();
    _challengesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SafeArea(
        top: false,
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              children: [
                _SalesDailyReportFormHeader(
                  title: _isEditing
                      ? (isSwahili
                            ? 'Hariri Ripoti ya Mauzo'
                            : 'Edit Sales Daily Report')
                      : (isSwahili
                            ? 'Ripoti Mpya ya Mauzo'
                            : 'New Sales Daily Report'),
                  onBack: () => Navigator.pop(context),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    children: [
                      _SalesDailyReportDateField(
                        label: isSwahili ? 'Tarehe ya Ripoti' : 'Report Date',
                        controller: _dateController,
                      ),
                      TextFormField(
                        controller: _notesController,
                        maxLines: 5,
                        decoration: InputDecoration(
                          labelText: isSwahili ? 'Muhtasari wa Siku' : 'Daily Summary',
                        ),
                        validator: (value) {
                          if ((value ?? '').trim().isEmpty) {
                            return isSwahili
                                ? 'Muhtasari wa siku unahitajika'
                                : 'Daily summary is required';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _nextStepsController,
                        maxLines: 4,
                        decoration: InputDecoration(
                          labelText: isSwahili ? 'Hatua Zifuatazo' : 'Next Steps',
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _challengesController,
                        maxLines: 4,
                        decoration: InputDecoration(
                          labelText: isSwahili ? 'Changamoto' : 'Challenges',
                        ),
                      ),
                      const SizedBox(height: 20),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  _isEditing
                                      ? (isSwahili ? 'Sasisha' : 'Update')
                                      : (isSwahili ? 'Hifadhi' : 'Save'),
                                ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);
    final isSwahili = ref.read(isSwahiliProvider);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'report_date': _dateController.text.trim(),
        'notes': _notesController.text.trim(),
        'next_steps': _nextStepsController.text.trim().isEmpty
            ? null
            : _nextStepsController.text.trim(),
        'challenges': _challengesController.text.trim().isEmpty
            ? null
            : _challengesController.text.trim(),
      };

      if (_isEditing) {
        final reportId = _asInt(widget.report?['id']);
        if (reportId == null) {
          throw Exception('Invalid report id');
        }
        await api.put('/sales-daily-reports/$reportId', data: data);
      } else {
        await api.post('/sales-daily-reports', data: data);
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_salesDailyReportErrorMessage(error, isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }
}

class _SalesDailyReportFormHeader extends StatelessWidget {
  final String title;
  final VoidCallback onBack;

  const _SalesDailyReportFormHeader({
    required this.title,
    required this.onBack,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
      decoration: const BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          Container(
            width: 42,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.white38,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              IconButton(
                onPressed: onBack,
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              ),
              Expanded(
                child: Text(
                  title,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(width: 48),
            ],
          ),
        ],
      ),
    );
  }
}

class _SalesDailyReportDateField extends StatelessWidget {
  final String label;
  final TextEditingController controller;

  const _SalesDailyReportDateField({
    required this.label,
    required this.controller,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        readOnly: true,
        decoration: InputDecoration(
          labelText: label,
          suffixIcon: const Icon(Icons.calendar_today_rounded),
        ),
        onTap: () async {
          final initial =
              DateTime.tryParse(controller.text.trim()) ?? DateTime.now();
          final picked = await showDatePicker(
            context: context,
            initialDate: initial,
            firstDate: DateTime(2000),
            lastDate: DateTime(2100),
          );
          if (picked != null) {
            controller.text = DateFormat('yyyy-MM-dd').format(picked);
          }
        },
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
