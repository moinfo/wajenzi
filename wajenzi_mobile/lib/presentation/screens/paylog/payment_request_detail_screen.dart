import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../core/services/external_launcher_service.dart';
import '../../../data/models/paylog/paylog_models.dart';
import '../../../data/repositories/paylog_repository.dart';
import 'paylog_shared.dart';

final _detailProvider = FutureProvider.autoDispose
    .family<SitePaymentRequestDto, int>((ref, id) {
  return ref.watch(paylogRepositoryProvider).requestShow(id);
});

class PaymentRequestDetailScreen extends ConsumerStatefulWidget {
  final int requestId;
  const PaymentRequestDetailScreen({super.key, required this.requestId});

  @override
  ConsumerState<PaymentRequestDetailScreen> createState() =>
      _PaymentRequestDetailScreenState();
}

class _PaymentRequestDetailScreenState
    extends ConsumerState<PaymentRequestDetailScreen> {
  bool _busy = false;

  // Record-payment form state.
  final _refCtrl = TextEditingController();
  final _noteCtrl = TextEditingController();
  DateTime _payDate = DateTime.now();
  String? _slipPath;

  @override
  void dispose() {
    _refCtrl.dispose();
    _noteCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_detailProvider(widget.requestId));
    return Scaffold(
      appBar: AppBar(title: const Text('Payment Request')),
      body: Stack(
        children: [
          async.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (e, _) => _ErrorView(
              error: e,
              onRetry: () =>
                  ref.invalidate(_detailProvider(widget.requestId)),
            ),
            data: (req) => RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_detailProvider(widget.requestId));
                await ref.read(_detailProvider(widget.requestId).future);
              },
              child: _buildBody(req),
            ),
          ),
          if (_busy)
            Container(
              color: Colors.black.withValues(alpha: 0.15),
              child: const Center(child: CircularProgressIndicator()),
            ),
        ],
      ),
    );
  }

  Widget _buildBody(SitePaymentRequestDto req) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                req.requestNumber,
                style: const TextStyle(
                    fontSize: 18, fontWeight: FontWeight.w800),
              ),
            ),
            PaylogStatusChip(
              label: req.displayStatus,
              colorToken: req.statusColor,
            ),
          ],
        ),
        const SizedBox(height: 12),
        _infoRow('Site', req.siteName ?? '-'),
        _infoRow('Project', req.projectName ?? '-'),
        _infoRow('Requested by', req.creatorName ?? '-'),
        _infoRow('Payment date', fmtDate(req.paymentDate)),
        _infoRow('Total', 'TZS ${fmtMoney(req.totalAmount)}'),
        if (req.nextStep != null)
          _infoRow(
            'Next step',
            '${req.nextStep!.actionLabel} — ${req.nextStep!.role}',
          ),
        const SizedBox(height: 20),
        const Text('Payment Lines',
            style: TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        _buildLines(req.lines),
        if (req.files.isNotEmpty) ...[
          const SizedBox(height: 20),
          const Text('Documents',
              style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          ...req.files.map(_buildFileTile),
        ],
        if (req.isPaid) ...[
          const SizedBox(height: 20),
          _buildPaidCard(req),
        ],
        const SizedBox(height: 20),
        _buildActions(req),
      ],
    );
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(color: Theme.of(context).hintColor),
            ),
          ),
          Expanded(
            child: Text(value,
                style: const TextStyle(fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }

  Widget _buildLines(List<SitePaylogLineDto> lines) {
    if (lines.isEmpty) {
      return Text('No lines on this request.',
          style: TextStyle(color: Theme.of(context).hintColor));
    }
    return Column(
      children: lines.map((l) {
        final meta = [
          l.categoryLabel,
          if (l.channelName != null && l.channelName!.isNotEmpty) l.channelName,
          if (l.accountName != null && l.accountName!.isNotEmpty) l.accountName,
        ].whereType<String>().join(' • ');
        return Card(
          margin: const EdgeInsets.symmetric(vertical: 4),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        l.payeeName,
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                    ),
                    Text('TZS ${fmtMoney(l.amount)}',
                        style: const TextStyle(fontWeight: FontWeight.w700)),
                  ],
                ),
                if (l.reason != null && l.reason!.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(l.reason!,
                      style: Theme.of(context).textTheme.bodySmall),
                ],
                const SizedBox(height: 4),
                Text(meta,
                    style: Theme.of(context)
                        .textTheme
                        .bodySmall
                        ?.copyWith(color: Theme.of(context).hintColor)),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildFileTile(SitePaymentRequestFileDto file) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 3),
      child: ListTile(
        dense: true,
        leading: const Icon(Icons.insert_drive_file_rounded),
        title: Text(
          file.displayName,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: const Icon(Icons.open_in_new_rounded, size: 18),
        onTap: () async {
          final ok =
              await ExternalLauncherService.openPortalPath(file.filePath);
          if (!ok && mounted) {
            _snack('Could not open the document.');
          }
        },
      ),
    );
  }

  Widget _buildPaidCard(SitePaymentRequestDto req) {
    return Card(
      color: Colors.green.withValues(alpha: 0.08),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Payment Recorded',
                style: TextStyle(
                    fontWeight: FontWeight.w700, color: Colors.green)),
            const SizedBox(height: 8),
            _infoRow('Reference', req.paymentReference ?? '-'),
            _infoRow('Paid date', fmtDate(req.paidDate)),
            if (req.paidByName != null)
              _infoRow('Paid by', req.paidByName!),
            if (req.paymentNote != null && req.paymentNote!.isNotEmpty)
              _infoRow('Note', req.paymentNote!),
            if (req.paymentSlipPath != null &&
                req.paymentSlipPath!.isNotEmpty)
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: () =>
                      ExternalLauncherService.openPortalPath(
                          req.paymentSlipPath!),
                  icon: const Icon(Icons.receipt_long_rounded, size: 18),
                  label: const Text('View payment slip'),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildActions(SitePaymentRequestDto req) {
    final buttons = <Widget>[];

    if (req.canSubmit) {
      buttons.add(_actionButton(
        label: 'Submit',
        icon: Icons.send_rounded,
        color: Colors.blue,
        onPressed: () => _runSimple(
          () => ref.read(paylogRepositoryProvider).submit(req.id),
          'Payment request submitted.',
        ),
      ));
    }
    if (req.canApprove) {
      final label = req.nextStep?.actionLabel ?? 'Approve';
      buttons.add(_actionButton(
        label: label,
        icon: Icons.check_circle_rounded,
        color: Colors.green,
        onPressed: () => _confirmApprove(req, label),
      ));
    }
    if (req.canReject) {
      buttons.add(_actionButton(
        label: 'Reject',
        icon: Icons.cancel_rounded,
        color: Colors.red,
        onPressed: () => _promptReject(req),
      ));
    }

    final widgets = <Widget>[];
    if (buttons.isNotEmpty) {
      widgets.add(Wrap(spacing: 8, runSpacing: 8, children: buttons));
    }
    if (req.canRecordPayment) {
      widgets.add(const SizedBox(height: 16));
      widgets.add(_buildRecordPaymentCard(req));
    }

    if (widgets.isEmpty) {
      return Padding(
        padding: const EdgeInsets.only(top: 8),
        child: Text(
          'No actions are available to you for this request.',
          style: TextStyle(color: Theme.of(context).hintColor),
        ),
      );
    }
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: widgets);
  }

  Widget _actionButton({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback onPressed,
  }) {
    return ElevatedButton.icon(
      onPressed: _busy ? null : onPressed,
      icon: Icon(icon, size: 18),
      label: Text(label),
      style: ElevatedButton.styleFrom(
        backgroundColor: color,
        foregroundColor: Colors.white,
      ),
    );
  }

  Widget _buildRecordPaymentCard(SitePaymentRequestDto req) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Record Payment',
                style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 10),
            TextField(
              controller: _refCtrl,
              decoration: const InputDecoration(
                labelText: 'Payment Reference *',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 10),
            InkWell(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: _payDate,
                  firstDate: DateTime(2020),
                  lastDate: DateTime(2035),
                );
                if (picked != null) setState(() => _payDate = picked);
              },
              child: InputDecorator(
                decoration: const InputDecoration(
                  labelText: 'Payment Date',
                  isDense: true,
                  border: OutlineInputBorder(),
                ),
                child: Text(DateFormat('dd MMM yyyy').format(_payDate)),
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _noteCtrl,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Note (optional)',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 10),
            _SlipField(
              filePath: _slipPath,
              onPicked: (p) => setState(() => _slipPath = p),
            ),
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _busy ? null : () => _recordPayment(req),
                child: const Text('Mark as Paid'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _confirmApprove(SitePaymentRequestDto req, String label) async {
    final commentCtrl = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('$label Request'),
        content: TextField(
          controller: commentCtrl,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'Comment (optional)',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(label),
          ),
        ],
      ),
    );
    final comment = commentCtrl.text.trim();
    commentCtrl.dispose();
    if (confirmed != true) return;
    await _runSimple(
      () => ref.read(paylogRepositoryProvider).approve(
            req.id,
            comment: comment.isEmpty ? null : comment,
          ),
      'Request ${label.toLowerCase()}d.',
    );
  }

  Future<void> _promptReject(SitePaymentRequestDto req) async {
    final commentCtrl = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Reject Request'),
        content: TextField(
          controller: commentCtrl,
          maxLines: 3,
          autofocus: true,
          decoration: const InputDecoration(
            labelText: 'Reason for rejection *',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () {
              if (commentCtrl.text.trim().isEmpty) return;
              Navigator.pop(ctx, true);
            },
            child: const Text('Reject'),
          ),
        ],
      ),
    );
    final comment = commentCtrl.text.trim();
    commentCtrl.dispose();
    if (confirmed != true || comment.isEmpty) return;
    await _runSimple(
      () => ref.read(paylogRepositoryProvider).reject(req.id, comment),
      'Request rejected.',
    );
  }

  Future<void> _recordPayment(SitePaymentRequestDto req) async {
    if (_refCtrl.text.trim().isEmpty) {
      _snack('Payment reference is required.');
      return;
    }
    setState(() => _busy = true);
    try {
      await ref.read(paylogRepositoryProvider).recordPayment(
            req.id,
            paymentReference: _refCtrl.text.trim(),
            paymentDate: DateFormat('yyyy-MM-dd').format(_payDate),
            paymentNote: _noteCtrl.text.trim(),
            paymentSlipPath: _slipPath,
          );
      ref.invalidate(_detailProvider(widget.requestId));
      if (mounted) _snack('Payment recorded successfully.');
    } catch (e) {
      _snack(paylogErrorMessage(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _runSimple(
      Future<void> Function() action, String successMsg) async {
    setState(() => _busy = true);
    try {
      await action();
      ref.invalidate(_detailProvider(widget.requestId));
      if (mounted) _snack(successMsg);
    } catch (e) {
      _snack(paylogErrorMessage(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _snack(String m) {
    if (mounted) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(m)));
    }
  }
}

// ── payment-slip picker (png/jpg/jpeg/pdf) ──────────────────────────────────
class _SlipField extends StatelessWidget {
  final String? filePath;
  final ValueChanged<String?> onPicked;
  const _SlipField({required this.filePath, required this.onPicked});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Payment Slip (optional)',
            style: TextStyle(fontSize: 12, color: Theme.of(context).hintColor)),
        const SizedBox(height: 6),
        InkWell(
          onTap: () => _pick(context),
          borderRadius: BorderRadius.circular(10),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: Theme.of(context).dividerColor),
            ),
            child: Row(
              children: [
                Icon(
                  filePath != null
                      ? Icons.check_circle_rounded
                      : Icons.attach_file_rounded,
                  size: 18,
                  color: filePath != null
                      ? Colors.green
                      : Theme.of(context).hintColor,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    filePath != null
                        ? filePath!.split('/').last
                        : 'No file chosen',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (filePath != null)
                  GestureDetector(
                    onTap: () => onPicked(null),
                    child: const Icon(Icons.close_rounded,
                        size: 18, color: Colors.red),
                  ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  void _pick(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.insert_drive_file_rounded),
              title: const Text('Choose file (pdf / image)'),
              onTap: () async {
                Navigator.pop(ctx);
                final result = await FilePicker.platform.pickFiles(
                  type: FileType.custom,
                  allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png'],
                );
                final path = result?.files.single.path;
                if (path != null) onPicked(path);
              },
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_rounded),
              title: const Text('Take photo'),
              onTap: () async {
                Navigator.pop(ctx);
                final picked = await ImagePicker().pickImage(
                  source: ImageSource.camera,
                  imageQuality: 80,
                );
                if (picked != null) onPicked(picked.path);
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;
  const _ErrorView({required this.error, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 100),
        Icon(Icons.error_outline_rounded,
            size: 56, color: Colors.red.withValues(alpha: 0.7)),
        const SizedBox(height: 12),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child:
                Text(paylogErrorMessage(error), textAlign: TextAlign.center),
          ),
        ),
        const SizedBox(height: 12),
        Center(
          child:
              OutlinedButton(onPressed: onRetry, child: const Text('Retry')),
        ),
      ],
    );
  }
}
