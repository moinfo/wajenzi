import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'labor_request_create_contract_screen.dart';

/// Full detail + lifecycle-action screen for a single Labor Request.
///
/// Pushed via Navigator; pops `true` when any mutation succeeds so the list can
/// refresh. Gates each action on the server-returned model-state flags
/// (`can_submit`, `can_approve`, `can_create_contract`, `has_contract`) and the
/// request `status` — there is no Spatie permission layer on labor.
class LaborRequestDetailScreen extends ConsumerStatefulWidget {
  final int requestId;
  const LaborRequestDetailScreen({super.key, required this.requestId});

  @override
  ConsumerState<LaborRequestDetailScreen> createState() =>
      _LaborRequestDetailScreenState();
}

class _LaborRequestDetailScreenState
    extends ConsumerState<LaborRequestDetailScreen> {
  Map<String, dynamic>? _request;
  bool _loading = true;
  bool _busy = false;
  bool _changed = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/labor/requests/${widget.requestId}');
      final data = response.data['data'] as Map<String, dynamic>?;
      if (!mounted) return;
      setState(() {
        _request = data;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final request = _request;

    return PopScope(
      canPop: true,
      onPopInvokedWithResult: (didPop, _) {},
      child: Scaffold(
        appBar: AppBar(
          title: Text(
            request?['request_number'] as String? ??
                (isSwahili ? 'Ombi la Labor' : 'Labor Request'),
          ),
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () => Navigator.of(context).pop(_changed),
          ),
        ),
        body: _loading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
            ? _errorView(isSwahili)
            : request == null
            ? Center(
                child: Text(isSwahili ? 'Hakuna data' : 'No data'),
              )
            : RefreshIndicator(
                onRefresh: _load,
                child: _detailBody(request, isSwahili, isDarkMode),
              ),
      ),
    );
  }

  Widget _errorView(bool isSwahili) => ListView(
    physics: const AlwaysScrollableScrollPhysics(),
    padding: const EdgeInsets.all(32),
    children: [
      const SizedBox(height: 80),
      const Icon(Icons.error_outline, size: 64, color: AppColors.error),
      const SizedBox(height: 16),
      Text(
        isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
      ),
      const SizedBox(height: 8),
      Text(
        '$_error',
        textAlign: TextAlign.center,
        style: const TextStyle(color: AppColors.textSecondary),
      ),
      const SizedBox(height: 24),
      Center(
        child: ElevatedButton.icon(
          onPressed: _load,
          icon: const Icon(Icons.refresh),
          label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
        ),
      ),
    ],
  );

  Widget _detailBody(
    Map<String, dynamic> r,
    bool isSwahili,
    bool isDarkMode,
  ) {
    final status = r['status'] as String? ?? '-';
    final currency = r['currency'] as String? ?? 'TZS';
    final materials = (r['materials_list'] as List? ?? const []).cast<dynamic>();
    final project = r['project'] as Map?;
    final artisan = r['artisan'] as Map?;
    final requester = r['requester'] as Map?;
    final approver = r['approver'] as Map?;
    final phase = r['construction_phase'] as Map?;
    final contract = r['contract'] as Map?;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        // Header with status
        Row(
          children: [
            Expanded(
              child: Text(
                r['request_number'] as String? ?? '-',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
            ),
            _statusChip(status, r['status_badge_class'] as String?),
          ],
        ),
        const SizedBox(height: 16),

        _card(isDarkMode, [
          _infoRow(
            isSwahili ? 'Mradi' : 'Project',
            project?['project_name'] as String? ?? '-',
            isDarkMode,
          ),
          if (phase != null)
            _infoRow(
              isSwahili ? 'Awamu' : 'Phase',
              phase['name'] as String? ?? '-',
              isDarkMode,
            ),
          _infoRow(
            isSwahili ? 'Fundi' : 'Artisan',
            artisan?['name'] as String? ?? (isSwahili ? 'Hajapangwa' : 'Unassigned'),
            isDarkMode,
          ),
          if (r['work_location'] != null &&
              (r['work_location'] as String).isNotEmpty)
            _infoRow(
              isSwahili ? 'Mahali' : 'Location',
              r['work_location'] as String,
              isDarkMode,
            ),
        ]),
        const SizedBox(height: 12),

        _card(isDarkMode, [
          _sectionLabel(isSwahili ? 'Maelezo ya Kazi' : 'Work Description', isDarkMode),
          const SizedBox(height: 4),
          Text(
            r['work_description'] as String? ?? '-',
            style: TextStyle(
              fontSize: 14,
              color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              if (r['start_date'] != null)
                Expanded(
                  child: _infoRow(
                    isSwahili ? 'Kuanza' : 'Start',
                    r['start_date'] as String,
                    isDarkMode,
                  ),
                ),
              if (r['end_date'] != null)
                Expanded(
                  child: _infoRow(
                    isSwahili ? 'Kumaliza' : 'End',
                    r['end_date'] as String,
                    isDarkMode,
                  ),
                ),
            ],
          ),
          if (r['estimated_duration_days'] != null)
            _infoRow(
              isSwahili ? 'Muda (siku)' : 'Duration (days)',
              '${r['estimated_duration_days']}',
              isDarkMode,
            ),
        ]),
        const SizedBox(height: 12),

        _card(isDarkMode, [
          _sectionLabel(isSwahili ? 'Malipo' : 'Payment', isDarkMode),
          const SizedBox(height: 8),
          _infoRow(
            isSwahili ? 'Kiasi Kilichopendekezwa' : 'Proposed Amount',
            '$currency ${_money(r['proposed_amount'])}',
            isDarkMode,
          ),
          if (r['negotiated_amount'] != null)
            _infoRow(
              isSwahili ? 'Kiasi Kilichokubaliwa' : 'Negotiated Amount',
              '$currency ${_money(r['negotiated_amount'])}',
              isDarkMode,
            ),
          if (r['approved_amount'] != null)
            _infoRow(
              isSwahili ? 'Kiasi Kilichoidhinishwa' : 'Approved Amount',
              '$currency ${_money(r['approved_amount'])}',
              isDarkMode,
            ),
          _infoRow(
            isSwahili ? 'Kiasi cha Mwisho' : 'Final Amount',
            '$currency ${_money(r['final_amount'])}',
            isDarkMode,
          ),
          if (r['payment_terms'] != null &&
              (r['payment_terms'] as String).isNotEmpty)
            _infoRow(
              isSwahili ? 'Masharti' : 'Payment Terms',
              r['payment_terms'] as String,
              isDarkMode,
            ),
        ]),
        const SizedBox(height: 12),

        _card(isDarkMode, [
          Row(
            children: [
              Text(
                isSwahili ? 'Vifaa Vimejumuishwa' : 'Materials Included',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const Spacer(),
              Icon(
                r['materials_included'] == true
                    ? Icons.check_circle
                    : Icons.cancel,
                size: 18,
                color: r['materials_included'] == true
                    ? AppColors.success
                    : (isDarkMode ? Colors.white38 : Colors.grey),
              ),
            ],
          ),
          if (materials.isNotEmpty) ...[
            const SizedBox(height: 8),
            ...materials.map((m) {
              final mm = Map<String, dynamic>.from(m as Map);
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 2),
                child: Row(
                  children: [
                    Icon(
                      Icons.circle,
                      size: 6,
                      color: isDarkMode ? Colors.white54 : AppColors.textHint,
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        mm['name']?.toString() ?? '-',
                        style: TextStyle(
                          fontSize: 13,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                    ),
                    if (mm['quantity'] != null)
                      Text(
                        'x${mm['quantity']}',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                  ],
                ),
              );
            }),
          ],
        ]),
        const SizedBox(height: 12),

        if (r['artisan_assessment'] != null &&
            (r['artisan_assessment'] as String).isNotEmpty) ...[
          _card(isDarkMode, [
            _sectionLabel(
              isSwahili ? 'Tathmini ya Fundi' : 'Artisan Assessment',
              isDarkMode,
            ),
            const SizedBox(height: 4),
            Text(
              r['artisan_assessment'] as String,
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
          ]),
          const SizedBox(height: 12),
        ],

        if (r['rejection_reason'] != null &&
            (r['rejection_reason'] as String).isNotEmpty) ...[
          _card(isDarkMode, [
            _sectionLabel(
              isSwahili ? 'Sababu ya Kukataliwa' : 'Rejection Reason',
              isDarkMode,
            ),
            const SizedBox(height: 4),
            Text(
              r['rejection_reason'] as String,
              style: const TextStyle(fontSize: 13, color: AppColors.error),
            ),
          ]),
          const SizedBox(height: 12),
        ],

        _card(isDarkMode, [
          if (requester != null)
            _infoRow(
              isSwahili ? 'Aliyeomba' : 'Requested by',
              requester['name'] as String? ?? '-',
              isDarkMode,
            ),
          if (approver != null)
            _infoRow(
              isSwahili ? 'Aliyeidhinisha' : 'Approved by',
              approver['name'] as String? ?? '-',
              isDarkMode,
            ),
          if (contract != null)
            _infoRow(
              isSwahili ? 'Mkataba' : 'Contract',
              contract['contract_number'] as String? ?? '-',
              isDarkMode,
            ),
        ]),

        const SizedBox(height: 24),
        _actions(r, isSwahili, isDarkMode),
        const SizedBox(height: 40),
      ],
    );
  }

  Widget _actions(Map<String, dynamic> r, bool isSwahili, bool isDarkMode) {
    final status = r['status'] as String? ?? '';
    final canSubmit = r['can_submit'] == true;
    final canApprove = r['can_approve'] == true;
    final canCreateContract = r['can_create_contract'] == true;
    final hasContract = r['has_contract'] == true;
    final canNegotiate = status != 'approved' && status != 'contracted';
    final canAssess = status != 'contracted';

    final buttons = <Widget>[];

    if (canSubmit) {
      buttons.add(
        _actionButton(
          label: isSwahili ? 'Wasilisha kwa Idhini' : 'Submit for Approval',
          icon: Icons.send,
          color: AppColors.primary,
          onPressed: _submit,
        ),
      );
    } else if (status == 'draft' && r['can_submit'] != true) {
      buttons.add(
        Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Text(
            isSwahili
                ? 'Panga fundi kabla ya kuwasilisha'
                : 'Assign an artisan before submitting',
            style: const TextStyle(fontSize: 12, color: AppColors.error),
          ),
        ),
      );
    }

    if (canApprove) {
      buttons.add(
        _actionButton(
          label: isSwahili ? 'Idhinisha' : 'Approve',
          icon: Icons.check_circle_outline,
          color: AppColors.success,
          onPressed: _approve,
        ),
      );
      buttons.add(
        _actionButton(
          label: isSwahili ? 'Kataa' : 'Reject',
          icon: Icons.cancel_outlined,
          color: AppColors.error,
          onPressed: _reject,
        ),
      );
    }

    if (canNegotiate) {
      buttons.add(
        _actionButton(
          label: isSwahili ? 'Rekodi Majadiliano' : 'Record Negotiation',
          icon: Icons.handshake_outlined,
          color: const Color(0xFF0891B2),
          onPressed: _negotiate,
          outlined: true,
        ),
      );
    }

    if (canAssess) {
      buttons.add(
        _actionButton(
          label: isSwahili ? 'Rekodi Tathmini' : 'Record Assessment',
          icon: Icons.assignment_outlined,
          color: const Color(0xFF6B7280),
          onPressed: _assess,
          outlined: true,
        ),
      );
    }

    if (canCreateContract && !hasContract) {
      buttons.add(
        _actionButton(
          label: isSwahili ? 'Tengeneza Mkataba' : 'Create Contract',
          icon: Icons.description_outlined,
          color: AppColors.primary,
          onPressed: () => _createContract(r),
        ),
      );
    }

    if (buttons.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          isSwahili ? 'Vitendo' : 'Actions',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
        const SizedBox(height: 12),
        ...buttons,
        if (_busy) ...[
          const SizedBox(height: 8),
          const Center(child: CircularProgressIndicator()),
        ],
      ],
    );
  }

  Widget _actionButton({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback onPressed,
    bool outlined = false,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: outlined
          ? OutlinedButton.icon(
              onPressed: _busy ? null : onPressed,
              icon: Icon(icon, size: 18),
              label: Text(label),
              style: OutlinedButton.styleFrom(
                foregroundColor: color,
                side: BorderSide(color: color),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            )
          : ElevatedButton.icon(
              onPressed: _busy ? null : onPressed,
              icon: Icon(icon, size: 18),
              label: Text(label),
              style: ElevatedButton.styleFrom(
                backgroundColor: color,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
    );
  }

  // ---- Action handlers ----

  Future<void> _submit() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await _confirm(
      title: isSwahili ? 'Wasilisha kwa Idhini' : 'Submit for Approval',
      message: isSwahili
          ? 'Je, una uhakika unataka kuwasilisha ombi hili kwa idhini?'
          : 'Submit this request for approval?',
    );
    if (confirmed != true) return;
    await _runAction(
      () => ref
          .read(apiClientProvider)
          .post('/labor/requests/${widget.requestId}/submit'),
      successMsg: isSwahili ? 'Imewasilishwa' : 'Submitted for approval',
    );
  }

  Future<void> _approve() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final amountController = TextEditingController();
    final commentController = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Idhinisha Ombi' : 'Approve Request'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: amountController,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: isSwahili
                    ? 'Kiasi Kilichoidhinishwa (hiari)'
                    : 'Approved Amount (optional)',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: commentController,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: isSwahili ? 'Maoni (hiari)' : 'Comment (optional)',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Idhinisha' : 'Approve'),
          ),
        ],
      ),
    );

    final amountText = amountController.text.trim();
    final commentText = commentController.text.trim();
    amountController.dispose();
    commentController.dispose();
    if (ok != true) return;

    final data = <String, dynamic>{};
    if (amountText.isNotEmpty) {
      final amount = double.tryParse(amountText);
      if (amount == null || amount < 0) {
        _snack(
          isSwahili ? 'Kiasi si sahihi' : 'Invalid amount',
          isError: true,
        );
        return;
      }
      data['approved_amount'] = amount;
    }
    if (commentText.isNotEmpty) data['comment'] = commentText;

    await _runAction(
      () => ref
          .read(apiClientProvider)
          .post('/labor/requests/${widget.requestId}/approve', data: data),
      successMsg: isSwahili ? 'Ombi limeidhinishwa' : 'Request approved',
    );
  }

  Future<void> _reject() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final reasonController = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Kataa Ombi' : 'Reject Request'),
        content: TextField(
          controller: reasonController,
          maxLines: 3,
          decoration: InputDecoration(
            labelText:
                isSwahili ? 'Sababu ya Kukataa *' : 'Rejection Reason *',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Kataa' : 'Reject'),
          ),
        ],
      ),
    );

    final reason = reasonController.text.trim();
    reasonController.dispose();
    if (ok != true) return;
    if (reason.isEmpty) {
      _snack(
        isSwahili ? 'Sababu inahitajika' : 'Rejection reason is required',
        isError: true,
      );
      return;
    }

    await _runAction(
      () => ref.read(apiClientProvider).post(
        '/labor/requests/${widget.requestId}/reject',
        data: {'comment': reason, 'rejection_reason': reason},
      ),
      successMsg: isSwahili ? 'Ombi limekataliwa' : 'Request rejected',
    );
  }

  Future<void> _negotiate() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final amountController = TextEditingController(
      text: _request?['negotiated_amount']?.toString() ?? '',
    );
    final assessmentController = TextEditingController();

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Rekodi Majadiliano' : 'Record Negotiation'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: amountController,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: isSwahili
                    ? 'Kiasi Kilichokubaliwa *'
                    : 'Negotiated Amount *',
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: assessmentController,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: isSwahili
                    ? 'Tathmini ya Fundi (hiari)'
                    : 'Artisan Assessment (optional)',
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Hifadhi' : 'Save'),
          ),
        ],
      ),
    );

    final amountText = amountController.text.trim();
    final assessmentText = assessmentController.text.trim();
    amountController.dispose();
    assessmentController.dispose();
    if (ok != true) return;

    final amount = double.tryParse(amountText);
    if (amount == null || amount < 0) {
      _snack(
        isSwahili ? 'Kiasi si sahihi' : 'Valid amount required',
        isError: true,
      );
      return;
    }

    final data = <String, dynamic>{'negotiated_amount': amount};
    if (assessmentText.isNotEmpty) data['artisan_assessment'] = assessmentText;

    await _runAction(
      () => ref
          .read(apiClientProvider)
          .post('/labor/requests/${widget.requestId}/negotiation', data: data),
      successMsg: isSwahili ? 'Majadiliano yamerekodiwa' : 'Negotiation recorded',
    );
  }

  Future<void> _assess() async {
    final isSwahili = ref.read(isSwahiliProvider);
    final controller = TextEditingController(
      text: _request?['artisan_assessment']?.toString() ?? '',
    );

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Rekodi Tathmini' : 'Record Assessment'),
        content: TextField(
          controller: controller,
          maxLines: 4,
          decoration: InputDecoration(
            labelText:
                isSwahili ? 'Tathmini ya Fundi *' : 'Artisan Assessment *',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Hifadhi' : 'Save'),
          ),
        ],
      ),
    );

    final text = controller.text.trim();
    controller.dispose();
    if (ok != true) return;
    if (text.isEmpty) {
      _snack(
        isSwahili ? 'Tathmini inahitajika' : 'Assessment is required',
        isError: true,
      );
      return;
    }

    await _runAction(
      () => ref.read(apiClientProvider).post(
        '/labor/requests/${widget.requestId}/assessment',
        data: {'artisan_assessment': text},
      ),
      successMsg: isSwahili ? 'Tathmini imerekodiwa' : 'Assessment recorded',
    );
  }

  Future<void> _createContract(Map<String, dynamic> r) async {
    final result = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => LaborRequestCreateContractScreen(request: r),
      ),
    );
    if (result == true) {
      _changed = true;
      await _load();
    }
  }

  // ---- Helpers ----

  Future<void> _runAction(
    Future<Response> Function() action, {
    required String successMsg,
  }) async {
    setState(() => _busy = true);
    try {
      await action();
      _changed = true;
      await _load();
      if (mounted) _snack(successMsg);
    } on DioException catch (e) {
      _snack(_dioMessage(e), isError: true);
    } catch (e) {
      _snack('$e', isError: true);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String _dioMessage(DioException e) {
    final data = e.response?.data;
    if (data is Map) {
      final msg = data['message']?.toString();
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
      if (msg != null && msg.isNotEmpty) return msg;
    }
    return e.message ?? 'Request failed';
  }

  Future<bool?> _confirm({required String title, required String message}) {
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
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Endelea' : 'Continue'),
          ),
        ],
      ),
    );
  }

  void _snack(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? AppColors.error : AppColors.success,
      ),
    );
  }

  Widget _card(bool isDarkMode, List<Widget> children) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      borderRadius: BorderRadius.circular(16),
      boxShadow: [
        BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10),
      ],
    ),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: children),
  );

  Widget _sectionLabel(String text, bool isDarkMode) => Text(
    text,
    style: TextStyle(
      fontSize: 14,
      fontWeight: FontWeight.w700,
      color: isDarkMode ? Colors.white : AppColors.textPrimary,
    ),
  );

  Widget _infoRow(String label, String value, bool isDarkMode) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 130,
          child: Text(
            label,
            style: TextStyle(
              fontSize: 13,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ),
      ],
    ),
  );

  Widget _statusChip(String status, String? badgeClass) {
    final color = _badgeColor(badgeClass);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        status.toUpperCase(),
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }

  String _money(dynamic value) {
    final d = value is num
        ? value.toDouble()
        : double.tryParse('$value') ?? 0;
    return NumberFormat('#,##0.00', 'en_US').format(d);
  }

  Color _badgeColor(String? badgeClass) {
    return switch (badgeClass) {
      'success' => const Color(0xFF16A34A),
      'warning' => const Color(0xFFF59E0B),
      'danger' => const Color(0xFFDC2626),
      'info' => const Color(0xFF0891B2),
      _ => const Color(0xFF6B7280),
    };
  }
}
