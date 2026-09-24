// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appName => 'Fun';

  @override
  String get login => 'Log in';

  @override
  String get register => 'Register';

  @override
  String get logout => 'Log out';

  @override
  String get email => 'Email';

  @override
  String get password => 'Password';

  @override
  String get passwordConfirmation => 'Confirm password';

  @override
  String get name => 'Name';

  @override
  String get remember => 'Remember me';

  @override
  String get language => 'Language';

  @override
  String get noAccount => 'Don\'t have an account?';

  @override
  String get haveAccount => 'Already have an account?';

  @override
  String get navHome => 'Home';

  @override
  String get navDiscover => 'Discover';

  @override
  String get navCreate => 'Create';

  @override
  String get navMyEvents => 'My Events';

  @override
  String get navProfile => 'Profile';

  @override
  String get navExplore => 'Explore';

  @override
  String get publicProfile => 'Public profile';

  @override
  String get yourPublicId => 'Your public ID';

  @override
  String get openPublicProfile => 'Open public profile';

  @override
  String get chatsTitle => 'Chats';

  @override
  String get chatsEmptyTitle => 'No chats yet';

  @override
  String get chatsEmptyBody =>
      'Join an event to start chatting with other members.';

  @override
  String get chatsNoMessages => 'No messages yet';

  @override
  String get chatsYou => 'You';

  @override
  String get openChat => 'Open chat';

  @override
  String get hostedEvents => 'Hosted events';

  @override
  String get statHosted => 'Hosted';

  @override
  String get statJoined => 'Joined';

  @override
  String get statRating => 'Rating';

  @override
  String get profileNotFound => 'Profile not found';

  @override
  String greetingMorning(String name) {
    return 'Good morning, $name';
  }

  @override
  String greetingAfternoon(String name) {
    return 'Good afternoon, $name';
  }

  @override
  String greetingEvening(String name) {
    return 'Good evening, $name';
  }

  @override
  String get greetingSub => 'Ready to find something fun to do today?';

  @override
  String get heroEyebrow => 'Activities happening around you';

  @override
  String get heroTitle1 => 'Find people.';

  @override
  String get heroTitle2 => 'Do what you love.';

  @override
  String get heroBody =>
      'Discover nearby activities, join people with the same interests, and turn a free evening into a great memory.';

  @override
  String get heroActivityPh => 'Football, PlayStation, running...';

  @override
  String get findEvents => 'Find events';

  @override
  String get trustIdentity => 'Identity-based community';

  @override
  String get trustPeople => 'Real people nearby';

  @override
  String get trustReport => 'Easy reporting';

  @override
  String get nearbyTitle => 'Happening near you';

  @override
  String get nearbySubGeneric => 'Events near you';

  @override
  String nearbySub(String city, int km) {
    return 'Events matching $city · within $km km';
  }

  @override
  String get seeAll => 'See all';

  @override
  String get createEvent => 'Create event';

  @override
  String get viewEvent => 'View Event';

  @override
  String get members => 'members';

  @override
  String get free => 'Free';

  @override
  String get currencyShort => 'EGP';

  @override
  String costPerPersonAmount(String amount) {
    return '$amount EGP / person';
  }

  @override
  String nowPlayingTitle(String brand) {
    return 'Live on $brand';
  }

  @override
  String get nowPlayingLive => 'Happening now';

  @override
  String get nowPlayingIdle => 'Nothing live right now — up next below.';

  @override
  String get nowPlayingUpNext => 'Up next';

  @override
  String get nowPlayingPrevious => 'Just wrapped';

  @override
  String get eventsLabel => 'Events';

  @override
  String get almostFull => 'Almost full';

  @override
  String get fillingFast => 'Filling fast';

  @override
  String get newBadge => 'New';

  @override
  String get popular => 'Popular';

  @override
  String get emptyNearby => 'No nearby events yet. Be the first to create one.';

  @override
  String get retry => 'Retry';

  @override
  String get loading => 'Loading…';

  @override
  String get comingSoon => 'Coming soon';

  @override
  String get search => 'Search';

  @override
  String get searchPlaceholder => 'Search events, activities, locations...';

  @override
  String get allCities => 'All cities';

  @override
  String get allCategories => 'All categories';

  @override
  String get applyFilters => 'Apply';

  @override
  String get clearFilters => 'Clear';

  @override
  String get nationwideNotice =>
      'Showing events nationwide. Choose a city to discover nearby.';

  @override
  String get emptyDiscover => 'No events match your filters.';

  @override
  String get showAllEvents => 'Show all events';

  @override
  String get changeLocation => 'Change location';

  @override
  String get discoveryDistance => 'Distance';

  @override
  String get discoverTitle => 'Discover';

  @override
  String get discoverSub => 'Browse meetups nearby or nationwide';

  @override
  String get errorGeneric => 'Something went wrong. Please try again.';

  @override
  String get errorOffline => 'You\'re offline. Check your connection.';

  @override
  String get errorUnauthorized => 'Please log in to continue.';

  @override
  String get validationRequired => 'This field is required';

  @override
  String get validationEmail => 'Enter a valid email';

  @override
  String get join => 'Join';

  @override
  String get leave => 'Leave';

  @override
  String get publish => 'Publish';

  @override
  String get chat => 'Chat';

  @override
  String get location => 'Location';

  @override
  String get identity => 'Identity';

  @override
  String get blocks => 'Blocked users';

  @override
  String get report => 'Report';

  @override
  String get rateEvent => 'Rate event';

  @override
  String get save => 'Save';

  @override
  String get send => 'Send';

  @override
  String get editEvent => 'Edit event';

  @override
  String get eventTitle => 'Title';

  @override
  String get description => 'Description';

  @override
  String get category => 'Category';

  @override
  String get city => 'City';

  @override
  String get venue => 'Venue';

  @override
  String get venueTba => 'Venue TBA';

  @override
  String get locationTba => 'Location TBA';

  @override
  String get placeOnMap => 'Place on map';

  @override
  String get placeOnMapHelp =>
      'Paste a Google Maps or OpenStreetMap share link. No Google API key needed — the app reads coordinates from the URL.';

  @override
  String get placeTapHint => 'Tap the map to drop or move the pin.';

  @override
  String get mapsUrl => 'Maps link';

  @override
  String get mapsUrlHint => 'https://maps.app.goo.gl/…';

  @override
  String get applyMapsLink => 'Apply link';

  @override
  String get placeClear => 'Clear pin';

  @override
  String get placePinned => 'Pin saved — guests can open this place in maps.';

  @override
  String get placeFromLink => 'Location read from your maps link.';

  @override
  String get placeCleared => 'Map pin cleared.';

  @override
  String get placeResolving => 'Reading location from link…';

  @override
  String get mapsUrlUnresolved =>
      'Could not read a location from that link. Paste a full Google Maps or OpenStreetMap URL.';

  @override
  String get openInMaps => 'Open in maps';

  @override
  String get startsAt => 'Starts';

  @override
  String get endsAt => 'Ends';

  @override
  String get maxParticipants => 'Max participants';

  @override
  String get requirements => 'Requirements';

  @override
  String get expectedCost => 'Expected cost';

  @override
  String get costPerPerson => 'Cost per person';

  @override
  String get pickImage => 'Pick image';

  @override
  String get imageSelected => 'Image selected';

  @override
  String get createDraft => 'Save draft';

  @override
  String get publishEvent => 'Publish event';

  @override
  String get cancelEvent => 'Cancel event';

  @override
  String get completeEvent => 'Mark complete';

  @override
  String get viewRequests => 'Join requests';

  @override
  String get viewMembers => 'Members';

  @override
  String get joinPending => 'Request pending';

  @override
  String get cancelJoin => 'Cancel join';

  @override
  String get confirmCancelJoin =>
      'Are you sure you want to cancel your join request?';

  @override
  String get confirmRejectRequest =>
      'Are you sure you want to reject this join request?';

  @override
  String get stop => 'Stop';

  @override
  String get confirmTitle => 'Just checking';

  @override
  String get confirmYes => 'Yes, continue';

  @override
  String get eventFull => 'Event full';

  @override
  String get requestRejected => 'Request rejected';

  @override
  String get requestPending => 'Request Pending';

  @override
  String get joined => 'Joined';

  @override
  String get notAvailable => 'Not available';

  @override
  String get verifyIdentity => 'Verify your identity to participate';

  @override
  String get uploadDocuments => 'Upload documents';

  @override
  String get idFront => 'ID front';

  @override
  String get idBack => 'ID back';

  @override
  String get selfie => 'Selfie';

  @override
  String get unblock => 'Unblock';

  @override
  String get reportReason => 'Reason';

  @override
  String get reportDetails => 'Details (optional)';

  @override
  String get submitReport => 'Submit report';

  @override
  String get reportSubmitted => 'Report submitted. Thank you.';

  @override
  String get safetyGuidelines => 'Safety guidelines';

  @override
  String get safetyGuidelinesBody =>
      'Meet in public places, trust your instincts, and report anything suspicious. Blocking hides users from your feed and chat.';

  @override
  String get distanceKm => 'Search radius';

  @override
  String get selectCity => 'Select city';

  @override
  String get locationSub =>
      'Choose your city and how far you\'re willing to travel for events.';

  @override
  String get locationSaved => 'Location saved';

  @override
  String withinKm(int km) {
    return 'Within $km km';
  }

  @override
  String showingWithin(int km) {
    return 'Showing events within $km km';
  }

  @override
  String get tapToChangeLocation => 'Tap to change city & distance';

  @override
  String get setYourLocation => 'Set your location';

  @override
  String get messageHint => 'Type a message…';

  @override
  String get attachSoon => 'Attachments coming soon.';

  @override
  String get chatChip => 'Chat';

  @override
  String get noMessages => 'No messages yet';

  @override
  String get noRequests => 'No pending requests';

  @override
  String get noBlocks => 'You haven\'t blocked anyone';

  @override
  String get emptyMyEvents => 'You haven\'t created or joined any events yet.';

  @override
  String get comment => 'Comment';

  @override
  String get thankYouRating => 'Thanks for your rating!';

  @override
  String get confirmLeave => 'Are you sure you want to leave this event?';

  @override
  String get confirmCancel => 'Are you sure you want to cancel this event?';

  @override
  String get statusDraft => 'Draft';

  @override
  String get statusPublished => 'Published';

  @override
  String get statusCancelled => 'Cancelled';

  @override
  String get statusCompleted => 'Completed';

  @override
  String get notActiveAccount =>
      'Verify your identity before creating or joining events.';

  @override
  String get invalidRoute => 'This link is invalid.';

  @override
  String get newChatMessage => 'You have a new chat message.';

  @override
  String get chatToastNew => 'New message';

  @override
  String get chatToastJustNow => 'Just now';

  @override
  String get uploadTooLarge => 'Image is too large (max 8 MB).';

  @override
  String get uploadBadType => 'Use a JPG, PNG, or WEBP image.';

  @override
  String get uploadMissing => 'Could not read that image. Try another file.';

  @override
  String get failedToLoadEvents => 'Failed to load events.';

  @override
  String get invalidCode => 'Invalid code';

  @override
  String get accept => 'Accept';

  @override
  String get reject => 'Reject';

  @override
  String get reportSpam => 'Spam';

  @override
  String get reportHarassment => 'Harassment';

  @override
  String get reportInappropriate => 'Inappropriate content';

  @override
  String get reportScam => 'Scam';

  @override
  String get reportOther => 'Other';

  @override
  String get leaveEvent => 'Leave event';

  @override
  String organizerRating(String average, int count) {
    return '$average ★ ($count ratings)';
  }

  @override
  String get eventSaved => 'Event saved';

  @override
  String get eventPublished => 'Event published';

  @override
  String get optional => 'Optional';

  @override
  String get pickDateTime => 'Pick date & time';

  @override
  String publishOfferBody(String title) {
    return 'Publish \"$title\" now so others can discover it?';
  }

  @override
  String get later => 'Later';

  @override
  String get cancel => 'Cancel';

  @override
  String get back => 'Back';

  @override
  String get identitySub =>
      'Upload clear photos of your ID (front and back) and a selfie for verification.';

  @override
  String get identityMissing => 'Please select all three photos';

  @override
  String get identityUploaded => 'Documents uploaded successfully';

  @override
  String get identityTheaterEnterTitle => 'Secure upload';

  @override
  String get identityTheaterEnterSub =>
      'Encrypting and sending your identity documents.';

  @override
  String get identityTheaterVerifyingTitle => 'Verifying...';

  @override
  String get identityTheaterVerifyingSub => 'Checking your documents securely.';

  @override
  String get identityTheaterGrantedTitle => 'Documents received';

  @override
  String get identityTheaterGrantedSub =>
      'Your account is active. Taking you home…';

  @override
  String get searchSuggestions => 'Suggestions';

  @override
  String get seeAllResults => 'See all results';

  @override
  String get attachmentHint => 'Click the folder to choose a file';

  @override
  String get attachmentAdd => 'Add file';

  @override
  String get attachmentFiles => 'Files';

  @override
  String get attachmentClear => 'Remove';

  @override
  String get selected => 'Selected';

  @override
  String get editProfile => 'Edit profile';

  @override
  String get updateProfile => 'Update profile';

  @override
  String get currentPassword => 'Current password';

  @override
  String get removePhoto => 'Remove current photo';

  @override
  String get resubmitRequired => 'Please resubmit your identity documents';

  @override
  String get identityPending => 'Pending upload';

  @override
  String get identitySubmitted => 'Documents submitted';

  @override
  String get intoTitle => 'What are you into?';

  @override
  String get intoSub => 'Pick an activity and discover people around you.';

  @override
  String get viewAllCategories => 'View all categories →';

  @override
  String get startingSoon => 'Starting soon';

  @override
  String get safetyTitle => 'Your safety matters.';

  @override
  String get safetyBody =>
      'Identity documents, reporting tools, moderation and account controls are built into the community experience.';

  @override
  String get safetyCta => 'Learn about safety';

  @override
  String howTitle(String brand) {
    return 'How $brand works';
  }

  @override
  String get howSub => 'From \"I want to play\" to actually playing.';

  @override
  String get how1Title => 'Find something';

  @override
  String get how1Body =>
      'Choose an activity and see events around your city and preferred distance.';

  @override
  String get how2Title => 'Request to join';

  @override
  String get how2Body =>
      'Send a join request. The event owner reviews and accepts members.';

  @override
  String get how3Title => 'Meet & enjoy';

  @override
  String get how3Body =>
      'Use the event chat to coordinate, then meet at the agreed venue.';

  @override
  String get ctaTitle => 'Can\'t find your activity?';

  @override
  String get ctaBody =>
      'Create an event and let people around you join the plan.';

  @override
  String get ctaButton => 'Create your event';

  @override
  String get today => 'Today';

  @override
  String get tomorrow => 'Tomorrow';

  @override
  String get weekend => 'This weekend';

  @override
  String get whenAny => 'Any time';

  @override
  String get whenFilter => 'When';

  @override
  String get promote => 'Promote to admin';

  @override
  String get demote => 'Demote to member';

  @override
  String get transferOwnership => 'Transfer ownership';

  @override
  String confirmTransfer(String name) {
    return 'Transfer ownership to $name?';
  }

  @override
  String get blockUser => 'Block user';

  @override
  String confirmBlock(String name) {
    return 'Block $name? They will be hidden from your feed and chats.';
  }

  @override
  String get userBlocked => 'User blocked';

  @override
  String get roleOwner => 'Owner';

  @override
  String get roleAdmin => 'Admin';

  @override
  String get roleMember => 'Member';

  @override
  String get emptyMembers => 'No members yet';

  @override
  String yourRating(int stars) {
    return 'Your rating: $stars/5';
  }

  @override
  String get recentRatings => 'Recent ratings';

  @override
  String get noRatingsYet => 'No ratings yet';

  @override
  String get reportMessage => 'Report message';

  @override
  String get blockCreator => 'Block organizer';

  @override
  String get guidelinesIntro =>
      'How we keep community events clearer and safer to join.';

  @override
  String get guidelineIdentityTitle => 'Identity documents';

  @override
  String get guidelineIdentityBody =>
      'Identity documents are required to help maintain a safer community.';

  @override
  String get guidelineReportTitle => 'Report when something is wrong';

  @override
  String get guidelineReportBody =>
      'You can report users, events, or messages. Moderators review reports and can take action.';

  @override
  String get guidelineBlockTitle => 'Block users';

  @override
  String get guidelineBlockBody =>
      'Blocking hides that person’s events from your discover feed and stops join/chat interactions between you.';

  @override
  String get guidelinesDisclaimer =>
      'These tools help maintain a safer community. They do not guarantee that every interaction is risk-free.';

  @override
  String get blockedUsers => 'Blocked users';

  @override
  String confirmUnblock(String name) {
    return 'Unblock $name?';
  }

  @override
  String get userUnblocked => 'User unblocked';

  @override
  String get accountDeactivatedTitle => 'Account deactivated';

  @override
  String get accountDeactivatedBody =>
      'Your account has been deactivated by an administrator. Contact support if you believe this is a mistake.';

  @override
  String get reportTitle => 'Submit a report';

  @override
  String get reportType => 'What are you reporting?';

  @override
  String get reportTypeUser => 'User';

  @override
  String get reportTypeEvent => 'Event';

  @override
  String get reportTypeMessage => 'Message';

  @override
  String get reportableId => 'ID';

  @override
  String get browseAsGuest => 'Browse events';

  @override
  String get continueWithGoogle => 'Continue with Google';

  @override
  String get loginRequired => 'Please log in to continue';

  @override
  String get googleSignInFailed =>
      'Google sign-in failed. Check that Google is configured for this app.';

  @override
  String get loadMore => 'Load more';

  @override
  String get verifyEmail => 'Verify email';

  @override
  String get verifyEmailHint => 'Enter the 4-digit code we sent to your email.';

  @override
  String get resendCode => 'Resend code';

  @override
  String get forgotPassword => 'Forgot password?';

  @override
  String get forgotPasswordSub =>
      'Enter your email and we’ll send a 4-digit code.';

  @override
  String get verifyResetCodeSub => 'Check your email for the 4-digit code.';

  @override
  String get accessFormSub => 'Sign in to continue to your community.';

  @override
  String get accessEnterTitle => 'Enter Access Code';

  @override
  String get accessEnterSub => 'A 4-digit code has been sent to your device.';

  @override
  String get accessLoginEnterSub => 'Using your password digits to unlock.';

  @override
  String get accessVerifyingTitle => 'Verifying...';

  @override
  String get accessVerifyingSub => 'Securely checking your credentials.';

  @override
  String get accessGrantedTitle => 'Access Granted';

  @override
  String get accessGrantedSub => 'Welcome back. Taking you in…';

  @override
  String get accessRegisteredTitle => 'Account created';

  @override
  String get accessRegisteredSub =>
      'Next: verify your email with the code we sent.';

  @override
  String get sendResetCode => 'Send reset code';

  @override
  String get verifyResetCode => 'Verify reset code';

  @override
  String get resetPassword => 'Reset password';

  @override
  String get newPassword => 'New password';

  @override
  String get enterCode => 'Enter code';

  @override
  String get codeSent => 'Code sent';

  @override
  String get favorites => 'Favorites';

  @override
  String get favorite => 'Favorite';

  @override
  String get unfavorite => 'Remove favorite';

  @override
  String get emptyFavorites =>
      'No favorites yet. Bookmark events from Discover.';

  @override
  String get viewTicket => 'View ticket';

  @override
  String get eventTicket => 'Event ticket';

  @override
  String get ticketWord => 'TICKET';

  @override
  String get ticketHelp =>
      'Show this pass at the venue. Your code is unique to you for this event.';

  @override
  String get ticketRoleOwner => 'Host';

  @override
  String get ticketRoleAdmin => 'Admin';

  @override
  String get ticketRoleMember => 'Pass';

  @override
  String get yourParticipations => 'Your participations';

  @override
  String get manageAll => 'Manage all';

  @override
  String get participationsEmpty =>
      'No upcoming participations. Discover something fun.';

  @override
  String get confirmedAttendance => 'Confirmed';

  @override
  String get requestPendingShort => 'Pending';

  @override
  String get pendingReviewNotice =>
      'Waiting for the host to accept your request.';

  @override
  String get pendingRequestsSection => 'Waiting for acceptance';

  @override
  String get joinedEventsSection => 'Events you joined';

  @override
  String get weatherSetLocation => 'Set your location';

  @override
  String get weatherSetLocationHint =>
      'Choose a city to see local weather on your home.';

  @override
  String get requestToJoin => 'Request to join';

  @override
  String get updateAvailable => 'Update available';

  @override
  String get updateAvailableBody => 'A new version of the app is available.';

  @override
  String get updateNow => 'Update';

  @override
  String get updateLater => 'Later';

  @override
  String get searching => 'Searching';
}
