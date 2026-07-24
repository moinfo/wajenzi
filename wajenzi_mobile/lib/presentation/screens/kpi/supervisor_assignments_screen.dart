import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/utils/permissions.dart';
import '../../../data/datasources/remote/supervisor_assignment_api.dart';
import '../../providers/supervisor_assignment_provider.dart';
import '../../widgets/common/empty_state_widget.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/loading_widget.dart';
import 'kpi_widgets.dart';

/// Roles allowed to manage the KPI reviewer matrix — mirrors the web
/// SupervisorAssignmentController::ensureAllowed() set (includes
/// 'General Manager'). This is a UX gate only; the API enforces the same set.
const List<String> _kSupervisorAssignmentRoles = [
  'System Administrator',
  'Managing Director',
  'CEO',
  'Chief Executive Officer',
  'HR Generalist',
  'General Manager',
];

class SupervisorAssignmentsScreen extends ConsumerWidget {
  const SupervisorAssignmentsScreen({super.key});

  bool _authorized(WidgetRef ref) =>
      _kSupervisorAssignmentRoles.any((r) => hasRole(ref, r));

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authorized = _authorized(ref);

    if (!authorized) {
      return Scaffold(
        appBar: kpiAppBar(
          context: context,
          ref: ref,
          title: 'Supervisor Assignments',
        ),
        body: const EmptyStateWidget(
          icon: Icons.lock_outline,
          message:
              'You are not authorized to manage supervisor assignments.',
        ),
      );
    }

    final state = ref.watch(supervisorAssignmentProvider);
    final notifier = ref.read(supervisorAssignmentProvider.notifier);

    return Scaffold(
      appBar: kpiAppBar(
        context: context,
        ref: ref,
        title: 'Supervisor Assignments',
      ),
      floatingActionButton: state.maybeWhen(
        data: (_) => _SaveButton(),
        orElse: () => null,
      ),
      body: RefreshIndicator(
        onRefresh: notifier.refresh,
        color: AppColors.brandGreen,
        child: state.when(
          loading: () =>
              const LoadingWidget(message: 'Loading staff...'),
          error: (e, _) => ListView(
            children: [
              SizedBox(
                height: MediaQuery.of(context).size.height * 0.7,
                child: CustomErrorWidget(
                  message:
                      'Could not load supervisor assignments.\n$e',
                  onRetry: notifier.refresh,
                ),
              ),
            ],
          ),
          data: (data) => _MatrixBody(data: data),
        ),
      ),
    );
  }
}

class _MatrixBody extends ConsumerWidget {
  final SupervisorAssignmentData data;

  const _MatrixBody({required this.data});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (data.users.isEmpty) {
      return ListView(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height * 0.7,
            child: const EmptyStateWidget(
              icon: Icons.people_outline,
              message: 'No active staff to assign.',
            ),
          ),
        ],
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 96),
      itemCount: data.users.length + 1,
      itemBuilder: (context, i) {
        if (i == 0) return _StatsHeader(stats: data.stats);
        final user = data.users[i - 1];
        return _StaffRow(user: user, candidates: data.candidates);
      },
    );
  }
}

class _StatsHeader extends StatelessWidget {
  final SupervisorAssignmentStats stats;

  const _StatsHeader({required this.stats});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Expanded(
            child: _StatTile(
              label: 'Total',
              value: stats.total,
              color: AppColors.brandBlue,
              icon: Icons.groups_rounded,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _StatTile(
              label: 'Assigned',
              value: stats.assigned,
              color: AppColors.brandGreen,
              icon: Icons.check_circle_rounded,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _StatTile(
              label: 'Missing',
              value: stats.missing,
              color: stats.missing > 0
                  ? AppColors.error
                  : AppColors.brandGreen,
              icon: Icons.error_outline_rounded,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatTile extends StatelessWidget {
  final String label;
  final int value;
  final Color color;
  final IconData icon;

  const _StatTile({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.30)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(height: 6),
          Text(
            '$value',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: Theme.of(context)
                  .textTheme
                  .bodyMedium
                  ?.color
                  ?.withValues(alpha: 0.7),
            ),
          ),
        ],
      ),
    );
  }
}

class _StaffRow extends ConsumerWidget {
  final SupervisorAssignmentUser user;
  final List<SupervisorCandidate> candidates;

  const _StaffRow({required this.user, required this.candidates});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Watch the provider so the row re-renders after edits/saves.
    ref.watch(supervisorAssignmentProvider);
    final notifier = ref.read(supervisorAssignmentProvider.notifier);

    final selected =
        notifier.currentSupervisorFor(user.id, user.supervisorId);
    final dirty = selected != user.supervisorId;

    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.7);

    // Candidates excluding the staff member themselves (no self-supervision).
    final options =
        candidates.where((c) => c.id != user.id).toList();
    // The current supervisor may not be an active-staff candidate (e.g. a
    // manager or an inactive user), so ensure the selected value always has a
    // matching dropdown item — otherwise DropdownButtonFormField asserts.
    final optionIds = options.map((c) => c.id).toSet();
    final needsCurrentFallback = selected != null && !optionIds.contains(selected);

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: dirty
            ? BorderSide(color: AppColors.brandYellow, width: 1.5)
            : BorderSide.none,
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    user.name,
                    style: AppType.display(14, weight: FontWeight.w700),
                  ),
                ),
                if (dirty)
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: AppColors.brandYellow.withValues(alpha: 0.20),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      'Unsaved',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: AppColors.brandYellow.withValues(alpha: 1),
                      ),
                    ),
                  ),
              ],
            ),
            if (user.email != null && user.email!.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text(
                user.email!,
                style: TextStyle(fontSize: 11, color: muted),
              ),
            ],
            const SizedBox(height: 10),
            DropdownButtonFormField<int?>(
              initialValue: selected,
              isExpanded: true,
              decoration: InputDecoration(
                labelText: 'Supervisor',
                prefixIcon: const Icon(Icons.supervisor_account_outlined,
                    size: 20),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                contentPadding: const EdgeInsets.symmetric(
                    horizontal: 12, vertical: 8),
                isDense: true,
              ),
              items: [
                const DropdownMenuItem<int?>(
                  value: null,
                  child: Text('— None —'),
                ),
                if (needsCurrentFallback)
                  DropdownMenuItem<int?>(
                    value: selected,
                    child: Text(
                      user.supervisorName ?? 'Supervisor #$selected',
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                for (final c in options)
                  DropdownMenuItem<int?>(
                    value: c.id,
                    child: Text(
                      c.name,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
              ],
              onChanged: (value) => notifier.setSupervisor(
                  user.id, value, user.supervisorId),
            ),
          ],
        ),
      ),
    );
  }
}

class _SaveButton extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    ref.watch(supervisorAssignmentProvider);
    final notifier = ref.read(supervisorAssignmentProvider.notifier);
    final dirty = notifier.hasPendingChanges;
    final saving = notifier.saving;

    return FloatingActionButton.extended(
      onPressed: (!dirty || saving)
          ? null
          : () async {
              final messenger = ScaffoldMessenger.of(context);
              try {
                final changed = await notifier.save();
                messenger.showSnackBar(
                  SnackBar(
                    content: Text(
                        'Saved supervisor assignments for $changed staff.'),
                    backgroundColor: AppColors.brandGreen,
                  ),
                );
              } catch (e) {
                messenger.showSnackBar(
                  SnackBar(
                    content: Text('Save failed: $e'),
                    backgroundColor: AppColors.error,
                  ),
                );
              }
            },
      backgroundColor:
          (!dirty || saving) ? Colors.grey : AppColors.brandGreen,
      icon: saving
          ? const SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: Colors.white,
              ),
            )
          : const Icon(Icons.save_rounded),
      label: Text(saving ? 'Saving...' : 'Save'),
    );
  }
}
