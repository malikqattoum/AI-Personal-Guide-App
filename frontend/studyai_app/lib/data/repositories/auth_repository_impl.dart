import '../datasources/auth_remote_datasource.dart';
import '../models/user_model.dart';
import '../../core/network/api_client.dart';

class AuthRepository {
  final AuthRemoteDatasource _remoteDatasource;
  final ApiClient _apiClient;

  AuthRepository(this._remoteDatasource, this._apiClient);

  Future<User> register({
    required String name,
    required String email,
    required String password,
  }) async {
    final data = await _remoteDatasource.register(
      name: name,
      email: email,
      password: password,
    );

    final token = data['token'];
    await _apiClient.saveToken(token);

    return User.fromJson(data['user']);
  }

  Future<User> login({
    required String email,
    required String password,
  }) async {
    final data = await _remoteDatasource.login(
      email: email,
      password: password,
    );

    final token = data['token'];
    await _apiClient.saveToken(token);

    return User.fromJson(data['user']);
  }

  Future<void> logout() async {
    await _remoteDatasource.logout();
  }

  Future<User> getCurrentUser() async {
    return _remoteDatasource.getCurrentUser();
  }

  Future<bool> isLoggedIn() async {
    final token = await _apiClient.getToken();
    return token != null;
  }
}
