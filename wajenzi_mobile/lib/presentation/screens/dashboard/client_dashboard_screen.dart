import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../providers/auth_provider.dart';
import '../../providers/client_dashboard_provider.dart';
import '../../providers/settings_provider.dart';
import '../../../data/datasources/remote/client_api.dart';

String _clientDashboardTr(
  AppLanguage language, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) {
  return switch (language) {
    AppLanguage.swahili => sw ?? en,
    AppLanguage.french => fr ?? en,
    AppLanguage.arabic => ar ?? en,
    AppLanguage.english => en,
  };
}

class ClientDashboardScreen extends ConsumerStatefulWidget {
  const ClientDashboardScreen({super.key});

  @override
  ConsumerState<ClientDashboardScreen> createState() =>
      _ClientDashboardScreenState();
}

class _ClientDashboardScreenState extends ConsumerState<ClientDashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(
      () => ref.read(clientDashboardProvider.notifier).fetchDashboard(),
    );
  }

  String _compact(double amount) {
    if (amount >= 1e9) return 'TZS ${(amount / 1e9).toStringAsFixed(1)}B';
    if (amount >= 1e6) return 'TZS ${(amount / 1e6).toStringAsFixed(1)}M';
    if (amount >= 1e3) return 'TZS ${(amount / 1e3).toStringAsFixed(0)}K';
    return 'TZS ${amount.toStringAsFixed(0)}';
  }

  String _full(double amount) {
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
  }

  @override
  Widget build(BuildContext context) {
    final dashboardState = ref.watch(clientDashboardProvider);
    final user = ref.watch(authStateProvider).valueOrNull?.user;
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final scaffoldKey = ref.read(rootScaffoldKeyProvider);
    final cs = Theme.of(context).colorScheme;

    return Scaffold(
      backgroundColor: cs.surface,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => scaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _clientDashboardTr(
            language,
            en: 'Dashboard',
            sw: 'Dashibodi',
            fr: 'Tableau de bord',
            ar: 'لوحة التحكم',
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () => context.push('/notifications'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () =>
            ref.read(clientDashboardProvider.notifier).fetchDashboard(),
        child: dashboardState.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            language: language,
            onRetry: () =>
                ref.read(clientDashboardProvider.notifier).fetchDashboard(),
          ),
          data: (data) => _DashboardBody(
            data: data,
            firstName: user?.name.split(' ').first ?? 'User',
            language: language,
            isDarkMode: isDarkMode,
            compact: _compact,
            full: _full,
          ),
        ),
      ),
    );
  }
}

// ─── Dashboard Body ───────────────────────────────────────────────────────────

class _DashboardBody extends StatelessWidget {
  final ClientDashboardData data;
  final String firstName;
  final AppLanguage language;
  final bool isDarkMode;
  final String Function(double) compact;
  final String Function(double) full;

  const _DashboardBody({
    required this.data,
    required this.firstName,
    required this.language,
    required this.isDarkMode,
    required this.compact,
    required this.full,
  });

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final isSwahili = language == AppLanguage.swahili;

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Hero banner ────────────────────────────────────────────────
          _HeroBanner(
            firstName: firstName,
            language: language,
            isDarkMode: isDarkMode,
          ),

          // ── Stats row ──────────────────────────────────────────────────
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 0),
            child: Row(
              children: [
                _StatTile(
                  label: _clientDashboardTr(
                    language,
                    en: 'Total Projects',
                    sw: 'Miradi Yote',
                    fr: 'Projets au total',
                    ar: 'إجمالي المشاريع',
                  ),
                  value: '${data.totalProjects}',
                  icon: Icons.folder_copy_rounded,
                  color: AppColors.secondary,
                  isDarkMode: isDarkMode,
                ),
                const SizedBox(width: 12),
                _StatTile(
                  label: _clientDashboardTr(
                    language,
                    en: 'Active',
                    sw: 'Miradi Hai',
                    fr: 'Actifs',
                    ar: 'نشطة',
                  ),
                  value: '${data.activeProjects}',
                  icon: Icons.construction_rounded,
                  color: AppColors.success,
                  isDarkMode: isDarkMode,
                ),
                const SizedBox(width: 12),
                _StatTile(
                  label: _clientDashboardTr(
                    language,
                    en: 'Invoiced',
                    sw: 'Ankara',
                    fr: 'Facture',
                    ar: 'المفوتر',
                  ),
                  value: compact(data.totalInvoiced),
                  icon: Icons.receipt_long_rounded,
                  color: AppColors.warning,
                  isDarkMode: isDarkMode,
                  compact: true,
                ),
              ],
            ),
          ),

          // ── Contract value banner ──────────────────────────────────────
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: _ContractBanner(
              value: compact(data.totalContractValue),
              fullValue: full(data.totalContractValue),
              language: language,
              isDarkMode: isDarkMode,
            ),
          ),

          // ── Projects header ────────────────────────────────────────────
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _clientDashboardTr(
                          language,
                          en: 'Your Projects',
                          sw: 'Miradi Yako',
                          fr: 'Vos projets',
                          ar: 'مشاريعك',
                        ),
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          color: cs.onSurface,
                        ),
                      ),
                      Text(
                        isSwahili
                            ? '${data.projects.length} miradi kwa jumla'
                            : language == AppLanguage.french
                            ? '${data.projects.length} projet${data.projects.length == 1 ? '' : 's'} au total'
                            : '${data.projects.length} project${data.projects.length == 1 ? '' : 's'} total',
                        style: TextStyle(
                          fontSize: 12,
                          color: cs.onSurface.withValues(alpha: 0.5),
                        ),
                      ),
                    ],
                  ),
                ),
                if (data.projects.isNotEmpty)
                  TextButton.icon(
                    onPressed: () => context.go('/projects'),
                    icon: const Icon(Icons.arrow_forward_rounded, size: 16),
                    label: Text(
                      _clientDashboardTr(
                        language,
                        en: 'View all',
                        sw: 'Zote',
                        fr: 'Tout voir',
                        ar: 'عرض الكل',
                      ),
                    ),
                    style: TextButton.styleFrom(
                      foregroundColor: AppColors.primary,
                      visualDensity: VisualDensity.compact,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // ── Project list ───────────────────────────────────────────────
          if (data.projects.isEmpty)
            _EmptyProjects(language: language, isDarkMode: isDarkMode)
          else
            ...data.projects.map(
              (p) => GestureDetector(
                onTap: () =>
                    context.push('/project/${p.id}', extra: p.projectName),
                child: _ProjectCard(
                  project: p,
                  language: language,
                  isDarkMode: isDarkMode,
                  formatCurrency: full,
                ),
              ),
            ),

          const SizedBox(height: 100),
        ],
      ),
    );
  }
}

// ─── Hero Banner ──────────────────────────────────────────────────────────────

class _HeroBanner extends StatelessWidget {
  final String firstName;
  final AppLanguage language;
  final bool isDarkMode;

  const _HeroBanner({
    required this.firstName,
    required this.language,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDarkMode
              ? [const Color(0xFF0D3B34), const Color(0xFF0A2A40)]
              : [const Color(0xFF1ABC9C), const Color(0xFF2980B9)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: Colors.white.withValues(alpha: 0.18),
                child: Text(
                  firstName.isNotEmpty ? firstName[0].toUpperCase() : 'U',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 18,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _clientDashboardTr(
                        language,
                        en: 'Welcome back,',
                        sw: 'Habari, $firstName!',
                        fr: 'Bon retour,',
                        ar: 'مرحبا بعودتك،',
                      ),
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                        fontSize: 13,
                      ),
                    ),
                    if (language != AppLanguage.swahili)
                      Text(
                        firstName,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 22,
                        ),
                      ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 5,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.calendar_today_rounded,
                      size: 12,
                      color: Colors.white,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      DateFormat('MMM yyyy').format(DateTime.now()),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            _clientDashboardTr(
              language,
              en: 'Here\'s an overview of your construction projects',
              sw: 'Hapa kuna hali ya miradi yako ya ujenzi',
              fr: 'Voici un apercu de vos projets de construction',
              ar: 'هذه نظرة عامة على مشاريع البناء الخاصة بك',
            ),
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.75),
              fontSize: 13,
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Stat Tile ────────────────────────────────────────────────────────────────

class _StatTile extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final bool isDarkMode;
  final bool compact;

  const _StatTile({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
    required this.isDarkMode,
    this.compact = false,
  });

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
        decoration: BoxDecoration(
          color: cs.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: cs.onSurface.withValues(alpha: 0.07),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDarkMode ? 0.15 : 0.05),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(icon, size: 16, color: color),
            ),
            const SizedBox(height: 10),
            Text(
              value,
              style: TextStyle(
                fontSize: compact ? 13 : 20,
                fontWeight: FontWeight.w800,
                color: cs.onSurface,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                color: cs.onSurface.withValues(alpha: 0.5),
                fontWeight: FontWeight.w500,
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

// ─── Contract Banner ──────────────────────────────────────────────────────────

class _ContractBanner extends StatelessWidget {
  final String value;
  final String fullValue;
  final AppLanguage language;
  final bool isDarkMode;

  const _ContractBanner({
    required this.value,
    required this.fullValue,
    required this.language,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDarkMode
              ? [const Color(0xFF1A3A2A), const Color(0xFF122B20)]
              : [
                  AppColors.success.withValues(alpha: 0.08),
                  AppColors.success.withValues(alpha: 0.03),
                ],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AppColors.success.withValues(alpha: isDarkMode ? 0.25 : 0.18),
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: AppColors.success.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.account_balance_rounded,
              size: 22,
              color: AppColors.success,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _clientDashboardTr(
                    language,
                    en: 'Total Contract Value',
                    sw: 'Jumla ya Mikataba',
                    fr: 'Valeur totale des contrats',
                    ar: 'إجمالي قيمة العقود',
                  ),
                  style: TextStyle(
                    fontSize: 12,
                    color: cs.onSurface.withValues(alpha: 0.55),
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: AppColors.success,
                  ),
                ),
                Text(
                  fullValue,
                  style: TextStyle(
                    fontSize: 11,
                    color: cs.onSurface.withValues(alpha: 0.4),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Project Card ─────────────────────────────────────────────────────────────

class _ProjectCard extends StatelessWidget {
  final ClientProject project;
  final AppLanguage language;
  final bool isDarkMode;
  final String Function(double) formatCurrency;

  const _ProjectCard({
    required this.project,
    required this.language,
    required this.isDarkMode,
    required this.formatCurrency,
  });

  Color get _accent {
    switch (project.status?.toUpperCase()) {
      case 'APPROVED':
        return AppColors.success;
      case 'COMPLETED':
        return const Color(0xFF9B59B6);
      case 'REJECTED':
        return AppColors.error;
      case 'IN_PROGRESS':
      case 'SUBMITTED':
        return AppColors.secondary;
      case 'PENDING':
      case 'CREATED':
        return AppColors.warning;
      default:
        return AppColors.draft;
    }
  }

  String _formatDate(String? date) {
    if (date == null) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date;
    }
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final accent = _accent;
    final rawStatus = project.status?.toUpperCase() ?? '';
    final statusLabel = switch (language) {
      AppLanguage.swahili => switch (rawStatus) {
        'APPROVED' => 'Imeidhinishwa',
        'COMPLETED' => 'Imekamilika',
        'REJECTED' => 'Imekataliwa',
        'IN_PROGRESS' => 'Inaendelea',
        'SUBMITTED' => 'Imewasilishwa',
        'PENDING' => 'Inasubiri',
        'CREATED' => 'Imeundwa',
        _ => rawStatus.replaceAll('_', ' '),
      },
      AppLanguage.french => switch (rawStatus) {
        'APPROVED' => 'Approuve',
        'COMPLETED' => 'Termine',
        'REJECTED' => 'Rejete',
        'IN_PROGRESS' => 'En cours',
        'SUBMITTED' => 'Soumis',
        'PENDING' => 'En attente',
        'CREATED' => 'Cree',
        _ => rawStatus.replaceAll('_', ' '),
      },
      AppLanguage.arabic => switch (rawStatus) {
        'APPROVED' => 'معتمد',
        'COMPLETED' => 'مكتمل',
        'REJECTED' => 'مرفوض',
        'IN_PROGRESS' => 'قيد التنفيذ',
        'SUBMITTED' => 'تم الإرسال',
        'PENDING' => 'معلق',
        'CREATED' => 'تم الإنشاء',
        _ => rawStatus.replaceAll('_', ' '),
      },
      AppLanguage.english => rawStatus
          .replaceAll('_', ' ')
          .split(' ')
          .map((w) => w.isEmpty ? '' : '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}')
          .join(' '),
    };

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      decoration: BoxDecoration(
        color: cs.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: cs.onSurface.withValues(alpha: 0.07)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.15 : 0.05),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Left accent bar
              Container(width: 4, color: accent),

              // Content
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Name + status
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  project.projectName,
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                    color: cs.onSurface,
                                  ),
                                ),
                                if (project.documentNumber != null)
                                  Text(
                                    project.documentNumber!,
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: cs.onSurface.withValues(alpha: 0.45),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 9,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: accent.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              statusLabel,
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: accent,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),

                      // Date range
                      Row(
                        children: [
                          Icon(
                            Icons.date_range_rounded,
                            size: 13,
                            color: cs.onSurface.withValues(alpha: 0.4),
                          ),
                          const SizedBox(width: 5),
                          Text(
                            '${_formatDate(project.startDate)} → ${_formatDate(project.expectedEndDate)}',
                            style: TextStyle(
                              fontSize: 11,
                              color: cs.onSurface.withValues(alpha: 0.55),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),

                      // Contract value
                      Row(
                        children: [
                          Icon(
                            Icons.monetization_on_rounded,
                            size: 14,
                            color: AppColors.success,
                          ),
                          const SizedBox(width: 5),
                          Text(
                            formatCurrency(project.contractValue),
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: AppColors.success,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),

                      // Count chips
                      Row(
                        children: [
                          _Chip(
                            icon: Icons.list_alt_rounded,
                            label: 'BOQ',
                            count: project.boqsCount,
                          ),
                          const SizedBox(width: 8),
                          _Chip(
                            icon: Icons.receipt_rounded,
                            label: _clientDashboardTr(
                              language,
                              en: 'Invoices',
                              sw: 'Ankara',
                              fr: 'Factures',
                              ar: 'الفواتير',
                            ),
                            count: project.invoicesCount,
                          ),
                          const SizedBox(width: 8),
                          _Chip(
                            icon: Icons.assignment_rounded,
                            label: _clientDashboardTr(
                              language,
                              en: 'Reports',
                              sw: 'Ripoti',
                              fr: 'Rapports',
                              ar: 'التقارير',
                            ),
                            count: project.dailyReportsCount,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),

              // Chevron
              Center(
                child: Padding(
                  padding: const EdgeInsets.only(right: 10),
                  child: Icon(
                    Icons.chevron_right_rounded,
                    color: cs.onSurface.withValues(alpha: 0.3),
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

class _Chip extends StatelessWidget {
  final IconData icon;
  final String label;
  final int count;
  const _Chip({required this.icon, required this.label, required this.count});

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: AppColors.primary),
          const SizedBox(width: 4),
          Text(
            '$count $label',
            style: TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: cs.brightness == Brightness.dark
                  ? AppColors.primaryLight
                  : AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Empty State ──────────────────────────────────────────────────────────────

class _EmptyProjects extends StatelessWidget {
  final AppLanguage language;
  final bool isDarkMode;
  const _EmptyProjects({required this.language, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: cs.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: cs.onSurface.withValues(alpha: 0.07)),
        ),
        child: Column(
          children: [
            Icon(
              Icons.folder_open_rounded,
              size: 52,
              color: cs.onSurface.withValues(alpha: 0.25),
            ),
            const SizedBox(height: 12),
            Text(
              _clientDashboardTr(
                language,
                en: 'No projects yet',
                sw: 'Hakuna miradi bado',
                fr: 'Aucun projet pour le moment',
                ar: 'لا توجد مشاريع بعد',
              ),
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: cs.onSurface.withValues(alpha: 0.6),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              _clientDashboardTr(
                language,
                en: 'Your projects will appear here',
                sw: 'Miradi yako itaonekana hapa',
                fr: 'Vos projets apparaitront ici',
                ar: 'ستظهر مشاريعك هنا',
              ),
              style: TextStyle(
                fontSize: 13,
                color: cs.onSurface.withValues(alpha: 0.4),
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Error View ───────────────────────────────────────────────────────────────

class _ErrorView extends StatelessWidget {
  final Object error;
  final AppLanguage language;
  final VoidCallback onRetry;
  const _ErrorView({
    required this.error,
    required this.language,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 56, color: AppColors.error),
            const SizedBox(height: 16),
            Text(
              _clientDashboardTr(
                language,
                en: 'Something went wrong',
                sw: 'Hitilafu imetokea',
                fr: 'Un probleme est survenu',
                ar: 'حدث خطأ ما',
              ),
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              _clientDashboardTr(
                language,
                en: 'Could not load your dashboard.',
                sw: 'Hatukuweza kupakia dashibodi yako.',
                fr: 'Impossible de charger votre tableau de bord.',
                ar: 'تعذر تحميل لوحة التحكم الخاصة بك.',
              ),
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.55),
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: Text(
                _clientDashboardTr(
                  language,
                  en: 'Try again',
                  sw: 'Jaribu tena',
                  fr: 'Reessayer',
                  ar: 'حاول مرة أخرى',
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
