import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/models/kpi_review_list_item.dart';
import '../../providers/kpi_provider.dart';
import '../../widgets/common/empty_state_widget.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/loading_widget.dart';
import 'kpi_widgets.dart';

class KpiListScreen extends ConsumerStatefulWidget {
  const KpiListScreen({super.key});

  @override
  ConsumerState<KpiListScreen> createState() => _KpiListScreenState();
}

// TickerProviderStateMixin (not SingleTicker…) because the TabController is
// re-created when `can_see_all` flips between 2 and 3 tabs.
class _KpiListScreenState extends ConsumerState<KpiListScreen>
    with TickerProviderStateMixin {
  TabController? _tabController;
  List<KpiTab> _tabs = const [KpiTab.mine, KpiTab.awaiting];

  @override
  void initState() {
    super.initState();
    _setupTabs(_tabs);
  }

  void _setupTabs(List<KpiTab> tabs) {
    final previousIndex = _tabController?.index ?? 0;
    _tabController?.dispose();
    _tabs = tabs;
    _tabController = TabController(
      length: tabs.length,
      vsync: this,
      initialIndex: previousIndex.clamp(0, tabs.length - 1),
    );
    _tabController!.addListener(() {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _tabController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // The "mine" tab carries the can_see_all flag, used to reveal the All tab.
    final mineState = ref.watch(kpiListProvider(KpiTab.mine));
    final canSeeAll = mineState.valueOrNull?.canSeeAll ?? false;

    final desiredTabs = canSeeAll
        ? const [KpiTab.mine, KpiTab.awaiting, KpiTab.all]
        : const [KpiTab.mine, KpiTab.awaiting];
    if (desiredTabs.length != _tabs.length) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() => _setupTabs(desiredTabs));
      });
    }

    final counts = mineState.valueOrNull?.counts;

    return Scaffold(
      appBar: kpiAppBar(
        context: context,
        ref: ref,
        title: 'My Performance',
        bottom: TabBar(
          controller: _tabController,
          isScrollable: false,
          indicatorColor: AppColors.brandYellow,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: [
            for (final t in _tabs)
              Tab(
                child: Text(_tabLabel(t, counts)),
              ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/performance/create'),
        icon: const Icon(Icons.add),
        label: const Text('New Review'),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [for (final t in _tabs) _KpiListTab(tab: t)],
      ),
    );
  }

  String _tabLabel(KpiTab t, KpiCounts? counts) {
    if (counts == null) return t.label;
    final n = switch (t) {
      KpiTab.mine => counts.mineOpen,
      KpiTab.awaiting => counts.awaiting,
      KpiTab.all => 0,
    };
    if (t == KpiTab.all || n <= 0) return t.label;
    return '${t.label} ($n)';
  }
}

class _KpiListTab extends ConsumerWidget {
  final KpiTab tab;

  const _KpiListTab({required this.tab});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(kpiListProvider(tab));
    final notifier = ref.read(kpiListProvider(tab).notifier);

    return RefreshIndicator(
      onRefresh: notifier.refresh,
      color: AppColors.brandGreen,
      child: state.when(
        loading: () => const LoadingWidget(message: 'Loading reviews...'),
        error: (e, _) => ListView(
          // ListView so pull-to-refresh works even on error.
          children: [
            SizedBox(
              height: MediaQuery.of(context).size.height * 0.7,
              child: CustomErrorWidget(
                message: 'Could not load performance reviews.\n$e',
                onRetry: notifier.refresh,
              ),
            ),
          ],
        ),
        data: (resp) {
          if (resp.reviews.isEmpty) {
            return ListView(
              children: [
                SizedBox(
                  height: MediaQuery.of(context).size.height * 0.7,
                  child: EmptyStateWidget(
                    icon: Icons.assessment_outlined,
                    message: tab == KpiTab.awaiting
                        ? 'No reviews are awaiting your action.'
                        : 'No performance reviews yet.',
                  ),
                ),
              ],
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(12, 12, 12, 96),
            itemCount: resp.reviews.length,
            itemBuilder: (context, i) =>
                _KpiReviewCard(item: resp.reviews[i]),
          );
        },
      ),
    );
  }
}

class _KpiReviewCard extends StatelessWidget {
  final KpiReviewListItem item;

  const _KpiReviewCard({required this.item});

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.75);
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => context.push('/performance/${item.id}'),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      item.employee.name.isEmpty
                          ? item.reviewNumber
                          : item.employee.name,
                      style: AppType.display(15, weight: FontWeight.w700),
                    ),
                  ),
                  KpiStatusChip(
                    status: item.status,
                    label: item.statusLabel,
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                '${item.reviewNumber} · ${item.template.name}',
                style: TextStyle(fontSize: 12, color: muted),
              ),
              Text(
                item.periodLabel,
                style: TextStyle(fontSize: 12, color: muted),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(Icons.bar_chart_rounded,
                      size: 18, color: AppColors.brandGreen),
                  const SizedBox(width: 6),
                  Text(
                    'Overall ${item.totalOverallScore.toStringAsFixed(1)}',
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(width: 10),
                  KpiGradePill(
                    score: item.totalOverallScore,
                    labelOverride: item.gradeLabel,
                  ),
                  const Spacer(),
                  if (item.canFill)
                    _actionHint(Icons.edit_note, 'Fill', AppColors.brandBlue),
                  if (item.canReview)
                    _actionHint(
                        Icons.rate_review, 'Review', AppColors.brandYellow),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _actionHint(IconData icon, String label, Color color) {
    return Padding(
      padding: const EdgeInsets.only(left: 6),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 2),
          Text(label,
              style: TextStyle(
                  fontSize: 11, color: color, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
