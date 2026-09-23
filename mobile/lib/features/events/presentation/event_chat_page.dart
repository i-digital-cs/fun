import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/safety/presentation/report_sheet.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
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
  final _text = TextEditingController();
  final _scroll = ScrollController();
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    _load();
    _poll = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!_sending) _load(silent: true);
    });
  }

  @override
  void dispose() {
    _poll?.cancel();
    _text.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load({bool silent = false}) async {
    if (!silent) {
      setState(() {
        _loading = _messages.isEmpty;
        _error = null;
      });
    }
    final result =
        await ref.read(eventsRepositoryProvider).messages(widget.eventId);
    if (!mounted) return;
    final next = result.data ?? _messages;
    final grew = next.length > _messages.length;
    setState(() {
      _loading = false;
      if (!silent) _error = result.failure?.message;
      if (result.data != null) _messages = result.data!;
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
        .read(eventsRepositoryProvider)
        .postMessage(widget.eventId, body);
    if (!mounted) return;
    setState(() => _sending = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    _text.clear();
    await _load();
  }

  Future<void> _onMessageActions(ChatMessage message) async {
    final l10n = AppLocalizations.of(context);
    final auth = ref.read(authControllerProvider);
    final currentId = auth is Authenticated ? auth.user.id : null;
    final canParticipate =
        auth is Authenticated && auth.user.canParticipate;
    if (!canParticipate) return;
    if (message.userId != null && message.userId == currentId) return;

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
        ),
      ),
    );
    if (!mounted || action == null) return;

    switch (action) {
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
          final result =
              await ref.read(safetyRemoteSourceProvider).block(message.userId!);
          if (!mounted) return;
          if (result.failure != null) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(result.failure!.message)),
            );
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(l10n.userBlocked)),
            );
          }
        }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.chat)),
      body: Column(
        children: [
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
                padding: const EdgeInsets.all(16),
                itemCount: _messages.length,
                itemBuilder: (context, index) {
                  final message = _messages[index];
                  return ChatBubble(
                    message: message,
                    onLongPress: message.isSystem
                        ? null
                        : () => _onMessageActions(message),
                  );
                },
              ),
            ),
          ),
          ChatComposer(
            controller: _text,
            sending: _sending,
            onSend: _send,
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
    this.onLongPress,
  });

  final ChatMessage message;
  final VoidCallback? onLongPress;

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

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onLongPress: onLongPress,
        borderRadius: BorderRadius.circular(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              radius: 16,
              backgroundColor: AppColors.primarySoft,
              child: Text(
                (message.userName?.isNotEmpty ?? false)
                    ? message.userName![0].toUpperCase()
                    : '?',
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.primary,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.borderSoft),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (message.userName != null)
                      Text(
                        message.userName!,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                          color: AppColors.primary,
                        ),
                      ),
                    Text(message.body, style: const TextStyle(height: 1.4)),
                    if (time.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          time,
                          style: const TextStyle(
                            fontSize: 10,
                            color: AppColors.muted,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class ChatComposer extends StatelessWidget {
  const ChatComposer({
    super.key,
    required this.controller,
    required this.sending,
    required this.onSend,
  });

  final TextEditingController controller;
  final bool sending;
  final VoidCallback onSend;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        12,
        16,
        MediaQuery.of(context).padding.bottom + 12,
      ),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(top: BorderSide(color: AppColors.borderSoft)),
      ),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: controller,
              textInputAction: TextInputAction.send,
              onSubmitted: (_) => onSend(),
              decoration: InputDecoration(
                hintText: l10n.messageHint,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(16),
                  borderSide: const BorderSide(color: AppColors.borderSoft),
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          SizedBox(
            width: 56,
            child: GradientButton(
              label: l10n.send,
              isLoading: sending,
              onPressed: onSend,
            ),
          ),
        ],
      ),
    );
  }
}
