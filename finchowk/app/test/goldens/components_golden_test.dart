import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:finchowk/core/theme/tokens.dart';
import 'package:finchowk/core/widgets/app_buttons.dart';
import 'package:finchowk/core/widgets/app_confirm_bottom_sheet.dart';
import 'package:finchowk/core/widgets/app_header.dart';
import 'package:finchowk/core/widgets/category_card.dart';
import 'package:finchowk/core/widgets/disclaimer_box.dart';
import 'package:finchowk/core/widgets/info_chip.dart';
import 'package:finchowk/core/widgets/legal_page.dart';
import 'package:finchowk/core/widgets/offer_card.dart';
import 'package:finchowk/core/widgets/settings_tile.dart';
import 'package:finchowk/core/widgets/state_view.dart';

import 'golden_helpers.dart';

void main() {
  setUpAll(loadAppFontsForGoldens);

  Future<void> golden(WidgetTester tester, String name, Widget widget, {Brightness brightness = Brightness.light}) async {
    await tester.pumpWidget(wrapForGolden(widget, brightness: brightness));
    await tester.pumpAndSettle();
    final suffix = brightness == Brightness.light ? 'light' : 'dark';
    await expectLater(find.byType(MaterialApp), matchesGoldenFile('${name}_$suffix.png'));
  }

  for (final brightness in [Brightness.light, Brightness.dark]) {
    final label = brightness == Brightness.light ? 'light' : 'dark';

    testWidgets('PrimaryButton ($label)', (tester) async {
      await golden(tester, 'primary_button', PrimaryButton(label: 'Apply Now', onPressed: () {}), brightness: brightness);
    });

    testWidgets('SecondaryButton ($label)', (tester) async {
      await golden(tester, 'secondary_button', SecondaryButton(label: 'Details', onPressed: () {}), brightness: brightness);
    });

    testWidgets('InfoChip ($label)', (tester) async {
      await golden(
        tester,
        'info_chip',
        const Wrap(
          spacing: AppSpacing.sm,
          children: [
            InfoChip(label: 'No paperwork'),
            InfoChip(label: 'Verified partner', variant: InfoChipVariant.verified, icon: Icons.verified),
            InfoChip(label: 'Personal Loan', variant: InfoChipVariant.category),
          ],
        ),
        brightness: brightness,
      );
    });

    testWidgets('DisclaimerBox ($label)', (tester) async {
      await golden(
        tester,
        'disclaimer_box',
        const DisclaimerBox(text: 'We are not a lender. Some links are affiliate links.'),
        brightness: brightness,
      );
    });

    testWidgets('CategoryCard ($label)', (tester) async {
      await golden(
        tester,
        'category_card',
        const SizedBox(
          height: 160,
          child: CategoryCard(gradient: AppCategoryTokens.personalLoan, label: 'Personal Loan', ctaLabel: 'Check offers'),
        ),
        brightness: brightness,
      );
    });

    testWidgets('OfferCard ($label)', (tester) async {
      await golden(
        tester,
        'offer_card',
        OfferCard(
          logoUrl: '',
          partnerName: 'ZET',
          title: 'Personal Loan',
          description: 'Compare personal loan offers from ZET. Apply through their official site.',
          highlights: const ['No paperwork', 'Digital process'],
          onDetails: () {},
          onApply: () {},
        ),
        brightness: brightness,
      );
    });

    testWidgets('OfferCard skeleton ($label)', (tester) async {
      await golden(tester, 'offer_card_skeleton', const OfferCard.skeleton(), brightness: brightness);
    });

    testWidgets('StateView empty ($label)', (tester) async {
      await golden(
        tester,
        'state_view_empty',
        const SizedBox(height: 200, child: StateView(kind: StateViewKind.empty)),
        brightness: brightness,
      );
    });

    testWidgets('StateView error ($label)', (tester) async {
      await golden(
        tester,
        'state_view_error',
        const SizedBox(height: 200, child: StateView(kind: StateViewKind.error)),
        brightness: brightness,
      );
    });

    testWidgets('SettingsTile ($label)', (tester) async {
      await golden(
        tester,
        'settings_tile',
        Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SettingsTile.chevron(title: 'Privacy Policy'),
            SettingsTile.toggle(title: 'Analytics consent', value: true, onChanged: (_) {}),
            const SettingsTile.radio(title: 'English', value: true),
          ],
        ),
        brightness: brightness,
      );
    });

    testWidgets('AppHeader ($label)', (tester) async {
      await golden(tester, 'app_header', const Scaffold(appBar: AppHeader()), brightness: brightness);
    });

    testWidgets('LegalPage ($label)', (tester) async {
      await golden(
        tester,
        'legal_page',
        const SizedBox(
          height: 300,
          child: LegalPage(
            title: 'Disclaimer',
            markdownBody: '**We are not a bank, NBFC, insurer, broker or investment company.**',
            lastUpdated: '2026-01-01',
          ),
        ),
        brightness: brightness,
      );
    });

    testWidgets('AppConfirmBottomSheet ($label)', (tester) async {
      await golden(tester, 'app_confirm_bottom_sheet', const AppConfirmBottomSheet(partnerName: 'ZET'), brightness: brightness);
    });
  }
}
