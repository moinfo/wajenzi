// ignore_for_file: use_build_context_synchronously
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/stats';
final _searchProvider = StateProvider<String>((ref) => '');

class StatsAdminScreen extends ConsumerWidget {
  const StatsAdminScreen({super.key});

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
        emptyIcon: Icons.analytics_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      error: (e, _) => LandingAdminAsyncSliver(
        isLoading: false,
        error: e,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.analytics_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      data: (items) {
        final filtered = search.isEmpty
            ? items
            : items.where((it) {
                final l = (it['label'] ?? '').toString().toLowerCase();
                final v = (it['value'] ?? '').toString().toLowerCase();
                return l.contains(search) || v.contains(search);
              }).toList();
        if (filtered.isEmpty) {
          return LandingAdminAsyncSliver(
            isLoading: false,
            error: null,
            isEmpty: true,
            emptyTextEn: items.isEmpty
                ? 'No hero stats yet'
                : 'No matching stats',
            emptyTextSw: items.isEmpty
                ? 'Hakuna takwimu bado'
                : 'Hakuna takwimu zinazolingana',
            emptyIcon: Icons.analytics_outlined,
            onRetry: refresh,
            isSwahili: isSwahili,
          );
        }
        return SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
          sliver: SliverList.builder(
            itemCount: filtered.length,
            itemBuilder: (context, index) => _StatCard(
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
      titleEn: 'Hero Stats',
      titleSw: 'Takwimu',
      searchHintEn: 'Search stats...',
      searchHintSw: 'Tafuta takwimu...',
      fabTooltipEn: 'Add Stat',
      fabTooltipSw: 'Ongeza Takwimu',
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
        heightFactor: 0.7,
        child: _StatFormSheet(item: item),
      ),
    );
    if (result == true) {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
  }
}

class _StatCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _StatCard({
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
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 4,
        ),
        leading: Container(
          width: 56,
          alignment: Alignment.center,
          padding: const EdgeInsets.symmetric(vertical: 6),
          decoration: BoxDecoration(
            color: AppColors.brandYellow.withValues(alpha: 0.18),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            item['value']?.toString() ?? '-',
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontWeight: FontWeight.w900,
              fontSize: 14,
              color: AppColors.brandBlue,
            ),
          ),
        ),
        title: Text(
          item['label']?.toString() ?? '-',
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 4),
          child: publishedChip(published, isSwahili),
        ),
        trailing: PopupMenuButton<String>(
          onSelected: (value) async {
            final isSw = ref.read(isSwahiliProvider);
            if (value == 'edit') {
              await StatsAdminScreen._openForm(context, ref, item: item);
            } else if (value == 'delete') {
              final confirmed = await confirmLandingDelete(
                context: context,
                isSwahili: isSw,
                titleEn: 'Delete Stat',
                titleSw: 'Futa Takwimu',
                messageEn: 'Delete "${item['label']}"?',
                messageSw: 'Futa "${item['label']}"?',
              );
              if (!confirmed) return;
              try {
                await ref
                    .read(apiClientProvider)
                    .delete('$_endpoint/${item['id']}');
                onChanged();
                showLandingSnack(
                  context,
                  isSw ? 'Takwimu imefutwa' : 'Stat deleted',
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
      ),
    );
  }
}

class _StatFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const _StatFormSheet({this.item});

  @override
  ConsumerState<_StatFormSheet> createState() => _StatFormSheetState();
}

class _StatFormSheetState extends ConsumerState<_StatFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _value;
  late final TextEditingController _label;
  late final TextEditingController _sortOrder;
  late bool _published;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _value = TextEditingController(
      text: widget.item?['value']?.toString() ?? '',
    );
    _label = TextEditingController(
      text: widget.item?['label']?.toString() ?? '',
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
    _value.dispose();
    _label.dispose();
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
                      ? (isSwahili ? 'Hariri Takwimu' : 'Edit Hero Stat')
                      : (isSwahili ? 'Ongeza Takwimu' : 'Add Hero Stat'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _value,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Thamani (mfano "50+")'
                        : 'Value (e.g. "50+")',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? (isSwahili ? 'Thamani inahitajika' : 'Value required')
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _label,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Lebo (mfano "Miradi")'
                        : 'Label (e.g. "Projects")',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? (isSwahili ? 'Lebo inahitajika' : 'Label required')
                      : null,
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
      'value': _value.text.trim(),
      'label': _label.text.trim(),
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
