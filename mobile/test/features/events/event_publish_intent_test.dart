import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/domain/otp_issue_meta.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/events/data/events_remote_source.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';
import 'package:fun_mobile/features/events/presentation/create_event_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:fun_mobile/l10n/app_localizations_ar.dart';
import 'package:fun_mobile/l10n/app_localizations_en.dart';

EventDraft _sampleDraft({bool publishIntent = false}) {
  return EventDraft(
    title: 'Sunset Run',
    categoryId: 1,
    cityId: 2,
    startsAt: DateTime.utc(2030, 6, 1, 18),
    maxParticipants: 12,
    sex: 'mix',
    publishIntent: publishIntent,
  );
}

EventItem _event({required int id, required String status}) {
  return EventItem(
    id: id,
    title: 'Sunset Run',
    venue: 'Corniche',
    maxParticipants: 12,
    membersCount: 1,
    status: status,
  );
}

class _FakeEventsRepo implements EventsRepository {
  _FakeEventsRepo({
    this.createResult,
    this.createFailure,
    this.delay = Duration.zero,
  });

  Result<EventItem>? createResult;
  Failure? createFailure;
  Duration delay;
  EventDraft? lastCreateDraft;
  int createCalls = 0;
  DiscoverFilters? lastDiscoverFilters;

  @override
  Future<Result<EventItem>> create(EventDraft draft) async {
    createCalls++;
    lastCreateDraft = draft;
    if (delay > Duration.zero) {
      await Future<void>.delayed(delay);
    }
    if (createFailure != null) {
      return (failure: createFailure, data: null);
    }
    return createResult ?? (failure: const ServerFailure('missing'), data: null);
  }

  @override
  Future<Result<PaginatedEvents>> discover(DiscoverFilters filters) async {
    lastDiscoverFilters = filters;
    return (
      failure: null,
      data: const PaginatedEvents(
        items: [],
        currentPage: 1,
        lastPage: 1,
      ),
    );
  }

  @override
  noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class _FakeAuthRepository implements AuthRepository {
  @override
  Future<bool> hasToken() async => true;

  @override
  Future<Result<User>> me() async => (
        failure: null,
        data: const User(
          id: 1,
          name: 'Host',
          email: 'h@example.com',
          status: 'active',
          locale: 'en',
          canParticipateFlag: true,
        ),
      );

  @override
  Future<Result<void>> logout() async => (failure: null, data: null);

  @override
  Future<Result<AuthSession>> login({
    required String email,
    required String password,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<AuthSession>> loginWithGoogle({
    String? idToken,
    String? accessToken,
    String? locale,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<AuthSession>> loginWithApple({
    required String idToken,
    String? name,
    String? locale,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<void>> requestAccountDeletion({
    String? password,
    bool providerReauth = false,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<void>> cancelAccountDeletion() async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<AuthSession>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String sex,
    required String locale,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> verifyEmail(String code) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<OtpIssueMeta>> resendEmailVerify() async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<ForgotPasswordResult>> forgotPassword(String email) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<String>> verifyResetCode({
    required String email,
    required String code,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);
}

ProviderContainer _container(_FakeEventsRepo repo) {
  return ProviderContainer(
    overrides: [
      authRepositoryProvider.overrideWithValue(_FakeAuthRepository()),
      eventsRepositoryProvider.overrideWithValue(repo),
    ],
  );
}

void main() {
  group('eventDraftMultipartFields publish_intent', () {
    test('Create Event serializes publish_intent as "1"', () {
      final fields = eventDraftMultipartFields(
        _sampleDraft(publishIntent: true),
      );
      expect(fields['publish_intent'], '1');
      expect(fields['publish_intent'], isA<String>());
      expect(fields['title'], 'Sunset Run');
    });

    test('Save Draft serializes publish_intent as "0"', () {
      final fields = eventDraftMultipartFields(
        _sampleDraft(publishIntent: false),
      );
      expect(fields['publish_intent'], '0');
    });

    test('update path can omit publish_intent', () {
      final fields = eventDraftMultipartFields(
        _sampleDraft(publishIntent: true),
        includePublishIntent: false,
      );
      expect(fields.containsKey('publish_intent'), isFalse);
    });
  });

  group('CreateEventController submit outcomes', () {
    test('publish_intent=true + published → ok published + discover stale',
        () async {
      final repo = _FakeEventsRepo(
        createResult: (
          failure: null,
          data: _event(id: 101, status: 'published'),
        ),
      );
      final container = _container(repo);
      addTearDown(container.dispose);

      // Seed discover as bootstrapped so markStale is observable via ensureLoaded.
      final discover = container.read(discoverControllerProvider.notifier);
      await discover.ensureLoaded();
      final filtersBefore =
          container.read(discoverControllerProvider).filters;

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .submit(_sampleDraft(publishIntent: true));

      expect(repo.lastCreateDraft?.publishIntent, isTrue);
      expect(result.ok, isTrue);
      expect(result.outcome, CreateSubmitOutcome.published);
      expect(result.isPublished, isTrue);
      expect(result.event?.id, 101);

      // Stale → ensureLoaded refetches with same filters (Map/List SSOT preserved).
      await discover.ensureLoaded();
      expect(
        container.read(discoverControllerProvider).filters.cityId,
        filtersBefore.cityId,
      );
      expect(
        container.read(discoverControllerProvider).filters.distanceKm,
        filtersBefore.distanceKm,
      );
      expect(
        container.read(discoverControllerProvider).filters.q,
        filtersBefore.q,
      );
    });

    test('publish_intent=false + draft → ok draft; no claim published',
        () async {
      final repo = _FakeEventsRepo(
        createResult: (
          failure: null,
          data: _event(id: 202, status: 'draft'),
        ),
      );
      final container = _container(repo);
      addTearDown(container.dispose);

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .submit(_sampleDraft(publishIntent: false));

      expect(repo.lastCreateDraft?.publishIntent, isFalse);
      expect(result.ok, isTrue);
      expect(result.outcome, CreateSubmitOutcome.draft);
      expect(result.isPublished, isFalse);
    });

    test('Create Event unexpected draft → not ok; no published success',
        () async {
      final repo = _FakeEventsRepo(
        createResult: (
          failure: null,
          data: _event(id: 303, status: 'draft'),
        ),
      );
      final container = _container(repo);
      addTearDown(container.dispose);

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .submit(_sampleDraft(publishIntent: true));

      expect(result.ok, isFalse);
      expect(result.outcome, CreateSubmitOutcome.unexpectedDraft);
      expect(result.error, 'publish_intent_mismatch');
      expect(result.event?.id, 303);
    });

    test('Save Draft unexpected published → not ok', () async {
      final repo = _FakeEventsRepo(
        createResult: (
          failure: null,
          data: _event(id: 404, status: 'published'),
        ),
      );
      final container = _container(repo);
      addTearDown(container.dispose);

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .submit(_sampleDraft(publishIntent: false));

      expect(result.ok, isFalse);
      expect(result.outcome, CreateSubmitOutcome.unexpectedPublished);
    });

    test('HTTP 422 validation failure surfaces error', () async {
      final repo = _FakeEventsRepo(
        createFailure: const ValidationFailure('Title is required'),
      );
      final container = _container(repo);
      addTearDown(container.dispose);

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .submit(_sampleDraft(publishIntent: true));

      expect(result.ok, isFalse);
      expect(result.error, 'Title is required');
      expect(result.outcome, isNull);
    });

    test('HTTP 403 forbidden surfaces error', () async {
      final repo = _FakeEventsRepo(
        createFailure: const ForbiddenFailure('Forbidden'),
      );
      final container = _container(repo);
      addTearDown(container.dispose);

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .submit(_sampleDraft(publishIntent: true));

      expect(result.ok, isFalse);
      expect(result.error, contains('Forbidden'));
    });

    test('network failure surfaces error', () async {
      final repo = _FakeEventsRepo(
        createFailure: const NetworkFailure('No connection'),
      );
      final container = _container(repo);
      addTearDown(container.dispose);

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .submit(_sampleDraft(publishIntent: true));

      expect(result.ok, isFalse);
      expect(result.error, 'No connection');
    });

    test('double submission blocked while in flight', () async {
      final repo = _FakeEventsRepo(
        createResult: (
          failure: null,
          data: _event(id: 505, status: 'published'),
        ),
        delay: const Duration(milliseconds: 80),
      );
      final container = _container(repo);
      addTearDown(container.dispose);
      // Keep autoDispose provider alive across concurrent submits.
      final sub = container.listen(
        createEventControllerProvider(0),
        (_, __) {},
      );
      addTearDown(sub.close);
      final ctrl = container.read(createEventControllerProvider(0).notifier);

      final first = ctrl.submit(_sampleDraft(publishIntent: true));
      final second = await ctrl.submit(_sampleDraft(publishIntent: true));
      final firstResult = await first;

      expect(second.error, 'Already submitting');
      expect(firstResult.ok, isTrue);
      expect(repo.createCalls, 1);
    });
  });

  group('EN/AR publish labels', () {
    test('English Create Event / Save Draft / success copy', () {
      final en = AppLocalizationsEn();
      expect(en.createEvent, 'Create Event');
      expect(en.createDraft, 'Save Draft');
      expect(en.eventPublished, 'Event published successfully');
      expect(en.eventSaved, 'Draft saved successfully');
      expect(en.viewEvent, 'View Event');
      expect(en.continueEditing, 'Continue Editing');
    });

    test('Arabic Create Event / Save Draft / success copy', () {
      final ar = AppLocalizationsAr();
      expect(ar.createEvent, 'إنشاء الفعالية');
      expect(ar.createDraft, 'حفظ كمسودة');
      expect(ar.eventPublished, 'تم نشر الفعالية بنجاح');
      expect(ar.eventSaved, 'تم حفظ المسودة بنجاح');
      expect(ar.continueEditing, 'متابعة التحرير');
    });

    testWidgets('RTL Directionality with Arabic locale', (tester) async {
      await tester.pumpWidget(
        MaterialApp(
          locale: const Locale('ar'),
          localizationsDelegates: AppLocalizations.localizationsDelegates,
          supportedLocales: AppLocalizations.supportedLocales,
          home: Builder(
            builder: (context) {
              final l10n = AppLocalizations.of(context);
              return Directionality(
                textDirection: TextDirection.rtl,
                child: Scaffold(
                  body: Column(
                    children: [
                      Text(l10n.createEvent),
                      Text(l10n.createDraft),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('إنشاء الفعالية'), findsOneWidget);
      expect(find.text('حفظ كمسودة'), findsOneWidget);
      final directionality = tester.widget<Directionality>(
        find.byType(Directionality).first,
      );
      expect(directionality.textDirection, TextDirection.rtl);
    });
  });
}
