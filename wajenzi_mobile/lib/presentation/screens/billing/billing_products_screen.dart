import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _productSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _productTypeProvider = StateProvider.autoDispose<String?>((ref) => null);

final _productStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _productListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/billing/products',
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

final _productRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/products/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

final _productDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/billing/products/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

class BillingProductsScreen extends ConsumerStatefulWidget {
  const BillingProductsScreen({super.key});

  @override
  ConsumerState<BillingProductsScreen> createState() =>
      _BillingProductsScreenState();
}

class _BillingProductsScreenState extends ConsumerState<BillingProductsScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final productsAsync = ref.watch(_productListProvider);
    final search = ref.watch(_productSearchProvider);
    final type = ref.watch(_productTypeProvider);
    final status = ref.watch(_productStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Bidhaa na Huduma' : 'Products & Services'),
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
                      ref.read(_productSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta bidhaa...'
                        : 'Search products...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(_productSearchProvider.notifier).state =
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
                        isSelected: type == null,
                        onTap: () =>
                            ref.read(_productTypeProvider.notifier).state =
                                null,
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Products',
                        isSelected: type == 'product',
                        onTap: () =>
                            ref.read(_productTypeProvider.notifier).state =
                                'product',
                        isDarkMode: isDarkMode,
                        color: AppColors.info,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Services',
                        isSelected: type == 'service',
                        onTap: () =>
                            ref.read(_productTypeProvider.notifier).state =
                                'service',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 16),
                      Container(
                        width: 1,
                        height: 24,
                        color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      ),
                      const SizedBox(width: 16),
                      _FilterChip(
                        label: isSwahili ? 'Haina' : 'All',
                        isSelected: status == null,
                        onTap: () =>
                            ref.read(_productStatusProvider.notifier).state =
                                null,
                        isDarkMode: isDarkMode,
                        color: Colors.grey,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Active',
                        isSelected: status == 'active',
                        onTap: () =>
                            ref.read(_productStatusProvider.notifier).state =
                                'active',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Inactive',
                        isSelected: status == 'inactive',
                        onTap: () =>
                            ref.read(_productStatusProvider.notifier).state =
                                'inactive',
                        isDarkMode: isDarkMode,
                        color: AppColors.textSecondary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Low Stock',
                        isSelected: status == 'low_stock',
                        onTap: () =>
                            ref.read(_productStatusProvider.notifier).state =
                                'low_stock',
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
              onRefresh: () async => ref.invalidate(_productListProvider),
              child: productsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ProductsErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_productListProvider),
                  isDarkMode: isDarkMode,
                ),
                data: (products) {
                  final filteredProducts = products.where((item) {
                    // Type filter
                    if (type != null &&
                        _text(item['type']).toLowerCase() != type) {
                      return false;
                    }

                    // Status filter
                    if (status != null) {
                      final isActive = item['is_active'] == true;
                      final trackInventory = item['track_inventory'] == true;
                      final currentStock = _toDouble(item['current_stock']);
                      final minimumStock = _toDouble(item['minimum_stock']);

                      if (status == 'active' && !isActive) return false;
                      if (status == 'inactive' && isActive) return false;
                      if (status == 'low_stock' &&
                          (!trackInventory || currentStock > minimumStock)) {
                        return false;
                      }
                    }

                    // Search filter
                    if (search.isEmpty) return true;
                    final query = search.toLowerCase();
                    final name = _text(item['name']).toLowerCase();
                    final code = _text(item['code']).toLowerCase();
                    final category = _text(item['category']).toLowerCase();
                    return name.contains(query) ||
                        code.contains(query) ||
                        category.contains(query);
                  }).toList();

                  if (filteredProducts.isEmpty) {
                    return ListView(
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.2,
                        ),
                        Icon(
                          Icons.inventory_2_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili ? 'Hakuna bidhaa' : 'No products found',
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
                    itemCount: filteredProducts.length,
                    itemBuilder: (context, index) {
                      final item = filteredProducts[index];
                      final id = _toInt(item['id']);

                      return _ProductCard(
                        item: item,
                        index: index + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: id > 0
                            ? () => _showProductSheet(context, ref, id)
                            : null,
                        onEdit: () =>
                            _openProductForm(context, ref, product: item),
                        onDelete: () => _deleteProduct(context, ref, item),
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
          onPressed: () => _openProductForm(context, ref),
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

class _ProductCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback? onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  const _ProductCard({
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
    final isProduct = _text(item['type']).toLowerCase() == 'product';
    final isActive = item['is_active'] == true;
    final trackInventory = item['track_inventory'] == true;
    final currentStock = _toDouble(item['current_stock']);
    final minimumStock = _toDouble(item['minimum_stock']);
    final isLowStock = trackInventory && currentStock <= minimumStock;

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
                  color: (isProduct ? AppColors.info : AppColors.primary)
                      .withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Icon(
                    isProduct
                        ? Icons.inventory_2_outlined
                        : Icons.handyman_outlined,
                    color: isProduct ? AppColors.info : AppColors.primary,
                    size: 22,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _text(item['name']),
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
                    const SizedBox(height: 4),
                    Text(
                      '${_text(item['code'])} | ${_text(item['category'])}',
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
                        _TypeChip(
                          label: isProduct ? 'Product' : 'Service',
                          isProduct: isProduct,
                        ),
                        const SizedBox(width: 8),
                        _StatusChip(
                          label: isActive ? 'Active' : 'Inactive',
                          isActive: isActive,
                        ),
                        if (isLowStock) ...[
                          const SizedBox(width: 8),
                          const _StatusChip(
                            label: 'Low',
                            isActive: false,
                            isLowStock: true,
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _money(item['unit_price']),
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                    if (trackInventory) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(
                            Icons.inventory_outlined,
                            size: 12,
                            color: isLowStock
                                ? AppColors.error
                                : (isDarkMode
                                      ? Colors.white54
                                      : AppColors.textSecondary),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            'Stock: ${_numberText(item['current_stock'])}',
                            style: TextStyle(
                              fontSize: 11,
                              color: isLowStock
                                  ? AppColors.error
                                  : (isDarkMode
                                        ? Colors.white54
                                        : AppColors.textSecondary),
                              fontWeight: isLowStock
                                  ? FontWeight.w600
                                  : FontWeight.normal,
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

class _TypeChip extends StatelessWidget {
  final String label;
  final bool isProduct;

  const _TypeChip({required this.label, required this.isProduct});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: (isProduct ? AppColors.info : AppColors.primary).withValues(
          alpha: 0.12,
        ),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: isProduct ? AppColors.info : AppColors.primary,
          fontWeight: FontWeight.w700,
          fontSize: 10,
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final bool isActive;
  final bool isLowStock;

  const _StatusChip({
    required this.label,
    required this.isActive,
    this.isLowStock = false,
  });

  @override
  Widget build(BuildContext context) {
    Color color;
    if (isLowStock) {
      color = AppColors.error;
    } else if (isActive) {
      color = AppColors.success;
    } else {
      color = AppColors.textSecondary;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
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

Future<void> _openProductForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? product,
}) async {
  final refs = await ref.read(_productRefsProvider.future);
  var initialProduct = product;
  final productId = _toInt(product?['id']);
  if (productId > 0 &&
      (product == null ||
          product['tax_rate'] == null ||
          product['track_inventory'] == null)) {
    initialProduct = await ref.read(_productDetailProvider(productId).future);
  }
  if (!context.mounted) return;

  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.94,
      child: _ProductFormSheet(refs: refs, product: initialProduct),
    ),
  );
  if (result == true) {
    ref.invalidate(_productListProvider);
    if (productId > 0) {
      ref.invalidate(_productDetailProvider(productId));
    }
  }
}

Future<void> _deleteProduct(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> product,
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
            ? 'Je, una uhakika unataka kufuta "${_text(product['name'])}"?'
            : 'Are you sure you want to delete "${_text(product['name'])}"?',
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
        .delete('/billing/products/${product['id']}');
    ref.invalidate(_productListProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(isSwahili ? 'Bidhaa imefutwa' : 'Product deleted'),
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

void _showProductSheet(BuildContext context, WidgetRef ref, int id) {
  if (id <= 0) return;
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.88,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_productDetailProvider(id));
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
                error: (error, _) => _ProductsErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_productDetailProvider(id)),
                  isDarkMode: isDarkMode,
                ),
                data: (product) {
                  final stats =
                      product['stats'] as Map<String, dynamic>? ?? const {};
                  final taxRate =
                      product['tax_rate'] as Map<String, dynamic>? ?? const {};
                  final isProduct =
                      _text(product['type']).toLowerCase() == 'product';
                  final tracksInventory = product['track_inventory'] == true;
                  final isActive = product['is_active'] == true;

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
                                (isProduct ? AppColors.info : AppColors.primary)
                                    .withValues(alpha: 0.1),
                            child: Icon(
                              isProduct
                                  ? Icons.inventory_2_outlined
                                  : Icons.handyman_outlined,
                              color: isProduct
                                  ? AppColors.info
                                  : AppColors.primary,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _text(product['name']),
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${_text(product['code'])} | ${_text(product['category'])}',
                                  style: TextStyle(
                                    color: isDarkMode
                                        ? Colors.white54
                                        : AppColors.textSecondary,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  children: [
                                    _TypeChip(
                                      label: isProduct ? 'Product' : 'Service',
                                      isProduct: isProduct,
                                    ),
                                    const SizedBox(width: 8),
                                    _StatusChip(
                                      label: isActive ? 'Active' : 'Inactive',
                                      isActive: isActive,
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            onPressed: () async {
                              Navigator.of(context).pop();
                              await _openProductForm(
                                context,
                                ref,
                                product: product,
                              );
                            },
                            icon: const Icon(Icons.edit_outlined),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      _SectionCard(
                        title: isSwahili ? 'Takwimu' : 'Statistics',
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: _StatCard(
                                  label: isSwahili ? 'Imetumika' : 'Used',
                                  value: _numberText(stats['times_used']),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: _StatCard(
                                  label: isSwahili ? 'Imeuzwa' : 'Sold',
                                  value: _numberText(stats['total_sold']),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: _StatCard(
                                  label: isSwahili ? 'Mapato' : 'Revenue',
                                  value: _money(stats['total_revenue']),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: _StatCard(
                                  label: isSwahili
                                      ? 'Bei ya Kati'
                                      : 'Avg Price',
                                  value: _money(stats['average_price']),
                                  isDarkMode: isDarkMode,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      _SectionCard(
                        title: isSwahili ? 'Maelezo' : 'Details',
                        children: [
                          _DetailRow(
                            label: isSwahili ? 'Maelezo' : 'Description',
                            value: _text(product['description']),
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Kipimo' : 'Unit',
                            value: _text(product['unit_of_measure']),
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Bei ya Uniti' : 'Unit Price',
                            value: _money(product['unit_price']),
                            isDarkMode: isDarkMode,
                            valueColor: AppColors.primary,
                          ),
                          _DetailRow(
                            label: isSwahili
                                ? 'Bei ya Kununua'
                                : 'Purchase Price',
                            value: _money(product['purchase_price']),
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Kodi ya ushuru' : 'Tax Rate',
                            value: taxRate.isEmpty
                                ? '-'
                                : '${_text(taxRate['name'])} (${_numberText(taxRate['rate'])}%)',
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: 'SKU',
                            value: _text(product['sku']),
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: 'Barcode',
                            value: _text(product['barcode']),
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                      if (isProduct && tracksInventory) ...[
                        const SizedBox(height: 14),
                        _SectionCard(
                          title: isSwahili ? 'Hisa' : 'Inventory',
                          children: [
                            _DetailRow(
                              label: isSwahili
                                  ? 'Hisa za Sasa'
                                  : 'Current Stock',
                              value: _numberText(product['current_stock']),
                              isDarkMode: isDarkMode,
                              valueColor: AppColors.primary,
                            ),
                            _DetailRow(
                              label: isSwahili
                                  ? 'Hisa ya Chini'
                                  : 'Minimum Stock',
                              value: _numberText(product['minimum_stock']),
                              isDarkMode: isDarkMode,
                            ),
                            _DetailRow(
                              label: isSwahili
                                  ? 'Kiwango cha Kuagiza'
                                  : 'Reorder Level',
                              value: _numberText(product['reorder_level']),
                              isDarkMode: isDarkMode,
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

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _StatCard({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode
            ? Colors.white.withValues(alpha: 0.05)
            : AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
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

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _DetailRow({
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

class _ProductsErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;
  final bool isDarkMode;

  const _ProductsErrorView({
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

class _ProductFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? product;

  const _ProductFormSheet({required this.refs, this.product});

  @override
  ConsumerState<_ProductFormSheet> createState() => _ProductFormSheetState();
}

class _ProductFormSheetState extends ConsumerState<_ProductFormSheet> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _nameController;
  late final TextEditingController _codeController;
  late final TextEditingController _descriptionController;
  late final TextEditingController _categoryController;
  late final TextEditingController _unitController;
  late final TextEditingController _unitPriceController;
  late final TextEditingController _purchasePriceController;
  late final TextEditingController _skuController;
  late final TextEditingController _barcodeController;
  late final TextEditingController _currentStockController;
  late final TextEditingController _minimumStockController;
  late final TextEditingController _reorderLevelController;

  String _type = 'product';
  int? _taxRateId;
  bool _trackInventory = true;
  bool _isActive = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final product = widget.product;
    _type = product?['type']?.toString() == 'service' ? 'service' : 'product';
    _taxRateId = _toNullableInt(product?['tax_rate_id']);
    _trackInventory = product?['track_inventory'] != false;
    _isActive = product?['is_active'] != false;

    _nameController = TextEditingController(
      text: product?['name']?.toString() ?? '',
    );
    _codeController = TextEditingController(
      text: product?['code']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: product?['description']?.toString() ?? '',
    );
    _categoryController = TextEditingController(
      text: product?['category']?.toString() ?? '',
    );
    _unitController = TextEditingController(
      text: product?['unit_of_measure']?.toString() ?? '',
    );
    _unitPriceController = TextEditingController(
      text: _fieldNumberText(product?['unit_price']),
    );
    _purchasePriceController = TextEditingController(
      text: _fieldNumberText(product?['purchase_price']),
    );
    _skuController = TextEditingController(
      text: product?['sku']?.toString() ?? '',
    );
    _barcodeController = TextEditingController(
      text: product?['barcode']?.toString() ?? '',
    );
    _currentStockController = TextEditingController(
      text: _fieldNumberText(product?['current_stock']),
    );
    _minimumStockController = TextEditingController(
      text: _fieldNumberText(product?['minimum_stock']),
    );
    _reorderLevelController = TextEditingController(
      text: _fieldNumberText(product?['reorder_level']),
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _codeController.dispose();
    _descriptionController.dispose();
    _categoryController.dispose();
    _unitController.dispose();
    _unitPriceController.dispose();
    _purchasePriceController.dispose();
    _skuController.dispose();
    _barcodeController.dispose();
    _currentStockController.dispose();
    _minimumStockController.dispose();
    _reorderLevelController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final taxRates = (widget.refs['tax_rates'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();

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
                          backgroundColor:
                              (_type == 'product'
                                      ? AppColors.info
                                      : AppColors.primary)
                                  .withValues(alpha: 0.1),
                          child: Icon(
                            _type == 'product'
                                ? Icons.inventory_2_outlined
                                : Icons.handyman_outlined,
                            color: _type == 'product'
                                ? AppColors.info
                                : AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Text(
                            widget.product == null
                                ? (isSwahili
                                      ? 'Bidhaa/Huduma Mpya'
                                      : 'New Product/Service')
                                : (isSwahili
                                      ? 'Hariri Bidhaa/Huduma'
                                      : 'Edit Product/Service'),
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        IconButton(
                          icon: Icon(
                            Icons.close,
                            color: isDarkMode ? Colors.white70 : Colors.black54,
                          ),
                          onPressed: () => Navigator.of(context).pop(),
                          tooltip: isSwahili ? 'Funga' : 'Close',
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    _dropdownStringField(
                      label: isSwahili ? 'Aina *' : 'Type *',
                      isDarkMode: isDarkMode,
                      value: _type,
                      items: [
                        DropdownMenuItem<String>(
                          value: 'product',
                          child: Text(isSwahili ? 'Bidhaa' : 'Product'),
                        ),
                        DropdownMenuItem<String>(
                          value: 'service',
                          child: Text(isSwahili ? 'Huduma' : 'Service'),
                        ),
                      ],
                      onChanged: (value) {
                        setState(() {
                          _type = value ?? 'product';
                          if (_type == 'service') {
                            _trackInventory = false;
                          }
                        });
                      },
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _nameController,
                      label: isSwahili ? 'Jina *' : 'Name *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _codeController,
                      label: isSwahili ? 'Kodi' : 'Code',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _descriptionController,
                      label: isSwahili ? 'Maelezo' : 'Description',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      maxLines: 4,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _categoryController,
                      label: isSwahili ? 'Kategoria' : 'Category',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _unitController,
                      label: isSwahili ? 'Kipimo' : 'Unit of Measure',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _unitPriceController,
                      label: isSwahili ? 'Bei ya Uniti *' : 'Unit Price *',
                      isDarkMode: isDarkMode,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _purchasePriceController,
                      label: isSwahili ? 'Bei ya Kununua' : 'Purchase Price',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _dropdownField(
                      label: isSwahili ? 'Kodi ya Ushuru' : 'Tax Rate',
                      isDarkMode: isDarkMode,
                      value:
                          taxRates.any(
                            (item) => _toNullableInt(item['id']) == _taxRateId,
                          )
                          ? _taxRateId
                          : null,
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(isSwahili ? 'Hakuna Ushuru' : 'No Tax'),
                        ),
                        ...taxRates.map(
                          (item) => DropdownMenuItem<int?>(
                            value: _toNullableInt(item['id']),
                            child: Text(
                              '${_text(item['name'])} (${_numberText(item['rate'])}%)',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ),
                      ],
                      isRequired: false,
                      onChanged: (value) => setState(() => _taxRateId = value),
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _skuController,
                      label: 'SKU',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _barcodeController,
                      label: 'Barcode',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _SwitchTile(
                      value: _isActive,
                      onChanged: (value) => setState(() => _isActive = value),
                      title: isSwahili ? 'Inatekelezwa' : 'Active',
                      isDarkMode: isDarkMode,
                    ),
                    if (_type == 'product') ...[
                      _SwitchTile(
                        value: _trackInventory,
                        onChanged: (value) =>
                            setState(() => _trackInventory = value),
                        title: isSwahili ? 'Kagua Hisa' : 'Track Inventory',
                        isDarkMode: isDarkMode,
                      ),
                    ],
                    if (_type == 'product' && _trackInventory) ...[
                      const SizedBox(height: 12),
                      _textField(
                        controller: _currentStockController,
                        label: isSwahili ? 'Hisa za Sasa' : 'Current Stock',
                        isDarkMode: isDarkMode,
                        isRequired: false,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                      ),
                      const SizedBox(height: 12),
                      _textField(
                        controller: _minimumStockController,
                        label: isSwahili ? 'Hisa ya Chini' : 'Minimum Stock',
                        isDarkMode: isDarkMode,
                        isRequired: false,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                      ),
                      const SizedBox(height: 12),
                      _textField(
                        controller: _reorderLevelController,
                        label: isSwahili
                            ? 'Kiwango cha Kuagiza'
                            : 'Reorder Level',
                        isDarkMode: isDarkMode,
                        isRequired: false,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                      ),
                    ],
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
                              widget.product == null
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
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);
    final payload = <String, dynamic>{
      'type': _type,
      'name': _nameController.text.trim(),
      'code': _blankToNull(_codeController.text),
      'description': _blankToNull(_descriptionController.text),
      'category': _blankToNull(_categoryController.text),
      'unit_of_measure': _unitController.text.trim().isEmpty
          ? null
          : _unitController.text.trim(),
      'unit_price': _toDouble(_unitPriceController.text),
      'purchase_price': _blankToNull(_purchasePriceController.text) == null
          ? null
          : _toDouble(_purchasePriceController.text),
      'tax_rate_id': _taxRateId,
      'sku': _blankToNull(_skuController.text),
      'barcode': _blankToNull(_barcodeController.text),
      'track_inventory': _type == 'product' ? _trackInventory : false,
      'current_stock':
          _type == 'product' &&
              _trackInventory &&
              _blankToNull(_currentStockController.text) != null
          ? _toDouble(_currentStockController.text)
          : null,
      'minimum_stock':
          _type == 'product' &&
              _trackInventory &&
              _blankToNull(_minimumStockController.text) != null
          ? _toDouble(_minimumStockController.text)
          : null,
      'reorder_level':
          _type == 'product' &&
              _trackInventory &&
              _blankToNull(_reorderLevelController.text) != null
          ? _toDouble(_reorderLevelController.text)
          : null,
      'is_active': _isActive,
    };

    try {
      final api = ref.read(apiClientProvider);
      final id = _toInt(widget.product?['id']);
      final isUpdate = id > 0;
      Map<String, dynamic>? resultData;
      if (isUpdate) {
        final response = await api.put('/billing/products/$id', data: payload);
        resultData = response.data is Map<String, dynamic>
            ? response.data['data'] as Map<String, dynamic>?
            : null;
      } else {
        final response = await api.post('/billing/products', data: payload);
        resultData = response.data is Map<String, dynamic>
            ? response.data['data'] as Map<String, dynamic>?
            : null;
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            isUpdate
                ? (isSwahili ? 'Bidhaa imesasishwa' : 'Product updated')
                : (isSwahili ? 'Bidhaa imehifadhiwa' : 'Product created'),
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

class _SwitchTile extends StatelessWidget {
  final bool value;
  final ValueChanged<bool> onChanged;
  final String title;
  final bool isDarkMode;

  const _SwitchTile({
    required this.value,
    required this.onChanged,
    required this.title,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: TextStyle(
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          Switch(
            value: value,
            onChanged: onChanged,
            activeColor: AppColors.primary,
          ),
        ],
      ),
    );
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
      alignLabelWithHint: maxLines > 1,
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
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
  bool isRequired = true,
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
    validator: (selected) {
      if (!isRequired) return null;
      return selected == null ? 'Required' : null;
    },
    onChanged: onChanged,
  );
}

Widget _dropdownStringField({
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
    validator: (selected) =>
        selected == null || selected.isEmpty ? 'Required' : null,
    onChanged: onChanged,
  );
}

String _text(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? '-' : text;
}

String _money(dynamic value) {
  final amount = _toDouble(value);
  return 'TZS ${amount.toStringAsFixed(2)}';
}

String _numberText(dynamic value) {
  if (value == null) return '-';
  final text = value.toString().trim();
  if (text.isEmpty) return '-';
  final amount = _toDouble(value);
  if (amount == amount.truncateToDouble()) {
    return amount.toInt().toString();
  }
  return amount.toStringAsFixed(2);
}

String _fieldNumberText(dynamic value) {
  if (value == null) return '';
  final text = value.toString().trim();
  if (text.isEmpty) return '';
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
