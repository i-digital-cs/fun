import 'dart:math' as math;
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/espiral_glow.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Full-screen ticket stage — mirrors Blade `events/ticket.blade.php` + `x-events.ticket`.
class EventTicketPage extends ConsumerStatefulWidget {
  const EventTicketPage({super.key, required this.eventId});

  final int eventId;

  @override
  ConsumerState<EventTicketPage> createState() => _EventTicketPageState();
}

class _EventTicketPageState extends ConsumerState<EventTicketPage>
    with SingleTickerProviderStateMixin {
  EventTicket? _ticket;
  String? _error;
  bool _loading = true;
  late final AnimationController _float;

  @override
  void initState() {
    super.initState();
    _float = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 3),
    )..repeat(reverse: true);
    _load();
  }

  @override
  void dispose() {
    _float.dispose();
    super.dispose();
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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      if (context.canPop()) context.pop();
      return;
    }
    setState(() {
      _loading = false;
      _ticket = result.data;
    });
  }

  String _roleLabel(AppLocalizations l10n, String role) {
    return switch (role) {
      'owner' => l10n.ticketRoleOwner,
      'admin' => l10n.ticketRoleAdmin,
      _ => l10n.ticketRoleMember,
    };
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final ticket = _ticket;
    final reduceMotion = MediaQuery.disableAnimationsOf(context);

    return Scaffold(
      backgroundColor: const Color(0xFF0B1224),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        elevation: 0,
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
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 520),
                    child: _TicketStage(
                      child: Column(
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      l10n.ticketWord,
                                      style: TextStyle(
                                        color: const Color(0xFFC7D2FE)
                                            .withValues(alpha: 0.9),
                                        fontSize: 11,
                                        fontWeight: FontWeight.w800,
                                        letterSpacing: 3.2,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      ticket.title,
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 20,
                                        fontWeight: FontWeight.w900,
                                        letterSpacing: -0.3,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      '${ticket.dateLabel} · ${ticket.venueLine}',
                                      style: TextStyle(
                                        color: Colors.white
                                            .withValues(alpha: 0.72),
                                        fontSize: 13,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 10),
                              TextButton(
                                onPressed: () =>
                                    context.push('/events/${widget.eventId}'),
                                style: TextButton.styleFrom(
                                  foregroundColor: Colors.white,
                                  backgroundColor:
                                      Colors.white.withValues(alpha: 0.12),
                                  side: BorderSide(
                                    color: Colors.white.withValues(alpha: 0.25),
                                  ),
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 14,
                                    vertical: 10,
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                ),
                                child: Text(
                                  l10n.viewEvent,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 13,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 28),
                          AnimatedBuilder(
                            animation: _float,
                            builder: (context, child) {
                              final t = reduceMotion ? 0.0 : _float.value;
                              return Transform.translate(
                                offset: Offset(0, -7 * t),
                                child: Transform.scale(
                                  scale: 1 + 0.02 * t,
                                  child: child,
                                ),
                              );
                            },
                            child: EventTicketPass(
                              ticket: ticket,
                              roleLabel: _roleLabel(l10n, ticket.role),
                              animateHolo: !reduceMotion,
                            ),
                          ),
                          const SizedBox(height: 24),
                          Text(
                            l10n.ticketHelp,
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.65),
                              fontSize: 13,
                              height: 1.45,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
      ),
    );
  }
}

class _TicketStage extends StatelessWidget {
  const _TicketStage({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(18, 28, 18, 28),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(32),
        border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
        gradient: const LinearGradient(
          begin: Alignment(-0.2, -1),
          end: Alignment(0.4, 1),
          colors: [
            Color(0xFF0B1224),
            Color(0xFF151B33),
            Color(0xFF1A1030),
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.45),
            blurRadius: 40,
            offset: const Offset(0, 20),
          ),
        ],
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          const Positioned.fill(
            child: Opacity(
              opacity: 0.4,
              child: EspiralGlow(
                tone: EspiralTone.indigo,
                size: EspiralSize.lg,
                arms: 3,
                dots: 14,
              ),
            ),
          ),
          Positioned(
            top: 40,
            left: 40,
            right: 40,
            height: 120,
            child: DecoratedBox(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(999),
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFFFF6BFE).withValues(alpha: 0.28),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            top: -40,
            left: 20,
            right: 20,
            height: 160,
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFF6366F1).withValues(alpha: 0.35),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            bottom: -20,
            right: -10,
            width: 200,
            height: 160,
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFFF97316).withValues(alpha: 0.22),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
          child,
        ],
      ),
    );
  }
}

/// Portrait holographic perforated pass — Blade `x-events.ticket` / `.event-ticket--stage`.
class EventTicketPass extends StatefulWidget {
  const EventTicketPass({
    super.key,
    required this.ticket,
    required this.roleLabel,
    this.animateHolo = true,
  });

  final EventTicket ticket;
  final String roleLabel;
  final bool animateHolo;

  @override
  State<EventTicketPass> createState() => _EventTicketPassState();
}

class _EventTicketPassState extends State<EventTicketPass>
    with SingleTickerProviderStateMixin {
  late final AnimationController _holo;

  @override
  void initState() {
    super.initState();
    _holo = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 3),
    );
    if (widget.animateHolo) {
      _holo.repeat(reverse: true);
    }
  }

  @override
  void didUpdateWidget(covariant EventTicketPass oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.animateHolo && !_holo.isAnimating) {
      _holo.repeat(reverse: true);
    } else if (!widget.animateHolo && _holo.isAnimating) {
      _holo.stop();
      _holo.value = 0;
    }
  }

  @override
  void dispose() {
    _holo.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    const w = 200.0;
    const h = 360.0;

    return Semantics(
      label: '${l10n.ticketWord}: ${widget.ticket.title}',
      child: SizedBox(
        width: w,
        height: h,
        child: AnimatedBuilder(
          animation: _holo,
          builder: (context, _) {
            return CustomPaint(
              painter: _TicketShellPainter(
                holoShift: widget.animateHolo ? _holo.value : 0,
              ),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Column(
                  children: [
                    // Header
                    SizedBox(
                      height: 72,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          Text(
                            l10n.ticketWord,
                            style: TextStyle(
                              fontSize: 40,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 2,
                              height: 1,
                              color: Colors.white.withValues(alpha: 0.62),
                              shadows: const [
                                Shadow(color: Colors.black54, blurRadius: 0),
                              ],
                            ),
                          ),
                          Positioned(
                            top: 36,
                            right: 10,
                            child: Transform.rotate(
                              angle: 185 * math.pi / 180,
                              child: Text(
                                '✁',
                                style: TextStyle(
                                  fontSize: 22,
                                  color: Colors.white.withValues(alpha: 0.22),
                                  height: 0.5,
                                ),
                              ),
                            ),
                          ),
                          // Soft music notes wash
                          Positioned.fill(
                            child: IgnorePointer(
                              child: Opacity(
                                opacity: 0.18,
                                child: Align(
                                  alignment: const Alignment(0, 0.4),
                                  child: Text(
                                    '♪♪♪♪♪',
                                    style: TextStyle(
                                      fontSize: 42,
                                      color: Colors.grey.shade300,
                                      height: 1,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Body
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: Align(
                          alignment: Alignment.topLeft,
                          child: DefaultTextStyle(
                            style: const TextStyle(
                              color: Color(0xFF0F172A),
                              fontSize: 13,
                              fontWeight: FontWeight.w300,
                              height: 1.35,
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                if (widget.ticket.passLabel.isNotEmpty)
                                  Text(
                                    widget.ticket.passLabel,
                                    style: const TextStyle(
                                      fontStyle: FontStyle.italic,
                                      fontWeight: FontWeight.w400,
                                    ),
                                  ),
                                const SizedBox(height: 4),
                                Text(
                                  widget.ticket.title,
                                  maxLines: 3,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                    fontSize: 14.5,
                                    height: 1.25,
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Text(widget.ticket.dateLabel),
                                Text(widget.ticket.venueLine),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                    // Footer
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                      child: Column(
                        children: [
                          Text.rich(
                            TextSpan(
                              style: const TextStyle(
                                color: Colors.black,
                                fontSize: 13,
                                fontWeight: FontWeight.w300,
                              ),
                              children: [
                                TextSpan(text: '${widget.roleLabel} '),
                                TextSpan(
                                  text: widget.ticket.code,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                    letterSpacing: 0.8,
                                  ),
                                ),
                              ],
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 10),
                          CustomPaint(
                            painter: _BarcodePainter(
                              code: widget.ticket.code,
                              shadowCss: widget.ticket.barcodeShadow,
                            ),
                            size: const Size(90, 32),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

/// Ticket silhouette: holographic fill + top/bottom stamp + side cutouts.
class _TicketShellPainter extends CustomPainter {
  _TicketShellPainter({required this.holoShift});

  final double holoShift;

  @override
  void paint(Canvas canvas, Size size) {
    final path = _ticketPath(size);
    canvas.save();
    canvas.clipPath(path);

    // Base white
    canvas.drawPath(path, Paint()..color = Colors.white);

    // Conic-ish holographic wash
    final holoColors = const [
      Color(0xFFCCCCCC),
      Color(0xFFFF6BFE),
      Color(0xFF00F9F8),
      Color(0xFFDDDDDD),
      Color(0xFF0081FD),
      Color(0xFFEEF0BC),
      Color(0xFF0081FD),
      Color(0xFFFF6BFE),
      Color(0xFF0081FD),
      Color(0xFF01FEFB),
      Color(0xFFCCCCCC),
    ];
    final holoStops = List<double>.generate(
      holoColors.length,
      (i) => i / (holoColors.length - 1),
    );
    final holo = Paint()
      ..shader = ui.Gradient.sweep(
        Offset(size.width * 0.6, size.height * 0.5),
        holoColors,
        holoStops,
        TileMode.clamp,
        holoShift * math.pi * 2,
      );
    canvas.drawRect(Offset.zero & size, holo);

    // Soft top fade
    final fade = Paint()
      ..shader = ui.Gradient.linear(
        Offset.zero,
        Offset(0, size.height),
        const [
          Color(0x88FFEE55),
          Color(0x22000000),
        ],
      );
    canvas.drawRect(Offset.zero & size, fade);

    // Animated color-burn band
    final bandY = size.height * (0.15 + 0.55 * holoShift);
    final bandColors = const [
      Color(0x33FF2200),
      Color(0x88FF0000),
      Color(0x8800FF00),
      Color(0x33FF2200),
    ];
    final band = Paint()
      ..blendMode = BlendMode.difference
      ..shader = ui.Gradient.linear(
        Offset(0, bandY - 40),
        Offset(0, bandY + 80),
        bandColors,
        const [0.0, 0.35, 0.65, 1.0],
      );
    canvas.drawRect(Offset.zero & size, band);

    // Diagonal shimmer sweep
    final sweepX = size.width * (-0.3 + 1.6 * holoShift);
    final shimmer = Paint()
      ..blendMode = BlendMode.softLight
      ..shader = ui.Gradient.linear(
        Offset(sweepX, 0),
        Offset(sweepX + size.width * 0.35, size.height),
        [
          Colors.transparent,
          Colors.white.withValues(alpha: 0.55),
          Colors.transparent,
        ],
        const [0.0, 0.5, 1.0],
      );
    canvas.drawRect(Offset.zero & size, shimmer);

    canvas.restore();

    // Drop shadow around path
    canvas.drawShadow(path, Colors.black.withValues(alpha: 0.45), 18, true);
    // Re-draw fill above shadow edge
    canvas.save();
    canvas.clipPath(path);
    canvas.drawPath(path, Paint()..color = Colors.transparent);
    canvas.restore();
  }

  Path _ticketPath(Size size) {
    const cutY = 70.0;
    const cutR = 8.0;
    final outer = Path()
      ..addRRect(
        RRect.fromRectAndRadius(
          Rect.fromLTWH(0, 0, size.width, size.height),
          const Radius.circular(4),
        ),
      );
    final holes = Path();
    holes.addOval(Rect.fromCircle(center: Offset(0, cutY), radius: cutR));
    holes.addOval(
      Rect.fromCircle(center: Offset(size.width, cutY), radius: cutR),
    );
    const perf = 5.5;
    for (var x = perf; x < size.width; x += perf * 2) {
      holes.addOval(Rect.fromCircle(center: Offset(x, 0), radius: perf - 1));
      holes.addOval(
        Rect.fromCircle(center: Offset(x, size.height), radius: perf - 1),
      );
    }
    return Path.combine(PathOperation.difference, outer, holes);
  }

  @override
  bool shouldRepaint(covariant _TicketShellPainter oldDelegate) =>
      oldDelegate.holoShift != holoShift;
}

class _BarcodePainter extends CustomPainter {
  _BarcodePainter({required this.code, this.shadowCss});

  final String code;
  final String? shadowCss;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.black
      ..style = PaintingStyle.fill;

    final xs = <double>[];
    final css = shadowCss;
    if (css != null && css.isNotEmpty) {
      final re = RegExp(r'(-?\d+(?:\.\d+)?)px');
      for (final m in re.allMatches(css)) {
        xs.add(double.tryParse(m.group(1)!) ?? 0);
      }
    }

    if (xs.isEmpty) {
      final bits = StringBuffer();
      for (final unit in code.toUpperCase().codeUnits) {
        bits.write((unit % 64).toRadixString(2).padLeft(6, '0'));
      }
      final bitStr = bits.toString();
      var x = 0.0;
      for (var i = 0; i < bitStr.length && x < size.width - 2; i++) {
        x += 2;
        if (bitStr[i] == '1') {
          xs.add(x);
          x += 1;
        } else {
          x += 2;
        }
      }
    }

    for (final x in xs) {
      if (x <= 0) continue;
      canvas.drawRect(Rect.fromLTWH(x, 0, 1.2, size.height), paint);
    }
  }

  @override
  bool shouldRepaint(covariant _BarcodePainter oldDelegate) =>
      oldDelegate.code != code || oldDelegate.shadowCss != shadowCss;
}
