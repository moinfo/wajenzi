import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _emailSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _emailStatusProvider = StateProvider.autoDispose<String?>((ref) => null);

final _emailTypeProvider = StateProvider.autoDispose<String?>((ref) => null);

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

class BillingEmailsScreen extends ConsumerStatefulWidget {
  const BillingEmailsScreen({super.key});

  @override
  ConsumerState<BillingEmailsScreen> createState() =>
      _BillingEmailsScreenState();
}

class _BillingEmailsScreenState extends ConsumerState<BillingEmailsScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final emailsAsync = ref.watch(_billingEmailsProvider);
    final search = ref.watch(_emailSearchProvider);
    final status = ref.watch(_emailStatusProvider);
    final docType = ref.watch(_emailTypeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Barua za Email' : 'Sent Emails'),
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  onChanged: (value) =>
                      ref.read(_emailSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta email...'
                        : 'Search emails...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(_emailSearchProvider.notifier).state =
                                  '';
                            },
                          )
                        : null,
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _FilterChip(
                        label: isSwahili ? 'Haina' : 'All',
                        isSelected: docType == null,
                        onTap: () =>
                            ref.read(_emailTypeProvider.notifier).state = null,
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Invoice',
                        isSelected: docType == 'invoice',
                        onTap: () =>
                            ref.read(_emailTypeProvider.notifier).state =
                                'invoice',
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Proforma',
                        isSelected: docType == 'proforma',
                        onTap: () =>
                            ref.read(_emailTypeProvider.notifier).state =
                                'proforma',
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Quote',
                        isSelected: docType == 'quote',
                        onTap: () =>
                            ref.read(_emailTypeProvider.notifier).state =
                                'quote',
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 16),
                      Container(
                        width: 1,
                        height: 24,
                        color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      ),
                      const SizedBox(width: 16),
                      _FilterChip(
                        label: 'All',
                        isSelected: status == null,
                        onTap: () =>
                            ref.read(_emailStatusProvider.notifier).state =
                                null,
                        isDarkMode: isDarkMode,
                        color: Colors.grey,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Sent',
                        isSelected: status == 'sent',
                        onTap: () =>
                            ref.read(_emailStatusProvider.notifier).state =
                                'sent',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Failed',
                        isSelected: status == 'failed',
                        onTap: () =>
                            ref.read(_emailStatusProvider.notifier).state =
                                'failed',
                        isDarkMode: isDarkMode,
                        color: AppColors.error,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => ref.invalidate(_billingEmailsProvider),
              child: emailsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _EmailErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_billingEmailsProvider),
                  isDarkMode: isDarkMode,
                ),
                data: (items) {
                  final filteredItems = items.where((item) {
                    if (status != null &&
                        _text(item['status']).toLowerCase() != status) {
                      return false;
                    }
                    if (docType != null &&
                        _text(item['document_type']).toLowerCase() != docType) {
                      return false;
                    }
                    if (search.isEmpty) return true;
                    final query = search.toLowerCase();
                    final subject = (_text(item['subject'])).toLowerCase();
                    final recipient = (_text(
                      item['recipient_email'],
                    )).toLowerCase();
                    final document =
                        item['document'] as Map<String, dynamic>? ?? {};
                    final docNumber = (_text(
                      document['document_number'],
                    )).toLowerCase();
                    final client =
                        document['client'] as Map<String, dynamic>? ?? {};
                    final clientName = _getClientName(client).toLowerCase();
                    return subject.contains(query) ||
                        recipient.contains(query) ||
                        docNumber.contains(query) ||
                        clientName.contains(query);
                  }).toList();

                  if (filteredItems.isEmpty) {
                    return ListView(
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.2,
                        ),
                        Icon(
                          Icons.email_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili ? 'Hakuna emails' : 'No emails found',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                    itemCount: filteredItems.length,
                    itemBuilder: (context, index) {
                      final item = filteredItems[index];
                      final id = _toInt(item['id']);

                      return _EmailCard(
                        item: item,
                        index: index + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: id > 0
                            ? () => _showEmailSheet(context, ref, id)
                            : null,
                        onResend: () => _openResendSheet(context, ref, item),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

String _getClientName(Map<String, dynamic>? client) {
  if (client == null) return '-';

  final fullName = client['full_name']?.toString();
  if (fullName != null && fullName.isNotEmpty && fullName.trim().isNotEmpty) {
    return fullName.trim();
  }

  final firstName = (client['first_name'] ?? '').toString().trim();
  final lastName = (client['last_name'] ?? '').toString().trim();
  if (firstName.isNotEmpty || lastName.isNotEmpty) {
    return '$firstName $lastName'.trim();
  }

  final contactPerson = (client['contact_person'] ?? '').toString().trim();
  if (contactPerson.isNotEmpty) {
    return contactPerson;
  }

  final companyName = (client['company_name'] ?? '').toString().trim();
  if (companyName.isNotEmpty) {
    return companyName;
  }

  return '-';
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final bool isDarkMode;
  final Color color;

  const _FilterChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected
              ? color
              : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100]),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? color : Colors.transparent),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: isSelected
                ? Colors.white
                : (isDarkMode ? Colors.white54 : Colors.grey[600]),
          ),
        ),
      ),
    );
  }
}

class _EmailCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback? onTap;
  final VoidCallback? onResend;

  const _EmailCard({
    required this.item,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    this.onTap,
    this.onResend,
  });

  @override
  Widget build(BuildContext context) {
    final status = _text(item['status']);
    final isSent = status.toLowerCase() == 'sent';
    final document = item['document'] as Map<String, dynamic>? ?? {};
    final hasAttachment = item['has_attachment'] == true;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: isDarkMode ? Colors.white12 : Colors.grey[200]!,
        ),
      ),
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: (isSent ? AppColors.success : AppColors.error)
                      .withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Icon(
                    isSent ? Icons.mark_email_read : Icons.error_outline,
                    color: isSent ? AppColors.success : AppColors.error,
                    size: 22,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            _text(item['subject']),
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (hasAttachment)
                          const Padding(
                            padding: EdgeInsets.only(left: 8),
                            child: Icon(
                              Icons.attach_file,
                              size: 16,
                              color: AppColors.success,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _text(item['recipient_email']),
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        _StatusChip(label: status, isSent: isSent),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 6,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            _text(item['document_type']).toUpperCase(),
                            style: const TextStyle(
                              fontSize: 10,
                              color: AppColors.primary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(
                          Icons.schedule_outlined,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textSecondary,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          _dateTimeText(item['sent_at']),
                          style: TextStyle(
                            fontSize: 11,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Icon(
                          Icons.receipt_outlined,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textSecondary,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            _text(document['document_number']),
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textSecondary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'view') {
                    onTap?.call();
                  } else if (value == 'resend') {
                    onResend?.call();
                  }
                },
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'view',
                    child: Row(
                      children: [
                        const Icon(Icons.visibility_outlined, size: 20),
                        const SizedBox(width: 10),
                        Text(isSwahili ? 'Tazama' : 'View'),
                      ],
                    ),
                  ),
                  PopupMenuItem(
                    value: 'resend',
                    child: Row(
                      children: [
                        const Icon(Icons.refresh, size: 20),
                        const SizedBox(width: 10),
                        Text(isSwahili ? 'Tuma Tena' : 'Resend'),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final bool isSent;

  const _StatusChip({required this.label, required this.isSent});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: (isSent ? AppColors.success : AppColors.error).withValues(
          alpha: 0.12,
        ),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.toUpperCase(),
        style: TextStyle(
          color: isSent ? AppColors.success : AppColors.error,
          fontWeight: FontWeight.w700,
          fontSize: 10,
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
          final isSwahili = ref.watch(isSwahiliProvider);
          final isDarkMode = ref.watch(isDarkModeProvider);
          return Container(
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(24),
              ),
            ),
            child: SafeArea(
              top: false,
              child: detailAsync.when(
                loading: () => const _EmailLoadingView(),
                error: (error, _) => _EmailErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () =>
                      ref.invalidate(_billingEmailDetailProvider(id)),
                  isDarkMode: isDarkMode,
                ),
                data: (email) {
                  final document =
                      email['document'] as Map<String, dynamic>? ?? {};
                  final client =
                      document['client'] as Map<String, dynamic>? ?? {};
                  final sender = email['sender'] as Map<String, dynamic>? ?? {};
                  final emailStatus = _text(email['status']);
                  final isSent = emailStatus.toLowerCase() == 'sent';

                  return ListView(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
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
                      const SizedBox(height: 16),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          CircleAvatar(
                            radius: 24,
                            backgroundColor:
                                (isSent ? AppColors.success : AppColors.error)
                                    .withValues(alpha: 0.1),
                            child: Icon(
                              isSent
                                  ? Icons.mark_email_read
                                  : Icons.error_outline,
                              color: isSent
                                  ? AppColors.success
                                  : AppColors.error,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _text(email['subject']),
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                _StatusChip(label: emailStatus, isSent: isSent),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      _SectionCard(
                        title: isSwahili ? 'Maelezo' : 'Details',
                        children: [
                          _EmailDetailRow(
                            label: isSwahili ? 'Mteja' : 'Client',
                            value: _getClientName(client),
                            isDarkMode: isDarkMode,
                          ),
                          _EmailDetailRow(
                            label: isSwahili ? 'Hati' : 'Document',
                            value: _text(document['document_number']),
                            isDarkMode: isDarkMode,
                          ),
                          _EmailDetailRow(
                            label: isSwahili ? 'Aina' : 'Type',
                            value: _text(email['document_type']).toUpperCase(),
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      _SectionCard(
                        title: isSwahili ? 'Email' : 'Email',
                        children: [
                          _EmailDetailRow(
                            label: isSwahili ? 'Kupokea' : 'Recipient',
                            value: _text(email['recipient_email']),
                            isDarkMode: isDarkMode,
                          ),
                          if (_text(email['cc_emails']).isNotEmpty &&
                              _text(email['cc_emails']) != '-')
                            _EmailDetailRow(
                              label: 'CC',
                              value: _text(email['cc_emails']),
                              isDarkMode: isDarkMode,
                            ),
                          _EmailDetailRow(
                            label: isSwahili ? 'Imetuma' : 'Sent By',
                            value: _text(sender['name']),
                            isDarkMode: isDarkMode,
                          ),
                          _EmailDetailRow(
                            label: isSwahili ? 'Wakati' : 'Sent At',
                            value: _dateTimeText(email['sent_at']),
                            isDarkMode: isDarkMode,
                          ),
                          if (_text(email['attachment_filename']).isNotEmpty &&
                              _text(email['attachment_filename']) != '-')
                            _EmailDetailRow(
                              label: isSwahili ? 'Kiambatanisho' : 'Attachment',
                              value: _text(email['attachment_filename']),
                              isDarkMode: isDarkMode,
                            ),
                          if (!isSent &&
                              _text(email['error_message']).isNotEmpty &&
                              _text(email['error_message']) != '-')
                            _EmailDetailRow(
                              label: isSwahili ? 'Hitilafu' : 'Error',
                              value: _text(email['error_message']),
                              isDarkMode: isDarkMode,
                              valueColor: AppColors.error,
                            ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      _SectionCard(
                        title: isSwahili ? 'Ujumbe' : 'Message',
                        children: [
                          Text(
                            _text(email['message']),
                            style: const TextStyle(height: 1.4),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      ElevatedButton.icon(
                        onPressed: () {
                          Navigator.of(context).pop();
                          _openResendSheet(context, ref, email);
                        },
                        icon: const Icon(Icons.refresh),
                        label: Text(isSwahili ? 'Tuma Tena' : 'Resend Email'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
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

class _EmailDetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _EmailDetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white54
                        : AppColors.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value.isEmpty ? '-' : value,
                  style: TextStyle(
                    fontSize: 14,
                    color:
                        valueColor ??
                        (isDarkMode ? Colors.white : AppColors.textPrimary),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _SectionCard({required this.title, required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        color: Colors.grey.withValues(alpha: 0.08),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          ...children,
        ],
      ),
    );
  }
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
  late final TextEditingController _emailController = TextEditingController(
    text: widget.email['recipient_email']?.toString() ?? '',
  );
  late final TextEditingController _ccController = TextEditingController(
    text: widget.email['cc_emails']?.toString() ?? '',
  );
  late final TextEditingController _subjectController = TextEditingController(
    text: widget.email['subject']?.toString() ?? '',
  );
  late final TextEditingController _messageController = TextEditingController(
    text: widget.email['message']?.toString() ?? '',
  );

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
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 24,
                          backgroundColor: AppColors.primary.withValues(
                            alpha: 0.1,
                          ),
                          child: const Icon(
                            Icons.refresh,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Text(
                          isSwahili ? 'Tuma Tena Email' : 'Resend Email',
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    _emailField(
                      controller: _emailController,
                      label: isSwahili ? 'Kupokea *' : 'To *',
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
                      label: isSwahili ? 'Mada *' : 'Subject *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _emailField(
                      controller: _messageController,
                      label: isSwahili ? 'Ujumbe *' : 'Message *',
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
                          : Text(isSwahili ? 'Tuma Tena' : 'Send Again'),
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
    final isSwahili = ref.read(isSwahiliProvider);
    if (!_formKey.currentState!.validate()) return;
    setState(() => _sending = true);
    try {
      await ref
          .read(apiClientProvider)
          .post(
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
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            isSwahili ? 'Email imetumwa tena' : 'Email resent successfully',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
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
  final bool isDarkMode;

  const _EmailErrorView({
    required this.message,
    required this.isSwahili,
    required this.onRetry,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 64, color: AppColors.error),
            const SizedBox(height: 16),
            Text(
              isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 12),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
              ),
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
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          const SizedBox(height: 12),
          Container(
            width: 44,
            height: 5,
            decoration: BoxDecoration(
              color: Colors.black12,
              borderRadius: BorderRadius.circular(999),
            ),
          ),
          const Expanded(child: Center(child: CircularProgressIndicator())),
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
        borderRadius: BorderRadius.circular(12),
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
