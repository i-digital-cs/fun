import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';

/// Four single-digit boxes with auto-advance and paste support.
class OtpCodeFields extends StatefulWidget {
  const OtpCodeFields({
    super.key,
    required this.onChanged,
    this.enabled = true,
  });

  final ValueChanged<String> onChanged;
  final bool enabled;

  @override
  State<OtpCodeFields> createState() => _OtpCodeFieldsState();
}

class _OtpCodeFieldsState extends State<OtpCodeFields> {
  final _controllers = List.generate(4, (_) => TextEditingController());
  final _focusNodes = List.generate(4, (_) => FocusNode());

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    for (final f in _focusNodes) {
      f.dispose();
    }
    super.dispose();
  }

  String get _code => _controllers.map((c) => c.text).join();

  void _notify() => widget.onChanged(_code);

  void _onChanged(int index, String value) {
    if (value.length > 1) {
      _applyPaste(value);
      return;
    }
    if (value.isNotEmpty && index < 3) {
      _focusNodes[index + 1].requestFocus();
    }
    _notify();
  }

  void _applyPaste(String raw) {
    final digits = raw.replaceAll(RegExp(r'\D'), '');
    for (var i = 0; i < 4; i++) {
      _controllers[i].text = i < digits.length ? digits[i] : '';
    }
    final focusIndex = digits.length.clamp(0, 3);
    _focusNodes[focusIndex].requestFocus();
    _notify();
  }

  KeyEventResult _onKey(int index, FocusNode node, KeyEvent event) {
    if (event is! KeyDownEvent) return KeyEventResult.ignored;
    if (event.logicalKey == LogicalKeyboardKey.backspace &&
        _controllers[index].text.isEmpty &&
        index > 0) {
      _focusNodes[index - 1].requestFocus();
      return KeyEventResult.handled;
    }
    return KeyEventResult.ignored;
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: List.generate(4, (i) {
        return SizedBox(
          width: 56,
          child: Focus(
            onKeyEvent: (node, event) => _onKey(i, node, event),
            child: TextField(
              controller: _controllers[i],
              focusNode: _focusNodes[i],
              enabled: widget.enabled,
              textAlign: TextAlign.center,
              keyboardType: TextInputType.number,
              maxLength: 4,
              style: const TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w700,
                letterSpacing: 2,
              ),
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: InputDecoration(
                counterText: '',
                contentPadding: const EdgeInsets.symmetric(vertical: 14),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(
                    color: AppColors.primary,
                    width: 1.5,
                  ),
                ),
              ),
              onChanged: (v) => _onChanged(i, v),
            ),
          ),
        );
      }),
    );
  }
}
