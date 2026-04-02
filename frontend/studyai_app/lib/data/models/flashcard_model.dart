class Flashcard {
  final int id;
  final String uuid;
  final int documentId;
  final int userId;
  final String frontText;
  final String backText;
  final String difficulty;
  final int timesReviewed;
  final int timesCorrect;
  final DateTime? lastReviewedAt;
  final DateTime? createdAt;

  Flashcard({
    required this.id,
    required this.uuid,
    required this.documentId,
    required this.userId,
    required this.frontText,
    required this.backText,
    this.difficulty = 'medium',
    this.timesReviewed = 0,
    this.timesCorrect = 0,
    this.lastReviewedAt,
    this.createdAt,
  });

  factory Flashcard.fromJson(Map<String, dynamic> json) {
    return Flashcard(
      id: json['id'],
      uuid: json['uuid'],
      documentId: json['document_id'],
      userId: json['user_id'],
      frontText: json['front_text'],
      backText: json['back_text'],
      difficulty: json['difficulty'] ?? 'medium',
      timesReviewed: json['times_reviewed'] ?? 0,
      timesCorrect: json['times_correct'] ?? 0,
      lastReviewedAt: json['last_reviewed_at'] != null
          ? DateTime.parse(json['last_reviewed_at'])
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }

  double get successRate {
    if (timesReviewed == 0) return 0;
    return (timesCorrect / timesReviewed) * 100;
  }
}
