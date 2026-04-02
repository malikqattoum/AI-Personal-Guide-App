import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../core/constants/app_colors.dart';
import '../blocs/flashcard/flashcard_cubit.dart';
import '../blocs/flashcard/flashcard_state.dart';

class FlashcardsPage extends StatefulWidget {
  const FlashcardsPage({super.key});

  @override
  State<FlashcardsPage> createState() => _FlashcardsPageState();
}

class _FlashcardsPageState extends State<FlashcardsPage> {
  @override
  void initState() {
    super.initState();
    context.read<FlashcardCubit>().loadFlashcards();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Flashcards'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => context.read<FlashcardCubit>().loadFlashcards(),
          ),
        ],
      ),
      body: BlocBuilder<FlashcardCubit, FlashcardState>(
        builder: (context, state) {
          if (state is FlashcardLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (state is FlashcardError) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.error_outline,
                    size: 64,
                    color: AppColors.error.withValues(alpha: 0.5),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Error loading flashcards',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 8),
                  Text(state.message),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () =>
                        context.read<FlashcardCubit>().loadFlashcards(),
                    child: const Text('Try Again'),
                  ),
                ],
              ),
            );
          }

          if (state is FlashcardLoaded) {
            if (state.flashcards.isEmpty) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.style_outlined,
                      size: 80,
                      color: AppColors.textHint.withValues(alpha: 0.5),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'No flashcards yet',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            color: AppColors.textSecondary,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Generate flashcards from your documents',
                      textAlign: TextAlign.center,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],
                ),
              );
            }

            return _buildFlashcardView(context, state);
          }

          if (state is FlashcardGenerating) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const CircularProgressIndicator(),
                  const SizedBox(height: 16),
                  Text(
                    'Generating flashcards...',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ],
              ),
            );
          }

          return const SizedBox.shrink();
        },
      ),
    );
  }

  Widget _buildFlashcardView(BuildContext context, FlashcardLoaded state) {
    final flashcard = state.currentFlashcard;
    if (flashcard == null) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          // Progress indicator
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                '${state.currentIndex + 1}',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      color: AppColors.primary,
                      fontWeight: FontWeight.bold,
                    ),
              ),
              Text(
                ' / ${state.flashcards.length}',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      color: AppColors.textSecondary,
                    ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          LinearProgressIndicator(
            value: (state.currentIndex + 1) / state.flashcards.length,
            backgroundColor: AppColors.primary.withValues(alpha: 0.1),
            valueColor:
                const AlwaysStoppedAnimation<Color>(AppColors.primary),
          ),
          const SizedBox(height: 32),
          // Flashcard
          Expanded(
            child: GestureDetector(
              onTap: () => context.read<FlashcardCubit>().flipCard(),
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 300),
                transitionBuilder: (child, animation) {
                  return FadeTransition(opacity: animation, child: child);
                },
                child: Container(
                  key: ValueKey(state.isFlipped),
                  width: double.infinity,
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    gradient: state.isFlipped
                        ? AppColors.primaryGradient
                        : null,
                    color: state.isFlipped ? null : AppColors.surface,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.1),
                        blurRadius: 20,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        state.isFlipped ? Icons.lightbulb : Icons.help_outline,
                        size: 48,
                        color: state.isFlipped
                            ? Colors.white.withValues(alpha: 0.5)
                            : AppColors.primary.withValues(alpha: 0.5),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        state.isFlipped ? flashcard.backText : flashcard.frontText,
                        style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                              color: state.isFlipped
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 24),
                      Text(
                        state.isFlipped ? 'Answer' : 'Question',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: state.isFlipped
                                  ? Colors.white.withValues(alpha: 0.7)
                                  : AppColors.textHint,
                            ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 24),
          // Tap to flip hint
          Text(
            'Tap card to flip',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: AppColors.textHint,
                ),
          ),
          const SizedBox(height: 24),
          // Navigation buttons
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              if (state.hasPrevious)
                OutlinedButton.icon(
                  onPressed: () =>
                      context.read<FlashcardCubit>().previousCard(),
                  icon: const Icon(Icons.arrow_back),
                  label: const Text('Previous'),
                )
              else
                const SizedBox(width: 100),
              if (state.hasNext)
                ElevatedButton.icon(
                  onPressed: () => context.read<FlashcardCubit>().nextCard(),
                  icon: const Icon(Icons.arrow_forward),
                  label: const Text('Next'),
                )
              else
                const SizedBox(width: 100),
            ],
          ),
        ],
      ),
    );
  }
}
