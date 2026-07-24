import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../data/models/procurement/material_request_models.dart';
import '../../../data/repositories/material_requests_repository.dart';
import '../../providers/settings_provider.dart';
import 'procurement_shared.dart';

/// Reference data (projects always; per-project BOQ items when projectId set).
final _createReferenceProvider = FutureProvider.autoDispose
    .family<MrReferenceData, int?>((ref, projectId) async {
  return ref
      .read(materialRequestsRepositoryProvider)
      .referenceData(projectId: projectId);
});

class _FreeTextRow {
  final TextEditingController descCtrl = TextEditingController();
  final TextEditingController qtyCtrl = TextEditingController();
  final TextEditingController unitCtrl = TextEditingController();

  void dispose() {
    descCtrl.dispose();
    qtyCtrl.dispose();
    unitCtrl.dispose();
  }
}

class MaterialRequestCreateScreen extends ConsumerStatefulWidget {
  const MaterialRequestCreateScreen({super.key});

  @override
  ConsumerState<MaterialRequestCreateScreen> createState() =>
      _MaterialRequestCreateScreenState();
}

class _MaterialRequestCreateScreenState
    extends ConsumerState<MaterialRequestCreateScreen> {
  final _formKey = GlobalKey<FormState>();
  int? _projectId;
  DateTime _requiredDate = DateTime.now().add(const Duration(days: 7));
  String _priority = 'medium';
  final _purposeCtrl = TextEditingController();

  // BOQ selections: id -> qty controller (also the "selected" set of keys).
  final Map<int, TextEditingController> _boqQtyCtrls = {};
  // Free-text rows.
  final List<_FreeTextRow> _freeRows = [];

  bool _saving = false;

  @override
  void dispose() {
    _purposeCtrl.dispose();
    for (final c in _boqQtyCtrls.values) {
      c.dispose();
    }
    for (final r in _freeRows) {
      r.dispose();
    }
    super.dispose();
  }

  void _toggleBoqItem(MrBoqPickerItem item, bool selected) {
    setState(() {
      if (selected) {
        _boqQtyCtrls[item.id] = TextEditingController();
      } else {
        _boqQtyCtrls.remove(item.id)?.dispose();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final referenceAsync = ref.watch(_createReferenceProvider(_projectId));

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ombi Jipya la Vifaa' : 'New Material Request'),
      ),
      body: referenceAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(procurementErrorMessage(e),
                textAlign: TextAlign.center),
          ),
        ),
        data: (reference) => _buildForm(
          context,
          reference,
          isSwahili,
          isDarkMode,
        ),
      ),
    );
  }

  Widget _buildForm(
    BuildContext context,
    MrReferenceData reference,
    bool isSwahili,
    bool isDarkMode,
  ) {
    final priorities =
        reference.priorities.isEmpty ? _fallbackPriorities : reference.priorities;

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          DropdownButtonFormField<int>(
            initialValue: _projectId,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Mradi' : 'Project',
              border: const OutlineInputBorder(),
            ),
            items: reference.projects
                .map((p) => DropdownMenuItem<int>(
                      value: p.id,
                      child: Text(p.name, overflow: TextOverflow.ellipsis),
                    ))
                .toList(),
            onChanged: (v) {
              setState(() {
                _projectId = v;
                // Project change invalidates BOQ selections.
                for (final c in _boqQtyCtrls.values) {
                  c.dispose();
                }
                _boqQtyCtrls.clear();
              });
            },
            validator: (v) => v == null
                ? (isSwahili ? 'Chagua mradi' : 'Select a project')
                : null,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: InkWell(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2100),
                      initialDate: _requiredDate,
                    );
                    if (picked != null) {
                      setState(() => _requiredDate = picked);
                    }
                  },
                  child: InputDecorator(
                    decoration: InputDecoration(
                      labelText:
                          isSwahili ? 'Tarehe inayohitajika' : 'Required date',
                      border: const OutlineInputBorder(),
                    ),
                    child: Text(_fmtDate(_requiredDate)),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<String>(
                  initialValue: _priority,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kipaumbele' : 'Priority',
                    border: const OutlineInputBorder(),
                  ),
                  items: priorities
                      .map((p) => DropdownMenuItem<String>(
                            value: p,
                            child: Text(p[0].toUpperCase() + p.substring(1)),
                          ))
                      .toList(),
                  onChanged: (v) =>
                      setState(() => _priority = v ?? 'medium'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _purposeCtrl,
            maxLines: 2,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Madhumuni (hiari)' : 'Purpose (optional)',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 20),
          Text(
            isSwahili ? 'Vipengele vya BOQ' : 'BOQ Items',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
          ),
          const SizedBox(height: 8),
          if (_projectId == null)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Text(
                isSwahili
                    ? 'Chagua mradi kuona vipengele vya BOQ'
                    : 'Select a project to see BOQ items',
                style: TextStyle(
                    fontSize: 12, color: AppColors.textSecondary),
              ),
            )
          else if (reference.boqItems.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 12),
              child: Text(
                isSwahili
                    ? 'Hakuna vipengele vya BOQ vinavyopatikana'
                    : 'No BOQ items available',
                style: TextStyle(
                    fontSize: 12, color: AppColors.textSecondary),
              ),
            )
          else
            ...reference.boqItems.map((item) => _BoqItemTile(
                  item: item,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  selected: _boqQtyCtrls.containsKey(item.id),
                  qtyController: _boqQtyCtrls[item.id],
                  onToggle: (sel) => _toggleBoqItem(item, sel),
                )),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: Text(
                  isSwahili
                      ? 'Vipengele Vingine (${_freeRows.length})'
                      : 'Free-text Items (${_freeRows.length})',
                  style:
                      const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                ),
              ),
              TextButton.icon(
                onPressed: () => setState(() => _freeRows.add(_FreeTextRow())),
                icon: const Icon(Icons.add),
                label: Text(isSwahili ? 'Ongeza' : 'Add'),
              ),
            ],
          ),
          for (int i = 0; i < _freeRows.length; i++)
            _FreeTextEditor(
              row: _freeRows[i],
              index: i,
              isSwahili: isSwahili,
              onRemove: () => setState(() {
                _freeRows[i].dispose();
                _freeRows.removeAt(i);
              }),
            ),
          const SizedBox(height: 24),
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
                  : const Icon(Icons.send),
              label: Text(
                isSwahili ? 'Wasilisha Ombi' : 'Submit Request',
              ),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Future<void> _submit() async {
    final isSwahili = ref.read(isSwahiliProvider);
    if (!(_formKey.currentState?.validate() ?? false)) return;

    final reference = ref.read(_createReferenceProvider(_projectId)).valueOrNull;
    final boqById = <int, MrBoqPickerItem>{
      for (final b in reference?.boqItems ?? const []) b.id: b,
    };

    final items = <Map<String, dynamic>>[];

    // BOQ-linked items.
    for (final entry in _boqQtyCtrls.entries) {
      final boq = boqById[entry.key];
      final qty = double.tryParse(entry.value.text.trim()) ?? 0;
      if (qty <= 0) {
        _snack(
          isSwahili
              ? 'Weka idadi sahihi kwa ${boq?.itemCode ?? 'kipengele'}'
              : 'Enter a valid quantity for ${boq?.itemCode ?? 'item'}',
        );
        return;
      }
      if (boq != null && qty > boq.availableQuantity) {
        _snack(
          isSwahili
              ? 'Idadi ya ${boq.itemCode} inazidi inayopatikana (${_trimNum(boq.availableQuantity)})'
              : 'Quantity for ${boq.itemCode} exceeds available (${_trimNum(boq.availableQuantity)})',
        );
        return;
      }
      items.add({
        'boq_item_id': entry.key,
        'quantity_requested': qty,
        'unit': boq?.unit ?? 'unit',
      });
    }

    // Free-text items.
    for (int i = 0; i < _freeRows.length; i++) {
      final row = _freeRows[i];
      final desc = row.descCtrl.text.trim();
      final qty = double.tryParse(row.qtyCtrl.text.trim()) ?? 0;
      final unit = row.unitCtrl.text.trim();
      if (desc.isEmpty && qty <= 0 && unit.isEmpty) {
        continue; // skip empty rows
      }
      if (desc.isEmpty) {
        _snack(
          isSwahili
              ? 'Kipengele #${i + 1}: weka maelezo'
              : 'Item #${i + 1}: enter a description',
        );
        return;
      }
      if (qty <= 0) {
        _snack(
          isSwahili
              ? 'Kipengele #${i + 1}: weka idadi sahihi'
              : 'Item #${i + 1}: enter a valid quantity',
        );
        return;
      }
      items.add({
        'description': desc,
        'quantity_requested': qty,
        'unit': unit.isEmpty ? 'unit' : unit,
      });
    }

    if (items.isEmpty) {
      _snack(
        isSwahili
            ? 'Chagua angalau kipengele kimoja'
            : 'Select at least one item',
      );
      return;
    }

    setState(() => _saving = true);
    try {
      final payload = <String, dynamic>{
        'project_id': _projectId,
        'required_date': _fmtDate(_requiredDate),
        'priority': _priority,
        'purpose': _purposeCtrl.text.trim(),
        'items': items,
      };
      await ref.read(materialRequestsRepositoryProvider).createBulk(payload);
      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        _snack(procurementErrorMessage(e));
      }
    }
  }

  void _snack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: AppColors.error),
    );
  }

  static const List<String> _fallbackPriorities = [
    'low',
    'medium',
    'high',
    'urgent',
  ];
}

class _BoqItemTile extends StatelessWidget {
  final MrBoqPickerItem item;
  final bool isSwahili;
  final bool isDarkMode;
  final bool selected;
  final TextEditingController? qtyController;
  final ValueChanged<bool> onToggle;

  const _BoqItemTile({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
    required this.selected,
    required this.qtyController,
    required this.onToggle,
  });

  @override
  Widget build(BuildContext context) {
    final disabled = item.hasPendingRequest;
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: selected
              ? AppColors.primary
              : Theme.of(context).dividerColor.withValues(alpha: 0.5),
        ),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Checkbox(
                value: selected,
                onChanged:
                    disabled ? null : (v) => onToggle(v ?? false),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${item.itemCode} — ${item.description}',
                      style: const TextStyle(
                          fontSize: 13, fontWeight: FontWeight.w600),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      '${isSwahili ? 'Inapatikana' : 'Available'}: '
                      '${_trimNum(item.availableQuantity)} ${item.unit ?? ''}',
                      style: TextStyle(
                          fontSize: 11, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
              if (disabled)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.warning.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    isSwahili ? 'INASUBIRI' : 'PENDING',
                    style: TextStyle(
                      fontSize: 9,
                      fontWeight: FontWeight.w700,
                      color: AppColors.warning,
                    ),
                  ),
                ),
            ],
          ),
          if (selected && qtyController != null)
            Padding(
              padding: const EdgeInsets.only(left: 40, top: 4, bottom: 4),
              child: TextField(
                controller: qtyController,
                keyboardType:
                    const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: isSwahili
                      ? 'Idadi (kiwango ${_trimNum(item.availableQuantity)})'
                      : 'Quantity (max ${_trimNum(item.availableQuantity)})',
                  isDense: true,
                  border: const OutlineInputBorder(),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _FreeTextEditor extends StatelessWidget {
  final _FreeTextRow row;
  final int index;
  final bool isSwahili;
  final VoidCallback onRemove;

  const _FreeTextEditor({
    required this.row,
    required this.index,
    required this.isSwahili,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(top: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    '${isSwahili ? 'Kipengele' : 'Item'} ${index + 1}',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.delete, color: AppColors.error),
                  onPressed: onRemove,
                ),
              ],
            ),
            TextField(
              controller: row.descCtrl,
              decoration: InputDecoration(
                labelText: isSwahili ? 'Maelezo' : 'Description',
                border: const OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: row.qtyCtrl,
                    keyboardType:
                        const TextInputType.numberWithOptions(decimal: true),
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Idadi' : 'Quantity',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: TextField(
                    controller: row.unitCtrl,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Kipimo' : 'Unit',
                      border: const OutlineInputBorder(),
                    ),
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

String _trimNum(double v) {
  if (v == v.roundToDouble()) return v.toInt().toString();
  return v.toString();
}

String _fmtDate(DateTime d) =>
    '${d.year.toString().padLeft(4, '0')}-'
    '${d.month.toString().padLeft(2, '0')}-'
    '${d.day.toString().padLeft(2, '0')}';
