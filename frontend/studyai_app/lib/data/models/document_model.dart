class Document {
  final int id;
  final String uuid;
  final int userId;
  final String title;
  final String? filePath;
  final String? fileName;
  final int? fileSize;
  final String? mimeType;
  final int? pageCount;
  final String? extractedText;
  final String sourceType;
  final String status;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Document({
    required this.id,
    required this.uuid,
    required this.userId,
    required this.title,
    this.filePath,
    this.fileName,
    this.fileSize,
    this.mimeType,
    this.pageCount,
    this.extractedText,
    this.sourceType = 'pdf',
    this.status = 'pending',
    this.createdAt,
    this.updatedAt,
  });

  factory Document.fromJson(Map<String, dynamic> json) {
    return Document(
      id: json['id'],
      uuid: json['uuid'],
      userId: json['user_id'],
      title: json['title'],
      filePath: json['file_path'],
      fileName: json['file_name'],
      fileSize: json['file_size'],
      mimeType: json['mime_type'],
      pageCount: json['page_count'],
      extractedText: json['extracted_text'],
      sourceType: json['source_type'] ?? 'pdf',
      status: json['status'] ?? 'pending',
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.parse(json['updated_at'])
          : null,
    );
  }

  bool get isPdf => sourceType == 'pdf';
  bool get isYoutube => sourceType == 'youtube';
  bool get isProcessing => status == 'processing';
  bool get isCompleted => status == 'completed';
  bool get isFailed => status == 'failed';
}
