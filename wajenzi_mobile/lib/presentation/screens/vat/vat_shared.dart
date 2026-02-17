import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';

// --- Colors ---
const vatDarkCard = Color(0xFF1A2332);
const vatDarkBg = Color(0xFF0F1923);
const vatDarkBorder = Color(0xFF243447);
const vatAccentTeal = Color(0xFF2FACB2);
const vatAccentBlue = Color(0xFF3F9CE8);

// --- Number formatter ---
String vatMoney(dynamic v) {
  final n = (v is num) ? v.toDouble() : 0.0;
  return NumberFormat('#,##0.00').format(n);
}

String vatDateFmt(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

BoxDecoration vatCardDeco(bool isDark) => BoxDecoration(
      color: isDark ? vatDarkCard : Colors.white,
      borderRadius: BorderRadius.circular(12),
      border: Border.all(
        color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.1),
      ),
      boxShadow: isDark
          ? null
          : [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.03),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
    );

// --- Date Range Bar ---
class VatDateRangeBar extends ConsumerWidget {
  final AutoDisposeStateProvider<DateTime> startProvider;
  final AutoDisposeStateProvider<DateTime> endProvider;
  final bool isDark;
  final bool isSwahili;

  const VatDateRangeBar({
    super.key,
    required this.startProvider,
    required this.endProvider,
    required this.isDark,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final start = ref.watch(startProvider);
    final end = ref.watch(endProvider);
    final df = DateFormat('dd MMM yyyy');

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: isDark ? vatDarkCard : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.15),
        ),
      ),
      child: Row(
        children: [
          Expanded(
            child: GestureDetector(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: start,
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now(),
                );
                if (picked != null) {
                  ref.read(startProvider.notifier).state = picked;
                }
              },
              child: _DateChip(
                  label: isSwahili ? 'Kuanzia' : 'From',
                  date: df.format(start),
                  isDark: isDark),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Icon(Icons.arrow_forward_rounded,
                size: 16, color: isDark ? Colors.white38 : AppColors.textHint),
          ),
          Expanded(
            child: GestureDetector(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: end,
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now(),
                );
                if (picked != null) {
                  ref.read(endProvider.notifier).state = picked;
                }
              },
              child: _DateChip(
                  label: isSwahili ? 'Hadi' : 'To',
                  date: df.format(end),
                  isDark: isDark),
            ),
          ),
        ],
      ),
    );
  }
}

class _DateChip extends StatelessWidget {
  final String label;
  final String date;
  final bool isDark;
  const _DateChip(
      {required this.label, required this.date, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: TextStyle(
                fontSize: 10,
                color: isDark ? Colors.white38 : AppColors.textHint)),
        const SizedBox(height: 2),
        Row(
          children: [
            Icon(Icons.calendar_today_rounded, size: 12, color: vatAccentTeal),
            const SizedBox(width: 6),
            Text(date,
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: isDark ? Colors.white : AppColors.textPrimary)),
          ],
        ),
      ],
    );
  }
}

// --- Summary Chip ---
class VatSummaryChip extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final bool isDark;
  const VatSummaryChip({
    super.key,
    required this.label,
    required this.value,
    required this.color,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDark ? vatDarkCard : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border(left: BorderSide(color: color, width: 3.5)),
        boxShadow: isDark
            ? null
            : [
                BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 2))
              ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label,
              style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w500,
                  color: isDark ? Colors.white54 : AppColors.textSecondary)),
          const SizedBox(height: 4),
          Text(value,
              style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: isDark ? Colors.white : AppColors.textPrimary)),
        ],
      ),
    );
  }
}

// --- Count Badge ---
class VatCountBadge extends StatelessWidget {
  final int count;
  final String label;
  final bool isDark;
  const VatCountBadge(
      {super.key,
      required this.count,
      required this.label,
      required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Text(
      '$label ($count)',
      style: TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: isDark ? Colors.white70 : AppColors.textPrimary),
    );
  }
}

// --- Info Column ---
class VatInfoCol extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;
  final bool isMoney;
  const VatInfoCol(
      {super.key,
      required this.label,
      required this.value,
      required this.isDark,
      this.isMoney = false});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label,
              style: TextStyle(
                  fontSize: 9,
                  color: isDark ? Colors.white38 : AppColors.textHint)),
          const SizedBox(height: 2),
          FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Text(
              value,
              style: TextStyle(
                fontSize: isMoney ? 12 : 11,
                fontWeight: isMoney ? FontWeight.w600 : FontWeight.w500,
                color: isMoney
                    ? (isDark ? vatAccentTeal : vatAccentBlue)
                    : (isDark ? Colors.white70 : AppColors.textSecondary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// --- Key-Value Row (for detail views) ---
class VatKvRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;
  final bool isMoney;
  const VatKvRow({
    super.key,
    required this.label,
    required this.value,
    required this.isDark,
    this.isMoney = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(label,
                style: TextStyle(
                    fontSize: 11,
                    color: isDark ? Colors.white38 : AppColors.textHint)),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: isMoney ? FontWeight.w600 : FontWeight.w500,
                color: isMoney
                    ? (isDark ? vatAccentTeal : vatAccentBlue)
                    : (isDark ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// --- Reference Data Provider ---
final vatReferenceDataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final resp = await api.get('/vat/reference-data');
  return resp.data['data'] as Map<String, dynamic>;
});

// --- CRUD Helpers ---
Future<bool> vatDelete(
    BuildContext context, WidgetRef ref, String endpoint, int id,
    {required bool isSwahili}) async {
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(isSwahili ? 'Thibitisha' : 'Confirm'),
      content: Text(isSwahili
          ? 'Una uhakika unataka kufuta?'
          : 'Are you sure you want to delete?'),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx, false),
          child: Text(isSwahili ? 'Hapana' : 'Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(ctx, true),
          style: TextButton.styleFrom(foregroundColor: Colors.red),
          child: Text(isSwahili ? 'Futa' : 'Delete'),
        ),
      ],
    ),
  );
  if (confirmed != true) return false;

  try {
    final api = ref.read(apiClientProvider);
    await api.delete('$endpoint/$id');
    return true;
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    }
    return false;
  }
}

// --- Form Field Builders ---
Widget vatTextField({
  required TextEditingController controller,
  required String label,
  required bool isDark,
  TextInputType? keyboardType,
  bool readOnly = false,
  VoidCallback? onTap,
  String? Function(String?)? validator,
}) {
  return Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      readOnly: readOnly,
      onTap: onTap,
      validator: validator,
      style: TextStyle(
          fontSize: 13, color: isDark ? Colors.white : AppColors.textPrimary),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(
            fontSize: 12, color: isDark ? Colors.white54 : AppColors.textHint),
        filled: true,
        fillColor:
            isDark ? const Color(0xFF0F1923) : Colors.grey.withValues(alpha: 0.05),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(
              color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.2)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(
              color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.2)),
        ),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      ),
    ),
  );
}

Widget vatDropdown<T>({
  required T? value,
  required List<T> items,
  required String label,
  required bool isDark,
  required String Function(T) labelBuilder,
  required void Function(T?) onChanged,
  String? Function(T?)? validator,
}) {
  return Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: DropdownButtonFormField<T>(
      // ignore: deprecated_member_use
      value: value,
      items: items
          .map((item) => DropdownMenuItem<T>(
                value: item,
                child: Text(labelBuilder(item),
                    style: TextStyle(
                        fontSize: 13,
                        color:
                            isDark ? Colors.white : AppColors.textPrimary)),
              ))
          .toList(),
      onChanged: onChanged,
      validator: validator,
      hint: Text(label.replaceAll(' *', ''),
          style: TextStyle(
              fontSize: 13,
              color: isDark ? Colors.white38 : AppColors.textHint)),
      dropdownColor: isDark ? vatDarkCard : Colors.white,
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(
            fontSize: 12, color: isDark ? Colors.white54 : AppColors.textHint),
        filled: true,
        fillColor:
            isDark ? const Color(0xFF0F1923) : Colors.grey.withValues(alpha: 0.05),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(
              color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.2)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(
              color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.2)),
        ),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      ),
    ),
  );
}

Future<DateTime?> vatPickDate(BuildContext context, DateTime initial) {
  return showDatePicker(
    context: context,
    initialDate: initial,
    firstDate: DateTime(2020),
    lastDate: DateTime(2030),
  );
}

// --- File Picker Widget ---
class VatFilePicker extends StatelessWidget {
  final File? file;
  final bool isDark;
  final bool isSwahili;
  final ValueChanged<File?> onPicked;

  const VatFilePicker({
    super.key,
    required this.file,
    required this.isDark,
    required this.isSwahili,
    required this.onPicked,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili ? 'Chagua faili' : 'Choose file',
            style: TextStyle(
                fontSize: 12,
                color: isDark ? Colors.white54 : AppColors.textHint),
          ),
          const SizedBox(height: 6),
          GestureDetector(
            onTap: () => _pickFile(context),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: isDark
                    ? const Color(0xFF0F1923)
                    : Colors.grey.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                  color: isDark
                      ? vatDarkBorder
                      : Colors.grey.withValues(alpha: 0.2),
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    file != null
                        ? Icons.check_circle_rounded
                        : Icons.attach_file_rounded,
                    size: 18,
                    color: file != null
                        ? AppColors.primary
                        : (isDark ? Colors.white38 : AppColors.textHint),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      file != null
                          ? file!.path.split('/').last
                          : (isSwahili ? 'Hakuna faili' : 'No file chosen'),
                      style: TextStyle(
                        fontSize: 13,
                        color: file != null
                            ? (isDark ? Colors.white : AppColors.textPrimary)
                            : (isDark ? Colors.white38 : AppColors.textHint),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (file != null)
                    GestureDetector(
                      onTap: () => onPicked(null),
                      child: Icon(Icons.close_rounded,
                          size: 18,
                          color: Colors.red.withValues(alpha: 0.7)),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _pickFile(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.photo_library_rounded),
              title: Text(isSwahili ? 'Chagua picha' : 'Pick from gallery'),
              onTap: () async {
                Navigator.pop(ctx);
                final picked = await ImagePicker()
                    .pickImage(source: ImageSource.gallery, imageQuality: 80);
                if (picked != null) onPicked(File(picked.path));
              },
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: Text(isSwahili ? 'Piga picha' : 'Take photo'),
              onTap: () async {
                Navigator.pop(ctx);
                final picked = await ImagePicker()
                    .pickImage(source: ImageSource.camera, imageQuality: 80);
                if (picked != null) onPicked(File(picked.path));
              },
            ),
          ],
        ),
      ),
    );
  }
}

// --- Build FormData with optional file ---
Future<FormData> vatBuildFormData(
    Map<String, dynamic> fields, File? file) async {
  final map = <String, dynamic>{};
  for (final e in fields.entries) {
    map[e.key] = e.value;
  }
  if (file != null) {
    map['file'] = await MultipartFile.fromFile(file.path,
        filename: file.path.split('/').last);
  }
  return FormData.fromMap(map);
}

// --- Status Badge ---
class VatStatusBadge extends StatelessWidget {
  final String status;
  const VatStatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    Color color;
    switch (status.toUpperCase()) {
      case 'APPROVED':
        color = const Color(0xFF27AE60);
      case 'PENDING':
        color = const Color(0xFFF59E0B);
      case 'REJECTED':
        color = const Color(0xFFEF4444);
      default:
        color = const Color(0xFF3B82F6);
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(status.toUpperCase(),
          style:
              TextStyle(fontSize: 9, fontWeight: FontWeight.w700, color: color)),
    );
  }
}

// --- Empty State ---
class VatEmptyState extends StatelessWidget {
  final bool isDark;
  final bool isSwahili;
  const VatEmptyState(
      {super.key, required this.isDark, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 40),
      child: Column(
        children: [
          Icon(Icons.inbox_rounded,
              size: 48, color: isDark ? Colors.white24 : Colors.grey[300]),
          const SizedBox(height: 12),
          Text(
            isSwahili ? 'Hakuna data' : 'No data available',
            style: TextStyle(
                fontSize: 13,
                color: isDark ? Colors.white38 : AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

// --- Error Body ---
class VatErrorBody extends StatelessWidget {
  final VoidCallback onRetry;
  final bool isSwahili;
  const VatErrorBody(
      {super.key, required this.onRetry, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 80),
        const Icon(Icons.error_outline, size: 56, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 20),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}
