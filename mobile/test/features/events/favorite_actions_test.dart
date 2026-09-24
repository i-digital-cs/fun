import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';
import 'package:fun_mobile/features/events/presentation/event_favorite_actions.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';

class _FakeEventsRepo implements EventsRepository {
  _FakeEventsRepo({this.fail = false});

  bool fail;
  int calls = 0;

  @override
  Future<Result<bool>> toggleFavorite(int id) async {
    calls++;
    if (fail) {
      return (failure: const ServerFailure('nope'), data: null);
    }
    return (failure: null, data: true);
  }

  @override
  noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  const item = EventItem(
    id: 7,
    title: 'T',
    venue: 'V',
    maxParticipants: 10,
    membersCount: 1,
    isFavorited: false,
  );

  test('toggleFavoriteInList optimistic then commits', () async {
    final repo = _FakeEventsRepo();
    List<EventItem>? optimistic;
    final outcome = await toggleFavoriteInList(
      repository: repo,
      items: const [item],
      eventId: 7,
      onOptimistic: (list) => optimistic = list,
    );
    expect(optimistic!.first.isFavorited, isTrue);
    expect(outcome.items.first.isFavorited, isTrue);
    expect(outcome.error, isNull);
    expect(repo.calls, 1);
  });

  test('toggleFavoriteInList rolls back on failure', () async {
    final repo = _FakeEventsRepo(fail: true);
    final outcome = await toggleFavoriteInList(
      repository: repo,
      items: const [item],
      eventId: 7,
    );
    expect(outcome.items.first.isFavorited, isFalse);
    expect(outcome.error, isNotNull);
  });
}
