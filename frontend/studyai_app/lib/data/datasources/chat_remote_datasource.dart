import '../../core/network/api_client.dart';
import '../../core/constants/api_constants.dart';
import '../models/chat_message_model.dart';

class ChatRemoteDatasource {
  final ApiClient _apiClient;

  ChatRemoteDatasource(this._apiClient);

  Map<String, dynamic>? _documentUuidQuery(String? documentUuid) =>
      documentUuid != null ? {'document_uuid': documentUuid} : null;

  Future<List<ChatMessage>> getMessages({String? documentUuid}) async {
    final response = await _apiClient.get(
      ApiConstants.chatMessages,
      query: _documentUuidQuery(documentUuid),
    );
    final List<dynamic> messagesJson = response.data['messages'];
    return messagesJson.map((json) => ChatMessage.fromJson(json)).toList();
  }

  Future<Map<String, ChatMessage>> sendMessage({
    required String message,
    String? documentUuid,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.sendMessage,
      data: {
        'message': message,
        if (documentUuid != null) 'document_uuid': documentUuid,
      },
    );

    return {
      'user_message': ChatMessage.fromJson(response.data['user_message']),
      'assistant_message': ChatMessage.fromJson(response.data['assistant_message']),
    };
  }

  Future<void> clearMessages({String? documentUuid}) async {
    await _apiClient.delete(
      ApiConstants.chatMessages,
      query: _documentUuidQuery(documentUuid),
    );
  }
}
