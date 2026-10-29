import 'dart:async';
import 'dart:math';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/data/chat_realtime_client.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/safety/presentation/report_sheet.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

class EventChatPage extends ConsumerStatefulWidget {
  const EventChatPage({super.key, required this.eventId});

  final int eventId;

  @override
  ConsumerState<EventChatPage> createState() => _EventChatPageState();
}

class _EventChatPageState extends ConsumerState<EventChatPage> {
  List<ChatMessage> _messages = [];
  String? _error;
  bool _loading = true;
  bool _sending = false;
  ChatMessage? _replyingTo;
  String? _eventTitle;
  EventCreator? _host;
  int? _ownerId;
  final _text = TextEditingController();
  final _scroll = ScrollController();
  Timer? _poll;
  CancelToken? _cancel;
  StreamSubscription<ChatMessage>? _threadSub;
  StreamSubscription<ChatMessageReaction>? _threadReactSub;
  ChatRealtimeClient? _realtime;

  @override
  void initState() {
    super.initState();
    _load();
    _loadEventMeta();
    _startPoll(const Duration(seconds: 8));
    WidgetsBinding.instance.addPostFrameCallback((_) {
      unawaited(_subscribeLive());
    });
  }

  @override
  void dispose() {
    _poll?.cancel();
    _cancel?.cancel();
    unawaited(_threadSub?.cancel());
    unawaited(_threadReactSub?.cancel());
    unawaited(_realtime?.unsubscribeEventChat() ?? Future<void>.value());
    _text.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _startPoll(Duration interval) {
    _poll?.cancel();
    _poll = Timer.periodic(interval, (_) {
      if (!_sending) _load(silent: true);
    });
  }

  Future<void> _loadEventMeta() async {
    final result = await ref
        .read(eventsRepositoryProvider)
        .show(widget.eventId);
    if (!mounted || result.data == null) return;
    final event = result.data!.event;
    setState(() {
      _eventTitle = event.title;
      _host = event.creator;
      _ownerId = event.createdBy ?? event.creator?.id;
    });
  }

  Future<void> _subscribeLive() async {
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) return;

    final branding = await ref.read(siteBrandingProvider.future);
    final config = branding.broadcast;
    if (!config.enabled) return;

    final client = ref.read(chatRealtimeClientProvider);
    _realtime = client;
    if (!client.isConnected || client.userId != auth.user.id) {
      final started = await client.connect(
        userId: auth.user.id,
        config: config,
      );
      if (!started) return;
      // Wait briefly for socket
      for (var i = 0; i < 20 && !client.isConnected; i++) {
        await Future<void>.delayed(const Duration(milliseconds: 150));
      }
    }

    await client.subscribeEventChat(widget.eventId);
    await _threadSub?.cancel();
    _threadSub = client.threadPosted.listen(_onLiveMessage);
    await _threadReactSub?.cancel();
    _threadReactSub = client.threadReacted.listen(_onLiveReaction);

    if (!mounted) return;
    if (client.isConnected) {
      // Live socket — keep a slow sync as safety net.
      _startPoll(const Duration(seconds: 60));
    }
  }

  void _onLiveMessage(ChatMessage message) {
    if (!mounted) return;
    if (message.eventId != 0 && message.eventId != widget.eventId) return;
    if (_messages.any((m) => m.id == message.id)) return;

    final auth = ref.read(authControllerProvider);
    final currentId = auth is Authenticated ? auth.user.id : null;
    // Own posts are appended after POST; skip duplicate from echo if any.
    if (currentId != null && message.userId == currentId) {
      if (_messages.any((m) => m.id == message.id)) return;
    }

    setState(() {
      _messages = [..._messages, message]..sort((a, b) => a.id.compareTo(b.id));
    });
    _scrollToBottom();
  }

  void _onLiveReaction(ChatMessageReaction reaction) {
    if (!mounted) return;
    if (reaction.eventId != 0 && reaction.eventId != widget.eventId) return;
    final auth = ref.read(authControllerProvider);
    final currentId = auth is Authenticated ? auth.user.id : null;
    setState(() {
      _messages = [
        for (final m in _messages)
          if (m.id == reaction.messageId)
            m.copyWith(
              likesCount: reaction.likesCount,
              dislikesCount: reaction.dislikesCount,
              myReaction: currentId != null && reaction.actorId == currentId
                  ? reaction.myReaction
                  : m.myReaction,
              clearMyReaction:
                  currentId != null &&
                  reaction.actorId == currentId &&
                  reaction.myReaction == null,
            )
          else
            m,
      ];
    });
  }

  Future<void> _react(ChatMessage message, String type) async {
    final next = message.myReaction == type ? null : type;
    final result = await ref
        .read(eventsRepositoryProvider)
        .reactToMessage(widget.eventId, message.id, type: next);
    if (!mounted) return;
    if (result.failure != null || result.data == null) {
      if (result.failure != null) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(result.failure!.message)));
      }
      return;
    }
    final updated = result.data!;
    setState(() {
      _messages = [
        for (final m in _messages)
          if (m.id == message.id)
            m.copyWith(
              likesCount: updated.likesCount,
              dislikesCount: updated.dislikesCount,
              myReaction: updated.myReaction,
              clearMyReaction: updated.myReaction == null,
            )
          else
            m,
      ];
    });
  }

  Future<void> _showReactors(ChatMessage message, String focusType) async {
    final l10n = AppLocalizations.of(context);
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return _ReactionsSheet(
          eventId: widget.eventId,
          messageId: message.id,
          initialTab: focusType,
          likedByLabel: l10n.likedBy,
          dislikedByLabel: l10n.dislikedBy,
          noLikesLabel: l10n.noLikesYet,
          noDislikesLabel: l10n.noDislikesYet,
          title: l10n.reactionsTitle,
          load: () => ref
              .read(eventsRepositoryProvider)
              .messageReactions(widget.eventId, message.id),
        );
      },
    );
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _loading = _messages.isEmpty;
        _error = null;
      });
    }
    _cancel?.cancel();
    _cancel = CancelToken();
    final result = await ref
        .read(eventsRepositoryProvider)
        .messages(widget.eventId, cancelToken: _cancel);
    if (!mounted || result.data == null && result.failure == null) return;
    final next = result.data ?? _messages;
    final chronological = List<ChatMessage>.from(next)
      ..sort((a, b) => a.id.compareTo(b.id));
    final grew = chronological.length > _messages.length;
    final newestChanged =
        chronological.isNotEmpty &&
        (_messages.isEmpty || chronological.last.id != _messages.last.id);
    setState(() {
      _loading = false;
      if (!silent) _error = result.failure?.message;
      if (result.data != null) _messages = chronological;
    });
    if (grew || newestChanged || !silent) _scrollToBottom();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent,
          duration: const Duration(milliseconds: 200),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _send() async {
    final body = _text.text.trim();
    if (body.isEmpty || _sending) return;
    setState(() => _sending = true);
    final result = await ref
        .read(eventsRepositoryProvider)
        .postMessage(
          widget.eventId,
          body,
          replyToId: _replyingTo?.id,
          clientUuid: _clientUuid(),
        );
    if (!mounted) return;
    setState(() => _sending = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(result.failure!.message)));
      return;
    }
    _text.clear();
    setState(() => _replyingTo = null);
    final posted = result.data;
    if (posted != null && !_messages.any((m) => m.id == posted.id)) {
      setState(() {
        _messages = [..._messages, posted]
          ..sort((a, b) => a.id.compareTo(b.id));
      });
      _scrollToBottom();
    } else {
      await _load();
    }
  }

  String _clientUuid() {
    final random = Random.secure();
    String hex(int length) => List.generate(
      length,
      (_) => random.nextInt(16).toRadixString(16),
    ).join();
    return '${hex(8)}-${hex(4)}-4${hex(3)}-'
        '${(8 + random.nextInt(4)).toRadixString(16)}${hex(3)}-${hex(12)}';
  }

  void _jumpTo(int messageId) {
    final index = _messages.indexWhere((message) => message.id == messageId);
    if (index < 0 || !_scroll.hasClients) return;
    final fraction = _messages.length <= 1
        ? 0.0
        : index / (_messages.length - 1);
    _scroll.animateTo(
      _scroll.position.maxScrollExtent * fraction,
      duration: const Duration(milliseconds: 280),
      curve: Curves.easeOut,
    );
  }

  void _openProfile(ChatMessage message) {
    final publicId = message.userPublicId;
    if (publicId == null || publicId.isEmpty) return;
    context.push('/u/$publicId');
  }

  Future<void> _onMessageActions(ChatMessage message) async {
    final l10n = AppLocalizations.of(context);
    final auth = ref.read(authControllerProvider);
    final currentId = auth is Authenticated ? auth.user.id : null;
    final canParticipate = auth is Authenticated && auth.user.canParticipate;
    final isMine = message.userId != null && message.userId == currentId;

    final action = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.reply_rounded),
              title: Text(l10n.reply),
              onTap: () => Navigator.pop(ctx, 'reply'),
            ),
            ListTile(
              leading: const Icon(Icons.copy_outlined),
              title: Text(l10n.copyMessage),
              onTap: () => Navigator.pop(ctx, 'copy'),
            ),
            if (canParticipate && !isMine) ...[
              ListTile(
                leading: const Icon(Icons.flag_outlined),
                title: Text(l10n.reportMessage),
                onTap: () => Navigator.pop(ctx, 'report_message'),
              ),
              if (message.userId != null)
                ListTile(
                  leading: const Icon(Icons.person_off_outlined),
                  title: Text(l10n.report),
                  onTap: () => Navigator.pop(ctx, 'report_user'),
                ),
              if (message.userId != null)
                ListTile(
                  leading: const Icon(Icons.block_outlined),
                  title: Text(l10n.blockUser),
                  onTap: () => Navigator.pop(ctx, 'block'),
                ),
            ],
          ],
        ),
      ),
    );
    if (!mounted || action == null) return;

    switch (action) {
      case 'reply':
        setState(() => _replyingTo = message);
        return;
      case 'copy':
        await Clipboard.setData(ClipboardData(text: message.body));
        if (!mounted) return;
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(l10n.messageCopied)));
      case 'report_message':
        await showReportSheet(
          context,
          reportableType: 'message',
          reportableId: message.id,
        );
      case 'report_user':
        if (message.userId != null) {
          await showReportSheet(
            context,
            reportableType: 'user',
            reportableId: message.userId!,
          );
        }
      case 'block':
        if (message.userId != null) {
          final result = await ref
              .read(safetyRepositoryProvider)
              .block(message.userId!);
          if (!mounted) return;
          if (result.failure != null) {
            ScaffoldMessenger.of(context)
                .showSnackBar(SnackBar(content: Text(result.failure!.message)));
          } else {
            ScaffoldMessenger.of(context)
                .showSnackBar(SnackBar(content: Text(l10n.userBlocked)));
          }
        }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final currentId = auth is Authenticated ? auth.user.id : null;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        titleSpacing: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.chat, style: const TextStyle(fontSize: 17)),
            if (_eventTitle != null)
              Text(
                _eventTitle!,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textDirection: bidiTextDirection(_eventTitle),
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: AppColors.muted,
                ),
              ),
          ],
        ),
        actions: [
          TextButton.icon(
            // NAV-F9: replace chat with detail to avoid Event→Chat→Event duplicate.
            onPressed: () =>
                context.pushReplacement('/events/${widget.eventId}'),
            icon: const Icon(Icons.event_outlined, size: 18),
            label: Text(l10n.viewEvent),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: Column(
        children: [
          if (_host != null)
            Material(
              color: AppColors.surface,
              child: InkWell(
                onTap: () {
                  final publicId = _host!.publicId?.trim();
                  if (publicId != null && publicId.isNotEmpty) {
                    context.push('/u/$publicId');
                  }
                },
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 10),
                  decoration: const BoxDecoration(
                    border: Border(
                      bottom: BorderSide(color: AppColors.borderSoft),
                    ),
                  ),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 14,
                        backgroundColor: AppColors.primary,
                        child: Text(
                          _host!.displayInitials,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          _host!.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 3,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.primarySoft,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          l10n.participationHost,
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w900,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          Expanded(
            child: AsyncBody(
              isLoading: _loading,
              error: _error,
              onRetry: _load,
              isEmpty: _messages.isEmpty,
              empty: Center(
                child: Text(
                  l10n.noMessages,
                  style: const TextStyle(color: AppColors.muted),
                ),
              ),
              child: ListView.builder(
                controller: _scroll,
                padding: const EdgeInsets.fromLTRB(12, 16, 12, 12),
                itemCount: _messages.length,
                itemBuilder: (context, index) {
                  final message = _messages[index];
                  final mine = currentId != null && message.userId == currentId;
                  final isHost =
                      _ownerId != null &&
                      message.userId != null &&
                      message.userId == _ownerId;
                  return ChatBubble(
                    message: message,
                    isMine: mine,
                    isHost: isHost,
                    hostLabel: l10n.participationHost,
                    likeLabel: l10n.chatLike,
                    dislikeLabel: l10n.chatDislike,
                    onReact: message.isSystem
                        ? null
                        : (type) => _react(message, type),
                    onShowReactors: message.isSystem
                        ? null
                        : (type) => _showReactors(message, type),
                    onAvatarTap: message.isSystem
                        ? null
                        : () => _openProfile(message),
                    onLongPress: message.isSystem
                        ? null
                        : () => _onMessageActions(message),
                    onReplyTap: message.replyToId == null
                        ? null
                        : () => _jumpTo(message.replyToId!),
                  );
                },
              ),
            ),
          ),
          ChatComposer(
            controller: _text,
            sending: _sending,
            onSend: _send,
            replyingTo: _replyingTo,
            onCancelReply: () => setState(() => _replyingTo = null),
          ),
        ],
      ),
    );
  }
}

class ChatBubble extends StatelessWidget {
  const ChatBubble({
    super.key,
    required this.message,
    required this.isMine,
    this.isHost = false,
    this.hostLabel,
    this.likeLabel,
    this.dislikeLabel,
    this.onReact,
    this.onShowReactors,
    this.onLongPress,
    this.onAvatarTap,
    this.onReplyTap,
  });

  final ChatMessage message;
  final bool isMine;
  final bool isHost;
  final String? hostLabel;
  final String? likeLabel;
  final String? dislikeLabel;
  final ValueChanged<String>? onReact;
  final ValueChanged<String>? onShowReactors;
  final VoidCallback? onLongPress;
  final VoidCallback? onAvatarTap;
  final VoidCallback? onReplyTap;

  @override
  Widget build(BuildContext context) {
    final locale = Localizations.localeOf(context).toString();
    final time = message.createdAt != null
        ? DateFormat('h:mm a', locale).format(message.createdAt!.toLocal())
        : '';

    if (message.isSystem) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Center(
          child: Text(
            message.body,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.muted,
              fontStyle: FontStyle.italic,
            ),
          ),
        ),
      );
    }

    final myReaction = message.myReaction;
    final Color bubbleColor;
    final Color? bubbleBorder;
    if (myReaction == 'like') {
      bubbleColor = isMine ? AppColors.success : const Color(0xFFECFDF5);
      bubbleBorder = isMine ? null : AppColors.success.withValues(alpha: 0.45);
    } else if (myReaction == 'dislike') {
      bubbleColor = isMine ? AppColors.danger : const Color(0xFFFFF1F2);
      bubbleBorder = isMine ? null : AppColors.danger.withValues(alpha: 0.4);
    } else {
      bubbleColor = isMine ? AppColors.primary : AppColors.surface;
      bubbleBorder = isMine
          ? null
          : (isHost
                ? AppColors.primary.withValues(alpha: 0.35)
                : AppColors.borderSoft);
    }

    final bubble = Flexible(
      child: InkWell(
        onLongPress: onLongPress,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 8),
          decoration: BoxDecoration(
            color: bubbleColor,
            borderRadius: BorderRadius.only(
              topLeft: const Radius.circular(18),
              topRight: const Radius.circular(18),
              bottomLeft: Radius.circular(isMine ? 18 : 4),
              bottomRight: Radius.circular(isMine ? 4 : 18),
            ),
            border: bubbleBorder == null
                ? null
                : Border.all(color: bubbleBorder),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (message.replyTo != null)
                InkWell(
                  onTap: onReplyTap,
                  child: Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 7),
                    padding: const EdgeInsetsDirectional.fromSTEB(9, 6, 9, 6),
                    decoration: BoxDecoration(
                      color: isMine
                          ? Colors.white.withValues(alpha: 0.14)
                          : AppColors.primarySoft,
                      borderRadius: BorderRadius.circular(9),
                    ),
                    child: Text(
                      '${message.replyTo!.userName ?? ''}\n${message.replyTo!.body}',
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                      textDirection: bidiTextDirection(message.replyTo!.body),
                      style: TextStyle(
                        fontSize: 11,
                        color: isMine ? Colors.white : AppColors.primaryInk,
                      ),
                    ),
                  ),
                ),
              if (!isMine && message.userName != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: GestureDetector(
                    onTap: onAvatarTap,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Flexible(
                          child: Text(
                            message.userName!,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 12,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                        if (isHost && hostLabel != null) ...[
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.primarySoft,
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              hostLabel!,
                              style: const TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.w900,
                                color: AppColors.primary,
                                letterSpacing: 0.3,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                )
              else if (isMine && isHost && hostLabel != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Align(
                    alignment: AlignmentDirectional.centerEnd,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.22),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        hostLabel!,
                        style: const TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                          letterSpacing: 0.3,
                        ),
                      ),
                    ),
                  ),
                ),
              Text(
                message.body,
                style: TextStyle(
                  height: 1.4,
                  color: isMine ? Colors.white : AppColors.text,
                ),
              ),
              if (!message.isSystem && onReact != null) ...[
                const SizedBox(height: 6),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _ReactionChip(
                      emoji: '👍',
                      count: message.likesCount,
                      active: message.myReaction == 'like',
                      isMine: isMine,
                      tooltip: likeLabel ?? 'Like',
                      onTap: () => onReact!('like'),
                      onCountTap: onShowReactors == null
                          ? null
                          : () => onShowReactors!('like'),
                    ),
                    const SizedBox(width: 6),
                    _ReactionChip(
                      emoji: '👎',
                      count: message.dislikesCount,
                      active: message.myReaction == 'dislike',
                      isMine: isMine,
                      tooltip: dislikeLabel ?? 'Dislike',
                      onTap: () => onReact!('dislike'),
                      onCountTap: onShowReactors == null
                          ? null
                          : () => onShowReactors!('dislike'),
                    ),
                    if (time.isNotEmpty) ...[
                      const SizedBox(width: 8),
                      Text(
                        time,
                        style: TextStyle(
                          fontSize: 10,
                          color: isMine
                              ? Colors.white.withValues(alpha: 0.75)
                              : AppColors.muted,
                        ),
                      ),
                    ],
                  ],
                ),
              ] else if (time.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Align(
                    alignment: AlignmentDirectional.centerEnd,
                    child: Text(
                      time,
                      style: TextStyle(
                        fontSize: 10,
                        color: isMine
                            ? Colors.white.withValues(alpha: 0.75)
                            : AppColors.muted,
                      ),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );

    final avatar = _ChatAvatar(
      name: message.userName,
      imageUrl: message.userProfileImagePath,
      onTap: onAvatarTap,
    );

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        mainAxisAlignment: isMine
            ? MainAxisAlignment.end
            : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: isMine
            ? [bubble, const SizedBox(width: 8), avatar]
            : [avatar, const SizedBox(width: 8), bubble],
      ),
    );
  }
}

class _ReactionChip extends StatelessWidget {
  const _ReactionChip({
    required this.emoji,
    required this.count,
    required this.active,
    required this.isMine,
    required this.tooltip,
    required this.onTap,
    this.onCountTap,
  });

  final String emoji;
  final int count;
  final bool active;
  final bool isMine;
  final String tooltip;
  final VoidCallback onTap;
  final VoidCallback? onCountTap;

  @override
  Widget build(BuildContext context) {
    final bg = active
        ? (isMine
              ? Colors.white.withValues(alpha: 0.28)
              : AppColors.primarySoft)
        : (isMine
              ? Colors.white.withValues(alpha: 0.12)
              : const Color(0xFFF8FAFC));
    final fg = isMine
        ? Colors.white
        : (active ? AppColors.primary : AppColors.muted);

    return Tooltip(
      message: tooltip,
      child: Material(
        color: bg,
        borderRadius: BorderRadius.circular(999),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              InkWell(
                onTap: onTap,
                onLongPress: onCountTap,
                borderRadius: BorderRadius.circular(999),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 4,
                    vertical: 2,
                  ),
                  child: Text(emoji, style: const TextStyle(fontSize: 11)),
                ),
              ),
              InkWell(
                onTap: onCountTap ?? onTap,
                borderRadius: BorderRadius.circular(999),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 4,
                    vertical: 2,
                  ),
                  child: Text(
                    '$count',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      color: fg,
                      decoration: onCountTap != null
                          ? TextDecoration.underline
                          : TextDecoration.none,
                      decorationColor: fg,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ReactionsSheet extends StatefulWidget {
  const _ReactionsSheet({
    required this.eventId,
    required this.messageId,
    required this.initialTab,
    required this.title,
    required this.likedByLabel,
    required this.dislikedByLabel,
    required this.noLikesLabel,
    required this.noDislikesLabel,
    required this.load,
  });

  final int eventId;
  final int messageId;
  final String initialTab;
  final String title;
  final String likedByLabel;
  final String dislikedByLabel;
  final String noLikesLabel;
  final String noDislikesLabel;
  final Future<Result<MessageReactions>> Function() load;

  @override
  State<_ReactionsSheet> createState() => _ReactionsSheetState();
}

class _ReactionsSheetState extends State<_ReactionsSheet> {
  late String _tab = widget.initialTab;
  bool _loading = true;
  String? _error;
  MessageReactions? _data;

  @override
  void initState() {
    super.initState();
    _fetch();
  }

  Future<void> _fetch() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await widget.load();
    if (!mounted) return;
    setState(() {
      _loading = false;
      if (result.failure != null) {
        _error = result.failure!.message;
      } else {
        _data = result.data;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final likes = _data?.likes ?? const <MessageReactor>[];
    final dislikes = _data?.dislikes ?? const <MessageReactor>[];
    final people = _tab == 'dislike' ? dislikes : likes;
    final empty = _tab == 'dislike'
        ? widget.noDislikesLabel
        : widget.noLikesLabel;

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(99),
              ),
            ),
            const SizedBox(height: 12),
            Align(
              alignment: AlignmentDirectional.centerStart,
              child: Text(
                widget.title,
                style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _TabChip(
                    label: '👍 ${widget.likedByLabel} (${likes.length})',
                    active: _tab == 'like',
                    onTap: () => setState(() => _tab = 'like'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _TabChip(
                    label: '👎 ${widget.dislikedByLabel} (${dislikes.length})',
                    active: _tab == 'dislike',
                    onTap: () => setState(() => _tab = 'dislike'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (_loading)
              const Padding(
                padding: EdgeInsets.all(24),
                child: CircularProgressIndicator(),
              )
            else if (_error != null)
              Padding(
                padding: const EdgeInsets.all(16),
                child: Text(_error!, textAlign: TextAlign.center),
              )
            else if (people.isEmpty)
              Padding(
                padding: const EdgeInsets.all(20),
                child: Text(
                  empty,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted),
                ),
              )
            else
              ConstrainedBox(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.sizeOf(context).height * 0.45,
                ),
                child: ListView.separated(
                  shrinkWrap: true,
                  itemCount: people.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 4),
                  itemBuilder: (context, index) {
                    final person = people[index];
                    final publicId = person.publicId?.trim();
                    return ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: CircleAvatar(
                        backgroundColor: AppColors.primary,
                        child: Text(
                          (person.initials?.isNotEmpty ?? false)
                              ? person.initials!
                              : (person.name.isNotEmpty
                                    ? person.name[0].toUpperCase()
                                    : '?'),
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 12,
                          ),
                        ),
                      ),
                      title: Text(
                        person.name,
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                      onTap: publicId != null && publicId.isNotEmpty
                          ? () {
                              Navigator.pop(context);
                              context.push('/u/$publicId');
                            }
                          : null,
                    );
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _TabChip extends StatelessWidget {
  const _TabChip({
    required this.label,
    required this.active,
    required this.onTap,
  });

  final String label;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: active ? AppColors.primarySoft : const Color(0xFFF8FAFC),
      borderRadius: BorderRadius.circular(999),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(999),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            border: Border.all(
              color: active
                  ? AppColors.primary.withValues(alpha: 0.35)
                  : AppColors.borderSoft,
            ),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w800,
              color: active ? AppColors.primary : AppColors.muted,
            ),
          ),
        ),
      ),
    );
  }
}

class _ChatAvatar extends StatelessWidget {
  const _ChatAvatar({this.name, this.imageUrl, this.onTap});

  final String? name;
  final String? imageUrl;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final initial = (name?.trim().isNotEmpty ?? false)
        ? name!.trim()[0].toUpperCase()
        : '?';
    final hasPhoto = imageUrl != null && imageUrl!.isNotEmpty;

    final child = CircleAvatar(
      radius: 16,
      backgroundColor: AppColors.primarySoft,
      child: ClipOval(
        child: hasPhoto
            ? SizedBox(
                width: 32,
                height: 32,
                child: AppNetworkImage(
                  url: imageUrl!,
                  logicalWidth: 32,
                  logicalHeight: 32,
                  maxCacheWidth: 96,
                  maxCacheHeight: 96,
                  placeholder: Center(
                    child: Text(
                      initial,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.primary,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  error: Center(
                    child: Text(
                      initial,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.primary,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ),
              )
            : Text(
                initial,
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.primary,
                  fontWeight: FontWeight.w800,
                ),
              ),
      ),
    );

    if (onTap == null) return child;
    return InkWell(
      onTap: onTap,
      customBorder: const CircleBorder(),
      child: child,
    );
  }
}

class ChatComposer extends StatelessWidget {
  const ChatComposer({
    super.key,
    required this.controller,
    required this.sending,
    required this.onSend,
    this.replyingTo,
    this.onCancelReply,
  });

  final TextEditingController controller;
  final bool sending;
  final VoidCallback onSend;
  final ChatMessage? replyingTo;
  final VoidCallback? onCancelReply;

  static const _ink = Color(0xFF0B0B0F);

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final bottom = MediaQuery.of(context).padding.bottom;

    return Container(
      padding: EdgeInsets.fromLTRB(16, 12, 16, bottom + 12),
      decoration: const BoxDecoration(
        color: Color(0xFFF8FAFC),
        border: Border(top: BorderSide(color: AppColors.borderSoft)),
      ),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: const Color(0x0A0F172A),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (replyingTo != null)
              Container(
                width: double.infinity,
                padding: const EdgeInsetsDirectional.fromSTEB(12, 6, 4, 6),
                decoration: const BoxDecoration(color: AppColors.primarySoft),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        '${l10n.reply}: ${replyingTo!.body}',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        textDirection: bidiTextDirection(replyingTo!.body),
                      ),
                    ),
                    IconButton(
                      tooltip: l10n.cancel,
                      onPressed: onCancelReply,
                      icon: const Icon(Icons.close, size: 18),
                    ),
                  ],
                ),
              ),
            TextField(
              controller: controller,
              minLines: 1,
              maxLines: 6,
              style: const TextStyle(color: Colors.white, height: 1.35),
              cursorColor: AppColors.accent,
              textInputAction: TextInputAction.send,
              onSubmitted: (_) => onSend(),
              decoration: InputDecoration(
                hintText: l10n.messageHint,
                hintStyle: TextStyle(
                  color: Colors.white.withValues(alpha: 0.7),
                ),
                filled: true,
                fillColor: _ink,
                contentPadding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
                border: const OutlineInputBorder(
                  borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
                  borderSide: BorderSide.none,
                ),
                enabledBorder: const OutlineInputBorder(
                  borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
                  borderSide: BorderSide.none,
                ),
                focusedBorder: const OutlineInputBorder(
                  borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
            Container(
              height: 48,
              decoration: const BoxDecoration(
                color: _ink,
                borderRadius: BorderRadius.vertical(
                  bottom: Radius.circular(12),
                ),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Row(
                children: [
                  _ComposerIconButton(
                    icon: Icons.attach_file_rounded,
                    onPressed: () {
                      ScaffoldMessenger.of(
                        context,
                      ).showSnackBar(SnackBar(content: Text(l10n.attachSoon)));
                    },
                  ),
                  const SizedBox(width: 8),
                  Container(
                    height: 32,
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(color: const Color(0xFF818CF8)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.chat_bubble_outline,
                          size: 14,
                          color: Color(0xFF818CF8),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          l10n.chatChip,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFFA5B4FC),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Spacer(),
                  _ComposerIconButton(
                    icon: Icons.send_rounded,
                    highlight: true,
                    onPressed: sending ? null : onSend,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ComposerIconButton extends StatelessWidget {
  const _ComposerIconButton({
    required this.icon,
    required this.onPressed,
    this.highlight = false,
  });

  final IconData icon;
  final VoidCallback? onPressed;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.05),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: Icon(
            icon,
            size: 16,
            color: highlight
                ? const Color(0xFFFDBA74)
                : Colors.white.withValues(alpha: 0.45),
          ),
        ),
      ),
    );
  }
}
