import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';
import 'app_buttons.dart';

enum StateViewKind { loading, empty, error, offline }

/// Spec 2.2 StateView: illustration icon + message + retry. Used whenever a
/// list has nothing to show — never a raw exception message (spec 3.4).
class StateView extends StatelessWidget {
  const StateView({super.key, required this.kind, this.message, this.onRetry});

  final StateViewKind kind;
  final String? message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    if (kind == StateViewKind.loading) {
      return const Center(child: CircularProgressIndicator());
    }

    final (icon, defaultMessage) = switch (kind) {
      StateViewKind.empty => (Icons.inbox_outlined, 'Nothing to show here yet.'),
      StateViewKind.error => (Icons.error_outline, 'Something went wrong.'),
      StateViewKind.offline => (Icons.cloud_off_outlined, 'Offline — showing saved offers.'),
      StateViewKind.loading => (Icons.hourglass_empty, ''),
    };

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 40, color: colors.textSecondary),
            const SizedBox(height: AppSpacing.md),
            Text(
              message ?? defaultMessage,
              textAlign: TextAlign.center,
              style: TextStyle(color: colors.textSecondary),
            ),
            if (onRetry != null) ...[
              const SizedBox(height: AppSpacing.lg),
              SecondaryButton(label: 'Retry', onPressed: onRetry),
            ],
          ],
        ),
      ),
    );
  }
}
