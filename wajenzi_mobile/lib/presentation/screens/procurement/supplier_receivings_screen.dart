import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/procurement/purchase_order_models.dart';
import '../../../data/repositories/purchase_orders_repository.dart';
import 'procurement_shared.dart';

final _searchProvider = StateProvider.autoDispose<String>((_) => '');
final _statusFilterProvider = StateProvider.autoDispose<String?>((_) => null);

final _receivingsProvider =
    FutureProvider.autoDispose<List<ReceivingListItem>>((ref) async {
  final repo = ref.watch(purchaseOrdersRepositoryProvider);
  final search = ref.watch(_searchProvider);
  final status = ref.watch(_statusFilterProvider);
  return repo.listReceivings(search: search, status: status);
});

final _receivingDetailProvider = FutureProvider.autoDispose
    .family<ReceivingDetail, int>((ref, id) async {
  final repo = ref.watch(purchaseOrdersRepositoryProvider);
  return repo.getReceiving(id);
});

class SupplierReceivingsScreen extends ConsumerWidget {
  const SupplierReceivingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_receivingsProvider);
    final status = ref.watch(_statusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: const Text('Supplier Receivings'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
            child: Column(
              children: [
                TextField(
                  onChanged: (v) =>
                      ref.read(_searchProvider.notifier).state = v,
                  decoration: InputDecoration(
                    hintText: 'Search receivings...',
                    prefixIcon: const Icon(Icons.search),
                    isDense: true,
                    border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 8),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _chip(ref, 'All', status == null, null),
                      const SizedBox(width: 8),
                      _chip(ref, 'Pending', status == 'pending', 'pending'),
                      const SizedBox(width: 8),
                      _chip(ref, 'Received', status == 'received',
                          'received'),
                      const SizedBox(width: 8),
                      _chip(ref, 'Inspected', status == 'inspected',
                          'inspected'),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_receivingsProvider);
                await ref.read(_receivingsProvider.future);
              },
              child: async.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(_receivingsProvider),
                ),
                data: (list) {
                  if (list.isEmpty) {
                    return _EmptyView(
                      icon: Icons.inventory_2_outlined,
                      label: 'No supplier receivings',
                    );
                  }
                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(12, 8, 12, 90),
                    itemCount: list.length,
                    itemBuilder: (context, i) => _ReceivingCard(
                      receiving: list[i],
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) =>
                              _ReceivingDetailScreen(id: list[i].id),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _chip(WidgetRef ref, String label, bool selected, String? value) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) =>
          ref.read(_statusFilterProvider.notifier).state = value,
    );
  }
}

class _ReceivingCard extends StatelessWidget {
  final ReceivingListItem receiving;
  final VoidCallback onTap;
  const _ReceivingCard({required this.receiving, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final subtitle = [
      receiving.purchaseDocumentNumber,
      receiving.projectName,
      receiving.supplierName,
    ].whereType<String>().where((v) => v.isNotEmpty).join(' • ');
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 6),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(receiving.receivingNumber,
                        style: const TextStyle(
                            fontSize: 15, fontWeight: FontWeight.w700)),
                  ),
                  ProcurementStatusChip(status: receiving.status),
                ],
              ),
              if (subtitle.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(subtitle,
                    style: Theme.of(context).textTheme.bodySmall,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis),
              ],
              const SizedBox(height: 8),
              Row(
                children: [
                  _meta(context, Icons.event_rounded,
                      fmtDate(receiving.date)),
                  const SizedBox(width: 10),
                  _meta(context, Icons.local_shipping_rounded,
                      receiving.deliveryNoteNumber ?? '-'),
                  const SizedBox(width: 10),
                  _meta(context, Icons.inventory_2_rounded,
                      fmtQty(receiving.quantityDelivered)),
                  const Spacer(),
                  if (receiving.needsInspection)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.orange.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Text('NEEDS INSPECTION',
                          style: TextStyle(
                              color: Colors.orange,
                              fontSize: 9,
                              fontWeight: FontWeight.w700)),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _meta(BuildContext context, IconData icon, String label) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 13, color: Theme.of(context).hintColor),
        const SizedBox(width: 3),
        Text(label,
            style: TextStyle(
                fontSize: 11, color: Theme.of(context).hintColor)),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Detail
// ─────────────────────────────────────────────────────────────────────────────
class _ReceivingDetailScreen extends ConsumerWidget {
  final int id;
  const _ReceivingDetailScreen({required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(_receivingDetailProvider(id));
    return Scaffold(
      appBar: AppBar(title: const Text('Supplier Receiving')),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_receivingDetailProvider(id)),
        ),
        data: (r) => _body(context, ref, r),
      ),
    );
  }

  Widget _body(BuildContext context, WidgetRef ref, ReceivingDetail r) {
    final canEditOverheads = hasPermission(ref, 'Supplier Receivings');
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
      children: [
        Row(
          children: [
            Expanded(
              child: Text(r.receivingNumber,
                  style: const TextStyle(
                      fontSize: 20, fontWeight: FontWeight.w800)),
            ),
            ProcurementStatusChip(status: r.status),
          ],
        ),
        const SizedBox(height: 12),
        _InfoCard(rows: [
          _InfoRow('Purchase Order', r.purchaseDocumentNumber ?? '-'),
          _InfoRow('Project', r.projectName ?? '-'),
          _InfoRow('Supplier', r.supplierName ?? '-'),
          _InfoRow('Received By', r.receivedByName ?? '-'),
          _InfoRow('Delivery Date', fmtDate(r.date)),
          _InfoRow('Delivery Note', r.deliveryNoteNumber ?? '-'),
          _InfoRow('Qty Ordered', fmtQty(r.quantityOrdered)),
          _InfoRow('Qty Delivered', fmtQty(r.quantityDelivered)),
          _InfoRow('Condition', _titleCase(r.condition)),
          if ((r.description ?? '').isNotEmpty)
            _InfoRow('Description', r.description!),
        ]),
        const SizedBox(height: 16),
        Row(
          children: [
            const Text('Inspection',
                style: TextStyle(fontWeight: FontWeight.w700)),
            const Spacer(),
            Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: (r.hasInspection ? Colors.green : Colors.orange)
                    .withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                r.hasInspection
                    ? 'Inspected'
                    : (r.needsInspection ? 'Needs Inspection' : 'Pending'),
                style: TextStyle(
                    color: r.hasInspection ? Colors.green : Colors.orange,
                    fontSize: 11,
                    fontWeight: FontWeight.w700),
              ),
            ),
          ],
        ),
        if (r.inspections.isNotEmpty) ...[
          const SizedBox(height: 8),
          ...r.inspections.map((ins) => Card(
                child: ListTile(
                  dense: true,
                  title: Text(ins['inspection_number']?.toString() ??
                      'Inspection'),
                  subtitle: Text(
                    'Accepted ${fmtQty((ins['quantity_accepted'] as num?)?.toDouble())} • ${ins['overall_result'] ?? ''}',
                  ),
                  trailing:
                      ProcurementStatusChip(status: ins['status']?.toString()),
                ),
              )),
        ],
        const SizedBox(height: 16),
        const Text('Items', style: TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        ...r.items.map((item) => Card(
              margin: const EdgeInsets.symmetric(vertical: 4),
              child: ListTile(
                dense: true,
                title: Text(item.description ?? 'Item'),
                subtitle: Text(
                  'Received ${fmtQty(item.quantityReceived)} / ${fmtQty(item.quantity)} ${item.unit ?? ''}',
                ),
                trailing: ProcurementStatusChip(status: item.status),
              ),
            )),
        const SizedBox(height: 16),
        const Text('Delivery Overheads',
            style: TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        _OverheadsForm(
          receiving: r,
          enabled: canEditOverheads && r.projectId != 0,
          onSaved: () => ref.invalidate(_receivingDetailProvider(id)),
        ),
      ],
    );
  }
}

class _OverheadsForm extends ConsumerStatefulWidget {
  final ReceivingDetail receiving;
  final bool enabled;
  final VoidCallback onSaved;
  const _OverheadsForm({
    required this.receiving,
    required this.enabled,
    required this.onSaved,
  });

  @override
  ConsumerState<_OverheadsForm> createState() => _OverheadsFormState();
}

class _OverheadsFormState extends ConsumerState<_OverheadsForm> {
  late final TextEditingController _loadingCtrl;
  late final TextEditingController _offloadingCtrl;
  late final TextEditingController _transportCtrl;
  late final TextEditingController _notesCtrl;
  DateTime _expenseDate = DateTime.now();
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    final o = widget.receiving.overheads;
    _loadingCtrl = TextEditingController(
        text: (o != null && o.loading > 0) ? fmtQty(o.loading) : '');
    _offloadingCtrl = TextEditingController(
        text: (o != null && o.offloading > 0) ? fmtQty(o.offloading) : '');
    _transportCtrl = TextEditingController(
        text: (o != null && o.transportation > 0)
            ? fmtQty(o.transportation)
            : '');
    _notesCtrl = TextEditingController(text: o?.notes ?? '');
    final parsed = DateTime.tryParse(o?.expenseDate ?? '');
    if (parsed != null) _expenseDate = parsed;
  }

  @override
  void dispose() {
    _loadingCtrl.dispose();
    _offloadingCtrl.dispose();
    _transportCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _busy = true);
    try {
      await ref.read(purchaseOrdersRepositoryProvider).storeReceivingOverheads(
            widget.receiving.id,
            loadingAmount: double.tryParse(_loadingCtrl.text.trim()),
            offloadingAmount: double.tryParse(_offloadingCtrl.text.trim()),
            transportationAmount:
                double.tryParse(_transportCtrl.text.trim()),
            notes: _notesCtrl.text.trim(),
            expenseDate: DateFormat('yyyy-MM-dd').format(_expenseDate),
          );
      widget.onSaved();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Delivery overheads saved.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(procurementErrorMessage(e))),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (widget.receiving.projectId == 0) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(14),
          child:
              Text('This receiving has no linked project; overheads cannot be recorded.'),
        ),
      );
    }
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _amountField('Loading Amount', _loadingCtrl),
            const SizedBox(height: 10),
            _amountField('Offloading Amount', _offloadingCtrl),
            const SizedBox(height: 10),
            _amountField('Transportation Amount', _transportCtrl),
            const SizedBox(height: 10),
            InkWell(
              onTap: widget.enabled
                  ? () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: _expenseDate,
                        firstDate: DateTime(2020),
                        lastDate: DateTime(2035),
                      );
                      if (picked != null) {
                        setState(() => _expenseDate = picked);
                      }
                    }
                  : null,
              child: InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Expense Date',
                  border: OutlineInputBorder(),
                  isDense: true,
                ),
                child:
                    Text(DateFormat('dd MMM yyyy').format(_expenseDate)),
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _notesCtrl,
              enabled: widget.enabled,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Notes (optional)',
                border: OutlineInputBorder(),
                isDense: true,
              ),
            ),
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: (!widget.enabled || _busy) ? null : _save,
                style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12)),
                child: _busy
                    ? const SizedBox(
                        height: 18,
                        width: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save Overheads'),
              ),
            ),
            if (!widget.enabled)
              const Padding(
                padding: EdgeInsets.only(top: 6),
                child: Text(
                  'You do not have permission to edit overheads.',
                  style: TextStyle(fontSize: 12),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _amountField(String label, TextEditingController ctrl) {
    return TextField(
      controller: ctrl,
      enabled: widget.enabled,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
        isDense: true,
        hintText: '0',
      ),
    );
  }
}

String _titleCase(String? s) {
  if (s == null || s.isEmpty) return '-';
  return s
      .split(RegExp(r'[_\s]+'))
      .where((w) => w.isNotEmpty)
      .map((w) => w[0].toUpperCase() + w.substring(1))
      .join(' ');
}

// ─────────────────────────────────────────────────────────────────────────────
// File-local small widgets
// ─────────────────────────────────────────────────────────────────────────────
class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(label,
                style: TextStyle(
                    fontSize: 13, color: Theme.of(context).hintColor)),
          ),
          Expanded(
            child: Text(value,
                style: const TextStyle(
                    fontSize: 13, fontWeight: FontWeight.w500)),
          ),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final List<Widget> rows;
  const _InfoCard({required this.rows});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
            crossAxisAlignment: CrossAxisAlignment.start, children: rows),
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final IconData icon;
  final String label;
  const _EmptyView({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 64, color: Theme.of(context).hintColor),
        const SizedBox(height: 12),
        Center(
          child: Text(label,
              style: TextStyle(color: Theme.of(context).hintColor)),
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
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 100),
        Icon(Icons.error_outline_rounded,
            size: 56, color: Colors.red.withValues(alpha: 0.7)),
        const SizedBox(height: 12),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(procurementErrorMessage(error),
                textAlign: TextAlign.center),
          ),
        ),
        const SizedBox(height: 12),
        Center(
          child: OutlinedButton(
              onPressed: onRetry, child: const Text('Retry')),
        ),
      ],
    );
  }
}
