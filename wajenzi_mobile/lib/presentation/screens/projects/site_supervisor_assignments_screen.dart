import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _assignmentsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _assignmentsFilterProvider = StateProvider<AssignmentsFilter>((ref) {
  return AssignmentsFilter();
});

final _assignmentsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(_assignmentsFilterProvider);

  try {
    final response = await api.get(
      '/site-supervisor-assignments',
      queryParameters: {
        if (filter.siteId != null) 'site_id': filter.siteId.toString(),
        if (filter.supervisorId != null)
          'supervisor_id': filter.supervisorId.toString(),
      },
    );

    final data = response.data['data'] is Map
        ? Map<String, dynamic>.from(response.data['data'] as Map)
        : <String, dynamic>{};

    return {
      'assignments': (data['assignments'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList(),
      'unassigned_sites': (data['unassigned_sites'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList(),
      'sites': (data['sites'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList(),
      'supervisors': (data['supervisors'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList(),
      'stats': data['stats'] is Map
          ? Map<String, dynamic>.from(data['stats'] as Map)
          : const <String, dynamic>{},
      'unavailable_on_live': false,
    };
  } on DioException catch (error) {
    if ((error.response?.statusCode ?? 0) == 404) {
      return {
        'assignments': const <Map<String, dynamic>>[],
        'unassigned_sites': const <Map<String, dynamic>>[],
        'sites': const <Map<String, dynamic>>[],
        'supervisors': const <Map<String, dynamic>>[],
        'stats': const <String, dynamic>{},
        'unavailable_on_live': true,
      };
    }
    rethrow;
  }
});

class AssignmentsFilter {
  final int? siteId;
  final int? supervisorId;

  AssignmentsFilter({this.siteId, this.supervisorId});

  AssignmentsFilter copyWith({
    int? siteId,
    int? supervisorId,
    bool clearSite = false,
    bool clearSupervisor = false,
  }) {
    return AssignmentsFilter(
      siteId: clearSite ? null : (siteId ?? this.siteId),
      supervisorId: clearSupervisor
          ? null
          : (supervisorId ?? this.supervisorId),
    );
  }
}

class SiteSupervisorAssignmentsScreen extends ConsumerStatefulWidget {
  const SiteSupervisorAssignmentsScreen({super.key});

  @override
  ConsumerState<SiteSupervisorAssignmentsScreen> createState() =>
      _SiteSupervisorAssignmentsScreenState();
}

class _SiteSupervisorAssignmentsScreenState
    extends ConsumerState<SiteSupervisorAssignmentsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_assignmentsProvider);
    final search = ref.watch(_assignmentsSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili ? 'Makabidhi ya Wasimamizi' : 'Supervisor Assignments',
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showCreateForm(context, ref, isDark, isSwahili),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza Kabidhi' : 'Assign Supervisor',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_assignmentsProvider.future),
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
                          ref.read(_assignmentsSearchProvider.notifier).state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta makabidhi...'
                            : 'Search assignments...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _assignmentsSearchProvider
                                                  .notifier,
                                            )
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
                    dataAsync.when(
                      loading: () => const SizedBox.shrink(),
                      error: (_, __) => const SizedBox.shrink(),
                      data: (data) => _FilterBar(
                        sites:
                            (data['sites'] as List?)
                                ?.cast<Map<String, dynamic>>() ??
                            [],
                        supervisors:
                            (data['supervisors'] as List?)
                                ?.cast<Map<String, dynamic>>() ??
                            [],
                        isDark: isDark,
                        isSwahili: isSwahili,
                        onFilterChanged: (filter) =>
                            ref
                                    .read(_assignmentsFilterProvider.notifier)
                                    .state =
                                filter,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            dataAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _ErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_assignmentsProvider),
                ),
              ),
              data: (data) {
                if (data['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
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
                              isSwahili
                                  ? 'Site Supervisor Assignments haipatikani kwenye live API kwa sasa.'
                                  : 'Site Supervisor Assignments is not available on the live API right now.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[700],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }

                final unassignedSites =
                    (data['unassigned_sites'] as List?)
                        ?.cast<Map<String, dynamic>>() ??
                    [];
                final stats = data['stats'] as Map<String, dynamic>? ?? {};

                final assignments =
                    (data['assignments'] as List?)
                        ?.cast<Map<String, dynamic>>() ??
                    [];

                final filteredAssignments = search.isEmpty
                    ? assignments
                    : assignments.where((a) {
                        final haystack = [
                          a['supervisor_name'] ?? '',
                          a['site_name'] ?? '',
                          a['site_location'] ?? '',
                          a['assigned_by_name'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (filteredAssignments.isEmpty) {
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
                            assignments.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna makabidhi'
                                      : 'No assignments found')
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
                                            _assignmentsSearchProvider.notifier,
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
                    delegate: SliverChildListDelegate([
                      if (unassignedSites.isNotEmpty) ...[
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.orange.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: Colors.orange.withValues(alpha: 0.3),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(
                                    Icons.warning_rounded,
                                    color: Colors.orange,
                                    size: 20,
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    isSwahili
                                        ? 'Maeneo Yasiyobidhiwa'
                                        : 'Unassigned Sites',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w600,
                                      color: Colors.orange,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              ...unassignedSites
                                  .take(3)
                                  .map(
                                    (site) => Padding(
                                      padding: const EdgeInsets.symmetric(
                                        vertical: 2,
                                      ),
                                      child: Text(
                                        '• ${site['name'] ?? site['location'] ?? '-'}',
                                        style: TextStyle(
                                          fontSize: 13,
                                          color: isDark
                                              ? Colors.white70
                                              : Colors.black87,
                                        ),
                                      ),
                                    ),
                                  ),
                              if (unassignedSites.length > 3)
                                Text(
                                  '+${unassignedSites.length - 3} ${isSwahili ? 'zaidi' : 'more'}',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    color: Colors.orange,
                                  ),
                                ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.assignment_ind_rounded,
                              size: 16,
                              color: Color(0xFF3B82F6),
                            ),
                            const SizedBox(width: 4),
                            Text(
                              '${stats['total'] ?? filteredAssignments.length} ${isSwahili ? 'Makabidhi' : 'Assignments'}',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF3B82F6),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      ...filteredAssignments.map(
                        (a) => _AssignmentCard(
                          assignment: a,
                          isDark: isDark,
                          isSwahili: isSwahili,
                          onTap: () =>
                              _showDetail(context, ref, a, isDark, isSwahili),
                          onEdit: () =>
                              _showEditForm(context, ref, a, isDark, isSwahili),
                          onEnd: () =>
                              _endAssignment(context, ref, a, isSwahili),
                        ),
                      ),
                    ]),
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
    Map<String, dynamic> assignment,
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
        initialChildSize: 0.6,
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
                      assignment['site_name'] ?? 'Assignment Details',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                  ),
                  if (assignment['is_active'] == 1) ...[
                    IconButton(
                      onPressed: () {
                        Navigator.pop(ctx);
                        _showEditForm(
                          context,
                          ref,
                          assignment,
                          isDark,
                          isSwahili,
                        );
                      },
                      icon: Icon(Icons.edit_rounded, color: AppColors.primary),
                    ),
                    IconButton(
                      onPressed: () {
                        Navigator.pop(ctx);
                        _endAssignment(context, ref, assignment, isSwahili);
                      },
                      icon: const Icon(Icons.delete_rounded, color: Colors.red),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 16),
              _DetailRow(
                label: isSwahili ? 'Eneo' : 'Site',
                value:
                    '${assignment['site_name'] ?? '-'} - ${assignment['site_location'] ?? ''}',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Msimamizi' : 'Supervisor',
                value: assignment['supervisor_name'] ?? '-',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Aliyebidhi' : 'Assigned By',
                value: assignment['assigned_by_name'] ?? '-',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                value: assignment['assigned_from'] ?? '-',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Tarehe ya Kumalizia' : 'End Date',
                value:
                    assignment['assigned_to'] ??
                    (isSwahili ? 'Inaendelea' : 'Ongoing'),
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Muda' : 'Duration',
                value:
                    '${assignment['duration_days'] ?? 0} ${isSwahili ? 'siku' : 'days'}',
                isDark: isDark,
              ),
              _DetailRow(
                label: isSwahili ? 'Hali' : 'Status',
                value: assignment['is_active'] == 1
                    ? (isSwahili ? 'Hai' : 'Active')
                    : (isSwahili ? 'Haifai' : 'Inactive'),
                isDark: isDark,
                valueColor: assignment['is_active'] == 1
                    ? const Color(0xFF27AE60)
                    : Colors.grey,
              ),
              if (assignment['notes'] != null &&
                  (assignment['notes'] as String).isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  isSwahili ? 'Maelezo' : 'Notes',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDark ? Colors.white54 : AppColors.textHint,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  assignment['notes'],
                  style: TextStyle(
                    fontSize: 14,
                    color: isDark ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ],
              if (assignment['is_active'] == 1) ...[
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.pop(ctx);
                          _showEditForm(
                            context,
                            ref,
                            assignment,
                            isDark,
                            isSwahili,
                          );
                        },
                        icon: const Icon(Icons.edit),
                        label: Text(isSwahili ? 'Hariri' : 'Edit'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.primary,
                          side: const BorderSide(color: AppColors.primary),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: () {
                          Navigator.pop(ctx);
                          _endAssignment(context, ref, assignment, isSwahili);
                        },
                        icon: const Icon(Icons.delete),
                        label: Text(isSwahili ? 'Malizia' : 'End'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
              const SizedBox(height: 20),
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
    final data = ref.read(_assignmentsProvider).valueOrNull;
    if (data == null) return;

    final availableSites =
        (data['unassigned_sites'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final supervisors =
        (data['supervisors'] as List?)?.cast<Map<String, dynamic>>() ?? [];

    if (availableSites.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Hakuna maeneo yasiyobidhiwa'
                : 'No unassigned sites available',
          ),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    int? selectedSiteId;
    int? selectedSupervisorId;
    final notesCtrl = TextEditingController();
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
                    isSwahili ? 'Ongeza Kabidhi' : 'Assign Supervisor',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<int>(
                    value: selectedSiteId,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Eneo *' : 'Site *',
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
                      ),
                    ),
                    dropdownColor: isDark
                        ? const Color(0xFF1A2332)
                        : Colors.white,
                    items: availableSites
                        .map<DropdownMenuItem<int>>(
                          (s) => DropdownMenuItem(
                            value: s['id'],
                            child: Text(
                              '${s['name']} - ${s['location'] ?? ''}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() => selectedSiteId = v),
                    validator: (v) => v == null ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    value: selectedSupervisorId,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Msimamizi *' : 'Supervisor *',
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
                      ),
                    ),
                    dropdownColor: isDark
                        ? const Color(0xFF1A2332)
                        : Colors.white,
                    items: supervisors
                        .map<DropdownMenuItem<int>>(
                          (s) => DropdownMenuItem(
                            value: s['id'],
                            child: Text(
                              '${s['name']}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (v) => setState(() => selectedSupervisorId = v),
                    validator: (v) => v == null ? 'Required' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: notesCtrl,
                    maxLines: 2,
                    style: TextStyle(
                      fontSize: 13,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Maelezo' : 'Notes',
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
                          final today = DateTime.now();
                          final dateStr =
                              '${today.year}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
                          await api.post(
                            '/site-supervisor-assignments',
                            data: {
                              'site_id': selectedSiteId,
                              'user_id': selectedSupervisorId,
                              'assigned_from': dateStr,
                              'notes': notesCtrl.text,
                            },
                          );
                          ref.invalidate(_assignmentsProvider);
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
                        } on DioException catch (e) {
                          if (ctx.mounted) {
                            final msg =
                                e.response?.data?['message'] ??
                                (isSwahili ? 'Hitilafu' : 'Error');
                            ScaffoldMessenger.of(ctx).showSnackBar(
                              SnackBar(
                                content: Text(msg),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
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
    Map<String, dynamic> assignment,
    bool isDark,
    bool isSwahili,
  ) async {
    final assignmentId = assignment['id'];
    if (assignmentId == null || assignmentId == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Hitilafu: Hatuna ID ya kabidhi'
                : 'Error: No assignment ID',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final notesCtrl = TextEditingController(text: assignment['notes'] ?? '');
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
                    isSwahili ? 'Hariri Kabidhi' : 'Edit Assignment',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isDark
                          ? const Color(0xFF0F1923)
                          : Colors.grey.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          assignment['site_name'] ?? '-',
                          style: TextStyle(
                            fontWeight: FontWeight.w600,
                            color: isDark
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                        Text(
                          assignment['supervisor_name'] ?? '-',
                          style: TextStyle(
                            fontSize: 13,
                            color: isDark
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: notesCtrl,
                    maxLines: 2,
                    style: TextStyle(
                      fontSize: 13,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Maelezo' : 'Notes',
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
                            '/site-supervisor-assignments/$assignmentId',
                            data: {'notes': notesCtrl.text},
                          );
                          ref.invalidate(_assignmentsProvider);
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
                        } on DioException catch (e) {
                          if (ctx.mounted) {
                            final msg =
                                e.response?.data?['message'] ??
                                (isSwahili ? 'Hitilafu' : 'Error');
                            ScaffoldMessenger.of(ctx).showSnackBar(
                              SnackBar(
                                content: Text(msg),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
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

  Future<void> _endAssignment(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> assignment,
    bool isSwahili,
  ) async {
    final assignmentId = assignment['id'];
    if (assignmentId == null || assignmentId == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Hitilafu: Hatuna ID ya kabidhi'
                : 'Error: No assignment ID',
          ),
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
          isSwahili
              ? 'Unataka kumalizia kabidhi hii?'
              : 'Are you sure you want to end this assignment?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Hapana' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: Colors.orange),
            child: Text(isSwahili ? 'Malizia' : 'End'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      await api.delete('/site-supervisor-assignments/$assignmentId');
      ref.invalidate(_assignmentsProvider);
      if (context.mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imemalizika' : 'Assignment ended'),
            backgroundColor: Colors.green,
          ),
        );
    } on DioException catch (e) {
      if (context.mounted) {
        final msg =
            e.response?.data?['message'] ?? (isSwahili ? 'Hitilafu' : 'Error');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(msg), backgroundColor: Colors.red),
        );
      }
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

class _FilterBar extends ConsumerWidget {
  final List<Map<String, dynamic>> sites;
  final List<Map<String, dynamic>> supervisors;
  final bool isDark;
  final bool isSwahili;
  final ValueChanged<AssignmentsFilter> onFilterChanged;

  const _FilterBar({
    required this.sites,
    required this.supervisors,
    required this.isDark,
    required this.isSwahili,
    required this.onFilterChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filter = ref.watch(_assignmentsFilterProvider);

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF2A2A3E) : Colors.white,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili ? 'Vichujio' : 'Filters',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDark ? Colors.white54 : AppColors.textHint,
            ),
          ),
          const SizedBox(height: 8),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: isDark
                        ? const Color(0xFF0F1923)
                        : Colors.grey.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: DropdownButton<int?>(
                    value: filter.siteId,
                    hint: Text(
                      isSwahili ? 'Eneo' : 'Site',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDark ? Colors.white54 : AppColors.textHint,
                      ),
                    ),
                    underline: const SizedBox(),
                    dropdownColor: isDark
                        ? const Color(0xFF1A2332)
                        : Colors.white,
                    items: [
                      DropdownMenuItem(
                        value: null,
                        child: Text(isSwahili ? 'Zote' : 'All'),
                      ),
                      ...sites.map(
                        (s) => DropdownMenuItem(
                          value: s['id'],
                          child: Text(
                            '${s['name']}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ],
                    onChanged: (v) => onFilterChanged(
                      filter.copyWith(siteId: v, clearSite: v == null),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: isDark
                        ? const Color(0xFF0F1923)
                        : Colors.grey.withValues(alpha: 0.05),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: DropdownButton<int?>(
                    value: filter.supervisorId,
                    hint: Text(
                      isSwahili ? 'Msimamizi' : 'Supervisor',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDark ? Colors.white54 : AppColors.textHint,
                      ),
                    ),
                    underline: const SizedBox(),
                    dropdownColor: isDark
                        ? const Color(0xFF1A2332)
                        : Colors.white,
                    items: [
                      DropdownMenuItem(
                        value: null,
                        child: Text(isSwahili ? 'Zote' : 'All'),
                      ),
                      ...supervisors.map(
                        (s) => DropdownMenuItem(
                          value: s['id'],
                          child: Text(
                            '${s['name']}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ],
                    onChanged: (v) => onFilterChanged(
                      filter.copyWith(
                        supervisorId: v,
                        clearSupervisor: v == null,
                      ),
                    ),
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

class _AssignmentCard extends StatelessWidget {
  final Map<String, dynamic> assignment;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onEnd;

  const _AssignmentCard({
    required this.assignment,
    required this.isDark,
    required this.isSwahili,
    required this.onTap,
    required this.onEdit,
    required this.onEnd,
  });

  @override
  Widget build(BuildContext context) {
    final isActive = assignment['is_active'] == 1;

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
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFF10B981).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.person_pin_rounded,
                    size: 20,
                    color: Color(0xFF10B981),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        assignment['supervisor_name'] ?? '-',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      Row(
                        children: [
                          Icon(
                            Icons.location_on_rounded,
                            size: 12,
                            color: isDark ? Colors.white38 : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              assignment['site_name'] ?? '-',
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
                    color: (isActive ? const Color(0xFF27AE60) : Colors.grey)
                        .withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    isActive
                        ? (isSwahili ? 'Hai' : 'Active')
                        : (isSwahili ? 'Haifai' : 'Inactive'),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: isActive ? const Color(0xFF27AE60) : Colors.grey,
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
                        Icons.calendar_today_rounded,
                        size: 14,
                        color: isDark ? Colors.white38 : AppColors.textHint,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        '${assignment['assigned_from'] ?? '-'}',
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
                        Icons.timelapse_rounded,
                        size: 12,
                        color: Color(0xFF3B82F6),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        '${assignment['duration_days'] ?? 0}d',
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
            if (isActive) ...[
              const SizedBox(height: 12),
              const Divider(height: 1),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: TextButton.icon(
                      onPressed: onEdit,
                      icon: const Icon(Icons.edit, size: 16),
                      label: Text(
                        isSwahili ? 'Hariri' : 'Edit',
                        style: const TextStyle(fontSize: 12),
                      ),
                      style: TextButton.styleFrom(
                        foregroundColor: AppColors.primary,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                      ),
                    ),
                  ),
                  Container(
                    width: 1,
                    height: 24,
                    color: isDark
                        ? Colors.white24
                        : Colors.grey.withValues(alpha: 0.2),
                  ),
                  Expanded(
                    child: TextButton.icon(
                      onPressed: onEnd,
                      icon: const Icon(
                        Icons.delete_outline,
                        size: 16,
                        color: Colors.red,
                      ),
                      label: Text(
                        isSwahili ? 'Malizia' : 'End',
                        style: const TextStyle(fontSize: 12, color: Colors.red),
                      ),
                      style: TextButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
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
          const SizedBox(height: 8),
          Text(
            '$error',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.grey[600]),
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
