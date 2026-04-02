import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _boqItemsSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _boqItemsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/boq-items');
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

final _boqItemRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/boq-items/reference-data');
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
});

class BoqItemsScreen extends ConsumerStatefulWidget {
  const BoqItemsScreen({super.key});

  @override
  ConsumerState<BoqItemsScreen> createState() => _BoqItemsScreenState();
}

class _BoqItemsScreenState extends ConsumerState<BoqItemsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final itemsAsync = ref.watch(_boqItemsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_boqItemsSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'BOQ Items' : 'BOQ Items'),
      ),
      floatingActionButton: itemsAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: isSwahili ? 'Add BOQ Item' : 'Add BOQ Item',
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Add BOQ Item' : 'Add BOQ Item',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_boqItemsProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_boqItemsSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta BOQ items...'
                        : 'Search BOQ items...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(_boqItemsSearchProvider.notifier)
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
            itemsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _BoqItemErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_boqItemsProvider),
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
                              Icons.inventory_2_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'BOQ Items haipatikani kwenye live API kwa sasa.'
                                  : 'BOQ Items is not available on the live API right now.',
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

                final items = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final filteredItems = items.where((item) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      item['name'] ?? '',
                      item['category_name'] ?? '',
                      item['category_parent_name'] ?? '',
                      item['unit'] ?? '',
                      item['description'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  return true;
                }).toList();

                if (filteredItems.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.inventory_2_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            items.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna BOQ items'
                                      : 'No BOQ items found')
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
                                            _boqItemsSearchProvider.notifier,
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
                      final item = filteredItems[index];
                      return _BoqItemCard(
                        item: item,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () => _openForm(context, ref, item: item),
                        onDelete: () => _deleteItem(context, ref, item),
                        onTap: () =>
                            _showDetails(context, item, isDarkMode, isSwahili),
                      );
                    }, childCount: filteredItems.length),
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
    final refs = await ref.read(_boqItemRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _BoqItemFormSheet(refs: refs, item: item),
    );
    if (result == true) ref.invalidate(_boqItemsProvider);
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
        title: Text(isSwahili ? 'Futa BOQ Item' : 'Delete BOQ Item'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta "${item['name']}"?'
              : 'Delete "${item['name']}"?',
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
      await ref.read(apiClientProvider).delete('/boq-items/${item['id']}');
      ref.invalidate(_boqItemsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'BOQ item imefutwa' : 'BOQ item deleted'),
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
    Map<String, dynamic> item,
    bool isDarkMode,
    bool isSwahili,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.68,
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
                        item['name']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 16),
                      _detailLine('Category', _categoryLabel(item), isDarkMode),
                      _detailLine('Unit', item['unit'], isDarkMode),
                      _detailLine(
                        'Base Price',
                        _money(item['base_price']),
                        isDarkMode,
                      ),
                      _detailLine(
                        'Description',
                        item['description'],
                        isDarkMode,
                      ),
                      _detailLine(
                        'Sub Activity Usage',
                        item['sub_activity_materials_count']?.toString() ?? '0',
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

  Widget _detailLine(String label, dynamic value, bool isDarkMode) {
    final text = (value ?? '').toString().trim();
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: RichText(
        text: TextSpan(
          style: TextStyle(
            fontSize: 13,
            color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
          ),
          children: [
            TextSpan(
              text: '$label: ',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            TextSpan(text: text.isEmpty ? '-' : text),
          ],
        ),
      ),
    );
  }
}

class _BoqItemErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _BoqItemErrorView({
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

class _BoqItemCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _BoqItemCard({
    required this.item,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final categoryText = _categoryLabel(item);
    final unit = item['unit']?.toString().trim();
    final price = _money(item['base_price']);

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
                      item['name']?.toString() ?? '-',
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
                    if (categoryText != 'Uncategorized') ...[
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
                          categoryText,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w500,
                            color: Colors.blue,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(height: 4),
                    ],
                    Row(
                      children: [
                        if (unit != null && unit.isNotEmpty) ...[
                          Icon(
                            Icons.straighten,
                            size: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            unit,
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                          ),
                          const SizedBox(width: 12),
                        ],
                        if (price != '-') ...[
                          Icon(
                            Icons.attach_money,
                            size: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                          Text(
                            price,
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                          ),
                        ],
                      ],
                    ),
                    if (item['description']?.toString().isNotEmpty ??
                        false) ...[
                      const SizedBox(height: 4),
                      Text(
                        item['description']?.toString() ?? '-',
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
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

class _BoqItemFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? item;

  const _BoqItemFormSheet({required this.refs, this.item});

  @override
  ConsumerState<_BoqItemFormSheet> createState() => _BoqItemFormSheetState();
}

class _BoqItemFormSheetState extends ConsumerState<_BoqItemFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _unitController;
  late final TextEditingController _basePriceController;
  late final TextEditingController _descriptionController;
  int? _categoryId;
  bool _saving = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
    _unitController = TextEditingController(
      text: widget.item?['unit']?.toString() ?? '',
    );
    _basePriceController = TextEditingController(
      text: widget.item?['base_price']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.item?['description']?.toString() ?? '',
    );
    _categoryId = _toNullableInt(widget.item?['category_id']);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _unitController.dispose();
    _basePriceController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final categories = _toMaps(widget.refs['categories']);

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
      height: 0.86 * MediaQuery.of(context).size.height,
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
                          ? (isSwahili ? 'Hariri BOQ Item' : 'Edit BOQ Item')
                          : (isSwahili ? 'BOQ Item Mpya' : 'New BOQ Item'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    TextFormField(
                      controller: _nameController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? (isSwahili
                                ? 'Jina linahitajika'
                                : 'Name is required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Item Name *' : 'Item Name *',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      isExpanded: true,
                      value:
                          categories.any(
                            (item) => _toInt(item['id']) == _categoryId,
                          )
                          ? _categoryId
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Category' : 'Category',
                      ),
                      dropdownColor: bgColor,
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(
                            isSwahili ? 'Hakuna Category' : 'Uncategorized',
                            style: TextStyle(color: textColor),
                          ),
                        ),
                        ...categories.map(
                          (item) => DropdownMenuItem<int?>(
                            value: _toInt(item['id']),
                            child: Text(
                              _categoryRefLabel(item),
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(color: textColor),
                            ),
                          ),
                        ),
                      ],
                      onChanged: (value) => setState(() => _categoryId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _unitController,
                      decoration: inputStyle(
                        isSwahili
                            ? 'Unit of Measurement'
                            : 'Unit of Measurement',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _basePriceController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      decoration: inputStyle(
                        isSwahili ? 'Base Price' : 'Base Price',
                      ),
                      style: TextStyle(color: textColor),
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
      final basePriceRaw = _basePriceController.text.trim();
      final data = {
        'name': _nameController.text.trim(),
        'category_id': _categoryId,
        'unit': _unitController.text.trim().isEmpty
            ? null
            : _unitController.text.trim(),
        'base_price': basePriceRaw.isEmpty
            ? null
            : double.tryParse(basePriceRaw),
        'description': _descriptionController.text.trim().isEmpty
            ? null
            : _descriptionController.text.trim(),
      };

      if (_isEdit) {
        await api.put('/boq-items/${widget.item!['id']}', data: data);
      } else {
        await api.post('/boq-items', data: data);
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

String _categoryLabel(Map<String, dynamic> item) {
  final parent = item['category_parent_name']?.toString().trim();
  final name = item['category_name']?.toString().trim();
  if (name == null || name.isEmpty) return 'Uncategorized';
  if (parent == null || parent.isEmpty) return name;
  return '$parent > $name';
}

String _categoryRefLabel(Map<String, dynamic> item) {
  final parent = item['parent_name']?.toString().trim();
  final name = item['name']?.toString().trim() ?? '-';
  if (parent == null || parent.isEmpty) return name;
  return '$parent > $name';
}

String _money(dynamic value) {
  final number = value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '');
  if (number == null) return '-';
  return number.toStringAsFixed(2);
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
