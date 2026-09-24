import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/profile/data/profile_remote_source.dart';
import 'package:fun_mobile/features/profile/domain/profile_repository.dart';

class ProfileRepositoryImpl implements ProfileRepository {
  ProfileRepositoryImpl(this._remote);

  final ProfileRemoteSource _remote;

  @override
  Future<({Failure? failure, User? data})> updateLocation({
    required int cityId,
    required int distanceKm,
  }) => _remote.updateLocation(cityId: cityId, distanceKm: distanceKm);

  @override
  Future<({Failure? failure, User? data, bool emailChanged})> updateProfile({
    required String name,
    required String email,
    String? locale,
    String? currentPassword,
    String? password,
    String? passwordConfirmation,
    String? photoPath,
    bool removePhoto = false,
  }) => _remote.updateProfile(
    name: name,
    email: email,
    locale: locale,
    currentPassword: currentPassword,
    password: password,
    passwordConfirmation: passwordConfirmation,
    photoPath: photoPath,
    removePhoto: removePhoto,
  );
}
