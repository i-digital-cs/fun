import 'package:equatable/equatable.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';

class CityItem extends Equatable {
  const CityItem({
    required this.id,
    required this.name,
    this.nameEn,
    this.nameAr,
    this.latitude,
    this.longitude,
    this.subtitle,
  });

  final int id;
  final String name;
  final String? nameEn;
  final String? nameAr;
  final double? latitude;
  final double? longitude;
  final String? subtitle;

  @override
  List<Object?> get props =>
      [id, name, nameEn, nameAr, latitude, longitude, subtitle];
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
  const CatalogBundle({required this.cities, required this.distancesKm});

  final List<CityItem> cities;
  final List<int> distancesKm;

  @override
  List<Object?> get props => [cities, distancesKm];
}

abstract class CatalogRepository {
  Future<Result<CatalogBundle>> catalog();
  Future<Result<List<CityItem>>> cities();
  Future<Result<List<CityItem>>> searchCities(String query, {int limit = 12});
  Future<Result<List<CategoryItem>>> categories();
}
