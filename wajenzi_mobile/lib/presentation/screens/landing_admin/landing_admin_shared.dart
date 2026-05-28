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
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

/// Shared scaffold + widgets used by every Landing CMS admin screen.
/// Keeps each per-feature screen file small and consistent.

/// Family-keyed provider that fetches a list payload from the given admin
/// endpoint and returns the `data` array as raw maps. Per-endpoint provider
/// instance is what each screen uses (so invalidation refetches that endpoint
/// only).
final landingAdminListProvider = FutureProvider.autoDispose
    .family<List<Map<String, dynamic>>, LandingListKey>((ref, key) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        key.path,
        queryParameters: {'lang': key.lang},
      );
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((m) => Map<String, dynamic>.from(m))
          .toList();
    });

class LandingListKey {
  final String path;
  final String lang;
  const LandingListKey(this.path, this.lang);

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is LandingListKey && other.path == path && other.lang == lang);
  @override
  int get hashCode => Object.hash(path, lang);
}

/// Singleton (object) GET provider — used for /landing-admin/about.
final landingAdminObjectProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, LandingListKey>((ref, key) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        key.path,
        queryParameters: {'lang': key.lang},
      );
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final obj = data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
      return obj;
    });

/// Convenience to build the list-provider key for an authenticated locale.
LandingListKey landingKey(String path, bool isSwahili) =>
    LandingListKey(path, isSwahili ? 'sw' : 'en');

/// Resolve a likely error message from a Dio/Validation response.
String landingAdminErrorMessage(Object error) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      if (data['message'] is String &&
          (data['message'] as String).trim().isNotEmpty) {
        final base = data['message'].toString();
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final firstKey = errors.keys.first;
          final firstVal = errors[firstKey];
          final detail = firstVal is List && firstVal.isNotEmpty
              ? firstVal.first.toString()
              : firstVal?.toString() ?? '';
          if (detail.isNotEmpty) return '$base: $detail';
        }
        return base;
      }
      if (data['error'] is String) return data['error'].toString();
    }
    return error.message ?? 'Request failed';
  }
  return error.toString();
}

/// Shared Scaffold layout for Landing admin screens.
/// The screen body is a sliver list rendered by [buildBody].
class LandingAdminScaffold extends ConsumerWidget {
  final String titleEn;
  final String titleSw;
  final String searchHintEn;
  final String searchHintSw;
  final IconData fabIcon;
  final String fabTooltipEn;
  final String fabTooltipSw;
  final VoidCallback? onAdd;
  final StateProvider<String>? searchProvider;
  final List<ProviderOrFamily>? refreshProviders;
  final Widget body;

  const LandingAdminScaffold({
    super.key,
    required this.titleEn,
    required this.titleSw,
    this.searchHintEn = 'Search...',
    this.searchHintSw = 'Tafuta...',
    this.fabIcon = Icons.add_rounded,
    this.fabTooltipEn = 'Add',
    this.fabTooltipSw = 'Ongeza',
    this.onAdd,
    this.searchProvider,
    this.refreshProviders,
    required this.body,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = searchProvider != null
        ? ref.watch(searchProvider!).trim()
        : '';

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? titleSw : titleEn),
      ),
      floatingActionButton: onAdd == null
          ? null
          : Padding(
              padding: const EdgeInsets.only(bottom: 80),
              child: FloatingActionButton(
                onPressed: onAdd,
                tooltip: isSwahili ? fabTooltipSw : fabTooltipEn,
                child: Icon(fabIcon),
              ),
            ),
      body: RefreshIndicator(
        onRefresh: () async {
          for (final p in (refreshProviders ?? const [])) {
            ref.invalidate(p);
          }
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            if (searchProvider != null)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: TextField(
                    onChanged: (value) =>
                        ref.read(searchProvider!.notifier).state = value,
                    decoration: InputDecoration(
                      hintText: isSwahili ? searchHintSw : searchHintEn,
                      prefixIcon: const Icon(Icons.search_rounded),
                      suffixIcon: search.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear),
                              onPressed: () => ref
                                  .read(searchProvider!.notifier)
                                  .state = '',
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
            body,
          ],
        ),
      ),
    );
  }
}

/// Sliver representation of the AsyncValue states (loading/error/empty).
class LandingAdminAsyncSliver extends StatelessWidget {
  final bool isLoading;
  final Object? error;
  final bool isEmpty;
  final String emptyTextEn;
  final String emptyTextSw;
  final IconData emptyIcon;
  final VoidCallback onRetry;
  final bool isSwahili;

  const LandingAdminAsyncSliver({
    super.key,
    required this.isLoading,
    required this.error,
    required this.isEmpty,
    required this.emptyTextEn,
    required this.emptyTextSw,
    required this.emptyIcon,
    required this.onRetry,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const SliverFillRemaining(
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (error != null) {
      return SliverFillRemaining(
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
              const SizedBox(height: 16),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Text(
                  landingAdminErrorMessage(error!),
                  textAlign: TextAlign.center,
                ),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: onRetry,
                child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
              ),
            ],
          ),
        ),
      );
    }
    if (isEmpty) {
      return SliverFillRemaining(
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(emptyIcon, size: 64, color: Colors.grey[400]),
              const SizedBox(height: 16),
              Text(
                isSwahili ? emptyTextSw : emptyTextEn,
                style: TextStyle(fontSize: 16, color: Colors.grey[600]),
              ),
            ],
          ),
        ),
      );
    }
    return const SliverToBoxAdapter(child: SizedBox.shrink());
  }
}

/// Thumbnail widget — shows a network image (via portal media resolver),
/// falling back to a placeholder.
class LandingThumbnail extends StatelessWidget {
  final String? path;
  final double size;
  final IconData fallbackIcon;
  final BorderRadius? borderRadius;

  const LandingThumbnail({
    super.key,
    required this.path,
    this.size = 56,
    this.fallbackIcon = Icons.image_outlined,
    this.borderRadius,
  });

  @override
  Widget build(BuildContext context) {
    final url = AppConfig.resolvePortalMediaUrl(path);
    final radius = borderRadius ?? BorderRadius.circular(8);

    if (url == null || url.isEmpty) {
      return Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          color: Colors.grey.shade200,
          borderRadius: radius,
        ),
        child: Icon(fallbackIcon, color: Colors.grey.shade500),
      );
    }
    return ClipRRect(
      borderRadius: radius,
      child: CachedNetworkImage(
        imageUrl: url,
        width: size,
        height: size,
        fit: BoxFit.cover,
        placeholder: (_, _) => Container(
          width: size,
          height: size,
          color: Colors.grey.shade200,
        ),
        errorWidget: (_, _, _) => Container(
          width: size,
          height: size,
          color: Colors.grey.shade200,
          child: Icon(fallbackIcon, color: Colors.grey.shade500),
        ),
      ),
    );
  }
}

/// Standard delete confirmation dialog.
Future<bool> confirmLandingDelete({
  required BuildContext context,
  required bool isSwahili,
  required String titleEn,
  required String titleSw,
  required String messageEn,
  required String messageSw,
}) async {
  final res = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: Text(isSwahili ? titleSw : titleEn),
      content: Text(isSwahili ? messageSw : messageEn),
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
  return res == true;
}

/// Show snack: success or error.
void showLandingSnack(
  BuildContext context,
  String message, {
  bool error = false,
}) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text(message),
      backgroundColor: error ? AppColors.error : AppColors.success,
    ),
  );
}

/// Pick an image and return it as a `MultipartFile` for upload.
Future<({MultipartFile multipart, File file})?> pickLandingImage(
  BuildContext context, {
  required bool isSwahili,
}) async {
  try {
    final picker = ImagePicker();
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (sheetContext) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.photo_library_rounded),
              title: Text(isSwahili ? 'Chagua kutoka kwa picha' : 'Choose from gallery'),
              onTap: () => Navigator.pop(sheetContext, ImageSource.gallery),
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: Text(isSwahili ? 'Piga picha' : 'Take a photo'),
              onTap: () => Navigator.pop(sheetContext, ImageSource.camera),
            ),
          ],
        ),
      ),
    );
    if (source == null) return null;
    final picked = await picker.pickImage(
      source: source,
      imageQuality: 85,
      maxWidth: 2048,
      maxHeight: 2048,
    );
    if (picked == null) return null;
    final file = File(picked.path);
    final multipart = await MultipartFile.fromFile(
      file.path,
      filename: picked.name,
    );
    return (multipart: multipart, file: file);
  } catch (_) {
    return null;
  }
}

/// Build a published / draft chip.
Widget publishedChip(bool published, bool isSwahili) {
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
    decoration: BoxDecoration(
      color: (published ? AppColors.success : AppColors.warning).withValues(
        alpha: 0.15,
      ),
      borderRadius: BorderRadius.circular(999),
    ),
    child: Text(
      published
          ? (isSwahili ? 'Imechapishwa' : 'Published')
          : (isSwahili ? 'Rasimu' : 'Draft'),
      style: TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        color: published ? AppColors.success : AppColors.warning,
      ),
    ),
  );
}
