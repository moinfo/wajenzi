import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _autoStartProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime.now(),
);
final _autoEndProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime.now(),
);
final _autoSearchProvider = StateProvider.autoDispose<String>((ref) => '');

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
    final search = ref.watch(_autoSearchProvider).trim().toLowerCase();
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Manunuzi ya Moja kwa Moja' : 'Auto Purchases'),
        backgroundColor: isDark ? vatDarkCard : null,
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showAutoPurchaseForm(context, ref, isDark, isSwahili),
          child: const Icon(Icons.add),
        ),
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
            final allReceipts =
                (data['receipts'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final receipts = search.isEmpty
                ? allReceipts
                : allReceipts.where((receipt) {
                    final haystack = [
                      receipt['company_name'],
                      receipt['vrn'],
                      receipt['receipt_number'],
                      receipt['receipt_date'],
                      receipt['date'],
                      receipt['items_summary'],
                      receipt['receipt_verification_code'],
                      receipt['is_expense'],
                    ].whereType<String>().join(' ').toLowerCase();
                    return haystack.contains(search);
                  }).toList();
            final totals = data['totals'] as Map<String, dynamic>? ?? {};

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: EdgeInsets.fromLTRB(16, 12, 16, bottomPad),
              children: [
                _SearchField(
                  isDark: isDark,
                  isSwahili: isSwahili,
                  onChanged: (value) =>
                      ref.read(_autoSearchProvider.notifier).state = value,
                ),
                const SizedBox(height: 12),
                VatDateRangeBar(
                  startProvider: _autoStartProvider,
                  endProvider: _autoEndProvider,
                  isDark: isDark,
                  isSwahili: isSwahili,
                ),
                const SizedBox(height: 14),
                if (receipts.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...receipts.asMap().entries.map(
                    (entry) => _ReceiptWebCard(
                      index: entry.key + 1,
                      receipt: entry.value,
                      isDark: isDark,
                      isSwahili: isSwahili,
                      onOpenItems: () => _showReceiptItemsSheet(
                        context,
                        receipt: entry.value,
                        isDark: isDark,
                        isSwahili: isSwahili,
                      ),
                      onVerificationTap: () => _openVerificationLink(
                        context,
                        entry.value['verification_url'] as String?,
                        isSwahili: isSwahili,
                      ),
                      onEdit: () => _showAutoPurchaseForm(
                        context,
                        ref,
                        isDark,
                        isSwahili,
                        receipt: entry.value,
                      ),
                      onDelete: () async {
                        final ok = await vatDelete(
                          context,
                          ref,
                          '/vat/auto-purchases',
                          entry.value['id'] as int,
                          isSwahili: isSwahili,
                        );
                        if (ok) {
                          ref.invalidate(_autoDataProvider);
                        }
                      },
                    ),
                  ),
                if (receipts.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _TotalsFooter(
                    totals: totals,
                    isDark: isDark,
                  ),
                ],
              ],
            );
          },
        ),
      ),
    );
  }
}

double _asDouble(dynamic value) {
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0;
  return 0;
}

Future<void> _showAutoPurchaseForm(
  BuildContext context,
  WidgetRef ref,
  bool isDark,
  bool isSwahili, {
  Map<String, dynamic>? receipt,
}) async {
  final isEdit = receipt != null;
  final companyCtrl =
      TextEditingController(text: receipt?['company_name'] as String? ?? '');
  final vrnCtrl = TextEditingController(text: receipt?['vrn'] as String? ?? '');
  final receiptNoCtrl = TextEditingController(
    text: receipt?['receipt_number'] as String? ?? '',
  );
  final receiptDateCtrl = TextEditingController(
    text: receipt?['receipt_date'] as String? ?? vatDateFmt(DateTime.now()),
  );
  final dateCtrl = TextEditingController(
    text: receipt?['date'] as String? ?? vatDateFmt(DateTime.now()),
  );
  final receiptTimeCtrl = TextEditingController(
    text: receipt?['receipt_time'] as String? ?? TimeOfDay.now().format(context),
  );
  final verificationCtrl = TextEditingController(
    text: receipt?['receipt_verification_code'] as String? ?? '',
  );
  final amountVatExcCtrl = TextEditingController(
    text: receipt?['amount_vat_exc']?.toString() ?? '',
  );
  final vatAmountCtrl = TextEditingController(
    text: receipt?['vat_amount']?.toString() ?? '',
  );
  final totalAmountCtrl = TextEditingController(
    text: receipt?['total_amount']?.toString() ?? '',
  );
  final discountCtrl = TextEditingController(
    text: receipt?['discount']?.toString() ?? '',
  );
  final formKey = GlobalKey<FormState>();
  String isExpense = receipt?['is_expense'] as String? ?? 'NO';
  final initialItems = ((receipt?['items'] as List?) ?? [])
      .cast<Map<String, dynamic>>()
      .map(
        (item) => {
          'description': TextEditingController(
            text: item['description'] as String? ?? '',
          ),
          'qty': TextEditingController(text: item['qty']?.toString() ?? '1'),
          'amount': TextEditingController(
            text: item['amount']?.toString() ?? '',
          ),
        },
      )
      .toList();
  final itemControllers = initialItems.isEmpty
      ? [_buildItemController()]
      : initialItems;

  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: isDark ? vatDarkCard : Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setState) {
          return Padding(
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
                        width: 40,
                        height: 4,
                        decoration: BoxDecoration(
                          color: Colors.grey[400],
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      isEdit
                          ? (isSwahili
                              ? 'Hariri Manunuzi ya EFD'
                              : 'Edit ${receipt?['company_name'] ?? ''} Receipt')
                          : (isSwahili
                              ? 'Ongeza Manunuzi ya EFD'
                              : 'Add EFD Auto Purchase'),
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Text(
                          isSwahili ? 'Ni Gharama?' : 'Is Expense?',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDark ? Colors.white54 : AppColors.textHint,
                          ),
                        ),
                        const Spacer(),
                        Switch(
                          value: isExpense == 'YES',
                          onChanged: (v) =>
                              setState(() => isExpense = v ? 'YES' : 'NO'),
                          activeTrackColor: vatAccentTeal,
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    if (isEdit) ...[
                      vatTextField(
                        controller: dateCtrl,
                        label: isSwahili ? 'Tarehe *' : 'Date *',
                        isDark: isDark,
                        readOnly: true,
                        onTap: () async {
                          final picked = await vatPickDate(
                            ctx,
                            DateTime.tryParse(dateCtrl.text) ?? DateTime.now(),
                          );
                          if (picked != null) {
                            dateCtrl.text = vatDateFmt(picked);
                          }
                        },
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                    ] else ...[
                      vatTextField(
                        controller: companyCtrl,
                        label: isSwahili ? 'Jina la Muuzaji *' : 'Supplier Name *',
                        isDark: isDark,
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                      vatTextField(
                        controller: vrnCtrl,
                        label: 'VRN',
                        isDark: isDark,
                      ),
                      vatTextField(
                        controller: receiptNoCtrl,
                        label: isSwahili ? 'Namba ya Risiti *' : 'Receipt Number *',
                        isDark: isDark,
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                      vatTextField(
                        controller: receiptDateCtrl,
                        label: isSwahili ? 'Tarehe ya Risiti *' : 'Receipt Date *',
                        isDark: isDark,
                        readOnly: true,
                        onTap: () async {
                          final picked = await vatPickDate(
                            ctx,
                            DateTime.tryParse(receiptDateCtrl.text) ?? DateTime.now(),
                          );
                          if (picked != null) {
                            receiptDateCtrl.text = vatDateFmt(picked);
                          }
                        },
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                      vatTextField(
                        controller: receiptTimeCtrl,
                        label: isSwahili ? 'Muda wa Risiti' : 'Receipt Time',
                        isDark: isDark,
                      ),
                      vatTextField(
                        controller: verificationCtrl,
                        label: isSwahili
                            ? 'Namba ya Uhakiki'
                            : 'Verification Code',
                        isDark: isDark,
                      ),
                      vatTextField(
                        controller: amountVatExcCtrl,
                        label: isSwahili ? 'Kiasi Bila VAT *' : 'Amount VAT Excl. *',
                        isDark: isDark,
                        keyboardType: TextInputType.number,
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                      vatTextField(
                        controller: vatAmountCtrl,
                        label: 'VAT Amount',
                        isDark: isDark,
                        keyboardType: TextInputType.number,
                      ),
                      vatTextField(
                        controller: totalAmountCtrl,
                        label: isSwahili ? 'Jumla *' : 'Total Amount *',
                        isDark: isDark,
                        keyboardType: TextInputType.number,
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                      vatTextField(
                        controller: discountCtrl,
                        label: isSwahili ? 'Punguzo' : 'Discount',
                        isDark: isDark,
                        keyboardType: TextInputType.number,
                      ),
                      const SizedBox(height: 6),
                      Text(
                        isSwahili ? 'Bidhaa za Risiti' : 'Receipt Items',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 10),
                      ...List.generate(itemControllers.length, (index) {
                        final item = itemControllers[index];
                        return _ItemEditorCard(
                          index: index,
                          isDark: isDark,
                          isSwahili: isSwahili,
                          descriptionCtrl:
                              item['description']! as TextEditingController,
                          qtyCtrl: item['qty']! as TextEditingController,
                          amountCtrl: item['amount']! as TextEditingController,
                          canRemove: itemControllers.length > 1,
                          onRemove: () {
                            setState(() {
                              itemControllers.removeAt(index);
                            });
                          },
                        );
                      }),
                      const SizedBox(height: 6),
                      OutlinedButton.icon(
                        onPressed: () {
                          setState(() {
                            itemControllers.add(_buildItemController());
                          });
                        },
                        icon: const Icon(Icons.add_rounded),
                        label: Text(isSwahili ? 'Ongeza Bidhaa' : 'Add Item'),
                      ),
                    ],
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () async {
                          if (!formKey.currentState!.validate()) return;

                          final items = itemControllers
                              .map(
                                (item) => {
                                  'description':
                                      (item['description']! as TextEditingController)
                                          .text
                                          .trim(),
                                  'qty': int.tryParse(
                                        (item['qty']! as TextEditingController)
                                            .text
                                            .trim(),
                                      ) ??
                                      1,
                                  'amount': double.tryParse(
                                        (item['amount']! as TextEditingController)
                                            .text
                                            .trim(),
                                      ) ??
                                      0,
                                },
                              )
                              .where((item) => (item['description'] as String).isNotEmpty)
                              .toList();

                          final fields = {
                            'company_name': companyCtrl.text.trim(),
                            'vrn': vrnCtrl.text.trim(),
                            'receipt_number': receiptNoCtrl.text.trim(),
                            'receipt_date': receiptDateCtrl.text.trim(),
                            'receipt_time': receiptTimeCtrl.text.trim(),
                            'receipt_verification_code':
                                verificationCtrl.text.trim(),
                            'receipt_total_excl_of_tax':
                                double.tryParse(amountVatExcCtrl.text.trim()) ?? 0,
                            'receipt_total_tax':
                                double.tryParse(vatAmountCtrl.text.trim()) ?? 0,
                            'receipt_total_incl_of_tax':
                                double.tryParse(totalAmountCtrl.text.trim()) ?? 0,
                            'receipt_total_discount':
                                double.tryParse(discountCtrl.text.trim()) ?? 0,
                            'is_expense': isExpense,
                            'items': items,
                          };

                          try {
                            final api = ref.read(apiClientProvider);
                            if (isEdit) {
                              await api.put(
                                '/vat/auto-purchases/${receipt['id']}',
                                data: {
                                  'is_expense': isExpense,
                                  'date': dateCtrl.text.trim(),
                                },
                              );
                            } else {
                              await api.post('/vat/auto-purchases', data: fields);
                            }
                            ref.invalidate(_autoDataProvider);
                            if (ctx.mounted) Navigator.pop(ctx);
                          } catch (e) {
                            if (ctx.mounted) {
                              ScaffoldMessenger.of(ctx).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    vatErrorMessage(e, isSwahili: isSwahili),
                                  ),
                                  backgroundColor: Colors.red,
                                ),
                              );
                            }
                          }
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        child: Text(
                          isEdit
                              ? (isSwahili ? 'Sasisha' : 'Update')
                              : (isSwahili ? 'Hifadhi' : 'Save'),
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      );
    },
  );
}

Map<String, TextEditingController> _buildItemController() => {
      'description': TextEditingController(),
      'qty': TextEditingController(text: '1'),
      'amount': TextEditingController(),
    };

Future<void> _showReceiptItemsSheet(
  BuildContext context, {
  required Map<String, dynamic> receipt,
  required bool isDark,
  required bool isSwahili,
}) async {
  final items =
      (receipt['items'] as List?)?.cast<Map<String, dynamic>>() ?? const [];

  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: isDark ? vatDarkCard : Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (ctx) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey[400],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              isSwahili
                  ? 'Bidhaa za ${receipt['company_name'] ?? 'Risiti'}'
                  : 'Receipt items for ${receipt['company_name'] ?? 'Receipt'}',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: isDark ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 16),
            if (items.isEmpty)
              Text(
                isSwahili ? 'Hakuna bidhaa' : 'No items available',
                style: TextStyle(
                  fontSize: 13,
                  color: isDark ? Colors.white54 : AppColors.textSecondary,
                ),
              )
            else
              Flexible(
                child: ListView.separated(
                  shrinkWrap: true,
                  itemCount: items.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final item = items[index];
                    return Container(
                      padding: const EdgeInsets.all(12),
                      decoration: vatCardDeco(isDark),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            width: 28,
                            height: 28,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: vatAccentBlue.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              '${index + 1}',
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: vatAccentBlue,
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item['description'] as String? ?? '-',
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: isDark
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  '${isSwahili ? 'Idadi' : 'Qty'}: ${item['qty'] ?? 0}',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: isDark
                                        ? Colors.white54
                                        : AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Text(
                            vatMoney(_asDouble(item['amount'])),
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: isDark ? vatAccentTeal : vatAccentBlue,
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
          ],
        ),
      );
    },
  );
}

Future<void> _openVerificationLink(
  BuildContext context,
  String? url, {
  required bool isSwahili,
}) async {
  final trimmed = url?.trim() ?? '';
  if (trimmed.isEmpty) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          isSwahili
              ? 'Hakuna kiungo cha uhakiki.'
              : 'No verification link available.',
        ),
      ),
    );
    return;
  }

  final opened = await ExternalLauncherService.openUri(Uri.parse(trimmed));
  if (!opened && context.mounted) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          isSwahili ? 'Imeshindwa kufungua kiungo.' : 'Failed to open link.',
        ),
      ),
    );
  }
}

class _SearchField extends ConsumerWidget {
  final bool isDark;
  final bool isSwahili;
  final ValueChanged<String> onChanged;

  const _SearchField({
    required this.isDark,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final value = ref.watch(_autoSearchProvider);
    return TextFormField(
      initialValue: value,
      onChanged: onChanged,
      style: TextStyle(
        fontSize: 13,
        color: isDark ? Colors.white : AppColors.textPrimary,
      ),
      decoration: InputDecoration(
        labelText: isSwahili ? 'Tafuta' : 'Search',
        prefixIcon: const Icon(Icons.search_rounded),
        filled: true,
        fillColor:
            isDark ? const Color(0xFF0F1923) : Colors.grey.withValues(alpha: 0.05),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(
            color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.2),
          ),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(
            color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.2),
          ),
        ),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      ),
    );
  }
}

class _ReceiptWebCard extends StatelessWidget {
  final int index;
  final Map<String, dynamic> receipt;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onOpenItems;
  final VoidCallback onVerificationTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ReceiptWebCard({
    required this.index,
    required this.receipt,
    required this.isDark,
    required this.isSwahili,
    required this.onOpenItems,
    required this.onVerificationTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final goodsText = (receipt['items_summary'] as String? ?? '').trim();
    final verificationCode =
        (receipt['receipt_verification_code'] as String? ?? '').trim();
    final verificationUrl = receipt['verification_url'] as String?;
    final hasVerification = verificationCode.isNotEmpty && verificationUrl != null;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: vatCardDeco(isDark),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 30,
                height: 30,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: vatAccentBlue.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '$index',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: vatAccentBlue,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      receipt['company_name'] as String? ?? '-',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${receipt['inserted_date'] ?? receipt['date'] ?? '-'}',
                      style: TextStyle(
                        fontSize: 11,
                        color: isDark ? Colors.white54 : AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'edit') onEdit();
                  if (value == 'delete') onDelete();
                },
                itemBuilder: (context) => [
                  PopupMenuItem<String>(
                    value: 'edit',
                    child: Text(isSwahili ? 'Hariri' : 'Edit'),
                  ),
                  PopupMenuItem<String>(
                    value: 'delete',
                    child: Text(isSwahili ? 'Futa' : 'Delete'),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          _TwoColumnRow(
            leftLabel: 'SupplierVRN',
            leftValue: receipt['vrn'] as String? ?? '-',
            rightLabel: 'TaxInvoice',
            rightValue: receipt['receipt_number'] as String? ?? '-',
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          _TwoColumnRow(
            leftLabel: 'InvoiceDate',
            leftValue: receipt['receipt_date'] as String? ?? '-',
            rightLabel: 'Is Expenses',
            rightValue: receipt['is_expense'] as String? ?? 'NO',
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          Text(
            'Goods',
            style: TextStyle(
              fontSize: 10,
              color: isDark ? Colors.white38 : AppColors.textHint,
            ),
          ),
          const SizedBox(height: 4),
          InkWell(
            onTap: onOpenItems,
            borderRadius: BorderRadius.circular(8),
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 2),
              child: Text(
                goodsText.isEmpty ? '-' : goodsText,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: vatAccentBlue,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 12,
            runSpacing: 10,
            children: [
              _MoneyCell(
                label: 'AmountVATEXC',
                value: vatMoney(receipt['amount_vat_exc']),
                isDark: isDark,
              ),
              _MoneyCell(
                label: 'VATAmount',
                value: vatMoney(receipt['vat_amount']),
                isDark: isDark,
              ),
              _MoneyCell(
                label: 'TotalAmount',
                value: vatMoney(receipt['total_amount']),
                isDark: isDark,
              ),
              _MoneyCell(
                label: 'Discount',
                value: vatMoney(receipt['discount']),
                isDark: isDark,
              ),
            ],
          ),
          if (hasVerification) ...[
            const SizedBox(height: 10),
            InkWell(
              onTap: onVerificationTap,
              borderRadius: BorderRadius.circular(8),
              child: Row(
                children: [
                  const Icon(
                    Icons.verified_rounded,
                    size: 14,
                    color: Color(0xFF10B981),
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      'VerificationCode: $verificationCode',
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF10B981),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _TwoColumnRow extends StatelessWidget {
  final String leftLabel;
  final String leftValue;
  final String rightLabel;
  final String rightValue;
  final bool isDark;

  const _TwoColumnRow({
    required this.leftLabel,
    required this.leftValue,
    required this.rightLabel,
    required this.rightValue,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: VatInfoCol(
            label: leftLabel,
            value: leftValue,
            isDark: isDark,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: VatInfoCol(
            label: rightLabel,
            value: rightValue,
            isDark: isDark,
          ),
        ),
      ],
    );
  }
}

class _TotalsFooter extends StatelessWidget {
  final Map<String, dynamic> totals;
  final bool isDark;

  const _TotalsFooter({required this.totals, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: vatCardDeco(isDark),
      child: Column(
        children: [
          _TotalRow(
            label: 'AmountVATEXC',
            value: vatMoney(totals['amount_vat_exc']),
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          _TotalRow(
            label: 'VATAmount',
            value: vatMoney(totals['vat_amount']),
            isDark: isDark,
          ),
          const SizedBox(height: 8),
          _TotalRow(
            label: 'TotalAmount',
            value: vatMoney(totals['total_amount']),
            isDark: isDark,
          ),
        ],
      ),
    );
  }
}

class _TotalRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;

  const _TotalRow({
    required this.label,
    required this.value,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDark ? Colors.white70 : AppColors.textSecondary,
            ),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: isDark ? vatAccentTeal : vatAccentBlue,
          ),
        ),
      ],
    );
  }
}

class _ItemEditorCard extends StatelessWidget {
  final int index;
  final bool isDark;
  final bool isSwahili;
  final TextEditingController descriptionCtrl;
  final TextEditingController qtyCtrl;
  final TextEditingController amountCtrl;
  final bool canRemove;
  final VoidCallback onRemove;

  const _ItemEditorCard({
    required this.index,
    required this.isDark,
    required this.isSwahili,
    required this.descriptionCtrl,
    required this.qtyCtrl,
    required this.amountCtrl,
    required this.canRemove,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color:
            isDark ? const Color(0xFF0F1923) : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.15),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${isSwahili ? 'Bidhaa' : 'Item'} ${index + 1}',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: isDark ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ),
              if (canRemove)
                IconButton(
                  onPressed: onRemove,
                  icon: const Icon(Icons.delete_outline_rounded),
                  color: Colors.red.withValues(alpha: 0.8),
                  visualDensity: VisualDensity.compact,
                ),
            ],
          ),
          vatTextField(
            controller: descriptionCtrl,
            label: isSwahili ? 'Maelezo ya Bidhaa' : 'Description',
            isDark: isDark,
          ),
          Row(
            children: [
              Expanded(
                child: vatTextField(
                  controller: qtyCtrl,
                  label: isSwahili ? 'Idadi' : 'Qty',
                  isDark: isDark,
                  keyboardType: TextInputType.number,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: vatTextField(
                  controller: amountCtrl,
                  label: isSwahili ? 'Kiasi' : 'Amount',
                  isDark: isDark,
                  keyboardType: TextInputType.number,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MoneyCell extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;

  const _MoneyCell({
    required this.label,
    required this.value,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 110,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 9,
              color: isDark ? Colors.white38 : AppColors.textHint,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDark ? vatAccentTeal : vatAccentBlue,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
