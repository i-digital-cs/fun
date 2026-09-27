class SearchSuggestItem {
  const SearchSuggestItem({
    required this.id,
    required this.title,
    this.subtitle,
    this.meta,
    this.joinAction,
    this.badge,
  });

  final int id;
  final String title;
  final String? subtitle;
  final String? meta;
  final String? joinAction;
  final String? badge;
}
