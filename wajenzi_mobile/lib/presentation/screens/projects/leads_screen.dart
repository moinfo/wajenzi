import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _leadsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/leads');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};

  return {
    'items': (data['data'] as List? ?? const []).whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList(),
    'meta': data['meta'] is Map ? Map<String, dynamic>.from(data['meta'] as Map) : const <String, dynamic>{},
  };
});

final _leadRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/leads/reference-data');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};

  return data['data'] is Map ? Map<String, dynamic>.from(data['data'] as Map) : const <String, dynamic>{};
});

String _leadMessage(Object error, bool isSwahili) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }
    }
  }

  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class LeadsScreen extends ConsumerWidget {
  const LeadsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final leadsAsync = ref.watch(_leadsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Lead' : 'Leads'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_rounded),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_leadsProvider),
        child: leadsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ErrorView(
            message: _leadMessage(error, isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_leadsProvider),
          ),
          data: (payload) {
            final leads = (payload['items'] as List).cast<Map<String, dynamic>>();
            final total = payload['meta'] is Map<String, dynamic> ? payload['meta']['total'] ?? leads.length : leads.length;

            if (leads.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.person_search_outlined,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna lead zilizopatikana' : 'No leads found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: leads.length + 2,
              itemBuilder: (context, index) {
                if (index == 0) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Text(
                      isSwahili ? 'Jumla ya lead: $total' : 'Total leads: $total',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                      ),
                    ),
                  );
                }

                if (index == leads.length + 1) {
                  return const SizedBox(height: 80);
                }

                final lead = leads[index - 1];

                return _LeadCard(
                  lead: lead,
                  isSwahili: isSwahili,
                  onView: () => _showDetails(context, ref, lead),
                  onEdit: () => _openForm(context, ref, lead: lead),
                  onDelete: () => _deleteLead(context, ref, lead),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? lead}) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.94,
        child: _LeadFormSheet(lead: lead),
      ),
    );

    if (result == true) {
      ref.invalidate(_leadsProvider);
    }
  }

  Future<void> _deleteLead(BuildContext context, WidgetRef ref, Map<String, dynamic> lead) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Lead' : 'Delete Lead'),
        content: Text(
          isSwahili ? 'Je, unataka kufuta ${lead['name']}?' : 'Delete ${lead['name']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    try {
      await ref.read(apiClientProvider).delete('/leads/${lead['id']}');

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Lead imefutwa' : 'Lead deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }

      ref.invalidate(_leadsProvider);
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_leadMessage(error, isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(BuildContext context, WidgetRef ref, Map<String, dynamic> lead) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.88,
        child: Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            top: false,
            child: Column(
              children: [
                const SizedBox(height: 12),
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          lead['name'] as String? ?? '-',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 12),
                        _info('Lead No', lead['lead_number']),
                        _info(isSwahili ? 'Hali' : 'Status', lead['lead_status_name'] ?? lead['status']),
                        _info(isSwahili ? 'Chanzo' : 'Source', lead['lead_source_name']),
                        _info(isSwahili ? 'Huduma' : 'Service', lead['service_interested_name']),
                        _info(isSwahili ? 'Muuza' : 'Salesperson', lead['salesperson_name']),
                        _info(isSwahili ? 'Mteja' : 'Client', lead['client_name']),
                        _info(isSwahili ? 'Simu' : 'Phone', lead['phone']),
                        _info('Email', lead['email']),
                        _info(isSwahili ? 'Anwani' : 'Address', lead['address']),
                        _info(isSwahili ? 'Mji' : 'City', lead['city']),
                        _info(isSwahili ? 'Eneo' : 'Site Location', lead['site_location']),
                        _info(isSwahili ? 'Thamani ya Makadirio' : 'Estimated Value', lead['estimated_value']),
                        _info(isSwahili ? 'Kumbukumbu' : 'Notes', lead['notes']),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _info(String label, dynamic value) {
    final displayValue = (value ?? '').toString().trim();

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
          ),
          const SizedBox(height: 3),
          Text(displayValue.isEmpty ? '-' : displayValue),
        ],
      ),
    );
  }
}

class _LeadCard extends StatelessWidget {
  final Map<String, dynamic> lead;
  final bool isSwahili;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _LeadCard({
    required this.lead,
    required this.isSwahili,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        onTap: onView,
        leading: const CircleAvatar(child: Icon(Icons.person_search)),
        title: Text(lead['name'] as String? ?? '-'),
        subtitle: Text('${lead['lead_number'] ?? '-'} - ${lead['phone'] ?? '-'}'),
        trailing: PopupMenuButton<String>(
          onSelected: (value) {
            if (value == 'view') {
              onView();
            } else if (value == 'edit') {
              onEdit();
            } else if (value == 'delete') {
              onDelete();
            }
          },
          itemBuilder: (_) => [
            PopupMenuItem<String>(
              value: 'view',
              child: Text(isSwahili ? 'Tazama' : 'View'),
            ),
            PopupMenuItem<String>(
              value: 'edit',
              child: Text(isSwahili ? 'Hariri' : 'Edit'),
            ),
            PopupMenuItem<String>(
              value: 'delete',
              child: Text(isSwahili ? 'Futa' : 'Delete'),
            ),
          ],
        ),
      ),
    );
  }
}

class _LeadFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? lead;

  const _LeadFormSheet({this.lead});

  @override
  ConsumerState<_LeadFormSheet> createState() => _LeadFormSheetState();
}

class _LeadFormSheetState extends ConsumerState<_LeadFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController = TextEditingController(text: widget.lead?['name']?.toString() ?? '');
  late final TextEditingController _phoneController = TextEditingController(text: widget.lead?['phone']?.toString() ?? '');
  late final TextEditingController _emailController = TextEditingController(text: widget.lead?['email']?.toString() ?? '');
  late final TextEditingController _dateController = TextEditingController(text: widget.lead?['lead_date']?.toString() ?? '');
  late final TextEditingController _addressController = TextEditingController(text: widget.lead?['address']?.toString() ?? '');
  late final TextEditingController _cityController = TextEditingController(text: widget.lead?['city']?.toString() ?? '');
  late final TextEditingController _siteController = TextEditingController(text: widget.lead?['site_location']?.toString() ?? '');
  late final TextEditingController _estimatedValueController = TextEditingController(text: widget.lead?['estimated_value']?.toString() ?? '');
  late final TextEditingController _notesController = TextEditingController(text: widget.lead?['notes']?.toString() ?? '');

  int? _clientId;
  int? _sourceId;
  int? _serviceId;
  int? _statusId;
  int? _salespersonId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _clientId = _toInt(widget.lead?['client_id']);
    _sourceId = _toInt(widget.lead?['lead_source_id']);
    _serviceId = _toInt(widget.lead?['service_interested_id']);
    _statusId = _toInt(widget.lead?['lead_status_id']);
    _salespersonId = _toInt(widget.lead?['salesperson_id']);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _dateController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _siteController.dispose();
    _estimatedValueController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final refsAsync = ref.watch(_leadRefsProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: refsAsync.when(
          loading: () => const Padding(
            padding: EdgeInsets.all(32),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => Padding(
            padding: const EdgeInsets.all(24),
            child: Text(_leadMessage(error, isSwahili)),
          ),
          data: (refs) => Column(
            children: [
              const SizedBox(height: 12),
              Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: isDarkMode ? Colors.white24 : Colors.black12,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.of(context).viewInsets.bottom + 24),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          widget.lead == null
                              ? (isSwahili ? 'Lead Mpya' : 'New Lead')
                              : (isSwahili ? 'Hariri Lead' : 'Edit Lead'),
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 20),
                        _input(_nameController, isSwahili ? 'Jina *' : 'Name *', required: true),
                        const SizedBox(height: 12),
                        _input(
                          _phoneController,
                          isSwahili ? 'Simu *' : 'Phone *',
                          required: true,
                          keyboardType: TextInputType.phone,
                        ),
                        const SizedBox(height: 12),
                        _input(_emailController, 'Email', keyboardType: TextInputType.emailAddress),
                        const SizedBox(height: 12),
                        _input(_dateController, isSwahili ? 'Tarehe (YYYY-MM-DD)' : 'Date (YYYY-MM-DD)'),
                        const SizedBox(height: 12),
                        _input(_addressController, isSwahili ? 'Anwani' : 'Address'),
                        const SizedBox(height: 12),
                        _dropdown(
                          isSwahili ? 'Chanzo cha Lead *' : 'Lead Source *',
                          refs['lead_sources'],
                          _sourceId,
                          (value) => setState(() => _sourceId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          isSwahili ? 'Huduma *' : 'Service *',
                          _mapOptions(refs['service_interesteds']),
                          _serviceId,
                          (value) => setState(() => _serviceId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          isSwahili ? 'Hali *' : 'Status *',
                          refs['lead_statuses'],
                          _statusId,
                          (value) => setState(() => _statusId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          isSwahili ? 'Muuza *' : 'Salesperson *',
                          refs['salespeople'],
                          _salespersonId,
                          (value) => setState(() => _salespersonId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          isSwahili ? 'Mteja' : 'Client',
                          _clientOptions(refs['clients']),
                          _clientId,
                          (value) => setState(() => _clientId = value),
                          required: false,
                        ),
                        const SizedBox(height: 12),
                        _input(_cityController, isSwahili ? 'Mji' : 'City'),
                        const SizedBox(height: 12),
                        _input(_siteController, isSwahili ? 'Eneo la Site' : 'Site Location'),
                        const SizedBox(height: 12),
                        _input(
                          _estimatedValueController,
                          isSwahili ? 'Thamani ya Makadirio' : 'Estimated Value',
                          keyboardType: TextInputType.number,
                        ),
                        const SizedBox(height: 12),
                        _input(_notesController, isSwahili ? 'Maelezo' : 'Notes', maxLines: 3),
                        const SizedBox(height: 20),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _saving ? null : _submit,
                            child: _saving
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : Text(widget.lead == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _input(
    TextEditingController controller,
    String label, {
    bool required = false,
    int maxLines = 1,
    TextInputType? keyboardType,
  }) {
    final isDarkMode = ref.read(isDarkModeProvider);

    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      validator: required
          ? (value) => (value == null || value.trim().isEmpty) ? 'Required' : null
          : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dropdown(
    String label,
    dynamic rawItems,
    int? value,
    ValueChanged<int?> onChanged, {
    bool required = true,
  }) {
    final isDarkMode = ref.read(isDarkModeProvider);
    final items = _mapOptions(rawItems);

    return DropdownButtonFormField<int>(
      value: items.any((item) => _toInt(item['id']) == value) ? value : null,
      validator: required ? (selected) => selected == null ? 'Required' : null : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map(
            (item) => DropdownMenuItem<int>(
              value: _toInt(item['id']),
              child: Text(item['name']?.toString() ?? '-'),
            ),
          )
          .toList(),
      onChanged: onChanged,
    );
  }

  List<Map<String, dynamic>> _clientOptions(dynamic rawItems) {
    final list = rawItems as List? ?? const [];

    return list.whereType<Map>().map((item) {
      final map = Map<String, dynamic>.from(item);
      final fullName = '${map['first_name'] ?? ''} ${map['last_name'] ?? ''}'.trim();

      return {
        'id': map['id'],
        'name': fullName.isEmpty ? '-' : fullName,
      };
    }).toList();
  }

  List<Map<String, dynamic>> _mapOptions(dynamic rawItems) {
    final list = rawItems as List? ?? const [];
    return list.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _saving = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = <String, dynamic>{
        'client_id': _clientId,
        'name': _nameController.text.trim(),
        'phone': _phoneController.text.trim(),
        'email': _nullableText(_emailController),
        'lead_date': _nullableText(_dateController),
        'address': _nullableText(_addressController),
        'city': _nullableText(_cityController),
        'site_location': _nullableText(_siteController),
        'estimated_value': _nullableText(_estimatedValueController),
        'notes': _nullableText(_notesController),
        'lead_source_id': _sourceId,
        'service_interested_id': _serviceId,
        'lead_status_id': _statusId,
        'salesperson_id': _salespersonId,
      };

      if (widget.lead == null) {
        await api.post('/leads', data: data);
      } else {
        await api.put('/leads/${widget.lead!['id']}', data: data);
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_leadMessage(error, ref.read(isSwahiliProvider))),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  String? _nullableText(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : value;
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
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        Text(message, textAlign: TextAlign.center),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}

int? _toInt(dynamic value) {
  if (value is int) {
    return value;
  }

  return int.tryParse(value?.toString() ?? '');
}
