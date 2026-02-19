import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_cubit.dart';
import 'package:bookmi_app/features/messaging/bloc/messaging_state.dart';
import 'package:bookmi_app/features/messaging/data/models/conversation_model.dart';
import 'package:bookmi_app/features/messaging/data/models/message_model.dart';
import 'package:bookmi_app/features/messaging/data/repositories/messaging_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class _MockMessagingRepository extends Mock implements MessagingRepository {}

void main() {
  late _MockMessagingRepository repository;

  final fakeConversation = ConversationModel(
    id: 1,
    clientId: 10,
    talentProfileId: 20,
    clientName: 'Alice',
    talentName: 'Bob',
  );

  const fakeMessage = MessageModel(
    id: 1,
    conversationId: 1,
    senderId: 10,
    content: 'Bonjour !',
    type: 'text',
    isFlagged: false,
    isAutoReply: false,
  );

  const flaggedMessage = MessageModel(
    id: 2,
    conversationId: 1,
    senderId: 10,
    content: 'Contactez-moi au +225 07 00 00 00',
    type: 'text',
    isFlagged: true,
    isAutoReply: false,
  );

  setUp(() {
    repository = _MockMessagingRepository();
    // Default: markAsRead is fire-and-forget
    when(() => repository.markAsRead(any())).thenAnswer(
      (_) async => const ApiSuccess(0),
    );
  });

  group('MessagingCubit', () {
    test('initial state is MessagingInitial', () {
      expect(
        MessagingCubit(repository: repository).state,
        isA<MessagingInitial>(),
      );
    });

    // ── loadConversations ──────────────────────────────────────────────────────

    blocTest<MessagingCubit, MessagingState>(
      'emits [Loading, ConversationsLoaded] on success',
      build: () {
        when(() => repository.getConversations()).thenAnswer(
          (_) async => ApiSuccess([fakeConversation]),
        );
        return MessagingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadConversations(),
      expect: () => [
        isA<MessagingLoading>(),
        isA<ConversationsLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as ConversationsLoaded;
        expect(loaded.conversations, hasLength(1));
        expect(loaded.conversations.first.id, equals(1));
      },
    );

    blocTest<MessagingCubit, MessagingState>(
      'emits [Loading, Error] when getConversations fails',
      build: () {
        when(() => repository.getConversations()).thenAnswer(
          (_) async => const ApiFailure(
            code: 'NETWORK_ERROR',
            message: 'Erreur réseau',
          ),
        );
        return MessagingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadConversations(),
      expect: () => [
        isA<MessagingLoading>(),
        isA<MessagingError>(),
      ],
      verify: (cubit) {
        expect(
          (cubit.state as MessagingError).message,
          equals('Erreur réseau'),
        );
      },
    );

    // ── loadMessages ───────────────────────────────────────────────────────────

    blocTest<MessagingCubit, MessagingState>(
      'emits [Loading, MessagesLoaded] on success — messages reversed oldest-first',
      build: () {
        when(() => repository.getMessages(1)).thenAnswer(
          (_) async => const ApiSuccess([fakeMessage]),
        );
        return MessagingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadMessages(1),
      expect: () => [
        isA<MessagingLoading>(),
        isA<MessagesLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as MessagesLoaded;
        expect(loaded.messages, hasLength(1));
        expect(loaded.conversationId, equals(1));
      },
    );

    // ── sendMessage ────────────────────────────────────────────────────────────

    blocTest<MessagingCubit, MessagingState>(
      'emits [Sending, MessagesLoaded] after successful send',
      build: () {
        when(() => repository.getMessages(1)).thenAnswer(
          (_) async => const ApiSuccess([fakeMessage]),
        );
        when(
          () => repository.sendMessage(1, content: 'Bonjour encore !'),
        ).thenAnswer(
          (_) async => const ApiSuccess(
            MessageModel(
              id: 3,
              conversationId: 1,
              senderId: 10,
              content: 'Bonjour encore !',
              type: 'text',
              isFlagged: false,
              isAutoReply: false,
            ),
          ),
        );
        return MessagingCubit(repository: repository);
      },
      act: (cubit) async {
        await cubit.loadMessages(1);
        await cubit.sendMessage(1, 'Bonjour encore !');
      },
      expect: () => [
        isA<MessagingLoading>(),
        isA<MessagesLoaded>(),
        isA<MessageSending>(),
        isA<MessagesLoaded>(),
      ],
      verify: (cubit) {
        final loaded = cubit.state as MessagesLoaded;
        expect(loaded.messages, hasLength(2));
        expect(loaded.messages.last.content, equals('Bonjour encore !'));
      },
    );

    blocTest<MessagingCubit, MessagingState>(
      'flagged message is present in MessagesLoaded',
      build: () {
        when(() => repository.getMessages(1)).thenAnswer(
          (_) async => const ApiSuccess([flaggedMessage]),
        );
        return MessagingCubit(repository: repository);
      },
      act: (cubit) => cubit.loadMessages(1),
      verify: (cubit) {
        final loaded = cubit.state as MessagesLoaded;
        expect(loaded.messages.first.isFlagged, isTrue);
      },
    );
  });
}
