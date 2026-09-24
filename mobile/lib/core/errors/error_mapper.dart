import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/failures.dart';

Failure mapDioException(DioException error) {
  switch (error.type) {
    case DioExceptionType.connectionTimeout:
    case DioExceptionType.sendTimeout:
    case DioExceptionType.receiveTimeout:
    case DioExceptionType.connectionError:
      return const NetworkFailure();
    case DioExceptionType.cancel:
      return const NetworkFailure('Request cancelled');
    case DioExceptionType.badResponse:
      final status = error.response?.statusCode ?? 0;
      final data = error.response?.data;
      final message = _extractMessage(data) ?? 'Request failed ($status)';

      if (status == 401) {
        return UnauthorizedFailure(message);
      }
      if (status == 403) {
        return ForbiddenFailure(message);
      }
      if (status == 404) {
        return NotFoundFailure(message);
      }
      if (status == 409) {
        return ConflictFailure(message);
      }
      if (status == 422) {
        final errors = <String, List<String>>{};
        if (data is Map && data['errors'] is Map) {
          (data['errors'] as Map).forEach((key, value) {
            if (value is List) {
              errors[key.toString()] = value.map((e) => e.toString()).toList();
            }
          });
        }
        return ValidationFailure(message, errors);
      }
      if (status == 429) {
        return RateLimitFailure(message);
      }
      if (status >= 500) {
        return ServerFailure(message);
      }
      return ServerFailure(message);
    default:
      // ERR-001: never surface Dio internals / exception text to UI.
      return const UnknownFailure();
  }
}

/// Non-Dio parse/logic failures (ERR-001). Never pass [e.toString()].
Failure mapUnexpectedError([Object? _]) => const UnknownFailure();

String? _extractMessage(dynamic data) {
  if (data is Map && data['message'] != null) {
    return data['message'].toString();
  }
  return null;
}

/// First field error or [failure.message] for SnackBars / forms.
String failureUserMessage(Failure failure) {
  if (failure is ValidationFailure && failure.errors.isNotEmpty) {
    final first = failure.errors.values.first;
    if (first.isNotEmpty) return first.first;
  }
  if (failure is UnknownFailure) {
    return failure.message.isEmpty ? 'Something went wrong' : failure.message;
  }
  return failure.message;
}
