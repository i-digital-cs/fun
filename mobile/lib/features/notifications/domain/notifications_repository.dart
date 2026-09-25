import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/notifications/domain/app_notification.dart';

abstract class NotificationsRepository {
  Future<({Failure? failure, List<AppNotification> data, int unreadCount})>
  list({int perPage = 30});

  Future<({Failure? failure, int unreadCount})> unreadCount();

  Future<({Failure? failure, AppNotification? data})> markRead(String id);

  Future<({Failure? failure, void data})> markAllRead();

  Future<({Failure? failure, void data})> registerDeviceToken({
    required String token,
    String platform = 'android',
  });

  Future<({Failure? failure, void data})> forgetDeviceToken(String token);
}
