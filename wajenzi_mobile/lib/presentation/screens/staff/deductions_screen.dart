import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _deductionsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/deductions');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _deductionRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/deductions/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class DeductionsScreen extends ConsumerWidget {
  const DeductionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final deductionsAsync = ref.watch(_deductionsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Makato' : 'Deductions'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_deductionsProvider),
        child: deductionsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _DeductionErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_deductionsProvider),
          ),
          data: (deductions) {
            if (deductions.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.money_off_csred_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna makato' : 'No deductions found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: deductions.length + 1,
              itemBuilder: (context, index) {
                if (index == deductions.length) return const SizedBox(height: 90);
                final deduction = deductions[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    onTap: () => _showDeductionSheet(context, deduction, isDarkMode),
                    contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(Icons.money_off, color: AppColors.primary),
                    ),
                    title: Text(
                      deduction['name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${deduction['nature'] ?? '-'} • ${deduction['abbreviation'] ?? '-'}\n${deduction['registration_number'] ?? '-'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDeductionSheet(context, deduction, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, deduction: deduction);
                        } else if (value == 'delete') {
                          _deleteDeduction(context, ref, deduction);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
                      ],
                    ),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? deduction}) async {
    final refs = await ref.read(_deductionRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.88,
        child: _DeductionFormSheet(refs: refs, deduction: deduction),
      ),
    );
    if (result == true) ref.invalidate(_deductionsProvider);
  }

  Future<void> _deleteDeduction(BuildContext context, WidgetRef ref, Map<String, dynamic> deduction) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Deduction' : 'Delete Deduction'),
        content: Text(isSwahili ? 'Je, unataka kufuta ${deduction['name']}?' : 'Delete ${deduction['name']}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/deductions/${deduction['id']}');
      ref.invalidate(_deductionsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Deduction imefutwa' : 'Deduction deleted'),
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

void _showDeductionSheet(BuildContext context, Map<String, dynamic> deduction, bool isDarkMode) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.58,
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
                      deduction['name']?.toString() ?? 'Deduction',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 18),
                    _DeductionDetailRow('Nature', deduction['nature']?.toString() ?? '-'),
                    _DeductionDetailRow('Abbreviation', deduction['abbreviation']?.toString() ?? '-'),
                    _DeductionDetailRow('Registration Number', deduction['registration_number']?.toString().trim().isEmpty == true ? '-' : deduction['registration_number']?.toString() ?? '-'),
                    _DeductionDetailRow('Description', deduction['description']?.toString().trim().isEmpty == true ? '-' : deduction['description']?.toString() ?? '-'),
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

class _DeductionFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? deduction;

  const _DeductionFormSheet({
    required this.refs,
    this.deduction,
  });

  @override
  ConsumerState<_DeductionFormSheet> createState() => _DeductionFormSheetState();
}

class _DeductionFormSheetState extends ConsumerState<_DeductionFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController =
      TextEditingController(text: widget.deduction?['name']?.toString() ?? '');
  late final TextEditingController _abbreviationController =
      TextEditingController(text: widget.deduction?['abbreviation']?.toString() ?? '');
  late final TextEditingController _descriptionController =
      TextEditingController(text: widget.deduction?['description']?.toString() ?? '');
  late final TextEditingController _registrationNumberController =
      TextEditingController(text: widget.deduction?['registration_number']?.toString() ?? '');

  String? _nature;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _nature = widget.deduction?['nature']?.toString();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _abbreviationController.dispose();
    _descriptionController.dispose();
    _registrationNumberController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final natures = _toNameList(widget.refs['natures']);

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
                          widget.deduction == null
                              ? (isSwahili ? 'Deduction Mpya' : 'New Deduction')
                              : (isSwahili ? 'Hariri Deduction' : 'Edit Deduction'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _dropdownNature(isDarkMode, natures),
                        const SizedBox(height: 12),
                        _input(_nameController, isSwahili ? 'Deduction Name *' : 'Deduction Name *', isDarkMode),
                        const SizedBox(height: 12),
                        _input(_abbreviationController, isSwahili ? 'Abbreviation *' : 'Abbreviation *', isDarkMode),
                        const SizedBox(height: 12),
                        _input(_descriptionController, isSwahili ? 'Description' : 'Description', isDarkMode, required: false, maxLines: 4),
                        const SizedBox(height: 12),
                        _input(_registrationNumberController, isSwahili ? 'Registration Number' : 'Registration Number', isDarkMode, required: false),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.deduction == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      validator: required ? (value) => value == null || value.trim().isEmpty ? 'Required' : null : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dropdownNature(bool isDarkMode, List<String> natures) {
    return DropdownButtonFormField<String>(
      isExpanded: true,
      value: natures.contains(_nature) ? _nature : null,
      validator: (selected) => selected == null || selected.isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: 'Nature *',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: natures
          .map((item) => DropdownMenuItem<String>(
                value: item,
                child: Text(item, overflow: TextOverflow.ellipsis),
              ))
          .toList(),
      onChanged: (value) => setState(() => _nature = value),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'nature': _nature,
        'name': _nameController.text.trim(),
        'abbreviation': _abbreviationController.text.trim(),
        'description': _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
        'registration_number': _registrationNumberController.text.trim().isEmpty ? null : _registrationNumberController.text.trim(),
      };

      if (widget.deduction == null) {
        await api.post('/deductions', data: data);
      } else {
        await api.put('/deductions/${widget.deduction!['id']}', data: data);
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

class _DeductionDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DeductionDetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(
              label,
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? 'N/A' : value,
              style: const TextStyle(fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }
}

class _DeductionErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _DeductionErrorView({
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

List<String> _toNameList(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => item['name']?.toString() ?? '')
      .where((item) => item.isNotEmpty)
      .toList();
}
