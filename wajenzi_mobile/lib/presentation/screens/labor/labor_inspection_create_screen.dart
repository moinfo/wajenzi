import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

/// Create a new labor inspection.
///
/// Loads `GET /labor/inspections/reference-data` for the contract picker
/// (`contracts_pending_inspection`) and enum lists, then POSTs a multipart
/// request to `/labor/inspections` with `photos[]` files. On success the API
/// creates the inspection as `pending` and auto-submits it into the approval
/// flow, so we simply pop(true) to trigger a list refresh.
class LaborInspectionCreateScreen extends ConsumerStatefulWidget {
  const LaborInspectionCreateScreen({super.key});

  @override
  ConsumerState<LaborInspectionCreateScreen> createState() =>
      _LaborInspectionCreateScreenState();
}

class _LaborInspectionCreateScreenState
    extends ConsumerState<LaborInspectionCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  final _defectsController = TextEditingController(text: '0');
  final _rectificationNotesController = TextEditingController();
  final _notesController = TextEditingController();

  bool _isLoadingRef = true;
  bool _isSubmitting = false;
  String? _refError;

  List<dynamic> _contracts = [];
  List<Map<String, dynamic>> _inspectionTypes = const [
    {'value': 'progress', 'label': 'Progress'},
    {'value': 'milestone', 'label': 'Milestone'},
    {'value': 'final', 'label': 'Final'},
  ];
  List<Map<String, dynamic>> _qualityLevels = const [
    {'value': 'excellent', 'label': 'Excellent'},
    {'value': 'good', 'label': 'Good'},
    {'value': 'acceptable', 'label': 'Acceptable'},
    {'value': 'poor', 'label': 'Poor'},
    {'value': 'unacceptable', 'label': 'Unacceptable'},
  ];

  int? _contractId;
  String? _inspectionType;
  String? _workQuality;
  double _completion = 0;
  bool _scopeCompliance = true;
  bool _rectificationRequired = false;
  DateTime _inspectionDate = DateTime.now();
  final List<XFile> _photos = [];

  @override
  void initState() {
    super.initState();
    _loadReferenceData();
  }

  @override
  void dispose() {
    _defectsController.dispose();
    _rectificationNotesController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _loadReferenceData() async {
    setState(() {
      _isLoadingRef = true;
      _refError = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/labor/inspections/reference-data');

      if (response.statusCode == 200) {
        final data = response.data['data'] as Map<String, dynamic>? ?? const {};
        setState(() {
          _contracts = data['contracts_pending_inspection'] as List<dynamic>? ??
              data['contracts'] as List<dynamic>? ??
              [];
          final types = data['inspection_types'] as List<dynamic>?;
          if (types != null && types.isNotEmpty) {
            _inspectionTypes = types
                .map((e) => Map<String, dynamic>.from(e as Map))
                .toList();
          }
          final quality = data['quality_levels'] as List<dynamic>?;
          if (quality != null && quality.isNotEmpty) {
            _qualityLevels = quality
                .map((e) => Map<String, dynamic>.from(e as Map))
                .toList();
          }
          _isLoadingRef = false;
        });
      } else {
        setState(() {
          _refError = 'Failed to load reference data';
          _isLoadingRef = false;
        });
      }
    } catch (e) {
      setState(() {
        _refError = 'Error loading reference data';
        _isLoadingRef = false;
      });
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _inspectionDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
    );
    if (picked != null) {
      setState(() => _inspectionDate = picked);
    }
  }

  Future<void> _addPhoto(ImageSource source) async {
    try {
      final picked =
          await ImagePicker().pickImage(source: source, imageQuality: 80);
      if (picked != null) {
        setState(() => _photos.add(picked));
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not pick image')),
        );
      }
    }
  }

  void _showPhotoSourceSheet() {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: const Text('Take photo'),
              onTap: () {
                Navigator.pop(ctx);
                _addPhoto(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_rounded),
              title: const Text('Choose from gallery'),
              onTap: () {
                Navigator.pop(ctx);
                _addPhoto(ImageSource.gallery);
              },
            ),
          ],
        ),
      ),
    );
  }

  String _dateString(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _submit() async {
    if (_isSubmitting) return;
    if (!_formKey.currentState!.validate()) return;
    if (_contractId == null) {
      _showError('Please select a contract');
      return;
    }
    if (_inspectionType == null) {
      _showError('Please select an inspection type');
      return;
    }
    if (_workQuality == null) {
      _showError('Please select work quality');
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final api = ref.read(apiClientProvider);

      final formData = FormData();
      formData.fields.addAll([
        MapEntry('labor_contract_id', _contractId.toString()),
        MapEntry('inspection_date', _dateString(_inspectionDate)),
        MapEntry('inspection_type', _inspectionType!),
        MapEntry('completion_percentage', _completion.round().toString()),
        MapEntry('work_quality', _workQuality!),
        MapEntry('scope_compliance', _scopeCompliance ? '1' : '0'),
        MapEntry(
          'defects_found',
          (int.tryParse(_defectsController.text.trim()) ?? 0).toString(),
        ),
        MapEntry('rectification_required', _rectificationRequired ? '1' : '0'),
      ]);

      if (_rectificationNotesController.text.trim().isNotEmpty) {
        formData.fields.add(
          MapEntry(
            'rectification_notes',
            _rectificationNotesController.text.trim(),
          ),
        );
      }
      if (_notesController.text.trim().isNotEmpty) {
        formData.fields
            .add(MapEntry('notes', _notesController.text.trim()));
      }

      for (final photo in _photos) {
        formData.files.add(
          MapEntry(
            'photos[]',
            await MultipartFile.fromFile(
              photo.path,
              filename: photo.name,
            ),
          ),
        );
      }

      final response =
          await api.uploadFile('/labor/inspections', data: formData);

      if (response.statusCode == 200 || response.statusCode == 201) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                response.data['message']?.toString() ??
                    'Inspection created and submitted for approval.',
              ),
            ),
          );
          Navigator.of(context).pop(true);
        }
      } else {
        _showError('Failed to create inspection');
      }
    } catch (e) {
      _showError(_messageFromError(e));
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  String _messageFromError(Object e) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map) {
        if (data['errors'] is Map) {
          final errors = data['errors'] as Map;
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
        }
        if (data['message'] is String) return data['message'] as String;
      }
    }
    return 'Failed to create inspection';
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ukaguzi Mpya' : 'New Inspection'),
      ),
      body: _isLoadingRef
          ? const Center(child: CircularProgressIndicator())
          : _refError != null
              ? _buildRefError()
              : _buildForm(isSwahili),
    );
  }

  Widget _buildRefError() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.error_outline, size: 56, color: Colors.red),
          const SizedBox(height: 12),
          Text(_refError ?? 'Error'),
          const SizedBox(height: 16),
          OutlinedButton(
            onPressed: _loadReferenceData,
            child: const Text('Retry'),
          ),
        ],
      ),
    );
  }

  Widget _buildForm(bool isSwahili) {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (_contracts.isEmpty)
            Card(
              color: Colors.orange.withValues(alpha: 0.1),
              child: const Padding(
                padding: EdgeInsets.all(12),
                child: Text(
                  'No active contracts are pending inspection. An inspection can only be created for an active contract with a pending payment phase.',
                ),
              ),
            ),
          const SizedBox(height: 8),

          // Contract
          DropdownButtonFormField<int>(
            initialValue: _contractId,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Mkataba *' : 'Contract *',
              border: const OutlineInputBorder(),
            ),
            items: _contracts.map((c) {
              final contract = c as Map<String, dynamic>;
              final label =
                  '${contract['contract_number'] ?? ''} - ${contract['artisan_name'] ?? ''}';
              return DropdownMenuItem<int>(
                value: contract['id'] as int?,
                child: Text(label, overflow: TextOverflow.ellipsis),
              );
            }).toList(),
            onChanged: (v) => setState(() => _contractId = v),
            validator: (v) => v == null ? 'Required' : null,
          ),
          const SizedBox(height: 16),

          // Date
          InkWell(
            onTap: _pickDate,
            child: InputDecorator(
              decoration: InputDecoration(
                labelText: isSwahili ? 'Tarehe ya Ukaguzi *' : 'Inspection Date *',
                border: const OutlineInputBorder(),
                suffixIcon: const Icon(Icons.calendar_today),
              ),
              child: Text(_dateString(_inspectionDate)),
            ),
          ),
          const SizedBox(height: 16),

          // Type
          DropdownButtonFormField<String>(
            initialValue: _inspectionType,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Aina *' : 'Inspection Type *',
              border: const OutlineInputBorder(),
            ),
            items: _inspectionTypes
                .map((t) => DropdownMenuItem<String>(
                      value: t['value'] as String,
                      child: Text(t['label'] as String),
                    ))
                .toList(),
            onChanged: (v) => setState(() => _inspectionType = v),
            validator: (v) => v == null ? 'Required' : null,
          ),
          const SizedBox(height: 16),

          // Completion percentage
          Text(
            '${isSwahili ? 'Ukamilifu' : 'Completion'}: ${_completion.round()}%',
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
          Slider(
            value: _completion,
            min: 0,
            max: 100,
            divisions: 100,
            label: '${_completion.round()}%',
            onChanged: (v) => setState(() => _completion = v),
          ),
          const SizedBox(height: 8),

          // Work quality
          DropdownButtonFormField<String>(
            initialValue: _workQuality,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Ubora wa Kazi *' : 'Work Quality *',
              border: const OutlineInputBorder(),
            ),
            items: _qualityLevels
                .map((q) => DropdownMenuItem<String>(
                      value: q['value'] as String,
                      child: Text(q['label'] as String),
                    ))
                .toList(),
            onChanged: (v) => setState(() => _workQuality = v),
            validator: (v) => v == null ? 'Required' : null,
          ),
          const SizedBox(height: 8),

          // Scope compliance
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(isSwahili ? 'Kufuata Wigo' : 'Scope Compliance'),
            value: _scopeCompliance,
            onChanged: (v) => setState(() => _scopeCompliance = v),
          ),

          // Defects
          TextFormField(
            controller: _defectsController,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Kasoro Zilizopatikana' : 'Defects Found',
              border: const OutlineInputBorder(),
            ),
            validator: (v) {
              if (v == null || v.trim().isEmpty) return null;
              final n = int.tryParse(v.trim());
              if (n == null || n < 0) return 'Enter a valid number';
              return null;
            },
          ),
          const SizedBox(height: 8),

          // Rectification required
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title:
                Text(isSwahili ? 'Marekebisho Yanahitajika' : 'Rectification Required'),
            value: _rectificationRequired,
            onChanged: (v) => setState(() => _rectificationRequired = v),
          ),

          if (_rectificationRequired) ...[
            TextFormField(
              controller: _rectificationNotesController,
              maxLines: 2,
              decoration: InputDecoration(
                labelText:
                    isSwahili ? 'Maelezo ya Marekebisho' : 'Rectification Notes',
                border: const OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
          ],

          // Notes
          TextFormField(
            controller: _notesController,
            maxLines: 3,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Maelezo' : 'Notes',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),

          // Photos
          _buildPhotoSection(isSwahili),
          const SizedBox(height: 24),

          FilledButton.icon(
            onPressed: _isSubmitting || _contracts.isEmpty ? null : _submit,
            icon: _isSubmitting
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.check),
            label: Text(
              _isSubmitting
                  ? (isSwahili ? 'Inatuma...' : 'Submitting...')
                  : (isSwahili ? 'Unda na Wasilisha' : 'Create & Submit'),
            ),
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildPhotoSection(bool isSwahili) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              '${isSwahili ? 'Picha' : 'Photos'} (${_photos.length})',
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
            const Spacer(),
            TextButton.icon(
              onPressed: _showPhotoSourceSheet,
              icon: const Icon(Icons.add_a_photo),
              label: Text(isSwahili ? 'Ongeza' : 'Add'),
            ),
          ],
        ),
        if (_photos.isNotEmpty)
          SizedBox(
            height: 90,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: _photos.length,
              separatorBuilder: (context, index) => const SizedBox(width: 8),
              itemBuilder: (context, index) {
                final photo = _photos[index];
                return Stack(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: Image.file(
                        File(photo.path),
                        width: 90,
                        height: 90,
                        fit: BoxFit.cover,
                      ),
                    ),
                    Positioned(
                      top: 0,
                      right: 0,
                      child: GestureDetector(
                        onTap: () => setState(() => _photos.removeAt(index)),
                        child: Container(
                          decoration: const BoxDecoration(
                            color: Colors.black54,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.close,
                            size: 18,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
          ),
      ],
    );
  }
}
