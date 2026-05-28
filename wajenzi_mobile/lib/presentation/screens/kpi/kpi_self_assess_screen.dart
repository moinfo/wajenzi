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

class KpiSelfAssessScreen extends ConsumerStatefulWidget {
  final int reviewId;

  const KpiSelfAssessScreen({super.key, required this.reviewId});

  @override
  ConsumerState<KpiSelfAssessScreen> createState() =>
      _KpiSelfAssessScreenState();
}

class _KpiSelfAssessScreenState extends ConsumerState<KpiSelfAssessScreen> {
  final _rateControllers = <int, TextEditingController>{};
  final _commentControllers = <int, TextEditingController>{};
  final _achievements = TextEditingController();
  final _areas = TextEditingController();
  final _training = TextEditingController();
  final _employeeComments = TextEditingController();

  List<KpiRating> _ratings = const [];
  bool _initialized = false;
  bool _saving = false;

  @override
  void dispose() {
    for (final c in _rateControllers.values) {
      c.dispose();
    }
    for (final c in _commentControllers.values) {
      c.dispose();
    }
    _achievements.dispose();
    _areas.dispose();
    _training.dispose();
    _employeeComments.dispose();
    super.dispose();
  }

  void _hydrate(KpiReviewDetail d) {
    if (_initialized) return;
    _initialized = true;
    _ratings = d.allRatings;
    for (final r in _ratings) {
      _rateControllers[r.id] = TextEditingController(
        text: r.selfRate == null ? '' : _fmt(r.selfRate!),
      );
      _commentControllers[r.id] =
          TextEditingController(text: r.comment ?? '');
    }
    _achievements.text = d.footer.achievements;
    _areas.text = d.footer.areasOfImprovement;
    _training.text = d.footer.trainingNeeds;
    _employeeComments.text = d.footer.employeeComments;
  }

  String _fmt(double v) =>
      v == v.roundToDouble() ? v.toStringAsFixed(0) : v.toString();

  double? _rateOf(int id) {
    final text = _rateControllers[id]?.text.trim() ?? '';
    if (text.isEmpty) return null;
    final v = double.tryParse(text);
    if (v == null) return null;
    return v.clamp(0, 100);
  }

  double get _liveTotal => kpiWeightedScore(
        _ratings.map((r) => (weight: r.weight, rate: _rateOf(r.id))),
      );

  bool get _allRated => _ratings.every((r) => _rateOf(r.id) != null);

  List<Map<String, dynamic>> _ratingPayload() => [
        for (final r in _ratings)
          {
            'id': r.id,
            'self_rate': _rateOf(r.id),
            'comment': _commentControllers[r.id]?.text.trim() ?? '',
          },
      ];

  Future<void> _save({required bool submit}) async {
    final messenger = ScaffoldMessenger.of(context);
    if (submit && !_allRated) {
      messenger.showSnackBar(
        const SnackBar(
            content: Text('Please rate all KPIs before submitting.')),
      );
      return;
    }
    if (submit) {
      final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Submit Self-Assessment'),
          content: const Text(
              'Once submitted, this goes to your supervisor for review. '
              'Continue?'),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(ctx, false),
                child: const Text('Cancel')),
            TextButton(
                onPressed: () => Navigator.pop(ctx, true),
                child: const Text('Submit')),
          ],
        ),
      );
      if (ok != true) return;
    }

    setState(() => _saving = true);
    try {
      await ref.read(kpiApiProvider).saveSelfAssessment(
            widget.reviewId,
            ratings: _ratingPayload(),
            achievements: _achievements.text.trim(),
            areasOfImprovement: _areas.text.trim(),
            trainingNeeds: _training.text.trim(),
            employeeComments: _employeeComments.text.trim(),
            action: submit ? 'submit' : 'save',
          );
      ref.read(kpiReviewDetailProvider(widget.reviewId).notifier).refresh();
      ref.read(kpiListProvider(KpiTab.mine).notifier).refresh();
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(
          content: Text(submit ? 'Submitted for review.' : 'Draft saved.'),
        ),
      );
      if (context.canPop()) context.pop();
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(SnackBar(content: Text('Save failed: $e')));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(kpiReviewDetailProvider(widget.reviewId));

    return Scaffold(
      appBar: AppBar(title: Text('Self-Assessment', style: AppType.display(18))),
      body: state.when(
        loading: () => const LoadingWidget(message: 'Loading...'),
        error: (e, _) => CustomErrorWidget(
          message: 'Could not load this review.\n$e',
          onRetry: () =>
              ref.read(kpiReviewDetailProvider(widget.reviewId).notifier).refresh(),
        ),
        data: (d) {
          if (!d.permissions.canFill) {
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
            Text('Not Editable',
                style: AppType.display(16), textAlign: TextAlign.center),
            const SizedBox(height: 8),
            const Text(
              'This self-assessment can no longer be edited.',
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
    return ListView(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
      children: [
        for (final r in _ratings) _ratingCard(context, r),
        const SizedBox(height: 8),
        _narrativeField('Achievements', _achievements),
        _narrativeField('Areas of Improvement', _areas),
        _narrativeField('Training Needs', _training),
        _narrativeField('Employee Comments', _employeeComments),
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
            const SizedBox(height: 10),
            TextField(
              controller: _rateControllers[r.id],
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
              ],
              decoration: const InputDecoration(
                labelText: 'Self rating (0–100)',
                suffixText: '%',
                isDense: true,
              ),
              onChanged: (_) => setState(() {}),
            ),
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

  Widget _narrativeField(String label, TextEditingController controller) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextField(
        controller: controller,
        maxLines: 3,
        decoration: InputDecoration(labelText: label, alignLabelWithHint: true),
      ),
    );
  }

  Widget _stickyBar(BuildContext context) {
    final total = _liveTotal;
    return Material(
      elevation: 12,
      color: Theme.of(context).cardColor,
      child: SafeArea(
        top: false,
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
                    Text('${_ratings.where((r) => _rateOf(r.id) != null).length}'
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
                    child: OutlinedButton(
                      onPressed: _saving ? null : () => Navigator.maybePop(context),
                      child: const Text('Cancel'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _saving ? null : () => _save(submit: false),
                      icon: const Icon(Icons.save_outlined, size: 18),
                      label: const Text('Save'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: _saving ? null : () => _save(submit: true),
                      icon: _saving
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                  strokeWidth: 2, color: Colors.white))
                          : const Icon(Icons.send, size: 18),
                      label: const Text('Submit'),
                      style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.brandGreen),
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
}
