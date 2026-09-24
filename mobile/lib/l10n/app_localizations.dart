import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
  ];

  /// No description provided for @appName.
  ///
  /// In en, this message translates to:
  /// **'Fun'**
  String get appName;

  /// No description provided for @login.
  ///
  /// In en, this message translates to:
  /// **'Log in'**
  String get login;

  /// No description provided for @register.
  ///
  /// In en, this message translates to:
  /// **'Register'**
  String get register;

  /// No description provided for @logout.
  ///
  /// In en, this message translates to:
  /// **'Log out'**
  String get logout;

  /// No description provided for @email.
  ///
  /// In en, this message translates to:
  /// **'Email'**
  String get email;

  /// No description provided for @password.
  ///
  /// In en, this message translates to:
  /// **'Password'**
  String get password;

  /// No description provided for @passwordConfirmation.
  ///
  /// In en, this message translates to:
  /// **'Confirm password'**
  String get passwordConfirmation;

  /// No description provided for @name.
  ///
  /// In en, this message translates to:
  /// **'Name'**
  String get name;

  /// No description provided for @remember.
  ///
  /// In en, this message translates to:
  /// **'Remember me'**
  String get remember;

  /// No description provided for @language.
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get language;

  /// No description provided for @noAccount.
  ///
  /// In en, this message translates to:
  /// **'Don\'t have an account?'**
  String get noAccount;

  /// No description provided for @haveAccount.
  ///
  /// In en, this message translates to:
  /// **'Already have an account?'**
  String get haveAccount;

  /// No description provided for @navHome.
  ///
  /// In en, this message translates to:
  /// **'Home'**
  String get navHome;

  /// No description provided for @navDiscover.
  ///
  /// In en, this message translates to:
  /// **'Discover'**
  String get navDiscover;

  /// No description provided for @navCreate.
  ///
  /// In en, this message translates to:
  /// **'Create'**
  String get navCreate;

  /// No description provided for @navMyEvents.
  ///
  /// In en, this message translates to:
  /// **'My Events'**
  String get navMyEvents;

  /// No description provided for @navProfile.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get navProfile;

  /// No description provided for @navExplore.
  ///
  /// In en, this message translates to:
  /// **'Explore'**
  String get navExplore;

  /// No description provided for @publicProfile.
  ///
  /// In en, this message translates to:
  /// **'Public profile'**
  String get publicProfile;

  /// No description provided for @yourPublicId.
  ///
  /// In en, this message translates to:
  /// **'Your public ID'**
  String get yourPublicId;

  /// No description provided for @openPublicProfile.
  ///
  /// In en, this message translates to:
  /// **'Open public profile'**
  String get openPublicProfile;

  /// No description provided for @chatsTitle.
  ///
  /// In en, this message translates to:
  /// **'Chats'**
  String get chatsTitle;

  /// No description provided for @chatsEmptyTitle.
  ///
  /// In en, this message translates to:
  /// **'No chats yet'**
  String get chatsEmptyTitle;

  /// No description provided for @chatsEmptyBody.
  ///
  /// In en, this message translates to:
  /// **'Join an event to start chatting with other members.'**
  String get chatsEmptyBody;

  /// No description provided for @chatsNoMessages.
  ///
  /// In en, this message translates to:
  /// **'No messages yet'**
  String get chatsNoMessages;

  /// No description provided for @chatsYou.
  ///
  /// In en, this message translates to:
  /// **'You'**
  String get chatsYou;

  /// No description provided for @openChat.
  ///
  /// In en, this message translates to:
  /// **'Open chat'**
  String get openChat;

  /// No description provided for @hostedEvents.
  ///
  /// In en, this message translates to:
  /// **'Hosted events'**
  String get hostedEvents;

  /// No description provided for @statHosted.
  ///
  /// In en, this message translates to:
  /// **'Hosted'**
  String get statHosted;

  /// No description provided for @statJoined.
  ///
  /// In en, this message translates to:
  /// **'Joined'**
  String get statJoined;

  /// No description provided for @statRating.
  ///
  /// In en, this message translates to:
  /// **'Rating'**
  String get statRating;

  /// No description provided for @profileNotFound.
  ///
  /// In en, this message translates to:
  /// **'Profile not found'**
  String get profileNotFound;

  /// No description provided for @greetingMorning.
  ///
  /// In en, this message translates to:
  /// **'Good morning, {name}'**
  String greetingMorning(String name);

  /// No description provided for @greetingAfternoon.
  ///
  /// In en, this message translates to:
  /// **'Good afternoon, {name}'**
  String greetingAfternoon(String name);

  /// No description provided for @greetingEvening.
  ///
  /// In en, this message translates to:
  /// **'Good evening, {name}'**
  String greetingEvening(String name);

  /// No description provided for @greetingSub.
  ///
  /// In en, this message translates to:
  /// **'Ready to find something fun to do today?'**
  String get greetingSub;

  /// No description provided for @heroEyebrow.
  ///
  /// In en, this message translates to:
  /// **'Activities happening around you'**
  String get heroEyebrow;

  /// No description provided for @heroTitle1.
  ///
  /// In en, this message translates to:
  /// **'Find people.'**
  String get heroTitle1;

  /// No description provided for @heroTitle2.
  ///
  /// In en, this message translates to:
  /// **'Do what you love.'**
  String get heroTitle2;

  /// No description provided for @heroBody.
  ///
  /// In en, this message translates to:
  /// **'Discover nearby activities, join people with the same interests, and turn a free evening into a great memory.'**
  String get heroBody;

  /// No description provided for @heroActivityPh.
  ///
  /// In en, this message translates to:
  /// **'Football, PlayStation, running...'**
  String get heroActivityPh;

  /// No description provided for @findEvents.
  ///
  /// In en, this message translates to:
  /// **'Find events'**
  String get findEvents;

  /// No description provided for @trustIdentity.
  ///
  /// In en, this message translates to:
  /// **'Identity-based community'**
  String get trustIdentity;

  /// No description provided for @trustPeople.
  ///
  /// In en, this message translates to:
  /// **'Real people nearby'**
  String get trustPeople;

  /// No description provided for @trustReport.
  ///
  /// In en, this message translates to:
  /// **'Easy reporting'**
  String get trustReport;

  /// No description provided for @nearbyTitle.
  ///
  /// In en, this message translates to:
  /// **'Happening near you'**
  String get nearbyTitle;

  /// No description provided for @nearbySubGeneric.
  ///
  /// In en, this message translates to:
  /// **'Events near you'**
  String get nearbySubGeneric;

  /// No description provided for @nearbySub.
  ///
  /// In en, this message translates to:
  /// **'Events matching {city} · within {km} km'**
  String nearbySub(String city, int km);

  /// No description provided for @seeAll.
  ///
  /// In en, this message translates to:
  /// **'See all'**
  String get seeAll;

  /// No description provided for @createEvent.
  ///
  /// In en, this message translates to:
  /// **'Create event'**
  String get createEvent;

  /// No description provided for @viewEvent.
  ///
  /// In en, this message translates to:
  /// **'View Event'**
  String get viewEvent;

  /// No description provided for @members.
  ///
  /// In en, this message translates to:
  /// **'members'**
  String get members;

  /// No description provided for @free.
  ///
  /// In en, this message translates to:
  /// **'Free'**
  String get free;

  /// No description provided for @currencyShort.
  ///
  /// In en, this message translates to:
  /// **'EGP'**
  String get currencyShort;

  /// No description provided for @costPerPersonAmount.
  ///
  /// In en, this message translates to:
  /// **'{amount} EGP / person'**
  String costPerPersonAmount(String amount);

  /// No description provided for @nowPlayingTitle.
  ///
  /// In en, this message translates to:
  /// **'Live on {brand}'**
  String nowPlayingTitle(String brand);

  /// No description provided for @nowPlayingLive.
  ///
  /// In en, this message translates to:
  /// **'Happening now'**
  String get nowPlayingLive;

  /// No description provided for @nowPlayingIdle.
  ///
  /// In en, this message translates to:
  /// **'Nothing live right now — up next below.'**
  String get nowPlayingIdle;

  /// No description provided for @nowPlayingUpNext.
  ///
  /// In en, this message translates to:
  /// **'Up next'**
  String get nowPlayingUpNext;

  /// No description provided for @nowPlayingPrevious.
  ///
  /// In en, this message translates to:
  /// **'Just wrapped'**
  String get nowPlayingPrevious;

  /// No description provided for @eventsLabel.
  ///
  /// In en, this message translates to:
  /// **'Events'**
  String get eventsLabel;

  /// No description provided for @almostFull.
  ///
  /// In en, this message translates to:
  /// **'Almost full'**
  String get almostFull;

  /// No description provided for @fillingFast.
  ///
  /// In en, this message translates to:
  /// **'Filling fast'**
  String get fillingFast;

  /// No description provided for @newBadge.
  ///
  /// In en, this message translates to:
  /// **'New'**
  String get newBadge;

  /// No description provided for @popular.
  ///
  /// In en, this message translates to:
  /// **'Popular'**
  String get popular;

  /// No description provided for @emptyNearby.
  ///
  /// In en, this message translates to:
  /// **'No nearby events yet. Be the first to create one.'**
  String get emptyNearby;

  /// No description provided for @retry.
  ///
  /// In en, this message translates to:
  /// **'Retry'**
  String get retry;

  /// No description provided for @loading.
  ///
  /// In en, this message translates to:
  /// **'Loading…'**
  String get loading;

  /// No description provided for @comingSoon.
  ///
  /// In en, this message translates to:
  /// **'Coming soon'**
  String get comingSoon;

  /// No description provided for @search.
  ///
  /// In en, this message translates to:
  /// **'Search'**
  String get search;

  /// No description provided for @searchPlaceholder.
  ///
  /// In en, this message translates to:
  /// **'Search events, activities, locations...'**
  String get searchPlaceholder;

  /// No description provided for @allCities.
  ///
  /// In en, this message translates to:
  /// **'All cities'**
  String get allCities;

  /// No description provided for @allCategories.
  ///
  /// In en, this message translates to:
  /// **'All categories'**
  String get allCategories;

  /// No description provided for @applyFilters.
  ///
  /// In en, this message translates to:
  /// **'Apply'**
  String get applyFilters;

  /// No description provided for @clearFilters.
  ///
  /// In en, this message translates to:
  /// **'Clear'**
  String get clearFilters;

  /// No description provided for @nationwideNotice.
  ///
  /// In en, this message translates to:
  /// **'Showing events nationwide. Choose a city to discover nearby.'**
  String get nationwideNotice;

  /// No description provided for @emptyDiscover.
  ///
  /// In en, this message translates to:
  /// **'No events match your filters.'**
  String get emptyDiscover;

  /// No description provided for @showAllEvents.
  ///
  /// In en, this message translates to:
  /// **'Show all events'**
  String get showAllEvents;

  /// No description provided for @changeLocation.
  ///
  /// In en, this message translates to:
  /// **'Change location'**
  String get changeLocation;

  /// No description provided for @discoveryDistance.
  ///
  /// In en, this message translates to:
  /// **'Distance'**
  String get discoveryDistance;

  /// No description provided for @discoverTitle.
  ///
  /// In en, this message translates to:
  /// **'Discover'**
  String get discoverTitle;

  /// No description provided for @discoverSub.
  ///
  /// In en, this message translates to:
  /// **'Browse meetups nearby or nationwide'**
  String get discoverSub;

  /// No description provided for @errorGeneric.
  ///
  /// In en, this message translates to:
  /// **'Something went wrong. Please try again.'**
  String get errorGeneric;

  /// No description provided for @errorOffline.
  ///
  /// In en, this message translates to:
  /// **'You\'re offline. Check your connection.'**
  String get errorOffline;

  /// No description provided for @errorUnauthorized.
  ///
  /// In en, this message translates to:
  /// **'Please log in to continue.'**
  String get errorUnauthorized;

  /// No description provided for @validationRequired.
  ///
  /// In en, this message translates to:
  /// **'This field is required'**
  String get validationRequired;

  /// No description provided for @validationEmail.
  ///
  /// In en, this message translates to:
  /// **'Enter a valid email'**
  String get validationEmail;

  /// No description provided for @join.
  ///
  /// In en, this message translates to:
  /// **'Join'**
  String get join;

  /// No description provided for @leave.
  ///
  /// In en, this message translates to:
  /// **'Leave'**
  String get leave;

  /// No description provided for @publish.
  ///
  /// In en, this message translates to:
  /// **'Publish'**
  String get publish;

  /// No description provided for @chat.
  ///
  /// In en, this message translates to:
  /// **'Chat'**
  String get chat;

  /// No description provided for @location.
  ///
  /// In en, this message translates to:
  /// **'Location'**
  String get location;

  /// No description provided for @identity.
  ///
  /// In en, this message translates to:
  /// **'Identity'**
  String get identity;

  /// No description provided for @blocks.
  ///
  /// In en, this message translates to:
  /// **'Blocked users'**
  String get blocks;

  /// No description provided for @report.
  ///
  /// In en, this message translates to:
  /// **'Report'**
  String get report;

  /// No description provided for @rateEvent.
  ///
  /// In en, this message translates to:
  /// **'Rate event'**
  String get rateEvent;

  /// No description provided for @save.
  ///
  /// In en, this message translates to:
  /// **'Save'**
  String get save;

  /// No description provided for @send.
  ///
  /// In en, this message translates to:
  /// **'Send'**
  String get send;

  /// No description provided for @editEvent.
  ///
  /// In en, this message translates to:
  /// **'Edit event'**
  String get editEvent;

  /// No description provided for @eventTitle.
  ///
  /// In en, this message translates to:
  /// **'Title'**
  String get eventTitle;

  /// No description provided for @description.
  ///
  /// In en, this message translates to:
  /// **'Description'**
  String get description;

  /// No description provided for @category.
  ///
  /// In en, this message translates to:
  /// **'Category'**
  String get category;

  /// No description provided for @city.
  ///
  /// In en, this message translates to:
  /// **'City'**
  String get city;

  /// No description provided for @venue.
  ///
  /// In en, this message translates to:
  /// **'Venue'**
  String get venue;

  /// No description provided for @venueTba.
  ///
  /// In en, this message translates to:
  /// **'Venue TBA'**
  String get venueTba;

  /// No description provided for @locationTba.
  ///
  /// In en, this message translates to:
  /// **'Location TBA'**
  String get locationTba;

  /// No description provided for @placeOnMap.
  ///
  /// In en, this message translates to:
  /// **'Place on map'**
  String get placeOnMap;

  /// No description provided for @placeOnMapHelp.
  ///
  /// In en, this message translates to:
  /// **'Paste a Google Maps or OpenStreetMap share link. No Google API key needed — the app reads coordinates from the URL.'**
  String get placeOnMapHelp;

  /// No description provided for @placeTapHint.
  ///
  /// In en, this message translates to:
  /// **'Tap the map to drop or move the pin.'**
  String get placeTapHint;

  /// No description provided for @mapsUrl.
  ///
  /// In en, this message translates to:
  /// **'Maps link'**
  String get mapsUrl;

  /// No description provided for @mapsUrlHint.
  ///
  /// In en, this message translates to:
  /// **'https://maps.app.goo.gl/…'**
  String get mapsUrlHint;

  /// No description provided for @applyMapsLink.
  ///
  /// In en, this message translates to:
  /// **'Apply link'**
  String get applyMapsLink;

  /// No description provided for @placeClear.
  ///
  /// In en, this message translates to:
  /// **'Clear pin'**
  String get placeClear;

  /// No description provided for @placePinned.
  ///
  /// In en, this message translates to:
  /// **'Pin saved — guests can open this place in maps.'**
  String get placePinned;

  /// No description provided for @placeFromLink.
  ///
  /// In en, this message translates to:
  /// **'Location read from your maps link.'**
  String get placeFromLink;

  /// No description provided for @placeCleared.
  ///
  /// In en, this message translates to:
  /// **'Map pin cleared.'**
  String get placeCleared;

  /// No description provided for @placeResolving.
  ///
  /// In en, this message translates to:
  /// **'Reading location from link…'**
  String get placeResolving;

  /// No description provided for @mapsUrlUnresolved.
  ///
  /// In en, this message translates to:
  /// **'Could not read a location from that link. Paste a full Google Maps or OpenStreetMap URL.'**
  String get mapsUrlUnresolved;

  /// No description provided for @openInMaps.
  ///
  /// In en, this message translates to:
  /// **'Open in maps'**
  String get openInMaps;

  /// No description provided for @startsAt.
  ///
  /// In en, this message translates to:
  /// **'Starts'**
  String get startsAt;

  /// No description provided for @endsAt.
  ///
  /// In en, this message translates to:
  /// **'Ends'**
  String get endsAt;

  /// No description provided for @maxParticipants.
  ///
  /// In en, this message translates to:
  /// **'Max participants'**
  String get maxParticipants;

  /// No description provided for @requirements.
  ///
  /// In en, this message translates to:
  /// **'Requirements'**
  String get requirements;

  /// No description provided for @expectedCost.
  ///
  /// In en, this message translates to:
  /// **'Expected cost'**
  String get expectedCost;

  /// No description provided for @costPerPerson.
  ///
  /// In en, this message translates to:
  /// **'Cost per person'**
  String get costPerPerson;

  /// No description provided for @pickImage.
  ///
  /// In en, this message translates to:
  /// **'Pick image'**
  String get pickImage;

  /// No description provided for @imageSelected.
  ///
  /// In en, this message translates to:
  /// **'Image selected'**
  String get imageSelected;

  /// No description provided for @createDraft.
  ///
  /// In en, this message translates to:
  /// **'Save draft'**
  String get createDraft;

  /// No description provided for @publishEvent.
  ///
  /// In en, this message translates to:
  /// **'Publish event'**
  String get publishEvent;

  /// No description provided for @cancelEvent.
  ///
  /// In en, this message translates to:
  /// **'Cancel event'**
  String get cancelEvent;

  /// No description provided for @completeEvent.
  ///
  /// In en, this message translates to:
  /// **'Mark complete'**
  String get completeEvent;

  /// No description provided for @viewRequests.
  ///
  /// In en, this message translates to:
  /// **'Join requests'**
  String get viewRequests;

  /// No description provided for @viewMembers.
  ///
  /// In en, this message translates to:
  /// **'Members'**
  String get viewMembers;

  /// No description provided for @joinPending.
  ///
  /// In en, this message translates to:
  /// **'Request pending'**
  String get joinPending;

  /// No description provided for @cancelJoin.
  ///
  /// In en, this message translates to:
  /// **'Cancel join'**
  String get cancelJoin;

  /// No description provided for @confirmCancelJoin.
  ///
  /// In en, this message translates to:
  /// **'Are you sure you want to cancel your join request?'**
  String get confirmCancelJoin;

  /// No description provided for @confirmRejectRequest.
  ///
  /// In en, this message translates to:
  /// **'Are you sure you want to reject this join request?'**
  String get confirmRejectRequest;

  /// No description provided for @stop.
  ///
  /// In en, this message translates to:
  /// **'Stop'**
  String get stop;

  /// No description provided for @confirmTitle.
  ///
  /// In en, this message translates to:
  /// **'Just checking'**
  String get confirmTitle;

  /// No description provided for @confirmYes.
  ///
  /// In en, this message translates to:
  /// **'Yes, continue'**
  String get confirmYes;

  /// No description provided for @eventFull.
  ///
  /// In en, this message translates to:
  /// **'Event full'**
  String get eventFull;

  /// No description provided for @requestRejected.
  ///
  /// In en, this message translates to:
  /// **'Request rejected'**
  String get requestRejected;

  /// No description provided for @requestPending.
  ///
  /// In en, this message translates to:
  /// **'Request Pending'**
  String get requestPending;

  /// No description provided for @joined.
  ///
  /// In en, this message translates to:
  /// **'Joined'**
  String get joined;

  /// No description provided for @notAvailable.
  ///
  /// In en, this message translates to:
  /// **'Not available'**
  String get notAvailable;

  /// No description provided for @verifyIdentity.
  ///
  /// In en, this message translates to:
  /// **'Verify your identity to participate'**
  String get verifyIdentity;

  /// No description provided for @uploadDocuments.
  ///
  /// In en, this message translates to:
  /// **'Upload documents'**
  String get uploadDocuments;

  /// No description provided for @idFront.
  ///
  /// In en, this message translates to:
  /// **'ID front'**
  String get idFront;

  /// No description provided for @idBack.
  ///
  /// In en, this message translates to:
  /// **'ID back'**
  String get idBack;

  /// No description provided for @selfie.
  ///
  /// In en, this message translates to:
  /// **'Selfie'**
  String get selfie;

  /// No description provided for @unblock.
  ///
  /// In en, this message translates to:
  /// **'Unblock'**
  String get unblock;

  /// No description provided for @reportReason.
  ///
  /// In en, this message translates to:
  /// **'Reason'**
  String get reportReason;

  /// No description provided for @reportDetails.
  ///
  /// In en, this message translates to:
  /// **'Details (optional)'**
  String get reportDetails;

  /// No description provided for @submitReport.
  ///
  /// In en, this message translates to:
  /// **'Submit report'**
  String get submitReport;

  /// No description provided for @reportSubmitted.
  ///
  /// In en, this message translates to:
  /// **'Report submitted. Thank you.'**
  String get reportSubmitted;

  /// No description provided for @safetyGuidelines.
  ///
  /// In en, this message translates to:
  /// **'Safety guidelines'**
  String get safetyGuidelines;

  /// No description provided for @safetyGuidelinesBody.
  ///
  /// In en, this message translates to:
  /// **'Meet in public places, trust your instincts, and report anything suspicious. Blocking hides users from your feed and chat.'**
  String get safetyGuidelinesBody;

  /// No description provided for @distanceKm.
  ///
  /// In en, this message translates to:
  /// **'Search radius'**
  String get distanceKm;

  /// No description provided for @selectCity.
  ///
  /// In en, this message translates to:
  /// **'Select city'**
  String get selectCity;

  /// No description provided for @locationSub.
  ///
  /// In en, this message translates to:
  /// **'Choose your city and how far you\'re willing to travel for events.'**
  String get locationSub;

  /// No description provided for @locationSaved.
  ///
  /// In en, this message translates to:
  /// **'Location saved'**
  String get locationSaved;

  /// No description provided for @withinKm.
  ///
  /// In en, this message translates to:
  /// **'Within {km} km'**
  String withinKm(int km);

  /// No description provided for @showingWithin.
  ///
  /// In en, this message translates to:
  /// **'Showing events within {km} km'**
  String showingWithin(int km);

  /// No description provided for @tapToChangeLocation.
  ///
  /// In en, this message translates to:
  /// **'Tap to change city & distance'**
  String get tapToChangeLocation;

  /// No description provided for @setYourLocation.
  ///
  /// In en, this message translates to:
  /// **'Set your location'**
  String get setYourLocation;

  /// No description provided for @messageHint.
  ///
  /// In en, this message translates to:
  /// **'Type a message…'**
  String get messageHint;

  /// No description provided for @attachSoon.
  ///
  /// In en, this message translates to:
  /// **'Attachments coming soon.'**
  String get attachSoon;

  /// No description provided for @chatChip.
  ///
  /// In en, this message translates to:
  /// **'Chat'**
  String get chatChip;

  /// No description provided for @noMessages.
  ///
  /// In en, this message translates to:
  /// **'No messages yet'**
  String get noMessages;

  /// No description provided for @noRequests.
  ///
  /// In en, this message translates to:
  /// **'No pending requests'**
  String get noRequests;

  /// No description provided for @noBlocks.
  ///
  /// In en, this message translates to:
  /// **'You haven\'t blocked anyone'**
  String get noBlocks;

  /// No description provided for @emptyMyEvents.
  ///
  /// In en, this message translates to:
  /// **'You haven\'t created or joined any events yet.'**
  String get emptyMyEvents;

  /// No description provided for @comment.
  ///
  /// In en, this message translates to:
  /// **'Comment'**
  String get comment;

  /// No description provided for @thankYouRating.
  ///
  /// In en, this message translates to:
  /// **'Thanks for your rating!'**
  String get thankYouRating;

  /// No description provided for @confirmLeave.
  ///
  /// In en, this message translates to:
  /// **'Are you sure you want to leave this event?'**
  String get confirmLeave;

  /// No description provided for @confirmCancel.
  ///
  /// In en, this message translates to:
  /// **'Are you sure you want to cancel this event?'**
  String get confirmCancel;

  /// No description provided for @statusDraft.
  ///
  /// In en, this message translates to:
  /// **'Draft'**
  String get statusDraft;

  /// No description provided for @statusPublished.
  ///
  /// In en, this message translates to:
  /// **'Published'**
  String get statusPublished;

  /// No description provided for @statusCancelled.
  ///
  /// In en, this message translates to:
  /// **'Cancelled'**
  String get statusCancelled;

  /// No description provided for @statusCompleted.
  ///
  /// In en, this message translates to:
  /// **'Completed'**
  String get statusCompleted;

  /// No description provided for @notActiveAccount.
  ///
  /// In en, this message translates to:
  /// **'Verify your identity before creating or joining events.'**
  String get notActiveAccount;

  /// No description provided for @invalidRoute.
  ///
  /// In en, this message translates to:
  /// **'This link is invalid.'**
  String get invalidRoute;

  /// No description provided for @newChatMessage.
  ///
  /// In en, this message translates to:
  /// **'You have a new chat message.'**
  String get newChatMessage;

  /// No description provided for @chatToastNew.
  ///
  /// In en, this message translates to:
  /// **'New message'**
  String get chatToastNew;

  /// No description provided for @chatToastJustNow.
  ///
  /// In en, this message translates to:
  /// **'Just now'**
  String get chatToastJustNow;

  /// No description provided for @uploadTooLarge.
  ///
  /// In en, this message translates to:
  /// **'Image is too large (max 8 MB).'**
  String get uploadTooLarge;

  /// No description provided for @uploadBadType.
  ///
  /// In en, this message translates to:
  /// **'Use a JPG, PNG, or WEBP image.'**
  String get uploadBadType;

  /// No description provided for @uploadMissing.
  ///
  /// In en, this message translates to:
  /// **'Could not read that image. Try another file.'**
  String get uploadMissing;

  /// No description provided for @failedToLoadEvents.
  ///
  /// In en, this message translates to:
  /// **'Failed to load events.'**
  String get failedToLoadEvents;

  /// No description provided for @invalidCode.
  ///
  /// In en, this message translates to:
  /// **'Invalid code'**
  String get invalidCode;

  /// No description provided for @accept.
  ///
  /// In en, this message translates to:
  /// **'Accept'**
  String get accept;

  /// No description provided for @reject.
  ///
  /// In en, this message translates to:
  /// **'Reject'**
  String get reject;

  /// No description provided for @reportSpam.
  ///
  /// In en, this message translates to:
  /// **'Spam'**
  String get reportSpam;

  /// No description provided for @reportHarassment.
  ///
  /// In en, this message translates to:
  /// **'Harassment'**
  String get reportHarassment;

  /// No description provided for @reportInappropriate.
  ///
  /// In en, this message translates to:
  /// **'Inappropriate content'**
  String get reportInappropriate;

  /// No description provided for @reportScam.
  ///
  /// In en, this message translates to:
  /// **'Scam'**
  String get reportScam;

  /// No description provided for @reportOther.
  ///
  /// In en, this message translates to:
  /// **'Other'**
  String get reportOther;

  /// No description provided for @leaveEvent.
  ///
  /// In en, this message translates to:
  /// **'Leave event'**
  String get leaveEvent;

  /// No description provided for @organizerRating.
  ///
  /// In en, this message translates to:
  /// **'{average} ★ ({count} ratings)'**
  String organizerRating(String average, int count);

  /// No description provided for @eventSaved.
  ///
  /// In en, this message translates to:
  /// **'Event saved'**
  String get eventSaved;

  /// No description provided for @eventPublished.
  ///
  /// In en, this message translates to:
  /// **'Event published'**
  String get eventPublished;

  /// No description provided for @optional.
  ///
  /// In en, this message translates to:
  /// **'Optional'**
  String get optional;

  /// No description provided for @pickDateTime.
  ///
  /// In en, this message translates to:
  /// **'Pick date & time'**
  String get pickDateTime;

  /// No description provided for @publishOfferBody.
  ///
  /// In en, this message translates to:
  /// **'Publish \"{title}\" now so others can discover it?'**
  String publishOfferBody(String title);

  /// No description provided for @later.
  ///
  /// In en, this message translates to:
  /// **'Later'**
  String get later;

  /// No description provided for @cancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get cancel;

  /// No description provided for @back.
  ///
  /// In en, this message translates to:
  /// **'Back'**
  String get back;

  /// No description provided for @identitySub.
  ///
  /// In en, this message translates to:
  /// **'Upload clear photos of your ID (front and back) and a selfie for verification.'**
  String get identitySub;

  /// No description provided for @identityMissing.
  ///
  /// In en, this message translates to:
  /// **'Please select all three photos'**
  String get identityMissing;

  /// No description provided for @identityUploaded.
  ///
  /// In en, this message translates to:
  /// **'Documents uploaded successfully'**
  String get identityUploaded;

  /// No description provided for @identityTheaterEnterTitle.
  ///
  /// In en, this message translates to:
  /// **'Secure upload'**
  String get identityTheaterEnterTitle;

  /// No description provided for @identityTheaterEnterSub.
  ///
  /// In en, this message translates to:
  /// **'Encrypting and sending your identity documents.'**
  String get identityTheaterEnterSub;

  /// No description provided for @identityTheaterVerifyingTitle.
  ///
  /// In en, this message translates to:
  /// **'Verifying...'**
  String get identityTheaterVerifyingTitle;

  /// No description provided for @identityTheaterVerifyingSub.
  ///
  /// In en, this message translates to:
  /// **'Checking your documents securely.'**
  String get identityTheaterVerifyingSub;

  /// No description provided for @identityTheaterGrantedTitle.
  ///
  /// In en, this message translates to:
  /// **'Documents received'**
  String get identityTheaterGrantedTitle;

  /// No description provided for @identityTheaterGrantedSub.
  ///
  /// In en, this message translates to:
  /// **'Your account is active. Taking you home…'**
  String get identityTheaterGrantedSub;

  /// No description provided for @searchSuggestions.
  ///
  /// In en, this message translates to:
  /// **'Suggestions'**
  String get searchSuggestions;

  /// No description provided for @seeAllResults.
  ///
  /// In en, this message translates to:
  /// **'See all results'**
  String get seeAllResults;

  /// No description provided for @attachmentHint.
  ///
  /// In en, this message translates to:
  /// **'Click the folder to choose a file'**
  String get attachmentHint;

  /// No description provided for @attachmentAdd.
  ///
  /// In en, this message translates to:
  /// **'Add file'**
  String get attachmentAdd;

  /// No description provided for @attachmentFiles.
  ///
  /// In en, this message translates to:
  /// **'Files'**
  String get attachmentFiles;

  /// No description provided for @attachmentClear.
  ///
  /// In en, this message translates to:
  /// **'Remove'**
  String get attachmentClear;

  /// No description provided for @selected.
  ///
  /// In en, this message translates to:
  /// **'Selected'**
  String get selected;

  /// No description provided for @editProfile.
  ///
  /// In en, this message translates to:
  /// **'Edit profile'**
  String get editProfile;

  /// No description provided for @updateProfile.
  ///
  /// In en, this message translates to:
  /// **'Update profile'**
  String get updateProfile;

  /// No description provided for @currentPassword.
  ///
  /// In en, this message translates to:
  /// **'Current password'**
  String get currentPassword;

  /// No description provided for @removePhoto.
  ///
  /// In en, this message translates to:
  /// **'Remove current photo'**
  String get removePhoto;

  /// No description provided for @resubmitRequired.
  ///
  /// In en, this message translates to:
  /// **'Please resubmit your identity documents'**
  String get resubmitRequired;

  /// No description provided for @identityPending.
  ///
  /// In en, this message translates to:
  /// **'Pending upload'**
  String get identityPending;

  /// No description provided for @identitySubmitted.
  ///
  /// In en, this message translates to:
  /// **'Documents submitted'**
  String get identitySubmitted;

  /// No description provided for @intoTitle.
  ///
  /// In en, this message translates to:
  /// **'What are you into?'**
  String get intoTitle;

  /// No description provided for @intoSub.
  ///
  /// In en, this message translates to:
  /// **'Pick an activity and discover people around you.'**
  String get intoSub;

  /// No description provided for @viewAllCategories.
  ///
  /// In en, this message translates to:
  /// **'View all categories →'**
  String get viewAllCategories;

  /// No description provided for @startingSoon.
  ///
  /// In en, this message translates to:
  /// **'Starting soon'**
  String get startingSoon;

  /// No description provided for @safetyTitle.
  ///
  /// In en, this message translates to:
  /// **'Your safety matters.'**
  String get safetyTitle;

  /// No description provided for @safetyBody.
  ///
  /// In en, this message translates to:
  /// **'Identity documents, reporting tools, moderation and account controls are built into the community experience.'**
  String get safetyBody;

  /// No description provided for @safetyCta.
  ///
  /// In en, this message translates to:
  /// **'Learn about safety'**
  String get safetyCta;

  /// No description provided for @howTitle.
  ///
  /// In en, this message translates to:
  /// **'How {brand} works'**
  String howTitle(String brand);

  /// No description provided for @howSub.
  ///
  /// In en, this message translates to:
  /// **'From \"I want to play\" to actually playing.'**
  String get howSub;

  /// No description provided for @how1Title.
  ///
  /// In en, this message translates to:
  /// **'Find something'**
  String get how1Title;

  /// No description provided for @how1Body.
  ///
  /// In en, this message translates to:
  /// **'Choose an activity and see events around your city and preferred distance.'**
  String get how1Body;

  /// No description provided for @how2Title.
  ///
  /// In en, this message translates to:
  /// **'Request to join'**
  String get how2Title;

  /// No description provided for @how2Body.
  ///
  /// In en, this message translates to:
  /// **'Send a join request. The event owner reviews and accepts members.'**
  String get how2Body;

  /// No description provided for @how3Title.
  ///
  /// In en, this message translates to:
  /// **'Meet & enjoy'**
  String get how3Title;

  /// No description provided for @how3Body.
  ///
  /// In en, this message translates to:
  /// **'Use the event chat to coordinate, then meet at the agreed venue.'**
  String get how3Body;

  /// No description provided for @ctaTitle.
  ///
  /// In en, this message translates to:
  /// **'Can\'t find your activity?'**
  String get ctaTitle;

  /// No description provided for @ctaBody.
  ///
  /// In en, this message translates to:
  /// **'Create an event and let people around you join the plan.'**
  String get ctaBody;

  /// No description provided for @ctaButton.
  ///
  /// In en, this message translates to:
  /// **'Create your event'**
  String get ctaButton;

  /// No description provided for @today.
  ///
  /// In en, this message translates to:
  /// **'Today'**
  String get today;

  /// No description provided for @tomorrow.
  ///
  /// In en, this message translates to:
  /// **'Tomorrow'**
  String get tomorrow;

  /// No description provided for @weekend.
  ///
  /// In en, this message translates to:
  /// **'This weekend'**
  String get weekend;

  /// No description provided for @whenAny.
  ///
  /// In en, this message translates to:
  /// **'Any time'**
  String get whenAny;

  /// No description provided for @whenFilter.
  ///
  /// In en, this message translates to:
  /// **'When'**
  String get whenFilter;

  /// No description provided for @promote.
  ///
  /// In en, this message translates to:
  /// **'Promote to admin'**
  String get promote;

  /// No description provided for @demote.
  ///
  /// In en, this message translates to:
  /// **'Demote to member'**
  String get demote;

  /// No description provided for @transferOwnership.
  ///
  /// In en, this message translates to:
  /// **'Transfer ownership'**
  String get transferOwnership;

  /// No description provided for @confirmTransfer.
  ///
  /// In en, this message translates to:
  /// **'Transfer ownership to {name}?'**
  String confirmTransfer(String name);

  /// No description provided for @blockUser.
  ///
  /// In en, this message translates to:
  /// **'Block user'**
  String get blockUser;

  /// No description provided for @confirmBlock.
  ///
  /// In en, this message translates to:
  /// **'Block {name}? They will be hidden from your feed and chats.'**
  String confirmBlock(String name);

  /// No description provided for @userBlocked.
  ///
  /// In en, this message translates to:
  /// **'User blocked'**
  String get userBlocked;

  /// No description provided for @roleOwner.
  ///
  /// In en, this message translates to:
  /// **'Owner'**
  String get roleOwner;

  /// No description provided for @roleAdmin.
  ///
  /// In en, this message translates to:
  /// **'Admin'**
  String get roleAdmin;

  /// No description provided for @roleMember.
  ///
  /// In en, this message translates to:
  /// **'Member'**
  String get roleMember;

  /// No description provided for @emptyMembers.
  ///
  /// In en, this message translates to:
  /// **'No members yet'**
  String get emptyMembers;

  /// No description provided for @yourRating.
  ///
  /// In en, this message translates to:
  /// **'Your rating: {stars}/5'**
  String yourRating(int stars);

  /// No description provided for @recentRatings.
  ///
  /// In en, this message translates to:
  /// **'Recent ratings'**
  String get recentRatings;

  /// No description provided for @noRatingsYet.
  ///
  /// In en, this message translates to:
  /// **'No ratings yet'**
  String get noRatingsYet;

  /// No description provided for @reportMessage.
  ///
  /// In en, this message translates to:
  /// **'Report message'**
  String get reportMessage;

  /// No description provided for @blockCreator.
  ///
  /// In en, this message translates to:
  /// **'Block organizer'**
  String get blockCreator;

  /// No description provided for @guidelinesIntro.
  ///
  /// In en, this message translates to:
  /// **'How we keep community events clearer and safer to join.'**
  String get guidelinesIntro;

  /// No description provided for @guidelineIdentityTitle.
  ///
  /// In en, this message translates to:
  /// **'Identity documents'**
  String get guidelineIdentityTitle;

  /// No description provided for @guidelineIdentityBody.
  ///
  /// In en, this message translates to:
  /// **'Identity documents are required to help maintain a safer community.'**
  String get guidelineIdentityBody;

  /// No description provided for @guidelineReportTitle.
  ///
  /// In en, this message translates to:
  /// **'Report when something is wrong'**
  String get guidelineReportTitle;

  /// No description provided for @guidelineReportBody.
  ///
  /// In en, this message translates to:
  /// **'You can report users, events, or messages. Moderators review reports and can take action.'**
  String get guidelineReportBody;

  /// No description provided for @guidelineBlockTitle.
  ///
  /// In en, this message translates to:
  /// **'Block users'**
  String get guidelineBlockTitle;

  /// No description provided for @guidelineBlockBody.
  ///
  /// In en, this message translates to:
  /// **'Blocking hides that person’s events from your discover feed and stops join/chat interactions between you.'**
  String get guidelineBlockBody;

  /// No description provided for @guidelinesDisclaimer.
  ///
  /// In en, this message translates to:
  /// **'These tools help maintain a safer community. They do not guarantee that every interaction is risk-free.'**
  String get guidelinesDisclaimer;

  /// No description provided for @blockedUsers.
  ///
  /// In en, this message translates to:
  /// **'Blocked users'**
  String get blockedUsers;

  /// No description provided for @confirmUnblock.
  ///
  /// In en, this message translates to:
  /// **'Unblock {name}?'**
  String confirmUnblock(String name);

  /// No description provided for @userUnblocked.
  ///
  /// In en, this message translates to:
  /// **'User unblocked'**
  String get userUnblocked;

  /// No description provided for @accountDeactivatedTitle.
  ///
  /// In en, this message translates to:
  /// **'Account deactivated'**
  String get accountDeactivatedTitle;

  /// No description provided for @accountDeactivatedBody.
  ///
  /// In en, this message translates to:
  /// **'Your account has been deactivated by an administrator. Contact support if you believe this is a mistake.'**
  String get accountDeactivatedBody;

  /// No description provided for @reportTitle.
  ///
  /// In en, this message translates to:
  /// **'Submit a report'**
  String get reportTitle;

  /// No description provided for @reportType.
  ///
  /// In en, this message translates to:
  /// **'What are you reporting?'**
  String get reportType;

  /// No description provided for @reportTypeUser.
  ///
  /// In en, this message translates to:
  /// **'User'**
  String get reportTypeUser;

  /// No description provided for @reportTypeEvent.
  ///
  /// In en, this message translates to:
  /// **'Event'**
  String get reportTypeEvent;

  /// No description provided for @reportTypeMessage.
  ///
  /// In en, this message translates to:
  /// **'Message'**
  String get reportTypeMessage;

  /// No description provided for @reportableId.
  ///
  /// In en, this message translates to:
  /// **'ID'**
  String get reportableId;

  /// No description provided for @browseAsGuest.
  ///
  /// In en, this message translates to:
  /// **'Browse events'**
  String get browseAsGuest;

  /// No description provided for @continueWithGoogle.
  ///
  /// In en, this message translates to:
  /// **'Continue with Google'**
  String get continueWithGoogle;

  /// No description provided for @loginRequired.
  ///
  /// In en, this message translates to:
  /// **'Please log in to continue'**
  String get loginRequired;

  /// No description provided for @googleSignInFailed.
  ///
  /// In en, this message translates to:
  /// **'Google sign-in failed. Check that Google is configured for this app.'**
  String get googleSignInFailed;

  /// No description provided for @loadMore.
  ///
  /// In en, this message translates to:
  /// **'Load more'**
  String get loadMore;

  /// No description provided for @verifyEmail.
  ///
  /// In en, this message translates to:
  /// **'Verify email'**
  String get verifyEmail;

  /// No description provided for @verifyEmailHint.
  ///
  /// In en, this message translates to:
  /// **'Enter the 4-digit code we sent to your email.'**
  String get verifyEmailHint;

  /// No description provided for @resendCode.
  ///
  /// In en, this message translates to:
  /// **'Resend code'**
  String get resendCode;

  /// No description provided for @forgotPassword.
  ///
  /// In en, this message translates to:
  /// **'Forgot password?'**
  String get forgotPassword;

  /// No description provided for @forgotPasswordSub.
  ///
  /// In en, this message translates to:
  /// **'Enter your email and we’ll send a 4-digit code.'**
  String get forgotPasswordSub;

  /// No description provided for @verifyResetCodeSub.
  ///
  /// In en, this message translates to:
  /// **'Check your email for the 4-digit code.'**
  String get verifyResetCodeSub;

  /// No description provided for @accessFormSub.
  ///
  /// In en, this message translates to:
  /// **'Sign in to continue to your community.'**
  String get accessFormSub;

  /// No description provided for @accessEnterTitle.
  ///
  /// In en, this message translates to:
  /// **'Enter Access Code'**
  String get accessEnterTitle;

  /// No description provided for @accessEnterSub.
  ///
  /// In en, this message translates to:
  /// **'A 4-digit code has been sent to your device.'**
  String get accessEnterSub;

  /// No description provided for @accessLoginEnterSub.
  ///
  /// In en, this message translates to:
  /// **'Using your password digits to unlock.'**
  String get accessLoginEnterSub;

  /// No description provided for @accessVerifyingTitle.
  ///
  /// In en, this message translates to:
  /// **'Verifying...'**
  String get accessVerifyingTitle;

  /// No description provided for @accessVerifyingSub.
  ///
  /// In en, this message translates to:
  /// **'Securely checking your credentials.'**
  String get accessVerifyingSub;

  /// No description provided for @accessGrantedTitle.
  ///
  /// In en, this message translates to:
  /// **'Access Granted'**
  String get accessGrantedTitle;

  /// No description provided for @accessGrantedSub.
  ///
  /// In en, this message translates to:
  /// **'Welcome back. Taking you in…'**
  String get accessGrantedSub;

  /// No description provided for @accessRegisteredTitle.
  ///
  /// In en, this message translates to:
  /// **'Account created'**
  String get accessRegisteredTitle;

  /// No description provided for @accessRegisteredSub.
  ///
  /// In en, this message translates to:
  /// **'Next: verify your email with the code we sent.'**
  String get accessRegisteredSub;

  /// No description provided for @sendResetCode.
  ///
  /// In en, this message translates to:
  /// **'Send reset code'**
  String get sendResetCode;

  /// No description provided for @verifyResetCode.
  ///
  /// In en, this message translates to:
  /// **'Verify reset code'**
  String get verifyResetCode;

  /// No description provided for @resetPassword.
  ///
  /// In en, this message translates to:
  /// **'Reset password'**
  String get resetPassword;

  /// No description provided for @newPassword.
  ///
  /// In en, this message translates to:
  /// **'New password'**
  String get newPassword;

  /// No description provided for @enterCode.
  ///
  /// In en, this message translates to:
  /// **'Enter code'**
  String get enterCode;

  /// No description provided for @codeSent.
  ///
  /// In en, this message translates to:
  /// **'Code sent'**
  String get codeSent;

  /// No description provided for @favorites.
  ///
  /// In en, this message translates to:
  /// **'Favorites'**
  String get favorites;

  /// No description provided for @favorite.
  ///
  /// In en, this message translates to:
  /// **'Favorite'**
  String get favorite;

  /// No description provided for @unfavorite.
  ///
  /// In en, this message translates to:
  /// **'Remove favorite'**
  String get unfavorite;

  /// No description provided for @emptyFavorites.
  ///
  /// In en, this message translates to:
  /// **'No favorites yet. Bookmark events from Discover.'**
  String get emptyFavorites;

  /// No description provided for @viewTicket.
  ///
  /// In en, this message translates to:
  /// **'View ticket'**
  String get viewTicket;

  /// No description provided for @eventTicket.
  ///
  /// In en, this message translates to:
  /// **'Event ticket'**
  String get eventTicket;

  /// No description provided for @ticketWord.
  ///
  /// In en, this message translates to:
  /// **'TICKET'**
  String get ticketWord;

  /// No description provided for @ticketHelp.
  ///
  /// In en, this message translates to:
  /// **'Show this pass at the venue. Your code is unique to you for this event.'**
  String get ticketHelp;

  /// No description provided for @ticketRoleOwner.
  ///
  /// In en, this message translates to:
  /// **'Host'**
  String get ticketRoleOwner;

  /// No description provided for @ticketRoleAdmin.
  ///
  /// In en, this message translates to:
  /// **'Admin'**
  String get ticketRoleAdmin;

  /// No description provided for @ticketRoleMember.
  ///
  /// In en, this message translates to:
  /// **'Pass'**
  String get ticketRoleMember;

  /// No description provided for @yourParticipations.
  ///
  /// In en, this message translates to:
  /// **'Your participations'**
  String get yourParticipations;

  /// No description provided for @manageAll.
  ///
  /// In en, this message translates to:
  /// **'Manage all'**
  String get manageAll;

  /// No description provided for @participationsEmpty.
  ///
  /// In en, this message translates to:
  /// **'No upcoming participations. Discover something fun.'**
  String get participationsEmpty;

  /// No description provided for @confirmedAttendance.
  ///
  /// In en, this message translates to:
  /// **'Confirmed'**
  String get confirmedAttendance;

  /// No description provided for @requestPendingShort.
  ///
  /// In en, this message translates to:
  /// **'Pending'**
  String get requestPendingShort;

  /// No description provided for @pendingReviewNotice.
  ///
  /// In en, this message translates to:
  /// **'Waiting for the host to accept your request.'**
  String get pendingReviewNotice;

  /// No description provided for @pendingRequestsSection.
  ///
  /// In en, this message translates to:
  /// **'Waiting for acceptance'**
  String get pendingRequestsSection;

  /// No description provided for @joinedEventsSection.
  ///
  /// In en, this message translates to:
  /// **'Events you joined'**
  String get joinedEventsSection;

  /// No description provided for @weatherSetLocation.
  ///
  /// In en, this message translates to:
  /// **'Set your location'**
  String get weatherSetLocation;

  /// No description provided for @weatherSetLocationHint.
  ///
  /// In en, this message translates to:
  /// **'Choose a city to see local weather on your home.'**
  String get weatherSetLocationHint;

  /// No description provided for @requestToJoin.
  ///
  /// In en, this message translates to:
  /// **'Request to join'**
  String get requestToJoin;

  /// No description provided for @updateAvailable.
  ///
  /// In en, this message translates to:
  /// **'Update available'**
  String get updateAvailable;

  /// No description provided for @updateAvailableBody.
  ///
  /// In en, this message translates to:
  /// **'A new version of the app is available.'**
  String get updateAvailableBody;

  /// No description provided for @updateNow.
  ///
  /// In en, this message translates to:
  /// **'Update'**
  String get updateNow;

  /// No description provided for @updateLater.
  ///
  /// In en, this message translates to:
  /// **'Later'**
  String get updateLater;

  /// No description provided for @searching.
  ///
  /// In en, this message translates to:
  /// **'Searching'**
  String get searching;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
