import '../../core/network/api_client.dart';
import '../../core/constants/api_constants.dart';
import '../models/flashcard_model.dart';

class FlashcardRemoteDatasource {
  final ApiClient _apiClient;

  FlashcardRemoteDatasource(this._apiClient);

  Future<List<Flashcard>> getFlashcards() async {
    final response = await _apiClient.get(ApiConstants.flashcards);
    final List<dynamic> flashcardsJson = response.data['flashcards'];
    return flashcardsJson.map((json) => Flashcard.fromJson(json)).toList();
  }

  Future<List<Flashcard>> generateFlashcards({
    required String documentUuid,
    int count = 5,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.generateFlashcards,
      data: {
        'document_uuid': documentUuid,
        'count': count,
      },
    );
    final List<dynamic> flashcardsJson = response.data['flashcards'];
    return flashcardsJson.map((json) => Flashcard.fromJson(json)).toList();
  }

  Future<List<Flashcard>> getFlashcardsByDocument(String documentUuid) async {
    final response = await _apiClient.get(
      '${ApiConstants.documents}/$documentUuid/flashcards',
    );
    final List<dynamic> flashcardsJson = response.data['flashcards'];
    return flashcardsJson.map((json) => Flashcard.fromJson(json)).toList();
  }

  Future<Flashcard> reviewFlashcard({
    required String uuid,
    required bool correct,
  }) async {
    final response = await _apiClient.put(
      '${ApiConstants.flashcards}/$uuid/review',
      data: {'correct': correct},
    );
    return Flashcard.fromJson(response.data['flashcard']);
  }

  Future<void> deleteFlashcard(String uuid) async {
    await _apiClient.delete('${ApiConstants.flashcards}/$uuid');
  }
}
