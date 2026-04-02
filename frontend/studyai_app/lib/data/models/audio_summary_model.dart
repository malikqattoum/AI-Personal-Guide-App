class AudioSummary {
  final int id;
  final String uuid;
  final int documentId;
  final int userId;
  final String title;
  final String audioPath;
  final int durationSeconds;
  final String? transcript;
  final String status;
  final DateTime? createdAt;

  AudioSummary({
    required this.id,
    required this.uuid,
    required this.documentId,
    required this.userId,
    required this.title,
    required this.audioPath,
    required this.durationSeconds,
    this.transcript,
    this.status = 'pending',
    this.createdAt,
  });

  factory AudioSummary.fromJson(Map<String, dynamic> json) {
    return AudioSummary(
      id: json['id'],
      uuid: json['uuid'],
      documentId: json['document_id'],
      userId: json['user_id'],
      title: json['title'],
      audioPath: json['audio_path'],
      durationSeconds: json['duration_seconds'],
      transcript: json['transcript'],
      status: json['status'] ?? 'pending',
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }

  bool get isProcessing => status == 'processing';
  bool get isCompleted => status == 'completed';
  bool get isFailed => status == 'failed';

  String get formattedDuration {
    final minutes = durationSeconds ~/ 60;
    final seconds = durationSeconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }
}
