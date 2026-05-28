// ignore_for_file: use_build_context_synchronously
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/values';
final _searchProvider = StateProvider<String>((ref) => '');

class ValuesAdminScreen extends ConsumerWidget {
  const ValuesAdminScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final key = landingKey(_endpoint, isSwahili);
    final asyncData = ref.watch(landingAdminListProvider(key));
    final search = ref.watch(_searchProvider).trim().toLowerCase();

    void refresh() => ref.invalidate(landingAdminListProvider(key));

    final body = asyncData.when(
      loading: () => LandingAdminAsyncSliver(
        isLoading: true,
        error: null,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.star_outline,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      error: (e, _) => LandingAdminAsyncSliver(
        isLoading: false,
        error: e,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.star_outline,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      data: (items) {
        final filtered = search.isEmpty
            ? items
            : items.where((it) {
                final t = (it['title'] ?? '').toString().toLowerCase();
                final d = (it['description'] ?? '').toString().toLowerCase();
                return t.contains(search) || d.contains(search);
              }).toList();
        if (filtered.isEmpty) {
          return LandingAdminAsyncSliver(
            isLoading: false,
            error: null,
            isEmpty: true,
            emptyTextEn: items.isEmpty
                ? 'No core values yet'
                : 'No matching values',
            emptyTextSw: items.isEmpty
                ? 'Hakuna maadili bado'
                : 'Hakuna maadili yanayolingana',
            emptyIcon: Icons.star_outline,
            onRetry: refresh,
            isSwahili: isSwahili,
          );
        }
        return SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
          sliver: SliverList.builder(
            itemCount: filtered.length,
            itemBuilder: (context, index) => _ValueCard(
              item: filtered[index],
              isDarkMode: isDarkMode,
              isSwahili: isSwahili,
              onChanged: refresh,
            ),
          ),
        );
      },
    );

    return LandingAdminScaffold(
      titleEn: 'Core Values',
      titleSw: 'Maadili Yetu',
      searchHintEn: 'Search values...',
      searchHintSw: 'Tafuta maadili...',
      fabTooltipEn: 'Add Value',
      fabTooltipSw: 'Ongeza Thamani',
      searchProvider: _searchProvider,
      refreshProviders: [landingAdminListProvider(key)],
      onAdd: () => _openForm(context, ref),
      body: body,
    );
  }

  static Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.75,
        child: _ValueFormSheet(item: item),
      ),
    );
    if (result == true) {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
  }
}

class _ValueCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _ValueCard({
    required this.item,
    required this.isDarkMode,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final published = item['is_published'] == true;
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: AppColors.brandGreen.withValues(alpha: 0.14),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(
                Icons.star_rounded,
                color: AppColors.brandGreen,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          item['title']?.toString() ?? '-',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      publishedChip(published, isSwahili),
                    ],
                  ),
                  if ((item['description']?.toString().isNotEmpty ?? false))
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        item['description'].toString(),
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontSize: 13,
                        ),
                      ),
                    ),
                ],
              ),
            ),
            PopupMenuButton<String>(
              onSelected: (value) async {
                final isSw = ref.read(isSwahiliProvider);
                if (value == 'edit') {
                  await ValuesAdminScreen._openForm(context, ref, item: item);
                } else if (value == 'delete') {
                  final confirmed = await confirmLandingDelete(
                    context: context,
                    isSwahili: isSw,
                    titleEn: 'Delete Value',
                    titleSw: 'Futa Thamani',
                    messageEn: 'Delete "${item['title']}"?',
                    messageSw: 'Futa "${item['title']}"?',
                  );
                  if (!confirmed) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('$_endpoint/${item['id']}');
                    onChanged();
                    showLandingSnack(
                      context,
                      isSw ? 'Thamani imefutwa' : 'Value deleted',
                    );
                  } catch (e) {
                    showLandingSnack(
                      context,
                      landingAdminErrorMessage(e),
                      error: true,
                    );
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
          ],
        ),
      ),
    );
  }
}

class _ValueFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const _ValueFormSheet({this.item});

  @override
  ConsumerState<_ValueFormSheet> createState() => _ValueFormSheetState();
}

class _ValueFormSheetState extends ConsumerState<_ValueFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _description;
  late final TextEditingController _sortOrder;
  late bool _published;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _title = TextEditingController(
      text: widget.item?['title']?.toString() ?? '',
    );
    _description = TextEditingController(
      text: widget.item?['description']?.toString() ?? '',
    );
    _sortOrder = TextEditingController(
      text: (widget.item?['sort_order'] ?? 0).toString(),
    );
    _published = widget.item == null
        ? true
        : (widget.item!['is_published'] == true);
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _sortOrder.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1F1F2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
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
                      ? (isSwahili ? 'Hariri Thamani' : 'Edit Core Value')
                      : (isSwahili ? 'Ongeza Thamani' : 'Add Core Value'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _title,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kichwa' : 'Title',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? (isSwahili ? 'Kichwa kinahitajika' : 'Title required')
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _description,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Maelezo' : 'Description',
                    border: const OutlineInputBorder(),
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _sortOrder,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Mpangilio' : 'Sort order',
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                ),
                SwitchListTile(
                  title: Text(isSwahili ? 'Imechapishwa' : 'Published'),
                  value: _published,
                  onChanged: (v) => setState(() => _published = v),
                  contentPadding: EdgeInsets.zero,
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? (isSwahili ? 'Inahifadhi...' : 'Saving...')
                          : (_isEdit
                                ? (isSwahili ? 'Sasisha' : 'Update')
                                : (isSwahili ? 'Hifadhi' : 'Save')),
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
    final isSw = ref.read(isSwahiliProvider);

    final payload = <String, dynamic>{
      'title': _title.text.trim(),
      'description': _description.text.trim(),
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_published': _published,
      'lang': isSw ? 'sw' : 'en',
    };
    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('$_endpoint/${widget.item!['id']}', data: payload);
      } else {
        await api.post(_endpoint, data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _submitting = false);
      showLandingSnack(context, landingAdminErrorMessage(e), error: true);
    }
  }
}
