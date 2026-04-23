import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/empty_state_widget.dart';

final _genericReportDataProvider =
    FutureProvider.family<Map<String, dynamic>, String>((ref, cacheKey) async {
      final params = cacheKey.split('|');
      final endpoint = params.isNotEmpty ? params[0] : '';
      final queryParams = <String, String>{};
      for (var i = 1; i < params.length; i++) {
        final parts = params[i].split('=');
        if (parts.length == 2) {
          queryParams[parts[0]] = parts[1];
        }
      }
      debugPrint('=== PROVIDER: Fetching $endpoint with params $queryParams');

      final api = ref.watch(apiClientProvider);
      final response = await api.get(endpoint, queryParameters: queryParams);
      debugPrint('=== PROVIDER: Response status: ${response.statusCode}');

      final responseData = response.data;
      if (responseData is Map) {
        final data = responseData['data'];
        if (data is Map) {
          debugPrint(
            '=== PROVIDER: Returning data with keys: ${data.keys.toList()}',
          );
          return Map<String, dynamic>.from(data);
        }
        return Map<String, dynamic>.from(responseData);
      }
      return <String, dynamic>{};
    });

String _reportTr(
  AppLanguage language, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) {
  return switch (language) {
    AppLanguage.swahili => sw ?? en,
    AppLanguage.french => fr ?? en,
    AppLanguage.arabic => ar ?? en,
    AppLanguage.english => en,
  };
}

String _buildCacheKey(
  String endpoint, {
  int? year,
  DateTimeRange? dateRange,
  bool isStatutoryReport = false,
}) {
  final parts = <String>[endpoint];
  if (year != null) {
    parts.add('year=$year');
  } else if (dateRange != null && !isStatutoryReport) {
    parts.add('start=${DateFormat('yyyy-MM-dd').format(dateRange.start)}');
    parts.add('end=${DateFormat('yyyy-MM-dd').format(dateRange.end)}');
  }
  return parts.join('|');
}

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
  int? _selectedYear;

  @override
  void initState() {
    super.initState();
    _selectedYear = DateTime.now().year;
    _dateRange = DateTimeRange(
      start: DateTime(_selectedYear!),
      end: DateTime(_selectedYear!, 12, 31),
    );
  }

  String _buildParams() {
    final isStatutory =
        widget.apiEndpoint == '/reports/statutory-category-report' ||
        widget.apiEndpoint == '/reports/statutory-payment-report' ||
        widget.apiEndpoint == '/reports/statutory-schedules-report';
    return _buildCacheKey(
      widget.apiEndpoint,
      year: isStatutory ? _selectedYear : null,
      dateRange: isStatutory ? null : _dateRange,
      isStatutoryReport: isStatutory,
    );
  }

  Future<void> _showYearPicker(BuildContext context) async {
    final language = ref.read(currentLanguageProvider);
    final currentYear = DateTime.now().year;
    final years = List.generate(10, (i) => currentYear - i);
    final picked = await showDialog<int>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(
          _reportTr(
            language,
            en: 'Select Year',
            sw: 'Chagua Mwaka',
            fr: 'Choisir l\'année',
            ar: 'اختر السنة',
          ),
        ),
        content: SizedBox(
          width: 200,
          height: 300,
          child: ListView.builder(
            itemCount: years.length,
            itemBuilder: (context, index) => ListTile(
              title: Text('${years[index]}'),
              selected: years[index] == _selectedYear,
              onTap: () => Navigator.pop(context, years[index]),
            ),
          ),
        ),
      ),
    );
    if (picked != null) {
      setState(() {
        _selectedYear = picked;
        _dateRange = DateTimeRange(
          start: DateTime(picked),
          end: DateTime(picked, 12, 31),
        );
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final dataAsync = ref.watch(_genericReportDataProvider(_buildParams()));

    debugPrint('=== ASYNC STATE ===');
    debugPrint('isLoading: ${dataAsync.isLoading}');
    debugPrint('isError: ${dataAsync.hasError}');
    if (dataAsync.hasValue) {
      debugPrint('hasValue: true');
      debugPrint('value keys: ${dataAsync.value?.keys.toList()}');
    }

    String title = _reportTr(
      language,
      en: widget.title,
      sw: widget.titleSw,
    );
    if (dataAsync.hasValue && dataAsync.value != null) {
      final data = dataAsync.value!;
      if (data.containsKey('sub_category_name') &&
          data['sub_category_name'] != null) {
        title = data['sub_category_name'].toString();
      } else if (data.containsKey('category_name') &&
          data['category_name'] != null) {
        title = data['category_name'].toString();
      } else if (data.containsKey('name') && data['name'] != null) {
        title = data['name'].toString();
      }
    }

    return Scaffold(
      appBar: AppBar(
        leading:
            widget.apiEndpoint == '/reports/statutory-category-report' ||
                widget.apiEndpoint == '/reports/statutory-payment-report' ||
                widget.apiEndpoint == '/reports/statutory-schedules-report'
            ? Consumer(
                builder: (context, ref, _) {
                  final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
                  return IconButton(
                    icon: const Icon(Icons.menu_rounded),
                    onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
                  );
                },
              )
            : IconButton(
                icon: const Icon(Icons.arrow_back_rounded),
                onPressed: () => context.go('/reports'),
              ),
        title: Text(title),
        actions: [
          if (widget.apiEndpoint != '/reports/statutory-category-report' &&
              widget.apiEndpoint != '/reports/statutory-payment-report' &&
              widget.apiEndpoint != '/reports/statutory-schedules-report')
            IconButton(
              icon: const Icon(Icons.calendar_today),
              tooltip: _reportTr(
                language,
                en: 'Select Date',
                sw: 'Chagua Tarehe',
                fr: 'Choisir la date',
                ar: 'اختر التاريخ',
              ),
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
            tooltip: _reportTr(
              language,
              en: 'Refresh',
              sw: 'Onyesha Upya',
              fr: 'Actualiser',
              ar: 'تحديث',
            ),
            onPressed: () =>
                ref.invalidate(_genericReportDataProvider(_buildParams())),
          ),
        ],
      ),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: Theme.of(context).colorScheme.primaryContainer,
            child:
                widget.apiEndpoint == '/reports/statutory-category-report' ||
                    widget.apiEndpoint == '/reports/statutory-payment-report' ||
                    widget.apiEndpoint == '/reports/statutory-schedules-report'
                ? Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.calendar_today,
                        size: 18,
                        color: Theme.of(context).colorScheme.onPrimaryContainer,
                      ),
                      const SizedBox(width: 8),
                      GestureDetector(
                        onTap: () => _showYearPicker(context),
                        child: Row(
                          children: [
                            Text(
                              '${_reportTr(language, en: 'Year', sw: 'Mwaka', fr: 'Année', ar: 'السنة')}: $_selectedYear',
                              style: TextStyle(
                                fontWeight: FontWeight.w600,
                                color: Theme.of(
                                  context,
                                ).colorScheme.onPrimaryContainer,
                              ),
                            ),
                            const SizedBox(width: 4),
                            Icon(
                              Icons.arrow_drop_down,
                              color: Theme.of(
                                context,
                              ).colorScheme.onPrimaryContainer,
                            ),
                          ],
                        ),
                      ),
                    ],
                  )
                : _dateRange != null
                ? Row(
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
                          color: Theme.of(
                            context,
                          ).colorScheme.onPrimaryContainer,
                        ),
                      ),
                    ],
                  )
                : const SizedBox.shrink(),
          ),
          Expanded(
            child: dataAsync.when(
              loading: () => LoadingWidget(
                message: _reportTr(
                  language,
                  en: 'Loading data...',
                  sw: 'Inapakia data...',
                  fr: 'Chargement des données...',
                  ar: 'جارٍ تحميل البيانات...',
                ),
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
                    Text(
                      _reportTr(
                        language,
                        en: 'Error',
                        sw: 'Hitilafu',
                        fr: 'Erreur',
                        ar: 'خطأ',
                      ),
                    ),
                    const SizedBox(height: 8),
                    ElevatedButton.icon(
                      onPressed: () => ref.invalidate(
                        _genericReportDataProvider(_buildParams()),
                      ),
                      icon: const Icon(Icons.refresh),
                      label: Text(
                        _reportTr(
                          language,
                          en: 'Retry',
                          sw: 'Jaribu tena',
                          fr: 'Réessayer',
                          ar: 'أعد المحاولة',
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              data: (data) => data.isEmpty
                  ? EmptyStateWidget(
                      message: _reportTr(
                        language,
                        en: 'No data available',
                        sw: 'Hakuna data',
                        fr: 'Aucune donnée disponible',
                        ar: 'لا توجد بيانات متاحة',
                      ),
                      icon: Icons.bar_chart,
                    )
                  : _ReportContent(data: data, language: language),
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportContent extends StatelessWidget {
  final Map<String, dynamic> data;
  final AppLanguage language;

  const _ReportContent({required this.data, required this.language});

  @override
  Widget build(BuildContext context) {
    debugPrint('=== REPORT DATA ===');
    debugPrint('Keys: ${data.keys.toList()}');
    debugPrint('Data: $data');

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
            language: language,
          ),
        );
      } else if (value is Map) {
        listItems.add(
          _DataMapSection(
            title: _formatLabel(key),
            data: Map<String, dynamic>.from(value as Map),
            language: language,
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
  final AppLanguage language;

  const _DataSection({
    required this.title,
    required this.items,
    required this.language,
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
                  child: Text(
                    _reportTr(
                      language,
                      en: 'No data',
                      sw: 'Hakuna data',
                      fr: 'Aucune donnée',
                      ar: 'لا توجد بيانات',
                    ),
                  ),
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
                  _reportTr(
                    language,
                    en: '+${items.length - 10} more',
                    sw: '+${items.length - 10} zaidi',
                    fr: '+${items.length - 10} de plus',
                    ar: '+${items.length - 10} المزيد',
                  ),
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
  final AppLanguage language;

  const _DataMapSection({
    required this.title,
    required this.data,
    required this.language,
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
