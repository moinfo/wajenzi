import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _siteDailyReportSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _siteDailyReportStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final _siteDailyReportSiteFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);

final _siteDailyReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final status = ref.watch(_siteDailyReportStatusProvider);
      final siteId = ref.watch(_siteDailyReportSiteFilterProvider);
      final response = await api.get(
        '/site-daily-reports',
        queryParameters: {
          if (status != null && status.isNotEmpty) 'status': status,
          if (siteId != null) 'site_id': siteId.toString(),
        },
      );

      final data = response.data['data'];
      final reports = data is List
          ? data
          : (data is Map<String, dynamic>
                ? (data['data'] as List? ?? const [])
                : const []);

      return {
        'items': reports.cast<Map<String, dynamic>>(),
        'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
      };
    });

final _siteDailyReportSitesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/sites');
      final data = response.data['data'];
      final sites = data is List
          ? data
          : (data is Map<String, dynamic>
                ? (data['sites'] as List? ?? data['data'] as List? ?? const [])
                : const []);
      final validSites = sites
          .cast<Map<String, dynamic>>()
          .where((s) => s['id'] != null && s['id'] != 0)
          .toList();
      final seen = <dynamic>{};
      return validSites.where((s) => seen.add(s['id'])).toList();
    });

final _siteDailyReportDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, reportId) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/site-daily-reports/$reportId');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class SiteDailyReportListScreen extends ConsumerStatefulWidget {
  const SiteDailyReportListScreen({super.key});

  @override
  ConsumerState<SiteDailyReportListScreen> createState() =>
      _SiteDailyReportListScreenState();
}

class _SiteDailyReportListScreenState
    extends ConsumerState<SiteDailyReportListScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final reportsAsync = ref.watch(_siteDailyReportsProvider);
    final sitesAsync = ref.watch(_siteDailyReportSitesProvider);
    final selectedStatus = ref.watch(_siteDailyReportStatusProvider);
    final selectedSite = ref.watch(_siteDailyReportSiteFilterProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref
        .watch(_siteDailyReportSearchProvider)
        .trim()
        .toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili ? 'Ripoti za Kila Siku za Eneo' : 'Site Daily Reports',
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showCreateForm(context, ref, isDarkMode, isSwahili),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza Ripoti' : 'Add Report',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_siteDailyReportsProvider.future),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) =>
                          ref
                                  .read(_siteDailyReportSearchProvider.notifier)
                                  .state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta ripoti...'
                            : 'Search reports...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _siteDailyReportSearchProvider
                                                  .notifier,
                                            )
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    _SiteDailyReportFilterBar(
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                      selectedStatus: selectedStatus,
                      onChanged: (value) =>
                          ref
                                  .read(_siteDailyReportStatusProvider.notifier)
                                  .state =
                              value,
                    ),
                    const SizedBox(height: 8),
                    sitesAsync.when(
                      loading: () => const SizedBox.shrink(),
                      error: (_, __) => const SizedBox.shrink(),
                      data: (sites) {
                        if (sites.isEmpty) return const SizedBox.shrink();
                        return Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: DropdownButton<int?>(
                            value: selectedSite,
                            hint: Text(isSwahili ? 'Eneo lote' : 'All sites'),
                            underline: const SizedBox(),
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF1A2332)
                                : Colors.white,
                            items: [
                              DropdownMenuItem(
                                value: null,
                                child: Text(
                                  isSwahili ? 'Eneo lote' : 'All sites',
                                ),
                              ),
                              ...sites.map(
                                (s) => DropdownMenuItem(
                                  value: s['id'] as int,
                                  child: Text(s['name']?.toString() ?? '-'),
                                ),
                              ),
                            ],
                            onChanged: (value) =>
                                ref
                                        .read(
                                          _siteDailyReportSiteFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    value,
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
            reportsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _SiteDailyReportErrorView(
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_siteDailyReportsProvider),
                ),
              ),
              data: (payload) {
                final reports = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();

                final filteredReports = search.isEmpty
                    ? reports
                    : reports.where((report) {
                        final site = report['site'] as Map<String, dynamic>?;
                        final haystack = [
                          site?['name'] ?? '',
                          report['report_date'] ?? '',
                          (report['prepared_by_user']
                                  as Map<String, dynamic>?)?['name'] ??
                              '',
                          report['next_steps'] ?? '',
                          report['challenges'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (filteredReports.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.assignment_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            reports.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna ripoti za eneo'
                                      : 'No site daily reports found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () =>
                                  ref
                                          .read(
                                            _siteDailyReportSearchProvider
                                                .notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa utafutaji' : 'Clear search',
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final report = filteredReports[index];
                      return _SiteDailyReportCard(
                        report: report,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onView: () => _showSiteDailyReportDetails(
                          context,
                          ref,
                          reportId: _toInt(report['id']),
                          isSwahili: isSwahili,
                          isDarkMode: isDarkMode,
                        ),
                        onEdit: () => _showEditForm(
                          context,
                          ref,
                          report,
                          isDarkMode,
                          isSwahili,
                        ),
                        onDelete: () =>
                            _deleteReport(context, ref, report, isSwahili),
                      );
                    }, childCount: filteredReports.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showSiteDailyReportDetails(
    BuildContext context,
    WidgetRef ref, {
    required int reportId,
    required bool isSwahili,
    required bool isDarkMode,
  }) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDarkMode ? const Color(0xFF1A2332) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (ctx, scrollController) {
          return Consumer(
            builder: (context, ref, _) {
              final detailAsync = ref.watch(
                _siteDailyReportDetailProvider(reportId),
              );

              return detailAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _SiteDailyReportErrorView(
                  isSwahili: isSwahili,
                  onRetry: () =>
                      ref.invalidate(_siteDailyReportDetailProvider(reportId)),
                ),
                data: (report) {
                  final site = report['site'] as Map<String, dynamic>?;
                  final siteName = site?['name'] as String? ?? '-';
                  final status = (report['status'] as String? ?? 'draft')
                      .toLowerCase();
                  final activities =
                      (report['work_activities'] as List? ?? const [])
                          .cast<Map<String, dynamic>>();
                  final materials =
                      (report['materials_used'] as List? ?? const [])
                          .cast<Map<String, dynamic>>();
                  final payments = (report['payments'] as List? ?? const [])
                      .cast<Map<String, dynamic>>();
                  final laborNeeded =
                      (report['labor_needed'] as List? ?? const [])
                          .cast<Map<String, dynamic>>();

                  return ListView(
                    controller: scrollController,
                    padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                    children: [
                      Center(
                        child: Container(
                          width: 40,
                          height: 4,
                          decoration: BoxDecoration(
                            color: Colors.grey[400],
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              siteName,
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                                color: isDarkMode
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.close),
                            onPressed: () => Navigator.pop(ctx),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _StatusBadge(status: status, isSwahili: isSwahili),
                          _InfoChip(
                            icon: Icons.calendar_today_rounded,
                            label: _formatDate(
                              report['report_date'] as String?,
                            ),
                            isDarkMode: isDarkMode,
                          ),
                          _InfoChip(
                            icon: Icons.trending_up_rounded,
                            label:
                                '${_toInt(report['progress_percentage'])}% ${isSwahili ? 'maendeleo' : 'progress'}',
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),
                      _DetailSection(
                        title: isSwahili ? 'Muhtasari' : 'Overview',
                        isDarkMode: isDarkMode,
                        children: [
                          _DetailRow(
                            label: isSwahili ? 'Aliyeandaa' : 'Prepared By',
                            value:
                                (report['prepared_by_user']
                                        as Map<String, dynamic>?)?['name']
                                    as String? ??
                                '-',
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Msimamizi' : 'Supervisor',
                            value:
                                (report['supervisor']
                                        as Map<String, dynamic>?)?['name']
                                    as String? ??
                                '-',
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Hatua Zifuatazo' : 'Next Steps',
                            value: report['next_steps'] as String? ?? '-',
                            isDarkMode: isDarkMode,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Changamoto' : 'Challenges',
                            value: report['challenges'] as String? ?? '-',
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                      if (activities.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        _DetailSection(
                          title: isSwahili
                              ? 'Shughuli za Kazi'
                              : 'Work Activities',
                          isDarkMode: isDarkMode,
                          children: activities
                              .map(
                                (activity) => _DetailRow(
                                  label:
                                      activity['activity_name'] as String? ??
                                      '-',
                                  value:
                                      [
                                            activity['description'] as String?,
                                            if (_toInt(
                                                  activity['workers_count'],
                                                ) >
                                                0)
                                              '${_toInt(activity['workers_count'])} ${isSwahili ? 'wafanyakazi' : 'workers'}',
                                          ]
                                          .whereType<String>()
                                          .where((value) => value.isNotEmpty)
                                          .join(' - '),
                                  isDarkMode: isDarkMode,
                                ),
                              )
                              .toList(),
                        ),
                      ],
                      if (materials.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        _DetailSection(
                          title: isSwahili
                              ? 'Vifaa Vilivyotumika'
                              : 'Materials Used',
                          isDarkMode: isDarkMode,
                          children: materials
                              .map(
                                (material) => _DetailRow(
                                  label:
                                      material['material_name'] as String? ??
                                      '-',
                                  value: [
                                    '${_toDouble(material['quantity']).toStringAsFixed(0)} ${material['unit'] as String? ?? ''}'
                                        .trim(),
                                    if (_toDouble(material['total_cost']) > 0)
                                      _formatCurrency(
                                        _toDouble(material['total_cost']),
                                      ),
                                  ].where((value) => value.isNotEmpty).join(' - '),
                                  isDarkMode: isDarkMode,
                                ),
                              )
                              .toList(),
                        ),
                      ],
                      if (payments.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        _DetailSection(
                          title: isSwahili ? 'Malipo' : 'Payments',
                          isDarkMode: isDarkMode,
                          children: payments
                              .map(
                                (payment) => _DetailRow(
                                  label:
                                      payment['recipient_name'] as String? ??
                                      '-',
                                  value:
                                      [
                                            _formatCurrency(
                                              _toDouble(payment['amount']),
                                            ),
                                            payment['payment_type'] as String?,
                                            payment['payment_method']
                                                as String?,
                                          ]
                                          .whereType<String>()
                                          .where((value) => value.isNotEmpty)
                                          .join(' - '),
                                  isDarkMode: isDarkMode,
                                ),
                              )
                              .toList(),
                        ),
                      ],
                      if (laborNeeded.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        _DetailSection(
                          title: isSwahili
                              ? 'Kazi Inayohitajika'
                              : 'Labor Needed',
                          isDarkMode: isDarkMode,
                          children: laborNeeded
                              .map(
                                (labor) => _DetailRow(
                                  label: labor['labor_type'] as String? ?? '-',
                                  value: [
                                    '${_toInt(labor['quantity'])} ${isSwahili ? 'watu' : 'people'}',
                                    if (_toDouble(labor['total_cost']) > 0)
                                      _formatCurrency(
                                        _toDouble(labor['total_cost']),
                                      ),
                                  ].where((value) => value.isNotEmpty).join(' - '),
                                  isDarkMode: isDarkMode,
                                ),
                              )
                              .toList(),
                        ),
                      ],
                    ],
                  );
                },
              );
            },
          );
        },
      ),
    );
  }

  Future<void> _showCreateForm(
    BuildContext context,
    WidgetRef ref,
    bool isDarkMode,
    bool isSwahili,
  ) async {
    final sitesAsync = ref.read(_siteDailyReportSitesProvider);
    final sites = sitesAsync.valueOrNull?.cast<Map<String, dynamic>>() ?? [];

    if (sites.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSwahili ? 'Hakuna maeneo' : 'No sites available'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    int? selectedSiteId;
    String reportDate = DateFormat('yyyy-MM-dd').format(DateTime.now());
    int progressPercentage = 0;
    String nextSteps = '';
    String challenges = '';

    List<Map<String, String>> workActivities = [];
    List<Map<String, String>> materials = [];
    List<Map<String, String>> payments = [];
    List<Map<String, String>> laborNeeded = [];

    bool workActivitiesExpanded = true;
    bool materialsExpanded = true;
    bool paymentsExpanded = true;
    bool laborExpanded = true;

    final formKey = GlobalKey<FormState>();

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDarkMode ? const Color(0xFF1A2332) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setState) {
          final bgColor = isDarkMode ? const Color(0xFF1A2332) : Colors.white;
          final inputBg = isDarkMode
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05);
          final textColor = isDarkMode ? Colors.white : AppColors.textPrimary;
          final hintColor = isDarkMode ? Colors.white54 : AppColors.textHint;

          InputDecoration inputStyle(String label) => InputDecoration(
            labelText: label,
            labelStyle: TextStyle(fontSize: 12, color: hintColor),
            filled: true,
            fillColor: inputBg,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 12,
              vertical: 12,
            ),
          );

          Widget sectionTitle(String title, IconData icon) => Padding(
            padding: const EdgeInsets.only(bottom: 12, top: 8),
            child: Row(
              children: [
                Icon(icon, size: 18, color: AppColors.primary),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: textColor,
                  ),
                ),
              ],
            ),
          );

          Widget dynamicList({
            required List<Map<String, String>> items,
            required List<String> keys,
            required List<String> labels,
            required VoidCallback onAdd,
            required Function(int) onRemove,
          }) {
            return Column(
              children: [
                ...items.asMap().entries.map((entry) {
                  final idx = entry.key;
                  final item = entry.value;
                  return Container(
                    margin: const EdgeInsets.only(bottom: 8),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: inputBg,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(
                        color: Colors.grey.withValues(alpha: 0.2),
                      ),
                    ),
                    child: Column(
                      children: [
                        ...keys.asMap().entries.map((e) {
                          final keyIdx = e.key;
                          final key = e.value;
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: TextFormField(
                              initialValue: item[key],
                              decoration: inputStyle(labels[keyIdx]),
                              style: TextStyle(color: textColor),
                              onChanged: (v) => item[key] = v,
                            ),
                          );
                        }),
                        Align(
                          alignment: Alignment.centerRight,
                          child: TextButton.icon(
                            onPressed: () => onRemove(idx),
                            icon: const Icon(
                              Icons.remove_circle,
                              size: 18,
                              color: Colors.red,
                            ),
                            label: Text(
                              isSwahili ? 'Ondoa' : 'Remove',
                              style: const TextStyle(
                                color: Colors.red,
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                }),
                OutlinedButton.icon(
                  onPressed: onAdd,
                  icon: const Icon(Icons.add, size: 18),
                  label: Text(isSwahili ? 'Ongeza' : 'Add'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: const BorderSide(color: AppColors.primary),
                  ),
                ),
              ],
            );
          }

          return DraggableScrollableSheet(
            initialChildSize: 0.9,
            minChildSize: 0.5,
            maxChildSize: 0.95,
            expand: false,
            builder: (ctx, scrollController) => Form(
              key: formKey,
              child: ListView(
                controller: scrollController,
                padding: EdgeInsets.fromLTRB(
                  20,
                  16,
                  20,
                  MediaQuery.of(ctx).viewInsets.bottom + 20,
                ),
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey[400],
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Center(
                    child: Text(
                      isSwahili
                          ? 'Ongeza Ripoti Mpya'
                          : 'Create Site Daily Report',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  sectionTitle(
                    isSwahili ? 'Taarifa za Ripoti' : 'Report Information',
                    Icons.description,
                  ),

                  DropdownButtonFormField<int>(
                    value: selectedSiteId,
                    isExpanded: true,
                    decoration: inputStyle(isSwahili ? 'Eneo *' : 'Site *'),
                    dropdownColor: bgColor,
                    items: sites
                        .map(
                          (s) => DropdownMenuItem(
                            value: s['id'] as int,
                            child: Text(
                              s['name']?.toString() ?? '-',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() => selectedSiteId = v),
                    validator: (v) => v == null
                        ? (isSwahili ? 'Hitajiwa' : 'Required')
                        : null,
                  ),
                  const SizedBox(height: 12),

                  TextFormField(
                    initialValue: reportDate,
                    readOnly: true,
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: ctx,
                        initialDate: DateTime.now(),
                        firstDate: DateTime(2020),
                        lastDate: DateTime.now(),
                      );
                      if (picked != null) {
                        setState(
                          () => reportDate = DateFormat(
                            'yyyy-MM-dd',
                          ).format(picked),
                        );
                      }
                    },
                    decoration:
                        inputStyle(
                          isSwahili ? 'Tarehe ya Ripoti *' : 'Report Date *',
                        ).copyWith(
                          suffixIcon: const Icon(
                            Icons.calendar_today,
                            size: 18,
                          ),
                        ),
                    style: TextStyle(color: textColor),
                  ),
                  const SizedBox(height: 12),

                  Row(
                    children: [
                      Text(
                        '${isSwahili ? 'Asilimia ya Maendeleo' : 'Progress Percentage'}: $progressPercentage%',
                        style: TextStyle(
                          color: textColor,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                  Slider(
                    value: progressPercentage.toDouble(),
                    min: 0,
                    max: 100,
                    divisions: 20,
                    onChanged: (v) =>
                        setState(() => progressPercentage = v.toInt()),
                  ),
                  const Divider(),

                  ExpansionTile(
                    initiallyExpanded: workActivitiesExpanded,
                    onExpansionChanged: (v) =>
                        setState(() => workActivitiesExpanded = v),
                    leading: const Icon(
                      Icons.construction,
                      color: AppColors.primary,
                    ),
                    title: Text(
                      isSwahili ? 'Shughuli za Kazi' : 'Work Activities',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        color: textColor,
                      ),
                    ),
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: dynamicList(
                          items: workActivities,
                          keys: [
                            'activity_name',
                            'description',
                            'workers_count',
                          ],
                          labels: [
                            isSwahili ? 'Jina la Shughuli' : 'Activity Name',
                            isSwahili ? 'Maelezo' : 'Description',
                            isSwahili
                                ? 'Idadi ya Wafanyakazi'
                                : 'Workers Count',
                          ],
                          onAdd: () => setState(
                            () => workActivities.add({
                              'activity_name': '',
                              'description': '',
                              'workers_count': '',
                            }),
                          ),
                          onRemove: (i) =>
                              setState(() => workActivities.removeAt(i)),
                        ),
                      ),
                    ],
                  ),

                  ExpansionTile(
                    initiallyExpanded: materialsExpanded,
                    onExpansionChanged: (v) =>
                        setState(() => materialsExpanded = v),
                    leading: const Icon(
                      Icons.inventory_2,
                      color: AppColors.primary,
                    ),
                    title: Text(
                      isSwahili ? 'Vifaa Vilivyotumika' : 'Materials Used',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        color: textColor,
                      ),
                    ),
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: dynamicList(
                          items: materials,
                          keys: ['material_name', 'quantity', 'unit'],
                          labels: [
                            isSwahili ? 'Jina la Vifaa' : 'Material Name',
                            isSwahili ? 'Kiasi' : 'Quantity',
                            isSwahili ? 'Uniti' : 'Unit',
                          ],
                          onAdd: () => setState(
                            () => materials.add({
                              'material_name': '',
                              'quantity': '',
                              'unit': '',
                            }),
                          ),
                          onRemove: (i) =>
                              setState(() => materials.removeAt(i)),
                        ),
                      ),
                    ],
                  ),

                  ExpansionTile(
                    initiallyExpanded: paymentsExpanded,
                    onExpansionChanged: (v) =>
                        setState(() => paymentsExpanded = v),
                    leading: const Icon(
                      Icons.payments,
                      color: AppColors.primary,
                    ),
                    title: Text(
                      isSwahili ? 'Malipo' : 'Payments',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        color: textColor,
                      ),
                    ),
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: dynamicList(
                          items: payments,
                          keys: ['description', 'amount', 'recipient_name'],
                          labels: [
                            isSwahili
                                ? 'Maelezo ya Malipo'
                                : 'Payment Description',
                            isSwahili ? 'Kiasi' : 'Amount',
                            isSwahili ? 'Malipo kwa' : 'Payment To',
                          ],
                          onAdd: () => setState(
                            () => payments.add({
                              'description': '',
                              'amount': '',
                              'recipient_name': '',
                            }),
                          ),
                          onRemove: (i) => setState(() => payments.removeAt(i)),
                        ),
                      ),
                    ],
                  ),

                  ExpansionTile(
                    initiallyExpanded: laborExpanded,
                    onExpansionChanged: (v) =>
                        setState(() => laborExpanded = v),
                    leading: const Icon(
                      Icons.engineering,
                      color: AppColors.primary,
                    ),
                    title: Text(
                      isSwahili ? 'Kazi Inayohitajika' : 'Labor Needed',
                      style: TextStyle(
                        fontWeight: FontWeight.w600,
                        color: textColor,
                      ),
                    ),
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(12),
                        child: dynamicList(
                          items: laborNeeded,
                          keys: ['labor_type', 'quantity', 'description'],
                          labels: [
                            isSwahili ? 'Aina ya Kazi' : 'Labor Type',
                            isSwahili ? 'Idadi' : 'Quantity',
                            isSwahili ? 'Maelezo' : 'Description',
                          ],
                          onAdd: () => setState(
                            () => laborNeeded.add({
                              'labor_type': '',
                              'quantity': '',
                              'description': '',
                            }),
                          ),
                          onRemove: (i) =>
                              setState(() => laborNeeded.removeAt(i)),
                        ),
                      ),
                    ],
                  ),

                  sectionTitle(
                    isSwahili ? 'Taarifa za Ziada' : 'Additional Information',
                    Icons.info_outline,
                  ),

                  TextFormField(
                    initialValue: challenges,
                    maxLines: 3,
                    onChanged: (v) => challenges = v,
                    decoration:
                        inputStyle(
                          isSwahili ? 'Changamoto (Challenges)' : 'Challenges',
                        ).copyWith(
                          prefixIcon: Padding(
                            padding: const EdgeInsets.only(bottom: 40),
                            child: Icon(
                              Icons.warning_amber,
                              color: Colors.orange,
                              size: 20,
                            ),
                          ),
                        ),
                    style: TextStyle(color: textColor),
                  ),
                  const SizedBox(height: 12),

                  TextFormField(
                    initialValue: nextSteps,
                    maxLines: 3,
                    onChanged: (v) => nextSteps = v,
                    decoration:
                        inputStyle(
                          isSwahili
                              ? 'Hatua Zinazofuata (Next Steps)'
                              : 'Next Steps',
                        ).copyWith(
                          prefixIcon: Padding(
                            padding: const EdgeInsets.only(bottom: 40),
                            child: Icon(
                              Icons.arrow_forward,
                              color: Colors.green,
                              size: 20,
                            ),
                          ),
                        ),
                    style: TextStyle(color: textColor),
                  ),
                  const SizedBox(height: 24),

                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (!formKey.currentState!.validate()) return;
                        if (selectedSiteId == null) {
                          ScaffoldMessenger.of(ctx).showSnackBar(
                            SnackBar(
                              content: Text(
                                isSwahili
                                    ? 'Tafadhali chagua eneo'
                                    : 'Please select a site',
                              ),
                              backgroundColor: Colors.red,
                            ),
                          );
                          return;
                        }
                        try {
                          final api = ref.read(apiClientProvider);
                          final workActivitiesData = workActivities
                              .where(
                                (a) => a['activity_name']?.isNotEmpty == true,
                              )
                              .map(
                                (a) => {
                                  'activity_name': a['activity_name'] ?? '',
                                  'description': a['description'] ?? '',
                                  'workers_count':
                                      int.tryParse(a['workers_count'] ?? '') ??
                                      0,
                                },
                              )
                              .toList();
                          final materialsData = materials
                              .where(
                                (m) => m['material_name']?.isNotEmpty == true,
                              )
                              .map(
                                (m) => {
                                  'material_name': m['material_name'] ?? '',
                                  'quantity':
                                      double.tryParse(m['quantity'] ?? '') ?? 0,
                                  'unit': m['unit'] ?? '',
                                },
                              )
                              .toList();
                          final paymentsData = payments
                              .where(
                                (p) => p['description']?.isNotEmpty == true,
                              )
                              .map(
                                (p) => {
                                  'description': p['description'] ?? '',
                                  'amount':
                                      double.tryParse(p['amount'] ?? '') ?? 0,
                                  'recipient_name': p['recipient_name'] ?? '',
                                },
                              )
                              .toList();
                          final laborData = laborNeeded
                              .where((l) => l['labor_type']?.isNotEmpty == true)
                              .map(
                                (l) => {
                                  'labor_type': l['labor_type'] ?? '',
                                  'quantity':
                                      int.tryParse(l['quantity'] ?? '') ?? 0,
                                  'description': l['description'] ?? '',
                                },
                              )
                              .toList();

                          await api.post(
                            '/site-daily-reports',
                            data: {
                              'site_id': selectedSiteId,
                              'report_date': reportDate,
                              'progress_percentage': progressPercentage,
                              'next_steps': nextSteps,
                              'challenges': challenges,
                              if (workActivitiesData.isNotEmpty)
                                'work_activities': workActivitiesData,
                              if (materialsData.isNotEmpty)
                                'materials_used': materialsData,
                              if (paymentsData.isNotEmpty)
                                'payments': paymentsData,
                              if (laborData.isNotEmpty)
                                'labor_needed': laborData,
                            },
                          );
                          ref.invalidate(_siteDailyReportsProvider);
                          if (ctx.mounted) Navigator.pop(ctx);
                          if (context.mounted)
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(
                                  isSwahili ? 'Imefanikiwa' : 'Success',
                                ),
                                backgroundColor: Colors.green,
                              ),
                            );
                        } catch (e) {
                          String errorMsg = isSwahili ? 'Hitilafu' : 'Error';
                          if (e is DioException && e.response?.data != null) {
                            final data = e.response!.data;
                            if (data is Map) {
                              errorMsg =
                                  data['message'] ??
                                  data['error'] ??
                                  e.message ??
                                  errorMsg;
                            }
                          }
                          if (ctx.mounted)
                            ScaffoldMessenger.of(ctx).showSnackBar(
                              SnackBar(
                                content: Text(errorMsg),
                                backgroundColor: Colors.red,
                                duration: const Duration(seconds: 5),
                              ),
                            );
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: Text(isSwahili ? 'Hifadhi Ripoti' : 'Save Report'),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _showEditForm(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> report,
    bool isDarkMode,
    bool isSwahili,
  ) async {
    final reportId = report['id'];
    if (reportId == null || reportId == 0) return;

    String status = report['status'] as String? ?? 'draft';
    String nextSteps = report['next_steps'] as String? ?? '';
    String challenges = report['challenges'] as String? ?? '';
    int progressPercentage = _toInt(report['progress_percentage']);

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDarkMode ? const Color(0xFF1A2332) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setState) => Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(ctx).viewInsets.bottom + 20,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey[400],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili ? 'Hariri Ripoti' : 'Edit Report',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  '${isSwahili ? 'Maendeleo' : 'Progress'}: $progressPercentage%',
                  style: TextStyle(
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                Slider(
                  value: progressPercentage.toDouble(),
                  min: 0,
                  max: 100,
                  divisions: 20,
                  onChanged: (v) =>
                      setState(() => progressPercentage = v.toInt()),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  initialValue: nextSteps,
                  maxLines: 2,
                  onChanged: (v) => nextSteps = v,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Hatua Zifuatazo' : 'Next Steps',
                    labelStyle: TextStyle(
                      fontSize: 12,
                      color: isDarkMode ? Colors.white54 : AppColors.textHint,
                    ),
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF0F1923)
                        : Colors.grey.withValues(alpha: 0.05),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  style: TextStyle(
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  initialValue: challenges,
                  maxLines: 2,
                  onChanged: (v) => challenges = v,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Changamoto' : 'Challenges',
                    labelStyle: TextStyle(
                      fontSize: 12,
                      color: isDarkMode ? Colors.white54 : AppColors.textHint,
                    ),
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF0F1923)
                        : Colors.grey.withValues(alpha: 0.05),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  style: TextStyle(
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () async {
                      try {
                        final api = ref.read(apiClientProvider);
                        await api.put(
                          '/site-daily-reports/$reportId',
                          data: {
                            'status': status,
                            'progress_percentage': progressPercentage,
                            'next_steps': nextSteps,
                            'challenges': challenges,
                          },
                        );
                        ref.invalidate(_siteDailyReportsProvider);
                        if (ctx.mounted) Navigator.pop(ctx);
                        if (context.mounted)
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(
                                isSwahili ? 'Imesasishwa' : 'Updated',
                              ),
                              backgroundColor: Colors.green,
                            ),
                          );
                      } catch (e) {
                        if (ctx.mounted)
                          ScaffoldMessenger.of(ctx).showSnackBar(
                            SnackBar(
                              content: Text(isSwahili ? 'Hitilafu' : 'Error'),
                              backgroundColor: Colors.red,
                            ),
                          );
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                    child: Text(isSwahili ? 'Sasisha' : 'Update'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _deleteReport(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> report,
    bool isSwahili,
  ) async {
    final reportId = report['id'];
    if (reportId == null || reportId == 0) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Thibitisha' : 'Confirm'),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta ripoti hii?'
              : 'Are you sure you want to delete this report?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Hapana' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      await api.delete('/site-daily-reports/$reportId');
      ref.invalidate(_siteDailyReportsProvider);
      if (context.mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imefutwa' : 'Report deleted'),
            backgroundColor: Colors.green,
          ),
        );
    } catch (e) {
      if (context.mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Hitilafu' : 'Error'),
            backgroundColor: Colors.red,
          ),
        );
    }
  }
}

class _SiteDailyReportFilterBar extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _SiteDailyReportFilterBar({
    required this.isSwahili,
    required this.isDarkMode,
    required this.selectedStatus,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final options = <String?, String>{
      null: isSwahili ? 'Zote' : 'All',
      'draft': isSwahili ? 'Rasimu' : 'Draft',
      'submitted': isSwahili ? 'Imewasilishwa' : 'Submitted',
      'pending': isSwahili ? 'Inasubiri' : 'Pending',
      'approved': isSwahili ? 'Imeidhinishwa' : 'Approved',
      'rejected': isSwahili ? 'Imekataliwa' : 'Rejected',
    };

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: options.entries.map((entry) {
          final selected = selectedStatus == entry.key;
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(
              selected: selected,
              label: Text(entry.value),
              onSelected: (_) => onChanged(entry.key),
              selectedColor: AppColors.primary.withValues(alpha: 0.15),
              labelStyle: TextStyle(
                color: selected
                    ? AppColors.primary
                    : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
                fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
              ),
              side: BorderSide(
                color: selected
                    ? AppColors.primary
                    : (isDarkMode
                          ? Colors.white12
                          : AppColors.textHint.withValues(alpha: 0.4)),
              ),
              backgroundColor: isDarkMode
                  ? const Color(0xFF2A2A3E)
                  : Colors.white,
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _SiteDailyReportCard extends StatelessWidget {
  final Map<String, dynamic> report;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _SiteDailyReportCard({
    required this.report,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final site = report['site'] as Map<String, dynamic>?;
    final siteName = site?['name'] as String? ?? '-';
    final reportDate = report['report_date'] as String?;
    final status = (report['status'] as String? ?? 'draft').toLowerCase();
    final progress = _toInt(report['progress_percentage']);
    final preparedBy =
        (report['prepared_by_user'] as Map<String, dynamic>?)?['name']
            as String? ??
        '-';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: InkWell(
        onTap: onView,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(
                      Icons.assignment_rounded,
                      color: Color(0xFF3B82F6),
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          siteName,
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          _formatDate(reportDate),
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'view') {
                        onView();
                      } else if (value == 'edit') {
                        onEdit();
                      } else if (value == 'delete') {
                        onDelete();
                      }
                    },
                    itemBuilder: (_) => [
                      PopupMenuItem(
                        value: 'view',
                        child: Row(
                          children: [
                            const Icon(Icons.visibility, size: 20),
                            const SizedBox(width: 8),
                            Text(isSwahili ? 'Tazama' : 'View'),
                          ],
                        ),
                      ),
                      PopupMenuItem(
                        value: 'edit',
                        child: Row(
                          children: [
                            const Icon(Icons.edit, size: 20),
                            const SizedBox(width: 8),
                            Text(isSwahili ? 'Hariri' : 'Edit'),
                          ],
                        ),
                      ),
                      PopupMenuItem(
                        value: 'delete',
                        child: Row(
                          children: [
                            const Icon(
                              Icons.delete,
                              size: 20,
                              color: Colors.red,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              isSwahili ? 'Futa' : 'Delete',
                              style: const TextStyle(color: Colors.red),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  _StatusBadge(status: status, isSwahili: isSwahili),
                  const SizedBox(width: 8),
                  _InfoChip(
                    icon: Icons.trending_up_rounded,
                    label: '$progress%',
                    isDarkMode: isDarkMode,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _InfoChip(
                      icon: Icons.person_outline_rounded,
                      label: preparedBy,
                      isDarkMode: isDarkMode,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _StatusBadge({required this.status, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final color = _statusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        _statusLabel(status, isSwahili),
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isDarkMode;

  const _InfoChip({
    required this.icon,
    required this.label,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.primary),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              label,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailSection extends StatelessWidget {
  final String title;
  final bool isDarkMode;
  final List<Widget> children;

  const _DetailSection({
    required this.title,
    required this.isDarkMode,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode
            ? const Color(0xFF252540)
            : Colors.grey.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 4,
            child: Text(
              label,
              style: TextStyle(
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 6,
            child: Text(
              value.isEmpty ? '-' : value,
              style: TextStyle(
                fontSize: 14,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
              textAlign: TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }
}

class _SiteDailyReportErrorView extends StatelessWidget {
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SiteDailyReportErrorView({
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ],
      ),
    );
  }
}

String _formatDate(String? date) {
  if (date == null || date.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
  } catch (_) {
    return date;
  }
}

String _formatCurrency(double amount) {
  final formatter = NumberFormat('#,##0.00', 'en');
  return 'TZS ${formatter.format(amount)}';
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

double _toDouble(dynamic value) {
  if (value is double) return value;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0;
  return 0;
}

Color _statusColor(String status) {
  switch (status.toLowerCase()) {
    case 'approved':
      return AppColors.success;
    case 'submitted':
    case 'pending':
      return AppColors.warning;
    case 'rejected':
      return AppColors.error;
    default:
      return AppColors.textSecondary;
  }
}

String _statusLabel(String status, bool isSwahili) {
  switch (status.toLowerCase()) {
    case 'draft':
      return isSwahili ? 'Rasimu' : 'Draft';
    case 'submitted':
      return isSwahili ? 'Imewasilishwa' : 'Submitted';
    case 'pending':
      return isSwahili ? 'Inasubiri' : 'Pending';
    case 'approved':
      return isSwahili ? 'Imeidhinishwa' : 'Approved';
    case 'rejected':
      return isSwahili ? 'Imekataliwa' : 'Rejected';
    default:
      return status.isEmpty ? '-' : status;
  }
}
