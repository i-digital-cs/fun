import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/chat/domain/chat_toast_payload.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Blade / Uiverse-style in-app toast (web `.chat-toast`).
///
/// Chat: SEC-PRIV-002 — never shows message body ([AppLocalizations.newChatMessage]).
/// Join accept / request: title + body from realtime/FCM (same as web).
class ChatToastBanner extends StatelessWidget {
  const ChatToastBanner({
    super.key,
    required this.payload,
    required this.onOpen,
    required this.onDismiss,
  });

  final InAppToastPayload payload;
  final VoidCallback onOpen;
  final VoidCallback onDismiss;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final eyebrow = switch (payload.kind) {
      InAppToastKind.chat || InAppToastKind.dm => l10n.chatToastNew,
      InAppToastKind.joinAccepted =>
        payload.title.isNotEmpty ? payload.title : l10n.joinAcceptedToast,
      InAppToastKind.joinRequested =>
        payload.title.isNotEmpty ? payload.title : l10n.joinRequestedToast,
      InAppToastKind.messageReacted =>
        payload.title.isNotEmpty ? payload.title : l10n.messageReactedToast,
      InAppToastKind.friendRequest =>
        payload.title.isNotEmpty ? payload.title : l10n.friendsTabRequests,
    };
    final name = switch (payload.kind) {
      InAppToastKind.chat || InAppToastKind.dm =>
        payload.title.isEmpty ? l10n.chatToastNew : payload.title,
      InAppToastKind.joinAccepted =>
        payload.title.isNotEmpty ? payload.title : l10n.joinAcceptedToast,
      InAppToastKind.joinRequested =>
        payload.title.isNotEmpty ? payload.title : l10n.joinRequestedToast,
      InAppToastKind.messageReacted =>
        payload.title.isNotEmpty ? payload.title : l10n.messageReactedToast,
      InAppToastKind.friendRequest =>
        payload.eventTitle.isNotEmpty ? payload.eventTitle : payload.title,
    };
    final body = switch (payload.kind) {
      InAppToastKind.chat || InAppToastKind.dm => l10n.newChatMessage,
      InAppToastKind.joinAccepted ||
      InAppToastKind.joinRequested ||
      InAppToastKind.messageReacted ||
      InAppToastKind.friendRequest =>
        payload.body.isNotEmpty ? payload.body : name,
    };

    return Material(
      color: Colors.transparent,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.borderSoft),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF0F172A).withValues(alpha: 0.14),
              blurRadius: 32,
              offset: const Offset(0, 12),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      eyebrow,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: AppColors.text,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: onDismiss,
                    icon: const Icon(Icons.close, size: 16),
                    color: AppColors.muted,
                    visualDensity: VisualDensity.compact,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(
                      minWidth: 32,
                      minHeight: 32,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              InkWell(
                onTap: onOpen,
                borderRadius: BorderRadius.circular(8),
                child: Row(
                  children: [
                    _Avatar(payload: payload),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              color: AppColors.text,
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            body,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Color(0xFF475569),
                              fontSize: 14,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              if (payload.eventTitle.isNotEmpty)
                                Flexible(
                                  child: Text(
                                    payload.eventTitle,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: AppColors.muted,
                                    ),
                                  ),
                                ),
                              if (payload.eventTitle.isNotEmpty)
                                const SizedBox(width: 8),
                              Text(
                                l10n.chatToastJustNow,
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.primary,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.payload});

  final InAppToastPayload payload;

  @override
  Widget build(BuildContext context) {
    if (payload.kind == InAppToastKind.joinAccepted) {
      return Container(
        width: 48,
        height: 48,
        decoration: const BoxDecoration(
          shape: BoxShape.circle,
          gradient: LinearGradient(
            colors: [Color(0xFF10B981), Color(0xFF059669)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: const Icon(Icons.check_rounded, color: Colors.white, size: 26),
      );
    }

    return SizedBox(
      width: 48,
      height: 48,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          ClipOval(
            child: payload.senderPhotoUrl != null
                ? AppNetworkImage(
                    url: payload.senderPhotoUrl!,
                    logicalWidth: 48,
                    logicalHeight: 48,
                    maxCacheWidth: 128,
                    maxCacheHeight: 128,
                  )
                : DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: payload.kind == InAppToastKind.joinRequested
                            ? const [Color(0xFFF59E0B), Color(0xFFEA580C)]
                            : const [AppColors.primary, AppColors.accent],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                    ),
                    child: Center(
                      child: Text(
                        payload.senderInitials,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 18,
                        ),
                      ),
                    ),
                  ),
          ),
          if (payload.kind == InAppToastKind.chat ||
              payload.kind == InAppToastKind.messageReacted)
            PositionedDirectional(
              end: 0,
              bottom: 0,
              child: Container(
                width: 20,
                height: 20,
                decoration: BoxDecoration(
                  color: payload.kind == InAppToastKind.messageReacted
                      ? const Color(0xFFF59E0B)
                      : AppColors.primary,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 2),
                ),
                child: Icon(
                  payload.kind == InAppToastKind.messageReacted
                      ? Icons.thumb_up_alt_rounded
                      : Icons.chat_bubble,
                  size: 10,
                  color: Colors.white,
                ),
              ),
            ),
        ],
      ),
    );
  }
}
