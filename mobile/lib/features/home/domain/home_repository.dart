import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/home/domain/search_suggest_item.dart';
import 'package:fun_mobile/features/home/domain/weather.dart';

/// Home feed helpers — ARCH-REPO-HOME-001.
abstract class HomeRepository {
  Future<({Failure? failure, WeatherSnapshot? data})> weather({int? cityId});

  Future<({Failure? failure, HomeParticipations? data})> participations();

  Future<({Failure? failure, List<SearchSuggestItem> data})> suggest(String q);
}
