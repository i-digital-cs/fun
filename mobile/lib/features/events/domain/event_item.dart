import 'package:equatable/equatable.dart';
import 'package:fun_mobile/features/events/domain/maps_url_parser.dart';

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
    this.latitude,
    this.longitude,
  });

  final int id;
  final String name;
  final String? nameEn;
  final String? nameAr;
  final double? latitude;
  final double? longitude;

  @override
  List<Object?> get props => [id, name, nameEn, nameAr, latitude, longitude];
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
    this.isFavorited = false,
    this.latitude,
    this.longitude,
    this.mapsUrl,
    this.mapsOpenUrl,
    this.hasPlacePin = false,
    this.locationLabel,
    this.sex,
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
  final bool isFavorited;
  final double? latitude;
  final double? longitude;
  final String? mapsUrl;
  final String? mapsOpenUrl;
  final bool hasPlacePin;
  final String? locationLabel;

  /// Audience: `male` | `female` | `mix`.
  final String? sex;

  String get placeLine {
    final label = locationLabel?.trim();
    if (label != null && label.isNotEmpty) return label;
    if (venue.trim().isNotEmpty) {
      final cityName = city?.name;
      if (cityName != null &&
          cityName.isNotEmpty &&
          !venue.contains(cityName)) {
        return '$venue · $cityName';
      }
      return venue.trim();
    }
    return city?.name ?? '';
  }

  /// Prefer pin coordinates for external open so a moved pin always wins
  /// over a stale pasted maps link.
  String? get openMapsUrl {
    if (hasPlacePin && latitude != null && longitude != null) {
      return MapsUrlParser.googleOpenUrl(latitude!, longitude!);
    }
    final open = mapsOpenUrl?.trim();
    if (open != null && open.isNotEmpty) return open;
    final stored = mapsUrl?.trim();
    if (stored != null && stored.isNotEmpty) return stored;
    return null;
  }

  bool get isFree => costPerPerson == null || costPerPerson! <= 0;

  int get fillPercent {
    final max = maxParticipants <= 0 ? 1 : maxParticipants;
    return ((membersCount / max) * 100).round().clamp(0, 100);
  }

  bool get isUrgent => fillPercent >= 80;

  bool get isDraft => status == 'draft';
  bool get isPublished => status == 'published';
  bool get isOwnerActionable => status == 'draft' || status == 'published';

  EventItem copyWith({
    String? joinAction,
    String? status,
    int? membersCount,
    bool? readyForRatings,
    bool? isFavorited,
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
      isFavorited: isFavorited ?? this.isFavorited,
      latitude: latitude,
      longitude: longitude,
      mapsUrl: mapsUrl,
      mapsOpenUrl: mapsOpenUrl,
      hasPlacePin: hasPlacePin,
      locationLabel: locationLabel,
      sex: sex,
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
    isFavorited,
    latitude,
    longitude,
    mapsUrl,
    mapsOpenUrl,
    hasPlacePin,
    locationLabel,
    sex,
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

  EventDetail copyWith({
    EventItem? event,
    String? joinAction,
    bool? readyForRatings,
    bool? canRate,
    EventRating? myRating,
    CreatorRating? creatorRating,
  }) {
    return EventDetail(
      event: event ?? this.event,
      joinAction: joinAction ?? this.joinAction,
      readyForRatings: readyForRatings ?? this.readyForRatings,
      canRate: canRate ?? this.canRate,
      myRating: myRating ?? this.myRating,
      creatorRating: creatorRating ?? this.creatorRating,
    );
  }

  @override
  List<Object?> get props => [
    event,
    joinAction,
    readyForRatings,
    canRate,
    myRating,
    creatorRating,
  ];
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
  List<Object?> get props => [
    id,
    eventId,
    body,
    isSystem,
    createdAt,
    userId,
    userName,
  ];
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
    required this.sex,
    this.description,
    this.venue,
    this.endsAt,
    this.requirements,
    this.expectedCost,
    this.costPerPerson,
    this.imagePath,
    this.latitude,
    this.longitude,
    this.mapsUrl,
  });

  final String title;
  final String? description;
  final int categoryId;
  final int cityId;
  final String? venue;
  final DateTime startsAt;
  final DateTime? endsAt;
  final int maxParticipants;

  /// `male` | `female` | `mix`
  final String sex;
  final String? requirements;
  final String? expectedCost;
  final num? costPerPerson;
  final String? imagePath;
  final double? latitude;
  final double? longitude;
  final String? mapsUrl;
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

  bool get hasAny => running != null || next.isNotEmpty || previous.isNotEmpty;
}

class EventTicketMember extends Equatable {
  const EventTicketMember({required this.id, required this.name});

  final int id;
  final String name;

  @override
  List<Object?> get props => [id, name];
}

class EventTicketEventRef extends Equatable {
  const EventTicketEventRef({
    required this.id,
    required this.title,
    required this.status,
  });

  final int id;
  final String title;
  final String status;

  @override
  List<Object?> get props => [id, title, status];
}

/// Pass payload from `GET /events/{id}/ticket`.
class EventTicket extends Equatable {
  const EventTicket({
    required this.code,
    required this.role,
    required this.passLabel,
    required this.title,
    required this.dateLabel,
    required this.venueLine,
    this.dateIso,
    this.barcodeShadow,
    this.member,
    this.event,
  });

  final String code;
  final String role;
  final String passLabel;
  final String title;
  final String dateLabel;
  final String? dateIso;
  final String venueLine;
  final String? barcodeShadow;
  final EventTicketMember? member;
  final EventTicketEventRef? event;

  @override
  List<Object?> get props => [
    code,
    role,
    passLabel,
    title,
    dateLabel,
    dateIso,
    venueLine,
    barcodeShadow,
    member,
    event,
  ];
}

class HomeParticipationRow extends Equatable {
  const HomeParticipationRow({
    required this.event,
    this.role,
    this.requestId,
    this.status,
  });

  final EventItem event;
  final String? role;
  final int? requestId;
  final String? status;

  @override
  List<Object?> get props => [event, role, requestId, status];
}

class HomeParticipations extends Equatable {
  const HomeParticipations({
    this.memberships = const [],
    this.pendingRequests = const [],
  });

  final List<HomeParticipationRow> memberships;
  final List<HomeParticipationRow> pendingRequests;

  int get scheduledCount => memberships.length + pendingRequests.length;

  bool get isEmpty => scheduledCount == 0;

  @override
  List<Object?> get props => [memberships, pendingRequests];
}
