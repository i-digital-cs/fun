import 'package:equatable/equatable.dart';

sealed class Failure extends Equatable {
  const Failure(this.message);

  final String message;

  @override
  List<Object?> get props => [message];
}

class NetworkFailure extends Failure {
  const NetworkFailure([super.message = 'Network error']);
}

class ServerFailure extends Failure {
  const ServerFailure([super.message = 'Server error']);
}

class UnauthorizedFailure extends Failure {
  const UnauthorizedFailure([super.message = 'Unauthorized']);
}

/// HTTP 403 — authenticated but not allowed.
class ForbiddenFailure extends Failure {
  const ForbiddenFailure([super.message = 'Forbidden']);
}

/// HTTP 404.
class NotFoundFailure extends Failure {
  const NotFoundFailure([super.message = 'Not found']);
}

/// HTTP 409 — e.g. capacity / state conflict.
class ConflictFailure extends Failure {
  const ConflictFailure([super.message = 'Conflict']);
}

/// HTTP 429.
class RateLimitFailure extends Failure {
  const RateLimitFailure([
    super.message = 'Too many requests',
    this.retryAfterSeconds,
  ]);

  final int? retryAfterSeconds;

  @override
  List<Object?> get props => [message, retryAfterSeconds];
}

class ValidationFailure extends Failure {
  const ValidationFailure(super.message, [this.errors = const {}]);

  final Map<String, List<String>> errors;

  @override
  List<Object?> get props => [message, errors];
}

class UnknownFailure extends Failure {
  const UnknownFailure([super.message = 'Something went wrong']);
}
