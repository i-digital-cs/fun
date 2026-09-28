import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';

abstract class FriendsRepository {
  Future<({Failure? failure, List<FriendUser> data})> listFriends();

  Future<({Failure? failure, FriendRequestsBundle? data})> listRequests();

  Future<({Failure? failure, List<FriendUser> data})> search(String query);

  Future<({Failure? failure, void data})> requestFriend(String publicId);

  Future<({Failure? failure, void data})> accept(int userId);

  Future<({Failure? failure, void data})> decline(int userId);

  Future<({Failure? failure, void data})> cancel(int userId);

  Future<({Failure? failure, void data})> unfriend(int userId);
}
