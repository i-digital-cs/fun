import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/catalog/data/catalog_remote_source.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';

class CatalogRepositoryImpl implements CatalogRepository {
  CatalogRepositoryImpl(this._remote);

  final CatalogRemoteSource _remote;

  @override
  Future<Result<CatalogBundle>> catalog() => _remote.catalog();

  @override
  Future<Result<List<CityItem>>> cities() => _remote.cities();

  @override
  Future<Result<List<CategoryItem>>> categories() => _remote.categories();
}
