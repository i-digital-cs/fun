/// Mirrors Blade `category_emoji()` helper.
String categoryEmoji(String? name) {
  final n = (name ?? '').toLowerCase();
  if (n.contains('foot') || n.contains('كرة القدم')) return '⚽';
  if (n.contains('basket') || n.contains('سلة')) return '🏀';
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
  if (n.contains('cinema') ||
      n.contains('film') ||
      n.contains('movie') ||
      n.contains('سينما')) {
    return '🎬';
  }
  if (n.contains('social') || n.contains('اجتماع')) return '🤝';
  if (n.contains('outdoor') || n.contains('خارج')) return '🌳';
  if (n.contains('book') || n.contains('كتاب')) return '📚';
  if (n.contains('cycl') || n.contains('دراج')) return '🚴';
  if (n.contains('play') ||
      n.contains('game') ||
      n.contains('gaming') ||
      n.contains('لعب')) {
    return '🎮';
  }
  return '✨';
}
