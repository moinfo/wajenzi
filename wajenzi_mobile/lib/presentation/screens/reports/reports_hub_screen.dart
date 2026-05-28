import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../../presentation/providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';

final _reportsHubMenusProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/reports');
  final payload = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final data = payload['data'] is Map<String, dynamic>
      ? payload['data'] as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['reports'] as List? ?? const [];
});

String _reportsTr(
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

class ReportsHubScreen extends ConsumerStatefulWidget {
  const ReportsHubScreen({super.key});

  @override
  ConsumerState<ReportsHubScreen> createState() => _ReportsHubScreenState();
}

class _ReportsHubScreenState extends ConsumerState<ReportsHubScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _query = '';

  static const List<_SupportedReportDefinition> _supportedReports = [
    _SupportedReportDefinition(
      fallbackName: 'Architect Bonus Report',
      mobileDestination: '/architect-bonus/report',
      routeAliases: {
        'architect.bonus.report',
        'architect_bonus_report',
        'architect-bonus/report',
      },
      pathAliases: {'/architect-bonus/report'},
    ),
    _SupportedReportDefinition(
      fallbackName: 'Statutory Category Report',
      mobileDestination: '/reports-statutory-category',
      routeAliases: {'reports_statutory_category_report'},
      pathAliases: {'/reports/statutory-category-report'},
    ),
    _SupportedReportDefinition(
      fallbackName: 'Statutory Payment Report',
      mobileDestination: '/reports-statutory-payment',
      routeAliases: {'reports_statutory_payment_report'},
      pathAliases: {'/reports/statutory-payment-report'},
    ),
    _SupportedReportDefinition(
      fallbackName: 'Statutory Schedules Report',
      mobileDestination: '/reports-statutory-schedules',
      routeAliases: {'reports_statutory_schedules_report'},
      pathAliases: {'/reports/statutory-schedules-report'},
    ),
  ];

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    final language = ref.watch(currentLanguageProvider);
    final menusAsync = ref.watch(_reportsHubMenusProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(
          _reportsTr(
            language,
            en: 'Reports',
            sw: 'Ripoti',
            fr: 'Rapports',
            ar: 'التقارير',
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: _reportsTr(
              language,
              en: 'Refresh',
              sw: 'Onyesha Upya',
              fr: 'Actualiser',
              ar: 'تحديث',
            ),
            onPressed: () => ref.invalidate(_reportsHubMenusProvider),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_reportsHubMenusProvider),
        child: menusAsync.when(
          loading: () => LoadingWidget(
            message: _reportsTr(
              language,
              en: 'Loading reports...',
              sw: 'Inapakia ripoti...',
              fr: 'Chargement des rapports...',
              ar: 'جارٍ تحميل التقارير...',
            ),
          ),
          error: (error, _) => _ReportsErrorView(
            message: error.toString(),
            language: language,
            onRetry: () => ref.invalidate(_reportsHubMenusProvider),
          ),
          data: (reportsPayload) {
            final reports = _buildReportItems(reportsPayload).where((item) {
              if (_query.trim().isEmpty) {
                return true;
              }

              final query = _query.toLowerCase();
              return item.name.toLowerCase().contains(query) ||
                  item.route.toLowerCase().contains(query);
            }).toList();

            return CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                    child: _ReportsHeader(
                      isDarkMode: isDarkMode,
                      language: language,
                      controller: _searchController,
                      onChanged: (value) => setState(() => _query = value),
                      totalCount: reports.length,
                      subtitleOverride: _reportsTr(
                        language,
                        en: 'Important reports available on mobile',
                        sw: 'Ripoti muhimu zilizo tayari kwa simu',
                        fr: 'Rapports importants disponibles sur mobile',
                        ar: 'تقارير مهمة متاحة على الهاتف',
                      ),
                    ),
                  ),
                ),
                if (reports.isEmpty)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: _ReportsEmptyView(
                      isDarkMode: isDarkMode,
                      language: language,
                      hasSearch: _query.trim().isNotEmpty,
                    ),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 96),
                    sliver: SliverLayoutBuilder(
                      builder: (context, constraints) {
                        final width = constraints.crossAxisExtent;
                        final crossAxisCount = width >= 1200
                            ? 4
                            : width >= 800
                            ? 3
                            : 2;

                        return SliverGrid(
                          delegate: SliverChildBuilderDelegate((
                            context,
                            index,
                          ) {
                            final item = reports[index];
                            return _ReportCard(
                              item: item,
                              isDarkMode: isDarkMode,
                              language: language,
                              onTap: () => _openReport(item),
                            );
                          }, childCount: reports.length),
                          gridDelegate:
                              SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: crossAxisCount,
                                mainAxisSpacing: 14,
                                crossAxisSpacing: 14,
                                childAspectRatio: width >= 800 ? 1.28 : 0.98,
                              ),
                        );
                      },
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }

  List<_ReportItem> _buildReportItems(List<dynamic> payload) {
    final items = <_ReportItem>[];
    final rawReports = payload
        .whereType<Map>()
        .map((raw) => Map<String, dynamic>.from(raw))
        .toList();

    for (final supported in _supportedReports) {
      Map<String, dynamic>? match;
      for (final report in rawReports) {
        final route = (report['route'] ?? '').toString().trim().toLowerCase();
        final url = report['url']?.toString() ?? '';
        final path = Uri.tryParse(url)?.path.toLowerCase() ?? '';
        if (supported.routeAliases.contains(route) ||
            supported.pathAliases.contains(path)) {
          match = report;
          break;
        }
      }

      if (match == null) continue;

      final name = (match['name'] ?? '').toString().trim();
      final route = (match['route'] ?? '').toString().trim();
      final url = match['url']?.toString();
      items.add(
        _ReportItem(
          name: name.isNotEmpty ? name : supported.fallbackName,
          route: route,
          url: url,
          mobileDestination: supported.mobileDestination,
          supportsMobile: true,
        ),
      );
    }

    if (items.isEmpty) {
      for (final supported in _supportedReports) {
        items.add(
          _ReportItem(
            name: supported.fallbackName,
            route: '',
            url: null,
            mobileDestination: supported.mobileDestination,
            supportsMobile: true,
          ),
        );
      }
    }

    return items;
  }

  String? _resolveMobileReportRoute(String route, String? url) {
    final normalizedRoute = route.trim().toLowerCase();
    final path = Uri.tryParse(url ?? '')?.path.toLowerCase() ?? '';

    if (normalizedRoute == 'reports' || normalizedRoute == 'report') {
      return '/reports';
    }

    const directMap = <String, String>{
      'architect.bonus.report': '/architect-bonus/report',
      'architect_bonus_report': '/architect-bonus/report',
      'architect-bonus/report': '/architect-bonus/report',
      'reports_vat_analysis_report': '/reports-vat-analysis',
      'reports/vat_analysis_report': '/reports-vat-analysis',
      'reports_sales_report': '/reports-sales',
      'reports/sales_report': '/reports-sales',
      'reports_vat_payments_report': '/reports-vat-payments',
      'reports/vat_payments_report': '/reports-vat-payments',
      'reports_exempt_analysis_report': '/reports-exempt-analysis',
      'reports/exempt_analysis_report': '/reports-exempt-analysis',
      'reports_purchases_report': '/reports-purchases',
      'reports/purchases_report': '/reports-purchases',
      'reports_attendances_report': '/reports-attendances',
      'reports/attendances_report': '/reports-attendances',
      'reports_daily_attendances_report': '/reports-daily-attendances',
      'reports/daily_attendances_report': '/reports-daily-attendances',
      'reports_purchases_by_supplier_report': '/reports-purchases-by-supplier',
      'reports/purchases_by_supplier_report': '/reports-purchases-by-supplier',
      'reports_deduction_report': '/reports-deduction',
      'reports/deduction_report': '/reports-deduction',
      'reports_allowance_subscriptions_report':
          '/reports-allowance-subscriptions',
      'reports/allowance_subscriptions_report':
          '/reports-allowance-subscriptions',
      'reports_statement_of_comprehensive_income_report':
          '/reports-statement-comprehensive-income',
      'reports_statement_of_financial_position_report':
          '/reports-statement-financial-position',
      'reports_detailed_expenditure_statement_report':
          '/reports-detailed-expenditure',
      'reports_efd_report': '/reports-efd',
      'reports_detailed_efd_report': '/reports-detailed-efd',
      'reports_annually_sales_summary_report': '/reports-annually-sales',
      'reports_annually_purchases_summary_report':
          '/reports-annually-purchases',
      'reports_annually_expenses_summary_report': '/reports-annually-expenses',
      'reports_annually_expense_sub_categories_summary_report':
          '/reports-annually-expense-categories',
      'reports_annually_financial_charges_summary_report':
          '/reports-annually-financial-charges',
      'reports_annually_salaries_summary_report': '/reports-annually-salaries',
      'reports_annually_sdl_summary_report': '/reports-annually-sdl',
      'reports_annually_advance_salary_summary_report':
          '/reports-annually-advance-salary',
      'reports_annually_allowance_summary_report':
          '/reports-annually-allowance',
      'reports_annually_heslb_summary_report': '/reports-annually-heslb',
      'reports_annually_net_salary_summary_report':
          '/reports-annually-net-salary',
      'reports_annually_nhif_summary_report': '/reports-annually-nhif',
      'reports_annually_nssf_summary_report': '/reports-annually-nssf',
      'reports_annually_deduction_report': '/reports-annually-deduction',
      'reports_annually_paye_summary_report': '/reports-annually-paye',
      'reports_annually_wcf_summary_report': '/reports-annually-wcf',
      'reports_expense_categories_report': '/reports-expense-categories',
      'reports_expenses_per_system_report': '/reports-expenses-per-system',
      'reports_gross_summary_report': '/reports-gross',
      'reports_net_report': '/reports-net',
      'reports_nhif_report': '/reports-nhif',
      'reports_nssf_report': '/reports-nssf',
      'reports_paye_report': '/reports-paye',
      'reports_sdl_report': '/reports-sdl',
      'reports_wcf_report': '/reports-wcf',
      'reports_heslb_report': '/reports-heslb',
      'reports_provision_report': '/reports-provision',
      'reports_statutory_category_report': '/reports-statutory-category',
      'reports_statutory_payment_report': '/reports-statutory-payment',
      'reports_statutory_schedules_report': '/reports-statutory-schedules',
      'bank_deposit_report': '/reports-bank-deposit',
      'bank_withdraw_report': '/reports-bank-withdraw',
    };

    if (directMap.containsKey(normalizedRoute)) {
      return directMap[normalizedRoute];
    }

    if (path == '/reports') {
      return '/reports';
    }
    if (path.contains('/vat-analysis-report')) {
      return '/reports-vat-analysis';
    }
    if (path.contains('/sales-report') && !path.contains('daily')) {
      return '/reports-sales';
    }
    if (path.contains('/architect-bonus/report')) {
      return '/architect-bonus/report';
    }
    if (path.contains('/vat-payments-report')) {
      return '/reports-vat-payments';
    }
    if (path.contains('/exempt-analysis-report')) {
      return '/reports-exempt-analysis';
    }
    if (path.contains('/purchases-report') && !path.contains('supplier')) {
      return '/reports-purchases';
    }
    if (path.contains('/purchases-by-supplier-report')) {
      return '/reports-purchases-by-supplier';
    }
    if (path.contains('/attendances-report') && !path.contains('daily')) {
      return '/reports-attendances';
    }
    if (path.contains('/daily-attendances-report')) {
      return '/reports-daily-attendances';
    }
    if (path.contains('/deduction-report') && !path.contains('annually')) {
      return '/reports-deduction';
    }
    if (path.contains('/allowance-subscriptions-report')) {
      return '/reports-allowance-subscriptions';
    }
    if (path.contains('/statement-of-comprehensive-income-report')) {
      return '/reports-statement-comprehensive-income';
    }
    if (path.contains('/statement-of-financial-position-report')) {
      return '/reports-statement-financial-position';
    }
    if (path.contains('/detailed-expenditure-statement-report')) {
      return '/reports-detailed-expenditure';
    }
    if (path.contains('/efd-report') && !path.contains('detailed')) {
      return '/reports-efd';
    }
    if (path.contains('/detailed-efd-report')) {
      return '/reports-detailed-efd';
    }
    if (path.contains('/annually-sales-summary-report')) {
      return '/reports-annually-sales';
    }
    if (path.contains('/annually-purchases-summary-report')) {
      return '/reports-annually-purchases';
    }
    if (path.contains('/annually-expenses-summary-report')) {
      return '/reports-annually-expenses';
    }
    if (path.contains('/annually-expense-sub-categories-summary-report')) {
      return '/reports-annually-expense-categories';
    }
    if (path.contains('/annually-financial-charges-summary-report')) {
      return '/reports-annually-financial-charges';
    }
    if (path.contains('/annually-salaries-summary-report')) {
      return '/reports-annually-salaries';
    }
    if (path.contains('/annually-sdl-summary-report')) {
      return '/reports-annually-sdl';
    }
    if (path.contains('/annually-advance-salary-summary-report')) {
      return '/reports-annually-advance-salary';
    }
    if (path.contains('/annually-allowance-summary-report')) {
      return '/reports-annually-allowance';
    }
    if (path.contains('/annually-heslb-summary-report')) {
      return '/reports-annually-heslb';
    }
    if (path.contains('/annually-net-salary-summary-report')) {
      return '/reports-annually-net-salary';
    }
    if (path.contains('/annually-nhif-summary-report')) {
      return '/reports-annually-nhif';
    }
    if (path.contains('/annually-nssf-summary-report')) {
      return '/reports-annually-nssf';
    }
    if (path.contains('/annually-deduction-report')) {
      return '/reports-annually-deduction';
    }
    if (path.contains('/annually-paye-summary-report')) {
      return '/reports-annually-paye';
    }
    if (path.contains('/annually-wcf-summary-report')) {
      return '/reports-annually-wcf';
    }
    if (path.contains('/expense-categories-report')) {
      return '/reports-expense-categories';
    }
    if (path.contains('/expenses-per-system-report')) {
      return '/reports-expenses-per-system';
    }
    if (path.contains('/gross-summary-report')) {
      return '/reports-gross';
    }
    if (path.contains('/net-report')) {
      return '/reports-net';
    }
    if (path.contains('/nhif-report')) {
      return '/reports-nhif';
    }
    if (path.contains('/nssf-report')) {
      return '/reports-nssf';
    }
    if (path.contains('/paye-report')) {
      return '/reports-paye';
    }
    if (path.contains('/sdl-report')) {
      return '/reports-sdl';
    }
    if (path.contains('/wcf-report')) {
      return '/reports-wcf';
    }
    if (path.contains('/heslb-report')) {
      return '/reports-heslb';
    }
    if (path.contains('/provision-report')) {
      return '/reports-provision';
    }
    if (path.contains('/statutory-category-report')) {
      return '/reports-statutory-category';
    }
    if (path.contains('/statutory-payment-report')) {
      return '/reports-statutory-payment';
    }
    if (path.contains('/statutory-schedules-report')) {
      return '/reports-statutory-schedules';
    }
    if (path.contains('/bank-deposit-report')) {
      return '/reports-bank-deposit';
    }
    if (path.contains('/bank-withdraw-report')) {
      return '/reports-bank-withdraw';
    }

    return null;
  }

  Future<void> _openReport(_ReportItem item) async {
    if (item.mobileDestination != null &&
        item.mobileDestination != '/reports') {
      if (!mounted) return;
      context.go(item.mobileDestination!);
      return;
    }

    final opened = await ExternalLauncherService.openMenuUrlInApp(
      item.url,
      fallbackPath: '/reports',
    );
    if (!mounted || opened) return;

    final language = ref.read(currentLanguageProvider);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          item.supportsMobile
              ? _reportsTr(
                  language,
                  en: 'Not available yet',
                  sw: 'Hakuna huko kwa sasa',
                  fr: 'Pas encore disponible',
                  ar: 'غير متاح بعد',
                )
              : '${item.name} - ${_reportsTr(
                  language,
                  en: 'Not available in mobile app yet',
                  sw: 'Hakuna kwenye app ya simu bado',
                  fr: 'Pas encore disponible dans l’application mobile',
                  ar: 'غير متاح في تطبيق الهاتف بعد',
                )}',
        ),
      ),
    );
  }
}

class _ReportsHeader extends StatelessWidget {
  final bool isDarkMode;
  final AppLanguage language;
  final TextEditingController controller;
  final ValueChanged<String> onChanged;
  final int totalCount;
  final String? subtitleOverride;

  const _ReportsHeader({
    required this.isDarkMode,
    required this.language,
    required this.controller,
    required this.onChanged,
    required this.totalCount,
    this.subtitleOverride,
  });

  @override
  Widget build(BuildContext context) {
    final heading = _reportsTr(
      language,
      en: 'Important Reports',
      sw: 'Ripoti Muhimu',
      fr: 'Rapports importants',
      ar: 'التقارير المهمة',
    );
    final summary =
        subtitleOverride ??
        _reportsTr(
          language,
          en: '$totalCount reports available',
          sw: '$totalCount ripoti zimepatikana',
          fr: '$totalCount rapports disponibles',
          ar: '$totalCount تقريراً متاحاً',
        );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF4461E9), Color(0xFF32CD32)],
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
            ),
            borderRadius: BorderRadius.circular(24),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                heading,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                summary,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        TextField(
          controller: controller,
          onChanged: onChanged,
          textInputAction: TextInputAction.search,
          decoration: InputDecoration(
            hintText: _reportsTr(
              language,
              en: 'Search reports...',
              sw: 'Tafuta ripoti...',
              fr: 'Rechercher des rapports...',
              ar: 'ابحث في التقارير...',
            ),
            prefixIcon: const Icon(Icons.search_rounded),
            filled: true,
            fillColor: isDarkMode
                ? Colors.white.withValues(alpha: 0.06)
                : const Color(0xFFF4F6F8),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(18),
              borderSide: BorderSide.none,
            ),
          ),
        ),
      ],
    );
  }
}

class _ReportCard extends StatelessWidget {
  final _ReportItem item;
  final bool isDarkMode;
  final AppLanguage language;
  final VoidCallback onTap;

  const _ReportCard({
    required this.item,
    required this.isDarkMode,
    required this.language,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final cardColor = isDarkMode ? const Color(0xFF16213E) : Colors.white;
    final borderColor = isDarkMode
        ? Colors.white.withValues(alpha: 0.08)
        : const Color(0xFFE5EAF0);
    final subLabel = item.supportsMobile
        ? _reportsTr(
            language,
            en: 'Open in app',
            sw: 'Fungua ndani ya app',
            fr: 'Ouvrir dans l’application',
            ar: 'افتح داخل التطبيق',
          )
        : _reportsTr(
            language,
            en: 'Open on web',
            sw: 'Fungua kwenye web',
            fr: 'Ouvrir sur le web',
            ar: 'افتح على الويب',
          );

    return Material(
      color: cardColor,
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        borderRadius: BorderRadius.circular(22),
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: borderColor),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: isDarkMode ? 0.18 : 0.04),
                blurRadius: 24,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: const Color(0xFF4461E9).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(
                  Icons.menu_book_rounded,
                  color: Color(0xFF4461E9),
                  size: 26,
                ),
              ),
              const SizedBox(height: 16),
              Expanded(
                child: Text(
                  item.name,
                  maxLines: 4,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 15,
                    height: 1.35,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : const Color(0xFF1F2D3D),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                subLabel,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: item.supportsMobile
                      ? const Color(0xFF122833)
                      : (isDarkMode ? Colors.white70 : const Color(0xFF6B7785)),
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ReportsErrorView extends StatelessWidget {
  final String message;
  final AppLanguage language;
  final VoidCallback onRetry;

  const _ReportsErrorView({
    required this.message,
    required this.language,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(24),
      children: [
        const SizedBox(height: 60),
        const Icon(Icons.error_outline_rounded, size: 64, color: Colors.red),
        const SizedBox(height: 12),
        Text(
          _reportsTr(
            language,
            en: 'Failed to load reports',
            sw: 'Imeshindikana kupakia ripoti',
            fr: 'Échec du chargement des rapports',
            ar: 'فشل تحميل التقارير',
          ),
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Text(message, textAlign: TextAlign.center),
        const SizedBox(height: 16),
        Center(
          child: OutlinedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh_rounded),
            label: Text(
              _reportsTr(
                language,
                en: 'Retry',
                sw: 'Jaribu tena',
                fr: 'Réessayer',
                ar: 'أعد المحاولة',
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _ReportsEmptyView extends StatelessWidget {
  final bool isDarkMode;
  final AppLanguage language;
  final bool hasSearch;

  const _ReportsEmptyView({
    required this.isDarkMode,
    required this.language,
    required this.hasSearch,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.bar_chart_rounded,
              size: 60,
              color: isDarkMode ? Colors.white24 : Colors.black12,
            ),
            const SizedBox(height: 12),
            Text(
              hasSearch
                  ? _reportsTr(
                      language,
                      en: 'No reports match your search',
                      sw: 'Hakuna ripoti inayofanana na utafutaji wako',
                      fr: 'Aucun rapport ne correspond à votre recherche',
                      ar: 'لا توجد تقارير تطابق بحثك',
                    )
                  : _reportsTr(
                      language,
                      en: 'No reports available',
                      sw: 'Hakuna ripoti zinazopatikana',
                      fr: 'Aucun rapport disponible',
                      ar: 'لا توجد تقارير متاحة',
                    ),
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      ),
    );
  }
}

class _ReportItem {
  final String name;
  final String route;
  final String? url;
  final String? mobileDestination;
  final bool supportsMobile;

  const _ReportItem({
    required this.name,
    required this.route,
    required this.url,
    required this.mobileDestination,
    required this.supportsMobile,
  });
}

class _SupportedReportDefinition {
  final String fallbackName;
  final String mobileDestination;
  final Set<String> routeAliases;
  final Set<String> pathAliases;

  const _SupportedReportDefinition({
    required this.fallbackName,
    required this.mobileDestination,
    required this.routeAliases,
    required this.pathAliases,
  });
}
