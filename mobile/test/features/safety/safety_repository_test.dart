import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/safety/domain/safety_models.dart';
import 'package:fun_mobile/features/safety/domain/safety_repository.dart';

class _FakeSafetyRepository implements SafetyRepository {
  int blockCalls = 0;

  @override
  Future<({Failure? failure, BlocksPageResult? data})> listBlocks({
    int page = 1,
  }) async {
    return (
      failure: null,
      data: BlocksPageResult(
        items: const [BlockedUser(userId: 1, name: 'A')],
        currentPage: page,
        lastPage: 1,
      ),
    );
  }

  @override
  Future<({Failure? failure, void data})> block(int userId) async {
    blockCalls++;
    return (failure: null, data: null);
  }

  @override
  Future<({Failure? failure, void data})> unblock(int userId) async {
    return (failure: null, data: null);
  }

  @override
  Future<({Failure? failure, void data})> report({
    required String reportableType,
    required int reportableId,
    required String reason,
    String? description,
  }) async {
    return (failure: null, data: null);
  }
}

void main() {
  test('ARCH-REPO-SAFETY-001 repository contract for block/list', () async {
    final repo = _FakeSafetyRepository();
    final list = await repo.listBlocks();
    expect(list.failure, isNull);
    expect(list.data!.items.single.userId, 1);

    final blocked = await repo.block(9);
    expect(blocked.failure, isNull);
    expect(repo.blockCalls, 1);
  });
}
