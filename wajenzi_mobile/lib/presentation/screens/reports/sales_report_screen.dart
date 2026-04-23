import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/empty_state_widget.dart';

final _salesReportDataProvider = FutureProvider.family
    .autoDispose<Map<String, dynamic>, Map<String, String>>((
      ref,
      params,
    ) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/reports/sales-report',
        queryParameters: params,
      );
      return response.data is Map<String, dynamic>
          ? Map<String, dynamic>.from(response.data as Map)
          : {};
    });

String _salesTr(
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

class SalesReportScreen extends ConsumerStatefulWidget {
  const SalesReportScreen({super.key});

  @override
  ConsumerState<SalesReportScreen> createState() => _SalesReportScreenState();
}

class _SalesReportScreenState extends ConsumerState<SalesReportScreen> {
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
    final language = ref.watch(currentLanguageProvider);
    final params = {
      'start_date': DateFormat('yyyy-MM-dd').format(_dateRange!.start),
      'end_date': DateFormat('yyyy-MM-dd').format(_dateRange!.end),
    };
    final dataAsync = ref.watch(_salesReportDataProvider(params));

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.go('/reports'),
        ),
        title: Text(
          _salesTr(
            language,
            en: 'Sales Report',
            sw: 'Ripoti ya Mauzo',
            fr: 'Rapport des ventes',
            ar: 'تقرير المبيعات',
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today),
            tooltip: _salesTr(
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
            tooltip: _salesTr(
              language,
              en: 'Refresh',
              sw: 'Onyesha Upya',
              fr: 'Actualiser',
              ar: 'تحديث',
            ),
            onPressed: () => ref.invalidate(_salesReportDataProvider(params)),
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
                message: _salesTr(
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
                    Text(_salesTr(language, en: 'Error', sw: 'Hitilafu', fr: 'Erreur', ar: 'خطأ')),
                    const SizedBox(height: 8),
                    ElevatedButton.icon(
                      onPressed: () =>
                          ref.invalidate(_salesReportDataProvider(params)),
                      icon: const Icon(Icons.refresh),
                      label: Text(
                        _salesTr(
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
                      message: _salesTr(
                        language,
                        en: 'No data available',
                        sw: 'Hakuna data',
                        fr: 'Aucune donnée disponible',
                        ar: 'لا توجد بيانات متاحة',
                      ),
                      icon: Icons.bar_chart,
                    )
                  : _SalesReportContent(data: data, language: language),
            ),
          ),
        ],
      ),
    );
  }
}

class _SalesReportContent extends StatelessWidget {
  final Map<String, dynamic> data;
  final AppLanguage language;

  const _SalesReportContent({required this.data, required this.language});

  @override
  Widget build(BuildContext context) {
    final sales = data['sales'] as List? ?? [];
    final totalTurnover = data['total_turnover'] ?? data['total_amount'] ?? 0;
    final totalTax = data['total_tax'] ?? 0;
    final totalNet = data['total_net'] ?? 0;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _StatsGrid(
            totalTurnover: totalTurnover,
            totalTax: totalTax,
            totalNet: totalNet,
            language: language,
          ),
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _salesTr(
                      language,
                      en: 'Sales Details',
                      sw: 'Maelezo ya Mauzo',
                      fr: 'Détails des ventes',
                      ar: 'تفاصيل المبيعات',
                    ),
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const Divider(),
                  if (sales.isEmpty)
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Center(
                        child: Text(
                          _salesTr(
                            language,
                            en: 'No sales found',
                            sw: 'Hakuna mauzo',
                            fr: 'Aucune vente trouvée',
                            ar: 'لم يتم العثور على مبيعات',
                          ),
                        ),
                      ),
                    )
                  else
                    ...sales
                        .take(20)
                        .map(
                          (item) => _SaleItem(
                            data: Map<String, dynamic>.from(item as Map),
                            language: language,
                          ),
                        ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  final dynamic totalTurnover;
  final dynamic totalTax;
  final dynamic totalNet;
  final AppLanguage language;

  const _StatsGrid({
    required this.totalTurnover,
    required this.totalTax,
    required this.totalNet,
    required this.language,
  });

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 3,
      crossAxisSpacing: 8,
      mainAxisSpacing: 8,
      childAspectRatio: 1.1,
      children: [
        _StatCard(
          label: _salesTr(language, en: 'Turnover', sw: 'Jumla ya Mauzo', fr: 'Chiffre d’affaires', ar: 'إجمالي المبيعات'),
          value: _formatCurrency(totalTurnover),
          color: Colors.blue,
          icon: Icons.trending_up,
        ),
        _StatCard(
          label: _salesTr(language, en: 'Tax (VAT)', sw: 'Kodi (VAT)', fr: 'Taxe (TVA)', ar: 'الضريبة (VAT)'),
          value: _formatCurrency(totalTax),
          color: Colors.orange,
          icon: Icons.receipt,
        ),
        _StatCard(
          label: _salesTr(language, en: 'NET', sw: 'Net', fr: 'Net', ar: 'الصافي'),
          value: _formatCurrency(totalNet),
          color: Colors.green,
          icon: Icons.account_balance_wallet,
        ),
      ],
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

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final IconData icon;

  const _StatCard({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: color,
            ),
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          Text(
            label,
            style: TextStyle(fontSize: 10, color: Colors.grey[600]),
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

class _SaleItem extends StatelessWidget {
  final Map<String, dynamic> data;
  final AppLanguage language;

  const _SaleItem({required this.data, required this.language});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey[200]!)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.blue.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(Icons.receipt_long, color: Colors.blue[700], size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  data['date']?.toString() ?? '-',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                if (data['invoice'] != null || data['efd_name'] != null)
                  Text(
                    data['invoice']?.toString() ??
                        data['efd_name']?.toString() ??
                        '-',
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                _formatCurrency(
                  data['turnover'] ?? data['total'] ?? data['amount'] ?? 0,
                ),
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
              Text(
                _formatCurrency(data['tax'] ?? data['vat'] ?? 0),
                style: TextStyle(fontSize: 12, color: Colors.green[700]),
              ),
            ],
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
