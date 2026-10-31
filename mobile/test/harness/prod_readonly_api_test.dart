import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

/// Safe, read-only probes against Production.
///
/// Only GET endpoints that are public (or that must 401 without a token).
/// Never POST/PUT/PATCH/DELETE — no OTP, join, message, or upload.
void main() {
  final dio = Dio(
    BaseOptions(
      baseUrl: 'https://funnity.com/api',
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 20),
      validateStatus: (code) => code != null && code < 500,
      headers: const {
        'Accept': 'application/json',
        'X-Funnity-Client': 'flutter-phase5-readonly-qa',
      },
    ),
  );

  test('GET /site returns branding metadata', () async {
    final response = await dio.get<Map<String, dynamic>>('/site');
    expect(response.statusCode, 200);
    expect(response.data, isA<Map>());
  });

  test('GET /events public index is reachable without auth', () async {
    final response = await dio.get<Map<String, dynamic>>(
      '/events',
      queryParameters: const {'per_page': 5},
    );
    expect(response.statusCode, 200);
    expect(response.data, isA<Map>());
  });

  test('GET /dms without token is unauthorized (private)', () async {
    final response = await dio.get<dynamic>('/dms');
    expect(response.statusCode, anyOf(401, 403));
  });

  test('GET private DM media without token is unauthorized', () async {
    final response = await dio.get<dynamic>('/dms/media/1');
    expect(response.statusCode, anyOf(401, 403, 404));
  });

  test('GET event chat without token is unauthorized', () async {
    final response = await dio.get<dynamic>('/events/1/messages');
    expect(response.statusCode, anyOf(401, 403, 404));
  });
}
