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
}) => downloadFile(context, ref, '/exports/$path', query: query);

/// Same as [downloadExport] for any endpoint answering with a base64 file
/// (`file_base64` or `pdf_base64`).
Future<void> downloadFile(
  BuildContext context,
  WidgetRef ref,
  String apiPath, {
  Map<String, dynamic>? query,
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

  const ExportOption(
    this.label,
    this.path, {
    this.query,
    this.icon = Icons.picture_as_pdf_outlined,
  });
}

/// App-bar / card button that lists export formats and downloads the chosen one.
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
    return PopupMenuButton<ExportOption>(
      tooltip: tooltip,
      icon: const Icon(Icons.file_download_outlined),
      onSelected: (o) => downloadExport(context, ref, o.path, query: o.query),
      itemBuilder: (_) => [
        for (final o in options)
          PopupMenuItem(
            value: o,
            child: Row(
              children: [
                Icon(o.icon, size: 20),
                const SizedBox(width: 12),
                Text(o.label),
              ],
            ),
          ),
      ],
    );
  }
}
