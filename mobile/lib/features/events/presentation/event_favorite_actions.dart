import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';

/// Optimistic favorite toggle with rollback (ARCH-FAV-001).
Future<({List<EventItem> items, String? error})> toggleFavoriteInList({
  required EventsRepository repository,
  required List<EventItem> items,
  required int eventId,
  void Function(List<EventItem> optimistic)? onOptimistic,
}) async {
  final index = items.indexWhere((e) => e.id == eventId);
  if (index < 0) return (items: items, error: null);

  final previous = items[index].isFavorited;
  final optimistic = [
    for (var i = 0; i < items.length; i++)
      if (i == index) items[i].copyWith(isFavorited: !previous) else items[i],
  ];
  onOptimistic?.call(optimistic);

  final result = await repository.toggleFavorite(eventId);
  if (result.failure != null) {
    return (items: items, error: result.failure!.message);
  }

  final favorited = result.data ?? !previous;
  return (
    items: [
      for (final e in optimistic)
        if (e.id == eventId) e.copyWith(isFavorited: favorited) else e,
    ],
    error: null,
  );
}
