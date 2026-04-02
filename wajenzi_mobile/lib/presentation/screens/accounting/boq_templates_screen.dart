import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _boqTemplatesSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _boqTemplatesStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _boqTemplatesProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/boq-templates');
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

final _boqTemplateRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/boq-templates/reference-data');
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

class BoqTemplatesScreen extends ConsumerStatefulWidget {
  const BoqTemplatesScreen({super.key});

  @override
  ConsumerState<BoqTemplatesScreen> createState() => _BoqTemplatesScreenState();
}

class _BoqTemplatesScreenState extends ConsumerState<BoqTemplatesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final templatesAsync = ref.watch(_boqTemplatesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_boqTemplatesSearchProvider).trim().toLowerCase();
    final statusFilter = ref.watch(_boqTemplatesStatusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'BOQ Templates' : 'BOQ Templates'),
      ),
      floatingActionButton: templatesAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: isSwahili ? 'Add Template' : 'Add Template',
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Add Template' : 'Add Template',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_boqTemplatesProvider),
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
                          ref.read(_boqTemplatesSearchProvider.notifier).state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta templates...'
                            : 'Search templates...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _boqTemplatesSearchProvider
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
                                          _boqTemplatesStatusFilterProvider
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
                                          _boqTemplatesStatusFilterProvider
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
                                          _boqTemplatesStatusFilterProvider
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
            templatesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _BoqTemplateErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_boqTemplatesProvider),
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
                              Icons.description_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'BOQ Templates haipatikani kwenye live API kwa sasa.'
                                  : 'BOQ Templates is not available on the live API right now.',
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

                final templates = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final filteredTemplates = templates.where((template) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      template['name'] ?? '',
                      template['building_type_name'] ?? '',
                      template['building_type_parent_name'] ?? '',
                      template['description'] ?? '',
                      template['creator_name'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  if (statusFilter != null) {
                    final isActive = template['is_active'] == true;
                    if (statusFilter == 'active' && !isActive) return false;
                    if (statusFilter == 'inactive' && isActive) return false;
                  }
                  return true;
                }).toList();

                if (filteredTemplates.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.description_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            templates.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna BOQ templates'
                                      : 'No BOQ templates found')
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
                                          _boqTemplatesSearchProvider.notifier,
                                        )
                                        .state =
                                    '';
                                ref
                                        .read(
                                          _boqTemplatesStatusFilterProvider
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
                      final template = filteredTemplates[index];
                      return _BoqTemplateCard(
                        template: template,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () =>
                            _openForm(context, ref, template: template),
                        onDelete: () => _deleteTemplate(context, ref, template),
                        onTap: () => _showDetails(
                          context,
                          template,
                          isDarkMode,
                          isSwahili,
                        ),
                      );
                    }, childCount: filteredTemplates.length),
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
    Map<String, dynamic>? template,
  }) async {
    final refs = await ref.read(_boqTemplateRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _BoqTemplateFormSheet(refs: refs, template: template),
    );
    if (result == true) ref.invalidate(_boqTemplatesProvider);
  }

  Future<void> _deleteTemplate(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> template,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa BOQ Template' : 'Delete BOQ Template'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta "${template['name']}"?'
              : 'Delete "${template['name']}"?',
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
          .delete('/boq-templates/${template['id']}');
      ref.invalidate(_boqTemplatesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Template imefutwa' : 'Template deleted'),
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

  void _showDetails(
    BuildContext context,
    Map<String, dynamic> template,
    bool isDarkMode,
    bool isSwahili,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.74,
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
                        template['name']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 16),
                      _detailLine(
                        'Building Type',
                        _buildingTypeLabel(template),
                        isDarkMode,
                      ),
                      _detailLine(
                        'Roof Type',
                        _prettyEnum(template['roof_type']),
                        isDarkMode,
                      ),
                      _detailLine('Rooms', template['no_of_rooms'], isDarkMode),
                      _detailLine(
                        'Square Metre',
                        _measurement(template['square_metre'], 'SQM'),
                        isDarkMode,
                      ),
                      _detailLine(
                        'Run Metre',
                        _measurement(template['run_metre'], 'RM'),
                        isDarkMode,
                      ),
                      _detailLine(
                        'Description',
                        template['description'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Status',
                        template['is_active'] == true ? 'Active' : 'Inactive',
                        isDarkMode,
                      ),
                      _detailLine(
                        'Created By',
                        template['creator_name'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Stages',
                        template['stages_count'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Activities',
                        template['activities_count'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Sub Activities',
                        template['sub_activities_count'],
                        isDarkMode,
                      ),
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

  Widget _detailLine(String label, dynamic value, bool isDarkMode) {
    final text = (value ?? '').toString().trim();
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: RichText(
        text: TextSpan(
          style: TextStyle(
            fontSize: 13,
            color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
          ),
          children: [
            TextSpan(
              text: '$label: ',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            TextSpan(text: text.isEmpty ? '-' : text),
          ],
        ),
      ),
    );
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

class _BoqTemplateErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _BoqTemplateErrorView({
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

class _BoqTemplateCard extends StatelessWidget {
  final Map<String, dynamic> template;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _BoqTemplateCard({
    required this.template,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isActive = template['is_active'] == true;
    final buildingType = _buildingTypeLabel(template);
    final specs = <String>[];
    final roof = _prettyEnum(template['roof_type']);
    final rooms = template['no_of_rooms']?.toString();
    if (roof != '-') specs.add(roof);
    if (rooms != null && rooms.isNotEmpty) specs.add('$rooms rooms');

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '${index + 1}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
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
                            template['name']?.toString() ?? '-',
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
                    if (buildingType != 'No building type') ...[
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.blue.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          buildingType,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                            color: Colors.blue,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(height: 4),
                    ],
                    if (specs.isNotEmpty) ...[
                      Wrap(
                        spacing: 4,
                        children: specs
                            .map(
                              (spec) => Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 6,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: isDarkMode
                                      ? Colors.white.withValues(alpha: 0.08)
                                      : Colors.grey.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  spec,
                                  style: TextStyle(
                                    fontSize: 10,
                                    color: isDarkMode
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            )
                            .toList(),
                      ),
                      const SizedBox(height: 4),
                    ],
                    Row(
                      children: [
                        Icon(
                          Icons.layers,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${template['stages_count'] ?? 0} ${isSwahili ? 'stages' : 'stages'} • ${template['activities_count'] ?? 0} ${isSwahili ? 'activities' : 'activities'}',
                          style: TextStyle(
                            fontSize: 11,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                        ),
                      ],
                    ),
                    if (template['description']?.toString().isNotEmpty ??
                        false) ...[
                      const SizedBox(height: 4),
                      Text(
                        template['description']?.toString() ?? '-',
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'view') {
                    onTap();
                  } else if (value == 'edit') {
                    onEdit();
                  } else if (value == 'delete') {
                    onDelete();
                  }
                },
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'view',
                    child: Row(
                      children: [
                        const Icon(Icons.visibility_rounded, size: 20),
                        const SizedBox(width: 8),
                        Text(isSwahili ? 'Tazama' : 'View'),
                      ],
                    ),
                  ),
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
      ),
    );
  }
}

class _BoqTemplateFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? template;

  const _BoqTemplateFormSheet({required this.refs, this.template});

  @override
  ConsumerState<_BoqTemplateFormSheet> createState() =>
      _BoqTemplateFormSheetState();
}

class _BoqTemplateFormSheetState extends ConsumerState<_BoqTemplateFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _squareMetreController;
  late final TextEditingController _runMetreController;
  late final TextEditingController _descriptionController;
  int? _buildingTypeId;
  String? _roofType;
  String? _rooms;
  bool _isActive = true;
  bool _saving = false;

  bool get _isEdit => widget.template != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.template?['name']?.toString() ?? '',
    );
    _squareMetreController = TextEditingController(
      text: widget.template?['square_metre']?.toString() ?? '',
    );
    _runMetreController = TextEditingController(
      text: widget.template?['run_metre']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.template?['description']?.toString() ?? '',
    );
    _buildingTypeId = _toNullableInt(widget.template?['building_type_id']);
    _roofType = _nullableString(widget.template?['roof_type']);
    _rooms = _nullableString(widget.template?['no_of_rooms']);
    _isActive = widget.template?['is_active'] != false;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _squareMetreController.dispose();
    _runMetreController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final buildingTypes = _toMaps(widget.refs['building_types']);
    final roofTypes = _toNameList(widget.refs['roof_types']);
    final roomOptions = _toNameList(widget.refs['room_options']);

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
      height: 0.92 * MediaQuery.of(context).size.height,
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
                          ? (isSwahili ? 'Hariri Template' : 'Edit Template')
                          : (isSwahili ? 'Template Mpya' : 'New Template'),
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
                        isSwahili ? 'Template Name *' : 'Template Name *',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      isExpanded: true,
                      value:
                          buildingTypes.any(
                            (item) => _toInt(item['id']) == _buildingTypeId,
                          )
                          ? _buildingTypeId
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Building Type' : 'Building Type',
                      ),
                      dropdownColor: bgColor,
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(
                            isSwahili
                                ? 'Hakuna building type'
                                : 'No building type',
                            style: TextStyle(color: textColor),
                          ),
                        ),
                        ...buildingTypes.map(
                          (item) => DropdownMenuItem<int?>(
                            value: _toInt(item['id']),
                            child: Text(
                              _buildingTypeRefLabel(item),
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(color: textColor),
                            ),
                          ),
                        ),
                      ],
                      onChanged: (value) =>
                          setState(() => _buildingTypeId = value),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String?>(
                            value: _roofType,
                            decoration: inputStyle(
                              isSwahili ? 'Roof Type' : 'Roof Type',
                            ),
                            dropdownColor: bgColor,
                            items: [
                              DropdownMenuItem<String?>(
                                value: null,
                                child: Text(
                                  isSwahili ? 'Not set' : 'Not set',
                                  style: TextStyle(color: textColor),
                                ),
                              ),
                              ...roofTypes.map(
                                (item) => DropdownMenuItem<String?>(
                                  value: item,
                                  child: Text(
                                    _prettyEnum(item),
                                    style: TextStyle(color: textColor),
                                  ),
                                ),
                              ),
                            ],
                            onChanged: (value) =>
                                setState(() => _roofType = value),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: DropdownButtonFormField<String?>(
                            value: _rooms,
                            decoration: inputStyle(
                              isSwahili ? 'Rooms' : 'Rooms',
                            ),
                            dropdownColor: bgColor,
                            items: [
                              DropdownMenuItem<String?>(
                                value: null,
                                child: Text(
                                  isSwahili ? 'Not set' : 'Not set',
                                  style: TextStyle(color: textColor),
                                ),
                              ),
                              ...roomOptions.map(
                                (item) => DropdownMenuItem<String?>(
                                  value: item,
                                  child: Text(
                                    item,
                                    style: TextStyle(color: textColor),
                                  ),
                                ),
                              ),
                            ],
                            onChanged: (value) =>
                                setState(() => _rooms = value),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _squareMetreController,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            decoration: inputStyle(
                              isSwahili
                                  ? 'Square Metre (SQM)'
                                  : 'Square Metre (SQM)',
                            ),
                            style: TextStyle(color: textColor),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _runMetreController,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            decoration: inputStyle(
                              isSwahili ? 'Run Metre (RM)' : 'Run Metre (RM)',
                            ),
                            style: TextStyle(color: textColor),
                          ),
                        ),
                      ],
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
                    const SizedBox(height: 8),
                    SwitchListTile(
                      value: _isActive,
                      onChanged: (value) => setState(() => _isActive = value),
                      title: Text(
                        isSwahili ? 'Active' : 'Active',
                        style: TextStyle(color: textColor, fontSize: 14),
                      ),
                      contentPadding: EdgeInsets.zero,
                    ),
                    if (!_isEdit) ...[
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.blueGrey.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          isSwahili
                              ? "Baada ya kuunda template, unaweza kusanidi stages, activities, na sub-activities kutoka kwa web template builder."
                              : "After creating the template, you can configure stages, activities, and sub-activities from the web template builder.",
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white70
                                : AppColors.textSecondary,
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 16),
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
      final squareMetreRaw = _squareMetreController.text.trim();
      final runMetreRaw = _runMetreController.text.trim();
      final data = {
        'name': _nameController.text.trim(),
        'building_type_id': _buildingTypeId,
        'roof_type': _roofType,
        'no_of_rooms': _rooms,
        'square_metre': squareMetreRaw.isEmpty
            ? null
            : double.tryParse(squareMetreRaw),
        'run_metre': runMetreRaw.isEmpty ? null : double.tryParse(runMetreRaw),
        'description': _descriptionController.text.trim().isEmpty
            ? null
            : _descriptionController.text.trim(),
        'is_active': _isActive,
      };

      if (_isEdit) {
        await api.put('/boq-templates/${widget.template!['id']}', data: data);
      } else {
        await api.post('/boq-templates', data: data);
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

String _buildingTypeLabel(Map<String, dynamic> template) {
  final name = _nullableString(template['building_type_name']);
  final parent = _nullableString(template['building_type_parent_name']);
  if (name == null) return 'No building type';
  if (parent == null) return name;
  return '$parent > $name';
}

String _buildingTypeRefLabel(Map<String, dynamic> item) {
  final name = item['name']?.toString().trim() ?? '-';
  final parent = _nullableString(item['parent_name']);
  if (parent == null) return name;
  return '$parent > $name';
}

String _prettyEnum(dynamic value) {
  final text = value?.toString().trim();
  if (text == null || text.isEmpty) return '-';
  return text
      .replaceAll('_', ' ')
      .split(' ')
      .map((word) {
        if (word.isEmpty) return word;
        return word[0].toUpperCase() + word.substring(1);
      })
      .join(' ');
}

String _measurement(dynamic value, String suffix) {
  final number = value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '');
  if (number == null) return '-';
  return '${number.toStringAsFixed(2)} $suffix';
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

List<String> _toNameList(dynamic value) {
  return _toMaps(value)
      .map((item) => item['name']?.toString() ?? '')
      .where((item) => item.isNotEmpty)
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

String? _nullableString(dynamic value) {
  final text = value?.toString().trim();
  return text == null || text.isEmpty ? null : text;
}
