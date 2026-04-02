import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../data/models/flashcard_model.dart';
import '../../../data/repositories/flashcard_repository_impl.dart';
import 'flashcard_state.dart';

class FlashcardCubit extends Cubit<FlashcardState> {
  final FlashcardRepository _flashcardRepository;

  FlashcardCubit(this._flashcardRepository) : super(FlashcardInitial());

  Future<void> loadFlashcards() async {
    emit(FlashcardLoading());
    try {
      final flashcards = await _flashcardRepository.getFlashcards();
      emit(FlashcardLoaded(flashcards: flashcards));
    } catch (e) {
      emit(FlashcardError(e.toString()));
    }
  }

  Future<void> loadFlashcardsByDocument(String documentUuid) async {
    emit(FlashcardLoading());
    try {
      final flashcards =
          await _flashcardRepository.getFlashcardsByDocument(documentUuid);
      emit(FlashcardLoaded(flashcards: flashcards));
    } catch (e) {
      emit(FlashcardError(e.toString()));
    }
  }

  Future<void> generateFlashcards({
    required String documentUuid,
    int count = 5,
  }) async {
    final currentState = state;
    final List<Flashcard> existingFlashcards =
        currentState is FlashcardLoaded ? currentState.flashcards : <Flashcard>[];

    emit(FlashcardGenerating(existingFlashcards: existingFlashcards));

    try {
      final newFlashcards = await _flashcardRepository.generateFlashcards(
        documentUuid: documentUuid,
        count: count,
      );

      final allFlashcards = [...existingFlashcards, ...newFlashcards];
      emit(FlashcardLoaded(flashcards: allFlashcards));
    } catch (e) {
      emit(FlashcardError(e.toString()));
    }
  }

  void flipCard() {
    final currentState = state;
    if (currentState is FlashcardLoaded) {
      emit(currentState.copyWith(isFlipped: !currentState.isFlipped));
    }
  }

  void nextCard() {
    final currentState = state;
    if (currentState is FlashcardLoaded && currentState.hasNext) {
      emit(currentState.copyWith(
        currentIndex: currentState.currentIndex + 1,
        isFlipped: false,
      ));
    }
  }

  void previousCard() {
    final currentState = state;
    if (currentState is FlashcardLoaded && currentState.hasPrevious) {
      emit(currentState.copyWith(
        currentIndex: currentState.currentIndex - 1,
        isFlipped: false,
      ));
    }
  }

  Future<void> reviewFlashcard({
    required String uuid,
    required bool correct,
  }) async {
    final currentState = state;
    if (currentState is! FlashcardLoaded) return;

    try {
      await _flashcardRepository.reviewFlashcard(uuid: uuid, correct: correct);
    } catch (e) {
      // Silently fail review update
    }
  }
}
