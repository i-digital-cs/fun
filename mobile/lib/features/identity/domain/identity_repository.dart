import 'dart:typed_data';

import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/identity/domain/identity_status.dart';

/// Identity verification boundary (ARCH-REPO-IDENTITY-001).
abstract class IdentityRepository {
  Future<({Failure? failure, IdentityStatus? data})> fetch();

  Future<({Failure? failure, Uint8List? data})> documentBytes(int id);

  Future<({Failure? failure, IdentityStatus? data})> upload({
    required String idFrontPath,
    required String idBackPath,
    required String selfiePath,
  });
}
