import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _suppliersProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/settings-suppliers');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

final _supplierReferenceProvider =
    FutureProvider.autoDispose<Map<String, List<Map<String, dynamic>>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/settings-suppliers/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final payload = data['data'] is Map<String, dynamic>
      ? data['data'] as Map<String, dynamic>
      : const <String, dynamic>{};

  List<Map<String, dynamic>> parseList(String key) {
    final values = payload[key] as List? ?? const [];
    return values
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  return {
    'systems': parseList('systems'),
    'banks': parseList('banks'),
    'suppliers': parseList('suppliers'),
  };
});

class SuppliersScreen extends ConsumerWidget {
  const SuppliersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(_suppliersProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Suppliers'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_business_outlined),
            onPressed: () => _openSupplierForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_suppliersProvider);
          ref.invalidate(_supplierReferenceProvider);
        },
        child: asyncData.when(
          loading: () => const LoadingWidget(message: 'Loading suppliers...'),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              const Text(
                'Failed to load suppliers',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              Text(vatErrorMessage(error), textAlign: TextAlign.center),
            ],
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(24),
                children: [
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: Column(
                      children: [
                        const Icon(Icons.local_shipping_outlined, size: 56, color: AppColors.primary),
                        const SizedBox(height: 12),
                        const Text(
                          'No suppliers found',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Create a supplier to manage this setting from mobile.',
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openSupplierForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: const Text('New Supplier'),
                        ),
                      ],
                    ),
                  ),
                ],
              );
            }

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 18,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 52,
                        height: 52,
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: const Icon(Icons.local_shipping_outlined, color: AppColors.primary),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Suppliers',
                              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Showing ${items.length} suppliers',
                              style: const TextStyle(color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                ...items.map(
                  (item) => _SupplierCard(
                    item: item,
                    onRefresh: () {
                      ref.invalidate(_suppliersProvider);
                      ref.invalidate(_supplierReferenceProvider);
                    },
                  ),
                ),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openSupplierForm(context, ref),
        icon: const Icon(Icons.add),
        label: const Text('New Supplier'),
      ),
    );
  }

  Future<void> _openSupplierForm(BuildContext context, WidgetRef ref,
      {Map<String, dynamic>? item}) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.78,
        child: _SupplierFormSheet(item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_suppliersProvider);
      ref.invalidate(_supplierReferenceProvider);
    }
  }
}

class _SupplierCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final VoidCallback onRefresh;

  const _SupplierCard({
    required this.item,
    required this.onRefresh,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final contacts = (item['contacts'] as List? ?? const [])
        .whereType<Map>()
        .map((contact) => Map<String, dynamic>.from(contact))
        .toList();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        title: Text(
          item['name']?.toString() ?? '-',
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Phone: ${item['phone'] ?? '-'}'),
              Text('System: ${item['system_name'] ?? '-'}'),
              Text('VRN: ${item['vrn'] ?? '-'}'),
            ],
          ),
        ),
        trailing: PopupMenuButton<String>(
          onSelected: (value) async {
            if (value == 'edit') {
              final result = await showModalBottomSheet<bool>(
                context: context,
                isScrollControlled: true,
                backgroundColor: Colors.transparent,
                builder: (_) => FractionallySizedBox(
                  heightFactor: 0.78,
                  child: _SupplierFormSheet(item: item),
                ),
              );
              if (result == true) onRefresh();
            } else if (value == 'delete') {
              final confirmed = await showDialog<bool>(
                context: context,
                builder: (dialogContext) => AlertDialog(
                  title: const Text('Delete Supplier'),
                  content: Text('Delete ${item['name']} and all its contacts?'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(dialogContext, false),
                      child: const Text('Cancel'),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(dialogContext, true),
                      child: const Text('Delete'),
                    ),
                  ],
                ),
              );
              if (confirmed == true) {
                try {
                  await ref.read(apiClientProvider).delete('/settings-suppliers/${item['id']}');
                  onRefresh();
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Supplier deleted successfully'),
                      backgroundColor: AppColors.success,
                    ),
                  );
                } catch (error) {
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(vatErrorMessage(error)),
                      backgroundColor: AppColors.error,
                    ),
                  );
                }
              }
            } else if (value == 'add-contact') {
              final result = await showModalBottomSheet<bool>(
                context: context,
                isScrollControlled: true,
                backgroundColor: Colors.transparent,
                builder: (_) => FractionallySizedBox(
                  heightFactor: 0.74,
                  child: _SupplierContactFormSheet(parentSupplier: item),
                ),
              );
              if (result == true) onRefresh();
            }
          },
          itemBuilder: (_) => const [
            PopupMenuItem(value: 'edit', child: Text('Edit supplier')),
            PopupMenuItem(value: 'add-contact', child: Text('Add contact')),
            PopupMenuItem(value: 'delete', child: Text('Delete supplier')),
          ],
        ),
        children: [
          if ((item['address']?.toString().trim().isNotEmpty ?? false))
            Align(
              alignment: Alignment.centerLeft,
              child: Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Text('Address: ${item['address']}'),
              ),
            ),
          Row(
            children: [
              const Text(
                'Supplier Contacts',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
              const Spacer(),
              TextButton.icon(
                onPressed: () async {
                  final result = await showModalBottomSheet<bool>(
                    context: context,
                    isScrollControlled: true,
                    backgroundColor: Colors.transparent,
                    builder: (_) => FractionallySizedBox(
                      heightFactor: 0.74,
                      child: _SupplierContactFormSheet(parentSupplier: item),
                    ),
                  );
                  if (result == true) onRefresh();
                },
                icon: const Icon(Icons.add, size: 18),
                label: const Text('Add'),
              ),
            ],
          ),
          if (contacts.isEmpty)
            const Align(
              alignment: Alignment.centerLeft,
              child: Padding(
                padding: EdgeInsets.only(top: 8),
                child: Text(
                  'No supplier contacts yet.',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
              ),
            ),
          ...contacts.map(
            (contact) => _SupplierContactTile(
              contact: contact,
              parentSupplier: item,
              onRefresh: onRefresh,
            ),
          ),
        ],
      ),
    );
  }
}

class _SupplierContactTile extends ConsumerWidget {
  final Map<String, dynamic> contact;
  final Map<String, dynamic> parentSupplier;
  final VoidCallback onRefresh;

  const _SupplierContactTile({
    required this.contact,
    required this.parentSupplier,
    required this.onRefresh,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: const Icon(Icons.account_balance_outlined),
      title: Text(contact['account_name']?.toString() ?? '-'),
      subtitle: Text('${contact['bank_name'] ?? '-'} • ${contact['account_number'] ?? '-'}'),
      trailing: PopupMenuButton<String>(
        onSelected: (value) async {
          if (value == 'edit') {
            final result = await showModalBottomSheet<bool>(
              context: context,
              isScrollControlled: true,
              backgroundColor: Colors.transparent,
              builder: (_) => FractionallySizedBox(
                heightFactor: 0.74,
                child: _SupplierContactFormSheet(
                  parentSupplier: parentSupplier,
                  contact: contact,
                ),
              ),
            );
            if (result == true) onRefresh();
          } else if (value == 'delete') {
            final confirmed = await showDialog<bool>(
              context: context,
              builder: (dialogContext) => AlertDialog(
                title: const Text('Delete Supplier Contact'),
                content: Text('Delete ${contact['account_name']}?'),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(dialogContext, false),
                    child: const Text('Cancel'),
                  ),
                  TextButton(
                    onPressed: () => Navigator.pop(dialogContext, true),
                    child: const Text('Delete'),
                  ),
                ],
              ),
            );
            if (confirmed == true) {
              try {
                await ref.read(apiClientProvider).delete('/settings-suppliers/contacts/${contact['id']}');
                onRefresh();
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Supplier contact deleted successfully'),
                    backgroundColor: AppColors.success,
                  ),
                );
              } catch (error) {
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(vatErrorMessage(error)),
                    backgroundColor: AppColors.error,
                  ),
                );
              }
            }
          }
        },
        itemBuilder: (_) => const [
          PopupMenuItem(value: 'edit', child: Text('Edit')),
          PopupMenuItem(value: 'delete', child: Text('Delete')),
        ],
      ),
    );
  }
}

class _SupplierFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _SupplierFormSheet({this.item});

  @override
  ConsumerState<_SupplierFormSheet> createState() => _SupplierFormSheetState();
}

class _SupplierFormSheetState extends ConsumerState<_SupplierFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _phoneController;
  late final TextEditingController _addressController;
  late final TextEditingController _vrnController;
  int? _systemId;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.item?['name']?.toString() ?? '');
    _phoneController = TextEditingController(text: widget.item?['phone']?.toString() ?? '');
    _addressController = TextEditingController(text: widget.item?['address']?.toString() ?? '');
    _vrnController = TextEditingController(text: widget.item?['vrn']?.toString() ?? '');
    _systemId = widget.item?['system_id'] is int
        ? widget.item!['system_id'] as int
        : int.tryParse(widget.item?['system_id']?.toString() ?? '');
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _vrnController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final referenceAsync = ref.watch(_supplierReferenceProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Form(
            key: _formKey,
            child: ListView(
              children: [
                Center(
                  child: Container(
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  _isEdit ? 'Edit Supplier' : 'Create New Supplier',
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(labelText: 'Name', border: OutlineInputBorder()),
                  validator: (value) => (value == null || value.trim().isEmpty) ? 'Name is required' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _phoneController,
                  decoration: const InputDecoration(labelText: 'Phone', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _addressController,
                  decoration: const InputDecoration(labelText: 'Address', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _vrnController,
                  decoration: const InputDecoration(labelText: 'VRN', border: OutlineInputBorder()),
                  validator: (value) => (value == null || value.trim().isEmpty) ? 'VRN is required' : null,
                ),
                const SizedBox(height: 16),
                referenceAsync.when(
                  loading: () => const LinearProgressIndicator(),
                  error: (_, _) => const SizedBox.shrink(),
                  data: (reference) {
                    final systems = reference['systems'] ?? const <Map<String, dynamic>>[];
                    return DropdownButtonFormField<int>(
                      value: _systemId,
                      decoration: const InputDecoration(
                        labelText: 'System',
                        border: OutlineInputBorder(),
                      ),
                      items: systems
                          .map(
                            (system) => DropdownMenuItem<int>(
                              value: system['id'] is int
                                  ? system['id'] as int
                                  : int.tryParse(system['id'].toString()),
                              child: Text(system['name']?.toString() ?? '-'),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _systemId = value),
                      validator: (value) => value == null ? 'System is required' : null,
                    );
                  },
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(_submitting ? 'Saving...' : (_isEdit ? 'Update Supplier' : 'Save Supplier')),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);

    final payload = {
      'name': _nameController.text.trim(),
      'phone': _phoneController.text.trim().isEmpty ? null : _phoneController.text.trim(),
      'address': _addressController.text.trim().isEmpty ? null : _addressController.text.trim(),
      'vrn': _vrnController.text.trim(),
      'system_id': _systemId,
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/settings-suppliers/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/settings-suppliers', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _SupplierContactFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> parentSupplier;
  final Map<String, dynamic>? contact;

  const _SupplierContactFormSheet({
    required this.parentSupplier,
    this.contact,
  });

  @override
  ConsumerState<_SupplierContactFormSheet> createState() => _SupplierContactFormSheetState();
}

class _SupplierContactFormSheetState extends ConsumerState<_SupplierContactFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _accountNameController;
  late final TextEditingController _accountNumberController;
  int? _supplierId;
  int? _bankId;
  bool _submitting = false;

  bool get _isEdit => widget.contact != null;

  @override
  void initState() {
    super.initState();
    _accountNameController = TextEditingController(text: widget.contact?['account_name']?.toString() ?? '');
    _accountNumberController = TextEditingController(text: widget.contact?['account_number']?.toString() ?? '');
    _supplierId = widget.contact?['supplier_id'] is int
        ? widget.contact!['supplier_id'] as int
        : int.tryParse(widget.contact?['supplier_id']?.toString() ?? '') ??
            (widget.parentSupplier['id'] is int
                ? widget.parentSupplier['id'] as int
                : int.tryParse(widget.parentSupplier['id'].toString()));
    _bankId = widget.contact?['bank_id'] is int
        ? widget.contact!['bank_id'] as int
        : int.tryParse(widget.contact?['bank_id']?.toString() ?? '');
  }

  @override
  void dispose() {
    _accountNameController.dispose();
    _accountNumberController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final referenceAsync = ref.watch(_supplierReferenceProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Form(
            key: _formKey,
            child: ListView(
              children: [
                Center(
                  child: Container(
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  _isEdit ? 'Edit Supplier Contact' : 'Add Supplier Contact',
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 18),
                referenceAsync.when(
                  loading: () => const LinearProgressIndicator(),
                  error: (_, _) => const SizedBox.shrink(),
                  data: (reference) {
                    final suppliers = reference['suppliers'] ?? const <Map<String, dynamic>>[];
                    final banks = reference['banks'] ?? const <Map<String, dynamic>>[];
                    return Column(
                      children: [
                        DropdownButtonFormField<int>(
                          value: _supplierId,
                          decoration: const InputDecoration(
                            labelText: 'Supplier',
                            border: OutlineInputBorder(),
                          ),
                          items: suppliers
                              .map(
                                (supplier) => DropdownMenuItem<int>(
                                  value: supplier['id'] is int
                                      ? supplier['id'] as int
                                      : int.tryParse(supplier['id'].toString()),
                                  child: Text(supplier['name']?.toString() ?? '-'),
                                ),
                              )
                              .toList(),
                          onChanged: _isEdit ? null : (value) => setState(() => _supplierId = value),
                          validator: (value) => value == null ? 'Supplier is required' : null,
                        ),
                        const SizedBox(height: 16),
                        DropdownButtonFormField<int>(
                          value: _bankId,
                          decoration: const InputDecoration(
                            labelText: 'Bank',
                            border: OutlineInputBorder(),
                          ),
                          items: banks
                              .map(
                                (bank) => DropdownMenuItem<int>(
                                  value: bank['id'] is int
                                      ? bank['id'] as int
                                      : int.tryParse(bank['id'].toString()),
                                  child: Text(bank['name']?.toString() ?? '-'),
                                ),
                              )
                              .toList(),
                          onChanged: (value) => setState(() => _bankId = value),
                          validator: (value) => value == null ? 'Bank is required' : null,
                        ),
                      ],
                    );
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _accountNameController,
                  decoration: const InputDecoration(
                    labelText: 'Account Name',
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty) ? 'Account name is required' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _accountNumberController,
                  decoration: const InputDecoration(
                    labelText: 'Account Number',
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty) ? 'Account number is required' : null,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(_submitting ? 'Saving...' : (_isEdit ? 'Update Contact' : 'Save Contact')),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);

    final payload = {
      'supplier_id': _supplierId,
      'bank_id': _bankId,
      'account_name': _accountNameController.text.trim(),
      'account_number': _accountNumberController.text.trim(),
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/settings-suppliers/contacts/${widget.contact!['id']}', data: payload);
      } else {
        await api.post('/settings-suppliers/${_supplierId}/contacts', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}
