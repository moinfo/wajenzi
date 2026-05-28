// ignore_for_file: use_build_context_synchronously
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/awards';
final _searchProvider = StateProvider<String>((ref) => '');

class AwardsAdminScreen extends ConsumerWidget {
  const AwardsAdminScreen({super.key});

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
        emptyIcon: Icons.emoji_events_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      error: (e, _) => LandingAdminAsyncSliver(
        isLoading: false,
        error: e,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.emoji_events_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      data: (items) {
        final filtered = search.isEmpty
            ? items
            : items.where((it) {
                final t = (it['title'] ?? '').toString().toLowerCase();
                final org = (it['organization'] ?? '').toString().toLowerCase();
                final year = (it['year'] ?? '').toString().toLowerCase();
                return t.contains(search) ||
                    org.contains(search) ||
                    year.contains(search);
              }).toList();
        if (filtered.isEmpty) {
          return LandingAdminAsyncSliver(
            isLoading: false,
            error: null,
            isEmpty: true,
            emptyTextEn: items.isEmpty
                ? 'No awards yet'
                : 'No matching awards',
            emptyTextSw: items.isEmpty
                ? 'Hakuna tuzo bado'
                : 'Hakuna tuzo zinazolingana',
            emptyIcon: Icons.emoji_events_outlined,
            onRetry: refresh,
            isSwahili: isSwahili,
          );
        }
        return SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
          sliver: SliverList.builder(
            itemCount: filtered.length,
            itemBuilder: (context, index) {
              return _AwardCard(
                item: filtered[index],
                isDarkMode: isDarkMode,
                isSwahili: isSwahili,
                onChanged: refresh,
              );
            },
          ),
        );
      },
    );

    return LandingAdminScaffold(
      titleEn: 'Awards',
      titleSw: 'Tuzo',
      searchHintEn: 'Search awards...',
      searchHintSw: 'Tafuta tuzo...',
      fabTooltipEn: 'Add Award',
      fabTooltipSw: 'Ongeza Tuzo',
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
        heightFactor: 0.92,
        child: _AwardFormSheet(item: item),
      ),
    );
    if (result == true) {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
  }
}

class _AwardCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _AwardCard({
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
              fallbackIcon: Icons.emoji_events_outlined,
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
                  const SizedBox(height: 4),
                  if (item['year'] != null &&
                      item['year'].toString().isNotEmpty)
                    Text(
                      item['year'].toString(),
                      style: const TextStyle(
                        color: AppColors.brandYellow,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  if (item['organization'] != null &&
                      item['organization'].toString().isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(
                        item['organization'].toString(),
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  if (item['subtitle'] != null &&
                      item['subtitle'].toString().isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(
                        item['subtitle'].toString(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 13),
                      ),
                    ),
                ],
              ),
            ),
            PopupMenuButton<String>(
              onSelected: (value) async {
                final isSw = ref.read(isSwahiliProvider);
                if (value == 'edit') {
                  await AwardsAdminScreen._openForm(
                    context,
                    ref,
                    item: item,
                  );
                } else if (value == 'delete') {
                  final confirmed = await confirmLandingDelete(
                    context: context,
                    isSwahili: isSw,
                    titleEn: 'Delete Award',
                    titleSw: 'Futa Tuzo',
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
                      isSw ? 'Tuzo imefutwa' : 'Award deleted',
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

class _AwardFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const _AwardFormSheet({this.item});

  @override
  ConsumerState<_AwardFormSheet> createState() => _AwardFormSheetState();
}

class _AwardFormSheetState extends ConsumerState<_AwardFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _year;
  late final TextEditingController _subtitle;
  late final TextEditingController _organization;
  late final TextEditingController _description;
  late final TextEditingController _sortOrder;
  late bool _published;
  File? _pickedImage;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _title = TextEditingController(
      text: widget.item?['title']?.toString() ?? '',
    );
    _year = TextEditingController(
      text: widget.item?['year']?.toString() ?? '',
    );
    _subtitle = TextEditingController(
      text: widget.item?['subtitle']?.toString() ?? '',
    );
    _organization = TextEditingController(
      text: widget.item?['organization']?.toString() ?? '',
    );
    _description = TextEditingController(
      text: widget.item?['description']?.toString() ?? '',
    );
    _sortOrder = TextEditingController(
      text: (widget.item?['sort_order'] ?? 0).toString(),
    );
    _published = widget.item == null ? true : (widget.item!['is_published'] == true);
  }

  @override
  void dispose() {
    _title.dispose();
    _year.dispose();
    _subtitle.dispose();
    _organization.dispose();
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
                      ? (isSwahili ? 'Hariri Tuzo' : 'Edit Award')
                      : (isSwahili ? 'Ongeza Tuzo' : 'Add Award'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if (isSwahili) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Maandishi yatahifadhiwa kwa Kiswahili',
                    style: TextStyle(color: Colors.grey[600], fontSize: 12),
                  ),
                ],
                const SizedBox(height: 16),
                _imageRow(isSwahili),
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
                  controller: _year,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Mwaka' : 'Year',
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _organization,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Shirika' : 'Organization',
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _subtitle,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Maelezo Mafupi' : 'Subtitle',
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _description,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Maelezo' : 'Description',
                    border: const OutlineInputBorder(),
                  ),
                  maxLines: 3,
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
                const SizedBox(height: 12),
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

  Widget _imageRow(bool isSwahili) {
    final currentPath = widget.item?['image']?.toString();
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        if (_pickedImage != null)
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
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
            fallbackIcon: Icons.emoji_events_outlined,
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
                      ? (_isEdit ? 'Badilisha picha' : 'Pakia picha')
                      : (_isEdit ? 'Replace image' : 'Upload image'),
                ),
              ),
              if (!_isEdit)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text(
                    isSwahili ? 'Picha inahitajika' : 'Image is required',
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
    if (!_isEdit && _pickedImage == null) {
      showLandingSnack(
        context,
        ref.read(isSwahiliProvider)
            ? 'Tafadhali pakia picha'
            : 'Please upload an image',
        error: true,
      );
      return;
    }
    setState(() => _submitting = true);

    final isSw = ref.read(isSwahiliProvider);
    final form = <String, dynamic>{
      'title': _title.text.trim(),
      'year': _year.text.trim(),
      'subtitle': _subtitle.text.trim(),
      'organization': _organization.text.trim(),
      'description': _description.text.trim(),
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
