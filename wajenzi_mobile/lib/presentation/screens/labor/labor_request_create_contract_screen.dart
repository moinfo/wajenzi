import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

/// Create a Labor Contract directly from an approved Labor Request.
///
/// POSTs to `/labor/contracts` with `labor_request_id` (+ dates, scope, amount,
/// optional supervisor / terms / phases). Pushed from the request detail screen;
/// pops `true` on success so the caller can refresh.
class LaborRequestCreateContractScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic> request;
  const LaborRequestCreateContractScreen({super.key, required this.request});

  @override
  ConsumerState<LaborRequestCreateContractScreen> createState() =>
      _LaborRequestCreateContractScreenState();
}

class _LaborRequestCreateContractScreenState
    extends ConsumerState<LaborRequestCreateContractScreen> {
  final _scopeController = TextEditingController();
  final _amountController = TextEditingController();
  final _termsController = TextEditingController();
  final _startController = TextEditingController();
  final _endController = TextEditingController();

  DateTime? _startDate;
  DateTime? _endDate;
  int? _supervisorId;
  bool _submitting = false;

  List<Map<String, dynamic>> _supervisors = const [];
  bool _loadingRefs = true;

  final List<_PhaseInput> _phases = [];

  @override
  void initState() {
    super.initState();
    // Prefill from the request.
    _scopeController.text =
        widget.request['work_description']?.toString() ?? '';
    final finalAmount = widget.request['final_amount'];
    if (finalAmount != null) {
      _amountController.text = finalAmount.toString();
    }
    _loadRefs();
  }

  Future<void> _loadRefs() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/labor/contracts/reference-data');
      final data = response.data['data'] as Map<String, dynamic>?;
      final sup = (data?['supervisors'] as List? ?? const [])
          .map((s) => Map<String, dynamic>.from(s as Map))
          .toList();
      if (!mounted) return;
      setState(() {
        _supervisors = sup;
        _loadingRefs = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingRefs = false);
    }
  }

  @override
  void dispose() {
    _scopeController.dispose();
    _amountController.dispose();
    _termsController.dispose();
    _startController.dispose();
    _endController.dispose();
    for (final p in _phases) {
      p.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Tengeneza Mkataba' : 'Create Contract'),
      ),
      body: _loadingRefs
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _fromRequestCard(isSwahili, isDarkMode),
                const SizedBox(height: 16),
                _dateField(
                  controller: _startController,
                  label: isSwahili ? 'Tarehe ya Kuanza *' : 'Start Date *',
                  selectedDate: _startDate,
                  onPicked: (d) => setState(() {
                    _startDate = d;
                    _startController.text = DateFormat('yyyy-MM-dd').format(d);
                  }),
                  isDarkMode: isDarkMode,
                ),
                _dateField(
                  controller: _endController,
                  label: isSwahili ? 'Tarehe ya Kumaliza *' : 'End Date *',
                  selectedDate: _endDate,
                  firstDate: _startDate,
                  onPicked: (d) => setState(() {
                    _endDate = d;
                    _endController.text = DateFormat('yyyy-MM-dd').format(d);
                  }),
                  isDarkMode: isDarkMode,
                ),
                _textField(
                  controller: _amountController,
                  label: isSwahili ? 'Jumla ya Kiasi *' : 'Total Amount *',
                  keyboardType: TextInputType.number,
                  isDarkMode: isDarkMode,
                ),
                _textField(
                  controller: _scopeController,
                  label: isSwahili ? 'Wigo wa Kazi *' : 'Scope of Work *',
                  maxLines: 3,
                  isDarkMode: isDarkMode,
                ),
                _supervisorDropdown(isSwahili, isDarkMode),
                _textField(
                  controller: _termsController,
                  label: isSwahili
                      ? 'Masharti na Vigezo'
                      : 'Terms & Conditions',
                  maxLines: 3,
                  isDarkMode: isDarkMode,
                ),
                const SizedBox(height: 8),
                _phasesSection(isSwahili, isDarkMode),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: _submitting
                        ? const CircularProgressIndicator(color: Colors.white)
                        : Text(
                            isSwahili ? 'Tengeneza Mkataba' : 'Create Contract',
                          ),
                  ),
                ),
                const SizedBox(height: 40),
              ],
            ),
    );
  }

  Widget _fromRequestCard(bool isSwahili, bool isDarkMode) {
    final project = widget.request['project'] as Map?;
    final artisan = widget.request['artisan'] as Map?;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili ? 'Kutoka Ombi' : 'From Request',
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            widget.request['request_number']?.toString() ?? '-',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            '${project?['project_name'] ?? '-'} • ${artisan?['name'] ?? (isSwahili ? 'Hakuna fundi' : 'No artisan')}',
            style: TextStyle(
              fontSize: 13,
              color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _phasesSection(bool isSwahili, bool isDarkMode) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              isSwahili ? 'Awamu za Malipo (hiari)' : 'Payment Phases (optional)',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const Spacer(),
            TextButton.icon(
              onPressed: () => setState(() {
                _phases.add(_PhaseInput(phaseNumber: _phases.length + 1));
              }),
              icon: const Icon(Icons.add, size: 18),
              label: Text(isSwahili ? 'Ongeza' : 'Add'),
            ),
          ],
        ),
        ..._phases.asMap().entries.map((entry) {
          final i = entry.key;
          final p = entry.value;
          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        '${isSwahili ? 'Awamu' : 'Phase'} ${i + 1}',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color:
                              isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(
                        Icons.delete_outline,
                        size: 20,
                        color: AppColors.error,
                      ),
                      onPressed: () => setState(() {
                        p.dispose();
                        _phases.removeAt(i);
                        for (var j = 0; j < _phases.length; j++) {
                          _phases[j].phaseNumber = j + 1;
                        }
                      }),
                    ),
                  ],
                ),
                TextField(
                  controller: p.nameController,
                  style: TextStyle(
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina la Awamu' : 'Phase Name',
                    isDense: true,
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: p.percentageController,
                  keyboardType: TextInputType.number,
                  style: TextStyle(
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Asilimia (%)' : 'Percentage (%)',
                    isDense: true,
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: p.descriptionController,
                  style: TextStyle(
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Maelezo ya Hatua (hiari)'
                        : 'Milestone Description (optional)',
                    isDense: true,
                  ),
                ),
              ],
            ),
          );
        }),
      ],
    );
  }

  Widget _supervisorDropdown(bool isSwahili, bool isDarkMode) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: DropdownButtonFormField<int?>(
      value: _supervisorId,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: isSwahili ? 'Msimamizi (hiari)' : 'Supervisor (optional)',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
      ),
      dropdownColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      items: [
        DropdownMenuItem<int?>(
          value: null,
          child: Text(isSwahili ? 'Hakuna' : 'None'),
        ),
        ..._supervisors.map(
          (s) => DropdownMenuItem<int?>(
            value: s['id'] as int?,
            child: Text(s['name']?.toString() ?? '-'),
          ),
        ),
      ],
      onChanged: (v) => setState(() => _supervisorId = v),
    ),
  );

  Widget _textField({
    required TextEditingController controller,
    required String label,
    int maxLines = 1,
    TextInputType? keyboardType,
    required bool isDarkMode,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      style: TextStyle(
        color: isDarkMode ? Colors.white : AppColors.textPrimary,
      ),
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
      ),
    ),
  );

  Widget _dateField({
    required TextEditingController controller,
    required String label,
    required DateTime? selectedDate,
    required Function(DateTime) onPicked,
    required bool isDarkMode,
    DateTime? firstDate,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: TextFormField(
      controller: controller,
      readOnly: true,
      style: TextStyle(
        color: isDarkMode ? Colors.white : AppColors.textPrimary,
      ),
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        suffixIcon: Icon(
          Icons.calendar_today,
          color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
        ),
      ),
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: selectedDate ?? DateTime.now(),
          firstDate: firstDate ?? DateTime(2020),
          lastDate: DateTime.now().add(const Duration(days: 730)),
        );
        if (picked != null) onPicked(picked);
      },
    ),
  );

  Future<void> _submit() async {
    final isSwahili = ref.read(isSwahiliProvider);

    if (_startDate == null) {
      _snack(isSwahili ? 'Chagua tarehe ya kuanza' : 'Select start date',
          isError: true);
      return;
    }
    if (_endDate == null) {
      _snack(isSwahili ? 'Chagua tarehe ya kumaliza' : 'Select end date',
          isError: true);
      return;
    }
    if (!_endDate!.isAfter(_startDate!)) {
      _snack(
        isSwahili
            ? 'Tarehe ya kumaliza lazima iwe baada ya kuanza'
            : 'End date must be after start date',
        isError: true,
      );
      return;
    }
    if (_scopeController.text.trim().isEmpty) {
      _snack(isSwahili ? 'Andika wigo wa kazi' : 'Enter scope of work',
          isError: true);
      return;
    }
    final amount = double.tryParse(_amountController.text.trim());
    if (amount == null || amount < 0) {
      _snack(isSwahili ? 'Kiasi si sahihi' : 'Valid total amount required',
          isError: true);
      return;
    }

    final phases = <Map<String, dynamic>>[];
    for (final p in _phases) {
      final name = p.nameController.text.trim();
      final pct = double.tryParse(p.percentageController.text.trim());
      if (name.isEmpty || pct == null) {
        _snack(
          isSwahili
              ? 'Jaza jina na asilimia kwa kila awamu'
              : 'Each phase needs a name and percentage',
          isError: true,
        );
        return;
      }
      phases.add({
        'phase_number': p.phaseNumber,
        'phase_name': name,
        'percentage': pct,
        if (p.descriptionController.text.trim().isNotEmpty)
          'milestone_description': p.descriptionController.text.trim(),
      });
    }

    setState(() => _submitting = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.post(
        '/labor/contracts',
        data: {
          'labor_request_id': widget.request['id'],
          'start_date': _startController.text,
          'end_date': _endController.text,
          'scope_of_work': _scopeController.text.trim(),
          'total_amount': amount,
          if (_supervisorId != null) 'supervisor_id': _supervisorId,
          if (_termsController.text.trim().isNotEmpty)
            'terms_conditions': _termsController.text.trim(),
          if (phases.isNotEmpty) 'phases': phases,
        },
      );
      if (!mounted) return;
      _snack(isSwahili ? 'Mkataba umetengenezwa' : 'Contract created');
      Navigator.of(context).pop(true);
    } on DioException catch (e) {
      _snack(_dioMessage(e), isError: true);
    } catch (e) {
      _snack('$e', isError: true);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  String _dioMessage(DioException e) {
    final data = e.response?.data;
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
      final msg = data['message']?.toString();
      if (msg != null && msg.isNotEmpty) return msg;
    }
    return e.message ?? 'Request failed';
  }

  void _snack(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? AppColors.error : AppColors.success,
      ),
    );
  }
}

class _PhaseInput {
  int phaseNumber;
  final TextEditingController nameController = TextEditingController();
  final TextEditingController percentageController = TextEditingController();
  final TextEditingController descriptionController = TextEditingController();

  _PhaseInput({required this.phaseNumber});

  void dispose() {
    nameController.dispose();
    percentageController.dispose();
    descriptionController.dispose();
  }
}
