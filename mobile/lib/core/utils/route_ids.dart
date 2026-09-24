/// NAV-001 — never coerce invalid IDs to `0`.
int? parsePositiveRouteId(String? raw) {
  final id = int.tryParse(raw ?? '');
  if (id == null || id <= 0) return null;
  return id;
}
