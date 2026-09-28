import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';

enum DisclaimerBoxVariant { compact, full }

/// The non-negotiable "we are not a lender" notice (spec 1.1, 2.2, 6.1).
/// Always renders on [AppColors.warningBg] — never hide, resize away, or
/// visually bury this component.
class DisclaimerBox extends StatelessWidget {
  const DisclaimerBox({super.key, required this.text, this.variant = DisclaimerBoxVariant.compact, this.onLearnMore});

  final String text;
  final DisclaimerBoxVariant variant;
  final VoidCallback? onLearnMore;

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: colors.warningBg,
        borderRadius: BorderRadius.circular(AppRadius.button),
        border: Border.all(color: colors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Icon(Icons.info_outline, size: 18, color: colors.textSecondary),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text(
                  text,
                  style: TextStyle(
                    fontSize: AppTypeScale.captionSize,
                    color: colors.textSecondary,
                    height: AppTypeScale.captionLineHeight / AppTypeScale.captionSize,
                  ),
                  maxLines: variant == DisclaimerBoxVariant.compact ? 2 : null,
                  overflow: variant == DisclaimerBoxVariant.compact ? TextOverflow.ellipsis : null,
                ),
              ),
            ],
          ),
          if (variant == DisclaimerBoxVariant.compact && onLearnMore != null)
            Padding(
              padding: const EdgeInsets.only(top: AppSpacing.xs, left: 26),
              child: GestureDetector(
                onTap: onLearnMore,
                child: Text(
                  'Learn more',
                  style: TextStyle(
                    fontSize: AppTypeScale.captionSize,
                    fontWeight: FontWeight.w600,
                    color: colors.primary,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}
