import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/services/export_service.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/motion.dart';

/// Site stock and stock receipts: the web's Project Stock and Stock Receipts
/// pages. Same rules as the web (no permission checks there), served by
/// `/project-stock` and `/project-stock-receipts`.

final _stockProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, String?>((ref, projectId) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/project-stock',
    queryParameters: {if (projectId != null) 'project_id': projectId},
  );
  return Map<String, dynamic>.from(response.data as Map);
});

final _receiptsProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, String?>((ref, projectId) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/project-stock-receipts',
    queryParameters: {if (projectId != null) 'project_id': projectId},
  );
  return Map<String, dynamic>.from(response.data as Map);
});

String _qty(dynamic v) {
  final n = double.tryParse('$v') ?? 0;
  return n == n.roundToDouble() ? n.toStringAsFixed(0) : n.toStringAsFixed(2);
}

String _error(Object e, String fallback) {
  if (e is DioException) {
    final data = e.response?.data;
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        return errors.values.map((v) => v is List ? v.join(' ') : '$v').join('\n');
      }
      if (data['message'] != null) return '${data['message']}';
    }
  }
  return fallback;
}

Widget _projectFilter({
  required List<Map<String, dynamic>> projects,
  required String? value,
  required bool isSwahili,
  required ValueChanged<String?> onChanged,
}) {
  return Padding(
    padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
    child: DropdownButtonFormField<String?>(
      isExpanded: true,
      initialValue: projects.any((p) => '${p['id']}' == value) ? value : null,
      decoration: InputDecoration(
        labelText: isSwahili ? 'Mradi / Site' : 'Project / Site',
        isDense: true,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      ),
      items: [
        DropdownMenuItem<String?>(value: null, child: Text(isSwahili ? 'Miradi yote' : 'All projects')),
        for (final p in projects)
          DropdownMenuItem<String?>(
            value: '${p['id']}',
            child: Text('${p['name']}', overflow: TextOverflow.ellipsis),
          ),
      ],
      onChanged: onChanged,
    ),
  );
}

// ───────────────────────────────────────────────────────────────────────────
// Project Stock
// ───────────────────────────────────────────────────────────────────────────

class ProjectStockScreen extends ConsumerStatefulWidget {
  final String? projectId;
  const ProjectStockScreen({super.key, this.projectId});

  @override
  ConsumerState<ProjectStockScreen> createState() => _ProjectStockScreenState();
}

class _ProjectStockScreenState extends ConsumerState<ProjectStockScreen> {
  late String? _projectId = widget.projectId;

  Future<void> _openItemForm(
    List<Map<String, dynamic>> projects, {
    Map<String, dynamic>? item,
  }) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _StockItemForm(projects: projects, projectId: _projectId, item: item),
    );
    if (saved == true) ref.invalidate(_stockProvider(_projectId));
  }

  Future<void> _delete(Map<String, dynamic> item) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final messenger = ScaffoldMessenger.of(context);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa' : 'Delete'),
        content: Text(isSwahili ? 'Futa bidhaa hii ya hisa?' : 'Delete this stock item?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Futa' : 'Delete', style: const TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref.read(apiClientProvider).delete('/project-stock/${item['id']}');
      ref.invalidate(_stockProvider(_projectId));
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(_error(e, 'Failed to delete')), backgroundColor: AppColors.error));
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_stockProvider(_projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final projects = ((async.valueOrNull?['meta'] as Map?)?['projects'] as List? ?? const [])
        .cast<Map<String, dynamic>>();
    final query = _projectId != null ? '?project_id=$_projectId' : '';

    return Scaffold(
      appBar: AppBar(title: Text(isSwahili ? 'Hisa ya Mradi' : 'Project Stock')),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 0),
        child: FloatingActionButton(
          onPressed: () => _openItemForm(projects),
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: Column(
        children: [
          ExportButtonsBar(options: webTableExportOptions('project_stock.index')),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 0),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  OutlinedButton.icon(
                    onPressed: () => context.push('/project-stock-receipts$query'),
                    icon: const Icon(Icons.inbox_outlined, size: 18),
                    label: Text(isSwahili ? 'Risiti za Hisa' : 'Stock Receipts'),
                  ),
                  FilledButton.icon(
                    onPressed: () => context.push('/project-stock-receipts/new$query'),
                    icon: const Icon(Icons.download_rounded, size: 18),
                    label: Text(isSwahili ? 'Pokea Bidhaa' : 'Receive Goods'),
                  ),
                ],
              ),
            ),
          ),
          _projectFilter(
            projects: projects,
            value: _projectId,
            isSwahili: isSwahili,
            onChanged: (v) => setState(() => _projectId = v),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(_stockProvider(_projectId).future),
              child: async.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (_, __) => ListView(children: [
                  const SizedBox(height: 100),
                  Center(
                    child: TextButton.icon(
                      onPressed: () => ref.invalidate(_stockProvider(_projectId)),
                      icon: const Icon(Icons.refresh),
                      label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                    ),
                  ),
                ]),
                data: (payload) {
                  final items = (payload['data'] as List).cast<Map<String, dynamic>>();
                  return ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      if (items.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 48),
                          child: Center(child: Text(isSwahili ? 'Hakuna bidhaa za hisa' : 'No stock items')),
                        ),
                      for (final (i, item) in items.indexed)
                  FadeSlideIn(index: i, child: Card(
                          margin: const EdgeInsets.only(bottom: 10),
                          child: ListTile(
                            title: Text('${item['description']}', style: const TextStyle(fontWeight: FontWeight.w600)),
                            subtitle: Text([
                              '${item['item_code'] ?? ''}',
                              '${item['project_name'] ?? ''}',
                              if ((item['notes'] ?? '').toString().isNotEmpty) '${item['notes']}',
                            ].where((s) => s.isNotEmpty).join(' · ')),
                            trailing: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  '${_qty(item['quantity_on_hand'])} ${item['unit']}',
                                  style: const TextStyle(fontWeight: FontWeight.w700),
                                ),
                                PopupMenuButton<String>(
                                  onSelected: (v) => v == 'edit' ? _openItemForm(projects, item: item) : _delete(item),
                                  itemBuilder: (_) => [
                                    PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                                    PopupMenuItem(
                                      value: 'delete',
                                      child: Text(isSwahili ? 'Futa' : 'Delete', style: const TextStyle(color: AppColors.error)),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        )),
                      const SizedBox(height: 90),
                    ],
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

class _StockItemForm extends ConsumerStatefulWidget {
  final List<Map<String, dynamic>> projects;
  final String? projectId;
  final Map<String, dynamic>? item;
  const _StockItemForm({required this.projects, this.projectId, this.item});

  @override
  ConsumerState<_StockItemForm> createState() => _StockItemFormState();
}

class _StockItemFormState extends ConsumerState<_StockItemForm> {
  final _key = GlobalKey<FormState>();
  late final _description = TextEditingController(text: widget.item?['description'] as String? ?? '');
  late final _unit = TextEditingController(text: widget.item?['unit'] as String? ?? '');
  late final _quantity = TextEditingController(text: widget.item == null ? '' : _qty(widget.item!['quantity_on_hand']));
  late final _notes = TextEditingController(text: widget.item?['notes'] as String? ?? '');
  late String? _project = widget.item != null ? '${widget.item!['project_id']}' : widget.projectId;
  bool _saving = false;
  String? _err;

  @override
  void dispose() {
    _description.dispose();
    _unit.dispose();
    _quantity.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_key.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _err = null;
    });
    final body = {
      if (widget.item == null) 'project_id': _project,
      'description': _description.text.trim(),
      'unit': _unit.text.trim(),
      'quantity_on_hand': _quantity.text.trim(),
      'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
    };
    try {
      final api = ref.read(apiClientProvider);
      if (widget.item == null) {
        await api.post('/project-stock', data: body);
      } else {
        await api.put('/project-stock/${widget.item!['id']}', data: body);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) setState(() => _err = _error(e, 'Failed to save'));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    String? req(String? v) => (v == null || v.trim().isEmpty) ? (isSwahili ? 'Hitaji' : 'Required') : null;

    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Form(
          key: _key,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                widget.item == null ? (isSwahili ? 'Ongeza Bidhaa ya Hisa' : 'Add Stock Item') : (isSwahili ? 'Hariri Bidhaa' : 'Edit Stock Item'),
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 16),
              if (widget.item == null) ...[
                DropdownButtonFormField<String>(
                  isExpanded: true,
                  initialValue: widget.projects.any((p) => '${p['id']}' == _project) ? _project : null,
                  decoration: InputDecoration(labelText: '${isSwahili ? 'Mradi / Site' : 'Project / Site'} *'),
                  items: [for (final p in widget.projects) DropdownMenuItem(value: '${p['id']}', child: Text('${p['name']}', overflow: TextOverflow.ellipsis))],
                  validator: req,
                  onChanged: (v) => setState(() => _project = v),
                ),
                const SizedBox(height: 12),
              ],
              TextFormField(controller: _description, decoration: InputDecoration(labelText: '${isSwahili ? 'Maelezo' : 'Description'} *'), validator: req),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(child: TextFormField(controller: _unit, decoration: InputDecoration(labelText: '${isSwahili ? 'Kipimo' : 'Unit'} *'), validator: req)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: _quantity,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(labelText: '${isSwahili ? 'Kiasi' : 'Qty on Hand'} *'),
                      validator: req,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(controller: _notes, maxLines: 2, decoration: InputDecoration(labelText: isSwahili ? 'Maelezo ya ziada' : 'Notes')),
              if (_err != null) Padding(padding: const EdgeInsets.only(top: 12), child: Text(_err!, style: const TextStyle(color: AppColors.error))),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                    : Text(widget.item == null ? (isSwahili ? 'Wasilisha' : 'Submit') : (isSwahili ? 'Sasisha' : 'Update')),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ───────────────────────────────────────────────────────────────────────────
// Stock Receipts
// ───────────────────────────────────────────────────────────────────────────

class ProjectStockReceiptsScreen extends ConsumerStatefulWidget {
  final String? projectId;
  const ProjectStockReceiptsScreen({super.key, this.projectId});

  @override
  ConsumerState<ProjectStockReceiptsScreen> createState() => _ProjectStockReceiptsScreenState();
}

class _ProjectStockReceiptsScreenState extends ConsumerState<ProjectStockReceiptsScreen> {
  late String? _projectId = widget.projectId;

  Future<void> _showReceipt(Map<String, dynamic> summary) async {
    final deleted = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _ReceiptDetail(id: summary['id'] as int),
    );
    if (deleted == true) {
      ref.invalidate(_receiptsProvider(_projectId));
      ref.invalidate(_stockProvider(_projectId));
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_receiptsProvider(_projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final projects = ((async.valueOrNull?['meta'] as Map?)?['projects'] as List? ?? const [])
        .cast<Map<String, dynamic>>();
    final query = _projectId != null ? '?project_id=$_projectId' : '';

    return Scaffold(
      appBar: AppBar(title: Text(isSwahili ? 'Risiti za Hisa' : 'Stock Receipts')),
      body: Column(
        children: [
          ExportButtonsBar(options: webTableExportOptions('project_stock_receipts.index')),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 6, 16, 0),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  OutlinedButton.icon(
                    onPressed: () => context.push('/project-stock$query'),
                    icon: const Icon(Icons.inventory_2_outlined, size: 18),
                    label: Text(isSwahili ? 'Angalia Hisa' : 'View Stock Items'),
                  ),
                  FilledButton.icon(
                    onPressed: () async {
                      await context.push('/project-stock-receipts/new$query');
                      ref.invalidate(_receiptsProvider(_projectId));
                    },
                    icon: const Icon(Icons.add, size: 18),
                    label: Text(isSwahili ? 'Risiti Mpya' : 'New Receipt'),
                  ),
                ],
              ),
            ),
          ),
          _projectFilter(
            projects: projects,
            value: _projectId,
            isSwahili: isSwahili,
            onChanged: (v) => setState(() => _projectId = v),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(_receiptsProvider(_projectId).future),
              child: async.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (_, __) => ListView(children: [
                  const SizedBox(height: 100),
                  Center(
                    child: TextButton.icon(
                      onPressed: () => ref.invalidate(_receiptsProvider(_projectId)),
                      icon: const Icon(Icons.refresh),
                      label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                    ),
                  ),
                ]),
                data: (payload) {
                  final receipts = (payload['data'] as List).cast<Map<String, dynamic>>();
                  return ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      if (receipts.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 48),
                          child: Center(child: Text(isSwahili ? 'Hakuna risiti' : 'No receipts yet')),
                        ),
                      for (final (i, r) in receipts.indexed)
                  FadeSlideIn(index: i, child: Card(
                          margin: const EdgeInsets.only(bottom: 10),
                          child: ListTile(
                            onTap: () => _showReceipt(r),
                            title: Text('${r['receipt_number']}', style: const TextStyle(fontWeight: FontWeight.w700)),
                            subtitle: Text([
                              '${r['project_name'] ?? ''}',
                              '${r['receipt_date'] ?? ''}',
                              if ((r['supplier'] ?? '').toString().isNotEmpty) '${r['supplier']}',
                              '${r['items_count']} ${isSwahili ? 'bidhaa' : 'items'}',
                              if (r['received_by'] != null) '${r['received_by']}',
                            ].where((s) => s.isNotEmpty).join(' · ')),
                            trailing: const Icon(Icons.chevron_right_rounded),
                          ),
                        )),
                      const SizedBox(height: 90),
                    ],
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

final _receiptDetailProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final response = await ref.watch(apiClientProvider).get('/project-stock-receipts/$id');
  return Map<String, dynamic>.from(response.data['data'] as Map);
});

class _ReceiptDetail extends ConsumerWidget {
  final int id;
  const _ReceiptDetail({required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(_receiptDetailProvider(id));
    final isSwahili = ref.watch(isSwahiliProvider);

    Future<void> delete() async {
      final messenger = ScaffoldMessenger.of(context);
      final navigator = Navigator.of(context);
      final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Text(isSwahili ? 'Futa Risiti' : 'Delete Receipt'),
          content: Text(isSwahili
              ? 'Futa risiti hii? Kiasi cha hisa kitarudishwa nyuma.'
              : 'Delete this receipt? The stock quantities it added will be reversed.'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
            TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: Text(isSwahili ? 'Futa' : 'Delete', style: const TextStyle(color: AppColors.error)),
            ),
          ],
        ),
      );
      if (ok != true) return;
      try {
        await ref.read(apiClientProvider).delete('/project-stock-receipts/$id');
        navigator.pop(true);
      } catch (e) {
        messenger.showSnackBar(SnackBar(content: Text(_error(e, 'Failed to delete')), backgroundColor: AppColors.error));
      }
    }

    return Padding(
      padding: const EdgeInsets.all(16),
      child: async.when(
        loading: () => const SizedBox(height: 160, child: Center(child: CircularProgressIndicator())),
        error: (_, __) => SizedBox(height: 120, child: Center(child: Text(isSwahili ? 'Imeshindwa kupakia' : 'Could not load'))),
        data: (r) {
          final items = (r['items'] as List? ?? const []).cast<Map<String, dynamic>>();
          Widget row(String k, dynamic v) => Padding(
                padding: const EdgeInsets.symmetric(vertical: 3),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(flex: 4, child: Text(k, style: const TextStyle(color: Colors.grey))),
                    Expanded(flex: 6, child: Text('${v ?? '-'}', textAlign: TextAlign.end)),
                  ],
                ),
              );
          return SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text('${r['receipt_number']}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                const SizedBox(height: 8),
                row(isSwahili ? 'Mradi' : 'Project', r['project_name']),
                row(isSwahili ? 'Tarehe' : 'Date', r['receipt_date']),
                row(isSwahili ? 'Msambazaji / Chanzo' : 'Supplier / Source', r['supplier']),
                row(isSwahili ? 'Alipokea' : 'Received by', r['received_by']),
                if ((r['notes'] ?? '').toString().isNotEmpty) row(isSwahili ? 'Maelezo' : 'Notes', r['notes']),
                const Divider(height: 24),
                Text(isSwahili ? 'Bidhaa zilizopokelewa' : 'Items received', style: const TextStyle(fontWeight: FontWeight.w700)),
                for (final it in items)
                  ListTile(
                    dense: true,
                    contentPadding: EdgeInsets.zero,
                    title: Text('${it['description']}'),
                    subtitle: Text('${it['item_code'] ?? ''}'),
                    trailing: Text('${_qty(it['quantity'])} ${it['unit']}', style: const TextStyle(fontWeight: FontWeight.w700)),
                  ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: delete,
                  icon: const Icon(Icons.delete_outline, color: AppColors.error),
                  label: Text(isSwahili ? 'Futa Risiti' : 'Delete Receipt'),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

// ───────────────────────────────────────────────────────────────────────────
// New receipt: Step 1 site, Step 2 details, Step 3 items (as on the web)
// ───────────────────────────────────────────────────────────────────────────

class _ReceiptLine {
  String? stockItemId; // null = a new stock item
  final description = TextEditingController();
  final unit = TextEditingController();
  final quantity = TextEditingController();

  void dispose() {
    description.dispose();
    unit.dispose();
    quantity.dispose();
  }
}

class StockReceiptFormScreen extends ConsumerStatefulWidget {
  final String? projectId;
  const StockReceiptFormScreen({super.key, this.projectId});

  @override
  ConsumerState<StockReceiptFormScreen> createState() => _StockReceiptFormScreenState();
}

class _StockReceiptFormScreenState extends ConsumerState<StockReceiptFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late String? _projectId = widget.projectId;
  final _date = TextEditingController(text: DateFormat('yyyy-MM-dd').format(DateTime.now()));
  final _supplier = TextEditingController();
  final _notes = TextEditingController();
  final List<_ReceiptLine> _lines = [_ReceiptLine()];
  bool _saving = false;
  String? _err;

  @override
  void dispose() {
    _date.dispose();
    _supplier.dispose();
    _notes.dispose();
    for (final l in _lines) {
      l.dispose();
    }
    super.dispose();
  }

  Future<void> _save(bool isSwahili) async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _err = null;
    });
    try {
      await ref.read(apiClientProvider).post('/project-stock-receipts', data: {
        'project_id': _projectId,
        'receipt_date': _date.text,
        'supplier': _supplier.text.trim().isEmpty ? null : _supplier.text.trim(),
        'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
        'items': [
          for (final l in _lines)
            {
              if (l.stockItemId != null) 'stock_item_id': l.stockItemId,
              'description': l.description.text.trim(),
              'unit': l.unit.text.trim(),
              'quantity': l.quantity.text.trim(),
            },
        ],
      });
      ref.invalidate(_stockProvider(_projectId));
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(isSwahili ? 'Risiti imehifadhiwa na hisa imesasishwa' : 'Stock receipt saved and quantities updated.')),
      );
      Navigator.pop(context, true);
    } catch (e) {
      if (mounted) setState(() => _err = _error(e, 'Failed to save the receipt'));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final projects = ((ref.watch(_stockProvider(null)).valueOrNull?['meta'] as Map?)?['projects'] as List? ?? const [])
        .cast<Map<String, dynamic>>();
    final stock = _projectId == null
        ? const <Map<String, dynamic>>[]
        : (ref.watch(_stockProvider(_projectId)).valueOrNull?['data'] as List? ?? const []).cast<Map<String, dynamic>>();
    String? req(String? v) => (v == null || v.trim().isEmpty) ? (isSwahili ? 'Hitaji' : 'Required') : null;

    Widget heading(String text) => Padding(
          padding: const EdgeInsets.only(top: 8, bottom: 8),
          child: Text(text, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
        );

    return Scaffold(
      appBar: AppBar(title: Text(isSwahili ? 'Pokea Bidhaa' : 'New Stock Receipt')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            heading(isSwahili ? 'Hatua 1 — Chagua Site ya Kupokea' : 'Step 1 — Select Receiving Site'),
            DropdownButtonFormField<String>(
              isExpanded: true,
              initialValue: projects.any((p) => '${p['id']}' == _projectId) ? _projectId : null,
              decoration: InputDecoration(labelText: '${isSwahili ? 'Mradi / Site' : 'Project / Site'} *'),
              items: [for (final p in projects) DropdownMenuItem(value: '${p['id']}', child: Text('${p['name']}', overflow: TextOverflow.ellipsis))],
              validator: req,
              onChanged: (v) => setState(() {
                _projectId = v;
                for (final l in _lines) {
                  l.stockItemId = null;
                }
              }),
            ),
            heading(isSwahili ? 'Hatua 2 — Maelezo ya Risiti' : 'Step 2 — Receipt Details'),
            TextFormField(
              controller: _date,
              readOnly: true,
              decoration: InputDecoration(
                labelText: '${isSwahili ? 'Tarehe ya Risiti' : 'Receipt Date'} *',
                suffixIcon: const Icon(Icons.calendar_today_outlined),
              ),
              validator: req,
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: DateTime.tryParse(_date.text) ?? DateTime.now(),
                  firstDate: DateTime(2000),
                  lastDate: DateTime(2100),
                );
                if (picked != null) setState(() => _date.text = DateFormat('yyyy-MM-dd').format(picked));
              },
            ),
            const SizedBox(height: 12),
            TextFormField(controller: _supplier, decoration: InputDecoration(labelText: isSwahili ? 'Msambazaji / Chanzo' : 'Supplier / Source')),
            const SizedBox(height: 12),
            TextFormField(controller: _notes, maxLines: 2, decoration: InputDecoration(labelText: isSwahili ? 'Maelezo' : 'Notes')),
            heading(isSwahili ? 'Hatua 3 — Bidhaa Zilizopokelewa' : 'Step 3 — Items Received'),
            for (var i = 0; i < _lines.length; i++)
              Card(
                margin: const EdgeInsets.only(bottom: 10),
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Expanded(child: Text('${isSwahili ? 'Bidhaa' : 'Item'} ${i + 1}', style: const TextStyle(fontWeight: FontWeight.w600))),
                          if (_lines.length > 1)
                            IconButton(
                              icon: const Icon(Icons.delete_outline, color: AppColors.error),
                              onPressed: () => setState(() => _lines.removeAt(i).dispose()),
                            ),
                        ],
                      ),
                      if (stock.isNotEmpty)
                        DropdownButtonFormField<String?>(
                          isExpanded: true,
                          initialValue: stock.any((s) => '${s['id']}' == _lines[i].stockItemId) ? _lines[i].stockItemId : null,
                          decoration: InputDecoration(labelText: isSwahili ? 'Bidhaa iliyopo kwenye hisa' : 'Existing stock item'),
                          items: [
                            DropdownMenuItem<String?>(value: null, child: Text(isSwahili ? '— Bidhaa mpya —' : '— New item —')),
                            for (final s in stock)
                              DropdownMenuItem<String?>(
                                value: '${s['id']}',
                                child: Text('${s['description']} (${_qty(s['quantity_on_hand'])} ${s['unit']})', overflow: TextOverflow.ellipsis),
                              ),
                          ],
                          onChanged: (v) => setState(() {
                            _lines[i].stockItemId = v;
                            final s = stock.where((e) => '${e['id']}' == v).firstOrNull;
                            if (s != null) {
                              _lines[i].description.text = '${s['description']}';
                              _lines[i].unit.text = '${s['unit']}';
                            }
                          }),
                        ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _lines[i].description,
                        readOnly: _lines[i].stockItemId != null,
                        decoration: InputDecoration(labelText: '${isSwahili ? 'Maelezo' : 'Description'} *'),
                        validator: req,
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _lines[i].unit,
                              readOnly: _lines[i].stockItemId != null,
                              decoration: InputDecoration(labelText: '${isSwahili ? 'Kipimo' : 'Unit'} *'),
                              validator: req,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: _lines[i].quantity,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(labelText: '${isSwahili ? 'Kiasi' : 'Quantity'} *'),
                              validator: (v) {
                                final n = double.tryParse(v ?? '');
                                return n == null || n < 0.01 ? (isSwahili ? 'Kiasi kisichopungua 0.01' : 'At least 0.01') : null;
                              },
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            TextButton.icon(
              onPressed: () => setState(() => _lines.add(_ReceiptLine())),
              icon: const Icon(Icons.add),
              label: Text(isSwahili ? 'Ongeza bidhaa' : 'Add item'),
            ),
            if (_err != null)
              Padding(padding: const EdgeInsets.only(top: 8), child: Text(_err!, style: const TextStyle(color: AppColors.error))),
            const SizedBox(height: 12),
            FilledButton.icon(
              onPressed: _saving ? null : () => _save(isSwahili),
              icon: _saving
                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.check_rounded),
              label: Text(isSwahili ? 'Hifadhi Risiti' : 'Save Receipt'),
            ),
            const SizedBox(height: 90),
          ],
        ),
      ),
    );
  }
}
