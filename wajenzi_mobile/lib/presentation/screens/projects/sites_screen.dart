import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _sitesFilterProvider = StateProvider<SitesFilter>((ref) {
  return SitesFilter();
});

final _sitesProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(_sitesFilterProvider);

  final response = await api.get(
    '/sites',
    queryParameters: {
      if (filter.search.isNotEmpty) 'search': filter.search,
      if (filter.status.isNotEmpty) 'status': filter.status,
    },
  );

  return (response.data['data']['sites'] as List?)
          ?.cast<Map<String, dynamic>>() ??
      [];
});

final _siteDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>?, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/sites/$id');
      return response.data['data'] as Map<String, dynamic>?;
    });

class SitesFilter {
  final String search;
  final String status;

  SitesFilter({this.search = '', this.status = ''});

  SitesFilter copyWith({String? search, String? status}) {
    return SitesFilter(
      search: search ?? this.search,
      status: status ?? this.status,
    );
  }
}

class SitesScreen extends ConsumerWidget {
  const SitesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final sitesAsync = ref.watch(_sitesProvider);

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F1923) : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Maeneo ya Mradi' : 'Project Sites'),
        backgroundColor: isDark ? const Color(0xFF1A2332) : null,
        actions: [
          TextButton.icon(
            onPressed: () => _showSiteForm(context, ref, isDark, isSwahili),
            icon: const Icon(Icons.add, size: 20),
            label: Text(
              isSwahili ? 'Ongeza' : 'Add',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            style: TextButton.styleFrom(
              foregroundColor: Colors.white,
              backgroundColor: AppColors.primary,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            ),
          ),
          const SizedBox(width: 4),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.invalidate(_sitesProvider),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showSiteForm(context, ref, isDark, isSwahili),
        child: const Icon(Icons.add),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_sitesProvider.future),
        child: sitesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _SitesErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_sitesProvider),
          ),
          data: (sites) {
            if (sites.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  SizedBox(height: MediaQuery.of(context).size.height * 0.3),
                  Center(
                    child: Column(
                      children: [
                        Icon(
                          Icons.location_off_rounded,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili ? 'Hakuna maeneo' : 'No sites found',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              );
            }

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _SitesFilterBar(
                  isDark: isDark,
                  isSwahili: isSwahili,
                  onFilterChanged: (filter) =>
                      ref.read(_sitesFilterProvider.notifier).state = filter,
                ),
                const SizedBox(height: 12),
                ...sites.map(
                  (site) => _SiteCard(
                    site: site,
                    isDark: isDark,
                    isSwahili: isSwahili,
                    onTap: () =>
                        _showSiteDetail(context, ref, site, isDark, isSwahili),
                    onEdit: () => _showSiteForm(
                      context,
                      ref,
                      isDark,
                      isSwahili,
                      site: site,
                    ),
                    onDelete: () => _deleteSite(context, ref, site, isSwahili),
                  ),
                ),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
    );
  }

  void _showSiteDetail(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> site,
    bool isDark,
    bool isSwahili,
  ) {
    final detailAsync = ref.read(_siteDetailProvider(site['id']).future);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A2332) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
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
              initialChildSize: 0.6,
              minChildSize: 0.4,
              maxChildSize: 0.9,
              expand: false,
              builder: (ctx, scrollController) {
                return SingleChildScrollView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  child: Column(
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
                      const SizedBox(height: 20),
                      Text(
                        detail['name'] ?? 'Site Details',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 16),
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
                        label: isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
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
                      if (detail['actual_end_date'] != null) ...[
                        _DetailRow(
                          label: isSwahili
                              ? 'Tarehe ya Kumalizia'
                              : 'Actual End',
                          value: detail['actual_end_date'] ?? '-',
                          isDark: isDark,
                        ),
                      ],
                      if (detail['description'] != null &&
                          (detail['description'] as String).isNotEmpty) ...[
                        const SizedBox(height: 12),
                        Text(
                          isSwahili ? 'Maelezo' : 'Description',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDark ? Colors.white54 : AppColors.textHint,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          detail['description'],
                          style: TextStyle(
                            fontSize: 14,
                            color: isDark
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ],
                      const SizedBox(height: 20),
                      if (recentReports.isNotEmpty) ...[
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
                );
              },
            );
          },
        );
      },
    );
  }

  Future<void> _showSiteForm(
    BuildContext context,
    WidgetRef ref,
    bool isDark,
    bool isSwahili, {
    Map<String, dynamic>? site,
  }) async {
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

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A2332) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
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
                        isEdit
                            ? (isSwahili ? 'Hariri Eneo' : 'Edit Site')
                            : (isSwahili ? 'Ongeza Eneo' : 'Add Site'),
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 20),
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
                      const SizedBox(height: 16),
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
                                  '/sites/${site['id']}',
                                  data: data,
                                );
                              } else {
                                await api.post('/sites', data: data);
                              }
                              ref.invalidate(_sitesProvider);
                              if (ctx.mounted) Navigator.pop(ctx);
                            } catch (e) {
                              if (ctx.mounted) {
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  SnackBar(
                                    content: Text(
                                      _errorMessage(e, isSwahili: isSwahili),
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
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          child: Text(
                            isEdit
                                ? (isSwahili ? 'Sasisha' : 'Update')
                                : (isSwahili ? 'Hifadhi' : 'Save'),
                          ),
                        ),
                      ),
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
    WidgetRef ref,
    Map<String, dynamic> site,
    bool isSwahili,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Thibitisha' : 'Confirm'),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta eneo hili?'
              : 'Are you sure you want to delete this site?',
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
      await api.delete('/sites/${site['id']}');
      ref.invalidate(_sitesProvider);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_errorMessage(e, isSwahili: isSwahili)),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  String _errorMessage(Object error, {bool isSwahili = false}) {
    if (error is Exception) {
      return isSwahili
          ? 'Hitilafu imetokea. Jaribu tena.'
          : 'Something went wrong. Please try again.';
    }
    return isSwahili ? 'Hitilafu imetokea.' : 'An error occurred.';
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

class _SitesFilterBar extends ConsumerWidget {
  final bool isDark;
  final bool isSwahili;
  final ValueChanged<SitesFilter> onFilterChanged;

  const _SitesFilterBar({
    required this.isDark,
    required this.isSwahili,
    required this.onFilterChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filter = ref.watch(_sitesFilterProvider);

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A2332) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark
              ? const Color(0xFF243447)
              : Colors.grey.withValues(alpha: 0.1),
        ),
      ),
      child: Row(
        children: [
          Expanded(
            child: Container(
              height: 36,
              decoration: BoxDecoration(
                color: isDark
                    ? const Color(0xFF0F1923)
                    : Colors.grey.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(8),
              ),
              child: TextField(
                onChanged: (v) => onFilterChanged(filter.copyWith(search: v)),
                style: TextStyle(
                  fontSize: 13,
                  color: isDark ? Colors.white : AppColors.textPrimary,
                ),
                decoration: InputDecoration(
                  hintText: isSwahili ? 'Tafuta...' : 'Search...',
                  hintStyle: TextStyle(
                    fontSize: 13,
                    color: isDark ? Colors.white38 : AppColors.textHint,
                  ),
                  prefixIcon: Icon(
                    Icons.search,
                    size: 18,
                    color: isDark ? Colors.white38 : AppColors.textHint,
                  ),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 10),
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Container(
            decoration: BoxDecoration(
              color: isDark
                  ? const Color(0xFF0F1923)
                  : Colors.grey.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(8),
            ),
            child: PopupMenuButton<String>(
              onSelected: (v) => onFilterChanged(filter.copyWith(status: v)),
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.filter_list,
                      size: 18,
                      color: isDark ? Colors.white54 : AppColors.textSecondary,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      filter.status.isEmpty
                          ? (isSwahili ? 'Hali' : 'Status')
                          : filter.status,
                      style: TextStyle(
                        fontSize: 12,
                        color: isDark
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              itemBuilder: (ctx) => [
                PopupMenuItem(
                  value: '',
                  child: Text(isSwahili ? 'Zote' : 'All'),
                ),
                PopupMenuItem(
                  value: 'ACTIVE',
                  child: Text(isSwahili ? 'Wastani' : 'Active'),
                ),
                PopupMenuItem(
                  value: 'INACTIVE',
                  child: Text(isSwahili ? 'Isiyo Endelevu' : 'Inactive'),
                ),
                PopupMenuItem(
                  value: 'COMPLETED',
                  child: Text(isSwahili ? 'Imalizika' : 'Completed'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SiteCard extends StatelessWidget {
  final Map<String, dynamic> site;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _SiteCard({
    required this.site,
    required this.isDark,
    required this.isSwahili,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      onLongPress: () => _showActions(context),
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1A2332) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isDark
                ? const Color(0xFF243447)
                : Colors.grey.withValues(alpha: 0.1),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.location_on_rounded,
                    size: 20,
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
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(
                            Icons.place_rounded,
                            size: 12,
                            color: isDark ? Colors.white38 : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
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
                          ),
                        ],
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
                    color: _getStatusColor(
                      site['status'],
                    ).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    site['status'] ?? '-',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: _getStatusColor(site['status']),
                    ),
                  ),
                ),
              ],
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
                      Icon(
                        Icons.trending_up_rounded,
                        size: 12,
                        color: const Color(0xFF27AE60),
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
    );
  }

  void _showActions(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.edit),
              title: Text(isSwahili ? 'Hariri' : 'Edit'),
              onTap: () {
                Navigator.pop(ctx);
                onEdit();
              },
            ),
            ListTile(
              leading: const Icon(Icons.delete, color: Colors.red),
              title: Text(
                isSwahili ? 'Futa' : 'Delete',
                style: const TextStyle(color: Colors.red),
              ),
              onTap: () {
                Navigator.pop(ctx);
                onDelete();
              },
            ),
          ],
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
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDark ? Colors.white54 : AppColors.textHint,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
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
          fontSize: 13,
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
            borderRadius: BorderRadius.circular(10),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
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
        dropdownColor: isDark ? const Color(0xFF1A2332) : Colors.white,
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
            borderRadius: BorderRadius.circular(10),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
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
          fontSize: 13,
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
            borderRadius: BorderRadius.circular(10),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
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
