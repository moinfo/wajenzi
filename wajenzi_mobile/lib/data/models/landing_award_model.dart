/// Award served by the public landing CMS endpoint
/// (`GET /api/v1/public/awards`). Plain model (no codegen).
class LandingAwardModel {
  final int id;
  final String? year;
  final String title;
  final String? subtitle;
  final String? organization;
  final String? description;

  /// Raw storage path as returned by the API, e.g. `/storage/landing/...`.
  final String? image;

  const LandingAwardModel({
    required this.id,
    this.year,
    required this.title,
    this.subtitle,
    this.organization,
    this.description,
    this.image,
  });

  factory LandingAwardModel.fromJson(Map<String, dynamic> json) {
    return LandingAwardModel(
      id: (json['id'] as num).toInt(),
      year: json['year']?.toString(),
      title: (json['title'] ?? '').toString(),
      subtitle: json['subtitle']?.toString(),
      organization: json['organization']?.toString(),
      description: json['description']?.toString(),
      image: json['image']?.toString(),
    );
  }
}
