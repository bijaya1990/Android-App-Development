import 'package:flutter/material.dart';

import '../../core/theme/tokens.dart';
import '../../core/widgets/app_buttons.dart';
import '../../core/widgets/app_confirm_bottom_sheet.dart';
import '../../core/widgets/app_header.dart';
import '../../core/widgets/category_card.dart';
import '../../core/widgets/disclaimer_box.dart';
import '../../core/widgets/info_chip.dart';
import '../../core/widgets/legal_page.dart';
import '../../core/widgets/offer_card.dart';
import '../../core/widgets/settings_tile.dart';
import '../../core/widgets/state_view.dart';

/// Dev-only catalogue of every core widget in both themes, per spec 2.1 /
/// build-order Phase 1 deliverable ("all components in a widget gallery
/// screen (dev only)"). Never routed to in a release build — see
/// `router.dart`, which only registers `/_gallery` when `kDebugMode`.
class WidgetGalleryScreen extends StatefulWidget {
  const WidgetGalleryScreen({super.key});

  @override
  State<WidgetGalleryScreen> createState() => _WidgetGalleryScreenState();
}

class _WidgetGalleryScreenState extends State<WidgetGalleryScreen> {
  bool _toggleValue = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppHeader(),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          _Section(
            title: 'Buttons',
            children: [
              PrimaryButton(label: 'Apply Now', onPressed: () {}),
              const SizedBox(height: AppSpacing.sm),
              const PrimaryButton(label: 'Loading', loading: true),
              const SizedBox(height: AppSpacing.sm),
              const PrimaryButton(label: 'Disabled'),
              const SizedBox(height: AppSpacing.sm),
              SecondaryButton(label: 'Details', onPressed: () {}),
            ],
          ),
          const _Section(
            title: 'InfoChip',
            children: [
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  InfoChip(label: 'No paperwork'),
                  InfoChip(label: 'Verified partner', variant: InfoChipVariant.verified, icon: Icons.verified),
                  InfoChip(label: 'Personal Loan', variant: InfoChipVariant.category),
                ],
              ),
            ],
          ),
          _Section(
            title: 'DisclaimerBox',
            children: [
              const DisclaimerBox(
                text: 'We are not a lender. Some links are affiliate links.',
              ),
              const SizedBox(height: AppSpacing.sm),
              DisclaimerBox(
                text: 'We are not a bank, NBFC, insurer, broker or investment company. '
                    'We do not approve or reject applications.',
                variant: DisclaimerBoxVariant.full,
                onLearnMore: () {},
              ),
            ],
          ),
          _Section(
            title: 'CategoryCard',
            children: [
              GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: AppSpacing.md,
                crossAxisSpacing: AppSpacing.md,
                childAspectRatio: 1.4,
                children: [
                  for (final entry in AppCategoryTokens.all.entries)
                    CategoryCard(
                      gradient: entry.value,
                      label: entry.key.replaceAll('_', ' '),
                      ctaLabel: 'Explore',
                      onTap: () {},
                    ),
                ],
              ),
            ],
          ),
          _Section(
            title: 'OfferCard',
            children: [
              OfferCard(
                logoUrl: '',
                partnerName: 'ZET',
                title: 'Personal Loan',
                description: 'Compare personal loan offers from ZET. Apply through their official site.',
                highlights: const ['No paperwork', 'Digital process'],
                onDetails: () {},
                onApply: () {},
              ),
              const SizedBox(height: AppSpacing.md),
              const OfferCard.skeleton(),
            ],
          ),
          const _Section(
            title: 'StateView',
            children: [
              SizedBox(height: 120, child: StateView(kind: StateViewKind.empty)),
              SizedBox(height: 120, child: StateView(kind: StateViewKind.error)),
              SizedBox(height: 120, child: StateView(kind: StateViewKind.offline)),
            ],
          ),
          _Section(
            title: 'SettingsTile',
            children: [
              SettingsTile.chevron(title: 'Privacy Policy', onTap: () {}),
              SettingsTile.toggle(
                title: 'Analytics consent',
                value: _toggleValue,
                onChanged: (v) => setState(() => _toggleValue = v),
              ),
              const SettingsTile.radio(title: 'English', value: true),
            ],
          ),
          const _Section(
            title: 'LegalPage (embedded preview)',
            children: [
              SizedBox(
                height: 200,
                child: LegalPage(
                  title: 'Disclaimer',
                  markdownBody: '**We are not a bank, NBFC, insurer, broker or investment company.**',
                  lastUpdated: '2026-01-01',
                ),
              ),
            ],
          ),
          _Section(
            title: 'Leave-app confirm sheet',
            children: [
              SecondaryButton(
                label: 'Show sheet',
                onPressed: () => showLeaveAppConfirmSheet(context, partnerName: 'ZET'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppSpacing.xxl),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: AppSpacing.md),
          ...children,
        ],
      ),
    );
  }
}
