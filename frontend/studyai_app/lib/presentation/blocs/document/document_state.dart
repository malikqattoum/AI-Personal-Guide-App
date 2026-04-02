import 'package:equatable/equatable.dart';
import '../../../data/models/document_model.dart';

abstract class DocumentState extends Equatable {
  const DocumentState();

  @override
  List<Object?> get props => [];
}

class DocumentInitial extends DocumentState {}

class DocumentLoading extends DocumentState {}

class DocumentLoaded extends DocumentState {
  final List<Document> documents;

  const DocumentLoaded(this.documents);

  @override
  List<Object?> get props => [documents];
}

class DocumentUploading extends DocumentState {
  final double progress;
  final List<Document> documents;

  const DocumentUploading({
    required this.progress,
    required this.documents,
  });

  @override
  List<Object?> get props => [progress, documents];
}

class DocumentError extends DocumentState {
  final String message;
  final List<Document> documents;

  const DocumentError({
    required this.message,
    this.documents = const [],
  });

  @override
  List<Object?> get props => [message, documents];
}
