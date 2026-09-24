import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

/// Settings profile / location boundary (ARCH-REPO-PROFILE-001).
abstract class ProfileRepository {
  Future<({Failure? failure, User? data})> updateLocation({
    required int cityId,
    required int distanceKm,
  });

  Future<({Failure? failure, User? data, bool emailChanged})> updateProfile({
    required String name,
    required String email,
    required String sex,
    String? locale,
    String? currentPassword,
    String? password,
    String? passwordConfirmation,
    String? photoPath,
    bool removePhoto = false,
  });
}
