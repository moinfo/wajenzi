import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _startProvider = StateProvider.autoDispose<DateTime>(
    (ref) => DateTime(DateTime.now().year, DateTime.now().month, 1));
final _endProvider =
    StateProvider.autoDispose<DateTime>((ref) => DateTime.now());

final _dataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final s = ref.watch(_startProvider);
  final e = ref.watch(_endProvider);
  final resp = await api.get('/vat/payments',
      queryParameters: {'start_date': vatDateFmt(s), 'end_date': vatDateFmt(e)});
  return resp.data['data'] as Map<String, dynamic>;
});

class VatPaymentsScreen extends ConsumerWidget {
  const VatPaymentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_dataProvider);
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Malipo ya VAT' : 'VAT Payments'),
        backgroundColor: isDark ? vatDarkCard : null,
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showPaymentForm(context, ref, isDark, isSwahili),
          child: const Icon(Icons.add),
        ),
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
            final payments =
                (data['payments'] as List?)?.cast<Map<String, dynamic>>() ??
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
                VatSummaryChip(
                  label: isSwahili ? 'Jumla Malipo' : 'Total Payments',
                  value: vatMoney(totals['amount']),
                  color: vatAccentTeal,
                  isDark: isDark,
                ),
                const SizedBox(height: 14),
                VatCountBadge(
                    count: payments.length,
                    label: isSwahili ? 'Malipo' : 'Payments',
                    isDark: isDark),
                const SizedBox(height: 8),
                if (payments.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...payments.map((p) => _PaymentCard(
                        payment: p,
                        isDark: isDark,
                        isSwahili: isSwahili,
                        onEdit: () => _showPaymentForm(
                            context, ref, isDark, isSwahili,
                            payment: p),
                        onDelete: () async {
                          final ok = await vatDelete(
                              context, ref, '/vat/payments', p['id'],
                              isSwahili: isSwahili);
                          if (ok) ref.invalidate(_dataProvider);
                        },
                      )),
              ],
            );
          },
        ),
      ),
    );
  }
}

Future<void> _showPaymentForm(BuildContext context, WidgetRef ref, bool isDark,
    bool isSwahili,
    {Map<String, dynamic>? payment}) async {
  // Load reference data before opening sheet
  Map<String, dynamic>? refDataMap;
  try {
    final api = ref.read(apiClientProvider);
    final resp = await api.get('/vat/reference-data');
    refDataMap = resp.data['data'] as Map<String, dynamic>;
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error loading form data: $e'),
            backgroundColor: Colors.red),
      );
    }
    return;
  }
  if (!context.mounted) return;

  final isEdit = payment != null;
  final amountCtrl =
      TextEditingController(text: payment?['amount']?.toString() ?? '');
  final descCtrl =
      TextEditingController(text: payment?['description'] ?? '');
  final dateCtrl = TextEditingController(
      text: payment?['date'] ?? vatDateFmt(DateTime.now()));
  int? selectedBankId = payment?['bank_id'];
  File? selectedFile;
  final formKey = GlobalKey<FormState>();
  final banks = (refDataMap['banks'] as List?) ?? [];

  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: isDark ? vatDarkCard : Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (ctx) {
      return StatefulBuilder(builder: (ctx, setState) {
        return Padding(
          padding: EdgeInsets.fromLTRB(
              20, 16, 20, MediaQuery.of(ctx).viewInsets.bottom + 100),
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
                          borderRadius: BorderRadius.circular(2)),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    isEdit
                        ? (isSwahili ? 'Hariri Malipo' : 'Edit Payment')
                        : (isSwahili ? 'Ongeza Malipo' : 'Add Payment'),
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 20),
                  vatDropdown<int>(
                    value: selectedBankId,
                    items: banks.map((b) => b['id'] as int).toList(),
                    label: isSwahili ? 'Benki *' : 'Bank *',
                    isDark: isDark,
                    labelBuilder: (id) =>
                        banks.firstWhere(
                            (b) => b['id'] == id)['name'] as String,
                    onChanged: (v) =>
                        setState(() => selectedBankId = v),
                    validator: (v) =>
                        v == null ? 'Required' : null,
                  ),
                  vatTextField(
                    controller: amountCtrl,
                    label: isSwahili ? 'Kiasi *' : 'Amount *',
                    isDark: isDark,
                    keyboardType: TextInputType.number,
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Required' : null,
                  ),
                  vatTextField(
                    controller: descCtrl,
                    label: isSwahili ? 'Maelezo' : 'Description',
                    isDark: isDark,
                  ),
                  vatTextField(
                    controller: dateCtrl,
                    label: isSwahili ? 'Tarehe *' : 'Date *',
                    isDark: isDark,
                    readOnly: true,
                    onTap: () async {
                      final picked = await vatPickDate(
                          ctx,
                          DateTime.tryParse(dateCtrl.text) ?? DateTime.now());
                      if (picked != null) {
                        dateCtrl.text = vatDateFmt(picked);
                      }
                    },
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Required' : null,
                  ),
                  VatFilePicker(
                    file: selectedFile,
                    isDark: isDark,
                    isSwahili: isSwahili,
                    onPicked: (f) => setState(() => selectedFile = f),
                  ),
                  const SizedBox(height: 8),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (!formKey.currentState!.validate()) return;
                        if (selectedBankId == null) return;

                        final fields = {
                          'bank_id': selectedBankId,
                          'amount': double.tryParse(amountCtrl.text) ?? 0,
                          'description': descCtrl.text,
                          'date': dateCtrl.text,
                        };

                        try {
                          final api = ref.read(apiClientProvider);
                          if (isEdit) {
                            if (selectedFile != null) {
                              fields['_method'] = 'PUT';
                              final data = await vatBuildFormData(
                                  fields, selectedFile);
                              await api.uploadFile(
                                  '/vat/payments/${payment['id']}',
                                  data: data);
                            } else {
                              await api.put(
                                  '/vat/payments/${payment['id']}',
                                  data: fields);
                            }
                          } else {
                            if (selectedFile != null) {
                              final data = await vatBuildFormData(
                                  fields, selectedFile);
                              await api.uploadFile('/vat/payments',
                                  data: data);
                            } else {
                              await api.post('/vat/payments', data: fields);
                            }
                          }
                          ref.invalidate(_dataProvider);
                          if (ctx.mounted) Navigator.pop(ctx);
                        } catch (e) {
                          if (ctx.mounted) {
                            ScaffoldMessenger.of(ctx).showSnackBar(
                              SnackBar(
                                  content: Text('Error: $e'),
                                  backgroundColor: Colors.red),
                            );
                          }
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10)),
                      ),
                      child: Text(
                          isEdit
                              ? (isSwahili ? 'Sasisha' : 'Update')
                              : (isSwahili ? 'Hifadhi' : 'Save'),
                          style: const TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w600)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      });
    },
  );
}

class _PaymentCard extends StatelessWidget {
  final Map<String, dynamic> payment;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  const _PaymentCard({
    required this.payment,
    required this.isDark,
    required this.isSwahili,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onEdit,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(14),
        decoration: vatCardDeco(isDark),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.account_balance_rounded,
                    size: 16, color: vatAccentTeal),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    payment['bank_name'] ?? '-',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                ),
                VatStatusBadge(
                    status: payment['status'] as String? ?? ''),
                const SizedBox(width: 6),
                GestureDetector(
                  onTap: onDelete,
                  child: Icon(Icons.delete_outline_rounded,
                      size: 18,
                      color: Colors.red.withValues(alpha: 0.7)),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                VatInfoCol(
                    label: 'Date',
                    value: payment['date'] ?? '-',
                    isDark: isDark),
                VatInfoCol(
                  label: 'Amount',
                  value: vatMoney(payment['amount']),
                  isDark: isDark,
                  isMoney: true,
                ),
              ],
            ),
            if (payment['description'] != null &&
                (payment['description'] as String).isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                payment['description'],
                style: TextStyle(
                  fontSize: 11,
                  color: isDark ? Colors.white54 : AppColors.textSecondary,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
