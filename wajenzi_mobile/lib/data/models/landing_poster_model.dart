/// Home-banner poster served by the public landing CMS
/// (`GET /api/v1/public/posters`).
class LandingPosterModel {
  final int id;
  final String? title;
  final String? subtitle;
  final String? image;
  final String? linkUrl;
  final String? youtubeUrl;

  const LandingPosterModel({
    required this.id,
    this.title,
    this.subtitle,
    this.image,
    this.linkUrl,
    this.youtubeUrl,
  });

  bool get hasLink => (linkUrl != null && linkUrl!.isNotEmpty);
  bool get hasVideo => (youtubeUrl != null && youtubeUrl!.isNotEmpty);

  /// The tap target, preferring an explicit link, then a video.
  String? get tapUrl => hasLink ? linkUrl : (hasVideo ? youtubeUrl : null);

  factory LandingPosterModel.fromJson(Map<String, dynamic> json) {
    return LandingPosterModel(
      id: (json['id'] as num).toInt(),
      title: json['title']?.toString(),
      subtitle: json['subtitle']?.toString(),
      image: json['image']?.toString(),
      linkUrl: json['link_url']?.toString(),
      youtubeUrl: json['youtube_url']?.toString(),
    );
  }
}
