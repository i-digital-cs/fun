import 'package:equatable/equatable.dart';
import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';

class BlockedUser extends Equatable {
  const BlockedUser({
    required this.userId,
    required this.name,
  });

  final int userId;
  final String name;

  @override
  List<Object?> get props => [userId, name];
}

class BlocksPageResult extends Equatable {
  const BlocksPageResult({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<BlockedUser> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  @override
  List<Object?> get props => [items, currentPage, lastPage];
}

class SafetyRemoteSource {
  SafetyRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, BlocksPageResult? data})> listBlocks({
    int page = 1,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/blocks',
        queryParameters: {'page': page},
      );
      final list = (response.data!['data'] as List<dynamic>? ?? []).map((e) {
        final m = e as Map<String, dynamic>;
        final blocked = m['blocked'] as Map<String, dynamic>? ?? {};
        return BlockedUser(
          userId: blocked['id'] as int? ?? m['blocked_id'] as int? ?? 0,
          name: blocked['name'] as String? ?? '',
        );
      }).toList();
      final body = response.data!;
      final meta = body['meta'] as Map<String, dynamic>?;
      final currentPage = meta?['current_page'] as int? ??
          body['current_page'] as int? ??
          page;
      final lastPage =
          meta?['last_page'] as int? ?? body['last_page'] as int? ?? page;
      return (
        failure: null,
        data: BlocksPageResult(
          items: list,
          currentPage: currentPage,
          lastPage: lastPage,
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> block(int userId) async {
    try {
      await _client.dio.post<void>('/users/$userId/block');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> unblock(int userId) async {
    try {
      await _client.dio.delete<void>('/users/$userId/block');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> report({
    required String reportableType,
    required int reportableId,
    required String reason,
    String? description,
  }) async {
    try {
      await _client.dio.post<void>(
        '/reports',
        data: {
          'reportable_type': reportableType,
          'reportable_id': reportableId,
          'reason': reason,
          'description': ?description,
        },
      );
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}
