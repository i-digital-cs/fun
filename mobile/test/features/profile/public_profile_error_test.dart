import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/identity/user_public_id.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';
import 'package:fun_mobile/features/profile/data/public_profile_remote_source.dart';

class _MemTokens implements TokenStorage {
  String? _t;
  @override
  Future<void> clear() async => _t = null;
  @override
  Future<String?> read() async => _t;
  @override
  Future<void> write(String token) async => _t = token;
}

void main() {
  group('public profile API errors', () {
    test('invalid public id never calls API and returns NotFound', () async {
      final client = ApiClient(
        tokenStorage: _MemTokens(),
        baseUrl: 'https://example.test/api',
      );
      var hits = 0;
      client.dio.interceptors.insert(
        0,
        InterceptorsWrapper(
          onRequest: (o, h) {
            hits++;
            return h.reject(
              DioException(requestOptions: o, type: DioExceptionType.cancel),
            );
          },
        ),
      );

      final remote = PublicProfileRemoteSource(client);
      final result = await remote.show('chatqaa0cc175b9');
      expect(hits, 0);
      expect(result.data, isNull);
      expect(result.failure, isA<NotFoundFailure>());
      expect(
        failureUserMessage(result.failure!).toLowerCase().contains('route'),
        isFalse,
      );
      expect(UserPublicId.isValid('chatqaa0cc175b9'), isFalse);
    });

    test('Laravel route-not-found JSON never leaks to UI', () {
      final failure = mapDioException(
        DioException(
          requestOptions: RequestOptions(path: '/users/chatqaa0cc175b9'),
          type: DioExceptionType.badResponse,
          response: Response(
            requestOptions: RequestOptions(path: '/users/chatqaa0cc175b9'),
            statusCode: 404,
            data: {
              'message':
                  'The route api/users/chatqaa0cc175b9 could not be found.',
            },
          ),
        ),
      );
      expect(failure, isA<NotFoundFailure>());
      final msg = failureUserMessage(failure);
      expect(msg.toLowerCase().contains('route'), isFalse);
      expect(msg.toLowerCase().contains('api/users'), isFalse);
      expect(msg.toLowerCase().contains('chatqaa'), isFalse);
    });

    test('network / 500 map to safe typed failures', () {
      final offline = mapDioException(
        DioException(
          requestOptions: RequestOptions(path: '/users/Funity-F001115'),
          type: DioExceptionType.connectionError,
        ),
      );
      expect(offline, isA<NetworkFailure>());

      final server = mapDioException(
        DioException(
          requestOptions: RequestOptions(path: '/users/Funity-F001115'),
          type: DioExceptionType.badResponse,
          response: Response(
            requestOptions: RequestOptions(path: '/users/Funity-F001115'),
            statusCode: 500,
            data: {'message': 'SQLSTATE[HY000] secret'},
          ),
        ),
      );
      expect(server, isA<ServerFailure>());
      expect(failureUserMessage(server).contains('SQLSTATE'), isFalse);
    });

    test('valid canonical public id is accepted', () {
      expect(UserPublicId.normalize('Funity-F001115'), 'Funity-F001115');
      expect(UserPublicId.isValid('Funity-F001115'), isTrue);
    });
  });
}
