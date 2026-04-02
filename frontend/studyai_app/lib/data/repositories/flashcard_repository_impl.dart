import '../datasources/flashcard_remote_datasource.dart';
import '../models/flashcard_model.dart';

class FlashcardRepository {
  final FlashcardRemoteDatasource _remoteDatasource;

  FlashcardRepository(this._remoteDatasource);

  Future<List<Flashcard>> getFlashcards() {
    return _remoteDatasource.getFlashcards();
  }

  Future<List<Flashcard>> generateFlashcards({
    required String documentUuid,
    int count = 5,
  }) {
    return _remoteDatasource.generateFlashcards(
      documentUuid: documentUuid,
      count: count,
    );
  }

  Future<List<Flashcard>> getFlashcardsByDocument(String documentUuid) {
    return _remoteDatasource.getFlashcardsByDocument(documentUuid);
  }

  Future<Flashcard> reviewFlashcard({
    required String uuid,
    required bool correct,
  }) {
    return _remoteDatasource.reviewFlashcard(
      uuid: uuid,
      correct: correct,
    );
  }

  Future<void> deleteFlashcard(String uuid) {
    return _remoteDatasource.deleteFlashcard(uuid);
  }
}
