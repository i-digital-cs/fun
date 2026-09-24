import 'package:equatable/equatable.dart';

class User extends Equatable {
  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.status,
    required this.locale,
    this.publicId,
    this.platformRole,
    this.cityId,
    this.distanceKm,
    this.cityName,
    this.profileImagePath,
    this.emailVerified = true,
    this.needsEmailVerification = false,
    this.needsIdentityDocuments = false,
    this.hasIdentityDocuments = false,
    this.identityResubmitRequired = false,
    this.canParticipateFlag,
    this.hasPassword = true,
  });

  final int id;
  final String name;
  final String email;
  final String status;
  final String locale;
  final String? publicId;
  final String? platformRole;
  final int? cityId;
  final int? distanceKm;
  final String? cityName;
  final String? profileImagePath;
  final bool emailVerified;
  final bool needsEmailVerification;
  final bool needsIdentityDocuments;
  final bool hasIdentityDocuments;
  final bool identityResubmitRequired;
  /// When non-null, API `can_participate` wins; else derived from status.
  final bool? canParticipateFlag;
  final bool hasPassword;

  factory User.fromJson(Map<String, dynamic> json) {
    final city = json['city'];
    final emailVerified = json['email_verified'] as bool? ??
        (json['email_verified_at'] != null);
    return User(
      id: json['id'] as int,
      publicId: json['public_id'] as String?,
      name: json['name'] as String? ?? '',
      email: json['email'] as String? ?? '',
      status: json['status'] as String? ?? 'active',
      locale: json['locale'] as String? ?? 'en',
      platformRole: json['platform_role'] as String?,
      cityId: json['city_id'] as int?,
      distanceKm: json['distance_km'] as int?,
      cityName: city is Map<String, dynamic> ? city['name'] as String? : null,
      profileImagePath: json['profile_image_path'] as String?,
      emailVerified: emailVerified,
      needsEmailVerification:
          json['needs_email_verification'] as bool? ?? !emailVerified,
      needsIdentityDocuments:
          json['needs_identity_documents'] as bool? ?? false,
      hasIdentityDocuments: json['has_identity_documents'] as bool? ?? false,
      identityResubmitRequired:
          json['identity_resubmit_required'] as bool? ?? false,
      canParticipateFlag: json['can_participate'] as bool?,
      hasPassword: json['has_password'] as bool? ?? true,
    );
  }

  String get firstName {
    final parts = name.trim().split(RegExp(r'\s+'));
    return parts.isEmpty ? '' : parts.first;
  }

  String get initials {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return '?';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return '${parts.first[0]}${parts.last[0]}'.toUpperCase();
  }

  bool get canParticipate =>
      canParticipateFlag ?? (status == 'active' && emailVerified);

  bool get needsIdentity =>
      needsIdentityDocuments || status == 'pending_documents';

  bool get isDeactivated => status == 'deactivated';

  /// Post-auth / gate target per FLUTTER_WEB_PARITY §2A.
  String get routeAfterAuth {
    if (isDeactivated) return '/account/deactivated';
    if (needsEmailVerification || !emailVerified) return '/email/verify';
    if (needsIdentity) return '/identity';
    return '/home';
  }

  User copyWith({
    int? id,
    String? name,
    String? email,
    String? status,
    String? locale,
    String? publicId,
    String? platformRole,
    int? cityId,
    int? distanceKm,
    String? cityName,
    String? profileImagePath,
    bool? emailVerified,
    bool? needsEmailVerification,
    bool? needsIdentityDocuments,
    bool? hasIdentityDocuments,
    bool? identityResubmitRequired,
    bool? canParticipateFlag,
    bool? hasPassword,
    bool clearProfileImage = false,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      status: status ?? this.status,
      locale: locale ?? this.locale,
      publicId: publicId ?? this.publicId,
      platformRole: platformRole ?? this.platformRole,
      cityId: cityId ?? this.cityId,
      distanceKm: distanceKm ?? this.distanceKm,
      cityName: cityName ?? this.cityName,
      profileImagePath: clearProfileImage
          ? null
          : (profileImagePath ?? this.profileImagePath),
      emailVerified: emailVerified ?? this.emailVerified,
      needsEmailVerification:
          needsEmailVerification ?? this.needsEmailVerification,
      needsIdentityDocuments:
          needsIdentityDocuments ?? this.needsIdentityDocuments,
      hasIdentityDocuments: hasIdentityDocuments ?? this.hasIdentityDocuments,
      identityResubmitRequired:
          identityResubmitRequired ?? this.identityResubmitRequired,
      canParticipateFlag: canParticipateFlag ?? this.canParticipateFlag,
      hasPassword: hasPassword ?? this.hasPassword,
    );
  }

  @override
  List<Object?> get props => [
        id,
        name,
        email,
        status,
        locale,
        publicId,
        platformRole,
        cityId,
        distanceKm,
        cityName,
        profileImagePath,
        emailVerified,
        needsEmailVerification,
        needsIdentityDocuments,
        hasIdentityDocuments,
        identityResubmitRequired,
        canParticipateFlag,
        hasPassword,
      ];
}
