import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

class SiteVisitsFilter {
  final DateTime startDate;
  final DateTime endDate;
  final int? projectId;

  SiteVisitsFilter({
    required this.startDate,
    required this.endDate,
    this.projectId,
  });

  SiteVisitsFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    int? projectId,
    bool clearProject = false,
  }) {
    return SiteVisitsFilter(
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
      projectId: clearProject ? null : (projectId ?? this.projectId),
    );
  }

  Map<String, String> toQueryParams() {
    final params = <String, String>{
      'start_date': DateFormat('yyyy-MM-dd').format(startDate),
      'end_date': DateFormat('yyyy-MM-dd').format(endDate),
    };
    if (projectId != null) params['project_id'] = projectId.toString();
    return params;
  }
}

final siteVisitsFilterProvider = StateProvider<SiteVisitsFilter>((ref) {
  return SiteVisitsFilter(
    startDate: DateTime.now().subtract(const Duration(days: 30)),
    endDate: DateTime.now(),
  );
});

final _siteVisitsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(siteVisitsFilterProvider);
  final response = await api.get(
    '/site-visits',
    queryParameters: filter.toQueryParams(),
  );
  final data = response.data['data'];
  if (data is List) return data;
  if (data is Map && data['data'] is List) return data['data'] as List;
  return [];
});

final _siteVisitProjectsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/site-visits/projects');
  return response.data['data'] as List? ?? [];
});

final _siteVisitDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/site-visits/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class SiteVisitsScreen extends ConsumerWidget {
  const SiteVisitsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final visitsAsync = ref.watch(_siteVisitsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final filter = ref.watch(siteVisitsFilterProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Visiti za Shughuli' : 'Site Visits'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza Visit' : 'Add Visit',
            onPressed: () => _showVisitForm(context, ref),
          ),
          if (filter.projectId != null)
            IconButton(
              icon: const Icon(Icons.clear),
              onPressed: () =>
                  ref.read(siteVisitsFilterProvider.notifier).state = filter
                      .copyWith(clearProject: true),
            ),
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: () => _showFilterSheet(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_siteVisitsProvider),
        child: visitsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 48, color: Colors.grey),
                const SizedBox(height: 16),
                Text('$e'),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => ref.invalidate(_siteVisitsProvider),
                  child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                ),
              ],
            ),
          ),
          data: (visits) {
            if (visits.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.location_off, size: 64, color: Colors.grey[400]),
                  const SizedBox(height: 16),
                  Text(
                    isSwahili
                        ? 'Hakuna visiti zilizopatikana'
                        : 'No site visits found',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.grey[600], fontSize: 16),
                  ),
                ],
              );
            }
            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: visits.length,
              itemBuilder: (context, index) => _SiteVisitCard(
                visit: visits[index] as Map<String, dynamic>,
                isSwahili: isSwahili,
                onTap: () => _showVisitDetail(
                  context,
                  ref,
                  visits[index] as Map<String, dynamic>,
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  void _showFilterSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _FilterSheet(parentRef: ref),
    );
  }

  void _showVisitForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? visit,
  }) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _VisitFormSheet(visit: visit),
    ).then((result) {
      if (result == true) ref.invalidate(_siteVisitsProvider);
    });
  }

  void _showVisitDetail(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> visit,
  ) {
    final id = visit['id'] as int?;
    if (id == null) return;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _VisitDetailSheet(
        visitId: id,
        visitData: visit,
        onEdit: () {
          Navigator.pop(context);
          _showVisitForm(context, ref, visit: visit);
        },
        onDeleted: () {
          Navigator.pop(context);
          ref.invalidate(_siteVisitsProvider);
        },
      ),
    );
  }
}

Color getVisitStatusColor(String status) {
  switch (status.toUpperCase()) {
    case 'APPROVED':
      return const Color(0xFF27AE60);
    case 'SUBMITTED':
      return const Color(0xFF3498DB);
    case 'CREATED':
      return const Color(0xFFF39C12);
    case 'REJECTED':
      return const Color(0xFFE74C3C);
    default:
      return const Color(0xFF95A5A6);
  }
}

String getVisitStatusLabel(String status, bool isSwahili) {
  switch (status.toUpperCase()) {
    case 'APPROVED':
      return isSwahili ? 'IMEDHINISHWA' : 'APPROVED';
    case 'SUBMITTED':
      return isSwahili ? 'IMEWASILISHWA' : 'SUBMITTED';
    case 'CREATED':
      return isSwahili ? 'IMEUNDWA' : 'CREATED';
    case 'REJECTED':
      return isSwahili ? 'IMEKATALIWA' : 'REJECTED';
    default:
      return status;
  }
}

class _SiteVisitCard extends StatelessWidget {
  final Map<String, dynamic> visit;
  final bool isSwahili;
  final VoidCallback onTap;

  const _SiteVisitCard({
    required this.visit,
    required this.isSwahili,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final project = visit['project'] as Map<String, dynamic>?;
    final projectName =
        project?['project_name'] as String? ??
        project?['name'] as String? ??
        '-';
    final status = visit['status'] as String? ?? 'CREATED';
    final visitDate =
        visit['visit_date'] as String? ?? visit['date'] as String? ?? '';
    final statusColor = getVisitStatusColor(status);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: onTap,
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
                      color: statusColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      Icons.location_on,
                      color: statusColor,
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          projectName,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            Icon(
                              Icons.calendar_today,
                              size: 14,
                              color: Colors.grey[500],
                            ),
                            const SizedBox(width: 4),
                            Text(
                              _formatDate(visitDate),
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      getVisitStatusLabel(status, isSwahili),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),
              if (visit['description'] != null &&
                  (visit['description'] as String).isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  visit['description'] as String,
                  style: TextStyle(fontSize: 13, color: Colors.grey[600]),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(String date) {
    if (date.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date;
    }
  }
}

class _FilterSheet extends ConsumerWidget {
  final WidgetRef parentRef;

  const _FilterSheet({required this.parentRef});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final filter = ref.watch(siteVisitsFilterProvider);
    final projectsAsync = ref.watch(_siteVisitProjectsProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 42,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 18),
          Text(
            isSwahili ? 'Chuja Visit' : 'Filter Visits',
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 24),
          Text(
            isSwahili ? 'Mradi' : 'Project',
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: Colors.grey[100],
              borderRadius: BorderRadius.circular(8),
            ),
            child: projectsAsync.when(
              loading: () => const Padding(
                padding: EdgeInsets.all(16),
                child: CircularProgressIndicator(),
              ),
              error: (_, __) => Text(isSwahili ? 'Imeshindikana' : 'Failed'),
              data: (projects) => DropdownButtonHideUnderline(
                child: DropdownButton<int?>(
                  value: filter.projectId,
                  hint: Text(isSwahili ? 'Chagua Mradi' : 'Select Project'),
                  isExpanded: true,
                  items: [
                    DropdownMenuItem(
                      value: null,
                      child: Text(isSwahili ? 'Zote' : 'All'),
                    ),
                    ...(projects as List).map(
                      (p) => DropdownMenuItem(
                        value: p['id'] as int,
                        child: Text(p['project_name'] as String? ?? '-'),
                      ),
                    ),
                  ],
                  onChanged: (v) {
                    parentRef.read(siteVisitsFilterProvider.notifier).state =
                        filter.copyWith(projectId: v, clearProject: v == null);
                  },
                ),
              ),
            ),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => Navigator.pop(context),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1ABC9C),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(isSwahili ? 'Onyesha Matokeo' : 'Show Results'),
            ),
          ),
        ],
      ),
    );
  }
}

class _VisitDetailSheet extends ConsumerWidget {
  final int visitId;
  final Map<String, dynamic>? visitData;
  final VoidCallback? onEdit;
  final VoidCallback? onDeleted;

  const _VisitDetailSheet({
    required this.visitId,
    this.visitData,
    this.onEdit,
    this.onDeleted,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detailAsync = ref.watch(_siteVisitDetailProvider(visitId));
    final isSwahili = ref.watch(isSwahiliProvider);

    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: detailAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (visit) {
          final project = visit['project'] as Map<String, dynamic>?;
          final projectName = project?['project_name'] as String? ?? '-';
          final status = visit['status'] as String? ?? 'CREATED';
          final statusColor = getVisitStatusColor(status);

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
                        color: Colors.grey[300],
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            isSwahili ? 'Maelezo ya Visit' : 'Visit Details',
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        if (_canEditOrDelete(status)) ...[
                          IconButton(
                            icon: const Icon(
                              Icons.edit,
                              color: Color(0xFF1ABC9C),
                            ),
                            onPressed: onEdit,
                            tooltip: isSwahili ? 'Hariri' : 'Edit',
                          ),
                          IconButton(
                            icon: const Icon(Icons.delete, color: Colors.red),
                            onPressed: () => _showDeleteDialog(context, ref),
                            tooltip: isSwahili ? 'Futa' : 'Delete',
                          ),
                        ],
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
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: statusColor.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Icon(
                              Icons.location_on,
                              color: statusColor,
                              size: 28,
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  projectName,
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 10,
                                    vertical: 4,
                                  ),
                                  decoration: BoxDecoration(
                                    color: statusColor.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(20),
                                  ),
                                  child: Text(
                                    getVisitStatusLabel(status, isSwahili),
                                    style: TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                      color: statusColor,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    _DetailRow(
                      label: isSwahili ? 'Tarehe ya Visit' : 'Visit Date',
                      value: _formatDate(
                        visit['visit_date'] as String? ??
                            visit['date'] as String? ??
                            '',
                      ),
                      icon: Icons.calendar_today,
                    ),
                    if (visit['location'] != null)
                      _DetailRow(
                        label: isSwahili ? 'Mahali' : 'Location',
                        value: visit['location'] as String,
                        icon: Icons.location_on,
                      ),
                    if (visit['description'] != null &&
                        (visit['description'] as String).isNotEmpty)
                      _DetailRow(
                        label: isSwahili ? 'Maelezo' : 'Description',
                        value: visit['description'] as String,
                        icon: Icons.description,
                      ),
                    if (visit['findings'] != null &&
                        (visit['findings'] as String).isNotEmpty)
                      _DetailRow(
                        label: isSwahili ? 'Ugunduzi' : 'Findings',
                        value: visit['findings'] as String,
                        icon: Icons.search,
                      ),
                    if (visit['recommendations'] != null &&
                        (visit['recommendations'] as String).isNotEmpty)
                      _DetailRow(
                        label: isSwahili ? 'Mapendekezo' : 'Recommendations',
                        value: visit['recommendations'] as String,
                        icon: Icons.thumb_up,
                      ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  String _formatDate(String date) {
    if (date.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date;
    }
  }

  bool _canEditOrDelete(String status) {
    final s = status.toUpperCase();
    return s.isEmpty || s == 'CREATED' || s == 'DRAFT' || s == 'REJECTED';
  }

  Future<void> _showDeleteDialog(BuildContext context, WidgetRef ref) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete'),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta visit hii?'
              : 'Are you sure you want to delete this visit?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final api = ref.read(apiClientProvider);
        await api.delete('/site-visits/$visitId');
        if (context.mounted) {
          onDeleted?.call();
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
          );
        }
      }
    }
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFF1ABC9C).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 20, color: const Color(0xFF1ABC9C)),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _VisitFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? visit;

  const _VisitFormSheet({this.visit});

  @override
  ConsumerState<_VisitFormSheet> createState() => _VisitFormSheetState();
}

class _VisitFormSheetState extends ConsumerState<_VisitFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _descriptionController = TextEditingController();
  final _findingsController = TextEditingController();
  final _recommendationsController = TextEditingController();
  final _locationController = TextEditingController();
  int? _selectedProjectId;
  DateTime _visitDate = DateTime.now();
  bool _loading = false;
  late final bool _isEditing;
  int? _visitId;

  @override
  void initState() {
    super.initState();
    _isEditing = widget.visit != null;
    if (_isEditing) {
      final v = widget.visit!;
      _visitId = v['id'] as int?;
      final project = v['project'] as Map<String, dynamic>?;
      _selectedProjectId = project?['id'] as int? ?? v['project_id'] as int?;
      _visitDate = _parseDate(
        v['visit_date'] as String? ?? v['date'] as String?,
      );
      _locationController.text = v['location'] as String? ?? '';
      _descriptionController.text = v['description'] as String? ?? '';
      _findingsController.text = v['findings'] as String? ?? '';
      _recommendationsController.text = v['recommendations'] as String? ?? '';
    }
  }

  DateTime _parseDate(String? date) {
    if (date == null || date.isEmpty) return DateTime.now();
    try {
      return DateTime.parse(date);
    } catch (_) {
      return DateTime.now();
    }
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _findingsController.dispose();
    _recommendationsController.dispose();
    _locationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final projectsAsync = ref.watch(_siteVisitProjectsProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        top: false,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  _isEditing
                      ? (isSwahili ? 'Hariri Visit' : 'Edit Site Visit')
                      : (isSwahili ? 'Visit Mpya' : 'New Site Visit'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  isSwahili ? 'Mradi *' : 'Project *',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: projectsAsync.when(
                    loading: () => const Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                    error: (_, __) =>
                        Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                    data: (projects) => DropdownButtonHideUnderline(
                      child: DropdownButton<int?>(
                        value: _selectedProjectId,
                        hint: Text(
                          isSwahili ? 'Chagua Mradi' : 'Select Project',
                        ),
                        isExpanded: true,
                        items: (projects as List)
                            .map(
                              (p) => DropdownMenuItem(
                                value: p['id'] as int,
                                child: Text(
                                  p['project_name'] as String? ?? '-',
                                ),
                              ),
                            )
                            .toList(),
                        onChanged: (v) =>
                            setState(() => _selectedProjectId = v),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili ? 'Tarehe ya Visit *' : 'Visit Date *',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                InkWell(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _visitDate,
                      firstDate: DateTime(2020),
                      lastDate: DateTime.now().add(const Duration(days: 365)),
                    );
                    if (picked != null) setState(() => _visitDate = picked);
                  },
                  child: Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today),
                        const SizedBox(width: 12),
                        Text(DateFormat('dd MMM yyyy').format(_visitDate)),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _locationController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Mahali' : 'Location',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.location_on),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Maelezo' : 'Description',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.description),
                  ),
                  maxLines: 2,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _findingsController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Ugunduzi' : 'Findings',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.search),
                  ),
                  maxLines: 2,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _recommendationsController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Mapendekezo' : 'Recommendations',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.thumb_up),
                  ),
                  maxLines: 2,
                ),
                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _loading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1ABC9C),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: _loading
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            isSwahili ? 'Hifadhi' : 'Save',
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
      ),
    );
  }

  Future<void> _submit() async {
    if (_selectedProjectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider) ? 'Chagua mradi' : 'Select a project',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'project_id': _selectedProjectId,
        'visit_date': DateFormat('yyyy-MM-dd').format(_visitDate),
        'location': _locationController.text.trim().isEmpty
            ? null
            : _locationController.text.trim(),
        'description': _descriptionController.text.trim().isEmpty
            ? null
            : _descriptionController.text.trim(),
        'findings': _findingsController.text.trim().isEmpty
            ? null
            : _findingsController.text.trim(),
        'recommendations': _recommendationsController.text.trim().isEmpty
            ? null
            : _recommendationsController.text.trim(),
      };

      if (_isEditing && _visitId != null) {
        await api.put('/site-visits/$_visitId', data: data);
      } else {
        await api.post('/site-visits', data: data);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}
