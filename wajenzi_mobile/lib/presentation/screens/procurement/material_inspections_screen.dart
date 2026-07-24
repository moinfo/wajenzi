import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/procurement/inspection_models.dart';
import '../../../data/repositories/inspections_repository.dart';
import 'material_inspection_create_screen.dart';
import 'procurement_shared.dart';

final _searchProvider = StateProvider.autoDispose<String>((_) => '');
final _statusFilterProvider = StateProvider.autoDispose<String?>((_) => null);

final _inspectionsListProvider =
    FutureProvider.autoDispose<InspectionListResult>((ref) async {
  final repo = ref.watch(inspectionsRepositoryProvider);
  final search = ref.watch(_searchProvider);
  final status = ref.watch(_statusFilterProvider);
  return repo.list(
    search: search.isEmpty ? null : search,
    status: status,
    perPage: 100,
  );
});

final _inspectionDetailProvider =
    FutureProvider.autoDispose.family<InspectionDto, int>((ref, id) async {
  final repo = ref.watch(inspectionsRepositoryProvider);
  return repo.show(id);
});

class MaterialInspectionsScreen extends ConsumerStatefulWidget {
  const MaterialInspectionsScreen({super.key});

  @override
  ConsumerState<MaterialInspectionsScreen> createState() =>
      _MaterialInspectionsScreenState();
}

class _MaterialInspectionsScreenState
    extends ConsumerState<MaterialInspectionsScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _refresh() async {
    ref.invalidate(_inspectionsListProvider);
    await ref.read(_inspectionsListProvider.future);
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final listAsync = ref.watch(_inspectionsListProvider);
    final search = ref.watch(_searchProvider);
    final status = ref.watch(_statusFilterProvider);

    final pendingCount =
        listAsync.valueOrNull?.pendingReceivings.length ?? 0;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: const Text('Material Inspections'),
        bottom: TabBar(
          controller: _tabController,
          tabs: [
            const Tab(text: 'Inspections'),
            Tab(text: 'To Inspect ($pendingCount)'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          Column(
            children: [
              _SearchAndFilter(
                search: search,
                status: status,
                onSearchChange: (v) =>
                    ref.read(_searchProvider.notifier).state = v,
                onStatusChange: (v) =>
                    ref.read(_statusFilterProvider.notifier).state = v,
              ),
              Expanded(
                child: RefreshIndicator(
                  onRefresh: _refresh,
                  child: listAsync.when(
                    loading: () =>
                        const Center(child: CircularProgressIndicator()),
                    error: (e, _) => _ErrorView(
                      error: e,
                      onRetry: () =>
                          ref.invalidate(_inspectionsListProvider),
                    ),
                    data: (result) {
                      final items = result.inspections;
                      if (items.isEmpty) {
                        return _EmptyView(
                          icon: Icons.fact_check_outlined,
                          message: 'No inspections yet',
                        );
                      }
                      return ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 88),
                        itemCount: items.length,
                        itemBuilder: (context, index) => _InspectionCard(
                          inspection: items[index],
                          onTap: () => _openDetail(items[index].id),
                        ),
                      );
                    },
                  ),
                ),
              ),
            ],
          ),
          RefreshIndicator(
            onRefresh: _refresh,
            child: listAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => _ErrorView(
                error: e,
                onRetry: () => ref.invalidate(_inspectionsListProvider),
              ),
              data: (result) {
                final pending = result.pendingReceivings;
                final canAdd = hasPermission(ref, 'Add Material Inspection');
                if (pending.isEmpty) {
                  return _EmptyView(
                    icon: Icons.inventory_2_outlined,
                    message: 'No deliveries awaiting inspection',
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 88),
                  itemCount: pending.length,
                  itemBuilder: (context, index) => _PendingReceivingCard(
                    receiving: pending[index],
                    canInspect: canAdd,
                    onInspect: () => _openCreate(pending[index].id),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  void _openDetail(int id) {
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) => MaterialInspectionDetailScreen(id: id),
          ),
        )
        .then((_) => ref.invalidate(_inspectionsListProvider));
  }

  void _openCreate(int receivingId) {
    Navigator.of(context)
        .push(
          MaterialPageRoute(
            builder: (_) =>
                MaterialInspectionCreateScreen(receivingId: receivingId),
          ),
        )
        .then((created) {
      if (created == true) {
        ref.invalidate(_inspectionsListProvider);
      }
    });
  }
}

class _SearchAndFilter extends StatelessWidget {
  final String search;
  final String? status;
  final ValueChanged<String> onSearchChange;
  final ValueChanged<String?> onStatusChange;

  const _SearchAndFilter({
    required this.search,
    required this.status,
    required this.onSearchChange,
    required this.onStatusChange,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
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
            decoration: InputDecoration(
              hintText: 'Search inspection, GRN, project…',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: search.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () => onSearchChange(''),
                    )
                  : null,
              filled: true,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
            ),
            onChanged: onSearchChange,
          ),
          const SizedBox(height: 12),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _FilterChip(
                  label: 'All',
                  selected: status == null,
                  color: Colors.grey,
                  onTap: () => onStatusChange(null),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: 'Pending',
                  selected: status == 'pending',
                  color: Colors.orange,
                  onTap: () => onStatusChange('pending'),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: 'Approved',
                  selected: status == 'approved',
                  color: Colors.green,
                  onTap: () => onStatusChange('approved'),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: 'Rejected',
                  selected: status == 'rejected',
                  color: Colors.red,
                  onTap: () => onStatusChange('rejected'),
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
          color: selected ? color : color.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: color.withValues(alpha: 0.5)),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: selected ? Colors.white : color,
          ),
        ),
      ),
    );
  }
}

class _InspectionCard extends StatelessWidget {
  final InspectionDto inspection;
  final VoidCallback onTap;

  const _InspectionCard({required this.inspection, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final status = inspection.approvalStatus ?? inspection.status;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: theme.dividerColor.withValues(alpha: 0.5),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      inspection.inspectionNumber ?? '—',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  ProcurementStatusChip(status: status),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                inspection.projectName ?? '—',
                style: theme.textTheme.bodySmall,
                overflow: TextOverflow.ellipsis,
              ),
              if (inspection.supplierReceiving?.receivingNumber != null) ...[
                const SizedBox(height: 2),
                Text(
                  'GRN: ${inspection.supplierReceiving!.receivingNumber}',
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: theme.hintColor),
                ),
              ],
              const SizedBox(height: 10),
              Row(
                children: [
                  _MiniStat(
                    label: 'Delivered',
                    value: fmtQty(inspection.quantityDelivered),
                  ),
                  _MiniStat(
                    label: 'Accepted',
                    value: fmtQty(inspection.quantityAccepted),
                    color: Colors.green,
                  ),
                  _MiniStat(
                    label: 'Rejected',
                    value: fmtQty(inspection.quantityRejected),
                    color: inspection.quantityRejected > 0
                        ? Colors.red
                        : null,
                  ),
                  const Spacer(),
                  _ResultBadge(result: inspection.overallResult),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  final Color? color;

  const _MiniStat({required this.label, required this.value, this.color});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(right: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label.toUpperCase(),
            style: TextStyle(
              fontSize: 9,
              fontWeight: FontWeight.w700,
              color: theme.hintColor,
              letterSpacing: 0.4,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
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

class _ResultBadge extends StatelessWidget {
  final String? result;
  const _ResultBadge({required this.result});

  @override
  Widget build(BuildContext context) {
    final r = (result ?? '').toLowerCase();
    Color color;
    switch (r) {
      case 'pass':
        color = Colors.green;
        break;
      case 'conditional':
        color = Colors.orange;
        break;
      case 'fail':
        color = Colors.red;
        break;
      default:
        color = Colors.blueGrey;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(
        r.isEmpty ? '—' : r.toUpperCase(),
        style: TextStyle(
            fontSize: 10, fontWeight: FontWeight.w700, color: color),
      ),
    );
  }
}

class _PendingReceivingCard extends StatelessWidget {
  final PendingReceivingDto receiving;
  final bool canInspect;
  final VoidCallback onInspect;

  const _PendingReceivingCard({
    required this.receiving,
    required this.canInspect,
    required this.onInspect,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: theme.cardColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: theme.dividerColor.withValues(alpha: 0.5)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    receiving.receivingNumber ?? '—',
                    style: const TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ),
                ProcurementStatusChip(status: receiving.condition),
              ],
            ),
            const SizedBox(height: 6),
            Text(receiving.projectName ?? '—',
                style: theme.textTheme.bodySmall,
                overflow: TextOverflow.ellipsis),
            const SizedBox(height: 2),
            Text(
              '${receiving.supplierName ?? '—'} · ${receiving.purchaseNumber ?? '—'}',
              style:
                  theme.textTheme.bodySmall?.copyWith(color: theme.hintColor),
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Icon(Icons.calendar_today, size: 12, color: theme.hintColor),
                const SizedBox(width: 4),
                Text(fmtDate(receiving.deliveryDate),
                    style: theme.textTheme.bodySmall),
                const SizedBox(width: 12),
                Icon(Icons.inventory_2, size: 12, color: theme.hintColor),
                const SizedBox(width: 4),
                Text('${fmtQty(receiving.quantityDelivered)} delivered',
                    style: theme.textTheme.bodySmall),
                const Spacer(),
                if (canInspect)
                  ElevatedButton.icon(
                    onPressed: onInspect,
                    icon: const Icon(Icons.fact_check, size: 16),
                    label: const Text('Inspect'),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      visualDensity: VisualDensity.compact,
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

/// --------------------------- Detail screen --------------------------------

class MaterialInspectionDetailScreen extends ConsumerWidget {
  final int id;
  const MaterialInspectionDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detailAsync = ref.watch(_inspectionDetailProvider(id));
    return Scaffold(
      appBar: AppBar(title: const Text('Inspection Detail')),
      body: detailAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_inspectionDetailProvider(id)),
        ),
        data: (inspection) => _DetailBody(
          inspection: inspection,
          onChanged: () async {
            ref.invalidate(_inspectionDetailProvider(id));
            await ref.read(_inspectionDetailProvider(id).future);
          },
        ),
      ),
    );
  }
}

class _DetailBody extends ConsumerStatefulWidget {
  final InspectionDto inspection;
  final Future<void> Function() onChanged;

  const _DetailBody({required this.inspection, required this.onChanged});

  @override
  ConsumerState<_DetailBody> createState() => _DetailBodyState();
}

class _DetailBodyState extends ConsumerState<_DetailBody> {
  bool _busy = false;

  InspectionDto get _i => widget.inspection;

  @override
  Widget build(BuildContext context) {
    final flow = _i.approvalFlow;
    final status = _i.approvalStatus ?? _i.status;
    final labels = _i.criteriaLabels.isNotEmpty
        ? _i.criteriaLabels
        : kInspectionCriteriaLabels;

    final canAct = flow?.canBeApproved == true && _busy == false;
    final nextAction = flow?.nextAction;
    final approveLabel =
        (nextAction != null && nextAction.toUpperCase() == 'VERIFY')
            ? 'Verify'
            : 'Approve';

    return RefreshIndicator(
      onRefresh: widget.onChanged,
      child: ListView(
        padding: const EdgeInsets.all(16),
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          _SectionCard(
            title: _i.inspectionNumber ?? '—',
            child: Column(
              children: [
                _InfoRow(
                  label: 'Status',
                  valueWidget: ProcurementStatusChip(status: status),
                ),
                _InfoRow(label: 'Project', value: _i.projectName ?? '—'),
                _InfoRow(
                  label: 'GRN',
                  value: _i.supplierReceiving?.receivingNumber ?? '—',
                ),
                _InfoRow(
                  label: 'PO',
                  value: _i.supplierReceiving?.purchaseNumber ?? '—',
                ),
                _InfoRow(
                  label: 'Supplier',
                  value: _i.supplierReceiving?.supplierName ?? '—',
                ),
                if (_i.boqItem != null)
                  _InfoRow(
                    label: 'BOQ Item',
                    value:
                        '${_i.boqItem!.itemCode ?? ''} ${_i.boqItem!.description ?? ''}'
                            .trim(),
                  ),
                _InfoRow(label: 'Inspector', value: _i.inspectorName ?? '—'),
                if (_i.verifierName != null)
                  _InfoRow(label: 'Verifier', value: _i.verifierName!),
                _InfoRow(
                    label: 'Inspected on', value: fmtDate(_i.inspectionDate)),
                _InfoRow(
                  label: 'Condition',
                  value: (_i.overallCondition ?? '—').toUpperCase(),
                ),
                _InfoRow(
                  label: 'Result',
                  valueWidget: _ResultBadge(result: _i.overallResult),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Quantities',
            child: Column(
              children: [
                _InfoRow(
                    label: 'Delivered',
                    value: fmtQty(_i.quantityDelivered)),
                _InfoRow(
                  label: 'Accepted',
                  value: fmtQty(_i.quantityAccepted),
                  valueColor: Colors.green,
                ),
                _InfoRow(
                  label: 'Rejected',
                  value: fmtQty(_i.quantityRejected),
                  valueColor:
                      _i.quantityRejected > 0 ? Colors.red : null,
                ),
                _InfoRow(
                  label: 'Acceptance rate',
                  value: '${fmtQty(_i.acceptanceRate)}%',
                ),
                if ((_i.rejectionReason ?? '').isNotEmpty)
                  _InfoRow(
                      label: 'Rejection reason', value: _i.rejectionReason!),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Inspection Criteria',
            child: Column(
              children: labels.entries.map((entry) {
                final passed = _i.criteriaChecklist[entry.key] == true;
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    children: [
                      Icon(
                        passed ? Icons.check_circle : Icons.cancel,
                        size: 18,
                        color: passed ? Colors.green : Colors.red,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(entry.value,
                            style: const TextStyle(fontSize: 13)),
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
          ),
          if ((_i.notes ?? '').isNotEmpty) ...[
            const SizedBox(height: 12),
            _SectionCard(
              title: 'Notes',
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(_i.notes!, style: const TextStyle(fontSize: 13)),
              ),
            ),
          ],
          if (flow != null) ...[
            const SizedBox(height: 12),
            _SectionCard(
              title: 'Approval Flow',
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    flow.statusLabel ?? '',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: Theme.of(context).hintColor,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ...flow.steps.map((s) => _ApprovalStepRow(step: s)),
                ],
              ),
            ),
          ],
          const SizedBox(height: 16),
          if (canAct)
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red,
                      side: const BorderSide(color: Colors.red),
                    ),
                    onPressed: _busy ? null : _reject,
                    icon: const Icon(Icons.close),
                    label: const Text('Reject'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green,
                      foregroundColor: Colors.white,
                    ),
                    onPressed: _busy ? null : _approve,
                    icon: const Icon(Icons.check),
                    label: Text(approveLabel),
                  ),
                ),
              ],
            ),
          if (_i.stockUpdated)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: Row(
                children: [
                  const Icon(Icons.inventory, size: 16, color: Colors.green),
                  const SizedBox(width: 6),
                  Text(
                    'Stock updated ${fmtDate(_i.stockUpdatedAt)}',
                    style: const TextStyle(
                        fontSize: 12, color: Colors.green),
                  ),
                ],
              ),
            )
          else if (_i.canUpdateStock)
            Padding(
              padding: const EdgeInsets.only(top: 12),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _busy ? null : _updateStock,
                  icon: const Icon(Icons.sync),
                  label: const Text('Update Stock'),
                ),
              ),
            ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Future<void> _approve() async {
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Approve Inspection'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: const InputDecoration(
            hintText: 'Comment (optional)',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Confirm'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    await _run(() => ref
        .read(inspectionsRepositoryProvider)
        .approve(_i.id, comment: controller.text.trim()));
  }

  Future<void> _reject() async {
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Reject Inspection'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: const InputDecoration(
            hintText: 'Rejection reason',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Reject'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    final reason = controller.text.trim();
    if (reason.isEmpty) {
      _snack('A rejection reason is required.', isError: true);
      return;
    }
    await _run(
        () => ref.read(inspectionsRepositoryProvider).reject(_i.id, reason));
  }

  Future<void> _updateStock() async {
    await _run(
        () => ref.read(inspectionsRepositoryProvider).updateStock(_i.id));
  }

  Future<void> _run(Future<InspectionDto> Function() action) async {
    setState(() => _busy = true);
    try {
      await action();
      await widget.onChanged();
      if (mounted) _snack('Done.');
    } catch (e) {
      if (mounted) _snack(procurementErrorMessage(e), isError: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _snack(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }
}

class _ApprovalStepRow extends StatelessWidget {
  final InspectionApprovalStep step;
  const _ApprovalStepRow({required this.step});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final done = step.action.toUpperCase() != 'PENDING';
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            done ? Icons.check_circle : Icons.radio_button_unchecked,
            size: 18,
            color: done ? Colors.green : theme.hintColor,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${step.roleName}${step.stepAction != null ? ' · ${step.stepAction}' : ''}',
                  style: const TextStyle(
                      fontSize: 12, fontWeight: FontWeight.w600),
                ),
                Text(
                  step.action +
                      (step.approverName != null
                          ? ' — ${step.approverName}'
                          : '') +
                      (step.date != null ? ' (${step.date})' : ''),
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: theme.hintColor),
                ),
                if ((step.comment ?? '').isNotEmpty)
                  Text(step.comment!,
                      style: theme.textTheme.bodySmall
                          ?.copyWith(fontStyle: FontStyle.italic)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// ---------------------------- shared helpers ------------------------------

class _SectionCard extends StatelessWidget {
  final String title;
  final Widget child;

  const _SectionCard({required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
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
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
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
  final String? value;
  final Widget? valueWidget;
  final Color? valueColor;

  const _InfoRow({
    required this.label,
    this.value,
    this.valueWidget,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: TextStyle(fontSize: 12, color: theme.hintColor),
            ),
          ),
          Expanded(
            flex: 3,
            child: Align(
              alignment: Alignment.centerRight,
              child: valueWidget ??
                  Text(
                    value ?? '—',
                    textAlign: TextAlign.right,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: valueColor,
                    ),
                  ),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final IconData icon;
  final String message;

  const _EmptyView({required this.icon, required this.message});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 64, color: theme.hintColor),
        const SizedBox(height: 12),
        Center(
          child: Text(message, style: TextStyle(color: theme.hintColor)),
        ),
      ],
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;

  const _ErrorView({required this.error, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 80),
        const Icon(Icons.error_outline, size: 48, color: Colors.red),
        const SizedBox(height: 12),
        const Center(
          child: Text('Failed to load',
              style: TextStyle(fontWeight: FontWeight.bold)),
        ),
        const SizedBox(height: 8),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              procurementErrorMessage(error),
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: theme.hintColor),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: const Text('Retry'),
          ),
        ),
      ],
    );
  }
}
