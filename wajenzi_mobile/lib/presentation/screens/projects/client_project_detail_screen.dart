import 'dart:io';
import 'dart:math';
import 'dart:ui';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/config/app_config.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/services/storage_service.dart';
import '../../../data/datasources/remote/client_api.dart';
import '../../providers/client_project_detail_provider.dart';
import '../../providers/settings_provider.dart';

class ClientProjectDetailScreen extends ConsumerStatefulWidget {
  final int projectId;
  final String projectName;

  const ClientProjectDetailScreen({
    super.key,
    required this.projectId,
    this.projectName = '',
  });

  @override
  ConsumerState<ClientProjectDetailScreen> createState() =>
      _ClientProjectDetailScreenState();
}

class _ClientProjectDetailScreenState
    extends ConsumerState<ClientProjectDetailScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Track which tabs have been visited to enable lazy loading
  final Set<int> _visitedTabs = {0};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 7, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        setState(() {
          _visitedTabs.add(_tabController.index);
        });
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _downloadPdf(String url, String fileName) async {
    final messenger = ScaffoldMessenger.of(context);
    final isSwahili = ref.read(isSwahiliProvider);

    messenger.showSnackBar(SnackBar(
      content: Text(isSwahili ? 'Inapakua...' : 'Downloading...'),
      duration: const Duration(seconds: 1),
    ));

    try {
      final token = await ref.read(storageServiceProvider).getToken();
      final dir = await getTemporaryDirectory();
      final filePath = '${dir.path}/$fileName';

      await Dio().download(
        url,
        filePath,
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      final file = File(filePath);
      if (await file.exists()) {
        final uri = Uri.file(filePath);
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri);
        } else {
          messenger.showSnackBar(SnackBar(
            content: Text(isSwahili ? 'Imehifadhiwa: $fileName' : 'Saved: $fileName'),
          ));
        }
      }
    } catch (e) {
      messenger.showSnackBar(SnackBar(
        content: Text(isSwahili ? 'Imeshindwa kupakua' : 'Download failed'),
        backgroundColor: AppColors.error,
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final overviewAsync = ref.watch(projectOverviewProvider(widget.projectId));

    final projectName = overviewAsync.whenOrNull(
          data: (data) => data.project.projectName,
        ) ??
        widget.projectName;

    final status = overviewAsync.whenOrNull(
      data: (data) => data.project.status,
    );

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Text(
              projectName,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
              overflow: TextOverflow.ellipsis,
            ),
            if (status != null)
              Text(
                _statusLabel(status),
                style: TextStyle(
                  fontSize: 11,
                  color: Colors.white.withValues(alpha: 0.8),
                ),
              ),
          ],
        ),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          labelStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
          unselectedLabelStyle: const TextStyle(fontSize: 12),
          tabAlignment: TabAlignment.start,
          tabs: [
            Tab(text: isSwahili ? 'Muhtasari' : 'Overview'),
            Tab(text: 'BOQ'),
            Tab(text: isSwahili ? 'Ratiba' : 'Schedule'),
            Tab(text: isSwahili ? 'Fedha' : 'Financials'),
            Tab(text: isSwahili ? 'Nyaraka' : 'Documents'),
            Tab(text: isSwahili ? 'Picha' : 'Gallery'),
            Tab(text: isSwahili ? 'Ripoti' : 'Reports'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _OverviewTab(projectId: widget.projectId),
          _visitedTabs.contains(1)
              ? _BoqTab(projectId: widget.projectId)
              : const SizedBox.shrink(),
          _visitedTabs.contains(2)
              ? _ScheduleTab(projectId: widget.projectId)
              : const SizedBox.shrink(),
          _visitedTabs.contains(3)
              ? _FinancialsTab(
                  projectId: widget.projectId,
                  downloadPdf: _downloadPdf,
                )
              : const SizedBox.shrink(),
          _visitedTabs.contains(4)
              ? _DocumentsTab(projectId: widget.projectId)
              : const SizedBox.shrink(),
          _visitedTabs.contains(5)
              ? _GalleryTab(projectId: widget.projectId)
              : const SizedBox.shrink(),
          _visitedTabs.contains(6)
              ? _ReportsTab(
                  projectId: widget.projectId,
                  downloadPdf: _downloadPdf,
                )
              : const SizedBox.shrink(),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════
// Shared helpers
// ═══════════════════════════════════════════════════

String _statusLabel(String? status) {
  if (status == null) return '';
  return status.replaceAll('_', ' ').split(' ').map((w) {
    if (w.isEmpty) return w;
    return '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}';
  }).join(' ');
}

Color _statusColor(String? status) {
  switch (status?.toLowerCase()) {
    case 'active':
    case 'in_progress':
    case 'approved':
      return AppColors.success;
    case 'completed':
      return AppColors.info;
    case 'on_hold':
    case 'pending':
      return AppColors.warning;
    case 'cancelled':
    case 'overdue':
      return AppColors.error;
    default:
      return AppColors.draft;
  }
}

Widget _buildStatusBadge(String? status) {
  final color = _statusColor(status);
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(8),
    ),
    child: Text(
      _statusLabel(status),
      style: TextStyle(
        fontSize: 10,
        fontWeight: FontWeight.w700,
        color: color,
        letterSpacing: 0.3,
      ),
    ),
  );
}

String _formatDate(String? date) {
  if (date == null) return '—';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
  } catch (_) {
    return date;
  }
}

String _formatCurrency(double amount) {
  final formatter = NumberFormat('#,##0.00', 'en');
  return 'TZS ${formatter.format(amount)}';
}

String _formatCurrencyShort(double amount) {
  if (amount >= 1e9) return 'TZS ${(amount / 1e9).toStringAsFixed(1)}B';
  if (amount >= 1e6) return 'TZS ${(amount / 1e6).toStringAsFixed(1)}M';
  if (amount >= 1e3) return 'TZS ${(amount / 1e3).toStringAsFixed(0)}K';
  return 'TZS ${amount.toStringAsFixed(0)}';
}

class _GlassContainer extends StatelessWidget {
  final Widget child;
  final bool isDarkMode;
  final EdgeInsetsGeometry? margin;

  const _GlassContainer({
    required this.child,
    required this.isDarkMode,
    this.margin,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.15)
              : Colors.white.withValues(alpha: 0.6),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.06),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: isDarkMode
                    ? [
                        Colors.white.withValues(alpha: 0.08),
                        Colors.white.withValues(alpha: 0.04),
                      ]
                    : [
                        Colors.white.withValues(alpha: 0.75),
                        Colors.white.withValues(alpha: 0.55),
                      ],
              ),
            ),
            child: child,
          ),
        ),
      ),
    );
  }
}

Widget _buildTabError(Object error, bool isSwahili, VoidCallback onRetry) {
  return ListView(
    padding: const EdgeInsets.all(32),
    children: [
      const SizedBox(height: 80),
      const Icon(Icons.error_outline, size: 56, color: AppColors.error),
      const SizedBox(height: 16),
      Text(
        isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
      ),
      const SizedBox(height: 8),
      Text(
        error.toString(),
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
      ),
      const SizedBox(height: 24),
      Center(
        child: ElevatedButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh, size: 18),
          label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
        ),
      ),
    ],
  );
}

Widget _buildTabLoading() {
  return const Center(child: CircularProgressIndicator());
}

Widget _buildEmpty(String message, IconData icon) {
  return Center(
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 56, color: AppColors.textHint),
        const SizedBox(height: 12),
        Text(message, style: const TextStyle(color: AppColors.textSecondary)),
      ],
    ),
  );
}

// ═══════════════════════════════════════════════════
// OVERVIEW TAB
// ═══════════════════════════════════════════════════

class _OverviewTab extends ConsumerWidget {
  final int projectId;

  const _OverviewTab({required this.projectId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(projectOverviewProvider(projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return asyncData.when(
      loading: _buildTabLoading,
      error: (e, _) => _buildTabError(
        e,
        isSwahili,
        () => ref.invalidate(projectOverviewProvider(projectId)),
      ),
      data: (data) => RefreshIndicator(
        onRefresh: () async =>
            ref.invalidate(projectOverviewProvider(projectId)),
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Progress ring
              if (data.progress != null)
                _ProgressSection(
                  progress: data.progress!,
                  isDarkMode: isDarkMode,
                  isSwahili: isSwahili,
                ),

              // Progress by phase
              if (data.progressByPhase.isNotEmpty) ...[
                const SizedBox(height: 16),
                _ProgressByPhaseSection(
                  progressByPhase: data.progressByPhase,
                  isDarkMode: isDarkMode,
                  isSwahili: isSwahili,
                ),
              ],

              const SizedBox(height: 16),

              // Project details card
              _ProjectDetailsCard(
                project: data.project,
                isDarkMode: isDarkMode,
                isSwahili: isSwahili,
              ),

              const SizedBox(height: 16),

              // Timeline
              _TimelineCard(
                project: data.project,
                isDarkMode: isDarkMode,
                isSwahili: isSwahili,
              ),

              // Construction phases
              if (data.project.phases.isNotEmpty) ...[
                const SizedBox(height: 16),
                _PhasesCard(
                  phases: data.project.phases,
                  isDarkMode: isDarkMode,
                  isSwahili: isSwahili,
                ),
              ],

              // Contract value
              const SizedBox(height: 16),
              _GlassContainer(
                isDarkMode: isDarkMode,
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: AppColors.success.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.account_balance_wallet_rounded,
                            color: AppColors.success),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isSwahili ? 'Thamani ya Mkataba' : 'Contract Value',
                              style: TextStyle(
                                fontSize: 12,
                                color: isDarkMode
                                    ? Colors.white54
                                    : AppColors.textSecondary,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _formatCurrency(data.project.contractValue),
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: isDarkMode
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 32),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Progress Section with Ring ──────────────────

class _ProgressSection extends StatelessWidget {
  final ProjectProgress progress;
  final bool isDarkMode;
  final bool isSwahili;

  const _ProgressSection({
    required this.progress,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Maendeleo ya Jumla' : 'Overall Progress',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                SizedBox(
                  width: 110,
                  height: 110,
                  child: CustomPaint(
                    painter: _ProgressRingPainter(
                      percentage: progress.percentage,
                      isDarkMode: isDarkMode,
                    ),
                    child: Center(
                      child: Text(
                        '${progress.percentage.toStringAsFixed(0)}%',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 24),
                Expanded(
                  child: Column(
                    children: [
                      _ProgressStat(
                        label: isSwahili ? 'Imekamilika' : 'Completed',
                        count: progress.completed,
                        color: AppColors.success,
                      ),
                      const SizedBox(height: 8),
                      _ProgressStat(
                        label: isSwahili ? 'Inaendelea' : 'In Progress',
                        count: progress.inProgress,
                        color: AppColors.info,
                      ),
                      const SizedBox(height: 8),
                      _ProgressStat(
                        label: isSwahili ? 'Inasubiri' : 'Pending',
                        count: progress.pending,
                        color: AppColors.warning,
                      ),
                      if (progress.overdue > 0) ...[
                        const SizedBox(height: 8),
                        _ProgressStat(
                          label: isSwahili ? 'Imechelewa' : 'Overdue',
                          count: progress.overdue,
                          color: AppColors.error,
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ProgressStat extends StatelessWidget {
  final String label;
  final int count;
  final Color color;

  const _ProgressStat({
    required this.label,
    required this.count,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
          ),
        ),
        Text(
          '$count',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }
}

class _ProgressRingPainter extends CustomPainter {
  final double percentage;
  final bool isDarkMode;

  _ProgressRingPainter({required this.percentage, required this.isDarkMode});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = min(size.width, size.height) / 2 - 8;
    const strokeWidth = 10.0;

    // Background ring
    final bgPaint = Paint()
      ..color = isDarkMode
          ? Colors.white.withValues(alpha: 0.1)
          : Colors.grey.withValues(alpha: 0.15)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    canvas.drawCircle(center, radius, bgPaint);

    // Progress arc
    final progressPaint = Paint()
      ..shader = const SweepGradient(
        colors: [AppColors.primary, AppColors.success],
      ).createShader(Rect.fromCircle(center: center, radius: radius))
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    final sweepAngle = (percentage / 100) * 2 * pi;
    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius),
      -pi / 2,
      sweepAngle,
      false,
      progressPaint,
    );
  }

  @override
  bool shouldRepaint(covariant _ProgressRingPainter oldDelegate) =>
      oldDelegate.percentage != percentage;
}

// ─── Progress by Phase ──────────────────────────

class _ProgressByPhaseSection extends StatelessWidget {
  final Map<String, dynamic> progressByPhase;
  final bool isDarkMode;
  final bool isSwahili;

  const _ProgressByPhaseSection({
    required this.progressByPhase,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Maendeleo kwa Awamu' : 'Progress by Phase',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            ...progressByPhase.entries.map((e) {
              final phaseData = e.value is Map<String, dynamic>
                  ? e.value as Map<String, dynamic>
                  : <String, dynamic>{};
              final pct = (phaseData['percentage'] as num?)?.toDouble() ?? 0;
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Text(
                            e.key,
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
                            ),
                          ),
                        ),
                        Text(
                          '${pct.toStringAsFixed(0)}%',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: isDarkMode ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: pct / 100,
                        minHeight: 6,
                        backgroundColor: isDarkMode
                            ? Colors.white.withValues(alpha: 0.1)
                            : Colors.grey.withValues(alpha: 0.15),
                        valueColor: AlwaysStoppedAnimation(
                          pct >= 100
                              ? AppColors.success
                              : pct >= 50
                                  ? AppColors.info
                                  : AppColors.warning,
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }
}

// ─── Project Details Card ───────────────────────

class _ProjectDetailsCard extends StatelessWidget {
  final ProjectDetail project;
  final bool isDarkMode;
  final bool isSwahili;

  const _ProjectDetailsCard({
    required this.project,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Maelezo ya Mradi' : 'Project Details',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            if (project.description != null) ...[
              Text(
                project.description!,
                style: TextStyle(
                  fontSize: 13,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 12),
            ],
            _DetailRow(
              label: isSwahili ? 'Aina ya Mradi' : 'Project Type',
              value: project.projectType ?? '—',
              isDarkMode: isDarkMode,
            ),
            _DetailRow(
              label: isSwahili ? 'Huduma' : 'Service Type',
              value: project.serviceType ?? '—',
              isDarkMode: isDarkMode,
            ),
            _DetailRow(
              label: isSwahili ? 'Msimamizi' : 'Project Manager',
              value: project.projectManager ?? '—',
              isDarkMode: isDarkMode,
            ),
            _DetailRow(
              label: isSwahili ? 'Kipaumbele' : 'Priority',
              value: _statusLabel(project.priority),
              isDarkMode: isDarkMode,
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Timeline Card ──────────────────────────────

class _TimelineCard extends StatelessWidget {
  final ProjectDetail project;
  final bool isDarkMode;
  final bool isSwahili;

  const _TimelineCard({
    required this.project,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    String duration = '—';
    if (project.startDate != null && project.expectedEndDate != null) {
      try {
        final start = DateTime.parse(project.startDate!);
        final end = DateTime.parse(project.expectedEndDate!);
        final days = end.difference(start).inDays;
        if (days > 30) {
          duration = '${(days / 30).round()} ${isSwahili ? "miezi" : "months"}';
        } else {
          duration = '$days ${isSwahili ? "siku" : "days"}';
        }
      } catch (_) {}
    }

    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Ratiba' : 'Timeline',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            _DetailRow(
              label: isSwahili ? 'Kuanza' : 'Start Date',
              value: _formatDate(project.startDate),
              isDarkMode: isDarkMode,
            ),
            _DetailRow(
              label: isSwahili ? 'Mwisho' : 'Expected End',
              value: _formatDate(project.expectedEndDate),
              isDarkMode: isDarkMode,
            ),
            if (project.actualEndDate != null)
              _DetailRow(
                label: isSwahili ? 'Mwisho Halisi' : 'Actual End',
                value: _formatDate(project.actualEndDate),
                isDarkMode: isDarkMode,
              ),
            _DetailRow(
              label: isSwahili ? 'Muda' : 'Duration',
              value: duration,
              isDarkMode: isDarkMode,
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Phases Card ────────────────────────────────

class _PhasesCard extends StatelessWidget {
  final List<ConstructionPhase> phases;
  final bool isDarkMode;
  final bool isSwahili;

  const _PhasesCard({
    required this.phases,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              isSwahili ? 'Awamu za Ujenzi' : 'Construction Phases',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            ...phases.map((phase) => Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              phase.phaseName,
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                color: isDarkMode ? Colors.white : AppColors.textPrimary,
                              ),
                            ),
                            Text(
                              '${_formatDate(phase.startDate)} — ${_formatDate(phase.endDate)}',
                              style: const TextStyle(
                                fontSize: 11,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                      _buildStatusBadge(phase.status),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }
}

// ═══════════════════════════════════════════════════
// BOQ TAB
// ═══════════════════════════════════════════════════

class _BoqTab extends ConsumerWidget {
  final int projectId;

  const _BoqTab({required this.projectId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(projectBoqProvider(projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return asyncData.when(
      loading: _buildTabLoading,
      error: (e, _) => _buildTabError(
        e,
        isSwahili,
        () => ref.invalidate(projectBoqProvider(projectId)),
      ),
      data: (boqs) {
        if (boqs.isEmpty) {
          return _buildEmpty(
            isSwahili ? 'Hakuna BOQ bado' : 'No BOQ yet',
            Icons.list_alt_rounded,
          );
        }
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: boqs.map((boq) => _BoqCard(
              boq: boq,
              isDarkMode: isDarkMode,
              isSwahili: isSwahili,
            )).toList(),
          ),
        );
      },
    );
  }
}

class _BoqCard extends StatelessWidget {
  final ProjectBoq boq;
  final bool isDarkMode;
  final bool isSwahili;

  const _BoqCard({
    required this.boq,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header row
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'BOQ${boq.version != null ? " v${boq.version}" : ""}',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      if (boq.type != null)
                        Text(
                          boq.type!,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                    ],
                  ),
                ),
                _buildStatusBadge(boq.status),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              '${isSwahili ? "Jumla" : "Total"}: ${_formatCurrency(boq.totalAmount)}',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: isDarkMode ? Colors.white : AppColors.primary,
              ),
            ),
            const SizedBox(height: 12),

            // Sections (recursive)
            ...boq.sections.map((section) => _BoqSectionWidget(
              section: section,
              isDarkMode: isDarkMode,
              isSwahili: isSwahili,
              depth: 0,
            )),

            // Unsectioned items
            if (boq.items.isNotEmpty) ...[
              const Divider(),
              ...boq.items.map((item) => _BoqItemRow(
                item: item,
                isDarkMode: isDarkMode,
              )),
            ],
          ],
        ),
      ),
    );
  }
}

class _BoqSectionWidget extends StatefulWidget {
  final BoqSection section;
  final bool isDarkMode;
  final bool isSwahili;
  final int depth;

  const _BoqSectionWidget({
    required this.section,
    required this.isDarkMode,
    required this.isSwahili,
    required this.depth,
  });

  @override
  State<_BoqSectionWidget> createState() => _BoqSectionWidgetState();
}

class _BoqSectionWidgetState extends State<_BoqSectionWidget> {
  bool _expanded = true;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(left: widget.depth * 12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GestureDetector(
            onTap: () => setState(() => _expanded = !_expanded),
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
              decoration: BoxDecoration(
                color: widget.isDarkMode
                    ? Colors.white.withValues(alpha: 0.05)
                    : AppColors.primary.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(
                    _expanded
                        ? Icons.keyboard_arrow_down
                        : Icons.keyboard_arrow_right,
                    size: 18,
                    color: AppColors.primary,
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      widget.section.name,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: widget.isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (_expanded) ...[
            const SizedBox(height: 4),
            // Items in this section
            ...widget.section.items.map((item) => _BoqItemRow(
              item: item,
              isDarkMode: widget.isDarkMode,
            )),
            // Child sections (recursive)
            ...widget.section.children.map((child) => _BoqSectionWidget(
              section: child,
              isDarkMode: widget.isDarkMode,
              isSwahili: widget.isSwahili,
              depth: widget.depth + 1,
            )),
          ],
          const SizedBox(height: 4),
        ],
      ),
    );
  }
}

class _BoqItemRow extends StatelessWidget {
  final BoqItem item;
  final bool isDarkMode;

  const _BoqItemRow({required this.item, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final typeColor =
        item.itemType?.toLowerCase() == 'labour' ? AppColors.info : AppColors.warning;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (item.itemCode != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  margin: const EdgeInsets.only(right: 8),
                  decoration: BoxDecoration(
                    color: isDarkMode
                        ? Colors.white.withValues(alpha: 0.08)
                        : Colors.grey.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    item.itemCode!,
                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w600),
                  ),
                ),
              Expanded(
                child: Text(
                  item.description ?? '—',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
                  ),
                ),
              ),
              if (item.itemType != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: typeColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    _statusLabel(item.itemType),
                    style: TextStyle(
                      fontSize: 9,
                      fontWeight: FontWeight.w600,
                      color: typeColor,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Text(
                '${NumberFormat('#,##0.##').format(item.quantity)} ${item.unit ?? ""}',
                style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
              ),
              const Text(' × ', style: TextStyle(fontSize: 11, color: AppColors.textSecondary)),
              Text(
                _formatCurrency(item.unitPrice),
                style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
              ),
              const Spacer(),
              Text(
                _formatCurrency(item.totalPrice),
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
            ],
          ),
          Divider(
            color: isDarkMode ? Colors.white12 : Colors.grey.withValues(alpha: 0.2),
            height: 16,
          ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════
// SCHEDULE TAB
// ═══════════════════════════════════════════════════

class _ScheduleTab extends ConsumerWidget {
  final int projectId;

  const _ScheduleTab({required this.projectId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(projectScheduleProvider(projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return asyncData.when(
      loading: _buildTabLoading,
      error: (e, _) => _buildTabError(
        e,
        isSwahili,
        () => ref.invalidate(projectScheduleProvider(projectId)),
      ),
      data: (data) {
        if (data.phases.isEmpty && data.activities.isEmpty) {
          return _buildEmpty(
            isSwahili ? 'Hakuna ratiba bado' : 'No schedule yet',
            Icons.schedule_rounded,
          );
        }
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Phases
              if (data.phases.isNotEmpty) ...[
                Text(
                  isSwahili ? 'Awamu za Ujenzi' : 'Construction Phases',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 12),
                ...data.phases.map((phase) => _GlassContainer(
                  isDarkMode: isDarkMode,
                  margin: const EdgeInsets.only(bottom: 8),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                phase.phaseName,
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${_formatDate(phase.startDate)} — ${_formatDate(phase.endDate)}',
                                style: const TextStyle(
                                  fontSize: 12,
                                  color: AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ),
                        _buildStatusBadge(phase.status),
                      ],
                    ),
                  ),
                )),
              ],

              // Activities
              if (data.activities.isNotEmpty) ...[
                const SizedBox(height: 20),
                Text(
                  isSwahili ? 'Shughuli' : 'Activities',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 12),
                ...data.activities.map((a) => _GlassContainer(
                  isDarkMode: isDarkMode,
                  margin: const EdgeInsets.only(bottom: 8),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            if (a.activityCode != null) ...[
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppColors.primary.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  a.activityCode!,
                                  style: const TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 8),
                            ],
                            Expanded(
                              child: Text(
                                a.name,
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                                ),
                              ),
                            ),
                            _buildStatusBadge(a.status),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 12,
                          runSpacing: 4,
                          children: [
                            if (a.phase != null)
                              _InfoChip(icon: Icons.layers_rounded, label: a.phase!, isDarkMode: isDarkMode),
                            _InfoChip(
                              icon: Icons.calendar_today_rounded,
                              label: '${_formatDate(a.startDate)} — ${_formatDate(a.endDate)}',
                              isDarkMode: isDarkMode,
                            ),
                            _InfoChip(
                              icon: Icons.timer_rounded,
                              label: '${a.durationDays} ${isSwahili ? "siku" : "days"}',
                              isDarkMode: isDarkMode,
                            ),
                          ],
                        ),
                        if (a.notes != null && a.notes!.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            a.notes!,
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                )),
              ],

              const SizedBox(height: 32),
            ],
          ),
        );
      },
    );
  }
}

// ═══════════════════════════════════════════════════
// FINANCIALS TAB
// ═══════════════════════════════════════════════════

class _FinancialsTab extends ConsumerWidget {
  final int projectId;
  final Future<void> Function(String url, String fileName) downloadPdf;

  const _FinancialsTab({
    required this.projectId,
    required this.downloadPdf,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(projectFinancialsProvider(projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return asyncData.when(
      loading: _buildTabLoading,
      error: (e, _) => _buildTabError(
        e,
        isSwahili,
        () => ref.invalidate(projectFinancialsProvider(projectId)),
      ),
      data: (data) => SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Summary cards 2x2
            Row(
              children: [
                Expanded(
                  child: _SummaryStatCard(
                    label: isSwahili ? 'Thamani' : 'Contract Value',
                    value: _formatCurrencyShort(data.contractValue),
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    isDarkMode: isDarkMode,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _SummaryStatCard(
                    label: isSwahili ? 'Ankara' : 'Total Invoiced',
                    value: _formatCurrencyShort(data.totalInvoiced),
                    color: AppColors.info,
                    isDarkMode: isDarkMode,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _SummaryStatCard(
                    label: isSwahili ? 'Limelipwa' : 'Total Paid',
                    value: _formatCurrencyShort(data.totalPaid),
                    color: AppColors.success,
                    isDarkMode: isDarkMode,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _SummaryStatCard(
                    label: isSwahili ? 'Deni' : 'Balance Due',
                    value: _formatCurrencyShort(data.balanceDue),
                    color: data.balanceDue > 0 ? AppColors.error : AppColors.success,
                    isDarkMode: isDarkMode,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Invoices
            if (data.billingInvoices.isNotEmpty) ...[
              _FinancialSection(
                title: isSwahili ? 'Ankara' : 'Invoices',
                icon: Icons.receipt_rounded,
                count: data.billingInvoices.length,
                isDarkMode: isDarkMode,
                initiallyExpanded: true,
                children: data.billingInvoices.map((doc) => _FinancialDocCard(
                  doc: doc,
                  projectId: projectId,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  downloadPdf: downloadPdf,
                )).toList(),
              ),
            ],

            // Quotes
            if (data.billingQuotes.isNotEmpty) ...[
              const SizedBox(height: 16),
              _FinancialSection(
                title: isSwahili ? 'Nukuu' : 'Quotations',
                icon: Icons.request_quote_rounded,
                count: data.billingQuotes.length,
                isDarkMode: isDarkMode,
                children: data.billingQuotes.map((doc) => _FinancialDocCard(
                  doc: doc,
                  projectId: projectId,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  downloadPdf: downloadPdf,
                )).toList(),
              ),
            ],

            // Proformas
            if (data.billingProformas.isNotEmpty) ...[
              const SizedBox(height: 16),
              _FinancialSection(
                title: isSwahili ? 'Ankara za Awali' : 'Proformas',
                icon: Icons.description_rounded,
                count: data.billingProformas.length,
                isDarkMode: isDarkMode,
                children: data.billingProformas.map((doc) => _FinancialDocCard(
                  doc: doc,
                  projectId: projectId,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  downloadPdf: downloadPdf,
                )).toList(),
              ),
            ],

            if (data.billingInvoices.isEmpty &&
                data.billingQuotes.isEmpty &&
                data.billingProformas.isEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 40),
                child: _buildEmpty(
                  isSwahili ? 'Hakuna ankara bado' : 'No billing documents yet',
                  Icons.receipt_long_rounded,
                ),
              ),

            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }
}

class _SummaryStatCard extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final bool isDarkMode;

  const _SummaryStatCard({
    required this.label,
    required this.value,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label.toUpperCase(),
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                letterSpacing: 0.5,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 6),
            Text(
              value,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: color,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

class _FinancialSection extends StatefulWidget {
  final String title;
  final IconData icon;
  final int count;
  final bool initiallyExpanded;
  final bool isDarkMode;
  final List<Widget> children;

  const _FinancialSection({
    required this.title,
    required this.icon,
    required this.count,
    this.initiallyExpanded = false,
    required this.isDarkMode,
    required this.children,
  });

  @override
  State<_FinancialSection> createState() => _FinancialSectionState();
}

class _FinancialSectionState extends State<_FinancialSection> {
  late bool _expanded;

  @override
  void initState() {
    super.initState();
    _expanded = widget.initiallyExpanded;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        GestureDetector(
          onTap: () => setState(() => _expanded = !_expanded),
          child: Row(
            children: [
              Icon(widget.icon, size: 20, color: AppColors.primary),
              const SizedBox(width: 8),
              Text(
                widget.title,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: widget.isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${widget.count}',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const Spacer(),
              AnimatedRotation(
                turns: _expanded ? 0.5 : 0,
                duration: const Duration(milliseconds: 200),
                child: Icon(
                  Icons.keyboard_arrow_down_rounded,
                  color: widget.isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ),
        if (_expanded) ...[
          const SizedBox(height: 12),
          ...widget.children,
        ],
      ],
    );
  }
}

class _FinancialDocCard extends StatelessWidget {
  final BillingDocument doc;
  final int projectId;
  final bool isSwahili;
  final bool isDarkMode;
  final Future<void> Function(String url, String fileName) downloadPdf;

  const _FinancialDocCard({
    required this.doc,
    required this.projectId,
    required this.isSwahili,
    required this.isDarkMode,
    required this.downloadPdf,
  });

  @override
  Widget build(BuildContext context) {
    final isInvoice = doc.documentType == 'invoice';
    return _GlassContainer(
      isDarkMode: isDarkMode,
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    doc.documentNumber ?? '—',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                ),
                _buildStatusBadge(
                  doc.isOverdue && doc.status != 'paid' ? 'overdue' : doc.status,
                ),
                const SizedBox(width: 8),
                GestureDetector(
                  onTap: () {
                    final url =
                        '${AppConfig.clientBaseUrl}/projects/$projectId/billing/${doc.id}/pdf';
                    final name =
                        '${doc.documentType}-${doc.documentNumber ?? doc.id}.pdf';
                    downloadPdf(url, name);
                  },
                  child: Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: AppColors.error.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.picture_as_pdf_rounded,
                        size: 18, color: AppColors.error),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                _InfoChip(
                  icon: Icons.calendar_today_rounded,
                  label: _formatDate(doc.issueDate),
                  isDarkMode: isDarkMode,
                ),
                if (isInvoice) ...[
                  const SizedBox(width: 12),
                  _InfoChip(
                    icon: Icons.event_rounded,
                    label: '${isSwahili ? "Hadi" : "Due"} ${_formatDate(doc.dueDate)}',
                    isDarkMode: isDarkMode,
                    isWarning: doc.isOverdue,
                  ),
                ],
              ],
            ),
            const SizedBox(height: 8),
            if (isInvoice)
              Row(
                children: [
                  Expanded(
                    child: _AmountCol(
                      label: isSwahili ? 'Kiasi' : 'Amount',
                      value: _formatCurrency(doc.totalAmount),
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  Expanded(
                    child: _AmountCol(
                      label: isSwahili ? 'Limelipwa' : 'Paid',
                      value: _formatCurrency(doc.paidAmount),
                      color: AppColors.success,
                    ),
                  ),
                  Expanded(
                    child: _AmountCol(
                      label: isSwahili ? 'Deni' : 'Balance',
                      value: _formatCurrency(doc.balanceAmount),
                      color: doc.balanceAmount > 0 ? AppColors.error : AppColors.success,
                    ),
                  ),
                ],
              )
            else
              Text(
                _formatCurrency(doc.totalAmount),
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _AmountCol extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _AmountCol({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary)),
        const SizedBox(height: 2),
        Text(
          value,
          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: color),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ],
    );
  }
}

// ═══════════════════════════════════════════════════
// DOCUMENTS TAB
// ═══════════════════════════════════════════════════

class _DocumentsTab extends ConsumerWidget {
  final int projectId;

  const _DocumentsTab({required this.projectId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(projectDocumentsProvider(projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return asyncData.when(
      loading: _buildTabLoading,
      error: (e, _) => _buildTabError(
        e,
        isSwahili,
        () => ref.invalidate(projectDocumentsProvider(projectId)),
      ),
      data: (designs) {
        if (designs.isEmpty) {
          return _buildEmpty(
            isSwahili ? 'Hakuna nyaraka bado' : 'No documents yet',
            Icons.folder_open_rounded,
          );
        }
        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: designs.length,
          itemBuilder: (context, index) {
            final d = designs[index];
            return _GlassContainer(
              isDarkMode: isDarkMode,
              margin: const EdgeInsets.only(bottom: 10),
              child: ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: AppColors.info.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.insert_drive_file_rounded,
                      color: AppColors.info, size: 24),
                ),
                title: Text(
                  d.designType ?? (isSwahili ? 'Nyaraka' : 'Document'),
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                subtitle: Row(
                  children: [
                    if (d.version != null) ...[
                      Text(
                        'v${d.version}',
                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                      ),
                      const SizedBox(width: 8),
                    ],
                    if (d.status != null) _buildStatusBadge(d.status),
                  ],
                ),
                trailing: d.fileUrl != null
                    ? IconButton(
                        icon: const Icon(Icons.download_rounded, color: AppColors.primary),
                        onPressed: () async {
                          final uri = Uri.parse(d.fileUrl!);
                          if (await canLaunchUrl(uri)) {
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                          }
                        },
                      )
                    : null,
              ),
            );
          },
        );
      },
    );
  }
}

// ═══════════════════════════════════════════════════
// GALLERY TAB
// ═══════════════════════════════════════════════════

class _GalleryTab extends ConsumerStatefulWidget {
  final int projectId;

  const _GalleryTab({required this.projectId});

  @override
  ConsumerState<_GalleryTab> createState() => _GalleryTabState();
}

class _GalleryTabState extends ConsumerState<_GalleryTab> {
  String? _selectedPhase;

  @override
  Widget build(BuildContext context) {
    final asyncData = ref.watch(projectGalleryProvider(widget.projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return asyncData.when(
      loading: _buildTabLoading,
      error: (e, _) => _buildTabError(
        e,
        isSwahili,
        () => ref.invalidate(projectGalleryProvider(widget.projectId)),
      ),
      data: (data) {
        if (data.images.isEmpty) {
          return _buildEmpty(
            isSwahili ? 'Hakuna picha bado' : 'No images yet',
            Icons.photo_library_rounded,
          );
        }

        final filtered = _selectedPhase == null
            ? data.images
            : data.images.where((i) => i.phase == _selectedPhase).toList();

        return Column(
          children: [
            // Phase filter chips
            if (data.phases.isNotEmpty)
              SizedBox(
                height: 50,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  children: [
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ChoiceChip(
                        label: Text(isSwahili ? 'Zote' : 'All'),
                        selected: _selectedPhase == null,
                        onSelected: (_) => setState(() => _selectedPhase = null),
                        selectedColor: AppColors.primary.withValues(alpha: 0.2),
                        labelStyle: TextStyle(
                          fontSize: 12,
                          color: _selectedPhase == null
                              ? AppColors.primary
                              : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
                        ),
                      ),
                    ),
                    ...data.phases.map((phase) => Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ChoiceChip(
                        label: Text(phase.phaseName),
                        selected: _selectedPhase == phase.phaseName,
                        onSelected: (_) =>
                            setState(() => _selectedPhase = phase.phaseName),
                        selectedColor: AppColors.primary.withValues(alpha: 0.2),
                        labelStyle: TextStyle(
                          fontSize: 12,
                          color: _selectedPhase == phase.phaseName
                              ? AppColors.primary
                              : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
                        ),
                      ),
                    )),
                  ],
                ),
              ),
            // Grid
            Expanded(
              child: GridView.builder(
                padding: const EdgeInsets.all(16),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  childAspectRatio: 0.85,
                ),
                itemCount: filtered.length,
                itemBuilder: (context, index) {
                  final img = filtered[index];
                  return GestureDetector(
                    onTap: () => _showFullImage(context, img, isDarkMode),
                    child: _GlassContainer(
                      isDarkMode: isDarkMode,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: ClipRRect(
                              borderRadius: const BorderRadius.vertical(
                                  top: Radius.circular(16)),
                              child: img.imageUrl != null
                                  ? Image.network(
                                      img.imageUrl!,
                                      width: double.infinity,
                                      fit: BoxFit.cover,
                                      errorBuilder: (_, e, s) =>
                                          const Center(
                                        child: Icon(Icons.broken_image_rounded,
                                            size: 40,
                                            color: AppColors.textHint),
                                      ),
                                    )
                                  : const Center(
                                      child: Icon(Icons.image_rounded,
                                          size: 40,
                                          color: AppColors.textHint),
                                    ),
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.all(8),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  img.title ?? (isSwahili ? 'Picha' : 'Photo'),
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: isDarkMode
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                if (img.takenAt != null)
                                  Text(
                                    _formatDate(img.takenAt),
                                    style: const TextStyle(
                                      fontSize: 10,
                                      color: AppColors.textSecondary,
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        );
      },
    );
  }

  void _showFullImage(
      BuildContext context, ProgressImage img, bool isDarkMode) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => Scaffold(
          backgroundColor: Colors.black,
          appBar: AppBar(
            backgroundColor: Colors.black,
            foregroundColor: Colors.white,
            title: Text(img.title ?? '', style: const TextStyle(fontSize: 14)),
          ),
          body: Center(
            child: img.imageUrl != null
                ? InteractiveViewer(
                    child: Image.network(
                      img.imageUrl!,
                      fit: BoxFit.contain,
                      errorBuilder: (_, e, s) => const Icon(
                        Icons.broken_image_rounded,
                        size: 64,
                        color: Colors.white54,
                      ),
                    ),
                  )
                : const Icon(
                    Icons.image_rounded,
                    size: 64,
                    color: Colors.white54,
                  ),
          ),
        ),
      ),
    );
  }
}

// ═══════════════════════════════════════════════════
// REPORTS TAB
// ═══════════════════════════════════════════════════

class _ReportsTab extends ConsumerWidget {
  final int projectId;
  final Future<void> Function(String url, String fileName) downloadPdf;

  const _ReportsTab({
    required this.projectId,
    required this.downloadPdf,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(projectReportsProvider(projectId));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return asyncData.when(
      loading: _buildTabLoading,
      error: (e, _) => _buildTabError(
        e,
        isSwahili,
        () => ref.invalidate(projectReportsProvider(projectId)),
      ),
      data: (data) {
        if (data.dailyReports.isEmpty && data.siteVisits.isEmpty) {
          return _buildEmpty(
            isSwahili ? 'Hakuna ripoti bado' : 'No reports yet',
            Icons.assessment_rounded,
          );
        }
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Daily Reports
              if (data.dailyReports.isNotEmpty) ...[
                Text(
                  isSwahili ? 'Ripoti za Kila Siku' : 'Daily Reports',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 12),
                ...data.dailyReports.map((r) => _DailyReportCard(
                  report: r,
                  isDarkMode: isDarkMode,
                  isSwahili: isSwahili,
                )),
              ],

              // Site Visits
              if (data.siteVisits.isNotEmpty) ...[
                const SizedBox(height: 20),
                Text(
                  isSwahili ? 'Ziara za Tovuti' : 'Site Visits',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 12),
                ...data.siteVisits.map((v) => _SiteVisitCard(
                  visit: v,
                  projectId: projectId,
                  isDarkMode: isDarkMode,
                  isSwahili: isSwahili,
                  downloadPdf: downloadPdf,
                )),
              ],

              const SizedBox(height: 32),
            ],
          ),
        );
      },
    );
  }
}

class _DailyReportCard extends StatefulWidget {
  final DailyReport report;
  final bool isDarkMode;
  final bool isSwahili;

  const _DailyReportCard({
    required this.report,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  State<_DailyReportCard> createState() => _DailyReportCardState();
}

class _DailyReportCardState extends State<_DailyReportCard> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final r = widget.report;
    return _GlassContainer(
      isDarkMode: widget.isDarkMode,
      margin: const EdgeInsets.only(bottom: 10),
      child: Column(
        children: [
          GestureDetector(
            onTap: () => setState(() => _expanded = !_expanded),
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: AppColors.info.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.description_rounded,
                        color: AppColors.info, size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _formatDate(r.reportDate),
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: widget.isDarkMode ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                        if (r.supervisor != null)
                          Text(
                            r.supervisor!,
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        if (r.weatherConditions != null)
                          Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: _InfoChip(
                              icon: Icons.cloud_rounded,
                              label: r.weatherConditions!,
                              isDarkMode: widget.isDarkMode,
                            ),
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 4),
                  AnimatedRotation(
                    turns: _expanded ? 0.5 : 0,
                    duration: const Duration(milliseconds: 200),
                    child: const Icon(Icons.keyboard_arrow_down_rounded,
                        color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
          ),
          if (_expanded)
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Divider(),
                  if (r.workCompleted != null)
                    _ReportField(
                      label: widget.isSwahili ? 'Kazi Iliyofanywa' : 'Work Completed',
                      value: r.workCompleted!,
                      isDarkMode: widget.isDarkMode,
                    ),
                  if (r.materialsUsed != null)
                    _ReportField(
                      label: widget.isSwahili ? 'Vifaa' : 'Materials Used',
                      value: r.materialsUsed!,
                      isDarkMode: widget.isDarkMode,
                    ),
                  if (r.laborHours != null)
                    _ReportField(
                      label: widget.isSwahili ? 'Saa za Kazi' : 'Labor Hours',
                      value: r.laborHours!,
                      isDarkMode: widget.isDarkMode,
                    ),
                  if (r.issuesFaced != null)
                    _ReportField(
                      label: widget.isSwahili ? 'Changamoto' : 'Issues Faced',
                      value: r.issuesFaced!,
                      isDarkMode: widget.isDarkMode,
                      isWarning: true,
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _ReportField extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final bool isWarning;

  const _ReportField({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.isWarning = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color: isWarning ? AppColors.warning : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: TextStyle(
              fontSize: 13,
              color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _SiteVisitCard extends StatelessWidget {
  final SiteVisit visit;
  final int projectId;
  final bool isDarkMode;
  final bool isSwahili;
  final Future<void> Function(String url, String fileName) downloadPdf;

  const _SiteVisitCard({
    required this.visit,
    required this.projectId,
    required this.isDarkMode,
    required this.isSwahili,
    required this.downloadPdf,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        visit.documentNumber ?? (isSwahili ? 'Ziara' : 'Site Visit'),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      Text(
                        _formatDate(visit.visitDate),
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                _buildStatusBadge(visit.status),
                const SizedBox(width: 8),
                GestureDetector(
                  onTap: () {
                    final url =
                        '${AppConfig.clientBaseUrl}/projects/$projectId/site-visits/${visit.id}/pdf';
                    downloadPdf(
                        url, 'site-visit-${visit.documentNumber ?? visit.id}.pdf');
                  },
                  child: Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: AppColors.error.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.picture_as_pdf_rounded,
                        size: 18, color: AppColors.error),
                  ),
                ),
              ],
            ),
            if (visit.inspector != null) ...[
              const SizedBox(height: 8),
              _InfoChip(
                icon: Icons.person_rounded,
                label: visit.inspector!,
                isDarkMode: isDarkMode,
              ),
            ],
            if (visit.findings != null && visit.findings!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                isSwahili ? 'Matokeo:' : 'Findings:',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              ),
              Text(
                visit.findings!,
                style: TextStyle(
                  fontSize: 12,
                  color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
                ),
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
            ],
            if (visit.recommendations != null &&
                visit.recommendations!.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                isSwahili ? 'Mapendekezo:' : 'Recommendations:',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              ),
              Text(
                visit.recommendations!,
                style: TextStyle(
                  fontSize: 12,
                  color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
                ),
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// ═══════════════════════════════════════════════════
// Shared small widgets
// ═══════════════════════════════════════════════════

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isDarkMode;
  final bool isWarning;

  const _InfoChip({
    required this.icon,
    required this.label,
    required this.isDarkMode,
    this.isWarning = false,
  });

  @override
  Widget build(BuildContext context) {
    final color = isWarning
        ? AppColors.error
        : (isDarkMode ? Colors.white54 : AppColors.textSecondary);
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 13, color: color),
        const SizedBox(width: 4),
        Flexible(
          child: Text(label, style: TextStyle(fontSize: 11, color: color)),
        ),
      ],
    );
  }
}
