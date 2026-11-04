import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

enum EmailDeliveryTipContext { verify, reset }

/// Soft inbox/spam guidance after a transactional email was requested.
class EmailDeliveryTip extends StatelessWidget {
  const EmailDeliveryTip({
    super.key,
    this.contextType = EmailDeliveryTipContext.verify,
  });

  final EmailDeliveryTipContext contextType;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isReset = contextType == EmailDeliveryTipContext.reset;
    final title = isReset ? l10n.emailTipResetTitle : l10n.emailTipVerifyTitle;
    final body = isReset ? l10n.emailTipResetBody : l10n.emailTipVerifyBody;

    return Semantics(
      container: true,
      label: '$title. $body',
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppColors.primarySoft.withValues(alpha: 0.9),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE8E0FF)),
          boxShadow: [
            BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.1),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.mail_outline_rounded,
                  size: 18,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: AppColors.primaryInk,
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        height: 1.3,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      body,
                      style: const TextStyle(
                        color: AppColors.muted,
                        fontSize: 13,
                        height: 1.45,
                      ),
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
