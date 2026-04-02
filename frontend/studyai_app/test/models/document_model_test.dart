import 'package:flutter_test/flutter_test.dart';
import 'package:studyai_app/data/models/document_model.dart';

void main() {
  group('Document', () {
    test('fromJson creates Document correctly', () {
      final json = {
        'id': 1,
        'uuid': 'doc-uuid-123',
        'user_id': 10,
        'title': 'Test Document',
        'file_path': '/path/to/file.pdf',
        'file_name': 'file.pdf',
        'file_size': 1024000,
        'mime_type': 'application/pdf',
        'page_count': 10,
        'extracted_text': 'Some extracted text...',
        'source_type': 'pdf',
        'status': 'completed',
        'created_at': '2024-01-15T10:30:00Z',
        'updated_at': '2024-01-15T11:00:00Z',
      };

      final doc = Document.fromJson(json);

      expect(doc.id, 1);
      expect(doc.uuid, 'doc-uuid-123');
      expect(doc.userId, 10);
      expect(doc.title, 'Test Document');
      expect(doc.filePath, '/path/to/file.pdf');
      expect(doc.fileName, 'file.pdf');
      expect(doc.fileSize, 1024000);
      expect(doc.mimeType, 'application/pdf');
      expect(doc.pageCount, 10);
      expect(doc.extractedText, 'Some extracted text...');
      expect(doc.sourceType, 'pdf');
      expect(doc.status, 'completed');
    });

    test('fromJson handles null optional fields', () {
      final json = {
        'id': 1,
        'uuid': 'doc-uuid',
        'user_id': 10,
        'title': 'Minimal Doc',
        'source_type': 'youtube',
        'status': 'pending',
      };

      final doc = Document.fromJson(json);

      expect(doc.filePath, isNull);
      expect(doc.fileName, isNull);
      expect(doc.fileSize, isNull);
      expect(doc.mimeType, isNull);
      expect(doc.pageCount, isNull);
      expect(doc.extractedText, isNull);
      expect(doc.createdAt, isNull);
      expect(doc.updatedAt, isNull);
    });

    test('isPdf returns true for pdf source type', () {
      final doc = Document(
        id: 1,
        uuid: 'uuid',
        userId: 1,
        title: 'Test',
        sourceType: 'pdf',
        status: 'completed',
      );

      expect(doc.isPdf, true);
      expect(doc.isYoutube, false);
    });

    test('isYoutube returns true for youtube source type', () {
      final doc = Document(
        id: 1,
        uuid: 'uuid',
        userId: 1,
        title: 'Test',
        sourceType: 'youtube',
        status: 'completed',
      );

      expect(doc.isYoutube, true);
      expect(doc.isPdf, false);
    });

    test('isProcessing returns true for processing status', () {
      final doc = Document(
        id: 1,
        uuid: 'uuid',
        userId: 1,
        title: 'Test',
        status: 'processing',
      );

      expect(doc.isProcessing, true);
      expect(doc.isCompleted, false);
      expect(doc.isFailed, false);
    });

    test('isCompleted returns true for completed status', () {
      final doc = Document(
        id: 1,
        uuid: 'uuid',
        userId: 1,
        title: 'Test',
        status: 'completed',
      );

      expect(doc.isCompleted, true);
      expect(doc.isFailed, false);
    });

    test('isFailed returns true for failed status', () {
      final doc = Document(
        id: 1,
        uuid: 'uuid',
        userId: 1,
        title: 'Test',
        status: 'failed',
      );

      expect(doc.isFailed, true);
      expect(doc.isCompleted, false);
    });
  });
}
