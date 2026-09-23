import 'package:equatable/equatable.dart';

class EventCategory extends Equatable {
  const EventCategory({
    required this.id,
    required this.name,
    this.nameEn,
    this.nameAr,
  });

  final int id;
  final String name;
  final String? nameEn;
  final String? nameAr;

  @override
  List<Object?> get props => [id, name, nameEn, nameAr];
}

class EventCity extends Equatable {
  const EventCity({
    required this.id,
    required this.name,
    this.nameEn,
    this.nameAr,
  });

  final int id;
  final String name;
  final String? nameEn;
  final String? nameAr;

  @override
  List<Object?> get props => [id, name, nameEn, nameAr];
}

class EventItem extends Equatable {
  const EventItem({
    required this.id,
    required this.title,
    required this.venue,
    required this.maxParticipants,
    required this.membersCount,
    this.description,
    this.requirements,
    this.expectedCost,
    this.startsAt,
    this.endsAt,
    this.costPerPerson,
    this.imageUrl,
    this.status,
    this.category,
    this.city,
    this.categoryId,
    this.cityId,
    this.distanceKm,
    this.joinAction,
    this.createdBy,
    this.readyForRatings = false,
  });

  final int id;
  final String title;
  final String venue;
  final String? description;
  final String? requirements;
  final String? expectedCost;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final int maxParticipants;
  final int membersCount;
  final num? costPerPerson;
  final String? imageUrl;
  final String? status;
  final EventCategory? category;
  final EventCity? city;
  final int? categoryId;
  final int? cityId;
  final double? distanceKm;
  final String? joinAction;
  final int? createdBy;
  final bool readyForRatings;

  bool get isFree => costPerPerson == null || costPerPerson! <= 0;

  int get fillPercent {
    final max = maxParticipants <= 0 ? 1 : maxParticipants;
    return ((membersCount / max) * 100).round().clamp(0, 100);
  }

  bool get isUrgent => fillPercent >= 80;

  bool get isDraft => status == 'draft';
  bool get isPublished => status == 'published';
  bool get isOwnerActionable =>
      status == 'draft' || status == 'published';

  EventItem copyWith({
    String? joinAction,
    String? status,
    int? membersCount,
    bool? readyForRatings,
  }) {
    return EventItem(
      id: id,
      title: title,
      venue: venue,
      description: description,
      requirements: requirements,
      expectedCost: expectedCost,
      startsAt: startsAt,
      endsAt: endsAt,
      maxParticipants: maxParticipants,
      membersCount: membersCount ?? this.membersCount,
      costPerPerson: costPerPerson,
      imageUrl: imageUrl,
      status: status ?? this.status,
      category: category,
      city: city,
      categoryId: categoryId,
      cityId: cityId,
      distanceKm: distanceKm,
      joinAction: joinAction ?? this.joinAction,
      createdBy: createdBy,
      readyForRatings: readyForRatings ?? this.readyForRatings,
    );
  }

  @override
  List<Object?> get props => [
        id,
        title,
        venue,
        description,
        requirements,
        expectedCost,
        startsAt,
        endsAt,
        maxParticipants,
        membersCount,
        costPerPerson,
        imageUrl,
        status,
        category,
        city,
        categoryId,
        cityId,
        distanceKm,
        joinAction,
        createdBy,
        readyForRatings,
      ];
}

class CreatorRating extends Equatable {
  const CreatorRating({this.average, this.count = 0});

  final double? average;
  final int count;

  @override
  List<Object?> get props => [average, count];
}

class EventRating extends Equatable {
  const EventRating({
    required this.id,
    required this.stars,
    this.comment,
    this.raterName,
  });

  final int id;
  final int stars;
  final String? comment;
  final String? raterName;

  @override
  List<Object?> get props => [id, stars, comment, raterName];
}

class EventDetail extends Equatable {
  const EventDetail({
    required this.event,
    required this.joinAction,
    this.readyForRatings = false,
    this.canRate = false,
    this.myRating,
    this.creatorRating,
  });

  final EventItem event;
  final String joinAction;
  final bool readyForRatings;
  final bool canRate;
  final EventRating? myRating;
  final CreatorRating? creatorRating;

  @override
  List<Object?> get props =>
      [event, joinAction, readyForRatings, canRate, myRating, creatorRating];
}

class PaginatedEvents extends Equatable {
  const PaginatedEvents({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<EventItem> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  @override
  List<Object?> get props => [items, currentPage, lastPage];
}

class ChatMessage extends Equatable {
  const ChatMessage({
    required this.id,
    required this.eventId,
    required this.body,
    required this.isSystem,
    this.createdAt,
    this.userId,
    this.userName,
  });

  final int id;
  final int eventId;
  final String body;
  final bool isSystem;
  final DateTime? createdAt;
  final int? userId;
  final String? userName;

  @override
  List<Object?> get props =>
      [id, eventId, body, isSystem, createdAt, userId, userName];
}

class JoinRequestItem extends Equatable {
  const JoinRequestItem({
    required this.id,
    required this.status,
    required this.userId,
    required this.userName,
  });

  final int id;
  final String status;
  final int userId;
  final String userName;

  @override
  List<Object?> get props => [id, status, userId, userName];
}

class EventMember extends Equatable {
  const EventMember({
    required this.userId,
    required this.name,
    required this.role,
  });

  final int userId;
  final String name;
  final String role;

  @override
  List<Object?> get props => [userId, name, role];
}

class EventDraft {
  const EventDraft({
    required this.title,
    required this.categoryId,
    required this.cityId,
    required this.startsAt,
    required this.maxParticipants,
    this.description,
    this.venue,
    this.endsAt,
    this.requirements,
    this.expectedCost,
    this.costPerPerson,
    this.imagePath,
  });

  final String title;
  final String? description;
  final int categoryId;
  final int cityId;
  final String? venue;
  final DateTime startsAt;
  final DateTime? endsAt;
  final int maxParticipants;
  final String? requirements;
  final String? expectedCost;
  final num? costPerPerson;
  final String? imagePath;
}

class NowPlayingFeed {
  const NowPlayingFeed({
    this.running,
    this.next = const [],
    this.previous = const [],
  });

  final EventItem? running;
  final List<EventItem> next;
  final List<EventItem> previous;

  bool get hasAny =>
      running != null || next.isNotEmpty || previous.isNotEmpty;
}
