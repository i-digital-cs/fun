import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/data/chat_realtime_client.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

class DmChatPage extends ConsumerStatefulWidget {
  const DmChatPage({super.key, required this.conversationId});

  final int conversationId;

  @override
  ConsumerState<DmChatPage> createState() => _DmChatPageState();
}

class _DmChatPageState extends ConsumerState<DmChatPage> {
  List<DmMessage> _messages = [];
  String? _error;
  bool _loading = true;
  bool _sending = false;
  String? _peerName;
  String? _peerPublicId;
  final _text = TextEditingController();
  final _scroll = ScrollController();
  Timer? _poll;
  CancelToken? _cancel;
  StreamSubscription<DmMessage>? _threadSub;
  ChatRealtimeClient? _realtime;

  @override
  void initState() {
    super.initState();
    _load();
    _loadPeerFromInbox();
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
    unawaited(_realtime?.unsubscribeDm() ?? Future<void>.value());
    _text.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _loadPeerFromInbox() async {
    final result = await ref.read(dmsRepositoryProvider).list();
    if (!mounted || result.failure != null) return;
    for (final c in result.data) {
      if (c.id == widget.conversationId) {
        setState(() {
          _peerName = c.otherUser.name;
          _peerPublicId = c.otherUser.publicId;
        });
        break;
      }
    }
  }

  void _startPoll(Duration interval) {
    _poll?.cancel();
    _poll = Timer.periodic(interval, (_) {
      if (!_sending) _load(silent: true);
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
      for (var i = 0; i < 20 && !client.isConnected; i++) {
        await Future<void>.delayed(const Duration(milliseconds: 150));
      }
    }

    await client.subscribeDm(widget.conversationId);
    await _threadSub?.cancel();
    _threadSub = client.dmThreadPosted.listen(_onLiveMessage);

    if (!mounted) return;
    if (client.isConnected) {
      _startPoll(const Duration(seconds: 60));
    }
  }

  void _onLiveMessage(DmMessage message) {
    if (!mounted) return;
    if (message.conversationId != widget.conversationId) return;
    if (_messages.any((m) => m.id == message.id)) return;

    setState(() {
      _messages = [..._messages, message]..sort((a, b) => a.id.compareTo(b.id));
    });
    _scrollToBottom();
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
    final result = await ref.read(dmsRepositoryProvider).messages(
          widget.conversationId,
          cancelToken: _cancel,
        );
    if (!mounted) return;
    if (result.failure != null && !silent) {
      setState(() {
        _loading = false;
        _error = result.failure!.message;
      });
      return;
    }
    final chronological = List<DmMessage>.from(result.data)
      ..sort((a, b) => a.id.compareTo(b.id));
    final grew = chronological.length > _messages.length;
    setState(() {
      _loading = false;
      _messages = chronological;
      if (_peerName == null && chronological.isNotEmpty) {
        final auth = ref.read(authControllerProvider);
        final me = auth is Authenticated ? auth.user.id : null;
        for (final m in chronological.reversed) {
          if (m.userId != null && m.userId != me) {
            _peerName = m.userName;
            _peerPublicId = m.userPublicId;
            break;
          }
        }
      }
    });
    if (grew || !silent) _scrollToBottom();
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
        .read(dmsRepositoryProvider)
        .postMessage(widget.conversationId, body);
    if (!mounted) return;
    setState(() => _sending = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    _text.clear();
    final posted = result.data;
    if (posted != null && !_messages.any((m) => m.id == posted.id)) {
      setState(() {
        _messages = [..._messages, posted]..sort((a, b) => a.id.compareTo(b.id));
      });
      _scrollToBottom();
    } else {
      await _load();
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
            Text(l10n.friendsDirectChat),
            if (_peerName != null)
              Text(
                _peerName!,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textDirection: bidiTextDirection(_peerName),
                style: const TextStyle(fontSize: 12, color: AppColors.muted),
              ),
          ],
        ),
        actions: [
          if (_peerPublicId != null)
            TextButton(
              onPressed: () => context.push('/u/$_peerPublicId'),
              child: Text(l10n.publicProfile),
            ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: _loading
                ? const Center(child: FunLoader())
                : _error != null
                ? Center(child: Text(_error!))
                : _messages.isEmpty
                ? Center(
                    child: Text(
                      l10n.dmEmptyThread,
                      style: const TextStyle(color: AppColors.muted),
                    ),
                  )
                : ListView.builder(
                    controller: _scroll,
                    padding: const EdgeInsets.all(12),
                    itemCount: _messages.length,
                    itemBuilder: (context, index) {
                      final message = _messages[index];
                      final isMine =
                          message.userId != null && message.userId == currentId;
                      return _DmBubble(message: message, isMine: isMine);
                    },
                  ),
          ),
          Material(
            color: AppColors.surface,
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _text,
                        minLines: 1,
                        maxLines: 4,
                        textInputAction: TextInputAction.send,
                        onSubmitted: (_) => _send(),
                        decoration: InputDecoration(
                          hintText: l10n.messageHint,
                          filled: true,
                          fillColor: AppColors.canvas,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                            borderSide: const BorderSide(
                              color: AppColors.borderSoft,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton.filled(
                      tooltip: l10n.send,
                      onPressed: _sending ? null : _send,
                      icon: _sending
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.send_rounded),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _DmBubble extends StatelessWidget {
  const _DmBubble({required this.message, required this.isMine});

  final DmMessage message;
  final bool isMine;

  @override
  Widget build(BuildContext context) {
    final locale = Localizations.localeOf(context).toString();
    final when = message.createdAt != null
        ? DateFormat('h:mm a', locale).format(message.createdAt!.toLocal())
        : '';

    return Align(
      alignment: isMine ? AlignmentDirectional.centerEnd : AlignmentDirectional.centerStart,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.sizeOf(context).width * 0.78,
        ),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: isMine ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: isMine ? null : Border.all(color: AppColors.borderSoft),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              message.body,
              textDirection: bidiTextDirection(message.body),
              style: TextStyle(
                color: isMine ? Colors.white : AppColors.text,
                fontWeight: FontWeight.w500,
              ),
            ),
            if (when.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                when,
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
      ),
    );
  }
}
