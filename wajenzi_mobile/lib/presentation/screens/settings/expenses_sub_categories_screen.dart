import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _expensesSubCategoriesSearchProvider = StateProvider<String>((ref) => '');

final _expensesSubCategoriesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/expenses-sub-categories');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _expensesSubCategoryReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/expenses-sub-categories/reference-data');
      final payload = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final data = payload['data'] is Map<String, dynamic>
          ? payload['data'] as Map<String, dynamic>
          : const <String, dynamic>{};
      return data;
    });

class ExpensesSubCategoriesScreen extends ConsumerWidget {
  const ExpensesSubCategoriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final asyncData = ref.watch(_expensesSubCategoriesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref
        .watch(_expensesSubCategoriesSearchProvider)
        .trim()
        .toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili ? 'Kundi la Masuala Madogo' : 'Expense Sub Categories',
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openForm(context, ref),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza Kundi' : 'Add Sub Category',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_expensesSubCategoriesProvider);
          ref.invalidate(_expensesSubCategoryReferenceProvider);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref
                              .read(
                                _expensesSubCategoriesSearchProvider.notifier,
                              )
                              .state =
                          value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta kundi...'
                        : 'Search sub categories...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(
                                          _expensesSubCategoriesSearchProvider
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
              ),
            ),
            asyncData.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.error_outline,
                        size: 64,
                        color: Colors.grey[400],
                      ),
                      const SizedBox(height: 16),
                      Text('$error', textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () =>
                            ref.invalidate(_expensesSubCategoriesProvider),
                        child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                      ),
                    ],
                  ),
                ),
              ),
              data: (items) {
                final filtered = search.isEmpty
                    ? items
                    : items.where((item) {
                        final name =
                            item['name']?.toString().toLowerCase() ?? '';
                        final category =
                            item['expenses_category_name']
                                ?.toString()
                                .toLowerCase() ??
                            '';
                        return name.contains(search) ||
                            category.contains(search);
                      }).toList();

                if (filtered.isEmpty) {
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
                            items.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna kundi'
                                      : 'No sub categories found')
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
                                            _expensesSubCategoriesSearchProvider
                                                .notifier,
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
                      final item = filtered[index];
                      return _ExpenseSubCategoryCard(
                        item: item,
                        index: index,
                        onEdit: () => _openForm(context, ref, item: item),
                        onDelete: () => _deleteItem(context, ref, item),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      );
                    }, childCount: filtered.length),
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
    Map<String, dynamic>? item,
  }) async {
    final refs = await ref.read(_expensesSubCategoryReferenceProvider.future);
    if (!context.mounted) return;

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.72,
        child: _ExpenseSubCategoryFormSheet(item: item, refs: refs),
      ),
    );
    if (result == true) {
      ref.invalidate(_expensesSubCategoriesProvider);
      ref.invalidate(_expensesSubCategoryReferenceProvider);
    }
  }

  Future<void> _deleteItem(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Kundi' : 'Delete Expense Sub Category'),
        content: Text(
          isSwahili ? 'Futa ${item['name']}?' : 'Delete ${item['name']}?',
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
          .delete('/expenses-sub-categories/${item['id']}');
      ref.invalidate(_expensesSubCategoriesProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili ? 'Kundi limefutwa' : 'Sub category deleted successfully',
          ),
          backgroundColor: AppColors.success,
        ),
      );
    } catch (error) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _ExpenseSubCategoryCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final bool isSwahili;
  final bool isDarkMode;

  const _ExpenseSubCategoryCard({
    required this.item,
    required this.index,
    required this.onEdit,
    required this.onDelete,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final isFinancial = (item['is_financial'] ?? 'NO').toString();
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 10,
        ),
        leading: Container(
          width: 38,
          height: 38,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            '${index + 1}',
            style: const TextStyle(
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
        ),
        title: Text(
          item['name']?.toString() ?? '-',
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                item['expenses_category_name']?.toString() ?? 'No category',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(color: Colors.grey[600], fontSize: 13),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: isFinancial == 'YES'
                      ? AppColors.success.withValues(alpha: 0.12)
                      : Colors.grey.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  isFinancial == 'YES'
                      ? (isSwahili ? 'Imetoa' : 'Deducted')
                      : (isSwahili ? 'Haijatoa' : 'Not deducted'),
                  style: TextStyle(
                    color: isFinancial == 'YES'
                        ? AppColors.success
                        : AppColors.textSecondary,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        ),
        trailing: PopupMenuButton<String>(
          onSelected: (value) {
            if (value == 'edit') {
              onEdit();
            } else if (value == 'delete') {
              onDelete();
            }
          },
          itemBuilder: (_) => [
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
      ),
    );
  }
}

class _ExpenseSubCategoryFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  final Map<String, dynamic> refs;

  const _ExpenseSubCategoryFormSheet({this.item, required this.refs});

  @override
  ConsumerState<_ExpenseSubCategoryFormSheet> createState() =>
      _ExpenseSubCategoryFormSheetState();
}

class _ExpenseSubCategoryFormSheetState
    extends ConsumerState<_ExpenseSubCategoryFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  int? _expensesCategoryId;
  String _isFinancial = 'NO';
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  List<Map<String, dynamic>> get _categories =>
      _toMaps(widget.refs['expenses_categories']);
  List<Map<String, dynamic>> get _financialOptions =>
      _toMaps(widget.refs['financial_options']);

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
    _expensesCategoryId = _asInt(widget.item?['expenses_category_id']);
    _isFinancial = widget.item?['is_financial']?.toString() ?? 'NO';
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Form(
            key: _formKey,
            child: ListView(
              children: [
                Center(
                  child: Container(
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  _isEdit
                      ? (isSwahili
                            ? 'Hariri Kundi'
                            : 'Edit Expense Sub Category')
                      : (isSwahili
                            ? 'Unda Kundi Mpya'
                            : 'Create New Expense Sub Category'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Jina la Kundi'
                        : 'Sub Category Name',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? (isSwahili
                            ? 'Jina linahitajika'
                            : 'Sub category name is required')
                      : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value:
                      _categories.any(
                        (cat) => _asInt(cat['id']) == _expensesCategoryId,
                      )
                      ? _expensesCategoryId
                      : null,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kundi Kuu' : 'Parent Category',
                    border: const OutlineInputBorder(),
                  ),
                  items: _categories
                      .map(
                        (category) => DropdownMenuItem<int>(
                          value: _asInt(category['id']),
                          child: Text(category['name']?.toString() ?? '-'),
                        ),
                      )
                      .toList(),
                  onChanged: (value) =>
                      setState(() => _expensesCategoryId = value),
                  validator: (value) => value == null
                      ? (isSwahili
                            ? 'Kundi linahitajika'
                            : 'Category is required')
                      : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value:
                      _financialOptions.any(
                        (opt) => opt['value']?.toString() == _isFinancial,
                      )
                      ? _isFinancial
                      : null,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Inatoa?' : 'Is Deducted',
                    border: const OutlineInputBorder(),
                  ),
                  items: _financialOptions
                      .map(
                        (option) => DropdownMenuItem<String>(
                          value: option['value']?.toString() ?? 'NO',
                          child: Text(option['label']?.toString() ?? 'NO'),
                        ),
                      )
                      .toList(),
                  onChanged: (value) =>
                      setState(() => _isFinancial = value ?? 'NO'),
                  validator: (value) => (value == null || value.isEmpty)
                      ? (isSwahili
                            ? 'Uchaguzi unahitajika'
                            : 'Selection is required')
                      : null,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? (isSwahili ? 'Inahifadhi...' : 'Saving...')
                          : (_isEdit
                                ? (isSwahili
                                      ? 'Sasisha Kundi'
                                      : 'Update Sub Category')
                                : (isSwahili
                                      ? 'Hifadhi Kundi'
                                      : 'Save Sub Category')),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);

    final payload = {
      'name': _nameController.text.trim(),
      'expenses_category_id': _expensesCategoryId,
      'is_financial': _isFinancial,
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put(
          '/expenses-sub-categories/${widget.item!['id']}',
          data: payload,
        );
      } else {
        await api.post('/expenses-sub-categories', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  List<Map<String, dynamic>> _toMaps(dynamic value) {
    if (value is! List) return const [];
    return value
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  int? _asInt(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    return int.tryParse(value.toString());
  }
}
