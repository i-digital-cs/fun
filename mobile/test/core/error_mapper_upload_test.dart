import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/utils/upload_validator.dart';
import 'package:dio/dio.dart';

void main() {
  group('mapDioException status mapping', () {
    Failure mapStatus(int status) {
      return mapDioException(
        DioException(
          requestOptions: RequestOptions(path: '/x'),
          response: Response(
            requestOptions: RequestOptions(path: '/x'),
            statusCode: status,
            data: {'message': 'm$status'},
          ),
          type: DioExceptionType.badResponse,
        ),
      );
    }

    test('maps 401/403/404/409/422/429', () {
      expect(mapStatus(401), isA<UnauthorizedFailure>());
      expect(mapStatus(403), isA<ForbiddenFailure>());
      expect(mapStatus(404), isA<NotFoundFailure>());
      expect(mapStatus(409), isA<ConflictFailure>());
      expect(mapStatus(422), isA<ValidationFailure>());
      expect(mapStatus(429), isA<RateLimitFailure>());
      expect(mapStatus(500), isA<ServerFailure>());
      // Status payloads must not leak Laravel "m$status" copy into UI defaults.
      expect(failureUserMessage(mapStatus(404)), isNot(contains('m404')));
      expect(failureUserMessage(mapStatus(500)), isNot(contains('m500')));
    });

    test('failureUserMessage prefers first validation field error', () {
      const failure = ValidationFailure('msg', {
        'email': ['bad email'],
      });
      expect(failureUserMessage(failure), 'bad email');
    });

    test('ERR-001 mapUnexpectedError never leaks exception text', () {
      final failure = mapUnexpectedError(FormatException('secret stack'));
      expect(failure, isA<UnknownFailure>());
      expect(failure.message, 'Something went wrong');
      expect(failureUserMessage(failure), 'Something went wrong');
      expect(failure.message.contains('secret'), isFalse);
    });

    test('ERR-001 Dio unknown type does not use raw error.message', () {
      final failure = mapDioException(
        DioException(
          requestOptions: RequestOptions(path: '/x'),
          type: DioExceptionType.unknown,
          message: 'HandshakeException: CERT_SECRET',
        ),
      );
      expect(failure, isA<UnknownFailure>());
      expect(failureUserMessage(failure).contains('CERT_SECRET'), isFalse);
    });
  });

  group('UploadValidator', () {
    test('rejects missing file', () async {
      final result = await UploadValidator.validateImageFile(
        'C:\\no\\such\\file_xyz.jpg',
      );
      expect(result.ok, isFalse);
      expect(result.errorKey, 'uploadMissing');
    });

    test('rejects bad extension', () async {
      final dir = await Directory.systemTemp.createTemp('fun_upload');
      final file = File('${dir.path}/doc.pdf');
      await file.writeAsBytes([1, 2, 3]);
      addTearDown(() => dir.delete(recursive: true));

      final result = await UploadValidator.validateImageFile(file.path);
      expect(result.ok, isFalse);
      expect(result.errorKey, 'uploadBadType');
    });

    test('accepts small jpg', () async {
      final dir = await Directory.systemTemp.createTemp('fun_upload');
      final file = File('${dir.path}/ok.jpg');
      await file.writeAsBytes(List<int>.filled(64, 1));
      addTearDown(() => dir.delete(recursive: true));

      final result = await UploadValidator.validateImageFile(file.path);
      expect(result.ok, isTrue);
    });
  });
}
