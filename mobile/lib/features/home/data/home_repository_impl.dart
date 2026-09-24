import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/home/data/home_remote_source.dart';
import 'package:fun_mobile/features/home/data/search_suggest_remote_source.dart';
import 'package:fun_mobile/features/home/domain/home_repository.dart';
import 'package:fun_mobile/features/home/domain/search_suggest_item.dart';
import 'package:fun_mobile/features/home/domain/weather.dart';

class HomeRepositoryImpl implements HomeRepository {
  HomeRepositoryImpl(this._remote, this._suggest);

  final HomeRemoteSource _remote;
  final SearchSuggestRemoteSource _suggest;

  @override
  Future<({Failure? failure, WeatherSnapshot? data})> weather({int? cityId}) =>
      _remote.weather(cityId: cityId);

  @override
  Future<({Failure? failure, HomeParticipations? data})> participations() =>
      _remote.participations();

  @override
  Future<({Failure? failure, List<SearchSuggestItem> data})> suggest(String q) =>
      _suggest.suggest(q);
}
