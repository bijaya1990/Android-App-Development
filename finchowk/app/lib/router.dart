import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'features/_dev/widget_gallery_screen.dart';

/// Route table placeholder. Real routes (home, category, offer details,
/// search, legal, settings, onboarding) are wired up in Phase 3.
final GoRouter appRouter = GoRouter(
  routes: [
    GoRoute(
      path: '/',
      builder: (context, state) => const _SetupPlaceholderScreen(),
    ),
    // Dev-only widget catalogue (spec Phase 1 deliverable) — never
    // registered in a release build.
    if (kDebugMode)
      GoRoute(
        path: '/_gallery',
        builder: (context, state) => const WidgetGalleryScreen(),
      ),
  ],
);

class _SetupPlaceholderScreen extends StatelessWidget {
  const _SetupPlaceholderScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'FinChowk — Phase 1 design system complete',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            if (kDebugMode)
              TextButton(
                onPressed: () => context.go('/_gallery'),
                child: const Text('Open widget gallery'),
              ),
          ],
        ),
      ),
    );
  }
}
