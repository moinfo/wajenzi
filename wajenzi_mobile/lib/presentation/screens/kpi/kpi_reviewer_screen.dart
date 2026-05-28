import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/datasources/remote/kpi_api.dart';
import '../../../data/models/kpi_common.dart';
import '../../../data/models/kpi_review_detail.dart';
import '../../providers/kpi_provider.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/loading_widget.dart';
import 'kpi_widgets.dart';

class KpiReviewerScreen extends ConsumerStatefulWidget {
  final int reviewId;

  const KpiReviewerScreen({super.key, required this.reviewId});

  @override
  ConsumerState<KpiReviewerScreen> createState() => _KpiReviewerScreenState();
}

class _KpiReviewerScreenState extends ConsumerState<KpiReviewerScreen> {
  final _supervisorControllers = <int, TextEditingController>{};
  final _overallControllers = <int, TextEditingController>{};
  final _commentControllers = <int, TextEditingController>{};
  final _stageComment = TextEditingController();

  List<KpiRating> _ratings = const [];
  String _stage = '';
  bool _initialized = false;
  bool _saving = false;

  /// Supervisor stage edits both supervisor_rate + overall_rate.
  bool get _editsSupervisor => _stage == 'supervisor';

  @override
  void dispose() {
    for (final c in _supervisorControllers.values) {
      c.dispose();
    }
    for (final c in _overallControllers.values) {
      c.dispose();
    }
    for (final c in _commentControllers.values) {
      c.dispose();
    }
    _stageComment.dispose();
    super.dispose();
  }

  void _hydrate(KpiReviewDetail d) {
    if (_initialized) return;
    _initialized = true;
    _stage = d.permissions.reviewStage;
    _ratings = d.allRatings;
    for (final r in _ratings) {
      _supervisorControllers[r.id] = TextEditingController(
        text: r.supervisorRate == null ? '' : _fmt(r.supervisorRate!),
      );
      _overallControllers[r.id] = TextEditingController(
        text: r.overallRate == null ? '' : _fmt(r.overallRate!),
      );
      _commentControllers[r.id] =
          TextEditingController(text: r.comment ?? '');
    }
  }

  String _fmt(double v) =>
      v == v.roundToDouble() ? v.toStringAsFixed(0) : v.toString();

  double? _parse(TextEditingController? c) {
    final text = c?.text.trim() ?? '';
    if (text.isEmpty) return null;
    final v = double.tryParse(text);
    if (v == null) return null;
    return v.clamp(0, 100);
  }

  /// The rate that defines this stage's weighted score.
  double? _stageRate(int id) => _editsSupervisor
      ? _parse(_supervisorControllers[id])
      : _parse(_overallControllers[id]);

  double get _liveTotal => kpiWeightedScore(
        _ratings.map((r) => (weight: r.weight, rate: _stageRate(r.id))),
      );

  bool get _allRated => _ratings.every((r) => _stageRate(r.id) != null);

  List<Map<String, dynamic>> _ratingPayload() => [
        for (final r in _ratings)
          {
            'id': r.id,
            if (_editsSupervisor)
              'supervisor_rate': _parse(_supervisorControllers[r.id]),
            'overall_rate': _parse(_overallControllers[r.id]),
            'comment': _commentControllers[r.id]?.text.trim() ?? '',
          },
      ];

  Future<String?> _askReason(String title) async {
    final controller = TextEditingController();
    final result = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          maxLines: 3,
          autofocus: true,
          decoration: const InputDecoration(
            hintText: 'Reason',
            border: OutlineInputBorder(),
          ),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, controller.text.trim()),
            child: const Text('Confirm'),
          ),
        ],
      ),
    );
    controller.dispose();
    return result;
  }

  Future<void> _runAction(String action) async {
    final messenger = ScaffoldMessenger.of(context);
    String? reason;

    if (action == 'approve' && !_allRated) {
      messenger.showSnackBar(
        const SnackBar(
            content: Text('Please rate all KPIs before approving.')),
      );
      return;
    }
    if (action == 'return' || action == 'reject') {
      reason = await _askReason(
          action == 'return' ? 'Return for Changes' : 'Reject Review');
      if (reason == null) return; // cancelled
      if (reason.isEmpty) {
        messenger.showSnackBar(
          const SnackBar(content: Text('A reason is required.')),
        );
        return;
      }
    }

    setState(() => _saving = true);
    try {
      await ref.read(kpiApiProvider).review(
            widget.reviewId,
            ratings: _ratingPayload(),
            stageComment: _stageComment.text.trim(),
            action: action,
            reason: reason,
          );
      ref.read(kpiReviewDetailProvider(widget.reviewId).notifier).refresh();
      ref.read(kpiListProvider(KpiTab.awaiting).notifier).refresh();
      ref.read(kpiListProvider(KpiTab.mine).notifier).refresh();
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(content: Text(_actionMessage(action))),
      );
      if (action != 'save' && context.canPop()) context.pop();
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(SnackBar(content: Text('Action failed: $e')));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  String _actionMessage(String action) => switch (action) {
        'save' => 'Progress saved.',
        'approve' => 'Approved and forwarded.',
        'return' => 'Returned for changes.',
        'reject' => 'Review rejected.',
        _ => 'Done.',
      };

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kpiReviewDetailProvider(widget.reviewId));

    return Scaffold(
      appBar: kpiAppBar(context: context, ref: ref, title: 'Review'),
      body: state.when(
        loading: () => const LoadingWidget(message: 'Loading...'),
        error: (e, _) => CustomErrorWidget(
          message: 'Could not load this review.\n$e',
          onRetry: () => ref
              .read(kpiReviewDetailProvider(widget.reviewId).notifier)
              .refresh(),
        ),
        data: (d) {
          if (!d.permissions.canReview) {
            return _readOnlyNotice(context);
          }
          _hydrate(d);
          return Column(
            children: [
              Expanded(child: _form(context)),
              _stickyBar(context),
            ],
          );
        },
      ),
    );
  }

  Widget _readOnlyNotice(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.lock_outline,
                size: 56, color: AppColors.brandYellow),
            const SizedBox(height: 12),
            Text('No Review Action',
                style: AppType.display(16), textAlign: TextAlign.center),
            const SizedBox(height: 8),
            const Text(
              'You do not have a pending review action on this review.',
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 20),
            OutlinedButton.icon(
              onPressed: () => Navigator.of(context).maybePop(),
              icon: const Icon(Icons.arrow_back),
              label: const Text('Go Back'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _form(BuildContext context) {
    final stageLabel = _stage.isEmpty ? 'Reviewer' : _titleCase(_stage);
    return ListView(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
      children: [
        Container(
          padding: const EdgeInsets.all(12),
          margin: const EdgeInsets.only(bottom: 12),
          decoration: BoxDecoration(
            color: AppColors.brandBlue.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Row(
            children: [
              const Icon(Icons.info_outline,
                  size: 18, color: AppColors.brandBlue),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  _editsSupervisor
                      ? '$stageLabel stage: set supervisor and overall ratings.'
                      : '$stageLabel stage: set the overall ratings.',
                  style: const TextStyle(fontSize: 12),
                ),
              ),
            ],
          ),
        ),
        for (final r in _ratings) _ratingCard(context, r),
        const SizedBox(height: 8),
        TextField(
          controller: _stageComment,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'Stage Comment',
            alignLabelWithHint: true,
          ),
        ),
        const SizedBox(height: 8),
      ],
    );
  }

  Widget _ratingCard(BuildContext context, KpiRating r) {
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.7);
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(r.kpa,
                      style: const TextStyle(
                          fontWeight: FontWeight.w700, fontSize: 14)),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.brandBlue.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text('Weight ${r.weight.toStringAsFixed(0)}',
                      style: const TextStyle(
                          fontSize: 11, fontWeight: FontWeight.w700)),
                ),
              ],
            ),
            if (r.measure.isNotEmpty)
              Text('Measure: ${r.measure}',
                  style: TextStyle(fontSize: 12, color: muted)),
            if (r.target.isNotEmpty)
              Text('Target: ${r.target}',
                  style: TextStyle(fontSize: 12, color: muted)),
            const SizedBox(height: 8),
            // Read-only self rating.
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.grey.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                'Self: ${r.selfRate == null ? '—' : '${_fmt(r.selfRate!)}%'}',
                style: const TextStyle(
                    fontSize: 12, fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(height: 10),
            if (_editsSupervisor) ...[
              _rateField(
                  _supervisorControllers[r.id]!, 'Supervisor rating (0–100)'),
              const SizedBox(height: 8),
            ],
            _rateField(_overallControllers[r.id]!, 'Overall rating (0–100)'),
            const SizedBox(height: 8),
            TextField(
              controller: _commentControllers[r.id],
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Comment',
                isDense: true,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _rateField(TextEditingController controller, String label) {
    return TextField(
      controller: controller,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      inputFormatters: [
        FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
      ],
      decoration: InputDecoration(
        labelText: label,
        suffixText: '%',
        isDense: true,
      ),
      onChanged: (_) => setState(() {}),
    );
  }

  Widget _stickyBar(BuildContext context) {
    final total = _liveTotal;
    return Material(
      elevation: 12,
      color: Theme.of(context).cardColor,
      child: SafeArea(
        top: false,
        // Lift the action bar above the outer curved bottom nav.
        minimum: const EdgeInsets.only(bottom: 90),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  const Text('Live Total',
                      style: TextStyle(fontWeight: FontWeight.w700)),
                  const SizedBox(width: 8),
                  Text(total.toStringAsFixed(1),
                      style: AppType.display(20,
                          weight: FontWeight.w800,
                          color: kpiGradeColor(total))),
                  const SizedBox(width: 8),
                  KpiGradePill(score: total),
                  const Spacer(),
                  if (!_allRated)
                    Text(
                        '${_ratings.where((r) => _stageRate(r.id) != null).length}'
                        '/${_ratings.length} rated',
                        style: TextStyle(
                            fontSize: 11,
                            color: Theme.of(context)
                                .textTheme
                                .bodyMedium
                                ?.color
                                ?.withValues(alpha: 0.7))),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _saving ? null : () => _runAction('save'),
                      icon: const Icon(Icons.save_outlined, size: 18),
                      label: const Text('Save'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton.icon(
                      onPressed: _saving ? null : () => _runAction('approve'),
                      icon: _saving
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : const Icon(Icons.check, size: 18),
                      label: const Text('Approve & Forward'),
                      style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.brandGreen),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _saving ? null : () => _runAction('return'),
                      icon: const Icon(Icons.undo, size: 18),
                      label: const Text('Return'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFFE67E22),
                        side: const BorderSide(color: Color(0xFFE67E22)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _saving ? null : () => _runAction('reject'),
                      icon: const Icon(Icons.close, size: 18),
                      label: const Text('Reject'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.error,
                        side: const BorderSide(color: AppColors.error),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _titleCase(String s) =>
      s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);
}
