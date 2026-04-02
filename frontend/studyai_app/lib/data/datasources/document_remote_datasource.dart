import '../../core/network/api_client.dart';
import '../../core/constants/api_constants.dart';
import '../models/document_model.dart';

class DocumentRemoteDatasource {
  final ApiClient _apiClient;

  DocumentRemoteDatasource(this._apiClient);

  Future<List<Document>> getDocuments() async {
    final response = await _apiClient.get(ApiConstants.documents);
    final List<dynamic> documentsJson = response.data['documents'];
    return documentsJson.map((json) => Document.fromJson(json)).toList();
  }

  Future<Document> uploadPdf({
    required String filePath,
    required String fileName,
    String? title,
  }) async {
    final response = await _apiClient.uploadFile(
      ApiConstants.uploadPdf,
      filePath,
      fileName,
      additionalFields: title != null ? {'title': title} : null,
    );
    return Document.fromJson(response.data['document']);
  }

  Future<Document> addYoutube({
    required String url,
    String? title,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.addYoutube,
      data: {
        'url': url,
        if (title != null) 'title': title,
      },
    );
    return Document.fromJson(response.data['document']);
  }

  Future<Document> getDocument(String uuid) async {
    final response = await _apiClient.get('${ApiConstants.documents}/$uuid');
    return Document.fromJson(response.data['document']);
  }

  Future<String> getDocumentContent(String uuid) async {
    final response = await _apiClient.get('${ApiConstants.documents}/$uuid/content');
    return response.data['extracted_text'];
  }

  Future<void> deleteDocument(String uuid) async {
    await _apiClient.delete('${ApiConstants.documents}/$uuid');
  }
}
