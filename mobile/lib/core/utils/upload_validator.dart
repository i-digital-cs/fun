import 'dart:io';

/// Client-side upload checks (UX + resource protection).
/// Server-side validation remains authoritative (SEC-UPLOAD-001).
class UploadValidationResult {
  const UploadValidationResult.ok() : ok = true, errorKey = null;

  const UploadValidationResult.fail(this.errorKey) : ok = false;

  final bool ok;

  /// Localization key hint: `uploadTooLarge` | `uploadBadType` | `uploadMissing`
  final String? errorKey;
}

abstract final class UploadValidator {
  /// Soft client limit for identity / event images (bytes).
  static const maxImageBytes = 8 * 1024 * 1024;

  static const allowedExtensions = {
    'jpg',
    'jpeg',
    'png',
    'webp',
    'heic',
    'heif',
  };

  static Future<UploadValidationResult> validateImageFile(String path) async {
    if (path.trim().isEmpty) {
      return const UploadValidationResult.fail('uploadMissing');
    }

    final file = File(path);
    if (!await file.exists()) {
      return const UploadValidationResult.fail('uploadMissing');
    }

    final name = path.split(RegExp(r'[\\/]')).last.toLowerCase();
    final dot = name.lastIndexOf('.');
    final ext = dot >= 0 ? name.substring(dot + 1) : '';
    if (!allowedExtensions.contains(ext)) {
      return const UploadValidationResult.fail('uploadBadType');
    }

    final length = await file.length();
    if (length <= 0 || length > maxImageBytes) {
      return const UploadValidationResult.fail('uploadTooLarge');
    }

    return const UploadValidationResult.ok();
  }
}
