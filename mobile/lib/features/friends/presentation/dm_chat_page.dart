import 'dart:async';
import 'dart:io';
import 'dart:math';
import 'dart:typed_data';

import 'package:audioplayers/audioplayers.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/identity/user_public_id.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/utils/upload_validator.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/data/chat_realtime_client.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';
import 'package:fun_mobile/features/friends/domain/dms_repository.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import 'package:record/record.dart';

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
  bool _sendFailed = false;
  String? _pendingClientUuid;
  DmMessage? _replyingTo;
  String? _mediaPath;
  String? _voicePath;
  int? _voiceDurationMs;
  int _recordingDurationMs = 0;
  bool _recording = false;
  bool _recordingPaused = false;
  bool _previewPlaying = false;
  String? _peerName;
  String? _peerPublicId;
  final _text = TextEditingController();
  final _scroll = ScrollController();
  final _recorder = AudioRecorder();
  final _previewPlayer = AudioPlayer();
  Timer? _poll;
  Timer? _recordingTimer;
  CancelToken? _cancel;
  StreamSubscription<DmMessage>? _threadSub;
  ChatRealtimeClient? _realtime;

  @override
  void initState() {
    super.initState();
    _previewPlayer.onPlayerStateChanged.listen((state) {
      if (!mounted) return;
      setState(() => _previewPlaying = state == PlayerState.playing);
    });
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
    _recordingTimer?.cancel();
    _cancel?.cancel();
    final voicePath = _voicePath;
    if (voicePath != null) unawaited(_deleteVoiceFile(voicePath));
    unawaited(_recorder.dispose());
    unawaited(_previewPlayer.dispose());
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
    final result = await ref
        .read(dmsRepositoryProvider)
        .messages(widget.conversationId, cancelToken: _cancel);
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
    if (body.isEmpty && _mediaPath == null && _voicePath == null || _sending) {
      return;
    }
    final voicePath = _voicePath;
    // Reuse client_uuid across retries so Sanctum idempotency returns the
    // same message instead of uploading duplicate media.
    final clientUuid = _pendingClientUuid ?? _clientUuid();
    setState(() {
      _sending = true;
      _sendFailed = false;
      _pendingClientUuid = clientUuid;
    });
    final result = await ref
        .read(dmsRepositoryProvider)
        .postMessage(
          widget.conversationId,
          body,
          clientUuid: clientUuid,
          replyToId: _replyingTo?.id,
          mediaPath: voicePath ?? _mediaPath,
          mediaKind: voicePath != null
              ? 'voice'
              : (_mediaPath == null ? null : 'image'),
          durationMs: voicePath == null ? null : _voiceDurationMs,
        );
    if (!mounted) return;
    setState(() => _sending = false);
    if (result.failure != null) {
      setState(() => _sendFailed = true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(failureUserMessage(result.failure!)),
          action: SnackBarAction(
            label: AppLocalizations.of(context).retry,
            onPressed: () {
              if (!_sending) unawaited(_send());
            },
          ),
        ),
      );
      return;
    }
    _text.clear();
    setState(() {
      _replyingTo = null;
      _mediaPath = null;
      _voicePath = null;
      _voiceDurationMs = null;
      _pendingClientUuid = null;
      _sendFailed = false;
    });
    if (voicePath != null) unawaited(_deleteVoiceFile(voicePath));
    final posted = result.data;
    if (posted != null) {
      setState(() {
        _messages = [
          for (final m in _messages)
            if (m.id != posted.id &&
                (posted.clientUuid == null ||
                    m.clientUuid != posted.clientUuid))
              m,
          posted,
        ]..sort((a, b) => a.id.compareTo(b.id));
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

  Future<void> _pickImage() async {
    final picked = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      imageQuality: 88,
      maxWidth: 1920,
    );
    if (picked == null) return;
    final validation = await UploadValidator.validateImageFile(picked.path);
    if (!mounted) return;
    if (!validation.ok) {
      final l10n = AppLocalizations.of(context);
      final message = switch (validation.errorKey) {
        'uploadTooLarge' => l10n.uploadTooLarge,
        'uploadBadType' => l10n.uploadBadType,
        _ => l10n.uploadMissing,
      };
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(message)));
      return;
    }
    await _cancelVoicePreview();
    if (!mounted) return;
    setState(() => _mediaPath = picked.path);
  }

  Future<void> _toggleRecording() async {
    if (_recording) {
      await _stopRecording();
      return;
    }
    try {
      if (!await _recorder.hasPermission()) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(AppLocalizations.of(context).voiceUnavailable),
          ),
        );
        return;
      }

      await _cancelVoicePreview();
      final path =
          '${Directory.systemTemp.path}/dm_voice_${DateTime.now().microsecondsSinceEpoch}.m4a';
      await _recorder.start(
        const RecordConfig(encoder: AudioEncoder.aacLc),
        path: path,
      );
      if (!mounted) return;
      setState(() {
        _mediaPath = null;
        _recording = true;
        _recordingPaused = false;
        _recordingDurationMs = 0;
      });
      _recordingTimer = Timer.periodic(const Duration(milliseconds: 250), (_) {
        if (!mounted || !_recording || _recordingPaused) return;
        final next = _recordingDurationMs + 250;
        if (next >= 120000) {
          setState(() => _recordingDurationMs = 120000);
          unawaited(_stopRecording());
        } else {
          setState(() => _recordingDurationMs = next);
        }
      });
    } on Exception {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).voiceUnavailable)),
      );
    }
  }

  Future<void> _pauseOrResumeRecording() async {
    if (!_recording) return;
    try {
      if (_recordingPaused) {
        await _recorder.resume();
        if (!mounted) return;
        setState(() => _recordingPaused = false);
      } else {
        await _recorder.pause();
        if (!mounted) return;
        setState(() => _recordingPaused = true);
      }
    } on Exception {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).voiceUnavailable)),
      );
    }
  }

  Future<void> _stopRecording() async {
    if (!_recording) return;
    _recordingTimer?.cancel();
    setState(() {
      _recording = false;
      _recordingPaused = false;
    });
    final path = await _recorder.stop();
    if (!mounted) {
      if (path != null) await _deleteVoiceFile(path);
      return;
    }
    setState(() {
      _voicePath = path;
      _voiceDurationMs = _recordingDurationMs.clamp(1, 120000);
    });
  }

  Future<void> _toggleVoicePreview() async {
    final path = _voicePath;
    if (path == null) return;
    if (_previewPlaying) {
      await _previewPlayer.pause();
    } else {
      await _previewPlayer.play(DeviceFileSource(path));
    }
  }

  Future<void> _cancelVoicePreview() async {
    await _previewPlayer.stop();
    final path = _voicePath;
    if (path != null) await _deleteVoiceFile(path);
    if (!mounted) return;
    setState(() {
      _voicePath = null;
      _voiceDurationMs = null;
      _pendingClientUuid = null;
      _sendFailed = false;
    });
  }

  Future<void> _deleteVoiceFile(String path) async {
    try {
      await File(path).delete();
    } on FileSystemException {
      // The recorder may already have removed an unusable temporary file.
    }
  }

  Future<void> _showMessageActions(DmMessage message) async {
    final l10n = AppLocalizations.of(context);
    final action = await showModalBottomSheet<String>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.reply_rounded),
              title: Text(l10n.reply),
              onTap: () => Navigator.pop(context, 'reply'),
            ),
            ListTile(
              leading: const Icon(Icons.add_reaction_outlined),
              title: Text(l10n.react),
              onTap: () => Navigator.pop(context, 'react'),
            ),
          ],
        ),
      ),
    );
    if (!mounted || action == null) return;
    if (action == 'reply') {
      setState(() => _replyingTo = message);
      return;
    }
    await _showReactionPicker(message);
  }

  Future<void> _showReactionPicker(DmMessage message) async {
    const emojis = ['❤️', '👍', '😂', '😮', '😢', '👏'];
    final emoji = await showModalBottomSheet<String>(
      context: context,
      builder: (context) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Wrap(
            alignment: WrapAlignment.center,
            spacing: 10,
            children: [
              for (final emoji in emojis)
                Semantics(
                  button: true,
                  label: emoji,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(999),
                    onTap: () => Navigator.pop(context, emoji),
                    child: Padding(
                      padding: const EdgeInsets.all(10),
                      child: Text(emoji, style: const TextStyle(fontSize: 26)),
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
    if (!mounted || emoji == null) return;
    final result = await ref
        .read(dmsRepositoryProvider)
        .react(widget.conversationId, message.id, emoji);
    if (!mounted || result.failure == null) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(failureUserMessage(result.failure!))),
    );
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

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final currentId = auth is Authenticated ? auth.user.id : null;

    return Scaffold(
      resizeToAvoidBottomInset: true,
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
          if (UserPublicId.isValid(_peerPublicId))
            TextButton(
              onPressed: () => pushPublicProfile(context, _peerPublicId),
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
                      return _DmBubble(
                        message: message,
                        isMine: isMine,
                        repository: ref.read(dmsRepositoryProvider),
                        onLongPress: () => _showMessageActions(message),
                        onReplyTap: message.replyToId == null
                            ? null
                            : () => _jumpTo(message.replyToId!),
                      );
                    },
                  ),
          ),
          Material(
            color: AppColors.surface,
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsetsDirectional.fromSTEB(12, 8, 12, 12),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (_replyingTo != null)
                      _ComposerReplyBar(
                        message: _replyingTo!,
                        onClose: () => setState(() => _replyingTo = null),
                      ),
                    if (_sendFailed)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                l10n.retry,
                                style: const TextStyle(color: AppColors.danger),
                              ),
                            ),
                            TextButton(
                              onPressed: _sending
                                  ? null
                                  : () => unawaited(_send()),
                              child: Text(l10n.retry),
                            ),
                          ],
                        ),
                      ),
                    if (_mediaPath != null)
                      ListTile(
                        dense: true,
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.image_outlined),
                        title: Text(l10n.imageSelected),
                        trailing: IconButton(
                          tooltip: l10n.cancel,
                          onPressed: () => setState(() {
                            _mediaPath = null;
                            _pendingClientUuid = null;
                            _sendFailed = false;
                          }),
                          icon: const Icon(Icons.close),
                        ),
                      ),
                    if (_voicePath != null)
                      ListTile(
                        dense: true,
                        contentPadding: EdgeInsets.zero,
                        leading: IconButton(
                          tooltip: l10n.voiceMessage,
                          onPressed: _toggleVoicePreview,
                          icon: Icon(
                            _previewPlaying
                                ? Icons.pause_circle_outline
                                : Icons.play_circle_outline,
                          ),
                        ),
                        title: Text(
                          '${l10n.voiceMessage} · ${((_voiceDurationMs ?? 0) / 1000).ceil()}s',
                        ),
                        trailing: IconButton(
                          tooltip: l10n.cancel,
                          onPressed: _cancelVoicePreview,
                          icon: const Icon(Icons.close),
                        ),
                      ),
                    if (_recording)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                '${_recordingPaused ? l10n.voicePause : l10n.voiceMessage} · ${(_recordingDurationMs / 1000).ceil()}s / 120s',
                                style: TextStyle(
                                  color: _recordingPaused
                                      ? AppColors.warning
                                      : AppColors.danger,
                                ),
                              ),
                            ),
                            IconButton(
                              tooltip: _recordingPaused
                                  ? l10n.voiceResume
                                  : l10n.voicePause,
                              onPressed: _pauseOrResumeRecording,
                              icon: Icon(
                                _recordingPaused
                                    ? Icons.play_arrow_rounded
                                    : Icons.pause_rounded,
                              ),
                            ),
                            IconButton(
                              tooltip: l10n.voiceStop,
                              onPressed: _stopRecording,
                              icon: const Icon(Icons.stop_rounded),
                            ),
                          ],
                        ),
                      ),
                    Row(
                      children: [
                        IconButton(
                          tooltip: l10n.attachmentAdd,
                          onPressed: _sending || _recording ? null : _pickImage,
                          icon: const Icon(Icons.image_outlined),
                        ),
                        IconButton(
                          tooltip: l10n.voiceMessage,
                          onPressed: _sending ? null : _toggleRecording,
                          icon: Icon(
                            _recording
                                ? Icons.stop_circle_outlined
                                : Icons.mic_none_rounded,
                            color: _recording ? AppColors.danger : null,
                          ),
                        ),
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
                          onPressed: _sending || _recording ? null : _send,
                          icon: _sending
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.send_rounded),
                        ),
                      ],
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
  const _DmBubble({
    required this.message,
    required this.isMine,
    required this.repository,
    required this.onLongPress,
    this.onReplyTap,
  });

  final DmMessage message;
  final bool isMine;
  final DmsRepository repository;
  final VoidCallback onLongPress;
  final VoidCallback? onReplyTap;

  @override
  Widget build(BuildContext context) {
    final locale = Localizations.localeOf(context).toString();
    final when = message.createdAt != null
        ? DateFormat('h:mm a', locale).format(message.createdAt!.toLocal())
        : '';

    return Align(
      alignment: isMine
          ? AlignmentDirectional.centerEnd
          : AlignmentDirectional.centerStart,
      child: GestureDetector(
        onLongPress: onLongPress,
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
              if (message.messageType == 'image' &&
                  message.mediaUrl != null) ...[
                _DmMediaImage(messageId: message.id),
                if (message.body.isNotEmpty) const SizedBox(height: 7),
              ],
              if (message.messageType == 'voice')
                _DmVoicePlayer(
                  messageId: message.id,
                  durationMs: message.mediaDurationMs,
                  repository: repository,
                  foregroundColor: isMine ? Colors.white : AppColors.text,
                ),
              if (message.body.isNotEmpty)
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
      ),
    );
  }
}

class _ComposerReplyBar extends StatelessWidget {
  const _ComposerReplyBar({required this.message, required this.onClose});

  final DmMessage message;
  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsetsDirectional.fromSTEB(10, 7, 4, 7),
      decoration: BoxDecoration(
        color: AppColors.primarySoft,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              '${l10n.reply}: ${message.body}',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              textDirection: bidiTextDirection(message.body),
            ),
          ),
          IconButton(
            tooltip: l10n.cancel,
            onPressed: onClose,
            icon: const Icon(Icons.close, size: 18),
          ),
        ],
      ),
    );
  }
}

class _DmVoicePlayer extends StatefulWidget {
  const _DmVoicePlayer({
    required this.messageId,
    required this.durationMs,
    required this.repository,
    required this.foregroundColor,
  });

  final int messageId;
  final int? durationMs;
  final DmsRepository repository;
  final Color foregroundColor;

  @override
  State<_DmVoicePlayer> createState() => _DmVoicePlayerState();
}

class _DmVoicePlayerState extends State<_DmVoicePlayer> {
  final _player = AudioPlayer();
  bool _loading = false;
  bool _loaded = false;
  bool _playing = false;

  @override
  void initState() {
    super.initState();
    _player.onPlayerStateChanged.listen((state) {
      if (!mounted) return;
      setState(() => _playing = state == PlayerState.playing);
    });
  }

  @override
  void dispose() {
    unawaited(_player.dispose());
    super.dispose();
  }

  Future<void> _toggle() async {
    if (_playing) {
      await _player.pause();
      return;
    }
    if (!_loaded) {
      setState(() => _loading = true);
      final result = await widget.repository.media(widget.messageId);
      if (!mounted) return;
      if (result.data == null) {
        setState(() => _loading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              result.failure?.message ??
                  AppLocalizations.of(context).voiceUnavailable,
            ),
          ),
        );
        return;
      }
      await _player.setSourceBytes(Uint8List.fromList(result.data!));
      if (!mounted) return;
      setState(() {
        _loaded = true;
        _loading = false;
      });
    }
    await _player.resume();
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        IconButton(
          tooltip: AppLocalizations.of(context).voiceMessage,
          visualDensity: VisualDensity.compact,
          onPressed: _loading ? null : _toggle,
          color: widget.foregroundColor,
          icon: _loading
              ? const SizedBox.square(
                  dimension: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : Icon(
                  _playing
                      ? Icons.pause_circle_outline
                      : Icons.play_circle_outline,
                ),
        ),
        Text(
          widget.durationMs == null
              ? AppLocalizations.of(context).voiceMessage
              : '${(widget.durationMs! / 1000).ceil()}s',
          style: TextStyle(color: widget.foregroundColor),
        ),
      ],
    );
  }
}

class _DmMediaImage extends ConsumerStatefulWidget {
  const _DmMediaImage({required this.messageId});

  final int messageId;

  @override
  ConsumerState<_DmMediaImage> createState() => _DmMediaImageState();
}

class _DmMediaImageState extends ConsumerState<_DmMediaImage> {
  Uint8List? _bytes;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final result = await ref
        .read(dmsRepositoryProvider)
        .media(widget.messageId);
    if (!mounted || result.data == null) return;
    setState(() => _bytes = Uint8List.fromList(result.data!));
  }

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: SizedBox(
        width: 220,
        height: 160,
        child: _bytes == null
            ? const ColoredBox(
                color: AppColors.primarySoft,
                child: Center(child: CircularProgressIndicator()),
              )
            : Image.memory(_bytes!, fit: BoxFit.cover),
      ),
    );
  }
}
