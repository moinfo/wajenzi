import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _buildingTypesSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _buildingTypesStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _buildingTypesProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/building-types');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final items = data['data'] as List? ?? const [];
        return {
          'items': items
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return {
            'items': const <Map<String, dynamic>>[],
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _buildingTypeRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/building-types/reference-data');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        return data['data'] is Map
            ? Map<String, dynamic>.from(data['data'] as Map)
            : const <String, dynamic>{};
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return const <String, dynamic>{};
        }
        rethrow;
      }
    });

class BuildingTypesScreen extends ConsumerStatefulWidget {
  const BuildingTypesScreen({super.key});

  @override
  ConsumerState<BuildingTypesScreen> createState() =>
      _BuildingTypesScreenState();
}

class _BuildingTypesScreenState extends ConsumerState<BuildingTypesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final typesAsync = ref.watch(_buildingTypesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_buildingTypesSearchProvider).trim().toLowerCase();
    final statusFilter = ref.watch(_buildingTypesStatusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Aina za Jengo' : 'Building Types'),
      ),
      floatingActionButton: typesAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: isSwahili ? 'Ongeza Aina' : 'Add Type',
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza Aina' : 'Add Type',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_buildingTypesProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) =>
                          ref
                                  .read(_buildingTypesSearchProvider.notifier)
                                  .state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta aina ya jengo...'
                            : 'Search building types...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _buildingTypesSearchProvider
                                                  .notifier,
                                            )
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _StatusChip(
                            label: isSwahili ? 'Zote' : 'All',
                            isSelected: statusFilter == null,
                            onTap: () =>
                                ref
                                        .read(
                                          _buildingTypesStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null,
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'Active',
                            isSelected: statusFilter == 'active',
                            onTap: () =>
                                ref
                                        .read(
                                          _buildingTypesStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'active',
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'Inactive',
                            isSelected: statusFilter == 'inactive',
                            onTap: () =>
                                ref
                                        .read(
                                          _buildingTypesStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'inactive',
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            typesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _BuildingTypeErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_buildingTypesProvider),
                ),
              ),
              data: (payload) {
                if (payload['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.home_work_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Building Types haipatikani kwenye live API kwa sasa.'
                                  : 'Building Types is not available on the live API right now.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[700],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }

                final types = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final sorted = _sortBuildingTypes(types);
                final filteredTypes = sorted.where((type) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      type['name'] ?? '',
                      type['parent_name'] ?? '',
                      type['description'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  if (statusFilter != null) {
                    final isActive = type['is_active'] == true;
                    if (statusFilter == 'active' && !isActive) return false;
                    if (statusFilter == 'inactive' && isActive) return false;
                  }
                  return true;
                }).toList();

                if (filteredTypes.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.home_work_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            types.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna aina za jengo'
                                      : 'No building types found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty || statusFilter != null) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () {
                                ref
                                        .read(
                                          _buildingTypesSearchProvider.notifier,
                                        )
                                        .state =
                                    '';
                                ref
                                        .read(
                                          _buildingTypesStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null;
                              },
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa vichujio' : 'Clear filters',
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final type = filteredTypes[index];
                      return _BuildingTypeCard(
                        type: type,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () => _openForm(context, ref, type: type),
                        onDelete: () => _deleteType(context, ref, type),
                      );
                    }, childCount: filteredTypes.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? type,
  }) async {
    final refs = await ref.read(_buildingTypeRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _BuildingTypeFormSheet(refs: refs, type: type),
    );
    if (result == true) ref.invalidate(_buildingTypesProvider);
  }

  Future<void> _deleteType(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> type,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Aina ya Jengo' : 'Delete Building Type'),
        content: Text(
          isSwahili ? 'Futa "${type['name']}"?' : 'Delete "${type['name']}"?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(
              isSwahili ? 'Futa' : 'Delete',
              style: const TextStyle(color: AppColors.error),
            ),
          ),
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
            content: Text(
              isSwahili ? 'Aina imefutwa' : 'Building type deleted',
            ),
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
}

class _StatusChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final bool isDarkMode;

  const _StatusChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.primary.withValues(alpha: 0.15)
              : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.white),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected
                ? AppColors.primary
                : Colors.grey.withValues(alpha: 0.3),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
            color: isSelected
                ? AppColors.primary
                : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
          ),
        ),
      ),
    );
  }
}

class _BuildingTypeCard extends StatelessWidget {
  final Map<String, dynamic> type;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _BuildingTypeCard({
    required this.type,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final isChild = type['parent_id'] != null;
    final isActive = type['is_active'] == true;
    final childrenCount = type['children_count'] ?? 0;

    return Card(
      margin: EdgeInsets.only(left: isChild ? 20 : 0, bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: (isChild ? AppColors.info : AppColors.primary)
                    .withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                isChild ? Icons.subdirectory_arrow_right : Icons.home_work,
                color: isChild ? AppColors.info : AppColors.primary,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          type['name']?.toString() ?? '-',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: isActive
                              ? AppColors.success.withValues(alpha: 0.12)
                              : Colors.grey.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          isActive ? 'Active' : 'Inactive',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: isActive
                                ? AppColors.success
                                : AppColors.textSecondary,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  if (type['parent_name'] != null)
                    Text(
                      '${isSwahili ? 'Wazazi' : 'Parent'}: ${type['parent_name']}',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  if (type['description']?.toString().isNotEmpty ?? false) ...[
                    const SizedBox(height: 4),
                    Text(
                      type['description']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 11,
                        color: isDarkMode ? Colors.white38 : AppColors.textHint,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                  if (childrenCount > 0) ...[
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Icon(
                          Icons.list,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '$childrenCount ${isSwahili ? 'watoto' : 'children'}',
                          style: TextStyle(
                            fontSize: 11,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
            PopupMenuButton<String>(
              onSelected: (value) {
                if (value == 'edit') {
                  onEdit();
                } else if (value == 'delete') {
                  onDelete();
                }
              },
              itemBuilder: (_) => [
                PopupMenuItem(
                  value: 'edit',
                  child: Row(
                    children: [
                      const Icon(Icons.edit_rounded, size: 20),
                      const SizedBox(width: 8),
                      Text(isSwahili ? 'Hariri' : 'Edit'),
                    ],
                  ),
                ),
                PopupMenuItem(
                  value: 'delete',
                  child: Row(
                    children: [
                      const Icon(
                        Icons.delete_rounded,
                        size: 20,
                        color: AppColors.error,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        isSwahili ? 'Futa' : 'Delete',
                        style: const TextStyle(color: AppColors.error),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _BuildingTypeFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? type;

  const _BuildingTypeFormSheet({required this.refs, this.type});

  @override
  ConsumerState<_BuildingTypeFormSheet> createState() =>
      _BuildingTypeFormSheetState();
}

class _BuildingTypeFormSheetState
    extends ConsumerState<_BuildingTypeFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _descriptionController;
  late final TextEditingController _sortOrderController;
  int? _parentId;
  bool _isActive = true;
  bool _saving = false;

  bool get _isEdit => widget.type != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.type?['name']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.type?['description']?.toString() ?? '',
    );
    _sortOrderController = TextEditingController(
      text: widget.type?['sort_order']?.toString() ?? '0',
    );
    _parentId = _toNullableInt(widget.type?['parent_id']);
    _isActive =
        widget.type?['is_active'] == null || widget.type!['is_active'] == true;
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

    final bgColor = isDarkMode ? const Color(0xFF1A1A2E) : Colors.white;
    final inputBg = isDarkMode ? const Color(0xFF0F1923) : Colors.grey[100];
    final textColor = isDarkMode ? Colors.white : AppColors.textPrimary;

    InputDecoration inputStyle(String label) => InputDecoration(
      labelText: label,
      filled: true,
      fillColor: inputBg,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
    );

    return Container(
      height: 0.85 * MediaQuery.of(context).size.height,
      decoration: BoxDecoration(
        color: bgColor,
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
              child: Form(
                key: _formKey,
                child: ListView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    MediaQuery.of(context).viewInsets.bottom + 24,
                  ),
                  children: [
                    Text(
                      _isEdit
                          ? (isSwahili
                                ? 'Hariri Aina ya Jengo'
                                : 'Edit Building Type')
                          : (isSwahili
                                ? 'Aina Mpya ya Jengo'
                                : 'New Building Type'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: _nameController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili
                                ? 'Jina linahitajika'
                                : 'Name is required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Jina la Aina *' : 'Type Name *',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      isExpanded: true,
                      value: _parentId,
                      decoration: inputStyle(
                        isSwahili ? 'Aina ya Wazazi' : 'Parent Type',
                      ),
                      dropdownColor: bgColor,
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(
                            isSwahili
                                ? '-- Hakuna Wazazi (Kiwango cha Juu) --'
                                : '-- No Parent (Top Level) --',
                            style: TextStyle(color: textColor),
                          ),
                        ),
                        ...parents.map(
                          (item) => DropdownMenuItem<int?>(
                            value: _toInt(item['id']),
                            child: Text(
                              item['name']?.toString() ?? '-',
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(color: textColor),
                            ),
                          ),
                        ),
                      ],
                      onChanged: (value) => setState(() => _parentId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _descriptionController,
                      maxLines: 3,
                      decoration: inputStyle(
                        isSwahili ? 'Maelezo' : 'Description',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _sortOrderController,
                            keyboardType: TextInputType.number,
                            decoration: inputStyle(
                              isSwahili ? 'Mpangilio wa Kut排列' : 'Sort Order',
                            ),
                            style: TextStyle(color: textColor),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: DropdownButtonFormField<bool>(
                            value: _isActive,
                            decoration: inputStyle(
                              isSwahili ? 'Hali *' : 'Status *',
                            ),
                            dropdownColor: bgColor,
                            items: [
                              DropdownMenuItem(
                                value: true,
                                child: Text(
                                  'Active',
                                  style: TextStyle(color: textColor),
                                ),
                              ),
                              DropdownMenuItem(
                                value: false,
                                child: Text(
                                  'Inactive',
                                  style: TextStyle(color: textColor),
                                ),
                              ),
                            ],
                            onChanged: (value) =>
                                setState(() => _isActive = value ?? true),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _saving ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              _isEdit
                                  ? (isSwahili ? 'Sasisha' : 'Update')
                                  : (isSwahili ? 'Hifadhi' : 'Save'),
                            ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
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
        'description': _descriptionController.text.trim().isEmpty
            ? null
            : _descriptionController.text.trim(),
        'sort_order': int.tryParse(_sortOrderController.text.trim()) ?? 0,
        'is_active': _isActive,
      };

      if (_isEdit) {
        await api.put('/building-types/${widget.type!['id']}', data: data);
      } else {
        await api.post('/building-types', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider)),
            ),
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
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ],
      ),
    );
  }
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}

List<Map<String, dynamic>> _sortBuildingTypes(
  List<Map<String, dynamic>> types,
) {
  final parents = types.where((type) => type['parent_id'] == null).toList()
    ..sort((a, b) {
      final aSort = _toInt(a['sort_order']);
      final bSort = _toInt(b['sort_order']);
      if (aSort != bSort) return aSort.compareTo(bSort);
      return (a['name']?.toString() ?? '').compareTo(
        b['name']?.toString() ?? '',
      );
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
      return (a['name']?.toString() ?? '').compareTo(
        b['name']?.toString() ?? '',
      );
    });
  }

  final sorted = <Map<String, dynamic>>[];
  for (final parent in parents) {
    sorted.add(parent);
    sorted.addAll(childrenByParent[_toInt(parent['id'])] ?? const []);
  }

  final orphans = types
      .where(
        (type) =>
            type['parent_id'] != null &&
            !childrenByParent.containsKey(_toInt(type['parent_id'])),
      )
      .toList();
  sorted.addAll(orphans);
  return sorted;
}
