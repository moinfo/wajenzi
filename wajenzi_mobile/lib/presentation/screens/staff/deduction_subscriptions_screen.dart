import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _deductionSubscriptionsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/deduction-subscriptions');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _deductionSubscriptionRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/deduction-subscriptions/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class DeductionSubscriptionsScreen extends ConsumerWidget {
  const DeductionSubscriptionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final subscriptionsAsync = ref.watch(_deductionSubscriptionsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Usajili wa Makato' : 'Deduction Subscriptions'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_deductionSubscriptionsProvider),
        child: subscriptionsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _DeductionSubscriptionErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_deductionSubscriptionsProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.badge_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna usajili wa makato' : 'No deduction subscriptions found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: items.length + 1,
              itemBuilder: (context, index) {
                if (index == items.length) return const SizedBox(height: 90);
                final item = items[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    onTap: () => _showSubscriptionSheet(context, item, isDarkMode),
                    contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(Icons.badge, color: AppColors.primary),
                    ),
                    title: Text(
                      item['staff_name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${_deductionLabel(item)}\n${_membershipLabel(item['membership_number'])}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showSubscriptionSheet(context, item, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, subscription: item);
                        } else if (value == 'delete') {
                          _deleteSubscription(context, ref, item);
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

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? subscription}) async {
    final refs = await ref.read(_deductionSubscriptionRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.84,
        child: _DeductionSubscriptionFormSheet(refs: refs, subscription: subscription),
      ),
    );
    if (result == true) ref.invalidate(_deductionSubscriptionsProvider);
  }

  Future<void> _deleteSubscription(BuildContext context, WidgetRef ref, Map<String, dynamic> subscription) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Usajili' : 'Delete Subscription'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta usajili wa ${subscription['staff_name']}?'
              : 'Delete subscription for ${subscription['staff_name']}?',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/deduction-subscriptions/${subscription['id']}');
      ref.invalidate(_deductionSubscriptionsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Usajili umefutwa' : 'Subscription deleted'),
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

void _showSubscriptionSheet(BuildContext context, Map<String, dynamic> subscription, bool isDarkMode) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.54,
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
                      subscription['staff_name']?.toString() ?? 'Subscription',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 18),
                    _SubscriptionDetailRow('Deduction', _deductionLabel(subscription)),
                    _SubscriptionDetailRow('Membership Number', _membershipLabel(subscription['membership_number'])),
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

class _DeductionSubscriptionFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? subscription;

  const _DeductionSubscriptionFormSheet({
    required this.refs,
    this.subscription,
  });

  @override
  ConsumerState<_DeductionSubscriptionFormSheet> createState() => _DeductionSubscriptionFormSheetState();
}

class _DeductionSubscriptionFormSheetState extends ConsumerState<_DeductionSubscriptionFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _membershipNumberController =
      TextEditingController(text: widget.subscription?['membership_number']?.toString() ?? '');

  int? _staffId;
  int? _deductionId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _staffId = _toNullableInt(widget.subscription?['staff_id']);
    _deductionId = _toNullableInt(widget.subscription?['deduction_id']);
  }

  @override
  void dispose() {
    _membershipNumberController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final staffs = _toMaps(widget.refs['staffs']);
    final deductions = _toMaps(widget.refs['deductions']);

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
                          widget.subscription == null
                              ? (isSwahili ? 'Usajili Mpya' : 'New Subscription')
                              : (isSwahili ? 'Hariri Usajili' : 'Edit Subscription'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Staff *' : 'Staff *',
                          items: staffs,
                          value: _staffId,
                          onChanged: (value) => setState(() => _staffId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Deduction *' : 'Deduction *',
                          items: deductions,
                          value: _deductionId,
                          onChanged: (value) => setState(() => _deductionId = value),
                        ),
                        const SizedBox(height: 12),
                        _input(_membershipNumberController, isSwahili ? 'Membership Number' : 'Membership Number', isDarkMode, required: false),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.subscription == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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
  }) {
    return TextFormField(
      controller: controller,
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
                child: Text(_dropdownLabel(item), overflow: TextOverflow.ellipsis),
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
        'staff_id': _staffId,
        'deduction_id': _deductionId,
        'membership_number': _membershipNumberController.text.trim().isEmpty ? null : _membershipNumberController.text.trim(),
      };

      if (widget.subscription == null) {
        await api.post('/deduction-subscriptions', data: data);
      } else {
        await api.put('/deduction-subscriptions/${widget.subscription!['id']}', data: data);
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

class _SubscriptionDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _SubscriptionDetailRow(this.label, this.value);

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

class _DeductionSubscriptionErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _DeductionSubscriptionErrorView({
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

String _deductionLabel(Map<String, dynamic> item) {
  final name = item['deduction_name']?.toString().trim();
  final abbreviation = item['deduction_abbreviation']?.toString().trim();
  if ((abbreviation ?? '').isNotEmpty) {
    return '${name?.isNotEmpty == true ? name : '-'} (${abbreviation!})';
  }
  return name?.isNotEmpty == true ? name! : '-';
}

String _membershipLabel(dynamic value) {
  final membership = value?.toString().trim() ?? '';
  return membership.isEmpty ? '-' : membership;
}

String _dropdownLabel(Map<String, dynamic> item) {
  final name = item['name']?.toString().trim();
  final abbreviation = item['abbreviation']?.toString().trim();
  if ((abbreviation ?? '').isNotEmpty) {
    return '${name?.isNotEmpty == true ? name : '-'} (${abbreviation!})';
  }
  return name?.isNotEmpty == true ? name! : '-';
}
