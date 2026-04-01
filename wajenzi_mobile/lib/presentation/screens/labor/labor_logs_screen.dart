import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final laborLogsProjectFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);

final laborLogsContractFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);

final laborLogsDateRangeProvider =
    StateProvider.autoDispose<Map<String, String>?>((ref) => null);

final _laborLogsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final projectId = ref.watch(laborLogsProjectFilterProvider);
  final contractId = ref.watch(laborLogsContractFilterProvider);
  final dateRange = ref.watch(laborLogsDateRangeProvider);

  final now = DateTime.now();
  final startDate =
      dateRange?['start'] ??
      DateFormat('yyyy-MM-dd').format(DateTime(now.year, now.month, 1));
  final endDate = dateRange?['end'] ?? DateFormat('yyyy-MM-dd').format(now);

  final response = await api.get(
    '/labor/logs',
    queryParameters: {
      'start_date': startDate,
      'end_date': endDate,
      if (projectId != null) 'project_id': projectId,
      if (contractId != null) 'contract_id': contractId,
    },
  );
  return response.data['data'] as Map<String, dynamic>? ?? const {};
});

final _laborLogsReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/logs/reference-data');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

final _laborLogsDashboardProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/logs/dashboard');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class LaborLogsScreen extends ConsumerStatefulWidget {
  const LaborLogsScreen({super.key});

  @override
  ConsumerState<LaborLogsScreen> createState() => _LaborLogsScreenState();
}

class _LaborLogsScreenState extends ConsumerState<LaborLogsScreen> {
  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final logsAsync = ref.watch(_laborLogsProvider);
    final selectedProject = ref.watch(laborLogsProjectFilterProvider);
    final selectedContract = ref.watch(laborLogsContractFilterProvider);
    final dateRange = ref.watch(laborLogsDateRangeProvider);
    final referenceDataAsync = ref.watch(_laborLogsReferenceProvider);

    final now = DateTime.now();
    final startDate =
        dateRange?['start'] ??
        DateFormat('yyyy-MM-dd').format(DateTime(now.year, now.month, 1));
    final endDate = dateRange?['end'] ?? DateFormat('yyyy-MM-dd').format(now);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Kumbukumbu za Kazi' : 'Work Logs'),
        actions: [
          IconButton(
            icon: const Icon(Icons.dashboard_rounded),
            tooltip: isSwahili ? 'Dashibodi' : 'Dashboard',
            onPressed: () => context.go('/labor-dashboard'),
          ),
          IconButton(
            icon: const Icon(Icons.calendar_today),
            onPressed: () => _selectDateRange(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_laborLogsProvider);
          ref.invalidate(_laborLogsReferenceProvider);
          ref.invalidate(_laborLogsDashboardProvider);
        },
        child: logsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _LogsErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () {
              ref.invalidate(_laborLogsProvider);
              ref.invalidate(_laborLogsReferenceProvider);
              ref.invalidate(_laborLogsDashboardProvider);
            },
          ),
          data: (payload) {
            final logs = (payload['data'] as List? ?? const []).cast<dynamic>();
            final filters =
                payload['filters'] as Map<String, dynamic>? ?? const {};
            final projects =
                referenceDataAsync.valueOrNull?['projects'] as List? ??
                const [];
            final contracts =
                referenceDataAsync.valueOrNull?['contracts'] as List? ??
                const [];

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            isSwahili ? 'Tarehe' : 'Date Range',
                            style: TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white70
                                  : AppColors.textSecondary,
                            ),
                          ),
                          Text(
                            '$startDate - $endDate',
                            style: TextStyle(
                              fontSize: 12,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Mradi' : 'Project',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<int?>(
                        value: selectedProject,
                        isExpanded: true,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF252540)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'Miradi Yote' : 'All Projects',
                            ),
                          ),
                          ...projects.map(
                            (project) => DropdownMenuItem<int?>(
                              value: project['id'] as int?,
                              child: Text(
                                project['project_name'] as String? ?? '-',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          ref
                                  .read(laborLogsProjectFilterProvider.notifier)
                                  .state =
                              value;
                          ref
                                  .read(
                                    laborLogsContractFilterProvider.notifier,
                                  )
                                  .state =
                              null;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Mkataba' : 'Contract',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<int?>(
                        value: selectedContract,
                        isExpanded: true,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF252540)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'Mikataba Yote' : 'All Contracts',
                            ),
                          ),
                          ...contracts.map(
                            (contract) => DropdownMenuItem<int?>(
                              value: contract['id'] as int?,
                              child: Text(
                                '${contract['contract_number']} - ${contract['artisan_name'] ?? '-'}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          ref
                                  .read(
                                    laborLogsContractFilterProvider.notifier,
                                  )
                                  .state =
                              value;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (logs.isEmpty)
                  _SectionCard(
                    isDarkMode: isDarkMode,
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          children: [
                            Icon(
                              Icons.history_outlined,
                              size: 64,
                              color: isDarkMode ? Colors.white30 : Colors.grey,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Hakuna kumbukumbu zilizopatikana'
                                  : 'No logs found',
                              style: TextStyle(
                                fontSize: 16,
                                color: isDarkMode
                                    ? Colors.white54
                                    : Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  )
                else
                  ...logs.map(
                    (item) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _WorkLogCard(
                        item: Map<String, dynamic>.from(item as Map),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      ),
                    ),
                  ),
                const SizedBox(height: 90),
              ],
            );
          },
        ),
      ),
    );
  }

  Future<void> _selectDateRange(BuildContext context, WidgetRef ref) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final now = DateTime.now();
    final initialRange = DateTimeRange(
      start: DateTime(now.year, now.month, 1),
      end: now,
    );

    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: now,
      initialDateRange: initialRange,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF2563EB),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.black,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null) {
      ref.read(laborLogsDateRangeProvider.notifier).state = {
        'start': DateFormat('yyyy-MM-dd').format(picked.start),
        'end': DateFormat('yyyy-MM-dd').format(picked.end),
      };
    }
  }
}

class _SectionCard extends StatelessWidget {
  final bool isDarkMode;
  final Widget child;

  const _SectionCard({required this.isDarkMode, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
      child: child,
    );
  }
}

class _WorkLogCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final bool isDarkMode;

  const _WorkLogCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final contract = item['contract'] as Map<String, dynamic>?;
    final artisan = contract?['artisan'] as Map<String, dynamic>?;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: const Color(0xFF2563EB).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.assignment_outlined,
                  color: Color(0xFF2563EB),
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          item['log_date'] != null
                              ? DateFormat('EEE, MMM d, yyyy').format(
                                  DateTime.parse(item['log_date'] as String),
                                )
                              : '-',
                          style: TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 15,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                        if (item['weather_conditions'] != null)
                          _WeatherBadge(
                            condition: item['weather_conditions'] as String,
                            isDarkMode: isDarkMode,
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    if (contract != null) ...[
                      Row(
                        children: [
                          Icon(
                            Icons.description_outlined,
                            size: 14,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              contract['contract_number'] as String? ?? '-',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: const Color(0xFF2563EB),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                    if (artisan != null) ...[
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(
                            Icons.person_outline,
                            size: 14,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            artisan['name'] as String? ?? '-',
                            style: TextStyle(
                              fontSize: 12,
                              color: isDarkMode
                                  ? Colors.white70
                                  : AppColors.textSecondary,
                            ),
                          ),
                          if (artisan['trade_skill'] != null) ...[
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: const Color(
                                  0xFF0891B2,
                                ).withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                artisan['trade_skill'] as String? ?? '',
                                style: const TextStyle(
                                  fontSize: 10,
                                  color: Color(0xFF0891B2),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF252540) : Colors.grey[50],
              borderRadius: BorderRadius.circular(8),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isSwahili ? 'Kazi Iliyofanywa' : 'Work Done',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  item['work_done'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 13,
                    color: isDarkMode
                        ? Colors.white70
                        : AppColors.textSecondary,
                  ),
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _TableCell(
                  label: isSwahili ? 'Wafanyi kazi' : 'Workers',
                  value: '${item['workers_present'] ?? 0}',
                  icon: Icons.people_outline,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _TableCell(
                  label: isSwahili ? 'Masaa' : 'Hours',
                  value: item['hours_worked'] != null
                      ? '${(item['hours_worked'] as num).toStringAsFixed(1)}h'
                      : '-',
                  icon: Icons.access_time,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _TableCell(
                  label: isSwahili ? 'Maendeleo' : 'Progress',
                  value: item['progress_percentage'] != null
                      ? '${(item['progress_percentage'] as num).toStringAsFixed(0)}%'
                      : '-',
                  icon: Icons.trending_up,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          if ((item['photo_count'] as int? ?? 0) > 0 ||
              (item['materials_count'] as int? ?? 0) > 0) ...[
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if ((item['photo_count'] as int? ?? 0) > 0)
                  _MiniBadge(
                    icon: Icons.photo_library_outlined,
                    value: '${item['photo_count']}',
                    label: isSwahili ? 'Picha' : 'Photos',
                    isDarkMode: isDarkMode,
                  ),
                if ((item['materials_count'] as int? ?? 0) > 0)
                  _MiniBadge(
                    icon: Icons.inventory_2_outlined,
                    value: '${item['materials_count']}',
                    label: isSwahili ? 'Vifaa' : 'Materials',
                    isDarkMode: isDarkMode,
                  ),
              ],
            ),
          ],
          if (item['challenges'] != null &&
              (item['challenges'] as String).isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFDC2626).withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: const Color(0xFFDC2626).withValues(alpha: 0.2),
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(
                    Icons.warning_amber_outlined,
                    color: Color(0xFFDC2626),
                    size: 18,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      item['challenges'] as String? ?? '',
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFFDC2626),
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 12),
          Row(
            children: [
              Icon(
                Icons.person_outline,
                size: 14,
                color: isDarkMode ? Colors.white38 : AppColors.textHint,
              ),
              const SizedBox(width: 4),
              Text(
                '${isSwahili ? 'Alirekodi' : 'Logged by'}: ${(item['logger'] as Map?)?['name'] as String? ?? '-'}',
                style: TextStyle(
                  fontSize: 11,
                  color: isDarkMode ? Colors.white38 : AppColors.textHint,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _TableCell extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final bool isDarkMode;

  const _TableCell({
    required this.label,
    required this.value,
    required this.icon,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        children: [
          Icon(
            icon,
            size: 18,
            color: isDarkMode ? Colors.white54 : AppColors.textHint,
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniBadge extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;
  final bool isDarkMode;

  const _MiniBadge({
    required this.icon,
    required this.value,
    required this.label,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: const Color(0xFF2563EB)),
          const SizedBox(width: 4),
          Text(
            '$value $label',
            style: TextStyle(
              fontSize: 11,
              color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;
  final bool isDarkMode;

  const _MiniStat({
    required this.icon,
    required this.value,
    required this.label,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 14,
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
          const SizedBox(width: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _WeatherBadge extends StatelessWidget {
  final String condition;
  final bool isDarkMode;

  const _WeatherBadge({required this.condition, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final icons = {
      'sunny': Icons.wb_sunny_outlined,
      'cloudy': Icons.cloud_outlined,
      'rainy': Icons.water_drop_outlined,
      'stormy': Icons.thunderstorm_outlined,
    };

    final colors = {
      'sunny': const Color(0xFFF59E0B),
      'cloudy': const Color(0xFF6B7280),
      'rainy': const Color(0xFF0891B2),
      'stormy': const Color(0xFFDC2626),
    };

    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: (colors[condition] ?? const Color(0xFF6B7280)).withValues(
          alpha: 0.12,
        ),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Icon(
        icons[condition] ?? Icons.cloud_outlined,
        color: colors[condition] ?? const Color(0xFF6B7280),
        size: 20,
      ),
    );
  }
}

class _LogsErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _LogsErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          '$error',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}
