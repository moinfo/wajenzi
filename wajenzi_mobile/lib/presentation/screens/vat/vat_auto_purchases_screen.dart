import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _startProvider = StateProvider.autoDispose<DateTime>(
    (ref) => DateTime(DateTime.now().year, 1, 1));
final _endProvider =
    StateProvider.autoDispose<DateTime>((ref) => DateTime.now());

final _dataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final s = ref.watch(_startProvider);
  final e = ref.watch(_endProvider);
  final resp = await api.get('/vat/auto-purchases',
      queryParameters: {'start_date': vatDateFmt(s), 'end_date': vatDateFmt(e)});
  return resp.data['data'] as Map<String, dynamic>;
});

class VatAutoPurchasesScreen extends ConsumerWidget {
  const VatAutoPurchasesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_dataProvider);
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Manunuzi Otomatiki' : 'Auto Purchases'),
        backgroundColor: isDark ? vatDarkCard : null,
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_dataProvider.future),
        child: dataAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => VatErrorBody(
            onRetry: () => ref.invalidate(_dataProvider),
            isSwahili: isSwahili,
          ),
          data: (data) {
            final receipts =
                (data['receipts'] as List?)?.cast<Map<String, dynamic>>() ??
                    [];
            final totals = data['totals'] as Map<String, dynamic>? ?? {};

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: EdgeInsets.fromLTRB(16, 12, 16, bottomPad),
              children: [
                VatDateRangeBar(
                  startProvider: _startProvider,
                  endProvider: _endProvider,
                  isDark: isDark,
                  isSwahili: isSwahili,
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                        child: VatSummaryChip(
                      label: 'Total',
                      value: vatMoney(totals['total_amount']),
                      color: vatAccentBlue,
                      isDark: isDark,
                    )),
                    const SizedBox(width: 8),
                    Expanded(
                        child: VatSummaryChip(
                      label: 'VAT Exc.',
                      value: vatMoney(totals['amount_vat_exc']),
                      color: vatAccentTeal,
                      isDark: isDark,
                    )),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                        child: VatSummaryChip(
                      label: 'VAT',
                      value: vatMoney(totals['vat_amount']),
                      color: const Color(0xFFF59E0B),
                      isDark: isDark,
                    )),
                    const SizedBox(width: 8),
                    Expanded(
                        child: VatSummaryChip(
                      label: isSwahili ? 'Punguzo' : 'Discount',
                      value: vatMoney(totals['discount']),
                      color: const Color(0xFFEF5350),
                      isDark: isDark,
                    )),
                  ],
                ),
                const SizedBox(height: 14),
                VatCountBadge(
                    count: receipts.length,
                    label: isSwahili ? 'Risiti' : 'Receipts',
                    isDark: isDark),
                const SizedBox(height: 8),
                if (receipts.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...receipts
                      .map((r) => _ReceiptCard(receipt: r, isDark: isDark)),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _ReceiptCard extends StatelessWidget {
  final Map<String, dynamic> receipt;
  final bool isDark;
  const _ReceiptCard({required this.receipt, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(14),
      decoration: vatCardDeco(isDark),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.receipt_long_rounded,
                  size: 16, color: const Color(0xFF8B5CF6)),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  receipt['supplier_name'] ?? '-',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDark ? Colors.white : AppColors.textPrimary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (receipt['is_expense'] == 'YES')
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text(
                    'EXPENSE',
                    style: TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFF59E0B)),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              VatInfoCol(
                  label: 'Date',
                  value: receipt['date'] ?? '-',
                  isDark: isDark),
              VatInfoCol(
                  label: 'Total',
                  value: vatMoney(receipt['total_amount']),
                  isDark: isDark,
                  isMoney: true),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              VatInfoCol(
                  label: 'VAT Exc.',
                  value: vatMoney(receipt['amount_vat_exc']),
                  isDark: isDark,
                  isMoney: true),
              VatInfoCol(
                  label: 'VAT',
                  value: vatMoney(receipt['vat_amount']),
                  isDark: isDark,
                  isMoney: true),
              VatInfoCol(
                  label: 'Discount',
                  value: vatMoney(receipt['discount']),
                  isDark: isDark,
                  isMoney: true),
            ],
          ),
          if (receipt['receipt_number'] != null) ...[
            const SizedBox(height: 6),
            Text(
              'Receipt: ${receipt['receipt_number']}',
              style: TextStyle(
                  fontSize: 10,
                  color: isDark ? Colors.white38 : AppColors.textHint),
            ),
          ],
        ],
      ),
    );
  }
}
