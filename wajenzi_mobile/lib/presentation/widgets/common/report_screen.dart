import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import 'loading_widget.dart';
import 'empty_state_widget.dart';

class ReportScreen extends ConsumerStatefulWidget {
  final String title;
  final String titleSw;
  final String apiEndpoint;
  final List<Map<String, String>>? filterOptions;
  final Widget Function(Map<String, dynamic> data, bool isSwahili)?
  customBuilder;
  final String Function(bool isSwahili)? customEmptyMessage;

  const ReportScreen({
    super.key,
    required this.title,
    required this.titleSw,
    required this.apiEndpoint,
    this.filterOptions,
    this.customBuilder,
    this.customEmptyMessage,
  });

  @override
  ConsumerState<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends ConsumerState<ReportScreen> {
  bool _isLoading = false;
  Map<String, dynamic> _data = {};
  String? _error;
  DateTimeRange? _dateRange;

  @override
  void initState() {
    super.initState();
    _dateRange = DateTimeRange(
      start: DateTime.now().subtract(const Duration(days: 30)),
      end: DateTime.now(),
    );
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final params = <String, String>{
        'start_date': DateFormat('yyyy-MM-dd').format(_dateRange!.start),
        'end_date': DateFormat('yyyy-MM-dd').format(_dateRange!.end),
      };

      final response = await api.get(
        widget.apiEndpoint,
        queryParameters: params,
      );

      if (mounted) {
        setState(() {
          _data = response.data is Map<String, dynamic>
              ? Map<String, dynamic>.from(response.data as Map)
              : {};
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _selectDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: _dateRange,
    );

    if (picked != null) {
      setState(() => _dateRange = picked);
      _loadData();
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.go('/reports'),
        ),
        title: Text(isSwahili ? widget.titleSw : widget.title),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today),
            tooltip: isSwahili ? 'Chagua Tarehe' : 'Select Date',
            onPressed: _selectDateRange,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: isSwahili ? 'Onyesha Upya' : 'Refresh',
            onPressed: _loadData,
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
            child: _isLoading
                ? LoadingWidget(
                    message: isSwahili ? 'Inapakia data...' : 'Loading data...',
                  )
                : _error != null
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.error_outline,
                          size: 48,
                          color: Colors.red,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili
                              ? 'Hitilafu wakati wa kupakia'
                              : 'Error loading data',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        Text(_error!, textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: _loadData,
                          icon: const Icon(Icons.refresh),
                          label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                        ),
                      ],
                    ),
                  )
                : _data.isEmpty
                ? EmptyStateWidget(
                    message:
                        widget.customEmptyMessage?.call(isSwahili) ??
                        (isSwahili
                            ? 'Hakuna data ya ripoti'
                            : 'No report data available'),
                    icon: Icons.bar_chart,
                  )
                : widget.customBuilder?.call(_data, isSwahili) ??
                      _DefaultReportBuilder(data: _data, isSwahili: isSwahili),
          ),
        ],
      ),
    );
  }
}

class _DefaultReportBuilder extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _DefaultReportBuilder({required this.data, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildSummaryCards(),
          const SizedBox(height: 16),
          _buildDataTable(),
        ],
      ),
    );
  }

  Widget _buildSummaryCards() {
    final summaryItems = <Widget>[];

    data.forEach((key, value) {
      if (value is num && !key.contains('_') && key.length < 30) {
        summaryItems.add(
          Expanded(
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  children: [
                    Text(
                      _formatLabel(key),
                      style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _formatCurrency(value),
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      }
    });

    if (summaryItems.isEmpty) return const SizedBox.shrink();

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: summaryItems.take(4).toList(),
    );
  }

  Widget _buildDataTable() {
    final rows = <Widget>[];

    data.forEach((key, value) {
      if (value is List && (value as List).isNotEmpty) {
        rows.add(
          Card(
            margin: const EdgeInsets.only(bottom: 16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _formatLabel(key),
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const Divider(),
                  if (value.first is Map)
                    ...(value as List).asMap().entries.map((entry) {
                      final item = Map<String, dynamic>.from(
                        entry.value as Map,
                      );
                      return Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          children: item.entries.map((e) {
                            return Expanded(
                              child: Text(
                                '${e.key}: ${e.value}',
                                style: const TextStyle(fontSize: 12),
                              ),
                            );
                          }).toList(),
                        ),
                      );
                    }).toList(),
                ],
              ),
            ),
          ),
        );
      }
    });

    return Column(children: rows);
  }

  String _formatLabel(String label) {
    return label
        .replaceAll('_', ' ')
        .split(' ')
        .map(
          (word) => word.isNotEmpty
              ? '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}'
              : '',
        )
        .join(' ');
  }

  String _formatCurrency(dynamic value) {
    if (value is! num) return value.toString();
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(value.toDouble());
  }
}
