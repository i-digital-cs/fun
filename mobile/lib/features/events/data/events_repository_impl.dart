import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/events/data/events_remote_source.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';
import 'package:fun_mobile/features/events/domain/place_suggestion.dart';

class EventsRepositoryImpl implements EventsRepository {
  EventsRepositoryImpl(this._remote);

  final EventsRemoteSource _remote;

  @override
  Future<Result<PaginatedEvents>> discover(DiscoverFilters filters) =>
      _remote.discover(filters);

  @override
  Future<Result<HomeDiscoveryBundle>> discoverHome(DiscoverFilters filters) =>
      _remote.discoverHome(filters);

  @override
  Future<Result<PaginatedEvents>> mine({int page = 1}) =>
      _remote.mine(page: page);

  @override
  Future<Result<EventDetail>> show(int id) => _remote.show(id);

  @override
  Future<Result<EventItem>> create(EventDraft draft) => _remote.create(draft);

  @override
  Future<Result<EventItem>> update(int id, EventDraft draft) =>
      _remote.update(id, draft);

  @override
  Future<Result<EventItem>> publish(int id) => _remote.publish(id);

  @override
  Future<Result<EventItem>> cancel(int id) => _remote.cancel(id);

  @override
  Future<Result<EventItem>> complete(int id) => _remote.complete(id);

  @override
  Future<Result<String>> join(int id, {String? message}) =>
      _remote.join(id, message: message);

  @override
  Future<Result<void>> leave(int id) => _remote.leave(id);

  @override
  Future<Result<String>> cancelJoinRequest(int id) =>
      _remote.cancelJoinRequest(id);

  @override
  Future<Result<List<JoinRequestItem>>> joinRequests(int id) =>
      _remote.joinRequests(id);

  @override
  Future<Result<void>> acceptRequest(int eventId, int requestId) =>
      _remote.acceptRequest(eventId, requestId);

  @override
  Future<Result<void>> rejectRequest(int eventId, int requestId) =>
      _remote.rejectRequest(eventId, requestId);

  @override
  Future<Result<List<EventMember>>> members(int id) => _remote.members(id);

  @override
  Future<Result<void>> promoteMember(int eventId, int userId) =>
      _remote.promoteMember(eventId, userId);

  @override
  Future<Result<void>> demoteMember(int eventId, int userId) =>
      _remote.demoteMember(eventId, userId);

  @override
  Future<Result<void>> transferOwnership(int eventId, int userId) =>
      _remote.transferOwnership(eventId, userId);

  @override
  Future<Result<List<ChatMessage>>> messages(int id, {Object? cancelToken}) =>
      _remote.messages(id, cancelToken: cancelToken);

  @override
  Future<Result<ChatMessage>> postMessage(
    int id,
    String body, {
    int? replyToId,
    String? clientUuid,
    String? mediaPath,
    String? mediaKind,
    int? durationMs,
  }) => _remote.postMessage(
    id,
    body,
    replyToId: replyToId,
    clientUuid: clientUuid,
    mediaPath: mediaPath,
    mediaKind: mediaKind,
    durationMs: durationMs,
  );

  @override
  Future<Result<List<int>>> messageMedia(int eventId, int messageId) =>
      _remote.messageMedia(eventId, messageId);

  @override
  Future<Result<ChatMessage>> reactToMessage(
    int eventId,
    int messageId, {
    String? type,
  }) => _remote.reactToMessage(eventId, messageId, type: type);

  @override
  Future<Result<MessageReactions>> messageReactions(
    int eventId,
    int messageId,
  ) => _remote.messageReactions(eventId, messageId);

  @override
  Future<Result<List<EventRating>>> ratings(int id) => _remote.ratings(id);

  @override
  Future<Result<EventRating?>> myRating(int id) => _remote.myRating(id);

  @override
  Future<Result<EventRating>> rate(
    int id, {
    required int stars,
    String? comment,
  }) => _remote.rate(id, stars: stars, comment: comment);

  @override
  Future<Result<NowPlayingFeed>> nowPlaying({int? cityId}) =>
      _remote.nowPlaying(cityId: cityId);

  @override
  Future<Result<PaginatedEvents>> favorites({int page = 1}) =>
      _remote.favorites(page: page);

  @override
  Future<Result<bool>> toggleFavorite(int id) => _remote.toggleFavorite(id);

  @override
  Future<Result<EventTicket>> ticket(int id) => _remote.ticket(id);

  @override
  Future<Result<({double lat, double lng, String mapsUrl})>> resolveMapsUrl(
    String url,
  ) => _remote.resolveMapsUrl(url);

  @override
  Future<Result<List<PlaceSuggestion>>> suggestPlaces(
    String query, {
    int? cityId,
  }) => _remote.suggestPlaces(query, cityId: cityId);
}
