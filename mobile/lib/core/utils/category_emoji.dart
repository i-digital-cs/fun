/// Mirrors Blade `category_emoji()` helper.
String categoryEmoji(String? name) {
  final n = (name ?? '').toLowerCase();
  if (n.contains('foot') || n.contains('كرة')) return '⚽';
  if (n.contains('padel') ||
      n.contains('tennis') ||
      n.contains('بادل') ||
      n.contains('تنس')) {
    return '🎾';
  }
  if (n.contains('run') || n.contains('جري')) return '🏃';
  if (n.contains('chess') || n.contains('board') || n.contains('شطرنج')) {
    return '♟️';
  }
  if (n.contains('book') || n.contains('كتاب')) return '📚';
  if (n.contains('cycl') || n.contains('دراج')) return '🚴';
  if (n.contains('play') || n.contains('game') || n.contains('لعب')) {
    return '🎮';
  }
  return '✨';
}
