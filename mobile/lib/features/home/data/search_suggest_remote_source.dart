import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/home/domain/search_suggest_item.dart';

class SearchSuggestRemoteSource {
  SearchSuggestRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, List<SearchSuggestItem> data})> suggest(
    String q,
  ) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/search/suggest',
        queryParameters: {'q': q},
      );
      final body = response.data ?? {};
      final list = (body['data'] as List<dynamic>? ?? [])
          .whereType<Map>()
          .map((e) {
            final m = Map<String, dynamic>.from(e);
            return SearchSuggestItem(
              id: m['id'] as int? ?? 0,
              title: m['title'] as String? ?? '',
              subtitle: m['subtitle'] as String?,
              meta: m['meta'] as String?,
            );
          })
          .where((e) => e.id > 0 && e.title.isNotEmpty)
          .toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: const <SearchSuggestItem>[]);
    }
  }
}
