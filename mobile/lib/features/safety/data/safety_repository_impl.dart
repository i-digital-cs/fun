import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/safety/data/safety_remote_source.dart';
import 'package:fun_mobile/features/safety/domain/safety_models.dart';
import 'package:fun_mobile/features/safety/domain/safety_repository.dart';

class SafetyRepositoryImpl implements SafetyRepository {
  SafetyRepositoryImpl(this._remote);

  final SafetyRemoteSource _remote;

  @override
  Future<({Failure? failure, BlocksPageResult? data})> listBlocks({
    int page = 1,
  }) => _remote.listBlocks(page: page);

  @override
  Future<({Failure? failure, void data})> block(int userId) =>
      _remote.block(userId);

  @override
  Future<({Failure? failure, void data})> unblock(int userId) =>
      _remote.unblock(userId);

  @override
  Future<({Failure? failure, void data})> report({
    required String reportableType,
    required int reportableId,
    required String reason,
    String? description,
  }) => _remote.report(
    reportableType: reportableType,
    reportableId: reportableId,
    reason: reason,
    description: description,
  );
}
