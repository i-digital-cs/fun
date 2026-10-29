import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/friends/data/friends_remote_source.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';
import 'package:fun_mobile/features/friends/domain/friends_repository.dart';

class FriendsRepositoryImpl implements FriendsRepository {
  FriendsRepositoryImpl(this._remote);

  final FriendsRemoteSource _remote;

  @override
  Future<({Failure? failure, List<FriendUser> data})> listFriends() =>
      _remote.listFriends();

  @override
  Future<({Failure? failure, FriendRequestsBundle? data})> listRequests() =>
      _remote.listRequests();

  @override
  Future<({Failure? failure, List<FriendUser> data})> search(String query) =>
      _remote.search(query);

  @override
  Future<({Failure? failure, List<FriendUser> data})> peopleSuggestions({
    int limit = 8,
  }) => _remote.peopleSuggestions(limit: limit);

  @override
  Future<({Failure? failure, void data})> requestFriend(String publicId) =>
      _remote.requestFriend(publicId);

  @override
  Future<({Failure? failure, void data})> accept(int userId) =>
      _remote.accept(userId);

  @override
  Future<({Failure? failure, void data})> decline(int userId) =>
      _remote.decline(userId);

  @override
  Future<({Failure? failure, void data})> cancel(int userId) =>
      _remote.cancel(userId);

  @override
  Future<({Failure? failure, void data})> unfriend(int userId) =>
      _remote.unfriend(userId);
}
