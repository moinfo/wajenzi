import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _subActivitiesSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _subActivitiesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/settings-sub-activities');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _subActivityRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/settings-sub-activities/reference-data');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

class SettingsSubActivitiesScreen extends ConsumerStatefulWidget {
  const SettingsSubActivitiesScreen({super.key});

  @override
  ConsumerState<SettingsSubActivitiesScreen> createState() =>
      _SettingsSubActivitiesScreenState();
}

class _SettingsSubActivitiesScreenState
    extends ConsumerState<SettingsSubActivitiesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final subActivitiesAsync = ref.watch(_subActivitiesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_subActivitiesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Sub-Activities' : 'Sub-Activities'),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openForm(context, ref),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Add Sub-Activity' : 'Add Sub-Activity',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_subActivitiesProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_subActivitiesSearchProvider.notifier).state =
                          value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta sub-activities...'
                        : 'Search sub-activities...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(
                                          _subActivitiesSearchProvider.notifier,
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
            subActivitiesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _SubActivityErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_subActivitiesProvider),
                ),
              ),
              data: (subActivities) {
                final filteredSubActivities = subActivities.where((sub) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      sub['name'] ?? '',
                      sub['activity_name'] ?? '',
                      sub['construction_stage_name'] ?? '',
                      sub['description'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  return true;
                }).toList();

                if (filteredSubActivities.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.extension_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            subActivities.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna sub-activities'
                                      : 'No sub-activities found')
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
                                            _subActivitiesSearchProvider
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
                      final subActivity = filteredSubActivities[index];
                      return _SubActivityCard(
                        subActivity: subActivity,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () =>
                            _openForm(context, ref, subActivity: subActivity),
                        onDelete: () =>
                            _deleteSubActivity(context, ref, subActivity),
                        onTap: () => _showDetails(
                          context,
                          subActivity,
                          isDarkMode,
                          isSwahili,
                        ),
                      );
                    }, childCount: filteredSubActivities.length),
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
    Map<String, dynamic>? subActivity,
  }) async {
    final refs = await ref.read(_subActivityRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) =>
          _SubActivityFormSheet(refs: refs, subActivity: subActivity),
    );
    if (result == true) ref.invalidate(_subActivitiesProvider);
  }

  Future<void> _deleteSubActivity(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> subActivity,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Sub-Activity' : 'Delete Sub-Activity'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta "${subActivity['name']}"?'
              : 'Delete "${subActivity['name']}"?',
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
          .delete('/settings-sub-activities/${subActivity['id']}');
      ref.invalidate(_subActivitiesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili ? 'Sub-activity imefutwa' : 'Sub-activity deleted',
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

  void _showDetails(
    BuildContext context,
    Map<String, dynamic> subActivity,
    bool isDarkMode,
    bool isSwahili,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.72,
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
                        subActivity['name']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 16),
                      _detailLine(
                        'Activity',
                        subActivity['activity_name'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Construction Stage',
                        subActivity['construction_stage_name'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Description',
                        subActivity['description'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Duration',
                        '${subActivity['estimated_duration_hours'] ?? '-'} ${subActivity['duration_unit'] ?? ''}',
                        isDarkMode,
                      ),
                      _detailLine(
                        'Labor Requirement',
                        subActivity['labor_requirement'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Skill Level',
                        subActivity['skill_level']?.toString().replaceAll(
                              '_',
                              ' ',
                            ) ??
                            '-',
                        isDarkMode,
                      ),
                      _detailLine(
                        'Parallel',
                        subActivity['can_run_parallel'] == true ? 'Yes' : 'No',
                        isDarkMode,
                      ),
                      _detailLine(
                        'Weather Dependent',
                        subActivity['weather_dependent'] == true ? 'Yes' : 'No',
                        isDarkMode,
                      ),
                      _detailLine(
                        'Sort Order',
                        subActivity['sort_order'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Materials Count',
                        subActivity['materials_count'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'BOQ Items Count',
                        subActivity['boq_items_count'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Template Usage Count',
                        subActivity['template_sub_activities_count'],
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

class _SubActivityErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SubActivityErrorView({
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

class _SubActivityCard extends StatelessWidget {
  final Map<String, dynamic> subActivity;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _SubActivityCard({
    required this.subActivity,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
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
                  color: AppColors.secondary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '${index + 1}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppColors.secondary,
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
                            subActivity['name']?.toString() ?? '-',
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
                        if (subActivity['sort_order'] != null) ...[
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
                              '#${subActivity['sort_order']}',
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
                    const SizedBox(height: 4),
                    Row(
                      children: [
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
                            subActivity['activity_name']?.toString() ?? '-',
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w500,
                              color: Colors.blue,
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (subActivity['construction_stage_name'] != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        subActivity['construction_stage_name']?.toString() ??
                            '-',
                        style: TextStyle(
                          fontSize: 11,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    if (subActivity['description']?.toString().isNotEmpty ??
                        false) ...[
                      const SizedBox(height: 4),
                      Text(
                        subActivity['description']?.toString() ?? '-',
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
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        if (subActivity['estimated_duration_hours'] !=
                            null) ...[
                          Icon(
                            Icons.schedule,
                            size: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '${subActivity['estimated_duration_hours']} ${subActivity['duration_unit'] ?? 'days'}',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                          ),
                          const SizedBox(width: 12),
                        ],
                        if (subActivity['skill_level'] != null) ...[
                          Icon(
                            Icons.build,
                            size: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            subActivity['skill_level'].toString().replaceAll(
                              '_',
                              ' ',
                            ),
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                          ),
                        ],
                      ],
                    ),
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

class _SubActivityFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? subActivity;

  const _SubActivityFormSheet({required this.refs, this.subActivity});

  @override
  ConsumerState<_SubActivityFormSheet> createState() =>
      _SubActivityFormSheetState();
}

class _SubActivityFormSheetState extends ConsumerState<_SubActivityFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _descriptionController;
  late final TextEditingController _durationController;
  late final TextEditingController _laborController;
  late final TextEditingController _sortOrderController;
  int? _activityId;
  String _durationUnit = 'days';
  String _skillLevel = 'semi_skilled';
  bool _canRunParallel = false;
  bool _weatherDependent = false;
  bool _saving = false;

  bool get _isEdit => widget.subActivity != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.subActivity?['name']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.subActivity?['description']?.toString() ?? '',
    );
    _durationController = TextEditingController(
      text: widget.subActivity?['estimated_duration_hours']?.toString() ?? '',
    );
    _laborController = TextEditingController(
      text: widget.subActivity?['labor_requirement']?.toString() ?? '',
    );
    _sortOrderController = TextEditingController(
      text: widget.subActivity?['sort_order']?.toString() ?? '0',
    );
    _activityId = _toNullableInt(widget.subActivity?['activity_id']);
    _durationUnit = widget.subActivity?['duration_unit']?.toString() ?? 'days';
    _skillLevel =
        widget.subActivity?['skill_level']?.toString() ?? 'semi_skilled';
    _canRunParallel = widget.subActivity?['can_run_parallel'] == true;
    _weatherDependent = widget.subActivity?['weather_dependent'] == true;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    _durationController.dispose();
    _laborController.dispose();
    _sortOrderController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final activities = _toMaps(widget.refs['activities']);
    final durationUnits = _toMaps(widget.refs['duration_units']);
    final skillLevels = _toMaps(widget.refs['skill_levels']);

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
                          ? (isSwahili
                                ? 'Hariri Sub-Activity'
                                : 'Edit Sub-Activity')
                          : (isSwahili
                                ? 'Sub-Activity Mpya'
                                : 'New Sub-Activity'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value:
                          activities.any(
                            (item) => _toInt(item['id']) == _activityId,
                          )
                          ? _activityId
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Activity *' : 'Activity *',
                      ),
                      dropdownColor: bgColor,
                      items: activities
                          .map(
                            (item) => DropdownMenuItem<int>(
                              value: _toInt(item['id']),
                              child: Text(
                                '${item['construction_stage_name'] ?? '-'} - ${item['name'] ?? '-'}',
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(color: textColor),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _activityId = value),
                      validator: (selected) => selected == null
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _nameController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili
                                ? 'Jina linahitajika'
                                : 'Name is required')
                          : null,
                      decoration: inputStyle(
                        isSwahili
                            ? 'Jina la Sub-Activity *'
                            : 'Sub-Activity Name *',
                      ),
                      style: TextStyle(color: textColor),
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
                          flex: 2,
                          child: TextFormField(
                            controller: _durationController,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            validator: (value) =>
                                (value == null || value.trim().isEmpty)
                                ? (isSwahili ? 'Hitaji' : 'Required')
                                : null,
                            decoration: inputStyle(
                              isSwahili ? 'Duration *' : 'Duration *',
                            ),
                            style: TextStyle(color: textColor),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          flex: 1,
                          child: DropdownButtonFormField<String>(
                            value: _durationUnit,
                            decoration: inputStyle(isSwahili ? 'Unit' : 'Unit'),
                            dropdownColor: bgColor,
                            items: durationUnits
                                .map(
                                  (item) => DropdownMenuItem<String>(
                                    value: item['name']?.toString() ?? 'days',
                                    child: Text(
                                      item['name']?.toString() ?? 'days',
                                      style: TextStyle(color: textColor),
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (value) =>
                                setState(() => _durationUnit = value ?? 'days'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _laborController,
                      keyboardType: TextInputType.number,
                      decoration: inputStyle(
                        isSwahili ? 'Workers Required' : 'Workers Required',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: _skillLevel,
                      decoration: inputStyle(
                        isSwahili ? 'Skill Level' : 'Skill Level',
                      ),
                      dropdownColor: bgColor,
                      items: skillLevels
                          .map(
                            (item) => DropdownMenuItem<String>(
                              value: item['name']?.toString() ?? 'semi_skilled',
                              child: Text(
                                item['name']?.toString()?.replaceAll(
                                      '_',
                                      ' ',
                                    ) ??
                                    'Semi-skilled',
                                style: TextStyle(color: textColor),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) =>
                          setState(() => _skillLevel = value ?? 'semi_skilled'),
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
                    const SizedBox(height: 8),
                    SwitchListTile(
                      value: _canRunParallel,
                      onChanged: (value) =>
                          setState(() => _canRunParallel = value),
                      title: Text(
                        isSwahili
                            ? 'Inaweza kuendeshwa kwa sambamba'
                            : 'Can run in parallel',
                        style: TextStyle(color: textColor, fontSize: 14),
                      ),
                      contentPadding: EdgeInsets.zero,
                    ),
                    SwitchListTile(
                      value: _weatherDependent,
                      onChanged: (value) =>
                          setState(() => _weatherDependent = value),
                      title: Text(
                        isSwahili
                            ? 'Inategemea hali ya hewa'
                            : 'Weather dependent',
                        style: TextStyle(color: textColor, fontSize: 14),
                      ),
                      contentPadding: EdgeInsets.zero,
                    ),
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
      final data = {
        'activity_id': _activityId,
        'name': _nameController.text.trim(),
        'description': _descriptionController.text.trim().isEmpty
            ? null
            : _descriptionController.text.trim(),
        'estimated_duration_hours': _durationController.text.trim(),
        'duration_unit': _durationUnit,
        'labor_requirement': _laborController.text.trim().isEmpty
            ? null
            : int.tryParse(_laborController.text.trim()),
        'skill_level': _skillLevel,
        'can_run_parallel': _canRunParallel,
        'weather_dependent': _weatherDependent,
        'sort_order': int.tryParse(_sortOrderController.text.trim()) ?? 0,
      };

      if (_isEdit) {
        await api.put(
          '/settings-sub-activities/${widget.subActivity!['id']}',
          data: data,
        );
      } else {
        await api.post('/settings-sub-activities', data: data);
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
