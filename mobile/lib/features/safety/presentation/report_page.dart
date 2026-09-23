import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Standalone report form (Blade `safety/report` parity).
class ReportPage extends ConsumerStatefulWidget {
  const ReportPage({
    super.key,
    this.initialType = 'user',
    this.initialId,
  });

  final String initialType;
  final int? initialId;

  @override
  ConsumerState<ReportPage> createState() => _ReportPageState();
}

class _ReportPageState extends ConsumerState<ReportPage> {
  late String _type;
  late final TextEditingController _id;
  String _reason = 'spam';
  final _details = TextEditingController();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _type = widget.initialType;
    _id = TextEditingController(
      text: widget.initialId != null ? '${widget.initialId}' : '',
    );
  }

  @override
  void dispose() {
    _id.dispose();
    _details.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final id = int.tryParse(_id.text.trim());
    if (id == null || id < 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).validationRequired)),
      );
      return;
    }

    setState(() => _submitting = true);
    final result = await ref.read(safetyRemoteSourceProvider).report(
          reportableType: _type,
          reportableId: id,
          reason: _reason,
          description:
              _details.text.trim().isEmpty ? null : _details.text.trim(),
        );
    if (!mounted) return;
    setState(() => _submitting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).reportSubmitted)),
    );
    Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.reportTitle)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            l10n.report,
            style: const TextStyle(color: AppColors.muted, fontSize: 13),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: AppColors.borderSoft),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                DropdownButtonFormField<String>(
                  // ignore: deprecated_member_use
                  value: _type,
                  decoration: InputDecoration(labelText: l10n.reportType),
                  items: [
                    DropdownMenuItem(
                      value: 'user',
                      child: Text(l10n.reportTypeUser),
                    ),
                    DropdownMenuItem(
                      value: 'event',
                      child: Text(l10n.reportTypeEvent),
                    ),
                    DropdownMenuItem(
                      value: 'message',
                      child: Text(l10n.reportTypeMessage),
                    ),
                  ],
                  onChanged: (v) {
                    if (v != null) setState(() => _type = v);
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _id,
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: InputDecoration(labelText: l10n.reportableId),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  // ignore: deprecated_member_use
                  value: _reason,
                  decoration: InputDecoration(labelText: l10n.reportReason),
                  items: [
                    DropdownMenuItem(
                      value: 'spam',
                      child: Text(l10n.reportSpam),
                    ),
                    DropdownMenuItem(
                      value: 'harassment',
                      child: Text(l10n.reportHarassment),
                    ),
                    DropdownMenuItem(
                      value: 'inappropriate',
                      child: Text(l10n.reportInappropriate),
                    ),
                    DropdownMenuItem(
                      value: 'scam',
                      child: Text(l10n.reportScam),
                    ),
                    DropdownMenuItem(
                      value: 'other',
                      child: Text(l10n.reportOther),
                    ),
                  ],
                  onChanged: (v) {
                    if (v != null) setState(() => _reason = v);
                  },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _details,
                  maxLines: 4,
                  maxLength: 1000,
                  decoration: InputDecoration(
                    labelText: l10n.reportDetails,
                    hintText: l10n.optional,
                  ),
                ),
                const SizedBox(height: 20),
                GradientButton(
                  label: l10n.submitReport,
                  isLoading: _submitting,
                  onPressed: _submit,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
