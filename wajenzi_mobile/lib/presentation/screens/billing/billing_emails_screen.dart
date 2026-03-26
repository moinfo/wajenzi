import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _billingEmailsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/billing/emails',
    queryParameters: {'per_page': 100},
  );
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

final _billingEmailDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/emails/$id');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class BillingEmailsScreen extends ConsumerWidget {
  const BillingEmailsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final emailsAsync = ref.watch(_billingEmailsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Barua za Billing' : 'Billing Emails'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_billingEmailsProvider),
        child: emailsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _EmailErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_billingEmailsProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(24),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.email_outlined,
                    size: 60,
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna emails bado' : 'No emails yet',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final id = _toInt(item['id']);
                final document =
                    item['document'] as Map<String, dynamic>? ?? const {};
                final client =
                    document['client'] as Map<String, dynamic>? ?? const {};
                final status = _text(item['status']);
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: id > 0 ? () => _showEmailSheet(context, ref, id) : null,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          CircleAvatar(
                            backgroundColor:
                                (status == 'sent' ? AppColors.success : AppColors.error)
                                    .withValues(alpha: 0.12),
                            child: Icon(
                              status == 'sent' ? Icons.mark_email_read : Icons.error_outline,
                              color: status == 'sent' ? AppColors.success : AppColors.error,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _text(item['subject']),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${_text(item['recipient_email'])}\n${_text(document['document_number'])} - ${_text(client['name'])}',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(color: AppColors.textSecondary),
                                ),
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: [
                                    _EmailChip(label: status.toUpperCase()),
                                    _EmailMeta(
                                      icon: Icons.schedule_outlined,
                                      label: _dateTimeText(item['sent_at']),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          PopupMenuButton<String>(
                            onSelected: (value) {
                              if (value == 'view') {
                                _showEmailSheet(context, ref, id);
                              } else if (value == 'resend') {
                                _openResendSheet(context, ref, item);
                              }
                            },
                            itemBuilder: (context) => const [
                              PopupMenuItem<String>(
                                value: 'view',
                                child: Text('View'),
                              ),
                              PopupMenuItem<String>(
                                value: 'resend',
                                child: Text('Resend'),
                              ),
                            ],
                          ),
                        ],
                      ),
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
}

void _showEmailSheet(BuildContext context, WidgetRef ref, int id) {
  if (id <= 0) return;
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.88,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_billingEmailDetailProvider(id));
          final isDarkMode = ref.watch(isDarkModeProvider);
          return Container(
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SafeArea(
              top: false,
              child: detailAsync.when(
                loading: () => const _EmailLoadingView(),
                error: (error, _) => _EmailErrorView(
                  message: vatErrorMessage(error, isSwahili: false),
                  isSwahili: false,
                  onRetry: () => ref.invalidate(_billingEmailDetailProvider(id)),
                ),
                data: (email) {
                  final document =
                      email['document'] as Map<String, dynamic>? ?? const {};
                  final client =
                      document['client'] as Map<String, dynamic>? ?? const {};
                  final sender = email['sender'] as Map<String, dynamic>? ?? const {};
                  return Column(
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
                              _text(email['subject']),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 16),
                            _EmailDetailRow('Status', _text(email['status'])),
                            _EmailDetailRow('Recipient', _text(email['recipient_email'])),
                            _EmailDetailRow('CC', _text(email['cc_emails'])),
                            _EmailDetailRow('Document', _text(document['document_number'])),
                            _EmailDetailRow('Type', _text(email['document_type'])),
                            _EmailDetailRow('Client', _text(client['name'])),
                            _EmailDetailRow('Amount', _money(document['total_amount'])),
                            _EmailDetailRow('Sent By', _text(sender['name'])),
                            _EmailDetailRow('Sent At', _dateTimeText(email['sent_at'])),
                            _EmailDetailRow('Attachment', _text(email['attachment_filename'])),
                            _EmailDetailRow('Error', _text(email['error_message'])),
                            const SizedBox(height: 16),
                            const Text(
                              'Message',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 8),
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: Colors.grey.withValues(alpha: 0.08),
                                borderRadius: BorderRadius.circular(14),
                              ),
                              child: Text(_text(email['message'])),
                            ),
                            const SizedBox(height: 18),
                            ElevatedButton.icon(
                              onPressed: () {
                                Navigator.of(context).pop();
                                _openResendSheet(context, ref, email);
                              },
                              icon: const Icon(Icons.refresh),
                              label: const Text('Resend Email'),
                            ),
                          ],
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
          );
        },
      ),
    ),
  );
}

Future<void> _openResendSheet(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> email,
) async {
  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.88,
      child: _ResendEmailSheet(email: email),
    ),
  );
  if (result == true) {
    ref.invalidate(_billingEmailsProvider);
  }
}

class _ResendEmailSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> email;

  const _ResendEmailSheet({required this.email});

  @override
  ConsumerState<_ResendEmailSheet> createState() => _ResendEmailSheetState();
}

class _ResendEmailSheetState extends ConsumerState<_ResendEmailSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _emailController =
      TextEditingController(text: widget.email['recipient_email']?.toString() ?? '');
  late final TextEditingController _ccController =
      TextEditingController(text: widget.email['cc_emails']?.toString() ?? '');
  late final TextEditingController _subjectController =
      TextEditingController(text: widget.email['subject']?.toString() ?? '');
  late final TextEditingController _messageController =
      TextEditingController(text: widget.email['message']?.toString() ?? '');

  bool _sending = false;

  @override
  void dispose() {
    _emailController.dispose();
    _ccController.dispose();
    _subjectController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
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
                      'Resend Email',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 18),
                    _emailField(
                      controller: _emailController,
                      label: 'To *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _emailField(
                      controller: _ccController,
                      label: 'CC',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _emailField(
                      controller: _subjectController,
                      label: 'Subject *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _emailField(
                      controller: _messageController,
                      label: 'Message *',
                      isDarkMode: isDarkMode,
                      maxLines: 8,
                    ),
                    const SizedBox(height: 18),
                    ElevatedButton(
                      onPressed: _sending ? null : _submit,
                      child: _sending
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text('Send Again'),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _sending = true);
    try {
      await ref.read(apiClientProvider).post(
            '/billing/emails/${widget.email['id']}/resend',
            data: {
              'email': _emailController.text.trim(),
              'cc': _blankToNull(_ccController.text),
              'subject': _subjectController.text.trim(),
              'message': _messageController.text.trim(),
            },
          );
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          backgroundColor: AppColors.success,
          content: Text('Email resent successfully'),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: false)),
        ),
      );
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }
}

class _EmailErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _EmailErrorView({
    required this.message,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 52, color: AppColors.error),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmailLoadingView extends StatelessWidget {
  const _EmailLoadingView();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const SizedBox(height: 12),
        Container(
          width: 44,
          height: 5,
          decoration: BoxDecoration(
            color: Colors.white24,
            borderRadius: BorderRadius.circular(999),
          ),
        ),
        const Expanded(child: Center(child: CircularProgressIndicator())),
      ],
    );
  }
}

class _EmailDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _EmailDetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(value),
        ],
      ),
    );
  }
}

class _EmailChip extends StatelessWidget {
  final String label;

  const _EmailChip({required this.label});

  @override
  Widget build(BuildContext context) {
    final isSent = label.toLowerCase() == 'sent';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: (isSent ? AppColors.success : AppColors.error).withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: isSent ? AppColors.success : AppColors.error,
          fontWeight: FontWeight.w700,
          fontSize: 11,
        ),
      ),
    );
  }
}

class _EmailMeta extends StatelessWidget {
  final IconData icon;
  final String label;

  const _EmailMeta({
    required this.icon,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.textSecondary),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(fontSize: 12)),
        ],
      ),
    );
  }
}

Widget _emailField({
  required TextEditingController controller,
  required String label,
  required bool isDarkMode,
  bool isRequired = true,
  int maxLines = 1,
}) {
  return TextFormField(
    controller: controller,
    maxLines: maxLines,
    decoration: InputDecoration(
      labelText: label,
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
      alignLabelWithHint: maxLines > 1,
    ),
    validator: (value) {
      final text = (value ?? '').trim();
      if (!isRequired && text.isEmpty) return null;
      if (text.isEmpty) return 'Required';
      return null;
    },
  );
}

String _text(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? '-' : text;
}

String _money(dynamic value) {
  final amount = value is num ? value.toDouble() : double.tryParse('${value ?? 0}') ?? 0;
  return 'TZS ${amount.toStringAsFixed(2)}';
}

String _dateTimeText(dynamic value) {
  final text = value?.toString() ?? '';
  if (text.isEmpty) return '-';
  return text.replaceFirst('T', ' ').split('.').first;
}

int _toInt(dynamic value) {
  if (value == null) return 0;
  if (value is int) return value;
  return int.tryParse(value.toString()) ?? 0;
}

String? _blankToNull(String? value) {
  final text = value?.trim() ?? '';
  return text.isEmpty ? null : text;
}
