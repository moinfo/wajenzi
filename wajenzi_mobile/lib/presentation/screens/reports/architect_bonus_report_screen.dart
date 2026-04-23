import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/empty_state_widget.dart';
import '../../widgets/common/loading_widget.dart';

String _bonusTr(
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

class ArchitectBonusReportScreen extends ConsumerStatefulWidget {
  const ArchitectBonusReportScreen({
    super.key,
    this.useDrawerMenu = false,
  });

  final bool useDrawerMenu;

  @override
  ConsumerState<ArchitectBonusReportScreen> createState() =>
      _ArchitectBonusReportScreenState();
}

class _ArchitectBonusReportScreenState
    extends ConsumerState<ArchitectBonusReportScreen> {
  final NumberFormat _money = NumberFormat('#,##0.##');
  bool _isLoading = false;
  Map<String, dynamic> _reportData = <String, dynamic>{};
  String _selectedMonth = DateFormat('yyyy-MM').format(DateTime.now());
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get(
        '/architect-bonus/report',
        queryParameters: {'month': _selectedMonth},
      );

      setState(() {
        _reportData = response.data['data'] is Map
            ? Map<String, dynamic>.from(response.data['data'] as Map)
            : <String, dynamic>{};
        _isLoading = false;
      });
    } catch (error) {
      setState(() {
        _isLoading = false;
        _errorMessage = _humanizeError(error);
      });
    }
  }

  String _humanizeError(Object error) {
    final language = ref.read(currentLanguageProvider);
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map<String, dynamic>) {
        final message = data['message']?.toString();
        if (message != null && message.isNotEmpty) {
          return message;
        }
      }

      switch (error.response?.statusCode) {
        case 401:
          return _bonusTr(
            language,
            en: 'Authentication required. Please login again.',
            sw: 'Uthibitishaji unahitajika. Tafadhali ingia tena.',
            fr: 'Authentification requise. Veuillez vous reconnecter.',
            ar: 'يلزم تسجيل الدخول. يرجى تسجيل الدخول مرة أخرى.',
          );
        case 403:
          return _bonusTr(
            language,
            en: 'Permission denied. You may not have access to bonus reports.',
            sw: 'Ruhusa imekataliwa. Huenda huna ufikiaji wa ripoti za bonasi.',
            fr: 'Permission refusée. Vous n’avez peut-être pas accès aux rapports de bonus.',
            ar: 'تم رفض الإذن. قد لا تكون لديك صلاحية الوصول إلى تقارير المكافآت.',
          );
        case 404:
          return _bonusTr(
            language,
            en: 'Bonus report endpoint not found. Please check API configuration.',
            sw: 'Njia ya ripoti ya bonasi haikupatikana. Tafadhali angalia usanidi wa API.',
            fr: 'Point d’accès au rapport de bonus introuvable. Veuillez vérifier la configuration de l’API.',
            ar: 'لم يتم العثور على واجهة تقرير المكافآت. يرجى التحقق من إعدادات الـ API.',
          );
      }
    }

    return _bonusTr(
      language,
      en: 'Error loading bonus report.',
      sw: 'Hitilafu katika kupakia ripoti ya bonasi.',
      fr: 'Erreur lors du chargement du rapport de bonus.',
      ar: 'حدث خطأ أثناء تحميل تقرير المكافآت.',
    );
  }

  Future<void> _pickMonth() async {
    final now = DateTime.now();
    final current = DateTime.parse('$_selectedMonth-01');
    final months = List<DateTime>.generate(
      24,
      (index) => DateTime(now.year, now.month - index, 1),
    );

    final picked = await showModalBottomSheet<DateTime>(
      context: context,
      showDragHandle: true,
      builder: (sheetContext) {
        return SafeArea(
          child: ListView.builder(
            shrinkWrap: true,
            itemCount: months.length,
            itemBuilder: (context, index) {
              final month = months[index];
              final selected =
                  month.year == current.year && month.month == current.month;
              return ListTile(
                title: Text(DateFormat('MMMM yyyy').format(month)),
                trailing: selected
                    ? const Icon(Icons.check_circle, color: Colors.green)
                    : null,
                onTap: () => Navigator.pop(sheetContext, month),
              );
            },
          ),
        );
      },
    );

    if (picked == null) return;

    final nextMonth = DateFormat('yyyy-MM').format(picked);
    if (nextMonth == _selectedMonth) return;

    setState(() {
      _selectedMonth = nextMonth;
    });
    _loadReport();
  }

  String _formatCurrency(dynamic value) {
    final number = value is num
        ? value.toDouble()
        : double.tryParse(value?.toString() ?? '') ?? 0;
    return _money.format(number);
  }

  String _formatPercent(dynamic value) {
    final number = value is num
        ? value.toDouble()
        : double.tryParse(value?.toString() ?? '') ?? 0;
    if (number <= 0) return '-';
    return '${(number * 100).round()}%';
  }

  String _formatDecimal(dynamic value) {
    final number = value is num
        ? value.toDouble()
        : double.tryParse(value?.toString() ?? '') ?? 0;
    return number.toStringAsFixed(
      number.truncateToDouble() == number ? 0 : 3,
    );
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'paid':
      case 'scored':
        return Colors.green;
      case 'no_bonus':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final architectSummary =
        _reportData['architect_summary'] as List<dynamic>? ?? <dynamic>[];
    final tasks = _reportData['tasks'] as List<dynamic>? ?? <dynamic>[];

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: Icon(
            widget.useDrawerMenu
                ? Icons.menu_rounded
                : Icons.arrow_back_rounded,
          ),
          onPressed: () {
            if (widget.useDrawerMenu) {
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer();
            } else {
              context.go('/reports');
            }
          },
        ),
        title: Text(
          _bonusTr(
            language,
            en: 'Bonus Report',
            sw: 'Ripoti ya Bonasi',
            fr: 'Rapport des bonus',
            ar: 'تقرير المكافآت',
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_month),
            onPressed: _pickMonth,
            tooltip: _bonusTr(
              language,
              en: 'Select Month',
              sw: 'Chagua Mwezi',
              fr: 'Choisir le mois',
              ar: 'اختر الشهر',
            ),
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadReport,
            tooltip: _bonusTr(
              language,
              en: 'Refresh',
              sw: 'Onyesha Upya',
              fr: 'Actualiser',
              ar: 'تحديث',
            ),
          ),
        ],
      ),
      body: _isLoading
          ? LoadingWidget(
              message: _bonusTr(
                language,
                en: 'Loading report...',
                sw: 'Inapakia ripoti...',
                fr: 'Chargement du rapport...',
                ar: 'جارٍ تحميل التقرير...',
              ),
            )
          : _errorMessage != null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.error_outline, size: 52, color: Colors.red),
                    const SizedBox(height: 12),
                    Text(_errorMessage!, textAlign: TextAlign.center),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: _loadReport,
                      icon: const Icon(Icons.refresh),
                      label: Text(
                        _bonusTr(
                          language,
                          en: 'Retry',
                          sw: 'Jaribu tena',
                          fr: 'Réessayer',
                          ar: 'أعد المحاولة',
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            )
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _bonusTr(
                              language,
                              en: 'Bonus Report',
                              sw: 'Ripoti ya Bonasi',
                              fr: 'Rapport des bonus',
                              ar: 'تقرير المكافآت',
                            ),
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            '${_bonusTr(language, en: 'Month', sw: 'Mwezi', fr: 'Mois', ar: 'الشهر')}: ${DateFormat('MMMM yyyy').format(DateTime.parse('$_selectedMonth-01'))}',
                            style: TextStyle(
                              fontSize: 15,
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: _SummaryCard(
                          title: _bonusTr(
                            language,
                            en: 'Total Tasks',
                            sw: 'Jumla ya Kazi',
                            fr: 'Total des tâches',
                            ar: 'إجمالي المهام',
                          ),
                          value: '${_reportData['total_tasks'] ?? 0}',
                          icon: Icons.task_alt,
                          color: Colors.orange,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _SummaryCard(
                          title: _bonusTr(language, en: 'Total Units', sw: 'Jumla ya Units', fr: 'Total des unités', ar: 'إجمالي الوحدات'),
                          value: '${_reportData['grand_total_units'] ?? 0}',
                          icon: Icons.work_outline,
                          color: Colors.blue,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  _SummaryCard(
                    title: _bonusTr(language, en: 'Total Bonus', sw: 'Jumla ya Bonasi', fr: 'Bonus total', ar: 'إجمالي المكافآت'),
                    value:
                        'TZS ${_formatCurrency(_reportData['grand_total_bonus'])}',
                    icon: Icons.payments_outlined,
                    color: Colors.green,
                  ),
                  const SizedBox(height: 24),
                  if (architectSummary.isEmpty && tasks.isEmpty)
                    EmptyStateWidget(
                      message: _bonusTr(
                        language,
                        en: 'No scored tasks found for this month',
                        sw: 'Hakuna kazi zilizopimwa kwa mwezi huu',
                        fr: 'Aucune tâche évaluée pour ce mois',
                        ar: 'لا توجد مهام تم تقييمها لهذا الشهر',
                      ),
                      icon: Icons.bar_chart,
                    )
                  else ...[
                    Text(
                      _bonusTr(
                        language,
                        en: 'Architect Summary',
                        sw: 'Muhtasari wa Architect',
                        fr: 'Résumé de l’architecte',
                        ar: 'ملخص المعماري',
                      ),
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 12),
                    ...architectSummary.map((architect) {
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: _ArchitectSummaryCard(
                          architect: Map<String, dynamic>.from(architect as Map),
                          language: language,
                          formatCurrency: _formatCurrency,
                          formatPercent: _formatPercent,
                        ),
                      );
                    }),
                    const SizedBox(height: 12),
                    Text(
                      _bonusTr(
                        language,
                        en: 'Task Details',
                        sw: 'Maelezo ya Kazi',
                        fr: 'Détails des tâches',
                        ar: 'تفاصيل المهام',
                      ),
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 12),
                    ...tasks.map((task) {
                      final item = Map<String, dynamic>.from(task as Map);
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: _TaskDetailCard(
                          task: item,
                          language: language,
                          statusColor: _statusColor(
                            item['status']?.toString() ?? '',
                          ),
                          formatCurrency: _formatCurrency,
                          formatPercent: _formatPercent,
                          formatDecimal: _formatDecimal,
                        ),
                      );
                    }),
                  ],
                ],
              ),
            ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String title;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(icon, color: color, size: 22),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(fontSize: 13, color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    value,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: color,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ArchitectSummaryCard extends StatelessWidget {
  const _ArchitectSummaryCard({
    required this.architect,
    required this.language,
    required this.formatCurrency,
    required this.formatPercent,
  });

  final Map<String, dynamic> architect;
  final AppLanguage language;
  final String Function(dynamic value) formatCurrency;
  final String Function(dynamic value) formatPercent;

  @override
  Widget build(BuildContext context) {
    final architectInfo = architect['architect'] is Map
        ? Map<String, dynamic>.from(architect['architect'] as Map)
        : const <String, dynamic>{};
    final name = architectInfo['name']?.toString() ?? 'Unknown';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.blue,
                  child: Text(
                    name.isEmpty ? 'A' : name.substring(0, 1).toUpperCase(),
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    name,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Wrap(
              spacing: 12,
              runSpacing: 10,
              children: [
                _MetricChip(
                  label: _bonusTr(language, en: 'Tasks', sw: 'Kazi', fr: 'Tâches', ar: 'المهام'),
                  value: '${architect['tasks_count'] ?? 0}',
                ),
                _MetricChip(
                  label: _bonusTr(language, en: 'Units', sw: 'Units', fr: 'Unités', ar: 'الوحدات'),
                  value: '${architect['total_units'] ?? 0}',
                ),
                _MetricChip(
                  label: _bonusTr(language, en: 'Bonus', sw: 'Bonasi', fr: 'Bonus', ar: 'المكافأة'),
                  value: 'TZS ${formatCurrency(architect['total_bonus'])}',
                ),
                _MetricChip(
                  label: _bonusTr(
                    language,
                    en: 'Avg Performance',
                    sw: 'Wastani wa Utendaji',
                    fr: 'Performance moyenne',
                    ar: 'متوسط الأداء',
                  ),
                  value: formatPercent(architect['avg_performance']),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _TaskDetailCard extends StatelessWidget {
  const _TaskDetailCard({
    required this.task,
    required this.language,
    required this.statusColor,
    required this.formatCurrency,
    required this.formatPercent,
    required this.formatDecimal,
  });

  final Map<String, dynamic> task;
  final AppLanguage language;
  final Color statusColor;
  final String Function(dynamic value) formatCurrency;
  final String Function(dynamic value) formatPercent;
  final String Function(dynamic value) formatDecimal;

  @override
  Widget build(BuildContext context) {
    final architect = task['architect'] is Map
        ? Map<String, dynamic>.from(task['architect'] as Map)
        : const <String, dynamic>{};
    final status =
        task['status_label']?.toString() ?? task['status']?.toString() ?? '-';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        task['task_number']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        task['project_name']?.toString() ?? '-',
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${_bonusTr(language, en: 'Architect', sw: 'Architect', fr: 'Architecte', ar: 'المعماري')}: ${architect['name'] ?? '-'}',
                        style: TextStyle(color: Colors.grey[700]),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    status.toUpperCase(),
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      color: statusColor,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _MetricChip(label: 'SP', value: formatDecimal(task['schedule_performance'])),
                _MetricChip(label: 'DQ', value: formatDecimal(task['design_quality_score'])),
                _MetricChip(label: 'CA', value: formatDecimal(task['client_approval_efficiency'])),
                _MetricChip(label: 'PS', value: formatPercent(task['performance_score'])),
                _MetricChip(
                  label: _bonusTr(language, en: 'Units', sw: 'Units', fr: 'Unités', ar: 'الوحدات'),
                  value: '${task['final_units'] ?? '-'}',
                ),
                _MetricChip(
                  label: _bonusTr(language, en: 'Bonus', sw: 'Bonasi', fr: 'Bonus', ar: 'المكافأة'),
                  value: 'TZS ${formatCurrency(task['bonus_amount'])}',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricChip extends StatelessWidget {
  const _MetricChip({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.grey.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[600])),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}
