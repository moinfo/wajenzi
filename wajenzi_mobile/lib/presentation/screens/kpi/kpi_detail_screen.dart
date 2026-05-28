import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../../data/datasources/remote/kpi_api.dart';
import '../../../data/models/kpi_review_detail.dart';
import '../../providers/kpi_provider.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/loading_widget.dart';
import 'kpi_widgets.dart';

class KpiDetailScreen extends ConsumerWidget {
  final int reviewId;

  const KpiDetailScreen({super.key, required this.reviewId});

  int _completedStages(KpiReviewDetail d) {
    final ts = d.timestamps;
    int n = 0;
    if (ts.selfSubmittedAt != null) n++;
    if (ts.supervisorReviewedAt != null) n++;
    if (ts.mdReviewedAt != null) n++;
    if (ts.completedAt != null) n++;
    return n;
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(kpiReviewDetailProvider(reviewId));
    final notifier = ref.read(kpiReviewDetailProvider(reviewId).notifier);

    return Scaffold(
      appBar: AppBar(
        title: Text('Review', style: AppType.display(18)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: notifier.refresh,
          ),
        ],
      ),
      body: state.when(
        loading: () => const LoadingWidget(message: 'Loading review...'),
        error: (e, _) => CustomErrorWidget(
          message: 'Could not load this review.\n$e',
          onRetry: notifier.refresh,
        ),
        data: (d) => RefreshIndicator(
          onRefresh: notifier.refresh,
          color: AppColors.brandGreen,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
            children: [
              _headerCard(context, d),
              const SizedBox(height: 12),
              for (final s in d.sections) ...[
                _sectionCard(context, s),
                const SizedBox(height: 12),
              ],
              _footerCard(context, d),
              const SizedBox(height: 12),
              _actions(context, ref, d),
            ],
          ),
        ),
      ),
    );
  }

  Widget _headerCard(BuildContext context, KpiReviewDetail d) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(d.reviewNumber,
                      style: AppType.display(16, weight: FontWeight.w700)),
                ),
                KpiStatusChip(status: d.status, label: d.statusLabel),
              ],
            ),
            const SizedBox(height: 8),
            _kv(context, Icons.person_outline, d.employee.name,
                sub: d.employee.department),
            _kv(context, Icons.calendar_month_outlined, d.periodLabel),
            _kv(context, Icons.supervisor_account_outlined,
                d.supervisor?.name ?? 'No supervisor'),
            _kv(context, Icons.description_outlined, d.template.name),
            const SizedBox(height: 14),
            KpiStageProgress(completed: _completedStages(d)),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: KpiScoreTile(
                      label: 'Self', score: d.totalSelfScore),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: KpiScoreTile(
                      label: 'Supervisor', score: d.totalSupervisorScore),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: KpiScoreTile(
                    label: 'Overall',
                    score: d.totalOverallScore,
                    highlight: true,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Center(
              child: KpiGradePill(
                score: d.totalOverallScore,
                labelOverride: d.gradeLabel,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _kv(BuildContext context, IconData icon, String value,
      {String? sub}) {
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.75);
    if (value.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.brandGreen),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              sub != null && sub.isNotEmpty ? '$value · $sub' : value,
              style: TextStyle(fontSize: 13, color: muted),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionCard(BuildContext context, KpiSection s) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    s.title.isEmpty ? s.code : s.title,
                    style: AppType.display(14, weight: FontWeight.w700),
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.brandBlue.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    'Wt ${s.weightTotal.toStringAsFixed(0)}',
                    style: const TextStyle(
                        fontSize: 11, fontWeight: FontWeight.w700),
                  ),
                ),
              ],
            ),
            const Divider(height: 18),
            for (final r in s.ratings) _ratingRow(context, r),
          ],
        ),
      ),
    );
  }

  Widget _ratingRow(BuildContext context, KpiRating r) {
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.7);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(r.kpa,
              style: const TextStyle(
                  fontWeight: FontWeight.w700, fontSize: 13)),
          if (r.measure.isNotEmpty)
            Text('Measure: ${r.measure}',
                style: TextStyle(fontSize: 12, color: muted)),
          if (r.target.isNotEmpty)
            Text('Target: ${r.target}',
                style: TextStyle(fontSize: 12, color: muted)),
          const SizedBox(height: 6),
          Wrap(
            spacing: 8,
            runSpacing: 4,
            children: [
              _pct('Weight', r.weight, isWeight: true),
              _pct('Self', r.selfRate),
              _pct('Supervisor', r.supervisorRate),
              _pct('Overall', r.overallRate),
            ],
          ),
          if (r.comment != null && r.comment!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 4),
              child: Text('“${r.comment}”',
                  style: TextStyle(
                      fontSize: 12,
                      fontStyle: FontStyle.italic,
                      color: muted)),
            ),
        ],
      ),
    );
  }

  Widget _pct(String label, double? value, {bool isWeight = false}) {
    final text = value == null ? '—' : value.toStringAsFixed(isWeight ? 0 : 1);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        '$label: $text${isWeight ? '' : '%'}',
        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
      ),
    );
  }

  Widget _footerCard(BuildContext context, KpiReviewDetail d) {
    final f = d.footer;
    final items = <(String, String)>[
      ('Achievements', f.achievements),
      ('Areas of Improvement', f.areasOfImprovement),
      ('Training Needs', f.trainingNeeds),
      ('Employee Comments', f.employeeComments),
      ('Supervisor Comments', f.supervisorComments),
      ('MD Comments', f.mdComments),
      ('CEO Comments', f.ceoComments),
    ].where((e) => e.$2.trim().isNotEmpty).toList();

    if (items.isEmpty) return const SizedBox.shrink();
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.8);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Narrative', style: AppType.display(14, weight: FontWeight.w700)),
            const Divider(height: 18),
            for (final it in items)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(it.$1,
                        style: const TextStyle(
                            fontWeight: FontWeight.w700, fontSize: 12)),
                    const SizedBox(height: 2),
                    Text(it.$2,
                        style: TextStyle(fontSize: 13, color: muted)),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _actions(BuildContext context, WidgetRef ref, KpiReviewDetail d) {
    final p = d.permissions;
    final buttons = <Widget>[];

    if (p.canFill) {
      buttons.add(_btn(
        'Fill Self-Assessment',
        Icons.edit_note,
        AppColors.brandBlue,
        () => context.push('/performance/${d.id}/self'),
      ));
    }
    if (p.canReview) {
      final stage = p.reviewStage.isEmpty ? 'Stage' : _titleCase(p.reviewStage);
      buttons.add(_btn(
        'Open $stage Review',
        Icons.rate_review,
        AppColors.brandGreen,
        () => context.push('/performance/${d.id}/review'),
      ));
    }
    if (p.canRecall) {
      buttons.add(_btn(
        'Recall',
        Icons.undo,
        AppColors.brandYellow,
        () => _confirmRecall(context, ref, d.id),
        foreground: AppColors.brandBlue,
      ));
    }
    if (d.pdfUrl != null && d.pdfUrl!.isNotEmpty) {
      buttons.add(_btn(
        'View PDF',
        Icons.picture_as_pdf,
        AppColors.brandBlue,
        () => ExternalLauncherService.openUri(Uri.parse(d.pdfUrl!)),
        outlined: true,
      ));
    }

    if (buttons.isEmpty) return const SizedBox.shrink();
    return Column(
      children: [
        for (final b in buttons)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: SizedBox(width: double.infinity, height: 48, child: b),
          ),
      ],
    );
  }

  Widget _btn(String label, IconData icon, Color color, VoidCallback onTap,
      {bool outlined = false, Color? foreground}) {
    if (outlined) {
      return OutlinedButton.icon(
        onPressed: onTap,
        icon: Icon(icon),
        label: Text(label),
        style: OutlinedButton.styleFrom(
          foregroundColor: color,
          side: BorderSide(color: color),
        ),
      );
    }
    return ElevatedButton.icon(
      onPressed: onTap,
      icon: Icon(icon),
      label: Text(label),
      style: ElevatedButton.styleFrom(
        backgroundColor: color,
        foregroundColor: foreground ?? Colors.white,
      ),
    );
  }

  Future<void> _confirmRecall(
      BuildContext context, WidgetRef ref, int id) async {
    final messenger = ScaffoldMessenger.of(context);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Recall Review'),
        content: const Text(
            'Recall this review back to draft so you can edit it again?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Recall')),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref.read(kpiApiProvider).recall(id);
      ref.read(kpiReviewDetailProvider(id).notifier).refresh();
      ref.read(kpiListProvider(KpiTab.mine).notifier).refresh();
      messenger.showSnackBar(
        const SnackBar(content: Text('Review recalled to draft.')),
      );
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('Recall failed: $e')));
    }
  }

  String _titleCase(String s) =>
      s.isEmpty ? s : s[0].toUpperCase() + s.substring(1);
}
