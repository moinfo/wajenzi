import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

class ExpenseFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final int? categoryId;
  final int? subCategoryId;
  final int? projectId;

  ExpenseFilter({
    this.startDate,
    this.endDate,
    this.categoryId,
    this.subCategoryId,
    this.projectId,
  });

  ExpenseFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    int? categoryId,
    int? subCategoryId,
    int? projectId,
    bool clearStartDate = false,
    bool clearEndDate = false,
    bool clearCategory = false,
    bool clearSubCategory = false,
    bool clearProject = false,
  }) {
    return ExpenseFilter(
      startDate: clearStartDate ? null : (startDate ?? this.startDate),
      endDate: clearEndDate ? null : (endDate ?? this.endDate),
      categoryId: clearCategory ? null : (categoryId ?? this.categoryId),
      subCategoryId: clearSubCategory
          ? null
          : (subCategoryId ?? this.subCategoryId),
      projectId: clearProject ? null : (projectId ?? this.projectId),
    );
  }

  Map<String, String> toQueryParams({bool projectExpensesMode = false}) {
    final params = <String, String>{'per_page': '100'};
    if (startDate != null) {
      params['start_date'] = DateFormat('yyyy-MM-dd').format(startDate!);
    }
    if (endDate != null) {
      params['end_date'] = DateFormat('yyyy-MM-dd').format(endDate!);
    }
    if (categoryId != null) {
      params[projectExpensesMode
          ? 'cost_category_id'
          : 'expenses_category_id'] = categoryId
          .toString();
    }
    if (subCategoryId != null) {
      params['expenses_sub_category_id'] = subCategoryId.toString();
    }
    if (projectId != null) {
      params['project_id'] = projectId.toString();
    }
    return params;
  }
}

final expenseFilterProvider = StateProvider.autoDispose<ExpenseFilter>((ref) {
  return ExpenseFilter();
});

final projectExpensesModeProvider = StateProvider.autoDispose<bool>((ref) {
  return false;
});

final _expensesProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(expenseFilterProvider);
  final projectExpensesMode = ref.watch(projectExpensesModeProvider);
  final response = await api.get(
    projectExpensesMode ? '/project-expenses' : '/expenses',
    queryParameters: filter.toQueryParams(
      projectExpensesMode: projectExpensesMode,
    ),
  );
  final payload = response.data['data'];
  final collection = payload is Map<String, dynamic> ? payload : null;
  final items = collection?['data'] ?? payload;
  final meta =
      collection?['meta'] as Map<String, dynamic>? ??
      response.data['meta'] as Map<String, dynamic>? ??
      {};

  double totalAmount = 0;
  if (items is List) {
    for (var item in items) {
      if (item is Map && item['amount'] != null) {
        totalAmount += _toDouble(item['amount']);
      }
    }
  }

  final uniqueCategories = <int>{};
  if (items is List) {
    for (var item in items) {
      if (item is Map && item['expenses_category'] is Map) {
        final category = item['expenses_category'] as Map;
        final id = category['id'];
        if (id is int) {
          uniqueCategories.add(id);
        }
      }
    }
  }

  return {
    'items': items ?? [],
    'meta': meta,
    'total_amount': totalAmount,
    'categories_count': uniqueCategories.length,
    'records_count': items is List ? items.length : 0,
  };
});

final _expenseReferencesProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final projectExpensesMode = ref.watch(projectExpensesModeProvider);
      final response = await api.get(
        projectExpensesMode
            ? '/project-expenses/categories'
            : '/expenses/categories',
      );
      final data = response.data['data'];
      return data is Map<String, dynamic> ? data : <String, dynamic>{};
    });

class ExpenseListScreen extends ConsumerStatefulWidget {
  final int? projectId;
  final bool projectExpensesMode;

  const ExpenseListScreen({
    super.key,
    this.projectId,
    this.projectExpensesMode = false,
  });

  @override
  ConsumerState<ExpenseListScreen> createState() => _ExpenseListScreenState();
}

class _ExpenseListScreenState extends ConsumerState<ExpenseListScreen> {
  final _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (widget.projectId != null) {
        ref.read(expenseFilterProvider.notifier).state = ref
            .read(expenseFilterProvider)
            .copyWith(projectId: widget.projectId);
      }
      if (widget.projectExpensesMode) {
        ref.read(projectExpensesModeProvider.notifier).state = true;
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final expensesAsync = ref.watch(_expensesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili
              ? (widget.projectExpensesMode ? 'Matumizi za Mradi' : 'Matumizi')
              : (widget.projectExpensesMode ? 'Project Expenses' : 'Expenses'),
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showExpenseForm(context),
          tooltip: isSwahili ? 'Ongeza' : 'Add Expense',
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: Column(
        children: [
          _ExpenseStatsSection(isSwahili: isSwahili, isDarkMode: isDarkMode),
          if (widget.projectExpensesMode)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Chip(
                  label: Text(
                    isSwahili ? 'Matumizi za Mradi' : 'Project Expenses',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  backgroundColor: isDarkMode
                      ? Colors.white10
                      : Colors.grey.withOpacity(0.18),
                ),
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: isSwahili ? 'Tafuta...' : 'Search...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (_searchController.text.isNotEmpty)
                      IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          setState(() {});
                        },
                      ),
                    IconButton(
                      icon: const Icon(Icons.filter_list),
                      onPressed: () => _showFilterSheet(context),
                    ),
                  ],
                ),
                filled: true,
                fillColor: isDarkMode
                    ? Colors.white.withValues(alpha: 0.05)
                    : Colors.grey.withValues(alpha: 0.1),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
              onChanged: (_) => setState(() {}),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => ref.invalidate(_expensesProvider),
              child: expensesAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ExpenseErrorView(
                  error: error,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_expensesProvider),
                ),
                data: (payload) {
                  final allExpenses = (payload['items'] as List)
                      .cast<Map<String, dynamic>>();
                  final searchQuery = _searchController.text.toLowerCase();
                  final expenses = searchQuery.isEmpty
                      ? allExpenses
                      : allExpenses.where((e) {
                          final description =
                              (e['description'] as String? ?? '').toLowerCase();
                          final category =
                              ((e['expenses_category']
                                              as Map?)?['expenses_category_name']
                                          as String? ??
                                      '')
                                  .toLowerCase();
                          final subCategory =
                              ((e['expenses_sub_category']
                                              as Map?)?['expenses_sub_category_name']
                                          as String? ??
                                      '')
                                  .toLowerCase();
                          final amount = (e['amount']?.toString() ?? '')
                              .toLowerCase();
                          return description.contains(searchQuery) ||
                              category.contains(searchQuery) ||
                              subCategory.contains(searchQuery) ||
                              amount.contains(searchQuery);
                        }).toList();

                  if (expenses.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(32),
                      children: [
                        const SizedBox(height: 100),
                        Icon(
                          Icons.receipt_long_outlined,
                          size: 56,
                          color: isDarkMode ? Colors.white24 : Colors.grey[300],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          searchQuery.isEmpty
                              ? (isSwahili
                                    ? 'Hakuna gharama zilizopatikana'
                                    : 'No costs found')
                              : (isSwahili
                                    ? 'Hakuna matokeo ya utafutaji'
                                    : 'No search results'),
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _showExpenseForm(context),
                          icon: const Icon(Icons.add),
                          label: Text(
                            isSwahili ? 'Ongeza Gharama' : 'Add Cost',
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: expenses.length + 1,
                    itemBuilder: (context, index) {
                      if (index == expenses.length)
                        return const SizedBox(height: 90);
                      final expense = expenses[index];
                      return _ExpenseCard(
                        expense: expense,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        isProjectExpenses: widget.projectExpensesMode,
                        onEdit: () =>
                            _showExpenseForm(context, expense: expense),
                        onDelete: () => _deleteExpense(context, ref, expense),
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

  void _showFilterSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _FilterSheet(parentRef: ref),
    );
  }

  void _showExpenseForm(BuildContext context, {Map<String, dynamic>? expense}) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _ExpenseFormSheet(
        expense: expense,
        projectExpensesMode: widget.projectExpensesMode,
      ),
    ).then((result) {
      if (result == true) ref.invalidate(_expensesProvider);
    });
  }

  Future<void> _deleteExpense(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> expense,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete',
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta gharama hii?'
              : 'Are you sure you want to delete this cost?',
          style: TextStyle(
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              isSwahili ? 'Futa' : 'Delete',
              style: const TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );

    if (confirm == true && context.mounted) {
      try {
        final api = ref.read(apiClientProvider);
        await api.delete(
          widget.projectExpensesMode
              ? '/project-expenses/${expense['id']}'
              : '/expenses/${expense['id']}',
        );
        ref.invalidate(_expensesProvider);
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(isSwahili ? 'Gharama imefutwa' : 'Cost deleted'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
          );
        }
      }
    }
  }
}

class _ExpenseStatsSection extends ConsumerWidget {
  final bool isSwahili;
  final bool isDarkMode;

  const _ExpenseStatsSection({
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final expensesAsync = ref.watch(_expensesProvider);

    return expensesAsync.when(
      loading: () => const SizedBox.shrink(),
      error: (_, __) => const SizedBox.shrink(),
      data: (payload) {
        final totalAmount = payload['total_amount'] as double? ?? 0;
        final recordsCount = payload['records_count'] as int? ?? 0;
        final categoriesCount = payload['categories_count'] as int? ?? 0;

        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 10,
              ),
            ],
          ),
          child: Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              SizedBox(
                width: 150,
                child: _StatCard(
                  title: isSwahili ? 'Rekodi' : 'Records',
                  value: '$recordsCount',
                  icon: Icons.receipt,
                  color: const Color(0xFF3498DB),
                  isDarkMode: isDarkMode,
                ),
              ),
              SizedBox(
                width: 150,
                child: _StatCard(
                  title: isSwahili ? 'Jumla (TZS)' : 'Total (TZS)',
                  value: _formatAmount(totalAmount),
                  icon: Icons.attach_money,
                  color: const Color(0xFF27AE60),
                  isDarkMode: isDarkMode,
                ),
              ),
              SizedBox(
                width: 150,
                child: _StatCard(
                  title: isSwahili ? 'Kategoria' : 'Categories',
                  value: '$categoriesCount',
                  icon: Icons.category,
                  color: const Color(0xFF9B59B6),
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String _formatAmount(double amount) {
    if (amount >= 1000000) return '${(amount / 1000000).toStringAsFixed(1)}M';
    if (amount >= 1000) return '${(amount / 1000).toStringAsFixed(1)}K';
    return amount.toStringAsFixed(0);
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;
  final bool isDarkMode;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: isDarkMode ? Colors.white : color,
            ),
          ),
          Text(
            title,
            style: TextStyle(
              fontSize: 10,
              color: isDarkMode ? Colors.white54 : Colors.grey[600],
            ),
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

class _FilterSheet extends ConsumerWidget {
  final WidgetRef parentRef;

  const _FilterSheet({required this.parentRef});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final isProjectExpenses = ref.watch(projectExpensesModeProvider);
    final filter = ref.watch(expenseFilterProvider);
    final referencesAsync = ref.watch(_expenseReferencesProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                isSwahili ? 'Chuja Gharama' : 'Filter Costs',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                isSwahili ? 'Kategoria' : 'Category',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: isDarkMode
                      ? const Color(0xFF2A2A3E)
                      : Colors.grey[100],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: referencesAsync.when(
                  loading: () => const Padding(
                    padding: EdgeInsets.all(16),
                    child: CircularProgressIndicator(),
                  ),
                  error: (_, __) => Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                  ),
                  data: (refs) {
                    final categories = (refs['categories'] as List? ?? const [])
                        .cast<dynamic>();
                    return DropdownButtonHideUnderline(
                      child: DropdownButton<int?>(
                        value: filter.categoryId,
                        hint: Text(
                          isSwahili ? 'All Categories' : 'All Categories',
                        ),
                        isExpanded: true,
                        dropdownColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'All Categories' : 'All Categories',
                            ),
                          ),
                          ...categories.map(
                            (c) => DropdownMenuItem(
                              value: c['id'] as int,
                              child: Text(c['name'] as String? ?? '-'),
                            ),
                          ),
                        ],
                        onChanged: (v) =>
                            parentRef
                                .read(expenseFilterProvider.notifier)
                                .state = filter.copyWith(
                              categoryId: v,
                              clearCategory: v == null,
                            ),
                      ),
                    );
                  },
                ),
              ),
              if (!isProjectExpenses) ...[
                const SizedBox(height: 16),
                Text(
                  isSwahili ? 'Sub Category' : 'Sub Category',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode
                        ? Colors.white70
                        : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    color: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: referencesAsync.when(
                    loading: () => const Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                    error: (_, __) => Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                    ),
                    data: (refs) {
                      final subCategories =
                          (refs['sub_categories'] as List? ?? const [])
                              .cast<dynamic>()
                              .where(
                                (item) =>
                                    filter.categoryId == null ||
                                    item['expenses_category_id'] ==
                                        filter.categoryId,
                              )
                              .toList();
                      return DropdownButtonHideUnderline(
                        child: DropdownButton<int?>(
                          value: filter.subCategoryId,
                          hint: Text(
                            isSwahili
                                ? 'All Sub Categories'
                                : 'All Sub Categories',
                          ),
                          isExpanded: true,
                          dropdownColor: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.white,
                          items: [
                            DropdownMenuItem(
                              value: null,
                              child: Text(
                                isSwahili
                                    ? 'All Sub Categories'
                                    : 'All Sub Categories',
                              ),
                            ),
                            ...subCategories.map(
                              (c) => DropdownMenuItem(
                                value: c['id'] as int,
                                child: Text(c['name'] as String? ?? '-'),
                              ),
                            ),
                          ],
                          onChanged: (v) =>
                              parentRef
                                  .read(expenseFilterProvider.notifier)
                                  .state = filter.copyWith(
                                subCategoryId: v,
                                clearSubCategory: v == null,
                              ),
                        ),
                      );
                    },
                  ),
                ),
              ],
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: Text(
                    isSwahili ? 'Omba' : 'Apply Filters',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ExpenseFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? expense;
  final bool projectExpensesMode;

  const _ExpenseFormSheet({this.expense, this.projectExpensesMode = false});

  @override
  ConsumerState<_ExpenseFormSheet> createState() => _ExpenseFormSheetState();
}

class _ExpenseFormSheetState extends ConsumerState<_ExpenseFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  final _amountController = TextEditingController();
  final _remarksController = TextEditingController();
  int? _selectedProjectId;
  int? _selectedCategoryId;
  int? _selectedSubCategoryId;
  DateTime _selectedDate = DateTime.now();
  bool _loading = false;
  List<dynamic> _projects = [];
  List<dynamic> _categories = [];
  List<dynamic> _subCategories = [];
  bool _loadingData = true;

  late final bool _isEditing;
  int? _expenseId;

  @override
  void initState() {
    super.initState();
    _isEditing = widget.expense != null;
    _selectedDate = DateTime.now();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get(
        widget.projectExpensesMode
            ? '/project-expenses/categories'
            : '/expenses/categories',
      );
      final projectsRes = await api.get('/projects');

      if (mounted) {
        setState(() {
          // The API response has nested structure: data.data.data
          final projectsData = projectsRes.data['data']?['data'] as List? ?? [];
          _projects = projectsData
              .map((p) => {'id': p['id'], 'project_name': p['project_name']})
              .toList();
          final data =
              response.data['data'] as Map<String, dynamic>? ?? const {};
          _categories = data['categories'] as List? ?? [];
          _subCategories = data['sub_categories'] as List? ?? [];
          _loadingData = false;

          if (_isEditing && widget.expense != null) {
            final e = widget.expense!;
            _expenseId = e['id'] as int?;
            _descriptionController.text = e['description'] as String? ?? '';
            _amountController.text = _toDouble(e['amount']).toString();
            _remarksController.text = e['remarks'] as String? ?? '';

            // Debug: Log the original project_id
            print('DEBUG: Original expense project_id: ${e['project_id']}');

            _selectedProjectId = e['project_id'] as int?;

            // Debug: Log the selected project_id
            print('DEBUG: Selected _selectedProjectId: $_selectedProjectId');

            if (widget.projectExpensesMode) {
              _selectedCategoryId =
                  e['expenses_category']?['id'] as int? ??
                  e['cost_category_id'] as int?;
            } else {
              final subCategory =
                  e['expenses_sub_category'] as Map<String, dynamic>?;
              _selectedSubCategoryId =
                  subCategory?['id'] as int? ??
                  e['expenses_sub_category_id'] as int?;
            }

            final dateStr = e['expense_date'] as String?;
            if (dateStr != null && dateStr.isNotEmpty) {
              _selectedDate = DateTime.tryParse(dateStr) ?? DateTime.now();
            }
          }
        });
      }
    } catch (e) {
      if (mounted) setState(() => _loadingData = false);
    }
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _amountController.dispose();
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
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        child: _loadingData
            ? const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator()),
              )
            : SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? Colors.white24
                                : Colors.grey[300],
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        _isEditing
                            ? (isSwahili ? 'Hariri Expense' : 'Edit Expense')
                            : (isSwahili ? 'Expense Mpya' : 'New Expense'),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        isSwahili ? 'Mradi *' : 'Project *',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int?>(
                            value: _selectedProjectId,
                            hint: Text(
                              isSwahili ? 'Chagua Mradi' : 'Select Project',
                            ),
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            items: _projects
                                .map(
                                  (p) => DropdownMenuItem(
                                    value: p['id'] as int,
                                    child: Text(
                                      p['project_name'] as String? ?? '-',
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (v) =>
                                setState(() => _selectedProjectId = v),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        isSwahili ? 'Maelezo *' : 'Description *',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _descriptionController,
                        decoration: InputDecoration(
                          hintText: isSwahili
                              ? 'Maelezo ya gharama'
                              : 'Cost description',
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        validator: (v) => v == null || v.isEmpty
                            ? (isSwahili
                                  ? 'Maelezo yahitajika'
                                  : 'Description required')
                            : null,
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  isSwahili
                                      ? 'Kiasi (TZS) *'
                                      : 'Amount (TZS) *',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: isDarkMode
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                TextFormField(
                                  controller: _amountController,
                                  keyboardType:
                                      const TextInputType.numberWithOptions(
                                        decimal: true,
                                      ),
                                  decoration: InputDecoration(
                                    hintText: '0.00',
                                    filled: true,
                                    fillColor: isDarkMode
                                        ? const Color(0xFF2A2A3E)
                                        : Colors.grey[100],
                                    border: OutlineInputBorder(
                                      borderRadius: BorderRadius.circular(12),
                                      borderSide: BorderSide.none,
                                    ),
                                  ),
                                  validator: (v) {
                                    if (v == null || v.isEmpty)
                                      return isSwahili
                                          ? 'Kiasi yahitajika'
                                          : 'Amount required';
                                    if (double.tryParse(v) == null)
                                      return isSwahili
                                          ? 'Kiasi batili'
                                          : 'Invalid';
                                    return null;
                                  },
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  isSwahili ? 'Tarehe *' : 'Date *',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: isDarkMode
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                InkWell(
                                  onTap: () => _selectDate(context),
                                  borderRadius: BorderRadius.circular(12),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 16,
                                    ),
                                    decoration: BoxDecoration(
                                      color: isDarkMode
                                          ? const Color(0xFF2A2A3E)
                                          : Colors.grey[100],
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      children: [
                                        Icon(
                                          Icons.calendar_today,
                                          size: 18,
                                          color: isDarkMode
                                              ? Colors.white54
                                              : AppColors.textSecondary,
                                        ),
                                        const SizedBox(width: 8),
                                        Text(
                                          DateFormat(
                                            'dd MMM yyyy',
                                          ).format(_selectedDate),
                                          style: TextStyle(
                                            color: isDarkMode
                                                ? Colors.white
                                                : AppColors.textPrimary,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        isSwahili
                            ? (widget.projectExpensesMode
                                  ? 'Category *'
                                  : 'Expense Sub Category *')
                            : (widget.projectExpensesMode
                                  ? 'Category *'
                                  : 'Expense Sub Category *'),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int?>(
                            value: widget.projectExpensesMode
                                ? _selectedCategoryId
                                : _selectedSubCategoryId,
                            hint: Text(
                              isSwahili
                                  ? (widget.projectExpensesMode
                                        ? 'Chagua Category'
                                        : 'Chagua sub category')
                                  : (widget.projectExpensesMode
                                        ? 'Select Category'
                                        : 'Select sub category'),
                            ),
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            items:
                                (widget.projectExpensesMode
                                        ? _categories
                                        : _subCategories)
                                    .map(
                                      (c) => DropdownMenuItem(
                                        value: c['id'] as int,
                                        child: Text(
                                          widget.projectExpensesMode
                                              ? (c['name'] as String? ?? '-')
                                              : '${c['name'] ?? '-'}${(c['category_name'] as String?)?.isNotEmpty == true ? ' - ${c['category_name']}' : ''}',
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    )
                                    .toList(),
                            onChanged: (v) => setState(() {
                              if (widget.projectExpensesMode) {
                                _selectedCategoryId = v;
                              } else {
                                _selectedSubCategoryId = v;
                              }
                            }),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (!widget.projectExpensesMode &&
                          _selectedSubCategoryId != null) ...[
                        Text(
                          isSwahili ? 'Category' : 'Category',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white70
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.grey[100],
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            _selectedCategoryName(),
                            style: TextStyle(
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ),
                        const SizedBox(height: 24),
                      ],
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _loading ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _loading
                              ? const SizedBox(
                                  width: 24,
                                  height: 24,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  _isEditing
                                      ? (isSwahili
                                            ? 'Hifadhi Mabadiliko'
                                            : 'Save Changes')
                                      : (isSwahili
                                            ? 'Unda Expense'
                                            : 'Create Expense'),
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Future<void> _selectDate(BuildContext context) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _selectedDate = picked);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    // Debug: Log the current state
    print(
      'DEBUG: _submit - _isEditing: $_isEditing, _selectedProjectId: $_selectedProjectId',
    );

    // For edit mode, project_id is not required if it wasn't changed
    if (!_isEditing && _selectedProjectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider) ? 'Chagua mradi' : 'Select a project',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    if (widget.projectExpensesMode) {
      if (_selectedCategoryId == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              ref.read(isSwahiliProvider)
                  ? 'Chagua category'
                  : 'Select a category',
            ),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }
    } else {
      if (_selectedSubCategoryId == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              ref.read(isSwahiliProvider)
                  ? 'Chagua sub category'
                  : 'Select a sub category',
            ),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }
    }

    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final Map<String, dynamic> data = {
        'description': _descriptionController.text.trim(),
        'amount': double.parse(_amountController.text),
        'date': DateFormat('yyyy-MM-dd').format(_selectedDate),
        'remarks': _remarksController.text.trim(),
      };

      if (widget.projectExpensesMode) {
        data['cost_category_id'] = _selectedCategoryId;
      } else {
        data['expenses_sub_category_id'] = _selectedSubCategoryId;
      }

      if (_selectedProjectId != null || !_isEditing) {
        data['project_id'] = _selectedProjectId;
      }

      if (_isEditing && _expenseId != null) {
        await api.put(
          widget.projectExpensesMode
              ? '/project-expenses/$_expenseId'
              : '/expenses/$_expenseId',
          data: data,
        );
      } else {
        await api.post(
          widget.projectExpensesMode ? '/project-expenses' : '/expenses',
          data: data,
        );
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _selectedCategoryName() {
    if (widget.projectExpensesMode) {
      final selected = _categories.cast<Map>().firstWhere(
        (item) => item['id'] == _selectedCategoryId,
        orElse: () => <String, dynamic>{},
      );
      return selected['name'] as String? ?? '-';
    }

    final selected = _subCategories.cast<Map>().firstWhere(
      (item) => item['id'] == _selectedSubCategoryId,
      orElse: () => <String, dynamic>{},
    );
    return selected['category_name'] as String? ?? '-';
  }
}

class _ExpenseCard extends StatelessWidget {
  final Map<String, dynamic> expense;
  final bool isSwahili;
  final bool isDarkMode;
  final bool isProjectExpenses;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ExpenseCard({
    required this.expense,
    required this.isSwahili,
    required this.isDarkMode,
    required this.isProjectExpenses,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final description = expense['description'] as String? ?? '-';
    final amount = _toDouble(expense['amount']);
    final status = (expense['status'] as String? ?? 'pending').toLowerCase();
    final date =
        expense['expense_date'] as String? ?? expense['date'] as String?;
    final category =
        (expense['expenses_category'] as Map<String, dynamic>?)?['name']
            as String? ??
        '-';
    final subCategory =
        (expense['expenses_sub_category'] as Map<String, dynamic>?)?['name']
            as String? ??
        '-';
    final notes = expense['document_number'] as String?;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () => _showDetails(context),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF3498DB).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.receipt_long,
                      color: Color(0xFF3498DB),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          description,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '$subCategory - $category',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(status: status, isSwahili: isSwahili),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.calendar_today_rounded,
                        size: 14,
                        color: isDarkMode ? Colors.white38 : AppColors.textHint,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        _formatDate(date),
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                  Text(
                    'TZS ${NumberFormat('#,##0.00', 'en_US').format(amount)}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF27AE60),
                    ),
                  ),
                ],
              ),
              if (notes != null && notes.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  notes,
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode ? Colors.white38 : AppColors.textHint,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const Divider(height: 20),
              Wrap(
                alignment: WrapAlignment.end,
                spacing: 8,
                runSpacing: 8,
                children: [
                  TextButton.icon(
                    onPressed: onEdit,
                    icon: const Icon(Icons.edit, size: 16),
                    label: Text(isSwahili ? 'Hariri' : 'Edit'),
                    style: TextButton.styleFrom(
                      foregroundColor: const Color(0xFF1ABC9C),
                    ),
                  ),
                  const SizedBox(width: 8),
                  TextButton.icon(
                    onPressed: onDelete,
                    icon: const Icon(Icons.delete, size: 16),
                    label: Text(isSwahili ? 'Futa' : 'Delete'),
                    style: TextButton.styleFrom(foregroundColor: Colors.red),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showDetails(BuildContext context) {
    final category =
        (expense['expenses_category'] as Map<String, dynamic>?)?['name']
            as String? ??
        '-';
    final subCategory =
        (expense['expenses_sub_category'] as Map<String, dynamic>?)?['name']
            as String? ??
        '-';
    final documentNumber = expense['document_number'] as String? ?? '-';
    final receiptUrl = expense['receipt_path'] as String?;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.all(20),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                expense['description'] as String? ?? '-',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 16),
              _DetailRow(
                label: isSwahili ? 'Sub Category' : 'Sub Category',
                value: subCategory,
                isDarkMode: isDarkMode,
              ),
              _DetailRow(
                label: isSwahili ? 'Kategoria' : 'Category',
                value: category,
                isDarkMode: isDarkMode,
              ),
              _DetailRow(
                label: isSwahili ? 'Kiasi' : 'Amount',
                value:
                    'TZS ${NumberFormat('#,##0.00', 'en_US').format(_toDouble(expense['amount']))}',
                isDarkMode: isDarkMode,
              ),
              _DetailRow(
                label: isSwahili ? 'Tarehe' : 'Date',
                value: _formatDate(expense['expense_date'] as String?),
                isDarkMode: isDarkMode,
              ),
              _DetailRow(
                label: isSwahili ? 'Hali' : 'Status',
                value: (expense['status'] as String? ?? '-').toUpperCase(),
                isDarkMode: isDarkMode,
              ),
              _DetailRow(
                label: isSwahili ? 'Document No' : 'Document No',
                value: documentNumber,
                isDarkMode: isDarkMode,
              ),
              if (receiptUrl != null && receiptUrl.isNotEmpty)
                _DetailRow(
                  label: isSwahili ? 'Receipt' : 'Receipt',
                  value: receiptUrl,
                  isDarkMode: isDarkMode,
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _StatusBadge({required this.status, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final color =
        {
          'approved': const Color(0xFF27AE60),
          'pending': const Color(0xFFF39C12),
          'rejected': const Color(0xFFE74C3C),
        }.containsKey(status)
        ? {
            'approved': const Color(0xFF27AE60),
            'pending': const Color(0xFFF39C12),
            'rejected': const Color(0xFFE74C3C),
          }[status]
        : const Color(0xFF95A5A6);
    final label =
        {
          'approved': isSwahili ? 'IMEIDHINISHWA' : 'APPROVED',
          'pending': isSwahili ? 'INASUBIRI' : 'PENDING',
          'rejected': isSwahili ? 'IMEKATALIWA' : 'REJECTED',
        }.containsKey(status)
        ? {
            'approved': isSwahili ? 'IMEIDHINISHWA' : 'APPROVED',
            'pending': isSwahili ? 'INASUBIRI' : 'PENDING',
            'rejected': isSwahili ? 'IMEKATALIWA' : 'REJECTED',
          }[status]
        : (isSwahili ? 'RASIMU' : 'DRAFT');

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color?.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label!,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: color,
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
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode
            ? const Color(0xFF252540)
            : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value.isEmpty ? '-' : value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _ExpenseErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ExpenseErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          '$error',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
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

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}
