import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

class _Filters {
  final String granularity;
  final int? projectId;
  const _Filters({this.granularity = 'monthly', this.projectId});

  _Filters copyWith({String? granularity, int? projectId, bool clearProject = false}) =>
      _Filters(
        granularity: granularity ?? this.granularity,
        projectId: clearProject ? null : (projectId ?? this.projectId),
      );
}

final _filtersProvider =
    StateProvider.autoDispose<_Filters>((ref) => const _Filters());

final _expenditureDashboardProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final filters = ref.watch(_filtersProvider);
  final params = <String, dynamic>{
    'granularity': filters.granularity,
  };
  if (filters.projectId != null) {
    params['project_id'] = filters.projectId;
  }
  final response = await api.get(
    '/finance/expenditure-dashboard',
    queryParameters: params,
  );
  return (response.data['data'] as Map?)?.cast<String, dynamic>() ?? const {};
});

/// Expenditure dashboard — outflow-focused view (project expenses, top sites,
/// trend, statutory + VAT payment status).
class ExpenditureDashboardScreen extends ConsumerWidget {
  const ExpenditureDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final asyncDashboard = ref.watch(_expenditureDashboardProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili ? 'Dashibodi ya Matumizi' : 'Expenditure Dashboard',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_expenditureDashboardProvider);
          await ref.read(_expenditureDashboardProvider.future);
        },
        child: asyncDashboard.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_expenditureDashboardProvider),
          ),
          data: (data) => _Body(
            data: data,
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
          ),
        ),
      ),
    );
  }
}

class _Body extends ConsumerWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;
  final bool isDarkMode;

  const _Body({
    required this.data,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filters = ref.watch(_filtersProvider);
    final totals =
        (data['totals'] as Map?)?.cast<String, dynamic>() ?? const {};
    final byCategory = ((totals['by_category'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .toList();
    final perSite = ((data['per_site'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .toList();
    final series = ((data['series'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .toList();
    final statutory =
        (data['statutory'] as Map?)?.cast<String, dynamic>() ?? const {};
    final vat = (data['vat'] as Map?)?.cast<String, dynamic>() ?? const {};
    final projects = ((data['projects'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .toList();
    final grand = (totals['grand_total'] as num?)?.toDouble() ?? 0;
    final filtersInfo = (data['filters'] as Map?)?.cast<String, dynamic>();

    return ListView(
      padding: const EdgeInsets.all(16),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        // Filter strip — granularity + project.
        _FilterStrip(
          filters: filters,
          projects: projects,
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
          onGranularityChange: (g) {
            ref.read(_filtersProvider.notifier).state =
                filters.copyWith(granularity: g);
          },
          onProjectChange: (id) {
            ref.read(_filtersProvider.notifier).state =
                id == null
                    ? filters.copyWith(clearProject: true)
                    : filters.copyWith(projectId: id);
          },
        ),
        const SizedBox(height: 12),
        // Headline total + date window summary.
        _SectionCard(
          isDarkMode: isDarkMode,
          title: isSwahili ? 'Jumla ya Matumizi' : 'Total Expenditure',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _money(grand),
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: AppColors.error,
                ),
              ),
              if (filtersInfo != null) ...[
                const SizedBox(height: 4),
                Text(
                  '${filtersInfo['start_date'] ?? '—'} → '
                  '${filtersInfo['end_date'] ?? '—'}',
                  style: TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
              const SizedBox(height: 12),
              if (byCategory.isEmpty)
                _EmptyHint(label: isSwahili ? 'Hakuna data' : 'No data')
              else
                ...byCategory.map(
                  (c) => _AmountRow(
                    label: isSwahili
                        ? _swahiliCategory(c['name']?.toString() ?? '')
                        : c['name']?.toString() ?? '',
                    amount: _money(c['total']),
                    color: _categoryColor(c['name']?.toString() ?? ''),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        // Trend chart.
        if (series.isNotEmpty)
          _SectionCard(
            isDarkMode: isDarkMode,
            title: isSwahili ? 'Mwenendo' : 'Trend',
            child: _SeriesChart(series: series, isDarkMode: isDarkMode),
          ),
        const SizedBox(height: 12),
        // Top sites by spend.
        _SectionCard(
          isDarkMode: isDarkMode,
          title: isSwahili ? 'Tovuti Bora' : 'Top Sites',
          child: perSite.isEmpty
              ? _EmptyHint(label: isSwahili ? 'Hakuna tovuti' : 'No sites')
              : Column(
                  children: perSite
                      .map((row) => _SiteRow(row: row, isSwahili: isSwahili))
                      .toList(),
                ),
        ),
        const SizedBox(height: 12),
        // Statutory + VAT status footer.
        Row(
          children: [
            Expanded(
              child: _MiniStatusCard(
                title: isSwahili ? 'Statutory' : 'Statutory',
                pending: statutory['pending'] ?? 0,
                overdue: statutory['overdue'] ?? 0,
                paidYtd: (statutory['paid_ytd'] as num?)?.toDouble() ?? 0,
                isSwahili: isSwahili,
                color: AppColors.warning,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _MiniStatusCard(
                title: 'VAT',
                pending: vat['pending'] ?? 0,
                overdue: 0,
                paidYtd: (vat['paid_ytd'] as num?)?.toDouble() ?? 0,
                isSwahili: isSwahili,
                color: AppColors.info,
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  String _swahiliCategory(String name) {
    switch (name) {
      case 'Material':
        return 'Vifaa';
      case 'Labour Charge':
        return 'Gharama za Kazi';
      case 'Overhead Expense':
        return 'Matumizi ya Ziada';
      default:
        return name;
    }
  }

  Color _categoryColor(String name) {
    switch (name) {
      case 'Material':
        return AppColors.info;
      case 'Labour Charge':
        return AppColors.secondary;
      case 'Overhead Expense':
        return AppColors.warning;
      default:
        return AppColors.primary;
    }
  }
}

class _FilterStrip extends StatelessWidget {
  final _Filters filters;
  final List<Map<String, dynamic>> projects;
  final bool isSwahili;
  final bool isDarkMode;
  final ValueChanged<String> onGranularityChange;
  final ValueChanged<int?> onProjectChange;

  const _FilterStrip({
    required this.filters,
    required this.projects,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onGranularityChange,
    required this.onProjectChange,
  });

  @override
  Widget build(BuildContext context) {
    final options = <(String, String)>[
      ('daily', isSwahili ? 'Kila siku' : 'Daily'),
      ('weekly', isSwahili ? 'Kila wiki' : 'Weekly'),
      ('monthly', isSwahili ? 'Kila mwezi' : 'Monthly'),
    ];

    return Container(
      padding: const EdgeInsets.all(12),
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
          // Granularity chips.
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                for (final opt in options) ...[
                  _Chip(
                    label: opt.$2,
                    selected: filters.granularity == opt.$1,
                    onTap: () => onGranularityChange(opt.$1),
                  ),
                  const SizedBox(width: 6),
                ],
              ],
            ),
          ),
          if (projects.isNotEmpty) ...[
            const SizedBox(height: 8),
            DropdownButtonFormField<int?>(
              initialValue: filters.projectId,
              isExpanded: true,
              decoration: InputDecoration(
                isDense: true,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                hintText: isSwahili ? 'Chagua mradi' : 'Filter by project',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              items: [
                DropdownMenuItem<int?>(
                  value: null,
                  child: Text(isSwahili ? 'Miradi yote' : 'All projects'),
                ),
                ...projects.map((p) {
                  return DropdownMenuItem<int?>(
                    value: (p['id'] as num?)?.toInt(),
                    child: Text(
                      p['name']?.toString() ?? '—',
                      overflow: TextOverflow.ellipsis,
                    ),
                  );
                }),
              ],
              onChanged: onProjectChange,
            ),
          ],
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;

  const _Chip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? AppColors.primary : Colors.grey[100],
          border: Border.all(
            color: selected ? AppColors.primary : Colors.transparent,
          ),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: selected ? Colors.white : Colors.grey[700],
          ),
        ),
      ),
    );
  }
}

class _SeriesChart extends StatelessWidget {
  final List<Map<String, dynamic>> series;
  final bool isDarkMode;

  const _SeriesChart({required this.series, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final maxTotal = series.fold<double>(0, (m, row) {
      final v = (row['total'] as num?)?.toDouble() ?? 0;
      return v > m ? v : m;
    });

    return SizedBox(
      height: 160,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          for (final row in series)
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Container(
                    width: 12,
                    height: _heightFor(row['total'], maxTotal, maxBar: 120),
                    decoration: BoxDecoration(
                      color: AppColors.error,
                      borderRadius: const BorderRadius.vertical(
                        top: Radius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 4),
                  RotatedBox(
                    quarterTurns: series.length > 8 ? 3 : 0,
                    child: Text(
                      row['label']?.toString() ?? '',
                      style: TextStyle(
                        fontSize: 9,
                        color:
                            isDarkMode ? Colors.white60 : Colors.black54,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  double _heightFor(dynamic v, double max, {double maxBar = 120}) {
    if (max <= 0) return 0;
    final value = (v as num?)?.toDouble() ?? 0;
    return (value / max).clamp(0, 1).toDouble() * maxBar;
  }
}

class _SiteRow extends StatelessWidget {
  final Map<String, dynamic> row;
  final bool isSwahili;

  const _SiteRow({required this.row, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  row['project_name']?.toString() ?? '—',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Text(
                _money(row['total']),
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: AppColors.error,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Wrap(
            spacing: 12,
            runSpacing: 4,
            children: [
              _PillText(
                label: isSwahili ? 'Vifaa' : 'Material',
                value: _money(row['material']),
                color: AppColors.info,
              ),
              _PillText(
                label: isSwahili ? 'Kazi' : 'Labour',
                value: _money(row['labour']),
                color: AppColors.secondary,
              ),
              _PillText(
                label: isSwahili ? 'Ziada' : 'Overhead',
                value: _money(row['overhead']),
                color: AppColors.warning,
              ),
            ],
          ),
          const SizedBox(height: 8),
          const Divider(height: 1),
        ],
      ),
    );
  }
}

class _PillText extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _PillText({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(width: 6, height: 6, decoration: BoxDecoration(
          color: color, shape: BoxShape.circle,
        )),
        const SizedBox(width: 4),
        Text(
          '$label: ',
          style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
        ),
        Text(
          value,
          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
        ),
      ],
    );
  }
}

class _MiniStatusCard extends StatelessWidget {
  final String title;
  final dynamic pending;
  final dynamic overdue;
  final double paidYtd;
  final bool isSwahili;
  final Color color;

  const _MiniStatusCard({
    required this.title,
    required this.pending,
    required this.overdue,
    required this.paidYtd,
    required this.isSwahili,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        border: Border.all(color: color.withValues(alpha: 0.2)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _money(paidYtd),
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: color,
            ),
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 4),
          Text(
            isSwahili ? 'Iliyolipwa (YTD)' : 'Paid YTD',
            style: TextStyle(fontSize: 10, color: AppColors.textSecondary),
          ),
          const Divider(height: 16),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '$pending',
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Text(
                      isSwahili ? 'Inasubiri' : 'Pending',
                      style: TextStyle(
                        fontSize: 10,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              if ((overdue is num ? overdue.toInt() : 0) > 0)
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '$overdue',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: AppColors.error,
                        ),
                      ),
                      Text(
                        isSwahili ? 'Imepita' : 'Overdue',
                        style: TextStyle(
                          fontSize: 10,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ),
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
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 8),
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

class _EmptyHint extends StatelessWidget {
  final String label;
  const _EmptyHint({required this.label});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Center(
        child: Text(
          label,
          style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
        ),
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
                ? 'Imeshindwa kupakia dashibodi'
                : 'Failed to load dashboard',
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
  final value =
      (v is num) ? v.toDouble() : double.tryParse(v?.toString() ?? '') ?? 0;
  return NumberFormat.currency(
    locale: 'en_TZ',
    symbol: 'TZS ',
    decimalDigits: 0,
  ).format(value);
}
