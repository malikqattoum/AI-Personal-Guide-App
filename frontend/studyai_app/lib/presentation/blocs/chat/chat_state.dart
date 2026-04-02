import 'package:equatable/equatable.dart';
import '../../../data/models/chat_message_model.dart';

abstract class ChatState extends Equatable {
  const ChatState();

  @override
  List<Object?> get props => [];
}

class ChatInitial extends ChatState {}

class ChatLoading extends ChatState {}

class ChatLoaded extends ChatState {
  final List<ChatMessage> messages;
  final bool isTyping;
  final String? currentDocumentUuid;

  const ChatLoaded({
    required this.messages,
    this.isTyping = false,
    this.currentDocumentUuid,
  });

  @override
  List<Object?> get props => [messages, isTyping, currentDocumentUuid];

  ChatLoaded copyWith({
    List<ChatMessage>? messages,
    bool? isTyping,
    String? currentDocumentUuid,
  }) {
    return ChatLoaded(
      messages: messages ?? this.messages,
      isTyping: isTyping ?? this.isTyping,
      currentDocumentUuid: currentDocumentUuid ?? this.currentDocumentUuid,
    );
  }
}

class ChatError extends ChatState {
  final String message;

  const ChatError(this.message);

  @override
  List<Object?> get props => [message];
}
