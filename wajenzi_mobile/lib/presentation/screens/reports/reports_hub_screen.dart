import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
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

class ReportsHubScreen extends ConsumerStatefulWidget {
  const ReportsHubScreen({super.key});

  @override
  ConsumerState<ReportsHubScreen> createState() => _ReportsHubScreenState();
}

class _ReportsHubScreenState extends ConsumerState<ReportsHubScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final menusAsync = ref.watch(_reportsHubMenusProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ripoti' : 'Reports'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_reportsHubMenusProvider),
        child: menusAsync.when(
          loading: () => const LoadingWidget(message: 'Loading reports...'),
          error: (error, _) => _ReportsErrorView(
            message: error.toString(),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_reportsHubMenusProvider),
          ),
          data: (reportsPayload) {
            final reports = _buildReportItems(reportsPayload)
                .where((item) {
                  if (_query.trim().isEmpty) {
                    return true;
                  }

                  final query = _query.toLowerCase();
                  return item.name.toLowerCase().contains(query) ||
                      item.route.toLowerCase().contains(query);
                })
                .toList();

            return CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                    child: _ReportsHeader(
                      isDarkMode: isDarkMode,
                      isSwahili: isSwahili,
                      controller: _searchController,
                      onChanged: (value) => setState(() => _query = value),
                      totalCount: reports.length,
                    ),
                  ),
                ),
                if (reports.isEmpty)
                  SliverFillRemaining(
                    hasScrollBody: false,
                    child: _ReportsEmptyView(
                      isDarkMode: isDarkMode,
                      isSwahili: isSwahili,
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
                          delegate: SliverChildBuilderDelegate(
                            (context, index) {
                              final item = reports[index];
                              return _ReportCard(
                                item: item,
                                isDarkMode: isDarkMode,
                                isSwahili: isSwahili,
                                onTap: () => _openReport(item),
                              );
                            },
                            childCount: reports.length,
                          ),
                          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
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
    final seen = <String>{};
    final items = <_ReportItem>[];

    void addMenuItem(Map<String, dynamic> raw) {
      final name = (raw['name'] ?? '').toString().trim();
      final route = (raw['route'] ?? '').toString().trim();
      final url = raw['url']?.toString();

      if (name.isEmpty) return;

      final mobileDestination = _resolveMobileReportRoute(route, url);
      final supportsMobile =
          mobileDestination != null && mobileDestination != '/reports';

      final reportKey = '${name.toLowerCase()}|${route.toLowerCase()}|$url';
      if (seen.contains(reportKey)) return;
      seen.add(reportKey);

      items.add(
        _ReportItem(
          name: name,
          route: route,
          url: url,
          mobileDestination: mobileDestination,
          supportsMobile: supportsMobile,
        ),
      );
    }

    for (final rawReport in payload.whereType<Map>()) {
      final report = Map<String, dynamic>.from(rawReport);
      if ((report['name'] ?? '').toString().trim().isNotEmpty) {
        addMenuItem(report);
      }
    }

    items.sort((a, b) => a.name.toLowerCase().compareTo(b.name.toLowerCase()));
    return items;
  }

  String? _resolveMobileReportRoute(String route, String? url) {
    final normalizedRoute = route.trim().toLowerCase();
    final path = Uri.tryParse(url ?? '')?.path.toLowerCase() ?? '';

    if (normalizedRoute == 'reports' || normalizedRoute == 'report') {
      return '/reports';
    }

    const directMap = <String, String>{
      'reports_statutory_category_report': '/reports-statutory-category-report',
      'reports/statutory_category_report': '/reports-statutory-category-report',
      'reports_statutory_payment_report': '/reports-statutory-payment-report',
      'reports/statutory_payment_report': '/reports-statutory-payment-report',
      'reports_statutory_schedules_report':
          '/reports-statutory-schedules-report',
      'reports/statutory_schedules_report':
          '/reports-statutory-schedules-report',
      'architect.bonus.report': '/architect-bonus/report',
      'architect_bonus_report': '/architect-bonus/report',
      'architect-bonus/report': '/architect-bonus/report',
    };

    if (directMap.containsKey(normalizedRoute)) {
      return directMap[normalizedRoute];
    }

    if (path == '/reports') {
      return '/reports';
    }
    if (path.contains('/reports/statutory-category-report')) {
      return '/reports-statutory-category-report';
    }
    if (path.contains('/reports/statutory-payment-report')) {
      return '/reports-statutory-payment-report';
    }
    if (path.contains('/reports/statutory-schedules-report')) {
      return '/reports-statutory-schedules-report';
    }
    if (path.contains('/architect-bonus/report')) {
      return '/architect-bonus/report';
    }

    return null;
  }

  Future<void> _openReport(_ReportItem item) async {
    if (item.mobileDestination != null && item.mobileDestination != '/reports') {
      if (!mounted) return;
      context.go(item.mobileDestination!);
      return;
    }

    final opened = await ExternalLauncherService.openMenuUrlInApp(
      item.url,
      fallbackPath: '/reports',
    );
    if (!mounted || opened) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('${item.name} is not available in the mobile app yet.'),
      ),
    );
  }
}

class _ReportsHeader extends StatelessWidget {
  final bool isDarkMode;
  final bool isSwahili;
  final TextEditingController controller;
  final ValueChanged<String> onChanged;
  final int totalCount;

  const _ReportsHeader({
    required this.isDarkMode,
    required this.isSwahili,
    required this.controller,
    required this.onChanged,
    required this.totalCount,
  });

  @override
  Widget build(BuildContext context) {
    final heading = isSwahili ? 'Ripoti zinazohusiana' : 'Related Reports';
    final summary = isSwahili
        ? '$totalCount ripoti zimepatikana'
        : '$totalCount reports available';

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
            hintText: isSwahili ? 'Tafuta ripoti...' : 'Search reports...',
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
  final bool isSwahili;
  final VoidCallback onTap;

  const _ReportCard({
    required this.item,
    required this.isDarkMode,
    required this.isSwahili,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final cardColor = isDarkMode ? const Color(0xFF16213E) : Colors.white;
    final borderColor = isDarkMode
        ? Colors.white.withValues(alpha: 0.08)
        : const Color(0xFFE5EAF0);
    final subLabel = item.supportsMobile
        ? (isSwahili ? 'Fungua ndani ya app' : 'Open in app')
        : (isSwahili ? 'Fungua kwenye web' : 'Open on web');

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
                      ? const Color(0xFF16A085)
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
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ReportsErrorView({
    required this.message,
    required this.isSwahili,
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
          isSwahili ? 'Imeshindikana kupakia ripoti' : 'Failed to load reports',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 8),
        Text(
          message,
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 16),
        Center(
          child: OutlinedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh_rounded),
            label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ),
      ],
    );
  }
}

class _ReportsEmptyView extends StatelessWidget {
  final bool isDarkMode;
  final bool isSwahili;
  final bool hasSearch;

  const _ReportsEmptyView({
    required this.isDarkMode,
    required this.isSwahili,
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
                  ? (isSwahili
                        ? 'Hakuna ripoti inayofanana na utafutaji wako'
                        : 'No reports match your search')
                  : (isSwahili
                        ? 'Hakuna ripoti zinazopatikana'
                        : 'No reports available'),
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
