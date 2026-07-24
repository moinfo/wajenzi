import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/paylog/paylog_models.dart';
import '../../../data/repositories/paylog_repository.dart';
import 'paylog_shared.dart';

final _refDataProvider = FutureProvider.autoDispose<PaylogReferenceData>((ref) {
  return ref.watch(paylogRepositoryProvider).referenceData();
});

final _selectedSiteProvider = StateProvider.autoDispose<int?>((_) => null);
final _selectedMonthProvider =
    StateProvider.autoDispose<DateTime>((_) => DateTime.now());

typedef _MonthlyKey = ({int siteId, String month});

final _monthlyReportProvider = FutureProvider.autoDispose
    .family<MonthlyReportDto, _MonthlyKey>((ref, key) {
  return ref
      .read(paylogRepositoryProvider)
      .reportsMonthly(siteId: key.siteId, month: key.month);
});

/// Monthly Payment Report — site + month picker (default current month), a
/// summary table (category, count, total) with grand total, and a full-month
/// ledger table.
class MonthlyPaymentReportScreen extends ConsumerWidget {
  const MonthlyPaymentReportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final refAsync = ref.watch(_refDataProvider);
    final siteId = ref.watch(_selectedSiteProvider);
    final month = ref.watch(_selectedMonthProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(tr('Monthly Payment Report')),
      ),
      body: refAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_refDataProvider),
        ),
        data: (refData) {
          return Column(
            children: [
              _FilterBar(
                sites: refData.sites,
                siteId: siteId,
                month: month,
                onSiteChange: (v) =>
                    ref.read(_selectedSiteProvider.notifier).state = v,
                onMonthChange: (d) =>
                    ref.read(_selectedMonthProvider.notifier).state = d,
              ),
              Expanded(
                child: siteId == null
                    ? const _PromptView(
                        icon: Icons.filter_alt_outlined,
                        label: 'Select a site to view the monthly report.',
                      )
                    : _ReportBody(
                        key: ValueKey('$siteId-${_fmtMonth(month)}'),
                        siteId: siteId,
                        month: month,
                      ),
              ),
            ],
          );
        },
      ),
    );
  }
}

String _fmtMonth(DateTime d) => DateFormat('yyyy-MM').format(d);

class _ReportBody extends ConsumerWidget {
  final int siteId;
  final DateTime month;
  const _ReportBody({
    super.key,
    required this.siteId,
    required this.month,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final key = (siteId: siteId, month: _fmtMonth(month));
    final async = ref.watch(_monthlyReportProvider(key));

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(_monthlyReportProvider(key));
        await ref.read(_monthlyReportProvider(key).future);
      },
      child: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_monthlyReportProvider(key)),
        ),
        data: (report) {
          return ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 40),
            children: [
              _SectionTitle('Summary'),
              _SummaryTable(
                summary: report.summary,
                grandTotal: report.grandTotal,
              ),
              const SizedBox(height: 20),
              _SectionTitle('Ledger (${report.ledger.length})'),
              if (report.ledger.isEmpty)
                const Padding(
                  padding: EdgeInsets.only(top: 40),
                  child: Center(
                      child: Text('No payments logged for this month.')),
                )
              else
                _LedgerTable(rows: report.ledger),
            ],
          );
        },
      ),
    );
  }
}

class _FilterBar extends StatelessWidget {
  final List<NamedRefDto> sites;
  final int? siteId;
  final DateTime month;
  final ValueChanged<int?> onSiteChange;
  final ValueChanged<DateTime> onMonthChange;

  const _FilterBar({
    required this.sites,
    required this.siteId,
    required this.month,
    required this.onSiteChange,
    required this.onMonthChange,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
      child: Row(
        children: [
          Expanded(
            flex: 3,
            child: DropdownButtonFormField<int>(
              initialValue: siteId,
              isExpanded: true,
              decoration: const InputDecoration(
                labelText: 'Site',
                isDense: true,
                border: OutlineInputBorder(),
              ),
              items: sites
                  .map((s) => DropdownMenuItem(
                        value: s.id,
                        child: Text(s.name, overflow: TextOverflow.ellipsis),
                      ))
                  .toList(),
              onChanged: onSiteChange,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            flex: 2,
            child: InkWell(
              onTap: () => _pickMonth(context),
              child: InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Month',
                  isDense: true,
                  border: OutlineInputBorder(),
                ),
                child: Text(DateFormat('MMM yyyy').format(month),
                    style: const TextStyle(fontSize: 13)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _pickMonth(BuildContext context) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: month,
      firstDate: DateTime(2020),
      lastDate: DateTime(2035, 12, 31),
      initialDatePickerMode: DatePickerMode.year,
      helpText: 'Select any day in the target month',
    );
    if (picked != null) {
      onMonthChange(DateTime(picked.year, picked.month, 1));
    }
  }
}

class _SummaryTable extends StatelessWidget {
  final List<MonthlySummaryRowDto> summary;
  final double grandTotal;
  const _SummaryTable({required this.summary, required this.grandTotal});

  String _catLabel(String code) {
    final c = code.trim();
    if (c.isEmpty) return '-';
    return c[0].toUpperCase() + c.substring(1).toLowerCase();
  }

  @override
  Widget build(BuildContext context) {
    final hintColor = Theme.of(context).hintColor;
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Row(
                children: [
                  Expanded(
                      flex: 3,
                      child: Text('Category',
                          style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: hintColor))),
                  Expanded(
                      flex: 2,
                      child: Text('Count',
                          textAlign: TextAlign.end,
                          style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: hintColor))),
                  Expanded(
                      flex: 3,
                      child: Text('Total',
                          textAlign: TextAlign.end,
                          style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: hintColor))),
                ],
              ),
            ),
            const Divider(height: 1),
            if (summary.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Text('No data', style: TextStyle(color: hintColor)),
              )
            else
              ...summary.map((r) => Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Row(
                      children: [
                        Expanded(
                            flex: 3,
                            child: Text(_catLabel(r.category),
                                style: const TextStyle(
                                    fontWeight: FontWeight.w600))),
                        Expanded(
                            flex: 2,
                            child: Text('${r.count}',
                                textAlign: TextAlign.end)),
                        Expanded(
                            flex: 3,
                            child: Text(fmtMoney(r.total),
                                textAlign: TextAlign.end,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w600))),
                      ],
                    ),
                  )),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Row(
                children: [
                  const Expanded(
                      flex: 5,
                      child: Text('Grand Total',
                          style: TextStyle(fontWeight: FontWeight.w800))),
                  Expanded(
                    flex: 3,
                    child: Text(fmtMoney(grandTotal),
                        textAlign: TextAlign.end,
                        style: TextStyle(
                            fontWeight: FontWeight.w800,
                            color: Theme.of(context).colorScheme.primary)),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LedgerTable extends StatelessWidget {
  final List<SitePaylogLineDto> rows;
  const _LedgerTable({required this.rows});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Column(
        children: [
          for (var i = 0; i < rows.length; i++) ...[
            if (i > 0) const Divider(height: 1),
            _LedgerRow(row: rows[i]),
          ],
        ],
      ),
    );
  }
}

class _LedgerRow extends StatelessWidget {
  final SitePaylogLineDto row;
  const _LedgerRow({required this.row});

  @override
  Widget build(BuildContext context) {
    final meta = [
      row.categoryLabel,
      if (row.channelName != null && row.channelName!.isNotEmpty)
        row.channelName,
      if (row.paymentDate != null) fmtDate(row.paymentDate),
    ].whereType<String>().join(' • ');
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(row.payeeName,
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                if ((row.reason ?? '').isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(row.reason!,
                      style: Theme.of(context).textTheme.bodySmall,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis),
                ],
                const SizedBox(height: 2),
                Text(meta,
                    style: Theme.of(context)
                        .textTheme
                        .bodySmall
                        ?.copyWith(color: Theme.of(context).hintColor)),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(fmtMoney(row.amount),
              style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  const _SectionTitle(this.title);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(title,
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
    );
  }
}

class _PromptView extends StatelessWidget {
  final IconData icon;
  final String label;
  const _PromptView({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 64, color: Theme.of(context).hintColor),
          const SizedBox(height: 12),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(label,
                textAlign: TextAlign.center,
                style: TextStyle(color: Theme.of(context).hintColor)),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;
  const _ErrorView({required this.error, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 100),
        Icon(Icons.error_outline_rounded,
            size: 56, color: Colors.red.withValues(alpha: 0.7)),
        const SizedBox(height: 12),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(paylogErrorMessage(error), textAlign: TextAlign.center),
          ),
        ),
        const SizedBox(height: 12),
        Center(
          child:
              OutlinedButton(onPressed: onRetry, child: const Text('Retry')),
        ),
      ],
    );
  }
}
