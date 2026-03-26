import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _chartAccountUsagesProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/chart-account-usages');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _chartAccountUsageRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/chart-account-usages/reference-data');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};
  return data['data'] is Map ? Map<String, dynamic>.from(data['data'] as Map) : const <String, dynamic>{};
});

String _usageErrorMessage(Object error, bool isSwahili) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) return message;
    }
  }
  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class ChartAccountUsagesScreen extends ConsumerWidget {
  const ChartAccountUsagesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final usagesAsync = ref.watch(_chartAccountUsagesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Matumizi ya Akaunti' : 'Chart Account Usages'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_chartAccountUsagesProvider),
        child: usagesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _UsagesErrorView(
            message: _usageErrorMessage(error, isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_chartAccountUsagesProvider),
          ),
          data: (usages) {
            if (usages.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.rule_folder_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna matumizi yaliyopatikana' : 'No chart account usages found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: usages.length + 1,
              itemBuilder: (context, index) {
                if (index == usages.length) return const SizedBox(height: 80);
                final usage = usages[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    isThreeLine: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                      child: const Icon(Icons.rule, color: AppColors.primary),
                    ),
                    title: Text(
                      usage['name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${usage['chart_account_code'] ?? '-'} - ${usage['chart_account_name'] ?? '-'}\n${usage['description'] ?? '-'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDetails(context, usage, isSwahili, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, usage: usage);
                        } else if (value == 'delete') {
                          _deleteUsage(context, ref, usage);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem<String>(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        PopupMenuItem<String>(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        PopupMenuItem<String>(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
                      ],
                    ),
                    onTap: () => _showDetails(context, usage, isSwahili, isDarkMode),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? usage}) async {
    final refs = await ref.read(_chartAccountUsageRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.82,
        child: _ChartAccountUsageFormSheet(refs: refs, usage: usage),
      ),
    );
    if (result == true) ref.invalidate(_chartAccountUsagesProvider);
  }

  Future<void> _deleteUsage(BuildContext context, WidgetRef ref, Map<String, dynamic> usage) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Matumizi' : 'Delete Usage'),
        content: Text(
          isSwahili ? 'Je, unataka kufuta ${usage['name']}?' : 'Delete ${usage['name']}?',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/chart-account-usages/${usage['id']}');
      ref.invalidate(_chartAccountUsagesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Matumizi yamefutwa' : 'Usage deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_usageErrorMessage(error, isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(BuildContext context, Map<String, dynamic> usage, bool isSwahili, bool isDarkMode) {
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
                        usage['name']?.toString() ?? '-',
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 16),
                      _detailLine(isSwahili ? 'Name' : 'Name', usage['name']),
                      _detailLine(isSwahili ? 'Chart Account' : 'Chart Account', '${usage['chart_account_code'] ?? '-'} - ${usage['chart_account_name'] ?? '-'}'),
                      _detailLine(isSwahili ? 'Description' : 'Description', usage['description']),
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

class _ChartAccountUsageFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? usage;

  const _ChartAccountUsageFormSheet({
    required this.refs,
    this.usage,
  });

  @override
  ConsumerState<_ChartAccountUsageFormSheet> createState() => _ChartAccountUsageFormSheetState();
}

class _ChartAccountUsageFormSheetState extends ConsumerState<_ChartAccountUsageFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController = TextEditingController(text: widget.usage?['name']?.toString() ?? '');
  late final TextEditingController _descriptionController = TextEditingController(text: widget.usage?['description']?.toString() ?? '');
  int? _chartAccountId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _chartAccountId = _toNullableInt(widget.usage?['charts_account_id']);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final accounts = _toMaps(widget.refs['charts_accounts']);

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
                          widget.usage == null
                              ? (isSwahili ? 'Matumizi Mapya' : 'New Usage')
                              : (isSwahili ? 'Hariri Matumizi' : 'Edit Usage'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _input(_nameController, isSwahili ? 'Name *' : 'Name *', isDarkMode),
                        const SizedBox(height: 12),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Chart of Account *' : 'Chart of Account *',
                          items: accounts.map((e) => {'id': e['id'], 'name': '${e['code']} - ${e['account_name']}'}).toList(),
                          value: _chartAccountId,
                          onChanged: (value) => setState(() => _chartAccountId = value),
                        ),
                        const SizedBox(height: 12),
                        _input(_descriptionController, isSwahili ? 'Description' : 'Description', isDarkMode, required: false, maxLines: 4),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.usage == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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

  Widget _input(TextEditingController controller, String label, bool isDarkMode, {bool required = true, int maxLines = 1}) {
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

  Widget _dropdown({
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'name': _nameController.text.trim(),
        'charts_account_id': _chartAccountId,
        'description': _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
      };

      if (widget.usage == null) {
        await api.post('/chart-account-usages', data: data);
      } else {
        await api.put('/chart-account-usages/${widget.usage!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_usageErrorMessage(error, ref.read(isSwahiliProvider))),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _UsagesErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _UsagesErrorView({
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
