import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../../../core/config/app_config.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _startProvider = StateProvider.autoDispose<DateTime>(
    (ref) => DateTime.now());
final _endProvider =
    StateProvider.autoDispose<DateTime>((ref) => DateTime.now());
final _paymentSearchProvider =
    StateProvider.autoDispose<String>((ref) => '');

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
    final searchTerm = ref.watch(_paymentSearchProvider).trim().toLowerCase();
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
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
            final allPayments =
                (data['payments'] as List?)?.cast<Map<String, dynamic>>() ??
                    [];
            final payments = searchTerm.isEmpty
                ? allPayments
                : allPayments.where((payment) {
                    final haystack = [
                      payment['date'],
                      payment['bank_name'],
                      payment['description'],
                      payment['status'],
                      payment['approval_summary'],
                      payment['amount'],
                    ].whereType<Object>().join(' ').toLowerCase();
                    return haystack.contains(searchTerm);
                  }).toList();
            final totals = data['totals'] as Map<String, dynamic>? ?? {};

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: EdgeInsets.fromLTRB(16, 12, 16, bottomPad),
              children: [
                TextField(
                  onChanged: (value) =>
                      ref.read(_paymentSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.search_rounded),
                    hintText: isSwahili ? 'Search' : 'Search',
                    filled: true,
                    fillColor: isDark ? vatDarkCard : Colors.white,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: isDark
                            ? vatDarkBorder
                            : Colors.grey.withValues(alpha: 0.15),
                      ),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: isDark
                            ? vatDarkBorder
                            : Colors.grey.withValues(alpha: 0.15),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 8),
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
                        onView: () =>
                            _showPaymentDetails(context, ref, isDark, isSwahili, p),
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

Future<void> _showPaymentDetails(
  BuildContext context,
  WidgetRef ref,
  bool isDark,
  bool isSwahili,
  Map<String, dynamic> payment,
) async {
  Map<String, dynamic> detail = payment;

  try {
    final api = ref.read(apiClientProvider);
    final resp = await api.get('/vat/payments/${payment['id']}');
    final data = resp.data['data'];
    if (data is Map<String, dynamic>) {
      detail = data;
    }
  } catch (_) {}

  if (!context.mounted) return;

  showModalBottomSheet(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (ctx) => Container(
      height: MediaQuery.of(context).size.height * 0.6,
      decoration: BoxDecoration(
        color: isDark ? vatDarkCard : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          isSwahili ? 'Maelezo ya Malipo' : 'Payment Details',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: isDark ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(ctx),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                children: [
                  _DetailRow(
                    label: isSwahili ? 'Benki' : 'Bank',
                    value: detail['bank_name']?.toString() ?? '-',
                    isDarkMode: isDark,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Tarehe' : 'Date',
                    value: detail['date']?.toString() ?? '-',
                    isDarkMode: isDark,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Kiasi' : 'Amount',
                    value: vatMoney(detail['amount']),
                    isDarkMode: isDark,
                    valueColor: vatAccentTeal,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Hali' : 'Status',
                    value: detail['status']?.toString() ?? '-',
                    isDarkMode: isDark,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Namba ya Hati' : 'Document Number',
                    value: detail['document_number']?.toString() ?? '-',
                    isDarkMode: isDark,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Kiambatisho' : 'Attachment',
                    value: (detail['has_attachment'] == true)
                        ? (isSwahili ? 'Kipo' : 'Available')
                        : (isSwahili ? 'Hakipo' : 'Not available'),
                    isDarkMode: isDark,
                  ),
                  if ((detail['file_url']?.toString().isNotEmpty ?? false)) ...[
                    const SizedBox(height: 8),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: () async {
                          final normalizedUrl = AppConfig.normalizeExternalUrl(
                            detail['file_url']?.toString(),
                          );
                          if (normalizedUrl == null) return;
                          await ExternalLauncherService.openUri(
                            Uri.parse(normalizedUrl),
                          );
                        },
                        icon: const Icon(Icons.attach_file_rounded),
                        label: Text(
                          isSwahili ? 'Fungua Kiambatisho' : 'Open Attachment',
                        ),
                      ),
                    ),
                  ],
                  if ((detail['description']?.toString().isNotEmpty ?? false))
                    _DetailRow(
                      label: isSwahili ? 'Maelezo' : 'Description',
                      value: detail['description'].toString(),
                      isDarkMode: isDark,
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
  );
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
        SnackBar(
          content: Text(vatErrorMessage(e, isSwahili: isSwahili)),
          backgroundColor: Colors.red,
        ),
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
                                data: data,
                                options: Options(
                                  contentType: 'multipart/form-data',
                                ),
                              );
                            } else {
                              await api.put(
                                  '/vat/payments/${payment['id']}',
                                  data: fields);
                            }
                          } else {
                            if (selectedFile != null) {
                              final data = await vatBuildFormData(
                                  fields, selectedFile);
                              await api.uploadFile(
                                '/vat/payments',
                                data: data,
                                options: Options(
                                  contentType: 'multipart/form-data',
                                ),
                              );
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
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  const _PaymentCard({
    required this.payment,
    required this.isDark,
    required this.isSwahili,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onView,
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
                  onTap: onEdit,
                  child: Icon(Icons.edit_outlined,
                      size: 18,
                      color: (isDark ? Colors.white70 : AppColors.textSecondary)),
                ),
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

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
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
              value.isEmpty ? '-' : value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: valueColor ??
                    (isDarkMode ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
