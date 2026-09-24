import 'package:equatable/equatable.dart';

class PublicProfile extends Equatable {
  const PublicProfile({
    this.id,
    required this.publicId,
    required this.name,
    required this.initials,
    required this.roleLabel,
    required this.hostedEventsCount,
    required this.joinedEventsCount,
    this.profileImagePath,
    this.cityName,
    this.ratingAverage,
    this.ratingCount = 0,
    this.hostedEvents = const [],
  });

  final int? id;
  final String publicId;
  final String name;
  final String initials;
  final String? profileImagePath;
  final String roleLabel;
  final String? cityName;
  final int hostedEventsCount;
  final int joinedEventsCount;
  final double? ratingAverage;
  final int ratingCount;
  final List<PublicHostedEvent> hostedEvents;

  @override
  List<Object?> get props => [
    id,
    publicId,
    name,
    initials,
    profileImagePath,
    roleLabel,
    cityName,
    hostedEventsCount,
    joinedEventsCount,
    ratingAverage,
    ratingCount,
    hostedEvents,
  ];
}

class PublicHostedEvent extends Equatable {
  const PublicHostedEvent({
    required this.id,
    required this.title,
    this.startsAt,
    this.city,
    this.membersCount,
    this.maxParticipants,
  });

  final int id;
  final String title;
  final DateTime? startsAt;
  final String? city;
  final int? membersCount;
  final int? maxParticipants;

  @override
  List<Object?> get props => [
    id,
    title,
    startsAt,
    city,
    membersCount,
    maxParticipants,
  ];
}
