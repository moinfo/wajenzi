import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _pettyCashRefillRequestsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/petty-cash-refill-requests');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];

  return {
    'items': items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList(),
    'meta': data['meta'] is Map ? Map<String, dynamic>.from(data['meta'] as Map) : const <String, dynamic>{},
  };
});

final _pettyCashRefillRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/petty-cash-refill-requests/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class PettyCashRefillRequestsScreen extends ConsumerWidget {
  const PettyCashRefillRequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final requestsAsync = ref.watch(_pettyCashRefillRequestsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Petty Cash Refill' : 'Petty Cash Refill'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_pettyCashRefillRequestsProvider),
        child: requestsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ErrorState(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_pettyCashRefillRequestsProvider),
          ),
          data: (payload) {
            final items = (payload['items'] as List?)?.whereType<Map<String, dynamic>>().toList() ?? const <Map<String, dynamic>>[];
            final meta = payload['meta'] is Map<String, dynamic>
                ? payload['meta'] as Map<String, dynamic>
                : const <String, dynamic>{};

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _SummaryCard(meta: meta, isDarkMode: isDarkMode),
                const SizedBox(height: 16),
                if (items.isEmpty)
                  _EmptyState(isDarkMode: isDarkMode, isSwahili: isSwahili)
                else
                  ...items.map(
                    (item) => Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: ListTile(
                        isThreeLine: true,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                        leading: CircleAvatar(
                          backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                          child: const Icon(Icons.account_balance_wallet_outlined, color: AppColors.primary),
                        ),
                        title: Text(
                          item['document_number']?.toString() ?? '-',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          '${item['date'] ?? '-'}\n${vatMoney(item['refill_amount'])} - ${item['status'] ?? '-'}',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        trailing: PopupMenuButton<String>(
                          onSelected: (value) {
                            if (value == 'view') {
                              _showDetails(context, ref, item);
                            } else if (value == 'edit') {
                              _openForm(context, ref, request: item);
                            } else if (value == 'delete') {
                              _deleteRequest(context, ref, item);
                            }
                          },
                          itemBuilder: (_) => [
                            PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                            PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                            PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
                          ],
                        ),
                        onTap: () => _showDetails(context, ref, item),
                      ),
                    ),
                  ),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? request}) async {
    final refs = await ref.read(_pettyCashRefillRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.86,
        child: _PettyCashRefillFormSheet(refs: refs, request: request),
      ),
    );

    if (result == true) {
      ref.invalidate(_pettyCashRefillRequestsProvider);
      ref.invalidate(_pettyCashRefillRefsProvider);
    }
  }

  Future<void> _deleteRequest(BuildContext context, WidgetRef ref, Map<String, dynamic> request) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Ombi' : 'Delete Request'),
        content: Text('${isSwahili ? 'Futa' : 'Delete'} ${request['document_number']}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/petty-cash-refill-requests/${request['id']}');
      ref.invalidate(_pettyCashRefillRequestsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Ombi limefutwa' : 'Request deleted'),
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

  void _showDetails(BuildContext context, WidgetRef ref, Map<String, dynamic> request) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.7,
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
                        request['document_number']?.toString() ?? '-',
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 16),
                      _detailLine('Date', request['date']),
                      _detailLine('Account', '${request['chart_account_code'] ?? '-'} - ${request['chart_account_name'] ?? '-'}'),
                      _detailLine('Balance', vatMoney(request['balance'])),
                      _detailLine('Refill Amount', vatMoney(request['refill_amount'])),
                      _detailLine('Requested By', request['requested_user_name']),
                      _detailLine('Status', request['status']),
                      if ((request['file_url']?.toString().isNotEmpty ?? false))
                        Padding(
                          padding: const EdgeInsets.only(top: 16),
                          child: OutlinedButton.icon(
                            onPressed: () async {
                              final uri = Uri.tryParse(request['file_url'].toString());
                              final opened = uri != null && await ExternalLauncherService.openUri(uri);
                              if (!opened && context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(isSwahili ? 'Imeshindikana kufungua faili' : 'Unable to open attachment')),
                                );
                              }
                            },
                            icon: const Icon(Icons.attach_file),
                            label: Text(isSwahili ? 'Fungua attachment' : 'Open attachment'),
                          ),
                        ),
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
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(fontSize: 13, color: AppColors.textPrimary),
          children: [
            TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w700)),
            TextSpan(text: (value ?? '-').toString()),
          ],
        ),
      ),
    );
  }
}

class _PettyCashRefillFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? request;

  const _PettyCashRefillFormSheet({required this.refs, this.request});

  @override
  ConsumerState<_PettyCashRefillFormSheet> createState() => _PettyCashRefillFormSheetState();
}

class _PettyCashRefillFormSheetState extends ConsumerState<_PettyCashRefillFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _dateController;
  File? _file;
  bool _saving = false;
  int? _chartAccountId;

  @override
  void initState() {
    super.initState();
    final accounts = _toMaps(widget.refs['charts_accounts']);
    _chartAccountId = _toNullableInt(widget.request?['charts_account_id']) ??
        (accounts.isNotEmpty ? _toNullableInt(accounts.first['id']) : null);
    _dateController = TextEditingController(
      text: widget.request?['date']?.toString() ?? vatDateFmt(DateTime.now()),
    );
  }

  @override
  void dispose() {
    _dateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final accounts = _toMaps(widget.refs['charts_accounts']);
    final balance = widget.request?['balance'] ?? widget.refs['current_balance'];
    final refillAmount = widget.request?['refill_amount'] ?? widget.refs['suggested_refill_amount'];

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
                          widget.request == null ? 'New Refill Request' : 'Edit Refill Request',
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        DropdownButtonFormField<int>(
                          isExpanded: true,
                          value: accounts.any((item) => _toInt(item['id']) == _chartAccountId) ? _chartAccountId : null,
                          validator: (value) => value == null ? 'Required' : null,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Akaunti *' : 'Account *',
                            filled: true,
                            fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
                          ),
                          items: accounts
                              .map(
                                (item) => DropdownMenuItem<int>(
                                  value: _toInt(item['id']),
                                  child: Text('${item['code']} - ${item['account_name']}', overflow: TextOverflow.ellipsis),
                                ),
                              )
                              .toList(),
                          onChanged: (value) => setState(() => _chartAccountId = value),
                        ),
                        const SizedBox(height: 12),
                        _readOnlyField(isDarkMode, 'Balance', vatMoney(balance)),
                        const SizedBox(height: 12),
                        _readOnlyField(isDarkMode, 'Refill Amount', vatMoney(refillAmount)),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _dateController,
                          readOnly: true,
                          validator: (value) => value == null || value.trim().isEmpty ? 'Required' : null,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Tarehe *' : 'Date *',
                            filled: true,
                            fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
                            suffixIcon: const Icon(Icons.calendar_today_outlined),
                          ),
                          onTap: () async {
                            final initial = DateTime.tryParse(_dateController.text) ?? DateTime.now();
                            final picked = await showDatePicker(
                              context: context,
                              initialDate: initial,
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2100),
                            );
                            if (picked != null) _dateController.text = vatDateFmt(picked);
                          },
                        ),
                        const SizedBox(height: 12),
                        VatFilePicker(
                          isDark: isDarkMode,
                          isSwahili: isSwahili,
                          file: _file,
                          onPicked: (picked) => setState(() => _file = picked),
                        ),
                        if (_file == null && (widget.request?['file_url']?.toString().isNotEmpty ?? false))
                          Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text(
                              isSwahili ? 'Attachment ya sasa ipo' : 'Current attachment already exists',
                              style: TextStyle(color: isDarkMode ? Colors.white70 : AppColors.textSecondary),
                            ),
                          ),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : () => _submit(balance, refillAmount),
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.request == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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

  Widget _readOnlyField(bool isDarkMode, String label, String value) {
    return TextFormField(
      initialValue: value,
      readOnly: true,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Future<void> _submit(dynamic balance, dynamic refillAmount) async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final formData = await vatBuildFormData({
        'charts_account_id': _chartAccountId,
        'date': _dateController.text.trim(),
        'balance': balance,
        'refill_amount': refillAmount,
      }, _file);

      if (widget.request == null) {
        await api.post('/petty-cash-refill-requests', data: formData);
      } else {
        await api.post('/petty-cash-refill-requests/${widget.request!['id']}', data: formData);
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

class _SummaryCard extends StatelessWidget {
  final Map<String, dynamic> meta;
  final bool isDarkMode;

  const _SummaryCard({required this.meta, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDarkMode ? Colors.white10 : Colors.black12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Summary', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          _line('Current balance', vatMoney(meta['current_balance'])),
          _line('Limit', vatMoney(meta['petty_cash_limit'])),
          _line('Suggested refill', vatMoney(meta['suggested_refill_amount'])),
        ],
      ),
    );
  }

  Widget _line(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          Expanded(child: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis)),
          const SizedBox(width: 12),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final bool isDarkMode;
  final bool isSwahili;

  const _EmptyState({required this.isDarkMode, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          Icon(Icons.inbox_outlined, size: 52, color: isDarkMode ? Colors.white24 : Colors.grey[350]),
          const SizedBox(height: 12),
          Text(
            isSwahili ? 'Hakuna refill requests' : 'No refill requests found',
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ErrorState({
    required this.isSwahili,
    required this.message,
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
