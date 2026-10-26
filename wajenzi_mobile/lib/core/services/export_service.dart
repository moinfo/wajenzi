import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../network/api_client.dart';

final FlutterLocalNotificationsPlugin _notifications =
    FlutterLocalNotificationsPlugin();
bool _notificationsReady = false;

/// Call once at app start. Tapping a "Download complete" notification opens
/// the downloaded document.
Future<void> initDownloadNotifications() async {
  if (kIsWeb || _notificationsReady) return;
  try {
    await _notifications.initialize(
      settings: const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
        iOS: DarwinInitializationSettings(),
      ),
      onDidReceiveNotificationResponse: (response) {
        final path = response.payload;
        if (path != null && path.isNotEmpty) OpenFilex.open(path);
      },
    );
    await _notifications
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.requestNotificationsPermission();
    _notificationsReady = true;
  } catch (e) {
    debugPrint('Download notifications unavailable: $e');
  }
}

/// Saves [bytes] to the device, posts a "Download complete" notification (tap
/// to open) and returns the saved path.
Future<String> saveDownload(List<int> bytes, String filename) async {
  final safe = _safeName(filename);
  Directory dir;
  try {
    dir =
        (!kIsWeb && Platform.isAndroid
            ? await getExternalStorageDirectory()
            : null) ??
        await getApplicationDocumentsDirectory();
  } catch (_) {
    dir = await getTemporaryDirectory();
  }
  final folder = Directory('${dir.path}/Downloads');
  if (!await folder.exists()) await folder.create(recursive: true);
  final path = '${folder.path}/$safe';
  await File(path).writeAsBytes(bytes);

  await initDownloadNotifications();
  try {
    await _notifications.show(
      id: DateTime.now().millisecondsSinceEpoch ~/ 1000,
      title: 'Download complete',
      body: safe,
      payload: path,
      notificationDetails: const NotificationDetails(
        android: AndroidNotificationDetails(
          'downloads',
          'Downloads',
          channelDescription: 'Completed document downloads',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
    );
  } catch (e) {
    debugPrint('Could not show download notification: $e');
  }
  return path;
}

/// Downloads a file produced by the backend `/exports/...` endpoints (the same
/// documents the web export buttons give), saves it and notifies the user.
///
/// The API answers `{ success, data: { filename, mime_type, file_base64 } }`.
Future<void> downloadExport(
  BuildContext context,
  WidgetRef ref,
  String path, {
  Map<String, dynamic>? query,
  bool openAfter = false,
}) => downloadFile(
      context,
      ref,
      '/exports/$path',
      query: query,
      openAfter: openAfter,
    );

/// Same as [downloadExport] for any endpoint answering with a base64 file
/// (`file_base64` or `pdf_base64`).
Future<void> downloadFile(
  BuildContext context,
  WidgetRef ref,
  String apiPath, {
  Map<String, dynamic>? query,
  bool openAfter = false,
}) async {
  final messenger = ScaffoldMessenger.of(context);
  void say(String text, {bool error = false, String? openPath}) {
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(text),
          backgroundColor: error ? Colors.red.shade700 : null,
          action: openPath == null
              ? null
              : SnackBarAction(
                  label: 'Open',
                  onPressed: () => OpenFilex.open(openPath),
                ),
        ),
      );
  }

  say('Downloading...');
  try {
    final response = await ref
        .read(apiClientProvider)
        .get(apiPath, queryParameters: query);
    final data = response.data['data'] as Map<String, dynamic>?;
    final b64 =
        (data?['file_base64'] ?? data?['pdf_base64']) as String?;
    if (b64 == null || b64.isEmpty) {
      throw StateError('Empty file response');
    }
    final saved = await saveDownload(
      base64Decode(b64),
      data?['filename'] as String? ?? 'export',
    );
    say(
      'Download complete: ${saved.split(RegExp(r'[\/]')).last}',
      openPath: saved,
    );
    // Print: open the PDF in the viewer, which offers printing.
    if (openAfter) await OpenFilex.open(saved);
  } on DioException catch (e) {
    final body = e.response?.data;
    final message = body is Map ? body['message'] as String? : null;
    say(message ?? 'Could not download the file. Please try again.', error: true);
  } catch (_) {
    say('Could not download the file. Please try again.', error: true);
  }
}

String _safeName(String name) => name.replaceAll(RegExp(r'[\\/:*?"<>|]'), '_');

/// One export format offered by [ExportMenuButton].
class ExportOption {
  final String label;
  final String path;
  final Map<String, dynamic>? query;
  final IconData icon;

  /// Open the file right after downloading (the Print button).
  final bool openAfter;

  const ExportOption(
    this.label,
    this.path, {
    this.query,
    this.icon = Icons.picture_as_pdf_outlined,
    this.openAfter = false,
  });
}

/// Download button(s) for a single document (a card or detail sheet): one
/// button per export format, side by side, never hidden in a menu.
class ExportMenuButton extends ConsumerWidget {
  final List<ExportOption> options;
  final String tooltip;

  const ExportMenuButton({
    super.key,
    required this.options,
    this.tooltip = 'Export',
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (final o in options)
          IconButton(
            tooltip: options.length == 1 ? tooltip : o.label,
            icon: Icon(
              options.length == 1 ? Icons.file_download_outlined : o.icon,
            ),
            onPressed: () => downloadExport(
              context,
              ref,
              o.path,
              query: o.query,
              openAfter: o.openAfter,
            ),
          ),
      ],
    );
  }
}

/// The export formats side by side as separate buttons, shown above a list the
/// way the web shows its Print / Excel / PDF toolbar above a table.
class ExportButtonsBar extends ConsumerWidget {
  final List<ExportOption> options;
  final EdgeInsetsGeometry padding;

  const ExportButtonsBar({
    super.key,
    required this.options,
    this.padding = const EdgeInsets.fromLTRB(16, 10, 16, 2),
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (options.isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: padding,
      child: Align(
        alignment: AlignmentDirectional.centerStart,
        child: Wrap(
          spacing: 8,
          runSpacing: 6,
          children: [
            for (final o in options)
              _ExportPill(
                label: o.label,
                onPressed: () => downloadExport(
                  context,
                  ref,
                  o.path,
                  query: o.query,
                  openAfter: o.openAfter,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// One export button: a rounded, lightly tinted pill with an icon (green for
/// Excel, red for PDF, neutral for Print) that reads on light and dark.
class _ExportPill extends StatelessWidget {
  final String label;
  final VoidCallback onPressed;
  const _ExportPill({required this.label, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final key = label.toLowerCase();
    final (IconData icon, Color color) = key.contains('excel')
        ? (Icons.table_chart_outlined, const Color(0xFF2E9E57))
        : key.contains('pdf')
            ? (Icons.picture_as_pdf_outlined, const Color(0xFFE0483E))
            : key.contains('print')
                ? (Icons.print_outlined, dark ? const Color(0xFF9FB4C7) : const Color(0xFF4B6478))
                : (Icons.download_outlined, dark ? const Color(0xFF7CC4E8) : const Color(0xFF3B8EDB));

    return Material(
      color: color.withValues(alpha: dark ? 0.16 : 0.10),
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: onPressed,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: color.withValues(alpha: 0.45)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 17, color: color),
              const SizedBox(width: 6),
              Text(
                label,
                style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: color),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Print + Excel + PDF for a web list page (the DataTables toolbar).
/// [route] is the web route name, e.g. `project_clients`. Print produces the
/// PDF and opens it in the viewer, which can print it.
///
/// [params] are the filters the page was opened with (start_date, end_date,
/// project_id...), so the file holds the same rows as the filtered list, as the
/// web's buttons export what the table shows.
List<ExportOption> webTableExportOptions(
  String route, {
  Map<String, String?> params = const {},
}) {
  final filters = {
    for (final e in params.entries)
      if (e.value != null && e.value!.isNotEmpty) e.key: e.value,
  };
  Map<String, dynamic> query(String format) => {
        'route': route,
        'format': format,
        if (filters.isNotEmpty) 'params': filters,
      };

  return [
    ExportOption('Print', 'table', query: query('pdf'), openAfter: true),
    ExportOption(
      'Excel',
      'table',
      query: query('excel'),
      icon: Icons.table_chart_outlined,
    ),
    ExportOption('PDF', 'table', query: query('pdf')),
  ];
}
