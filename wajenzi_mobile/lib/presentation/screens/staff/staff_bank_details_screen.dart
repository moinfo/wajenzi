import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _staffBankSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _staffBankDetailsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/staff-bank-details');
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

final _staffBankRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>(
  (ref) async {
    final api = ref.watch(apiClientProvider);
    try {
      final response = await api.get('/staff-bank-details/reference-data');
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

class StaffBankDetailsScreen extends ConsumerStatefulWidget {
  const StaffBankDetailsScreen({super.key});

  @override
  ConsumerState<StaffBankDetailsScreen> createState() =>
      _StaffBankDetailsScreenState();
}

class _StaffBankDetailsScreenState
    extends ConsumerState<StaffBankDetailsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final detailsAsync = ref.watch(_staffBankDetailsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_staffBankSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Akaunti za Benki' : 'Staff Bank Details'),
      ),
      floatingActionButton: detailsAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: isSwahili ? 'Ongeza Taarifa' : 'Add Bank Detail',
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza Taarifa' : 'Add Bank Detail',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_staffBankDetailsProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_staffBankSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta taarifa za benki...'
                        : 'Search bank details...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(_staffBankSearchProvider.notifier)
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
              ),
            ),
            detailsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _StaffBankErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_staffBankDetailsProvider),
                ),
              ),
              data: (payload) {
                if (payload['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: Center(
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
                                  ? 'Staff Bank Details haipatikani kwenye live API kwa sasa.'
                                  : 'Staff Bank Details is not available on the live API right now.',
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
                    ),
                  );
                }

                final details = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final filteredDetails = details.where((detail) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      detail['staff_name'] ?? '',
                      detail['bank_name'] ?? '',
                      detail['account_number'] ?? '',
                      detail['branch'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  return true;
                }).toList();

                if (filteredDetails.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
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
                            details.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna taarifa za benki'
                                      : 'No bank details found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () =>
                                  ref
                                          .read(
                                            _staffBankSearchProvider.notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa utafutaji' : 'Clear search',
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
                      final detail = filteredDetails[index];
                      return _StaffBankCard(
                        detail: detail,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () => _openForm(context, ref, detail: detail),
                        onDelete: () => _deleteDetail(context, ref, detail),
                        onTap: () => _showDetails(
                          context,
                          detail,
                          isDarkMode,
                          isSwahili,
                        ),
                      );
                    }, childCount: filteredDetails.length),
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
    Map<String, dynamic>? detail,
  }) async {
    final refs = await ref.read(_staffBankRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _StaffBankDetailFormSheet(refs: refs, detail: detail),
    );
    if (result == true) ref.invalidate(_staffBankDetailsProvider);
  }

  Future<void> _deleteDetail(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> detail,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Taarifa za Benki' : 'Delete Bank Detail'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta taarifa za "${detail['staff_name']}"?'
              : 'Delete bank detail for "${detail['staff_name']}"?',
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
          .delete('/staff-bank-details/${detail['id']}');
      ref.invalidate(_staffBankDetailsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili ? 'Taarifa za benki zimefutwa' : 'Bank detail deleted',
            ),
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
    Map<String, dynamic> detail,
    bool isDarkMode,
    bool isSwahili,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.58,
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
                        detail['staff_name']?.toString() ?? 'Bank Detail',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 18),
                      _BankDetailRow(
                        'Bank',
                        detail['bank_name']?.toString() ?? 'N/A',
                        isDarkMode,
                      ),
                      _BankDetailRow(
                        'Account Number',
                        detail['account_number']?.toString() ?? 'N/A',
                        isDarkMode,
                      ),
                      _BankDetailRow(
                        'Branch',
                        detail['branch']?.toString() ?? 'N/A',
                        isDarkMode,
                      ),
                      _BankDetailRow(
                        'Created',
                        _formatDate(detail['created_at']?.toString()),
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

class _StaffBankErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _StaffBankErrorView({
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

class _StaffBankCard extends StatelessWidget {
  final Map<String, dynamic> detail;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _StaffBankCard({
    required this.detail,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
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
                    Text(
                      detail['staff_name']?.toString() ?? '-',
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
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.blue.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        detail['bank_name']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: Colors.blue,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Icon(
                          Icons.credit_card,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          detail['account_number']?.toString() ?? '-',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                    if (detail['branch']?.toString().isNotEmpty ?? false) ...[
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(
                            Icons.location_on,
                            size: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            detail['branch']?.toString() ?? '-',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white54
                                  : AppColors.textSecondary,
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

class _BankDetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _BankDetailRow(this.label, this.value, this.isDarkMode);

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

class _StaffBankDetailFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? detail;

  const _StaffBankDetailFormSheet({required this.refs, this.detail});

  @override
  ConsumerState<_StaffBankDetailFormSheet> createState() =>
      _StaffBankDetailFormSheetState();
}

class _StaffBankDetailFormSheetState
    extends ConsumerState<_StaffBankDetailFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _accountNumberController;
  late final TextEditingController _branchController;
  int? _staffId;
  int? _bankId;
  bool _saving = false;

  bool get _isEdit => widget.detail != null;

  @override
  void initState() {
    super.initState();
    _accountNumberController = TextEditingController(
      text: widget.detail?['account_number']?.toString() ?? '',
    );
    _branchController = TextEditingController(
      text: widget.detail?['branch']?.toString() ?? '',
    );
    _staffId = _toNullableInt(widget.detail?['staff_id']);
    _bankId = _toNullableInt(widget.detail?['bank_id']);
  }

  @override
  void dispose() {
    _accountNumberController.dispose();
    _branchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final staffs = _toMaps(widget.refs['staffs']);
    final banks = _toMaps(widget.refs['banks']);

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
      height: 0.84 * MediaQuery.of(context).size.height,
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
                          ? (isSwahili
                                ? 'Hariri Taarifa za Benki'
                                : 'Edit Bank Detail')
                          : (isSwahili
                                ? 'Taarifa Mpya za Benki'
                                : 'New Bank Detail'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value:
                          staffs.any((item) => _toInt(item['id']) == _staffId)
                          ? _staffId
                          : null,
                      decoration: inputStyle(isSwahili ? 'Staff *' : 'Staff *'),
                      dropdownColor: bgColor,
                      validator: (selected) => selected == null
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      items: staffs
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
                      onChanged: (value) => setState(() => _staffId = value),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value: banks.any((item) => _toInt(item['id']) == _bankId)
                          ? _bankId
                          : null,
                      decoration: inputStyle(isSwahili ? 'Bank *' : 'Bank *'),
                      dropdownColor: bgColor,
                      validator: (selected) => selected == null
                          ? (isSwahili ? 'Hitaji' : 'Required')
                          : null,
                      items: banks
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
                      onChanged: (value) => setState(() => _bankId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _accountNumberController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili
                                ? 'Jina linahitajika'
                                : 'Name is required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Namba ya Akaunti *' : 'Account Number *',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _branchController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili
                                ? 'Jina linahitajika'
                                : 'Name is required')
                          : null,
                      decoration: inputStyle(isSwahili ? 'Tawi *' : 'Branch *'),
                      style: TextStyle(color: textColor),
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
        'staff_id': _staffId,
        'bank_id': _bankId,
        'account_number': _accountNumberController.text.trim(),
        'branch': _branchController.text.trim(),
      };

      if (_isEdit) {
        await api.put(
          '/staff-bank-details/${widget.detail!['id']}',
          data: data,
        );
      } else {
        await api.post('/staff-bank-details', data: data);
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

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    // Handle format like "2026-03-31 00:00:00"
    String normalized = raw.replaceAll(' ', 'T');
    return DateFormat('dd MMM yyyy').format(DateTime.parse(normalized));
  } catch (_) {
    // Try to extract just the date part
    final datePart = raw.split(' ').first;
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(datePart));
    } catch (_) {
      return raw;
    }
  }
}
