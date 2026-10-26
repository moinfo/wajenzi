import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:intl/intl.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/services/export_service.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/date_filter_bar.dart';
import '../../widgets/common/motion.dart';

/// Web route of each resource, used for the Export button (same file as the web).
const _webRoutes = {
  'supervisors': 'hr_settings_supervisors',
  'banks': 'hr_settings_banks',
  'assets': 'hr_settings_assets',
  'asset-properties': 'hr_settings_asset_properties',
  'user-groups': 'hr_settings_user_groups',
  'assign-user-groups': 'hr_settings_assign_user_groups',
  'statutory-categories': 'hr_settings_categories',
  'statutory-sub-categories': 'hr_settings_sub_categories',
  'activity-templates': 'hr_settings_activity_templates',
  'client-sources': 'client_sources',
  'system-settings': 'system_settings',
  'project-payments': 'project_payments',
  'collections': 'collection',
  'grosses': 'gross',
  'transaction-movements': 'transaction_movement',
  'receivings': 'receiving',
  'supplier-receivings': 'supplier_receiving',
  'system-inventories': 'system_inventory',
  'system-credits': 'system_credit',
  'system-capitals': 'system_capital',
  'system-cashes': 'system_cash',
  'withholding-taxes': 'withholding_taxes',
  'adjusted-assessment-taxes': 'adjusted_assessment_taxes',
  'adjustment-expenses': 'expense_adjustable',
  'financial-charges': 'financial_charges',
};

/// A page and the Start Date / End Date the user chose (null = no limit).
typedef _CrudKey = ({String resource, String? start, String? end});

final _crudProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, _CrudKey>((ref, key) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/crud/${key.resource}',
    queryParameters: {
      if (key.start != null) 'start_date': key.start,
      if (key.end != null) 'end_date': key.end,
    },
  );
  return Map<String, dynamic>.from(response.data as Map);
});

/// A Settings page of the web (list + add / edit / delete) driven entirely by
/// the server's schema: `GET /crud/{resource}` returns the fields, their
/// options, the user's permissions and the rows (see MobileCrudRegistry).
class GenericCrudScreen extends ConsumerStatefulWidget {
  final String resource;
  const GenericCrudScreen({super.key, required this.resource});

  @override
  ConsumerState<GenericCrudScreen> createState() => _GenericCrudScreenState();
}

class _GenericCrudScreenState extends ConsumerState<GenericCrudScreen> {
  String _query = '';
  DateTime? _start = defaultStartDate();
  DateTime? _end = today();

  _CrudKey get _key => (
        resource: widget.resource,
        start: DateFilterBar.format(_start),
        end: DateFilterBar.format(_end),
      );

  String _show(Map<String, dynamic> item, Map<String, dynamic> field) {
    final name = field['name'] as String;
    final labels = item['labels'] as Map? ?? const {};
    if (labels[name] != null) return '${labels[name]}';
    final v = item[name];
    if (v == null || '$v'.isEmpty) return '-';
    if (field['type'] == 'boolean') return v == true ? 'Yes' : 'No';
    if (field['type'] == 'file') return 'Attached';
    return '$v';
  }

  Future<void> _openForm(Map<String, dynamic> schema, {Map<String, dynamic>? item}) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _CrudFormSheet(
        resource: widget.resource,
        schema: schema,
        item: item,
      ),
    );
    if (saved == true) ref.invalidate(_crudProvider(_key));
  }

  Future<void> _delete(Map<String, dynamic> item) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final messenger = ScaffoldMessenger.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa' : 'Delete'),
        content: Text(isSwahili ? 'Una uhakika unataka kufuta?' : 'Are you sure you want to delete this?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Futa' : 'Delete', style: const TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/crud/${widget.resource}/${item['id']}');
      ref.invalidate(_crudProvider(_key));
    } on DioException catch (e) {
      final data = e.response?.data;
      messenger.showSnackBar(
        SnackBar(
          content: Text(data is Map && data['message'] != null ? '${data['message']}' : 'Failed to delete'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_crudProvider(_key));
    final isSwahili = ref.watch(isSwahiliProvider);
    final schema = async.valueOrNull?['schema'] as Map<String, dynamic>?;
    final perms = (schema?['permissions'] as Map?) ?? const {};
    final webRoute = _webRoutes[widget.resource];

    return Scaffold(
      appBar: AppBar(
        title: Text(schema?['title']?.toString() ?? ''),
        actions: [
          ],
      ),
      floatingActionButton: perms['add'] == true
          ? Padding(
              padding: const EdgeInsets.only(bottom: 0),
              child: FloatingActionButton(
                onPressed: () => _openForm(schema!),
                child: const Icon(Icons.add_rounded),
              ),
            )
          : null,
      body: Column(
        children: [
          if (webRoute != null)
            ExportButtonsBar(
              options: webTableExportOptions(
                webRoute,
                params: {
                  'start_date': DateFilterBar.format(_start),
                  'end_date': DateFilterBar.format(_end),
                },
              ),
            ),
          // Start Date / End Date like the web's filter bar (pages with a date column).
          if (schema?['date_filter'] != null)
            DateFilterBar(
              start: _start,
              end: _end,
              onStart: (d) => setState(() => _start = d),
              onEnd: (d) => setState(() => _end = d),
            ),
          Expanded(child: RefreshIndicator(
        onRefresh: () => ref.refresh(_crudProvider(_key).future),
        child: async.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) {
            final denied = e is DioException && e.response?.statusCode == 403;
            return ListView(
              children: [
                const SizedBox(height: 120),
                Center(
                  child: denied
                      ? Text(isSwahili ? 'Huna ruhusa' : 'You do not have access to this page')
                      : TextButton.icon(
                          onPressed: () => ref.invalidate(_crudProvider(_key)),
                          icon: const Icon(Icons.refresh),
                          label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                        ),
                ),
              ],
            );
          },
          data: (payload) {
            final fields = (schema!['fields'] as List).cast<Map<String, dynamic>>();
            final byName = {for (final f in fields) f['name'] as String: f};
            final listFields = (schema['list'] as List).map((n) => byName[n]).whereType<Map<String, dynamic>>().toList();
            final items = (payload['data'] as List).cast<Map<String, dynamic>>().where((item) {
              if (_query.trim().isEmpty) return true;
              final q = _query.trim().toLowerCase();
              return listFields.any((f) => _show(item, f).toLowerCase().contains(q));
            }).toList();

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                TextField(
                  onChanged: (v) => setState(() => _query = v),
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.search_rounded),
                    hintText: isSwahili ? 'Tafuta…' : 'Search…',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    isDense: true,
                  ),
                ),
                const SizedBox(height: 12),
                if (items.isEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 48),
                    child: Center(child: Text(isSwahili ? 'Hakuna data' : 'No records')),
                  ),
                for (final (i, item) in items.indexed)
                  FadeSlideIn(index: i, child: Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: ListTile(
                      title: Text(
                        listFields.isEmpty ? '#${item['id']}' : _show(item, listFields.first),
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      subtitle: Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            for (final f in listFields.skip(1))
                              if (_show(item, f) != '-')
                                Text('${f['label']}: ${_show(item, f)}', maxLines: 2, overflow: TextOverflow.ellipsis),
                          ],
                        ),
                      ),
                      isThreeLine: listFields.length > 2,
                      trailing: (perms['edit'] == true || perms['delete'] == true)
                          ? PopupMenuButton<String>(
                              onSelected: (v) => v == 'edit' ? _openForm(schema, item: item) : _delete(item),
                              itemBuilder: (_) => [
                                if (perms['edit'] == true)
                                  PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                                if (perms['delete'] == true)
                                  PopupMenuItem(
                                    value: 'delete',
                                    child: Text(isSwahili ? 'Futa' : 'Delete', style: const TextStyle(color: AppColors.error)),
                                  ),
                              ],
                            )
                          : null,
                    ),
                  )),
                const SizedBox(height: 90),
              ],
            );
          },
        ),
      )),
        ],
      ),
    );
  }
}

class _CrudFormSheet extends ConsumerStatefulWidget {
  final String resource;
  final Map<String, dynamic> schema;
  final Map<String, dynamic>? item;
  const _CrudFormSheet({required this.resource, required this.schema, this.item});

  @override
  ConsumerState<_CrudFormSheet> createState() => _CrudFormSheetState();
}

class _CrudFormSheetState extends ConsumerState<_CrudFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _controllers = {};
  final Map<String, dynamic> _values = {};
  final Map<String, PlatformFile> _files = {};
  bool _saving = false;
  String? _error;

  List<Map<String, dynamic>> get _fields =>
      (widget.schema['fields'] as List).cast<Map<String, dynamic>>();

  @override
  void initState() {
    super.initState();
    for (final f in _fields) {
      final name = f['name'] as String;
      final current = widget.item?[name];
      if (f['type'] == 'select') {
        _values[name] = current == null ? null : '$current';
      } else if (f['type'] == 'boolean') {
        _values[name] = widget.item == null ? (f['default'] == true) : current == true;
      } else if (f['type'] == 'file') {
        // nothing to prefill
      } else {
        _controllers[name] = TextEditingController(text: current == null ? '' : '$current');
      }
    }
  }

  @override
  void dispose() {
    for (final c in _controllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _error = null;
    });

    final body = <String, dynamic>{};
    for (final f in _fields) {
      final name = f['name'] as String;
      if (f['type'] == 'file') continue;
      if (f['type'] == 'select' || f['type'] == 'boolean') {
        body[name] = _values[name];
      } else {
        final text = _controllers[name]!.text.trim();
        body[name] = text.isEmpty ? null : text;
      }
    }

    try {
      final api = ref.read(apiClientProvider);
      final url = widget.item == null
          ? '/crud/${widget.resource}'
          : '/crud/${widget.resource}/${widget.item!['id']}';

      if (_files.isNotEmpty) {
        // A file is attached: multipart, always POST (PHP does not parse PUT bodies).
        final form = FormData.fromMap({
          for (final e in body.entries)
            if (e.value != null) e.key: e.value is bool ? ((e.value as bool) ? 1 : 0) : e.value,
          for (final e in _files.entries)
            e.key: await MultipartFile.fromFile(e.value.path!, filename: e.value.name),
        });
        await api.uploadFile(url, data: form);
      } else if (widget.item == null) {
        await api.post(url, data: body);
      } else {
        await api.put(url, data: body);
      }
      if (mounted) Navigator.pop(context, true);
    } on DioException catch (e) {
      final data = e.response?.data;
      var message = 'Failed to save';
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          message = errors.values.map((v) => v is List ? v.join(' ') : '$v').join('\n');
        } else if (data['message'] != null) {
          message = '${data['message']}';
        }
      }
      if (mounted) setState(() => _error = message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final title = widget.item == null
        ? (isSwahili ? 'Ongeza' : 'Add')
        : (isSwahili ? 'Hariri' : 'Edit');

    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('$title ${widget.schema['title']}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
              const SizedBox(height: 16),
              for (final f in _fields) ...[
                _field(f, isSwahili),
                const SizedBox(height: 12),
              ],
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Text(_error!, style: const TextStyle(color: AppColors.error)),
                ),
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

  Widget _field(Map<String, dynamic> f, bool isSwahili) {
    final name = f['name'] as String;
    final required = f['required'] == true;
    final label = '${f['label']}${required ? ' *' : ''}';
    String? requiredCheck(String? v) =>
        required && (v == null || v.trim().isEmpty) ? (isSwahili ? 'Hitaji' : 'Required') : null;

    switch (f['type']) {
      case 'select':
        final options = (f['options'] as List? ?? const []).cast<Map<String, dynamic>>();
        final current = _values[name] as String?;
        return DropdownButtonFormField<String>(
          isExpanded: true,
          initialValue: options.any((o) => '${o['value']}' == current) ? current : null,
          decoration: InputDecoration(labelText: label),
          items: [
            if (!required)
              const DropdownMenuItem<String>(value: '', child: Text('—')),
            for (final o in options)
              DropdownMenuItem<String>(value: '${o['value']}', child: Text('${o['label']}', overflow: TextOverflow.ellipsis)),
          ],
          validator: (v) => requiredCheck(v),
          onChanged: (v) => setState(() => _values[name] = (v == null || v.isEmpty) ? null : v),
        );
      case 'boolean':
        return SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: Text(label),
          value: _values[name] == true,
          onChanged: (v) => setState(() => _values[name] = v),
        );
      case 'date':
        return TextFormField(
          controller: _controllers[name],
          readOnly: true,
          decoration: InputDecoration(
            labelText: label,
            suffixIcon: const Icon(Icons.calendar_today_outlined),
          ),
          validator: requiredCheck,
          onTap: () async {
            final initial = DateTime.tryParse(_controllers[name]!.text) ?? DateTime.now();
            final picked = await showDatePicker(
              context: context,
              initialDate: initial,
              firstDate: DateTime(2000),
              lastDate: DateTime(2100),
            );
            if (picked != null) {
              setState(() => _controllers[name]!.text = DateFormat('yyyy-MM-dd').format(picked));
            }
          },
        );
      case 'file':
        return OutlinedButton.icon(
          onPressed: () async {
            final result = await FilePicker.platform.pickFiles();
            if (result != null && result.files.isNotEmpty && result.files.first.path != null) {
              setState(() => _files[name] = result.files.first);
            }
          },
          icon: const Icon(Icons.attach_file),
          label: Text(
            _files[name]?.name ??
                (widget.item?[name] != null
                    ? (isSwahili ? 'Badilisha kiambatisho' : 'Replace attachment')
                    : (isSwahili ? 'Chagua faili' : 'Choose file')),
            overflow: TextOverflow.ellipsis,
          ),
        );
      case 'number':
        return TextFormField(
          controller: _controllers[name],
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: InputDecoration(labelText: label),
          validator: requiredCheck,
        );
      case 'textarea':
        return TextFormField(
          controller: _controllers[name],
          maxLines: 3,
          decoration: InputDecoration(labelText: label),
          validator: requiredCheck,
        );
      default:
        return TextFormField(
          controller: _controllers[name],
          decoration: InputDecoration(labelText: label),
          validator: requiredCheck,
        );
    }
  }
}
