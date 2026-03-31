import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _payrollAdminSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _payrollAdminStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _payrollAdminProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/payroll-administration');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _payrollAdminRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/payroll-administration/reference-data');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

class PayrollAdministrationScreen extends ConsumerStatefulWidget {
  const PayrollAdministrationScreen({super.key});

  @override
  ConsumerState<PayrollAdministrationScreen> createState() =>
      _PayrollAdministrationScreenState();
}

class _PayrollAdministrationScreenState
    extends ConsumerState<PayrollAdministrationScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final payrollsAsync = ref.watch(_payrollAdminProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_payrollAdminSearchProvider).trim().toLowerCase();
    final statusFilter = ref.watch(_payrollAdminStatusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili ? 'Usimamizi wa Payroll' : 'Payroll Administration',
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openForm(context, ref),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza Payroll' : 'Add Payroll',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_payrollAdminProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) =>
                          ref.read(_payrollAdminSearchProvider.notifier).state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta payroll...'
                            : 'Search payroll...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _payrollAdminSearchProvider
                                                  .notifier,
                                            )
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _StatusChip(
                            label: isSwahili ? 'Zote' : 'All',
                            isSelected: statusFilter == null,
                            onTap: () =>
                                ref
                                        .read(
                                          _payrollAdminStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null,
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'CREATED',
                            isSelected: statusFilter == 'CREATED',
                            onTap: () =>
                                ref
                                        .read(
                                          _payrollAdminStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'CREATED',
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'SUBMITTED',
                            isSelected: statusFilter == 'SUBMITTED',
                            onTap: () =>
                                ref
                                        .read(
                                          _payrollAdminStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'SUBMITTED',
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'APPROVED',
                            isSelected: statusFilter == 'APPROVED',
                            onTap: () =>
                                ref
                                        .read(
                                          _payrollAdminStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'APPROVED',
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'PAID',
                            isSelected: statusFilter == 'PAID',
                            onTap: () =>
                                ref
                                        .read(
                                          _payrollAdminStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'PAID',
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            payrollsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _PayrollAdminErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_payrollAdminProvider),
                ),
              ),
              data: (payrolls) {
                final filteredPayrolls = payrolls.where((payroll) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      payroll['month_name'] ?? '',
                      payroll['year']?.toString() ?? '',
                      payroll['document_number'] ?? '',
                      payroll['payroll_number'] ?? '',
                      payroll['created_by_name'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  if (statusFilter != null) {
                    final status = payroll['status']?.toString() ?? '';
                    if (status != statusFilter) return false;
                  }
                  return true;
                }).toList();

                if (filteredPayrolls.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.payments_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            payrolls.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna payrolls'
                                      : 'No payrolls found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty || statusFilter != null) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () {
                                ref
                                        .read(
                                          _payrollAdminSearchProvider.notifier,
                                        )
                                        .state =
                                    '';
                                ref
                                        .read(
                                          _payrollAdminStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null;
                              },
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa vichujio' : 'Clear filters',
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final payroll = filteredPayrolls[index];
                      return _PayrollCard(
                        payroll: payroll,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () => _openForm(context, ref, payroll: payroll),
                        onDelete: () => _deletePayroll(context, ref, payroll),
                        onTap: () => _showDetails(
                          context,
                          payroll,
                          isDarkMode,
                          isSwahili,
                        ),
                      );
                    }, childCount: filteredPayrolls.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? payroll,
  }) async {
    final refs = await ref.read(_payrollAdminRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _PayrollAdminFormSheet(refs: refs, payroll: payroll),
    );
    if (result == true) ref.invalidate(_payrollAdminProvider);
  }

  Future<void> _deletePayroll(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> payroll,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Payroll' : 'Delete Payroll'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta payroll ya "${payroll['month_name']} ${payroll['year']}"?'
              : 'Delete payroll for "${payroll['month_name']} ${payroll['year']}"?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(
              isSwahili ? 'Futa' : 'Delete',
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
          .delete('/payroll-administration/${payroll['id']}');
      ref.invalidate(_payrollAdminProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Payroll imefutwa' : 'Payroll deleted'),
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

  void _showDetails(
    BuildContext context,
    Map<String, dynamic> payroll,
    bool isDarkMode,
    bool isSwahili,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.64,
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
                        '${payroll['month_name'] ?? '-'} ${payroll['year'] ?? ''}',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 16),
                      _DetailRow(
                        'Document Number',
                        payroll['document_number']?.toString() ?? '-',
                        isDarkMode,
                      ),
                      _DetailRow(
                        'Payroll Number',
                        payroll['payroll_number']?.toString() ?? '-',
                        isDarkMode,
                      ),
                      _DetailRow(
                        'Status',
                        payroll['status']?.toString() ?? '-',
                        isDarkMode,
                      ),
                      _DetailRow(
                        'Submitted Date',
                        _formatDate(payroll['submitted_date']?.toString()),
                        isDarkMode,
                      ),
                      _DetailRow(
                        'Payroll Amount',
                        'TZS ${_formatMoney(payroll['payroll_amount'])}',
                        isDarkMode,
                      ),
                      _DetailRow(
                        'Created By',
                        payroll['created_by_name']?.toString() ?? '-',
                        isDarkMode,
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
}

class _StatusChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final bool isDarkMode;

  const _StatusChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.primary.withValues(alpha: 0.15)
              : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.white),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected
                ? AppColors.primary
                : Colors.grey.withValues(alpha: 0.3),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
            color: isSelected
                ? AppColors.primary
                : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
          ),
        ),
      ),
    );
  }
}

class _PayrollAdminErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _PayrollAdminErrorView({
    required this.message,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ],
      ),
    );
  }
}

class _PayrollCard extends StatelessWidget {
  final Map<String, dynamic> payroll;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _PayrollCard({
    required this.payroll,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  Color _getStatusColor(String? status) {
    switch (status?.toUpperCase()) {
      case 'CREATED':
        return Colors.grey;
      case 'SUBMITTED':
        return Colors.blue;
      case 'APPROVED':
        return Colors.green;
      case 'REJECTED':
        return Colors.red;
      case 'CLOSED':
        return Colors.orange;
      case 'PAID':
        return AppColors.primary;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = payroll['status']?.toString() ?? '-';
    final statusColor = _getStatusColor(status);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '${index + 1}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            '${payroll['month_name'] ?? '-'} ${payroll['year'] ?? ''}',
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: statusColor.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            status,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: statusColor,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      payroll['document_number']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white70
                            : AppColors.textSecondary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Icon(
                          Icons.payments,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'TZS ${_formatMoney(payroll['payroll_amount'])}',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: AppColors.success,
                          ),
                        ),
                      ],
                    ),
                    if (payroll['created_by_name'] != null) ...[
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(
                            Icons.person,
                            size: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            payroll['created_by_name']?.toString() ?? '-',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white54
                                  : AppColors.textHint,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'view') {
                    onTap();
                  } else if (value == 'edit') {
                    onEdit();
                  } else if (value == 'delete') {
                    onDelete();
                  }
                },
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'view',
                    child: Row(
                      children: [
                        const Icon(Icons.visibility_rounded, size: 20),
                        const SizedBox(width: 8),
                        Text(isSwahili ? 'Tazama' : 'View'),
                      ],
                    ),
                  ),
                  PopupMenuItem(
                    value: 'edit',
                    child: Row(
                      children: [
                        const Icon(Icons.edit_rounded, size: 20),
                        const SizedBox(width: 8),
                        Text(isSwahili ? 'Hariri' : 'Edit'),
                      ],
                    ),
                  ),
                  PopupMenuItem(
                    value: 'delete',
                    child: Row(
                      children: [
                        const Icon(
                          Icons.delete_rounded,
                          size: 20,
                          color: AppColors.error,
                        ),
                        const SizedBox(width: 8),
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
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _DetailRow(this.label, this.value, this.isDarkMode);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? 'N/A' : value,
              style: TextStyle(
                fontSize: 14,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PayrollAdminFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? payroll;

  const _PayrollAdminFormSheet({required this.refs, this.payroll});

  @override
  ConsumerState<_PayrollAdminFormSheet> createState() =>
      _PayrollAdminFormSheetState();
}

class _PayrollAdminFormSheetState
    extends ConsumerState<_PayrollAdminFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _documentNumberController;
  late final TextEditingController _payrollNumberController;
  late final TextEditingController _yearController;
  late final TextEditingController _submittedDateController;
  int? _month;
  String _status = 'CREATED';
  bool _saving = false;

  bool get _isEdit => widget.payroll != null;

  @override
  void initState() {
    super.initState();
    _documentNumberController = TextEditingController(
      text: widget.payroll?['document_number']?.toString() ?? '',
    );
    _payrollNumberController = TextEditingController(
      text: widget.payroll?['payroll_number']?.toString() ?? '',
    );
    _yearController = TextEditingController(
      text:
          widget.payroll?['year']?.toString() ?? DateTime.now().year.toString(),
    );
    _submittedDateController = TextEditingController(
      text: _dateValue(widget.payroll?['submitted_date']?.toString()),
    );
    _month = _toNullableInt(widget.payroll?['month']) ?? DateTime.now().month;
    _status = widget.payroll?['status']?.toString().toUpperCase() ?? 'CREATED';
  }

  @override
  void dispose() {
    _documentNumberController.dispose();
    _payrollNumberController.dispose();
    _yearController.dispose();
    _submittedDateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final statuses = _toNameList(widget.refs['statuses']);
    final months = _toMaps(widget.refs['months']);

    final bgColor = isDarkMode ? const Color(0xFF1A1A2E) : Colors.white;
    final inputBg = isDarkMode ? const Color(0xFF0F1923) : Colors.grey[100];
    final textColor = isDarkMode ? Colors.white : AppColors.textPrimary;

    InputDecoration inputStyle(String label) => InputDecoration(
      labelText: label,
      filled: true,
      fillColor: inputBg,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
    );

    return Container(
      height: 0.9 * MediaQuery.of(context).size.height,
      decoration: BoxDecoration(
        color: bgColor,
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
                    MediaQuery.of(context).viewInsets.bottom + 24,
                  ),
                  children: [
                    Text(
                      _isEdit
                          ? (isSwahili ? 'Hariri Payroll' : 'Edit Payroll')
                          : (isSwahili ? 'Payroll Mpya' : 'New Payroll'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: _documentNumberController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Namba ya Hati *' : 'Document Number *',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _payrollNumberController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Namba ya Payroll *' : 'Payroll Number *',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value: months.any((item) => _toInt(item['id']) == _month)
                          ? _month
                          : null,
                      decoration: inputStyle(isSwahili ? 'Mwezi *' : 'Month *'),
                      dropdownColor: bgColor,
                      validator: (selected) => selected == null
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      items: months
                          .map(
                            (item) => DropdownMenuItem<int>(
                              value: _toInt(item['id']),
                              child: Text(
                                item['name']?.toString() ?? '-',
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(color: textColor),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _month = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _yearController,
                      keyboardType: TextInputType.number,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      decoration: inputStyle(isSwahili ? 'Mwaka *' : 'Year *'),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      isExpanded: true,
                      value:
                          statuses.any(
                            (s) => s.toUpperCase() == _status.toUpperCase(),
                          )
                          ? _status.toUpperCase()
                          : (statuses.isNotEmpty ? statuses.first : null),
                      decoration: inputStyle(isSwahili ? 'Hali *' : 'Status *'),
                      dropdownColor: bgColor,
                      validator: (selected) => selected == null
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      items: statuses
                          .map(
                            (item) => DropdownMenuItem<String>(
                              value: item.toUpperCase(),
                              child: Text(
                                item,
                                style: TextStyle(color: textColor),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(
                        () => _status = (value ?? 'CREATED').toUpperCase(),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _submittedDateController,
                      readOnly: true,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      decoration: inputStyle(
                        isSwahili
                            ? 'Tarehe ya Kujisalimisha *'
                            : 'Submitted Date *',
                      ),
                      style: TextStyle(color: textColor),
                      onTap: () async {
                        final initialDate =
                            DateTime.tryParse(_submittedDateController.text) ??
                            DateTime.now();
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: initialDate,
                          firstDate: DateTime(2000),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          _submittedDateController.text = DateFormat(
                            'yyyy-MM-dd',
                          ).format(picked);
                        }
                      },
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _saving ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
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
                              _isEdit
                                  ? (isSwahili ? 'Sasisha' : 'Update')
                                  : (isSwahili ? 'Hifadhi' : 'Save'),
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
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'document_number': _documentNumberController.text.trim(),
        'payroll_number': _payrollNumberController.text.trim(),
        'year': int.tryParse(_yearController.text.trim()),
        'month': _month,
        'status': _status,
        'submitted_date': _submittedDateController.text.trim(),
      };

      if (_isEdit) {
        await api.put(
          '/payroll-administration/${widget.payroll!['id']}',
          data: data,
        );
      } else {
        await api.post('/payroll-administration', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider)),
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

String _formatMoney(dynamic value) {
  final amount = value is num
      ? value.toDouble()
      : double.tryParse('$value') ?? 0;
  return NumberFormat('#,##0.00', 'en').format(amount);
}

String _formatDate(String? date) {
  if (date == null || date.isEmpty) return 'N/A';
  try {
    // Handle format like "2026-03-31 00:00:00"
    String normalized = date.replaceAll(' ', 'T');
    return DateFormat('dd MMM yyyy').format(DateTime.parse(normalized));
  } catch (_) {
    // Try to extract just the date part
    final datePart = date.split(' ').first;
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(datePart));
    } catch (_) {
      return date;
    }
  }
}

String _dateValue(String? raw) {
  if (raw == null || raw.isEmpty) {
    return DateFormat('yyyy-MM-dd').format(DateTime.now());
  }
  try {
    // Handle format like "2026-03-31 00:00:00"
    String normalized = raw.replaceAll(' ', 'T');
    return DateFormat('yyyy-MM-dd').format(DateTime.parse(normalized));
  } catch (_) {
    // Try to extract just the date part
    final datePart = raw.split(' ').first;
    try {
      return DateFormat('yyyy-MM-dd').format(DateTime.parse(datePart));
    } catch (_) {
      return raw;
    }
  }
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

List<String> _toNameList(dynamic value) {
  return _toMaps(value)
      .map((item) => item['name']?.toString() ?? '')
      .where((item) => item.isNotEmpty)
      .toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}
