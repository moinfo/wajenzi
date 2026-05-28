/// Portfolio project served by the public landing CMS endpoint
/// (`GET /api/v1/public/portfolio`). Plain model (no codegen) so the landing
/// screen can be wired without a build_runner step.
class LandingProjectModel {
  final int id;
  final String title;
  final String? category;
  final String? description;
  final num? priceTzs;
  final num? priceUsd;
  final String? youtubeUrl;
  final String? model3dUrl;
  final bool hasVideo;
  final bool has3d;
  final int likesCount;
  final bool isFeatured;
  final bool liked;

  /// Raw storage path as returned by the API, e.g. `/storage/landing/...`.
  final String? image;
  final List<String> images;
  final List<String> amenities;
  final DateTime? createdAt;

  const LandingProjectModel({
    required this.id,
    required this.title,
    this.category,
    this.description,
    this.priceTzs,
    this.priceUsd,
    this.youtubeUrl,
    this.model3dUrl,
    this.hasVideo = false,
    this.has3d = false,
    this.likesCount = 0,
    this.isFeatured = false,
    this.liked = false,
    this.image,
    this.images = const [],
    this.amenities = const [],
    this.createdAt,
  });

  factory LandingProjectModel.fromJson(Map<String, dynamic> json) {
    List<String> stringList(dynamic v) =>
        (v as List?)?.map((e) => e.toString()).toList() ?? const [];

    return LandingProjectModel(
      id: (json['id'] as num).toInt(),
      title: (json['title'] ?? '').toString(),
      category: json['category']?.toString(),
      description: json['description']?.toString(),
      priceTzs: json['price_tzs'] as num?,
      priceUsd: json['price_usd'] as num?,
      youtubeUrl: json['youtube_url']?.toString(),
      model3dUrl: json['model_3d_url']?.toString(),
      hasVideo: json['has_video'] == true,
      has3d: json['has_3d'] == true,
      likesCount: (json['likes_count'] as num?)?.toInt() ?? 0,
      isFeatured: json['is_featured'] == true,
      liked: json['liked'] == true,
      image: json['image']?.toString(),
      images: stringList(json['images']),
      amenities: stringList(json['amenities']),
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  LandingProjectModel copyWith({bool? liked, int? likesCount}) {
    return LandingProjectModel(
      id: id,
      title: title,
      category: category,
      description: description,
      priceTzs: priceTzs,
      priceUsd: priceUsd,
      youtubeUrl: youtubeUrl,
      model3dUrl: model3dUrl,
      hasVideo: hasVideo,
      has3d: has3d,
      likesCount: likesCount ?? this.likesCount,
      isFeatured: isFeatured,
      liked: liked ?? this.liked,
      image: image,
      images: images,
      amenities: amenities,
      createdAt: createdAt,
    );
  }
}
