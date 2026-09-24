import 'package:flutter/material.dart';

/// Chevron that points "forward" in the current text direction (A11Y-RTL-001).
IconData directionalChevronForward(BuildContext context) {
  return Directionality.of(context) == TextDirection.rtl
      ? Icons.chevron_left
      : Icons.chevron_right;
}
