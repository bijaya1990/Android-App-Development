import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';
import 'app_buttons.dart';

/// Spec 3.2 "leave-app confirm" sheet, shown before opening a partner's
/// tracking URL: "You are leaving FinChowk and going to <Partner>'s
/// official site. The partner decides eligibility, rates and approval."
/// Purely presentational here — the Apply Now flow (URL validation,
/// `confirmBeforeLeave` gating, click logging) is wired up in Phase 3.
Future<bool?> showLeaveAppConfirmSheet(BuildContext context, {required String partnerName}) {
  return showModalBottomSheet<bool>(
    context: context,
    builder: (context) => AppConfirmBottomSheet(partnerName: partnerName),
  );
}

class AppConfirmBottomSheet extends StatelessWidget {
  const AppConfirmBottomSheet({super.key, required this.partnerName});

  final String partnerName;

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.xxl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('You are leaving FinChowk', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            Text(
              'and going to $partnerName\'s official site. The partner decides '
              'eligibility, rates and approval.',
              style: TextStyle(color: colors.textSecondary),
            ),
            const SizedBox(height: AppSpacing.xxl),
            Row(
              children: [
                Expanded(
                  child: SecondaryButton(label: 'Cancel', onPressed: () => Navigator.of(context).pop(false)),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: PrimaryButton(label: 'Continue', onPressed: () => Navigator.of(context).pop(true)),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
