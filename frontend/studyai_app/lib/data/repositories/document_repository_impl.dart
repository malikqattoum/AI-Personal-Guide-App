import '../datasources/document_remote_datasource.dart';
import '../models/document_model.dart';

class DocumentRepository {
  final DocumentRemoteDatasource _remoteDatasource;

  DocumentRepository(this._remoteDatasource);

  Future<List<Document>> getDocuments() {
    return _remoteDatasource.getDocuments();
  }

  Future<Document> uploadPdf({
    required String filePath,
    required String fileName,
    String? title,
  }) {
    return _remoteDatasource.uploadPdf(
      filePath: filePath,
      fileName: fileName,
      title: title,
    );
  }

  Future<Document> addYoutube({
    required String url,
    String? title,
  }) {
    return _remoteDatasource.addYoutube(
      url: url,
      title: title,
    );
  }

  Future<Document> getDocument(String uuid) {
    return _remoteDatasource.getDocument(uuid);
  }

  Future<String> getDocumentContent(String uuid) {
    return _remoteDatasource.getDocumentContent(uuid);
  }

  Future<void> deleteDocument(String uuid) {
    return _remoteDatasource.deleteDocument(uuid);
  }
}
