import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/maps_url_parser.dart';
import 'package:fun_mobile/features/events/domain/place_suggestion.dart';
import 'package:fun_mobile/features/home/presentation/home_nearby_controller.dart';

/// Place pin status keys mapped to l10n by the page (ARCH-GOD-CREATE-001).
enum CreatePlaceStatus {
  none,
  pinned,
  fromLink,
  fromSuggest,
  resolving,
  unresolved,
  cleared,
}

enum CreateGate { none, needIdentity }

class CreateBootstrapResult {
  const CreateBootstrapResult({this.gate = CreateGate.none, this.editEvent});

  final CreateGate gate;
  final EventItem? editEvent;
}

enum CreateSubmitOutcome {
  /// Create/update succeeded; status matches intent.
  published,
  draft,

  /// HTTP success but status does not match publish_intent (do not claim success).
  unexpectedDraft,
  unexpectedPublished,
}

class CreateSubmitResult {
  const CreateSubmitResult({
    this.error,
    this.event,
    this.outcome,
    @Deprecated('Use outcome; dialog publish is no longer the primary path')
    this.offerPublish = false,
  });

  final String? error;
  final EventItem? event;
  final CreateSubmitOutcome? outcome;
  final bool offerPublish;

  bool get ok =>
      error == null &&
      event != null &&
      (outcome == CreateSubmitOutcome.published ||
          outcome == CreateSubmitOutcome.draft);

  bool get isPublished => outcome == CreateSubmitOutcome.published;
  bool get isDraft => outcome == CreateSubmitOutcome.draft;
}

class CreateEventState {
  const CreateEventState({
    this.categories = const [],
    this.cities = const [],
    this.categoryId,
    this.cityId,
    this.startsAt,
    this.endsAt,
    this.imagePath,
    this.latitude,
    this.longitude,
    this.resolvedMapsUrl,
    this.placeStatus = CreatePlaceStatus.none,
    this.loadingCatalog = false,
    this.loadingEvent = false,
    this.submitting = false,
    this.error,
  });

  final List<CategoryItem> categories;
  final List<CityItem> cities;
  final int? categoryId;
  final int? cityId;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final String? imagePath;
  final double? latitude;
  final double? longitude;
  final String? resolvedMapsUrl;
  final CreatePlaceStatus placeStatus;
  final bool loadingCatalog;
  final bool loadingEvent;
  final bool submitting;
  final String? error;

  bool get isLoading => loadingCatalog || loadingEvent;

  CreateEventState copyWith({
    List<CategoryItem>? categories,
    List<CityItem>? cities,
    int? categoryId,
    int? cityId,
    DateTime? startsAt,
    DateTime? endsAt,
    String? imagePath,
    double? latitude,
    double? longitude,
    String? resolvedMapsUrl,
    CreatePlaceStatus? placeStatus,
    bool? loadingCatalog,
    bool? loadingEvent,
    bool? submitting,
    String? error,
    bool clearError = false,
    bool clearImage = false,
    bool clearPlace = false,
    bool clearResolvedMapsUrl = false,
    bool clearStartsAt = false,
    bool clearEndsAt = false,
  }) {
    return CreateEventState(
      categories: categories ?? this.categories,
      cities: cities ?? this.cities,
      categoryId: categoryId ?? this.categoryId,
      cityId: cityId ?? this.cityId,
      startsAt: clearStartsAt ? null : (startsAt ?? this.startsAt),
      endsAt: clearEndsAt ? null : (endsAt ?? this.endsAt),
      imagePath: clearImage ? null : (imagePath ?? this.imagePath),
      latitude: clearPlace ? null : (latitude ?? this.latitude),
      longitude: clearPlace ? null : (longitude ?? this.longitude),
      resolvedMapsUrl: clearPlace || clearResolvedMapsUrl
          ? null
          : (resolvedMapsUrl ?? this.resolvedMapsUrl),
      placeStatus: placeStatus ?? this.placeStatus,
      loadingCatalog: loadingCatalog ?? this.loadingCatalog,
      loadingEvent: loadingEvent ?? this.loadingEvent,
      submitting: submitting ?? this.submitting,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

/// Catalog / edit-load / maps resolve / submit (ARCH-GOD-CREATE-001).
/// Form text controllers stay on the page.
class CreateEventController extends StateNotifier<CreateEventState> {
  CreateEventController(this._ref, this.eventId)
    : super(const CreateEventState(loadingCatalog: true));

  final Ref _ref;

  /// `0` = create mode; positive = edit event id.
  final int eventId;

  /// Sync lock so concurrent taps cannot both pass `state.submitting` before it flips.
  bool _submitLocked = false;

  bool get isEdit => eventId > 0;

  Future<CreateBootstrapResult> bootstrap() async {
    final auth = _ref.read(authControllerProvider);
    if (auth is Authenticated && !auth.user.canParticipate) {
      state = state.copyWith(loadingCatalog: false);
      return const CreateBootstrapResult(gate: CreateGate.needIdentity);
    }
    await loadCatalog();
    EventItem? editEvent;
    if (isEdit) {
      editEvent = await loadEventForEdit();
    }
    return CreateBootstrapResult(editEvent: editEvent);
  }

  Future<void> loadCatalog() async {
    state = state.copyWith(loadingCatalog: true, clearError: true);
    final cats = await _ref.read(catalogRepositoryProvider).categories();
    final cities = await _ref.read(catalogRepositoryProvider).cities();
    if (cats.failure != null || cities.failure != null) {
      state = state.copyWith(
        loadingCatalog: false,
        error: cats.failure?.message ?? cities.failure?.message,
      );
      return;
    }
    final categories = cats.data ?? const <CategoryItem>[];
    final cityList = cities.data ?? const <CityItem>[];
    state = state.copyWith(
      loadingCatalog: false,
      categories: categories,
      cities: cityList,
      categoryId: state.categoryId ?? categories.firstOrNull?.id,
      cityId: state.cityId ?? cityList.firstOrNull?.id,
      clearError: true,
    );
  }

  Future<EventItem?> loadEventForEdit() async {
    if (!isEdit) return null;
    state = state.copyWith(loadingEvent: true, clearError: true);
    final result = await _ref.read(eventsRepositoryProvider).show(eventId);
    state = state.copyWith(loadingEvent: false);
    if (result.failure != null || result.data == null) {
      state = state.copyWith(error: result.failure?.message);
      return null;
    }
    final event = result.data!.event;
    state = state.copyWith(
      categoryId: event.categoryId ?? event.category?.id,
      cityId: event.cityId ?? event.city?.id,
      startsAt: event.startsAt,
      endsAt: event.endsAt,
      latitude: event.latitude,
      longitude: event.longitude,
      placeStatus: event.hasPlacePin
          ? CreatePlaceStatus.pinned
          : CreatePlaceStatus.none,
      clearError: true,
    );
    return event;
  }

  void setCategoryId(int? id) => state = state.copyWith(categoryId: id);

  void setCityId(int? id) => state = state.copyWith(cityId: id);

  void setStartsAt(DateTime value) => state = state.copyWith(startsAt: value);

  void setEndsAt(DateTime value) => state = state.copyWith(endsAt: value);

  void setImagePath(String path) => state = state.copyWith(imagePath: path);

  void clearImage() => state = state.copyWith(clearImage: true);

  Future<void> applyMapsUrl(String raw) async {
    final parsed = MapsUrlParser.parse(raw);
    if (parsed != null) {
      state = state.copyWith(
        latitude: parsed.lat,
        longitude: parsed.lng,
        placeStatus: CreatePlaceStatus.fromLink,
        clearResolvedMapsUrl: true,
      );
      return;
    }
    if (raw.trim().isEmpty) return;
    state = state.copyWith(placeStatus: CreatePlaceStatus.resolving);
    final result = await _ref
        .read(eventsRepositoryProvider)
        .resolveMapsUrl(raw.trim());
    if (result.failure != null || result.data == null) {
      state = state.copyWith(placeStatus: CreatePlaceStatus.unresolved);
      return;
    }
    state = state.copyWith(
      latitude: result.data!.lat,
      longitude: result.data!.lng,
      resolvedMapsUrl: result.data!.mapsUrl,
      placeStatus: CreatePlaceStatus.fromLink,
    );
  }

  Future<List<PlaceSuggestion>> suggestPlaces(String query) async {
    final cityId = state.cityId;
    final result = await _ref
        .read(eventsRepositoryProvider)
        .suggestPlaces(query, cityId: cityId);
    if (result.failure != null || result.data == null) {
      return const [];
    }
    return result.data!;
  }

  /// Apply a place suggestion: set pin + OSM maps_url.
  String applySuggestion({
    required String label,
    required double lat,
    required double lng,
  }) {
    final nextUrl = MapsUrlParser.openStreetMapUrl(lat, lng);
    state = state.copyWith(
      latitude: lat,
      longitude: lng,
      placeStatus: CreatePlaceStatus.fromSuggest,
      resolvedMapsUrl: nextUrl,
    );
    return nextUrl;
  }

  void clearPlace() {
    state = state.copyWith(
      clearPlace: true,
      placeStatus: CreatePlaceStatus.cleared,
    );
  }

  /// Returns OSM maps URL always matching the pin (link field is updated).
  String? pin(double lat, double lng, {required String currentMapsUrl}) {
    final nextUrl = MapsUrlParser.openStreetMapUrl(lat, lng);
    state = state.copyWith(
      latitude: lat,
      longitude: lng,
      placeStatus: CreatePlaceStatus.pinned,
      resolvedMapsUrl: nextUrl,
    );
    return nextUrl;
  }

  /// Create or update. For create, [draft.publishIntent] drives OPTION A.
  Future<CreateSubmitResult> submit(EventDraft draft) async {
    if (_submitLocked || state.submitting) {
      return const CreateSubmitResult(error: 'Already submitting');
    }
    _submitLocked = true;
    state = state.copyWith(submitting: true, clearError: true);
    try {
      final repo = _ref.read(eventsRepositoryProvider);
      final result = isEdit
          ? await repo.update(eventId, draft.copyWith(publishIntent: false))
          : await repo.create(draft);
      if (result.failure != null || result.data == null) {
        return CreateSubmitResult(
          error: result.failure?.message ?? 'Error',
        );
      }
      final event = result.data!;
      if (isEdit) {
        return CreateSubmitResult(
          event: event,
          outcome: event.isPublished
              ? CreateSubmitOutcome.published
              : CreateSubmitOutcome.draft,
        );
      }

      final wantedPublish = draft.publishIntent;
      if (wantedPublish) {
        if (event.isPublished) {
          _onPublishedFeeds();
          return CreateSubmitResult(
            event: event,
            outcome: CreateSubmitOutcome.published,
          );
        }
        // Do not claim published; draft may exist from partial server failure.
        debugPrint(
          'CreateEvent: publish_intent=true but status=${event.status} id=${event.id}',
        );
        return CreateSubmitResult(
          event: event,
          outcome: CreateSubmitOutcome.unexpectedDraft,
          error: 'publish_intent_mismatch',
        );
      }

      if (event.isDraft) {
        return CreateSubmitResult(
          event: event,
          outcome: CreateSubmitOutcome.draft,
        );
      }
      debugPrint(
        'CreateEvent: publish_intent=false but status=${event.status} id=${event.id}',
      );
      return CreateSubmitResult(
        event: event,
        outcome: CreateSubmitOutcome.unexpectedPublished,
        error: 'draft_intent_mismatch',
      );
    } finally {
      _submitLocked = false;
      if (mounted) {
        state = state.copyWith(submitting: false);
      }
    }
  }

  void _onPublishedFeeds() {
    _ref.read(discoverControllerProvider.notifier).markStale();
    // Home nearby/soon feeds — best-effort; never block create success UX.
    unawaited(() async {
      try {
        await _ref.read(homeNearbyControllerProvider.notifier).load();
      } catch (_) {}
    }());
  }

  Future<String?> publish(int id) async {
    final result = await _ref.read(eventsRepositoryProvider).publish(id);
    if (result.failure == null) {
      _onPublishedFeeds();
    }
    return result.failure?.message;
  }
}

/// [eventId] `0` = create; positive = edit.
final createEventControllerProvider = StateNotifierProvider.autoDispose
    .family<CreateEventController, CreateEventState, int>((ref, eventId) {
      return CreateEventController(ref, eventId);
    });
