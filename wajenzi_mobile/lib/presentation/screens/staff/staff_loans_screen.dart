import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _staffLoansProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/staff-loans');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final items = data['data'] as List? ?? const [];
        return {
          'items': items
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return {
            'items': const <Map<String, dynamic>>[],
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _staffLoanRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>(
  (ref) async {
    final api = ref.watch(apiClientProvider);
    try {
      final response = await api.get('/staff-loans/reference-data');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    } on DioException catch (error) {
      if ((error.response?.statusCode ?? 0) == 404) {
        return const <String, dynamic>{};
      }
      rethrow;
    }
  },
);

final _searchQueryProvider = StateProvider.autoDispose<String>((ref) => '');
final _filterStatusProvider = StateProvider.autoDispose<String?>((ref) => null);

class StaffLoansScreen extends ConsumerStatefulWidget {
  const StaffLoansScreen({super.key});

  @override
  ConsumerState<StaffLoansScreen> createState() => _StaffLoansScreenState();
}

class _StaffLoansScreenState extends ConsumerState<StaffLoansScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final itemsAsync = ref.watch(_staffLoansProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final searchQuery = ref.watch(_searchQueryProvider);
    final filterStatus = ref.watch(_filterStatusProvider);

    const statusFilters = ['PENDING', 'APPROVED', 'REJECTED', 'PAID'];

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Mikopo ya Wafanyakazi' : 'Staff Loans'),
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
                      ref.read(_searchQueryProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili ? 'Tafuta mkopo...' : 'Search loans...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(_searchQueryProvider.notifier).state =
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
                SizedBox(
                  height: 40,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    children: [
                      _FilterChip(
                        label: isSwahili ? 'Zote' : 'All',
                        isSelected: filterStatus == null,
                        onTap: () =>
                            ref.read(_filterStatusProvider.notifier).state =
                                null,
                        isDarkMode: isDarkMode,
                      ),
                      const SizedBox(width: 8),
                      ...statusFilters.map(
                        (status) => Padding(
                          padding: const EdgeInsets.only(right: 8),
                          child: _FilterChip(
                            label: status,
                            isSelected: filterStatus == status,
                            onTap: () =>
                                ref.read(_filterStatusProvider.notifier).state =
                                    status,
                            isDarkMode: isDarkMode,
                            color: _getStatusColor(status),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => ref.invalidate(_staffLoansProvider),
              child: itemsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_staffLoansProvider),
                  isSwahili: isSwahili,
                ),
                data: (payload) {
                  if (payload['unavailable_on_live'] == true) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.account_balance_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Staff Loans haipatikani kwenye live API kwa sasa.'
                                  : 'Staff Loans is not available on the live API right now.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[700],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  final items = (payload['items'] as List)
                      .cast<Map<String, dynamic>>();
                  final filtered = items.where((item) {
                    final query = searchQuery.toLowerCase();
                    final matchesSearch =
                        query.isEmpty ||
                        (item['staff_name']?.toString().toLowerCase().contains(
                              query,
                            ) ??
                            false) ||
                        (item['document_number']
                                ?.toString()
                                .toLowerCase()
                                .contains(query) ??
                            false);
                    final matchesStatus =
                        filterStatus == null ||
                        item['status']?.toString().toUpperCase() ==
                            filterStatus;
                    return matchesSearch && matchesStatus;
                  }).toList();

                  if (filtered.isEmpty) {
                    return ListView(
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.2,
                        ),
                        Icon(
                          searchQuery.isNotEmpty || filterStatus != null
                              ? Icons.search_off
                              : Icons.account_balance_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          searchQuery.isNotEmpty || filterStatus != null
                              ? (isSwahili
                                    ? 'Hakuna matokeo'
                                    : 'No results found')
                              : (isSwahili
                                    ? 'Hakuna mikopo ya wafanyakazi'
                                    : 'No staff loans found'),
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
                    itemCount: filtered.length,
                    itemBuilder: (context, index) {
                      final item = filtered[index];
                      final canMutate = !_isLockedStatus(
                        item['status']?.toString(),
                      );
                      return _StaffLoanCard(
                        item: item,
                        index: index + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        canMutate: canMutate,
                        onView: () => _showViewSheet(
                          context,
                          item,
                          isDarkMode,
                          isSwahili,
                        ),
                        onEdit: canMutate
                            ? () => _openForm(context, ref, item: item)
                            : null,
                        onDelete: canMutate
                            ? () => _deleteItem(context, ref, item)
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
          onPressed: () => _openForm(context, ref),
          backgroundColor: AppColors.primary,
          child: const Icon(Icons.add, color: Colors.white),
        ),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final refs = await ref.read(_staffLoanRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.9,
        child: _StaffLoanFormSheet(refs: refs, item: item),
      ),
    );
    if (result == true) ref.invalidate(_staffLoansProvider);
  }

  void _showViewSheet(
    BuildContext context,
    Map<String, dynamic> item,
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
                    padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                    children: [
                      Row(
                        children: [
                          CircleAvatar(
                            radius: 28,
                            backgroundColor: AppColors.primary.withValues(
                              alpha: 0.1,
                            ),
                            child: const Icon(
                              Icons.savings,
                              color: AppColors.primary,
                              size: 28,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item['staff_name']?.toString() ?? '-',
                                  style: const TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                _StatusBadge(
                                  status: item['status']?.toString() ?? '-',
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                      _DetailRow(
                        icon: Icons.receipt_long_outlined,
                        label: isSwahili
                            ? 'Nambari ya Hati'
                            : 'Document Number',
                        value: item['document_number']?.toString() ?? '-',
                        isDarkMode: isDarkMode,
                      ),
                      _DetailRow(
                        icon: Icons.calendar_today_outlined,
                        label: isSwahili ? 'Tarehe' : 'Date',
                        value: _formatDate(item['date']?.toString()),
                        isDarkMode: isDarkMode,
                      ),
                      _DetailRow(
                        icon: Icons.attach_money_outlined,
                        label: isSwahili ? 'Kiasi cha Mkopo' : 'Loan Amount',
                        value: 'TZS ${_formatMoney(item['amount'])}',
                        isDarkMode: isDarkMode,
                        valueColor: AppColors.success,
                      ),
                      _DetailRow(
                        icon: Icons.money_outlined,
                        label: isSwahili ? 'Makato/Mwezi' : 'Deduction/Month',
                        value: 'TZS ${_formatMoney(item['deduction'])}',
                        isDarkMode: isDarkMode,
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

  Future<void> _deleteItem(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : null,
        title: Text(
          isSwahili ? 'Futa Mkopo' : 'Delete Staff Loan',
          style: TextStyle(color: isDarkMode ? Colors.white : null),
        ),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta mkopo wa "${item['staff_name']}"?'
              : 'Are you sure you want to delete loan for "${item['staff_name']}"?',
          style: TextStyle(color: isDarkMode ? Colors.white70 : null),
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
      await ref.read(apiClientProvider).delete('/staff-loans/${item['id']}');
      ref.invalidate(_staffLoansProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Mkopo umefutwa' : 'Staff loan deleted'),
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

class _StatusBadge extends StatelessWidget {
  final String status;

  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final color = _getStatusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        status,
        style: TextStyle(
          fontSize: 12,
          color: color,
          fontWeight: FontWeight.w600,
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
  final Color? color;

  const _FilterChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    final chipColor = color ?? AppColors.primary;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected
              ? chipColor
              : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[200]),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: isSelected
                ? Colors.white
                : (isDarkMode ? Colors.white : Colors.grey[700]),
          ),
        ),
      ),
    );
  }
}

class _StaffLoanCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final bool canMutate;
  final VoidCallback onView;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  const _StaffLoanCard({
    required this.item,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.canMutate,
    required this.onView,
    this.onEdit,
    this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final status = item['status']?.toString() ?? '-';
    final statusColor = _getStatusColor(status);

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
        onTap: onView,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '$index',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
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
                      item['staff_name']?.toString() ?? '-',
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
                      item['document_number']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Text(
                          'TZS ${_formatMoney(item['amount'])}',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: AppColors.success,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: statusColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            status,
                            style: TextStyle(
                              fontSize: 10,
                              color: statusColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              PopupMenuButton<String>(
                icon: Icon(
                  Icons.more_vert,
                  color: isDarkMode ? Colors.white54 : Colors.grey[500],
                ),
                onSelected: (value) {
                  if (value == 'view') {
                    onView();
                  } else if (value == 'edit' && onEdit != null) {
                    onEdit!();
                  } else if (value == 'delete' && onDelete != null) {
                    onDelete!();
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
                  if (canMutate)
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
                  if (canMutate)
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
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: AppColors.primary),
          const SizedBox(width: 12),
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
                  value,
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

class _StaffLoanFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? item;

  const _StaffLoanFormSheet({required this.refs, this.item});

  @override
  ConsumerState<_StaffLoanFormSheet> createState() =>
      _StaffLoanFormSheetState();
}

class _StaffLoanFormSheetState extends ConsumerState<_StaffLoanFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _amountController = TextEditingController(
    text: widget.item?['amount']?.toString() ?? '',
  );
  late final TextEditingController _deductionController = TextEditingController(
    text: widget.item?['deduction']?.toString() ?? '',
  );
  late final TextEditingController _dateController = TextEditingController(
    text: _dateValue(widget.item?['date']?.toString()),
  );

  int? _staffId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _staffId = _toNullableInt(widget.item?['staff_id']);
  }

  @override
  void dispose() {
    _amountController.dispose();
    _deductionController.dispose();
    _dateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final staffs = _toMaps(widget.refs['staffs']);

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
                padding: EdgeInsets.fromLTRB(
                  20,
                  16,
                  20,
                  MediaQuery.of(context).viewInsets.bottom + 24,
                ),
                children: [
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            CircleAvatar(
                              radius: 24,
                              backgroundColor: AppColors.primary.withValues(
                                alpha: 0.1,
                              ),
                              child: Icon(
                                widget.item == null ? Icons.add : Icons.edit,
                                color: AppColors.primary,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Text(
                              widget.item == null
                                  ? (isSwahili
                                        ? 'Mkopo Mpya'
                                        : 'New Staff Loan')
                                  : (isSwahili
                                        ? 'Hariri Mkopo'
                                        : 'Edit Staff Loan'),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),
                        _buildDropdown(
                          label: isSwahili ? 'Staff *' : 'Staff *',
                          isDarkMode: isDarkMode,
                          staffs: staffs,
                        ),
                        const SizedBox(height: 16),
                        _buildInput(
                          controller: _amountController,
                          label: isSwahili
                              ? 'Kiasi cha Mkopo *'
                              : 'Loan Amount *',
                          isDarkMode: isDarkMode,
                          icon: Icons.attach_money_outlined,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                        ),
                        const SizedBox(height: 16),
                        _buildInput(
                          controller: _deductionController,
                          label: isSwahili
                              ? 'Makato kwa Mwezi *'
                              : 'Deduction Per Month *',
                          isDarkMode: isDarkMode,
                          icon: Icons.money_outlined,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                        ),
                        const SizedBox(height: 16),
                        _buildDateInput(context, isDarkMode, isSwahili),
                        const SizedBox(height: 24),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
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
                                  widget.item == null
                                      ? (isSwahili ? 'Hifadhi' : 'Save')
                                      : (isSwahili ? 'Sasisha' : 'Update'),
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
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

  Widget _buildInput({
    required TextEditingController controller,
    required String label,
    required bool isDarkMode,
    required IconData icon,
    TextInputType? keyboardType,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: (value) =>
          value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 20),
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error),
        ),
      ),
    );
  }

  Widget _buildDateInput(
    BuildContext context,
    bool isDarkMode,
    bool isSwahili,
  ) {
    return TextFormField(
      controller: _dateController,
      readOnly: true,
      validator: (value) =>
          value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: isSwahili ? 'Tarehe *' : 'Date *',
        prefixIcon: const Icon(Icons.calendar_today_outlined, size: 20),
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error),
        ),
      ),
      onTap: () async {
        final initialDate =
            DateTime.tryParse(_dateController.text) ?? DateTime.now();
        final picked = await showDatePicker(
          context: context,
          initialDate: initialDate,
          firstDate: DateTime(2000),
          lastDate: DateTime(2100),
        );
        if (picked != null) {
          _dateController.text = DateFormat('yyyy-MM-dd').format(picked);
        }
      },
    );
  }

  Widget _buildDropdown({
    required String label,
    required bool isDarkMode,
    required List<Map<String, dynamic>> staffs,
  }) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: staffs.any((s) => _toInt(s['id']) == _staffId) ? _staffId : null,
      validator: (selected) => selected == null ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.person_outline, size: 20),
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error),
        ),
      ),
      items: staffs
          .map(
            (staff) => DropdownMenuItem<int>(
              value: _toInt(staff['id']),
              child: Text(
                staff['name']?.toString() ?? '-',
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(),
      onChanged: (value) => setState(() => _staffId = value),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final amount = double.tryParse(
        _amountController.text.replaceAll(',', '').trim(),
      );
      final deduction = double.tryParse(
        _deductionController.text.replaceAll(',', '').trim(),
      );
      if (amount == null ||
          amount <= 0 ||
          deduction == null ||
          deduction <= 0) {
        throw Exception('Invalid amount');
      }

      final data = {
        'staff_id': _staffId,
        'amount': amount,
        'deduction': deduction,
        'date': _dateController.text.trim(),
      };

      if (widget.item == null) {
        await api.post('/staff-loans', data: data);
      } else {
        await api.put('/staff-loans/${widget.item!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      final isSwahili = ref.read(isSwahiliProvider);
      final message =
          error is Exception && error.toString() == 'Exception: Invalid amount'
          ? (isSwahili ? 'Weka kiasi sahihi' : 'Enter valid amounts')
          : vatErrorMessage(error, isSwahili: isSwahili);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(message), backgroundColor: AppColors.error),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  final bool isSwahili;

  const _ErrorView({
    required this.message,
    required this.onRetry,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600]),
            ),
            const SizedBox(height: 24),
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

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}

bool _isLockedStatus(String? status) {
  final normalized = status?.toUpperCase() ?? '';
  return normalized == 'APPROVED' ||
      normalized == 'PAID' ||
      normalized == 'COMPLETED';
}

String _dateValue(String? raw) {
  if (raw == null || raw.isEmpty)
    return DateFormat('yyyy-MM-dd').format(DateTime.now());
  if (raw.length >= 10) return raw.substring(0, 10);
  return raw;
}

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  final date = DateTime.tryParse(raw);
  if (date == null) return raw;
  return DateFormat('dd MMM yyyy').format(date);
}

String _formatMoney(dynamic value) {
  final amount = value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '') ?? 0;
  return NumberFormat('#,##0.##').format(amount);
}

Color _getStatusColor(String status) {
  switch (status.toUpperCase()) {
    case 'APPROVED':
      return AppColors.success;
    case 'REJECTED':
      return AppColors.error;
    case 'PENDING':
      return Colors.orange;
    case 'PAID':
      return AppColors.primary;
    default:
      return Colors.grey;
  }
}
