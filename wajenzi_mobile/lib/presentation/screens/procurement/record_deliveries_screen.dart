import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/procurement/purchase_order_models.dart';
import '../../../data/repositories/purchase_orders_repository.dart';
import 'procurement_shared.dart';

final _searchProvider = StateProvider.autoDispose<String>((_) => '');

final _pendingDeliveriesProvider =
    FutureProvider.autoDispose<List<PendingDeliveryDto>>((ref) async {
  final repo = ref.watch(purchaseOrdersRepositoryProvider);
  final search = ref.watch(_searchProvider);
  return repo.listPendingDeliveries(search: search);
});

final _poForDeliveryProvider = FutureProvider.autoDispose
    .family<PurchaseOrderDetail, int>((ref, id) async {
  final repo = ref.watch(purchaseOrdersRepositoryProvider);
  return repo.getPurchaseOrder(id);
});

class RecordDeliveriesScreen extends ConsumerWidget {
  const RecordDeliveriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_pendingDeliveriesProvider);
    final search = ref.watch(_searchProvider);
    final canRecord = hasPermission(ref, 'Record Deliveries');

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: const Text('Record Deliveries'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
            child: TextField(
              onChanged: (v) => ref.read(_searchProvider.notifier).state = v,
              decoration: InputDecoration(
                hintText: 'Search purchase orders...',
                prefixIcon: const Icon(Icons.search),
                isDense: true,
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_pendingDeliveriesProvider);
                await ref.read(_pendingDeliveriesProvider.future);
              },
              child: async.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  error: e,
                  onRetry: () =>
                      ref.invalidate(_pendingDeliveriesProvider),
                ),
                data: (list) {
                  if (list.isEmpty) {
                    return _EmptyView(
                      icon: Icons.local_shipping_outlined,
                      label:
                          'All approved purchase orders have been fully delivered.',
                    );
                  }
                  final _ = search; // search drives the provider
                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.fromLTRB(12, 8, 12, 90),
                    itemCount: list.length,
                    itemBuilder: (context, i) => _PendingCard(
                      po: list[i],
                      enabled: canRecord,
                      onTap: () {
                        if (!canRecord) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text(
                                  'You do not have permission to record deliveries.'),
                            ),
                          );
                          return;
                        }
                        Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) =>
                                _RecordDeliveryScreen(purchaseId: list[i].id),
                          ),
                        );
                      },
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

class _PendingCard extends StatelessWidget {
  final PendingDeliveryDto po;
  final bool enabled;
  final VoidCallback onTap;
  const _PendingCard(
      {required this.po, required this.enabled, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final subtitle =
        [po.projectName, po.supplierName, po.materialRequestNumber]
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
                    child: Text(po.documentNumber,
                        style: const TextStyle(
                            fontSize: 15, fontWeight: FontWeight.w700)),
                  ),
                  const Icon(Icons.local_shipping_rounded,
                      color: Colors.blue, size: 20),
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
              Wrap(
                spacing: 8,
                runSpacing: 4,
                children: [
                  _pill('${po.fullyReceivedCount} received', Colors.green),
                  if (po.partiallyReceivedCount > 0)
                    _pill('${po.partiallyReceivedCount} partial',
                        Colors.orange),
                  _pill('${po.pendingCount} pending', Colors.blueGrey),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _pill(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(label,
          style: TextStyle(
              color: color, fontSize: 11, fontWeight: FontWeight.w600)),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Delivery form screen
// ─────────────────────────────────────────────────────────────────────────────
class _RecordDeliveryScreen extends ConsumerStatefulWidget {
  final int purchaseId;
  const _RecordDeliveryScreen({required this.purchaseId});

  @override
  ConsumerState<_RecordDeliveryScreen> createState() =>
      _RecordDeliveryScreenState();
}

class _RecordDeliveryScreenState
    extends ConsumerState<_RecordDeliveryScreen> {
  final _noteNumberCtrl = TextEditingController();
  final _descriptionCtrl = TextEditingController();
  final Map<int, TextEditingController> _qtyCtrls = {};
  DateTime _date = DateTime.now();
  String _condition = 'good';
  String? _filePath;
  bool _busy = false;
  bool _initialised = false;

  @override
  void dispose() {
    _noteNumberCtrl.dispose();
    _descriptionCtrl.dispose();
    for (final c in _qtyCtrls.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _initControllers(PurchaseOrderDetail po) {
    if (_initialised) return;
    for (final item in po.items) {
      _qtyCtrls[item.id] = TextEditingController(
        text: item.quantityPending > 0
            ? fmtQty(item.quantityPending)
            : '0',
      );
    }
    _initialised = true;
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_poForDeliveryProvider(widget.purchaseId));
    return Scaffold(
      appBar: AppBar(title: const Text('Record Delivery')),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () =>
              ref.invalidate(_poForDeliveryProvider(widget.purchaseId)),
        ),
        data: (po) {
          _initControllers(po);
          return _buildForm(po);
        },
      ),
    );
  }

  Widget _buildForm(PurchaseOrderDetail po) {
    final pendingItems =
        po.items.where((i) => i.quantityPending > 0).toList();
    return Stack(
      children: [
        ListView(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
          children: [
            Text(po.documentNumber,
                style: const TextStyle(
                    fontSize: 18, fontWeight: FontWeight.w800)),
            Text(
              [po.projectName, po.supplierName]
                  .whereType<String>()
                  .join(' • '),
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 16),
            const Text('Items',
                style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 8),
            ...po.items.map(_buildItemRow),
            if (pendingItems.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Text(
                    'All items in this purchase order have been fully received.'),
              ),
            const SizedBox(height: 16),
            TextField(
              controller: _noteNumberCtrl,
              decoration: const InputDecoration(
                labelText: 'Delivery Note Number *',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            _DateField(
              label: 'Delivery Date',
              value: _date,
              onPick: (d) => setState(() => _date = d),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _condition,
              decoration: const InputDecoration(
                labelText: 'Condition',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'good', child: Text('Good')),
                DropdownMenuItem(
                    value: 'partial_damage',
                    child: Text('Partial Damage')),
                DropdownMenuItem(
                    value: 'damaged', child: Text('Damaged')),
              ],
              onChanged: (v) =>
                  setState(() => _condition = v ?? 'good'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _descriptionCtrl,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Description (optional)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            _AttachmentField(
              filePath: _filePath,
              label: 'Delivery Note (optional)',
              onPicked: (p) => setState(() => _filePath = p),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed:
                    (_busy || pendingItems.isEmpty) ? null : () => _submit(po),
                style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14)),
                child: const Text('Record Delivery'),
              ),
            ),
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

  Widget _buildItemRow(PurchaseItemDto item) {
    final ctrl = _qtyCtrls[item.id];
    final fully = item.isFullyReceived;
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
              'Ordered ${fmtQty(item.quantity)} ${item.unit ?? ''}  •  Received ${fmtQty(item.quantityReceived)}  •  Pending ${fmtQty(item.quantityPending)}',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 8),
            if (fully)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.green.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Text('Fully Received',
                    style: TextStyle(
                        color: Colors.green,
                        fontSize: 12,
                        fontWeight: FontWeight.w600)),
              )
            else
              TextField(
                controller: ctrl,
                keyboardType: const TextInputType.numberWithOptions(
                    decimal: true),
                decoration: InputDecoration(
                  labelText:
                      'Qty delivering (max ${fmtQty(item.quantityPending)})',
                  isDense: true,
                  border: const OutlineInputBorder(),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _submit(PurchaseOrderDetail po) async {
    if (_noteNumberCtrl.text.trim().isEmpty) {
      _snack('Delivery note number is required.');
      return;
    }
    final items = <Map<String, dynamic>>[];
    double totalQty = 0;
    for (final item in po.items) {
      if (item.isFullyReceived) continue;
      final raw = _qtyCtrls[item.id]?.text.trim() ?? '';
      final qty = double.tryParse(raw) ?? 0;
      if (qty < 0) {
        _snack('Quantity cannot be negative.');
        return;
      }
      if (qty > item.quantityPending) {
        _snack(
            'Quantity for "${item.description}" exceeds pending (${fmtQty(item.quantityPending)}).');
        return;
      }
      if (qty > 0) {
        items.add({'purchase_item_id': item.id, 'quantity': qty});
        totalQty += qty;
      }
    }
    if (totalQty <= 0) {
      _snack('Enter a delivery quantity for at least one item.');
      return;
    }

    setState(() => _busy = true);
    try {
      await ref.read(purchaseOrdersRepositoryProvider).storeDelivery(
            po.id,
            deliveryNoteNumber: _noteNumberCtrl.text.trim(),
            date: DateFormat('yyyy-MM-dd').format(_date),
            condition: _condition,
            description: _descriptionCtrl.text.trim(),
            items: items,
            filePath: _filePath,
          );
      ref.invalidate(_pendingDeliveriesProvider);
      if (mounted) {
        Navigator.of(context).pop();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text(
                  'Delivery recorded. It will appear in Material Inspections for review.')),
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
}

// ─────────────────────────────────────────────────────────────────────────────
// File-local small widgets
// ─────────────────────────────────────────────────────────────────────────────
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
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(label,
                textAlign: TextAlign.center,
                style: TextStyle(color: Theme.of(context).hintColor)),
          ),
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
