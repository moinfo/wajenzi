import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _accountTypesProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/account-types');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];

  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

String _accountTypeErrorMessage(Object error, bool isSwahili) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }
    }
  }

  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class AccountTypesScreen extends ConsumerWidget {
  const AccountTypesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final typesAsync = ref.watch(_accountTypesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Aina za Akaunti' : 'Account Types'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            tooltip: isSwahili ? 'Ongeza' : 'Add',
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_accountTypesProvider),
        child: typesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _AccountTypesErrorView(
            message: _accountTypeErrorMessage(error, isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_accountTypesProvider),
          ),
          data: (types) {
            if (types.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.category_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna aina za akaunti zilizopatikana' : 'No account types found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: types.length + 1,
              itemBuilder: (context, index) {
                if (index == types.length) {
                  return const SizedBox(height: 80);
                }

                final type = types[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    isThreeLine: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                      child: const Icon(Icons.account_tree, color: AppColors.primary),
                    ),
                    title: Text(
                      '${type['code'] ?? ''} ${type['type'] ?? ''}'.trim(),
                      style: const TextStyle(fontWeight: FontWeight.w700),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        '${isSwahili ? 'Normal Balance' : 'Normal Balance'}: ${type['normal_balance'] ?? '-'}\n${isSwahili ? 'Charts' : 'Charts'}: ${type['charts_count'] ?? 0}',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showTypeDetails(context, type, isSwahili, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, type: type);
                        } else if (value == 'delete') {
                          _deleteType(context, ref, type);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem<String>(
                          value: 'view',
                          child: Text(isSwahili ? 'Tazama' : 'View'),
                        ),
                        PopupMenuItem<String>(
                          value: 'edit',
                          child: Text(isSwahili ? 'Hariri' : 'Edit'),
                        ),
                        PopupMenuItem<String>(
                          value: 'delete',
                          child: Text(isSwahili ? 'Futa' : 'Delete'),
                        ),
                      ],
                    ),
                    onTap: () => _showTypeDetails(context, type, isSwahili, isDarkMode),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? type}) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.7,
        child: _AccountTypeFormSheet(type: type),
      ),
    );

    if (result == true) {
      ref.invalidate(_accountTypesProvider);
    }
  }

  Future<void> _deleteType(BuildContext context, WidgetRef ref, Map<String, dynamic> type) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Aina ya Akaunti' : 'Delete Account Type'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta ${type['type']}?'
              : 'Delete ${type['type']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    try {
      await ref.read(apiClientProvider).delete('/account-types/${type['id']}');
      ref.invalidate(_accountTypesProvider);

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Aina ya akaunti imefutwa' : 'Account type deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_accountTypeErrorMessage(error, isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showTypeDetails(BuildContext context, Map<String, dynamic> type, bool isSwahili, bool isDarkMode) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.55,
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
                        '${type['code'] ?? ''} ${type['type'] ?? ''}'.trim(),
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 16),
                      _detailLine(isSwahili ? 'Type' : 'Type', type['type']),
                      _detailLine(isSwahili ? 'Code' : 'Code', type['code']),
                      _detailLine(isSwahili ? 'Normal Balance' : 'Normal Balance', type['normal_balance']),
                      _detailLine(isSwahili ? 'Charts Count' : 'Charts Count', type['charts_count']),
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

class _AccountTypeFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? type;

  const _AccountTypeFormSheet({this.type});

  @override
  ConsumerState<_AccountTypeFormSheet> createState() => _AccountTypeFormSheetState();
}

class _AccountTypeFormSheetState extends ConsumerState<_AccountTypeFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _typeController = TextEditingController(text: widget.type?['type']?.toString() ?? '');
  late final TextEditingController _codeController = TextEditingController(text: widget.type?['code']?.toString() ?? '');
  late final TextEditingController _normalBalanceController = TextEditingController(text: widget.type?['normal_balance']?.toString() ?? '');
  bool _saving = false;

  @override
  void dispose() {
    _typeController.dispose();
    _codeController.dispose();
    _normalBalanceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

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
                          widget.type == null
                              ? (isSwahili ? 'Aina Mpya ya Akaunti' : 'New Account Type')
                              : (isSwahili ? 'Hariri Aina ya Akaunti' : 'Edit Account Type'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _input(_typeController, isSwahili ? 'Type *' : 'Type *', isDarkMode),
                        const SizedBox(height: 12),
                        _input(_codeController, isSwahili ? 'Code *' : 'Code *', isDarkMode),
                        const SizedBox(height: 12),
                        _input(_normalBalanceController, isSwahili ? 'Normal Balance *' : 'Normal Balance *', isDarkMode),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.type == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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

  Widget _input(TextEditingController controller, String label, bool isDarkMode) {
    return TextFormField(
      controller: controller,
      validator: (value) => value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'type': _typeController.text.trim(),
        'code': _codeController.text.trim(),
        'normal_balance': _normalBalanceController.text.trim(),
      };

      if (widget.type == null) {
        await api.post('/account-types', data: data);
      } else {
        await api.put('/account-types/${widget.type!['id']}', data: data);
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_accountTypeErrorMessage(error, ref.read(isSwahiliProvider))),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }
}

class _AccountTypesErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _AccountTypesErrorView({
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
