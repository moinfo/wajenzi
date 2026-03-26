import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _settingsSubActivitiesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/settings-sub-activities');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _settingsSubActivityRefsProvider =
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

class SettingsSubActivitiesScreen extends ConsumerWidget {
  const SettingsSubActivitiesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final subActivitiesAsync = ref.watch(_settingsSubActivitiesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Sub-Activities' : 'Sub-Activities'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_settingsSubActivitiesProvider),
        child: subActivitiesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _SettingsSubActivityErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_settingsSubActivitiesProvider),
          ),
          data: (subActivities) {
            if (subActivities.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.extension_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna sub-activities' : 'No sub-activities found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: subActivities.length + 1,
              itemBuilder: (context, index) {
                if (index == subActivities.length) return const SizedBox(height: 80);
                final subActivity = subActivities[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    isThreeLine: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                      child: const Icon(Icons.extension, color: AppColors.primary),
                    ),
                    title: Text(
                      subActivity['name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${subActivity['construction_stage_name'] ?? '-'} - ${subActivity['activity_name'] ?? '-'}\n${subActivity['description'] ?? '-'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDetails(context, subActivity, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, subActivity: subActivity);
                        } else if (value == 'delete') {
                          _deleteSubActivity(context, ref, subActivity);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
                      ],
                    ),
                    onTap: () => _showDetails(context, subActivity, isDarkMode),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? subActivity}) async {
    final refs = await ref.read(_settingsSubActivityRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _SettingsSubActivityFormSheet(refs: refs, subActivity: subActivity),
      ),
    );
    if (result == true) ref.invalidate(_settingsSubActivitiesProvider);
  }

  Future<void> _deleteSubActivity(BuildContext context, WidgetRef ref, Map<String, dynamic> subActivity) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Sub-Activity' : 'Delete Sub-Activity'),
        content: Text(isSwahili ? 'Je, unataka kufuta ${subActivity['name']}?' : 'Delete ${subActivity['name']}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/settings-sub-activities/${subActivity['id']}');
      ref.invalidate(_settingsSubActivitiesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Sub-activity imefutwa' : 'Sub-activity deleted'),
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

  void _showDetails(BuildContext context, Map<String, dynamic> subActivity, bool isDarkMode) {
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
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 16),
                      _detailLine('Activity', subActivity['activity_name']),
                      _detailLine('Construction Stage', subActivity['construction_stage_name']),
                      _detailLine('Description', subActivity['description']),
                      _detailLine('Duration', '${subActivity['estimated_duration_hours'] ?? '-'} ${subActivity['duration_unit'] ?? ''}'),
                      _detailLine('Labor Requirement', subActivity['labor_requirement']),
                      _detailLine('Skill Level', subActivity['skill_level']),
                      _detailLine('Parallel', subActivity['can_run_parallel'] == true ? 'Yes' : 'No'),
                      _detailLine('Weather Dependent', subActivity['weather_dependent'] == true ? 'Yes' : 'No'),
                      _detailLine('Sort Order', subActivity['sort_order']),
                      _detailLine('Materials Count', subActivity['materials_count']),
                      _detailLine('BOQ Items Count', subActivity['boq_items_count']),
                      _detailLine('Template Usage Count', subActivity['template_sub_activities_count']),
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

class _SettingsSubActivityFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? subActivity;

  const _SettingsSubActivityFormSheet({
    required this.refs,
    this.subActivity,
  });

  @override
  ConsumerState<_SettingsSubActivityFormSheet> createState() => _SettingsSubActivityFormSheetState();
}

class _SettingsSubActivityFormSheetState extends ConsumerState<_SettingsSubActivityFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController = TextEditingController(text: widget.subActivity?['name']?.toString() ?? '');
  late final TextEditingController _descriptionController = TextEditingController(text: widget.subActivity?['description']?.toString() ?? '');
  late final TextEditingController _durationController = TextEditingController(text: widget.subActivity?['estimated_duration_hours']?.toString() ?? '');
  late final TextEditingController _laborController = TextEditingController(text: widget.subActivity?['labor_requirement']?.toString() ?? '');
  late final TextEditingController _sortOrderController = TextEditingController(text: widget.subActivity?['sort_order']?.toString() ?? '0');
  int? _activityId;
  String _durationUnit = 'days';
  String _skillLevel = 'semi_skilled';
  bool _canRunParallel = false;
  bool _weatherDependent = false;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _activityId = _toNullableInt(widget.subActivity?['activity_id']);
    _durationUnit = widget.subActivity?['duration_unit']?.toString() ?? 'days';
    _skillLevel = widget.subActivity?['skill_level']?.toString() ?? 'semi_skilled';
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
                          widget.subActivity == null ? 'New Sub-Activity' : 'Edit Sub-Activity',
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _dropdownInt(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Activity *' : 'Activity *',
                          items: activities.map((e) => {'id': e['id'], 'name': '${e['construction_stage_name'] ?? '-'} - ${e['name'] ?? '-'}'}).toList(),
                          value: _activityId,
                          onChanged: (value) => setState(() => _activityId = value),
                        ),
                        const SizedBox(height: 12),
                        _input(_nameController, isSwahili ? 'Name *' : 'Name *', isDarkMode),
                        const SizedBox(height: 12),
                        _input(_descriptionController, isSwahili ? 'Description' : 'Description', isDarkMode, required: false, maxLines: 3),
                        const SizedBox(height: 12),
                        _input(_durationController, isSwahili ? 'Duration *' : 'Duration *', isDarkMode, keyboardType: const TextInputType.numberWithOptions(decimal: true)),
                        const SizedBox(height: 12),
                        _dropdownString(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Unit' : 'Unit',
                          items: durationUnits.map((e) => e['name']?.toString() ?? '').where((e) => e.isNotEmpty).toList(),
                          value: _durationUnit,
                          onChanged: (value) => setState(() => _durationUnit = value ?? 'days'),
                        ),
                        const SizedBox(height: 12),
                        _input(_laborController, isSwahili ? 'Workers Required' : 'Workers Required', isDarkMode, required: false, keyboardType: TextInputType.number),
                        const SizedBox(height: 12),
                        _dropdownString(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Skill Level' : 'Skill Level',
                          items: skillLevels.map((e) => e['name']?.toString() ?? '').where((e) => e.isNotEmpty).toList(),
                          value: _skillLevel,
                          onChanged: (value) => setState(() => _skillLevel = value ?? 'semi_skilled'),
                        ),
                        const SizedBox(height: 12),
                        _input(_sortOrderController, isSwahili ? 'Sort Order' : 'Sort Order', isDarkMode, required: false, keyboardType: TextInputType.number),
                        const SizedBox(height: 8),
                        SwitchListTile(
                          value: _canRunParallel,
                          onChanged: (value) => setState(() => _canRunParallel = value),
                          title: Text(isSwahili ? 'Can run in parallel' : 'Can run in parallel'),
                          contentPadding: EdgeInsets.zero,
                        ),
                        SwitchListTile(
                          value: _weatherDependent,
                          onChanged: (value) => setState(() => _weatherDependent = value),
                          title: Text(isSwahili ? 'Weather dependent' : 'Weather dependent'),
                          contentPadding: EdgeInsets.zero,
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
                              : Text(widget.subActivity == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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

  Widget _dropdownInt({
    required bool isDarkMode,
    required String label,
    required List<Map<String, dynamic>> items,
    required int? value,
    required ValueChanged<int?> onChanged,
  }) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: items.any((item) => _toInt(item['id']) == value) ? value : null,
      validator: (selected) => selected == null ? 'Required' : null,
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

  Widget _dropdownString({
    required bool isDarkMode,
    required String label,
    required List<String> items,
    required String value,
    required ValueChanged<String?> onChanged,
  }) {
    return DropdownButtonFormField<String>(
      isExpanded: true,
      value: items.contains(value) ? value : (items.isNotEmpty ? items.first : null),
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map((item) => DropdownMenuItem<String>(
                value: item,
                child: Text(item.replaceAll('_', ' '), overflow: TextOverflow.ellipsis),
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
        'activity_id': _activityId,
        'name': _nameController.text.trim(),
        'description': _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
        'estimated_duration_hours': _durationController.text.trim(),
        'duration_unit': _durationUnit,
        'labor_requirement': _laborController.text.trim().isEmpty ? null : int.tryParse(_laborController.text.trim()),
        'skill_level': _skillLevel,
        'can_run_parallel': _canRunParallel,
        'weather_dependent': _weatherDependent,
        'sort_order': int.tryParse(_sortOrderController.text.trim()) ?? 0,
      };

      if (widget.subActivity == null) {
        await api.post('/settings-sub-activities', data: data);
      } else {
        await api.put('/settings-sub-activities/${widget.subActivity!['id']}', data: data);
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

class _SettingsSubActivityErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SettingsSubActivityErrorView({
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
