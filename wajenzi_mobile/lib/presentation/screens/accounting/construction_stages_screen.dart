import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _constructionStagesSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _constructionStagesProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/construction-stages');
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

final _constructionStageRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/construction-stages/reference-data');
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

class ConstructionStagesScreen extends ConsumerStatefulWidget {
  const ConstructionStagesScreen({super.key});

  @override
  ConsumerState<ConstructionStagesScreen> createState() =>
      _ConstructionStagesScreenState();
}

class _ConstructionStagesScreenState
    extends ConsumerState<ConstructionStagesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final stagesAsync = ref.watch(_constructionStagesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref
        .watch(_constructionStagesSearchProvider)
        .trim()
        .toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Hatua za Ujenzi' : 'Construction Stages'),
      ),
      floatingActionButton: stagesAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: isSwahili ? 'Ongeza Hatua' : 'Add Stage',
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza Hatua' : 'Add Stage',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_constructionStagesProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref
                              .read(_constructionStagesSearchProvider.notifier)
                              .state =
                          value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta hatua ya ujenzi...'
                        : 'Search construction stages...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(
                                          _constructionStagesSearchProvider
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
              ),
            ),
            stagesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _StageErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_constructionStagesProvider),
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
                              Icons.layers_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Construction Stages haipatikani kwenye live API kwa sasa.'
                                  : 'Construction Stages is not available on the live API right now.',
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

                final stages = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final sorted = _sortStages(stages);
                final filteredStages = sorted.where((stage) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      stage['name'] ?? '',
                      stage['parent_name'] ?? '',
                      stage['description'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  return true;
                }).toList();

                if (filteredStages.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.layers_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            stages.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna hatua za ujenzi'
                                      : 'No construction stages found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () =>
                                  ref
                                          .read(
                                            _constructionStagesSearchProvider
                                                .notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa utafutaji' : 'Clear search',
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
                      final stage = filteredStages[index];
                      return _StageCard(
                        stage: stage,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () => _openForm(context, ref, stage: stage),
                        onDelete: () => _deleteStage(context, ref, stage),
                      );
                    }, childCount: filteredStages.length),
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
    Map<String, dynamic>? stage,
  }) async {
    final refs = await ref.read(_constructionStageRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _StageFormSheet(refs: refs, stage: stage),
    );
    if (result == true) ref.invalidate(_constructionStagesProvider);
  }

  Future<void> _deleteStage(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> stage,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(
          isSwahili ? 'Futa Hatua ya Ujenzi' : 'Delete Construction Stage',
        ),
        content: Text(
          isSwahili ? 'Futa "${stage['name']}"?' : 'Delete "${stage['name']}"?',
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
      await ref
          .read(apiClientProvider)
          .delete('/construction-stages/${stage['id']}');
      ref.invalidate(_constructionStagesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili ? 'Hatua imefutwa' : 'Construction stage deleted',
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

class _StageErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _StageErrorView({
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

class _StageCard extends StatelessWidget {
  final Map<String, dynamic> stage;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _StageCard({
    required this.stage,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final isChild = stage['parent_id'] != null;
    final childrenCount = stage['children_count'] ?? 0;

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
                isChild ? Icons.subdirectory_arrow_right : Icons.layers,
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
                          stage['name']?.toString() ?? '-',
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
                      if (stage['sort_order'] != null) ...[
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.info.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '#${stage['sort_order']}',
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: AppColors.info,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                  if (stage['parent_name'] != null) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(
                          Icons.account_tree,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            '${isSwahili ? 'Wazazi' : 'Parent'}: ${stage['parent_name']}',
                            style: TextStyle(
                              fontSize: 12,
                              color: isDarkMode
                                  ? Colors.white54
                                  : AppColors.textSecondary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                  if (stage['description']?.toString().isNotEmpty ?? false) ...[
                    const SizedBox(height: 4),
                    Text(
                      stage['description']?.toString() ?? '-',
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

class _StageFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? stage;

  const _StageFormSheet({required this.refs, this.stage});

  @override
  ConsumerState<_StageFormSheet> createState() => _StageFormSheetState();
}

class _StageFormSheetState extends ConsumerState<_StageFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _descriptionController;
  late final TextEditingController _sortOrderController;
  int? _parentId;
  bool _saving = false;

  bool get _isEdit => widget.stage != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.stage?['name']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.stage?['description']?.toString() ?? '',
    );
    _sortOrderController = TextEditingController(
      text: widget.stage?['sort_order']?.toString() ?? '0',
    );
    _parentId = _toNullableInt(widget.stage?['parent_id']);
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
    final parents = _toMaps(widget.refs['parent_construction_stages'])
        .where((item) => _toInt(item['id']) != _toInt(widget.stage?['id']))
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
                                ? 'Hariri Hatua ya Ujenzi'
                                : 'Edit Construction Stage')
                          : (isSwahili
                                ? 'Hatua Mpya ya Ujenzi'
                                : 'New Construction Stage'),
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
                        isSwahili ? 'Jina la Hatua *' : 'Stage Name *',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      isExpanded: true,
                      value: _parentId,
                      decoration: inputStyle(
                        isSwahili ? 'Wazazi' : 'Parent Stage',
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
                    TextFormField(
                      controller: _sortOrderController,
                      keyboardType: TextInputType.number,
                      decoration: inputStyle(
                        isSwahili ? 'Mpangilio (Oda)' : 'Sort Order',
                      ),
                      style: TextStyle(color: textColor),
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
      };

      if (_isEdit) {
        await api.put(
          '/construction-stages/${widget.stage!['id']}',
          data: data,
        );
      } else {
        await api.post('/construction-stages', data: data);
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

List<Map<String, dynamic>> _sortStages(List<Map<String, dynamic>> stages) {
  final parents = stages.where((stage) => stage['parent_id'] == null).toList()
    ..sort((a, b) {
      final aSort = _toInt(a['sort_order']);
      final bSort = _toInt(b['sort_order']);
      if (aSort != bSort) return aSort.compareTo(bSort);
      return (a['name']?.toString() ?? '').compareTo(
        b['name']?.toString() ?? '',
      );
    });

  final childrenByParent = <int, List<Map<String, dynamic>>>{};
  for (final stage in stages.where((item) => item['parent_id'] != null)) {
    final parentId = _toInt(stage['parent_id']);
    childrenByParent.putIfAbsent(parentId, () => []).add(stage);
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

  final orphans = stages
      .where(
        (stage) =>
            stage['parent_id'] != null &&
            !childrenByParent.containsKey(_toInt(stage['parent_id'])),
      )
      .toList();
  sorted.addAll(orphans);
  return sorted;
}
