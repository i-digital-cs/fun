import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

Future<void> showReportSheet(
  BuildContext context, {
  required String reportableType,
  required int reportableId,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: AppColors.surface,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
    ),
    builder: (context) =>
        ReportSheet(reportableType: reportableType, reportableId: reportableId),
  );
}

class ReportSheet extends ConsumerStatefulWidget {
  const ReportSheet({
    super.key,
    required this.reportableType,
    required this.reportableId,
  });

  final String reportableType;
  final int reportableId;

  @override
  ConsumerState<ReportSheet> createState() => _ReportSheetState();
}

class _ReportSheetState extends ConsumerState<ReportSheet> {
  String _reason = 'spam';
  final _details = TextEditingController();
  bool _submitting = false;

  @override
  void dispose() {
    _details.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    final result = await ref
        .read(safetyRepositoryProvider)
        .report(
          reportableType: widget.reportableType,
          reportableId: widget.reportableId,
          reason: _reason,
          description: _details.text.trim().isEmpty
              ? null
              : _details.text.trim(),
        );
    if (!mounted) return;
    setState(() => _submitting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(result.failure!.message)));
      return;
    }
    Navigator.of(context).pop();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).reportSubmitted)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l10n.report,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            key: ValueKey(_reason),
            initialValue: _reason,
            decoration: InputDecoration(labelText: l10n.reportReason),
            items: [
              DropdownMenuItem(value: 'spam', child: Text(l10n.reportSpam)),
              DropdownMenuItem(
                value: 'harassment',
                child: Text(l10n.reportHarassment),
              ),
              DropdownMenuItem(
                value: 'inappropriate',
                child: Text(l10n.reportInappropriate),
              ),
              DropdownMenuItem(value: 'scam', child: Text(l10n.reportScam)),
              DropdownMenuItem(value: 'other', child: Text(l10n.reportOther)),
            ],
            onChanged: (v) {
              if (v != null) setState(() => _reason = v);
            },
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _details,
            maxLines: 3,
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
    );
  }
}
