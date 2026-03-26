import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _boqTemplatesProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/boq-templates');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _boqTemplateRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/boq-templates/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class BoqTemplatesScreen extends ConsumerWidget {
  const BoqTemplatesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final templatesAsync = ref.watch(_boqTemplatesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'BOQ Templates' : 'BOQ Templates'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_boqTemplatesProvider),
        child: templatesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _BoqTemplateErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_boqTemplatesProvider),
          ),
          data: (templates) {
            if (templates.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.description_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna BOQ templates' : 'No BOQ templates found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: templates.length + 1,
              itemBuilder: (context, index) {
                if (index == templates.length) return const SizedBox(height: 80);
                final template = templates[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    isThreeLine: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                      child: const Icon(Icons.description, color: AppColors.primary),
                    ),
                    title: Text(
                      template['name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${_buildingTypeLabel(template)}\n${_templateMeta(template)}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDetails(context, template, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, template: template);
                        } else if (value == 'delete') {
                          _deleteTemplate(context, ref, template);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
                      ],
                    ),
                    onTap: () => _showDetails(context, template, isDarkMode),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? template}) async {
    final refs = await ref.read(_boqTemplateRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _BoqTemplateFormSheet(refs: refs, template: template),
      ),
    );
    if (result == true) ref.invalidate(_boqTemplatesProvider);
  }

  Future<void> _deleteTemplate(BuildContext context, WidgetRef ref, Map<String, dynamic> template) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa BOQ Template' : 'Delete BOQ Template'),
        content: Text(isSwahili ? 'Je, unataka kufuta ${template['name']}?' : 'Delete ${template['name']}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/boq-templates/${template['id']}');
      ref.invalidate(_boqTemplatesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'BOQ template imefutwa' : 'BOQ template deleted'),
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

  void _showDetails(BuildContext context, Map<String, dynamic> template, bool isDarkMode) {
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
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 16),
                      _detailLine('Building Type', _buildingTypeLabel(template)),
                      _detailLine('Roof Type', _prettyEnum(template['roof_type'])),
                      _detailLine('Rooms', template['no_of_rooms']),
                      _detailLine('Square Metre', _measurement(template['square_metre'], 'SQM')),
                      _detailLine('Run Metre', _measurement(template['run_metre'], 'RM')),
                      _detailLine('Description', template['description']),
                      _detailLine('Status', template['is_active'] == true ? 'Active' : 'Inactive'),
                      _detailLine('Created By', template['creator_name']),
                      _detailLine('Stages', template['stages_count']),
                      _detailLine('Activities', template['activities_count']),
                      _detailLine('Sub Activities', template['sub_activities_count']),
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

class _BoqTemplateFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? template;

  const _BoqTemplateFormSheet({
    required this.refs,
    this.template,
  });

  @override
  ConsumerState<_BoqTemplateFormSheet> createState() => _BoqTemplateFormSheetState();
}

class _BoqTemplateFormSheetState extends ConsumerState<_BoqTemplateFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController = TextEditingController(text: widget.template?['name']?.toString() ?? '');
  late final TextEditingController _squareMetreController = TextEditingController(text: widget.template?['square_metre']?.toString() ?? '');
  late final TextEditingController _runMetreController = TextEditingController(text: widget.template?['run_metre']?.toString() ?? '');
  late final TextEditingController _descriptionController = TextEditingController(text: widget.template?['description']?.toString() ?? '');
  int? _buildingTypeId;
  String? _roofType;
  String? _rooms;
  bool _isActive = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
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
                          widget.template == null ? 'New BOQ Template' : 'Edit BOQ Template',
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _input(_nameController, isSwahili ? 'Template Name *' : 'Template Name *', isDarkMode),
                        const SizedBox(height: 12),
                        _dropdownInt(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Building Type' : 'Building Type',
                          items: buildingTypes,
                          value: _buildingTypeId,
                          onChanged: (value) => setState(() => _buildingTypeId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdownString(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Roof Type' : 'Roof Type',
                          items: roofTypes,
                          value: _roofType,
                          onChanged: (value) => setState(() => _roofType = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdownString(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Number of Rooms' : 'Number of Rooms',
                          items: roomOptions,
                          value: _rooms,
                          onChanged: (value) => setState(() => _rooms = value),
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _squareMetreController,
                          isSwahili ? 'Square Metre (SQM)' : 'Square Metre (SQM)',
                          isDarkMode,
                          required: false,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _runMetreController,
                          isSwahili ? 'Run Metre' : 'Run Metre',
                          isDarkMode,
                          required: false,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        ),
                        const SizedBox(height: 12),
                        _input(_descriptionController, isSwahili ? 'Description' : 'Description', isDarkMode, required: false, maxLines: 4),
                        const SizedBox(height: 8),
                        SwitchListTile(
                          value: _isActive,
                          onChanged: (value) => setState(() => _isActive = value),
                          title: Text(isSwahili ? 'Active' : 'Active'),
                          contentPadding: EdgeInsets.zero,
                        ),
                        if (widget.template == null) ...[
                          const SizedBox(height: 8),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.blueGrey.withValues(alpha: 0.08),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Text(
                              "After creating the template, you can configure stages, activities, and sub-activities from the web template builder.",
                            ),
                          ),
                        ],
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.template == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: [
        const DropdownMenuItem<int>(value: null, child: Text('No building type')),
        ...items.map(
          (item) => DropdownMenuItem<int>(
            value: _toInt(item['id']),
            child: Text(_buildingTypeRefLabel(item), overflow: TextOverflow.ellipsis),
          ),
        ),
      ],
      onChanged: onChanged,
    );
  }

  Widget _dropdownString({
    required bool isDarkMode,
    required String label,
    required List<String> items,
    required String? value,
    required ValueChanged<String?> onChanged,
  }) {
    final normalizedValue = items.contains(value) ? value : null;
    return DropdownButtonFormField<String>(
      isExpanded: true,
      value: normalizedValue,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: [
        const DropdownMenuItem<String>(value: null, child: Text('Not set')),
        ...items.map(
          (item) => DropdownMenuItem<String>(
            value: item,
            child: Text(_prettyEnum(item), overflow: TextOverflow.ellipsis),
          ),
        ),
      ],
      onChanged: onChanged,
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
        'square_metre': squareMetreRaw.isEmpty ? null : double.tryParse(squareMetreRaw),
        'run_metre': runMetreRaw.isEmpty ? null : double.tryParse(runMetreRaw),
        'description': _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
        'is_active': _isActive,
      };

      if (widget.template == null) {
        await api.post('/boq-templates', data: data);
      } else {
        await api.put('/boq-templates/${widget.template!['id']}', data: data);
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

String _buildingTypeLabel(Map<String, dynamic> template) {
  final name = _nullableString(template['building_type_name']);
  final parent = _nullableString(template['building_type_parent_name']);
  if (name == null) return 'No building type';
  if (parent == null) return name;
  return '$parent > $name';
}

String _templateMeta(Map<String, dynamic> template) {
  final specs = <String>[];
  final roof = _prettyEnum(template['roof_type']);
  final rooms = _nullableString(template['no_of_rooms']);
  if (roof != '-') specs.add(roof);
  if (rooms != null && rooms.isNotEmpty) specs.add('$rooms rooms');
  if (specs.isEmpty) {
    return '${template['stages_count'] ?? 0} stages • ${template['activities_count'] ?? 0} activities';
  }
  return '${specs.join(' • ')} • ${template['stages_count'] ?? 0} stages';
}

String _measurement(dynamic value, String suffix) {
  final number = value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '');
  if (number == null) return '-';
  return '${number.toStringAsFixed(2)} $suffix';
}

String _prettyEnum(dynamic value) {
  final text = value?.toString().trim();
  if (text == null || text.isEmpty) return '-';
  return text.replaceAll('_', ' ').split(' ').map((word) {
    if (word.isEmpty) return word;
    return word[0].toUpperCase() + word.substring(1);
  }).join(' ');
}

String _buildingTypeRefLabel(Map<String, dynamic> item) {
  final name = item['name']?.toString().trim() ?? '-';
  final parent = _nullableString(item['parent_name']);
  if (parent == null) return name;
  return '$parent > $name';
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
}

List<String> _toNameList(dynamic value) {
  return _toMaps(value).map((item) => item['name']?.toString() ?? '').where((item) => item.isNotEmpty).toList();
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
