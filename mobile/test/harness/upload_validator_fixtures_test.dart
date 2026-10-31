import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/utils/upload_validator.dart';

void main() {
  test('accepts local jpeg fixture under size limit', () async {
    final dir = await Directory.systemTemp.createTemp('fun_up_ok');
    final file = File('${dir.path}/ok.jpg');
    await file.writeAsBytes(List<int>.filled(1024, 3));
    final result = await UploadValidator.validateImageFile(file.path);
    expect(result.ok, isTrue);
    await dir.delete(recursive: true);
  });

  test('rejects bad extension and missing file', () async {
    final dir = await Directory.systemTemp.createTemp('fun_up_bad');
    final bad = File('${dir.path}/note.txt');
    await bad.writeAsString('nope');
    expect(
      (await UploadValidator.validateImageFile(bad.path)).errorKey,
      'uploadBadType',
    );
    expect(
      (await UploadValidator.validateImageFile('${dir.path}/missing.png'))
          .errorKey,
      'uploadMissing',
    );
    await dir.delete(recursive: true);
  });
}
