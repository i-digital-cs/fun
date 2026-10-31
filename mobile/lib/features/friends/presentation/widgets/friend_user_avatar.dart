import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';

/// Shared circular avatar for friend / people suggestion surfaces.
class FriendUserAvatar extends StatelessWidget {
  const FriendUserAvatar({
    super.key,
    required this.user,
    this.size = 40,
  });

  final FriendUser user;
  final double size;

  @override
  Widget build(BuildContext context) {
    final photo = user.profileImagePath;
    return SizedBox(
      width: size,
      height: size,
      child: photo != null && photo.isNotEmpty
          ? ClipOval(
              child: AppNetworkImage(
                url: photo,
                logicalWidth: size,
                logicalHeight: size,
                fit: BoxFit.cover,
              ),
            )
          : CircleAvatar(
              radius: size / 2,
              backgroundColor: AppColors.primary.withValues(alpha: 0.12),
              foregroundColor: AppColors.primary,
              child: Text(
                user.initials,
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: size * 0.28,
                ),
              ),
            ),
    );
  }
}
