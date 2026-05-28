// Shared building blocks for the Structural Design and Service Design screens.
//
// Both screens follow the same model (design header → ordered stages with
// file uploads + per-stage approval workflow), so the data layer, list cards,
// status chips and form modals are factored into one place and parameterised
// by an [EngineeringDesignKind] enum.

import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

/// Distinguishes which Engineering Design feature a screen / provider is for.
enum EngineeringDesignKind { structural, service }

extension EngineeringDesignKindX on EngineeringDesignKind {
  /// REST base path under `/api/v1`.
  String get path => switch (this) {
        EngineeringDesignKind.structural => '/structural-designs',
        EngineeringDesignKind.service => '/service-designs',
      };

  String titleEn() => switch (this) {
        EngineeringDesignKind.structural => 'Structural Design',
        EngineeringDesignKind.service => 'Service Design',
      };

  String titleSw() => switch (this) {
        EngineeringDesignKind.structural => 'Ubunifu wa Muundo',
        EngineeringDesignKind.service => 'Ubunifu wa Huduma',
      };
}

/// Filter applied on the list screen.
class DesignFilter {
  final int? projectId;
  final String? status; // pending/in_progress/submitted/approved/rejected
  final bool assignedToMe;

  const DesignFilter({
    this.projectId,
    this.status,
    this.assignedToMe = false,
  });

  Map<String, dynamic> toQuery() {
    final q = <String, dynamic>{};
    if (projectId != null) q['project_id'] = projectId;
    if (status != null && status!.isNotEmpty) q['status'] = status;
    if (assignedToMe) q['assigned_to_me'] = 1;
    return q;
  }

  DesignFilter copyWith({
    int? projectId,
    String? status,
    bool? assignedToMe,
    bool clearProject = false,
    bool clearStatus = false,
  }) =>
      DesignFilter(
        projectId: clearProject ? null : (projectId ?? this.projectId),
        status: clearStatus ? null : (status ?? this.status),
        assignedToMe: assignedToMe ?? this.assignedToMe,
      );
}

final designFilterProvider = StateProvider.autoDispose
    .family<DesignFilter, EngineeringDesignKind>((ref, _) => const DesignFilter());

final designListProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, EngineeringDesignKind>((ref, kind) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(designFilterProvider(kind));
  final response = await api.get(kind.path, queryParameters: filter.toQuery());

  final payload = response.data['data'];
  final collection = payload is Map<String, dynamic> ? payload : null;
  final items = collection?['data'] ?? payload;
  final meta = collection?['meta'] as Map<String, dynamic>? ?? const {};

  return {
    'items': (items as List? ?? const [])
        .whereType<Map>()
        .map((m) => Map<String, dynamic>.from(m))
        .toList(),
    'meta': meta,
  };
});

final designDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, ({EngineeringDesignKind kind, int id})>(
        (ref, args) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('${args.kind.path}/${args.id}');
  return Map<String, dynamic>.from(response.data['data'] as Map);
});

final designReferenceDataProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, EngineeringDesignKind>((ref, kind) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('${kind.path}/reference-data');
  return Map<String, dynamic>.from(response.data['data'] as Map);
});

// ── Translations ────────────────────────────────────────────────────────────

String trDesign(
  AppLanguage language, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) =>
    switch (language) {
      AppLanguage.swahili => sw ?? en,
      AppLanguage.french => fr ?? en,
      AppLanguage.arabic => ar ?? en,
      AppLanguage.english => en,
    };

String designStatusLabel(String? status, AppLanguage language) {
  switch ((status ?? '').toLowerCase()) {
    case 'pending':
      return trDesign(language, en: 'Pending', sw: 'Inasubiri');
    case 'in_progress':
      return trDesign(language, en: 'In Progress', sw: 'Inaendelea');
    case 'submitted':
      return trDesign(language, en: 'Submitted', sw: 'Imewasilishwa');
    case 'approved':
      return trDesign(language, en: 'Approved', sw: 'Imeidhinishwa');
    case 'rejected':
      return trDesign(language, en: 'Rejected', sw: 'Imekataliwa');
    case 'completed':
      return trDesign(language, en: 'Completed', sw: 'Imekamilika');
    case 'not_submitted':
      return trDesign(language, en: 'Not Submitted', sw: 'Haijawasilishwa');
    default:
      return status ?? '-';
  }
}

Color designStatusColor(String? status) {
  switch ((status ?? '').toLowerCase()) {
    case 'approved':
    case 'completed':
      return AppColors.brandGreen;
    case 'submitted':
      return AppColors.brandBlue;
    case 'in_progress':
      return AppColors.brandYellow;
    case 'rejected':
      return AppColors.error;
    case 'pending':
    case 'not_submitted':
    default:
      return Colors.grey;
  }
}

String formatDesignDate(dynamic value) {
  if (value == null) return '-';
  try {
    final dt = DateTime.parse(value.toString()).toLocal();
    return DateFormat('MMM d, yyyy').format(dt);
  } catch (_) {
    return value.toString();
  }
}

String designErrorMessage(Object error, AppLanguage language) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final m = data['message'];
      if (m is String && m.trim().isNotEmpty) return m;
    }
  }
  return trDesign(
    language,
    en: 'Something went wrong',
    sw: 'Hitilafu imetokea',
  );
}

// ── Widgets ─────────────────────────────────────────────────────────────────

class DesignStatusChip extends StatelessWidget {
  final String? status;
  final AppLanguage language;
  const DesignStatusChip({super.key, required this.status, required this.language});

  @override
  Widget build(BuildContext context) {
    final color = designStatusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.45), width: 0.8),
      ),
      child: Text(
        designStatusLabel(status, language),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

class DesignInfoRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  const DesignInfoRow({
    super.key,
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white60 : AppColors.textSecondary,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── File picker helper (image + camera; designs uploads commonly photos) ───

class DesignFilePickerTile extends StatelessWidget {
  final File? file;
  final bool isDarkMode;
  final AppLanguage language;
  final ValueChanged<File?> onPicked;

  const DesignFilePickerTile({
    super.key,
    required this.file,
    required this.isDarkMode,
    required this.language,
    required this.onPicked,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => _pick(context),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: isDarkMode
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isDarkMode
                ? Colors.white.withValues(alpha: 0.08)
                : Colors.grey.withValues(alpha: 0.2),
          ),
        ),
        child: Row(
          children: [
            Icon(
              file != null
                  ? Icons.check_circle_rounded
                  : Icons.attach_file_rounded,
              size: 20,
              color: file != null
                  ? AppColors.brandGreen
                  : (isDarkMode ? Colors.white38 : AppColors.textHint),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                file != null
                    ? file!.path.split('/').last
                    : trDesign(language,
                        en: 'Tap to attach a file', sw: 'Bonyeza kuambatisha faili'),
                style: TextStyle(
                  fontSize: 13,
                  color: file != null
                      ? (isDarkMode ? Colors.white : AppColors.textPrimary)
                      : (isDarkMode ? Colors.white60 : AppColors.textHint),
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (file != null)
              IconButton(
                icon: const Icon(Icons.close_rounded),
                color: Colors.red.withValues(alpha: 0.7),
                onPressed: () => onPicked(null),
              ),
          ],
        ),
      ),
    );
  }

  void _pick(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.photo_library_rounded),
              title: Text(trDesign(language,
                  en: 'Pick from gallery', sw: 'Chagua kutoka picha')),
              onTap: () async {
                Navigator.pop(ctx);
                final picked = await ImagePicker().pickImage(
                  source: ImageSource.gallery,
                  imageQuality: 85,
                );
                if (picked != null) onPicked(File(picked.path));
              },
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: Text(trDesign(language, en: 'Take photo', sw: 'Piga picha')),
              onTap: () async {
                Navigator.pop(ctx);
                final picked = await ImagePicker().pickImage(
                  source: ImageSource.camera,
                  imageQuality: 85,
                );
                if (picked != null) onPicked(File(picked.path));
              },
            ),
          ],
        ),
      ),
    );
  }
}
