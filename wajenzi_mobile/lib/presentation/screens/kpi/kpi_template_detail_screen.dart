import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/datasources/remote/kpi_api.dart';
import '../../../data/models/kpi_template.dart';
import '../../providers/kpi_template_provider.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/loading_widget.dart';
import 'kpi_templates_list_screen.dart' show canManageKpiTemplates;
import 'kpi_widgets.dart';

/// Per-item editing controllers.
class _ItemCtrls {
  final TextEditingController kpa;
  final TextEditingController measure;
  final TextEditingController target;
  final TextEditingController weight;

  _ItemCtrls(KpiTemplateItem item)
      : kpa = TextEditingController(text: item.kpa),
        measure = TextEditingController(text: item.measure),
        target = TextEditingController(text: item.target),
        weight = TextEditingController(text: _fmtWeight(item.weight));

  void dispose() {
    kpa.dispose();
    measure.dispose();
    target.dispose();
    weight.dispose();
  }
}

String _fmtWeight(double w) =>
    w == w.roundToDouble() ? w.toStringAsFixed(0) : w.toString();

class KpiTemplateDetailScreen extends ConsumerStatefulWidget {
  final int templateId;

  const KpiTemplateDetailScreen({super.key, required this.templateId});

  @override
  ConsumerState<KpiTemplateDetailScreen> createState() =>
      _KpiTemplateDetailScreenState();
}

class _KpiTemplateDetailScreenState
    extends ConsumerState<KpiTemplateDetailScreen> {
  final Map<int, _ItemCtrls> _itemCtrls = {};
  String _itemSignature = '';

  // Edit-details panel controllers.
  final _nameCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  String _frequency = 'quarterly';
  bool _isActive = true;
  String _detailSignature = '';
  bool _savingDetails = false;
  final Set<int> _savingSections = {};

  @override
  void dispose() {
    for (final c in _itemCtrls.values) {
      c.dispose();
    }
    _nameCtrl.dispose();
    _descCtrl.dispose();
    super.dispose();
  }

  /// Rebuild per-item controllers when the loaded item-set changes.
  void _syncItemControllers(KpiTemplateDetail d) {
    final sig = d.sections
        .expand((s) => s.items)
        .map((i) => '${i.id}:${i.weight}:${i.kpa.hashCode}')
        .join('|');
    if (sig == _itemSignature) return;
    _itemSignature = sig;
    for (final c in _itemCtrls.values) {
      c.dispose();
    }
    _itemCtrls.clear();
    for (final s in d.sections) {
      for (final item in s.items) {
        _itemCtrls[item.id] = _ItemCtrls(item);
      }
    }
  }

  /// Sync the edit-details panel fields once per load.
  void _syncDetailFields(KpiTemplateDetail d) {
    final sig = '${d.id}:${d.name}:${d.frequency}:${d.description}:${d.isActive}';
    if (sig == _detailSignature) return;
    _detailSignature = sig;
    _nameCtrl.text = d.name;
    _descCtrl.text = d.description ?? '';
    _frequency = kpiTemplateFrequencies.contains(d.frequency)
        ? d.frequency
        : 'quarterly';
    _isActive = d.isActive;
  }

  @override
  Widget build(BuildContext context) {
    final allowed = canManageKpiTemplates(ref);
    final state = ref.watch(kpiTemplateDetailProvider(widget.templateId));
    final notifier =
        ref.read(kpiTemplateDetailProvider(widget.templateId).notifier);

    return Scaffold(
      appBar: kpiAppBar(
        context: context,
        ref: ref,
        title: 'Template',
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: notifier.refresh,
          ),
        ],
      ),
      body: state.when(
        loading: () => const LoadingWidget(message: 'Loading template...'),
        error: (e, _) => CustomErrorWidget(
          message: 'Could not load this template.\n$e',
          onRetry: notifier.refresh,
        ),
        data: (d) {
          _syncItemControllers(d);
          _syncDetailFields(d);
          return RefreshIndicator(
            onRefresh: notifier.refresh,
            color: AppColors.brandGreen,
            child: ListView(
              padding: EdgeInsets.fromLTRB(
                12,
                12,
                12,
                MediaQuery.of(context).padding.bottom + 110,
              ),
              children: [
                _headerCard(d),
                const SizedBox(height: 12),
                _editDetailsCard(d, allowed),
                const SizedBox(height: 12),
                for (final s in d.sections) ...[
                  _sectionCard(d, s, allowed),
                  const SizedBox(height: 12),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _headerCard(KpiTemplateDetail d) {
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.75);
    final balanced = d.weightBalanced;
    final weightColor = balanced ? AppColors.brandGreen : AppColors.error;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(d.name,
                      style: AppType.display(17, weight: FontWeight.w700)),
                ),
                if (!d.isActive)
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppColors.draft.withValues(alpha: 0.16),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                          color: AppColors.draft.withValues(alpha: 0.5)),
                    ),
                    child: const Text('Inactive',
                        style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: AppColors.draft)),
                  ),
              ],
            ),
            const SizedBox(height: 6),
            Text('${d.role} · ${kpiFrequencyLabel(d.frequency)}',
                style: TextStyle(fontSize: 13, color: muted)),
            Text(d.code, style: TextStyle(fontSize: 12, color: muted)),
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.list_alt_rounded,
                    size: 18, color: AppColors.brandBlue),
                const SizedBox(width: 4),
                Text('${d.itemCount} items',
                    style: const TextStyle(
                        fontSize: 13, fontWeight: FontWeight.w600)),
                const SizedBox(width: 16),
                Icon(
                  balanced
                      ? Icons.check_circle_outline
                      : Icons.warning_amber_rounded,
                  size: 18,
                  color: weightColor,
                ),
                const SizedBox(width: 4),
                Text('Total ${_fmtWeight(d.totalWeight)}%',
                    style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: weightColor)),
              ],
            ),
            if (!balanced)
              Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text('Template weights should total 100%.',
                    style: TextStyle(
                        fontSize: 12,
                        color: AppColors.error,
                        fontWeight: FontWeight.w600)),
              ),
          ],
        ),
      ),
    );
  }

  Widget _editDetailsCard(KpiTemplateDetail d, bool allowed) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Edit Details',
                style: AppType.display(14, weight: FontWeight.w700)),
            const Divider(height: 18),
            _label('Name'),
            const SizedBox(height: 6),
            TextField(
              controller: _nameCtrl,
              enabled: allowed,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.badge_outlined),
              ),
            ),
            const SizedBox(height: 14),
            _label('Frequency'),
            const SizedBox(height: 6),
            DropdownButtonFormField<String>(
              initialValue: _frequency,
              isExpanded: true,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.repeat_rounded),
              ),
              items: [
                for (final f in kpiTemplateFrequencies)
                  DropdownMenuItem(value: f, child: Text(kpiFrequencyLabel(f))),
              ],
              onChanged:
                  allowed ? (v) => setState(() => _frequency = v ?? _frequency) : null,
            ),
            const SizedBox(height: 14),
            _label('Description'),
            const SizedBox(height: 6),
            TextField(
              controller: _descCtrl,
              enabled: allowed,
              maxLines: 3,
              decoration: const InputDecoration(
                hintText: 'Optional notes about this template',
                prefixIcon: Icon(Icons.notes_outlined),
              ),
            ),
            const SizedBox(height: 6),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Active'),
              subtitle: const Text('Inactive templates cannot be picked'),
              value: _isActive,
              activeThumbColor: AppColors.brandGreen,
              onChanged:
                  allowed ? (v) => setState(() => _isActive = v) : null,
            ),
            if (allowed) ...[
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton.icon(
                  onPressed: _savingDetails ? null : () => _saveDetails(d),
                  icon: _savingDetails
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.save_outlined),
                  label: Text(_savingDetails ? 'Saving...' : 'Save Details'),
                  style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.brandBlue),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _sectionCard(
      KpiTemplateDetail d, KpiTemplateSectionDetail s, bool allowed) {
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.7);
    final balanced = s.weightBalanced;
    final weightColor = balanced ? AppColors.brandGreen : AppColors.error;
    final saving = _savingSections.contains(s.id);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Section ${s.code} — ${s.title}',
                    style: AppType.display(14, weight: FontWeight.w700),
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.brandBlue.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text('Target ${_fmtWeight(s.weightTotal)}%',
                      style: const TextStyle(
                          fontSize: 11, fontWeight: FontWeight.w700)),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                Icon(
                  balanced
                      ? Icons.check_circle_outline
                      : Icons.warning_amber_rounded,
                  size: 15,
                  color: weightColor,
                ),
                const SizedBox(width: 4),
                Text(
                  'Items total ${_fmtWeight(s.itemsWeight)}% of ${_fmtWeight(s.weightTotal)}%',
                  style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: weightColor),
                ),
              ],
            ),
            const Divider(height: 18),
            if (s.items.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text('No KPIs yet in this section.',
                    style: TextStyle(fontSize: 13, color: muted)),
              ),
            for (final item in s.items) _itemEditor(item, allowed),
            const SizedBox(height: 4),
            if (allowed)
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _openAddItemSheet(d, s),
                      icon: const Icon(Icons.add, size: 18),
                      label: const Text('Add KPI'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.brandGreen,
                        side: const BorderSide(color: AppColors.brandGreen),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: (saving || s.items.isEmpty)
                          ? null
                          : () => _saveSection(d.id, s),
                      icon: saving
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.save_outlined, size: 18),
                      label: Text(saving ? 'Saving...' : 'Save Section'),
                      style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.brandBlue),
                    ),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget _itemEditor(KpiTemplateItem item, bool allowed) {
    final ctrls = _itemCtrls[item.id];
    if (ctrls == null) return const SizedBox.shrink();
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
      decoration: BoxDecoration(
        color: isDark
            ? Colors.white.withValues(alpha: 0.04)
            : AppColors.brandBlue.withValues(alpha: 0.025),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark
              ? Colors.white.withValues(alpha: 0.08)
              : AppColors.brandBlue.withValues(alpha: 0.10),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextField(
            controller: ctrls.kpa,
            enabled: allowed,
            decoration: const InputDecoration(
              isDense: true,
              labelText: 'KPA',
            ),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: ctrls.measure,
            enabled: allowed,
            maxLines: 2,
            minLines: 1,
            decoration: const InputDecoration(
              isDense: true,
              labelText: 'Measure',
            ),
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                flex: 2,
                child: TextField(
                  controller: ctrls.target,
                  enabled: allowed,
                  decoration: const InputDecoration(
                    isDense: true,
                    labelText: 'Target',
                  ),
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                width: 84,
                child: TextField(
                  controller: ctrls.weight,
                  enabled: allowed,
                  keyboardType: const TextInputType.numberWithOptions(
                      decimal: true),
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
                  ],
                  decoration: const InputDecoration(
                    isDense: true,
                    labelText: 'Weight',
                  ),
                ),
              ),
              if (allowed)
                IconButton(
                  icon: const Icon(Icons.delete_outline,
                      color: AppColors.error),
                  tooltip: 'Delete KPI',
                  onPressed: () => _deleteItem(item),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _label(String text) => Text(
        text.toUpperCase(),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 1,
          color: Theme.of(context)
              .textTheme
              .bodyMedium
              ?.color
              ?.withValues(alpha: 0.7),
        ),
      );

  // ── Actions ──────────────────────────────────────────────────────────

  Future<void> _saveDetails(KpiTemplateDetail d) async {
    final messenger = ScaffoldMessenger.of(context);
    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Name is required.')),
      );
      return;
    }
    setState(() => _savingDetails = true);
    try {
      await ref.read(kpiApiProvider).updateTemplate(
            d.id,
            name: name,
            frequency: _frequency,
            description: _descCtrl.text.trim(),
            isActive: _isActive,
          );
      _detailSignature = ''; // force resync from server
      await ref
          .read(kpiTemplateDetailProvider(widget.templateId).notifier)
          .refresh();
      ref.read(kpiTemplatesProvider.notifier).refresh();
      messenger.showSnackBar(
        const SnackBar(content: Text('Template details updated.')),
      );
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('Save failed: $e')));
    } finally {
      if (mounted) setState(() => _savingDetails = false);
    }
  }

  Future<void> _saveSection(
      int templateId, KpiTemplateSectionDetail s) async {
    final messenger = ScaffoldMessenger.of(context);
    final items = <String, Map<String, dynamic>>{};
    for (final item in s.items) {
      final c = _itemCtrls[item.id];
      if (c == null) continue;
      final kpa = c.kpa.text.trim();
      final measure = c.measure.text.trim();
      final weight = double.tryParse(c.weight.text.trim());
      if (kpa.isEmpty || measure.isEmpty) {
        messenger.showSnackBar(
          const SnackBar(
              content: Text('KPA and Measure are required on every row.')),
        );
        return;
      }
      if (weight == null || weight < 0 || weight > 100) {
        messenger.showSnackBar(
          const SnackBar(
              content: Text('Weight must be a number between 0 and 100.')),
        );
        return;
      }
      items['${item.id}'] = {
        'kpa': kpa,
        'measure': measure,
        'target': c.target.text.trim(),
        'weight': weight,
      };
    }
    if (items.isEmpty) return;

    setState(() => _savingSections.add(s.id));
    try {
      await ref.read(kpiApiProvider).bulkUpdateItems(templateId, items);
      _itemSignature = ''; // force resync from server
      await ref
          .read(kpiTemplateDetailProvider(widget.templateId).notifier)
          .refresh();
      ref.read(kpiTemplatesProvider.notifier).refresh();
      messenger.showSnackBar(
        SnackBar(content: Text('Section ${s.code} saved.')),
      );
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('Save failed: $e')));
    } finally {
      if (mounted) setState(() => _savingSections.remove(s.id));
    }
  }

  Future<void> _deleteItem(KpiTemplateItem item) async {
    final messenger = ScaffoldMessenger.of(context);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete KPI'),
        content: Text('Delete "${item.kpa}" from this template?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Delete',
                  style: TextStyle(color: AppColors.error))),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref
          .read(kpiApiProvider)
          .deleteTemplateItem(widget.templateId, item.id);
      _itemSignature = '';
      await ref
          .read(kpiTemplateDetailProvider(widget.templateId).notifier)
          .refresh();
      ref.read(kpiTemplatesProvider.notifier).refresh();
      messenger.showSnackBar(
        const SnackBar(content: Text('KPI deleted.')),
      );
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('Delete failed: $e')));
    }
  }

  Future<void> _openAddItemSheet(
      KpiTemplateDetail d, KpiTemplateSectionDetail s) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) => _AddItemForm(
        templateId: d.id,
        section: s,
        onSaved: () async {
          _itemSignature = '';
          await ref
              .read(kpiTemplateDetailProvider(widget.templateId).notifier)
              .refresh();
          ref.read(kpiTemplatesProvider.notifier).refresh();
        },
      ),
    );
  }
}

class _AddItemForm extends ConsumerStatefulWidget {
  final int templateId;
  final KpiTemplateSectionDetail section;
  final Future<void> Function() onSaved;

  const _AddItemForm({
    required this.templateId,
    required this.section,
    required this.onSaved,
  });

  @override
  ConsumerState<_AddItemForm> createState() => _AddItemFormState();
}

class _AddItemFormState extends ConsumerState<_AddItemForm> {
  final _kpaCtrl = TextEditingController();
  final _measureCtrl = TextEditingController();
  final _targetCtrl = TextEditingController();
  final _weightCtrl = TextEditingController();
  final _methodCtrl = TextEditingController();
  bool _submitting = false;

  @override
  void dispose() {
    _kpaCtrl.dispose();
    _measureCtrl.dispose();
    _targetCtrl.dispose();
    _weightCtrl.dispose();
    _methodCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final messenger = ScaffoldMessenger.of(context);
    final kpa = _kpaCtrl.text.trim();
    final measure = _measureCtrl.text.trim();
    final weight = double.tryParse(_weightCtrl.text.trim());
    if (kpa.isEmpty || measure.isEmpty) {
      messenger.showSnackBar(
        const SnackBar(content: Text('KPA and Measure are required.')),
      );
      return;
    }
    if (weight == null || weight < 0 || weight > 100) {
      messenger.showSnackBar(
        const SnackBar(
            content: Text('Weight must be a number between 0 and 100.')),
      );
      return;
    }
    setState(() => _submitting = true);
    try {
      await ref.read(kpiApiProvider).addTemplateItem(
            widget.templateId,
            sectionId: widget.section.id,
            kpa: kpa,
            measure: measure,
            target: _targetCtrl.text.trim(),
            weight: weight,
            measurementMethod: _methodCtrl.text.trim(),
          );
      await widget.onSaved();
      if (!mounted) return;
      Navigator.of(context).pop();
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(SnackBar(content: Text('Add failed: $e')));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 4, 16, 16 + bottomInset),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Add KPI — Section ${widget.section.code}',
                style: AppType.display(17, weight: FontWeight.w700)),
            const SizedBox(height: 16),
            TextField(
              controller: _kpaCtrl,
              decoration: const InputDecoration(labelText: 'KPA'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _measureCtrl,
              maxLines: 2,
              minLines: 1,
              decoration: const InputDecoration(labelText: 'Measure'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _targetCtrl,
              decoration: const InputDecoration(labelText: 'Target (optional)'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _weightCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
              ],
              decoration: const InputDecoration(labelText: 'Weight (0–100)'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _methodCtrl,
              decoration: const InputDecoration(
                  labelText: 'Measurement Method (optional)'),
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 50,
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _submitting ? null : _submit,
                icon: _submitting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white),
                      )
                    : const Icon(Icons.check),
                label: Text(_submitting ? 'Adding...' : 'Add KPI'),
                style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.brandGreen),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
