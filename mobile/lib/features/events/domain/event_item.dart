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

class EventCreator extends Equatable {
  const EventCreator({
    required this.id,
    required this.name,
    this.initials,
    this.profileImagePath,
    this.publicId,
  });

  final int id;
  final String name;
  final String? initials;
  final String? profileImagePath;
  final String? publicId;

  String get displayInitials {
    final fromApi = initials?.trim();
    if (fromApi != null && fromApi.isNotEmpty) return fromApi;
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return '?';
    if (parts.length == 1) return parts.first.substring(0, 1).toUpperCase();
    return (parts.first.substring(0, 1) + parts.last.substring(0, 1))
        .toUpperCase();
  }

  @override
  List<Object?> get props => [id, name, initials, profileImagePath, publicId];
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
    this.creator,
    this.categoryId,
    this.cityId,
    this.distanceKm,
    this.distancePrecision,
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
  final EventCreator? creator;
  final int? categoryId;
  final int? cityId;
  final double? distanceKm;

  /// `exact` | `city_level` | `none` — how [distanceKm] was derived.
  final String? distancePrecision;
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

  /// Prefer pin coordinates → Google Maps; otherwise rewrite parsable links
  /// to Google Maps so Open never lands on OSM by accident.
  String? get openMapsUrl {
    if (hasPlacePin && latitude != null && longitude != null) {
      return MapsUrlParser.googleOpenUrl(latitude!, longitude!);
    }
    final open = mapsOpenUrl?.trim();
    if (open != null && open.isNotEmpty) {
      final parsed = MapsUrlParser.parse(open);
      if (parsed != null) {
        return MapsUrlParser.googleOpenUrl(parsed.lat, parsed.lng);
      }
      return open;
    }
    final stored = mapsUrl?.trim();
    if (stored != null && stored.isNotEmpty) {
      final parsed = MapsUrlParser.parse(stored);
      if (parsed != null) {
        return MapsUrlParser.googleOpenUrl(parsed.lat, parsed.lng);
      }
      return stored;
    }
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
      creator: creator,
      categoryId: categoryId,
      cityId: cityId,
      distanceKm: distanceKm,
      distancePrecision: distancePrecision,
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
    creator,
    categoryId,
    cityId,
    distanceKm,
    distancePrecision,
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
    this.isSuggestion = false,
    this.selectedRadiusKm,
    this.effectiveRadiusKm,
  });

  final List<EventItem> items;
  final int currentPage;
  final int lastPage;
  final bool isSuggestion;
  final int? selectedRadiusKm;
  final int? effectiveRadiusKm;

  bool get hasMore => currentPage < lastPage;

  @override
  List<Object?> get props => [
    items,
    currentPage,
    lastPage,
    isSuggestion,
    selectedRadiusKm,
    effectiveRadiusKm,
  ];
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
    this.userPublicId,
    this.userProfileImagePath,
    this.likesCount = 0,
    this.dislikesCount = 0,
    this.myReaction,
    this.replyToId,
    this.replyTo,
    this.clientUuid,
    this.messageType = 'text',
    this.mediaUrl,
    this.mediaMime,
    this.mediaDurationMs,
  });

  final int id;
  final int eventId;
  final String body;
  final bool isSystem;
  final DateTime? createdAt;
  final int? userId;
  final String? userName;
  final String? userPublicId;
  final String? userProfileImagePath;
  final int likesCount;
  final int dislikesCount;
  final String? myReaction;
  final int? replyToId;
  final ChatMessageReply? replyTo;
  final String? clientUuid;
  final String messageType;
  final String? mediaUrl;
  final String? mediaMime;
  final int? mediaDurationMs;

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    final rawUser = json['user'];
    final user = rawUser is Map ? Map<String, dynamic>.from(rawUser) : null;
    return ChatMessage(
      id: (json['id'] as num?)?.toInt() ?? 0,
      eventId: (json['event_id'] as num?)?.toInt() ?? 0,
      body: json['body'] as String? ?? '',
      isSystem: json['is_system'] as bool? ?? false,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
      userId: (user?['id'] as num?)?.toInt(),
      userName: user?['name'] as String?,
      userPublicId: user?['public_id'] as String?,
      userProfileImagePath: user?['profile_image_path'] as String?,
      likesCount: (json['likes_count'] as num?)?.toInt() ?? 0,
      dislikesCount: (json['dislikes_count'] as num?)?.toInt() ?? 0,
      myReaction: json['my_reaction'] as String?,
      replyToId: (json['reply_to_id'] as num?)?.toInt(),
      replyTo: json['reply_to'] is Map
          ? ChatMessageReply.fromJson(
              Map<String, dynamic>.from(json['reply_to'] as Map),
            )
          : null,
      clientUuid: json['client_uuid'] as String?,
      messageType: json['message_type'] as String? ?? 'text',
      mediaUrl: json['media_url'] as String?,
      mediaMime: json['media_mime'] as String?,
      mediaDurationMs: (json['media_duration_ms'] as num?)?.toInt(),
    );
  }

  ChatMessage copyWith({
    int? likesCount,
    int? dislikesCount,
    String? myReaction,
    bool clearMyReaction = false,
  }) {
    return ChatMessage(
      id: id,
      eventId: eventId,
      body: body,
      isSystem: isSystem,
      createdAt: createdAt,
      userId: userId,
      userName: userName,
      userPublicId: userPublicId,
      userProfileImagePath: userProfileImagePath,
      likesCount: likesCount ?? this.likesCount,
      dislikesCount: dislikesCount ?? this.dislikesCount,
      myReaction: clearMyReaction ? null : (myReaction ?? this.myReaction),
      replyToId: replyToId,
      replyTo: replyTo,
      clientUuid: clientUuid,
      messageType: messageType,
      mediaUrl: mediaUrl,
      mediaMime: mediaMime,
      mediaDurationMs: mediaDurationMs,
    );
  }

  @override
  List<Object?> get props => [
    id,
    eventId,
    body,
    isSystem,
    createdAt,
    userId,
    userName,
    userPublicId,
    userProfileImagePath,
    likesCount,
    dislikesCount,
    myReaction,
    replyToId,
    replyTo,
    clientUuid,
    messageType,
    mediaUrl,
    mediaMime,
    mediaDurationMs,
  ];
}

class ChatMessageReply extends Equatable {
  const ChatMessageReply({required this.id, required this.body, this.userName});

  final int id;
  final String body;
  final String? userName;

  factory ChatMessageReply.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map
        ? Map<String, dynamic>.from(user)
        : const <String, dynamic>{};
    return ChatMessageReply(
      id: (json['id'] as num?)?.toInt() ?? 0,
      body: json['body'] as String? ?? '',
      userName: userMap['name'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, body, userName];
}

class MessageReactor extends Equatable {
  const MessageReactor({
    required this.id,
    required this.name,
    this.publicId,
    this.initials,
    this.profileImagePath,
    this.reactedAt,
  });

  final int id;
  final String name;
  final String? publicId;
  final String? initials;
  final String? profileImagePath;
  final DateTime? reactedAt;

  factory MessageReactor.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map
        ? Map<String, dynamic>.from(user)
        : const <String, dynamic>{};
    return MessageReactor(
      id: (userMap['id'] as num?)?.toInt() ?? 0,
      name: userMap['name'] as String? ?? '',
      publicId: userMap['public_id'] as String?,
      initials: userMap['initials'] as String?,
      profileImagePath: userMap['profile_image_path'] as String?,
      reactedAt: json['reacted_at'] is String
          ? DateTime.tryParse(json['reacted_at'] as String)
          : null,
    );
  }

  @override
  List<Object?> get props => [id, name, publicId, initials, profileImagePath];
}

class MessageReactions extends Equatable {
  const MessageReactions({this.likes = const [], this.dislikes = const []});

  final List<MessageReactor> likes;
  final List<MessageReactor> dislikes;

  factory MessageReactions.fromJson(Map<String, dynamic> json) {
    List<MessageReactor> parse(dynamic raw) {
      if (raw is! List) return const [];
      return raw
          .whereType<Map>()
          .map((e) => MessageReactor.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    }

    return MessageReactions(
      likes: parse(json['likes']),
      dislikes: parse(json['dislikes']),
    );
  }

  @override
  List<Object?> get props => [likes, dislikes];
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
    /// When true, `POST /events` must publish in the same request (OPTION A).
    /// Omitted/false → draft. Multipart sends `"1"` / `"0"` for Laravel booleans.
    this.publishIntent = false,
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
  final bool publishIntent;

  EventDraft copyWith({
    String? title,
    String? description,
    int? categoryId,
    int? cityId,
    String? venue,
    DateTime? startsAt,
    DateTime? endsAt,
    int? maxParticipants,
    String? sex,
    String? requirements,
    String? expectedCost,
    num? costPerPerson,
    String? imagePath,
    double? latitude,
    double? longitude,
    String? mapsUrl,
    bool? publishIntent,
  }) {
    return EventDraft(
      title: title ?? this.title,
      description: description ?? this.description,
      categoryId: categoryId ?? this.categoryId,
      cityId: cityId ?? this.cityId,
      venue: venue ?? this.venue,
      startsAt: startsAt ?? this.startsAt,
      endsAt: endsAt ?? this.endsAt,
      maxParticipants: maxParticipants ?? this.maxParticipants,
      sex: sex ?? this.sex,
      requirements: requirements ?? this.requirements,
      expectedCost: expectedCost ?? this.expectedCost,
      costPerPerson: costPerPerson ?? this.costPerPerson,
      imagePath: imagePath ?? this.imagePath,
      latitude: latitude ?? this.latitude,
      longitude: longitude ?? this.longitude,
      mapsUrl: mapsUrl ?? this.mapsUrl,
      publishIntent: publishIntent ?? this.publishIntent,
    );
  }
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
