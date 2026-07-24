import 'package:cached_network_image/cached_network_image.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

/// Detail view for a single labor inspection.
///
/// Loads `GET /labor/inspections/{id}` and surfaces the write actions the API
/// exposes, gated on server-returned model-state flags/status:
///  - Submit  (draft, `can_submit`)            -> POST inspections/{id}/submit
///  - Verify  (status == pending, Supervisor)  -> POST inspections/{id}/approve
///  - Approve (status == verified, MD)         -> POST inspections/{id}/approve
///  - Reject  (status pending|verified)        -> POST inspections/{id}/reject
///
/// The same approve endpoint serves both RingleSoft steps; the server enforces
/// the per-step role and returns 403 when the caller may not act. Popping with
/// `true` tells the list screen to refresh.
class LaborInspectionDetailScreen extends ConsumerStatefulWidget {
  final int inspectionId;

  const LaborInspectionDetailScreen({super.key, required this.inspectionId});

  @override
  ConsumerState<LaborInspectionDetailScreen> createState() =>
      _LaborInspectionDetailScreenState();
}

class _LaborInspectionDetailScreenState
    extends ConsumerState<LaborInspectionDetailScreen> {
  bool _isLoading = true;
  bool _isActing = false;
  bool _didChange = false;
  Map<String, dynamic>? _inspection;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/labor/inspections/${widget.inspectionId}');

      if (response.statusCode == 200) {
        final data = response.data['data'] as Map<String, dynamic>? ?? const {};
        setState(() {
          _inspection = data;
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = 'Failed to load inspection';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = _messageFromError(e, 'Error loading inspection');
        _isLoading = false;
      });
    }
  }

  String _messageFromError(Object e, String fallback) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map && data['message'] is String) {
        return data['message'] as String;
      }
    }
    final text = e.toString();
    if (text.contains('403')) return 'You are not allowed to perform this action.';
    if (text.contains('401') || text.contains('Unauthorized')) {
      return 'Authentication required. Please login again.';
    }
    return fallback;
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'approved':
        return Colors.green;
      case 'verified':
        return Colors.blue;
      case 'pending':
        return Colors.orange;
      case 'rejected':
        return Colors.red;
      case 'draft':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  Color _getQualityColor(String quality) {
    switch (quality.toLowerCase()) {
      case 'excellent':
        return Colors.green;
      case 'good':
        return Colors.blue;
      case 'acceptable':
        return Colors.orange;
      case 'poor':
      case 'unacceptable':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Color _getResultColor(String result) {
    switch (result.toLowerCase()) {
      case 'pass':
        return Colors.green;
      case 'conditional':
        return Colors.orange;
      case 'fail':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Future<void> _submit() async {
    await _runAction(
      confirmTitle: 'Submit for Approval',
      confirmBody:
          'Submit this inspection into the approval workflow? You will not be able to edit it afterwards.',
      confirmLabel: 'Submit',
      request: (api) =>
          api.post('/labor/inspections/${widget.inspectionId}/submit'),
      successMessage: 'Inspection submitted for approval.',
    );
  }

  Future<void> _approve(String actionLabel) async {
    final comment = await _promptForText(
      title: '$actionLabel Inspection',
      hint: 'Optional comment',
      required: false,
    );
    // Null means the dialog was dismissed -> abort.
    if (comment == null) return;

    await _runAction(
      request: (api) => api.post(
        '/labor/inspections/${widget.inspectionId}/approve',
        data: comment.isEmpty ? null : {'comment': comment},
      ),
      successMessage: 'Inspection ${actionLabel.toLowerCase()}d.',
    );
  }

  Future<void> _reject() async {
    final reason = await _promptForText(
      title: 'Reject Inspection',
      hint: 'Reason for rejection (required)',
      required: true,
    );
    if (reason == null || reason.isEmpty) return;

    await _runAction(
      request: (api) => api.post(
        '/labor/inspections/${widget.inspectionId}/reject',
        data: {'reason': reason},
      ),
      successMessage: 'Inspection rejected.',
    );
  }

  Future<void> _runAction({
    required Future<Response> Function(ApiClient api) request,
    required String successMessage,
    String? confirmTitle,
    String? confirmBody,
    String? confirmLabel,
  }) async {
    if (_isActing) return;

    if (confirmTitle != null) {
      final confirmed = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: Text(confirmTitle),
          content: Text(confirmBody ?? ''),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: Text(confirmLabel ?? 'Confirm'),
            ),
          ],
        ),
      );
      if (confirmed != true) return;
    }

    setState(() => _isActing = true);

    try {
      final api = ref.read(apiClientProvider);
      final response = await request(api);

      if (response.statusCode == 200 || response.statusCode == 201) {
        _didChange = true;
        final data = response.data['data'];
        if (data is Map<String, dynamic>) {
          setState(() => _inspection = data);
        } else {
          await _load();
        }
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(successMessage)),
          );
        }
      } else {
        _showError('Action failed. Please try again.');
      }
    } catch (e) {
      _showError(_messageFromError(e, 'Action failed. Please try again.'));
    } finally {
      if (mounted) setState(() => _isActing = false);
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  Future<String?> _promptForText({
    required String title,
    required String hint,
    required bool required,
  }) async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (ctx) {
        String? errorText;
        return StatefulBuilder(
          builder: (ctx, setDialogState) => AlertDialog(
            title: Text(title),
            content: TextField(
              controller: controller,
              maxLines: 3,
              autofocus: true,
              decoration: InputDecoration(
                hintText: hint,
                errorText: errorText,
                border: const OutlineInputBorder(),
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('Cancel'),
              ),
              FilledButton(
                onPressed: () {
                  final value = controller.text.trim();
                  if (required && value.isEmpty) {
                    setDialogState(() => errorText = 'This field is required');
                    return;
                  }
                  Navigator.pop(ctx, value);
                },
                child: const Text('Confirm'),
              ),
            ],
          ),
        );
      },
    );
  }

  void _viewPhoto(String url) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.black,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(
          children: [
            Center(
              child: InteractiveViewer(
                child: CachedNetworkImage(
                  imageUrl: url,
                  fit: BoxFit.contain,
                  placeholder: (c, u) => const Padding(
                    padding: EdgeInsets.all(40),
                    child: CircularProgressIndicator(),
                  ),
                  errorWidget: (c, u, e) => const Padding(
                    padding: EdgeInsets.all(40),
                    child: Icon(Icons.broken_image, color: Colors.white, size: 48),
                  ),
                ),
              ),
            ),
            Positioned(
              top: 0,
              right: 0,
              child: IconButton(
                icon: const Icon(Icons.close, color: Colors.white),
                onPressed: () => Navigator.pop(ctx),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        Navigator.of(context).pop(_didChange);
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(isSwahili ? 'Ukaguzi' : 'Inspection'),
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: _isLoading ? null : _load,
            ),
          ],
        ),
        body: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? _buildError()
                : _buildContent(isSwahili),
        bottomNavigationBar:
            _inspection == null ? null : _buildActionBar(isSwahili),
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 56, color: Colors.red),
            const SizedBox(height: 12),
            Text(_error ?? 'Error', textAlign: TextAlign.center),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: _load, child: const Text('Retry')),
          ],
        ),
      ),
    );
  }

  Widget _buildContent(bool isSwahili) {
    final inspection = _inspection!;
    final contract = inspection['contract'] as Map<String, dynamic>? ?? {};
    final inspector = inspection['inspector'] as Map<String, dynamic>? ?? {};
    final paymentPhase =
        inspection['payment_phase'] as Map<String, dynamic>? ?? {};
    final photos = (inspection['photos'] as List<dynamic>? ?? [])
        .whereType<String>()
        .toList();
    final status = (inspection['status'] ?? 'draft').toString();

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Header
          Row(
            children: [
              Expanded(
                child: Text(
                  inspection['inspection_number']?.toString() ?? 'Inspection',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              _statusChip(status),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            inspection['inspection_date']?.toString() ?? '',
            style: TextStyle(color: Colors.grey[600]),
          ),
          const SizedBox(height: 20),

          // Contract section
          _sectionCard(
            title: isSwahili ? 'Mkataba' : 'Contract',
            children: [
              _row('Contract #', contract['contract_number']?.toString()),
              _row('Project', contract['project_name']?.toString()),
              _row('Artisan', contract['artisan_name']?.toString()),
              if (contract['status'] != null)
                _row('Contract Status', contract['status']?.toString()),
            ],
          ),

          // Inspection details
          _sectionCard(
            title: isSwahili ? 'Maelezo' : 'Details',
            children: [
              _row('Type',
                  inspection['inspection_type']?.toString().toUpperCase()),
              _rowWidget(
                'Completion',
                _completionWidget(inspection['completion_percentage']),
              ),
              if (inspection['work_quality'] != null)
                _rowWidget(
                  'Work Quality',
                  _badge(
                    inspection['work_quality'].toString(),
                    _getQualityColor(inspection['work_quality'].toString()),
                  ),
                ),
              if (inspection['result'] != null)
                _rowWidget(
                  'Result',
                  _badge(
                    inspection['result'].toString(),
                    _getResultColor(inspection['result'].toString()),
                  ),
                ),
              _row(
                'Scope Compliance',
                _boolLabel(inspection['scope_compliance']),
              ),
              _row('Defects Found', inspection['defects_found']?.toString()),
              _row(
                'Rectification Required',
                _boolLabel(inspection['rectification_required']),
              ),
              if ((inspection['rectification_notes']?.toString() ?? '')
                  .isNotEmpty)
                _row('Rectification Notes',
                    inspection['rectification_notes']?.toString()),
              if ((inspection['notes']?.toString() ?? '').isNotEmpty)
                _row('Notes', inspection['notes']?.toString()),
            ],
          ),

          // Inspector / payment phase
          if (inspector['name'] != null || paymentPhase['id'] != null)
            _sectionCard(
              title: isSwahili ? 'Nyingine' : 'Other',
              children: [
                if (inspector['name'] != null)
                  _row('Inspector', inspector['name']?.toString()),
                if (paymentPhase['id'] != null)
                  _row(
                    'Payment Phase',
                    '${paymentPhase['phase_number'] ?? ''} ${paymentPhase['phase_name'] ?? ''}'
                        .trim(),
                  ),
              ],
            ),

          // Photos
          if (photos.isNotEmpty)
            _sectionCard(
              title: '${isSwahili ? 'Picha' : 'Photos'} (${photos.length})',
              children: [
                GridView.count(
                  crossAxisCount: 3,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 8,
                  crossAxisSpacing: 8,
                  children: photos.map((p) {
                    final url = AppConfig.portalUrl(p);
                    return GestureDetector(
                      onTap: () => _viewPhoto(url),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: CachedNetworkImage(
                          imageUrl: url,
                          fit: BoxFit.cover,
                          placeholder: (c, u) => Container(
                            color: Colors.grey.withValues(alpha: 0.2),
                          ),
                          errorWidget: (c, u, e) => Container(
                            color: Colors.grey.withValues(alpha: 0.2),
                            child: const Icon(Icons.broken_image),
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ],
            ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildActionBar(bool isSwahili) {
    final inspection = _inspection!;
    final status = (inspection['status'] ?? 'draft').toString();
    final canSubmit = inspection['can_submit'] == true;
    final canDecide = status == 'pending' || status == 'verified';
    // Same approve endpoint serves both steps; label by the step the current
    // status implies (pending -> Supervisor VERIFY, verified -> MD APPROVE).
    final approveLabel = status == 'pending' ? 'Verify' : 'Approve';

    if (!canSubmit && !canDecide) {
      return const SizedBox.shrink();
    }

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: _isActing
            ? const Center(
                child: Padding(
                  padding: EdgeInsets.all(8),
                  child: CircularProgressIndicator(),
                ),
              )
            : Row(
                children: [
                  if (canSubmit)
                    Expanded(
                      child: FilledButton.icon(
                        icon: const Icon(Icons.send),
                        label: Text(isSwahili ? 'Wasilisha' : 'Submit'),
                        onPressed: _submit,
                      ),
                    ),
                  if (canDecide) ...[
                    Expanded(
                      child: OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.red,
                        ),
                        icon: const Icon(Icons.close),
                        label: Text(isSwahili ? 'Kataa' : 'Reject'),
                        onPressed: _reject,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton.icon(
                        icon: const Icon(Icons.check),
                        label: Text(approveLabel),
                        onPressed: () => _approve(approveLabel),
                      ),
                    ),
                  ],
                ],
              ),
      ),
    );
  }

  // ---- small UI helpers ----

  Widget _statusChip(String status) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: _getStatusColor(status),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        status.toUpperCase(),
        style: const TextStyle(
          color: Colors.white,
          fontSize: 12,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  Widget _badge(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }

  Widget _completionWidget(dynamic value) {
    final pct = value is num ? value.toDouble() : 0.0;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('${pct.toStringAsFixed(pct % 1 == 0 ? 0 : 1)}%'),
        const SizedBox(height: 4),
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: (pct / 100).clamp(0.0, 1.0),
            minHeight: 6,
            backgroundColor: Colors.grey.withValues(alpha: 0.3),
          ),
        ),
      ],
    );
  }

  Widget _sectionCard({required String title, required List<Widget> children}) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.primary,
                letterSpacing: 0.5,
              ),
            ),
            const Divider(),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String? value) {
    if (value == null || value.isEmpty) return const SizedBox.shrink();
    return _rowWidget(label, Text(value));
  }

  Widget _rowWidget(String label, Widget value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey[600],
              ),
            ),
          ),
          Expanded(child: DefaultTextStyle.merge(
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
            child: value,
          )),
        ],
      ),
    );
  }

  String? _boolLabel(dynamic value) {
    if (value == null) return null;
    final truthy = value == true || value == 1 || value == '1' || value == 'true';
    return truthy ? 'Yes' : 'No';
  }
}
