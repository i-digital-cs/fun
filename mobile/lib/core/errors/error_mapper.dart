import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

Failure mapDioException(DioException error) {
  switch (error.type) {
    case DioExceptionType.connectionTimeout:
    case DioExceptionType.sendTimeout:
    case DioExceptionType.receiveTimeout:
      return const NetworkFailure('Request timed out');
    case DioExceptionType.connectionError:
      return const NetworkFailure();
    case DioExceptionType.cancel:
      return const NetworkFailure('Request cancelled');
    case DioExceptionType.badResponse:
      final status = error.response?.statusCode ?? 0;
      final data = error.response?.data;
      // Never pass Laravel route/SQL/exception text to the UI.
      // Validation field errors are the only server copy we surface.
      if (status == 401) {
        return const UnauthorizedFailure();
      }
      if (status == 403) {
        return const ForbiddenFailure();
      }
      if (status == 404) {
        return const NotFoundFailure();
      }
      if (status == 409) {
        return const ConflictFailure();
      }
      if (status == 422) {
        final errors = <String, List<String>>{};
        if (data is Map && data['errors'] is Map) {
          (data['errors'] as Map).forEach((key, value) {
            if (value is List) {
              errors[key.toString()] = value
                  .map((e) => e.toString())
                  .where((s) => s.isNotEmpty && !_isTechnicalMessage(s))
                  .toList();
            }
          });
        }
        final message = _safeServerMessage(data) ?? 'Validation failed';
        return ValidationFailure(message, errors);
      }
      if (status == 429) {
        final retryAfter = _retryAfterSeconds(error, data);
        return RateLimitFailure('Too many requests', retryAfter);
      }
      if (status == 503) {
        final message = _safeServerMessage(data);
        if (message != null) {
          return ServerFailure(message);
        }
        return const ServerFailure();
      }
      if (status >= 500) {
        return const ServerFailure();
      }
      return const ServerFailure();
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

int? _retryAfterSeconds(DioException error, dynamic data) {
  if (data is Map && data['retry_after_seconds'] != null) {
    final raw = data['retry_after_seconds'];
    if (raw is int) return raw;
    return int.tryParse(raw.toString());
  }
  final header = error.response?.headers.value('retry-after');
  if (header != null) return int.tryParse(header);
  return null;
}

String? _safeServerMessage(dynamic data) {
  final raw = _extractMessage(data);
  if (raw == null || raw.isEmpty) return null;
  if (_isTechnicalMessage(raw)) return null;
  return raw;
}

/// Laravel/framework diagnostics that must never reach end users.
bool _isTechnicalMessage(String message) {
  final m = message.toLowerCase();
  return m.contains('the route ') ||
      m.contains('could not be found') ||
      m.contains('sqlstate') ||
      m.contains('sql:') ||
      m.contains('stack trace') ||
      m.contains('exception') ||
      m.contains('\\app\\') ||
      m.contains('/vendor/') ||
      m.contains('illuminate\\') ||
      m.contains('symlink') ||
      RegExp(r'\bapi/[a-z0-9_\-/{}/]+\b', caseSensitive: false).hasMatch(m);
}

/// First field error or typed default — English fallback when l10n unavailable.
String failureUserMessage(Failure failure) {
  if (failure is ValidationFailure && failure.errors.isNotEmpty) {
    final first = failure.errors.values.first;
    if (first.isNotEmpty) return first.first;
  }
  if (failure is UnknownFailure) {
    return failure.message.isEmpty ? 'Something went wrong' : failure.message;
  }
  // Prefer constructor defaults (already safe) over any leaked copy.
  if (_isTechnicalMessage(failure.message)) {
    return switch (failure) {
      NetworkFailure() => 'Network error',
      UnauthorizedFailure() => 'Unauthorized',
      ForbiddenFailure() => 'Forbidden',
      NotFoundFailure() => 'Not found',
      ConflictFailure() => 'Conflict',
      RateLimitFailure() => 'Too many requests',
      ServerFailure() => 'Server error',
      ValidationFailure() => 'Validation failed',
      UnknownFailure() => 'Something went wrong',
    };
  }
  return failure.message;
}

/// Localized user-facing copy for typed [Failure]s (preferred in UI).
String localizedFailureMessage(AppLocalizations l10n, Failure failure) {
  if (failure is ValidationFailure && failure.errors.isNotEmpty) {
    final first = failure.errors.values.first;
    if (first.isNotEmpty && !_isTechnicalMessage(first.first)) {
      return first.first;
    }
  }
  return switch (failure) {
    NetworkFailure(:final message)
        when message.toLowerCase().contains('timed out') ||
            message.toLowerCase().contains('timeout') =>
      l10n.errorTimeout,
    NetworkFailure() => l10n.errorOffline,
    UnauthorizedFailure() => l10n.errorUnauthorized,
    ForbiddenFailure() => l10n.errorForbidden,
    NotFoundFailure() => l10n.errorNotFound,
    ConflictFailure() => l10n.errorConflict,
    RateLimitFailure() => l10n.errorRateLimit,
    ServerFailure() => l10n.errorGeneric,
    ValidationFailure() => failureUserMessage(failure),
    UnknownFailure() => l10n.errorGeneric,
  };
}
