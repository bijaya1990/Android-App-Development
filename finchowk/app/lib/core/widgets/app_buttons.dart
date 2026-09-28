import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';

/// "Apply Now" style button — filled with [AppColors.secondary] (saffron),
/// per spec 2.2 ("PrimaryButton / SecondaryButton ... Apply Now = secondary
/// (saffron) filled; min height 48"). Shows a spinner in [loading] state
/// and disables itself when [onPressed] is null or [loading] is true.
class PrimaryButton extends StatelessWidget {
  const PrimaryButton({super.key, required this.label, this.onPressed, this.loading = false});

  final String label;
  final VoidCallback? onPressed;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null || loading;
    return ElevatedButton(
      onPressed: disabled ? null : onPressed,
      child: loading
          ? const SizedBox(
              height: 20,
              width: 20,
              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
            )
          : Text(label),
    );
  }
}

/// Outlined secondary action, e.g. "Details" on an [OfferCard].
class SecondaryButton extends StatelessWidget {
  const SecondaryButton({super.key, required this.label, this.onPressed});

  final String label;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton(onPressed: onPressed, child: Text(label));
  }
}

/// Small variant used inline (e.g. inside a compact [OfferCard]).
class PrimaryButtonSmall extends StatelessWidget {
  const PrimaryButtonSmall({super.key, required this.label, this.onPressed});

  final String label;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return ElevatedButton(
      onPressed: onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: colors.secondary,
        minimumSize: const Size(0, 36),
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
        textStyle: const TextStyle(fontSize: AppTypeScale.captionSize, fontWeight: FontWeight.w600),
      ),
      child: Text(label),
    );
  }
}
