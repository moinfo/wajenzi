import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _buildingTypesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/building-types');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _buildingTypeRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/building-types/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class BuildingTypesScreen extends ConsumerWidget {
  const BuildingTypesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final typesAsync = ref.watch(_buildingTypesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Building Types' : 'Building Types'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_buildingTypesProvider),
        child: typesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _BuildingTypeErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_buildingTypesProvider),
          ),
          data: (types) {
            if (types.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.home_work_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna building types' : 'No building types found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            final sorted = _sortBuildingTypes(types);
            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: sorted.length + 1,
              itemBuilder: (context, index) {
                if (index == sorted.length) return const SizedBox(height: 80);
                final type = sorted[index];
                final isChild = type['parent_id'] != null;
                return Card(
                  margin: EdgeInsets.only(left: isChild ? 20 : 0, bottom: 12),
                  child: ListTile(
                    isThreeLine: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: (isChild ? AppColors.info : AppColors.primary).withValues(alpha: 0.12),
                      child: Icon(isChild ? Icons.subdirectory_arrow_right : Icons.home_work, color: isChild ? AppColors.info : AppColors.primary),
                    ),
                    title: Text(
                      type['name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${type['parent_name'] ?? 'Top Level'}\n${type['description'] ?? '-'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDetails(context, type, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, type: type);
                        } else if (value == 'delete') {
                          _deleteType(context, ref, type);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
                      ],
                    ),
                    onTap: () => _showDetails(context, type, isDarkMode),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? type}) async {
    final refs = await ref.read(_buildingTypeRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.86,
        child: _BuildingTypeFormSheet(refs: refs, type: type),
      ),
    );
    if (result == true) ref.invalidate(_buildingTypesProvider);
  }

  Future<void> _deleteType(BuildContext context, WidgetRef ref, Map<String, dynamic> type) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Building Type' : 'Delete Building Type'),
        content: Text(isSwahili ? 'Je, unataka kufuta ${type['name']}?' : 'Delete ${type['name']}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/building-types/${type['id']}');
      ref.invalidate(_buildingTypesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Building type imefutwa' : 'Building type deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(BuildContext context, Map<String, dynamic> type, bool isDarkMode) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.58,
        child: Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            top: false,
            child: Column(
              children: [
                const SizedBox(height: 12),
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                    children: [
                      Text(
                        type['name']?.toString() ?? '-',
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 16),
                      _detailLine('Parent', type['parent_name'] ?? 'Top Level'),
                      _detailLine('Description', type['description']),
                      _detailLine('Sort Order', type['sort_order']),
                      _detailLine('Status', (type['is_active'] == true) ? 'Active' : 'Inactive'),
                      _detailLine('Children', type['children_count']),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _detailLine(String label, dynamic value) {
    final text = (value ?? '').toString().trim();
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(fontSize: 13, color: AppColors.textPrimary),
          children: [
            TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w700)),
            TextSpan(text: text.isEmpty ? '-' : text),
          ],
        ),
      ),
    );
  }
}

class _BuildingTypeFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? type;

  const _BuildingTypeFormSheet({
    required this.refs,
    this.type,
  });

  @override
  ConsumerState<_BuildingTypeFormSheet> createState() => _BuildingTypeFormSheetState();
}

class _BuildingTypeFormSheetState extends ConsumerState<_BuildingTypeFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController = TextEditingController(text: widget.type?['name']?.toString() ?? '');
  late final TextEditingController _descriptionController = TextEditingController(text: widget.type?['description']?.toString() ?? '');
  late final TextEditingController _sortOrderController = TextEditingController(text: widget.type?['sort_order']?.toString() ?? '0');
  int? _parentId;
  bool _isActive = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _parentId = _toNullableInt(widget.type?['parent_id']);
    _isActive = widget.type?['is_active'] == null ? true : widget.type!['is_active'] == true;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    _sortOrderController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final parents = _toMaps(widget.refs['parent_building_types'])
        .where((item) => _toInt(item['id']) != _toInt(widget.type?['id']))
        .toList();

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Column(
          children: [
            const SizedBox(height: 12),
            Container(
              width: 44,
              height: 5,
              decoration: BoxDecoration(
                color: isDarkMode ? Colors.white24 : Colors.black12,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            Expanded(
              child: ListView(
                padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.of(context).viewInsets.bottom + 24),
                children: [
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          widget.type == null ? 'New Building Type' : 'Edit Building Type',
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _input(_nameController, isSwahili ? 'Building Type Name *' : 'Building Type Name *', isDarkMode),
                        const SizedBox(height: 12),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Parent Building Type' : 'Parent Building Type',
                          items: [
                            {'id': 0, 'name': '-- No Parent (Top Level) --'},
                            ...parents.map((e) => {'id': e['id'], 'name': e['name']}),
                          ],
                          value: _parentId ?? 0,
                          onChanged: (value) => setState(() => _parentId = (value == null || value == 0) ? null : value),
                          required: false,
                        ),
                        const SizedBox(height: 12),
                        _input(_descriptionController, isSwahili ? 'Description' : 'Description', isDarkMode, required: false, maxLines: 4),
                        const SizedBox(height: 12),
                        _input(_sortOrderController, isSwahili ? 'Sort Order' : 'Sort Order', isDarkMode, required: false, keyboardType: TextInputType.number),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<bool>(
                          value: _isActive,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Status *' : 'Status *',
                            filled: true,
                            fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
                          ),
                          items: const [
                            DropdownMenuItem(value: true, child: Text('Active')),
                            DropdownMenuItem(value: false, child: Text('Inactive')),
                          ],
                          onChanged: (value) => setState(() => _isActive = value ?? true),
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.type == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _input(
    TextEditingController controller,
    String label,
    bool isDarkMode, {
    bool required = true,
    int maxLines = 1,
    TextInputType? keyboardType,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      validator: required ? (value) => value == null || value.trim().isEmpty ? 'Required' : null : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dropdown({
    required bool isDarkMode,
    required String label,
    required List<Map<String, dynamic>> items,
    required int? value,
    required ValueChanged<int?> onChanged,
    bool required = true,
  }) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: items.any((item) => _toInt(item['id']) == value) ? value : null,
      validator: required ? (selected) => selected == null ? 'Required' : null : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map((item) => DropdownMenuItem<int>(
                value: _toInt(item['id']),
                child: Text(item['name']?.toString() ?? '-', overflow: TextOverflow.ellipsis),
              ))
          .toList(),
      onChanged: onChanged,
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'name': _nameController.text.trim(),
        'parent_id': _parentId,
        'description': _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
        'sort_order': int.tryParse(_sortOrderController.text.trim()) ?? 0,
        'is_active': _isActive,
      };

      if (widget.type == null) {
        await api.post('/building-types', data: data);
      } else {
        await api.put('/building-types/${widget.type!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider))),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _BuildingTypeErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _BuildingTypeErrorView({
    required this.message,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(isSwahili ? 'Hitilafu imetokea' : 'Something went wrong', textAlign: TextAlign.center),
        const SizedBox(height: 8),
        Text(message, textAlign: TextAlign.center),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}

List<Map<String, dynamic>> _sortBuildingTypes(List<Map<String, dynamic>> types) {
  final parents = types.where((type) => type['parent_id'] == null).toList()
    ..sort((a, b) {
      final aSort = _toInt(a['sort_order']);
      final bSort = _toInt(b['sort_order']);
      if (aSort != bSort) return aSort.compareTo(bSort);
      return (a['name']?.toString() ?? '').compareTo(b['name']?.toString() ?? '');
    });

  final childrenByParent = <int, List<Map<String, dynamic>>>{};
  for (final type in types.where((item) => item['parent_id'] != null)) {
    final parentId = _toInt(type['parent_id']);
    childrenByParent.putIfAbsent(parentId, () => []).add(type);
  }

  for (final children in childrenByParent.values) {
    children.sort((a, b) {
      final aSort = _toInt(a['sort_order']);
      final bSort = _toInt(b['sort_order']);
      if (aSort != bSort) return aSort.compareTo(bSort);
      return (a['name']?.toString() ?? '').compareTo(b['name']?.toString() ?? '');
    });
  }

  final sorted = <Map<String, dynamic>>[];
  for (final parent in parents) {
    sorted.add(parent);
    sorted.addAll(childrenByParent[_toInt(parent['id'])] ?? const []);
  }

  final orphans = types.where((type) => type['parent_id'] != null && !childrenByParent.containsKey(_toInt(type['parent_id']))).toList();
  sorted.addAll(orphans);
  return sorted;
}
