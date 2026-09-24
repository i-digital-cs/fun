/// Pure domain types for in-app update checks (ARCH-DOMAIN-001).
enum UpdateKind { none, soft, force }

class AppUpdateInfo {
  const AppUpdateInfo({
    required this.kind,
    this.message,
    this.storeUrl,
  });

  final UpdateKind kind;
  final String? message;
  final String? storeUrl;

  static const none = AppUpdateInfo(kind: UpdateKind.none);
}

int compareSemver(String a, String b) {
  List<int> parts(String v) =>
      v.split('.').map((e) => int.tryParse(e.trim()) ?? 0).toList();
  final pa = parts(a);
  final pb = parts(b);
  final n = pa.length > pb.length ? pa.length : pb.length;
  for (var i = 0; i < n; i++) {
    final x = i < pa.length ? pa[i] : 0;
    final y = i < pb.length ? pb[i] : 0;
    if (x != y) return x.compareTo(y);
  }
  return 0;
}
