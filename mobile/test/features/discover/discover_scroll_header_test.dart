import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/discover/presentation/discover_header_scroll.dart';

void main() {
  group('discoverHeaderVisibleAfterScroll', () {
    test('stays visible near the top', () {
      expect(
        discoverHeaderVisibleAfterScroll(
          currentlyVisible: false,
          offset: 0,
          delta: 20,
        ),
        isTrue,
      );
      expect(
        discoverHeaderVisibleAfterScroll(
          currentlyVisible: true,
          offset: 4,
          delta: 10,
        ),
        isTrue,
      );
    });

    test('hides when scrolling down past the top', () {
      expect(
        discoverHeaderVisibleAfterScroll(
          currentlyVisible: true,
          offset: 40,
          delta: 12,
        ),
        isFalse,
      );
    });

    test('shows again when scrolling up', () {
      expect(
        discoverHeaderVisibleAfterScroll(
          currentlyVisible: false,
          offset: 120,
          delta: -10,
        ),
        isTrue,
      );
    });

    test('ignores tiny jitter', () {
      expect(
        discoverHeaderVisibleAfterScroll(
          currentlyVisible: true,
          offset: 50,
          delta: 2,
        ),
        isTrue,
      );
      expect(
        discoverHeaderVisibleAfterScroll(
          currentlyVisible: false,
          offset: 50,
          delta: -2,
        ),
        isFalse,
      );
    });
  });
}
