import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'loading_widget.dart';
import 'empty_state_widget.dart';

class ReportScreen extends ConsumerStatefulWidget {
  final String title;
  final String titleSw;
  final String? titleFr;
  final String? titleAr;
  final String apiEndpoint;
  final List<Map<String, String>>? filterOptions;
  final Widget Function(Map<String, dynamic> data, bool isSwahili)?
  customBuilder;
  final String Function(bool isSwahili)? customEmptyMessage;

  const ReportScreen({
    super.key,
    required this.title,
    required this.titleSw,
    this.titleFr,
    this.titleAr,
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
    final language = ref.watch(currentLanguageProvider);

    String tr({
      required String en,
      required String sw,
      String? fr,
      String? ar,
    }) {
      return switch (language) {
        AppLanguage.swahili => sw,
        AppLanguage.french => fr ?? en,
        AppLanguage.arabic => ar ?? en,
        AppLanguage.english => en,
      };
    }

    String localizedTitle() {
      return switch (language) {
        AppLanguage.swahili => widget.titleSw,
        AppLanguage.french => widget.titleFr ?? widget.title,
        AppLanguage.arabic => widget.titleAr ?? widget.title,
        AppLanguage.english => widget.title,
      };
    }

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.go('/reports'),
        ),
        title: Text(localizedTitle()),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today),
            tooltip: tr(
              en: 'Select Date',
              sw: 'Chagua Tarehe',
              fr: 'Choisir la date',
              ar: 'اختر التاريخ',
            ),
            onPressed: _selectDateRange,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: tr(
              en: 'Refresh',
              sw: 'Onyesha Upya',
              fr: 'Actualiser',
              ar: 'تحديث',
            ),
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
                    message: tr(
                      en: 'Loading data...',
                      sw: 'Inapakia data...',
                      fr: 'Chargement des données...',
                      ar: 'جارٍ تحميل البيانات...',
                    ),
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
                          tr(
                            en: 'Error loading data',
                            sw: 'Hitilafu wakati wa kupakia',
                            fr: 'Erreur lors du chargement des données',
                            ar: 'حدث خطأ أثناء تحميل البيانات',
                          ),
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 8),
                        Text(_error!, textAlign: TextAlign.center),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: _loadData,
                          icon: const Icon(Icons.refresh),
                          label: Text(
                            tr(
                              en: 'Retry',
                              sw: 'Jaribu tena',
                              fr: 'Réessayer',
                              ar: 'أعد المحاولة',
                            ),
                          ),
                        ),
                      ],
                    ),
                  )
                : _data.isEmpty
                ? EmptyStateWidget(
                    message:
                        widget.customEmptyMessage?.call(
                          language == AppLanguage.swahili,
                        ) ??
                        tr(
                          en: 'No report data available',
                          sw: 'Hakuna data ya ripoti',
                          fr: 'Aucune donnée de rapport disponible',
                          ar: 'لا توجد بيانات تقرير متاحة',
                        ),
                    icon: Icons.bar_chart,
                  )
                : widget.customBuilder?.call(
                        _data,
                        language == AppLanguage.swahili,
                      ) ??
                      _DefaultReportBuilder(
                        data: _data,
                        isSwahili: language == AppLanguage.swahili,
                      ),
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
