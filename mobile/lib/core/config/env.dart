/// Compile-time config via `--dart-define`.
///
/// Example:
/// ```
/// flutter run --dart-define=API_BASE_URL=https://fun.idigital-cs.com/api
/// ```
abstract final class Env {
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://fun.idigital-cs.com/api',
  );

  static const flavor = String.fromEnvironment(
    'FLAVOR',
    defaultValue: 'prod',
  );
}
