import 'package:dio/dio.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';

typedef OnUnauthorized = void Function();

class ApiClient {
  ApiClient({
    required TokenStorage tokenStorage,
    OnUnauthorized? onUnauthorized,
    String? locale,
    String? baseUrl,
  }) : // ignore: prefer_initializing_formals
       _tokenStorage = tokenStorage,
       // ignore: prefer_initializing_formals
       _onUnauthorized = onUnauthorized {
    dio = Dio(
      BaseOptions(
        baseUrl: baseUrl ?? Env.apiBaseUrl,
        connectTimeout: const Duration(seconds: 20),
        sendTimeout: const Duration(seconds: 60),
        receiveTimeout: const Duration(seconds: 60),
        headers: {
          'Accept': 'application/json',
          // Do not force Content-Type globally — FormData uploads need a
          // multipart boundary. JSON posts set application/json below.
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
          // FormData must own Content-Type (multipart boundary). JSON bodies
          // default to application/json when unset.
          if (options.data is FormData) {
            options.headers.remove(Headers.contentTypeHeader);
          } else if (options.data != null &&
              options.headers[Headers.contentTypeHeader] == null) {
            options.headers[Headers.contentTypeHeader] =
                Headers.jsonContentType;
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
