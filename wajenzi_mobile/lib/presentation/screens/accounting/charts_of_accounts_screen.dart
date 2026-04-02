import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _chartsSearchProvider = StateProvider.autoDispose<String>((ref) => '');
final _chartsAccountTypeFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);
final _chartsStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _chartsOfAccountsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/charts-of-accounts');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final payload = data['data'] is Map
            ? Map<String, dynamic>.from(data['data'] as Map)
            : const <String, dynamic>{};
        return {
          ...payload,
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return const {
            'accounts': <Map<String, dynamic>>[],
            'account_types': <Map<String, dynamic>>[],
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
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

class ChartsOfAccountsScreen extends ConsumerStatefulWidget {
  const ChartsOfAccountsScreen({super.key});

  @override
  ConsumerState<ChartsOfAccountsScreen> createState() =>
      _ChartsOfAccountsScreenState();
}

class _ChartsOfAccountsScreenState
    extends ConsumerState<ChartsOfAccountsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final chartsAsync = ref.watch(_chartsOfAccountsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_chartsSearchProvider).trim().toLowerCase();
    final typeFilter = ref.watch(_chartsAccountTypeFilterProvider);
    final statusFilter = ref.watch(_chartsStatusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Chati ya Akaunti' : 'Charts of Accounts'),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openForm(context, ref),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza' : 'Add',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_chartsOfAccountsProvider),
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
                          ref.read(_chartsSearchProvider.notifier).state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta akaunti...'
                            : 'Search accounts...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _chartsSearchProvider.notifier,
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
                    chartsAsync.when(
                      loading: () => const SizedBox.shrink(),
                      error: (_, __) => const SizedBox.shrink(),
                      data: (payload) {
                        final accountTypes = _toMaps(payload['account_types']);
                        if (accountTypes.isEmpty)
                          return const SizedBox.shrink();
                        return Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: DropdownButton<int?>(
                            value: typeFilter,
                            hint: Text(isSwahili ? 'Aina zote' : 'All types'),
                            underline: const SizedBox(),
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF1A2332)
                                : Colors.white,
                            items: [
                              DropdownMenuItem(
                                value: null,
                                child: Text(
                                  isSwahili ? 'Aina zote' : 'All types',
                                ),
                              ),
                              ...accountTypes.map(
                                (t) => DropdownMenuItem(
                                  value: t['id'] as int,
                                  child: Text('${t['code']} - ${t['type']}'),
                                ),
                              ),
                            ],
                            onChanged: (value) =>
                                ref
                                        .read(
                                          _chartsAccountTypeFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    value,
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
            chartsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _ChartsErrorView(
                  message: _chartErrorMessage(error, isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_chartsOfAccountsProvider),
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
                              Icons.account_tree_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Charts of Accounts haipatikani kwenye live API kwa sasa.'
                                  : 'Charts of Accounts is not available on the live API right now.',
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

                final accountTypes = _toMaps(payload['account_types']);
                final accounts = _toMaps(payload['accounts']);

                final filteredAccounts = accounts.where((account) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      account['code'] ?? '',
                      account['account_name'] ?? '',
                      account['description'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  if (typeFilter != null) {
                    if (_toInt(account['account_type']) != typeFilter)
                      return false;
                  }
                  if (statusFilter != null && statusFilter.isNotEmpty) {
                    if ((account['status'] ?? '').toString().toLowerCase() !=
                        statusFilter.toLowerCase())
                      return false;
                  }
                  return true;
                }).toList();

                if (filteredAccounts.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.account_tree_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            accounts.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna akaunti'
                                      : 'No chart accounts found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty || typeFilter != null) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () {
                                ref.read(_chartsSearchProvider.notifier).state =
                                    '';
                                ref
                                        .read(
                                          _chartsAccountTypeFilterProvider
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

                final filteredTypes = typeFilter != null
                    ? accountTypes
                          .where((t) => _toInt(t['id']) == typeFilter)
                          .toList()
                    : accountTypes;

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final type = filteredTypes[index];
                      final typeAccounts = filteredAccounts
                          .where(
                            (account) =>
                                _toInt(account['account_type']) ==
                                _toInt(type['id']),
                          )
                          .toList();
                      if (typeAccounts.isEmpty) return const SizedBox.shrink();

                      return _AccountTypeCard(
                        type: type,
                        accounts: typeAccounts,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onView: (account) => _showChartAccountDetails(
                          context,
                          account,
                          isSwahili,
                          isDarkMode,
                        ),
                        onEdit: (account) =>
                            _openForm(context, ref, account: account),
                        onDelete: (account) =>
                            _deleteAccount(context, ref, account),
                      );
                    }, childCount: filteredTypes.length),
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
    Map<String, dynamic>? account,
  }) async {
    final payload = await ref.read(_chartsOfAccountsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) =>
          _ChartAccountFormSheet(payload: payload, account: account),
    );

    if (result == true) {
      ref.invalidate(_chartsOfAccountsProvider);
    }
  }

  Future<void> _deleteAccount(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> account,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
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
          .delete('/charts-of-accounts/${account['id']}');
      ref.invalidate(_chartsOfAccountsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili ? 'Akaunti imefutwa' : 'Chart account deleted',
            ),
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

  void _showChartAccountDetails(
    BuildContext context,
    Map<String, dynamic> account,
    bool isSwahili,
    bool isDarkMode,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => Container(
        height: 0.7 * MediaQuery.of(context).size.height,
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
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            '${account['code'] ?? '-'} - ${account['account_name'] ?? '-'}',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _DetailRow(
                      label: isSwahili ? 'Aina ya Akaunti' : 'Account Type',
                      value: account['account_type_name']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Szabuni' : 'Parent',
                      value: account['parent_name']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Sarafu' : 'Currency',
                      value: account['currency_name']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Hali' : 'Status',
                      value: _statusLabel(account['status']?.toString()),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Watoto' : 'Children',
                      value: '${account['children_count'] ?? 0}',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Maelezo' : 'Description',
                      value: account['description']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                  ],
                ),
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

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white54 : AppColors.textHint,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
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

class _AccountTypeCard extends StatelessWidget {
  final Map<String, dynamic> type;
  final List<Map<String, dynamic>> accounts;
  final bool isSwahili;
  final bool isDarkMode;
  final void Function(Map<String, dynamic>) onView;
  final void Function(Map<String, dynamic>) onEdit;
  final void Function(Map<String, dynamic>) onDelete;

  const _AccountTypeCard({
    required this.type,
    required this.accounts,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(
                    Icons.account_tree,
                    color: Color(0xFF3B82F6),
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${type['code'] ?? ''} ${type['type'] ?? ''}'.trim(),
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      Text(
                        '${isSwahili ? 'Salio la Kawaida' : 'Normal Balance'}: ${type['normal_balance'] ?? '-'}',
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '${accounts.length} ${isSwahili ? 'akaunti' : 'accounts'}',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            const Divider(height: 1),
            const SizedBox(height: 8),
            ...accounts
                .take(5)
                .map(
                  (account) => _AccountTile(
                    account: account,
                    isSwahili: isSwahili,
                    isDarkMode: isDarkMode,
                    onView: onView,
                    onEdit: onEdit,
                    onDelete: onDelete,
                  ),
                ),
            if (accounts.length > 5) ...[
              const SizedBox(height: 8),
              Center(
                child: TextButton(
                  onPressed: () => onView(accounts.first),
                  child: Text(
                    isSwahili
                        ? '+${accounts.length - 5} zaidi'
                        : '+${accounts.length - 5} more',
                    style: const TextStyle(fontSize: 13),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _AccountTile extends StatelessWidget {
  final Map<String, dynamic> account;
  final bool isSwahili;
  final bool isDarkMode;
  final void Function(Map<String, dynamic>) onView;
  final void Function(Map<String, dynamic>) onEdit;
  final void Function(Map<String, dynamic>) onDelete;

  const _AccountTile({
    required this.account,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: isDarkMode
            ? const Color(0xFF0F1923)
            : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: _statusColor(account['status']?.toString()),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${account['code'] ?? '-'} - ${account['account_name'] ?? '-'}',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                if (account['currency_name'] != null)
                  Text(
                    account['currency_name'].toString(),
                    style: TextStyle(
                      fontSize: 11,
                      color: isDarkMode ? Colors.white54 : AppColors.textHint,
                    ),
                  ),
              ],
            ),
          ),
          PopupMenuButton<String>(
            icon: Icon(
              Icons.more_vert,
              size: 20,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
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
              PopupMenuItem(
                value: 'view',
                child: Row(
                  children: [
                    const Icon(Icons.visibility, size: 20),
                    const SizedBox(width: 8),
                    Text(isSwahili ? 'Tazama' : 'View'),
                  ],
                ),
              ),
              PopupMenuItem(
                value: 'edit',
                child: Row(
                  children: [
                    const Icon(Icons.edit, size: 20),
                    const SizedBox(width: 8),
                    Text(isSwahili ? 'Hariri' : 'Edit'),
                  ],
                ),
              ),
              PopupMenuItem(
                value: 'delete',
                child: Row(
                  children: [
                    const Icon(Icons.delete, size: 20, color: AppColors.error),
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
    );
  }
}

class _ChartAccountFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> payload;
  final Map<String, dynamic>? account;

  const _ChartAccountFormSheet({required this.payload, this.account});

  @override
  ConsumerState<_ChartAccountFormSheet> createState() =>
      _ChartAccountFormSheetState();
}

class _ChartAccountFormSheetState
    extends ConsumerState<_ChartAccountFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _codeController = TextEditingController(
    text: widget.account?['code']?.toString() ?? '',
  );
  late final TextEditingController _nameController = TextEditingController(
    text: widget.account?['account_name']?.toString() ?? '',
  );
  late final TextEditingController _descriptionController =
      TextEditingController(
        text: widget.account?['description']?.toString() ?? '',
      );
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
    final parentOptions = allAccounts
        .where((item) => _toInt(item['id']) != _toInt(widget.account?['id']))
        .toList();

    final bgColor = isDarkMode ? const Color(0xFF1A2332) : Colors.white;
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
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(context).viewInsets.bottom + 24,
          ),
          child: Form(
            key: _formKey,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 44,
                      height: 5,
                      decoration: BoxDecoration(
                        color: isDarkMode ? Colors.white24 : Colors.black12,
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    widget.account == null
                        ? (isSwahili ? 'Akaunti Mpya' : 'New Chart Account')
                        : (isSwahili ? 'Hariri Akaunti' : 'Edit Chart Account'),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: textColor,
                    ),
                  ),
                  const SizedBox(height: 20),
                  DropdownButtonFormField<int>(
                    value: _accountTypeId,
                    isExpanded: true,
                    decoration: inputStyle(
                      isSwahili ? 'Aina ya Akaunti *' : 'Account Type *',
                    ),
                    dropdownColor: bgColor,
                    items: accountTypes
                        .map(
                          (e) => DropdownMenuItem(
                            value: e['id'] as int,
                            child: Text(
                              '${e['type']} (${e['code']})',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (value) =>
                        setState(() => _accountTypeId = value),
                    validator: (v) => v == null
                        ? (isSwahili ? 'Hitajiwa' : 'Required')
                        : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    value: _parentId,
                    isExpanded: true,
                    decoration: inputStyle(
                      isSwahili ? 'Szabuni ya Akaunti' : 'Parent Account',
                    ),
                    dropdownColor: bgColor,
                    items: [
                      DropdownMenuItem(
                        value: null,
                        child: Text(isSwahili ? 'Hakuna' : 'None'),
                      ),
                      ...parentOptions.map(
                        (e) => DropdownMenuItem(
                          value: e['id'] as int,
                          child: Text(
                            '${e['code']} - ${e['account_name']}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ],
                    onChanged: (value) => setState(() => _parentId = value),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _codeController,
                    decoration: inputStyle(
                      isSwahili ? 'Kodi ya Akaunti *' : 'Account Code *',
                    ),
                    style: TextStyle(color: textColor),
                    validator: (v) => v == null || v.trim().isEmpty
                        ? (isSwahili ? 'Hitajiwa' : 'Required')
                        : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    value: _currencyId,
                    isExpanded: true,
                    decoration: inputStyle(isSwahili ? 'Sarafu' : 'Currency'),
                    dropdownColor: bgColor,
                    items: [
                      DropdownMenuItem(
                        value: null,
                        child: Text(isSwahili ? 'Hakuna' : 'None'),
                      ),
                      ...currencies.map(
                        (e) => DropdownMenuItem(
                          value: e['id'] as int,
                          child: Text(
                            '${e['symbol'] ?? ''} ${e['name'] ?? ''}'.trim(),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ],
                    onChanged: (value) => setState(() => _currencyId = value),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _nameController,
                    decoration: inputStyle(
                      isSwahili ? 'Jina la Akaunti *' : 'Account Name *',
                    ),
                    style: TextStyle(color: textColor),
                    validator: (v) => v == null || v.trim().isEmpty
                        ? (isSwahili ? 'Hitajiwa' : 'Required')
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _descriptionController,
                    maxLines: 3,
                    decoration: inputStyle(
                      isSwahili ? 'Maelezo' : 'Description',
                    ),
                    style: TextStyle(color: textColor),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _status,
                    isExpanded: true,
                    decoration: inputStyle(isSwahili ? 'Hali' : 'Status'),
                    dropdownColor: bgColor,
                    items: const [
                      DropdownMenuItem(value: 'ACTIVE', child: Text('Active')),
                      DropdownMenuItem(
                        value: 'INACTIVE',
                        child: Text('Inactive'),
                      ),
                    ],
                    onChanged: (value) =>
                        setState(() => _status = value ?? 'ACTIVE'),
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
                            widget.account == null
                                ? (isSwahili ? 'Hifadhi' : 'Save')
                                : (isSwahili ? 'Sasisha' : 'Update'),
                          ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = <String, dynamic>{
        'account_type': _accountTypeId,
        'parent': _parentId,
        'code': _codeController.text.trim(),
        'currency': _currencyId,
        'account_name': _nameController.text.trim(),
        'description': _descriptionController.text.trim().isEmpty
            ? null
            : _descriptionController.text.trim(),
        'status': _status,
      };

      if (widget.account == null) {
        await api.post('/charts-of-accounts', data: data);
      } else {
        await api.put(
          '/charts-of-accounts/${widget.account!['id']}',
          data: data,
        );
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _chartErrorMessage(error, ref.read(isSwahiliProvider)),
            ),
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
      .map(
        (word) => word.isEmpty
            ? word
            : '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}',
      )
      .join(' ');
}
