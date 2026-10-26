import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _documentApprovalProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, ({String type, int id})>((ref, key) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/document-approvals/${key.type}/${key.id}');
  return Map<String, dynamic>.from(response.data['data'] as Map);
});

/// A document approved on the web only (payroll, project BOQ, imprest, petty
/// cash, advance salary, loan, statutory payment, VAT payment, sale, expense,
/// project schedule): its details, the approval steps, and Submit / Approve /
/// Reject, using the same RingleSoft flow and step-role rules as the web.
class DocumentApprovalScreen extends ConsumerStatefulWidget {
  final String type;
  final int id;
  const DocumentApprovalScreen({super.key, required this.type, required this.id});

  @override
  ConsumerState<DocumentApprovalScreen> createState() => _DocumentApprovalScreenState();
}

class _DocumentApprovalScreenState extends ConsumerState<DocumentApprovalScreen> {
  bool _busy = false;

  ({String type, int id}) get _key => (type: widget.type, id: widget.id);

  Future<void> _act(String action, bool isSwahili, {String? comment}) async {
    setState(() => _busy = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      final response = await ref.read(apiClientProvider).post(
        '/document-approvals/${widget.type}/${widget.id}/$action',
        data: {if (comment != null && comment.isNotEmpty) 'comment': comment},
      );
      ref.invalidate(_documentApprovalProvider(_key));
      messenger.showSnackBar(SnackBar(content: Text('${response.data['message'] ?? 'Done'}')));
    } on DioException catch (e) {
      final data = e.response?.data;
      messenger.showSnackBar(
        SnackBar(
          content: Text(data is Map && data['message'] != null ? '${data['message']}' : 'Failed'),
          backgroundColor: AppColors.error,
        ),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _decide(bool approve, bool isSwahili) async {
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(approve ? (isSwahili ? 'Idhinisha' : 'Approve') : (isSwahili ? 'Kataa' : 'Reject')),
        content: TextField(
          controller: controller,
          maxLines: 3,
          decoration: InputDecoration(labelText: isSwahili ? 'Maoni' : 'Comment'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: Text(approve ? (isSwahili ? 'Idhinisha' : 'Approve') : (isSwahili ? 'Kataa' : 'Reject'))),
        ],
      ),
    );
    final comment = controller.text.trim();
    controller.dispose();
    if (confirmed == true) await _act(approve ? 'approve' : 'reject', isSwahili, comment: comment);
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_documentApprovalProvider(_key));
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(title: Text(async.valueOrNull?['label']?.toString() ?? (isSwahili ? 'Idhini' : 'Approval'))),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_documentApprovalProvider(_key).future),
        child: async.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (_, __) => ListView(
            children: [
              const SizedBox(height: 120),
              Center(
                child: TextButton.icon(
                  onPressed: () => ref.invalidate(_documentApprovalProvider(_key)),
                  icon: const Icon(Icons.refresh),
                  label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                ),
              ),
            ],
          ),
          data: (d) {
            final details = (d['details'] as List? ?? const []).cast<Map<String, dynamic>>();
            final lines = (d['lines'] as List? ?? const []).cast<Map<String, dynamic>>();
            final flow = Map<String, dynamic>.from(d['approval_flow'] as Map? ?? const {});
            final steps = (flow['steps'] as List? ?? const []).cast<Map<String, dynamic>>();

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                Text('${d['title']}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Chip(label: Text('${flow['status_label'] ?? ''}')),
                const SizedBox(height: 8),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        for (final row in details)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 5),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(flex: 4, child: Text('${row['label']}', style: const TextStyle(color: AppColors.textSecondary))),
                                Expanded(flex: 6, child: Text('${row['value']}', textAlign: TextAlign.end)),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
                if (lines.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text(isSwahili ? 'Mishahara halisi' : 'Net salaries', style: const TextStyle(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  Card(
                    child: Column(
                      children: [
                        for (final l in lines)
                          ListTile(dense: true, title: Text('${l['label']}'), trailing: Text('${l['value']}')),
                      ],
                    ),
                  ),
                ],
                const SizedBox(height: 12),
                Text(isSwahili ? 'Hatua za idhini' : 'Approval steps', style: const TextStyle(fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                if (steps.isEmpty)
                  Text(isSwahili ? 'Hakuna hatua' : 'No approval steps', style: const TextStyle(color: AppColors.textSecondary)),
                for (final s in steps)
                  Card(
                    child: ListTile(
                      leading: Icon(
                        '${s['action']}'.toLowerCase().contains('approv')
                            ? Icons.check_circle_rounded
                            : '${s['action']}'.toLowerCase().contains('reject')
                                ? Icons.cancel_rounded
                                : Icons.hourglass_bottom_rounded,
                      ),
                      title: Text('${s['role_name']}'),
                      subtitle: Text([
                        '${s['action']}',
                        if (s['approver_name'] != null) '${s['approver_name']}',
                        if (s['date'] != null) '${s['date']}',
                        if (s['comment'] != null && '${s['comment']}'.isNotEmpty) '“${s['comment']}”',
                      ].join(' · ')),
                    ),
                  ),
                const SizedBox(height: 16),
                if (flow['can_be_submitted'] == true)
                  FilledButton.icon(
                    onPressed: _busy ? null : () => _act('submit', isSwahili),
                    icon: const Icon(Icons.send_rounded),
                    label: Text(isSwahili ? 'Wasilisha kwa idhini' : 'Submit for approval'),
                  ),
                if (flow['can_be_approved'] == true)
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _busy ? null : () => _decide(false, isSwahili),
                          icon: const Icon(Icons.close_rounded, color: AppColors.error),
                          label: Text(isSwahili ? 'Kataa' : 'Reject'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: _busy ? null : () => _decide(true, isSwahili),
                          icon: const Icon(Icons.check_rounded),
                          label: Text(isSwahili ? 'Idhinisha' : 'Approve'),
                        ),
                      ),
                    ],
                  ),
                const SizedBox(height: 90),
              ],
            );
          },
        ),
      ),
    );
  }
}
