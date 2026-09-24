import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/profile/domain/public_profile.dart';

/// Public user profile boundary (ARCH-REPO-PROFILE-001).
abstract class PublicProfileRepository {
  Future<({Failure? failure, PublicProfile? data})> show(String publicId);
}
