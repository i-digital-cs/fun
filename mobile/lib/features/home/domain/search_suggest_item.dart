class SearchSuggestItem {
  const SearchSuggestItem({
    required this.id,
    required this.title,
    this.subtitle,
    this.meta,
  });

  final int id;
  final String title;
  final String? subtitle;
  final String? meta;
}
