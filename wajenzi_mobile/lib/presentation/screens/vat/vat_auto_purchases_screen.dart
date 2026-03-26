import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _autoStartProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime(DateTime.now().year, DateTime.now().month, 1),
);
final _autoEndProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime.now(),
);

final _autoDataProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final s = ref.watch(_autoStartProvider);
  final e = ref.watch(_autoEndProvider);
  final resp = await api.get(
    '/vat/auto-purchases',
    queryParameters: {'start_date': vatDateFmt(s), 'end_date': vatDateFmt(e)},
  );
  return resp.data['data'] as Map<String, dynamic>;
});

class VatAutoPurchasesScreen extends ConsumerWidget {
  const VatAutoPurchasesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_autoDataProvider);
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Manunuzi ya EFD' : 'EFD Auto Purchases'),
        backgroundColor: isDark ? vatDarkCard : null,
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_autoDataProvider.future),
        child: dataAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => VatErrorBody(
            onRetry: () => ref.invalidate(_autoDataProvider),
            isSwahili: isSwahili,
          ),
          data: (data) {
            final receipts =
                (data['receipts'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final totals = data['totals'] as Map<String, dynamic>? ?? {};

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: EdgeInsets.fromLTRB(16, 12, 16, bottomPad),
              children: [
                VatDateRangeBar(
                  startProvider: _autoStartProvider,
                  endProvider: _autoEndProvider,
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
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: VatSummaryChip(
                        label: 'VAT Exc.',
                        value: vatMoney(totals['amount_vat_exc']),
                        color: vatAccentTeal,
                        isDark: isDark,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: VatSummaryChip(
                        label: 'VAT',
                        value: vatMoney(totals['vat_amount']),
                        color: const Color(0xFFF59E0B),
                        isDark: isDark,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                VatCountBadge(
                  count: receipts.length,
                  label: isSwahili ? 'Risiti za EFD' : 'EFD Receipts',
                  isDark: isDark,
                ),
                const SizedBox(height: 8),
                if (receipts.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...receipts.map(
                    (r) => _ReceiptCard(
                      receipt: r,
                      isDark: isDark,
                      isSwahili: isSwahili,
                    ),
                  ),
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
  final bool isSwahili;
  const _ReceiptCard({
    required this.receipt,
    required this.isDark,
    required this.isSwahili,
  });

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
              Icon(
                Icons.receipt_long_rounded,
                size: 16,
                color: const Color(0xFF66BB6A),
              ),
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
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFF3B82F6).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'EFD',
                  style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    color: const Color(0xFF3B82F6),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              VatInfoCol(
                label: 'Date',
                value: receipt['date'] ?? '-',
                isDark: isDark,
              ),
              VatInfoCol(
                label: 'Receipt #',
                value: receipt['receipt_number'] ?? '-',
                isDark: isDark,
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              VatInfoCol(
                label: 'Total',
                value: vatMoney(receipt['total_amount']),
                isDark: isDark,
                isMoney: true,
              ),
              VatInfoCol(
                label: 'VAT Exc.',
                value: vatMoney(receipt['amount_vat_exc']),
                isDark: isDark,
                isMoney: true,
              ),
              VatInfoCol(
                label: 'VAT',
                value: vatMoney(receipt['vat_amount']),
                isDark: isDark,
                isMoney: true,
              ),
            ],
          ),
          if (receipt['supplier_vrn'] != null &&
              (receipt['supplier_vrn'] as String).isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'VRN: ${receipt['supplier_vrn']}',
              style: TextStyle(
                fontSize: 10,
                color: isDark ? Colors.white38 : AppColors.textHint,
              ),
            ),
          ],
          if (receipt['verification_code'] != null &&
              (receipt['verification_code'] as String).isNotEmpty) ...[
            const SizedBox(height: 4),
            Row(
              children: [
                Icon(
                  Icons.verified_rounded,
                  size: 12,
                  color: const Color(0xFF10B981),
                ),
                const SizedBox(width: 4),
                Text(
                  'Verified: ${receipt['verification_code']}',
                  style: TextStyle(
                    fontSize: 10,
                    color: const Color(0xFF10B981),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ],
          if (receipt['is_expense'] != null) ...[
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: receipt['is_expense'] == 'YES'
                    ? Colors.orange.withValues(alpha: 0.15)
                    : Colors.green.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                receipt['is_expense'] == 'YES'
                    ? (isSwahili ? 'Gharama' : 'Expense')
                    : (isSwahili ? 'Sio Gharama' : 'Not Expense'),
                style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w600,
                  color: receipt['is_expense'] == 'YES'
                      ? Colors.orange
                      : Colors.green,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
