import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../data/repositories/chat_repository_impl.dart';
import '../../../data/models/chat_message_model.dart';
import 'chat_state.dart';

class ChatCubit extends Cubit<ChatState> {
  final ChatRepository _chatRepository;

  ChatCubit(this._chatRepository) : super(ChatInitial());

  Future<void> loadMessages({String? documentUuid}) async {
    emit(ChatLoading());
    try {
      final messages = await _chatRepository.getMessages(documentUuid: documentUuid);
      emit(ChatLoaded(
        messages: messages,
        currentDocumentUuid: documentUuid,
      ));
    } catch (e) {
      emit(ChatError(e.toString()));
    }
  }

  Future<void> sendMessage({
    required String message,
    String? documentUuid,
  }) async {
    final currentState = state;
    if (currentState is! ChatLoaded) return;

    // Add user message immediately
    final userMessage = ChatMessage(
      id: DateTime.now().millisecondsSinceEpoch,
      uuid: '',
      userId: 0,
      role: 'user',
      messageText: message,
      documentId: documentUuid != null ? 0 : null,
    );

    final updatedMessages = [...currentState.messages, userMessage];
    emit(currentState.copyWith(messages: updatedMessages, isTyping: true));

    try {
      final result = await _chatRepository.sendMessage(
        message: message,
        documentUuid: documentUuid,
      );

      final assistantMessage = result['assistant_message']!;
      emit(ChatLoaded(
        messages: [...currentState.messages, userMessage, assistantMessage],
        isTyping: false,
        currentDocumentUuid: documentUuid,
      ));
    } catch (e) {
      emit(currentState.copyWith(isTyping: false));
    }
  }

  Future<void> clearChat({String? documentUuid}) async {
    try {
      await _chatRepository.clearMessages(documentUuid: documentUuid);
      emit(ChatLoaded(
        messages: [],
        isTyping: false,
        currentDocumentUuid: documentUuid,
      ));
    } catch (e) {
      emit(ChatError(e.toString()));
    }
  }
}
