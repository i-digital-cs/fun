import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class EventTicketPage extends ConsumerStatefulWidget {
  const EventTicketPage({super.key, required this.eventId});

  final int eventId;

  @override
  ConsumerState<EventTicketPage> createState() => _EventTicketPageState();
}

class _EventTicketPageState extends ConsumerState<EventTicketPage> {
  EventTicket? _ticket;
  String? _error;
  bool _loading = true;

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
        await ref.read(eventsRepositoryProvider).ticket(widget.eventId);
    if (!mounted) return;
    if (result.failure != null) {
      setState(() {
        _loading = false;
        _error = result.failure!.message;
      });
      // 403 / unauthorized → pop back after brief snack
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      if (context.canPop()) {
        context.pop();
      }
      return;
    }
    setState(() {
      _loading = false;
      _ticket = result.data;
    });
  }

  String _roleLabel(AppLocalizations l10n, String role) {
    return switch (role) {
      'owner' => l10n.roleOwner,
      'admin' => l10n.roleAdmin,
      _ => l10n.roleMember,
    };
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final ticket = _ticket;

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        title: Text(l10n.eventTicket),
      ),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        child: ticket == null
            ? const SizedBox.shrink()
            : Center(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(24, 8, 24, 40),
                  child: Column(
                    children: [
                      Text(
                        l10n.ticketWord,
                        style: TextStyle(
                          color: Colors.indigo.shade200,
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 3.2,
                        ),
                      ),
                      const SizedBox(height: 16),
                      EventTicketPass(
                        ticket: ticket,
                        roleLabel: _roleLabel(l10n, ticket.role),
                      ),
                      const SizedBox(height: 20),
                      Text(
                        l10n.ticketHelp,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: Colors.indigo.shade100.withValues(alpha: 0.75),
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
      ),
    );
  }
}

/// Holographic perforated pass — mirrors Blade `x-events.ticket`.
class EventTicketPass extends StatelessWidget {
  const EventTicketPass({
    super.key,
    required this.ticket,
    required this.roleLabel,
  });

  final EventTicket ticket;
  final String roleLabel;

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: _TicketHoloPainter(),
      child: Container(
        width: double.infinity,
        constraints: const BoxConstraints(maxWidth: 360),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: Colors.white.withValues(alpha: 0.35)),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              Color(0xFFEEF2FF),
              Color(0xFFF8FAFC),
              Color(0xFFFFF7ED),
              Color(0xFFE0E7FF),
            ],
          ),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF4F46E5).withValues(alpha: 0.35),
              blurRadius: 40,
              offset: const Offset(0, 18),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(22),
          child: Stack(
            children: [
              Positioned.fill(
                child: CustomPaint(painter: _PerforationPainter()),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(22, 22, 22, 20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Text(
                          'TICKET',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 2.4,
                            color: AppColors.primaryDeep.withValues(alpha: 0.7),
                          ),
                        ),
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.primarySoft,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            ticket.passLabel.isNotEmpty
                                ? ticket.passLabel
                                : roleLabel,
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: AppColors.primaryInk,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    Text(
                      ticket.title,
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w900,
                        letterSpacing: -0.4,
                        color: AppColors.text,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _MetaRow(
                      icon: Icons.calendar_today_outlined,
                      label: ticket.dateLabel,
                    ),
                    const SizedBox(height: 6),
                    _MetaRow(
                      icon: Icons.place_outlined,
                      label: ticket.venueLine,
                    ),
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                roleLabel.toUpperCase(),
                                style: const TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: 1.2,
                                  color: AppColors.muted,
                                ),
                              ),
                              if (ticket.member != null) ...[
                                const SizedBox(height: 2),
                                Text(
                                  ticket.member!.name,
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text(
                              'CODE',
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 1.2,
                                color: AppColors.muted,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              ticket.code,
                              style: const TextStyle(
                                fontSize: 15,
                                fontWeight: FontWeight.w900,
                                fontFamily: 'monospace',
                                letterSpacing: 1.1,
                                color: AppColors.primaryDeep,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    CustomPaint(
                      painter: _BarcodePainter(code: ticket.code),
                      size: const Size(double.infinity, 42),
                      child: const SizedBox(height: 42, width: double.infinity),
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

class _MetaRow extends StatelessWidget {
  const _MetaRow({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 15, color: AppColors.primary),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: AppColors.muted,
            ),
          ),
        ),
      ],
    );
  }
}

class _TicketHoloPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {}

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _PerforationPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFF0F172A)
      ..style = PaintingStyle.fill;
    const radius = 7.0;
    const step = 18.0;
    for (var y = 24.0; y < size.height - 24; y += step) {
      canvas.drawCircle(Offset(-1, y), radius, paint);
      canvas.drawCircle(Offset(size.width + 1, y), radius, paint);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Decorative barcode derived from ticket code (parity with CSS shadow bars).
class _BarcodePainter extends CustomPainter {
  _BarcodePainter({required this.code});

  final String code;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.black
      ..style = PaintingStyle.fill;

    final bits = StringBuffer();
    for (final unit in code.toUpperCase().codeUnits) {
      bits.write((unit % 64).toRadixString(2).padLeft(6, '0'));
    }
    final bitStr = bits.toString();
    var x = 0.0;
    for (var i = 0; i < bitStr.length && x < size.width - 4; i++) {
      x += 2;
      if (bitStr[i] == '1') {
        canvas.drawRect(Rect.fromLTWH(x, 0, 1.4, size.height), paint);
        x += 1;
      } else {
        x += 2;
      }
    }

    // Soft sheen
    final sheen = Paint()
      ..shader = LinearGradient(
        colors: [
          Colors.white.withValues(alpha: 0.0),
          Colors.white.withValues(alpha: 0.35),
          Colors.white.withValues(alpha: 0.0),
        ],
        transform: GradientRotation(math.pi / 6),
      ).createShader(Offset.zero & size);
    canvas.drawRect(Offset.zero & size, sheen);
  }

  @override
  bool shouldRepaint(covariant _BarcodePainter oldDelegate) =>
      oldDelegate.code != code;
}
