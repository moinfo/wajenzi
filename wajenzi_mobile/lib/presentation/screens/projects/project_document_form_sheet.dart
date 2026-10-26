import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

/// Upload / edit form, same fields as the web's project_document_form:
/// Project, Document Type, Document File (required only when adding),
/// Description. Pops `true` once saved.
class ProjectDocumentFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? document;
  const ProjectDocumentFormSheet({super.key, this.document});

  @override
  ConsumerState<ProjectDocumentFormSheet> createState() =>
      _ProjectDocumentFormSheetState();
}

class _ProjectDocumentFormSheetState
    extends ConsumerState<ProjectDocumentFormSheet> {
  static const _types = ['contract', 'drawing', 'report', 'other'];

  final _description = TextEditingController();
  List<Map<String, dynamic>> _projects = [];
  int? _projectId;
  String? _type;
  PlatformFile? _file;
  bool _saving = false;
  bool _loadingProjects = true;
  String? _error;

  bool get _isEdit => widget.document != null;

  @override
  void initState() {
    super.initState();
    final d = widget.document;
    if (d != null) {
      _projectId = int.tryParse('${d['project_id']}');
      _type = d['document_type'] as String?;
      _description.text = d['description'] as String? ?? '';
    }
    _loadProjects();
  }

  @override
  void dispose() {
    _description.dispose();
    super.dispose();
  }

  Future<void> _loadProjects() async {
    try {
      final response = await ref.read(apiClientProvider).get('/projects');
      final data = response.data['data'];
      final list = data is Map ? data['data'] : data;
      if (!mounted) return;
      setState(() {
        _projects = (list as List? ?? const []).cast<Map<String, dynamic>>();
        _loadingProjects = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loadingProjects = false);
    }
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles();
    if (result != null && result.files.isNotEmpty) {
      setState(() => _file = result.files.first);
    }
  }

  Future<void> _save(bool isSwahili) async {
    if (_projectId == null ||
        _type == null ||
        (!_isEdit && _file?.path == null)) {
      setState(() {
        _error = isSwahili
            ? 'Jaza sehemu zote zinazohitajika'
            : 'Please fill in all required fields';
      });
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final form = FormData.fromMap({
        'project_id': _projectId,
        'document_type': _type,
        'description': _description.text.trim(),
        if (_file?.path != null)
          'file': await MultipartFile.fromFile(
            _file!.path!,
            filename: _file!.name,
          ),
      });
      await ref.read(apiClientProvider).uploadFile(
            _isEdit
                ? '/project-documents/${widget.document!['id']}'
                : '/project-documents',
            data: form,
          );
      if (mounted) Navigator.pop(context, true);
    } on DioException catch (e) {
      final data = e.response?.data;
      final message = data is Map && data['message'] != null
          ? data['message'].toString()
          : 'Failed to save the document';
      if (mounted) setState(() => _error = message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final title = _isEdit
        ? (isSwahili ? 'Hariri Hati' : 'Edit Document')
        : (isSwahili ? 'Pakia Hati Mpya' : 'Upload New Document');
    final fileLabel = _file?.name ??
        (_isEdit
            ? (isSwahili ? 'Badilisha faili (hiari)' : 'Replace file (optional)')
            : (isSwahili ? 'Chagua faili *' : 'Choose file *'));

    return Padding(
      padding: EdgeInsets.fromLTRB(
        16,
        16,
        16,
        16 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<int>(
              isExpanded: true,
              initialValue:
                  _projects.any((p) => int.tryParse('${p['id']}') == _projectId)
                      ? _projectId
                      : null,
              decoration: InputDecoration(
                labelText: isSwahili ? 'Mradi *' : 'Project *',
                suffixIcon: _loadingProjects
                    ? const Padding(
                        padding: EdgeInsets.all(12),
                        child: SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : null,
              ),
              items: _projects
                  .map(
                    (p) => DropdownMenuItem<int>(
                      value: int.tryParse('${p['id']}'),
                      child: Text(
                        '${p['project_name'] ?? p['name'] ?? p['id']}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  )
                  .toList(),
              onChanged: (v) => setState(() => _projectId = v),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              isExpanded: true,
              initialValue: _types.contains(_type) ? _type : null,
              decoration: InputDecoration(
                labelText: isSwahili ? 'Aina ya Hati *' : 'Document Type *',
              ),
              items: _types
                  .map(
                    (t) => DropdownMenuItem<String>(
                      value: t,
                      child: Text(t[0].toUpperCase() + t.substring(1)),
                    ),
                  )
                  .toList(),
              onChanged: (v) => setState(() => _type = v),
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: _saving ? null : _pickFile,
              icon: const Icon(Icons.attach_file),
              label: Text(fileLabel, overflow: TextOverflow.ellipsis),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _description,
              maxLines: 3,
              decoration: InputDecoration(
                labelText: isSwahili ? 'Maelezo' : 'Description',
              ),
            ),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Text(_error!, style: const TextStyle(color: AppColors.error)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _saving ? null : () => _save(isSwahili),
              child: _saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Text(
                      _isEdit
                          ? (isSwahili ? 'Sasisha' : 'Update')
                          : (isSwahili ? 'Wasilisha' : 'Submit'),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
