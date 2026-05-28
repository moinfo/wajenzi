import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _financeDashboardProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/finance/dashboard');
  return (response.data['data'] as Map?)?.cast<String, dynamic>() ?? const {};
});

/// Finance landing screen — surfaces aggregate KPIs and links to the
/// existing native sub-screens (accounting, expenses, VAT, statutory, etc.).
/// Mirrors the parent `finance` drawer entry on the web app.
class FinanceDashboardScreen extends ConsumerWidget {
  const FinanceDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final asyncDashboard = ref.watch(_financeDashboardProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Fedha' : 'Finance'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_financeDashboardProvider);
          await ref.read(_financeDashboardProvider.future);
        },
        child: asyncDashboard.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_financeDashboardProvider),
          ),
          data: (data) => _FinanceBody(
            data: data,
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
          ),
        ),
      ),
    );
  }
}

class _FinanceBody extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;
  final bool isDarkMode;

  const _FinanceBody({
    required this.data,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final metrics =
        (data['metrics'] as Map?)?.cast<String, dynamic>() ?? const {};
    final trend = ((data['monthly_trend'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .toList();

    return ListView(
      padding: const EdgeInsets.all(16),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        // Top KPI strip — receivable + overdue.
        Row(
          children: [
            Expanded(
              child: _MetricCard(
                label: isSwahili ? 'Madeni' : 'Receivable',
                value: _money(metrics['receivable']),
                subtitle: '${metrics['overdue_count'] ?? 0} '
                    '${isSwahili ? 'zilizopita' : 'overdue'}',
                color: AppColors.error,
                icon: Icons.account_balance_wallet_outlined,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _MetricCard(
                label: isSwahili ? 'Sanduku Dogo' : 'Petty Cash',
                value: _money(metrics['petty_cash_balance']),
                subtitle: '${metrics['pending_imprests'] ?? 0} '
                    '${isSwahili ? 'imprest' : 'pending imprests'}',
                color: AppColors.warning,
                icon: Icons.savings_outlined,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Revenue + collections (MTD/YTD).
        _SectionCard(
          title: isSwahili ? 'Mapato' : 'Revenue',
          isDarkMode: isDarkMode,
          child: Column(
            children: [
              _AmountRow(
                label: isSwahili ? 'Mwezi huu' : 'Month-to-date',
                amount: _money(metrics['revenue_mtd']),
                color: AppColors.success,
              ),
              _AmountRow(
                label: isSwahili ? 'Mwaka huu' : 'Year-to-date',
                amount: _money(metrics['revenue_ytd']),
                color: AppColors.success,
              ),
              const Divider(height: 16),
              _AmountRow(
                label: isSwahili ? 'Makusanyo (MTD)' : 'Collections (MTD)',
                amount: _money(metrics['collections_mtd']),
                color: AppColors.info,
              ),
              _AmountRow(
                label: isSwahili ? 'Makusanyo (YTD)' : 'Collections (YTD)',
                amount: _money(metrics['collections_ytd']),
                color: AppColors.info,
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        _SectionCard(
          title: isSwahili ? 'Matumizi' : 'Expenses',
          isDarkMode: isDarkMode,
          child: Column(
            children: [
              _AmountRow(
                label: isSwahili
                    ? 'Matumizi ya Mradi (MTD)'
                    : 'Project Expenses (MTD)',
                amount: _money(metrics['project_expenses_mtd']),
                color: AppColors.warning,
              ),
              _AmountRow(
                label: isSwahili
                    ? 'Matumizi ya Mradi (YTD)'
                    : 'Project Expenses (YTD)',
                amount: _money(metrics['project_expenses_ytd']),
                color: AppColors.warning,
              ),
              const Divider(height: 16),
              _AmountRow(
                label: isSwahili
                    ? 'Matumizi ya Utawala (MTD)'
                    : 'Admin Expenses (MTD)',
                amount: _money(metrics['admin_expenses_mtd']),
                color: AppColors.error,
              ),
              _AmountRow(
                label: isSwahili
                    ? 'Matumizi ya Utawala (YTD)'
                    : 'Admin Expenses (YTD)',
                amount: _money(metrics['admin_expenses_ytd']),
                color: AppColors.error,
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        _SectionCard(
          title: isSwahili ? 'Kodi (YTD)' : 'Taxes (YTD)',
          isDarkMode: isDarkMode,
          child: Column(
            children: [
              _AmountRow(
                label: isSwahili ? 'VAT Iliyolipwa' : 'VAT Paid',
                amount: _money(metrics['vat_paid_ytd']),
                color: AppColors.info,
              ),
              _AmountRow(
                label: isSwahili ? 'Statutory Iliyolipwa' : 'Statutory Paid',
                amount: _money(metrics['statutory_paid_ytd']),
                color: AppColors.info,
              ),
              _AmountRow(
                label: isSwahili
                    ? 'Statutory Inasubiri'
                    : 'Statutory Pending',
                amount: '${metrics['pending_statutory'] ?? 0}',
                color: AppColors.warning,
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        if (trend.isNotEmpty)
          _SectionCard(
            title:
                isSwahili ? 'Mwenendo wa Miezi 12' : '12-Month Trend',
            isDarkMode: isDarkMode,
            child: _TrendChart(trend: trend, isDarkMode: isDarkMode),
          ),
        const SizedBox(height: 16),
        _SectionCard(
          title: isSwahili ? 'Sehemu za Fedha' : 'Finance Sections',
          isDarkMode: isDarkMode,
          child: _SectionsGrid(isSwahili: isSwahili, isDarkMode: isDarkMode),
        ),
        const SizedBox(height: 24),
      ],
    );
  }
}

class _SectionsGrid extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;

  const _SectionsGrid({required this.isSwahili, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final tiles = <_FinanceTile>[
      _FinanceTile(
        label: isSwahili ? 'Uhasibu' : 'Accounting',
        icon: Icons.account_balance,
        color: AppColors.primary,
        route: '/accounting',
      ),
      _FinanceTile(
        label: isSwahili ? 'Matumizi' : 'Expenses',
        icon: Icons.receipt_long,
        color: AppColors.warning,
        route: '/expenses',
      ),
      _FinanceTile(
        label: isSwahili ? 'Dashibodi ya Matumizi' : 'Expenditure Dashboard',
        icon: Icons.show_chart,
        color: AppColors.error,
        route: '/finance/expenditure-dashboard',
      ),
      _FinanceTile(
        label: isSwahili ? 'Malipo ya VAT' : 'VAT Payments',
        icon: Icons.percent,
        color: AppColors.info,
        route: '/vat-payments',
      ),
      _FinanceTile(
        label: isSwahili ? 'Malipo ya Statutory' : 'Statutory Payments',
        icon: Icons.gavel,
        color: AppColors.success,
        route: '/statutory-payments',
      ),
      _FinanceTile(
        label: isSwahili ? 'Sanduku Dogo' : 'Petty Cash',
        icon: Icons.savings,
        color: AppColors.warning,
        route: '/petty-cash-refill-requests',
      ),
      _FinanceTile(
        label: isSwahili ? 'Imprest' : 'Imprest',
        icon: Icons.payments,
        color: AppColors.secondary,
        route: '/imprest-requests',
      ),
      _FinanceTile(
        label: isSwahili ? 'Viwango vya Ubadilishaji' : 'Exchange Rates',
        icon: Icons.currency_exchange,
        color: AppColors.info,
        route: '/exchange-rates',
      ),
    ];

    return LayoutBuilder(
      builder: (context, c) {
        final cols = c.maxWidth >= 600 ? 4 : 2;
        return GridView.count(
          crossAxisCount: cols,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          childAspectRatio: 1.05,
          children: tiles
              .map((t) => _FinanceTileCard(tile: t, isDarkMode: isDarkMode))
              .toList(),
        );
      },
    );
  }
}

class _FinanceTile {
  final String label;
  final IconData icon;
  final Color color;
  final String route;
  const _FinanceTile({
    required this.label,
    required this.icon,
    required this.color,
    required this.route,
  });
}

class _FinanceTileCard extends StatelessWidget {
  final _FinanceTile tile;
  final bool isDarkMode;

  const _FinanceTileCard({required this.tile, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: () => context.push(tile.route),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: tile.color.withValues(alpha: 0.08),
          border: Border.all(color: tile.color.withValues(alpha: 0.2)),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(tile.icon, color: tile.color, size: 32),
            const SizedBox(height: 8),
            Text(
              tile.label,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

class _TrendChart extends StatelessWidget {
  final List<Map<String, dynamic>> trend;
  final bool isDarkMode;

  const _TrendChart({required this.trend, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    // Compact bar chart — keeps the screen lightweight without pulling
    // in a charting dependency. Bars are scaled to the max value across
    // revenue + expenses so they're directly comparable visually.
    final maxValue = trend.fold<double>(0, (m, row) {
      final r = (row['revenue'] as num?)?.toDouble() ?? 0;
      final e = (row['expenses'] as num?)?.toDouble() ?? 0;
      return [m, r, e].reduce((a, b) => a > b ? a : b);
    });

    return SizedBox(
      height: 180,
      child: Row(
        children: [
          for (final row in trend)
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Expanded(
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        _ChartBar(
                          color: AppColors.success,
                          height:
                              _heightFor(row['revenue'], maxValue, maxBar: 140),
                        ),
                        _ChartBar(
                          color: AppColors.error,
                          height:
                              _heightFor(row['expenses'], maxValue, maxBar: 140),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _shortMonth(row['month']?.toString() ?? ''),
                    style: TextStyle(
                      fontSize: 9,
                      color: isDarkMode ? Colors.white60 : Colors.black54,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  double _heightFor(dynamic v, double max, {double maxBar = 140}) {
    if (max <= 0) return 0;
    final value = (v as num?)?.toDouble() ?? 0;
    return (value / max).clamp(0, 1).toDouble() * maxBar;
  }

  String _shortMonth(String monthYear) {
    // "May 2026" -> "M\n26"
    final parts = monthYear.split(' ');
    if (parts.length != 2) return monthYear;
    return '${parts[0].substring(0, parts[0].length > 3 ? 3 : parts[0].length)}\n${parts[1].substring(parts[1].length - 2)}';
  }
}

class _ChartBar extends StatelessWidget {
  final Color color;
  final double height;

  const _ChartBar({required this.color, required this.height});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 6,
      height: height.isNaN ? 0 : height,
      decoration: BoxDecoration(
        color: color,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(2)),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  final String label;
  final String value;
  final String? subtitle;
  final Color color;
  final IconData icon;

  const _MetricCard({
    required this.label,
    required this.value,
    this.subtitle,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        border: Border.all(color: color.withValues(alpha: 0.18)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: color, size: 18),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w600,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              color: color,
              fontWeight: FontWeight.bold,
            ),
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 4),
            Text(
              subtitle!,
              style: TextStyle(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final bool isDarkMode;
  final Widget child;

  const _SectionCard({
    required this.title,
    required this.isDarkMode,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _AmountRow extends StatelessWidget {
  final String label;
  final String amount;
  final Color color;

  const _AmountRow({
    required this.label,
    required this.amount,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
          ),
          Text(
            amount,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 80),
        Icon(Icons.error_outline, size: 48, color: AppColors.error),
        const SizedBox(height: 12),
        Center(
          child: Text(
            isSwahili
                ? 'Imeshindwa kupakia data ya fedha'
                : 'Failed to load finance data',
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
        ),
        const SizedBox(height: 8),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              error.toString(),
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu Tena' : 'Retry'),
          ),
        ),
      ],
    );
  }
}

String _money(dynamic v) {
  final value = (v is num) ? v.toDouble() : double.tryParse(v?.toString() ?? '') ?? 0;
  final formatter = NumberFormat.currency(
    locale: 'en_TZ',
    symbol: 'TZS ',
    decimalDigits: 0,
  );
  return formatter.format(value);
}
