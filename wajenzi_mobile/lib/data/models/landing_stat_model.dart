/// Hero stat served by the public landing CMS (`GET /api/v1/public/stats`).
class LandingStatModel {
  final int id;
  final String value;
  final String label;

  const LandingStatModel({
    required this.id,
    required this.value,
    required this.label,
  });

  factory LandingStatModel.fromJson(Map<String, dynamic> json) {
    return LandingStatModel(
      id: (json['id'] as num).toInt(),
      value: (json['value'] ?? '').toString(),
      label: (json['label'] ?? '').toString(),
    );
  }
}
