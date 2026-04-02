class ChatMessage {
  final int id;
  final String uuid;
  final int userId;
  final int? documentId;
  final String role;
  final String messageText;
  final DateTime? createdAt;

  ChatMessage({
    required this.id,
    required this.uuid,
    required this.userId,
    this.documentId,
    required this.role,
    required this.messageText,
    this.createdAt,
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id'],
      uuid: json['uuid'],
      userId: json['user_id'],
      documentId: json['document_id'],
      role: json['role'],
      messageText: json['message_text'],
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
    );
  }

  bool get isUser => role == 'user';
  bool get isAssistant => role == 'assistant';
}
