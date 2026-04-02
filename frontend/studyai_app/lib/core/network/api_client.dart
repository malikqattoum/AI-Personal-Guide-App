import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../constants/api_constants.dart';
import '../constants/storage_keys.dart';

class ApiClient {
  late final Dio _dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  String? _cachedToken;

  ApiClient() {
    _dio = Dio(BaseOptions(
      baseUrl: ApiConstants.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        _cachedToken ??= await _storage.read(key: StorageKeys.authToken);
        if (_cachedToken != null) {
          options.headers['Authorization'] = 'Bearer $_cachedToken';
        }
        return handler.next(options);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode == 401) {
          _cachedToken = null;
          await clearToken();
        }
        return handler.next(error);
      },
    ));
  }

  Future<Response> get(String path, {Map<String, dynamic>? query}) async {
    return _dio.get(path, queryParameters: query);
  }

  Future<Response> post(String path, {dynamic data}) async {
    return _dio.post(path, data: data);
  }

  Future<Response> put(String path, {dynamic data}) async {
    return _dio.put(path, data: data);
  }

  Future<Response> delete(String path, {Map<String, dynamic>? query}) async {
    return _dio.delete(path, queryParameters: query);
  }

  Future<Response> uploadFile(
    String path,
    String filePath,
    String fileName, {
    Map<String, dynamic>? additionalFields,
    Function(int, int)? onProgress,
  }) async {
    final formData = FormData.fromMap({
      'file': await MultipartFile.fromFile(filePath, filename: fileName),
      if (additionalFields != null) ...additionalFields,
    });

    return _dio.post(
      path,
      data: formData,
      onSendProgress: onProgress,
    );
  }

  Future<void> saveToken(String token) async {
    _cachedToken = token;
    await _storage.write(key: StorageKeys.authToken, value: token);
  }

  Future<void> clearToken() async {
    _cachedToken = null;
    await _storage.delete(key: StorageKeys.authToken);
  }

  Future<String?> getToken() async {
    return _storage.read(key: StorageKeys.authToken);
  }
}
