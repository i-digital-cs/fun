import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

class AsyncBody extends StatelessWidget {
  const AsyncBody({
    super.key,
    required this.isLoading,
    required this.error,
    required this.onRetry,
    required this.child,
    this.isEmpty = false,
    this.empty,
  });

  final bool isLoading;
  final String? error;
  final VoidCallback onRetry;
  final Widget child;
  final bool isEmpty;
  final Widget? empty;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    if (isLoading) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: FunLoader(size: FunLoaderSize.md),
        ),
      );
    }

    if (error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                error!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.muted),
              ),
              const SizedBox(height: 16),
              TextButton(
                onPressed: onRetry,
                child: Text(l10n.retry),
              ),
            ],
          ),
        ),
      );
    }

    if (isEmpty) {
      return empty ??
          Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                l10n.emptyNearby,
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.muted),
              ),
            ),
          );
    }

    return child;
  }
}
