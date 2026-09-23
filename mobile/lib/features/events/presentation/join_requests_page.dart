import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/cancel_stop_button.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

class JoinRequestsPage extends ConsumerStatefulWidget {
  const JoinRequestsPage({super.key, required this.eventId});

  final int eventId;

  @override
  ConsumerState<JoinRequestsPage> createState() => _JoinRequestsPageState();
}

class _JoinRequestsPageState extends ConsumerState<JoinRequestsPage> {
  List<JoinRequestItem> _requests = [];
  String? _error;
  bool _loading = true;
  int? _processingId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result =
        await ref.read(eventsRepositoryProvider).joinRequests(widget.eventId);
    if (!mounted) return;
    setState(() {
      _loading = false;
      _error = result.failure?.message;
      _requests = (result.data ?? [])
          .where((r) => r.status == 'pending')
          .toList();
    });
  }

  Future<void> _accept(JoinRequestItem request) async {
    setState(() => _processingId = request.id);
    final result = await ref
        .read(eventsRepositoryProvider)
        .acceptRequest(widget.eventId, request.id);
    if (!mounted) return;
    setState(() => _processingId = null);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  Future<void> _reject(JoinRequestItem request) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmRejectRequest,
    );
    if (confirm != true || !mounted) return;

    setState(() => _processingId = request.id);
    final result = await ref
        .read(eventsRepositoryProvider)
        .rejectRequest(widget.eventId, request.id);
    if (!mounted) return;
    setState(() => _processingId = null);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.viewRequests)),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        isEmpty: _requests.isEmpty,
        empty: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              l10n.noRequests,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.muted),
            ),
          ),
        ),
        child: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: _load,
          child: ListView.separated(
            padding: const EdgeInsets.all(20),
            itemCount: _requests.length,
            separatorBuilder: (_, _) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final request = _requests[index];
              return JoinRequestTile(
                request: request,
                processing: _processingId == request.id,
                onAccept: () => _accept(request),
                onReject: () => _reject(request),
              );
            },
          ),
        ),
      ),
    );
  }
}

class JoinRequestTile extends StatelessWidget {
  const JoinRequestTile({
    super.key,
    required this.request,
    required this.processing,
    required this.onAccept,
    required this.onReject,
  });

  final JoinRequestItem request;
  final bool processing;
  final VoidCallback onAccept;
  final VoidCallback onReject;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: AppColors.primarySoft,
                child: Text(
                  request.userName.isNotEmpty
                      ? request.userName[0].toUpperCase()
                      : '?',
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  request.userName,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: GradientButton(
                  label: l10n.accept,
                  isLoading: processing,
                  onPressed: processing ? null : onAccept,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: CancelStopButton(
                  label: l10n.reject,
                  size: CancelStopSize.sm,
                  onPressed: processing ? null : onReject,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
