import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/failures.dart';

Failure mapDioException(DioException error) {
  switch (error.type) {
    case DioExceptionType.connectionTimeout:
    case DioExceptionType.sendTimeout:
    case DioExceptionType.receiveTimeout:
    case DioExceptionType.connectionError:
      return const NetworkFailure();
    case DioExceptionType.badResponse:
      final status = error.response?.statusCode ?? 0;
      final data = error.response?.data;
      final message = _extractMessage(data) ?? 'Request failed ($status)';

      if (status == 401) {
        return UnauthorizedFailure(message);
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
      return ServerFailure(message);
    default:
      return UnknownFailure(error.message ?? 'Unknown error');
  }
}

String? _extractMessage(dynamic data) {
  if (data is Map && data['message'] != null) {
    return data['message'].toString();
  }
  return null;
}
