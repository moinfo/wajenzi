import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/procurement/material_request_models.dart';
import '../../../data/repositories/material_requests_repository.dart';
import '../../providers/settings_provider.dart';
import 'material_request_create_screen.dart';
import 'procurement_shared.dart';

// ── Filter state ────────────────────────────────────────────────────────────
final _mrProjectFilterProvider = StateProvider.autoDispose<int?>((_) => null);
final _mrStatusFilterProvider = StateProvider.autoDispose<String?>((_) => null);
final _mrSearchProvider = StateProvider.autoDispose<String>((_) => '');

/// Projects list (for the filter dropdown) — reuses the create reference data.
final _mrProjectsProvider =
    FutureProvider.autoDispose<List<MrProjectOption>>((ref) async {
  final data = await ref.watch(materialRequestsRepositoryProvider).referenceData();
  return data.projects;
});

final _mrDetailProvider = FutureProvider.autoDispose
    .family<MaterialRequestDto, int>((ref, id) async {
  return ref.watch(materialRequestsRepositoryProvider).show(id);
});

// ── List screen ─────────────────────────────────────────────────────────────

class MaterialRequestsScreen extends ConsumerStatefulWidget {
  const MaterialRequestsScreen({super.key});

  @override
  ConsumerState<MaterialRequestsScreen> createState() =>
      _MaterialRequestsScreenState();
}

class _MaterialRequestsScreenState
    extends ConsumerState<MaterialRequestsScreen> {
  final ScrollController _scrollCtrl = ScrollController();
  final TextEditingController _searchCtrl = TextEditingController();
  Timer? _debounce;

  List<MaterialRequestDto> _items = [];
  int _page = 1;
  int _lastPage = 1;
  bool _loading = false;
  bool _firstLoad = true;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _scrollCtrl.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => _refresh());
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollCtrl.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollCtrl.position.pixels >=
            _scrollCtrl.position.maxScrollExtent - 300 &&
        !_loading &&
        _page < _lastPage) {
      _fetch(_page + 1);
    }
  }

  Future<void> _refresh() => _fetch(1, reset: true);

  Future<void> _fetch(int page, {bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      if (reset) _error = null;
    });
    try {
      final repo = ref.read(materialRequestsRepositoryProvider);
      final result = await repo.list(
        projectId: ref.read(_mrProjectFilterProvider),
        status: ref.read(_mrStatusFilterProvider),
        search: ref.read(_mrSearchProvider),
        page: page,
        perPage: 20,
      );
      if (!mounted) return;
      setState(() {
        _items = reset ? result.items : [..._items, ...result.items];
        _page = result.currentPage;
        _lastPage = result.lastPage;
        _firstLoad = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _firstLoad = false;
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      ref.read(_mrSearchProvider.notifier).state = value.trim();
      _refresh();
    });
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final projectFilter = ref.watch(_mrProjectFilterProvider);
    final statusFilter = ref.watch(_mrStatusFilterProvider);
    final projectsAsync = ref.watch(_mrProjectsProvider);
    final canAdd = hasPermission(ref, 'Add Material Request');

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Maombi ya Vifaa' : 'Material Requests'),
      ),
      floatingActionButton: canAdd
          ? Padding(
              padding: const EdgeInsets.only(bottom: 72),
              child: FloatingActionButton.extended(
                onPressed: () => _openCreate(),
                icon: const Icon(Icons.add),
                label: Text(isSwahili ? 'Ombi Jipya' : 'New Request'),
              ),
            )
          : null,
      body: Column(
        children: [
          _FilterBar(
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
            searchController: _searchCtrl,
            onSearchChanged: _onSearchChanged,
            statusFilter: statusFilter,
            onStatusChanged: (v) {
              ref.read(_mrStatusFilterProvider.notifier).state = v;
              _refresh();
            },
            projectFilter: projectFilter,
            projects: projectsAsync.valueOrNull ?? const [],
            onProjectChanged: (v) {
              ref.read(_mrProjectFilterProvider.notifier).state = v;
              _refresh();
            },
          ),
          Expanded(child: _buildBody(isSwahili, isDarkMode)),
        ],
      ),
    );
  }

  Widget _buildBody(bool isSwahili, bool isDarkMode) {
    if (_firstLoad && _loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null && _items.isEmpty) {
      return _ErrorView(
        message: procurementErrorMessage(_error!),
        isSwahili: isSwahili,
        onRetry: _refresh,
      );
    }
    return RefreshIndicator(
      onRefresh: _refresh,
      child: _items.isEmpty
          ? ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              children: [
                const SizedBox(height: 120),
                Icon(Icons.inventory_2_outlined,
                    size: 64, color: AppColors.textHint),
                const SizedBox(height: 12),
                Center(
                  child: Text(
                    isSwahili ? 'Hakuna maombi' : 'No material requests',
                    style: TextStyle(color: AppColors.textSecondary),
                  ),
                ),
              ],
            )
          : ListView.builder(
              controller: _scrollCtrl,
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 96),
              itemCount: _items.length + (_page < _lastPage ? 1 : 0),
              itemBuilder: (context, index) {
                if (index >= _items.length) {
                  return const Padding(
                    padding: EdgeInsets.all(16),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }
                final mr = _items[index];
                return _RequestCard(
                  request: mr,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  onTap: () => _openDetail(mr.id),
                );
              },
            ),
    );
  }

  void _openDetail(int id) {
    Navigator.of(context)
        .push(MaterialPageRoute(
          builder: (_) => MaterialRequestDetailScreen(id: id),
        ))
        .then((_) => _refresh());
  }

  void _openCreate() {
    Navigator.of(context)
        .push(MaterialPageRoute(
          builder: (_) => const MaterialRequestCreateScreen(),
        ))
        .then((created) {
      if (created == true) _refresh();
    });
  }
}

class _FilterBar extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final TextEditingController searchController;
  final ValueChanged<String> onSearchChanged;
  final String? statusFilter;
  final ValueChanged<String?> onStatusChanged;
  final int? projectFilter;
  final List<MrProjectOption> projects;
  final ValueChanged<int?> onProjectChanged;

  const _FilterBar({
    required this.isSwahili,
    required this.isDarkMode,
    required this.searchController,
    required this.onSearchChanged,
    required this.statusFilter,
    required this.onStatusChanged,
    required this.projectFilter,
    required this.projects,
    required this.onProjectChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          TextField(
            controller: searchController,
            decoration: InputDecoration(
              hintText: isSwahili
                  ? 'Tafuta nambari, mradi…'
                  : 'Search number, project…',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: searchController.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        searchController.clear();
                        onSearchChanged('');
                      },
                    )
                  : null,
              filled: true,
              fillColor:
                  isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
            ),
            onChanged: onSearchChanged,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<int?>(
                  initialValue: projectFilter,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Mradi' : 'Project',
                    isDense: true,
                    border: const OutlineInputBorder(),
                  ),
                  items: [
                    DropdownMenuItem<int?>(
                      value: null,
                      child: Text(isSwahili ? 'Miradi yote' : 'All projects'),
                    ),
                    ...projects.map((p) => DropdownMenuItem<int?>(
                          value: p.id,
                          child: Text(p.name, overflow: TextOverflow.ellipsis),
                        )),
                  ],
                  onChanged: onProjectChanged,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _FilterChip(
                  label: isSwahili ? 'Zote' : 'All',
                  selected: statusFilter == null,
                  color: Colors.grey,
                  onTap: () => onStatusChanged(null),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: isSwahili ? 'Inasubiri' : 'Pending',
                  selected: statusFilter == 'pending',
                  color: AppColors.warning,
                  onTap: () => onStatusChanged('pending'),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: isSwahili ? 'Imepitishwa' : 'Approved',
                  selected: statusFilter == 'approved',
                  color: AppColors.success,
                  onTap: () => onStatusChanged('approved'),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: isSwahili ? 'Imekataliwa' : 'Rejected',
                  selected: statusFilter == 'rejected',
                  color: AppColors.error,
                  onTap: () => onStatusChanged('rejected'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final Color color;
  final VoidCallback onTap;

  const _FilterChip({
    required this.label,
    required this.selected,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? color : Colors.grey.withValues(alpha: 0.12),
          border: Border.all(color: selected ? color : Colors.transparent),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: selected ? Colors.white : AppColors.textSecondary,
          ),
        ),
      ),
    );
  }
}

class _RequestCard extends StatelessWidget {
  final MaterialRequestDto request;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _RequestCard({
    required this.request,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: Theme.of(context).dividerColor.withValues(alpha: 0.5),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      request.requestNumber.isEmpty
                          ? '#${request.id}'
                          : request.requestNumber,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  ProcurementStatusChip(status: request.approvalStatus),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                request.projectName ?? '—',
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              if (request.itemsSummary.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  request.itemsSummary,
                  style: TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 10),
              Row(
                children: [
                  _PriorityBadge(priority: request.priority),
                  const SizedBox(width: 12),
                  Icon(Icons.inventory_2,
                      size: 12, color: AppColors.textSecondary),
                  const SizedBox(width: 4),
                  Text(
                    '${request.itemsCount} ${isSwahili ? 'vipengele' : 'items'}',
                    style: TextStyle(
                        fontSize: 11, color: AppColors.textSecondary),
                  ),
                  const Spacer(),
                  Icon(Icons.calendar_today,
                      size: 12, color: AppColors.textSecondary),
                  const SizedBox(width: 4),
                  Text(
                    fmtDate(request.requiredDate),
                    style: TextStyle(
                        fontSize: 11, color: AppColors.textSecondary),
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

class _PriorityBadge extends StatelessWidget {
  final String priority;
  const _PriorityBadge({required this.priority});

  @override
  Widget build(BuildContext context) {
    final p = priority.toLowerCase();
    Color color;
    switch (p) {
      case 'urgent':
        color = AppColors.error;
        break;
      case 'high':
        color = AppColors.warning;
        break;
      case 'low':
        color = Colors.blueGrey;
        break;
      default:
        color = AppColors.primary;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        p.isEmpty ? '—' : (p[0].toUpperCase() + p.substring(1)),
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

// ── Detail screen ───────────────────────────────────────────────────────────

class MaterialRequestDetailScreen extends ConsumerWidget {
  final int id;
  const MaterialRequestDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final detailAsync = ref.watch(_mrDetailProvider(id));
    final detail = detailAsync.valueOrNull;

    final canDelete = detail != null &&
        detail.canDelete &&
        hasPermission(ref, 'Delete Material Request');

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Maelezo ya Ombi' : 'Request Detail'),
        actions: [
          if (canDelete)
            IconButton(
              tooltip: isSwahili ? 'Futa' : 'Delete',
              icon: Icon(Icons.delete_outline, color: AppColors.error),
              onPressed: () => _confirmDelete(context, ref, isSwahili),
            ),
        ],
      ),
      body: detailAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: procurementErrorMessage(e),
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_mrDetailProvider(id)),
        ),
        data: (mr) => _DetailBody(
          request: mr,
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
          onChanged: () => ref.invalidate(_mrDetailProvider(id)),
        ),
      ),
    );
  }

  Future<void> _confirmDelete(
    BuildContext context,
    WidgetRef ref,
    bool isSwahili,
  ) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa Ombi' : 'Delete Request'),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta ombi hili?'
              : 'Are you sure you want to delete this request?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref.read(materialRequestsRepositoryProvider).delete(id);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imefutwa' : 'Deleted'),
            backgroundColor: AppColors.success,
          ),
        );
        Navigator.of(context).pop();
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(procurementErrorMessage(e)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

class _DetailBody extends ConsumerWidget {
  final MaterialRequestDto request;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onChanged;

  const _DetailBody({
    required this.request,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final flow = request.approvalFlow;
    final canApprove = flow?.canBeApproved ?? false;
    final canSubmit = flow?.canBeSubmitted ?? false;
    final canEditQty = request.canEditQuantities &&
        request.items.isNotEmpty &&
        hasPermission(ref, 'Edit Material Request');

    return RefreshIndicator(
      onRefresh: () async => onChanged(),
      child: ListView(
        padding: const EdgeInsets.all(16),
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          _SectionCard(
            isDarkMode: isDarkMode,
            title: request.requestNumber.isEmpty
                ? '#${request.id}'
                : request.requestNumber,
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    children: [
                      Text(
                        isSwahili ? 'Hali:' : 'Status:',
                        style: TextStyle(
                            fontSize: 12, color: AppColors.textSecondary),
                      ),
                      const SizedBox(width: 8),
                      ProcurementStatusChip(status: request.approvalStatus),
                      const SizedBox(width: 6),
                      ProcurementStatusChip(status: request.status),
                    ],
                  ),
                ),
                _InfoRow(
                  label: isSwahili ? 'Mradi' : 'Project',
                  value: request.projectName ?? '—',
                ),
                _InfoRow(
                  label: isSwahili ? 'Kipaumbele' : 'Priority',
                  value: request.priority,
                ),
                _InfoRow(
                  label: isSwahili ? 'Tarehe inayohitajika' : 'Required date',
                  value: fmtDate(request.requiredDate),
                ),
                _InfoRow(
                  label: isSwahili ? 'Aliyeomba' : 'Requested by',
                  value: request.requesterName ?? '—',
                ),
                if (request.approverName != null)
                  _InfoRow(
                    label: isSwahili ? 'Aliyeidhinisha' : 'Approved by',
                    value: request.approverName!,
                  ),
                if ((request.purpose ?? '').isNotEmpty)
                  _InfoRow(
                    label: isSwahili ? 'Madhumuni' : 'Purpose',
                    value: request.purpose!,
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            isDarkMode: isDarkMode,
            title: '${isSwahili ? 'Vipengele' : 'Items'} (${request.items.length})',
            child: request.items.isEmpty
                ? Text(isSwahili ? 'Hakuna vipengele' : 'No items',
                    style: TextStyle(color: AppColors.textSecondary))
                : Column(
                    children: [
                      _ItemsHeader(isSwahili: isSwahili),
                      const Divider(height: 12),
                      ...request.items
                          .map((i) => _ItemRow(item: i, isSwahili: isSwahili)),
                    ],
                  ),
          ),
          if (flow != null) ...[
            const SizedBox(height: 12),
            _SectionCard(
              isDarkMode: isDarkMode,
              title: isSwahili ? 'Mtiririko wa Idhini' : 'Approval Flow',
              child: _ApprovalFlowView(flow: flow, isSwahili: isSwahili),
            ),
          ],
          if ((request.rejectionReason ?? '').isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.error.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.error.withValues(alpha: 0.4)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    isSwahili ? 'Sababu ya Kukataa' : 'Rejection Reason',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                      color: AppColors.error,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(request.rejectionReason!,
                      style: const TextStyle(fontSize: 13)),
                ],
              ),
            ),
          ],
          const SizedBox(height: 16),
          if (canEditQty)
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => _editQuantities(context, ref),
                icon: const Icon(Icons.edit),
                label: Text(isSwahili
                    ? 'Hariri Idadi Zilizoidhinishwa'
                    : 'Edit Approved Quantities'),
              ),
            ),
          if (canSubmit) ...[
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _submit(context, ref),
                icon: const Icon(Icons.send),
                label: Text(isSwahili ? 'Wasilisha kwa Idhini' : 'Submit for Approval'),
              ),
            ),
          ],
          if (canApprove) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.error,
                      side: BorderSide(color: AppColors.error),
                    ),
                    onPressed: () => _reject(context, ref),
                    icon: const Icon(Icons.close),
                    label: Text(isSwahili ? 'Kataa' : 'Reject'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.success,
                      foregroundColor: Colors.white,
                    ),
                    onPressed: () => _approve(context, ref),
                    icon: const Icon(Icons.check),
                    label: Text(isSwahili ? 'Idhinisha' : 'Approve'),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Future<void> _submit(BuildContext context, WidgetRef ref) async {
    try {
      await ref.read(materialRequestsRepositoryProvider).submit(request.id);
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imewasilishwa' : 'Submitted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) _showError(context, e);
    }
  }

  Future<void> _approve(BuildContext context, WidgetRef ref) async {
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Idhinisha Ombi' : 'Approve Request'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(
            hintText: isSwahili ? 'Maelezo (hiari)' : 'Comment (optional)',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Idhinisha' : 'Approve'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref.read(materialRequestsRepositoryProvider).approve(
            request.id,
            comment: controller.text.trim().isEmpty
                ? null
                : controller.text.trim(),
          );
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imeidhinishwa' : 'Approved'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) _showError(context, e);
    }
  }

  Future<void> _reject(BuildContext context, WidgetRef ref) async {
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Kataa Ombi' : 'Reject Request'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(
            hintText: isSwahili ? 'Sababu ya kukataa' : 'Rejection reason',
            border: const OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Kataa' : 'Reject'),
          ),
        ],
      ),
    );
    if (confirmed != true || controller.text.trim().isEmpty) return;
    try {
      await ref
          .read(materialRequestsRepositoryProvider)
          .reject(request.id, controller.text.trim());
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imekataliwa' : 'Rejected'),
            backgroundColor: AppColors.warning,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) _showError(context, e);
    }
  }

  Future<void> _editQuantities(BuildContext context, WidgetRef ref) async {
    final controllers = <int, TextEditingController>{};
    for (final item in request.items) {
      final initial = item.quantityApproved ?? item.quantityRequested;
      controllers[item.id] = TextEditingController(text: _trimNum(initial));
    }

    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        bool saving = false;
        return StatefulBuilder(
          builder: (ctx, setSheetState) {
            return ClipRRect(
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(16)),
              child: Material(
                color: Theme.of(ctx).scaffoldBackgroundColor,
                child: SafeArea(
                  child: Padding(
                    padding: EdgeInsets.only(
                      left: 16,
                      right: 16,
                      top: 16,
                      bottom: MediaQuery.of(ctx).viewInsets.bottom + 16,
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili
                              ? 'Idadi Zilizoidhinishwa'
                              : 'Approved Quantities',
                          style: const TextStyle(
                              fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 12),
                        Flexible(
                          child: SingleChildScrollView(
                            child: Column(
                              children: request.items.map((item) {
                                return Padding(
                                  padding:
                                      const EdgeInsets.symmetric(vertical: 6),
                                  child: Row(
                                    children: [
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            Text(item.label,
                                                style: const TextStyle(
                                                    fontSize: 13,
                                                    fontWeight:
                                                        FontWeight.w600)),
                                            Text(
                                              '${isSwahili ? 'Imeombwa' : 'Requested'}: '
                                              '${_trimNum(item.quantityRequested)} ${item.unit ?? ''}',
                                              style: TextStyle(
                                                  fontSize: 11,
                                                  color:
                                                      AppColors.textSecondary),
                                            ),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      SizedBox(
                                        width: 90,
                                        child: TextField(
                                          controller: controllers[item.id],
                                          keyboardType: const TextInputType
                                              .numberWithOptions(decimal: true),
                                          decoration: InputDecoration(
                                            labelText:
                                                isSwahili ? 'Idadi' : 'Qty',
                                            isDense: true,
                                            border: const OutlineInputBorder(),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              }).toList(),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: saving
                                ? null
                                : () async {
                                    setSheetState(() => saving = true);
                                    final payload = <String, dynamic>{};
                                    for (final entry in controllers.entries) {
                                      final v =
                                          double.tryParse(entry.value.text);
                                      if (v != null && v > 0) {
                                        payload['${entry.key}'] = v;
                                      }
                                    }
                                    try {
                                      await ref
                                          .read(
                                              materialRequestsRepositoryProvider)
                                          .updateQuantities(request.id, payload);
                                      if (ctx.mounted) {
                                        Navigator.of(ctx).pop(true);
                                      }
                                    } catch (e) {
                                      setSheetState(() => saving = false);
                                      if (ctx.mounted) {
                                        ScaffoldMessenger.of(ctx).showSnackBar(
                                          SnackBar(
                                            content: Text(
                                                procurementErrorMessage(e)),
                                            backgroundColor: AppColors.error,
                                          ),
                                        );
                                      }
                                    }
                                  },
                            child: saving
                                ? const SizedBox(
                                    width: 18,
                                    height: 18,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2, color: Colors.white),
                                  )
                                : Text(isSwahili ? 'Hifadhi' : 'Save'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );

    for (final c in controllers.values) {
      c.dispose();
    }

    if (saved == true) {
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imehifadhiwa' : 'Saved'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    }
  }

  void _showError(BuildContext context, Object e) {
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(procurementErrorMessage(e)),
        backgroundColor: AppColors.error,
      ),
    );
  }
}

class _ItemsHeader extends StatelessWidget {
  final bool isSwahili;
  const _ItemsHeader({required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(
      fontSize: 10,
      fontWeight: FontWeight.w700,
      color: AppColors.textSecondary,
      letterSpacing: 0.4,
    );
    return Row(
      children: [
        Expanded(
          flex: 4,
          child: Text((isSwahili ? 'KIPENGELE' : 'ITEM'), style: style),
        ),
        Expanded(
          flex: 2,
          child: Text(isSwahili ? 'IMEOMBWA' : 'REQ',
              textAlign: TextAlign.right, style: style),
        ),
        Expanded(
          flex: 2,
          child: Text(isSwahili ? 'IMEIDHIN.' : 'APPR',
              textAlign: TextAlign.right, style: style),
        ),
      ],
    );
  }
}

class _ItemRow extends StatelessWidget {
  final MaterialRequestItemDto item;
  final bool isSwahili;
  const _ItemRow({required this.item, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                flex: 4,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item.label,
                        style: const TextStyle(
                            fontSize: 13, fontWeight: FontWeight.w600)),
                    if ((item.specification ?? '').isNotEmpty)
                      Text(item.specification!,
                          style: TextStyle(
                              fontSize: 11, color: AppColors.textSecondary)),
                    if (item.boqItem != null)
                      Text(
                        '${isSwahili ? 'BOQ' : 'BOQ'}: '
                        '${fmtQty(item.boqItem!.quantity)} • '
                        '${isSwahili ? 'Imesalia' : 'Rem'} '
                        '${fmtQty(item.boqItem!.quantityRemaining)} ${item.boqItem!.unit ?? ''}',
                        style: TextStyle(
                            fontSize: 10, color: AppColors.textSecondary),
                      ),
                  ],
                ),
              ),
              Expanded(
                flex: 2,
                child: Text(
                  '${fmtQty(item.quantityRequested)} ${item.unit ?? ''}',
                  textAlign: TextAlign.right,
                  style: const TextStyle(fontSize: 12),
                ),
              ),
              Expanded(
                flex: 2,
                child: Text(
                  item.quantityApproved == null
                      ? '—'
                      : '${fmtQty(item.quantityApproved)} ${item.unit ?? ''}',
                  textAlign: TextAlign.right,
                  style: const TextStyle(
                      fontSize: 12, fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          const Divider(height: 1),
        ],
      ),
    );
  }
}

class _ApprovalFlowView extends StatelessWidget {
  final MrApprovalFlowDto flow;
  final bool isSwahili;
  const _ApprovalFlowView({required this.flow, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(
              flow.isCompleted
                  ? Icons.check_circle
                  : (flow.isSubmitted
                      ? Icons.hourglass_top
                      : Icons.edit_note),
              size: 18,
              color: flow.isCompleted
                  ? AppColors.success
                  : AppColors.warning,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                flow.statusLabel,
                style: const TextStyle(
                    fontSize: 13, fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
        if (flow.nextRoleName != null && !flow.isCompleted) ...[
          const SizedBox(height: 4),
          Text(
            '${isSwahili ? 'Hatua inayofuata' : 'Next'}: ${flow.nextRoleName}',
            style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
          ),
        ],
        const SizedBox(height: 8),
        ...flow.steps.map((step) => Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(
                    step.approverName != null
                        ? Icons.check_circle_outline
                        : Icons.radio_button_unchecked,
                    size: 16,
                    color: step.approverName != null
                        ? AppColors.success
                        : AppColors.textHint,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${step.roleName} • ${step.action}',
                          style: const TextStyle(
                              fontSize: 12, fontWeight: FontWeight.w600),
                        ),
                        if (step.approverName != null)
                          Text(
                            '${step.approverName}'
                            '${step.date != null ? ' — ${step.date}' : ''}',
                            style: TextStyle(
                                fontSize: 11,
                                color: AppColors.textSecondary),
                          ),
                        if ((step.comment ?? '').isNotEmpty)
                          Text(
                            step.comment!,
                            style: TextStyle(
                                fontSize: 11,
                                fontStyle: FontStyle.italic,
                                color: AppColors.textSecondary),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            )),
      ],
    );
  }
}

// ── Shared small widgets ────────────────────────────────────────────────────

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

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(label,
                style:
                    TextStyle(fontSize: 12, color: AppColors.textSecondary)),
          ),
          Expanded(
            flex: 3,
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.message,
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
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 13, color: AppColors.textSecondary),
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

String _trimNum(double v) {
  if (v == v.roundToDouble()) return v.toInt().toString();
  return v.toString();
}
