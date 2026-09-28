import 'package:flutter/material.dart';

import '../theme/tokens.dart';

/// One of the 8 home-screen category tiles (spec 2.2, 2.3). Background is
/// the category's gradient (dimmed to an 18% tint in dark mode, per
/// [AppCategoryGradient.forBrightness]); the icon sits on a solid circle of
/// the gradient's start colour.
class CategoryCard extends StatelessWidget {
  const CategoryCard({
    super.key,
    required this.gradient,
    required this.label,
    required this.ctaLabel,
    this.onTap,
  });

  final AppCategoryGradient gradient;
  final String label;
  final String ctaLabel;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final brightness = Theme.of(context).brightness;
    final colors = gradient.forBrightness(brightness);
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(AppRadius.card),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.card),
        child: AnimatedContainer(
          duration: AppMotion.fast,
          padding: const EdgeInsets.all(AppSpacing.lg),
          decoration: BoxDecoration(
            gradient: LinearGradient(colors: colors, begin: Alignment.topLeft, end: Alignment.bottomRight),
            borderRadius: BorderRadius.circular(AppRadius.card),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(color: gradient.start, shape: BoxShape.circle),
                child: Icon(gradient.icon, color: Colors.white, size: 20),
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                label,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                  fontSize: AppTypeScale.titleSize,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                ctaLabel,
                style: TextStyle(
                  fontSize: AppTypeScale.captionSize,
                  color: Colors.white.withOpacity(0.9),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
