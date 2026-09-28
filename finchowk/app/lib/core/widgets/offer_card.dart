import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';
import 'app_buttons.dart';
import 'info_chip.dart';

/// Spec 2.2 / 3.1 OfferCard: partner logo + name, product title, 2-line
/// description, up to 3 [InfoChip]s built from the offer's own
/// `highlights[]` (never invented), a "Details" text button, and the Apply
/// Now button. Subline always reads "Apply through official partner".
class OfferCard extends StatelessWidget {
  const OfferCard({
    super.key,
    required this.logoUrl,
    required this.partnerName,
    required this.title,
    required this.description,
    this.highlights = const [],
    this.hasEligibility = false,
    this.onDetails,
    this.onApply,
  }) : _loading = false;

  const OfferCard.skeleton({super.key})
      : logoUrl = '',
        partnerName = '',
        title = '',
        description = '',
        highlights = const [],
        hasEligibility = false,
        onDetails = null,
        onApply = null,
        _loading = true;

  final String logoUrl;
  final String partnerName;
  final String title;
  final String description;
  final List<String> highlights;
  final bool hasEligibility;
  final VoidCallback? onDetails;
  final VoidCallback? onApply;
  final bool _loading;

  @override
  Widget build(BuildContext context) {
    if (_loading) return const _OfferCardSkeleton();

    final colors = context.appColors;
    final cappedHighlights = highlights.take(3).toList();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(AppSpacing.sm),
                  child: logoUrl.isEmpty
                      ? Container(width: 36, height: 36, color: colors.border)
                      : CachedNetworkImage(
                          imageUrl: logoUrl,
                          width: 36,
                          height: 36,
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => Container(width: 36, height: 36, color: colors.border),
                        ),
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(partnerName, style: Theme.of(context).textTheme.bodySmall),
                      Text(title, style: Theme.of(context).textTheme.titleMedium),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              description,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.bodyLarge,
            ),
            if (cappedHighlights.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                runSpacing: AppSpacing.xs,
                children: [for (final h in cappedHighlights) InfoChip(label: h)],
              ),
            ],
            const SizedBox(height: AppSpacing.md),
            Text(
              'Apply through official partner',
              style: TextStyle(fontSize: AppTypeScale.captionSize, color: colors.textSecondary),
            ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                if (onDetails != null) ...[
                  SecondaryButton(label: 'Details', onPressed: onDetails),
                  const SizedBox(width: AppSpacing.sm),
                ],
                Expanded(child: PrimaryButton(label: 'Apply Now', onPressed: onApply)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _OfferCardSkeleton extends StatelessWidget {
  const _OfferCardSkeleton();

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    Widget bar(double width, double height) => Container(
          width: width,
          height: height,
          decoration: BoxDecoration(color: colors.border, borderRadius: BorderRadius.circular(4)),
        );

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(width: 36, height: 36, decoration: BoxDecoration(color: colors.border, borderRadius: BorderRadius.circular(AppSpacing.sm))),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [bar(80, 12), const SizedBox(height: 6), bar(140, 16)],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            bar(double.infinity, 14),
            const SizedBox(height: 6),
            bar(200, 14),
            const SizedBox(height: AppSpacing.lg),
            bar(double.infinity, 48),
          ],
        ),
      ),
    );
  }
}
