import 'package:equatable/equatable.dart';
import '../../../data/models/flashcard_model.dart';

abstract class FlashcardState extends Equatable {
  const FlashcardState();

  @override
  List<Object?> get props => [];
}

class FlashcardInitial extends FlashcardState {}

class FlashcardLoading extends FlashcardState {}

class FlashcardLoaded extends FlashcardState {
  final List<Flashcard> flashcards;
  final int currentIndex;
  final bool isFlipped;

  const FlashcardLoaded({
    required this.flashcards,
    this.currentIndex = 0,
    this.isFlipped = false,
  });

  @override
  List<Object?> get props => [flashcards, currentIndex, isFlipped];

  FlashcardLoaded copyWith({
    List<Flashcard>? flashcards,
    int? currentIndex,
    bool? isFlipped,
  }) {
    return FlashcardLoaded(
      flashcards: flashcards ?? this.flashcards,
      currentIndex: currentIndex ?? this.currentIndex,
      isFlipped: isFlipped ?? this.isFlipped,
    );
  }

  Flashcard? get currentFlashcard {
    if (flashcards.isEmpty || currentIndex >= flashcards.length) {
      return null;
    }
    return flashcards[currentIndex];
  }

  bool get hasNext => currentIndex < flashcards.length - 1;
  bool get hasPrevious => currentIndex > 0;
}

class FlashcardGenerating extends FlashcardState {
  final List<Flashcard> existingFlashcards;

  const FlashcardGenerating({this.existingFlashcards = const []});

  @override
  List<Object?> get props => [existingFlashcards];
}

class FlashcardError extends FlashcardState {
  final String message;

  const FlashcardError(this.message);

  @override
  List<Object?> get props => [message];
}
