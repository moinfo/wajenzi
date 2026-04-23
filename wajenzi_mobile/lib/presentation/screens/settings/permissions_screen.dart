import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _permissionsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/permissions');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _permissionReferenceProvider = FutureProvider.autoDispose<List<String>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/permissions/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final payload = data['data'] is Map<String, dynamic>
      ? data['data'] as Map<String, dynamic>
      : const <String, dynamic>{};
  return (payload['types'] as List? ?? const [])
      .map((item) => item.toString())
      .toList();
});

class PermissionsScreen extends ConsumerWidget {
  const PermissionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final asyncData = ref.watch(_permissionsProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(tr('Permissions', 'الصلاحيات')),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_permissionsProvider);
          ref.invalidate(_permissionReferenceProvider);
        },
        child: asyncData.when(
          loading: () => LoadingWidget(
            message: tr('Loading permissions...', 'جاري تحميل الصلاحيات...'),
          ),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              Text(
                tr('Failed to load permissions', 'تعذر تحميل الصلاحيات'),
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
                          Icons.key_outlined,
                          size: 56,
                          color: AppColors.primary,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          tr('No permissions found', 'لا توجد صلاحيات'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          tr(
                            'Create a permission to manage this setting from mobile.',
                            'أنشئ صلاحية لإدارة هذا الإعداد من التطبيق.',
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: Text(tr('New Permission', 'صلاحية جديدة')),
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
                          Icons.key_outlined,
                          color: AppColors.primary,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              tr('Permissions', 'الصلاحيات'),
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
                          '${index + 1}',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      title: Text(
                        item['name']?.toString() ?? '-',
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
                          children: [
                            Text(
                              tr(
                                'Type: ${item['permission_type'] ?? '-'}',
                                'النوع: ${item['permission_type'] ?? '-'}',
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              item['description']?.toString() ?? '',
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
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
        label: Text(tr('New Permission', 'صلاحية جديدة')),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.72,
        child: _PermissionFormSheet(item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_permissionsProvider);
      ref.invalidate(_permissionReferenceProvider);
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
        title: Text(tr('Delete Permission', 'حذف الصلاحية')),
        content: Text(
          tr('Delete ${item['name']}?', 'هل تريد حذف ${item['name']}؟'),
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
      await ref.read(apiClientProvider).delete('/permissions/${item['id']}');
      ref.invalidate(_permissionsProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            tr('Permission deleted successfully', 'تم حذف الصلاحية بنجاح'),
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

class _PermissionFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _PermissionFormSheet({this.item});

  @override
  ConsumerState<_PermissionFormSheet> createState() =>
      _PermissionFormSheetState();
}

class _PermissionFormSheetState extends ConsumerState<_PermissionFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _descriptionController;
  String _permissionType = 'MENU';
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.item?['description']?.toString() ?? '',
    );
    _permissionType = widget.item?['permission_type']?.toString() ?? 'MENU';
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final typesAsync = ref.watch(_permissionReferenceProvider);

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
                      ? tr('Edit Permission', 'تعديل الصلاحية')
                      : tr('Create New Permission', 'إنشاء صلاحية جديدة'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: tr('Permission Name', 'اسم الصلاحية'),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? tr('Permission name is required', 'اسم الصلاحية مطلوب')
                      : null,
                ),
                const SizedBox(height: 16),
                typesAsync.when(
                  loading: () => const LinearProgressIndicator(),
                  error: (_, _) => const SizedBox.shrink(),
                  data: (types) => DropdownButtonFormField<String>(
                    value: types.contains(_permissionType)
                        ? _permissionType
                        : (types.isNotEmpty ? types.first : 'MENU'),
                    decoration: InputDecoration(
                      labelText: tr('Permission Type', 'نوع الصلاحية'),
                      border: const OutlineInputBorder(),
                    ),
                    items: types
                        .map(
                          (type) => DropdownMenuItem<String>(
                            value: type,
                            child: Text(type),
                          ),
                        )
                        .toList(),
                    onChanged: (value) =>
                        setState(() => _permissionType = value ?? 'MENU'),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    labelText: tr('Description', 'الوصف'),
                    border: const OutlineInputBorder(),
                    alignLabelWithHint: true,
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
                                ? tr('Update Permission', 'تحديث الصلاحية')
                                : tr('Save Permission', 'حفظ الصلاحية')),
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

    final description = _descriptionController.text.trim();
    final payload = {
      'name': _nameController.text.trim(),
      'permission_type': _permissionType,
      'description': description.isEmpty ? null : description,
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/permissions/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/permissions', data: payload);
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
