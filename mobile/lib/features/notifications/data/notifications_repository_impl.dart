import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/notifications/data/notifications_remote_source.dart';
import 'package:fun_mobile/features/notifications/domain/app_notification.dart';
import 'package:fun_mobile/features/notifications/domain/notifications_repository.dart';

class NotificationsRepositoryImpl implements NotificationsRepository {
  NotificationsRepositoryImpl(this._remote);

  final NotificationsRemoteSource _remote;

  @override
  Future<({Failure? failure, List<AppNotification> data, int unreadCount})>
  list({int perPage = 30}) => _remote.list(perPage: perPage);

  @override
  Future<({Failure? failure, int unreadCount})> unreadCount() =>
      _remote.unreadCount();

  @override
  Future<({Failure? failure, AppNotification? data})> markRead(String id) =>
      _remote.markRead(id);

  @override
  Future<({Failure? failure, void data})> markAllRead() =>
      _remote.markAllRead();

  @override
  Future<({Failure? failure, void data})> registerDeviceToken({
    required String token,
    String platform = 'android',
  }) => _remote.registerDeviceToken(token: token, platform: platform);

  @override
  Future<({Failure? failure, void data})> forgetDeviceToken(String token) =>
      _remote.forgetDeviceToken(token);
}
