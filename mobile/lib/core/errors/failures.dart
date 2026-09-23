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

class ValidationFailure extends Failure {
  const ValidationFailure(super.message, [this.errors = const {}]);

  final Map<String, List<String>> errors;

  @override
  List<Object?> get props => [message, errors];
}

class UnknownFailure extends Failure {
  const UnknownFailure([super.message = 'Something went wrong']);
}
