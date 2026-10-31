import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Shared Discover search / activity / when filters for Map + List (SSOT).
///
/// Location / radius / city live in [discoveryLocationControllerProvider].
/// Map viewport bounds are an explicit, separate search dimension applied only
/// when the user taps "Search this area".
class DiscoveryQueryState {
  const DiscoveryQueryState({
    this.q,
    this.categoryId,
    this.when,
  });

  final String? q;
  final int? categoryId;

  /// API: `today` | `tomorrow` | `weekend`
  final String? when;

  String? get normalizedQ {
    final t = q?.trim();
    if (t == null || t.isEmpty) return null;
    return t;
  }

  DiscoveryQueryState copyWith({
    String? q,
    int? categoryId,
    String? when,
    bool clearQ = false,
    bool clearCategory = false,
    bool clearWhen = false,
  }) {
    return DiscoveryQueryState(
      q: clearQ ? null : (q ?? this.q),
      categoryId: clearCategory ? null : (categoryId ?? this.categoryId),
      when: clearWhen ? null : (when ?? this.when),
    );
  }

  @override
  bool operator ==(Object other) {
    return other is DiscoveryQueryState &&
        other.normalizedQ == normalizedQ &&
        other.categoryId == categoryId &&
        other.when == when;
  }

  @override
  int get hashCode => Object.hash(normalizedQ, categoryId, when);
}

class DiscoveryQueryController extends StateNotifier<DiscoveryQueryState> {
  DiscoveryQueryController() : super(const DiscoveryQueryState());

  void setQuery(String? q) {
    final next = q?.trim();
    state = state.copyWith(
      q: next,
      clearQ: next == null || next.isEmpty,
    );
  }

  void setCategoryId(int? id) {
    state = state.copyWith(categoryId: id, clearCategory: id == null);
  }

  void setWhen(String? when) {
    final next = when?.trim();
    state = state.copyWith(
      when: next,
      clearWhen: next == null || next.isEmpty,
    );
  }

  void apply({
    String? q,
    int? categoryId,
    String? when,
    bool clearCategory = false,
    bool clearWhen = false,
  }) {
    final nextQ = q?.trim();
    state = DiscoveryQueryState(
      q: (nextQ == null || nextQ.isEmpty) ? null : nextQ,
      categoryId: clearCategory ? null : (categoryId ?? state.categoryId),
      when: clearWhen
          ? null
          : ((when?.trim().isEmpty ?? true) ? state.when : when?.trim()),
    );
  }

  void clear() {
    state = const DiscoveryQueryState();
  }
}

final discoveryQueryControllerProvider =
    StateNotifierProvider<DiscoveryQueryController, DiscoveryQueryState>(
      (ref) => DiscoveryQueryController(),
    );
