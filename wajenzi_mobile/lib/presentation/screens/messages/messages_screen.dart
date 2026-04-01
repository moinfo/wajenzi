import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _messagesProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/messages',
    queryParameters: {'per_page': 50},
  );
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

final _messageReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/messages/reference-data');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

final _birthdaysProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/messages/birthdays');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

class MessagesScreen extends ConsumerStatefulWidget {
  const MessagesScreen({super.key});

  @override
  ConsumerState<MessagesScreen> createState() => _MessagesScreenState();
}

class _MessagesScreenState extends ConsumerState<MessagesScreen> {
  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final messagesAsync = ref.watch(_messagesProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: const Text('eSMS'),
        actions: [
          IconButton(
            onPressed: () => _showBirthdays(context, ref),
            icon: const Icon(Icons.cake_outlined),
            tooltip: isSwahili ? 'Siku ya Kuzaliwa' : 'Birthdays',
          ),
          IconButton(
            onPressed: () => ref.invalidate(_messagesProvider),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      floatingActionButton: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          FloatingActionButton.small(
            heroTag: 'bulk_sms',
            backgroundColor: AppColors.secondary,
            onPressed: () => _openBulkSheet(context, ref),
            child: const Icon(Icons.groups_rounded),
          ),
          const SizedBox(height: 10),
          FloatingActionButton(
            heroTag: 'new_sms',
            onPressed: () => _openMessageSheet(context, ref),
            child: const Icon(Icons.add_comment_rounded),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_messagesProvider);
          ref.invalidate(_birthdaysProvider);
        },
        child: messagesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _MessageErrorView(
            message: _errorText(error),
            onRetry: () => ref.invalidate(_messagesProvider),
          ),
          data: (payload) {
            final stats = payload['stats'] is Map
                ? Map<String, dynamic>.from(payload['stats'] as Map)
                : const <String, dynamic>{};
            final items = (payload['data'] as List? ?? const [])
                .whereType<Map>()
                .map((item) => Map<String, dynamic>.from(item))
                .toList();

            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
              children: [
                _SmsHero(stats: stats, isSwahili: isSwahili),
                const SizedBox(height: 16),
                _StatsGrid(stats: stats, isSwahili: isSwahili),
                const SizedBox(height: 16),
                _SectionHeader(
                  title: isSwahili ? 'Historia ya Ujumbe' : 'Message History',
                  subtitle:
                      '${stats['this_month_messages'] ?? 0} ${isSwahili ? 'ujumbe mwezi huu' : 'sent this month'}',
                ),
                const SizedBox(height: 12),
                if (items.isEmpty)
                  _EmptyCard(
                    icon: Icons.mark_email_read_outlined,
                    title: isSwahili
                        ? 'Hakuna historia ya SMS'
                        : 'No SMS history yet',
                    subtitle: isSwahili
                        ? 'Ujumbe uliotumwa utaonekana hapa.'
                        : 'Sent messages will appear here.',
                  )
                else
                  ...items.map(
                    (item) => _MessageCard(message: item, isSwahili: isSwahili),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _SmsHero extends StatelessWidget {
  final Map<String, dynamic> stats;
  final bool isSwahili;
  const _SmsHero({required this.stats, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2563EB), Color(0xFF22C55E)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili ? 'Ujumbe wa SMS' : 'SMS Messaging',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            isSwahili
                ? 'Tuma ujumbe na ufuatilia ugavi kwa urahisi katika shirika lako'
                : 'Send messages and track delivery across your organization',
            style: const TextStyle(color: Colors.white70),
          ),
          const SizedBox(height: 18),
          Text(
            '${stats['sms_balance'] ?? '--'}',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 32,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            isSwahili ? 'Salio la SMS' : 'SMS balance',
            style: const TextStyle(color: Colors.white70),
          ),
        ],
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  final Map<String, dynamic> stats;
  final bool isSwahili;
  const _StatsGrid({required this.stats, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 1.45,
      children: [
        _StatCard(
          label: isSwahili ? 'Imetuma Leo' : 'Sent Today',
          value: '${stats['today_messages'] ?? 0}',
          color: AppColors.success,
          icon: Icons.calendar_today_rounded,
        ),
        _StatCard(
          label: isSwahili ? 'Wiki Hii' : 'This Week',
          value: '${stats['this_week_messages'] ?? 0}',
          color: AppColors.warning,
          icon: Icons.date_range_rounded,
        ),
        _StatCard(
          label: isSwahili ? 'Jumla ya Ujumbe' : 'Total Messages',
          value: '${stats['total_messages'] ?? 0}',
          color: AppColors.textPrimary,
          icon: Icons.bar_chart_rounded,
        ),
        _StatCard(
          label: isSwahili ? 'Mwezi Huu' : 'This Month',
          value: '${stats['this_month_messages'] ?? 0}',
          color: AppColors.info,
          icon: Icons.event_note_rounded,
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final IconData icon;

  const _StatCard({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 20),
          const Spacer(),
          Text(
            value,
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w800,
              color: AppColors.textPrimary,
            ),
          ),
          Text(label, style: const TextStyle(color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  final String subtitle;
  const _SectionHeader({required this.title, required this.subtitle});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 2),
        Text(subtitle, style: const TextStyle(color: AppColors.textSecondary)),
      ],
    );
  }
}

class _MessageCard extends ConsumerWidget {
  final Map<String, dynamic> message;
  final bool isSwahili;
  const _MessageCard({required this.message, required this.isSwahili});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.person_rounded, color: AppColors.secondary),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  _text(message['name']),
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) async {
                  if (value == 'edit') {
                    await _openMessageSheet(context, ref, message: message);
                  } else if (value == 'delete') {
                    await _deleteMessage(context, ref, _toInt(message['id']));
                  }
                },
                itemBuilder: (context) => [
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
            ],
          ),
          const SizedBox(height: 6),
          Text(
            _text(message['phone']),
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontFamily: 'monospace',
            ),
          ),
          const SizedBox(height: 10),
          Text(_text(message['message'])),
          const SizedBox(height: 10),
          Text(
            _text(message['created_at_human']),
            style: const TextStyle(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

class _EmptyCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  const _EmptyCard({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          Icon(icon, size: 48, color: Colors.black26),
          const SizedBox(height: 12),
          Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 6),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

class _MessageErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _MessageErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 54, color: AppColors.error),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

Future<void> _showBirthdays(BuildContext context, WidgetRef ref) async {
  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.82,
      child: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Consumer(
            builder: (context, ref, _) {
              final birthdaysAsync = ref.watch(_birthdaysProvider);
              return birthdaysAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _MessageErrorView(
                  message: _errorText(error),
                  onRetry: () => ref.invalidate(_birthdaysProvider),
                ),
                data: (items) => ListView(
                  padding: const EdgeInsets.fromLTRB(16, 20, 16, 24),
                  children: [
                    const Text(
                      'Employee Birthdays',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 16),
                    if (items.isEmpty)
                      const _EmptyCard(
                        icon: Icons.cake_outlined,
                        title: 'No birthdays found',
                        subtitle:
                            'No active employees have a date of birth set.',
                      )
                    else
                      ...items.map((item) {
                        final isToday = item['is_today'] == true;
                        final daysUntil = _toInt(item['days_until']);
                        return Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: isToday
                                ? AppColors.success.withValues(alpha: 0.08)
                                : Colors.grey.withValues(alpha: 0.06),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                isToday
                                    ? Icons.star_rounded
                                    : Icons.cake_outlined,
                                color: isToday
                                    ? AppColors.success
                                    : AppColors.warning,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      _text(item['name']),
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      '${_text(item['phone_number'])} • ${_text(item['dob_formatted'])}',
                                      style: const TextStyle(
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              Text(
                                isToday
                                    ? 'Today'
                                    : 'In $daysUntil day${daysUntil == 1 ? '' : 's'}',
                                style: TextStyle(
                                  color: isToday
                                      ? AppColors.success
                                      : AppColors.warning,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        );
                      }),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    ),
  );
}

Future<void> _openMessageSheet(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? message,
}) async {
  final result = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.9,
      child: _MessageFormSheet(message: message),
    ),
  );
  if (result == true) {
    ref.invalidate(_messagesProvider);
  }
}

Future<void> _openBulkSheet(BuildContext context, WidgetRef ref) async {
  final refs = await ref.read(_messageReferenceProvider.future);
  if (!context.mounted) return;
  final result = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.82,
      child: _BulkMessageSheet(refs: refs),
    ),
  );
  if (result == true) {
    ref.invalidate(_messagesProvider);
  }
}

Future<void> _deleteMessage(BuildContext context, WidgetRef ref, int id) async {
  if (id <= 0) return;
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: const Text('Delete SMS'),
      content: const Text('Delete this SMS record?'),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: const Text('Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: const Text('Delete'),
        ),
      ],
    ),
  );
  if (confirmed != true) return;

  try {
    await ref.read(apiClientProvider).delete('/messages/$id');
    ref.invalidate(_messagesProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          backgroundColor: AppColors.success,
          content: Text('SMS record deleted'),
        ),
      );
    }
  } catch (error) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(_errorText(error)),
        ),
      );
    }
  }
}

class _MessageFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? message;
  const _MessageFormSheet({this.message});

  @override
  ConsumerState<_MessageFormSheet> createState() => _MessageFormSheetState();
}

class _MessageFormSheetState extends ConsumerState<_MessageFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController = TextEditingController(
    text: widget.message?['name']?.toString() ?? '',
  );
  late final TextEditingController _phoneController = TextEditingController(
    text: widget.message?['phone']?.toString() ?? '',
  );
  late final TextEditingController _messageController = TextEditingController(
    text: widget.message?['message']?.toString() ?? '',
  );
  bool _saving = false;

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final smsCount = _smsSegments(_messageController.text);
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              20,
              16,
              20,
              MediaQuery.of(context).viewInsets.bottom + 28,
            ),
            children: [
              const Text(
                'Send New Message',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 18),
              _sheetField(controller: _nameController, label: 'Recipient Name'),
              const SizedBox(height: 12),
              _sheetField(
                controller: _phoneController,
                label: 'Phone',
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _messageController,
                maxLines: 6,
                decoration: _inputDecoration('Message'),
                onChanged: (_) => setState(() {}),
                validator: (value) =>
                    (value ?? '').trim().isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 8),
              Text(
                '${_messageController.text.length} / 160 characters ($smsCount SMS)',
                style: TextStyle(
                  color: smsCount <= 1
                      ? AppColors.success
                      : smsCount <= 3
                      ? AppColors.warning
                      : AppColors.error,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 22),
              ElevatedButton(
                onPressed: _saving ? null : _submit,
                child: _saving
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Text(
                        widget.message == null ? 'Send SMS' : 'Update Record',
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    final payload = {
      'name': _nameController.text.trim(),
      'phone': _phoneController.text.trim(),
      'message': _messageController.text.trim(),
    };

    try {
      final api = ref.read(apiClientProvider);
      final id = _toInt(widget.message?['id']);
      if (id > 0) {
        await api.put('/messages/$id', data: payload);
      } else {
        await api.post('/messages', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            id > 0 ? 'SMS record updated' : 'SMS sent successfully',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(_errorText(error)),
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _BulkMessageSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  const _BulkMessageSheet({required this.refs});

  @override
  ConsumerState<_BulkMessageSheet> createState() => _BulkMessageSheetState();
}

class _BulkMessageSheetState extends ConsumerState<_BulkMessageSheet> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _messageController = TextEditingController();
  int? _departmentId;
  bool _saving = false;

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final departments = (widget.refs['departments'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final smsCount = _smsSegments(_messageController.text);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              20,
              16,
              20,
              MediaQuery.of(context).viewInsets.bottom + 28,
            ),
            children: [
              const Text(
                'Send Bulk SMS',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 18),
              DropdownButtonFormField<int?>(
                value: _departmentId,
                decoration: _inputDecoration('Department'),
                items: [
                  const DropdownMenuItem<int?>(
                    value: null,
                    child: Text('All Departments'),
                  ),
                  ...departments.map((item) {
                    final id = _toInt(item['id']);
                    return DropdownMenuItem<int?>(
                      value: id == 0 ? null : id,
                      child: Text(_text(item['name'])),
                    );
                  }),
                ],
                onChanged: (value) => setState(() => _departmentId = value),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _messageController,
                maxLines: 7,
                decoration: _inputDecoration('Message'),
                onChanged: (_) => setState(() {}),
                validator: (value) =>
                    (value ?? '').trim().isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 8),
              Text(
                '${_messageController.text.length} / 160 characters ($smsCount SMS)',
                style: TextStyle(
                  color: smsCount <= 1
                      ? AppColors.success
                      : smsCount <= 3
                      ? AppColors.warning
                      : AppColors.error,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 22),
              ElevatedButton(
                onPressed: _saving ? null : _submit,
                child: _saving
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Text('Send Bulk SMS'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    try {
      await ref
          .read(apiClientProvider)
          .post(
            '/messages/bulk',
            data: {
              'department_id': _departmentId,
              'message': _messageController.text.trim(),
            },
          );
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          backgroundColor: AppColors.success,
          content: Text('Bulk SMS sent successfully'),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(_errorText(error)),
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

InputDecoration _inputDecoration(String label) => InputDecoration(
  labelText: label,
  filled: true,
  fillColor: Colors.grey.withValues(alpha: 0.08),
  border: OutlineInputBorder(
    borderRadius: BorderRadius.circular(14),
    borderSide: BorderSide.none,
  ),
);

Widget _sheetField({
  required TextEditingController controller,
  required String label,
  TextInputType? keyboardType,
}) {
  return TextFormField(
    controller: controller,
    keyboardType: keyboardType,
    decoration: _inputDecoration(label),
    validator: (value) => (value ?? '').trim().isEmpty ? 'Required' : null,
  );
}

int _smsSegments(String text) {
  final len = text.trim().length;
  if (len == 0) return 0;
  if (len <= 160) return 1;
  return ((len - 160) / 153).ceil() + 1;
}

String _text(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? '-' : text;
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

String _errorText(Object error) {
  return error.toString().trim().isEmpty
      ? 'Something went wrong. Please try again.'
      : error.toString();
}
