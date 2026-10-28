/// Pure helper for Discover's collapsible page header.
///
/// Scroll down past the top → hide. Scroll up → show. Near top → always show.
bool discoverHeaderVisibleAfterScroll({
  required bool currentlyVisible,
  required double offset,
  required double delta,
  double topSlack = 8,
  double hideThreshold = 6,
  double showThreshold = 6,
}) {
  if (offset <= topSlack) return true;
  if (delta > hideThreshold && currentlyVisible) return false;
  if (delta < -showThreshold && !currentlyVisible) return true;
  return currentlyVisible;
}
