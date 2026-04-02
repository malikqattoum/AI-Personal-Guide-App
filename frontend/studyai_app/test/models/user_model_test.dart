import 'package:flutter_test/flutter_test.dart';
import 'package:studyai_app/data/models/user_model.dart';

void main() {
  group('User', () {
    test('fromJson creates User correctly', () {
      final json = {
        'id': 1,
        'uuid': 'user-uuid-123',
        'name': 'John Doe',
        'email': 'john@example.com',
        'is_premium': true,
        'study_streak': 5,
        'total_study_minutes': 120,
        'created_at': '2024-01-15T10:30:00Z',
      };

      final user = User.fromJson(json);

      expect(user.id, 1);
      expect(user.uuid, 'user-uuid-123');
      expect(user.name, 'John Doe');
      expect(user.email, 'john@example.com');
      expect(user.isPremium, true);
      expect(user.studyStreak, 5);
      expect(user.totalStudyMinutes, 120);
      expect(user.createdAt, isNotNull);
    });

    test('fromJson handles missing optional fields with defaults', () {
      final json = {
        'id': 1,
        'name': 'Jane Doe',
        'email': 'jane@example.com',
      };

      final user = User.fromJson(json);

      expect(user.uuid, '');
      expect(user.isPremium, false);
      expect(user.studyStreak, 0);
      expect(user.totalStudyMinutes, 0);
      expect(user.createdAt, isNull);
    });

    test('toJson produces correct map', () {
      final user = User(
        id: 1,
        uuid: 'user-uuid',
        name: 'Test User',
        email: 'test@example.com',
        isPremium: true,
        studyStreak: 3,
        totalStudyMinutes: 60,
      );

      final json = user.toJson();

      expect(json['id'], 1);
      expect(json['uuid'], 'user-uuid');
      expect(json['name'], 'Test User');
      expect(json['email'], 'test@example.com');
      expect(json['is_premium'], true);
      expect(json['study_streak'], 3);
      expect(json['total_study_minutes'], 60);
    });
  });
}
