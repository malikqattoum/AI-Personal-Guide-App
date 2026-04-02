import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../data/models/document_model.dart';
import '../../../data/repositories/document_repository_impl.dart';
import 'document_state.dart';

class DocumentCubit extends Cubit<DocumentState> {
  final DocumentRepository _documentRepository;

  DocumentCubit(this._documentRepository) : super(DocumentInitial());

  Future<void> loadDocuments() async {
    emit(DocumentLoading());
    try {
      final documents = await _documentRepository.getDocuments();
      emit(DocumentLoaded(documents));
    } catch (e) {
      emit(DocumentError(message: e.toString()));
    }
  }

  Future<void> uploadPdf({
    required String filePath,
    required String fileName,
    String? title,
  }) async {
    final currentDocuments = _getCurrentDocuments();
    emit(DocumentUploading(progress: 0, documents: currentDocuments));

    try {
      final document = await _documentRepository.uploadPdf(
        filePath: filePath,
        fileName: fileName,
        title: title,
      );

      final updatedDocuments = [...currentDocuments, document];
      emit(DocumentLoaded(updatedDocuments));
    } catch (e) {
      emit(DocumentError(message: e.toString(), documents: currentDocuments));
    }
  }

  Future<void> addYoutube({
    required String url,
    String? title,
  }) async {
    final currentDocuments = _getCurrentDocuments();
    emit(DocumentLoading());

    try {
      final document = await _documentRepository.addYoutube(
        url: url,
        title: title,
      );

      final updatedDocuments = [...currentDocuments, document];
      emit(DocumentLoaded(updatedDocuments));
    } catch (e) {
      emit(DocumentError(message: e.toString(), documents: currentDocuments));
    }
  }

  Future<void> deleteDocument(String uuid) async {
    final currentDocuments = _getCurrentDocuments();

    try {
      await _documentRepository.deleteDocument(uuid);
      final updatedDocuments =
          currentDocuments.where((d) => d.uuid != uuid).toList();
      emit(DocumentLoaded(updatedDocuments));
    } catch (e) {
      emit(DocumentError(message: e.toString(), documents: currentDocuments));
    }
  }

  List<Document> _getCurrentDocuments() {
    final currentState = state;
    if (currentState is DocumentLoaded) {
      return currentState.documents;
    } else if (currentState is DocumentUploading) {
      return currentState.documents;
    } else if (currentState is DocumentError) {
      return currentState.documents;
    }
    return [];
  }
}
