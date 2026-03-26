import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

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

final _productRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
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

class BillingProductsScreen extends ConsumerWidget {
  const BillingProductsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final productsAsync = ref.watch(_productListProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isSwahili ? 'Bidhaa na Huduma' : 'Billing Products & Services',
        ),
        actions: [
          IconButton(
            tooltip: isSwahili ? 'Ongeza' : 'Add',
            onPressed: () => _openProductForm(context, ref),
            icon: const Icon(Icons.add),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_productListProvider),
        child: productsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ProductsErrorView(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_productListProvider),
          ),
          data: (products) {
            if (products.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(24),
                children: [
                  const SizedBox(height: 96),
                  Icon(
                    Icons.inventory_2_outlined,
                    size: 60,
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna bidhaa au huduma bado'
                        : 'No products or services yet',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    isSwahili
                        ? 'Bonyeza alama ya kuongeza kuunda bidhaa au huduma mpya.'
                        : 'Tap the add button to create a new product or service.',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
              itemCount: products.length,
              itemBuilder: (context, index) {
                final item = products[index];
                final id = _toInt(item['id']);
                final isProduct = _text(item['type']).toLowerCase() == 'product';
                final isActive = item['is_active'] == true;
                final trackInventory = item['track_inventory'] == true;

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: id > 0 ? () => _showProductSheet(context, ref, id) : null,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CircleAvatar(
                                backgroundColor:
                                    AppColors.primary.withValues(alpha: 0.12),
                                child: Icon(
                                  isProduct
                                      ? Icons.inventory_2_outlined
                                      : Icons.handyman_outlined,
                                  color: AppColors.primary,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      _text(item['name']),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      '${_text(item['code'])} | ${_text(item['category'])}',
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              PopupMenuButton<String>(
                                onSelected: (value) {
                                  if (value == 'view') {
                                    _showProductSheet(context, ref, id);
                                  } else if (value == 'edit') {
                                    _openProductForm(context, ref, product: item);
                                  } else if (value == 'delete') {
                                    _deleteProduct(context, ref, item);
                                  }
                                },
                                itemBuilder: (context) => [
                                  PopupMenuItem<String>(
                                    value: 'view',
                                    child: Text(isSwahili ? 'Tazama' : 'View'),
                                  ),
                                  PopupMenuItem<String>(
                                    value: 'edit',
                                    child: Text(isSwahili ? 'Hariri' : 'Edit'),
                                  ),
                                  PopupMenuItem<String>(
                                    value: 'delete',
                                    child: Text(isSwahili ? 'Futa' : 'Delete'),
                                  ),
                                ],
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              _PillChip(
                                label: isProduct ? 'Product' : 'Service',
                                color: isProduct ? AppColors.info : AppColors.primary,
                              ),
                              _PillChip(
                                label: isActive ? 'Active' : 'Inactive',
                                color: isActive
                                    ? AppColors.success
                                    : AppColors.textSecondary,
                              ),
                              if (trackInventory)
                                _MetaChip(
                                  icon: Icons.inventory_outlined,
                                  label: 'Stock ${_numberText(item['current_stock'])}',
                                ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 12,
                            runSpacing: 8,
                            children: [
                              _MetricText(
                                label: isSwahili ? 'Bei' : 'Unit Price',
                                value: _money(item['unit_price']),
                              ),
                              if (_hasValue(item['purchase_price']))
                                _MetricText(
                                  label: isSwahili ? 'Bei ya kununua' : 'Purchase',
                                  value: _money(item['purchase_price']),
                                ),
                              if (_hasValue(item['unit_of_measure']))
                                _MetricText(
                                  label: isSwahili ? 'Kipimo' : 'Unit',
                                  value: _text(item['unit_of_measure']),
                                ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            );
          },
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
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      scrollable: true,
      title: const Text('Delete Product'),
      content: Text('Delete ${_text(product['name'])}?'),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: const Text('Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: const Text('Delete'),
        ),
      ],
    ),
  );
  if (confirmed != true) return;

  try {
    await ref.read(apiClientProvider).delete('/billing/products/${product['id']}');
    ref.invalidate(_productListProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          backgroundColor: AppColors.success,
          content: Text('Product deleted'),
        ),
      );
    }
  } catch (error) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: false)),
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
          final isDarkMode = ref.watch(isDarkModeProvider);
          return Container(
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SafeArea(
              top: false,
              child: detailAsync.when(
                loading: () => const _BottomLoading(),
                error: (error, _) => _ProductsErrorView(
                  isSwahili: false,
                  message: vatErrorMessage(error, isSwahili: false),
                  onRetry: () => ref.invalidate(_productDetailProvider(id)),
                ),
                data: (product) {
                  final stats =
                      product['stats'] as Map<String, dynamic>? ?? const {};
                  final taxRate =
                      product['tax_rate'] as Map<String, dynamic>? ?? const {};
                  final isProduct =
                      _text(product['type']).toLowerCase() == 'product';
                  final tracksInventory = product['track_inventory'] == true;

                  return Column(
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
                              _text(product['name']),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              '${_text(product['code'])} | ${_text(product['category'])}',
                              style: const TextStyle(
                                color: AppColors.textSecondary,
                              ),
                            ),
                            const SizedBox(height: 18),
                            Wrap(
                              spacing: 12,
                              runSpacing: 12,
                              children: [
                                _StatCard(
                                  label: 'Used',
                                  value: _numberText(stats['times_used']),
                                ),
                                _StatCard(
                                  label: 'Sold',
                                  value: _numberText(stats['total_sold']),
                                ),
                                _StatCard(
                                  label: 'Revenue',
                                  value: _money(stats['total_revenue']),
                                ),
                                _StatCard(
                                  label: 'Avg Price',
                                  value: _money(stats['average_price']),
                                ),
                              ],
                            ),
                            const SizedBox(height: 18),
                            _DetailRow('Type', isProduct ? 'Product' : 'Service'),
                            _DetailRow(
                              'Status',
                              product['is_active'] == true ? 'Active' : 'Inactive',
                            ),
                            _DetailRow('Description', _text(product['description'])),
                            _DetailRow('Category', _text(product['category'])),
                            _DetailRow('Unit', _text(product['unit_of_measure'])),
                            _DetailRow('Unit Price', _money(product['unit_price'])),
                            _DetailRow(
                              'Purchase Price',
                              _money(product['purchase_price']),
                            ),
                            _DetailRow(
                              'Tax Rate',
                              taxRate.isEmpty
                                  ? '-'
                                  : '${_text(taxRate['name'])} (${_numberText(taxRate['rate'])}%)',
                            ),
                            _DetailRow('SKU', _text(product['sku'])),
                            _DetailRow('Barcode', _text(product['barcode'])),
                            if (isProduct) ...[
                              _DetailRow(
                                'Track Inventory',
                                tracksInventory ? 'Yes' : 'No',
                              ),
                              if (tracksInventory) ...[
                                _DetailRow(
                                  'Current Stock',
                                  _numberText(product['current_stock']),
                                ),
                                _DetailRow(
                                  'Minimum Stock',
                                  _numberText(product['minimum_stock']),
                                ),
                                _DetailRow(
                                  'Reorder Level',
                                  _numberText(product['reorder_level']),
                                ),
                              ],
                            ],
                          ],
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

class _ProductFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? product;

  const _ProductFormSheet({
    required this.refs,
    this.product,
  });

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

    _nameController = TextEditingController(text: product?['name']?.toString() ?? '');
    _codeController = TextEditingController(text: product?['code']?.toString() ?? '');
    _descriptionController =
        TextEditingController(text: product?['description']?.toString() ?? '');
    _categoryController =
        TextEditingController(text: product?['category']?.toString() ?? '');
    _unitController =
        TextEditingController(text: product?['unit_of_measure']?.toString() ?? '');
    _unitPriceController =
        TextEditingController(text: _fieldNumberText(product?['unit_price']));
    _purchasePriceController =
        TextEditingController(text: _fieldNumberText(product?['purchase_price']));
    _skuController = TextEditingController(text: product?['sku']?.toString() ?? '');
    _barcodeController =
        TextEditingController(text: product?['barcode']?.toString() ?? '');
    _currentStockController =
        TextEditingController(text: _fieldNumberText(product?['current_stock']));
    _minimumStockController =
        TextEditingController(text: _fieldNumberText(product?['minimum_stock']));
    _reorderLevelController =
        TextEditingController(text: _fieldNumberText(product?['reorder_level']));
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
    final isDarkMode = ref.watch(isDarkModeProvider);
    final taxRates = (widget.refs['tax_rates'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final categories = (widget.refs['categories'] as List? ?? const [])
        .map((item) => item?.toString() ?? '')
        .where((item) => item.trim().isNotEmpty)
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
                    Text(
                      widget.product == null
                          ? 'New Product or Service'
                          : 'Edit Product or Service',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _dropdownStringField(
                      label: 'Type *',
                      isDarkMode: isDarkMode,
                      value: _type,
                      items: const [
                        DropdownMenuItem<String>(
                          value: 'product',
                          child: Text('Product'),
                        ),
                        DropdownMenuItem<String>(
                          value: 'service',
                          child: Text('Service'),
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
                      label: 'Name *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _codeController,
                      label: 'Code',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _descriptionController,
                      label: 'Description',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      maxLines: 4,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _categoryController,
                      label: 'Category',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    if (categories.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: categories
                            .take(8)
                            .map(
                              (category) => ActionChip(
                                label: Text(
                                  category,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                onPressed: () =>
                                    _categoryController.text = category,
                              ),
                            )
                            .toList(),
                      ),
                    ],
                    const SizedBox(height: 12),
                    _textField(
                      controller: _unitController,
                      label: 'Unit of Measure',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _unitPriceController,
                      label: 'Unit Price *',
                      isDarkMode: isDarkMode,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _purchasePriceController,
                      label: 'Purchase Price',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                    ),
                    const SizedBox(height: 12),
                    _dropdownField(
                      label: 'Tax Rate',
                      isDarkMode: isDarkMode,
                      value:
                          taxRates.any((item) => _toNullableInt(item['id']) == _taxRateId)
                              ? _taxRateId
                              : null,
                      items: [
                        const DropdownMenuItem<int?>(
                          value: null,
                          child: Text('No Tax'),
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
                    SwitchListTile.adaptive(
                      value: _isActive,
                      onChanged: (value) => setState(() => _isActive = value),
                      title: const Text('Active'),
                      contentPadding: EdgeInsets.zero,
                    ),
                    if (_type == 'product') ...[
                      const SizedBox(height: 4),
                      SwitchListTile.adaptive(
                        value: _trackInventory,
                        onChanged: (value) =>
                            setState(() => _trackInventory = value),
                        title: const Text('Track Inventory'),
                        contentPadding: EdgeInsets.zero,
                      ),
                    ],
                    if (_type == 'product' && _trackInventory) ...[
                      const SizedBox(height: 12),
                      _textField(
                        controller: _currentStockController,
                        label: 'Current Stock',
                        isDarkMode: isDarkMode,
                        isRequired: false,
                        keyboardType:
                            const TextInputType.numberWithOptions(decimal: true),
                      ),
                      const SizedBox(height: 12),
                      _textField(
                        controller: _minimumStockController,
                        label: 'Minimum Stock',
                        isDarkMode: isDarkMode,
                        isRequired: false,
                        keyboardType:
                            const TextInputType.numberWithOptions(decimal: true),
                      ),
                      const SizedBox(height: 12),
                      _textField(
                        controller: _reorderLevelController,
                        label: 'Reorder Level',
                        isDarkMode: isDarkMode,
                        isRequired: false,
                        keyboardType:
                            const TextInputType.numberWithOptions(decimal: true),
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
                          : Text(widget.product == null ? 'Save' : 'Update'),
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
    final payload = <String, dynamic>{
      'type': _type,
      'name': _nameController.text.trim(),
      'code': _blankToNull(_codeController.text),
      'description': _blankToNull(_descriptionController.text),
      'category': _blankToNull(_categoryController.text),
      'unit_of_measure': _blankToNull(_unitController.text),
      'unit_price': _toDouble(_unitPriceController.text),
      'purchase_price': _blankToNull(_purchasePriceController.text) == null
          ? null
          : _toDouble(_purchasePriceController.text),
      'tax_rate_id': _taxRateId,
      'sku': _blankToNull(_skuController.text),
      'barcode': _blankToNull(_barcodeController.text),
      'track_inventory': _type == 'product' ? _trackInventory : false,
      'current_stock':
          _type == 'product' && _trackInventory && _blankToNull(_currentStockController.text) != null
              ? _toDouble(_currentStockController.text)
              : null,
      'minimum_stock':
          _type == 'product' && _trackInventory && _blankToNull(_minimumStockController.text) != null
              ? _toDouble(_minimumStockController.text)
              : null,
      'reorder_level':
          _type == 'product' && _trackInventory && _blankToNull(_reorderLevelController.text) != null
              ? _toDouble(_reorderLevelController.text)
              : null,
      'is_active': _isActive,
    };

    try {
      final api = ref.read(apiClientProvider);
      final id = _toInt(widget.product?['id']);
      if (id > 0) {
        await api.put('/billing/products/$id', data: payload);
      } else {
        await api.post('/billing/products', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(id > 0 ? 'Product updated' : 'Product created'),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: false)),
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _MetricText extends StatelessWidget {
  final String label;
  final String value;

  const _MetricText({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return RichText(
      text: TextSpan(
        style: DefaultTextStyle.of(context).style,
        children: [
          TextSpan(
            text: '$label: ',
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
          ),
          TextSpan(
            text: value,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;

  const _StatCard({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 140,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(value),
        ],
      ),
    );
  }
}

class _PillChip extends StatelessWidget {
  final String label;
  final Color color;

  const _PillChip({
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _MetaChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _MetaChip({
    required this.icon,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.textSecondary),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(fontSize: 12)),
        ],
      ),
    );
  }
}

class _ProductsErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ProductsErrorView({
    required this.isSwahili,
    required this.message,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 52, color: AppColors.error),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
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
    return Column(
      children: [
        const SizedBox(height: 12),
        Container(
          width: 44,
          height: 5,
          decoration: BoxDecoration(
            color: Colors.white24,
            borderRadius: BorderRadius.circular(999),
          ),
        ),
        const Expanded(child: Center(child: CircularProgressIndicator())),
      ],
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
        borderRadius: BorderRadius.circular(14),
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
        borderRadius: BorderRadius.circular(14),
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
        borderRadius: BorderRadius.circular(14),
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

bool _hasValue(dynamic value) {
  if (value == null) return false;
  final text = value.toString().trim();
  return text.isNotEmpty && text != '0' && text != '0.0' && text != '0.00';
}
