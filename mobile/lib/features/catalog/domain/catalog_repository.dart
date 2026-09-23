import 'package:equatable/equatable.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';

class CityItem extends Equatable {
  const CityItem({required this.id, required this.name});

  final int id;
  final String name;

  @override
  List<Object?> get props => [id, name];
}

class CategoryItem extends Equatable {
  const CategoryItem({
    required this.id,
    required this.name,
    this.publishedEventsCount = 0,
  });

  final int id;
  final String name;
  final int publishedEventsCount;

  @override
  List<Object?> get props => [id, name, publishedEventsCount];
}

class CatalogBundle extends Equatable {
  const CatalogBundle({
    required this.cities,
    required this.distancesKm,
  });

  final List<CityItem> cities;
  final List<int> distancesKm;

  @override
  List<Object?> get props => [cities, distancesKm];
}

abstract class CatalogRepository {
  Future<Result<CatalogBundle>> catalog();
  Future<Result<List<CityItem>>> cities();
  Future<Result<List<CategoryItem>>> categories();
}
