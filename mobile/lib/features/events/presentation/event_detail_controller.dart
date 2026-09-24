import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';

/// UI gate / mutation result for event detail actions (ARCH-GOD-DETAIL-001).
enum EventDetailGate { none, needLogin, needIdentity }

class EventDetailMutation {
  const EventDetailMutation({
    this.error,
    this.gate = EventDetailGate.none,
    this.successMessageKey,
  });

  final String? error;
  final EventDetailGate gate;

  /// Optional l10n key handled by the page (`eventPublished`, `userBlocked`).
  final String? successMessageKey;

  bool get ok => error == null && gate == EventDetailGate.none;

  static const okReload = EventDetailMutation();
}

class EventDetailState {
  const EventDetailState({
    this.detail,
    this.ratings = const [],
    this.error,
    this.isLoading = false,
    this.acting = false,
    this.favoriting = false,
  });

  final EventDetail? detail;
  final List<EventRating> ratings;
  final String? error;
  final bool isLoading;
  final bool acting;
  final bool favoriting;

  EventDetailState copyWith({
    EventDetail? detail,
    List<EventRating>? ratings,
    String? error,
    bool clearError = false,
    bool clearDetail = false,
    bool? isLoading,
    bool? acting,
    bool? favoriting,
  }) {
    return EventDetailState(
      detail: clearDetail ? null : (detail ?? this.detail),
      ratings: ratings ?? this.ratings,
      error: clearError ? null : (error ?? this.error),
      isLoading: isLoading ?? this.isLoading,
      acting: acting ?? this.acting,
      favoriting: favoriting ?? this.favoriting,
    );
  }
}

/// Loads detail + owns join/favorite/lifecycle mutations (ARCH-GOD-DETAIL-001).
class EventDetailController extends StateNotifier<EventDetailState> {
  EventDetailController(this._ref, this.eventId)
    : super(const EventDetailState(isLoading: true));

  final Ref _ref;
  final int eventId;

  int? get currentUserId {
    final auth = _ref.read(authControllerProvider);
    return auth is Authenticated ? auth.user.id : null;
  }

  bool get isOwner {
    final detail = state.detail;
    final userId = currentUserId;
    return detail != null && userId != null && detail.event.createdBy == userId;
  }

  bool get canEdit {
    final event = state.detail?.event;
    if (!isOwner || event == null) return false;
    return event.status != 'cancelled' && event.status != 'completed';
  }

  bool get canParticipate {
    final auth = _ref.read(authControllerProvider);
    return auth is Authenticated && auth.user.canParticipate;
  }

  Future<void> load() async {
    state = state.copyWith(isLoading: true, clearError: true);
    final repo = _ref.read(eventsRepositoryProvider);
    final detailResult = await repo.show(eventId);
    final ratingsResult = await repo.ratings(eventId);
    if (detailResult.failure != null) {
      state = state.copyWith(
        isLoading: false,
        error: detailResult.failure!.message,
        clearDetail: true,
      );
      return;
    }
    state = state.copyWith(
      isLoading: false,
      detail: detailResult.data,
      ratings: ratingsResult.data ?? const [],
      clearError: true,
    );
  }

  Future<EventDetailMutation> toggleFavorite() async {
    final detail = state.detail;
    if (detail == null || state.favoriting) {
      return const EventDetailMutation();
    }
    final auth = _ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      return const EventDetailMutation(gate: EventDetailGate.needLogin);
    }

    final previous = detail.event.isFavorited;
    state = state.copyWith(
      favoriting: true,
      detail: detail.copyWith(
        event: detail.event.copyWith(isFavorited: !previous),
      ),
    );

    final result = await _ref
        .read(eventsRepositoryProvider)
        .toggleFavorite(eventId);
    state = state.copyWith(favoriting: false);

    if (result.failure != null) {
      state = state.copyWith(
        detail: detail.copyWith(
          event: detail.event.copyWith(isFavorited: previous),
        ),
      );
      return EventDetailMutation(error: result.failure!.message);
    }

    final favorited = result.data ?? !previous;
    state = state.copyWith(
      detail: detail.copyWith(
        event: detail.event.copyWith(isFavorited: favorited),
      ),
    );
    return EventDetailMutation.okReload;
  }

  Future<EventDetailMutation> join() async {
    final auth = _ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      return const EventDetailMutation(gate: EventDetailGate.needLogin);
    }
    // AUTHZ-JOIN-001: client UX gate; server remains authoritative.
    if (!auth.user.canParticipate) {
      return const EventDetailMutation(gate: EventDetailGate.needIdentity);
    }
    return _mutate(() async {
      final result = await _ref.read(eventsRepositoryProvider).join(eventId);
      return result.failure?.message;
    });
  }

  Future<EventDetailMutation> leave() => _mutate(() async {
    final result = await _ref.read(eventsRepositoryProvider).leave(eventId);
    return result.failure?.message;
  });

  Future<EventDetailMutation> cancelJoinRequest() => _mutate(() async {
    final result = await _ref
        .read(eventsRepositoryProvider)
        .cancelJoinRequest(eventId);
    return result.failure?.message;
  });

  Future<EventDetailMutation> publish() => _mutate(() async {
    final result = await _ref.read(eventsRepositoryProvider).publish(eventId);
    return result.failure?.message;
  }, successMessageKey: 'eventPublished');

  Future<EventDetailMutation> cancelEvent() => _mutate(() async {
    final result = await _ref.read(eventsRepositoryProvider).cancel(eventId);
    return result.failure?.message;
  });

  Future<EventDetailMutation> completeEvent() => _mutate(() async {
    final result = await _ref.read(eventsRepositoryProvider).complete(eventId);
    return result.failure?.message;
  });

  Future<EventDetailMutation> blockCreator() async {
    final creatorId = state.detail?.event.createdBy;
    if (creatorId == null) return const EventDetailMutation();
    final result = await _ref.read(safetyRepositoryProvider).block(creatorId);
    if (result.failure != null) {
      return EventDetailMutation(error: result.failure!.message);
    }
    return const EventDetailMutation(successMessageKey: 'userBlocked');
  }

  Future<EventDetailMutation> _mutate(
    Future<String?> Function() action, {
    String? successMessageKey,
  }) async {
    state = state.copyWith(acting: true);
    final error = await action();
    state = state.copyWith(acting: false);
    if (error != null) return EventDetailMutation(error: error);
    await load();
    return EventDetailMutation(successMessageKey: successMessageKey);
  }
}

final eventDetailControllerProvider = StateNotifierProvider.autoDispose
    .family<EventDetailController, EventDetailState, int>((ref, eventId) {
      return EventDetailController(ref, eventId);
    });
