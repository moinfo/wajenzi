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
  final resp = await api.get('/vat/sales',
      queryParameters: {'start_date': vatDateFmt(s), 'end_date': vatDateFmt(e)});
  return resp.data['data'] as Map<String, dynamic>;
});

class VatSalesScreen extends ConsumerWidget {
  const VatSalesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_dataProvider);
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Mauzo' : 'Sales'),
        backgroundColor: isDark ? vatDarkCard : null,
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showSaleForm(context, ref, isDark, isSwahili),
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
            final sales =
                (data['sales'] as List?)?.cast<Map<String, dynamic>>() ?? [];
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
                      label: 'Turnover',
                      value: vatMoney(totals['turnover']),
                      color: vatAccentBlue,
                      isDark: isDark,
                    )),
                    const SizedBox(width: 8),
                    Expanded(
                        child: VatSummaryChip(
                      label: 'Net (A+B+C)',
                      value: vatMoney(totals['net']),
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
                      label: 'Tax',
                      value: vatMoney(totals['tax']),
                      color: const Color(0xFFF59E0B),
                      isDark: isDark,
                    )),
                    const SizedBox(width: 8),
                    Expanded(
                        child: VatSummaryChip(
                      label: 'Exempt',
                      value: vatMoney(totals['turnover_exempt']),
                      color: const Color(0xFF8B5CF6),
                      isDark: isDark,
                    )),
                  ],
                ),
                const SizedBox(height: 14),
                VatCountBadge(
                    count: sales.length,
                    label: isSwahili ? 'Mauzo' : 'Sales',
                    isDark: isDark),
                const SizedBox(height: 8),
                if (sales.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...sales.map((s) => _SaleCard(
                        sale: s,
                        isDark: isDark,
                        isSwahili: isSwahili,
                        onEdit: () =>
                            _showSaleForm(context, ref, isDark, isSwahili, sale: s),
                        onDelete: () async {
                          final ok = await vatDelete(
                              context, ref, '/vat/sales', s['id'],
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

Future<void> _showSaleForm(BuildContext context, WidgetRef ref, bool isDark,
    bool isSwahili,
    {Map<String, dynamic>? sale}) async {
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

  final isEdit = sale != null;
  final amountCtrl =
      TextEditingController(text: sale?['turnover']?.toString() ?? '');
  final netCtrl = TextEditingController(text: sale?['net']?.toString() ?? '');
  final taxCtrl = TextEditingController(text: sale?['tax']?.toString() ?? '');
  final turnOverCtrl =
      TextEditingController(text: sale?['turnover_exempt']?.toString() ?? '');
  final dateCtrl =
      TextEditingController(text: sale?['date'] ?? vatDateFmt(DateTime.now()));
  int? selectedEfdId = sale?['efd_id'];
  File? selectedFile;
  final formKey = GlobalKey<FormState>();
  final efds = (refDataMap['efds'] as List?) ?? [];

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
                        ? (isSwahili ? 'Hariri Mauzo' : 'Edit Sale')
                        : (isSwahili ? 'Ongeza Mauzo' : 'Add Sale'),
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 20),
                  vatDropdown<int>(
                    value: selectedEfdId,
                    items: efds.map((e) => e['id'] as int).toList(),
                    label: 'Efd Name *',
                    isDark: isDark,
                    labelBuilder: (id) =>
                        efds.firstWhere(
                            (e) => e['id'] == id)['name'] as String,
                    onChanged: (v) => setState(() => selectedEfdId = v),
                    validator: (v) =>
                        v == null ? 'Required' : null,
                  ),
                  vatTextField(
                    controller: amountCtrl,
                    label: 'Turnover *',
                    isDark: isDark,
                    keyboardType: TextInputType.number,
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Required' : null,
                  ),
                  vatTextField(
                    controller: netCtrl,
                    label: 'Net (A+B+C) *',
                    isDark: isDark,
                    keyboardType: TextInputType.number,
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Required' : null,
                  ),
                  vatTextField(
                    controller: taxCtrl,
                    label: 'Tax *',
                    isDark: isDark,
                    keyboardType: TextInputType.number,
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Required' : null,
                  ),
                  vatTextField(
                    controller: turnOverCtrl,
                    label: 'Turnover (EX + SR) *',
                    isDark: isDark,
                    keyboardType: TextInputType.number,
                    validator: (v) =>
                        (v == null || v.isEmpty) ? 'Required' : null,
                  ),
                  vatTextField(
                    controller: dateCtrl,
                    label: 'Date *',
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
                        if (selectedEfdId == null) return;

                        final fields = {
                          'efd_id': selectedEfdId,
                          'amount': double.tryParse(amountCtrl.text) ?? 0,
                          'net': double.tryParse(netCtrl.text) ?? 0,
                          'tax': double.tryParse(taxCtrl.text) ?? 0,
                          'turn_over':
                              double.tryParse(turnOverCtrl.text) ?? 0,
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
                                  '/vat/sales/${sale['id']}',
                                  data: data);
                            } else {
                              await api.put('/vat/sales/${sale['id']}',
                                  data: fields);
                            }
                          } else {
                            if (selectedFile != null) {
                              final data = await vatBuildFormData(
                                  fields, selectedFile);
                              await api.uploadFile('/vat/sales',
                                  data: data);
                            } else {
                              await api.post('/vat/sales', data: fields);
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

class _SaleCard extends StatelessWidget {
  final Map<String, dynamic> sale;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  const _SaleCard({
    required this.sale,
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
                Icon(Icons.point_of_sale_rounded,
                    size: 16, color: vatAccentBlue),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    sale['efd_name'] ?? 'EFD',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                VatStatusBadge(status: sale['status'] as String? ?? ''),
                const SizedBox(width: 6),
                GestureDetector(
                  onTap: onDelete,
                  child: Icon(Icons.delete_outline_rounded,
                      size: 18, color: Colors.red.withValues(alpha: 0.7)),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                VatInfoCol(
                    label: 'Date',
                    value: sale['date'] ?? '-',
                    isDark: isDark),
                VatInfoCol(
                    label: 'Turnover',
                    value: vatMoney(sale['turnover']),
                    isDark: isDark,
                    isMoney: true),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                VatInfoCol(
                    label: 'Net (A+B+C)',
                    value: vatMoney(sale['net']),
                    isDark: isDark,
                    isMoney: true),
                VatInfoCol(
                    label: 'Tax',
                    value: vatMoney(sale['tax']),
                    isDark: isDark,
                    isMoney: true),
                VatInfoCol(
                    label: 'Exempt',
                    value: vatMoney(sale['turnover_exempt']),
                    isDark: isDark,
                    isMoney: true),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
