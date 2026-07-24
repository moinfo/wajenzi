import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/procurement/quotation_models.dart';
import '../../../data/repositories/quotations_repository.dart';
import '../../providers/settings_provider.dart';
import 'procurement_shared.dart';

final _compSearchProvider = StateProvider.autoDispose<String>((_) => '');
final _compStatusProvider = StateProvider.autoDispose<String?>((_) => null);

final _comparisonsProvider =
    FutureProvider.autoDispose<List<ComparisonListItemDto>>((ref) async {
  final repo = ref.watch(quotationsRepositoryProvider);
  final search = ref.watch(_compSearchProvider);
  final status = ref.watch(_compStatusProvider);
  return repo.comparisons(search: search, status: status);
});

final _comparisonDetailProvider = FutureProvider.autoDispose
    .family<ComparisonDetailDto, int>((ref, id) async {
  return ref.watch(quotationsRepositoryProvider).comparison(id);
});

class QuotationComparisonsScreen extends ConsumerWidget {
  const QuotationComparisonsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final comparisonsAsync = ref.watch(_comparisonsProvider);
    final search = ref.watch(_compSearchProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Ulinganisho wa Nukuu' : 'Quotation Comparisons'),
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: TextField(
              decoration: InputDecoration(
                hintText: isSwahili
                    ? 'Tafuta nambari, mradi…'
                    : 'Search number, project…',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: search.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () =>
                            ref.read(_compSearchProvider.notifier).state = '',
                      )
                    : null,
                filled: true,
                fillColor:
                    isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
              onChanged: (v) => ref.read(_compSearchProvider.notifier).state = v,
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_comparisonsProvider);
                await ref.read(_comparisonsProvider.future);
              },
              child: comparisonsAsync.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => _CompErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_comparisonsProvider),
                ),
                data: (comparisons) {
                  if (comparisons.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: [
                        const SizedBox(height: 120),
                        Center(
                          child: Icon(Icons.compare_arrows,
                              size: 64, color: AppColors.textHint),
                        ),
                        const SizedBox(height: 12),
                        Center(
                          child: Text(
                            isSwahili
                                ? 'Hakuna ulinganisho bado'
                                : 'No comparisons yet',
                            style: TextStyle(color: AppColors.textSecondary),
                          ),
                        ),
                      ],
                    );
                  }
                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(12, 8, 12, 24),
                    itemCount: comparisons.length,
                    itemBuilder: (context, index) => _ComparisonCard(
                      comparison: comparisons[index],
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                      onTap: () {
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => QuotationComparisonDetailScreen(
                              id: comparisons[index].id,
                            ),
                          ),
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ComparisonCard extends StatelessWidget {
  final ComparisonListItemDto comparison;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _ComparisonCard({
    required this.comparison,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF1E1E32) : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      comparison.comparisonNumber,
                      style: const TextStyle(
                          fontSize: 15, fontWeight: FontWeight.w700),
                    ),
                  ),
                  ProcurementStatusChip(status: comparison.status),
                ],
              ),
              const SizedBox(height: 6),
              _line(Icons.description_outlined,
                  '${comparison.materialRequestNumber} • ${comparison.projectName}'),
              if (comparison.selectedSupplierName.isNotEmpty)
                _line(Icons.storefront_outlined,
                    '${comparison.selectedSupplierName} — ${fmtMoney(comparison.selectedAmount)}'),
              _line(Icons.request_quote_outlined,
                  '${comparison.quotationCount} ${isSwahili ? 'nukuu' : 'quotations'}'),
              if (comparison.canCreatePurchase)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: _MiniPill(
                    label: isSwahili ? 'Inaweza kutoa PO' : 'PO available',
                    color: AppColors.success,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _line(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: Row(
        children: [
          Icon(icon, size: 15, color: AppColors.textSecondary),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              text,
              style: TextStyle(fontSize: 12.5, color: AppColors.textSecondary),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniPill extends StatelessWidget {
  final String label;
  final Color color;
  const _MiniPill({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
            fontSize: 10.5, fontWeight: FontWeight.w700, color: color),
      ),
    );
  }
}

// ── Detail screen ────────────────────────────────────────────────────────────
class QuotationComparisonDetailScreen extends ConsumerWidget {
  final int id;
  const QuotationComparisonDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final async = ref.watch(_comparisonDetailProvider(id));

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Maelezo ya Ulinganisho' : 'Comparison Detail'),
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _CompErrorView(
          error: e,
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_comparisonDetailProvider(id)),
        ),
        data: (c) => _DetailBody(
          comparison: c,
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
          onChanged: () {
            ref.invalidate(_comparisonDetailProvider(id));
            ref.invalidate(_comparisonsProvider);
          },
        ),
      ),
    );
  }
}

class _DetailBody extends ConsumerWidget {
  final ComparisonDetailDto comparison;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onChanged;

  const _DetailBody({
    required this.comparison,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final normalized = normalizeStatus(comparison.status);
    final isPending = normalized == 'PENDING' || normalized == 'SUBMITTED';
    final canApprove = isPending &&
        (hasRole(ref, 'Managing Director') ||
            hasRole(ref, 'System Administrator'));
    final canCreatePo = comparison.canCreatePurchase &&
        hasPermission(ref, 'Quotation Comparisons');

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                comparison.comparisonNumber,
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
            ),
            ProcurementStatusChip(status: comparison.status),
          ],
        ),
        const SizedBox(height: 12),
        _card([
          _kv(isSwahili ? 'Ombi' : 'Request', comparison.materialRequestNumber),
          _kv(isSwahili ? 'Mradi' : 'Project', comparison.projectName),
          _kv(isSwahili ? 'Tarehe' : 'Date', fmtDate(comparison.comparisonDate)),
          _kv(isSwahili ? 'Aliyeandaa' : 'Prepared by', comparison.preparedByName),
          if (comparison.approvedByName.isNotEmpty)
            _kv(isSwahili ? 'Aliyeidhinisha' : 'Approved by',
                comparison.approvedByName),
        ]),
        const SizedBox(height: 12),
        _card([
          _kv(isSwahili ? 'Msambazaji aliyechaguliwa' : 'Selected supplier',
              comparison.selectedSupplierName, bold: true),
          _kv(isSwahili ? 'Kiasi' : 'Amount', fmtMoney(comparison.selectedAmount)),
          _kv(isSwahili ? 'Idadi ya nukuu' : 'Quotations',
              comparison.quotationCount.toString()),
          _kv(isSwahili ? 'Wastani' : 'Average',
              fmtMoney(comparison.averageQuotationPrice)),
          _kv(isSwahili ? 'Tofauti ya bei' : 'Price variance',
              fmtMoney(comparison.priceVariance)),
          _kv(isSwahili ? 'Akiba' : 'Savings', fmtMoney(comparison.savings)),
        ]),
        if (comparison.recommendationReason != null &&
            comparison.recommendationReason!.isNotEmpty) ...[
          const SizedBox(height: 12),
          _card([
            Text(isSwahili ? 'Sababu ya mapendekezo' : 'Recommendation reason',
                style: const TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            Text(comparison.recommendationReason!,
                style: const TextStyle(fontSize: 13)),
          ]),
        ],
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Jedwali la Bei' : 'Price Matrix',
          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
        ),
        const SizedBox(height: 8),
        _MatrixTable(
          quotations: comparison.quotations,
          selectedAmount: comparison.selectedAmount,
          isDarkMode: isDarkMode,
          isSwahili: isSwahili,
        ),
        const SizedBox(height: 20),
        if (canApprove)
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _approve(context, ref),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.success,
                    foregroundColor: Colors.white,
                  ),
                  icon: const Icon(Icons.check, size: 18),
                  label: Text(isSwahili ? 'Idhinisha' : 'Approve'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _reject(context, ref),
                  style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.error),
                  icon: const Icon(Icons.close, size: 18),
                  label: Text(isSwahili ? 'Kataa' : 'Reject'),
                ),
              ),
            ],
          ),
        if (canCreatePo)
          Padding(
            padding: const EdgeInsets.only(top: 12),
            child: SizedBox(
              height: 48,
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _createPurchase(context, ref),
                icon: const Icon(Icons.shopping_cart_checkout, size: 18),
                label: Text(
                    isSwahili ? 'Tengeneza Oda ya Ununuzi' : 'Create Purchase Order'),
              ),
            ),
          ),
      ],
    );
  }

  Widget _card(List<Widget> children) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E32) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.18)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: children),
    );
  }

  Widget _kv(String k, String v, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 150,
            child: Text(k,
                style: TextStyle(fontSize: 13, color: AppColors.textSecondary)),
          ),
          Expanded(
            child: Text(v.isEmpty ? '-' : v,
                style: TextStyle(
                    fontSize: 13,
                    fontWeight: bold ? FontWeight.w800 : FontWeight.w500)),
          ),
        ],
      ),
    );
  }

  Future<void> _approve(BuildContext context, WidgetRef ref) async {
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Idhinisha Ulinganisho' : 'Approve Comparison'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(
            hintText: isSwahili ? 'Maelezo (hiari)' : 'Comment (optional)',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Idhinisha' : 'Approve'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref
          .read(quotationsRepositoryProvider)
          .approveComparison(comparison.id, comment: controller.text.trim());
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imeidhinishwa' : 'Approved'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(procurementErrorMessage(e)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  Future<void> _reject(BuildContext context, WidgetRef ref) async {
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Kataa Ulinganisho' : 'Reject Comparison'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(
            hintText: isSwahili ? 'Sababu ya kukataa' : 'Rejection reason',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Kataa' : 'Reject'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    final reason = controller.text.trim();
    if (reason.isEmpty) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Sababu inahitajika' : 'Reason is required'),
            backgroundColor: AppColors.error,
          ),
        );
      }
      return;
    }
    try {
      await ref
          .read(quotationsRepositoryProvider)
          .rejectComparison(comparison.id, reason);
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imekataliwa' : 'Rejected'),
            backgroundColor: AppColors.warning,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(procurementErrorMessage(e)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  Future<void> _createPurchase(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Tengeneza Oda' : 'Create Purchase Order'),
        content: Text(isSwahili
            ? 'Tengeneza oda ya ununuzi kutoka nukuu iliyochaguliwa?'
            : 'Generate a purchase order from the selected quotation?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Tengeneza' : 'Create'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      final result = await ref
          .read(quotationsRepositoryProvider)
          .createPurchase(comparison.id);
      onChanged();
      if (context.mounted) {
        final doc = result['document_number']?.toString();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(doc == null
                ? (isSwahili ? 'Oda imetengenezwa' : 'Purchase order created')
                : (isSwahili ? 'Oda imetengenezwa: $doc' : 'Purchase order created: $doc')),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(procurementErrorMessage(e)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

class _MatrixTable extends StatelessWidget {
  final List<ComparisonQuotationDto> quotations;
  final double selectedAmount;
  final bool isDarkMode;
  final bool isSwahili;

  const _MatrixTable({
    required this.quotations,
    required this.selectedAmount,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    if (quotations.isEmpty) {
      return Text(isSwahili ? 'Hakuna data.' : 'No data.',
          style: TextStyle(color: AppColors.textSecondary));
    }

    // Build union of item rows keyed by material_request_item_id.
    final rowIds = <int>[];
    final rowLabel = <int, String>{};
    for (final q in quotations) {
      for (final it in q.items) {
        final key = it.materialRequestItemId ?? it.id;
        if (!rowIds.contains(key)) {
          rowIds.add(key);
          rowLabel[key] = it.description.isEmpty
              ? (isSwahili ? 'Bidhaa' : 'Item')
              : it.description;
        }
      }
    }

    // price[rowKey][quotationId] = unit price
    final priceMap = <int, Map<int, double>>{};
    for (final q in quotations) {
      for (final it in q.items) {
        final key = it.materialRequestItemId ?? it.id;
        priceMap.putIfAbsent(key, () => {})[q.id] = it.unitPrice;
      }
    }

    final headerColor = isDarkMode ? const Color(0xFF25253C) : Colors.grey[100];

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        headingRowColor: WidgetStateProperty.all(headerColor),
        columnSpacing: 20,
        columns: [
          DataColumn(label: Text(isSwahili ? 'Bidhaa' : 'Item')),
          ...quotations.map((q) => DataColumn(
                label: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      q.supplierName.isEmpty ? q.quotationNumber : q.supplierName,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        color: q.isSelected ? AppColors.success : null,
                      ),
                    ),
                    Text(fmtMoney(q.grandTotal),
                        style: TextStyle(
                            fontSize: 11, color: AppColors.textSecondary)),
                  ],
                ),
                numeric: true,
              )),
        ],
        rows: [
          ...rowIds.map((key) {
            final row = priceMap[key] ?? const {};
            final values =
                quotations.map((q) => row[q.id]).whereType<double>().toList();
            final lowest = values.isEmpty
                ? null
                : values.reduce((a, b) => a < b ? a : b);
            return DataRow(cells: [
              DataCell(SizedBox(
                width: 120,
                child: Text(rowLabel[key] ?? '',
                    overflow: TextOverflow.ellipsis),
              )),
              ...quotations.map((q) {
                final v = row[q.id];
                final isLow = v != null && lowest != null && v <= lowest;
                return DataCell(Text(
                  v == null ? '-' : fmtMoney(v),
                  style: TextStyle(
                    color: isLow ? AppColors.success : null,
                    fontWeight: isLow ? FontWeight.w800 : FontWeight.w500,
                  ),
                ));
              }),
            ]);
          }),
          DataRow(
            color: WidgetStateProperty.all(headerColor),
            cells: [
              DataCell(Text(isSwahili ? 'Jumla' : 'Total',
                  style: const TextStyle(fontWeight: FontWeight.w800))),
              ...quotations.map((q) => DataCell(Text(
                    fmtMoney(q.grandTotal),
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      color: q.isSelected ? AppColors.success : null,
                    ),
                  ))),
            ],
          ),
        ],
      ),
    );
  }
}

class _CompErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _CompErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Center(child: Icon(Icons.error_outline, size: 56, color: AppColors.error)),
        const SizedBox(height: 12),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Text(
            procurementErrorMessage(error),
            textAlign: TextAlign.center,
            style: TextStyle(color: AppColors.textSecondary),
          ),
        ),
        const SizedBox(height: 16),
        Center(
          child: OutlinedButton(
            onPressed: onRetry,
            child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ),
      ],
    );
  }
}
