import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/safety/domain/safety_models.dart';

/// Thin boundary for block/report APIs (ARCH-REPO-SAFETY-001).
abstract class SafetyRepository {
  Future<({Failure? failure, BlocksPageResult? data})> listBlocks({
    int page = 1,
  });

  Future<({Failure? failure, void data})> block(int userId);

  Future<({Failure? failure, void data})> unblock(int userId);

  Future<({Failure? failure, void data})> report({
    required String reportableType,
    required int reportableId,
    required String reason,
    String? description,
  });
}
