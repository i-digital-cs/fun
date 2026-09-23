import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';

void main() {
  test('brand primary matches DESIGN.md indigo', () {
    expect(AppColors.primary, const Color(0xFF4F46E5));
    expect(AppColors.accent, const Color(0xFFF97316));
  });
}
