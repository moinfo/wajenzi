import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/empty_state_widget.dart';
import 'package:go_router/go_router.dart';

final _vatAnalysisDataProvider = FutureProvider.family
    .autoDispose<Map<String, dynamic>, Map<String, String>>((
      ref,
      params,
    ) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/reports/vat-analysis-report',
        queryParameters: params,
      );
      return response.data is Map<String, dynamic>
          ? Map<String, dynamic>.from(response.data as Map)
          : {};
    });

class VatAnalysisReportScreen extends ConsumerStatefulWidget {
  const VatAnalysisReportScreen({super.key});

  @override
  ConsumerState<VatAnalysisReportScreen> createState() =>
      _VatAnalysisReportScreenState();
}

class _VatAnalysisReportScreenState
    extends ConsumerState<VatAnalysisReportScreen> {
  DateTimeRange? _dateRange;

  @override
  void initState() {
    super.initState();
    _dateRange = DateTimeRange(
      start: DateTime.now().subtract(const Duration(days: 30)),
      end: DateTime.now(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final params = {
      'start_date': DateFormat('yyyy-MM-dd').format(_dateRange!.start),
      'end_date': DateFormat('yyyy-MM-dd').format(_dateRange!.end),
    };
    final dataAsync = ref.watch(_vatAnalysisDataProvider(params));

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.go('/reports'),
        ),
        title: Text(
          isSwahili ? 'Ripoti ya Uchambuzi wa VAT' : 'VAT Analysis Report',
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today),
            tooltip: isSwahili ? 'Chagua Tarehe' : 'Select Date',
            onPressed: () async {
              final picked = await showDateRangePicker(
                context: context,
                firstDate: DateTime(2020),
                lastDate: DateTime.now(),
                initialDateRange: _dateRange,
              );
              if (picked != null) {
                setState(() => _dateRange = picked);
              }
            },
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: isSwahili ? 'Onyesha Upya' : 'Refresh',
            onPressed: () => ref.invalidate(_vatAnalysisDataProvider(params)),
          ),
        ],
      ),
      body: Column(
        children: [
          if (_dateRange != null)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              color: Theme.of(context).colorScheme.primaryContainer,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.date_range,
                    size: 18,
                    color: Theme.of(context).colorScheme.onPrimaryContainer,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '${DateFormat('MMM d, yyyy').format(_dateRange!.start)} - ${DateFormat('MMM d, yyyy').format(_dateRange!.end)}',
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: Theme.of(context).colorScheme.onPrimaryContainer,
                    ),
                  ),
                ],
              ),
            ),
          Expanded(
            child: dataAsync.when(
              loading: () => LoadingWidget(
                message: isSwahili ? 'Inapakia data...' : 'Loading data...',
              ),
              error: (error, _) => Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.error_outline,
                      size: 48,
                      color: Colors.red,
                    ),
                    const SizedBox(height: 16),
                    Text(isSwahili ? 'Hitilafu' : 'Error'),
                    const SizedBox(height: 8),
                    ElevatedButton.icon(
                      onPressed: () =>
                          ref.invalidate(_vatAnalysisDataProvider(params)),
                      icon: const Icon(Icons.refresh),
                      label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                    ),
                  ],
                ),
              ),
              data: (data) => data.isEmpty
                  ? EmptyStateWidget(
                      message: isSwahili ? 'Hakuna data' : 'No data available',
                      icon: Icons.bar_chart,
                    )
                  : _VatAnalysisContent(data: data, isSwahili: isSwahili),
            ),
          ),
        ],
      ),
    );
  }
}

class _VatAnalysisContent extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _VatAnalysisContent({required this.data, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _SummarySection(data: data, isSwahili: isSwahili),
          const SizedBox(height: 16),
          _SalesTable(data: data, isSwahili: isSwahili),
          const SizedBox(height: 16),
          _PurchasesTable(data: data, isSwahili: isSwahili),
        ],
      ),
    );
  }
}

class _SummarySection extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _SummarySection({required this.data, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final totalTax = data['total_tax'] ?? data['total_vat'] ?? 0;
    final totalSales = data['total_sales'] ?? data['total_turnover'] ?? 0;
    final totalPurchases = data['total_purchases'] ?? 0;
    final vatPayable = data['vat_payable'] ?? 0;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Muhtasari' : 'Summary',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const Divider(),
            _SummaryRow(
              label: isSwahili ? 'Jumla ya Mauzo' : 'Total Sales',
              value: _formatCurrency(totalSales),
            ),
            _SummaryRow(
              label: isSwahili ? 'Jumla ya Ununuzi' : 'Total Purchases',
              value: _formatCurrency(totalPurchases),
            ),
            _SummaryRow(
              label: isSwahili ? 'Jumla ya VAT' : 'Total VAT',
              value: _formatCurrency(totalTax),
            ),
            _SummaryRow(
              label: isSwahili ? 'VAT Inayotakiwa' : 'VAT Payable',
              value: _formatCurrency(vatPayable),
              isHighlighted: true,
            ),
          ],
        ),
      ),
    );
  }

  String _formatCurrency(dynamic value) {
    final number = value is num ? value.toDouble() : 0.0;
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(number);
  }
}

class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isHighlighted;

  const _SummaryRow({
    required this.label,
    required this.value,
    this.isHighlighted = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Colors.grey[600])),
          Text(
            value,
            style: TextStyle(
              fontWeight: isHighlighted ? FontWeight.bold : FontWeight.w600,
              color: isHighlighted ? Colors.green : null,
              fontSize: isHighlighted ? 16 : 14,
            ),
          ),
        ],
      ),
    );
  }
}

class _SalesTable extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _SalesTable({required this.data, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final sales = data['sales'] as List? ?? [];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Mauzo' : 'Sales',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const Divider(),
            if (sales.isEmpty)
              Padding(
                padding: const EdgeInsets.all(16),
                child: Center(
                  child: Text(
                    isSwahili ? 'Hakuna mauzo' : 'No sales data',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ),
              )
            else
              ...sales
                  .take(10)
                  .map(
                    (item) => _TableRow(
                      data: Map<String, dynamic>.from(item as Map),
                      isSwahili: isSwahili,
                    ),
                  ),
          ],
        ),
      ),
    );
  }
}

class _PurchasesTable extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _PurchasesTable({required this.data, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final purchases = data['purchases'] as List? ?? [];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Ununuzi' : 'Purchases',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const Divider(),
            if (purchases.isEmpty)
              Padding(
                padding: const EdgeInsets.all(16),
                child: Center(
                  child: Text(
                    isSwahili ? 'Hakuna ununuzi' : 'No purchases data',
                    style: TextStyle(color: Colors.grey[600]),
                  ),
                ),
              )
            else
              ...purchases
                  .take(10)
                  .map(
                    (item) => _TableRow(
                      data: Map<String, dynamic>.from(item as Map),
                      isSwahili: isSwahili,
                    ),
                  ),
          ],
        ),
      ),
    );
  }
}

class _TableRow extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _TableRow({required this.data, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey[200]!)),
      ),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  data['date']?.toString() ?? '-',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                if (data['invoice'] != null)
                  Text(
                    '#${data['invoice']}',
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
              ],
            ),
          ),
          Expanded(
            child: Text(
              _formatCurrency(data['amount'] ?? data['total'] ?? 0),
              textAlign: TextAlign.right,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          Expanded(
            child: Text(
              _formatCurrency(data['vat'] ?? data['tax'] ?? 0),
              textAlign: TextAlign.right,
              style: TextStyle(color: Colors.green[700]),
            ),
          ),
        ],
      ),
    );
  }

  String _formatCurrency(dynamic value) {
    final number = value is num ? value.toDouble() : 0.0;
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(number);
  }
}
