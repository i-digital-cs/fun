import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';

class CatalogRemoteSource {
  CatalogRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, CatalogBundle? data})> catalog() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>('/cities');
      return (failure: null, data: _parseCatalog(response.data!));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<CityItem>? data})> cities() async {
    final result = await catalog();
    if (result.failure != null) {
      return (failure: result.failure, data: null);
    }
    return (failure: null, data: result.data!.cities);
  }

  CatalogBundle _parseCatalog(Map<String, dynamic> body) {
    final list = (body['cities'] as List<dynamic>? ?? []).map((e) {
      final m = e as Map<String, dynamic>;
      return CityItem(
        id: m['id'] as int,
        name: m['name'] as String? ?? m['name_en'] as String? ?? '',
      );
    }).toList();
    final distances = (body['distances_km'] as List<dynamic>? ?? [5, 10, 25, 50])
        .map((e) => (e as num).toInt())
        .toList();
    return CatalogBundle(cities: list, distancesKm: distances);
  }

  Future<({Failure? failure, List<CategoryItem>? data})> categories() async {
    try {
      final response =
          await _client.dio.get<Map<String, dynamic>>('/categories');
      final list = (response.data!['categories'] as List<dynamic>? ?? [])
          .map((e) {
            final m = e as Map<String, dynamic>;
            return CategoryItem(
              id: m['id'] as int,
              name: m['name'] as String? ??
                  m['name_en'] as String? ??
                  '',
            );
          })
          .toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}
