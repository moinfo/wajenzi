import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _chartsOfAccountsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/charts-of-accounts');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};
  return data['data'] is Map ? Map<String, dynamic>.from(data['data'] as Map) : const <String, dynamic>{};
});

String _chartErrorMessage(Object error, bool isSwahili) {
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

class ChartsOfAccountsScreen extends ConsumerWidget {
  const ChartsOfAccountsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final chartsAsync = ref.watch(_chartsOfAccountsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Chati ya Akaunti' : 'Charts of Accounts'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_chartsOfAccountsProvider),
        child: chartsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ChartsErrorView(
            message: _chartErrorMessage(error, isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_chartsOfAccountsProvider),
          ),
          data: (payload) {
            final accountTypes = _toMaps(payload['account_types']);
            final accounts = _toMaps(payload['accounts']);

            if (accounts.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.account_tree_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna akaunti zilizopatikana' : 'No chart accounts found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                ...accountTypes.map((type) {
                  final typeAccounts = accounts.where((account) => _toInt(account['account_type']) == _toInt(type['id'])).toList();
                  if (typeAccounts.isEmpty) {
                    return const SizedBox.shrink();
                  }

                  return _AccountTypeSection(
                    type: type,
                    accounts: typeAccounts,
                    isSwahili: isSwahili,
                    onView: (account) => _showChartAccountDetails(context, account, isSwahili, isDarkMode),
                    onEdit: (account) => _openForm(context, ref, account: account),
                    onDelete: (account) => _deleteAccount(context, ref, account),
                  );
                }),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? account}) async {
    final payload = await ref.read(_chartsOfAccountsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.9,
        child: _ChartAccountFormSheet(
          payload: payload,
          account: account,
        ),
      ),
    );

    if (result == true) {
      ref.invalidate(_chartsOfAccountsProvider);
    }
  }

  Future<void> _deleteAccount(BuildContext context, WidgetRef ref, Map<String, dynamic> account) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Akaunti' : 'Delete Account'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta ${account['account_name']}?'
              : 'Delete ${account['account_name']}?',
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
      await ref.read(apiClientProvider).delete('/charts-of-accounts/${account['id']}');
      ref.invalidate(_chartsOfAccountsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Akaunti imefutwa' : 'Chart account deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_chartErrorMessage(error, isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showChartAccountDetails(BuildContext context, Map<String, dynamic> account, bool isSwahili, bool isDarkMode) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.72,
        child: _ChartAccountDetailSheet(
          account: account,
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
        ),
      ),
    );
  }
}

class _AccountTypeSection extends StatelessWidget {
  final Map<String, dynamic> type;
  final List<Map<String, dynamic>> accounts;
  final bool isSwahili;
  final void Function(Map<String, dynamic>) onView;
  final void Function(Map<String, dynamic>) onEdit;
  final void Function(Map<String, dynamic>) onDelete;

  const _AccountTypeSection({
    required this.type,
    required this.accounts,
    required this.isSwahili,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final rootAccounts = accounts.where((account) => _toInt(account['parent']) == 0).toList()
      ..sort((a, b) => (a['code'] ?? '').toString().compareTo((b['code'] ?? '').toString()));

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${type['code'] ?? ''} ${type['type'] ?? ''}'.trim(),
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 4),
            Text(
              'Normal Balance: ${type['normal_balance'] ?? '-'}',
              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 12),
            ...rootAccounts.map((account) => _AccountTreeTile(
                  account: account,
                  allAccounts: accounts,
                  level: 0,
                  isSwahili: isSwahili,
                  onView: onView,
                  onEdit: onEdit,
                  onDelete: onDelete,
                )),
          ],
        ),
      ),
    );
  }
}

class _AccountTreeTile extends StatelessWidget {
  final Map<String, dynamic> account;
  final List<Map<String, dynamic>> allAccounts;
  final int level;
  final bool isSwahili;
  final void Function(Map<String, dynamic>) onView;
  final void Function(Map<String, dynamic>) onEdit;
  final void Function(Map<String, dynamic>) onDelete;

  const _AccountTreeTile({
    required this.account,
    required this.allAccounts,
    required this.level,
    required this.isSwahili,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final id = _toInt(account['id']);
    final children = allAccounts.where((item) => _toInt(item['parent']) == id).toList()
      ..sort((a, b) => (a['code'] ?? '').toString().compareTo((b['code'] ?? '').toString()));

    final tile = Container(
      margin: EdgeInsets.only(left: level * 14.0, bottom: 8),
      decoration: BoxDecoration(
        color: level == 0 ? AppColors.primary.withValues(alpha: 0.05) : Colors.transparent,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: level == 0 ? AppColors.primary.withValues(alpha: 0.12) : Colors.transparent,
        ),
      ),
      child: ListTile(
        isThreeLine: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
        leading: CircleAvatar(
          radius: 18,
          backgroundColor: _statusColor(account['status']?.toString()).withValues(alpha: 0.14),
          child: Icon(Icons.account_balance_wallet_outlined, size: 18, color: _statusColor(account['status']?.toString())),
        ),
        title: Text(
          '${account['code'] ?? '-'} - ${account['account_name'] ?? '-'}',
          style: TextStyle(fontSize: level == 0 ? 14 : 13, fontWeight: level == 0 ? FontWeight.w700 : FontWeight.w500),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Text(
          'Status: ${_statusLabel(account['status']?.toString())}${_currencySuffix(account)}',
          style: const TextStyle(fontSize: 12),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: PopupMenuButton<String>(
          onSelected: (value) {
            if (value == 'view') {
              onView(account);
            } else if (value == 'edit') {
              onEdit(account);
            } else if (value == 'delete') {
              onDelete(account);
            }
          },
          itemBuilder: (_) => [
            PopupMenuItem<String>(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
            PopupMenuItem<String>(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
            PopupMenuItem<String>(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
          ],
        ),
        onTap: () => onView(account),
      ),
    );

    if (children.isEmpty) {
      return tile;
    }

    return ExpansionTile(
      tilePadding: EdgeInsets.zero,
      childrenPadding: EdgeInsets.zero,
      title: tile,
      children: children
          .map(
            (child) => _AccountTreeTile(
              account: child,
              allAccounts: allAccounts,
              level: level + 1,
              isSwahili: isSwahili,
              onView: onView,
              onEdit: onEdit,
              onDelete: onDelete,
            ),
          )
          .toList(),
    );
  }

  String _currencySuffix(Map<String, dynamic> account) {
    final name = (account['currency_name'] ?? account['currency'] ?? '').toString().trim();
    return name.isEmpty ? '' : ' - $name';
  }
}

class _ChartAccountFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> payload;
  final Map<String, dynamic>? account;

  const _ChartAccountFormSheet({
    required this.payload,
    this.account,
  });

  @override
  ConsumerState<_ChartAccountFormSheet> createState() => _ChartAccountFormSheetState();
}

class _ChartAccountFormSheetState extends ConsumerState<_ChartAccountFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _codeController = TextEditingController(text: widget.account?['code']?.toString() ?? '');
  late final TextEditingController _nameController = TextEditingController(text: widget.account?['account_name']?.toString() ?? '');
  late final TextEditingController _descriptionController = TextEditingController(text: widget.account?['description']?.toString() ?? '');
  int? _accountTypeId;
  int? _parentId;
  int? _currencyId;
  String _status = 'ACTIVE';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _accountTypeId = _toNullableInt(widget.account?['account_type']);
    _parentId = _toNullableInt(widget.account?['parent']);
    _currencyId = _toNullableInt(widget.account?['currency']);
    _status = (widget.account?['status']?.toString().trim().isNotEmpty ?? false)
        ? widget.account!['status'].toString()
        : 'ACTIVE';
  }

  @override
  void dispose() {
    _codeController.dispose();
    _nameController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final accountTypes = _toMaps(widget.payload['account_types']);
    final currencies = _toMaps(widget.payload['currencies']);
    final allAccounts = _toMaps(widget.payload['accounts']);
    final parentOptions = allAccounts.where((item) => _toInt(item['id']) != _toInt(widget.account?['id'])).toList();

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
                        widget.account == null
                            ? (isSwahili ? 'Akaunti Mpya' : 'New Chart Account')
                            : (isSwahili ? 'Hariri Akaunti' : 'Edit Chart Account'),
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 20),
                      _dropdown(
                        label: 'Account Type *',
                        items: accountTypes.map((e) => {'id': e['id'], 'name': '${e['type']} (${e['code']})'}).toList(),
                        value: _accountTypeId,
                        onChanged: (value) => setState(() => _accountTypeId = value),
                      ),
                      const SizedBox(height: 12),
                      _dropdown(
                        label: 'Parent Account',
                        required: false,
                        items: parentOptions.map((e) => {'id': e['id'], 'name': '${e['code']} - ${e['account_name']}'}).toList(),
                        value: _parentId,
                        onChanged: (value) => setState(() => _parentId = value),
                      ),
                      const SizedBox(height: 12),
                      _input(_codeController, 'Account Code *', isDarkMode),
                      const SizedBox(height: 12),
                      _dropdown(
                        label: 'Currency',
                        required: false,
                        items: currencies.map((e) => {'id': e['id'], 'name': '${e['symbol'] ?? ''} ${e['name'] ?? ''}'.trim()}).toList(),
                        value: _currencyId,
                        onChanged: (value) => setState(() => _currencyId = value),
                      ),
                      const SizedBox(height: 12),
                      _input(_nameController, 'Account Name *', isDarkMode),
                      const SizedBox(height: 12),
                      _input(_descriptionController, 'Description', isDarkMode, required: false, maxLines: 3),
                      const SizedBox(height: 12),
                      _dropdown(
                        label: 'Status',
                        items: const [
                          {'id': 'ACTIVE', 'name': 'Active'},
                          {'id': 'INACTIVE', 'name': 'Inactive'},
                        ],
                        value: _status,
                        onChangedString: (value) => setState(() => _status = value ?? 'ACTIVE'),
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
                            : Text(widget.account == null ? 'Save' : 'Update'),
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

  Widget _dropdown({
    required String label,
    required List<Map<String, dynamic>> items,
    dynamic value,
    ValueChanged<int?>? onChanged,
    ValueChanged<String?>? onChangedString,
    bool required = true,
  }) {
    final isDarkMode = ref.read(isDarkModeProvider);
    final useString = onChangedString != null;

    if (useString) {
      return DropdownButtonFormField<String>(
        value: value?.toString(),
        isExpanded: true,
        validator: required ? (selected) => selected == null || selected.isEmpty ? 'Required' : null : null,
        decoration: InputDecoration(
          labelText: label,
          filled: true,
          fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        ),
      items: items
          .map((item) => DropdownMenuItem<String>(
                value: item['id'].toString(),
                child: Text(item['name']?.toString() ?? '-', overflow: TextOverflow.ellipsis),
              ))
          .toList(),
        onChanged: onChangedString,
      );
    }

    return DropdownButtonFormField<int>(
      value: items.any((item) => _toInt(item['id']) == value) ? value as int? : null,
      isExpanded: true,
      validator: required ? (selected) => selected == null ? 'Required' : null : null,
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
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = <String, dynamic>{
        'account_type': _accountTypeId,
        'parent': _parentId,
        'code': _codeController.text.trim(),
        'currency': _currencyId,
        'account_name': _nameController.text.trim(),
        'description': _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
        'status': _status,
      };

      if (widget.account == null) {
        await api.post('/charts-of-accounts', data: data);
      } else {
        await api.put('/charts-of-accounts/${widget.account!['id']}', data: data);
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_chartErrorMessage(error, ref.read(isSwahiliProvider))),
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

class _ChartsErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ChartsErrorView({
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

class _ChartAccountDetailSheet extends StatelessWidget {
  final Map<String, dynamic> account;
  final bool isSwahili;
  final bool isDarkMode;

  const _ChartAccountDetailSheet({
    required this.account,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
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
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                children: [
                  Text(
                    '${account['code'] ?? '-'} - ${account['account_name'] ?? '-'}',
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 10),
                  _detailLine('Account Type', account['account_type_name']),
                  _detailLine('Parent', account['parent_name']),
                  _detailLine('Currency', account['currency_name'] ?? account['currency']),
                  _detailLine('Status', _statusLabel(account['status']?.toString())),
                  _detailLine('Children', account['children_count']),
                  _detailLine('Description', account['description']),
                ],
              ),
            ),
          ],
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

Color _statusColor(String? status) {
  switch ((status ?? '').toLowerCase()) {
    case 'active':
      return AppColors.success;
    case 'inactive':
      return AppColors.error;
    default:
      return AppColors.info;
  }
}

String _statusLabel(String? status) {
  if (status == null || status.trim().isEmpty) return '-';
  return status
      .replaceAll('_', ' ')
      .split(' ')
      .map((word) => word.isEmpty ? word : '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}')
      .join(' ');
}
