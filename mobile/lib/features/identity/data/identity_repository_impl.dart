import 'dart:typed_data';

import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/identity/data/identity_remote_source.dart';
import 'package:fun_mobile/features/identity/domain/identity_repository.dart';
import 'package:fun_mobile/features/identity/domain/identity_status.dart';

class IdentityRepositoryImpl implements IdentityRepository {
  IdentityRepositoryImpl(this._remote);

  final IdentityRemoteSource _remote;

  @override
  Future<({Failure? failure, IdentityStatus? data})> fetch() => _remote.fetch();

  @override
  Future<({Failure? failure, Uint8List? data})> documentBytes(int id) =>
      _remote.documentBytes(id);

  @override
  Future<({Failure? failure, IdentityStatus? data})> upload({
    required String idFrontPath,
    required String idBackPath,
    required String selfiePath,
  }) => _remote.upload(
    idFrontPath: idFrontPath,
    idBackPath: idBackPath,
    selfiePath: selfiePath,
  );
}
