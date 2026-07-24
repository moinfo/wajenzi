import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../data/models/procurement/inspection_models.dart';
import '../../../data/repositories/inspections_repository.dart';
import 'procurement_shared.dart';

final _createDataProvider = FutureProvider.autoDispose
    .family<InspectionCreateData, int>((ref, receivingId) async {
  final repo = ref.watch(inspectionsRepositoryProvider);
  return repo.createData(receivingId);
});

const List<String> _kConditions = [
  'excellent',
  'good',
  'acceptable',
  'poor',
  'rejected',
];

class MaterialInspectionCreateScreen extends ConsumerWidget {
  final int receivingId;
  const MaterialInspectionCreateScreen({super.key, required this.receivingId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dataAsync = ref.watch(_createDataProvider(receivingId));
    return Scaffold(
      appBar: AppBar(title: const Text('New Inspection')),
      body: dataAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_createDataProvider(receivingId)),
        ),
        data: (data) {
          if (data.existingInspectionId != null) {
            return _AlreadyInspectedView(
              inspectionId: data.existingInspectionId!,
            );
          }
          return _InspectionForm(receivingId: receivingId, data: data);
        },
      ),
    );
  }
}

class _AlreadyInspectedView extends StatelessWidget {
  final int inspectionId;
  const _AlreadyInspectedView({required this.inspectionId});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.info_outline, size: 48, color: Colors.orange),
            const SizedBox(height: 12),
            const Text(
              'This delivery has already been inspected.',
              textAlign: TextAlign.center,
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Back'),
            ),
          ],
        ),
      ),
    );
  }
}

class _InspectionForm extends ConsumerStatefulWidget {
  final int receivingId;
  final InspectionCreateData data;

  const _InspectionForm({required this.receivingId, required this.data});

  @override
  ConsumerState<_InspectionForm> createState() => _InspectionFormState();
}

class _InspectionFormState extends ConsumerState<_InspectionForm> {
  final _formKey = GlobalKey<FormState>();
  int? _boqItemId;
  String _condition = 'good';
  late final TextEditingController _acceptedCtrl;
  final _rejectionCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  final Map<String, bool> _checklist = {};
  bool _saving = false;

  double get _delivered => widget.data.receiving.quantityDelivered;

  @override
  void initState() {
    super.initState();
    _acceptedCtrl =
        TextEditingController(text: _trimQty(_delivered));
    for (final key in widget.data.criteriaChecklist.keys) {
      _checklist[key] = true;
    }
  }

  @override
  void dispose() {
    _acceptedCtrl.dispose();
    _rejectionCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  String _trimQty(double v) {
    if (v == v.roundToDouble()) return v.toStringAsFixed(0);
    return v.toString();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final receiving = widget.data.receiving;
    final boqItems = widget.data.boqItems;
    final accepted = double.tryParse(_acceptedCtrl.text) ?? 0;
    final rejected = (_delivered - accepted).clamp(0, _delivered);

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _SectionCard(
            title: receiving.receivingNumber ?? 'Delivery',
            child: Column(
              children: [
                _InfoRow(label: 'Project', value: receiving.projectName ?? '—'),
                _InfoRow(label: 'Supplier', value: receiving.supplierName ?? '—'),
                _InfoRow(label: 'PO', value: receiving.purchaseNumber ?? '—'),
                _InfoRow(
                    label: 'Delivery note',
                    value: receiving.deliveryNoteNumber ?? '—'),
                _InfoRow(label: 'Date', value: fmtDate(receiving.date)),
                _InfoRow(
                    label: 'Qty delivered', value: fmtQty(_delivered)),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Inspection',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (boqItems.isNotEmpty) ...[
                  DropdownButtonFormField<int>(
                    initialValue: _boqItemId,
                    isExpanded: true,
                    decoration: const InputDecoration(
                      labelText: 'BOQ item (optional)',
                      border: OutlineInputBorder(),
                    ),
                    items: [
                      const DropdownMenuItem<int>(
                        value: null,
                        child: Text('—'),
                      ),
                      ...boqItems.map(
                        (b) => DropdownMenuItem<int>(
                          value: b.id,
                          child: Text(
                            '${b.itemCode ?? ''} ${b.description ?? ''}'.trim(),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ),
                    ],
                    onChanged: (v) => setState(() => _boqItemId = v),
                  ),
                  const SizedBox(height: 12),
                ],
                TextFormField(
                  controller: _acceptedCtrl,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(
                        RegExp(r'[0-9.]')),
                  ],
                  decoration: const InputDecoration(
                    labelText: 'Quantity accepted',
                    border: OutlineInputBorder(),
                  ),
                  onChanged: (_) => setState(() {}),
                  validator: (v) {
                    final val = double.tryParse(v ?? '');
                    if (val == null) return 'Enter a valid number';
                    if (val < 0) return 'Cannot be negative';
                    if (val > _delivered) {
                      return 'Cannot exceed delivered (${fmtQty(_delivered)})';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 6),
                Text(
                  'Rejected: ${fmtQty(rejected)}',
                  style: TextStyle(
                    fontSize: 12,
                    color: rejected > 0 ? Colors.red : theme.hintColor,
                  ),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: _condition,
                  decoration: const InputDecoration(
                    labelText: 'Overall condition',
                    border: OutlineInputBorder(),
                  ),
                  items: _kConditions
                      .map((c) => DropdownMenuItem<String>(
                            value: c,
                            child: Text(c[0].toUpperCase() + c.substring(1)),
                          ))
                      .toList(),
                  onChanged: (v) =>
                      setState(() => _condition = v ?? 'good'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Criteria Checklist',
            child: Column(
              children: widget.data.criteriaChecklist.entries.map((entry) {
                return CheckboxListTile(
                  contentPadding: EdgeInsets.zero,
                  dense: true,
                  controlAffinity: ListTileControlAffinity.leading,
                  value: _checklist[entry.key] ?? false,
                  title: Text(entry.value,
                      style: const TextStyle(fontSize: 13)),
                  onChanged: (v) =>
                      setState(() => _checklist[entry.key] = v ?? false),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            title: 'Notes',
            child: Column(
              children: [
                TextFormField(
                  controller: _rejectionCtrl,
                  maxLines: 2,
                  decoration: const InputDecoration(
                    labelText: 'Rejection reason (if any)',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _notesCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(
                    labelText: 'Inspection notes (optional)',
                    border: OutlineInputBorder(),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _saving ? null : _submit,
              icon: _saving
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.save),
              label: const Text('Save & Submit'),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    final accepted = double.tryParse(_acceptedCtrl.text) ?? 0;
    final projectId = widget.data.receiving.projectId;
    if (projectId == null) {
      _snack('This delivery has no linked project.', isError: true);
      return;
    }

    setState(() => _saving = true);
    try {
      final payload = <String, dynamic>{
        'supplier_receiving_id': widget.receivingId,
        'project_id': projectId,
        if (_boqItemId != null) 'boq_item_id': _boqItemId,
        'quantity_delivered': _delivered,
        'quantity_accepted': accepted,
        'overall_condition': _condition,
        'rejection_reason': _rejectionCtrl.text.trim(),
        'inspection_notes': _notesCtrl.text.trim(),
        'criteria_checklist': _checklist,
      };
      await ref.read(inspectionsRepositoryProvider).store(payload);
      if (mounted) {
        _snack('Inspection created.');
        Navigator.of(context).pop(true);
      }
    } catch (e) {
      if (mounted) _snack(procurementErrorMessage(e), isError: true);
    } finally {
      if (mounted) setState(() => _saving = false);
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
  final String value;

  const _InfoRow({required this.label, required this.value});

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
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(
                  fontSize: 12, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
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
