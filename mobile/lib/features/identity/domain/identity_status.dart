import 'package:equatable/equatable.dart';

class IdentityDocument extends Equatable {
  const IdentityDocument({
    required this.type,
    required this.uploaded,
    this.id,
    this.url,
    this.mimeType,
    this.size,
  });

  final String type;
  final int? id;
  final String? url;
  final bool uploaded;
  final String? mimeType;
  final int? size;

  factory IdentityDocument.fromJson(Map<String, dynamic> json) {
    return IdentityDocument(
      type: json['type'] as String? ?? '',
      id: json['id'] as int?,
      url: json['url'] as String?,
      uploaded: json['uploaded'] as bool? ?? false,
      mimeType: json['mime_type'] as String?,
      size: json['size'] as int?,
    );
  }

  @override
  List<Object?> get props => [type, id, url, uploaded, mimeType, size];
}

class IdentityStatus extends Equatable {
  const IdentityStatus({
    required this.status,
    required this.emailVerified,
    required this.needsEmailVerification,
    required this.needsUpload,
    required this.canUpload,
    required this.needsIdentityDocuments,
    required this.resubmitRequired,
    required this.hasIdentityDocuments,
    required this.documents,
  });

  final String status;
  final bool emailVerified;
  final bool needsEmailVerification;
  final bool needsUpload;
  final bool canUpload;
  final bool needsIdentityDocuments;
  final bool resubmitRequired;
  final bool hasIdentityDocuments;
  final List<IdentityDocument> documents;

  /// Chip key: pending_upload | submitted | resubmit_required
  String get statusChip {
    if (resubmitRequired) return 'resubmit_required';
    if (canUpload || needsUpload) return 'pending_upload';
    return 'submitted';
  }

  factory IdentityStatus.fromJson(Map<String, dynamic> json) {
    final docs = json['documents'];
    return IdentityStatus(
      status: json['status'] as String? ?? 'pending_documents',
      emailVerified: json['email_verified'] as bool? ?? false,
      needsEmailVerification:
          json['needs_email_verification'] as bool? ?? false,
      needsUpload: json['needs_upload'] as bool? ?? false,
      canUpload: json['can_upload'] as bool? ?? false,
      needsIdentityDocuments:
          json['needs_identity_documents'] as bool? ?? false,
      resubmitRequired: json['resubmit_required'] as bool? ?? false,
      hasIdentityDocuments: json['has_identity_documents'] as bool? ?? false,
      documents: docs is List
          ? docs
              .whereType<Map<String, dynamic>>()
              .map(IdentityDocument.fromJson)
              .toList()
          : const [],
    );
  }

  @override
  List<Object?> get props => [
        status,
        emailVerified,
        needsEmailVerification,
        needsUpload,
        canUpload,
        needsIdentityDocuments,
        resubmitRequired,
        hasIdentityDocuments,
        documents,
      ];
}
