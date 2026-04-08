import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _siteReportsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _siteReportsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_siteReportsSearchProvider);

  try {
    final response = await api.get(
      '/site-daily-reports',
      queryParameters: {if (search.isNotEmpty) 'search': search},
    );

    final data = response.data['data'] is List
        ? (response.data['data'] as List)
              .map((item) => Map<String, dynamic>.from(item))
              .toList()
        : <Map<String, dynamic>>[];

    final sitesResponse = await api.get('/sites');
    final sites = (sitesResponse.data['data']?['sites'] as List? ?? [])
        .map((s) => Map<String, dynamic>.from(s))
        .toList();

    return {'reports': data, 'sites': sites};
  } on DioException catch (e) {
    if ((e.response?.statusCode ?? 0) == 404) {
      return {
        'reports': <Map<String, dynamic>>[],
        'sites': <Map<String, dynamic>>[],
      };
    }
    rethrow;
  }
});

class SiteDailyReportsScreen extends ConsumerStatefulWidget {
  const SiteDailyReportsScreen({super.key});

  @override
  ConsumerState<SiteDailyReportsScreen> createState() =>
      _SiteDailyReportsScreenState();
}

class _SiteDailyReportsScreenState
    extends ConsumerState<SiteDailyReportsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_siteReportsProvider);
    final search = ref.watch(_siteReportsSearchProvider).trim().toLowerCase();

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
          onPressed: () => _showCreateForm(context, ref, isDark, isSwahili),
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_siteReportsProvider.future),
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (v) =>
                      ref.read(_siteReportsSearchProvider.notifier).state = v,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta ripoti...'
                        : 'Search reports...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(
                                          _siteReportsSearchProvider.notifier,
                                        )
                                        .state =
                                    '',
                          )
                        : null,
                    filled: true,
                    fillColor: isDark ? const Color(0xFF2A2A3E) : Colors.white,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
              ),
            ),
            dataAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) =>
                  SliverFillRemaining(child: Center(child: Text('Error: $e'))),
              data: (data) {
                final reports = (data['reports'] as List)
                    .cast<Map<String, dynamic>>();
                final filtered = search.isEmpty
                    ? reports
                    : reports.where((r) {
                        final haystack = [
                          r['site']?['name'] ?? '',
                          r['supervisor']?['name'] ?? '',
                          r['status'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (filtered.isEmpty) {
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
                            isSwahili ? 'Hakuna ripoti' : 'No reports found',
                          ),
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.all(16),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _ReportCard(
                        report: filtered[index],
                        isDark: isDark,
                        isSwahili: isSwahili,
                        onTap: () => _showDetail(
                          context,
                          ref,
                          filtered[index],
                          isDark,
                          isSwahili,
                        ),
                        onEdit: () => _showEditForm(
                          context,
                          ref,
                          filtered[index],
                          isDark,
                          isSwahili,
                        ),
                        onDelete: () => _deleteReport(
                          context,
                          ref,
                          filtered[index],
                          isSwahili,
                        ),
                      ),
                      childCount: filtered.length,
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

  void _showDetail(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> report,
    bool isDark,
    bool isSwahili,
  ) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A2332) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.4,
        maxChildSize: 0.9,
        expand: false,
        builder: (ctx, scrollController) => SingleChildScrollView(
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
              Row(
                children: [
                  Expanded(
                    child: Text(
                      report['site']?['name'] ?? 'Report Details',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: _getStatusColor(
                        report['status'] ?? '',
                      ).withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      report['status'] ?? '-',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: _getStatusColor(report['status'] ?? ''),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _DetailRow(
                label: isSwahili ? 'Tarehe' : 'Date',
                value: report['report_date'] ?? '-',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Eneo' : 'Site',
                value: report['site']?['name'] ?? '-',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Msimamizi' : 'Supervisor',
                value: report['supervisor']?['name'] ?? '-',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Maendeleo' : 'Progress',
                value: '${report['progress_percentage'] ?? 0}%',
                isDark: isDark,
              ),
              if (report['next_steps'] != null &&
                  (report['next_steps'] as String).isNotEmpty)
                _DetailRow(
                  label: isSwahili ? 'Hatua zijazo' : 'Next Steps',
                  value: report['next_steps'],
                  isDark: isDark,
                ),
              if (report['challenges'] != null &&
                  (report['challenges'] as String).isNotEmpty)
                _DetailRow(
                  label: isSwahili ? 'Changamoto' : 'Challenges',
                  value: report['challenges'],
                  isDark: isDark,
                ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _showCreateForm(
    BuildContext context,
    WidgetRef ref,
    bool isDark,
    bool isSwahili,
  ) async {
    final data = ref.read(_siteReportsProvider).valueOrNull;
    if (data == null) return;
    final sites = (data['sites'] as List).cast<Map<String, dynamic>>();
    if (sites.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSwahili ? 'Hakuna maeneo' : 'No sites'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final siteCtrl = TextEditingController();
    final progressCtrl = TextEditingController(text: '0');
    final nextStepsCtrl = TextEditingController();
    final challengesCtrl = TextEditingController();
    DateTime selectedDate = DateTime.now();
    final formKey = GlobalKey<FormState>();

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A2332) : Colors.white,
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
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Ongeza Ripoti' : 'Add Report',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: DropdownButtonFormField<String>(
                      value: siteCtrl.text.isEmpty ? null : siteCtrl.text,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: isSwahili ? 'Eneo *' : 'Site *',
                        filled: true,
                        fillColor: isDark
                            ? const Color(0xFF0F1923)
                            : Colors.grey.withValues(alpha: 0.05),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 14,
                        ),
                      ),
                      items: sites
                          .map(
                            (s) => DropdownMenuItem(
                              value: s['id'].toString(),
                              child: Text(
                                s['name'] ?? '-',
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: isDark
                                      ? Colors.white
                                      : AppColors.textPrimary,
                                ),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (v) => setState(() => siteCtrl.text = v ?? ''),
                      validator: (v) =>
                          v == null || v.isEmpty ? 'Required' : null,
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    readOnly: true,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Tarehe *' : 'Date *',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      suffixIcon: const Icon(Icons.calendar_today),
                    ),
                    controller: TextEditingController(
                      text: DateFormat('yyyy-MM-dd').format(selectedDate),
                    ),
                    onTap: () async {
                      final date = await showDatePicker(
                        context: ctx,
                        initialDate: selectedDate,
                        firstDate: DateTime(2020),
                        lastDate: DateTime.now().add(const Duration(days: 1)),
                      );
                      if (date != null) setState(() => selectedDate = date);
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: progressCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Maendeleo (%)' : 'Progress (%)',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: nextStepsCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Hatua zijazo' : 'Next Steps',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: challengesCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Changamoto' : 'Challenges',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (!formKey.currentState!.validate()) return;
                        try {
                          final api = ref.read(apiClientProvider);
                          await api.post(
                            '/site-daily-reports',
                            data: {
                              'report_date': DateFormat(
                                'yyyy-MM-dd',
                              ).format(selectedDate),
                              'site_id': siteCtrl.text,
                              'progress_percentage':
                                  int.tryParse(progressCtrl.text) ?? 0,
                              'next_steps': nextStepsCtrl.text,
                              'challenges': challengesCtrl.text,
                            },
                          );
                          ref.invalidate(_siteReportsProvider);
                          if (ctx.mounted) Navigator.pop(ctx);
                          if (context.mounted)
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(
                                  isSwahili ? 'Imesajiriwa' : 'Saved',
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
                      child: Text(isSwahili ? 'Hifadhi' : 'Save'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _showEditForm(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> report,
    bool isDark,
    bool isSwahili,
  ) async {
    final reportId = report['id'];
    if (reportId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSwahili ? 'Hitilafu' : 'Error'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    final data = ref.read(_siteReportsProvider).valueOrNull;
    if (data == null) return;
    final sites = (data['sites'] as List).cast<Map<String, dynamic>>();
    final siteCtrl = TextEditingController(
      text: report['site_id']?.toString() ?? '',
    );
    final progressCtrl = TextEditingController(
      text: (report['progress_percentage'] ?? 0).toString(),
    );
    final nextStepsCtrl = TextEditingController(
      text: report['next_steps'] ?? '',
    );
    final challengesCtrl = TextEditingController(
      text: report['challenges'] ?? '',
    );
    DateTime selectedDate =
        DateTime.tryParse(report['report_date'] ?? '') ?? DateTime.now();
    final formKey = GlobalKey<FormState>();

    await showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A2332) : Colors.white,
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
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hariri Ripoti' : 'Edit Report',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: DropdownButtonFormField<String>(
                      value: siteCtrl.text.isEmpty ? null : siteCtrl.text,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: isSwahili ? 'Eneo *' : 'Site *',
                        filled: true,
                        fillColor: isDark
                            ? const Color(0xFF0F1923)
                            : Colors.grey.withValues(alpha: 0.05),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 14,
                        ),
                      ),
                      items: sites
                          .map(
                            (s) => DropdownMenuItem(
                              value: s['id'].toString(),
                              child: Text(
                                s['name'] ?? '-',
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: isDark
                                      ? Colors.white
                                      : AppColors.textPrimary,
                                ),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (v) => setState(() => siteCtrl.text = v ?? ''),
                      validator: (v) =>
                          v == null || v.isEmpty ? 'Required' : null,
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    readOnly: true,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Tarehe *' : 'Date *',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      suffixIcon: const Icon(Icons.calendar_today),
                    ),
                    controller: TextEditingController(
                      text: DateFormat('yyyy-MM-dd').format(selectedDate),
                    ),
                    onTap: () async {
                      final date = await showDatePicker(
                        context: ctx,
                        initialDate: selectedDate,
                        firstDate: DateTime(2020),
                        lastDate: DateTime.now().add(const Duration(days: 1)),
                      );
                      if (date != null) setState(() => selectedDate = date);
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: progressCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Maendeleo (%)' : 'Progress (%)',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: nextStepsCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Hatua zijazo' : 'Next Steps',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: challengesCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Changamoto' : 'Challenges',
                      filled: true,
                      fillColor: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (!formKey.currentState!.validate()) return;
                        try {
                          final api = ref.read(apiClientProvider);
                          await api.put(
                            '/site-daily-reports/$reportId',
                            data: {
                              'report_date': DateFormat(
                                'yyyy-MM-dd',
                              ).format(selectedDate),
                              'site_id': siteCtrl.text,
                              'progress_percentage':
                                  int.tryParse(progressCtrl.text) ?? 0,
                              'next_steps': nextStepsCtrl.text,
                              'challenges': challengesCtrl.text,
                            },
                          );
                          ref.invalidate(_siteReportsProvider);
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
    if (reportId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSwahili ? 'Hitilafu' : 'Error'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Thibitisha' : 'Confirm'),
        content: Text(
          isSwahili ? 'Unataka kufuta ripoti hii?' : 'Delete this report?',
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
      ref.invalidate(_siteReportsProvider);
      if (context.mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imefutwa' : 'Deleted'),
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

  Color _getStatusColor(String status) {
    switch (status) {
      case 'APPROVED':
        return const Color(0xFF27AE60);
      case 'PENDING':
        return Colors.orange;
      case 'REJECTED':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}

class _ReportCard extends StatelessWidget {
  final Map<String, dynamic> report;
  final bool isDark, isSwahili;
  final VoidCallback onTap, onEdit, onDelete;
  const _ReportCard({
    required this.report,
    required this.isDark,
    required this.isSwahili,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  double _parseProgress(dynamic value) {
    if (value == null) return 0.0;
    if (value is num) return value / 100;
    if (value is String) {
      final parsed = double.tryParse(value);
      return parsed != null ? parsed / 100 : 0.0;
    }
    return 0.0;
  }

  @override
  Widget build(BuildContext context) {
    final status = report['status'] ?? '';
    final statusColor = status == 'APPROVED'
        ? const Color(0xFF27AE60)
        : (status == 'PENDING'
              ? Colors.orange
              : (status == 'REJECTED' ? Colors.red : Colors.grey));

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF2A2A3E) : Colors.white,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.assignment_rounded,
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
                        report['site']?['name'] ?? '-',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        report['report_date'] ?? '-',
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
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    status,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: statusColor,
                    ),
                  ),
                ),
                const Spacer(),
                GestureDetector(
                  onTap: onEdit,
                  child: Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Icon(Icons.edit, size: 14, color: Colors.white),
                  ),
                ),
                const SizedBox(width: 6),
                GestureDetector(
                  onTap: onDelete,
                  child: Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: Colors.red,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Icon(Icons.delete, size: 14, color: Colors.white),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Icon(
                  Icons.person_rounded,
                  size: 14,
                  color: isDark ? Colors.white38 : AppColors.textHint,
                ),
                const SizedBox(width: 4),
                Text(
                  report['supervisor']?['name'] ?? '-',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDark ? Colors.white54 : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: _parseProgress(report['progress_percentage']),
                      backgroundColor: isDark
                          ? Colors.white10
                          : Colors.grey[200],
                      valueColor: AlwaysStoppedAnimation(
                        const Color(0xFF27AE60),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  '${report['progress_percentage'] ?? 0}%',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: isDark ? Colors.white70 : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label, value;
  final bool isDark;
  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) => Padding(
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
              color: isDark ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ),
      ],
    ),
  );
}
