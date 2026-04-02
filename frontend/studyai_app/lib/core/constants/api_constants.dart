class ApiConstants {
  static const String baseUrl = 'http://10.0.2.2:8000/api';

  // Auth
  static const String register = '/auth/register';
  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String me = '/auth/me';

  // Documents
  static const String documents = '/documents';
  static const String uploadPdf = '/documents/upload';
  static const String addYoutube = '/documents/youtube';

  // Flashcards
  static const String flashcards = '/flashcards';
  static const String generateFlashcards = '/flashcards/generate';

  // Audio
  static const String generateAudio = '/audio/generate';

  // Chat
  static const String chatMessages = '/chat/messages';
  static const String sendMessage = '/chat/message';

  // Sessions
  static const String sessions = '/sessions';
  static const String startSession = '/sessions/start';
  static const String sessionStats = '/sessions/stats';
}
