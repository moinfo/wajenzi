import 'kpi_common.dart';

/// A role option for the "New Template" role dropdown (roles without a
/// template yet). Mirrors the `available_roles[]` payload.
class KpiRoleOption {
  final int id;
  final String name;

  const KpiRoleOption({required this.id, required this.name});

  factory KpiRoleOption.fromJson(Map<String, dynamic> json) {
    return KpiRoleOption(
      id: kpiToInt(json['id']),
      name: kpiToString(json['name']),
    );
  }
}

/// A row in the templates list (card grid). Mirrors `templates[]`.
class KpiTemplateSummary {
  final int id;
  final String code;
  final String name;
  final String role;
  final String frequency;
  final bool isActive;
  final int itemCount;
  final double totalWeight;

  const KpiTemplateSummary({
    required this.id,
    required this.code,
    required this.name,
    required this.role,
    required this.frequency,
    required this.isActive,
    required this.itemCount,
    required this.totalWeight,
  });

  /// True when the template's total weight is within 0.01 of 100%.
  bool get weightBalanced => (totalWeight - 100).abs() <= 0.01;

  factory KpiTemplateSummary.fromJson(Map<String, dynamic> json) {
    return KpiTemplateSummary(
      id: kpiToInt(json['id']),
      code: kpiToString(json['code']),
      name: kpiToString(json['name']),
      role: kpiToString(json['role']),
      frequency: kpiToString(json['frequency']),
      isActive: json['is_active'] == true,
      itemCount: kpiToInt(json['item_count']),
      totalWeight: kpiToDouble(json['total_weight']),
    );
  }
}

/// The full list response: templates + roles available for a new template.
class KpiTemplatesResponse {
  final List<KpiTemplateSummary> templates;
  final List<KpiRoleOption> availableRoles;

  const KpiTemplatesResponse({
    required this.templates,
    required this.availableRoles,
  });

  factory KpiTemplatesResponse.fromJson(Map<String, dynamic> json) {
    final rawTemplates = json['templates'] as List? ?? [];
    final rawRoles = json['available_roles'] as List? ?? [];
    return KpiTemplatesResponse(
      templates: rawTemplates
          .map((e) => KpiTemplateSummary.fromJson(e as Map<String, dynamic>))
          .toList(),
      availableRoles: rawRoles
          .map((e) => KpiRoleOption.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

/// A single editable KPI item inside a template section.
class KpiTemplateItem {
  final int id;
  final String kpa;
  final String measure;
  final String target;
  final double weight;
  final String? measurementMethod;
  final int sortOrder;

  const KpiTemplateItem({
    required this.id,
    required this.kpa,
    required this.measure,
    required this.target,
    required this.weight,
    this.measurementMethod,
    required this.sortOrder,
  });

  factory KpiTemplateItem.fromJson(Map<String, dynamic> json) {
    return KpiTemplateItem(
      id: kpiToInt(json['id']),
      kpa: kpiToString(json['kpa']),
      measure: kpiToString(json['measure']),
      target: kpiToString(json['target']),
      weight: kpiToDouble(json['weight']),
      measurementMethod: kpiToStringOrNull(json['measurement_method']),
      sortOrder: kpiToInt(json['sort_order']),
    );
  }
}

/// A section of a template with its editable items.
class KpiTemplateSectionDetail {
  final int id;
  final String code;
  final String title;
  final double weightTotal;
  final bool isCommon;
  final int sortOrder;
  final List<KpiTemplateItem> items;
  final double itemsWeight;

  const KpiTemplateSectionDetail({
    required this.id,
    required this.code,
    required this.title,
    required this.weightTotal,
    required this.isCommon,
    required this.sortOrder,
    required this.items,
    required this.itemsWeight,
  });

  /// True when the section's item weights match its target within 0.01.
  bool get weightBalanced => (itemsWeight - weightTotal).abs() <= 0.01;

  factory KpiTemplateSectionDetail.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return KpiTemplateSectionDetail(
      id: kpiToInt(json['id']),
      code: kpiToString(json['code']),
      title: kpiToString(json['title']),
      weightTotal: kpiToDouble(json['weight_total']),
      isCommon: json['is_common'] == true,
      sortOrder: kpiToInt(json['sort_order']),
      items: rawItems
          .map((e) => KpiTemplateItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      itemsWeight: kpiToDouble(json['items_weight']),
    );
  }
}

/// Full detail of a template (header + sections + items).
class KpiTemplateDetail {
  final int id;
  final String code;
  final String name;
  final String role;
  final int? roleId;
  final String frequency;
  final String? description;
  final bool isActive;
  final int itemCount;
  final double totalWeight;
  final List<KpiTemplateSectionDetail> sections;

  const KpiTemplateDetail({
    required this.id,
    required this.code,
    required this.name,
    required this.role,
    this.roleId,
    required this.frequency,
    this.description,
    required this.isActive,
    required this.itemCount,
    required this.totalWeight,
    required this.sections,
  });

  /// True when the template's total weight is within 0.01 of 100%.
  bool get weightBalanced => (totalWeight - 100).abs() <= 0.01;

  factory KpiTemplateDetail.fromJson(Map<String, dynamic> json) {
    final rawSections = json['sections'] as List? ?? [];
    return KpiTemplateDetail(
      id: kpiToInt(json['id']),
      code: kpiToString(json['code']),
      name: kpiToString(json['name']),
      role: kpiToString(json['role']),
      roleId: json['role_id'] == null ? null : kpiToInt(json['role_id']),
      frequency: kpiToString(json['frequency']),
      description: kpiToStringOrNull(json['description']),
      isActive: json['is_active'] == true,
      itemCount: kpiToInt(json['item_count']),
      totalWeight: kpiToDouble(json['total_weight']),
      sections: rawSections
          .map((e) =>
              KpiTemplateSectionDetail.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

/// The valid template frequencies (matches server `in:` validation).
const List<String> kpiTemplateFrequencies = [
  'monthly',
  'quarterly',
  'biannual',
  'annual',
];

String kpiFrequencyLabel(String frequency) {
  if (frequency.isEmpty) return '—';
  return frequency[0].toUpperCase() + frequency.substring(1);
}
