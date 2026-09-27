import 'package:flutter/painting.dart';

/// Base text direction from the first strong letter/digit (Arabic → RTL).
/// Keeps mixed AR/EN event titles visually stable under an LTR app shell.
TextDirection bidiTextDirection(String? text) {
  if (text == null || text.isEmpty) return TextDirection.ltr;
  final match = RegExp(r'[\p{L}\p{N}]', unicode: true).firstMatch(text);
  if (match == null) return TextDirection.ltr;
  final ch = match.group(0)!;
  if (RegExp(
    r'[\u0590-\u05FF\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]',
  ).hasMatch(ch)) {
    return TextDirection.rtl;
  }
  return TextDirection.ltr;
}
