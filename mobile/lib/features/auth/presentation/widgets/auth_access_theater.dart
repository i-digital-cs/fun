import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Digit source for the cinematic auth theater (Blade `data-digit-source`).
enum AuthDigitSource { password, email, code, none }

/// Runs the Blade `auth-access.js` sequence:
/// enter → orbit → verify (0–100 ring) → success/confetti, or shake on error.
class AuthAccessTheater extends StatefulWidget {
  const AuthAccessTheater({
    super.key,
    required this.digits,
    required this.authenticate,
    required this.onSuccess,
    required this.onCancel,
    this.title,
    this.subtitle,
    this.verifyingTitle,
    this.verifyingSubtitle,
    this.grantedTitle,
    this.grantedSubtitle,
  });

  /// Exactly 4 character slots (empty string for blank tiles).
  final List<String> digits;
  final Future<String?> Function() authenticate;
  final VoidCallback onSuccess;

  /// Called after shake; [error] is the auth failure message (shown on the form).
  final void Function(String? error) onCancel;
  final String? title;
  final String? subtitle;
  final String? verifyingTitle;
  final String? verifyingSubtitle;
  final String? grantedTitle;
  final String? grantedSubtitle;

  static List<String> digitsFrom({
    required AuthDigitSource source,
    String password = '',
    String email = '',
    String code = '',
  }) {
    switch (source) {
      case AuthDigitSource.password:
        // SEC-PRIV-001: never render password characters (shoulder surfing).
        final filled = password.isEmpty ? 0 : password.length.clamp(1, 4);
        return List<String>.generate(4, (i) => i < filled ? '•' : '');
      case AuthDigitSource.email:
        final local = email.split('@').first;
        final padded = local.padRight(4, '•').substring(0, 4);
        return padded.split('');
      case AuthDigitSource.code:
        final c = code.padRight(4).substring(0, 4);
        return c.split('').map((ch) => ch.trim()).toList();
      case AuthDigitSource.none:
        return ['•', '•', '•', '•'];
    }
  }

  @override
  State<AuthAccessTheater> createState() => _AuthAccessTheaterState();
}

class _AuthAccessTheaterState extends State<AuthAccessTheater>
    with TickerProviderStateMixin {
  late final AnimationController _orbit;
  late final AnimationController _ringSpin;
  late final AnimationController _progress;
  late final AnimationController _pop;
  late final AnimationController _shake;

  _Stage _stage = _Stage.enter;
  int _count = 0;
  late String _title;
  late String _subtitle;
  final _pieces = <_ConfettiPiece>[];
  final _rng = math.Random();

  @override
  void initState() {
    super.initState();
    _orbit = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1350),
    );
    _ringSpin = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1100),
    );
    _progress =
        AnimationController(
          vsync: this,
          duration: const Duration(milliseconds: 2200),
        )..addListener(() {
          setState(() => _count = (_progress.value * 100).round());
        });
    _pop = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 550),
    );
    _shake = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 420),
    );
    _title = widget.title ?? '';
    _subtitle = widget.subtitle ?? '';
    WidgetsBinding.instance.addPostFrameCallback((_) => _play());
  }

  @override
  void dispose() {
    _orbit.dispose();
    _ringSpin.dispose();
    _progress.dispose();
    _pop.dispose();
    _shake.dispose();
    super.dispose();
  }

  void _spawnConfetti() {
    const colors = [
      Color(0xFFFBBF24),
      Color(0xFF38BDF8),
      Color(0xFFF472B6),
      Color(0xFF34D399),
      Color(0xFFA78BFA),
      Color(0xFFFB923C),
    ];
    _pieces
      ..clear()
      ..addAll(
        List.generate(28, (i) {
          return _ConfettiPiece(
            color: colors[i % colors.length],
            dx: _rng.nextDouble() * 160 - 80,
            dy: _rng.nextDouble() * 140 - 90,
            rot: _rng.nextDouble() * 360,
            delay: 0.35 + _rng.nextDouble() * 0.55,
            round: _rng.nextBool(),
          );
        }),
      );
  }

  Future<void> _play() async {
    final l10n = AppLocalizations.of(context);
    // Always run the full stage timeline. Prefer-reduced-motion / animator
    // scale=0 still gets paced Future.delayed steps so the sequence is visible.

    setState(() {
      _stage = _Stage.enter;
      _title = widget.title ?? l10n.accessEnterTitle;
      _subtitle = widget.subtitle ?? l10n.accessEnterSub;
    });

    await Future<void>.delayed(const Duration(milliseconds: 450));
    if (!mounted) return;

    setState(() => _stage = _Stage.orbit);
    _orbit.repeat();
    await Future<void>.delayed(const Duration(milliseconds: 1600));
    if (!mounted) return;
    _orbit.stop();

    setState(() {
      _stage = _Stage.verify;
      _title = widget.verifyingTitle ?? l10n.accessVerifyingTitle;
      _subtitle = widget.verifyingSubtitle ?? l10n.accessVerifyingSub;
      _count = 0;
    });
    _ringSpin.repeat();

    String? authError;
    final authFuture = widget.authenticate().then((e) => authError = e);
    await Future.wait<void>([_progress.forward(from: 0), authFuture]);
    if (!mounted) return;
    _ringSpin.stop();

    if (authError != null) {
      await _shake.forward(from: 0);
      if (!mounted) return;
      widget.onCancel(authError);
      return;
    }

    setState(() {
      _stage = _Stage.success;
      _title = widget.grantedTitle ?? l10n.accessGrantedTitle;
      _subtitle = widget.grantedSubtitle ?? l10n.accessGrantedSub;
    });
    _spawnConfetti();
    _pop.forward(from: 0);
    await Future<void>.delayed(const Duration(milliseconds: 1400));
    if (mounted) widget.onSuccess();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _shake,
      builder: (context, child) {
        final t = Curves.easeInOut.transform(_shake.value);
        final dx = math.sin(t * math.pi * 6) * 8 * (1 - t);
        return Transform.translate(offset: Offset(dx, 0), child: child);
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            _title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.3,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            _subtitle,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13,
              color: AppColors.muted.withValues(alpha: 0.95),
            ),
          ),
          const SizedBox(height: 28),
          SizedBox(
            height: 176,
            width: double.infinity,
            child: Stack(
              alignment: Alignment.center,
              children: [
                if (_stage == _Stage.enter || _stage == _Stage.orbit)
                  _DigitsStage(
                    digits: widget.digits,
                    orbit: _orbit,
                    orbiting: _stage == _Stage.orbit,
                  ),
                if (_stage == _Stage.verify)
                  _VerifyRing(progress: _progress.value, count: _count),
                if (_stage == _Stage.success) ...[
                  ScaleTransition(
                    scale: CurvedAnimation(
                      parent: _pop,
                      curve: Curves.easeOutBack,
                    ),
                    child: Container(
                      width: 88,
                      height: 88,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        gradient: AppColors.indigoGradient,
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.primary.withValues(alpha: 0.35),
                            blurRadius: 20,
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.check_rounded,
                        color: Colors.white,
                        size: 48,
                      ),
                    ),
                  ),
                  ..._pieces.map((p) => _ConfettiBit(piece: p, pop: _pop)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

enum _Stage { enter, orbit, verify, success }

class _DigitsStage extends StatelessWidget {
  const _DigitsStage({
    required this.digits,
    required this.orbit,
    required this.orbiting,
  });

  final List<String> digits;
  final AnimationController orbit;
  final bool orbiting;

  @override
  Widget build(BuildContext context) {
    if (!orbiting) {
      return Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          for (var i = 0; i < 4; i++) ...[
            if (i > 0) const SizedBox(width: 12),
            _DigitTile(char: digits.length > i ? digits[i] : ''),
          ],
        ],
      );
    }

    return AnimatedBuilder(
      animation: orbit,
      builder: (context, _) {
        return Stack(
          alignment: Alignment.center,
          children: [
            for (var i = 0; i < 4; i++)
              Transform.translate(
                offset: _orbitOffset(i, orbit.value),
                child: Transform.rotate(
                  angle: _tumble(i) * math.pi / 180,
                  child: _DigitTile(char: digits.length > i ? digits[i] : ''),
                ),
              ),
          ],
        );
      },
    );
  }

  Offset _orbitOffset(int i, double t) {
    const radius = 54.0;
    final start = i * (math.pi / 2);
    final angle = start + t * math.pi * 2;
    return Offset(math.cos(angle) * radius, math.sin(angle) * radius);
  }

  double _tumble(int i) => switch (i) {
    0 => 12,
    1 => -28,
    2 => 48,
    _ => -18,
  };
}

class _DigitTile extends StatelessWidget {
  const _DigitTile({required this.char});

  final String char;

  @override
  Widget build(BuildContext context) {
    final empty = char.isEmpty;
    return Container(
      width: 52,
      height: 52,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: empty ? const Color(0xFFE2E8F0) : const Color(0xFFC7D2FE),
          width: 1.5,
        ),
        boxShadow: empty
            ? null
            : [
                BoxShadow(
                  color: const Color(0xFF4F46E5).withValues(alpha: 0.28),
                  blurRadius: 16,
                  offset: const Offset(0, 8),
                ),
              ],
      ),
      child: Text(
        char,
        style: const TextStyle(
          fontSize: 22,
          fontWeight: FontWeight.w800,
          color: Color(0xFF1E1B4B),
        ),
      ),
    );
  }
}

class _VerifyRing extends StatelessWidget {
  const _VerifyRing({required this.progress, required this.count});

  final double progress;
  final int count;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 112,
      height: 112,
      child: Stack(
        alignment: Alignment.center,
        children: [
          SizedBox(
            width: 112,
            height: 112,
            child: CircularProgressIndicator(
              value: progress.clamp(0.0, 1.0),
              strokeWidth: 6,
              backgroundColor: const Color(0xFFE0E7FF),
              color: const Color(0xFF818CF8),
            ),
          ),
          Text(
            '$count',
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w900,
              color: Color(0xFF312E81),
            ),
          ),
        ],
      ),
    );
  }
}

class _ConfettiPiece {
  const _ConfettiPiece({
    required this.color,
    required this.dx,
    required this.dy,
    required this.rot,
    required this.delay,
    required this.round,
  });

  final Color color;
  final double dx;
  final double dy;
  final double rot;
  final double delay;
  final bool round;
}

class _ConfettiBit extends StatelessWidget {
  const _ConfettiBit({required this.piece, required this.pop});

  final _ConfettiPiece piece;
  final AnimationController pop;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: pop,
      builder: (context, _) {
        final raw = ((pop.value - piece.delay * 0.15) / 0.85).clamp(0.0, 1.0);
        final t = Curves.easeOut.transform(raw);
        return Opacity(
          opacity: (1 - t).clamp(0.0, 1.0),
          child: Transform.translate(
            offset: Offset(piece.dx * t, piece.dy * t),
            child: Transform.rotate(
              angle: piece.rot * math.pi / 180 * t,
              child: Container(
                width: piece.round ? 8 : 7,
                height: piece.round ? 8 : 5,
                decoration: BoxDecoration(
                  color: piece.color,
                  borderRadius: BorderRadius.circular(piece.round ? 999 : 3),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
