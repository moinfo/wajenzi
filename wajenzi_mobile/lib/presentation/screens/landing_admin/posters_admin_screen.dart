// ignore_for_file: use_build_context_synchronously
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/posters';
final _searchProvider = StateProvider<String>((ref) => '');

class PostersAdminScreen extends ConsumerWidget {
  const PostersAdminScreen({super.key});

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
        emptyIcon: Icons.image_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      error: (e, _) => LandingAdminAsyncSliver(
        isLoading: false,
        error: e,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.image_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      data: (items) {
        final filtered = search.isEmpty
            ? items
            : items.where((it) {
                final t = (it['title'] ?? '').toString().toLowerCase();
                final s = (it['subtitle'] ?? '').toString().toLowerCase();
                return t.contains(search) || s.contains(search);
              }).toList();
        if (filtered.isEmpty) {
          return LandingAdminAsyncSliver(
            isLoading: false,
            error: null,
            isEmpty: true,
            emptyTextEn: items.isEmpty
                ? 'No home banners yet'
                : 'No matching banners',
            emptyTextSw: items.isEmpty
                ? 'Hakuna mabango bado'
                : 'Hakuna mabango yanayolingana',
            emptyIcon: Icons.image_outlined,
            onRetry: refresh,
            isSwahili: isSwahili,
          );
        }
        return SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
          sliver: SliverList.builder(
            itemCount: filtered.length,
            itemBuilder: (context, index) {
              return _PosterCard(
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
      titleEn: 'Home Banners',
      titleSw: 'Mabango ya Mwanzo',
      searchHintEn: 'Search banners...',
      searchHintSw: 'Tafuta mabango...',
      fabTooltipEn: 'Add Banner',
      fabTooltipSw: 'Ongeza Bango',
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
        child: _PosterFormSheet(item: item),
      ),
    );
    if (result == true) {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
  }
}

class _PosterCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _PosterCard({
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
              fallbackIcon: Icons.image_outlined,
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
                          (item['title']?.toString().trim().isNotEmpty ?? false)
                              ? item['title'].toString()
                              : (isSwahili ? 'Bango' : 'Banner'),
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
                  if ((item['subtitle']?.toString().isNotEmpty ?? false))
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        item['subtitle'].toString(),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  if ((item['link_url']?.toString().isNotEmpty ?? false))
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Row(
                        children: [
                          const Icon(Icons.link, size: 14, color: AppColors.brandGreen),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              item['link_url'].toString(),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 11,
                                color: AppColors.brandGreen,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  if ((item['youtube_url']?.toString().isNotEmpty ?? false))
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Row(
                        children: [
                          const Icon(Icons.play_circle, size: 14, color: AppColors.error),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              item['youtube_url'].toString(),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 11,
                                color: AppColors.error,
                              ),
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
                  await PostersAdminScreen._openForm(
                    context,
                    ref,
                    item: item,
                  );
                } else if (value == 'delete') {
                  final confirmed = await confirmLandingDelete(
                    context: context,
                    isSwahili: isSw,
                    titleEn: 'Delete Banner',
                    titleSw: 'Futa Bango',
                    messageEn: 'Delete this banner?',
                    messageSw: 'Futa bango hili?',
                  );
                  if (!confirmed) return;
                  try {
                    await ref
                        .read(apiClientProvider)
                        .delete('$_endpoint/${item['id']}');
                    onChanged();
                    showLandingSnack(
                      context,
                      isSw ? 'Bango limefutwa' : 'Banner deleted',
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

class _PosterFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const _PosterFormSheet({this.item});

  @override
  ConsumerState<_PosterFormSheet> createState() => _PosterFormSheetState();
}

class _PosterFormSheetState extends ConsumerState<_PosterFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _subtitle;
  late final TextEditingController _linkUrl;
  late final TextEditingController _youtubeUrl;
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
    _subtitle = TextEditingController(
      text: widget.item?['subtitle']?.toString() ?? '',
    );
    _linkUrl = TextEditingController(
      text: widget.item?['link_url']?.toString() ?? '',
    );
    _youtubeUrl = TextEditingController(
      text: widget.item?['youtube_url']?.toString() ?? '',
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
    _subtitle.dispose();
    _linkUrl.dispose();
    _youtubeUrl.dispose();
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
                      ? (isSwahili ? 'Hariri Bango' : 'Edit Banner')
                      : (isSwahili ? 'Ongeza Bango' : 'Add Banner'),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 16),
                _imageRow(isSwahili),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _title,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kichwa' : 'Title',
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
                  controller: _linkUrl,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Kiungo (URL)' : 'Link URL',
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.url,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _youtubeUrl,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'YouTube URL' : 'YouTube URL',
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.url,
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
            fallbackIcon: Icons.image_outlined,
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
                            ? 'Replace image'
                            : 'Upload image'),
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
      'subtitle': _subtitle.text.trim(),
      'link_url': _linkUrl.text.trim(),
      'youtube_url': _youtubeUrl.text.trim(),
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
