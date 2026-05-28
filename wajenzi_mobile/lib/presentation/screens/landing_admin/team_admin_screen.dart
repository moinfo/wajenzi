// ignore_for_file: use_build_context_synchronously
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/team';
final _searchProvider = StateProvider<String>((ref) => '');

/// Landing CMS admin — Team members.
/// Mirrors the Awards admin (image upload, drag-reorder, publish toggle),
/// but team `name` is a plain string (per controller) while `role` and `bio`
/// are stored as JSON localized maps and edited per-language via `lang`.
class TeamAdminScreen extends ConsumerWidget {
  const TeamAdminScreen({super.key});

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
        emptyIcon: Icons.groups_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      error: (e, _) => LandingAdminAsyncSliver(
        isLoading: false,
        error: e,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.groups_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      data: (items) {
        final filtered = search.isEmpty
            ? items
            : items.where((it) {
                final n = (it['name'] ?? '').toString().toLowerCase();
                final r = (it['role'] ?? '').toString().toLowerCase();
                return n.contains(search) || r.contains(search);
              }).toList();
        if (filtered.isEmpty) {
          return LandingAdminAsyncSliver(
            isLoading: false,
            error: null,
            isEmpty: true,
            emptyTextEn:
                items.isEmpty ? 'No team members yet' : 'No matching members',
            emptyTextSw: items.isEmpty
                ? 'Hakuna wanachama wa timu bado'
                : 'Hakuna wanachama wanaolingana',
            emptyIcon: Icons.groups_outlined,
            onRetry: refresh,
            isSwahili: isSwahili,
          );
        }
        return SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
          sliver: SliverReorderableList(
            itemCount: filtered.length,
            onReorder: (oldIndex, newIndex) =>
                _onReorder(ref, filtered, oldIndex, newIndex),
            itemBuilder: (context, index) {
              final item = filtered[index];
              return ReorderableDelayedDragStartListener(
                key: ValueKey('team-${item['id']}'),
                index: index,
                child: _TeamCard(
                  item: item,
                  isDarkMode: isDarkMode,
                  isSwahili: isSwahili,
                  onChanged: refresh,
                ),
              );
            },
          ),
        );
      },
    );

    return LandingAdminScaffold(
      titleEn: 'Team',
      titleSw: 'Timu',
      searchHintEn: 'Search members...',
      searchHintSw: 'Tafuta wanachama...',
      fabTooltipEn: 'Add Member',
      fabTooltipSw: 'Ongeza Mwanachama',
      searchProvider: _searchProvider,
      refreshProviders: [landingAdminListProvider(key)],
      onAdd: () => _openForm(context, ref),
      body: body,
    );
  }

  static Future<void> _onReorder(
    WidgetRef ref,
    List<Map<String, dynamic>> items,
    int oldIndex,
    int newIndex,
  ) async {
    final reordered = List<Map<String, dynamic>>.from(items);
    if (newIndex > oldIndex) newIndex -= 1;
    final moved = reordered.removeAt(oldIndex);
    reordered.insert(newIndex, moved);
    final payload = <Map<String, dynamic>>[];
    for (var i = 0; i < reordered.length; i++) {
      payload.add({'id': reordered[i]['id'], 'sort_order': i});
    }
    try {
      await ref.read(apiClientProvider).post(
        '$_endpoint/reorder',
        data: {'items': payload},
      );
    } catch (_) {
      // Silent — UI will refresh on next data fetch.
    } finally {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
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
        heightFactor: 0.92,
        child: _TeamFormSheet(item: item),
      ),
    );
    if (result == true) {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
  }
}

class _TeamCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _TeamCard({
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
            LandingThumbnail(
              path: item['image']?.toString(),
              size: 72,
              fallbackIcon: Icons.person_rounded,
              borderRadius: BorderRadius.circular(36),
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
                          item['name']?.toString() ?? '-',
                          maxLines: 1,
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
                  if ((item['role']?.toString().isNotEmpty ?? false))
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        item['role'].toString(),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.brandGreen,
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  if ((item['bio']?.toString().isNotEmpty ?? false))
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        item['bio'].toString(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ),
                  Padding(
                    padding: const EdgeInsets.only(top: 6),
                    child: Row(
                      children: [
                        Icon(
                          Icons.drag_indicator_rounded,
                          size: 16,
                          color: Colors.grey[500],
                        ),
                        const SizedBox(width: 4),
                        Text(
                          isSwahili
                              ? 'Mpangilio ${item['sort_order'] ?? 0}'
                              : 'Order ${item['sort_order'] ?? 0}',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            PopupMenuButton<String>(
              onSelected: (value) async {
                final isSw = ref.read(isSwahiliProvider);
                if (value == 'edit') {
                  await TeamAdminScreen._openForm(context, ref, item: item);
                } else if (value == 'delete') {
                  final confirmed = await confirmLandingDelete(
                    context: context,
                    isSwahili: isSw,
                    titleEn: 'Delete Team Member',
                    titleSw: 'Futa Mwanachama',
                    messageEn: 'Delete "${item['name']}"?',
                    messageSw: 'Futa "${item['name']}"?',
                  );
                  if (!confirmed) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('$_endpoint/${item['id']}');
                    onChanged();
                    showLandingSnack(
                      context,
                      isSw ? 'Mwanachama amefutwa' : 'Member deleted',
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

class _TeamFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const _TeamFormSheet({this.item});

  @override
  ConsumerState<_TeamFormSheet> createState() => _TeamFormSheetState();
}

class _TeamFormSheetState extends ConsumerState<_TeamFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _role;
  late final TextEditingController _bio;
  late final TextEditingController _sortOrder;
  late bool _published;
  File? _pickedImage;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
    _role = TextEditingController(
      text: widget.item?['role']?.toString() ?? '',
    );
    _bio = TextEditingController(
      text: widget.item?['bio']?.toString() ?? '',
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
    _name.dispose();
    _role.dispose();
    _bio.dispose();
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
                      ? (isSwahili ? 'Hariri Mwanachama' : 'Edit Team Member')
                      : (isSwahili ? 'Ongeza Mwanachama' : 'Add Team Member'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if (isSwahili) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Cheo na wasifu vitahifadhiwa kwa Kiswahili. Jina ni sawa kwa lugha zote.',
                    style: TextStyle(color: Colors.grey[600], fontSize: 12),
                  ),
                ],
                const SizedBox(height: 16),
                _imageRow(isSwahili),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _name,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina kamili' : 'Full name',
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? (isSwahili ? 'Jina linahitajika' : 'Name required')
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _role,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Cheo / nafasi' : 'Role / title',
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _bio,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Wasifu mfupi' : 'Short bio',
                    border: const OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                  maxLines: 4,
                  maxLength: 2000,
                ),
                const SizedBox(height: 4),
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
                const SizedBox(height: 12),
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

  Widget _imageRow(bool isSwahili) {
    final currentPath = widget.item?['image']?.toString();
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        if (_pickedImage != null)
          ClipRRect(
            borderRadius: BorderRadius.circular(42),
            child: Image.file(
              _pickedImage!,
              width: 84,
              height: 84,
              fit: BoxFit.cover,
            ),
          )
        else
          LandingThumbnail(
            path: currentPath,
            size: 84,
            fallbackIcon: Icons.person_rounded,
            borderRadius: BorderRadius.circular(42),
          ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ElevatedButton.icon(
                onPressed: () async {
                  final res = await pickLandingImage(
                    context,
                    isSwahili: isSwahili,
                  );
                  if (res == null) return;
                  setState(() => _pickedImage = res.file);
                },
                icon: const Icon(Icons.upload_rounded, size: 18),
                label: Text(
                  isSwahili
                      ? (currentPath != null
                            ? 'Badilisha picha'
                            : 'Pakia picha')
                      : (currentPath != null
                            ? 'Replace photo'
                            : 'Upload photo'),
                ),
              ),
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  isSwahili
                      ? 'Hiari (PNG/JPG, hadi 8MB)'
                      : 'Optional (PNG/JPG, max 8MB)',
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 11,
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    final isSw = ref.read(isSwahiliProvider);

    final form = <String, dynamic>{
      'name': _name.text.trim(),
      'role': _role.text.trim(),
      'bio': _bio.text.trim(),
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_published': _published ? 1 : 0,
      'lang': isSw ? 'sw' : 'en',
    };
    if (_pickedImage != null) {
      form['image'] = await MultipartFile.fromFile(_pickedImage!.path);
    }

    try {
      final api = ref.read(apiClientProvider);
      final body = FormData.fromMap(form);
      if (_isEdit) {
        await api.post('$_endpoint/${widget.item!['id']}', data: body);
      } else {
        await api.post(_endpoint, data: body);
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
