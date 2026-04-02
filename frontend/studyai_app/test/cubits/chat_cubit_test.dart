import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/annotations.dart';
import 'package:mockito/mockito.dart';
import 'package:studyai_app/data/models/chat_message_model.dart';
import 'package:studyai_app/data/repositories/chat_repository_impl.dart';
import 'package:studyai_app/presentation/blocs/chat/chat_cubit.dart';
import 'package:studyai_app/presentation/blocs/chat/chat_state.dart';

@GenerateMocks([ChatRepository])
import 'chat_cubit_test.mocks.dart';

void main() {
  late ChatCubit chatCubit;
  late MockChatRepository mockChatRepository;

  setUp(() {
    mockChatRepository = MockChatRepository();
    chatCubit = ChatCubit(mockChatRepository);
  });

  tearDown(() {
    chatCubit.close();
  });

  group('ChatCubit', () {
    test('initial state is ChatInitial', () {
      expect(chatCubit.state, isA<ChatInitial>());
    });

    blocTest<ChatCubit, ChatState>(
      'emits [ChatLoading, ChatLoaded] when loadMessages succeeds',
      build: () {
        final messages = [
          ChatMessage(
            id: 1,
            uuid: 'msg-1',
            userId: 1,
            role: 'user',
            messageText: 'Hello',
          ),
        ];
        when(mockChatRepository.getMessages(documentUuid: anyNamed('documentUuid')))
            .thenAnswer((_) async => messages);
        return chatCubit;
      },
      act: (cubit) => cubit.loadMessages(),
      expect: () => [
        isA<ChatLoading>(),
        isA<ChatLoaded>(),
      ],
    );

    blocTest<ChatCubit, ChatState>(
      'emits [ChatLoading, ChatError] when loadMessages fails',
      build: () {
        when(mockChatRepository.getMessages(documentUuid: anyNamed('documentUuid')))
            .thenThrow(Exception('Network error'));
        return chatCubit;
      },
      act: (cubit) => cubit.loadMessages(),
      expect: () => [
        isA<ChatLoading>(),
        isA<ChatError>(),
      ],
    );

    blocTest<ChatCubit, ChatState>(
      'emits ChatLoaded with empty messages when clearChat succeeds',
      build: () {
        when(mockChatRepository.clearMessages(documentUuid: anyNamed('documentUuid')))
            .thenAnswer((_) async {});
        return chatCubit;
      },
      act: (cubit) => cubit.clearChat(),
      expect: () => [
        isA<ChatLoaded>(),
      ],
    );

    blocTest<ChatCubit, ChatState>(
      'emits ChatError when clearChat fails',
      build: () {
        when(mockChatRepository.clearMessages(documentUuid: anyNamed('documentUuid')))
            .thenThrow(Exception('Clear failed'));
        return chatCubit;
      },
      act: (cubit) => cubit.clearChat(),
      expect: () => [
        isA<ChatError>(),
      ],
    );

    test('sendMessage does nothing when state is not ChatLoaded', () async {
      // Start with ChatInitial state
      final messages = <ChatMessage>[];
      when(mockChatRepository.sendMessage(
        message: anyNamed('message'),
        documentUuid: anyNamed('documentUuid'),
      )).thenAnswer((_) async => {
        'assistant_message': ChatMessage(
          id: 2,
          uuid: 'msg-2',
          userId: 0,
          role: 'assistant',
          messageText: 'Hi there!',
        ),
      });

      await chatCubit.sendMessage(message: 'Hello');

      // Should not call repository since state is not ChatLoaded
      verifyNever(mockChatRepository.sendMessage(
        message: anyNamed('message'),
        documentUuid: anyNamed('documentUuid'),
      ));
    });
  });
}
