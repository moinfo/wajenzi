// Generic list screen for Structural Design and Service Design.
//
// Both features share the same shape (header + ordered stages), so this widget
// is parameterised by [EngineeringDesignKind] and used twice with thin
// wrappers (`StructuralDesignScreen`, `ServiceDesignScreen`).

import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';
import 'engineering_design_shared.dart';

class DesignListScreen extends ConsumerStatefulWidget {
  final EngineeringDesignKind kind;
  const DesignListScreen({super.key, required this.kind});

  @override
  ConsumerState<DesignListScreen> createState() => _DesignListScreenState();
}

class _DesignListScreenState extends ConsumerState<DesignListScreen> {
  final _searchCtrl = TextEditingController();
  String _search = '';

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final listAsync = ref.watch(designListProvider(widget.kind));
    final filter = ref.watch(designFilterProvider(widget.kind));

    final title = language == AppLanguage.swahili
        ? widget.kind.titleSw()
        : widget.kind.titleEn();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(title),
        actions: [
          IconButton(
            tooltip: trDesign(language, en: 'Filter', sw: 'Chuja'),
            icon: const Icon(Icons.filter_list_rounded),
            onPressed: () => _showFilterSheet(context, filter),
          ),
        ],
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton.extended(
          onPressed: () => _showCreateModal(context),
          icon: const Icon(Icons.add_rounded),
          label: Text(trDesign(language, en: 'New', sw: 'Mpya')),
        ),
      ),
      body: RefreshIndicator(
        color: AppColors.brandGreen,
        onRefresh: () async => ref.invalidate(designListProvider(widget.kind)),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              child: TextField(
                controller: _searchCtrl,
                onChanged: (v) => setState(() => _search = v.trim().toLowerCase()),
                decoration: InputDecoration(
                  hintText: trDesign(language,
                      en: 'Search by project or engineer...',
                      sw: 'Tafuta kwa mradi au mhandisi...'),
                  prefixIcon: const Icon(Icons.search_rounded),
                  suffixIcon: _search.isEmpty
                      ? null
                      : IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchCtrl.clear();
                            setState(() => _search = '');
                          },
                        ),
                  filled: true,
                  fillColor: isDarkMode
                      ? Colors.white.withValues(alpha: 0.05)
                      : Colors.grey.withValues(alpha: 0.08),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
              ),
            ),
            if (filter.status != null ||
                filter.projectId != null ||
                filter.assignedToMe)
              _activeFiltersBar(language, filter),
            Expanded(
              child: listAsync.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.error_outline,
                            size: 56, color: AppColors.error),
                        const SizedBox(height: 12),
                        Text(designErrorMessage(e, language),
                            textAlign: TextAlign.center),
                        const SizedBox(height: 12),
                        ElevatedButton.icon(
                          onPressed: () => ref
                              .invalidate(designListProvider(widget.kind)),
                          icon: const Icon(Icons.refresh),
                          label: Text(trDesign(language,
                              en: 'Retry', sw: 'Jaribu tena')),
                        ),
                      ],
                    ),
                  ),
                ),
                data: (payload) {
                  final items = (payload['items'] as List)
                      .cast<Map<String, dynamic>>();
                  final filtered = _search.isEmpty
                      ? items
                      : items.where((d) {
                          final haystack = [
                            d['project_name'] ?? '',
                            d['assigned_engineer'] ?? '',
                            d['document_number'] ?? '',
                          ].join(' ').toLowerCase();
                          return haystack.contains(_search);
                        }).toList();

                  if (filtered.isEmpty) {
                    return ListView(
                      // wrap in ListView so RefreshIndicator works
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.6,
                          child: Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.architecture_rounded,
                                    size: 56, color: Colors.grey[400]),
                                const SizedBox(height: 12),
                                Text(
                                  trDesign(language,
                                      en: items.isEmpty
                                          ? 'No designs yet'
                                          : 'No designs match the search',
                                      sw: items.isEmpty
                                          ? 'Hakuna ubunifu bado'
                                          : 'Hakuna matokeo yanayolingana'),
                                  style: TextStyle(color: Colors.grey[600]),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(12, 8, 12, 100),
                    itemCount: filtered.length,
                    itemBuilder: (context, i) => _DesignCard(
                      kind: widget.kind,
                      design: filtered[i],
                      language: language,
                      isDarkMode: isDarkMode,
                      onTap: () => _showDetail(context, filtered[i]['id'] as int),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _activeFiltersBar(AppLanguage language, DesignFilter filter) {
    final chips = <Widget>[];
    if (filter.assignedToMe) {
      chips.add(
        InputChip(
          label: Text(trDesign(language, en: 'Assigned to me', sw: 'Zangu')),
          onDeleted: () =>
              ref.read(designFilterProvider(widget.kind).notifier).state =
                  filter.copyWith(assignedToMe: false),
        ),
      );
    }
    if (filter.status != null) {
      chips.add(
        InputChip(
          label: Text(designStatusLabel(filter.status, language)),
          onDeleted: () =>
              ref.read(designFilterProvider(widget.kind).notifier).state =
                  filter.copyWith(clearStatus: true),
        ),
      );
    }
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
      child: Wrap(spacing: 8, runSpacing: 6, children: chips),
    );
  }

  void _showFilterSheet(BuildContext context, DesignFilter current) {
    final language = ref.read(currentLanguageProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.all(20),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                trDesign(language, en: 'Filters', sw: 'Vichujio'),
                style: TextStyle(
                    fontSize: 17,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary),
              ),
              const SizedBox(height: 14),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(trDesign(language,
                    en: 'Only assigned to me',
                    sw: 'Zilizonipangiwa pekee')),
                value: current.assignedToMe,
                onChanged: (v) {
                  ref.read(designFilterProvider(widget.kind).notifier).state =
                      current.copyWith(assignedToMe: v);
                  Navigator.pop(ctx);
                },
              ),
              const SizedBox(height: 8),
              Text(
                trDesign(language, en: 'Status', sw: 'Hali'),
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  for (final s in const [
                    'pending',
                    'in_progress',
                    'submitted',
                    'approved',
                    'rejected',
                  ])
                    ChoiceChip(
                      label: Text(designStatusLabel(s, language)),
                      selected: current.status == s,
                      onSelected: (selected) {
                        ref
                                .read(designFilterProvider(widget.kind).notifier)
                                .state =
                            current.copyWith(
                                status: selected ? s : null,
                                clearStatus: !selected);
                        Navigator.pop(ctx);
                      },
                    ),
                ],
              ),
              const SizedBox(height: 14),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    ref.read(designFilterProvider(widget.kind).notifier).state =
                        const DesignFilter();
                    Navigator.pop(ctx);
                  },
                  icon: const Icon(Icons.refresh),
                  label: Text(trDesign(language,
                      en: 'Clear filters', sw: 'Futa vichujio')),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ── Detail bottom sheet ──────────────────────────────────────────────────

  void _showDetail(BuildContext context, int id) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.92,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (_, scrollCtrl) => _DesignDetailSheet(
          kind: widget.kind,
          id: id,
          scrollController: scrollCtrl,
        ),
      ),
    );
  }

  void _showCreateModal(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.4,
        maxChildSize: 0.95,
        builder: (_, scrollCtrl) => _DesignCreateForm(
          kind: widget.kind,
          scrollController: scrollCtrl,
          onCreated: () =>
              ref.invalidate(designListProvider(widget.kind)),
        ),
      ),
    );
  }
}

// ── List card ──────────────────────────────────────────────────────────────

class _DesignCard extends StatelessWidget {
  final EngineeringDesignKind kind;
  final Map<String, dynamic> design;
  final AppLanguage language;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _DesignCard({
    required this.kind,
    required this.design,
    required this.language,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final docNo = (design['document_number'] as String?) ?? '#${design['id']}';
    final projectName = (design['project_name'] as String?) ??
        trDesign(language, en: 'Unassigned project', sw: 'Mradi haijapangwa');
    final engineer = (design['assigned_engineer'] as String?) ??
        trDesign(language, en: 'Unassigned', sw: 'Haijapangwa');
    final stagesTotal = (design['stages_total'] as num?)?.toInt() ?? 0;
    final stagesDone = (design['stages_completed'] as num?)?.toInt() ?? 0;
    final progress = stagesTotal == 0 ? 0.0 : stagesDone / stagesTotal;

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      gradient: AppColors.primaryGradient,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      kind == EngineeringDesignKind.structural
                          ? Icons.architecture_rounded
                          : Icons.electrical_services_rounded,
                      color: Colors.white,
                      size: 22,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(projectName,
                            style: AppType.display(14, weight: FontWeight.w700),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis),
                        const SizedBox(height: 2),
                        Text(
                          '$docNo · $engineer',
                          style: TextStyle(
                              fontSize: 12,
                              color: isDarkMode
                                  ? Colors.white60
                                  : AppColors.textSecondary),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  DesignStatusChip(
                      status: design['status'] as String?, language: language),
                ],
              ),
              const SizedBox(height: 12),
              ClipRRect(
                borderRadius: BorderRadius.circular(99),
                child: LinearProgressIndicator(
                  value: progress.clamp(0.0, 1.0),
                  minHeight: 6,
                  backgroundColor:
                      isDarkMode ? Colors.white12 : Colors.grey.shade200,
                  valueColor: AlwaysStoppedAnimation<Color>(
                      AppColors.brandGreen),
                ),
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Text(
                    trDesign(language,
                        en: 'Stages $stagesDone / $stagesTotal',
                        sw: 'Hatua $stagesDone / $stagesTotal'),
                    style: TextStyle(
                        fontSize: 11,
                        color: isDarkMode
                            ? Colors.white60
                            : AppColors.textSecondary),
                  ),
                  const Spacer(),
                  Text(
                    trDesign(language, en: 'Schedule:', sw: 'Ratiba:'),
                    style: TextStyle(
                        fontSize: 11,
                        color: isDarkMode
                            ? Colors.white60
                            : AppColors.textSecondary),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    designStatusLabel(
                        design['schedule_status'] as String?, language),
                    style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: designStatusColor(
                            design['schedule_status'] as String?)),
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

// ── Detail bottom sheet ───────────────────────────────────────────────────

class _DesignDetailSheet extends ConsumerWidget {
  final EngineeringDesignKind kind;
  final int id;
  final ScrollController scrollController;

  const _DesignDetailSheet({
    required this.kind,
    required this.id,
    required this.scrollController,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final args = (kind: kind, id: id);
    final detailAsync = ref.watch(designDetailProvider(args));

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: detailAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(designErrorMessage(e, language),
                textAlign: TextAlign.center),
          ),
        ),
        data: (design) => _DesignDetailContent(
          kind: kind,
          design: design,
          language: language,
          isDarkMode: isDarkMode,
          scrollController: scrollController,
          onChanged: () {
            ref.invalidate(designDetailProvider(args));
            ref.invalidate(designListProvider(kind));
          },
        ),
      ),
    );
  }
}

class _DesignDetailContent extends ConsumerWidget {
  final EngineeringDesignKind kind;
  final Map<String, dynamic> design;
  final AppLanguage language;
  final bool isDarkMode;
  final ScrollController scrollController;
  final VoidCallback onChanged;

  const _DesignDetailContent({
    required this.kind,
    required this.design,
    required this.language,
    required this.isDarkMode,
    required this.scrollController,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final stages = (design['stages'] as List? ?? const [])
        .cast<Map<String, dynamic>>();
    final docNo = (design['document_number'] as String?) ?? '#${design['id']}';
    final scheduleStatus = (design['schedule_status'] as String?) ?? 'not_submitted';
    final overallStatus = (design['status'] as String?) ?? 'pending';
    final canEditHeader = !['submitted', 'approved'].contains(overallStatus);

    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 42,
              height: 4,
              decoration: BoxDecoration(
                color: isDarkMode ? Colors.white24 : Colors.grey[300],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: Text(
                  design['project_name'] as String? ?? docNo,
                  style: AppType.display(18, weight: FontWeight.w800),
                ),
              ),
              DesignStatusChip(status: overallStatus, language: language),
              const SizedBox(width: 4),
              IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close)),
            ],
          ),
          Text(docNo,
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode
                    ? Colors.white60
                    : AppColors.textSecondary,
              )),
          const SizedBox(height: 14),
          Expanded(
            child: SingleChildScrollView(
              controller: scrollController,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  DesignInfoRow(
                      label: trDesign(language,
                          en: 'Engineer', sw: 'Mhandisi'),
                      value: design['assigned_engineer'] as String? ??
                          trDesign(language,
                              en: 'Unassigned', sw: 'Haijapangwa'),
                      isDarkMode: isDarkMode),
                  DesignInfoRow(
                      label: trDesign(language, en: 'Created', sw: 'Iliyotengenezwa'),
                      value: formatDesignDate(design['created_at']),
                      isDarkMode: isDarkMode),
                  if ((design['notes'] as String?)?.trim().isNotEmpty == true)
                    DesignInfoRow(
                        label: trDesign(language, en: 'Notes', sw: 'Maelezo'),
                        value: design['notes'] as String,
                        isDarkMode: isDarkMode),
                  const SizedBox(height: 16),

                  // ── Schedule card ─────────────────────────────────────────
                  _SectionTitle(
                    title:
                        trDesign(language, en: 'Work Schedule', sw: 'Ratiba ya Kazi'),
                    isDarkMode: isDarkMode,
                  ),
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(top: 8, bottom: 16),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isDarkMode
                          ? Colors.white.withValues(alpha: 0.04)
                          : Colors.grey.withValues(alpha: 0.07),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(
                              trDesign(language, en: 'Status:', sw: 'Hali:'),
                              style: TextStyle(
                                fontSize: 12,
                                color: isDarkMode
                                    ? Colors.white70
                                    : AppColors.textSecondary,
                              ),
                            ),
                            const SizedBox(width: 6),
                            DesignStatusChip(
                                status: scheduleStatus, language: language),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          design['schedule_description'] as String? ??
                              trDesign(language,
                                  en: 'No schedule submitted yet.',
                                  sw: 'Hakuna ratiba iliyowasilishwa.'),
                          style: TextStyle(
                            fontSize: 13,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                        if (design['schedule_planned_start'] != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            '${formatDesignDate(design['schedule_planned_start'])} → ${formatDesignDate(design['schedule_planned_end'])}',
                            style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: isDarkMode
                                    ? Colors.white70
                                    : AppColors.textSecondary),
                          ),
                        ],
                        if ((design['schedule_rejection_notes'] as String?)
                                ?.trim()
                                .isNotEmpty ==
                            true) ...[
                          const SizedBox(height: 8),
                          Text(
                            trDesign(language,
                                en: 'Rejection notes:',
                                sw: 'Sababu za kukataliwa:'),
                            style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: AppColors.error),
                          ),
                          Text(
                            design['schedule_rejection_notes'] as String,
                            style: TextStyle(
                                fontSize: 12,
                                color: isDarkMode
                                    ? Colors.white
                                    : AppColors.textPrimary),
                          ),
                        ],
                        const SizedBox(height: 10),
                        if (scheduleStatus != 'approved')
                          SizedBox(
                            width: double.infinity,
                            child: OutlinedButton.icon(
                              onPressed: () =>
                                  _showScheduleForm(context, ref, design),
                              icon: const Icon(Icons.event_note_rounded),
                              label: Text(
                                scheduleStatus == 'submitted'
                                    ? trDesign(language,
                                        en: 'Edit & Resubmit',
                                        sw: 'Hariri & Wasilisha tena')
                                    : trDesign(language,
                                        en: 'Submit Schedule',
                                        sw: 'Wasilisha Ratiba'),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),

                  // ── Stages ────────────────────────────────────────────────
                  _SectionTitle(
                    title: trDesign(language, en: 'Stages', sw: 'Hatua'),
                    isDarkMode: isDarkMode,
                  ),
                  const SizedBox(height: 8),
                  for (final stage in stages)
                    _StageCard(
                      kind: kind,
                      designId: design['id'] as int,
                      stage: stage,
                      language: language,
                      isDarkMode: isDarkMode,
                      scheduleApproved: scheduleStatus == 'approved',
                      onChanged: onChanged,
                    ),
                  const SizedBox(height: 16),

                  // ── Footer actions ───────────────────────────────────────
                  if (canEditHeader)
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: () =>
                            _submitOverall(context, ref, design['id'] as int),
                        icon: const Icon(Icons.send_rounded),
                        label: Text(trDesign(language,
                            en: 'Submit for Approval',
                            sw: 'Wasilisha kwa Idhini')),
                      ),
                    ),
                  const SizedBox(height: 8),
                  if (canEditHeader)
                    SizedBox(
                      width: double.infinity,
                      child: TextButton.icon(
                        onPressed: () =>
                            _confirmDelete(context, ref, design['id'] as int),
                        style: TextButton.styleFrom(
                            foregroundColor: AppColors.error),
                        icon: const Icon(Icons.delete_outline),
                        label: Text(trDesign(language,
                            en: 'Delete Design', sw: 'Futa Ubunifu')),
                      ),
                    ),
                  const SizedBox(height: 8),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── Schedule submit form ───────────────────────────────────────────────

  void _showScheduleForm(
      BuildContext context, WidgetRef ref, Map<String, dynamic> design) {
    final language = ref.read(currentLanguageProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    final api = ref.read(apiClientProvider);

    final descCtrl = TextEditingController(
        text: design['schedule_description'] as String? ?? '');
    DateTime? start = design['schedule_planned_start'] != null
        ? DateTime.tryParse(design['schedule_planned_start'].toString())
        : null;
    DateTime? end = design['schedule_planned_end'] != null
        ? DateTime.tryParse(design['schedule_planned_end'].toString())
        : null;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom),
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 42,
                      height: 4,
                      decoration: BoxDecoration(
                        color: isDarkMode ? Colors.white24 : Colors.grey[300],
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    trDesign(language,
                        en: 'Submit Work Schedule',
                        sw: 'Wasilisha Ratiba ya Kazi'),
                    style: AppType.display(16, weight: FontWeight.w700),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: descCtrl,
                    maxLines: 4,
                    decoration: InputDecoration(
                      labelText: trDesign(language,
                          en: 'Description', sw: 'Maelezo'),
                      border: const OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _DateField(
                          label: trDesign(language,
                              en: 'Planned Start', sw: 'Mwanzo'),
                          value: start,
                          onPick: (d) => setSheet(() => start = d),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _DateField(
                          label: trDesign(language,
                              en: 'Planned End', sw: 'Mwisho'),
                          value: end,
                          onPick: (d) => setSheet(() => end = d),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        if (descCtrl.text.trim().isEmpty ||
                            start == null ||
                            end == null) {
                          ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(
                            content: Text(trDesign(language,
                                en: 'Please fill in all fields',
                                sw: 'Tafadhali jaza sehemu zote')),
                          ));
                          return;
                        }
                        try {
                          await api.post(
                            '${kind.path}/${design['id']}/schedule',
                            data: {
                              'schedule_description': descCtrl.text.trim(),
                              'schedule_planned_start':
                                  start!.toIso8601String().substring(0, 10),
                              'schedule_planned_end':
                                  end!.toIso8601String().substring(0, 10),
                            },
                          );
                          if (ctx.mounted) Navigator.pop(ctx);
                          onChanged();
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                              content: Text(trDesign(language,
                                  en: 'Schedule submitted',
                                  sw: 'Ratiba imewasilishwa')),
                              backgroundColor: AppColors.brandGreen,
                            ));
                          }
                        } catch (e) {
                          if (ctx.mounted) {
                            ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(
                              content: Text(designErrorMessage(e, language)),
                              backgroundColor: AppColors.error,
                            ));
                          }
                        }
                      },
                      child: Text(trDesign(language,
                          en: 'Submit', sw: 'Wasilisha')),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submitOverall(
      BuildContext context, WidgetRef ref, int id) async {
    final language = ref.read(currentLanguageProvider);
    final api = ref.read(apiClientProvider);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(trDesign(language,
            en: 'Submit for Approval?',
            sw: 'Wasilisha kwa Idhini?')),
        content: Text(trDesign(language,
            en: 'All stages must already be individually approved.',
            sw: 'Hatua zote lazima ziwe zimeidhinishwa kabla.')),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: Text(trDesign(language, en: 'Cancel', sw: 'Ghairi'))),
          ElevatedButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: Text(trDesign(language, en: 'Submit', sw: 'Wasilisha'))),
        ],
      ),
    );
    if (confirm != true) return;

    try {
      await api.post('${kind.path}/$id/submit');
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(trDesign(language,
              en: 'Submitted for approval.', sw: 'Imewasilishwa.')),
          backgroundColor: AppColors.brandGreen,
        ));
        Navigator.pop(context);
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(designErrorMessage(e, language)),
          backgroundColor: AppColors.error,
        ));
      }
    }
  }

  Future<void> _confirmDelete(
      BuildContext context, WidgetRef ref, int id) async {
    final language = ref.read(currentLanguageProvider);
    final api = ref.read(apiClientProvider);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(trDesign(language,
            en: 'Delete this design?', sw: 'Futa ubunifu huu?')),
        content: Text(trDesign(language,
            en: 'This cannot be undone. All stages will also be removed.',
            sw: 'Hili haliwezi kubatilishwa. Hatua zote pia zitaondolewa.')),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: Text(trDesign(language, en: 'Cancel', sw: 'Ghairi'))),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(trDesign(language, en: 'Delete', sw: 'Futa')),
          ),
        ],
      ),
    );
    if (confirm != true) return;

    try {
      await api.delete('${kind.path}/$id');
      onChanged();
      if (context.mounted) Navigator.pop(context);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(designErrorMessage(e, language)),
          backgroundColor: AppColors.error,
        ));
      }
    }
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  final bool isDarkMode;
  const _SectionTitle({required this.title, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: AppType.display(13, weight: FontWeight.w700).copyWith(
        color: isDarkMode ? Colors.white : AppColors.textPrimary,
        letterSpacing: 0.6,
      ),
    );
  }
}

class _StageCard extends ConsumerWidget {
  final EngineeringDesignKind kind;
  final int designId;
  final Map<String, dynamic> stage;
  final AppLanguage language;
  final bool isDarkMode;
  final bool scheduleApproved;
  final VoidCallback onChanged;

  const _StageCard({
    required this.kind,
    required this.designId,
    required this.stage,
    required this.language,
    required this.isDarkMode,
    required this.scheduleApproved,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = (stage['status'] as String?) ?? 'pending';
    final approvalStatus = (stage['approval_status'] as String?) ?? 'pending';
    final fileName = stage['file_name'] as String?;
    final fileUrl = stage['file_url'] as String?;
    final canEdit = scheduleApproved &&
        !['submitted', 'approved'].contains(approvalStatus);
    final canSubmit = scheduleApproved &&
        status == 'completed' &&
        fileName != null &&
        approvalStatus != 'submitted' &&
        approvalStatus != 'approved';

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 14,
                  backgroundColor: AppColors.brandBlue,
                  child: Text('${stage['stage_order']}',
                      style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 12)),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    stage['name'] as String? ?? '-',
                    style: AppType.display(13, weight: FontWeight.w700),
                  ),
                ),
                DesignStatusChip(status: status, language: language),
              ],
            ),
            const SizedBox(height: 6),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                Text(
                  trDesign(language, en: 'Approval:', sw: 'Idhini:'),
                  style: TextStyle(
                      fontSize: 11,
                      color: isDarkMode
                          ? Colors.white60
                          : AppColors.textSecondary),
                ),
                DesignStatusChip(status: approvalStatus, language: language),
              ],
            ),
            if ((stage['notes'] as String?)?.trim().isNotEmpty == true) ...[
              const SizedBox(height: 6),
              Text(
                stage['notes'] as String,
                style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white70
                        : AppColors.textSecondary),
              ),
            ],
            if ((stage['rejection_notes'] as String?)?.trim().isNotEmpty ==
                true) ...[
              const SizedBox(height: 6),
              Text(
                trDesign(language,
                    en: 'Rejection: ', sw: 'Sababu ya kukataliwa: '),
                style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: AppColors.error),
              ),
              Text(
                stage['rejection_notes'] as String,
                style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white
                        : AppColors.textPrimary),
              ),
            ],
            if (fileName != null) ...[
              const SizedBox(height: 6),
              InkWell(
                onTap: fileUrl == null
                    ? null
                    : () async {
                        final uri = Uri.tryParse(fileUrl);
                        if (uri != null) {
                          await ExternalLauncherService.openUri(uri);
                        }
                      },
                child: Row(
                  children: [
                    Icon(Icons.insert_drive_file_outlined,
                        size: 16, color: AppColors.brandGreen),
                    const SizedBox(width: 6),
                    Flexible(
                      child: Text(
                        fileName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 12,
                          color: AppColors.brandGreen,
                          decoration: TextDecoration.underline,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 10),
            Row(
              children: [
                if (canEdit)
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _editStage(context, ref),
                      icon: const Icon(Icons.edit_outlined, size: 16),
                      label: Text(trDesign(language,
                          en: 'Edit', sw: 'Hariri')),
                    ),
                  ),
                if (canEdit && canSubmit) const SizedBox(width: 8),
                if (canSubmit)
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _submitStage(context, ref),
                      icon: const Icon(Icons.send_outlined, size: 16),
                      label: Text(trDesign(language,
                          en: 'Submit', sw: 'Wasilisha')),
                    ),
                  ),
                if (!canEdit && !canSubmit)
                  Expanded(
                    child: Text(
                      !scheduleApproved
                          ? trDesign(language,
                              en: 'Awaiting schedule approval',
                              sw: 'Inasubiri idhini ya ratiba')
                          : (approvalStatus == 'approved'
                              ? trDesign(language,
                                  en: 'Approved — locked',
                                  sw: 'Imeidhinishwa')
                              : approvalStatus == 'submitted'
                                  ? trDesign(language,
                                      en: 'Awaiting management approval',
                                      sw: 'Inasubiri idhini ya menejimenti')
                                  : trDesign(language,
                                      en: 'Mark complete & upload file to submit',
                                      sw: 'Kamilisha & pakia faili')),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 11,
                        fontStyle: FontStyle.italic,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textHint,
                      ),
                    ),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _editStage(BuildContext context, WidgetRef ref) {
    final language = ref.read(currentLanguageProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    final api = ref.read(apiClientProvider);

    String status = (stage['status'] as String?) ?? 'pending';
    final notesCtrl =
        TextEditingController(text: (stage['notes'] as String?) ?? '');
    File? file;
    bool busy = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom),
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 42,
                      height: 4,
                      decoration: BoxDecoration(
                        color: isDarkMode ? Colors.white24 : Colors.grey[300],
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    '${trDesign(language, en: 'Update stage', sw: 'Sasisha hatua')}: ${stage['name']}',
                    style: AppType.display(16, weight: FontWeight.w700),
                  ),
                  const SizedBox(height: 14),
                  Text(trDesign(language, en: 'Status', sw: 'Hali'),
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary)),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    children: [
                      for (final s in const ['pending', 'in_progress', 'completed'])
                        ChoiceChip(
                          label: Text(designStatusLabel(s, language)),
                          selected: status == s,
                          onSelected: (_) => setSheet(() => status = s),
                        ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: notesCtrl,
                    maxLines: 3,
                    decoration: InputDecoration(
                      labelText: trDesign(language,
                          en: 'Notes (optional)',
                          sw: 'Maelezo (si lazima)'),
                      border: const OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Text(trDesign(language, en: 'Attachment', sw: 'Kiambatisho'),
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary)),
                  const SizedBox(height: 6),
                  DesignFilePickerTile(
                    file: file,
                    isDarkMode: isDarkMode,
                    language: language,
                    onPicked: (f) => setSheet(() => file = f),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: busy
                          ? null
                          : () async {
                              setSheet(() => busy = true);
                              try {
                                final form = FormData.fromMap({
                                  'status': status,
                                  'notes': notesCtrl.text.trim(),
                                  if (file != null)
                                    'file': await MultipartFile.fromFile(
                                      file!.path,
                                      filename: file!.path.split('/').last,
                                    ),
                                });
                                await api.uploadFile(
                                  '${kind.path}/$designId/stages/${stage['id']}',
                                  data: form,
                                );
                                if (ctx.mounted) Navigator.pop(ctx);
                                onChanged();
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(trDesign(language,
                                          en: 'Stage updated',
                                          sw: 'Hatua imesasishwa')),
                                      backgroundColor: AppColors.brandGreen,
                                    ),
                                  );
                                }
                              } catch (e) {
                                setSheet(() => busy = false);
                                if (ctx.mounted) {
                                  ScaffoldMessenger.of(ctx).showSnackBar(
                                    SnackBar(
                                      content:
                                          Text(designErrorMessage(e, language)),
                                      backgroundColor: AppColors.error,
                                    ),
                                  );
                                }
                              }
                            },
                      child: busy
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                  color: Colors.white, strokeWidth: 2))
                          : Text(trDesign(language, en: 'Save', sw: 'Hifadhi')),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submitStage(BuildContext context, WidgetRef ref) async {
    final language = ref.read(currentLanguageProvider);
    final api = ref.read(apiClientProvider);
    try {
      await api.post(
          '${kind.path}/$designId/stages/${stage['id']}/submit');
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(trDesign(language,
              en: 'Stage submitted for approval',
              sw: 'Hatua imewasilishwa kwa idhini')),
          backgroundColor: AppColors.brandGreen,
        ));
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(designErrorMessage(e, language)),
          backgroundColor: AppColors.error,
        ));
      }
    }
  }
}

// ── Create form ────────────────────────────────────────────────────────────

class _DesignCreateForm extends ConsumerStatefulWidget {
  final EngineeringDesignKind kind;
  final ScrollController scrollController;
  final VoidCallback onCreated;

  const _DesignCreateForm({
    required this.kind,
    required this.scrollController,
    required this.onCreated,
  });

  @override
  ConsumerState<_DesignCreateForm> createState() => _DesignCreateFormState();
}

class _DesignCreateFormState extends ConsumerState<_DesignCreateForm> {
  int? _projectId;
  int? _engineerId;
  final _notesCtrl = TextEditingController();
  bool _busy = false;

  @override
  void dispose() {
    _notesCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final refDataAsync = ref.watch(designReferenceDataProvider(widget.kind));

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 12,
        bottom: MediaQuery.of(context).viewInsets.bottom + 12,
      ),
      child: SingleChildScrollView(
        controller: widget.scrollController,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 42,
                height: 4,
                decoration: BoxDecoration(
                  color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              '${trDesign(language, en: 'New', sw: 'Mpya')} ${language == AppLanguage.swahili ? widget.kind.titleSw() : widget.kind.titleEn()}',
              style: AppType.display(18, weight: FontWeight.w800),
            ),
            const SizedBox(height: 14),
            refDataAsync.when(
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => Text(designErrorMessage(e, language)),
              data: (data) {
                final projects =
                    (data['projects'] as List? ?? []).cast<Map>().toList();
                final engineers =
                    (data['engineers'] as List? ?? []).cast<Map>().toList();
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    DropdownButtonFormField<int>(
                      initialValue: _projectId,
                      decoration: InputDecoration(
                        labelText:
                            trDesign(language, en: 'Project', sw: 'Mradi'),
                        border: const OutlineInputBorder(),
                      ),
                      items: [
                        for (final p in projects)
                          DropdownMenuItem<int>(
                            value: (p['id'] as num).toInt(),
                            child: Text(
                              p['project_name']?.toString() ?? '#${p['id']}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                      ],
                      onChanged: (v) => setState(() => _projectId = v),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      initialValue: _engineerId,
                      decoration: InputDecoration(
                        labelText: trDesign(language,
                            en: 'Assign Engineer (optional)',
                            sw: 'Pangia Mhandisi (si lazima)'),
                        border: const OutlineInputBorder(),
                      ),
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(trDesign(language,
                              en: '— Unassigned —',
                              sw: '— Haijapangwa —')),
                        ),
                        for (final e in engineers)
                          DropdownMenuItem<int?>(
                            value: (e['id'] as num).toInt(),
                            child: Text(e['name']?.toString() ?? '#${e['id']}'),
                          ),
                      ],
                      onChanged: (v) => setState(() => _engineerId = v),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _notesCtrl,
                      maxLines: 3,
                      decoration: InputDecoration(
                        labelText: trDesign(language,
                            en: 'Notes (optional)',
                            sw: 'Maelezo (si lazima)'),
                        border: const OutlineInputBorder(),
                      ),
                    ),
                    if (projects.isEmpty) ...[
                      const SizedBox(height: 12),
                      Text(
                        trDesign(language,
                            en:
                                'All projects already have a design — nothing to create.',
                            sw:
                                'Miradi yote tayari ina ubunifu — hakuna wa kutengeneza.'),
                        style: TextStyle(
                            fontSize: 12,
                            color: AppColors.brandYellow,
                            fontWeight: FontWeight.w600),
                      ),
                    ],
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _busy || _projectId == null ? null : _submit,
                child: _busy
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                            color: Colors.white, strokeWidth: 2),
                      )
                    : Text(trDesign(language, en: 'Create', sw: 'Tengeneza')),
              ),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    setState(() => _busy = true);
    final language = ref.read(currentLanguageProvider);
    final api = ref.read(apiClientProvider);
    try {
      await api.post(widget.kind.path, data: {
        'project_id': _projectId,
        if (_engineerId != null) 'assigned_engineer_id': _engineerId,
        if (_notesCtrl.text.trim().isNotEmpty) 'notes': _notesCtrl.text.trim(),
      });
      widget.onCreated();
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(trDesign(language,
              en: 'Design created.', sw: 'Ubunifu umetengenezwa.')),
          backgroundColor: AppColors.brandGreen,
        ));
      }
    } catch (e) {
      if (mounted) {
        setState(() => _busy = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(designErrorMessage(e, language)),
          backgroundColor: AppColors.error,
        ));
      }
    }
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final DateTime? value;
  final ValueChanged<DateTime> onPick;

  const _DateField({
    required this.label,
    required this.value,
    required this.onPick,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: value ?? DateTime.now(),
          firstDate: DateTime(2020),
          lastDate: DateTime(2035),
        );
        if (picked != null) onPick(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
        ),
        child: Text(
          value == null
              ? '—'
              : '${value!.year}-${value!.month.toString().padLeft(2, '0')}-${value!.day.toString().padLeft(2, '0')}',
        ),
      ),
    );
  }
}
