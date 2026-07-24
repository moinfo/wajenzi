import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/paylog/paylog_models.dart';
import '../../../data/repositories/paylog_repository.dart';
import 'paylog_shared.dart';
import 'payment_request_detail_screen.dart';

// ── module-level state + async providers ────────────────────────────────────
final _dpSiteProvider = StateProvider.autoDispose<int?>((_) => null);
final _dpDateProvider =
    StateProvider.autoDispose<DateTime>((_) => DateTime.now());

final _dpRefProvider = FutureProvider.autoDispose<PaylogReferenceData>((ref) {
  return ref.watch(paylogRepositoryProvider).referenceData();
});

final _dpSummaryProvider =
    FutureProvider.autoDispose<DailySummaryDto?>((ref) async {
  final siteId = ref.watch(_dpSiteProvider);
  if (siteId == null) return null;
  final date = ref.watch(_dpDateProvider);
  return ref.watch(paylogRepositoryProvider).dailySummary(
        siteId: siteId,
        date: DateFormat('yyyy-MM-dd').format(date),
      );
});

class DailyPaymentsScreen extends ConsumerStatefulWidget {
  const DailyPaymentsScreen({super.key});

  @override
  ConsumerState<DailyPaymentsScreen> createState() =>
      _DailyPaymentsScreenState();
}

class _DailyPaymentsScreenState extends ConsumerState<DailyPaymentsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final refAsync = ref.watch(_dpRefProvider);
    final canCreate = hasPermission(ref, 'Daily Payments');

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: const Text('Daily Payments'),
      ),
      floatingActionButton: canCreate
          ? refAsync.maybeWhen(
              data: (refData) => FloatingActionButton.extended(
                onPressed: () => _openCreate(refData),
                icon: const Icon(Icons.add),
                label: const Text('New Payment Request'),
              ),
              orElse: () => null,
            )
          : null,
      body: refAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_dpRefProvider),
        ),
        data: (refData) => _buildBody(refData),
      ),
    );
  }

  Widget _buildBody(PaylogReferenceData refData) {
    final selectedSite = ref.watch(_dpSiteProvider);
    final date = ref.watch(_dpDateProvider);

    // Default the site filter to the first available site once ref-data loads.
    if (selectedSite == null && refData.sites.isNotEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        if (ref.read(_dpSiteProvider) == null) {
          ref.read(_dpSiteProvider.notifier).state = refData.sites.first.id;
        }
      });
    }

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
          child: Row(
            children: [
              Expanded(
                flex: 3,
                child: DropdownButtonFormField<int>(
                  initialValue: selectedSite,
                  isExpanded: true,
                  decoration: const InputDecoration(
                    labelText: 'Site',
                    isDense: true,
                    border: OutlineInputBorder(),
                  ),
                  items: refData.sites
                      .map((s) => DropdownMenuItem(
                            value: s.id,
                            child: Text(s.name, overflow: TextOverflow.ellipsis),
                          ))
                      .toList(),
                  onChanged: (v) =>
                      ref.read(_dpSiteProvider.notifier).state = v,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                flex: 2,
                child: InkWell(
                  onTap: _pickDate,
                  child: InputDecorator(
                    decoration: const InputDecoration(
                      labelText: 'Date',
                      isDense: true,
                      border: OutlineInputBorder(),
                    ),
                    child: Text(DateFormat('dd MMM yyyy').format(date)),
                  ),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: selectedSite == null
              ? _EmptyView(
                  icon: Icons.location_city_outlined,
                  label: refData.sites.isEmpty
                      ? 'No sites are available for your account.'
                      : 'Select a site to view its daily payments.',
                )
              : _buildSummary(),
        ),
      ],
    );
  }

  Widget _buildSummary() {
    final async = ref.watch(_dpSummaryProvider);
    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(_dpSummaryProvider);
        await ref.read(_dpSummaryProvider.future);
      },
      child: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          onRetry: () => ref.invalidate(_dpSummaryProvider),
        ),
        data: (summary) {
          if (summary == null) {
            return _EmptyView(
              icon: Icons.receipt_long_outlined,
              label: 'Select a site to view its daily payments.',
            );
          }
          return ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(12, 8, 12, 90),
            children: [
              Row(
                children: [
                  Expanded(
                    child: _TotalCard(
                      label: 'Material',
                      amount: summary.material,
                      color: Colors.blueGrey,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _TotalCard(
                      label: 'Labour',
                      amount: summary.labour,
                      color: Colors.teal,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: _TotalCard(
                      label: 'Total',
                      amount: summary.all,
                      color: Colors.indigo,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                'Payment Requests (${summary.requests.length})',
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              if (summary.requests.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 32),
                  child: Center(
                    child: Text(
                      'No payment requests for this date.',
                      style: TextStyle(color: Theme.of(context).hintColor),
                    ),
                  ),
                )
              else
                ...summary.requests.map(
                  (r) => _RequestTile(
                    request: r,
                    onTap: () => _openDetail(r.id),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _pickDate() async {
    final current = ref.read(_dpDateProvider);
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked != null) {
      ref.read(_dpDateProvider.notifier).state = picked;
    }
  }

  Future<void> _openDetail(int id) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PaymentRequestDetailScreen(requestId: id),
      ),
    );
    ref.invalidate(_dpSummaryProvider);
  }

  Future<void> _openCreate(PaylogReferenceData refData) async {
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => _CreatePaymentRequestScreen(
          refData: refData,
          initialSiteId: ref.read(_dpSiteProvider),
          initialDate: ref.read(_dpDateProvider),
        ),
      ),
    );
    if (created == true) {
      ref.invalidate(_dpSummaryProvider);
    }
  }
}

// ── totals card ─────────────────────────────────────────────────────────────
class _TotalCard extends StatelessWidget {
  final String label;
  final double amount;
  final Color color;
  const _TotalCard({
    required this.label,
    required this.amount,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: color,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              fmtMoney(amount),
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w800,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

// ── request tile ────────────────────────────────────────────────────────────
class _RequestTile extends StatelessWidget {
  final SitePaymentRequestDto request;
  final VoidCallback onTap;
  const _RequestTile({required this.request, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final subtitle = [request.projectName, request.creatorName]
        .whereType<String>()
        .where((v) => v.isNotEmpty)
        .join(' • ');
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 5),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      request.requestNumber,
                      style: const TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  PaylogStatusChip(
                    label: request.displayStatus,
                    colorToken: request.statusColor,
                  ),
                ],
              ),
              if (subtitle.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: Theme.of(context).textTheme.bodySmall,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 8),
              Row(
                children: [
                  Text(
                    'TZS ${fmtMoney(request.totalAmount)}',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const Spacer(),
                  Text(
                    '${request.linesCount} line(s)',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
// Create Payment Request (header + N lines + optional docs) → storeBulk
// ═════════════════════════════════════════════════════════════════════════════
class _LineForm {
  final payee = TextEditingController();
  final reason = TextEditingController();
  final account = TextEditingController();
  final amount = TextEditingController();
  String category = 'material';
  int? channelId;

  void dispose() {
    payee.dispose();
    reason.dispose();
    account.dispose();
    amount.dispose();
  }
}

class _CreatePaymentRequestScreen extends ConsumerStatefulWidget {
  final PaylogReferenceData refData;
  final int? initialSiteId;
  final DateTime initialDate;
  const _CreatePaymentRequestScreen({
    required this.refData,
    required this.initialSiteId,
    required this.initialDate,
  });

  @override
  ConsumerState<_CreatePaymentRequestScreen> createState() =>
      _CreatePaymentRequestScreenState();
}

class _CreatePaymentRequestScreenState
    extends ConsumerState<_CreatePaymentRequestScreen> {
  int? _siteId;
  int? _projectId;
  late DateTime _date;
  final List<_LineForm> _lines = [_LineForm()];
  final List<String> _docPaths = [];
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _siteId = widget.initialSiteId ??
        (widget.refData.sites.isNotEmpty
            ? widget.refData.sites.first.id
            : null);
    _date = widget.initialDate;
  }

  @override
  void dispose() {
    for (final l in _lines) {
      l.dispose();
    }
    super.dispose();
  }

  List<PaymentChannelDto> get _channels =>
      widget.refData.channels.where((c) => c.isActive).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('New Payment Request')),
      body: Stack(
        children: [
          ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 40),
            children: [
              DropdownButtonFormField<int>(
                initialValue: _siteId,
                isExpanded: true,
                decoration: const InputDecoration(
                  labelText: 'Site *',
                  border: OutlineInputBorder(),
                ),
                items: widget.refData.sites
                    .map((s) => DropdownMenuItem(
                          value: s.id,
                          child:
                              Text(s.name, overflow: TextOverflow.ellipsis),
                        ))
                    .toList(),
                onChanged: (v) => setState(() => _siteId = v),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int?>(
                initialValue: _projectId,
                isExpanded: true,
                decoration: const InputDecoration(
                  labelText: 'Project (optional)',
                  border: OutlineInputBorder(),
                ),
                items: [
                  const DropdownMenuItem<int?>(
                    value: null,
                    child: Text('— None —'),
                  ),
                  ...widget.refData.projects.map(
                    (p) => DropdownMenuItem<int?>(
                      value: p.id,
                      child: Text(p.name, overflow: TextOverflow.ellipsis),
                    ),
                  ),
                ],
                onChanged: (v) => setState(() => _projectId = v),
              ),
              const SizedBox(height: 12),
              InkWell(
                onTap: _pickDate,
                child: InputDecorator(
                  decoration: const InputDecoration(
                    labelText: 'Payment Date',
                    border: OutlineInputBorder(),
                  ),
                  child: Text(DateFormat('dd MMM yyyy').format(_date)),
                ),
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  const Text('Payment Lines',
                      style: TextStyle(fontWeight: FontWeight.w700)),
                  const Spacer(),
                  TextButton.icon(
                    onPressed: () => setState(() => _lines.add(_LineForm())),
                    icon: const Icon(Icons.add, size: 18),
                    label: const Text('Add line'),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              ...List.generate(_lines.length, (i) => _buildLine(i)),
              const SizedBox(height: 16),
              _buildDocs(),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _busy ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  child: const Text('Submit Payment Request'),
                ),
              ),
            ],
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

  Widget _buildLine(int index) {
    final line = _lines[index];
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 6),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text('Line ${index + 1}',
                    style: const TextStyle(fontWeight: FontWeight.w700)),
                const Spacer(),
                if (_lines.length > 1)
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    icon: const Icon(Icons.delete_outline, color: Colors.red),
                    onPressed: () => setState(() {
                      _lines.removeAt(index).dispose();
                    }),
                  ),
              ],
            ),
            TextField(
              controller: line.payee,
              decoration: const InputDecoration(
                labelText: 'Payee Name *',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: line.reason,
              decoration: const InputDecoration(
                labelText: 'Reason *',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<String>(
              initialValue: line.category,
              decoration: const InputDecoration(
                labelText: 'Category',
                isDense: true,
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'material', child: Text('Material')),
                DropdownMenuItem(value: 'labour', child: Text('Labour')),
              ],
              onChanged: (v) =>
                  setState(() => line.category = v ?? 'material'),
            ),
            const SizedBox(height: 10),
            DropdownButtonFormField<int?>(
              initialValue: line.channelId,
              isExpanded: true,
              decoration: const InputDecoration(
                labelText: 'Payment Channel (optional)',
                isDense: true,
                border: OutlineInputBorder(),
              ),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('— None —'),
                ),
                ..._channels.map(
                  (c) => DropdownMenuItem<int?>(
                    value: c.id,
                    child: Text(c.name, overflow: TextOverflow.ellipsis),
                  ),
                ),
              ],
              onChanged: (v) => setState(() => line.channelId = v),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: line.account,
              decoration: const InputDecoration(
                labelText: 'Account Name / Number (optional)',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: line.amount,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Amount *',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDocs() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Text('Attachments (optional)',
                style: TextStyle(fontWeight: FontWeight.w700)),
            const Spacer(),
            TextButton.icon(
              onPressed: _pickDocument,
              icon: const Icon(Icons.attach_file_rounded, size: 18),
              label: const Text('Add'),
            ),
          ],
        ),
        if (_docPaths.isEmpty)
          Text('No files chosen',
              style: TextStyle(color: Theme.of(context).hintColor))
        else
          ..._docPaths.asMap().entries.map(
                (e) => ListTile(
                  dense: true,
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.insert_drive_file_rounded),
                  title: Text(
                    e.value.split('/').last,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: IconButton(
                    icon: const Icon(Icons.close_rounded,
                        color: Colors.red, size: 18),
                    onPressed: () =>
                        setState(() => _docPaths.removeAt(e.key)),
                  ),
                ),
              ),
      ],
    );
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked != null) setState(() => _date = picked);
  }

  void _pickDocument() {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.insert_drive_file_rounded),
              title: const Text('Choose file'),
              onTap: () async {
                Navigator.pop(ctx);
                final result = await FilePicker.platform.pickFiles(
                  type: FileType.custom,
                  allowedExtensions: const [
                    'png',
                    'jpg',
                    'jpeg',
                    'pdf',
                    'doc',
                    'docx',
                    'xls',
                    'xlsx',
                  ],
                );
                final path = result?.files.single.path;
                if (path != null) setState(() => _docPaths.add(path));
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
                if (picked != null) {
                  setState(() => _docPaths.add(picked.path));
                }
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (_siteId == null) {
      _snack('Please select a site.');
      return;
    }
    final payments = <Map<String, dynamic>>[];
    for (var i = 0; i < _lines.length; i++) {
      final l = _lines[i];
      final payee = l.payee.text.trim();
      final reason = l.reason.text.trim();
      final amount =
          double.tryParse(l.amount.text.trim().replaceAll(',', '')) ?? 0;
      if (payee.isEmpty) {
        _snack('Payee name is required on line ${i + 1}.');
        return;
      }
      if (reason.isEmpty) {
        _snack('Reason is required on line ${i + 1}.');
        return;
      }
      if (amount <= 0) {
        _snack('Amount must be greater than 0 on line ${i + 1}.');
        return;
      }
      payments.add({
        'payee_name': payee,
        'reason': reason,
        'category': l.category,
        'amount': amount,
        'payment_channel_id': l.channelId,
        'account_name': l.account.text.trim(),
      });
    }
    if (payments.isEmpty) {
      _snack('Add at least one payment line.');
      return;
    }

    setState(() => _busy = true);
    try {
      await ref.read(paylogRepositoryProvider).storeBulk(
            siteId: _siteId!,
            projectId: _projectId,
            paymentDate: DateFormat('yyyy-MM-dd').format(_date),
            payments: payments,
            documentPaths: _docPaths,
          );
      if (mounted) {
        Navigator.of(context).pop(true);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Payment request submitted for verification.'),
          ),
        );
      }
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

// ── shared small views (file-local) ─────────────────────────────────────────
class _EmptyView extends StatelessWidget {
  final IconData icon;
  final String label;
  const _EmptyView({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 64, color: Theme.of(context).hintColor),
        const SizedBox(height: 12),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(color: Theme.of(context).hintColor),
            ),
          ),
        ),
      ],
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
