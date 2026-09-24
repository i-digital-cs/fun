import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/profile/data/public_profile_remote_source.dart';
import 'package:fun_mobile/features/profile/domain/public_profile.dart';
import 'package:fun_mobile/features/profile/domain/public_profile_repository.dart';

class PublicProfileRepositoryImpl implements PublicProfileRepository {
  PublicProfileRepositoryImpl(this._remote);

  final PublicProfileRemoteSource _remote;

  @override
  Future<({Failure? failure, PublicProfile? data})> show(String publicId) =>
      _remote.show(publicId);
}
