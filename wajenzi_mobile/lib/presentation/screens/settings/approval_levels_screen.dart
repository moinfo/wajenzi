import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _approvalLevelsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/approval-levels');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _approvalLevelReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/approval-levels/reference-data');
      final payload = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final data = payload['data'] is Map<String, dynamic>
          ? payload['data'] as Map<String, dynamic>
          : const <String, dynamic>{};
      return data;
    });

class ApprovalLevelsScreen extends ConsumerWidget {
  const ApprovalLevelsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final asyncData = ref.watch(_approvalLevelsProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(tr('Approval Levels', 'مستويات الموافقة')),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_approvalLevelsProvider),
        child: asyncData.when(
          loading: () => LoadingWidget(
            message: tr(
              'Loading approval levels...',
              'جاري تحميل مستويات الموافقة...',
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
                  'Failed to load approval levels',
                  'تعذر تحميل مستويات الموافقة',
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
                          Icons.rule_folder_outlined,
                          size: 56,
                          color: AppColors.primary,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          tr(
                            'No approval levels found',
                            'لا توجد مستويات موافقة',
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
                            'Create an approval level to manage this setting from mobile.',
                            'أنشئ مستوى موافقة لإدارة هذا الإعداد من التطبيق.',
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: Text(
                            tr('New Approval Level', 'مستوى موافقة جديد'),
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
                          Icons.rule_folder_outlined,
                          color: AppColors.primary,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              tr('Approval Levels', 'مستويات الموافقة'),
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
                ...List.generate(items.length, (index) {
                  final item = items[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 10,
                      ),
                      leading: Container(
                        width: 38,
                        height: 38,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: Colors.grey.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          '${item['order'] ?? index + 1}',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      title: Text(
                        item['approval_document_type_name']?.toString() ?? '-',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      subtitle: Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              item['user_group_name']?.toString() ??
                                  tr('No group', 'لا توجد مجموعة'),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Text(
                              tr(
                                'Action: ${item['action']?.toString() ?? '-'}',
                                'الإجراء: ${item['action']?.toString() ?? '-'}',
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            if ((item['description']?.toString() ?? '')
                                .isNotEmpty)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Text(
                                  item['description']?.toString() ?? '',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                          ],
                        ),
                      ),
                      trailing: PopupMenuButton<String>(
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
        label: Text(tr('New Approval Level', 'مستوى موافقة جديد')),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final refs = await ref.read(_approvalLevelReferenceProvider.future);
    if (!context.mounted) return;

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.82,
        child: _ApprovalLevelFormSheet(item: item, refs: refs),
      ),
    );
    if (result == true) {
      ref.invalidate(_approvalLevelsProvider);
      ref.invalidate(_approvalLevelReferenceProvider);
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
        title: Text(tr('Delete Approval Level', 'حذف مستوى الموافقة')),
        content: Text(
          tr(
            'Delete approval level for ${item['approval_document_type_name'] ?? 'this document type'}?',
            'هل تريد حذف مستوى الموافقة لـ ${item['approval_document_type_name'] ?? 'هذا النوع من المستندات'}؟',
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
          .delete('/approval-levels/${item['id']}');
      ref.invalidate(_approvalLevelsProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            tr(
              'Approval level deleted successfully',
              'تم حذف مستوى الموافقة بنجاح',
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

class _ApprovalLevelFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  final Map<String, dynamic> refs;

  const _ApprovalLevelFormSheet({this.item, required this.refs});

  @override
  ConsumerState<_ApprovalLevelFormSheet> createState() =>
      _ApprovalLevelFormSheetState();
}

class _ApprovalLevelFormSheetState
    extends ConsumerState<_ApprovalLevelFormSheet> {
  final _formKey = GlobalKey<FormState>();
  int? _approvalDocumentTypeId;
  int? _userGroupId;
  late final TextEditingController _descriptionController;
  late final TextEditingController _orderController;
  String? _action;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  List<Map<String, dynamic>> _maps(dynamic value) {
    if (value is! List) return const [];
    return value
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  int? _asInt(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    return int.tryParse(value.toString());
  }

  @override
  void initState() {
    super.initState();
    _approvalDocumentTypeId = _asInt(
      widget.item?['approval_document_types_id'],
    );
    _userGroupId = _asInt(widget.item?['user_group_id']);
    _descriptionController = TextEditingController(
      text: widget.item?['description']?.toString() ?? '',
    );
    _orderController = TextEditingController(
      text: widget.item?['order']?.toString() ?? '',
    );
    _action = widget.item?['action']?.toString();
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _orderController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final documentTypes = _maps(widget.refs['approval_document_types']);
    final userGroups = _maps(widget.refs['user_groups']);
    final actions = _maps(widget.refs['actions']);

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
                      ? tr('Edit Approval Level', 'تعديل مستوى الموافقة')
                      : tr(
                          'Create New Approval Level',
                          'إنشاء مستوى موافقة جديد',
                        ),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                DropdownButtonFormField<int>(
                  value:
                      documentTypes.any(
                        (item) => _asInt(item['id']) == _approvalDocumentTypeId,
                      )
                      ? _approvalDocumentTypeId
                      : null,
                  decoration: InputDecoration(
                    labelText: tr(
                      'Approval Document Type',
                      'نوع مستند الموافقة',
                    ),
                    border: const OutlineInputBorder(),
                  ),
                  items: documentTypes
                      .map(
                        (item) => DropdownMenuItem<int>(
                          value: _asInt(item['id']),
                          child: Text(item['name']?.toString() ?? '-'),
                        ),
                      )
                      .toList(),
                  onChanged: (value) =>
                      setState(() => _approvalDocumentTypeId = value),
                  validator: (value) => value == null
                      ? tr(
                          'Approval document type is required',
                          'نوع مستند الموافقة مطلوب',
                        )
                      : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value:
                      userGroups.any(
                        (item) => _asInt(item['id']) == _userGroupId,
                      )
                      ? _userGroupId
                      : null,
                  decoration: InputDecoration(
                    labelText: tr('User Group', 'مجموعة المستخدمين'),
                    border: const OutlineInputBorder(),
                  ),
                  items: userGroups
                      .map(
                        (item) => DropdownMenuItem<int>(
                          value: _asInt(item['id']),
                          child: Text(item['name']?.toString() ?? '-'),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _userGroupId = value),
                  validator: (value) => value == null
                      ? tr('User group is required', 'مجموعة المستخدمين مطلوبة')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  minLines: 3,
                  maxLines: 4,
                  decoration: InputDecoration(
                    labelText: tr('Description', 'الوصف'),
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _orderController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: tr('Order', 'الترتيب'),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) {
                      return tr('Order is required', 'الترتيب مطلوب');
                    }
                    if (int.tryParse(value.trim()) == null) {
                      return tr(
                        'Order must be a number',
                        'يجب أن يكون الترتيب رقماً',
                      );
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value:
                      actions.any(
                        (item) => item['value']?.toString() == _action,
                      )
                      ? _action
                      : null,
                  decoration: InputDecoration(
                    labelText: tr('Action', 'الإجراء'),
                    border: const OutlineInputBorder(),
                  ),
                  items: actions
                      .map(
                        (item) => DropdownMenuItem<String>(
                          value: item['value']?.toString(),
                          child: Text(item['label']?.toString() ?? '-'),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _action = value),
                  validator: (value) => (value == null || value.isEmpty)
                      ? tr('Action is required', 'الإجراء مطلوب')
                      : null,
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
                                    'Update Approval Level',
                                    'تحديث مستوى الموافقة',
                                  )
                                : tr(
                                    'Save Approval Level',
                                    'حفظ مستوى الموافقة',
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
    setState(() => _submitting = true);

    final payload = {
      'approval_document_types_id': _approvalDocumentTypeId,
      'user_group_id': _userGroupId,
      'description': _descriptionController.text.trim(),
      'order': int.parse(_orderController.text.trim()),
      'action': _action,
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/approval-levels/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/approval-levels', data: payload);
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
