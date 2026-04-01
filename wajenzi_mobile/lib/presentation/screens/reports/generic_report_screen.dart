import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/empty_state_widget.dart';

final _genericReportDataProvider = FutureProvider.family
    .autoDispose<Map<String, dynamic>, Map<String, String>>((
      ref,
      params,
    ) async {
      final api = ref.watch(apiClientProvider);
      final endpoint = params['endpoint'] ?? '';
      final queryParams = Map<String, String>.from(params)..remove('endpoint');
      final response = await api.get(endpoint, queryParameters: queryParams);
      return response.data is Map<String, dynamic>
          ? Map<String, dynamic>.from(response.data as Map)
          : {};
    });

class GenericReportScreen extends ConsumerStatefulWidget {
  final String title;
  final String titleSw;
  final String apiEndpoint;

  const GenericReportScreen({
    super.key,
    required this.title,
    required this.titleSw,
    required this.apiEndpoint,
  });

  @override
  ConsumerState<GenericReportScreen> createState() =>
      _GenericReportScreenState();
}

class _GenericReportScreenState extends ConsumerState<GenericReportScreen> {
  DateTimeRange? _dateRange;

  @override
  void initState() {
    super.initState();
    _dateRange = DateTimeRange(
      start: DateTime.now().subtract(const Duration(days: 30)),
      end: DateTime.now(),
    );
  }

  Map<String, String> _buildParams() {
    return {
      'endpoint': widget.apiEndpoint,
      'start_date': DateFormat('yyyy-MM-dd').format(_dateRange!.start),
      'end_date': DateFormat('yyyy-MM-dd').format(_dateRange!.end),
    };
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_genericReportDataProvider(_buildParams()));

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
            onPressed: () =>
                ref.invalidate(_genericReportDataProvider(_buildParams())),
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
                      onPressed: () => ref.invalidate(
                        _genericReportDataProvider(_buildParams()),
                      ),
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
                  : _ReportContent(data: data, isSwahili: isSwahili),
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportContent extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _ReportContent({required this.data, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final summaryItems = <Widget>[];
    final listItems = <Widget>[];

    data.forEach((key, value) {
      if (value is num) {
        summaryItems.add(_SummaryCard(label: _formatLabel(key), value: value));
      } else if (value is List && (value as List).isNotEmpty) {
        listItems.add(
          _DataSection(
            title: _formatLabel(key),
            items: value as List,
            isSwahili: isSwahili,
          ),
        );
      } else if (value is Map) {
        listItems.add(
          _DataMapSection(
            title: _formatLabel(key),
            data: Map<String, dynamic>.from(value as Map),
            isSwahili: isSwahili,
          ),
        );
      }
    });

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (summaryItems.isNotEmpty) ...[
            Wrap(
              spacing: 12,
              runSpacing: 12,
              children: summaryItems.take(6).toList(),
            ),
            const SizedBox(height: 16),
          ],
          ...listItems,
        ],
      ),
    );
  }

  String _formatLabel(String label) {
    return label
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .split(' ')
        .map(
          (word) => word.isNotEmpty
              ? '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}'
              : '',
        )
        .join(' ');
  }
}

class _SummaryCard extends StatelessWidget {
  final String label;
  final num value;

  const _SummaryCard({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: (MediaQuery.of(context).size.width - 44) / 2,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.blue.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          Text(
            _formatCurrency(value),
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: Colors.blue,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            textAlign: TextAlign.center,
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

class _DataSection extends StatelessWidget {
  final String title;
  final List items;
  final bool isSwahili;

  const _DataSection({
    required this.title,
    required this.items,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const Divider(),
            if (items.isEmpty)
              Padding(
                padding: const EdgeInsets.all(16),
                child: Center(
                  child: Text(isSwahili ? 'Hakuna data' : 'No data'),
                ),
              )
            else
              ...items.take(10).map((item) {
                if (item is Map) {
                  return _ListItemRow(
                    data: Map<String, dynamic>.from(item as Map),
                  );
                }
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Text(item.toString()),
                );
              }),
            if (items.length > 10)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  isSwahili
                      ? '+${items.length - 10} zaidi'
                      : '+${items.length - 10} more',
                  style: TextStyle(
                    color: Colors.grey[600],
                    fontStyle: FontStyle.italic,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _DataMapSection extends StatelessWidget {
  final String title;
  final Map<String, dynamic> data;
  final bool isSwahili;

  const _DataMapSection({
    required this.title,
    required this.data,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const Divider(),
            ...data.entries.map((entry) {
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      _formatLabel(entry.key),
                      style: TextStyle(color: Colors.grey[600]),
                    ),
                    Flexible(
                      child: Text(
                        entry.value.toString(),
                        style: const TextStyle(fontWeight: FontWeight.w600),
                        textAlign: TextAlign.right,
                      ),
                    ),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  String _formatLabel(String label) {
    return label
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .split(' ')
        .map(
          (word) => word.isNotEmpty
              ? '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}'
              : '',
        )
        .join(' ');
  }
}

class _ListItemRow extends StatelessWidget {
  final Map<String, dynamic> data;

  const _ListItemRow({required this.data});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey[200]!)),
      ),
      child: Row(
        children: data.entries.map((entry) {
          return Expanded(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              child: Text(
                entry.value.toString(),
                style: const TextStyle(fontSize: 13),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}
