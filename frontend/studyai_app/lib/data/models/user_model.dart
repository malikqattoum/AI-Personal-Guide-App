class User {
  final int id;
  final String uuid;
  final String name;
  final String email;
  final bool isPremium;
  final int studyStreak;
  final int totalStudyMinutes;
  final DateTime? createdAt;

  User({
    required this.id,
    required this.uuid,
    required this.name,
    required this.email,
    this.isPremium = false,
    this.studyStreak = 0,
    this.totalStudyMinutes = 0,
    this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      uuid: json['uuid'] ?? '',
      name: json['name'],
      email: json['email'],
      isPremium: json['is_premium'] ?? false,
      studyStreak: json['study_streak'] ?? 0,
      totalStudyMinutes: json['total_study_minutes'] ?? 0,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'uuid': uuid,
      'name': name,
      'email': email,
      'is_premium': isPremium,
      'study_streak': studyStreak,
      'total_study_minutes': totalStudyMinutes,
    };
  }
}
