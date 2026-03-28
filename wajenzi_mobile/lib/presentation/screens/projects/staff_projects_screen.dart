import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';

final _filtersProvider =
    StateProvider.autoDispose<Map<String, dynamic>>((ref) => {});
final _searchProvider = StateProvider.autoDispose<String>((ref) => '');

final _referenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/projects/reference-data');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

final _statsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/projects/stats');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

final _projectsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final filters = ref.watch(_filtersProvider);
      final response = await api.get('/projects', queryParameters: filters);
      final payload = response.data['data'];
      final collection = payload is Map<String, dynamic> ? payload : null;
      return {
        'items': (collection?['data'] ?? const []) as List,
        'meta': collection?['meta'] as Map<String, dynamic>? ?? {},
      };
    });

final _projectDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/projects/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class StaffProjectsScreen extends ConsumerWidget {
  const StaffProjectsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final projectsAsync = ref.watch(_projectsProvider);
    final statsAsync = ref.watch(_statsProvider);
    final referenceAsync = ref.watch(_referenceProvider);
    final search = ref.watch(_searchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: const Text('Projects'),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showProjectForm(context, ref),
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_projectsProvider);
          ref.invalidate(_statsProvider);
          ref.invalidate(_referenceProvider);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: statsAsync.when(
                loading: () => const SizedBox.shrink(),
                error: (_, __) => const SizedBox.shrink(),
                data: (stats) => _StatsRow(stats: stats),
              ),
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                child: referenceAsync.when(
                  loading: () => const SizedBox.shrink(),
                  error: (_, __) => const SizedBox.shrink(),
                  data: (reference) => _Filters(reference: reference),
                ),
              ),
            ),
            projectsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: Center(child: Text('$e')),
              ),
              data: (payload) {
                final allItems =
                    (payload['items'] as List).cast<Map<String, dynamic>>();
                final items = search.isEmpty
                    ? allItems
                    : allItems.where((project) {
                        final haystack = [
                          project['document_number'],
                          project['project_name'],
                          (project['client'] as Map<String, dynamic>?)?['name'],
                          (project['project_type'] as Map<String, dynamic>?)?['name'],
                          (project['service_type'] as Map<String, dynamic>?)?['name'],
                          project['status'],
                          project['approval_status'],
                          project['approval_summary'],
                          (project['salesperson'] as Map<String, dynamic>?)?['name'],
                          (project['project_manager'] as Map<String, dynamic>?)?['name'],
                        ].whereType<Object>().join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();
                if (items.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text('No projects found'),
                            if (search.isNotEmpty) ...[
                              const SizedBox(height: 12),
                              ElevatedButton.icon(
                                onPressed: () =>
                                    ref.read(_searchProvider.notifier).state = '',
                                icon: const Icon(Icons.arrow_back_rounded),
                                label: const Text('Back'),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  );
                }
                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final project = items[index];
                      return _ProjectCard(
                        project: project,
                        onView: () => _showProjectDetails(context, project['id'] as int),
                        onEdit: () => _showProjectForm(context, ref, project: project),
                        onDelete: () => _deleteProject(context, ref, project),
                      );
                    }, childCount: items.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showProjectForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? project,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProjectFormSheet(project: project),
    );
    if (result == true) {
      ref.invalidate(_projectsProvider);
      ref.invalidate(_statsProvider);
    }
  }

  void _showProjectDetails(BuildContext context, int id) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProjectDetailSheet(projectId: id),
    );
  }

  Future<void> _deleteProject(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> project,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Project'),
        content: Text(
          'Are you sure you want to delete ${project['project_name'] ?? 'this project'}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    final api = ref.read(apiClientProvider);
    await api.delete('/projects/${project['id']}');
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Project deleted'),
          backgroundColor: AppColors.success,
        ),
      );
    }
    ref.invalidate(_projectsProvider);
    ref.invalidate(_statsProvider);
  }
}

class _StatsRow extends StatelessWidget {
  final Map<String, dynamic> stats;
  const _StatsRow({required this.stats});

  @override
  Widget build(BuildContext context) {
    final cards = [
      ('Total', '${stats['total'] ?? 0}'),
      ('Active', '${stats['active'] ?? 0}'),
      ('Completed', '${stats['completed'] ?? 0}'),
      ('Delayed', '${stats['delayed'] ?? 0}'),
      ('Value', NumberFormat.compact().format(_toDouble(stats['total_value']))),
    ];
    return SizedBox(
      height: 110,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
        scrollDirection: Axis.horizontal,
        itemBuilder: (_, index) => Container(
          width: 120,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(14),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(cards[index].$2, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
              const SizedBox(height: 6),
              Text(cards[index].$1, style: const TextStyle(color: AppColors.textSecondary)),
            ],
          ),
        ),
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemCount: cards.length,
      ),
    );
  }
}

class _Filters extends ConsumerWidget {
  final Map<String, dynamic> reference;
  const _Filters({required this.reference});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filters = ref.watch(_filtersProvider);
    final search = ref.watch(_searchProvider);
    return ExpansionTile(
      title: const Text('All Projects'),
      subtitle: const Text('Filters'),
      initiallyExpanded: filters.isNotEmpty,
      childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      backgroundColor: Colors.white,
      collapsedBackgroundColor: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      collapsedShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextFormField(
            initialValue: search,
            onChanged: (value) => ref.read(_searchProvider.notifier).state = value,
            decoration: const InputDecoration(
              labelText: 'Search',
              prefixIcon: Icon(Icons.search_rounded),
            ),
          ),
        ),
        _Drop<int>(
          label: 'Project Type',
          value: filters['project_type_id'] as int?,
          items: (reference['project_types'] as List? ?? const []).cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'project_type_id', value),
        ),
        _Drop<int>(
          label: 'Service Type',
          value: filters['service_type_id'] as int?,
          items: (reference['service_types'] as List? ?? const []).cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'service_type_id', value),
        ),
        _Drop<String>(
          label: 'Status',
          value: filters['status'] as String?,
          items: (reference['statuses'] as List? ?? const []).cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'status', value),
        ),
        _Drop<int>(
          label: 'Salesperson',
          value: filters['salesperson_id'] as int?,
          items: (reference['salespersons'] as List? ?? const []).cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'salesperson_id', value),
        ),
        _Drop<int>(
          label: 'Project Manager',
          value: filters['project_manager_id'] as int?,
          items: (reference['project_managers'] as List? ?? const []).cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'project_manager_id', value),
        ),
        OutlinedButton(
          onPressed: () {
            ref.read(_filtersProvider.notifier).state = {};
            ref.read(_searchProvider.notifier).state = '';
          },
          child: const Text('Clear'),
        ),
      ],
    );
  }

  void _set(WidgetRef ref, String key, Object? value) {
    final next = {...ref.read(_filtersProvider)};
    if (value == null || value == '') {
      next.remove(key);
    } else {
      next[key] = value;
    }
    ref.read(_filtersProvider.notifier).state = next;
  }
}

class _Drop<T> extends StatelessWidget {
  final String label;
  final T? value;
  final List<Map<String, dynamic>> items;
  final void Function(T?) onChanged;
  const _Drop({required this.label, required this.value, required this.items, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<T>(
        value: value,
        isExpanded: true,
        decoration: InputDecoration(labelText: label),
        items: [
          DropdownMenuItem<T>(
            value: null,
            child: const Text(
              'All',
              overflow: TextOverflow.ellipsis,
            ),
          ),
          ...items.map((item) => DropdownMenuItem<T>(
                value: item['id'] as T,
                child: Text(
                  item['name']?.toString() ?? '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              )),
        ],
        onChanged: onChanged,
      ),
    );
  }
}

class _ProjectCard extends StatelessWidget {
  final Map<String, dynamic> project;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ProjectCard({
    required this.project,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final status = (project['approval_status'] ?? project['status'] ?? 'pending').toString();
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
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          project['document_number']?.toString().isNotEmpty == true
                              ? project['document_number'].toString()
                              : 'PRJ-${project['id']}',
                          style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          project['project_name']?.toString() ?? '-',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      _StatusPill(status: status),
                      PopupMenuButton<String>(
                        onSelected: (value) {
                          if (value == 'view') onView();
                          if (value == 'edit') onEdit();
                          if (value == 'delete') onDelete();
                        },
                        itemBuilder: (_) => const [
                          PopupMenuItem(value: 'view', child: Text('View')),
                          PopupMenuItem(value: 'edit', child: Text('Edit')),
                          PopupMenuItem(value: 'delete', child: Text('Delete')),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _InfoLine('Client', (project['client'] as Map<String, dynamic>?)?['name']?.toString() ?? '-'),
              _InfoLine('Category', (project['project_type'] as Map<String, dynamic>?)?['name']?.toString() ?? '-'),
              _InfoLine('Service Type', (project['service_type'] as Map<String, dynamic>?)?['name']?.toString() ?? '-'),
              _InfoLine('Start Date', _formatDate(project['start_date']?.toString())),
              _InfoLine('Expected End', _formatDate(project['expected_end_date']?.toString())),
              _InfoLine('Actual End', _formatDate(project['actual_end_date']?.toString())),
              _InfoLine('Planned (Days)', _display(project['planned_duration'])),
              _InfoLine('Actual (Days)', _display(project['actual_duration'])),
              _InfoLine('Delay (Days)', _display(project['delay_days'])),
              _InfoLine('Contract Value', _money(project['contract_value'])),
              _InfoLine('Salesperson', (project['salesperson'] as Map<String, dynamic>?)?['name']?.toString() ?? '-'),
              _InfoLine('Project Manager', (project['project_manager'] as Map<String, dynamic>?)?['name']?.toString() ?? '-'),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProjectDetailSheet extends ConsumerStatefulWidget {
  final int projectId;
  const _ProjectDetailSheet({required this.projectId});

  @override
  ConsumerState<_ProjectDetailSheet> createState() => _ProjectDetailSheetState();
}

class _ProjectDetailSheetState extends ConsumerState<_ProjectDetailSheet> {
  Map<String, dynamic>? _detail;
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    final detailAsync = ref.watch(_projectDetailProvider(widget.projectId));
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: (_detail != null
                ? AsyncValue.data(_detail!)
                : detailAsync)
            .when(
          loading: () => const SizedBox(height: 320, child: Center(child: CircularProgressIndicator())),
          error: (e, _) => SizedBox(height: 320, child: Center(child: Text('$e'))),
          data: (project) => SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _SheetHeader(
                  title: 'Project',
                  onBack: () => Navigator.pop(context),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Project Details', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                      const SizedBox(height: 12),
                      ...(((project['project_details'] as Map<String, dynamic>?) ?? {}).entries
                          .map((entry) => _DetailLine(entry.key, entry.value?.toString() ?? '-'))),
                      if ((project['description']?.toString().isNotEmpty ?? false) &&
                          ((project['project_details'] as Map<String, dynamic>?)?['Description'] == null))
                        _DetailLine('Description', project['description'].toString()),
                      const SizedBox(height: 20),
                      const Text('Approval Flow', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                      const SizedBox(height: 12),
                      _DetailLine('', ((project['approval_flow'] as Map<String, dynamic>?)?['status_label']?.toString() ?? 'In Progress')),
                      const SizedBox(height: 12),
                      const Text('Approvals', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                      const SizedBox(height: 12),
                      Text(
                        ((project['approval_flow'] as Map<String, dynamic>?)?['message']?.toString() ?? ''),
                        style: const TextStyle(fontSize: 14, color: AppColors.textSecondary),
                      ),
                      if (_busy) ...[
                        const SizedBox(height: 12),
                        const LinearProgressIndicator(),
                      ],
                      const SizedBox(height: 12),
                      _ApprovalActions(
                        flow: (project['approval_flow'] as Map<String, dynamic>?) ?? const {},
                        busy: _busy,
                        onAction: (action) => _performAction(action),
                      ),
                      if ((((project['approval_flow'] as Map<String, dynamic>?)?['steps']) as List?)?.isNotEmpty ?? false) ...[
                        const SizedBox(height: 12),
                        ...(((project['approval_flow'] as Map<String, dynamic>)['steps'] as List)
                            .cast<Map<String, dynamic>>()
                            .map((step) => _ApprovalStepCard(step: step))),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _performAction(String action) async {
    String? comment;
    if (action == 'reject' || action == 'return') {
      comment = await _promptForComment(action == 'reject' ? 'Reject Request' : 'Return Request', required: true);
      if (comment == null || comment.trim().isEmpty) return;
    } else if (action == 'approve' || action == 'discard') {
      comment = await _promptForComment(action == 'approve' ? 'Approval Comment' : 'Discard Request', required: false);
      if (comment == null) return;
    }

    setState(() => _busy = true);
    try {
      final api = ref.read(apiClientProvider);
      late final response;
      switch (action) {
        case 'submit':
          response = await api.post('/projects/${widget.projectId}/submit');
          break;
        case 'approve':
          response = await api.post('/projects/${widget.projectId}/approve', data: {'comment': comment});
          break;
        case 'reject':
          response = await api.post('/projects/${widget.projectId}/reject', data: {'comment': comment});
          break;
        case 'return':
          response = await api.post('/projects/${widget.projectId}/return', data: {'comment': comment});
          break;
        case 'discard':
          response = await api.post('/projects/${widget.projectId}/discard', data: {'comment': comment});
          break;
        default:
          return;
      }
      final data = response.data['data'];
      if (data is Map<String, dynamic>) {
        setState(() => _detail = data);
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text((response.data['message'] ?? 'Success').toString()),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: AppColors.error),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<String?> _promptForComment(String title, {required bool required}) async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          maxLines: 4,
          decoration: InputDecoration(
            hintText: required ? 'Comment is required' : 'Optional comment',
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, null), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              final value = controller.text.trim();
              if (required && value.isEmpty) return;
              Navigator.pop(ctx, value);
            },
            child: const Text('Continue'),
          ),
        ],
      ),
    );
  }
}

class _ProjectFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? project;
  const _ProjectFormSheet({this.project});

  @override
  ConsumerState<_ProjectFormSheet> createState() => _ProjectFormSheetState();
}

class _ProjectFormSheetState extends ConsumerState<_ProjectFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _projectName;
  late final TextEditingController _description;
  late final TextEditingController _startDate;
  late final TextEditingController _expectedEndDate;
  late final TextEditingController _actualEndDate;
  late final TextEditingController _contractValue;
  int? _clientId;
  int? _projectTypeId;
  int? _serviceTypeId;
  int? _salespersonId;
  int? _projectManagerId;
  String? _priority;
  bool _loading = false;

  bool get _isNew => widget.project == null;

  @override
  void initState() {
    super.initState();
    final p = widget.project;
    _projectName = TextEditingController(text: p?['project_name']?.toString() ?? '');
    _description = TextEditingController(text: p?['description']?.toString() ?? '');
    _startDate = TextEditingController(text: p?['start_date']?.toString() ?? DateFormat('yyyy-MM-dd').format(DateTime.now()));
    _expectedEndDate = TextEditingController(text: p?['expected_end_date']?.toString() ?? DateFormat('yyyy-MM-dd').format(DateTime.now()));
    _actualEndDate = TextEditingController(text: p?['actual_end_date']?.toString() ?? '');
    _contractValue = TextEditingController(text: p?['contract_value']?.toString() ?? '');
    _clientId = _toInt((p?['client'] as Map<String, dynamic>?)?['id']);
    _projectTypeId = _toInt((p?['project_type'] as Map<String, dynamic>?)?['id']);
    _serviceTypeId = _toInt((p?['service_type'] as Map<String, dynamic>?)?['id']);
    _salespersonId = _toInt((p?['salesperson'] as Map<String, dynamic>?)?['id']);
    _projectManagerId = _toInt((p?['project_manager'] as Map<String, dynamic>?)?['id']);
    _priority = p?['priority']?.toString().isNotEmpty == true ? p!['priority'].toString() : 'normal';
  }

  @override
  void dispose() {
    _projectName.dispose();
    _description.dispose();
    _startDate.dispose();
    _expectedEndDate.dispose();
    _actualEndDate.dispose();
    _contractValue.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final referenceAsync = ref.watch(_referenceProvider);
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SafeArea(
        top: false,
        child: referenceAsync.when(
          loading: () => const SizedBox(height: 320, child: Center(child: CircularProgressIndicator())),
          error: (e, _) => SizedBox(height: 320, child: Center(child: Text('$e'))),
          data: (reference) => SingleChildScrollView(
            child: Form(
              key: _formKey,
              child: Column(
                children: [
                  _SheetHeader(
                    title: _isNew ? 'Create New Project' : 'Edit ${widget.project?['project_name'] ?? 'Project'}',
                    onBack: () => Navigator.pop(context),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        TextFormField(
                          controller: _projectName,
                          decoration: const InputDecoration(labelText: 'Project Name'),
                          validator: (v) => v == null || v.trim().isEmpty ? 'Required' : null,
                        ),
                        const SizedBox(height: 12),
                        _Drop<int>(
                          label: 'Client',
                          value: _clientId,
                          items: (reference['clients'] as List? ?? const []).cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _clientId = v),
                        ),
                        _Drop<int>(
                          label: 'Project Category',
                          value: _projectTypeId,
                          items: (reference['project_types'] as List? ?? const []).cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _projectTypeId = v),
                        ),
                        _Drop<int>(
                          label: 'Service Type',
                          value: _serviceTypeId,
                          items: (reference['service_types'] as List? ?? const []).cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _serviceTypeId = v),
                        ),
                        _DateField(label: 'Start Date', controller: _startDate),
                        _DateField(label: 'Expected End Date', controller: _expectedEndDate),
                        _DateField(label: 'Actual End Date', controller: _actualEndDate, allowBlank: true),
                        TextFormField(
                          controller: _contractValue,
                          decoration: const InputDecoration(labelText: 'Contract Value (TZS)'),
                          keyboardType: TextInputType.number,
                        ),
                        const SizedBox(height: 12),
                        _Drop<String>(
                          label: 'Priority',
                          value: _priority,
                          items: (reference['priorities'] as List? ?? const []).cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _priority = v),
                        ),
                        _Drop<int>(
                          label: 'Salesperson',
                          value: _salespersonId,
                          items: (reference['salespersons'] as List? ?? const []).cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _salespersonId = v),
                        ),
                        _Drop<int>(
                          label: 'Project Manager',
                          value: _projectManagerId,
                          items: (reference['project_managers'] as List? ?? const []).cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _projectManagerId = v),
                        ),
                        TextFormField(
                          controller: _description,
                          decoration: const InputDecoration(labelText: 'Description'),
                          maxLines: 2,
                        ),
                        const SizedBox(height: 20),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _loading ? null : _submit,
                            child: _loading
                                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                : Text(_isNew ? 'Create Project' : 'Update Project'),
                          ),
                        ),
                      ],
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_clientId == null || _projectTypeId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Client and Project Category are required')),
      );
      return;
    }
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'name': _projectName.text.trim(),
        'client_id': _clientId,
        'project_type_id': _projectTypeId,
        'service_type_id': _serviceTypeId,
        'salesperson_id': _salespersonId,
        'project_manager_id': _projectManagerId,
        'start_date': _startDate.text.trim(),
        'expected_end_date': _expectedEndDate.text.trim(),
        'actual_end_date': _actualEndDate.text.trim().isEmpty ? null : _actualEndDate.text.trim(),
        'contract_value': _contractValue.text.trim().isEmpty ? null : double.tryParse(_contractValue.text.trim()),
        'priority': _priority,
        'description': _description.text.trim().isEmpty ? null : _description.text.trim(),
      };
      if (_isNew) {
        await api.post('/projects', data: data);
      } else {
        await api.put('/projects/${widget.project!['id']}', data: data);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: AppColors.error),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

class _SheetHeader extends StatelessWidget {
  final String title;
  final VoidCallback onBack;

  const _SheetHeader({required this.title, required this.onBack});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
      decoration: const BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          Center(
            child: Container(
              width: 42,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.white38,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              IconButton(
                onPressed: onBack,
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              ),
              Expanded(
                child: Text(
                  title,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(width: 48),
            ],
          ),
        ],
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final TextEditingController controller;
  final bool allowBlank;

  const _DateField({
    required this.label,
    required this.controller,
    this.allowBlank = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        readOnly: true,
        decoration: InputDecoration(
          labelText: label,
          suffixIcon: const Icon(Icons.calendar_today_rounded),
        ),
        validator: allowBlank
            ? null
            : (value) => value == null || value.trim().isEmpty ? 'Required' : null,
        onTap: () async {
          final initial = controller.text.trim().isNotEmpty
              ? DateTime.tryParse(controller.text.trim()) ?? DateTime.now()
              : DateTime.now();
          final picked = await showDatePicker(
            context: context,
            initialDate: initial,
            firstDate: DateTime(2000),
            lastDate: DateTime(2100),
          );
          if (picked != null) {
            controller.text = DateFormat('yyyy-MM-dd').format(picked);
          }
        },
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  final String status;
  const _StatusPill({required this.status});

  @override
  Widget build(BuildContext context) {
    final normalized = status.toUpperCase();
    final color = switch (normalized) {
      'APPROVED' => AppColors.success,
      'COMPLETED' => const Color(0xFF9B59B6),
      'REJECTED' => AppColors.error,
      'PENDING' || 'CREATED' => const Color(0xFFF39C12),
      'IN_PROGRESS' || 'SUBMITTED' => const Color(0xFF3498DB),
      _ => AppColors.textSecondary,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        normalized.replaceAll('_', ' '),
        style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }
}

class _InfoLine extends StatelessWidget {
  final String label;
  final String value;
  const _InfoLine(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(fontSize: 13, color: AppColors.textPrimary),
          children: [
            const TextSpan(style: TextStyle(fontWeight: FontWeight.w700)),
            TextSpan(
              text: '$label: ',
              style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.textSecondary),
            ),
            TextSpan(text: value),
          ],
        ),
      ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  final String label;
  final String value;
  const _DetailLine(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (label.isNotEmpty)
            SizedBox(
              width: 130,
              child: Text(
                label,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textSecondary,
                ),
              ),
            ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 14, color: AppColors.textPrimary),
            ),
          ),
        ],
      ),
    );
  }
}

class _ApprovalActions extends StatelessWidget {
  final Map<String, dynamic> flow;
  final bool busy;
  final ValueChanged<String> onAction;

  const _ApprovalActions({
    required this.flow,
    required this.busy,
    required this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final buttons = <Widget>[];
    final nextAction = (flow['next_action']?.toString() ?? 'APPROVE').toUpperCase();
    final approveLabel = flow['is_rejected'] == true
        ? 'Re-Approve'
        : _capitalize(nextAction.toLowerCase());

    if (flow['can_be_submitted'] == true) {
      buttons.add(
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: busy ? null : () => onAction('submit'),
            child: const Text('Submit'),
          ),
        ),
      );
    }

    if (flow['can_be_approved'] == true) {
      buttons.add(
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            if (flow['can_be_discarded'] == true)
              OutlinedButton(
                onPressed: busy ? null : () => onAction('discard'),
                child: const Text('Discard'),
              )
            else ...[
              if (flow['can_be_rejected'] == true)
                OutlinedButton(
                  onPressed: busy ? null : () => onAction('reject'),
                  child: const Text('Reject'),
                ),
              if (flow['can_be_returned'] == true)
                OutlinedButton(
                  onPressed: busy ? null : () => onAction('return'),
                  child: const Text('Return'),
                ),
            ],
            ElevatedButton(
              onPressed: busy ? null : () => onAction('approve'),
              child: Text(approveLabel),
            ),
          ],
        ),
      );
    }

    if (buttons.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: buttons
          .map((button) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: button,
              ))
          .toList(),
    );
  }
}

class _ApprovalStepCard extends StatelessWidget {
  final Map<String, dynamic> step;
  const _ApprovalStepCard({required this.step});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${step['approver_name'] ?? '-'}',
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 4),
          Text(
            '${step['role_name'] ?? '-'}',
            style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
          ),
          if ((step['date']?.toString().isNotEmpty ?? false)) ...[
            const SizedBox(height: 4),
            Text(
              '${step['date']}',
              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
          ],
          if ((step['comment']?.toString().isNotEmpty ?? false)) ...[
            const SizedBox(height: 4),
            Text(
              '${step['comment']}',
              style: const TextStyle(fontSize: 12, color: AppColors.textPrimary),
            ),
          ],
        ],
      ),
    );
  }
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

int? _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse('$value');
}

String _formatDate(String? value) {
  if (value == null || value.isEmpty) return '-';
  try {
    return DateFormat('yyyy-MM-dd').format(DateTime.parse(value));
  } catch (_) {
    return value;
  }
}

String _money(dynamic value) {
  final amount = _toDouble(value);
  if (amount <= 0) return '-';
  return 'TZS ${NumberFormat('#,##0.00').format(amount)}';
}

String _display(dynamic value) {
  if (value == null || '$value'.isEmpty) return '-';
  return value.toString();
}

String _capitalize(String value) {
  if (value.isEmpty) return value;
  return value[0].toUpperCase() + value.substring(1);
}
