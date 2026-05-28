// ignore_for_file: use_build_context_synchronously
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/services';
final _searchProvider = StateProvider<String>((ref) => '');

class ServicesAdminScreen extends ConsumerWidget {
  const ServicesAdminScreen({super.key});

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
        emptyIcon: Icons.handshake_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      error: (e, _) => LandingAdminAsyncSliver(
        isLoading: false,
        error: e,
        isEmpty: false,
        emptyTextEn: '',
        emptyTextSw: '',
        emptyIcon: Icons.handshake_outlined,
        onRetry: refresh,
        isSwahili: isSwahili,
      ),
      data: (items) {
        final filtered = search.isEmpty
            ? items
            : items.where((it) {
                final t = (it['title'] ?? '').toString().toLowerCase();
                final sh = (it['short_description'] ?? '')
                    .toString()
                    .toLowerCase();
                return t.contains(search) || sh.contains(search);
              }).toList();
        if (filtered.isEmpty) {
          return LandingAdminAsyncSliver(
            isLoading: false,
            error: null,
            isEmpty: true,
            emptyTextEn: items.isEmpty
                ? 'No services yet'
                : 'No matching services',
            emptyTextSw: items.isEmpty
                ? 'Hakuna huduma bado'
                : 'Hakuna huduma zinazolingana',
            emptyIcon: Icons.handshake_outlined,
            onRetry: refresh,
            isSwahili: isSwahili,
          );
        }
        return SliverPadding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
          sliver: SliverList.builder(
            itemCount: filtered.length,
            itemBuilder: (context, index) {
              return _ServiceCard(
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
      titleEn: 'Services',
      titleSw: 'Huduma',
      searchHintEn: 'Search services...',
      searchHintSw: 'Tafuta huduma...',
      fabTooltipEn: 'Add Service',
      fabTooltipSw: 'Ongeza Huduma',
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
        heightFactor: 0.95,
        child: _ServiceFormSheet(item: item),
      ),
    );
    if (result == true) {
      final isSwahili = ref.read(isSwahiliProvider);
      ref.invalidate(landingAdminListProvider(landingKey(_endpoint, isSwahili)));
    }
  }
}

class _ServiceCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _ServiceCard({
    required this.item,
    required this.isDarkMode,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final published = item['is_published'] == true;
    final features = (item['features'] as List? ?? const [])
        .map((e) => e.toString())
        .toList();
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
                  size: 72,
                  fallbackIcon: Icons.handshake_outlined,
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
                      if (item['short_description'] != null &&
                          item['short_description'].toString().isNotEmpty)
                        Text(
                          item['short_description'].toString(),
                          maxLines: 3,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppColors.textSecondary,
                            fontSize: 13,
                          ),
                        ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (value) async {
                    final isSw = ref.read(isSwahiliProvider);
                    if (value == 'edit') {
                      await ServicesAdminScreen._openForm(
                        context,
                        ref,
                        item: item,
                      );
                    } else if (value == 'delete') {
                      final confirmed = await confirmLandingDelete(
                        context: context,
                        isSwahili: isSw,
                        titleEn: 'Delete Service',
                        titleSw: 'Futa Huduma',
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
                          isSw ? 'Huduma imefutwa' : 'Service deleted',
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
            if (features.isNotEmpty) ...[
              const SizedBox(height: 10),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: features
                    .take(6)
                    .map(
                      (f) => Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.brandBlue.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          f,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: AppColors.brandBlue,
                          ),
                        ),
                      ),
                    )
                    .toList(),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ServiceFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;
  const _ServiceFormSheet({this.item});

  @override
  ConsumerState<_ServiceFormSheet> createState() => _ServiceFormSheetState();
}

class _ServiceFormSheetState extends ConsumerState<_ServiceFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _shortDescription;
  late final TextEditingController _fullDescription;
  late final TextEditingController _sortOrder;
  late final List<TextEditingController> _features;
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
    _shortDescription = TextEditingController(
      text: widget.item?['short_description']?.toString() ?? '',
    );
    _fullDescription = TextEditingController(
      text: widget.item?['full_description']?.toString() ?? '',
    );
    _sortOrder = TextEditingController(
      text: (widget.item?['sort_order'] ?? 0).toString(),
    );
    final existing = (widget.item?['features'] as List? ?? const [])
        .map((e) => e.toString())
        .toList();
    _features = (existing.isNotEmpty ? existing : [''])
        .map((s) => TextEditingController(text: s))
        .toList();
    _published = widget.item == null
        ? true
        : (widget.item!['is_published'] == true);
  }

  @override
  void dispose() {
    _title.dispose();
    _shortDescription.dispose();
    _fullDescription.dispose();
    _sortOrder.dispose();
    for (final c in _features) {
      c.dispose();
    }
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
                      ? (isSwahili ? 'Hariri Huduma' : 'Edit Service')
                      : (isSwahili ? 'Ongeza Huduma' : 'Add Service'),
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
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? (isSwahili ? 'Kichwa kinahitajika' : 'Title required')
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _shortDescription,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Maelezo Mafupi'
                        : 'Short description',
                    border: const OutlineInputBorder(),
                  ),
                  maxLines: 2,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _fullDescription,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Maelezo Kamili'
                        : 'Full description',
                    border: const OutlineInputBorder(),
                  ),
                  maxLines: 4,
                ),
                const SizedBox(height: 16),
                _featuresEditor(isSwahili),
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
            fallbackIcon: Icons.handshake_outlined,
          ),
        const SizedBox(width: 12),
        Expanded(
          child: ElevatedButton.icon(
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
                  ? (currentPath != null ? 'Badilisha picha' : 'Pakia picha')
                  : (currentPath != null ? 'Replace image' : 'Upload image'),
            ),
          ),
        ),
      ],
    );
  }

  Widget _featuresEditor(bool isSwahili) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              isSwahili ? 'Sifa' : 'Features',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            const Spacer(),
            TextButton.icon(
              onPressed: () => setState(
                () => _features.add(TextEditingController()),
              ),
              icon: const Icon(Icons.add_rounded, size: 18),
              label: Text(isSwahili ? 'Ongeza sifa' : 'Add feature'),
            ),
          ],
        ),
        for (int i = 0; i < _features.length; i++) ...[
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: TextFormField(
                  controller: _features[i],
                  decoration: InputDecoration(
                    hintText: isSwahili ? 'Sifa #${i + 1}' : 'Feature #${i + 1}',
                    border: const OutlineInputBorder(),
                    isDense: true,
                  ),
                ),
              ),
              IconButton(
                icon: const Icon(
                  Icons.remove_circle_outline,
                  color: AppColors.error,
                ),
                onPressed: _features.length <= 1
                    ? null
                    : () {
                        setState(() {
                          _features[i].dispose();
                          _features.removeAt(i);
                        });
                      },
              ),
            ],
          ),
        ],
      ],
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    final isSw = ref.read(isSwahiliProvider);

    final features = _features
        .map((c) => c.text.trim())
        .where((s) => s.isNotEmpty)
        .toList();

    final form = <String, dynamic>{
      'title': _title.text.trim(),
      'short_description': _shortDescription.text.trim(),
      'full_description': _fullDescription.text.trim(),
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_published': _published ? 1 : 0,
      'lang': isSw ? 'sw' : 'en',
      // Send features as repeated form-field entries so PHP groups them.
      for (int i = 0; i < features.length; i++) 'features[$i]': features[i],
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
