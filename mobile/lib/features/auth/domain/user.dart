import 'package:equatable/equatable.dart';

class User extends Equatable {
  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.status,
    required this.locale,
    this.platformRole,
    this.cityId,
    this.distanceKm,
    this.cityName,
  });

  final int id;
  final String name;
  final String email;
  final String status;
  final String locale;
  final String? platformRole;
  final int? cityId;
  final int? distanceKm;
  final String? cityName;

  String get firstName {
    final parts = name.trim().split(RegExp(r'\s+'));
    return parts.isEmpty ? '' : parts.first;
  }

  String get initials {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return '?';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return '${parts.first[0]}${parts.last[0]}'.toUpperCase();
  }

  bool get canParticipate => status == 'active';

  bool get needsIdentity => status == 'pending_documents';

  bool get isDeactivated => status == 'deactivated';

  User copyWith({
    int? id,
    String? name,
    String? email,
    String? status,
    String? locale,
    String? platformRole,
    int? cityId,
    int? distanceKm,
    String? cityName,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      status: status ?? this.status,
      locale: locale ?? this.locale,
      platformRole: platformRole ?? this.platformRole,
      cityId: cityId ?? this.cityId,
      distanceKm: distanceKm ?? this.distanceKm,
      cityName: cityName ?? this.cityName,
    );
  }

  @override
  List<Object?> get props =>
      [id, name, email, status, locale, platformRole, cityId, distanceKm, cityName];
}
