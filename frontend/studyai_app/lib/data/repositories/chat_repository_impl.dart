import '../datasources/chat_remote_datasource.dart';
import '../models/chat_message_model.dart';

class ChatRepository {
  final ChatRemoteDatasource _remoteDatasource;

  ChatRepository(this._remoteDatasource);

  Future<List<ChatMessage>> getMessages({String? documentUuid}) {
    return _remoteDatasource.getMessages(documentUuid: documentUuid);
  }

  Future<Map<String, ChatMessage>> sendMessage({
    required String message,
    String? documentUuid,
  }) {
    return _remoteDatasource.sendMessage(
      message: message,
      documentUuid: documentUuid,
    );
  }

  Future<void> clearMessages({String? documentUuid}) {
    return _remoteDatasource.clearMessages(documentUuid: documentUuid);
  }
}
