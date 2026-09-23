import 'package:dio/dio.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';

typedef OnUnauthorized = void Function();

class ApiClient {
  ApiClient({
    required this._tokenStorage,
    this._onUnauthorized,
    String? locale,
    String? baseUrl,
  }) {
    dio = Dio(
      BaseOptions(
        baseUrl: baseUrl ?? Env.apiBaseUrl,
        connectTimeout: const Duration(seconds: 20),
        receiveTimeout: const Duration(seconds: 20),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Accept-Language': ?locale,
        },
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            await _tokenStorage.clear();
            _onUnauthorized?.call();
          }
          return handler.next(error);
        },
      ),
    );
  }

  late final Dio dio;
  final TokenStorage _tokenStorage;
  final OnUnauthorized? _onUnauthorized;

  void setLocale(String locale) {
    dio.options.headers['Accept-Language'] = locale;
  }
}
