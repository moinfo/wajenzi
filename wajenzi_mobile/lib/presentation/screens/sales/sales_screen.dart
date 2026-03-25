import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _salesProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/sales');

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
    debugPrint('Error parsing sales: $e $st');
  }

  return {'items': items, 'meta': meta};
});

final _efdsProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/sales/efds');
  return response.data['data'] as List? ?? [];
});

final _saleDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/sales/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class SalesScreen extends ConsumerWidget {
  const SalesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final salesAsync = ref.watch(_salesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Uuzaji' : 'Sales'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza' : 'Add',
            onPressed: () => _showSaleForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_salesProvider);
        },
        child: salesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_salesProvider),
          ),
          data: (payload) {
            final List sales = payload['items'] as List? ?? [];

            if (sales.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.point_of_sale_outlined,
                    size: 56,
                    color: Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna uuzaji uliopatikana' : 'No sales found',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 24),
                  Center(
                    child: ElevatedButton.icon(
                      onPressed: () => _showSaleForm(context, ref),
                      icon: const Icon(Icons.add),
                      label: Text(isSwahili ? 'Ongeza Uuzaji' : 'Add Sale'),
                    ),
                  ),
                ],
              );
            }

            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: sales.length + 1,
              itemBuilder: (context, index) {
                if (index == sales.length) {
                  return const SizedBox(height: 80);
                }
                final sale = sales[index] as Map<String, dynamic>;
                return _SaleCard(
                  sale: sale,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  onEdit: () => _showSaleForm(context, ref, sale: sale),
                  onDelete: () => _deleteSale(context, ref, sale),
                  onTap: () => _showSaleDetails(context, ref, sale),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _showSaleForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? sale,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _SaleFormSheet(sale: sale, isNew: sale == null),
    );

    if (result == true) {
      ref.invalidate(_salesProvider);
    }
  }

  Future<void> _deleteSale(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> sale,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa Uuzaji' : 'Delete Sale'),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta uuzaji wa tarehe ${sale['date']}?'
              : 'Are you sure you want to delete sale from ${sale['date']}?',
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
      await api.delete('/sales/${sale['id']}');

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Umefutwa' : 'Deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }

      ref.invalidate(_salesProvider);
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

  void _showSaleDetails(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> sale,
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
                            isSwahili ? 'Maelezo ya Uuzaji' : 'Sale Details',
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
                      label: 'EFD',
                      value:
                          (sale['efd'] as Map<String, dynamic>?)?['name']
                              as String? ??
                          '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Tarehe' : 'Date',
                      value: _formatDate(sale['date'] as String?),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'Document #',
                      value: sale['document_number'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'Turnover',
                      value: _formatMoney(_toDouble(sale['amount'])),
                      isDarkMode: isDarkMode,
                      valueColor: const Color(0xFF27AE60),
                    ),
                    _DetailRow(
                      label: 'NET (A+B+C)',
                      value: _formatMoney(_toDouble(sale['net'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'Tax',
                      value: _formatMoney(_toDouble(sale['tax'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'VAT',
                      value: _formatMoney(_toDouble(sale['vat'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: 'Turn Over',
                      value: _formatMoney(_toDouble(sale['turn_over'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Hali' : 'Status',
                      value: sale['status'] as String? ?? 'PENDING',
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

class _SaleFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? sale;
  final bool isNew;

  const _SaleFormSheet({this.sale, required this.isNew});

  @override
  ConsumerState<_SaleFormSheet> createState() => _SaleFormSheetState();
}

class _SaleFormSheetState extends ConsumerState<_SaleFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _amountController;
  late final TextEditingController _netController;
  late final TextEditingController _taxController;
  late final TextEditingController _turnOverController;
  late final TextEditingController _vatController;
  int? _selectedEfdId;
  DateTime _selectedDate = DateTime.now();
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _amountController = TextEditingController(
      text: widget.sale?['amount']?.toString() ?? '',
    );
    _netController = TextEditingController(
      text: widget.sale?['net']?.toString() ?? '',
    );
    _taxController = TextEditingController(
      text: widget.sale?['tax']?.toString() ?? '',
    );
    _turnOverController = TextEditingController(
      text: widget.sale?['turn_over']?.toString() ?? '',
    );
    _vatController = TextEditingController(
      text: widget.sale?['vat']?.toString() ?? '',
    );
    _selectedEfdId = widget.sale?['efd_id'] as int?;
    if (widget.sale?['date'] != null) {
      try {
        _selectedDate = DateTime.parse(widget.sale!['date'] as String);
      } catch (_) {}
    }
  }

  @override
  void dispose() {
    _amountController.dispose();
    _netController.dispose();
    _taxController.dispose();
    _turnOverController.dispose();
    _vatController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final efdsAsync = ref.watch(_efdsProvider);

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
                      ? (isSwahili ? 'Uuzaji Mpya' : 'New Sale')
                      : (isSwahili ? 'Hariri Uuzaji' : 'Edit Sale'),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  'EFD *',
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
                  child: efdsAsync.when(
                    loading: () => const Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                    error: (_, __) => Padding(
                      padding: const EdgeInsets.all(16),
                      child: Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                    ),
                    data: (efds) => DropdownButtonHideUnderline(
                      child: DropdownButton<int?>(
                        value: _selectedEfdId,
                        isExpanded: true,
                        hint: Text(isSwahili ? 'Chagua EFD' : 'Select EFD'),
                        dropdownColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        items: (efds as List)
                            .map(
                              (e) => DropdownMenuItem(
                                value: e['id'] as int,
                                child: Text(e['name'] as String? ?? ''),
                              ),
                            )
                            .toList(),
                        onChanged: (v) => setState(() => _selectedEfdId = v),
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
                      lastDate: DateTime.now(),
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
                  'Amount (Turnover) *',
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
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    hintText: '0.00',
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
                            'NET (A+B+C)',
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
                            controller: _netController,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              hintText: '0.00',
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
                            'Tax',
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
                            controller: _taxController,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              hintText: '0.00',
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
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Turn Over',
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
                            controller: _turnOverController,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              hintText: '0.00',
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
                            controller: _vatController,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
                              hintText: '0.00',
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
    if (_selectedEfdId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider) ? 'Chagua EFD' : 'Select EFD',
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
        'efd_id': _selectedEfdId,
        'date': DateFormat('yyyy-MM-dd').format(_selectedDate),
        'amount': double.tryParse(_amountController.text) ?? 0,
        'net': double.tryParse(_netController.text) ?? 0,
        'tax': double.tryParse(_taxController.text) ?? 0,
        'turn_over': double.tryParse(_turnOverController.text) ?? 0,
        'vat': double.tryParse(_vatController.text) ?? 0,
      };

      if (widget.isNew) {
        await api.post('/sales', data: data);
      } else {
        await api.put('/sales/${widget.sale!['id']}', data: data);
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

class _SaleCard extends StatelessWidget {
  final Map<String, dynamic> sale;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _SaleCard({
    required this.sale,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final status = sale['status'] as String? ?? 'PENDING';
    final amount = _toDouble(sale['amount']);

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
                      color: const Color(0xFF27AE60).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.point_of_sale,
                      color: Color(0xFF27AE60),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          (sale['efd'] as Map<String, dynamic>?)?['name']
                                  as String? ??
                              '-',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _formatDate(sale['date'] as String?),
                          style: TextStyle(
                            fontSize: 13,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
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
                          'Turnover',
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
                            color: Color(0xFF27AE60),
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
            width: 100,
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
              value,
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
