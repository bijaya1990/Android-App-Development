import 'package:flutter/material.dart';
import 'package:flutter_markdown/flutter_markdown.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';

/// Spec 3.3: every legal/info screen (About Us, Affiliate Disclosure,
/// Disclaimer, Partner Information, Privacy Policy, Terms & Conditions,
/// Contact Us) renders sanitised Markdown from `appConfig.legal.<key>` with
/// a "Last updated" date. Content sanitisation + the real appConfig wiring
/// land in Phase 2/3 — this is the presentational shell.
class LegalPage extends StatelessWidget {
  const LegalPage({super.key, required this.title, required this.markdownBody, this.lastUpdated});

  final String title;
  final String markdownBody;
  final String? lastUpdated;

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          if (lastUpdated != null) ...[
            Text(
              'Last updated: $lastUpdated',
              style: TextStyle(fontSize: AppTypeScale.captionSize, color: colors.textSecondary),
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          MarkdownBody(
            data: markdownBody,
            // Never render images from remote-authored legal text (spec 4.4
            // "sanitising ... legal Markdown rendered with links restricted
            // to https and no images from unknown hosts").
            sizedImageBuilder: (config) => const SizedBox.shrink(),
            onTapLink: (text, href, title) {
              // Real link handling (https-only allowlist) added in Phase 3.
            },
          ),
        ],
      ),
    );
  }
}
