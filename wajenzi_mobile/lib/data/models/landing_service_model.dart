/// Service served by the public landing CMS endpoint
/// (`GET /api/v1/public/services`). Plain model (no codegen).
class LandingServiceModel {
  final int id;
  final String title;
  final String? shortDescription;
  final String? fullDescription;
  final String? image;
  final List<String> features;

  const LandingServiceModel({
    required this.id,
    required this.title,
    this.shortDescription,
    this.fullDescription,
    this.image,
    this.features = const [],
  });

  factory LandingServiceModel.fromJson(Map<String, dynamic> json) {
    return LandingServiceModel(
      id: (json['id'] as num).toInt(),
      title: (json['title'] ?? '').toString(),
      shortDescription: json['short_description']?.toString(),
      fullDescription: json['full_description']?.toString(),
      image: json['image']?.toString(),
      features:
          (json['features'] as List?)?.map((e) => e.toString()).toList() ??
          const [],
    );
  }
}
