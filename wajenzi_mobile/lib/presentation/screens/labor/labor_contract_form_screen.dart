import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _laborContractFormReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/contracts/reference-data');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class _PhaseInput {
  final TextEditingController nameController;
  final TextEditingController percentController;
  final TextEditingController descController;

  _PhaseInput({String? name, String? percent, String? desc})
      : nameController = TextEditingController(text: name ?? ''),
        percentController = TextEditingController(text: percent ?? ''),
        descController = TextEditingController(text: desc ?? '');

  void dispose() {
    nameController.dispose();
    percentController.dispose();
    descController.dispose();
  }
}

class LaborContractFormScreen extends ConsumerStatefulWidget {
  /// When null → create-from-request mode. When provided → edit mode.
  final Map<String, dynamic>? existingContract;

  const LaborContractFormScreen({super.key, this.existingContract});

  @override
  ConsumerState<LaborContractFormScreen> createState() =>
      _LaborContractFormScreenState();
}

class _LaborContractFormScreenState
    extends ConsumerState<LaborContractFormScreen> {
  final _formKey = GlobalKey<FormState>();

  int? _requestId;
  int? _supervisorId;
  DateTime? _startDate;
  DateTime? _endDate;
  final _scopeController = TextEditingController();
  final _termsController = TextEditingController();
  final _totalController = TextEditingController();
  final List<_PhaseInput> _phases = [];

  bool _submitting = false;

  bool get _isEdit => widget.existingContract != null;

  @override
  void initState() {
    super.initState();
    final c = widget.existingContract;
    if (c != null) {
      _requestId = (c['labor_request'] as Map?)?['id'] as int?;
      _supervisorId = (c['supervisor'] as Map?)?['id'] as int?;
      _startDate = DateTime.tryParse(c['start_date']?.toString() ?? '');
      _endDate = DateTime.tryParse(c['end_date']?.toString() ?? '');
      _scopeController.text = c['scope_of_work']?.toString() ?? '';
      _termsController.text = c['terms_conditions']?.toString() ?? '';
      _totalController.text = _toDouble(c['total_amount']).toStringAsFixed(2);
      final phases = (c['payment_phases'] as List? ?? const []).cast<dynamic>();
      for (final p in phases) {
        final phase = Map<String, dynamic>.from(p as Map);
        _phases.add(_PhaseInput(
          name: phase['phase_name']?.toString(),
          percent: _toDouble(phase['percentage']).toStringAsFixed(0),
          desc: phase['milestone_description']?.toString(),
        ));
      }
    }
  }

  @override
  void dispose() {
    _scopeController.dispose();
    _termsController.dispose();
    _totalController.dispose();
    for (final p in _phases) {
      p.dispose();
    }
    super.dispose();
  }

  void _showMessage(String message, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error ? AppColors.error : null,
      ),
    );
  }

  String _dioMessage(DioException e, bool isSwahili) {
    final data = e.response?.data;
    if (data is Map) {
      if (data['errors'] is Map) {
        final errors = (data['errors'] as Map).values;
        if (errors.isNotEmpty && errors.first is List) {
          return (errors.first as List).first.toString();
        }
      }
      if (data['message'] != null) return data['message'].toString();
    }
    return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
  }

  Future<void> _pickDate({required bool isStart}) async {
    final initial = isStart
        ? (_startDate ?? DateTime.now())
        : (_endDate ?? _startDate ?? DateTime.now());
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked != null) {
      setState(() {
        if (isStart) {
          _startDate = picked;
        } else {
          _endDate = picked;
        }
      });
    }
  }

  void _addPhase() {
    setState(() => _phases.add(_PhaseInput()));
  }

  void _removePhase(int index) {
    setState(() {
      _phases.removeAt(index).dispose();
    });
  }

  List<Map<String, dynamic>>? _buildPhasesPayload() {
    if (_phases.isEmpty) return null;
    final result = <Map<String, dynamic>>[];
    for (var i = 0; i < _phases.length; i++) {
      final p = _phases[i];
      result.add({
        'phase_number': i + 1,
        'phase_name': p.nameController.text.trim(),
        'percentage':
            double.tryParse(p.percentController.text.trim()) ?? 0,
        'milestone_description': p.descController.text.trim().isEmpty
            ? null
            : p.descController.text.trim(),
      });
    }
    return result;
  }

  Future<void> _submit() async {
    final isSwahili = ref.read(isSwahiliProvider);
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_startDate == null || _endDate == null) {
      _showMessage(
        isSwahili ? 'Chagua tarehe za mkataba' : 'Select contract dates',
        error: true,
      );
      return;
    }
    if (!_endDate!.isAfter(_startDate!)) {
      _showMessage(
        isSwahili
            ? 'Tarehe ya mwisho lazima iwe baada ya kuanza'
            : 'End date must be after start date',
        error: true,
      );
      return;
    }
    if (!_isEdit && _requestId == null) {
      _showMessage(
        isSwahili ? 'Chagua ombi' : 'Select a request',
        error: true,
      );
      return;
    }

    // Validate phase percentages if any.
    final phasesPayload = _buildPhasesPayload();
    if (phasesPayload != null) {
      final total = phasesPayload.fold<double>(
          0, (sum, p) => sum + (p['percentage'] as double));
      if (total > 100.0001) {
        _showMessage(
          isSwahili
              ? 'Jumla ya asilimia za awamu zizidi 100'
              : 'Phase percentages exceed 100%',
          error: true,
        );
        return;
      }
    }

    setState(() => _submitting = true);
    try {
      final api = ref.read(apiClientProvider);
      final dateFmt = DateFormat('yyyy-MM-dd');
      Response<dynamic> response;
      if (_isEdit) {
        final data = <String, dynamic>{
          'start_date': dateFmt.format(_startDate!),
          'end_date': dateFmt.format(_endDate!),
          'scope_of_work': _scopeController.text.trim(),
          'supervisor_id': _supervisorId,
          'terms_conditions': _termsController.text.trim().isEmpty
              ? null
              : _termsController.text.trim(),
        };
        if (phasesPayload != null) {
          data['phases'] = phasesPayload;
        }
        response = await api.put(
          '/labor/contracts/${widget.existingContract!['id']}',
          data: data,
        );
      } else {
        final data = <String, dynamic>{
          'labor_request_id': _requestId,
          'start_date': dateFmt.format(_startDate!),
          'end_date': dateFmt.format(_endDate!),
          'scope_of_work': _scopeController.text.trim(),
          'supervisor_id': _supervisorId,
          'total_amount': _totalController.text.trim(),
          'terms_conditions': _termsController.text.trim().isEmpty
              ? null
              : _termsController.text.trim(),
        };
        if (phasesPayload != null) {
          data['phases'] = phasesPayload;
        }
        response = await api.post('/labor/contracts', data: data);
      }

      final body = response.data as Map<String, dynamic>?;
      if (body != null && body['success'] == false) {
        _showMessage(body['message']?.toString() ?? 'Failed', error: true);
      } else {
        if (mounted) {
          _showMessage(
            _isEdit
                ? (isSwahili ? 'Mkataba umehaririwa' : 'Contract updated')
                : (isSwahili ? 'Mkataba umeundwa' : 'Contract created'),
          );
          Navigator.of(context).pop(true);
        }
      }
    } on DioException catch (e) {
      _showMessage(_dioMessage(e, isSwahili), error: true);
    } catch (e) {
      _showMessage('$e', error: true);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final referenceAsync = ref.watch(_laborContractFormReferenceProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isEdit
              ? (isSwahili ? 'Hariri Mkataba' : 'Edit Contract')
              : (isSwahili ? 'Mkataba Mpya' : 'New Contract'),
        ),
      ),
      body: referenceAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.error_outline,
                    size: 48, color: AppColors.error),
                const SizedBox(height: 12),
                Text('$error', textAlign: TextAlign.center),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: () => ref.invalidate(
                      _laborContractFormReferenceProvider),
                  icon: const Icon(Icons.refresh),
                  label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
                ),
              ],
            ),
          ),
        ),
        data: (reference) {
          final availableRequests =
              (reference['available_requests'] as List? ?? const [])
                  .cast<dynamic>();
          final supervisors =
              (reference['supervisors'] as List? ?? const []).cast<dynamic>();

          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (!_isEdit) ...[
                  _label(isSwahili ? 'Ombi' : 'Request', isDarkMode),
                  DropdownButtonFormField<int?>(
                    value: _requestId,
                    isExpanded: true,
                    decoration: _fieldDecoration(isDarkMode),
                    hint: Text(isSwahili ? 'Chagua ombi' : 'Select request'),
                    items: availableRequests.map((r) {
                      final req = Map<String, dynamic>.from(r as Map);
                      return DropdownMenuItem<int?>(
                        value: req['id'] as int?,
                        child: Text(
                          '${req['request_number'] ?? '-'} · ${req['artisan_name'] ?? '-'}',
                          overflow: TextOverflow.ellipsis,
                        ),
                      );
                    }).toList(),
                    onChanged: (value) {
                      setState(() {
                        _requestId = value;
                        final match = availableRequests.firstWhere(
                          (r) => (r as Map)['id'] == value,
                          orElse: () => null,
                        );
                        if (match != null) {
                          final amount =
                              _toDouble((match as Map)['final_amount']);
                          if (amount > 0) {
                            _totalController.text = amount.toStringAsFixed(2);
                          }
                          if (_scopeController.text.trim().isEmpty &&
                              match['project_name'] != null) {
                            _scopeController.text =
                                'Labor works for ${match['project_name']}';
                          }
                        }
                      });
                    },
                    validator: (v) => v == null
                        ? (isSwahili ? 'Chagua ombi' : 'Select a request')
                        : null,
                  ),
                  const SizedBox(height: 16),
                ] else ...[
                  _card(
                    isDarkMode,
                    child: Row(
                      children: [
                        const Icon(Icons.assignment_outlined, size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '${widget.existingContract!['contract_number'] ?? '-'}',
                            style: TextStyle(
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                _label(isSwahili ? 'Msimamizi' : 'Supervisor', isDarkMode),
                DropdownButtonFormField<int?>(
                  value: _supervisorId,
                  isExpanded: true,
                  decoration: _fieldDecoration(isDarkMode),
                  items: [
                    DropdownMenuItem<int?>(
                      value: null,
                      child: Text(isSwahili ? 'Hakuna' : 'None'),
                    ),
                    ...supervisors.map((s) {
                      final sup = Map<String, dynamic>.from(s as Map);
                      return DropdownMenuItem<int?>(
                        value: sup['id'] as int?,
                        child: Text(
                          sup['name']?.toString() ?? '-',
                          overflow: TextOverflow.ellipsis,
                        ),
                      );
                    }),
                  ],
                  onChanged: (value) => setState(() => _supervisorId = value),
                ),
                const SizedBox(height: 16),

                Row(
                  children: [
                    Expanded(
                      child: _dateField(
                        label: isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                        value: _startDate,
                        onTap: () => _pickDate(isStart: true),
                        isDarkMode: isDarkMode,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _dateField(
                        label: isSwahili ? 'Tarehe ya Mwisho' : 'End Date',
                        value: _endDate,
                        onTap: () => _pickDate(isStart: false),
                        isDarkMode: isDarkMode,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),

                _label(isSwahili ? 'Kiasi cha Jumla' : 'Total Amount',
                    isDarkMode),
                TextFormField(
                  controller: _totalController,
                  enabled: !_isEdit,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  decoration: _fieldDecoration(isDarkMode).copyWith(
                    prefixText: 'TZS ',
                    helperText: _isEdit
                        ? (isSwahili
                            ? 'Kiasi hakiwezi kubadilishwa'
                            : 'Amount is locked on edit')
                        : null,
                  ),
                  validator: (v) {
                    if (_isEdit) return null;
                    final parsed =
                        double.tryParse((v ?? '').replaceAll(',', ''));
                    if (parsed == null || parsed <= 0) {
                      return isSwahili
                          ? 'Weka kiasi sahihi'
                          : 'Enter a valid amount';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                _label(isSwahili ? 'Kazi' : 'Scope of Work', isDarkMode),
                TextFormField(
                  controller: _scopeController,
                  maxLines: 3,
                  decoration: _fieldDecoration(isDarkMode),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? (isSwahili ? 'Weka kazi' : 'Enter scope of work')
                      : null,
                ),
                const SizedBox(height: 16),

                _label(isSwahili ? 'Masharti (hiari)' : 'Terms (optional)',
                    isDarkMode),
                TextFormField(
                  controller: _termsController,
                  maxLines: 3,
                  decoration: _fieldDecoration(isDarkMode),
                ),
                const SizedBox(height: 20),

                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    _label(
                        isSwahili
                            ? 'Awamu za Malipo (hiari)'
                            : 'Payment Phases (optional)',
                        isDarkMode),
                    TextButton.icon(
                      onPressed: _addPhase,
                      icon: const Icon(Icons.add, size: 18),
                      label: Text(isSwahili ? 'Ongeza' : 'Add'),
                    ),
                  ],
                ),
                if (_phases.isEmpty)
                  Text(
                    isSwahili
                        ? 'Ikiachwa wazi, awamu za kawaida zitatengenezwa.'
                        : 'If left empty, default phases will be created.',
                    style: TextStyle(
                      fontSize: 12,
                      fontStyle: FontStyle.italic,
                      color: isDarkMode ? Colors.white54 : AppColors.textHint,
                    ),
                  )
                else
                  ...List.generate(_phases.length, (i) {
                    final p = _phases[i];
                    return _card(
                      isDarkMode,
                      margin: const EdgeInsets.only(bottom: 10),
                      child: Column(
                        children: [
                          Row(
                            children: [
                              Text(
                                '#${i + 1}',
                                style: TextStyle(
                                  fontWeight: FontWeight.w700,
                                  color: isDarkMode
                                      ? Colors.white
                                      : AppColors.textPrimary,
                                ),
                              ),
                              const Spacer(),
                              IconButton(
                                icon: const Icon(Icons.delete_outline,
                                    size: 20, color: AppColors.error),
                                onPressed: () => _removePhase(i),
                              ),
                            ],
                          ),
                          TextFormField(
                            controller: p.nameController,
                            decoration: _fieldDecoration(isDarkMode).copyWith(
                              labelText:
                                  isSwahili ? 'Jina la awamu' : 'Phase name',
                            ),
                            validator: (v) => (v == null || v.trim().isEmpty)
                                ? (isSwahili ? 'Lazima' : 'Required')
                                : null,
                          ),
                          const SizedBox(height: 10),
                          TextFormField(
                            controller: p.percentController,
                            keyboardType: const TextInputType.numberWithOptions(
                                decimal: true),
                            decoration: _fieldDecoration(isDarkMode).copyWith(
                              labelText: isSwahili ? 'Asilimia' : 'Percentage',
                              suffixText: '%',
                            ),
                            validator: (v) {
                              final parsed = double.tryParse((v ?? '').trim());
                              if (parsed == null ||
                                  parsed <= 0 ||
                                  parsed > 100) {
                                return isSwahili ? '0-100' : '0-100';
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 10),
                          TextFormField(
                            controller: p.descController,
                            decoration: _fieldDecoration(isDarkMode).copyWith(
                              labelText: isSwahili
                                  ? 'Maelezo (hiari)'
                                  : 'Milestone (optional)',
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                const SizedBox(height: 24),

                ElevatedButton(
                  onPressed: _submitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                  ),
                  child: _submitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text(
                          _isEdit
                              ? (isSwahili ? 'Hifadhi' : 'Save Changes')
                              : (isSwahili ? 'Unda Mkataba' : 'Create Contract'),
                          style: const TextStyle(
                              fontSize: 16, fontWeight: FontWeight.w600),
                        ),
                ),
                const SizedBox(height: 40),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _label(String text, bool isDarkMode) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
        ),
      ),
    );
  }

  InputDecoration _fieldDecoration(bool isDarkMode) {
    return InputDecoration(
      filled: true,
      fillColor: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
      contentPadding:
          const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
    );
  }

  Widget _dateField({
    required String label,
    required DateTime? value,
    required VoidCallback onTap,
    required bool isDarkMode,
  }) {
    return InkWell(
      onTap: onTap,
      child: InputDecorator(
        decoration: _fieldDecoration(isDarkMode).copyWith(
          labelText: label,
          suffixIcon: const Icon(Icons.calendar_today, size: 18),
        ),
        child: Text(
          value != null ? DateFormat('yyyy-MM-dd').format(value) : '-',
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }

  Widget _card(bool isDarkMode,
      {required Widget child, EdgeInsets? margin}) {
    return Container(
      width: double.infinity,
      margin: margin,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDarkMode ? const Color(0xFF252540) : Colors.grey.shade200,
        ),
      ),
      child: child,
    );
  }
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}
