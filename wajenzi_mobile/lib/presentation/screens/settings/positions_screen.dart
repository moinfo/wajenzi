import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _positionsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/positions');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

final _positionsReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/positions/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map<String, dynamic>
      ? Map<String, dynamic>.from(data['data'] as Map<String, dynamic>)
      : const <String, dynamic>{};
});

class PositionsScreen extends ConsumerWidget {
  const PositionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(_positionsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Positions'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_positionsProvider);
          ref.invalidate(_positionsReferenceProvider);
        },
        child: asyncData.when(
          loading: () => const LoadingWidget(message: 'Loading positions...'),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              const Text(
                'Failed to load positions',
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
                        const Icon(Icons.badge_outlined, size: 56, color: AppColors.primary),
                        const SizedBox(height: 12),
                        const Text(
                          'No positions found',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Create a position to manage this setting from mobile.',
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: const Text('New Position'),
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
                        child: const Icon(Icons.badge_outlined, color: AppColors.primary),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Positions',
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Showing ${items.length} records',
                              style: const TextStyle(color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                ...List.generate(items.length, (index) {
                  final item = items[index];
                  final status = (item['status'] ?? 'ACTIVE').toString();
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      leading: Container(
                        width: 42,
                        height: 42,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: Colors.grey.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          item['abbreviation']?.toString().isNotEmpty == true
                              ? item['abbreviation'].toString()
                              : '${index + 1}',
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      title: Text(
                        item['name']?.toString() ?? '-',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                      ),
                      subtitle: Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Reports to: ${item['report_to_name'] ?? 'Not assigned'}'),
                            const SizedBox(height: 4),
                            Text(
                              item['description']?.toString().trim().isNotEmpty == true
                                  ? item['description'].toString()
                                  : 'No description',
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: status == 'ACTIVE'
                                    ? AppColors.success.withValues(alpha: 0.12)
                                    : Colors.grey.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: Text(
                                status,
                                style: TextStyle(
                                  color: status == 'ACTIVE'
                                      ? AppColors.success
                                      : AppColors.textSecondary,
                                  fontWeight: FontWeight.w700,
                                  fontSize: 12,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      trailing: PopupMenuButton<String>(
                        onSelected: (value) {
                          if (value == 'edit') {
                            _openForm(context, ref, item: item);
                          } else if (value == 'delete') {
                            _deleteItem(context, ref, item);
                          }
                        },
                        itemBuilder: (_) => const [
                          PopupMenuItem(value: 'edit', child: Text('Edit')),
                          PopupMenuItem(value: 'delete', child: Text('Delete')),
                        ],
                      ),
                    ),
                  );
                }),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(context, ref),
        icon: const Icon(Icons.add),
        label: const Text('New Position'),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.82,
        child: _PositionFormSheet(item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_positionsProvider);
      ref.invalidate(_positionsReferenceProvider);
    }
  }

  Future<void> _deleteItem(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete Position'),
        content: Text('Delete ${item['name']}?'),
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
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/positions/${item['id']}');
      ref.invalidate(_positionsProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Position deleted successfully'),
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

class _PositionFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _PositionFormSheet({this.item});

  @override
  ConsumerState<_PositionFormSheet> createState() => _PositionFormSheetState();
}

class _PositionFormSheetState extends ConsumerState<_PositionFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _abbreviationController;
  late final TextEditingController _descriptionController;
  String _status = 'ACTIVE';
  int? _reportToId;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
    _abbreviationController = TextEditingController(
      text: widget.item?['abbreviation']?.toString() ?? '',
    );
    _descriptionController = TextEditingController(
      text: widget.item?['description']?.toString() ?? '',
    );
    _status = widget.item?['status']?.toString() ?? 'ACTIVE';
    _reportToId = widget.item?['report_to_id'] is int
        ? widget.item!['report_to_id'] as int
        : int.tryParse(widget.item?['report_to_id']?.toString() ?? '');
  }

  @override
  void dispose() {
    _nameController.dispose();
    _abbreviationController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final referenceAsync = ref.watch(_positionsReferenceProvider);

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
                  _isEdit ? 'Edit Position' : 'Create New Position',
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    labelText: 'Position Name',
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Name is required' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _abbreviationController,
                  textCapitalization: TextCapitalization.characters,
                  decoration: const InputDecoration(
                    labelText: 'Abbreviation',
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? 'Abbreviation is required'
                      : null,
                ),
                const SizedBox(height: 16),
                referenceAsync.when(
                  loading: () => const LinearProgressIndicator(),
                  error: (_, _) => const SizedBox.shrink(),
                  data: (reference) {
                    final rawPositions = reference['positions'] as List? ?? const [];
                    final positions = rawPositions
                        .whereType<Map>()
                        .map((item) => Map<String, dynamic>.from(item))
                        .where((item) => item['id'] != widget.item?['id'])
                        .toList();

                    return DropdownButtonFormField<int?>(
                      value: _reportToId,
                      decoration: const InputDecoration(
                        labelText: 'Reports To',
                        border: OutlineInputBorder(),
                      ),
                      items: [
                        const DropdownMenuItem<int?>(
                          value: null,
                          child: Text('No reporting line'),
                        ),
                        ...positions.map(
                          (position) => DropdownMenuItem<int?>(
                            value: position['id'] is int
                                ? position['id'] as int
                                : int.tryParse(position['id'].toString()),
                            child: Text(position['name']?.toString() ?? '-'),
                          ),
                        ),
                      ],
                      onChanged: (value) => setState(() => _reportToId = value),
                    );
                  },
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: _status,
                  decoration: const InputDecoration(
                    labelText: 'Status',
                    border: OutlineInputBorder(),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'ACTIVE', child: Text('ACTIVE')),
                    DropdownMenuItem(value: 'INACTIVE', child: Text('INACTIVE')),
                  ],
                  onChanged: (value) => setState(() => _status = value ?? 'ACTIVE'),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Description',
                    border: OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? 'Saving...'
                          : (_isEdit ? 'Update Position' : 'Save Position'),
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
      'abbreviation': _abbreviationController.text.trim().toUpperCase(),
      'description': _descriptionController.text.trim().isEmpty
          ? null
          : _descriptionController.text.trim(),
      'report_to_id': _reportToId,
      'status': _status,
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/positions/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/positions', data: payload);
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
