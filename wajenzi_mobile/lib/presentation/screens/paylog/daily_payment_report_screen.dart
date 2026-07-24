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
final _selectedDateProvider =
    StateProvider.autoDispose<DateTime>((_) => DateTime.now());

typedef _ReportKey = ({int siteId, String date});

final _dailyReportProvider = FutureProvider.autoDispose
    .family<DailyReportDto, _ReportKey>((ref, key) {
  return ref
      .read(paylogRepositoryProvider)
      .reportsDaily(siteId: key.siteId, date: key.date);
});

/// Daily Payment Report — site + date pickers (default today), a grouped table
/// of line rows (category, payee, reason, channel, amount) and material /
/// labour / total footer cards. Reads line-level data regardless of approval
/// state.
class DailyPaymentReportScreen extends ConsumerWidget {
  const DailyPaymentReportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final refAsync = ref.watch(_refDataProvider);
    final siteId = ref.watch(_selectedSiteProvider);
    final date = ref.watch(_selectedDateProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(tr('Daily Payment Report')),
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
                date: date,
                onSiteChange: (v) =>
                    ref.read(_selectedSiteProvider.notifier).state = v,
                onDateChange: (d) =>
                    ref.read(_selectedDateProvider.notifier).state = d,
              ),
              Expanded(
                child: siteId == null
                    ? const _PromptView(
                        icon: Icons.filter_alt_outlined,
                        label: 'Select a site to view the daily report.',
                      )
                    : _ReportBody(
                        key: ValueKey('$siteId-${_fmtApi(date)}'),
                        siteId: siteId,
                        date: date,
                      ),
              ),
            ],
          );
        },
      ),
    );
  }
}

String _fmtApi(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

class _ReportBody extends ConsumerWidget {
  final int siteId;
  final DateTime date;
  const _ReportBody({
    super.key,
    required this.siteId,
    required this.date,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final key = (siteId: siteId, date: _fmtApi(date));
    final async = ref.watch(_dailyReportProvider(key));

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(_dailyReportProvider(key));
        await ref.read(_dailyReportProvider(key).future);
      },
      child: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_dailyReportProvider(key)),
        ),
        data: (report) {
          final material =
              report.rows.where((r) => !r.isLabour).toList();
          final labour = report.rows.where((r) => r.isLabour).toList();
          return ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 40),
            children: [
              _TotalsRow(
                material: report.material,
                labour: report.labour,
                all: report.all,
              ),
              const SizedBox(height: 16),
              if (report.rows.isEmpty)
                const Padding(
                  padding: EdgeInsets.only(top: 60),
                  child: Center(
                    child: Text('No payments logged for this day.'),
                  ),
                )
              else ...[
                if (material.isNotEmpty) ...[
                  _GroupHeader(
                      label: 'Material', total: report.material, rows: material.length),
                  _LineTable(rows: material),
                  const SizedBox(height: 16),
                ],
                if (labour.isNotEmpty) ...[
                  _GroupHeader(
                      label: 'Labour', total: report.labour, rows: labour.length),
                  _LineTable(rows: labour),
                ],
              ],
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
  final DateTime date;
  final ValueChanged<int?> onSiteChange;
  final ValueChanged<DateTime> onDateChange;

  const _FilterBar({
    required this.sites,
    required this.siteId,
    required this.date,
    required this.onSiteChange,
    required this.onDateChange,
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
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: date,
                  firstDate: DateTime(2020),
                  lastDate: DateTime(2035),
                );
                if (picked != null) onDateChange(picked);
              },
              child: InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Date',
                  isDense: true,
                  border: OutlineInputBorder(),
                ),
                child: Text(DateFormat('dd MMM yyyy').format(date),
                    style: const TextStyle(fontSize: 13)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TotalsRow extends StatelessWidget {
  final double material;
  final double labour;
  final double all;
  const _TotalsRow({
    required this.material,
    required this.labour,
    required this.all,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _TotalCard(
              label: 'Material', value: material, color: Colors.indigo),
        ),
        const SizedBox(width: 8),
        Expanded(
          child:
              _TotalCard(label: 'Labour', value: labour, color: Colors.teal),
        ),
        const SizedBox(width: 8),
        Expanded(
          child:
              _TotalCard(label: 'Total', value: all, color: Colors.deepOrange),
        ),
      ],
    );
  }
}

class _TotalCard extends StatelessWidget {
  final String label;
  final double value;
  final Color color;
  const _TotalCard({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        child: Column(
          children: [
            Text(label.toUpperCase(),
                style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Theme.of(context).hintColor)),
            const SizedBox(height: 6),
            FittedBox(
              child: Text(
                fmtMoney(value),
                style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: color),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _GroupHeader extends StatelessWidget {
  final String label;
  final double total;
  final int rows;
  const _GroupHeader({
    required this.label,
    required this.total,
    required this.rows,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6, top: 2),
      child: Row(
        children: [
          Text('$label ($rows)',
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
          const Spacer(),
          Text(fmtMoney(total),
              style: const TextStyle(
                  fontSize: 14, fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

class _LineTable extends StatelessWidget {
  final List<SitePaylogLineDto> rows;
  const _LineTable({required this.rows});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Column(
        children: [
          for (var i = 0; i < rows.length; i++) ...[
            if (i > 0) const Divider(height: 1),
            _LineRow(row: rows[i]),
          ],
        ],
      ),
    );
  }
}

class _LineRow extends StatelessWidget {
  final SitePaylogLineDto row;
  const _LineRow({required this.row});

  @override
  Widget build(BuildContext context) {
    final subtitle = [row.reason, row.channelName]
        .whereType<String>()
        .where((v) => v.isNotEmpty)
        .join(' • ');
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
                if (subtitle.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(subtitle,
                      style: Theme.of(context).textTheme.bodySmall,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis),
                ],
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
