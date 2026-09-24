import 'package:equatable/equatable.dart';

class BlockedUser extends Equatable {
  const BlockedUser({required this.userId, required this.name});

  final int userId;
  final String name;

  @override
  List<Object?> get props => [userId, name];
}

class BlocksPageResult extends Equatable {
  const BlocksPageResult({
    required this.items,
    required this.currentPage,
    required this.lastPage,
  });

  final List<BlockedUser> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;

  @override
  List<Object?> get props => [items, currentPage, lastPage];
}
