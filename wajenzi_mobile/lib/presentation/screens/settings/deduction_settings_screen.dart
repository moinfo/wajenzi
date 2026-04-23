import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _deductionSettingsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/deduction-settings');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _deductionSettingRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/deduction-settings/reference-data');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

class DeductionSettingsScreen extends ConsumerWidget {
  const DeductionSettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final asyncData = ref.watch(_deductionSettingsProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(tr('Deduction Settings', 'إعدادات الاستقطاعات')),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_deductionSettingsProvider),
        child: asyncData.when(
          loading: () => LoadingWidget(
            message: tr(
              'Loading deduction settings...',
              'جاري تحميل إعدادات الاستقطاعات...',
            ),
          ),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              const Text(
                'Failed to load deduction settings',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              Text(vatErrorMessage(error), textAlign: TextAlign.center),
            ],
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(24),
                children: [
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: Column(
                      children: [
                        const Icon(
                          Icons.tune,
                          size: 56,
                          color: AppColors.primary,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          tr(
                            'No deduction settings found',
                            'لا توجد إعدادات استقطاعات',
                          ),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          tr(
                            'Create a deduction setting to match the web settings page.',
                            'أنشئ إعداد استقطاع ليتوافق مع صفحة الإعدادات على الويب.',
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: Text(
                            tr('New Deduction Setting', 'إعداد استقطاع جديد'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              );
            }

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 18,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 52,
                        height: 52,
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: const Icon(Icons.tune, color: AppColors.primary),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              tr('Deduction Setting', 'إعداد الاستقطاع'),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              tr(
                                'Showing ${items.length} records',
                                'عرض ${items.length} سجلاً',
                              ),
                              style: const TextStyle(
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                ...items.map(
                  (item) => Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item['deduction_name']?.toString() ?? '-',
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    if ((item['deduction_abbreviation'] ?? '')
                                        .toString()
                                        .trim()
                                        .isNotEmpty) ...[
                                      const SizedBox(height: 4),
                                      Text(
                                        item['deduction_abbreviation']
                                            .toString(),
                                        style: const TextStyle(
                                          color: AppColors.textSecondary,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                              PopupMenuButton<String>(
                                onSelected: (value) {
                                  if (value == 'edit') {
                                    _openForm(context, ref, item: item);
                                  } else if (value == 'delete') {
                                    _deleteItem(context, ref, item);
                                  }
                                },
                                itemBuilder: (_) => [
                                  PopupMenuItem(
                                    value: 'edit',
                                    child: Text(tr('Edit', 'تعديل')),
                                  ),
                                  PopupMenuItem(
                                    value: 'delete',
                                    child: Text(tr('Delete', 'حذف')),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              _chip(
                                tr('Min', 'الحد الأدنى'),
                                _money(_toDouble(item['minimum_amount'])),
                              ),
                              _chip(
                                tr('Max', 'الحد الأقصى'),
                                _money(_toDouble(item['maximum_amount'])),
                              ),
                              _chip(
                                tr('Employee %', 'نسبة الموظف'),
                                _percent(
                                  _toDouble(item['employee_percentage']),
                                ),
                              ),
                              _chip(
                                tr('Employer %', 'نسبة صاحب العمل'),
                                _percent(
                                  _toDouble(item['employer_percentage']),
                                ),
                              ),
                              _chip(
                                tr('Additional', 'إضافي'),
                                _money(_toDouble(item['additional_amount'])),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(context, ref),
        icon: const Icon(Icons.add),
        label: Text(tr('New Setting', 'إعداد جديد')),
      ),
    );
  }

  Widget _chip(String label, String value) {
    return Container(
      constraints: const BoxConstraints(maxWidth: 220),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        '$label: $value',
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(fontSize: 12),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final refs = await ref.read(_deductionSettingRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.9,
        child: _DeductionSettingFormSheet(refs: refs, item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_deductionSettingsProvider);
    }
  }

  Future<void> _deleteItem(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final isArabic = ref.read(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(tr('Delete Deduction Setting', 'حذف إعداد الاستقطاع')),
        content: Text(
          tr(
            'Delete ${item['deduction_name']} setting?',
            'هل تريد حذف إعداد ${item['deduction_name']}؟',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(tr('Cancel', 'إلغاء')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(tr('Delete', 'حذف')),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref
          .read(apiClientProvider)
          .delete('/deduction-settings/${item['id']}');
      ref.invalidate(_deductionSettingsProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            tr(
              'Deduction setting deleted successfully',
              'تم حذف إعداد الاستقطاع بنجاح',
            ),
          ),
          backgroundColor: AppColors.success,
        ),
      );
    } catch (error) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _DeductionSettingFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? item;

  const _DeductionSettingFormSheet({required this.refs, this.item});

  @override
  ConsumerState<_DeductionSettingFormSheet> createState() =>
      _DeductionSettingFormSheetState();
}

class _DeductionSettingFormSheetState
    extends ConsumerState<_DeductionSettingFormSheet> {
  final _formKey = GlobalKey<FormState>();
  int? _selectedDeductionId;
  late final TextEditingController _minimumController;
  late final TextEditingController _maximumController;
  late final TextEditingController _employeeController;
  late final TextEditingController _employerController;
  late final TextEditingController _additionalController;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _selectedDeductionId = _toNullableInt(widget.item?['deduction_id']);
    _minimumController = TextEditingController(
      text: _textValue(widget.item?['minimum_amount']),
    );
    _maximumController = TextEditingController(
      text: _textValue(widget.item?['maximum_amount']),
    );
    _employeeController = TextEditingController(
      text: _textValue(widget.item?['employee_percentage']),
    );
    _employerController = TextEditingController(
      text: _textValue(widget.item?['employer_percentage']),
    );
    _additionalController = TextEditingController(
      text: _textValue(widget.item?['additional_amount']),
    );
  }

  @override
  void dispose() {
    _minimumController.dispose();
    _maximumController.dispose();
    _employeeController.dispose();
    _employerController.dispose();
    _additionalController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final deductions = (widget.refs['deductions'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Form(
            key: _formKey,
            child: ListView(
              children: [
                Center(
                  child: Container(
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  _isEdit
                      ? tr('Edit Deduction Setting', 'تعديل إعداد الاستقطاع')
                      : tr(
                          'Create New Deduction Setting',
                          'إنشاء إعداد استقطاع جديد',
                        ),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                DropdownButtonFormField<int>(
                  value: _selectedDeductionId,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: tr('Deduction', 'الاستقطاع'),
                    border: const OutlineInputBorder(),
                  ),
                  items: deductions
                      .map(
                        (deduction) => DropdownMenuItem<int>(
                          value: _toInt(deduction['id']),
                          child: Text(
                            deduction['name']?.toString() ?? '-',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) =>
                      setState(() => _selectedDeductionId = value),
                  validator: (value) => value == null
                      ? tr('Deduction is required', 'الاستقطاع مطلوب')
                      : null,
                ),
                const SizedBox(height: 16),
                _numberField(
                  _minimumController,
                  tr('Minimum Amount', 'الحد الأدنى للمبلغ'),
                ),
                const SizedBox(height: 16),
                _numberField(
                  _maximumController,
                  tr('Maximum Amount', 'الحد الأقصى للمبلغ'),
                ),
                const SizedBox(height: 16),
                _numberField(
                  _employeeController,
                  tr('Employee Percentage', 'نسبة الموظف'),
                  decimal: true,
                ),
                const SizedBox(height: 16),
                _numberField(
                  _employerController,
                  tr('Employer Percentage', 'نسبة صاحب العمل'),
                  decimal: true,
                ),
                const SizedBox(height: 16),
                _numberField(
                  _additionalController,
                  tr('Additional Amount', 'المبلغ الإضافي'),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? tr('Saving...', 'جاري الحفظ...')
                          : (_isEdit
                                ? tr('Update Setting', 'تحديث الإعداد')
                                : tr('Save Setting', 'حفظ الإعداد')),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _numberField(
    TextEditingController controller,
    String label, {
    bool decimal = false,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: TextInputType.numberWithOptions(decimal: decimal),
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
      ),
      validator: (value) {
        final text = value?.trim() ?? '';
        if (text.isEmpty) return null;
        if (double.tryParse(text) == null) return 'Enter a valid number';
        return null;
      },
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedDeductionId == null) return;

    setState(() => _submitting = true);

    final payload = {
      'deduction_id': _selectedDeductionId,
      'minimum_amount': _nullableDouble(_minimumController.text),
      'maximum_amount': _nullableDouble(_maximumController.text),
      'employee_percentage': _nullableDouble(_employeeController.text),
      'employer_percentage': _nullableDouble(_employerController.text),
      'additional_amount': _nullableDouble(_additionalController.text),
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put(
          '/deduction-settings/${widget.item!['id']}',
          data: payload,
        );
      } else {
        await api.post('/deduction-settings', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

String _money(double value) {
  final formatter = NumberFormat.currency(
    locale: 'en_TZ',
    symbol: 'TZS ',
    decimalDigits: 0,
  );
  return formatter.format(value);
}

String _percent(double value) {
  return NumberFormat('0.00').format(value);
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = _toInt(value);
  return parsed <= 0 ? null : parsed;
}

double? _nullableDouble(String? value) {
  final text = value?.trim() ?? '';
  if (text.isEmpty) return null;
  return double.tryParse(text);
}

String _textValue(dynamic value) {
  if (value == null) return '';
  final number = _toDouble(value);
  if (number == 0 && value.toString() == '0') return '0';
  if (number == number.roundToDouble()) return number.toInt().toString();
  return number.toString();
}
