import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/procurement/purchase_order_models.dart';
import '../../../data/repositories/purchase_orders_repository.dart';
import 'procurement_shared.dart';

final _poSearchProvider = StateProvider.autoDispose<String>((_) => '');
final _poStatusFilterProvider = StateProvider.autoDispose<String?>((_) => null);

final _purchaseOrdersProvider =
    FutureProvider.autoDispose<List<PurchaseOrderListItem>>((ref) async {
  final repo = ref.watch(purchaseOrdersRepositoryProvider);
  final search = ref.watch(_poSearchProvider);
  final status = ref.watch(_poStatusFilterProvider);
  return repo.listPurchaseOrders(search: search, status: status);
});

final _poDetailProvider = FutureProvider.autoDispose
    .family<PurchaseOrderDetail, int>((ref, id) async {
  final repo = ref.watch(purchaseOrdersRepositoryProvider);
  return repo.getPurchaseOrder(id);
});

// ─────────────────────────────────────────────────────────────────────────────
// List screen
// ─────────────────────────────────────────────────────────────────────────────
class PurchaseOrdersScreen extends ConsumerWidget {
  const PurchaseOrdersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_purchaseOrdersProvider);
    final search = ref.watch(_poSearchProvider);
    final status = ref.watch(_poStatusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: const Text('Purchase Orders'),
      ),
      body: Column(
        children: [
          _SearchAndStatusFilter(
            search: search,
            status: status,
            hint: 'Search purchase orders...',
            statuses: const ['pending', 'submitted', 'approved', 'rejected'],
            onSearchChange: (v) => ref.read(_poSearchProvider.notifier).state = v,
            onStatusChange: (v) =>
                ref.read(_poStatusFilterProvider.notifier).state = v,
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_purchaseOrdersProvider);
                await ref.read(_purchaseOrdersProvider.future);
              },
              child: async.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(_purchaseOrdersProvider),
                ),
                data: (orders) {
                  if (orders.isEmpty) {
                    return _EmptyView(
                      icon: Icons.shopping_cart_outlined,
                      label: 'No purchase orders',
                    );
                  }
                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(12, 8, 12, 90),
                    itemCount: orders.length,
                    itemBuilder: (context, i) => _PoCard(
                      po: orders[i],
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) =>
                              _PurchaseOrderDetailScreen(id: orders[i].id),
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
}

class _PoCard extends StatelessWidget {
  final PurchaseOrderListItem po;
  final VoidCallback onTap;
  const _PoCard({required this.po, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final subtitle = [po.projectName, po.supplierName, po.materialRequestNumber]
        .whereType<String>()
        .where((v) => v.isNotEmpty)
        .join(' • ');
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
                    child: Text(
                      po.documentNumber,
                      style: const TextStyle(
                          fontSize: 15, fontWeight: FontWeight.w700),
                    ),
                  ),
                  ProcurementStatusChip(status: po.status),
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
                  _MetaChip(
                      icon: Icons.attach_money_rounded,
                      label: fmtMoney(po.totalAmount)),
                  const SizedBox(width: 8),
                  _MetaChip(
                      icon: Icons.receipt_long_rounded,
                      label: '${po.purchaseItemsCount} items'),
                  const SizedBox(width: 8),
                  _MetaChip(
                      icon: Icons.event_rounded, label: fmtDate(po.date)),
                  const Spacer(),
                  _PaymentBadge(po: po),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PaymentBadge extends StatelessWidget {
  final PurchaseOrderListItem po;
  const _PaymentBadge({required this.po});

  @override
  Widget build(BuildContext context) {
    String label;
    Color color;
    if (po.isClosed) {
      label = 'CLOSED';
      color = Colors.blueGrey;
    } else if (po.isPaymentUploaded) {
      label = 'PAID';
      color = Colors.green;
    } else if (normalizeStatus(po.status) == 'APPROVED') {
      label = 'PENDING PAYMENT';
      color = Colors.orange;
    } else {
      return const SizedBox.shrink();
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(label,
          style: TextStyle(
              color: color, fontSize: 10, fontWeight: FontWeight.w700)),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Detail screen
// ─────────────────────────────────────────────────────────────────────────────
class _PurchaseOrderDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const _PurchaseOrderDetailScreen({required this.id});

  @override
  ConsumerState<_PurchaseOrderDetailScreen> createState() =>
      _PurchaseOrderDetailScreenState();
}

class _PurchaseOrderDetailScreenState
    extends ConsumerState<_PurchaseOrderDetailScreen> {
  bool _busy = false;

  Future<void> _run(Future<void> Function() action, String okMsg) async {
    setState(() => _busy = true);
    try {
      await action();
      ref.invalidate(_poDetailProvider(widget.id));
      ref.invalidate(_purchaseOrdersProvider);
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(okMsg)));
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
    final async = ref.watch(_poDetailProvider(widget.id));
    return Scaffold(
      appBar: AppBar(
        title: const Text('Purchase Order'),
        actions: [
          async.maybeWhen(
            data: (po) {
              if (normalizeStatus(po.status) != 'APPROVED') {
                return PopupMenuButton<String>(
                  onSelected: (v) {
                    if (v == 'delete') _confirmDelete();
                  },
                  itemBuilder: (_) => const [
                    PopupMenuItem(value: 'delete', child: Text('Delete')),
                  ],
                );
              }
              return const SizedBox.shrink();
            },
            orElse: () => const SizedBox.shrink(),
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_poDetailProvider(widget.id)),
        ),
        data: (po) => _buildBody(po),
      ),
    );
  }

  Widget _buildBody(PurchaseOrderDetail po) {
    return Stack(
      children: [
        ListView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(po.documentNumber,
                      style: const TextStyle(
                          fontSize: 20, fontWeight: FontWeight.w800)),
                ),
                ProcurementStatusChip(status: po.status),
              ],
            ),
            const SizedBox(height: 12),
            _InfoCard(rows: [
              _InfoRow('Project', po.projectName ?? '-'),
              _InfoRow('Supplier', po.supplierName ?? '-'),
              _InfoRow('Material Request', po.materialRequestNumber ?? '-'),
              _InfoRow('Date', fmtDate(po.date)),
              _InfoRow('Subtotal', fmtMoney(po.amountVatExc)),
              _InfoRow('VAT', fmtMoney(po.vatAmount)),
              _InfoRow('Total', fmtMoney(po.totalAmount)),
              _InfoRow('Payment Terms', po.paymentTerms ?? '-'),
              _InfoRow('Expected Delivery', fmtDate(po.expectedDeliveryDate)),
              _InfoRow('Created By', po.createdByName ?? '-'),
            ]),
            const SizedBox(height: 16),
            _SectionTitle('Items (${po.items.length})'),
            ...po.items.map(_buildItemTile),
            if (po.approvalFlow != null) ...[
              const SizedBox(height: 16),
              _SectionTitle('Approval Flow'),
              _ApprovalFlowCard(flow: po.approvalFlow!),
            ],
            if (normalizeStatus(po.status) == 'APPROVED') ...[
              const SizedBox(height: 16),
              _SectionTitle('Payment'),
              _PaymentCard(po: po),
            ],
            const SizedBox(height: 20),
            _buildActions(po),
          ],
        ),
        if (_busy)
          Container(
            color: Colors.black.withValues(alpha: 0.15),
            child: const Center(child: CircularProgressIndicator()),
          ),
      ],
    );
  }

  Widget _buildItemTile(PurchaseItemDto item) {
    final pct = item.quantity > 0
        ? (item.quantityReceived / item.quantity).clamp(0.0, 1.0)
        : 0.0;
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 5),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(item.description,
                style: const TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 4),
            Text(
              '${fmtQty(item.quantityReceived)} / ${fmtQty(item.quantity)} ${item.unit ?? ''} received  •  ${fmtMoney(item.unitPrice)}/unit',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 8),
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: LinearProgressIndicator(
                value: pct,
                minHeight: 6,
                backgroundColor: Colors.grey.withValues(alpha: 0.2),
                valueColor: AlwaysStoppedAnimation(
                  item.isFullyReceived ? Colors.green : Colors.orange,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActions(PurchaseOrderDetail po) {
    final flow = po.approvalFlow;
    final children = <Widget>[];

    if (flow != null && flow.canBeSubmitted) {
      children.add(_actionButton(
        'Submit for Approval',
        Icons.send_rounded,
        Colors.blue,
        () => _run(
          () => ref.read(purchaseOrdersRepositoryProvider).submit(po.id),
          'Submitted for approval.',
        ),
      ));
    }
    if (flow != null && flow.canBeApproved) {
      children.add(_actionButton(
        'Approve',
        Icons.check_circle_rounded,
        Colors.green,
        () => _run(
          () => ref.read(purchaseOrdersRepositoryProvider).approve(po.id),
          'Purchase order approved.',
        ),
      ));
      children.add(_actionButton(
        'Reject',
        Icons.cancel_rounded,
        Colors.red,
        _promptReject,
      ));
    }
    if (po.canUploadPayment &&
        hasPermission(ref, 'Upload Payment Attachment')) {
      children.add(_actionButton(
        'Upload Payment Attachment',
        Icons.upload_file_rounded,
        Colors.teal,
        () => _openPaymentSheet(po),
      ));
    }
    if (po.canClose && hasPermission(ref, 'Close Purchase Order')) {
      children.add(_actionButton(
        'Close Purchase Order',
        Icons.lock_rounded,
        Colors.blueGrey,
        _confirmClose,
      ));
    }

    if (children.isEmpty) return const SizedBox.shrink();
    return Column(children: children);
  }

  Widget _actionButton(
      String label, IconData icon, Color color, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: SizedBox(
        width: double.infinity,
        child: ElevatedButton.icon(
          onPressed: _busy ? null : onTap,
          icon: Icon(icon, size: 18),
          label: Text(label),
          style: ElevatedButton.styleFrom(
            backgroundColor: color,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 14),
          ),
        ),
      ),
    );
  }

  void _confirmDelete() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Purchase Order'),
        content: const Text(
            'Are you sure you want to delete this purchase order?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Delete',
                  style: TextStyle(color: Colors.red))),
        ],
      ),
    );
    if (ok != true) return;
    await _run(
      () => ref.read(purchaseOrdersRepositoryProvider).deletePurchaseOrder(widget.id),
      'Purchase order deleted.',
    );
    if (mounted) Navigator.of(context).pop();
  }

  void _confirmClose() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Close Purchase Order'),
        content: const Text(
            'Mark this purchase order as closed? This is final.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Close')),
        ],
      ),
    );
    if (ok != true) return;
    await _run(
      () => ref.read(purchaseOrdersRepositoryProvider).close(widget.id),
      'Purchase order closed.',
    );
  }

  void _promptReject() async {
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Reject Purchase Order'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: const InputDecoration(
            hintText: 'Reason for rejection',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, controller.text.trim()),
            child: const Text('Reject', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (reason == null || reason.isEmpty) return;
    await _run(
      () => ref.read(purchaseOrdersRepositoryProvider).reject(widget.id, reason),
      'Purchase order rejected.',
    );
  }

  void _openPaymentSheet(PurchaseOrderDetail po) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _PaymentUploadSheet(
        purchaseId: po.id,
        onDone: () {
          ref.invalidate(_poDetailProvider(widget.id));
          ref.invalidate(_purchaseOrdersProvider);
        },
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Payment upload bottom sheet
// ─────────────────────────────────────────────────────────────────────────────
class _PaymentUploadSheet extends ConsumerStatefulWidget {
  final int purchaseId;
  final VoidCallback onDone;
  const _PaymentUploadSheet({required this.purchaseId, required this.onDone});

  @override
  ConsumerState<_PaymentUploadSheet> createState() =>
      _PaymentUploadSheetState();
}

class _PaymentUploadSheetState extends ConsumerState<_PaymentUploadSheet> {
  final _refCtrl = TextEditingController();
  final _noteCtrl = TextEditingController();
  DateTime _date = DateTime.now();
  String? _filePath;
  bool _busy = false;

  @override
  void dispose() {
    _refCtrl.dispose();
    _noteCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_refCtrl.text.trim().isEmpty) {
      _snack('Payment reference is required.');
      return;
    }
    if (_filePath == null) {
      _snack('A payment attachment is required.');
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(purchaseOrdersRepositoryProvider).uploadPayment(
            widget.purchaseId,
            paymentDate: DateFormat('yyyy-MM-dd').format(_date),
            paymentReference: _refCtrl.text.trim(),
            paymentNote: _noteCtrl.text.trim(),
            filePath: _filePath!,
          );
      widget.onDone();
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Payment attachment uploaded.')),
        );
      }
    } catch (e) {
      _snack(procurementErrorMessage(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _snack(String m) {
    if (mounted) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(m)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Container(
      padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + bottom),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
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
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const Text('Upload Payment Attachment',
                style:
                    TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 16),
            _DateField(
              label: 'Payment Date',
              value: _date,
              onPick: (d) => setState(() => _date = d),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _refCtrl,
              decoration: const InputDecoration(
                labelText: 'Payment Reference *',
                hintText: 'e.g. CHQ-001234 or TT-20260507',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _noteCtrl,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Note (optional)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            _AttachmentField(
              filePath: _filePath,
              label: 'Payment Attachment * (pdf/jpg/png)',
              onPicked: (p) => setState(() => _filePath = p),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _busy ? null : _submit,
                style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14)),
                child: _busy
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Upload'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Payment / approval / info sub-widgets
// ─────────────────────────────────────────────────────────────────────────────
class _PaymentCard extends StatelessWidget {
  final PurchaseOrderDetail po;
  const _PaymentCard({required this.po});

  @override
  Widget build(BuildContext context) {
    String badge;
    Color color;
    if (po.isClosed) {
      badge = 'CLOSED';
      color = Colors.blueGrey;
    } else if (po.isPaymentUploaded) {
      badge = 'PAID';
      color = Colors.green;
    } else {
      badge = 'PENDING PAYMENT';
      color = Colors.orange;
    }
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Text('Payment Status',
                    style: TextStyle(fontWeight: FontWeight.w600)),
                const Spacer(),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: color.withValues(alpha: 0.5)),
                  ),
                  child: Text(badge,
                      style: TextStyle(
                          color: color,
                          fontSize: 11,
                          fontWeight: FontWeight.w700)),
                ),
              ],
            ),
            if (po.isPaymentUploaded || po.isClosed) ...[
              const SizedBox(height: 8),
              _InfoRow('Payment Date', fmtDate(po.paymentDate)),
              _InfoRow('Reference', po.paymentReference ?? '-'),
              _InfoRow('Uploaded By', po.paymentUploadedBy?.name ?? '-'),
              if ((po.paymentNote ?? '').isNotEmpty)
                _InfoRow('Note', po.paymentNote!),
              if ((po.paymentAttachmentUrl ?? '').isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 6),
                  child: Text(
                    'Attachment: ${po.paymentAttachmentUrl}',
                    style: const TextStyle(
                        fontSize: 12, color: Colors.blue),
                  ),
                ),
            ] else
              const Padding(
                padding: EdgeInsets.only(top: 8),
                child: Text(
                  'Awaiting Finance to upload payment proof.',
                  style: TextStyle(fontSize: 13),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _ApprovalFlowCard extends StatelessWidget {
  final ApprovalFlowDto flow;
  const _ApprovalFlowCard({required this.flow});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(flow.statusLabel,
                style: const TextStyle(fontWeight: FontWeight.w700)),
            if (flow.nextRoleName != null && !flow.isCompleted) ...[
              const SizedBox(height: 4),
              Text('Next: ${flow.nextRoleName}',
                  style: Theme.of(context).textTheme.bodySmall),
            ],
            if (flow.steps.isNotEmpty) const SizedBox(height: 8),
            ...flow.steps.map((s) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(
                        normalizeStatus(s.action) == 'APPROVED'
                            ? Icons.check_circle
                            : normalizeStatus(s.action) == 'REJECTED'
                                ? Icons.cancel
                                : Icons.radio_button_unchecked,
                        size: 18,
                        color: normalizeStatus(s.action) == 'APPROVED'
                            ? Colors.green
                            : normalizeStatus(s.action) == 'REJECTED'
                                ? Colors.red
                                : Colors.grey,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(s.roleName,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w600)),
                            Text(
                              '${s.action}${s.approverName != null ? ' • ${s.approverName}' : ''}${s.date != null ? ' • ${s.date}' : ''}',
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                            if ((s.comment ?? '').isNotEmpty)
                              Text('"${s.comment}"',
                                  style: Theme.of(context)
                                      .textTheme
                                      .bodySmall
                                      ?.copyWith(
                                          fontStyle: FontStyle.italic)),
                          ],
                        ),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Shared small widgets (file-local)
// ─────────────────────────────────────────────────────────────────────────────
class _SearchAndStatusFilter extends StatelessWidget {
  final String search;
  final String? status;
  final String hint;
  final List<String> statuses;
  final ValueChanged<String> onSearchChange;
  final ValueChanged<String?> onStatusChange;

  const _SearchAndStatusFilter({
    required this.search,
    required this.status,
    required this.hint,
    required this.statuses,
    required this.onSearchChange,
    required this.onStatusChange,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
      child: Column(
        children: [
          TextField(
            onChanged: onSearchChange,
            decoration: InputDecoration(
              hintText: hint,
              prefixIcon: const Icon(Icons.search),
              isDense: true,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
          const SizedBox(height: 8),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _chip(context, 'All', status == null,
                    () => onStatusChange(null)),
                ...statuses.map((s) => Padding(
                      padding: const EdgeInsets.only(left: 8),
                      child: _chip(
                        context,
                        s[0].toUpperCase() + s.substring(1),
                        status == s,
                        () => onStatusChange(s),
                      ),
                    )),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _chip(
      BuildContext context, String label, bool selected, VoidCallback onTap) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onTap(),
    );
  }
}

class _MetaChip extends StatelessWidget {
  final IconData icon;
  final String label;
  const _MetaChip({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
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

class _SectionTitle extends StatelessWidget {
  final String title;
  const _SectionTitle(this.title);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(title,
          style:
              const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
    );
  }
}

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
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: rows),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final DateTime value;
  final ValueChanged<DateTime> onPick;
  const _DateField(
      {required this.label, required this.value, required this.onPick});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: value,
          firstDate: DateTime(2020),
          lastDate: DateTime(2035),
        );
        if (picked != null) onPick(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
        ),
        child: Text(DateFormat('dd MMM yyyy').format(value)),
      ),
    );
  }
}

class _AttachmentField extends StatelessWidget {
  final String? filePath;
  final String label;
  final ValueChanged<String?> onPicked;
  const _AttachmentField({
    required this.filePath,
    required this.label,
    required this.onPicked,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style:
                TextStyle(fontSize: 12, color: Theme.of(context).hintColor)),
        const SizedBox(height: 6),
        InkWell(
          onTap: () => _pick(context),
          borderRadius: BorderRadius.circular(10),
          child: Container(
            width: double.infinity,
            padding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Theme.of(context).dividerColor),
            ),
            child: Row(
              children: [
                Icon(
                  filePath != null
                      ? Icons.check_circle_rounded
                      : Icons.attach_file_rounded,
                  size: 18,
                  color: filePath != null
                      ? Colors.green
                      : Theme.of(context).hintColor,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    filePath != null
                        ? filePath!.split('/').last
                        : 'No file chosen',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (filePath != null)
                  GestureDetector(
                    onTap: () => onPicked(null),
                    child: const Icon(Icons.close_rounded,
                        size: 18, color: Colors.red),
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  void _pick(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.insert_drive_file_rounded),
              title: const Text('Choose file (pdf / image)'),
              onTap: () async {
                Navigator.pop(ctx);
                final result = await FilePicker.platform.pickFiles(
                  type: FileType.custom,
                  allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png'],
                );
                final path = result?.files.single.path;
                if (path != null) onPicked(path);
              },
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: const Text('Take photo'),
              onTap: () async {
                Navigator.pop(ctx);
                final picked = await ImagePicker().pickImage(
                  source: ImageSource.camera,
                  imageQuality: 80,
                );
                if (picked != null) onPicked(picked.path);
              },
            ),
          ],
        ),
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
