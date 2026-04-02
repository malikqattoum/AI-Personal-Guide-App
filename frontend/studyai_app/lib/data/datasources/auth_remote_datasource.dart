import '../../core/network/api_client.dart';
import '../../core/constants/api_constants.dart';
import '../models/user_model.dart';

class AuthRemoteDatasource {
  final ApiClient _apiClient;

  AuthRemoteDatasource(this._apiClient);

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.register,
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
      },
    );

    return response.data;
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.login,
      data: {
        'email': email,
        'password': password,
      },
    );

    return response.data;
  }

  Future<void> logout() async {
    await _apiClient.post(ApiConstants.logout);
    await _apiClient.clearToken();
  }

  Future<User> getCurrentUser() async {
    final response = await _apiClient.get(ApiConstants.me);
    return User.fromJson(response.data);
  }
}
