import 'kpi_common.dart';

/// Response of `GET /performance/create-info`.
class KpiCreateInfo {
  final bool canCreate;
  final bool hasSupervisor;
  final String? reason;
  final KpiRef? autoTemplate;
  final List<KpiRef> templates;
  final String defaultPeriodLabel;
  final String? defaultPeriodStart;
  final String? defaultPeriodEnd;

  const KpiCreateInfo({
    required this.canCreate,
    required this.hasSupervisor,
    this.reason,
    this.autoTemplate,
    required this.templates,
    required this.defaultPeriodLabel,
    this.defaultPeriodStart,
    this.defaultPeriodEnd,
  });

  factory KpiCreateInfo.fromJson(Map<String, dynamic> json) {
    final rawTemplates = json['templates'] as List? ?? [];
    final autoRaw = json['auto_template'];
    return KpiCreateInfo(
      canCreate: json['can_create'] == true,
      hasSupervisor: json['has_supervisor'] == true,
      reason: kpiToStringOrNull(json['reason']),
      autoTemplate: autoRaw is Map<String, dynamic>
          ? KpiRef.fromJson(autoRaw)
          : null,
      templates: rawTemplates
          .map((e) => KpiRef.fromJson(e as Map<String, dynamic>))
          .toList(),
      defaultPeriodLabel: kpiToString(json['default_period_label']),
      defaultPeriodStart: kpiToStringOrNull(json['default_period_start']),
      defaultPeriodEnd: kpiToStringOrNull(json['default_period_end']),
    );
  }
}
