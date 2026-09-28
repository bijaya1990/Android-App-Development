import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';

enum InfoChipVariant { neutral, verified, category }

/// Compact fact pill. Spec 2.2: "InfoChip — neutral, verified, category —
/// only for partner-supplied facts." Never render one whose [label] was
/// computed/invented by the app — the caller is responsible for that rule.
class InfoChip extends StatelessWidget {
  const InfoChip({super.key, required this.label, this.variant = InfoChipVariant.neutral, this.icon});

  final String label;
  final InfoChipVariant variant;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    final (background, foreground, borderColor) = switch (variant) {
      InfoChipVariant.neutral => (colors.background, colors.textSecondary, colors.border),
      InfoChipVariant.verified => (
          colors.accent.withOpacity(0.12),
          colors.accent,
          colors.accent.withOpacity(0.4),
        ),
      InfoChipVariant.category => (
          colors.primary.withOpacity(0.10),
          colors.primary,
          colors.primary.withOpacity(0.35),
        ),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.xs),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(AppRadius.chip),
        border: Border.all(color: borderColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 14, color: foreground),
            const SizedBox(width: AppSpacing.xs),
          ],
          Text(
            label,
            style: TextStyle(
              fontSize: AppTypeScale.captionSize,
              fontWeight: FontWeight.w600,
              color: foreground,
            ),
          ),
        ],
      ),
    );
  }
}
