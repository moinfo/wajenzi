import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _sitesSearchProvider = StateProvider.autoDispose<String>((ref) => '');

class _SitesFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final String? status;

  _SitesFilter({this.startDate, this.endDate, this.status});

  _SitesFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    String? status,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearStatus = false,
  }) {
    return _SitesFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      status: clearStatus ? null : (status ?? this.status),
    );
  }

  Map<String, String> toQueryParams() {
    final params = <String, String>{};
    if (startDate != null)
      params['start_date'] = DateFormat('yyyy-MM-dd').format(startDate!);
    if (endDate != null)
      params['end_date'] = DateFormat('yyyy-MM-dd').format(endDate!);
    if (status != null && status!.isNotEmpty) params['status'] = status!;
    return params;
  }
}

final _sitesFilterProvider = StateProvider.autoDispose<_SitesFilter>(
  (ref) => _SitesFilter(),
);

final _sitesProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(_sitesFilterProvider);
  final search = ref.watch(_sitesSearchProvider);

  final response = await api.get(
    '/projects/sites',
    queryParameters: {
      if (search.isNotEmpty) 'search': search,
      ...filter.toQueryParams(),
    },
  );

  return (response.data['data']['sites'] as List?)
          ?.cast<Map<String, dynamic>>() ??
      [];
});

final _siteDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>?, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/projects/sites/$id');
      return response.data['data'] as Map<String, dynamic>?;
    });

String _siteErrorMessage(Object error, bool isSwahili) {
  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class SitesScreen extends ConsumerStatefulWidget {
  const SitesScreen({super.key});

  @override
  ConsumerState<SitesScreen> createState() => _SitesScreenState();
}

class _SitesScreenState extends ConsumerState<SitesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final sitesAsync = ref.watch(_sitesProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final filter = ref.watch(_sitesFilterProvider);
    final search = ref.watch(_sitesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Maeneo ya Mradi' : 'Project Sites'),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showSiteForm(context),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza' : 'Add',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_sitesProvider.future),
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
                          ref.read(_sitesSearchProvider.notifier).state = value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta maeneo...'
                            : 'Search sites...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(_sitesSearchProvider.notifier)
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDark
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
                    _SiteFilters(
                      filter: filter,
                      isSwahili: isSwahili,
                      isDarkMode: isDark,
                    ),
                  ],
                ),
              ),
            ),
            sitesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _SitesErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_sitesProvider),
                ),
              ),
              data: (allItems) {
                final sites = search.isEmpty
                    ? allItems
                    : allItems.where((site) {
                        final haystack = [
                          site['name'] ?? '',
                          site['location'] ?? '',
                          site['status'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (sites.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.location_off_rounded,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna maeneo'
                                      : 'No sites found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No sites match your search'),
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
                                          .read(_sitesSearchProvider.notifier)
                                          .state =
                                      '',
                              icon: const Icon(Icons.arrow_back_rounded),
                              label: Text(isSwahili ? 'Rudi' : 'Back'),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _SiteCard(
                        site: sites[index],
                        isDark: isDark,
                        isSwahili: isSwahili,
                        onView: () => _showSiteDetail(context, sites[index]),
                        onEdit: () =>
                            _showSiteForm(context, site: sites[index]),
                        onDelete: () => _deleteSite(context, sites[index]),
                      ),
                      childCount: sites.length,
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showSiteDetail(BuildContext context, Map<String, dynamic> site) {
    final isDark = ref.read(isDarkModeProvider);
    final isSwahili = ref.read(isSwahiliProvider);
    final detailAsync = ref.read(_siteDetailProvider(site['id'] as int).future);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return FutureBuilder<Map<String, dynamic>?>(
          future: detailAsync,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator()),
              );
            }

            final detail = snapshot.data?['site'] ?? site;
            final recentReports =
                (snapshot.data?['recent_reports'] as List?) ?? [];

            return DraggableScrollableSheet(
              initialChildSize: 0.7,
              minChildSize: 0.4,
              maxChildSize: 0.9,
              expand: false,
              builder: (ctx, scrollController) {
                return Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        children: [
                          Container(
                            width: 42,
                            height: 4,
                            decoration: BoxDecoration(
                              color: Colors.grey[400],
                              borderRadius: BorderRadius.circular(2),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  detail['name'] ?? 'Site Details',
                                  style: TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                    color: isDark
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                ),
                              ),
                              IconButton(
                                icon: const Icon(Icons.close),
                                onPressed: () => Navigator.pop(context),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Expanded(
                      child: ListView(
                        controller: scrollController,
                        padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                        children: [
                          _DetailRow(
                            label: isSwahili ? 'Mahali' : 'Location',
                            value: detail['location'] ?? '-',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Hali' : 'Status',
                            value: detail['status'] ?? '-',
                            isDark: isDark,
                            valueColor: _getStatusColor(detail['status']),
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Mratibu' : 'Supervisor',
                            value:
                                (detail['current_supervisor']
                                    as Map<String, dynamic>?)?['name'] ??
                                '-',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: isSwahili ? 'Maendeleo' : 'Progress',
                            value:
                                '${_toPercent(detail['progress_percentage'], decimals: 1)}%',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: isSwahili
                                ? 'Tarehe ya Kuanza'
                                : 'Start Date',
                            value: detail['start_date'] ?? '-',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: isSwahili
                                ? 'Tarehe ya Kukamilika'
                                : 'Expected End',
                            value: detail['expected_end_date'] ?? '-',
                            isDark: isDark,
                          ),
                          if (detail['actual_end_date'] != null)
                            _DetailRow(
                              label: isSwahili
                                  ? 'Tarehe ya Kumalizia'
                                  : 'Actual End',
                              value: detail['actual_end_date'] ?? '-',
                              isDark: isDark,
                            ),
                          if (detail['description'] != null &&
                              (detail['description'] as String).isNotEmpty) ...[
                            const SizedBox(height: 16),
                            Text(
                              isSwahili ? 'Maelezo' : 'Description',
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: isDark
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: isDark
                                    ? const Color(0xFF252540)
                                    : Colors.grey.withValues(alpha: 0.05),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                detail['description'] ?? '',
                                style: TextStyle(
                                  fontSize: 14,
                                  color: isDark
                                      ? Colors.white70
                                      : AppColors.textSecondary,
                                ),
                              ),
                            ),
                          ],
                          if (recentReports.isNotEmpty) ...[
                            const SizedBox(height: 20),
                            Text(
                              isSwahili
                                  ? 'Ripoti za Hivi Karibuni'
                                  : 'Recent Reports',
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: isDark
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 8),
                            ...recentReports.map(
                              (report) => ListTile(
                                dense: true,
                                contentPadding: EdgeInsets.zero,
                                leading: Icon(
                                  Icons.description_rounded,
                                  color: _getStatusColor(report['status']),
                                  size: 20,
                                ),
                                title: Text(
                                  report['report_date'] ?? '-',
                                  style: TextStyle(
                                    fontSize: 13,
                                    color: isDark
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                ),
                                subtitle: Text(
                                  report['prepared_by'] ?? '-',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: isDark
                                        ? Colors.white54
                                        : AppColors.textHint,
                                  ),
                                ),
                                trailing: Text(
                                  '${_toPercent(report['progress_percentage'], decimals: 0)}%',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: isDark
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                );
              },
            );
          },
        );
      },
    );
  }

  void _showSiteForm(BuildContext context, {Map<String, dynamic>? site}) {
    final isDark = ref.read(isDarkModeProvider);
    final isSwahili = ref.read(isSwahiliProvider);
    final isEdit = site != null;
    final nameCtrl = TextEditingController(text: site?['name'] ?? '');
    final locationCtrl = TextEditingController(text: site?['location'] ?? '');
    final descCtrl = TextEditingController(text: site?['description'] ?? '');
    String status = site?['status'] ?? 'ACTIVE';
    final startDateCtrl = TextEditingController(
      text: site?['start_date'] ?? '',
    );
    final expectedEndDateCtrl = TextEditingController(
      text: site?['expected_end_date'] ?? '',
    );
    final actualEndDateCtrl = TextEditingController(
      text: site?['actual_end_date'] ?? '',
    );
    final formKey = GlobalKey<FormState>();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setState) {
            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.of(ctx).viewInsets.bottom + 20,
              ),
              child: Form(
                key: formKey,
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: Colors.grey[400],
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        isEdit
                            ? (isSwahili ? 'Hariri Eneo' : 'Edit Site')
                            : (isSwahili ? 'Ongeza Eneo' : 'Add Site'),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 24),
                      _FormField(
                        controller: nameCtrl,
                        label: isSwahili ? 'Jina *' : 'Name *',
                        isDark: isDark,
                        validator: (v) =>
                            (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                      _FormField(
                        controller: locationCtrl,
                        label: isSwahili ? 'Mahali *' : 'Location *',
                        isDark: isDark,
                        validator: (v) =>
                            (v == null || v.isEmpty) ? 'Required' : null,
                      ),
                      _FormField(
                        controller: descCtrl,
                        label: isSwahili ? 'Maelezo' : 'Description',
                        isDark: isDark,
                        maxLines: 3,
                      ),
                      _StatusDropdown(
                        value: status,
                        isDark: isDark,
                        isSwahili: isSwahili,
                        onChanged: (v) =>
                            setState(() => status = v ?? 'ACTIVE'),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: _DateField(
                              controller: startDateCtrl,
                              label: isSwahili
                                  ? 'Tarehe ya Kuanza'
                                  : 'Start Date',
                              isDark: isDark,
                              ctx: ctx,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _DateField(
                              controller: expectedEndDateCtrl,
                              label: isSwahili
                                  ? 'Tarehe ya Kukamilika'
                                  : 'Expected End',
                              isDark: isDark,
                              ctx: ctx,
                            ),
                          ),
                        ],
                      ),
                      if (isEdit) ...[
                        const SizedBox(height: 8),
                        _DateField(
                          controller: actualEndDateCtrl,
                          label: isSwahili
                              ? 'Tarehe ya Kumalizia'
                              : 'Actual End Date',
                          isDark: isDark,
                          ctx: ctx,
                        ),
                      ],
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: () async {
                            if (!formKey.currentState!.validate()) return;
                            final api = ref.read(apiClientProvider);
                            final data = {
                              'name': nameCtrl.text,
                              'location': locationCtrl.text,
                              'description': descCtrl.text,
                              'status': status,
                              if (startDateCtrl.text.isNotEmpty)
                                'start_date': startDateCtrl.text,
                              if (expectedEndDateCtrl.text.isNotEmpty)
                                'expected_end_date': expectedEndDateCtrl.text,
                              if (actualEndDateCtrl.text.isNotEmpty)
                                'actual_end_date': actualEndDateCtrl.text,
                            };
                            try {
                              if (isEdit) {
                                await api.put(
                                  '/projects/sites/${site!['id']}',
                                  data: data,
                                );
                              } else {
                                await api.post('/projects/sites', data: data);
                              }
                              ref.invalidate(_sitesProvider);
                              if (ctx.mounted) Navigator.pop(ctx);
                            } catch (e) {
                              if (ctx.mounted) {
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  SnackBar(
                                    content: Text(
                                      _siteErrorMessage(e, isSwahili),
                                    ),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                              }
                            }
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: Text(
                            isEdit
                                ? (isSwahili ? 'Sasisha' : 'Update')
                                : (isSwahili ? 'Hifadhi' : 'Save'),
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _deleteSite(
    BuildContext context,
    Map<String, dynamic> site,
  ) async {
    final isDark = ref.read(isDarkModeProvider);
    final isSwahili = ref.read(isSwahiliProvider);

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          isSwahili ? 'Thibitisha' : 'Confirm',
          style: TextStyle(
            color: isDark ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta eneo hili?'
              : 'Are you sure you want to delete this site?',
          style: TextStyle(
            color: isDark ? Colors.white70 : AppColors.textSecondary,
          ),
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
      await api.delete('/projects/sites/${site['id']}');
      ref.invalidate(_sitesProvider);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_siteErrorMessage(e, isSwahili)),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Color _getStatusColor(String? status) {
    switch (status) {
      case 'ACTIVE':
        return const Color(0xFF27AE60);
      case 'INACTIVE':
        return const Color(0xFFF59E0B);
      case 'COMPLETED':
        return const Color(0xFF3B82F6);
      default:
        return Colors.grey;
    }
  }

  String _toPercent(dynamic value, {int decimals = 0}) {
    if (value == null) return '0';
    final n = (value is num)
        ? value.toDouble()
        : double.tryParse(value.toString()) ?? 0;
    return n.toStringAsFixed(decimals);
  }
}

class _SiteFilters extends ConsumerWidget {
  final _SitesFilter filter;
  final bool isSwahili;
  final bool isDarkMode;

  const _SiteFilters({
    required this.filter,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ExpansionTile(
      title: Text(isSwahili ? 'Vichungi' : 'Filters'),
      initiallyExpanded:
          filter.status != null ||
          filter.startDate != null ||
          filter.endDate != null,
      childrenPadding: const EdgeInsets.fromLTRB(0, 0, 0, 8),
      backgroundColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      collapsedBackgroundColor: isDarkMode
          ? const Color(0xFF2A2A3E)
          : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      collapsedShape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
      ),
      children: [
        _StatusFilterChips(
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
          selectedStatus: filter.status,
          onChanged: (value) => ref.read(_sitesFilterProvider.notifier).state =
              filter.copyWith(status: value, clearStatus: value == null),
        ),
        Row(
          children: [
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.startDate ?? DateTime.now(),
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now(),
                  );
                  if (picked != null)
                    ref.read(_sitesFilterProvider.notifier).state = filter
                        .copyWith(startDate: picked);
                },
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 20),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                            ),
                          ),
                          Text(
                            filter.startDate != null
                                ? DateFormat(
                                    'dd MMM yyyy',
                                  ).format(filter.startDate!)
                                : '-',
                            style: const TextStyle(fontSize: 14),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.endDate ?? DateTime.now(),
                    firstDate: filter.startDate ?? DateTime(2020),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null)
                    ref.read(_sitesFilterProvider.notifier).state = filter
                        .copyWith(endDate: picked);
                },
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 20),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili ? 'Tarehe ya Mwisho' : 'End Date',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                            ),
                          ),
                          Text(
                            filter.endDate != null
                                ? DateFormat(
                                    'dd MMM yyyy',
                                  ).format(filter.endDate!)
                                : '-',
                            style: const TextStyle(fontSize: 14),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
        if (filter.status != null ||
            filter.startDate != null ||
            filter.endDate != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: OutlinedButton(
              onPressed: () => ref.read(_sitesFilterProvider.notifier).state =
                  _SitesFilter(),
              child: Text(isSwahili ? 'Futa' : 'Clear'),
            ),
          ),
      ],
    );
  }
}

class _StatusFilterChips extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _StatusFilterChips({
    required this.isSwahili,
    required this.isDarkMode,
    required this.selectedStatus,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final options = <String?, String>{
      null: isSwahili ? 'Zote' : 'All',
      'ACTIVE': isSwahili ? 'Wastani' : 'Active',
      'INACTIVE': isSwahili ? 'Isiyo Endelevu' : 'Inactive',
      'COMPLETED': isSwahili ? 'Imalizika' : 'Completed',
    };

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: options.entries.map((entry) {
          final selected = selectedStatus == entry.key;
          return Padding(
            padding: const EdgeInsets.only(right: 8, bottom: 12),
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
                  ? const Color(0xFF1A2332)
                  : Colors.white,
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _SiteCard extends StatelessWidget {
  final Map<String, dynamic> site;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _SiteCard({
    required this.site,
    required this.isDark,
    required this.isSwahili,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
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
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.location_on_rounded,
                      color: Color(0xFF3B82F6),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          site['name'] ?? '-',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: isDark
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          site['location'] ?? '-',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDark
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'view')
                        onView();
                      else if (value == 'edit')
                        onEdit();
                      else if (value == 'delete')
                        onDelete();
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
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: _getStatusColor(
                    site['status'],
                  ).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  site['status'] ?? '-',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: _getStatusColor(site['status']),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        Icon(
                          Icons.person_rounded,
                          size: 14,
                          color: isDark ? Colors.white38 : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            (site['current_supervisor']
                                    as Map<String, dynamic>?)?['name'] ??
                                '-',
                            style: TextStyle(
                              fontSize: 12,
                              color: isDark
                                  ? Colors.white54
                                  : AppColors.textSecondary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.05)
                          : Colors.grey.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.trending_up_rounded,
                          size: 12,
                          color: Color(0xFF27AE60),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${_toPercent(site['progress_percentage'], decimals: 0)}%',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: isDark
                                ? Colors.white70
                                : AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(
                    Icons.calendar_today_rounded,
                    size: 12,
                    color: isDark ? Colors.white38 : AppColors.textHint,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    '${isSwahili ? 'Kuanza' : 'Start'}: ${site['start_date'] ?? '-'}',
                    style: TextStyle(
                      fontSize: 11,
                      color: isDark ? Colors.white38 : AppColors.textSecondary,
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

  Color _getStatusColor(String? status) {
    switch (status) {
      case 'ACTIVE':
        return const Color(0xFF27AE60);
      case 'INACTIVE':
        return const Color(0xFFF59E0B);
      case 'COMPLETED':
        return const Color(0xFF3B82F6);
      default:
        return Colors.grey;
    }
  }

  String _toPercent(dynamic value, {int decimals = 0}) {
    if (value == null) return '0';
    final n = (value is num)
        ? value.toDouble()
        : double.tryParse(value.toString()) ?? 0;
    return n.toStringAsFixed(decimals);
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;
  final Color? valueColor;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDark,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDark ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color:
                    valueColor ??
                    (isDark ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FormField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final bool isDark;
  final int maxLines;
  final String? Function(String?)? validator;

  const _FormField({
    required this.controller,
    required this.label,
    required this.isDark,
    this.maxLines = 1,
    this.validator,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: TextFormField(
        controller: controller,
        maxLines: maxLines,
        validator: validator,
        style: TextStyle(
          fontSize: 14,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: TextStyle(
            fontSize: 12,
            color: isDark ? Colors.white54 : AppColors.textHint,
          ),
          filled: true,
          fillColor: isDark
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
        ),
      ),
    );
  }
}

class _StatusDropdown extends StatelessWidget {
  final String value;
  final bool isDark;
  final bool isSwahili;
  final ValueChanged<String?> onChanged;

  const _StatusDropdown({
    required this.value,
    required this.isDark,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: DropdownButtonFormField<String>(
        value: value,
        items: [
          DropdownMenuItem(
            value: 'ACTIVE',
            child: Text(isSwahili ? 'Wastani' : 'Active'),
          ),
          DropdownMenuItem(
            value: 'INACTIVE',
            child: Text(isSwahili ? 'Isiyo Endelevu' : 'Inactive'),
          ),
          DropdownMenuItem(
            value: 'COMPLETED',
            child: Text(isSwahili ? 'Imalizika' : 'Completed'),
          ),
        ],
        onChanged: onChanged,
        dropdownColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
        decoration: InputDecoration(
          labelText: isSwahili ? 'Hali' : 'Status',
          labelStyle: TextStyle(
            fontSize: 12,
            color: isDark ? Colors.white54 : AppColors.textHint,
          ),
          filled: true,
          fillColor: isDark
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final bool isDark;
  final BuildContext ctx;

  const _DateField({
    required this.controller,
    required this.label,
    required this.isDark,
    required this.ctx,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: TextFormField(
        controller: controller,
        readOnly: true,
        onTap: () async {
          final date = await showDatePicker(
            context: ctx,
            initialDate: DateTime.now(),
            firstDate: DateTime(2020),
            lastDate: DateTime(2030),
          );
          if (date != null) {
            controller.text =
                '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
          }
        },
        style: TextStyle(
          fontSize: 14,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: TextStyle(
            fontSize: 12,
            color: isDark ? Colors.white54 : AppColors.textHint,
          ),
          filled: true,
          fillColor: isDark
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
          suffixIcon: Icon(
            Icons.calendar_today_rounded,
            size: 18,
            color: isDark ? Colors.white38 : AppColors.textHint,
          ),
        ),
      ),
    );
  }
}

class _SitesErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SitesErrorView({
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
        SizedBox(height: MediaQuery.of(context).size.height * 0.2),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
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
