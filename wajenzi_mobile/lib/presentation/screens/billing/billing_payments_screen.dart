import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _paymentSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _paymentStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _paymentListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/billing/payments',
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

final _paymentRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/payments/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

final _paymentDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/billing/payments/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

class BillingPaymentsScreen extends ConsumerStatefulWidget {
  const BillingPaymentsScreen({super.key});

  @override
  ConsumerState<BillingPaymentsScreen> createState() =>
      _BillingPaymentsScreenState();
}

class _BillingPaymentsScreenState extends ConsumerState<BillingPaymentsScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final itemsAsync = ref.watch(_paymentListProvider);
    final search = ref.watch(_paymentSearchProvider);
    final status = ref.watch(_paymentStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Malipo' : 'Payments'),
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
                      ref.read(_paymentSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta malipo...'
                        : 'Search payments...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(_paymentSearchProvider.notifier).state =
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
                        label: 'All',
                        isSelected: status == null,
                        onTap: () =>
                            ref.read(_paymentStatusProvider.notifier).state =
                                null,
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Completed',
                        isSelected: status == 'completed',
                        onTap: () =>
                            ref.read(_paymentStatusProvider.notifier).state =
                                'completed',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Pending',
                        isSelected: status == 'pending',
                        onTap: () =>
                            ref.read(_paymentStatusProvider.notifier).state =
                                'pending',
                        isDarkMode: isDarkMode,
                        color: Colors.orange,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Voided',
                        isSelected: status == 'voided',
                        onTap: () =>
                            ref.read(_paymentStatusProvider.notifier).state =
                                'voided',
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
              onRefresh: () async => ref.invalidate(_paymentListProvider),
              child: itemsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _PaymentErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_paymentListProvider),
                  isDarkMode: isDarkMode,
                ),
                data: (items) {
                  final filteredItems = items.where((item) {
                    if (status != null &&
                        _text(item['status']).toLowerCase() != status) {
                      return false;
                    }
                    if (search.isEmpty) return true;
                    final query = search.toLowerCase();
                    final paymentNumber = (_text(
                      item['payment_number'],
                    )).toLowerCase();
                    final document =
                        item['document'] as Map<String, dynamic>? ?? {};
                    final docNumber = (_text(
                      document['document_number'],
                    )).toLowerCase();

                    // Try direct client first, then document client
                    Map<String, dynamic>? client =
                        item['client'] as Map<String, dynamic>?;
                    client ??= document['client'] as Map<String, dynamic>?;

                    final clientName = _getClientName(client).toLowerCase();
                    return paymentNumber.contains(query) ||
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
                          Icons.payments_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili ? 'Hakuna malipo' : 'No payments found',
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
                      final paymentStatus = _text(item['status']);
                      final isPending =
                          paymentStatus.toLowerCase() == 'pending';

                      return _PaymentCard(
                        item: item,
                        index: index + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: id > 0
                            ? () => _showPaymentSheet(context, ref, id)
                            : null,
                        onEdit: (isPending || paymentStatus.isEmpty)
                            ? () =>
                                  _openPaymentForm(context, ref, payment: item)
                            : null,
                        onDelete: (isPending || paymentStatus.isEmpty)
                            ? () => _deletePayment(context, ref, item)
                            : null,
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 70),
        child: FloatingActionButton(
          onPressed: () => _openPaymentForm(context, ref),
          backgroundColor: AppColors.primary,
          child: const Icon(Icons.add, color: Colors.white),
        ),
      ),
    );
  }
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

class _PaymentCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback? onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  const _PaymentCard({
    required this.item,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    this.onTap,
    this.onEdit,
    this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final status = _text(item['status']);
    final document = item['document'] as Map<String, dynamic>? ?? {};

    // Try payment's direct client first, then fall back to document's client
    Map<String, dynamic>? client = item['client'] as Map<String, dynamic>?;
    client ??= document['client'] as Map<String, dynamic>?;

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
                  color: AppColors.success.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '$index',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.success,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _text(item['payment_number']),
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _getClientName(client),
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
                        _StatusChip(label: status),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(
                                  Icons.receipt_outlined,
                                  size: 10,
                                  color: AppColors.primary,
                                ),
                                const SizedBox(width: 4),
                                Flexible(
                                  child: Text(
                                    _text(document['document_number']),
                                    style: const TextStyle(
                                      fontSize: 10,
                                      color: AppColors.primary,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(
                          Icons.calendar_today_outlined,
                          size: 12,
                          color: AppColors.textSecondary,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          _text(item['payment_date']),
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(width: 12),
                        const Icon(
                          Icons.credit_card_outlined,
                          size: 12,
                          color: AppColors.textSecondary,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            _text(item['payment_method']).replaceAll('_', ' '),
                            style: TextStyle(
                              fontSize: 12,
                              color: isDarkMode
                                  ? Colors.white54
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
              const SizedBox(width: 8),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    _money(item['amount']),
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: AppColors.success,
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'view') {
                        onTap?.call();
                      } else if (value == 'edit') {
                        onEdit?.call();
                      } else if (value == 'delete') {
                        onDelete?.call();
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
                      if (onEdit != null)
                        PopupMenuItem(
                          value: 'edit',
                          child: Row(
                            children: [
                              const Icon(Icons.edit_outlined, size: 20),
                              const SizedBox(width: 10),
                              Text(isSwahili ? 'Hariri' : 'Edit'),
                            ],
                          ),
                        ),
                      if (onDelete != null)
                        PopupMenuItem(
                          value: 'delete',
                          child: Row(
                            children: [
                              const Icon(
                                Icons.delete_outlined,
                                size: 20,
                                color: AppColors.error,
                              ),
                              const SizedBox(width: 10),
                              Text(
                                isSwahili ? 'Futa' : 'Delete',
                                style: const TextStyle(color: AppColors.error),
                              ),
                            ],
                          ),
                        ),
                    ],
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

  const _StatusChip({required this.label});

  @override
  Widget build(BuildContext context) {
    final normalized = label.toLowerCase();
    Color color;
    if (normalized == 'completed') {
      color = AppColors.success;
    } else if (normalized == 'pending') {
      color = Colors.orange;
    } else if (normalized == 'voided') {
      color = AppColors.error;
    } else {
      color = AppColors.textSecondary;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.toUpperCase(),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 10,
        ),
      ),
    );
  }
}

Future<void> _openPaymentForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? payment,
}) async {
  final refs = await ref.read(_paymentRefsProvider.future);
  var initialPayment = payment;
  final paymentId = _toInt(payment?['id']);
  if (payment != null && paymentId > 0 && payment['document_id'] == null) {
    initialPayment = await ref.read(_paymentDetailProvider(paymentId).future);
  }
  if (!context.mounted) return;

  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.94,
      child: _PaymentFormSheet(refs: refs, payment: initialPayment),
    ),
  );
  if (result == true) {
    ref.invalidate(_paymentListProvider);
    if (paymentId > 0) {
      ref.invalidate(_paymentDetailProvider(paymentId));
    }
  }
}

Future<void> _deletePayment(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> payment,
) async {
  final isSwahili = ref.read(isSwahiliProvider);
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      backgroundColor: ref.read(isDarkModeProvider)
          ? const Color(0xFF1A1A2E)
          : null,
      title: Text(
        isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete',
        style: TextStyle(
          color: ref.read(isDarkModeProvider) ? Colors.white : null,
        ),
      ),
      content: Text(
        isSwahili
            ? 'Je, una uhakika unataka kufuta "${_text(payment['payment_number'])}"?'
            : 'Are you sure you want to delete "${_text(payment['payment_number'])}"?',
        style: TextStyle(
          color: ref.read(isDarkModeProvider) ? Colors.white70 : null,
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: Text(isSwahili ? 'Hapana' : 'No'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(
            isSwahili ? 'Ndiyo, Futa' : 'Yes, Delete',
            style: const TextStyle(color: AppColors.error),
          ),
        ),
      ],
    ),
  );
  if (confirmed != true) return;

  try {
    await ref
        .read(apiClientProvider)
        .delete('/billing/payments/${payment['id']}');
    ref.invalidate(_paymentListProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(isSwahili ? 'Malipo yamefutwa' : 'Payment deleted'),
        ),
      );
    }
  } catch (error) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
        ),
      );
    }
  }
}

void _showPaymentSheet(BuildContext context, WidgetRef ref, int id) {
  if (id <= 0) return;
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.88,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_paymentDetailProvider(id));
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
                loading: () => const _BottomLoading(),
                error: (error, _) => _PaymentErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_paymentDetailProvider(id)),
                  isDarkMode: isDarkMode,
                ),
                data: (payment) {
                  final document =
                      payment['document'] as Map<String, dynamic>? ?? {};
                  final client =
                      payment['client'] as Map<String, dynamic>? ?? {};
                  final paymentStatus = _text(payment['status']);
                  final isPending = paymentStatus.toLowerCase() == 'pending';

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
                            backgroundColor: AppColors.success.withValues(
                              alpha: 0.1,
                            ),
                            child: const Icon(
                              Icons.payments,
                              color: AppColors.success,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _text(payment['payment_number']),
                                  style: const TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                _StatusChip(label: paymentStatus),
                              ],
                            ),
                          ),
                          if (isPending)
                            IconButton(
                              onPressed: () async {
                                Navigator.of(context).pop();
                                await _openPaymentForm(
                                  context,
                                  ref,
                                  payment: payment,
                                );
                              },
                              icon: const Icon(Icons.edit_outlined),
                            ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      _SectionCard(
                        title: isSwahili ? 'Maelezo' : 'Details',
                        children: [
                          _PaymentDetailRow(
                            label: isSwahili ? 'Mteja' : 'Client',
                            value:
                                client['full_name']?.toString() ??
                                client['name']?.toString() ??
                                '-',
                            isDarkMode: isDarkMode,
                          ),
                          _PaymentDetailRow(
                            label: isSwahili ? 'Hati' : 'Document',
                            value: _text(document['document_number']),
                            isDarkMode: isDarkMode,
                          ),
                          _PaymentDetailRow(
                            label: isSwahili ? 'Tarehe' : 'Payment Date',
                            value: _text(payment['payment_date']),
                            isDarkMode: isDarkMode,
                          ),
                          _PaymentDetailRow(
                            label: isSwahili ? 'Njia' : 'Payment Method',
                            value: _text(
                              payment['payment_method'],
                            ).replaceAll('_', ' '),
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      _SectionCard(
                        title: isSwahili ? 'Kiasi' : 'Amount',
                        children: [
                          _PaymentDetailRow(
                            label: isSwahili ? 'Kiasi' : 'Amount',
                            value: _money(payment['amount']),
                            isDarkMode: isDarkMode,
                            valueColor: AppColors.success,
                          ),
                        ],
                      ),
                      if (_text(payment['reference_number']).isNotEmpty &&
                          _text(payment['reference_number']) != '-') ...[
                        const SizedBox(height: 14),
                        _SectionCard(
                          title: isSwahili ? 'Marejeo' : 'Reference',
                          children: [
                            _PaymentDetailRow(
                              label: isSwahili ? 'Nambari' : 'Reference Number',
                              value: _text(payment['reference_number']),
                              isDarkMode: isDarkMode,
                            ),
                            if (_text(payment['bank_name']).isNotEmpty)
                              _PaymentDetailRow(
                                label: isSwahili ? 'Benki' : 'Bank',
                                value: _text(payment['bank_name']),
                                isDarkMode: isDarkMode,
                              ),
                            if (_text(payment['cheque_number']).isNotEmpty)
                              _PaymentDetailRow(
                                label: isSwahili ? 'Cheque' : 'Cheque Number',
                                value: _text(payment['cheque_number']),
                                isDarkMode: isDarkMode,
                              ),
                            if (_text(payment['transaction_id']).isNotEmpty)
                              _PaymentDetailRow(
                                label: isSwahili ? 'Muamala' : 'Transaction ID',
                                value: _text(payment['transaction_id']),
                                isDarkMode: isDarkMode,
                              ),
                          ],
                        ),
                      ],
                      if (_text(payment['notes']).isNotEmpty &&
                          _text(payment['notes']) != '-') ...[
                        const SizedBox(height: 14),
                        _SectionCard(
                          title: isSwahili ? 'Maelezo' : 'Notes',
                          children: [
                            Text(
                              _text(payment['notes']),
                              style: const TextStyle(height: 1.4),
                            ),
                          ],
                        ),
                      ],
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

class _PaymentDetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _PaymentDetailRow({
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
                    fontSize: 15,
                    color:
                        valueColor ??
                        (isDarkMode ? Colors.white : AppColors.textPrimary),
                    fontWeight: valueColor != null
                        ? FontWeight.w600
                        : FontWeight.normal,
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

class _PaymentErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;
  final bool isDarkMode;

  const _PaymentErrorView({
    required this.isSwahili,
    required this.message,
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

class _BottomLoading extends StatelessWidget {
  const _BottomLoading();

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

class _PaymentFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? payment;

  const _PaymentFormSheet({required this.refs, this.payment});

  @override
  ConsumerState<_PaymentFormSheet> createState() => _PaymentFormSheetState();
}

class _PaymentFormSheetState extends ConsumerState<_PaymentFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _dateController = TextEditingController(
    text: _dateValue(widget.payment?['payment_date']),
  );
  late final TextEditingController _amountController = TextEditingController(
    text: _numberText(widget.payment?['amount']),
  );
  late final TextEditingController _referenceController = TextEditingController(
    text: widget.payment?['reference_number']?.toString() ?? '',
  );
  late final TextEditingController _bankController = TextEditingController(
    text: widget.payment?['bank_name']?.toString() ?? '',
  );
  late final TextEditingController _chequeController = TextEditingController(
    text: widget.payment?['cheque_number']?.toString() ?? '',
  );
  late final TextEditingController _transactionController =
      TextEditingController(
        text: widget.payment?['transaction_id']?.toString() ?? '',
      );
  late final TextEditingController _notesController = TextEditingController(
    text: widget.payment?['notes']?.toString() ?? '',
  );

  int? _documentId;
  String? _paymentMethod;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _documentId = _toNullableInt(widget.payment?['document_id']);
    _paymentMethod = widget.payment?['payment_method']?.toString();
  }

  @override
  void dispose() {
    _dateController.dispose();
    _amountController.dispose();
    _referenceController.dispose();
    _bankController.dispose();
    _chequeController.dispose();
    _transactionController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final documents = (widget.refs['documents'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final methods = (widget.refs['payment_methods'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final selectedDocument = documents.firstWhere(
      (doc) => _toNullableInt(doc['id']) == _documentId,
      orElse: () => const <String, dynamic>{},
    );

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
                          backgroundColor: AppColors.success.withValues(
                            alpha: 0.1,
                          ),
                          child: Icon(
                            widget.payment == null ? Icons.add : Icons.edit,
                            color: AppColors.success,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Text(
                          widget.payment == null
                              ? (isSwahili ? 'Malipo Mpya' : 'New Payment')
                              : (isSwahili ? 'Hariri Malipo' : 'Edit Payment'),
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    _dropdownField(
                      label: isSwahili ? 'Hati *' : 'Document *',
                      isDarkMode: isDarkMode,
                      value: _documentId,
                      items: documents
                          .map(
                            (item) => DropdownMenuItem<int?>(
                              value: _toNullableInt(item['id']),
                              child: Text(
                                '${_text(item['document_number'])} - ${_text(item['client_name'])}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _documentId = value),
                    ),
                    if (selectedDocument.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        '${isSwahili ? 'Salio' : 'Balance'}: ${_money(selectedDocument['balance_amount'])}',
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    const SizedBox(height: 12),
                    _dateField(
                      context,
                      controller: _dateController,
                      label: isSwahili ? 'Tarehe *' : 'Payment Date *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _amountController,
                      label: isSwahili ? 'Kiasi *' : 'Amount *',
                      isDarkMode: isDarkMode,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _dropdownMethodField(
                      label: isSwahili
                          ? 'Njia ya Malipo *'
                          : 'Payment Method *',
                      isDarkMode: isDarkMode,
                      value: _paymentMethod,
                      items: methods
                          .map(
                            (item) => DropdownMenuItem<String>(
                              value: item['id']?.toString(),
                              child: Text(_text(item['name'])),
                            ),
                          )
                          .toList(),
                      onChanged: (value) =>
                          setState(() => _paymentMethod = value),
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _referenceController,
                      label: isSwahili
                          ? 'Nambari ya Marejeo'
                          : 'Reference Number',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _bankController,
                      label: isSwahili ? 'Jina la Benki' : 'Bank Name',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _chequeController,
                      label: isSwahili ? 'Nambari ya Cheque' : 'Cheque Number',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _transactionController,
                      label: isSwahili ? 'ID ya Muamala' : 'Transaction ID',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _notesController,
                      label: isSwahili ? 'Maoni' : 'Notes',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      maxLines: 4,
                    ),
                    const SizedBox(height: 18),
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
                              widget.payment == null
                                  ? (isSwahili ? 'Hifadhi' : 'Save')
                                  : (isSwahili ? 'Sasisha' : 'Update'),
                            ),
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
    if (!_formKey.currentState!.validate() ||
        _documentId == null ||
        _paymentMethod == null) {
      return;
    }
    setState(() => _saving = true);
    final payload = <String, dynamic>{
      'document_id': _documentId,
      'payment_date': _dateController.text.trim(),
      'amount': _toDouble(_amountController.text),
      'payment_method': _paymentMethod,
      'reference_number': _blankToNull(_referenceController.text),
      'bank_name': _blankToNull(_bankController.text),
      'cheque_number': _blankToNull(_chequeController.text),
      'transaction_id': _blankToNull(_transactionController.text),
      'notes': _blankToNull(_notesController.text),
    };
    try {
      final api = ref.read(apiClientProvider);
      final id = _toInt(widget.payment?['id']);
      if (id > 0) {
        await api.put('/billing/payments/$id', data: payload);
      } else {
        await api.post('/billing/payments', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            id > 0
                ? (isSwahili ? 'Malipo yamesasishwa' : 'Payment updated')
                : (isSwahili ? 'Malipo yamehifadhiwa' : 'Payment created'),
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
      if (mounted) setState(() => _saving = false);
    }
  }
}

Widget _textField({
  required TextEditingController controller,
  required String label,
  required bool isDarkMode,
  bool isRequired = true,
  int maxLines = 1,
  TextInputType? keyboardType,
}) {
  return TextFormField(
    controller: controller,
    maxLines: maxLines,
    keyboardType: keyboardType,
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
      if (!isRequired) return null;
      if ((value ?? '').trim().isEmpty) return 'Required';
      return null;
    },
  );
}

Widget _dropdownField({
  required String label,
  required bool isDarkMode,
  required int? value,
  required List<DropdownMenuItem<int?>> items,
  required ValueChanged<int?> onChanged,
}) {
  return DropdownButtonFormField<int?>(
    value: value,
    items: items,
    isExpanded: true,
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
    ),
    validator: (value) => value == null ? 'Required' : null,
    onChanged: onChanged,
  );
}

Widget _dropdownMethodField({
  required String label,
  required bool isDarkMode,
  required String? value,
  required List<DropdownMenuItem<String>> items,
  required ValueChanged<String?> onChanged,
}) {
  return DropdownButtonFormField<String>(
    value: value,
    items: items,
    isExpanded: true,
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
    ),
    validator: (value) => value == null || value.isEmpty ? 'Required' : null,
    onChanged: onChanged,
  );
}

Widget _dateField(
  BuildContext context, {
  required TextEditingController controller,
  required String label,
  required bool isDarkMode,
}) {
  return TextFormField(
    controller: controller,
    readOnly: true,
    decoration: InputDecoration(
      labelText: label,
      suffixIcon: const Icon(Icons.calendar_today_outlined),
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
    ),
    validator: (value) => (value ?? '').trim().isEmpty ? 'Required' : null,
    onTap: () async {
      final initialDate = DateTime.tryParse(controller.text) ?? DateTime.now();
      final picked = await showDatePicker(
        context: context,
        initialDate: initialDate,
        firstDate: DateTime(2020),
        lastDate: DateTime(2100),
      );
      if (picked != null) {
        controller.text = picked.toIso8601String().split('T').first;
      }
    },
  );
}

String _text(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? '-' : text;
}

String _getClientName(Map<String, dynamic>? client) {
  if (client == null) return '-';

  // Try full_name first
  final fullName = client['full_name']?.toString();
  if (fullName != null && fullName.isNotEmpty && fullName.trim().isNotEmpty) {
    return fullName.trim();
  }

  // Try first_name + last_name (ProjectClient)
  final firstName = (client['first_name'] ?? '').toString().trim();
  final lastName = (client['last_name'] ?? '').toString().trim();
  if (firstName.isNotEmpty || lastName.isNotEmpty) {
    return '$firstName $lastName'.trim();
  }

  // Try contact_person (BillingClient)
  final contactPerson = (client['contact_person'] ?? '').toString().trim();
  if (contactPerson.isNotEmpty) {
    return contactPerson;
  }

  // Try company_name (BillingClient)
  final companyName = (client['company_name'] ?? '').toString().trim();
  if (companyName.isNotEmpty) {
    return companyName;
  }

  return '-';
}

String _money(dynamic value) {
  final amount = _toDouble(value);
  return 'TZS ${amount.toStringAsFixed(2)}';
}

String _dateValue(dynamic value) {
  final text = value?.toString() ?? '';
  return text.isEmpty ? '' : text.split('T').first;
}

String _numberText(dynamic value) {
  if (value == null) return '';
  final amount = _toDouble(value);
  if (amount == amount.truncateToDouble()) {
    return amount.toInt().toString();
  }
  return amount.toStringAsFixed(2);
}

double _toDouble(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0;
}

int _toInt(dynamic value) {
  if (value == null) return 0;
  if (value is int) return value;
  return int.tryParse(value.toString()) ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = _toInt(value);
  return parsed <= 0 ? null : parsed;
}

String? _blankToNull(String? value) {
  final text = value?.trim() ?? '';
  return text.isEmpty ? null : text;
}
