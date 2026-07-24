import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../core/config/app_config.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _laborLogFormReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/logs/reference-data');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class _MaterialEntry {
  final TextEditingController name;
  final TextEditingController quantity;

  _MaterialEntry({String? name, String? quantity})
    : name = TextEditingController(text: name ?? ''),
      quantity = TextEditingController(text: quantity ?? '');

  void dispose() {
    name.dispose();
    quantity.dispose();
  }
}

class LaborLogFormScreen extends ConsumerStatefulWidget {
  /// When provided the screen edits an existing log; otherwise it creates one.
  final Map<String, dynamic>? existingLog;

  const LaborLogFormScreen({super.key, this.existingLog});

  @override
  ConsumerState<LaborLogFormScreen> createState() => _LaborLogFormScreenState();
}

class _LaborLogFormScreenState extends ConsumerState<LaborLogFormScreen> {
  final _formKey = GlobalKey<FormState>();

  final _workDoneCtrl = TextEditingController();
  final _workersCtrl = TextEditingController();
  final _hoursCtrl = TextEditingController();
  final _progressCtrl = TextEditingController();
  final _challengesCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();

  int? _contractId;
  DateTime _logDate = DateTime.now();
  String? _weather;
  final List<_MaterialEntry> _materials = [];

  final List<String> _newPhotoPaths = [];
  List<String> _existingPhotos = [];

  bool _submitting = false;

  bool get _isEdit => widget.existingLog != null;

  @override
  void initState() {
    super.initState();
    final log = widget.existingLog;
    if (log != null) {
      _contractId = log['labor_contract_id'] as int?;
      if (log['log_date'] != null) {
        _logDate = DateTime.tryParse(log['log_date'] as String) ?? _logDate;
      }
      _workDoneCtrl.text = log['work_done'] as String? ?? '';
      _workersCtrl.text = log['workers_present'] != null
          ? '${log['workers_present']}'
          : '';
      if (log['hours_worked'] != null) {
        _hoursCtrl.text = '${log['hours_worked']}';
      }
      if (log['progress_percentage'] != null) {
        _progressCtrl.text = '${log['progress_percentage']}';
      }
      _challengesCtrl.text = log['challenges'] as String? ?? '';
      _notesCtrl.text = log['notes'] as String? ?? '';
      _weather = log['weather_conditions'] as String?;
      _existingPhotos = (log['photos'] as List? ?? const [])
          .map((e) => e.toString())
          .toList();
      final mats = log['materials_used'] as List? ?? const [];
      for (final m in mats) {
        final mat = Map<String, dynamic>.from(m as Map);
        _materials.add(
          _MaterialEntry(
            name: mat['name'] as String?,
            quantity: mat['quantity'] != null ? '${mat['quantity']}' : null,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _workDoneCtrl.dispose();
    _workersCtrl.dispose();
    _hoursCtrl.dispose();
    _progressCtrl.dispose();
    _challengesCtrl.dispose();
    _notesCtrl.dispose();
    for (final m in _materials) {
      m.dispose();
    }
    super.dispose();
  }

  String _resolvePhotoUrl(String path) {
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    final normalized = path.startsWith('/') ? path : '/$path';
    return '${AppConfig.activePortalBaseUrl}$normalized';
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _logDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      setState(() => _logDate = picked);
    }
  }

  Future<void> _addPhoto(bool isSwahili) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: Text(isSwahili ? 'Piga Picha' : 'Take photo'),
              onTap: () => Navigator.of(ctx).pop(ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_rounded),
              title: Text(isSwahili ? 'Chagua kwenye Galari' : 'From gallery'),
              onTap: () => Navigator.of(ctx).pop(ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;
    final picked = await ImagePicker().pickImage(
      source: source,
      imageQuality: 80,
      maxWidth: 1920,
    );
    if (picked != null) {
      setState(() => _newPhotoPaths.add(picked.path));
    }
  }

  void _addMaterial() {
    setState(() => _materials.add(_MaterialEntry()));
  }

  void _removeMaterial(int index) {
    setState(() {
      _materials.removeAt(index).dispose();
    });
  }

  String _errorMessage(Object e, bool isSwahili) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return first.first.toString();
        }
        final msg = data['message'];
        if (msg is String && msg.isNotEmpty) return msg;
      }
    }
    return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
  }

  Future<void> _submit(bool isSwahili) async {
    if (!_formKey.currentState!.validate()) return;
    if (_contractId == null) {
      _snack(isSwahili ? 'Chagua mkataba' : 'Please select a contract');
      return;
    }

    final map = <String, dynamic>{
      'log_date': DateFormat('yyyy-MM-dd').format(_logDate),
      'work_done': _workDoneCtrl.text.trim(),
      'workers_present': _workersCtrl.text.trim(),
    };
    if (!_isEdit) {
      map['labor_contract_id'] = _contractId;
    }
    if (_hoursCtrl.text.trim().isNotEmpty) {
      map['hours_worked'] = _hoursCtrl.text.trim();
    }
    if (_progressCtrl.text.trim().isNotEmpty) {
      map['progress_percentage'] = _progressCtrl.text.trim();
    }
    if (_weather != null) {
      map['weather_conditions'] = _weather;
    }
    if (_challengesCtrl.text.trim().isNotEmpty) {
      map['challenges'] = _challengesCtrl.text.trim();
    }
    if (_notesCtrl.text.trim().isNotEmpty) {
      map['notes'] = _notesCtrl.text.trim();
    }

    var matIndex = 0;
    for (final m in _materials) {
      final name = m.name.text.trim();
      if (name.isEmpty) continue;
      map['materials_used[$matIndex][name]'] = name;
      final qty = m.quantity.text.trim();
      if (qty.isNotEmpty) {
        map['materials_used[$matIndex][quantity]'] = qty;
      }
      matIndex++;
    }

    if (_newPhotoPaths.isNotEmpty) {
      final files = <MultipartFile>[];
      for (final path in _newPhotoPaths) {
        files.add(
          await MultipartFile.fromFile(path, filename: path.split('/').last),
        );
      }
      map['photos[]'] = files;
    }

    if (_isEdit) {
      map['_method'] = 'PUT';
    }

    setState(() => _submitting = true);
    try {
      final api = ref.read(apiClientProvider);
      final path = _isEdit ? '/labor/logs/${widget.existingLog!['id']}' : '/labor/logs';
      await api.uploadFile(path, data: FormData.fromMap(map));
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _isEdit
                ? (isSwahili
                      ? 'Kumbukumbu imesasishwa.'
                      : 'Work log updated.')
                : (isSwahili
                      ? 'Kumbukumbu imeundwa.'
                      : 'Work log created.'),
          ),
        ),
      );
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _submitting = false);
      _snack(_errorMessage(e, isSwahili));
    }
  }

  void _snack(String m) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(m)));
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final referenceAsync = ref.watch(_laborLogFormReferenceProvider);

    final contracts =
        referenceAsync.valueOrNull?['contracts'] as List? ?? const [];
    final weatherOptions =
        referenceAsync.valueOrNull?['weather_options'] as List? ?? const [];

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isEdit
              ? (isSwahili ? 'Hariri Kumbukumbu' : 'Edit Work Log')
              : (isSwahili ? 'Rekodi Kazi' : 'Log Work'),
        ),
      ),
      body: referenceAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.error_outline,
                  size: 64,
                  color: AppColors.error,
                ),
                const SizedBox(height: 16),
                Text(
                  '$error',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: () =>
                      ref.invalidate(_laborLogFormReferenceProvider),
                  icon: const Icon(Icons.refresh),
                  label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
                ),
              ],
            ),
          ),
        ),
        data: (_) => Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _label(isSwahili ? 'Mkataba' : 'Contract', isDarkMode),
              const SizedBox(height: 6),
              if (_isEdit)
                _readonlyField(
                  '${widget.existingLog?['contract']?['contract_number'] ?? '-'}'
                  '${widget.existingLog?['contract']?['artisan_name'] != null ? ' • ${widget.existingLog!['contract']['artisan_name']}' : ''}',
                  isDarkMode,
                )
              else
                DropdownButtonFormField<int?>(
                  value: _contractId,
                  isExpanded: true,
                  decoration: _fieldDecoration(isDarkMode),
                  hint: Text(
                    isSwahili ? 'Chagua mkataba' : 'Select a contract',
                  ),
                  items: contracts.map((c) {
                    final contract = Map<String, dynamic>.from(c as Map);
                    return DropdownMenuItem<int?>(
                      value: contract['id'] as int?,
                      child: Text(
                        '${contract['contract_number']}'
                        '${contract['artisan_name'] != null ? ' - ${contract['artisan_name']}' : ''}',
                        overflow: TextOverflow.ellipsis,
                      ),
                    );
                  }).toList(),
                  validator: (v) => v == null
                      ? (isSwahili ? 'Inahitajika' : 'Required')
                      : null,
                  onChanged: (v) => setState(() => _contractId = v),
                ),
              const SizedBox(height: 16),
              _label(isSwahili ? 'Tarehe' : 'Date', isDarkMode),
              const SizedBox(height: 6),
              InkWell(
                onTap: _pickDate,
                borderRadius: BorderRadius.circular(12),
                child: InputDecorator(
                  decoration: _fieldDecoration(isDarkMode),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        DateFormat('EEE, MMM d, yyyy').format(_logDate),
                        style: TextStyle(
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      Icon(
                        Icons.calendar_today,
                        size: 18,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textHint,
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              _label(isSwahili ? 'Kazi Iliyofanywa' : 'Work Done', isDarkMode),
              const SizedBox(height: 6),
              TextFormField(
                controller: _workDoneCtrl,
                maxLines: 4,
                decoration: _fieldDecoration(
                  isDarkMode,
                  hint: isSwahili
                      ? 'Eleza kazi iliyofanywa (angalau herufi 10)'
                      : 'Describe the work done (min 10 characters)',
                ),
                validator: (v) {
                  final t = v?.trim() ?? '';
                  if (t.isEmpty) {
                    return isSwahili ? 'Inahitajika' : 'Required';
                  }
                  if (t.length < 10) {
                    return isSwahili
                        ? 'Angalau herufi 10'
                        : 'At least 10 characters';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _label(
                          isSwahili ? 'Wafanyakazi' : 'Workers',
                          isDarkMode,
                        ),
                        const SizedBox(height: 6),
                        TextFormField(
                          controller: _workersCtrl,
                          keyboardType: TextInputType.number,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                          ],
                          decoration: _fieldDecoration(isDarkMode, hint: '0'),
                          validator: (v) {
                            final n = int.tryParse(v?.trim() ?? '');
                            if (n == null || n < 1) {
                              return isSwahili ? 'Angalau 1' : 'Min 1';
                            }
                            return null;
                          },
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _label(isSwahili ? 'Masaa' : 'Hours', isDarkMode),
                        const SizedBox(height: 6),
                        TextFormField(
                          controller: _hoursCtrl,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          decoration: _fieldDecoration(isDarkMode, hint: '0.0'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _label(
                isSwahili ? 'Asilimia ya Maendeleo' : 'Progress %',
                isDarkMode,
              ),
              const SizedBox(height: 6),
              TextFormField(
                controller: _progressCtrl,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                decoration: _fieldDecoration(isDarkMode, hint: '0 - 100'),
                validator: (v) {
                  final t = v?.trim() ?? '';
                  if (t.isEmpty) return null;
                  final n = double.tryParse(t);
                  if (n == null || n < 0 || n > 100) {
                    return isSwahili ? '0 hadi 100' : '0 to 100';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 16),
              _label(isSwahili ? 'Hali ya Hewa' : 'Weather', isDarkMode),
              const SizedBox(height: 6),
              DropdownButtonFormField<String?>(
                value: _weather,
                isExpanded: true,
                decoration: _fieldDecoration(isDarkMode),
                hint: Text(isSwahili ? 'Hiari' : 'Optional'),
                items: [
                  DropdownMenuItem<String?>(
                    value: null,
                    child: Text(isSwahili ? 'Hakuna' : 'None'),
                  ),
                  ...weatherOptions.map((w) {
                    final opt = Map<String, dynamic>.from(w as Map);
                    return DropdownMenuItem<String?>(
                      value: opt['value'] as String?,
                      child: Text(opt['label'] as String? ?? ''),
                    );
                  }),
                ],
                onChanged: (v) => setState(() => _weather = v),
              ),
              const SizedBox(height: 16),
              _label(isSwahili ? 'Changamoto' : 'Challenges', isDarkMode),
              const SizedBox(height: 6),
              TextFormField(
                controller: _challengesCtrl,
                maxLines: 2,
                decoration: _fieldDecoration(
                  isDarkMode,
                  hint: isSwahili ? 'Hiari' : 'Optional',
                ),
              ),
              const SizedBox(height: 16),
              _label(isSwahili ? 'Maelezo' : 'Notes', isDarkMode),
              const SizedBox(height: 6),
              TextFormField(
                controller: _notesCtrl,
                maxLines: 2,
                decoration: _fieldDecoration(
                  isDarkMode,
                  hint: isSwahili ? 'Hiari' : 'Optional',
                ),
              ),
              const SizedBox(height: 20),
              _materialsSection(isSwahili, isDarkMode),
              const SizedBox(height: 20),
              _photosSection(isSwahili, isDarkMode),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _submitting ? null : () => _submit(isSwahili),
                  icon: _submitting
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.check),
                  label: Text(
                    _isEdit
                        ? (isSwahili ? 'Sasisha' : 'Update')
                        : (isSwahili ? 'Hifadhi' : 'Save'),
                  ),
                  style: FilledButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                ),
              ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }

  Widget _materialsSection(bool isSwahili, bool isDarkMode) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _label(isSwahili ? 'Vifaa Vilivyotumika' : 'Materials Used', isDarkMode),
            TextButton.icon(
              onPressed: _addMaterial,
              icon: const Icon(Icons.add, size: 18),
              label: Text(isSwahili ? 'Ongeza' : 'Add'),
            ),
          ],
        ),
        for (var i = 0; i < _materials.length; i++)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Row(
              children: [
                Expanded(
                  flex: 3,
                  child: TextFormField(
                    controller: _materials[i].name,
                    decoration: _fieldDecoration(
                      isDarkMode,
                      hint: isSwahili ? 'Jina' : 'Name',
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  flex: 2,
                  child: TextFormField(
                    controller: _materials[i].quantity,
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    decoration: _fieldDecoration(
                      isDarkMode,
                      hint: isSwahili ? 'Kiasi' : 'Qty',
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(
                    Icons.remove_circle_outline,
                    color: AppColors.error,
                  ),
                  onPressed: () => _removeMaterial(i),
                ),
              ],
            ),
          ),
      ],
    );
  }

  Widget _photosSection(bool isSwahili, bool isDarkMode) {
    final tiles = <Widget>[];

    for (final photo in _existingPhotos) {
      tiles.add(
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Stack(
            children: [
              Positioned.fill(
                child: Image.network(
                  _resolvePhotoUrl(photo),
                  fit: BoxFit.cover,
                  errorBuilder: (_, _, _) => Container(
                    color: isDarkMode
                        ? const Color(0xFF252540)
                        : Colors.grey[200],
                    child: const Icon(
                      Icons.broken_image_outlined,
                      color: Colors.grey,
                    ),
                  ),
                ),
              ),
              Positioned(
                bottom: 2,
                left: 2,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 4,
                    vertical: 1,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    isSwahili ? 'Iliyopo' : 'Saved',
                    style: const TextStyle(color: Colors.white, fontSize: 9),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    for (var i = 0; i < _newPhotoPaths.length; i++) {
      tiles.add(
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Stack(
            children: [
              Positioned.fill(
                child: Image.file(File(_newPhotoPaths[i]), fit: BoxFit.cover),
              ),
              Positioned(
                top: 2,
                right: 2,
                child: GestureDetector(
                  onTap: () =>
                      setState(() => _newPhotoPaths.removeAt(i)),
                  child: const CircleAvatar(
                    radius: 11,
                    backgroundColor: Colors.black54,
                    child: Icon(Icons.close, size: 14, color: Colors.white),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    tiles.add(
      InkWell(
        onTap: () => _addPhoto(isSwahili),
        borderRadius: BorderRadius.circular(8),
        child: Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: isDarkMode ? Colors.white24 : Colors.grey.shade300,
            ),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.add_a_photo_outlined,
                color: isDarkMode ? Colors.white54 : AppColors.textHint,
              ),
              const SizedBox(height: 4),
              Text(
                isSwahili ? 'Ongeza' : 'Add',
                style: TextStyle(
                  fontSize: 11,
                  color: isDarkMode ? Colors.white54 : AppColors.textHint,
                ),
              ),
            ],
          ),
        ),
      ),
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(isSwahili ? 'Picha' : 'Photos', isDarkMode),
        const SizedBox(height: 8),
        GridView.count(
          crossAxisCount: 3,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 8,
          crossAxisSpacing: 8,
          children: tiles,
        ),
      ],
    );
  }

  Widget _label(String text, bool isDarkMode) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
      ),
    );
  }

  Widget _readonlyField(String value, bool isDarkMode) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        value,
        style: TextStyle(
          color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
        ),
      ),
    );
  }

  InputDecoration _fieldDecoration(bool isDarkMode, {String? hint}) {
    return InputDecoration(
      hintText: hint,
      filled: true,
      fillColor: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      contentPadding: const EdgeInsets.symmetric(
        horizontal: 14,
        vertical: 12,
      ),
    );
  }
}
