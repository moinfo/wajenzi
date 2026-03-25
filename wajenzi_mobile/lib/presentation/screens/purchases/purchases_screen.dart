import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _purchasesProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/purchases');

  List items = [];
  Map<String, dynamic> meta = {};

  try {
    final dynamic responseData = response.data;
    if (responseData is Map) {
      final dynamic dataField = responseData['data'];
      if (dataField is Map) {
        items =
            (dataField['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
        meta = (dataField['meta'] as Map<String, dynamic>?) ?? {};
      } else if (dataField is List) {
        items = dataField.cast<Map<String, dynamic>>();
      }
    }
  } catch (e, st) {
    debugPrint('Error parsing purchases: $e $st');
  }

  return {'items': items, 'meta': meta};
});

final _suppliersProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/purchases/suppliers');
  return response.data['data'] as List? ?? [];
});

final _purchaseDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/purchases/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class PurchasesScreen extends ConsumerWidget {
  const PurchasesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final purchasesAsync = ref.watch(_purchasesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Manunuzi' : 'Purchases'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza' : 'Add',
            onPressed: () => _showPurchaseForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_purchasesProvider);
        },
        child: purchasesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_purchasesProvider),
          ),
          data: (payload) {
            final List purchases = payload['items'] as List? ?? [];

            if (purchases.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.shopping_cart_outlined,
                    size: 56,
                    color: Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna manunuzi yoyote' : 'No purchases found',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 24),
                  Center(
                    child: ElevatedButton.icon(
                      onPressed: () => _showPurchaseForm(context, ref),
                      icon: const Icon(Icons.add),
                      label: Text(
                        isSwahili ? 'Ongeza Ununuzi' : 'Add Purchase',
                      ),
                    ),
                  ),
                ],
              );
            }

            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: purchases.length + 1,
              itemBuilder: (context, index) {
                if (index == purchases.length) {
                  return const SizedBox(height: 80);
                }
                final purchase = purchases[index] as Map<String, dynamic>;
                return _PurchaseCard(
                  purchase: purchase,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  onEdit: () =>
                      _showPurchaseForm(context, ref, purchase: purchase),
                  onDelete: () => _deletePurchase(context, ref, purchase),
                  onTap: () => _showPurchaseDetails(context, ref, purchase),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _showPurchaseForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? purchase,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) =>
          _PurchaseFormSheet(purchase: purchase, isNew: purchase == null),
    );

    if (result == true) {
      ref.invalidate(_purchasesProvider);
    }
  }

  Future<void> _deletePurchase(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> purchase,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa Ununuzi' : 'Delete Purchase'),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta ununuzi wa tarehe ${purchase['date']}?'
              : 'Are you sure you want to delete purchase from ${purchase['date']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      await api.delete('/purchases/${purchase['id']}');

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Umefutwa' : 'Deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }

      ref.invalidate(_purchasesProvider);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showPurchaseDetails(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> purchase,
  ) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        height: MediaQuery.of(context).size.height * 0.7,
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    Container(
                      width: 42,
                      height: 4,
                      decoration: BoxDecoration(
                        color: isDarkMode ? Colors.white24 : Colors.grey[300],
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            isSwahili
                                ? 'Maelezo ya Ununuzi'
                                : 'Purchase Details',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w700,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                  children: [
                    _DetailRow(
                      label: isSwahili ? 'Wasambazaji' : 'Supplier',
                      value:
                          (purchase['supplier']
                                  as Map<String, dynamic>?)?['name']
                              as String? ??
                          '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Tarehe' : 'Date',
                      value: _formatDate(purchase['date'] as String?),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'Tax Invoice',
                      value: purchase['tax_invoice'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'Total Amount',
                      value: _formatMoney(_toDouble(purchase['total_amount'])),
                      isDarkMode: isDarkMode,
                      valueColor: const Color(0xFF3B82F6),
                    ),
                    _DetailRow(
                      label: 'Amount (Exc. VAT)',
                      value: _formatMoney(
                        _toDouble(purchase['amount_vat_exc']),
                      ),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'VAT',
                      value: _formatMoney(_toDouble(purchase['vat_amount'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Hali' : 'Status',
                      value: purchase['status'] as String? ?? 'PENDING',
                      isDarkMode: isDarkMode,
                    ),
                    if ((purchase['notes'] as String?)?.isNotEmpty ?? false)
                      _DetailRow(
                        label: isSwahili ? 'Maelezo' : 'Notes',
                        value: purchase['notes'] as String? ?? '-',
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

  String _formatDate(String? date) {
    if (date == null || date.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date ?? '-';
    }
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatMoney(double amount) {
    if (amount <= 0) return '-';
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
  }
}

class _PurchaseFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? purchase;
  final bool isNew;

  const _PurchaseFormSheet({this.purchase, required this.isNew});

  @override
  ConsumerState<_PurchaseFormSheet> createState() => _PurchaseFormSheetState();
}

class _PurchaseFormSheetState extends ConsumerState<_PurchaseFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _totalAmountController;
  late final TextEditingController _amountVatExcController;
  late final TextEditingController _vatAmountController;
  late final TextEditingController _taxInvoiceController;
  late final TextEditingController _notesController;
  int? _selectedSupplierId;
  DateTime _selectedDate = DateTime.now();
  DateTime? _invoiceDate;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _totalAmountController = TextEditingController(
      text: widget.purchase?['total_amount']?.toString() ?? '',
    );
    _amountVatExcController = TextEditingController(
      text: widget.purchase?['amount_vat_exc']?.toString() ?? '',
    );
    _vatAmountController = TextEditingController(
      text: widget.purchase?['vat_amount']?.toString() ?? '',
    );
    _taxInvoiceController = TextEditingController(
      text: widget.purchase?['tax_invoice']?.toString() ?? '',
    );
    _notesController = TextEditingController(
      text: widget.purchase?['notes']?.toString() ?? '',
    );
    _selectedSupplierId = widget.purchase?['supplier_id'] as int?;
    if (widget.purchase?['date'] != null) {
      try {
        _selectedDate = DateTime.parse(widget.purchase!['date'] as String);
      } catch (_) {}
    }
    if (widget.purchase?['invoice_date'] != null) {
      try {
        _invoiceDate = DateTime.parse(
          widget.purchase!['invoice_date'] as String,
        );
      } catch (_) {}
    }
  }

  @override
  void dispose() {
    _totalAmountController.dispose();
    _amountVatExcController.dispose();
    _vatAmountController.dispose();
    _taxInvoiceController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final suppliersAsync = ref.watch(_suppliersProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        top: false,
        child: SingleChildScrollView(
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
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  widget.isNew
                      ? (isSwahili ? 'Ununuzi Mpya' : 'New Purchase')
                      : (isSwahili ? 'Hariri Ununuzi' : 'Edit Purchase'),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  '${isSwahili ? 'Wasambazaji' : 'Supplier'} *',
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
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: suppliersAsync.when(
                    loading: () => const Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                    error: (_, __) => Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                    ),
                    data: (suppliers) => DropdownButtonHideUnderline(
                      child: DropdownButton<int?>(
                        value: _selectedSupplierId,
                        isExpanded: true,
                        hint: Text(
                          isSwahili ? 'Chagua Wasambazaji' : 'Select Supplier',
                        ),
                        dropdownColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        items: (suppliers as List)
                            .map(
                              (s) => DropdownMenuItem(
                                value: s['id'] as int,
                                child: Text(s['name'] as String? ?? ''),
                              ),
                            )
                            .toList(),
                        onChanged: (v) =>
                            setState(() => _selectedSupplierId = v),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
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
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _selectedDate,
                      firstDate: DateTime(2020),
                      lastDate: DateTime.now().add(const Duration(days: 365)),
                    );
                    if (picked != null) {
                      setState(() => _selectedDate = picked);
                    }
                  },
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: isDarkMode
                          ? const Color(0xFF2A2A3E)
                          : Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          Icons.calendar_today,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                        const SizedBox(width: 12),
                        Text(
                          DateFormat('dd MMM yyyy').format(_selectedDate),
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
                const SizedBox(height: 16),
                Text(
                  'Tax Invoice',
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
                  controller: _taxInvoiceController,
                  decoration: InputDecoration(
                    hintText: 'Invoice number',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Total Amount *',
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
                  controller: _totalAmountController,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    hintText: '0.00',
                    prefixText: 'TZS ',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  validator: (v) => (v == null || v.isEmpty)
                      ? (isSwahili
                            ? 'Kiasi kinahitajika'
                            : 'Amount is required')
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
                            'Amount (Exc. VAT)',
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
                            controller: _amountVatExcController,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              hintText: '0.00',
                              prefixText: 'TZS ',
                              filled: true,
                              fillColor: isDarkMode
                                  ? const Color(0xFF2A2A3E)
                                  : Colors.grey[100],
                            ),
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
                            'VAT',
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
                            controller: _vatAmountController,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              hintText: '0.00',
                              prefixText: 'TZS ',
                              filled: true,
                              fillColor: isDarkMode
                                  ? const Color(0xFF2A2A3E)
                                  : Colors.grey[100],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili ? 'Maelezo' : 'Notes',
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
                  controller: _notesController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Maelezo ya ziada...'
                        : 'Additional notes...',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                ),
                const SizedBox(height: 32),
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
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            widget.isNew
                                ? (isSwahili ? 'Hifadhi' : 'Save')
                                : (isSwahili ? 'Sasisha' : 'Update'),
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedSupplierId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider)
                ? 'Chagua Wasambazaji'
                : 'Select Supplier',
          ),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    setState(() => _loading = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'supplier_id': _selectedSupplierId,
        'date': DateFormat('yyyy-MM-dd').format(_selectedDate),
        'tax_invoice': _taxInvoiceController.text.isEmpty
            ? null
            : _taxInvoiceController.text,
        'invoice_date': _invoiceDate != null
            ? DateFormat('yyyy-MM-dd').format(_invoiceDate!)
            : DateFormat('yyyy-MM-dd').format(_selectedDate),
        'total_amount': double.tryParse(_totalAmountController.text) ?? 0,
        'amount_vat_exc': double.tryParse(_amountVatExcController.text) ?? 0,
        'vat_amount': double.tryParse(_vatAmountController.text) ?? 0,
        'notes': _notesController.text.isEmpty ? null : _notesController.text,
      };

      if (widget.isNew) {
        await api.post('/purchases', data: data);
      } else {
        await api.put('/purchases/${widget.purchase!['id']}', data: data);
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }
}

class _PurchaseCard extends StatelessWidget {
  final Map<String, dynamic> purchase;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _PurchaseCard({
    required this.purchase,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final status = purchase['status'] as String? ?? 'PENDING';
    final amount = _toDouble(purchase['total_amount']);

    Color statusColor;
    switch (status.toUpperCase()) {
      case 'APPROVED':
      case 'COMPLETED':
        statusColor = const Color(0xFF27AE60);
        break;
      case 'SUBMITTED':
      case 'PENDING':
      case 'CREATED':
        statusColor = const Color(0xFFF59E0B);
        break;
      case 'REJECTED':
        statusColor = const Color(0xFFEF4444);
        break;
      default:
        statusColor = const Color(0xFF95A5A6);
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
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
                      color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.shopping_cart,
                      color: Color(0xFF3B82F6),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          (purchase['supplier']
                                      as Map<String, dynamic>?)?['name']
                                  as String? ??
                              '-',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _formatDate(purchase['date'] as String?),
                          style: TextStyle(
                            fontSize: 13,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        if ((purchase['tax_invoice'] as String?)?.isNotEmpty ??
                            false) ...[
                          const SizedBox(height: 2),
                          Text(
                            'Invoice: ${purchase['tax_invoice']}',
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
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
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
              const SizedBox(height: 16),
              const Divider(height: 1),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Total Amount',
                          style: TextStyle(
                            fontSize: 11,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _formatMoney(amount),
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF3B82F6),
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'edit') onEdit();
                      if (value == 'delete') onDelete();
                    },
                    itemBuilder: (ctx) => [
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
                            const Icon(
                              Icons.delete,
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
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(String? date) {
    if (date == null || date.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date ?? '-';
    }
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatMoney(double amount) {
    if (amount <= 0) return '-';
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
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
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color:
                    valueColor ??
                    (isDarkMode ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
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
