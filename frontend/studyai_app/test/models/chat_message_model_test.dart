import 'package:flutter_test/flutter_test.dart';
import 'package:studyai_app/data/models/chat_message_model.dart';

void main() {
  group('ChatMessage', () {
    test('fromJson creates ChatMessage correctly', () {
      final json = {
        'id': 1,
        'uuid': 'test-uuid-123',
        'user_id': 10,
        'document_id': 5,
        'role': 'user',
        'message_text': 'Hello, world!',
        'created_at': '2024-01-15T10:30:00Z',
      };

      final message = ChatMessage.fromJson(json);

      expect(message.id, 1);
      expect(message.uuid, 'test-uuid-123');
      expect(message.userId, 10);
      expect(message.documentId, 5);
      expect(message.role, 'user');
      expect(message.messageText, 'Hello, world!');
      expect(message.createdAt, isNotNull);
    });

    test('fromJson handles null documentId', () {
      final json = {
        'id': 1,
        'uuid': 'test-uuid',
        'user_id': 10,
        'document_id': null,
        'role': 'assistant',
        'message_text': 'Hi there!',
        'created_at': null,
      };

      final message = ChatMessage.fromJson(json);

      expect(message.documentId, isNull);
      expect(message.createdAt, isNull);
    });

    test('isUser returns true for user role', () {
      final message = ChatMessage(
        id: 1,
        uuid: 'uuid',
        userId: 1,
        role: 'user',
        messageText: 'Hello',
      );

      expect(message.isUser, true);
      expect(message.isAssistant, false);
    });

    test('isAssistant returns true for assistant role', () {
      final message = ChatMessage(
        id: 1,
        uuid: 'uuid',
        userId: 1,
        role: 'assistant',
        messageText: 'Hello',
      );

      expect(message.isUser, false);
      expect(message.isAssistant, true);
    });
  });
}
