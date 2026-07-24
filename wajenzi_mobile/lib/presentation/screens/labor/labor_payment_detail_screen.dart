import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';

/// Payment phase detail screen with status-driven actions:
/// Approve (can_approve), Process/Disburse (can_pay), Hold (not paid), Release (held).
class LaborPaymentDetailScreen extends ConsumerStatefulWidget {
  final int paymentId;

  const LaborPaymentDetailScreen({super.key, required this.paymentId});

  @override
  ConsumerState<LaborPaymentDetailScreen> createState() =>
      _LaborPaymentDetailScreenState();
}

class _LaborPaymentDetailScreenState
    extends ConsumerState<LaborPaymentDetailScreen> {
  Map<String, dynamic>? _payment;
  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _error;
  bool _changed = false;

  final NumberFormat _money = NumberFormat.currency(
    symbol: 'TZS ',
    decimalDigits: 0,
  );

  @override
  void initState() {
    super.initState();
    _loadPayment();
  }

  Future<void> _loadPayment() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/labor/payments/${widget.paymentId}');
      if (response.statusCode == 200 && response.data['success'] == true) {
        setState(() {
          _payment = Map<String, dynamic>.from(response.data['data']);
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.data['message']?.toString() ?? 'Failed to load payment';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Error loading payment: $e';
        _isLoading = false;
      });
    }
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'paid':
        return Colors.green;
      case 'approved':
        return Colors.blue;
      case 'due':
        return Colors.orange;
      case 'pending':
        return Colors.grey;
      case 'held':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatMoney(dynamic amount) {
    final value = amount is num
        ? amount.toDouble()
        : double.tryParse(amount?.toString() ?? '') ?? 0;
    return _money.format(value);
  }

  void _showSnack(String message, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: error ? Colors.red : null,
      ),
    );
  }

  String _extractError(Object e) {
    final s = e.toString();
    return s;
  }

  Future<void> _runAction(
    Future<void> Function(ApiClient api) action,
  ) async {
    setState(() => _isSubmitting = true);
    try {
      final api = ref.read(apiClientProvider);
      await action(api);
      _changed = true;
      await _loadPayment();
    } catch (e) {
      _showSnack(_extractError(e), error: true);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  Future<void> _approve() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await _confirm(
      title: isSwahili ? 'Idhinisha Malipo' : 'Approve Payment',
      message: isSwahili
          ? 'Una uhakika unataka kuidhinisha awamu hii ya malipo?'
          : 'Approve this payment phase for disbursement?',
      confirmLabel: isSwahili ? 'Idhinisha' : 'Approve',
    );
    if (confirmed != true) return;
    await _runAction((api) async {
      final r = await api.post('/labor/payments/${widget.paymentId}/approve');
      if (r.data['success'] != true) {
        throw Exception(r.data['message'] ?? 'Failed to approve');
      }
      _showSnack(r.data['message']?.toString() ?? 'Payment approved');
    });
  }

  Future<void> _process() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final result = await showDialog<Map<String, String>>(
      context: context,
      builder: (ctx) => _ProcessPaymentDialog(isSwahili: isSwahili),
    );
    if (result == null) return;
    await _runAction((api) async {
      final r = await api.post(
        '/labor/payments/${widget.paymentId}/process',
        data: {
          'payment_reference': result['payment_reference'],
          if ((result['notes'] ?? '').isNotEmpty) 'notes': result['notes'],
        },
      );
      if (r.data['success'] != true) {
        throw Exception(r.data['message'] ?? 'Failed to process payment');
      }
      _showSnack(r.data['message']?.toString() ?? 'Payment processed');
    });
  }

  Future<void> _hold() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final reason = await showDialog<String>(
      context: context,
      builder: (ctx) => _ReasonDialog(
        title: isSwahili ? 'Shikilia Malipo' : 'Hold Payment',
        label: isSwahili ? 'Sababu ya kushikilia' : 'Hold reason',
        confirmLabel: isSwahili ? 'Shikilia' : 'Hold',
        minLength: 10,
        isSwahili: isSwahili,
      ),
    );
    if (reason == null) return;
    await _runAction((api) async {
      final r = await api.post(
        '/labor/payments/${widget.paymentId}/hold',
        data: {'hold_reason': reason},
      );
      if (r.data['success'] != true) {
        throw Exception(r.data['message'] ?? 'Failed to hold payment');
      }
      _showSnack(r.data['message']?.toString() ?? 'Payment put on hold');
    });
  }

  Future<void> _release() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await _confirm(
      title: isSwahili ? 'Toa Kizuizi' : 'Release Payment',
      message: isSwahili
          ? 'Rudisha awamu hii kutoka kwenye kizuizi hadi "due"?'
          : 'Release this phase from hold back to due?',
      confirmLabel: isSwahili ? 'Toa' : 'Release',
    );
    if (confirmed != true) return;
    await _runAction((api) async {
      final r = await api.post('/labor/payments/${widget.paymentId}/release');
      if (r.data['success'] != true) {
        throw Exception(r.data['message'] ?? 'Failed to release payment');
      }
      _showSnack(r.data['message']?.toString() ?? 'Payment released');
    });
  }

  Future<bool?> _confirm({
    required String title,
    required String message,
    required String confirmLabel,
  }) {
    final isSwahili = ref.read(isSwahiliProvider);
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(confirmLabel),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final theme = Theme.of(context);

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        Navigator.pop(context, _changed);
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(isSwahili ? 'Maelezo ya Malipo' : 'Payment Detail'),
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: _isLoading ? null : _loadPayment,
            ),
          ],
        ),
        body: _isLoading
            ? const LoadingWidget(message: 'Loading payment...')
            : _error != null
                ? _buildError(isSwahili)
                : _buildContent(theme, isSwahili),
        bottomNavigationBar: (_payment == null || _isLoading)
            ? null
            : _buildActionBar(isSwahili),
      ),
    );
  }

  Widget _buildError(bool isSwahili) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 48, color: Colors.red),
            const SizedBox(height: 12),
            Text(_error ?? '', textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _loadPayment,
              child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContent(ThemeData theme, bool isSwahili) {
    final p = _payment!;
    final status = (p['status'] ?? '').toString();
    final contract = p['contract'] as Map<String, dynamic>?;

    return RefreshIndicator(
      onRefresh: _loadPayment,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      p['phase_name']?.toString() ?? 'Phase',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${isSwahili ? 'Awamu' : 'Phase'} ${p['phase_number'] ?? ''}',
                      style: theme.textTheme.bodySmall,
                    ),
                  ],
                ),
              ),
              _statusChip(status),
            ],
          ),
          const SizedBox(height: 20),
          _amountCard(theme, p, isSwahili),
          const SizedBox(height: 16),
          _section(theme, isSwahili ? 'Maelezo' : 'Details', [
            _row(isSwahili ? 'Asilimia' : 'Percentage',
                '${p['percentage'] ?? 0}%'),
            _row(isSwahili ? 'Tarehe ya kukamilika' : 'Due date',
                p['due_date']?.toString() ?? '-'),
            if ((p['description'] ?? '').toString().isNotEmpty)
              _row(isSwahili ? 'Maelezo' : 'Description',
                  p['description'].toString()),
            if ((p['milestone_description'] ?? '').toString().isNotEmpty)
              _row(isSwahili ? 'Hatua' : 'Milestone',
                  p['milestone_description'].toString()),
          ]),
          const SizedBox(height: 16),
          _section(theme, isSwahili ? 'Mkataba' : 'Contract', [
            _row(isSwahili ? 'Namba ya mkataba' : 'Contract no.',
                contract?['contract_number']?.toString() ?? '-'),
            _row(isSwahili ? 'Mradi' : 'Project',
                contract?['project_name']?.toString() ?? '-'),
            _row(isSwahili ? 'Fundi' : 'Artisan',
                contract?['artisan_name']?.toString() ?? '-'),
          ]),
          if (status.toLowerCase() == 'paid') ...[
            const SizedBox(height: 16),
            _section(theme, isSwahili ? 'Malipo' : 'Disbursement', [
              _row(isSwahili ? 'Kumbukumbu' : 'Reference',
                  p['payment_reference']?.toString() ?? '-'),
              _row(isSwahili ? 'Imelipwa' : 'Paid at',
                  p['paid_at']?.toString() ?? '-'),
              _row(isSwahili ? 'Imelipwa na' : 'Paid by',
                  p['paid_by']?.toString() ?? '-'),
            ]),
          ],
          if ((p['notes'] ?? '').toString().isNotEmpty) ...[
            const SizedBox(height: 16),
            _section(theme, isSwahili ? 'Vidokezo' : 'Notes', [
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Text(p['notes'].toString()),
              ),
            ]),
          ],
          const SizedBox(height: 80),
        ],
      ),
    );
  }

  Widget _statusChip(String status) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: _statusColor(status),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        status.isEmpty ? '-' : status.toUpperCase(),
        style: const TextStyle(
          color: Colors.white,
          fontSize: 12,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  Widget _amountCard(ThemeData theme, Map<String, dynamic> p, bool isSwahili) {
    return Card(
      color: theme.colorScheme.primaryContainer,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(Icons.payments_outlined,
                color: theme.colorScheme.onPrimaryContainer),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isSwahili ? 'Kiasi' : 'Amount',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onPrimaryContainer,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _formatMoney(p['amount']),
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: theme.colorScheme.onPrimaryContainer,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _section(ThemeData theme, String title, List<Widget> children) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: TextStyle(fontSize: 13, color: Colors.grey[600]),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionBar(bool isSwahili) {
    final p = _payment!;
    final status = (p['status'] ?? '').toString().toLowerCase();
    final canApprove = p['can_approve'] == true;
    final canPay = p['can_pay'] == true;
    final isPaid = status == 'paid';
    final isHeld = status == 'held';

    final buttons = <Widget>[];

    if (canApprove) {
      buttons.add(_actionButton(
        icon: Icons.check_circle_outline,
        label: isSwahili ? 'Idhinisha' : 'Approve',
        color: Colors.blue,
        onPressed: _approve,
      ));
    }
    if (canPay) {
      buttons.add(_actionButton(
        icon: Icons.account_balance_wallet_outlined,
        label: isSwahili ? 'Lipa' : 'Process',
        color: Colors.green,
        onPressed: _process,
      ));
    }
    if (isHeld) {
      buttons.add(_actionButton(
        icon: Icons.lock_open_outlined,
        label: isSwahili ? 'Toa' : 'Release',
        color: Colors.teal,
        onPressed: _release,
      ));
    }
    if (!isPaid && !isHeld) {
      buttons.add(_actionButton(
        icon: Icons.pause_circle_outline,
        label: isSwahili ? 'Shikilia' : 'Hold',
        color: Colors.orange,
        onPressed: _hold,
      ));
    }

    if (buttons.isEmpty) return const SizedBox.shrink();

    return SafeArea(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          border: Border(
            top: BorderSide(color: Theme.of(context).dividerColor),
          ),
        ),
        child: _isSubmitting
            ? const Center(
                child: Padding(
                  padding: EdgeInsets.all(8),
                  child: CircularProgressIndicator(),
                ),
              )
            : Wrap(
                spacing: 8,
                runSpacing: 8,
                alignment: WrapAlignment.center,
                children: buttons,
              ),
      ),
    );
  }

  Widget _actionButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onPressed,
  }) {
    return FilledButton.icon(
      style: FilledButton.styleFrom(backgroundColor: color),
      icon: Icon(icon, size: 18),
      label: Text(label),
      onPressed: _isSubmitting ? null : onPressed,
    );
  }
}

/// Dialog collecting a payment reference (+ optional notes) for disbursement.
class _ProcessPaymentDialog extends StatefulWidget {
  final bool isSwahili;
  const _ProcessPaymentDialog({required this.isSwahili});

  @override
  State<_ProcessPaymentDialog> createState() => _ProcessPaymentDialogState();
}

class _ProcessPaymentDialogState extends State<_ProcessPaymentDialog> {
  final _formKey = GlobalKey<FormState>();
  final _refController = TextEditingController();
  final _notesController = TextEditingController();

  @override
  void dispose() {
    _refController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.isSwahili;
    return AlertDialog(
      title: Text(s ? 'Lipa Malipo' : 'Process Payment'),
      content: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextFormField(
              controller: _refController,
              decoration: InputDecoration(
                labelText: s ? 'Kumbukumbu ya malipo' : 'Payment reference',
                hintText: s ? 'k.m. namba ya risiti' : 'e.g. transaction ref',
              ),
              validator: (v) => (v == null || v.trim().isEmpty)
                  ? (s ? 'Inahitajika' : 'Required')
                  : null,
            ),
            const SizedBox(height: 8),
            TextFormField(
              controller: _notesController,
              decoration: InputDecoration(
                labelText: s ? 'Vidokezo (hiari)' : 'Notes (optional)',
              ),
              maxLines: 2,
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text(s ? 'Ghairi' : 'Cancel'),
        ),
        FilledButton(
          onPressed: () {
            if (_formKey.currentState?.validate() ?? false) {
              Navigator.pop(context, {
                'payment_reference': _refController.text.trim(),
                'notes': _notesController.text.trim(),
              });
            }
          },
          child: Text(s ? 'Lipa' : 'Process'),
        ),
      ],
    );
  }
}

/// Reason-collecting dialog (used for Hold) with a minimum length validator.
class _ReasonDialog extends StatefulWidget {
  final String title;
  final String label;
  final String confirmLabel;
  final int minLength;
  final bool isSwahili;

  const _ReasonDialog({
    required this.title,
    required this.label,
    required this.confirmLabel,
    required this.minLength,
    required this.isSwahili,
  });

  @override
  State<_ReasonDialog> createState() => _ReasonDialogState();
}

class _ReasonDialogState extends State<_ReasonDialog> {
  final _formKey = GlobalKey<FormState>();
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.isSwahili;
    return AlertDialog(
      title: Text(widget.title),
      content: Form(
        key: _formKey,
        child: TextFormField(
          controller: _controller,
          decoration: InputDecoration(labelText: widget.label),
          maxLines: 3,
          validator: (v) {
            final text = v?.trim() ?? '';
            if (text.isEmpty) return s ? 'Inahitajika' : 'Required';
            if (text.length < widget.minLength) {
              return s
                  ? 'Angalau herufi ${widget.minLength}'
                  : 'At least ${widget.minLength} characters';
            }
            return null;
          },
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text(s ? 'Ghairi' : 'Cancel'),
        ),
        FilledButton(
          onPressed: () {
            if (_formKey.currentState?.validate() ?? false) {
              Navigator.pop(context, _controller.text.trim());
            }
          },
          child: Text(widget.confirmLabel),
        ),
      ],
    );
  }
}
