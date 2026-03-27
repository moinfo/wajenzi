import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/network/api_client.dart';
import '../../../presentation/providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/empty_state_widget.dart';
import '../../widgets/common/filter_bottom_sheet.dart';

final _provisionTaxReportProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/reports/provision-tax');
  return response.data['data'] as Map<String, dynamic>;
});

class ProvisionTaxReportScreen extends ConsumerStatefulWidget {
  const ProvisionTaxReportScreen({super.key});

  @override
  ConsumerState<ProvisionTaxReportScreen> createState() => _ProvisionTaxReportScreenState();
}

class _ProvisionTaxReportScreenState extends ConsumerState<ProvisionTaxReportScreen> {
  final ScrollController _scrollController = ScrollController();
  List<dynamic> _taxes = [];
  Map<String, dynamic> _filters = {};
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
          if (refresh) {
            _taxes = data['data'] ?? [];
          } else {
            _taxes.addAll(data['data'] ?? []);
          }
          _summary = data['summary'] ?? {};
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

      String errorMessage = 'Error loading provision tax report';

      if (e.toString().contains('401') ||
          e.toString().contains('Unauthorized')) {
        errorMessage = 'Authentication required. Please login again.';
      } else if (e.toString().contains('403') ||
          e.toString().contains('Forbidden')) {
        errorMessage =
            'Permission denied. You may not have access to provision tax reports.';
      } else if (e.toString().contains('404')) {
        errorMessage =
            'Provision tax report endpoint not found. Please check API configuration.';
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
        ).showSnackBar(SnackBar(content: Text('Error loading more data: $e')));
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
    Map<String, Map<String, dynamic>> options = {};

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => FilterBottomSheet(
        title: 'Filter Provision Tax Report',
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

  String _formatCurrency(double amount) {
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(amount);
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ripoti ya Kodi ya Usalala' : 'Provision Tax Report'),
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterBottomSheet,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => _loadData(refresh: true),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => _loadData(refresh: true),
        child: _isLoading
            ? const LoadingWidget(message: 'Loading provision tax report...')
            : _taxes.isEmpty && _summary.isEmpty
                ? EmptyStateWidget(
                    message: isSwahili 
                        ? 'Hakuna data ya ripoti ya kodi ya usalala'
                        : 'No provision tax report data found',
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
                                  title: isSwahili ? 'Jumla ya Kiasi' : 'Total Amount',
                                  value: _formatCurrency(_summary['total_amount'] ?? 0),
                                  icon: Icons.trending_up,
                                  color: Colors.green,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: _SummaryCard(
                                  title: isSwahili ? 'Idadi ya Rekodi' : 'Total Records',
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
                                      ? 'Hakuna kodi ya usalala iliyopatikana kwa vichungi hivi'
                                      : 'No provision taxes found for current filters',
                                  style: TextStyle(
                                    color: Colors.grey[600],
                                  ),
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
                                      child: Center(child: CircularProgressIndicator()),
                                    );
                                  }

                                  final tax = _taxes[index];
                                  return ProvisionTaxReportCard(
                                    tax: tax,
                                    isDarkMode: isDarkMode,
                                    isSwahili: isSwahili,
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
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
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
                fontSize: 16,
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

class ProvisionTaxReportCard extends StatelessWidget {
  final dynamic tax;
  final bool isDarkMode;
  final bool isSwahili;

  const ProvisionTaxReportCard({
    super.key,
    required this.tax,
    required this.isDarkMode,
    required this.isSwahili,
  });

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

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
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
                        tax['date'] ?? 'No Date',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.green.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    isSwahili ? 'Imekamilika' : 'Completed',
                    style: TextStyle(
                      color: Colors.green,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
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
                  Icon(Icons.account_balance, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      tax['bank']['name'] ?? 'Unknown Bank',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
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
                      '${isSwahili ? 'Namba ya Deni' : 'Debit Number'}: ${tax['debit_number']}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
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
                      isSwahili ? 'Waraka Wamepatikana' : 'Attachment Available',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.blue,
                      ),
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
    );
  }
}
