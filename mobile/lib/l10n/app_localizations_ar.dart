// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appName => 'فن';

  @override
  String get login => 'تسجيل الدخول';

  @override
  String get register => 'إنشاء حساب';

  @override
  String get logout => 'تسجيل الخروج';

  @override
  String get email => 'البريد الإلكتروني';

  @override
  String get password => 'كلمة المرور';

  @override
  String get passwordConfirmation => 'تأكيد كلمة المرور';

  @override
  String get name => 'الاسم';

  @override
  String get sex => 'الجنس';

  @override
  String get sexMale => 'ذكر';

  @override
  String get sexFemale => 'أنثى';

  @override
  String get sexMix => 'مختلط';

  @override
  String get sexRequired => 'يرجى اختيار الجنس.';

  @override
  String get sexPrompt => 'اختر الجنس';

  @override
  String get sexPromptBody => 'أضفه في ملفك الشخصي لإكمال إعداد حسابك.';

  @override
  String get remember => 'تذكرني';

  @override
  String get language => 'اللغة';

  @override
  String get noAccount => 'ليس لديك حساب؟';

  @override
  String get haveAccount => 'لديك حساب بالفعل؟';

  @override
  String get navHome => 'الرئيسية';

  @override
  String get navDiscover => 'اكتشف';

  @override
  String get navCreate => 'إنشاء';

  @override
  String get navMyEvents => 'فعالياتي';

  @override
  String get navProfile => 'الملف';

  @override
  String get navExplore => 'استكشف';

  @override
  String get publicProfile => 'الملف العام';

  @override
  String get yourPublicId => 'معرّفك العام';

  @override
  String get openPublicProfile => 'فتح الملف العام';

  @override
  String get chatsTitle => 'المحادثات';

  @override
  String get chatsEmptyTitle => 'لا محادثات بعد';

  @override
  String get chatsEmptyBody => 'انضم إلى فعالية لبدء المحادثة مع الأعضاء.';

  @override
  String get chatsNoMessages => 'لا رسائل بعد';

  @override
  String get chatsYou => 'أنت';

  @override
  String get chatsFilterRecent => 'الأحدث';

  @override
  String get chatsFilterUnread => 'غير مقروء';

  @override
  String get chatsNoResults => 'لا محادثات تطابق بحثك';

  @override
  String get chatsNoUnread => 'لا توجد محادثات غير مقروءة';

  @override
  String get searchChatsHint => 'ابحث في المحادثات…';

  @override
  String get copyMessage => 'نسخ الرسالة';

  @override
  String get messageCopied => 'تم نسخ الرسالة';

  @override
  String get notificationsTitle => 'الإشعارات';

  @override
  String get notificationsEmptyTitle => 'لا إشعارات بعد';

  @override
  String get notificationsEmptyBody =>
      'عندما يطلب أحد الانضمام لفعاليتك، سيظهر هنا.';

  @override
  String get notificationsMarkAllRead => 'تمييز الكل كمقروء';

  @override
  String get navNotifications => 'الإشعارات';

  @override
  String get openChat => 'فتح المحادثة';

  @override
  String get hostedEvents => 'فعاليات مستضافة';

  @override
  String get statHosted => 'استضاف';

  @override
  String get statJoined => 'انضم';

  @override
  String get statRating => 'التقييم';

  @override
  String get profileNotFound => 'الملف غير موجود';

  @override
  String greetingMorning(String name) {
    return 'صباح الخير، $name';
  }

  @override
  String greetingAfternoon(String name) {
    return 'مساء الخير، $name';
  }

  @override
  String greetingEvening(String name) {
    return 'مساء الخير، $name';
  }

  @override
  String get greetingSub => 'جاهز تجد شيئاً ممتعاً اليوم؟';

  @override
  String get heroEyebrow => 'أنشطة حولك الآن';

  @override
  String get heroTitle1 => 'اعثر على أشخاص.';

  @override
  String get heroTitle2 => 'افعل ما تحب.';

  @override
  String get heroBody =>
      'اكتشف أنشطة قريبة، وانضم لمن يشاركك الاهتمامات، وحوّل أمسيتك إلى ذكرى رائعة.';

  @override
  String get heroActivityPh => 'كرة قدم، بلاي ستيشن، جري...';

  @override
  String get findEvents => 'ابحث عن فعاليات';

  @override
  String get trustIdentity => 'مجتمع مبني على الهوية';

  @override
  String get trustPeople => 'أشخاص حقيقيون بالقرب منك';

  @override
  String get trustReport => 'إبلاغ سهل';

  @override
  String get nearbyTitle => 'يحدث بالقرب منك';

  @override
  String get nearbySubGeneric => 'فعاليات بالقرب منك';

  @override
  String nearbySub(String city, int km) {
    return 'فعاليات في $city · ضمن $km كم';
  }

  @override
  String get seeAll => 'عرض الكل';

  @override
  String get createEvent => 'إنشاء فعالية';

  @override
  String get viewEvent => 'عرض الفعالية';

  @override
  String get members => 'أعضاء';

  @override
  String get free => 'مجاني';

  @override
  String get currencyShort => 'ج.م';

  @override
  String costPerPersonAmount(String amount) {
    return '$amount ج.م / فرد';
  }

  @override
  String nowPlayingTitle(String brand) {
    return 'مباشر على $brand';
  }

  @override
  String get nowPlayingLive => 'جارية الآن';

  @override
  String get nowPlayingIdle => 'لا يوجد شيء مباشر الآن — التالي بالأسفل.';

  @override
  String get nowPlayingUpNext => 'التالي';

  @override
  String get nowPlayingPrevious => 'انتهت للتو';

  @override
  String get eventsLabel => 'فعاليات';

  @override
  String get almostFull => 'شبه ممتلئ';

  @override
  String get fillingFast => 'يمتلئ بسرعة';

  @override
  String get newBadge => 'جديد';

  @override
  String get popular => 'شائع';

  @override
  String get emptyNearby => 'لا توجد فعاليات قريبة بعد. كن أول من ينشئ واحدة.';

  @override
  String get retry => 'إعادة المحاولة';

  @override
  String get loading => 'جارٍ التحميل…';

  @override
  String get splashTagline => 'التقِ بالقرب. انضم فعلياً.';

  @override
  String get comingSoon => 'قريباً';

  @override
  String get search => 'بحث';

  @override
  String get searchPlaceholder => 'ابحث عن فعاليات وأنشطة وأماكن...';

  @override
  String get allCities => 'كل المدن';

  @override
  String get allCategories => 'كل التصنيفات';

  @override
  String get applyFilters => 'تطبيق';

  @override
  String get clearFilters => 'مسح';

  @override
  String get nationwideNotice =>
      'عرض الفعاليات على مستوى البلاد. اختر مدينة لاكتشاف القريب منك.';

  @override
  String get emptyDiscover => 'لا توجد فعاليات تطابق عوامل التصفية.';

  @override
  String get showAllEvents => 'عرض كل الفعاليات';

  @override
  String get changeLocation => 'تغيير الموقع';

  @override
  String get discoveryDistance => 'المسافة';

  @override
  String get discoverTitle => 'اكتشف';

  @override
  String get discoverSub => 'تصفّح اللقاءات القريبة أو على مستوى البلاد';

  @override
  String get errorGeneric => 'حدث خطأ. حاول مرة أخرى.';

  @override
  String get errorOffline => 'أنت غير متصل. تحقق من الاتصال.';

  @override
  String get errorUnauthorized => 'سجّل الدخول للمتابعة.';

  @override
  String get validationRequired => 'هذا الحقل مطلوب';

  @override
  String get validationEmail => 'أدخل بريداً إلكترونياً صالحاً';

  @override
  String get join => 'انضم';

  @override
  String get leave => 'مغادرة';

  @override
  String get publish => 'نشر';

  @override
  String get chat => 'محادثة';

  @override
  String get location => 'الموقع';

  @override
  String get identity => 'الهوية';

  @override
  String get blocks => 'المستخدمون المحظورون';

  @override
  String get report => 'إبلاغ';

  @override
  String get rateEvent => 'قيّم الفعالية';

  @override
  String get save => 'حفظ';

  @override
  String get send => 'إرسال';

  @override
  String get editEvent => 'تعديل الفعالية';

  @override
  String get eventTitle => 'العنوان';

  @override
  String get description => 'الوصف';

  @override
  String get category => 'التصنيف';

  @override
  String get city => 'المدينة';

  @override
  String get venue => 'المكان';

  @override
  String get venueTba => 'المكان غير محدد';

  @override
  String get venueHint => 'اسم المقهى، الحديقة، المبنى…';

  @override
  String get venueSuggestHelp =>
      'ابدأ بكتابة اسم المكان — اختر اقتراحًا لوضع الدبوس.';

  @override
  String get locationTba => 'الموقع غير محدد';

  @override
  String get placeOnMap => 'الموقع على الخريطة';

  @override
  String get placeOnMapHelp =>
      'اكتب اسم المكان، أو اضغط على الخريطة، أو الصق رابط خرائط. الدبوس هو نقطة اللقاء.';

  @override
  String get placeTapHint => 'اضغط على الخريطة لوضع الدبوس أو تحريكه.';

  @override
  String get mapsUrl => 'رابط الخرائط';

  @override
  String get mapsUrlHint => 'https://maps.app.goo.gl/…';

  @override
  String get applyMapsLink => 'تطبيق الرابط';

  @override
  String get placeClear => 'مسح الدبوس';

  @override
  String get placePinned =>
      'تم حفظ الدبوس — يمكن للضيوف فتح الموقع في خرائط جوجل.';

  @override
  String get placeFromLink => 'تم قراءة الموقع من رابط الخرائط.';

  @override
  String get placeFromSuggest => 'تم تعيين الموقع من بحث المكان.';

  @override
  String get placeCleared => 'تم مسح دبوس الخريطة.';

  @override
  String get placeResolving => 'جاري قراءة الموقع من الرابط…';

  @override
  String get mapsUrlUnresolved =>
      'تعذر قراءة موقع من هذا الرابط. الصق رابط خرائط جوجل أو OpenStreetMap كاملاً.';

  @override
  String get openInMaps => 'فتح في الخرائط';

  @override
  String get startsAt => 'يبدأ';

  @override
  String get endsAt => 'ينتهي';

  @override
  String get maxParticipants => 'الحد الأقصى للمشاركين';

  @override
  String get eventSex => 'الجمهور';

  @override
  String get eventSexHelp => 'لمن هذه الفعالية: ذكر، أنثى، أو مختلط.';

  @override
  String get eventSexMismatch =>
      'هذه الفعالية مخصصة لجمهور يختلف عن الجنس في ملفك.';

  @override
  String get requirements => 'المتطلبات';

  @override
  String get expectedCost => 'التكلفة المتوقعة';

  @override
  String get costPerPerson => 'التكلفة للفرد';

  @override
  String get pickImage => 'اختر صورة';

  @override
  String get imageSelected => 'تم اختيار الصورة';

  @override
  String get createDraft => 'حفظ كمسودة';

  @override
  String get publishEvent => 'نشر الفعالية';

  @override
  String get cancelEvent => 'إلغاء الفعالية';

  @override
  String get completeEvent => 'تحديد كمكتملة';

  @override
  String get viewRequests => 'طلبات الانضمام';

  @override
  String get viewMembers => 'الأعضاء';

  @override
  String get joinPending => 'الطلب قيد المراجعة';

  @override
  String get cancelJoin => 'إلغاء الانضمام';

  @override
  String get confirmCancelJoin => 'هل أنت متأكد أنك تريد إلغاء طلب الانضمام؟';

  @override
  String get confirmRejectRequest => 'هل أنت متأكد أنك تريد رفض طلب الانضمام؟';

  @override
  String get stop => 'إيقاف';

  @override
  String get confirmTitle => 'لحظة للتأكيد';

  @override
  String get confirmYes => 'نعم، متابعة';

  @override
  String get eventFull => 'الفعالية ممتلئة';

  @override
  String get requestRejected => 'تم رفض الطلب';

  @override
  String get requestPending => 'الطلب قيد الانتظار';

  @override
  String get joined => 'منضم';

  @override
  String get yourOwnEvent => 'فعاليتك';

  @override
  String get notAvailable => 'غير متاح';

  @override
  String get verifyIdentity => 'تحقق من هويتك للمشاركة';

  @override
  String get uploadDocuments => 'رفع المستندات';

  @override
  String get idFront => 'وجه الهوية';

  @override
  String get idBack => 'ظهر الهوية';

  @override
  String get selfie => 'صورة شخصية';

  @override
  String get unblock => 'إلغاء الحظر';

  @override
  String get reportReason => 'السبب';

  @override
  String get reportDetails => 'التفاصيل (اختياري)';

  @override
  String get submitReport => 'إرسال البلاغ';

  @override
  String get reportSubmitted => 'تم إرسال البلاغ. شكراً لك.';

  @override
  String get safetyGuidelines => 'إرشادات السلامة';

  @override
  String get safetyGuidelinesBody =>
      'التقِ في أماكن عامة، وثق بحدسك، وأبلغ عن أي سلوك مشبوه. الحظر يخفي المستخدمين من خلاصتك ومحادثاتك.';

  @override
  String get distanceKm => 'نطاق البحث';

  @override
  String get selectCity => 'اختر المدينة';

  @override
  String get locationSub => 'اختر مدينتك ومدى استعدادك للسفر للفعاليات.';

  @override
  String get locationSaved => 'تم حفظ الموقع';

  @override
  String withinKm(int km) {
    return 'ضمن $km كم';
  }

  @override
  String showingWithin(int km) {
    return 'عرض الفعاليات ضمن $km كم';
  }

  @override
  String get tapToChangeLocation => 'اضغط لتغيير المدينة والمسافة';

  @override
  String get setYourLocation => 'حدد موقعك';

  @override
  String get messageHint => 'اكتب رسالة…';

  @override
  String get attachSoon => 'المرفقات قريبًا.';

  @override
  String get chatChip => 'محادثة';

  @override
  String get noMessages => 'لا رسائل بعد';

  @override
  String get noRequests => 'لا طلبات معلقة';

  @override
  String get noBlocks => 'لم تحظر أحداً';

  @override
  String get emptyMyEvents => 'لم تنشئ أو تنضم لأي فعاليات بعد.';

  @override
  String get comment => 'تعليق';

  @override
  String get thankYouRating => 'شكراً على تقييمك!';

  @override
  String get confirmLeave => 'هل أنت متأكد أنك تريد مغادرة هذه الفعالية؟';

  @override
  String get confirmCancel => 'هل أنت متأكد أنك تريد إلغاء هذه الفعالية؟';

  @override
  String get statusDraft => 'مسودة';

  @override
  String get statusPublished => 'منشورة';

  @override
  String get statusCancelled => 'ملغاة';

  @override
  String get statusCompleted => 'مكتملة';

  @override
  String get notActiveAccount =>
      'تحقق من هويتك قبل إنشاء الفعاليات أو الانضمام إليها.';

  @override
  String get invalidRoute => 'هذا الرابط غير صالح.';

  @override
  String get newChatMessage => 'لديك رسالة دردشة جديدة.';

  @override
  String get chatToastNew => 'رسالة جديدة';

  @override
  String get chatToastJustNow => 'الآن';

  @override
  String get joinAcceptedToast => 'تم قبولك!';

  @override
  String get joinRequestedToast => 'طلب انضمام جديد';

  @override
  String get messageReactedToast => 'تفاعل جديد';

  @override
  String get uploadTooLarge => 'الصورة كبيرة جداً (الحد الأقصى 8 ميغابايت).';

  @override
  String get uploadBadType => 'استخدم صورة بصيغة JPG أو PNG أو WEBP.';

  @override
  String get uploadMissing => 'تعذر قراءة الصورة. جرّب ملفاً آخر.';

  @override
  String get failedToLoadEvents => 'تعذر تحميل الفعاليات.';

  @override
  String get invalidCode => 'رمز غير صالح';

  @override
  String get accept => 'قبول';

  @override
  String get reject => 'رفض';

  @override
  String get reportSpam => 'بريد مزعج';

  @override
  String get reportHarassment => 'تحرش';

  @override
  String get reportInappropriate => 'محتوى غير لائق';

  @override
  String get reportScam => 'احتيال';

  @override
  String get reportOther => 'أخرى';

  @override
  String get leaveEvent => 'مغادرة الفعالية';

  @override
  String organizerRating(String average, int count) {
    return '$average ★ ($count تقييم)';
  }

  @override
  String get eventSaved => 'تم حفظ الفعالية';

  @override
  String get eventPublished => 'تم نشر الفعالية';

  @override
  String get optional => 'اختياري';

  @override
  String get pickDateTime => 'اختر التاريخ والوقت';

  @override
  String publishOfferBody(String title) {
    return 'نشر \"$title\" الآن ليتمكن الآخرون من اكتشافها؟';
  }

  @override
  String get later => 'لاحقاً';

  @override
  String get cancel => 'إلغاء';

  @override
  String get back => 'رجوع';

  @override
  String get identitySub =>
      'ارفع صوراً واضحة لهويتك (الوجه والظهر) وصورة شخصية للتحقق.';

  @override
  String get identityMissing => 'يرجى اختيار الصور الثلاث';

  @override
  String get identityUploaded => 'تم رفع المستندات بنجاح';

  @override
  String get identityTheaterEnterTitle => 'رفع آمن';

  @override
  String get identityTheaterEnterSub => 'جارٍ تشفير وإرسال مستندات الهوية.';

  @override
  String get identityTheaterVerifyingTitle => 'جارٍ التحقق...';

  @override
  String get identityTheaterVerifyingSub => 'نتحقق من مستنداتك بأمان.';

  @override
  String get identityTheaterGrantedTitle => 'تم استلام المستندات';

  @override
  String get identityTheaterGrantedSub => 'حسابك نشط. جارٍ التحويل للرئيسية...';

  @override
  String get searchSuggestions => 'اقتراحات';

  @override
  String get seeAllResults => 'عرض كل النتائج';

  @override
  String get attachmentHint => 'اضغط على المجلد لاختيار ملف';

  @override
  String get attachmentAdd => 'إضافة ملف';

  @override
  String get attachmentFiles => 'ملفات';

  @override
  String get attachmentClear => 'إزالة';

  @override
  String get selected => 'تم الاختيار';

  @override
  String get editProfile => 'تعديل الملف';

  @override
  String get updateProfile => 'تحديث الملف';

  @override
  String get currentPassword => 'كلمة المرور الحالية';

  @override
  String get removePhoto => 'إزالة الصورة الحالية';

  @override
  String get resubmitRequired => 'يرجى إعادة رفع مستندات الهوية';

  @override
  String get identityPending => 'بانتظار الرفع';

  @override
  String get identitySubmitted => 'تم رفع المستندات';

  @override
  String get intoTitle => 'إيه اللي يهمك؟';

  @override
  String get intoSub => 'اختَر نشاطاً واكتشف ناس حواليك.';

  @override
  String get viewAllCategories => 'عرض كل التصنيفات ←';

  @override
  String get startingSoon => 'تبدأ قريباً';

  @override
  String get safetyTitle => 'سلامتك مهمة.';

  @override
  String get safetyBody =>
      'مستندات الهوية وأدوات الإبلاغ والإشراف وضوابط الحساب جزء من تجربة المجتمع.';

  @override
  String get safetyCta => 'تعرّف على السلامة';

  @override
  String howTitle(String brand) {
    return 'كيف يعمل $brand';
  }

  @override
  String get howSub => 'من \"عايز ألعب\" لحد ما تلعب فعلاً.';

  @override
  String get how1Title => 'لاقي حاجة';

  @override
  String get how1Body =>
      'اختَر نشاطاً وشوف فعاليات حول مدينتك والمسافة المفضلة.';

  @override
  String get how2Title => 'اطلب الانضمام';

  @override
  String get how2Body => 'ابعت طلب انضمام. صاحب الفعالية بيراجع ويقبل الأعضاء.';

  @override
  String get how3Title => 'قابل واستمتع';

  @override
  String get how3Body =>
      'استخدم محادثة الفعالية للتنسيق، وبعدين تقابلوا في المكان.';

  @override
  String get ctaTitle => 'مش لاقي نشاطك؟';

  @override
  String get ctaBody => 'أنشئ فعالية وخلي الناس حواليك تنضم للخطة.';

  @override
  String get ctaButton => 'أنشئ فعاليتك';

  @override
  String get today => 'اليوم';

  @override
  String get tomorrow => 'غداً';

  @override
  String get weekend => 'نهاية الأسبوع';

  @override
  String get whenAny => 'أي وقت';

  @override
  String get whenFilter => 'الوقت';

  @override
  String get promote => 'ترقية إلى مشرف';

  @override
  String get demote => 'تخفيض إلى عضو';

  @override
  String get transferOwnership => 'نقل الملكية';

  @override
  String confirmTransfer(String name) {
    return 'نقل الملكية إلى $name؟';
  }

  @override
  String get blockUser => 'حظر المستخدم';

  @override
  String confirmBlock(String name) {
    return 'حظر $name؟ سيتم إخفاؤه من خلاصتك ومحادثاتك.';
  }

  @override
  String get userBlocked => 'تم حظر المستخدم';

  @override
  String get roleOwner => 'المالك';

  @override
  String get roleAdmin => 'مشرف';

  @override
  String get roleMember => 'عضو';

  @override
  String get emptyMembers => 'لا يوجد أعضاء بعد';

  @override
  String yourRating(int stars) {
    return 'تقييمك: $stars/5';
  }

  @override
  String get recentRatings => 'أحدث التقييمات';

  @override
  String get noRatingsYet => 'لا توجد تقييمات بعد';

  @override
  String get reportMessage => 'الإبلاغ عن الرسالة';

  @override
  String get blockCreator => 'حظر المنظم';

  @override
  String get guidelinesIntro =>
      'كيف نحافظ على فعاليات المجتمع أوضح وأكثر أماناً للانضمام.';

  @override
  String get guidelineIdentityTitle => 'مستندات الهوية';

  @override
  String get guidelineIdentityBody =>
      'مستندات الهوية مطلوبة للمساعدة في الحفاظ على مجتمع أكثر أمانًا.';

  @override
  String get guidelineReportTitle => 'أبلغ عندما يحدث خطأ';

  @override
  String get guidelineReportBody =>
      'يمكنك الإبلاغ عن مستخدمين أو فعاليات أو رسائل. يراجع المشرفون البلاغات ويمكنهم اتخاذ إجراء.';

  @override
  String get guidelineBlockTitle => 'حظر المستخدمين';

  @override
  String get guidelineBlockBody =>
      'الحظر يخفي فعاليات ذلك الشخص من اكتشافك ويوقف تفاعلات الانضمام/المحادثة بينكما.';

  @override
  String get guidelinesDisclaimer =>
      'هذه الأدوات تساعد في الحفاظ على مجتمع أكثر أماناً. وهي لا تضمن أن كل تفاعل خالٍ من المخاطر.';

  @override
  String get blockedUsers => 'المستخدمون المحظورون';

  @override
  String confirmUnblock(String name) {
    return 'إلغاء حظر $name؟';
  }

  @override
  String get userUnblocked => 'تم إلغاء الحظر';

  @override
  String get accountDeactivatedTitle => 'تم إيقاف الحساب';

  @override
  String get accountDeactivatedBody =>
      'تم إيقاف حسابك بواسطة المسؤول. تواصل مع الدعم إذا كنت تعتقد أن هذا خطأ.';

  @override
  String get reportTitle => 'إرسال بلاغ';

  @override
  String get reportType => 'ماذا تبلّغ عنه؟';

  @override
  String get reportTypeUser => 'مستخدم';

  @override
  String get reportTypeEvent => 'فعالية';

  @override
  String get reportTypeMessage => 'رسالة';

  @override
  String get reportableId => 'المعرّف';

  @override
  String get browseAsGuest => 'تصفّح الفعاليات';

  @override
  String get continueWithGoogle => 'المتابعة باستخدام Google';

  @override
  String get loginRequired => 'يرجى تسجيل الدخول للمتابعة';

  @override
  String get googleSignInFailed =>
      'فشل تسجيل الدخول عبر Google. أضف SHA-1 لهذا الجهاز في Firebase (إعدادات المشروع → تطبيق Android)، فعّل تسجيل Google، ثم أعد البناء.';

  @override
  String get loadMore => 'تحميل المزيد';

  @override
  String get verifyEmail => 'تأكيد البريد';

  @override
  String get verifyEmailHint =>
      'أدخل الرمز المكوّن من 4 أرقام الذي أرسلناه إلى بريدك.';

  @override
  String get resendCode => 'إعادة إرسال الرمز';

  @override
  String get forgotPassword => 'نسيت كلمة المرور؟';

  @override
  String get forgotPasswordSub => 'أدخل بريدك وسنرسل رمزاً مكوّناً من 4 أرقام.';

  @override
  String get verifyResetCodeSub =>
      'تحقق من بريدك للحصول على الرمز المكوّن من 4 أرقام.';

  @override
  String get accessFormSub => 'سجّل الدخول للمتابعة إلى مجتمعك.';

  @override
  String get accessEnterTitle => 'أدخل رمز الوصول';

  @override
  String get accessEnterSub => 'تم إرسال رمز مكوّن من 4 أرقام إلى جهازك.';

  @override
  String get accessLoginEnterSub => 'نستخدم أرقام كلمة المرور لفتح الجلسة.';

  @override
  String get accessVerifyingTitle => 'جارٍ التحقق...';

  @override
  String get accessVerifyingSub => 'نتحقق من بياناتك بأمان.';

  @override
  String get accessGrantedTitle => 'تم منح الوصول';

  @override
  String get accessGrantedSub => 'مرحباً بعودتك. جارٍ التحويل...';

  @override
  String get accessRegisteredTitle => 'تم إنشاء الحساب';

  @override
  String get accessRegisteredSub =>
      'التالي: تحقق من بريدك بالرمز الذي أرسلناه.';

  @override
  String get sendResetCode => 'إرسال رمز الاستعادة';

  @override
  String get verifyResetCode => 'تأكيد رمز الاستعادة';

  @override
  String get resetPassword => 'إعادة تعيين كلمة المرور';

  @override
  String get newPassword => 'كلمة المرور الجديدة';

  @override
  String get enterCode => 'أدخل الرمز';

  @override
  String get codeSent => 'تم إرسال الرمز';

  @override
  String get favorites => 'المفضلة';

  @override
  String get favorite => 'إضافة للمفضلة';

  @override
  String get unfavorite => 'إزالة من المفضلة';

  @override
  String get emptyFavorites => 'لا مفضلات بعد. أضف فعاليات من اكتشف.';

  @override
  String get viewTicket => 'عرض التذكرة';

  @override
  String get eventTicket => 'تذكرة الفعالية';

  @override
  String get ticketWord => 'تذكرة';

  @override
  String get ticketHelp =>
      'اعرض هذا التصريح في المكان. الرمز فريد لك لهذه الفعالية.';

  @override
  String get ticketRoleOwner => 'مضيف';

  @override
  String get ticketRoleAdmin => 'مشرف';

  @override
  String get ticketRoleMember => 'تصريح';

  @override
  String get yourParticipations => 'مشاركاتك';

  @override
  String get manageAll => 'إدارة الكل';

  @override
  String get participationsEmpty => 'لا مشاركات قادمة. اكتشف شيئاً ممتعاً.';

  @override
  String participationsMore(int count) {
    return '+ $count أخرى · فعالياتي';
  }

  @override
  String get confirmedAttendance => 'مؤكد';

  @override
  String get requestPendingShort => 'قيد الانتظار';

  @override
  String get pendingReviewNotice => 'بانتظار موافقة المضيف على طلبك.';

  @override
  String get pendingRequestsSection => 'بانتظار القبول';

  @override
  String get joinedEventsSection => 'الفعاليات التي انضممت إليها';

  @override
  String get hostingEventsSection => 'تستضيفها';

  @override
  String get hostingEmpty => 'لم تنشئ أي فعاليات بعد.';

  @override
  String get joinedEmpty => 'لم تنضم إلى أي فعاليات بعد.';

  @override
  String get participationHost => 'المضيف';

  @override
  String get stepRequested => 'طُلب';

  @override
  String get stepAccepted => 'قُبل';

  @override
  String get stepGathering => 'التجمع';

  @override
  String get weatherSetLocation => 'حدد موقعك';

  @override
  String get weatherSetLocationHint =>
      'اختر مدينة لعرض الطقس المحلي في الرئيسية.';

  @override
  String get requestToJoin => 'طلب الانضمام';

  @override
  String get updateAvailable => 'يتوفر تحديث';

  @override
  String get updateAvailableBody => 'يتوفر إصدار جديد من التطبيق.';

  @override
  String get updateNow => 'تحديث';

  @override
  String get updateLater => 'لاحقاً';

  @override
  String get searching => 'جاري البحث';

  @override
  String get chatLike => 'إعجاب';

  @override
  String get chatDislike => 'عدم إعجاب';

  @override
  String get reactionsTitle => 'التفاعلات';

  @override
  String get likedBy => 'أعجب بها';

  @override
  String get dislikedBy => 'لم يعجب بها';

  @override
  String get noLikesYet => 'لا إعجابات بعد.';

  @override
  String get noDislikesYet => 'لا عدم إعجاب بعد.';
}
