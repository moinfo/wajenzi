import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/network/api_client.dart';
import '../../../core/services/export_service.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/date_filter_bar.dart';
import '../../widgets/common/motion.dart';

typedef _TableKey = ({String route, String? projectId, String? start, String? end});

final _webTableProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, _TableKey>((ref, key) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/web-tables/${key.route}',
    queryParameters: {
      if (key.projectId != null) 'project_id': key.projectId,
      if (key.start != null) 'start_date': key.start,
      if (key.end != null) 'end_date': key.end,
    },
  );
  return Map<String, dynamic>.from(response.data['data'] as Map);
});

/// A list or report page of the web (project, bank reconciliation, bookkeeping
/// pages) as a native screen: `GET /web-tables/{route}` returns the same table
/// the web page shows, and the Export button downloads the same Excel / PDF.
class WebTableScreen extends ConsumerStatefulWidget {
  final String route;

  /// Optional project filter (the web page's `?project_id=`).
  final String? projectId;
  const WebTableScreen({super.key, required this.route, this.projectId});

  @override
  ConsumerState<WebTableScreen> createState() => _WebTableScreenState();
}

class _WebTableScreenState extends ConsumerState<WebTableScreen> {
  String _query = '';
  DateTime? _start = defaultStartDate();
  DateTime? _end = today();
  bool _hasDates = false;

  _TableKey get _key => (
        route: widget.route,
        projectId: widget.projectId,
        start: DateFilterBar.format(_start),
        end: DateFilterBar.format(_end),
      );

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_webTableProvider(_key));
    final isSwahili = ref.watch(isSwahiliProvider);
    final title = async.valueOrNull?['title']?.toString() ?? '';
    // Pages that have a Start Date / End Date filter on the web get one here.
    final loaded = async.valueOrNull;
    if (loaded != null) _hasDates = loaded['has_date_filter'] == true;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.canPop()
              ? context.pop()
              : context.go(widget.route.startsWith('reports_')
                  ? '/reports'
                  : '/dashboard'),
        ),
        title: Text(title),
      ),
      body: Column(
        children: [
          ExportButtonsBar(
            options: webTableExportOptions(
              widget.route,
              params: {
                'project_id': widget.projectId,
                'start_date': DateFilterBar.format(_start),
                'end_date': DateFilterBar.format(_end),
              },
            ),
          ),
          if (_hasDates)
            DateFilterBar(
              start: _start,
              end: _end,
              onStart: (d) => setState(() => _start = d),
              onEnd: (d) => setState(() => _end = d),
            ),
          Expanded(child: RefreshIndicator(
        onRefresh: () => ref.refresh(_webTableProvider(_key).future),
        child: async.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) {
            var message = isSwahili ? 'Imeshindwa kupakia ukurasa' : 'Could not load this page';
            if (e is DioException && e.response?.data is Map && e.response!.data['message'] != null) {
              message = '${e.response!.data['message']}';
            }
            return ListView(
              children: [
                const SizedBox(height: 120),
                Padding(
                  padding: const EdgeInsets.all(24),
                  child: Center(child: Text(message, textAlign: TextAlign.center)),
                ),
                Center(
                  child: TextButton.icon(
                    onPressed: () => ref.invalidate(_webTableProvider(_key)),
                    icon: const Icon(Icons.refresh),
                    label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                  ),
                ),
              ],
            );
          },
          data: (table) {
            final headers = (table['headers'] as List).map((h) => '$h').toList();
            final rows = (table['rows'] as List).map((r) => (r as List).map((c) => '$c').toList()).toList();
            final q = _query.trim().toLowerCase();
            final shown = q.isEmpty
                ? rows
                : rows.where((r) => r.any((c) => c.toLowerCase().contains(q))).toList();

            String cell(List<String> row, int i) => i < row.length ? row[i] : '';
            // Title of a row: the first two non-empty cells that are not just the row number.
            String rowTitle(List<String> row) {
              final parts = <String>[];
              for (var i = 0; i < row.length && parts.length < 2; i++) {
                final c = row[i].trim();
                if (c.isEmpty || RegExp(r'^\d+$').hasMatch(c) && i == 0) continue;
                parts.add(c);
              }
              return parts.isEmpty ? '#' : parts.join(' · ');
            }

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                TextField(
                  onChanged: (v) => setState(() => _query = v),
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.search_rounded),
                    hintText: isSwahili ? 'Tafuta…' : 'Search…',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    isDense: true,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  '${shown.length} ${isSwahili ? 'safu' : 'rows'}',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 8),
                if (shown.isEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 48),
                    child: Center(child: Text(isSwahili ? 'Hakuna data' : 'No records')),
                  ),
                for (final (i, row) in shown.indexed)
                  FadeSlideIn(index: i, child: Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ExpansionTile(
                      title: Text(rowTitle(row), maxLines: 2, overflow: TextOverflow.ellipsis),
                      childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                      children: [
                        for (var i = 0; i < headers.length; i++)
                          if (headers[i].trim().isNotEmpty && cell(row, i).trim().isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 3),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(flex: 4, child: Text(headers[i], style: const TextStyle(color: Colors.grey))),
                                  Expanded(flex: 6, child: Text(cell(row, i), textAlign: TextAlign.end)),
                                ],
                              ),
                            ),
                      ],
                    ),
                  )),
                const SizedBox(height: 90),
              ],
            );
          },
        ),
      )),
        ],
      ),
    );
  }
}
