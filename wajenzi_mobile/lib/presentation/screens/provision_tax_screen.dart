import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/network/api_client.dart';
import '../../core/router/app_router.dart';
import '../providers/settings_provider.dart';
import '../widgets/common/loading_widget.dart';
import '../widgets/common/empty_state_widget.dart';
import '../widgets/common/filter_bottom_sheet.dart';
import 'vat/vat_shared.dart';

class ProvisionTaxScreen extends ConsumerStatefulWidget {
  const ProvisionTaxScreen({super.key});

  @override
  ConsumerState<ProvisionTaxScreen> createState() => _ProvisionTaxScreenState();
}

class _ProvisionTaxScreenState extends ConsumerState<ProvisionTaxScreen> {
  final ScrollController _scrollController = ScrollController();
  List<dynamic> _taxes = [];
  Map<String, dynamic> _filters = {};
  Map<String, dynamic> _referenceData = {};
  Map<String, dynamic> _summary = {};
  bool _isLoading = false;
  bool _hasMore = true;
  int _currentPage = 1;

  @override
  void initState() {
    super.initState();
    _loadData();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadData({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _currentPage = 1;
        _taxes.clear();
        _hasMore = true;
        _isLoading = true;
      });
    } else {
      setState(() {
        _isLoading = true;
      });
    }

    try {
      final api = ref.read(apiClientProvider);

      // Load taxes and reference data in parallel
      final taxesResponse = await api.get(
        '/provision-tax',
        queryParameters: {
          ..._filters,
          'per_page': '20',
          'page': _currentPage.toString(),
        },
      );

      final referenceDataResponse = await api.get(
        '/provision-tax/reference-data',
      );

      if (taxesResponse.statusCode == 200 &&
          referenceDataResponse.statusCode == 200) {
        final taxesData = taxesResponse.data['data'];
        final referenceData = referenceDataResponse.data['data'];

        setState(() {
          if (refresh) {
            _taxes = taxesData['data'] ?? [];
          } else {
            _taxes.addAll(taxesData['data'] ?? []);
          }
          _referenceData = referenceData;
          _summary = taxesData['summary'] ?? {};
          _hasMore =
              (taxesData['meta']['current_page'] ?? 1) <
              (taxesData['meta']['last_page'] ?? 1);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });

      String errorMessage = 'Error loading provision taxes';

      // Check for authentication errors
      if (e.toString().contains('401') ||
          e.toString().contains('Unauthorized')) {
        errorMessage = 'Authentication required. Please login again.';
      } else if (e.toString().contains('403') ||
          e.toString().contains('Forbidden')) {
        errorMessage =
            'Permission denied. You may not have access to provision taxes.';
      } else if (e.toString().contains('404')) {
        errorMessage =
            'Provision taxes endpoint not found. Please check API configuration.';
      } else if (e.toString().contains('Connection')) {
        errorMessage =
            'Cannot connect to server. Please check your internet connection.';
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            duration: const Duration(seconds: 3),
            action: SnackBarAction(
              label: 'Retry',
              onPressed: () => _loadData(refresh: true),
            ),
          ),
        );
      }
    }
  }

  Future<void> _loadMoreData() async {
    if (_isLoading || !_hasMore) return;

    setState(() {
      _currentPage++;
      _isLoading = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get(
        '/provision-tax',
        queryParameters: {
          ..._filters,
          'per_page': '20',
          'page': _currentPage.toString(),
        },
      );

      if (response.statusCode == 200) {
        final data = response.data['data'];
        setState(() {
          _taxes.addAll(data['data'] ?? []);
          _hasMore =
              (data['meta']['current_page'] ?? 1) <
              (data['meta']['last_page'] ?? 1);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error loading more taxes: $e')));
      }
    }
  }

  void _onScroll() {
    if (_scrollController.position.pixels ==
        _scrollController.position.maxScrollExtent) {
      if (!_isLoading && _hasMore) {
        _loadMoreData();
      }
    }
  }

  void _showFilterBottomSheet() {
    // Convert reference data to options format expected by FilterBottomSheet
    Map<String, Map<String, dynamic>> options = {};

    if (_referenceData['banks'] != null) {
      options['bank_id'] = {
        'label': 'Bank',
        'type': 'select',
        'options': _referenceData['banks'],
      };
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => FilterBottomSheet(
        title: 'Filter Provision Taxes',
        filters: _filters,
        options: options,
        onApply: (filters) {
          setState(() {
            _filters = filters;
          });
          _loadData(refresh: true);
          Navigator.pop(context);
        },
        onReset: () {
          setState(() {
            _filters = {};
          });
          _loadData(refresh: true);
          Navigator.pop(context);
        },
      ),
    );
  }

  String _formatCurrency(dynamic amount) {
    final number = amount is num
        ? amount.toDouble()
        : double.tryParse(amount?.toString() ?? '') ?? 0;
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(number);
  }

  Future<void> _openTaxForm({
    Map<String, dynamic>? tax,
    bool isSwahili = false,
  }) async {
    final isEdit = tax != null;
    final banks = (_referenceData['banks'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();

    if (banks.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Hakuna benki zinazopatikana kwa kodi ya akiba.'
                : 'No banks available for provision tax.',
          ),
        ),
      );
      return;
    }

    final formKey = GlobalKey<FormState>();
    final dateCtrl = TextEditingController(
      text:
          tax?['date']?.toString() ??
          DateFormat('yyyy-MM-dd').format(DateTime.now()),
    );
    final amountCtrl = TextEditingController(
      text: tax?['amount']?.toString() ?? '',
    );
    final descriptionCtrl = TextEditingController(
      text: tax?['description']?.toString() ?? '',
    );
    final debitCtrl = TextEditingController(
      text: tax?['debit_number']?.toString() ?? '',
    );
    int? selectedBankId = _toNullableInt(tax?['bank_id']);
    File? selectedFile;

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (sheetContext, setSheetState) {
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
                    MediaQuery.of(sheetContext).viewInsets.bottom + 24,
                  ),
                  child: Form(
                    key: formKey,
                    child: SingleChildScrollView(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
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
                            isEdit
                                ? (isSwahili
                                      ? 'Hariri Kodi ya Akiba'
                                      : 'Edit Provision Tax')
                                : (isSwahili
                                      ? 'Kodi Mpya ya Akiba'
                                      : 'New Provision Tax'),
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 18),
                          vatDropdown<int>(
                            value: selectedBankId,
                            items: banks
                                .map((bank) => _toInt(bank['id']))
                                .where((id) => id > 0)
                                .toList(),
                            label: isSwahili ? 'Benki *' : 'Bank *',
                            isDark: false,
                            labelBuilder: (id) {
                              final bank = banks.firstWhere(
                                (item) => _toInt(item['id']) == id,
                                orElse: () => const <String, dynamic>{},
                              );
                              return (bank['bank_name'] ??
                                      bank['name'] ??
                                      bank['label'] ??
                                      '-')
                                  .toString();
                            },
                            onChanged: (value) =>
                                setSheetState(() => selectedBankId = value),
                            validator: (value) => value == null
                                ? (isSwahili ? 'Inahitajika' : 'Required')
                                : null,
                          ),
                          vatTextField(
                            controller: amountCtrl,
                            label: isSwahili ? 'Kiasi *' : 'Amount *',
                            isDark: false,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            validator: (value) =>
                                (value == null || value.trim().isEmpty)
                                ? (isSwahili ? 'Inahitajika' : 'Required')
                                : null,
                          ),
                          vatTextField(
                            controller: descriptionCtrl,
                            label: isSwahili ? 'Maelezo *' : 'Description *',
                            isDark: false,
                            validator: (value) =>
                                (value == null || value.trim().isEmpty)
                                ? (isSwahili ? 'Inahitajika' : 'Required')
                                : null,
                          ),
                          vatTextField(
                            controller: debitCtrl,
                            label: isSwahili
                                ? 'Nambari ya Debiti'
                                : 'Debit Number',
                            isDark: false,
                          ),
                          vatTextField(
                            controller: dateCtrl,
                            label: isSwahili ? 'Tarehe *' : 'Date *',
                            isDark: false,
                            readOnly: true,
                            onTap: () async {
                              final picked = await vatPickDate(
                                sheetContext,
                                DateTime.tryParse(dateCtrl.text) ??
                                    DateTime.now(),
                              );
                              if (picked != null) {
                                dateCtrl.text = vatDateFmt(picked);
                              }
                            },
                            validator: (value) =>
                                (value == null || value.trim().isEmpty)
                                ? (isSwahili ? 'Inahitajika' : 'Required')
                                : null,
                          ),
                          VatFilePicker(
                            file: selectedFile,
                            isDark: false,
                            isSwahili: false,
                            onPicked: (file) =>
                                setSheetState(() => selectedFile = file),
                          ),
                          const SizedBox(height: 12),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              onPressed: () async {
                                if (!formKey.currentState!.validate()) return;
                                if (selectedBankId == null) return;

                                final fields = <String, dynamic>{
                                  'date': dateCtrl.text.trim(),
                                  'amount':
                                      double.tryParse(amountCtrl.text.trim()) ??
                                      0,
                                  'description': descriptionCtrl.text.trim(),
                                  'bank_id': selectedBankId,
                                  'debit_number': _blankToNull(debitCtrl.text),
                                };

                                try {
                                  final api = ref.read(apiClientProvider);
                                  if (isEdit) {
                                    if (selectedFile != null) {
                                      fields['_method'] = 'PUT';
                                      final formData = await vatBuildFormData(
                                        fields,
                                        selectedFile,
                                      );
                                      await api.uploadFile(
                                        '/provision-tax/${tax['id']}',
                                        data: formData,
                                      );
                                    } else {
                                      await api.put(
                                        '/provision-tax/${tax['id']}',
                                        data: fields,
                                      );
                                    }
                                  } else {
                                    if (selectedFile != null) {
                                      final formData = await vatBuildFormData(
                                        fields,
                                        selectedFile,
                                      );
                                      await api.uploadFile(
                                        '/provision-tax',
                                        data: formData,
                                      );
                                    } else {
                                      await api.post(
                                        '/provision-tax',
                                        data: fields,
                                      );
                                    }
                                  }

                                  if (sheetContext.mounted) {
                                    Navigator.of(sheetContext).pop(true);
                                  }
                                } catch (e) {
                                  if (sheetContext.mounted) {
                                    ScaffoldMessenger.of(
                                      sheetContext,
                                    ).showSnackBar(
                                      SnackBar(
                                        backgroundColor: Colors.red,
                                        content: Text(vatErrorMessage(e)),
                                      ),
                                    );
                                  }
                                }
                              },
                              child: Text(
                                isEdit
                                    ? (isSwahili ? 'Sasisha' : 'Update')
                                    : (isSwahili ? 'Hifadhi' : 'Save'),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );

    dateCtrl.dispose();
    amountCtrl.dispose();
    descriptionCtrl.dispose();
    debitCtrl.dispose();

    if (result == true) {
      _loadData(refresh: true);
    }
  }

  Future<void> _deleteTax(
    Map<String, dynamic> tax, {
    bool isSwahili = false,
  }) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Kodi ya Akiba' : 'Delete Provision Tax'),
        content: Text(
          isSwahili
              ? 'Futa "${tax['description'] ?? 'rekodi hii'}"?'
              : 'Delete "${tax['description'] ?? 'this record'}"?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/provision-tax/${tax['id']}');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: Colors.green,
          content: Text(
            isSwahili
                ? 'Kodi ya akiba imefutwa kwa mafanikio'
                : 'Provision tax deleted successfully',
          ),
        ),
      );
      _loadData(refresh: true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: Colors.red,
          content: Text(vatErrorMessage(e)),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Kodi ya Akiba' : 'Provision Tax'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            tooltip: isSwahili ? 'Chuja' : 'Filter',
            onPressed: _showFilterBottomSheet,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: isSwahili ? 'Onyesha Upya' : 'Refresh',
            onPressed: () => _loadData(refresh: true),
          ),
        ],
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openTaxForm(isSwahili: isSwahili),
          tooltip: isSwahili ? 'Ongeza' : 'Add',
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => _loadData(refresh: true),
        child: _isLoading
            ? LoadingWidget(
                message: isSwahili
                    ? 'Inapakia kodi za akiba...'
                    : 'Loading provision taxes...',
              )
            : _taxes.isEmpty && _summary.isEmpty
            ? EmptyStateWidget(
                message: isSwahili
                    ? 'Hakuna kodi za akiba zilizopatikana'
                    : 'No provision taxes found',
                icon: Icons.receipt_long,
              )
            : Column(
                children: [
                  // Summary Cards
                  if (_summary.isNotEmpty) ...[
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          Expanded(
                            child: _SummaryCard(
                              title: isSwahili
                                  ? 'Jumla ya Kiasi'
                                  : 'Total Amount',
                              value: _formatCurrency(
                                _summary['total_amount'] ?? 0,
                              ),
                              icon: Icons.trending_up,
                              color: Colors.green,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _SummaryCard(
                              title: isSwahili
                                  ? 'Jumla ya Rekodi'
                                  : 'Total Records',
                              value: (_summary['count'] ?? 0).toString(),
                              icon: Icons.receipt_long,
                              color: Colors.blue,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  // Taxes List
                  Expanded(
                    child: _taxes.isEmpty
                        ? Center(
                            child: Text(
                              isSwahili
                                  ? 'Hakuna kodi zinazolingana na vichujio'
                                  : 'No taxes found for current filters',
                              style: TextStyle(color: Colors.grey[600]),
                            ),
                          )
                        : ListView.builder(
                            controller: _scrollController,
                            padding: const EdgeInsets.all(16),
                            itemCount: _taxes.length + (_hasMore ? 1 : 0),
                            itemBuilder: (context, index) {
                              if (index == _taxes.length) {
                                return const Padding(
                                  padding: EdgeInsets.all(16),
                                  child: Center(
                                    child: CircularProgressIndicator(),
                                  ),
                                );
                              }

                              final tax = _taxes[index];
                              return ProvisionTaxCard(
                                tax: Map<String, dynamic>.from(tax as Map),
                                isSwahili: isSwahili,
                                onTap: () => _openTaxForm(
                                  tax: Map<String, dynamic>.from(tax as Map),
                                  isSwahili: isSwahili,
                                ),
                                onDelete: () => _deleteTax(
                                  Map<String, dynamic>.from(tax as Map),
                                  isSwahili: isSwahili,
                                ),
                              );
                            },
                          ),
                  ),
                ],
              ),
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const _SummaryCard({
    super.key,
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 20),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                    overflow: TextOverflow.ellipsis,
                    maxLines: 1,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 3),
            Text(
              value,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: color,
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
          ],
        ),
      ),
    );
  }
}

class ProvisionTaxCard extends StatelessWidget {
  final Map<String, dynamic> tax;
  final VoidCallback onTap;
  final VoidCallback onDelete;
  final bool isSwahili;

  const ProvisionTaxCard({
    super.key,
    required this.tax,
    required this.onTap,
    required this.onDelete,
    this.isSwahili = false,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tax['description'] ?? 'No Description',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                          overflow: TextOverflow.ellipsis,
                          maxLines: 1,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          tax['date'] ??
                              (isSwahili ? 'Tarehe Hakuna' : 'No Date'),
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'edit') {
                        onTap();
                      } else if (value == 'delete') {
                        onDelete();
                      }
                    },
                    itemBuilder: (context) => [
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
              Row(
                children: [
                  Icon(Icons.attach_money, size: 16, color: Colors.green),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      _formatCurrency(tax['amount'] ?? 0),
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (tax['bank'] != null) ...[
                    const SizedBox(width: 8),
                    Icon(
                      Icons.account_balance,
                      size: 16,
                      color: Colors.grey[600],
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        tax['bank']['bank_name'] ?? 'Unknown Bank',
                        style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                        overflow: TextOverflow.ellipsis,
                        maxLines: 1,
                      ),
                    ),
                  ],
                ],
              ),
              if (tax['debit_number'] != null) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.receipt_long, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        '${isSwahili ? 'Debiti #' : 'Debit #'}: ${tax['debit_number']}',
                        style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],
              if (tax['file'] != null) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.attach_file, size: 16, color: Colors.blue),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        isSwahili
                            ? 'Kiambatanisho kinapatikana'
                            : 'Attachment available',
                        style: TextStyle(fontSize: 12, color: Colors.blue),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Icon(Icons.download, size: 16, color: Colors.blue),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  String _formatCurrency(dynamic amount) {
    final number = amount is num
        ? amount.toDouble()
        : double.tryParse(amount?.toString() ?? '') ?? 0;
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(number);
  }
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = _toInt(value);
  return parsed <= 0 ? null : parsed;
}

String? _blankToNull(String? value) {
  final text = value?.trim() ?? '';
  return text.isEmpty ? null : text;
}
