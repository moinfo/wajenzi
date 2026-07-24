import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/utils/permissions.dart';
import '../../../data/datasources/remote/kpi_api.dart';
import '../../../data/models/kpi_template.dart';
import '../../providers/kpi_template_provider.dart';
import '../../widgets/common/empty_state_widget.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/loading_widget.dart';
import 'kpi_widgets.dart';

/// The 5 roles the web `authorizeTemplates()` admits. UI visibility gate only
/// — the API enforces the same set server-side.
const List<String> kpiTemplateRoles = [
  'System Administrator',
  'HR Generalist',
  'Managing Director',
  'CEO',
  'Chief Executive Officer',
];

bool canManageKpiTemplates(WidgetRef ref) {
  for (final r in kpiTemplateRoles) {
    if (hasRole(ref, r)) return true;
  }
  return false;
}

class KpiTemplatesListScreen extends ConsumerWidget {
  const KpiTemplatesListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final allowed = canManageKpiTemplates(ref);
    final state = ref.watch(kpiTemplatesProvider);
    final notifier = ref.read(kpiTemplatesProvider.notifier);

    return Scaffold(
      appBar: kpiAppBar(
        context: context,
        ref: ref,
        title: 'KPI Templates',
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: notifier.refresh,
          ),
        ],
      ),
      floatingActionButton: allowed
          ? FloatingActionButton.extended(
              onPressed: () => _openNewTemplateSheet(context, ref),
              icon: const Icon(Icons.add),
              label: const Text('New Template'),
            )
          : null,
      body: !allowed
          ? const _NotAllowedNotice()
          : state.when(
              loading: () =>
                  const LoadingWidget(message: 'Loading templates...'),
              error: (e, _) => CustomErrorWidget(
                message: 'Could not load KPI templates.\n$e',
                onRetry: notifier.refresh,
              ),
              data: (resp) {
                if (resp.templates.isEmpty) {
                  return ListView(
                    children: [
                      SizedBox(
                        height: MediaQuery.of(context).size.height * 0.7,
                        child: const EmptyStateWidget(
                          icon: Icons.description_outlined,
                          message: 'No KPI templates yet.\n'
                              'Tap "New Template" to create one.',
                        ),
                      ),
                    ],
                  );
                }
                return RefreshIndicator(
                  onRefresh: notifier.refresh,
                  color: AppColors.brandGreen,
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(12, 12, 12, 96),
                    itemCount: resp.templates.length,
                    itemBuilder: (context, i) =>
                        _TemplateCard(template: resp.templates[i]),
                  ),
                );
              },
            ),
    );
  }

  Future<void> _openNewTemplateSheet(
      BuildContext context, WidgetRef ref) async {
    final resp = ref.read(kpiTemplatesProvider).valueOrNull;
    final roles = resp?.availableRoles ?? const <KpiRoleOption>[];
    if (roles.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
              'Every role already has a template. No roles are available.'),
        ),
      );
      return;
    }
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) => _NewTemplateForm(roles: roles),
    );
  }
}

class _NotAllowedNotice extends StatelessWidget {
  const _NotAllowedNotice();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.lock_outline,
                size: 64, color: AppColors.brandYellow),
            const SizedBox(height: 16),
            Text('Not Authorized',
                style: AppType.display(18), textAlign: TextAlign.center),
            const SizedBox(height: 8),
            Text(
              'You do not have permission to manage KPI templates.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: Theme.of(context)
                    .textTheme
                    .bodyMedium
                    ?.color
                    ?.withValues(alpha: 0.8),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TemplateCard extends StatelessWidget {
  final KpiTemplateSummary template;

  const _TemplateCard({required this.template});

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context)
        .textTheme
        .bodyMedium
        ?.color
        ?.withValues(alpha: 0.75);
    final balanced = template.weightBalanced;
    final weightColor = balanced ? AppColors.brandGreen : AppColors.error;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => context.push('/performance/templates/${template.id}'),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      template.name,
                      style: AppType.display(15, weight: FontWeight.w700),
                    ),
                  ),
                  if (!template.isActive)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.draft.withValues(alpha: 0.16),
                        borderRadius: BorderRadius.circular(20),
                        border:
                            Border.all(color: AppColors.draft.withValues(alpha: 0.5)),
                      ),
                      child: const Text(
                        'Inactive',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: AppColors.draft,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                '${template.role} · ${kpiFrequencyLabel(template.frequency)}',
                style: TextStyle(fontSize: 12, color: muted),
              ),
              Text(
                template.code,
                style: TextStyle(
                  fontSize: 11,
                  color: muted,
                  fontFeatures: const [],
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(Icons.list_alt_rounded,
                      size: 16, color: AppColors.brandBlue),
                  const SizedBox(width: 4),
                  Text(
                    '${template.itemCount} item${template.itemCount == 1 ? '' : 's'}',
                    style: const TextStyle(
                        fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(width: 14),
                  Icon(
                    balanced ? Icons.check_circle_outline : Icons.warning_amber_rounded,
                    size: 16,
                    color: weightColor,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'Weight ${template.totalWeight.toStringAsFixed(template.totalWeight == template.totalWeight.roundToDouble() ? 0 : 1)}%',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: weightColor,
                    ),
                  ),
                ],
              ),
              if (!balanced)
                Padding(
                  padding: const EdgeInsets.only(top: 6),
                  child: Text(
                    'Weights should total 100%.',
                    style: TextStyle(
                      fontSize: 11,
                      color: AppColors.error,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NewTemplateForm extends ConsumerStatefulWidget {
  final List<KpiRoleOption> roles;

  const _NewTemplateForm({required this.roles});

  @override
  ConsumerState<_NewTemplateForm> createState() => _NewTemplateFormState();
}

class _NewTemplateFormState extends ConsumerState<_NewTemplateForm> {
  final _nameController = TextEditingController();
  int? _roleId;
  String _frequency = 'quarterly';
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _roleId = widget.roles.isNotEmpty ? widget.roles.first.id : null;
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final messenger = ScaffoldMessenger.of(context);
    final name = _nameController.text.trim();
    if (name.isEmpty) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Please enter a template name.')),
      );
      return;
    }
    if (_roleId == null) {
      messenger.showSnackBar(
        const SnackBar(content: Text('Please select a role.')),
      );
      return;
    }
    setState(() => _submitting = true);
    try {
      final id = await ref.read(kpiApiProvider).createTemplate(
            name: name,
            roleId: _roleId!,
            frequency: _frequency,
          );
      ref.read(kpiTemplatesProvider.notifier).refresh();
      if (!mounted) return;
      Navigator.of(context).pop();
      context.push('/performance/templates/$id');
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(content: Text('Failed to create template: $e')),
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 4, 16, 16 + bottomInset),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('New KPI Template',
                style: AppType.display(18, weight: FontWeight.w700)),
            const SizedBox(height: 4),
            Text(
              'Section A (30%, 14 common items) is auto-filled. Add Section B '
              'departmental KPIs (70%) afterwards.',
              style: TextStyle(
                fontSize: 12,
                color: Theme.of(context)
                    .textTheme
                    .bodyMedium
                    ?.color
                    ?.withValues(alpha: 0.75),
              ),
            ),
            const SizedBox(height: 16),
            _label('Name'),
            const SizedBox(height: 6),
            TextField(
              controller: _nameController,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                hintText: 'e.g. Site Engineer',
                prefixIcon: Icon(Icons.badge_outlined),
              ),
            ),
            const SizedBox(height: 16),
            _label('Role'),
            const SizedBox(height: 6),
            DropdownButtonFormField<int>(
              initialValue: _roleId,
              isExpanded: true,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.group_outlined),
              ),
              items: [
                for (final r in widget.roles)
                  DropdownMenuItem(value: r.id, child: Text(r.name)),
              ],
              onChanged: (v) => setState(() => _roleId = v),
            ),
            const SizedBox(height: 16),
            _label('Frequency'),
            const SizedBox(height: 6),
            DropdownButtonFormField<String>(
              initialValue: _frequency,
              isExpanded: true,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.repeat_rounded),
              ),
              items: [
                for (final f in kpiTemplateFrequencies)
                  DropdownMenuItem(
                      value: f, child: Text(kpiFrequencyLabel(f))),
              ],
              onChanged: (v) =>
                  setState(() => _frequency = v ?? _frequency),
            ),
            const SizedBox(height: 24),
            SizedBox(
              height: 50,
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _submitting ? null : _submit,
                icon: _submitting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white),
                      )
                    : const Icon(Icons.check),
                label: Text(_submitting ? 'Creating...' : 'Create Template'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.brandGreen,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _label(String text) => Text(
        text.toUpperCase(),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          letterSpacing: 1,
          color: Theme.of(context)
              .textTheme
              .bodyMedium
              ?.color
              ?.withValues(alpha: 0.7),
        ),
      );
}
