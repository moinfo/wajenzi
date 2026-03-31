import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _positionsSearchProvider = StateProvider<String>((ref) => '');

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
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final asyncData = ref.watch(_positionsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_positionsSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Nafasi za Kazi' : 'Positions'),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openForm(context, ref),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza Nafasi' : 'Add Position',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_positionsProvider);
          ref.invalidate(_positionsReferenceProvider);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_positionsSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta nafasi...'
                        : 'Search positions...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(_positionsSearchProvider.notifier)
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
                        onPressed: () => ref.invalidate(_positionsProvider),
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
                        final abbr =
                            item['abbreviation']?.toString().toLowerCase() ??
                            '';
                        return name.contains(search) || abbr.contains(search);
                      }).toList();

                if (filtered.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.badge_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            items.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna nafasi'
                                      : 'No positions found')
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
                                            _positionsSearchProvider.notifier,
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
                      return _PositionCard(
                        item: item,
                        index: index,
                        onEdit: () => _openForm(context, ref, item: item),
                        onDelete: () => _deleteItem(context, ref, item),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
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
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Nafasi' : 'Delete Position'),
        content: Text(
          isSwahili ? 'Futa ${item['name']}?' : 'Delete ${item['name']}?',
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
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/positions/${item['id']}');
      ref.invalidate(_positionsProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili ? 'Nafasi imefutwa' : 'Position deleted successfully',
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

class _PositionCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final bool isSwahili;
  final bool isDarkMode;

  const _PositionCard({
    required this.item,
    required this.index,
    required this.onEdit,
    required this.onDelete,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final status = (item['status'] ?? 'ACTIVE').toString();
    final abbreviation = item['abbreviation']?.toString() ?? '';
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 12,
        ),
        leading: Container(
          width: 42,
          height: 42,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            abbreviation.isNotEmpty ? abbreviation : '${index + 1}',
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
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
              Text(
                isSwahili
                    ? 'Ripoti kwa: ${item['report_to_name'] ?? 'Haiwekezwi'}'
                    : 'Reports to: ${item['report_to_name'] ?? 'Not assigned'}',
                style: TextStyle(fontSize: 13, color: Colors.grey[600]),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
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
              onEdit();
            } else if (value == 'delete') {
              onDelete();
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
      ),
    );
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
    final isSwahili = ref.watch(isSwahiliProvider);
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
                  _isEdit
                      ? (isSwahili ? 'Hariri Nafasi' : 'Edit Position')
                      : (isSwahili
                            ? 'Unda Nafasi Mpya'
                            : 'Create New Position'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina la Nafasi' : 'Position Name',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? (isSwahili ? 'Jina linahitajika' : 'Name is required')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _abbreviationController,
                  textCapitalization: TextCapitalization.characters,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kifupi' : 'Abbreviation',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? (isSwahili
                            ? 'Kifupi kinahitajika'
                            : 'Abbreviation is required')
                      : null,
                ),
                const SizedBox(height: 16),
                referenceAsync.when(
                  loading: () => const LinearProgressIndicator(),
                  error: (_, __) => const SizedBox.shrink(),
                  data: (reference) {
                    final rawPositions =
                        reference['positions'] as List? ?? const [];
                    final positions = rawPositions
                        .whereType<Map>()
                        .map((item) => Map<String, dynamic>.from(item))
                        .where((item) => item['id'] != widget.item?['id'])
                        .toList();

                    return DropdownButtonFormField<int?>(
                      value: _reportToId,
                      decoration: InputDecoration(
                        labelText: isSwahili ? 'Ripoti Kwa' : 'Reports To',
                        border: const OutlineInputBorder(),
                      ),
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(
                            isSwahili
                                ? 'Hakuna mstari wa ripoti'
                                : 'No reporting line',
                          ),
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
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Hali' : 'Status',
                    border: const OutlineInputBorder(),
                  ),
                  items: [
                    DropdownMenuItem(
                      value: 'ACTIVE',
                      child: Text(isSwahili ? 'INAENDELEA' : 'ACTIVE'),
                    ),
                    DropdownMenuItem(
                      value: 'INACTIVE',
                      child: Text(isSwahili ? 'HAIUNA KAZI' : 'INACTIVE'),
                    ),
                  ],
                  onChanged: (value) =>
                      setState(() => _status = value ?? 'ACTIVE'),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  maxLines: 4,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Maelezo' : 'Description',
                    border: const OutlineInputBorder(),
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
                          ? (isSwahili ? 'Inahifadhi...' : 'Saving...')
                          : (_isEdit
                                ? (isSwahili
                                      ? 'Sasisha Nafasi'
                                      : 'Update Position')
                                : (isSwahili
                                      ? 'Hifadhi Nafasi'
                                      : 'Save Position')),
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
