import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/export_service.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/motion.dart';

/// The Salary Slips page of the web, part by part: header with the selected
/// period, the Month / Year / Employee toolbar with Show, the three summary
/// cards, Print Payslip and Export PDF, and the payslip document itself
/// (company, employee grid, Employee Income and Deductions panels, Net Salary
/// banner, footer). `GET /salary-slips/slip` returns it as the web renders it.

typedef _SlipKey = ({int? month, int? year, int? staffId});

final _slipProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, _SlipKey>((ref, key) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/salary-slips/slip',
    queryParameters: {
      if (key.month != null) 'month': key.month,
      if (key.year != null) 'year': key.year,
      if (key.staffId != null) 'staff_id': key.staffId,
    },
  );
  return Map<String, dynamic>.from(response.data['data'] as Map);
});

const _navy = Color(0xFF193340);
const _teal = Color(0xFF1AA39A);

int? _toInt(dynamic v) => int.tryParse('$v');

class SalarySlipsScreen extends ConsumerStatefulWidget {
  const SalarySlipsScreen({super.key});

  @override
  ConsumerState<SalarySlipsScreen> createState() => _SalarySlipsScreenState();
}

class _SalarySlipsScreenState extends ConsumerState<SalarySlipsScreen> {
  /// What "Show" last applied. Null parts use the web's defaults (the latest
  /// approved payroll and its first employee).
  _SlipKey _applied = (month: null, year: null, staffId: null);

  /// What the three dropdowns currently show (applied only on Show).
  int? _month;
  int? _year;
  int? _staffId;
  bool _synced = false;

  void _syncFrom(Map<String, dynamic> data) {
    if (_synced) return;
    int? selected(List list) {
      for (final o in list) {
        if (o is Map && o['selected'] == true) return _toInt(o['id']);
      }
      return null;
    }

    _month = selected(data['months'] as List? ?? const []);
    _year = selected(data['years'] as List? ?? const []);
    _staffId = selected(data['staff'] as List? ?? const []);
    _synced = true;
  }

  void _show() {
    setState(() => _applied = (month: _month, year: _year, staffId: _staffId));
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_slipProvider(_applied));
    final isSwahili = ref.watch(isSwahiliProvider);
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Salary Slip' : 'Salary Slip'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_slipProvider(_applied).future),
        child: async.when(
          loading: () => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: const [
              SizedBox(height: 160),
              Center(child: CircularProgressIndicator()),
            ],
          ),
          error: (e, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              const SizedBox(height: 120),
              Padding(
                padding: const EdgeInsets.all(24),
                child: Center(
                  child: Text(
                    e is DioException && e.response?.statusCode == 403
                        ? (isSwahili ? 'Huna ruhusa ya kuona ukurasa huu' : 'You do not have access to this page')
                        : (isSwahili ? 'Imeshindwa kupakia salary slip' : 'Could not load the salary slip'),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
              Center(
                child: TextButton.icon(
                  onPressed: () => ref.invalidate(_slipProvider(_applied)),
                  icon: const Icon(Icons.refresh),
                  label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                ),
              ),
            ],
          ),
          data: (data) {
            _syncFrom(data);
            return _SlipBody(
              data: data,
              month: _month,
              year: _year,
              staffId: _staffId,
              applied: _applied,
              isSwahili: isSwahili,
              onMonth: (v) => setState(() => _month = v),
              onYear: (v) => setState(() => _year = v),
              onStaff: (v) => setState(() => _staffId = v),
              onShow: _show,
            );
          },
        ),
      ),
    );
  }
}

class _SlipBody extends ConsumerWidget {
  final Map<String, dynamic> data;
  final int? month;
  final int? year;
  final int? staffId;
  final _SlipKey applied;
  final bool isSwahili;
  final ValueChanged<int?> onMonth;
  final ValueChanged<int?> onYear;
  final ValueChanged<int?> onStaff;
  final VoidCallback onShow;

  const _SlipBody({
    required this.data,
    required this.month,
    required this.year,
    required this.staffId,
    required this.applied,
    required this.isSwahili,
    required this.onMonth,
    required this.onYear,
    required this.onStaff,
    required this.onShow,
  });

  List<Map<String, dynamic>> _list(String key) =>
      (data[key] as List? ?? const []).whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();

  /// The slip being shown, as the web resolved it (its own defaults included).
  ({int? month, int? year, int? staffId}) _shown() {
    int? sel(String key) {
      for (final o in _list(key)) {
        if (o['selected'] == true) return _toInt(o['id']);
      }
      return null;
    }

    return (month: sel('months'), year: sel('years'), staffId: sel('staff'));
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final hasSlip = data['has_payslip'] == true;
    final summary = _list('summary');
    final shown = _shown();

    final cardColor = dark ? const Color(0xFF1E1E2E) : Colors.white;
    final border = dark ? const Color(0xFF343448) : const Color(0xFFE3E8F0);
    final sub = dark ? const Color(0xFFB0B0C8) : const Color(0xFF6B7A86);

    Map<String, dynamic> panel(String key) =>
        data[key] is Map ? Map<String, dynamic>.from(data[key] as Map) : const {};

    final query = {
      if (shown.staffId != null) 'staff_id': shown.staffId,
      if (shown.month != null) 'month': shown.month,
      if (shown.year != null) 'year': shown.year,
    };

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
      children: [
        // ── header ───────────────────────────────────────────────────────
        FadeSlideIn(
          child: Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              gradient: const LinearGradient(
                colors: [_navy, Color(0xFF1B6F73), _teal],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${data['title'] ?? 'Salary Slip'}',
                        style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${data['subtitle'] ?? ''}',
                        style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 12),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0x26FFFFFF),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0x40FFFFFF)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        isSwahili ? 'MUDA ULIOCHAGULIWA' : 'SELECTED PERIOD',
                        style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 9, letterSpacing: 0.6),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        '${data['period_label'] ?? ''}',
                        style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),

        // ── toolbar: Month, Year, Employee, Show ─────────────────────────
        FadeSlideIn(
          index: 1,
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Expanded(
                      flex: 3,
                      child: _Labeled(
                        label: isSwahili ? 'MWEZI' : 'MONTH',
                        color: sub,
                        child: _Drop(items: _list('months'), value: month, onChanged: onMonth),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 2,
                      child: _Labeled(
                        label: isSwahili ? 'MWAKA' : 'YEAR',
                        color: sub,
                        child: _Drop(items: _list('years'), value: year, onChanged: onYear),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                _Labeled(
                  label: isSwahili ? 'MFANYAKAZI' : 'EMPLOYEE',
                  color: sub,
                  child: _Drop(items: _list('staff'), value: staffId, onChanged: onStaff),
                ),
                const SizedBox(height: 12),
                FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF20C4BA),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: onShow,
                  icon: const Icon(Icons.search_rounded, size: 20),
                  label: Text(isSwahili ? 'Onyesha' : 'Show'),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),

        if (!hasSlip)
          FadeSlideIn(
            index: 2,
            child: Container(
              padding: const EdgeInsets.all(22),
              decoration: BoxDecoration(
                color: dark ? const Color(0xFF2A2418) : const Color(0xFFFFF8E6),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFF0C36D)),
              ),
              child: Column(
                children: [
                  const Icon(Icons.error_outline_rounded, size: 44, color: Color(0xFFE29A16)),
                  const SizedBox(height: 10),
                  Text(
                    isSwahili ? 'Salary slip haipatikani' : 'Salary slip not available',
                    style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 6),
                  Text('${data['empty_message'] ?? ''}', textAlign: TextAlign.center, style: TextStyle(color: sub)),
                ],
              ),
            ),
          )
        else ...[
          // ── summary cards ─────────────────────────────────────────────
          for (var i = 0; i < summary.length; i++)
            FadeSlideIn(
              index: 2 + i,
              child: Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _SummaryTile(
                  label: '${summary[i]['label']}',
                  value: '${summary[i]['value']}',
                  kind: i,
                  cardColor: cardColor,
                  border: border,
                  sub: sub,
                ),
              ),
            ),

          // ── print / export ────────────────────────────────────────────
          Row(
            children: [
              Expanded(
                child: FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFF111B2B),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () => downloadFile(
                    context,
                    ref,
                    '/salary-slips/payslip/pdf',
                    query: query,
                    openAfter: true,
                  ),
                  icon: const Icon(Icons.print_rounded, size: 18),
                  label: Text(isSwahili ? 'Chapisha' : 'Print Payslip'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: FilledButton.icon(
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFFF59E0B),
                    foregroundColor: const Color(0xFF1B1B1B),
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () => downloadFile(
                    context,
                    ref,
                    '/salary-slips/payslip/pdf',
                    query: query,
                  ),
                  icon: const Icon(Icons.picture_as_pdf_rounded, size: 18),
                  label: const Text('Export PDF'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // ── the payslip document ──────────────────────────────────────
          FadeSlideIn(
            index: 6,
            child: _PayslipDocument(
              data: data,
              income: panel('income'),
              deductions: panel('deductions'),
              net: panel('net'),
              footer: panel('footer'),
              company: panel('company'),
              employee: _list('employee'),
              dark: dark,
              cardColor: cardColor,
              border: border,
              sub: sub,
            ),
          ),
        ],
      ],
    );
  }
}

class _Labeled extends StatelessWidget {
  final String label;
  final Color color;
  final Widget child;
  const _Labeled({required this.label, required this.color, required this.child});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 5, left: 2),
          child: Text(label, style: TextStyle(color: color, fontSize: 10.5, fontWeight: FontWeight.w700, letterSpacing: 0.6)),
        ),
        child,
      ],
    );
  }
}

class _Drop extends StatelessWidget {
  final List<Map<String, dynamic>> items;
  final int? value;
  final ValueChanged<int?> onChanged;
  const _Drop({required this.items, required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    final ids = items.map((e) => _toInt(e['id'])).toList();
    return DropdownButtonFormField<int>(
      isExpanded: true,
      initialValue: ids.contains(value) ? value : null,
      decoration: InputDecoration(
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      ),
      items: [
        for (final o in items)
          DropdownMenuItem<int>(
            value: _toInt(o['id']),
            child: Text('${o['name']}', overflow: TextOverflow.ellipsis),
          ),
      ],
      onChanged: onChanged,
    );
  }
}

class _SummaryTile extends StatelessWidget {
  final String label;
  final String value;
  final int kind; // 0 gross, 1 deductions, 2 net
  final Color cardColor;
  final Color border;
  final Color sub;

  const _SummaryTile({
    required this.label,
    required this.value,
    required this.kind,
    required this.cardColor,
    required this.border,
    required this.sub,
  });

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final icons = [Icons.account_balance_wallet_rounded, Icons.remove_circle_rounded, Icons.payments_rounded];
    final tints = [const Color(0xFF2B8BD6), const Color(0xFFE0762B), const Color(0xFF16A06B)];
    final tint = tints[kind.clamp(0, 2)];

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: border),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: tint.withValues(alpha: dark ? 0.22 : 0.13),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icons[kind.clamp(0, 2)], color: tint),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label.toUpperCase(), style: TextStyle(color: sub, fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.5)),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    color: kind == 2 ? (dark ? const Color(0xFF5FE0B0) : const Color(0xFF0E7A55)) : null,
                    fontFeatures: const [FontFeature.tabularFigures()],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PayslipDocument extends StatelessWidget {
  final Map<String, dynamic> data;
  final Map<String, dynamic> income;
  final Map<String, dynamic> deductions;
  final Map<String, dynamic> net;
  final Map<String, dynamic> footer;
  final Map<String, dynamic> company;
  final List<Map<String, dynamic>> employee;
  final bool dark;
  final Color cardColor;
  final Color border;
  final Color sub;

  const _PayslipDocument({
    required this.data,
    required this.income,
    required this.deductions,
    required this.net,
    required this.footer,
    required this.company,
    required this.employee,
    required this.dark,
    required this.cardColor,
    required this.border,
    required this.sub,
  });

  Widget _panel({
    required Map<String, dynamic> panel,
    required Color tint,
    required Color headerBg,
    required Color amountColor,
  }) {
    final rows = (panel['rows'] as List? ?? const []).whereType<Map>().toList();
    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: border),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            color: headerBg,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    '${panel['title'] ?? ''}'.toUpperCase(),
                    style: TextStyle(color: tint, fontWeight: FontWeight.w800, fontSize: 12, letterSpacing: 0.5),
                  ),
                ),
                Text('${panel['amount'] ?? ''}', style: TextStyle(color: amountColor, fontWeight: FontWeight.w800, fontSize: 14)),
              ],
            ),
          ),
          for (var i = 0; i < rows.length; i++)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: border)),
                color: i.isOdd ? (dark ? const Color(0x0DFFFFFF) : const Color(0xFFF8FAFC)) : null,
              ),
              child: Row(
                children: [
                  Expanded(child: Text('${rows[i]['name']}', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13))),
                  Text('${rows[i]['value']}', style: const TextStyle(fontSize: 13, fontFeatures: [FontFeature.tabularFigures()])),
                ],
              ),
            ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              border: Border(top: BorderSide(color: border, width: 1.5)),
              color: dark ? const Color(0x14FFFFFF) : const Color(0xFFF1F5F9),
            ),
            child: Row(
              children: [
                Expanded(child: Text('${panel['total_label'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13))),
                Text('${panel['total'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final lines = (company['lines'] as List? ?? const []).map((e) => '$e').toList();
    final logo = company['logo_url']?.toString();

    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: border),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(height: 5, color: _navy),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // company + payslip label
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 58,
                      height: 58,
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: border),
                      ),
                      child: logo == null
                          ? const Icon(Icons.business_rounded, color: _navy)
                          : Image.network(logo, fit: BoxFit.contain, errorBuilder: (_, __, ___) => const Icon(Icons.business_rounded, color: _navy)),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${company['name'] ?? data['organization'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                          const SizedBox(height: 4),
                          for (final l in lines) Text(l, style: TextStyle(color: sub, fontSize: 11, height: 1.35)),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: dark ? const Color(0x2216A06B) : const Color(0xFFE6F8F0),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: const Color(0xFF9ADFC2)),
                        ),
                        child: Text(
                          '${data['badge'] ?? ''}'.toUpperCase(),
                          style: TextStyle(color: dark ? const Color(0xFF5FE0B0) : const Color(0xFF0E7A55), fontSize: 10.5, fontWeight: FontWeight.w800),
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text('${data['document_title'] ?? ''}'.toUpperCase(), style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900, letterSpacing: 1)),
                      Text('${data['document_period'] ?? ''}', style: TextStyle(color: sub, fontSize: 12, fontWeight: FontWeight.w600)),
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                // employee grid: two columns
                Container(
                  decoration: BoxDecoration(
                    border: Border.all(color: border),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: Column(
                    children: [
                      for (var i = 0; i < employee.length; i += 2)
                        IntrinsicHeight(
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              for (var j = i; j < i + 2; j++)
                                Expanded(
                                  child: Container(
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(
                                      border: Border(
                                        top: i == 0 ? BorderSide.none : BorderSide(color: border),
                                        left: j.isOdd ? BorderSide(color: border) : BorderSide.none,
                                      ),
                                    ),
                                    child: j < employee.length
                                        ? Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text('${employee[j]['label']}'.toUpperCase(), style: TextStyle(color: sub, fontSize: 9.5, fontWeight: FontWeight.w700, letterSpacing: 0.4)),
                                              const SizedBox(height: 3),
                                              Text('${employee[j]['value']}', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12.5)),
                                            ],
                                          )
                                        : const SizedBox.shrink(),
                                  ),
                                ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),

                _panel(
                  panel: income,
                  tint: dark ? const Color(0xFF5FE0B0) : const Color(0xFF0E7A55),
                  headerBg: dark ? const Color(0x2216A06B) : const Color(0xFFE9F8F2),
                  amountColor: dark ? const Color(0xFF5FE0B0) : const Color(0xFF0E7A55),
                ),
                const SizedBox(height: 12),
                _panel(
                  panel: deductions,
                  tint: dark ? const Color(0xFFFFA566) : const Color(0xFFB4501A),
                  headerBg: dark ? const Color(0x22E0762B) : const Color(0xFFFFF1E6),
                  amountColor: dark ? const Color(0xFFFFA566) : const Color(0xFFB4501A),
                ),
                const SizedBox(height: 14),

                // net salary banner
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(12),
                    gradient: const LinearGradient(
                      colors: [_navy, Color(0xFF106B68)],
                      begin: Alignment.centerLeft,
                      end: Alignment.centerRight,
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${net['label'] ?? 'Net Salary'}'.toUpperCase(), style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 10.5, fontWeight: FontWeight.w700, letterSpacing: 0.6)),
                      const SizedBox(height: 2),
                      Text('${net['amount'] ?? ''}', style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w900)),
                      const SizedBox(height: 10),
                      Text('${net['payment_label'] ?? ''}'.toUpperCase(), style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 9.5, letterSpacing: 0.5)),
                      const SizedBox(height: 2),
                      Text('${net['payment_account'] ?? ''}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(border: Border(top: BorderSide(color: border))),
            child: Column(
              children: [
                Text('${footer['note'] ?? ''}', textAlign: TextAlign.center, style: TextStyle(color: sub, fontSize: 11)),
                const SizedBox(height: 2),
                Text('${footer['organization'] ?? ''}', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 11.5)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
