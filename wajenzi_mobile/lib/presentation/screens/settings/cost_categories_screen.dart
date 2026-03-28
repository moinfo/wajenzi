import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _costCategoriesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/cost-categories');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

class CostCategoriesScreen extends ConsumerWidget {
  const CostCategoriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(_costCategoriesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Cost Categories'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_costCategoriesProvider),
        child: asyncData.when(
          loading: () => const LoadingWidget(message: 'Loading cost categories...'),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              const Text(
                'Failed to load cost categories',
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
                        const Icon(Icons.category_outlined, size: 56, color: AppColors.primary),
                        const SizedBox(height: 12),
                        const Text(
                          'No cost categories found',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Create a cost category to match the web settings page.',
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: const Text('New Cost Category'),
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
                        child: const Icon(Icons.category_outlined, color: AppColors.primary),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Cost Categories',
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
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                      leading: Container(
                        width: 38,
                        height: 38,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: Colors.grey.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          '${index + 1}',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      title: Text(
                        item['name']?.toString() ?? '-',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
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
        label: const Text('New Cost Category'),
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
        heightFactor: 0.52,
        child: _CostCategoryFormSheet(item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_costCategoriesProvider);
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
        title: const Text('Delete Cost Category'),
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
      await ref.read(apiClientProvider).delete('/cost-categories/${item['id']}');
      ref.invalidate(_costCategoriesProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Cost category deleted successfully'),
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

class _CostCategoryFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _CostCategoryFormSheet({this.item});

  @override
  ConsumerState<_CostCategoryFormSheet> createState() => _CostCategoryFormSheetState();
}

class _CostCategoryFormSheetState extends ConsumerState<_CostCategoryFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
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
                  _isEdit ? 'Edit Cost Category' : 'Create New Cost Category',
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    labelText: 'Name',
                    border: OutlineInputBorder(),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Name is required' : null,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? 'Saving...'
                          : (_isEdit ? 'Update Cost Category' : 'Save Cost Category'),
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
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/cost-categories/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/cost-categories', data: payload);
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
