/// "About" content served by the public landing CMS endpoint
/// (`GET /api/v1/public/about`). Plain model (no codegen).
class LandingAboutModel {
  final String? foundedYear;
  final String? tagline;
  final String? story;
  final String? mission;
  final String? vision;
  final String? address;
  final String? phone;
  final String? email;
  final String? workingHours;
  final List<LandingValueModel> values;
  final List<LandingTeamMemberModel> team;

  const LandingAboutModel({
    this.foundedYear,
    this.tagline,
    this.story,
    this.mission,
    this.vision,
    this.address,
    this.phone,
    this.email,
    this.workingHours,
    this.values = const [],
    this.team = const [],
  });

  factory LandingAboutModel.fromJson(Map<String, dynamic> json) {
    return LandingAboutModel(
      foundedYear: json['founded_year']?.toString(),
      tagline: json['tagline']?.toString(),
      story: json['story']?.toString(),
      mission: json['mission']?.toString(),
      vision: json['vision']?.toString(),
      address: json['address']?.toString(),
      phone: json['phone']?.toString(),
      email: json['email']?.toString(),
      workingHours: json['working_hours']?.toString(),
      values:
          (json['values'] as List?)
              ?.whereType<Map>()
              .map(
                (e) =>
                    LandingValueModel.fromJson(Map<String, dynamic>.from(e)),
              )
              .toList() ??
          const [],
      team:
          (json['team'] as List?)
              ?.whereType<Map>()
              .map(
                (e) => LandingTeamMemberModel.fromJson(
                  Map<String, dynamic>.from(e),
                ),
              )
              .toList() ??
          const [],
    );
  }
}

class LandingValueModel {
  final int id;
  final String title;
  final String? description;

  const LandingValueModel({
    required this.id,
    required this.title,
    this.description,
  });

  factory LandingValueModel.fromJson(Map<String, dynamic> json) {
    return LandingValueModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: (json['title'] ?? '').toString(),
      description: json['description']?.toString(),
    );
  }
}

class LandingTeamMemberModel {
  final int id;
  final String name;
  final String? role;
  final String? bio;
  final String? image;

  const LandingTeamMemberModel({
    required this.id,
    required this.name,
    this.role,
    this.bio,
    this.image,
  });

  factory LandingTeamMemberModel.fromJson(Map<String, dynamic> json) {
    return LandingTeamMemberModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] ?? '').toString(),
      role: json['role']?.toString(),
      bio: json['bio']?.toString(),
      image: json['image']?.toString(),
    );
  }
}
