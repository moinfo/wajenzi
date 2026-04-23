import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _processApprovalFlowStepsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/process-approval-flow-steps');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _processApprovalFlowStepRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/process-approval-flow-steps/reference-data',
      );
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

class ProcessApprovalFlowStepsScreen extends ConsumerWidget {
  const ProcessApprovalFlowStepsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final stepsAsync = ref.watch(_processApprovalFlowStepsProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(tr('Approval Flow Steps', 'خطوات مسار الموافقة')),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async =>
            ref.invalidate(_processApprovalFlowStepsProvider),
        child: stepsAsync.when(
          loading: () => LoadingWidget(
            message: tr(
              'Loading approval flow steps...',
              'جاري تحميل خطوات مسار الموافقة...',
            ),
          ),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              Text(
                tr(
                  'Failed to load approval flow steps',
                  'تعذر تحميل خطوات مسار الموافقة',
                ),
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 8),
              Text(vatErrorMessage(error), textAlign: TextAlign.center),
            ],
          ),
          data: (steps) {
            if (steps.isEmpty) {
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
                          Icons.format_list_numbered,
                          size: 56,
                          color: AppColors.primary,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          tr(
                            'No approval flow steps found',
                            'لا توجد خطوات لمسار الموافقة',
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
                            'Create the first step to match the web settings page.',
                            'أنشئ الخطوة الأولى لتتوافق مع صفحة الإعدادات على الويب.',
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: Text(
                            tr(
                              'New Approval Flow Step',
                              'خطوة مسار موافقة جديدة',
                            ),
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
                        child: const Icon(
                          Icons.format_list_numbered,
                          color: AppColors.primary,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              tr(
                                'Approval Flow Steps Settings',
                                'إعدادات خطوات مسار الموافقة',
                              ),
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
                                'Showing ${steps.length} records',
                                'عرض ${steps.length} سجلاً',
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
                ...List.generate(steps.length, (index) {
                  final step = steps[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                width: 36,
                                height: 36,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: Colors.grey.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  '${index + 1}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      step['process_approval_flow_name']
                                              ?.toString() ??
                                          '-',
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    Text(
                                      step['role_name']?.toString() ?? '-',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              PopupMenuButton<String>(
                                onSelected: (value) {
                                  if (value == 'edit') {
                                    _openForm(context, ref, step: step);
                                  } else if (value == 'delete') {
                                    _deleteStep(context, ref, step);
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
                                tr('Action', 'الإجراء'),
                                step['action']?.toString() ?? '-',
                              ),
                              _chip(
                                tr('Order', 'الترتيب'),
                                '${step['order'] ?? '-'}',
                              ),
                              if ((step['description'] ?? '')
                                  .toString()
                                  .trim()
                                  .isNotEmpty)
                                _chip(
                                  tr('Description', 'الوصف'),
                                  step['description'].toString(),
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                }),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(context, ref),
        icon: const Icon(Icons.add),
        label: Text(tr('New Approval Flow Step', 'خطوة مسار موافقة جديدة')),
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
    Map<String, dynamic>? step,
  }) async {
    final refs = await ref.read(_processApprovalFlowStepRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.88,
        child: _ProcessApprovalFlowStepFormSheet(refs: refs, step: step),
      ),
    );

    if (result == true) {
      ref.invalidate(_processApprovalFlowStepsProvider);
    }
  }

  Future<void> _deleteStep(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> step,
  ) async {
    final isArabic = ref.read(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(tr('Delete Approval Flow Step', 'حذف خطوة مسار الموافقة')),
        content: Text(
          tr(
            'Delete ${step['process_approval_flow_name']} step for ${step['role_name']}?',
            'هل تريد حذف خطوة ${step['process_approval_flow_name']} للدور ${step['role_name']}؟',
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
          .delete('/process-approval-flow-steps/${step['id']}');
      ref.invalidate(_processApprovalFlowStepsProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            tr(
              'Approval flow step deleted successfully',
              'تم حذف خطوة مسار الموافقة بنجاح',
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

class _ProcessApprovalFlowStepFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? step;

  const _ProcessApprovalFlowStepFormSheet({required this.refs, this.step});

  @override
  ConsumerState<_ProcessApprovalFlowStepFormSheet> createState() =>
      _ProcessApprovalFlowStepFormSheetState();
}

class _ProcessApprovalFlowStepFormSheetState
    extends ConsumerState<_ProcessApprovalFlowStepFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _orderController;
  late final TextEditingController _descriptionController;
  int? _selectedFlowId;
  int? _selectedRoleId;
  String? _selectedAction;
  bool _submitting = false;

  bool get _isEdit => widget.step != null;

  @override
  void initState() {
    super.initState();
    _selectedFlowId = _toNullableInt(widget.step?['process_approval_flow_id']);
    _selectedRoleId = _toNullableInt(widget.step?['role_id']);
    _selectedAction = widget.step?['action']?.toString();
    _orderController = TextEditingController(
      text: widget.step?['order']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.step?['description']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _orderController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final flows = (widget.refs['flows'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final roles = (widget.refs['roles'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final actions = (widget.refs['actions'] as List? ?? const [])
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
                      ? tr(
                          'Edit Approval Flow Step',
                          'تعديل خطوة مسار الموافقة',
                        )
                      : tr(
                          'Create New Approval Flow Step',
                          'إنشاء خطوة مسار موافقة جديدة',
                        ),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                DropdownButtonFormField<int>(
                  value: _selectedFlowId,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: tr('Approval Flow', 'مسار الموافقة'),
                    border: const OutlineInputBorder(),
                  ),
                  items: flows
                      .map(
                        (flow) => DropdownMenuItem<int>(
                          value: _toInt(flow['id']),
                          child: Text(
                            flow['name']?.toString() ?? '-',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _selectedFlowId = value),
                  validator: (value) => value == null
                      ? tr('Approval flow is required', 'مسار الموافقة مطلوب')
                      : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value: _selectedRoleId,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: tr('Role', 'الدور'),
                    border: const OutlineInputBorder(),
                  ),
                  items: roles
                      .map(
                        (role) => DropdownMenuItem<int>(
                          value: _toInt(role['id']),
                          child: Text(
                            role['name']?.toString() ?? '-',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _selectedRoleId = value),
                  validator: (value) => value == null
                      ? tr('Role is required', 'الدور مطلوب')
                      : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: _selectedAction,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: tr('Action', 'الإجراء'),
                    border: const OutlineInputBorder(),
                  ),
                  items: actions
                      .map(
                        (action) => DropdownMenuItem<String>(
                          value: action['value']?.toString() ?? '',
                          child: Text(
                            action['name']?.toString() ?? '-',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _selectedAction = value),
                  validator: (value) => (value == null || value.isEmpty)
                      ? tr('Action is required', 'الإجراء مطلوب')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _orderController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: tr('Order', 'الترتيب'),
                    border: const OutlineInputBorder(),
                    hintText: tr(
                      'Step order e.g. 1, 2, 3',
                      'ترتيب الخطوة مثل 1، 2، 3',
                    ),
                  ),
                  validator: (value) {
                    final order = int.tryParse(value?.trim() ?? '');
                    if (order == null || order < 1) {
                      return tr('Valid order is required', 'ترتيب صحيح مطلوب');
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  minLines: 3,
                  maxLines: 4,
                  decoration: InputDecoration(
                    labelText: tr('Description', 'الوصف'),
                    border: const OutlineInputBorder(),
                    hintText: tr(
                      'Optional description for this approval step',
                      'وصف اختياري لهذه الخطوة',
                    ),
                  ),
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
                                ? tr(
                                    'Update Approval Flow Step',
                                    'تحديث خطوة مسار الموافقة',
                                  )
                                : tr(
                                    'Save Approval Flow Step',
                                    'حفظ خطوة مسار الموافقة',
                                  )),
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedFlowId == null ||
        _selectedRoleId == null ||
        _selectedAction == null) {
      return;
    }

    setState(() => _submitting = true);

    final payload = {
      'process_approval_flow_id': _selectedFlowId,
      'role_id': _selectedRoleId,
      'action': _selectedAction,
      'order': int.tryParse(_orderController.text.trim()) ?? 1,
      'description': _blankToNull(_descriptionController.text),
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put(
          '/process-approval-flow-steps/${widget.step!['id']}',
          data: payload,
        );
      } else {
        await api.post('/process-approval-flow-steps', data: payload);
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

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = _toInt(value);
  return parsed <= 0 ? null : parsed;
}

String? _blankToNull(String? value) {
  final text = value?.trim() ?? '';
  return text.isEmpty ? null : text;
}
