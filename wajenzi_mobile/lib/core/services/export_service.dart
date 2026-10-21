import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:path_provider/path_provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../network/api_client.dart';

/// Downloads a file produced by the backend `/exports/...` endpoints (the same
/// documents the web export buttons give) and opens it on the device.
///
/// The API answers `{ success, data: { filename, mime_type, file_base64 } }`.
Future<void> downloadExport(
  BuildContext context,
  WidgetRef ref,
  String path, {
  Map<String, dynamic>? query,
}) async {
  final messenger = ScaffoldMessenger.of(context);
  void say(String text, {bool error = false}) {
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(text),
          backgroundColor: error ? Colors.red.shade700 : null,
        ),
      );
  }

  say('Downloading...');
  try {
    final response = await ref
        .read(apiClientProvider)
        .get('/exports/$path', queryParameters: query);
    final data = response.data['data'] as Map<String, dynamic>?;
    final b64 = data?['file_base64'] as String?;
    if (b64 == null || b64.isEmpty) {
      throw StateError('Empty file response');
    }
    final filename = _safeName(data?['filename'] as String? ?? 'export');
    final dir = await getTemporaryDirectory();
    final filePath = '${dir.path}/$filename';
    await File(filePath).writeAsBytes(base64Decode(b64));
    messenger.hideCurrentSnackBar();
    final opened = await launchUrl(
      Uri.file(filePath),
      mode: LaunchMode.externalApplication,
    );
    if (!opened) say('Saved: $filename');
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
