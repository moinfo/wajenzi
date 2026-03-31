import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _suppliersSearchProvider = StateProvider<String>((ref) => '');

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
    FutureProvider.autoDispose<Map<String, List<Map<String, dynamic>>>>((
      ref,
    ) async {
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
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final asyncData = ref.watch(_suppliersProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_suppliersSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Wauzaji' : 'Suppliers'),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openSupplierForm(context, ref),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza Muuzaji' : 'Add Supplier',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_suppliersProvider);
          ref.invalidate(_supplierReferenceProvider);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_suppliersSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta muuzaji...'
                        : 'Search suppliers...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(_suppliersSearchProvider.notifier)
                                        .state =
                                    '',
                          )
                        : null,
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.white,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                  ),
                ),
              ),
            ),
            asyncData.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.error_outline,
                        size: 64,
                        color: Colors.grey[400],
                      ),
                      const SizedBox(height: 16),
                      Text('$error', textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () => ref.invalidate(_suppliersProvider),
                        child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                      ),
                    ],
                  ),
                ),
              ),
              data: (items) {
                final filtered = search.isEmpty
                    ? items
                    : items.where((item) {
                        final name =
                            item['name']?.toString().toLowerCase() ?? '';
                        final phone =
                            item['phone']?.toString().toLowerCase() ?? '';
                        return name.contains(search) || phone.contains(search);
                      }).toList();

                if (filtered.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.local_shipping_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            items.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna muuzaji'
                                      : 'No suppliers found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () =>
                                  ref
                                          .read(
                                            _suppliersSearchProvider.notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa utafutaji' : 'Clear search',
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final item = filtered[index];
                      return _SupplierCard(
                        item: item,
                        onRefresh: () {
                          ref.invalidate(_suppliersProvider);
                          ref.invalidate(_supplierReferenceProvider);
                        },
                      );
                    }, childCount: filtered.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openSupplierForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
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

  const _SupplierCard({required this.item, required this.onRefresh});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final contacts = (item['contacts'] as List? ?? const [])
        .whereType<Map>()
        .map((contact) => Map<String, dynamic>.from(contact))
        .toList();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
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
              Text('${isSwahili ? 'Simu' : 'Phone'}: ${item['phone'] ?? '-'}'),
              Text(
                '${isSwahili ? 'Mfumo' : 'System'}: ${item['system_name'] ?? '-'}',
              ),
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
                  title: Text(isSwahili ? 'Futa Muuzaji' : 'Delete Supplier'),
                  content: Text(
                    isSwahili
                        ? 'Futa ${item['name']} na mawasilisho yote?'
                        : 'Delete ${item['name']} and all its contacts?',
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(dialogContext, false),
                      child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(dialogContext, true),
                      child: Text(
                        isSwahili ? 'Futa' : 'Delete',
                        style: const TextStyle(color: AppColors.error),
                      ),
                    ),
                  ],
                ),
              );
              if (confirmed == true) {
                try {
                  await ref
                      .read(apiClientProvider)
                      .delete('/settings-suppliers/${item['id']}');
                  onRefresh();
                  if (!context.mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        isSwahili
                            ? 'Muuzaji amefutwa'
                            : 'Supplier deleted successfully',
                      ),
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
          itemBuilder: (_) => [
            PopupMenuItem(
              value: 'edit',
              child: Row(
                children: [
                  const Icon(Icons.edit_rounded, size: 20),
                  const SizedBox(width: 8),
                  Text(isSwahili ? 'Hariri muuzaji' : 'Edit supplier'),
                ],
              ),
            ),
            PopupMenuItem(
              value: 'add-contact',
              child: Row(
                children: [
                  const Icon(Icons.person_add_rounded, size: 20),
                  const SizedBox(width: 8),
                  Text(isSwahili ? 'Ongeza mwasilisho' : 'Add contact'),
                ],
              ),
            ),
            PopupMenuItem(
              value: 'delete',
              child: Row(
                children: [
                  const Icon(
                    Icons.delete_rounded,
                    size: 20,
                    color: AppColors.error,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    isSwahili ? 'Futa muuzaji' : 'Delete supplier',
                    style: const TextStyle(color: AppColors.error),
                  ),
                ],
              ),
            ),
          ],
        ),
        children: [
          if ((item['address']?.toString().trim().isNotEmpty ?? false))
            Align(
              alignment: Alignment.centerLeft,
              child: Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Text(
                  '${isSwahili ? 'Anwani' : 'Address'}: ${item['address']}',
                ),
              ),
            ),
          Row(
            children: [
              Text(
                isSwahili ? 'Mawasilisho ya Muuzaji' : 'Supplier Contacts',
                style: const TextStyle(fontWeight: FontWeight.w700),
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
                label: Text(isSwahili ? 'Ongeza' : 'Add'),
              ),
            ],
          ),
          if (contacts.isEmpty)
            Align(
              alignment: Alignment.centerLeft,
              child: Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  isSwahili
                      ? 'Hakuna mawasilisho bado.'
                      : 'No supplier contacts yet.',
                  style: const TextStyle(color: AppColors.textSecondary),
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
    final isSwahili = ref.watch(isSwahiliProvider);
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: const Icon(Icons.account_balance_outlined),
      title: Text(contact['account_name']?.toString() ?? '-'),
      subtitle: Text(
        '${contact['bank_name'] ?? '-'} - ${contact['account_number'] ?? '-'}',
      ),
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
                title: Text(
                  isSwahili ? 'Futa Mwasilisho' : 'Delete Supplier Contact',
                ),
                content: Text(
                  isSwahili
                      ? 'Futa ${contact['account_name']}?'
                      : 'Delete ${contact['account_name']}?',
                ),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(dialogContext, false),
                    child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
                  ),
                  TextButton(
                    onPressed: () => Navigator.pop(dialogContext, true),
                    child: Text(
                      isSwahili ? 'Futa' : 'Delete',
                      style: const TextStyle(color: AppColors.error),
                    ),
                  ),
                ],
              ),
            );
            if (confirmed == true) {
              try {
                await ref
                    .read(apiClientProvider)
                    .delete('/settings-suppliers/contacts/${contact['id']}');
                onRefresh();
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(
                      isSwahili
                          ? 'Mwasilisho umefutwa'
                          : 'Supplier contact deleted successfully',
                    ),
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
        itemBuilder: (_) => [
          PopupMenuItem(
            value: 'edit',
            child: Row(
              children: [
                const Icon(Icons.edit_rounded, size: 20),
                const SizedBox(width: 8),
                Text(isSwahili ? 'Hariri' : 'Edit'),
              ],
            ),
          ),
          PopupMenuItem(
            value: 'delete',
            child: Row(
              children: [
                const Icon(
                  Icons.delete_rounded,
                  size: 20,
                  color: AppColors.error,
                ),
                const SizedBox(width: 8),
                Text(
                  isSwahili ? 'Futa' : 'Delete',
                  style: const TextStyle(color: AppColors.error),
                ),
              ],
            ),
          ),
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
    _nameController = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
    _phoneController = TextEditingController(
      text: widget.item?['phone']?.toString() ?? '',
    );
    _addressController = TextEditingController(
      text: widget.item?['address']?.toString() ?? '',
    );
    _vrnController = TextEditingController(
      text: widget.item?['vrn']?.toString() ?? '',
    );
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
    final isSwahili = ref.watch(isSwahiliProvider);
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
                  _isEdit
                      ? (isSwahili ? 'Hariri Muuzaji' : 'Edit Supplier')
                      : (isSwahili
                            ? 'Unda Muuzaji Mpya'
                            : 'Create New Supplier'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina' : 'Name',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? (isSwahili ? 'Jina linahitajika' : 'Name is required')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _phoneController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Simu' : 'Phone',
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _addressController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Anwani' : 'Address',
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _vrnController,
                  decoration: InputDecoration(
                    labelText: 'VRN',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? (isSwahili ? 'VRN inahitajika' : 'VRN is required')
                      : null,
                ),
                const SizedBox(height: 16),
                referenceAsync.when(
                  loading: () => const LinearProgressIndicator(),
                  error: (_, _) => const SizedBox.shrink(),
                  data: (reference) {
                    final systems =
                        reference['systems'] ?? const <Map<String, dynamic>>[];
                    return DropdownButtonFormField<int>(
                      value: _systemId,
                      decoration: InputDecoration(
                        labelText: isSwahili ? 'Mfumo' : 'System',
                        border: const OutlineInputBorder(),
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
                      validator: (value) => value == null
                          ? (isSwahili
                                ? 'Mfumo unahitajika'
                                : 'System is required')
                          : null,
                    );
                  },
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? (isSwahili ? 'Inahifadhi...' : 'Saving...')
                          : (_isEdit
                                ? (isSwahili
                                      ? 'Sasisha Muuzaji'
                                      : 'Update Supplier')
                                : (isSwahili
                                      ? 'Hifadhi Muuzaji'
                                      : 'Save Supplier')),
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);

    final payload = {
      'name': _nameController.text.trim(),
      'phone': _phoneController.text.trim().isEmpty
          ? null
          : _phoneController.text.trim(),
      'address': _addressController.text.trim().isEmpty
          ? null
          : _addressController.text.trim(),
      'vrn': _vrnController.text.trim(),
      'system_id': _systemId,
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put(
          '/settings-suppliers/${widget.item!['id']}',
          data: payload,
        );
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

  const _SupplierContactFormSheet({required this.parentSupplier, this.contact});

  @override
  ConsumerState<_SupplierContactFormSheet> createState() =>
      _SupplierContactFormSheetState();
}

class _SupplierContactFormSheetState
    extends ConsumerState<_SupplierContactFormSheet> {
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
    _accountNameController = TextEditingController(
      text: widget.contact?['account_name']?.toString() ?? '',
    );
    _accountNumberController = TextEditingController(
      text: widget.contact?['account_number']?.toString() ?? '',
    );
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
    final isSwahili = ref.watch(isSwahiliProvider);
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
                  _isEdit
                      ? (isSwahili
                            ? 'Hariri Mwasilisho'
                            : 'Edit Supplier Contact')
                      : (isSwahili
                            ? 'Ongeza Mwasilisho wa Muuzaji'
                            : 'Add Supplier Contact'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                referenceAsync.when(
                  loading: () => const LinearProgressIndicator(),
                  error: (_, _) => const SizedBox.shrink(),
                  data: (reference) {
                    final suppliers =
                        reference['suppliers'] ??
                        const <Map<String, dynamic>>[];
                    final banks =
                        reference['banks'] ?? const <Map<String, dynamic>>[];
                    return Column(
                      children: [
                        DropdownButtonFormField<int>(
                          value: _supplierId,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Muuzaji' : 'Supplier',
                            border: const OutlineInputBorder(),
                          ),
                          items: suppliers
                              .map(
                                (supplier) => DropdownMenuItem<int>(
                                  value: supplier['id'] is int
                                      ? supplier['id'] as int
                                      : int.tryParse(supplier['id'].toString()),
                                  child: Text(
                                    supplier['name']?.toString() ?? '-',
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged: _isEdit
                              ? null
                              : (value) => setState(() => _supplierId = value),
                          validator: (value) => value == null
                              ? (isSwahili
                                    ? 'Muuzaji unahitajika'
                                    : 'Supplier is required')
                              : null,
                        ),
                        const SizedBox(height: 16),
                        DropdownButtonFormField<int>(
                          value: _bankId,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Benki' : 'Bank',
                            border: const OutlineInputBorder(),
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
                          validator: (value) => value == null
                              ? (isSwahili
                                    ? 'Benki inahitajika'
                                    : 'Bank is required')
                              : null,
                        ),
                      ],
                    );
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _accountNameController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina la Akaunti' : 'Account Name',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? (isSwahili
                            ? 'Jina la akaunti linahitajika'
                            : 'Account name is required')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _accountNumberController,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Nambari ya Akaunti'
                        : 'Account Number',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? (isSwahili
                            ? 'Nambari ya akaunti inahitajika'
                            : 'Account number is required')
                      : null,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? (isSwahili ? 'Inahifadhi...' : 'Saving...')
                          : (_isEdit
                                ? (isSwahili
                                      ? 'Sasisha Mwasilisho'
                                      : 'Update Contact')
                                : (isSwahili
                                      ? 'Hifadhi Mwasilisho'
                                      : 'Save Contact')),
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
        await api.put(
          '/settings-suppliers/contacts/${widget.contact!['id']}',
          data: payload,
        );
      } else {
        await api.post(
          '/settings-suppliers/$_supplierId/contacts',
          data: payload,
        );
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
