// ignore_for_file: use_build_context_synchronously
import 'dart:io';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/config/app_config.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/portfolio';
final _searchProvider = StateProvider<String>((ref) => '');

/// Landing CMS admin — Portfolio (projects) screen.
///
/// Most complex of the landing admin screens:
///   - Multi-image upload via image_picker (allows several images per project).
///   - Primary-image flag with inline "set primary" + "delete image" actions
///     that hit dedicated endpoints.
///   - Drag-reorder of projects (mirrors Awards).
///   - English/Swahili-aware editing via `lang` query/body param.
class PortfolioAdminScreen extends ConsumerWidget {
  const PortfolioAdminScreen({super.key});

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
        emptyIcon: Icons.architecture_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      error: (e, _) => LandingAdminAsyncSliver(
        isLoading: false,
        error: e,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.architecture_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      data: (items) {
        final filtered = search.isEmpty
            ? items
            : items.where((it) {
                final t = (it['title'] ?? '').toString().toLowerCase();
                final c = (it['category'] ?? '').toString().toLowerCase();
                final d = (it['description'] ?? '').toString().toLowerCase();
                return t.contains(search) ||
                    c.contains(search) ||
                    d.contains(search);
              }).toList();
        if (filtered.isEmpty) {
          return LandingAdminAsyncSliver(
            isLoading: false,
            error: null,
            isEmpty: true,
            emptyTextEn: items.isEmpty
                ? 'No portfolio projects yet'
                : 'No matching projects',
            emptyTextSw: items.isEmpty
                ? 'Hakuna miradi bado'
                : 'Hakuna miradi inayolingana',
            emptyIcon: Icons.architecture_outlined,
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
                key: ValueKey('portfolio-${item['id']}'),
                index: index,
                child: _PortfolioCard(
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
      titleEn: 'Portfolio',
      titleSw: 'Miradi',
      searchHintEn: 'Search projects...',
      searchHintSw: 'Tafuta miradi...',
      fabTooltipEn: 'Add Project',
      fabTooltipSw: 'Ongeza Mradi',
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
      // Silent — UI will refresh.
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
        heightFactor: 0.95,
        child: _PortfolioFormSheet(item: item),
      ),
    );
    if (result == true) {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
  }
}

class _PortfolioCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _PortfolioCard({
    required this.item,
    required this.isDarkMode,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final published = item['is_published'] == true;
    final featured = item['is_featured'] == true;
    final images = (item['images'] as List?)
            ?.whereType<Map>()
            .map((m) => Map<String, dynamic>.from(m))
            .toList() ??
        const <Map<String, dynamic>>[];
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                LandingThumbnail(
                  path: item['image']?.toString(),
                  size: 84,
                  fallbackIcon: Icons.architecture_outlined,
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
                      if (item['category'] != null &&
                          item['category'].toString().isNotEmpty)
                        Text(
                          item['category'].toString(),
                          style: const TextStyle(
                            color: AppColors.brandGreen,
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                          ),
                        ),
                      if (item['description'] != null &&
                          item['description'].toString().isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            item['description'].toString(),
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
                        child: Wrap(
                          spacing: 6,
                          runSpacing: 4,
                          children: [
                            _miniChip(
                              icon: Icons.image_outlined,
                              label: isSwahili
                                  ? '${images.length} picha'
                                  : '${images.length} images',
                            ),
                            if (featured)
                              _miniChip(
                                icon: Icons.star_rounded,
                                label: isSwahili ? 'Maalum' : 'Featured',
                                color: AppColors.brandYellow,
                              ),
                            if (item['likes_count'] != null &&
                                (item['likes_count'] as num) > 0)
                              _miniChip(
                                icon: Icons.favorite,
                                label: '${item['likes_count']}',
                                color: AppColors.error,
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
                      await PortfolioAdminScreen._openForm(
                        context,
                        ref,
                        item: item,
                      );
                    } else if (value == 'delete') {
                      final confirmed = await confirmLandingDelete(
                        context: context,
                        isSwahili: isSw,
                        titleEn: 'Delete Project',
                        titleSw: 'Futa Mradi',
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
                          isSw ? 'Mradi umefutwa' : 'Project deleted',
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
            if (images.length > 1) ...[
              const SizedBox(height: 10),
              SizedBox(
                height: 64,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: images.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 6),
                  itemBuilder: (context, idx) {
                    final img = images[idx];
                    return Stack(
                      children: [
                        LandingThumbnail(
                          path: img['file']?.toString(),
                          size: 64,
                          fallbackIcon: Icons.image_outlined,
                        ),
                        if (img['is_primary'] == true)
                          Positioned(
                            left: 4,
                            top: 4,
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 4,
                                vertical: 1,
                              ),
                              decoration: BoxDecoration(
                                color: AppColors.brandYellow,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: const Icon(
                                Icons.star_rounded,
                                size: 10,
                                color: AppColors.brandBlue,
                              ),
                            ),
                          ),
                      ],
                    );
                  },
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _miniChip({
    required IconData icon,
    required String label,
    Color color = AppColors.brandBlue,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: color),
          const SizedBox(width: 3),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _PortfolioFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const _PortfolioFormSheet({this.item});

  @override
  ConsumerState<_PortfolioFormSheet> createState() =>
      _PortfolioFormSheetState();
}

class _PortfolioFormSheetState extends ConsumerState<_PortfolioFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _category;
  late final TextEditingController _description;
  late final TextEditingController _priceTzs;
  late final TextEditingController _priceUsd;
  late final TextEditingController _youtubeUrl;
  late final TextEditingController _model3dUrl;
  late final TextEditingController _sortOrder;
  late bool _published;
  late bool _featured;
  final List<File> _pickedImages = [];
  // Existing images (only present in edit mode), kept locally so per-image
  // delete/setPrimary actions update the UI immediately.
  List<Map<String, dynamic>> _existingImages = [];
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    final item = widget.item;
    _title = TextEditingController(text: item?['title']?.toString() ?? '');
    _category = TextEditingController(
      text: item?['category']?.toString() ?? '',
    );
    _description = TextEditingController(
      text: item?['description']?.toString() ?? '',
    );
    _priceTzs = TextEditingController(
      text: item?['price_tzs']?.toString() ?? '',
    );
    _priceUsd = TextEditingController(
      text: item?['price_usd']?.toString() ?? '',
    );
    _youtubeUrl = TextEditingController(
      text: item?['youtube_url']?.toString() ?? '',
    );
    _model3dUrl = TextEditingController(
      text: item?['model_3d_url']?.toString() ?? '',
    );
    _sortOrder = TextEditingController(
      text: (item?['sort_order'] ?? 0).toString(),
    );
    _published = item == null ? true : (item['is_published'] == true);
    _featured = item != null && item['is_featured'] == true;
    _existingImages = ((item?['images'] as List?) ?? const [])
        .whereType<Map>()
        .map((m) => Map<String, dynamic>.from(m))
        .toList();
  }

  @override
  void dispose() {
    _title.dispose();
    _category.dispose();
    _description.dispose();
    _priceTzs.dispose();
    _priceUsd.dispose();
    _youtubeUrl.dispose();
    _model3dUrl.dispose();
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
                      ? (isSwahili ? 'Hariri Mradi' : 'Edit Project')
                      : (isSwahili ? 'Ongeza Mradi' : 'Add Project'),
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
                _imageManager(isSwahili),
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
                  controller: _category,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Kategoria / mahali'
                        : 'Category / location',
                    border: const OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _description,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Maelezo' : 'Description',
                    border: const OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _priceTzs,
                        decoration: InputDecoration(
                          labelText: isSwahili ? 'Bei (TZS)' : 'Price (TZS)',
                          border: const OutlineInputBorder(),
                        ),
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: TextFormField(
                        controller: _priceUsd,
                        decoration: InputDecoration(
                          labelText: isSwahili ? 'Bei (USD)' : 'Price (USD)',
                          border: const OutlineInputBorder(),
                        ),
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                      ),
                    ),
                  ],
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
                  controller: _model3dUrl,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Kiungo cha mtindo 3D'
                        : '3D model URL',
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
                  title: Text(isSwahili ? 'Maalum (featured)' : 'Featured'),
                  value: _featured,
                  onChanged: (v) => setState(() => _featured = v),
                  contentPadding: EdgeInsets.zero,
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

  Widget _imageManager(bool isSwahili) {
    final totalCount = _existingImages.length + _pickedImages.length;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  isSwahili ? 'Picha ($totalCount)' : 'Images ($totalCount)',
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 14,
                  ),
                ),
              ),
              TextButton.icon(
                onPressed: _pickMoreImages,
                icon: const Icon(Icons.add_photo_alternate_rounded, size: 18),
                label: Text(isSwahili ? 'Ongeza' : 'Add'),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (totalCount == 0)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Center(
                child: Text(
                  isSwahili
                      ? 'Hakuna picha bado. Bonyeza "Ongeza" kuongeza.'
                      : 'No images yet. Tap "Add" to pick some.',
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
              ),
            )
          else
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (var i = 0; i < _existingImages.length; i++)
                  _existingImageTile(_existingImages[i], i, isSwahili),
                for (var i = 0; i < _pickedImages.length; i++)
                  _pickedImageTile(_pickedImages[i], i, isSwahili),
              ],
            ),
          if (!_isEdit && _pickedImages.isEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                isSwahili
                    ? 'Picha angalau moja inahitajika'
                    : 'At least one image is required',
                style: const TextStyle(
                  color: AppColors.error,
                  fontSize: 11,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _existingImageTile(
    Map<String, dynamic> img,
    int index,
    bool isSwahili,
  ) {
    final isPrimary = img['is_primary'] == true;
    final url = AppConfig.resolvePortalMediaUrl(img['file']?.toString());
    return SizedBox(
      width: 92,
      child: Column(
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: url == null
                    ? Container(
                        width: 92,
                        height: 92,
                        color: Colors.grey.shade200,
                        child: const Icon(Icons.image_outlined),
                      )
                    : CachedNetworkImage(
                        imageUrl: url,
                        width: 92,
                        height: 92,
                        fit: BoxFit.cover,
                        placeholder: (_, _) => Container(
                          width: 92,
                          height: 92,
                          color: Colors.grey.shade200,
                        ),
                        errorWidget: (_, _, _) => Container(
                          width: 92,
                          height: 92,
                          color: Colors.grey.shade200,
                          child: const Icon(Icons.broken_image_outlined),
                        ),
                      ),
              ),
              if (isPrimary)
                Positioned(
                  left: 4,
                  top: 4,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 5,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.brandYellow,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.star_rounded,
                          size: 10,
                          color: AppColors.brandBlue,
                        ),
                        SizedBox(width: 2),
                        Text(
                          'PRIMARY',
                          style: TextStyle(
                            fontSize: 8,
                            fontWeight: FontWeight.w900,
                            color: AppColors.brandBlue,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              if (!isPrimary)
                IconButton(
                  onPressed: () => _setExistingPrimary(img['id'] as int, index),
                  icon: const Icon(Icons.star_outline_rounded),
                  iconSize: 18,
                  visualDensity: VisualDensity.compact,
                  tooltip: isSwahili ? 'Weka kuu' : 'Set primary',
                  color: AppColors.brandBlue,
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(
                    minWidth: 32,
                    minHeight: 32,
                  ),
                ),
              IconButton(
                onPressed: () =>
                    _deleteExistingImage(img['id'] as int, index, isSwahili),
                icon: const Icon(Icons.delete_outline_rounded),
                iconSize: 18,
                visualDensity: VisualDensity.compact,
                tooltip: isSwahili ? 'Futa' : 'Delete',
                color: AppColors.error,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(
                  minWidth: 32,
                  minHeight: 32,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _pickedImageTile(File file, int index, bool isSwahili) {
    return SizedBox(
      width: 92,
      child: Column(
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.file(
                  file,
                  width: 92,
                  height: 92,
                  fit: BoxFit.cover,
                ),
              ),
              Positioned(
                left: 4,
                top: 4,
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 5,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.brandGreen,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text(
                    'NEW',
                    style: TextStyle(
                      fontSize: 8,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          IconButton(
            onPressed: () => setState(() => _pickedImages.removeAt(index)),
            icon: const Icon(Icons.close_rounded),
            iconSize: 18,
            visualDensity: VisualDensity.compact,
            tooltip: isSwahili ? 'Ondoa' : 'Remove',
            color: AppColors.error,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
          ),
        ],
      ),
    );
  }

  Future<void> _pickMoreImages() async {
    try {
      final picker = ImagePicker();
      final picked = await picker.pickMultiImage(imageQuality: 85);
      if (picked.isEmpty) return;
      setState(() {
        _pickedImages.addAll(picked.map((x) => File(x.path)));
      });
    } catch (e) {
      if (!mounted) return;
      showLandingSnack(context, landingAdminErrorMessage(e), error: true);
    }
  }

  Future<void> _setExistingPrimary(int imageId, int index) async {
    try {
      await ref
          .read(apiClientProvider)
          .post('$_endpoint/images/$imageId/primary');
      setState(() {
        for (var i = 0; i < _existingImages.length; i++) {
          _existingImages[i]['is_primary'] = (i == index);
        }
      });
      if (!mounted) return;
      final isSw = ref.read(isSwahiliProvider);
      showLandingSnack(
        context,
        isSw ? 'Picha kuu imewekwa' : 'Primary image set',
      );
    } catch (e) {
      if (!mounted) return;
      showLandingSnack(context, landingAdminErrorMessage(e), error: true);
    }
  }

  Future<void> _deleteExistingImage(
    int imageId,
    int index,
    bool isSwahili,
  ) async {
    final confirmed = await confirmLandingDelete(
      context: context,
      isSwahili: isSwahili,
      titleEn: 'Delete image',
      titleSw: 'Futa picha',
      messageEn: 'Remove this image from the project?',
      messageSw: 'Ondoa picha hii kwenye mradi?',
    );
    if (!confirmed) return;
    try {
      await ref
          .read(apiClientProvider)
          .delete('$_endpoint/images/$imageId');
      setState(() => _existingImages.removeAt(index));
      if (!mounted) return;
      showLandingSnack(
        context,
        isSwahili ? 'Picha imeondolewa' : 'Image removed',
      );
    } catch (e) {
      if (!mounted) return;
      showLandingSnack(context, landingAdminErrorMessage(e), error: true);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final isSw = ref.read(isSwahiliProvider);
    if (!_isEdit && _pickedImages.isEmpty) {
      showLandingSnack(
        context,
        isSw
            ? 'Pakia picha angalau moja'
            : 'Please add at least one image',
        error: true,
      );
      return;
    }
    setState(() => _submitting = true);

    final form = <String, dynamic>{
      'title': _title.text.trim(),
      'category': _category.text.trim(),
      'description': _description.text.trim(),
      if (_priceTzs.text.trim().isNotEmpty) 'price_tzs': _priceTzs.text.trim(),
      if (_priceUsd.text.trim().isNotEmpty) 'price_usd': _priceUsd.text.trim(),
      'youtube_url': _youtubeUrl.text.trim(),
      'model_3d_url': _model3dUrl.text.trim(),
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_featured': _featured ? 1 : 0,
      'is_published': _published ? 1 : 0,
      'lang': isSw ? 'sw' : 'en',
    };

    if (_pickedImages.isNotEmpty) {
      final files = <MultipartFile>[];
      for (final f in _pickedImages) {
        files.add(await MultipartFile.fromFile(f.path));
      }
      form['images[]'] = files;
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
