import 'package:equatable/equatable.dart';

class WeatherCity extends Equatable {
  const WeatherCity({
    required this.id,
    required this.name,
    this.latitude,
    this.longitude,
  });

  final int id;
  final String name;
  final double? latitude;
  final double? longitude;

  @override
  List<Object?> get props => [id, name, latitude, longitude];
}

class WeatherForecastDay extends Equatable {
  const WeatherForecastDay({
    required this.day,
    required this.condition,
    required this.icon,
    this.high,
    this.low,
    this.label,
  });

  final String day;
  final String condition;
  final String icon;
  final int? high;
  final int? low;
  final String? label;

  @override
  List<Object?> get props => [day, condition, icon, high, low, label];
}

/// Payload from `GET /weather?city_id=`.
class WeatherSnapshot extends Equatable {
  const WeatherSnapshot({
    required this.city,
    required this.temperature,
    required this.theme,
    required this.label,
    required this.icon,
    required this.condition,
    this.provider,
    this.localTime,
    this.localDate,
    this.high,
    this.low,
    this.range,
    this.forecast = const [],
  });

  final String? provider;
  final WeatherCity city;
  final String? localTime;
  final String? localDate;
  final int temperature;
  final int? high;
  final int? low;
  final String? range;
  final String condition;
  final String label;
  final String icon;
  final String theme;
  final List<WeatherForecastDay> forecast;

  @override
  List<Object?> get props => [
    provider,
    city,
    localTime,
    localDate,
    temperature,
    high,
    low,
    range,
    condition,
    label,
    icon,
    theme,
    forecast,
  ];
}
