import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

/// Route table placeholder. Real routes (home, category, offer details,
/// search, legal, settings, onboarding) are wired up in Phase 3.
final GoRouter appRouter = GoRouter(
  routes: [
    GoRoute(
      path: '/',
      builder: (context, state) => const _SetupPlaceholderScreen(),
    ),
  ],
);

class _SetupPlaceholderScreen extends StatelessWidget {
  const _SetupPlaceholderScreen();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Text(
          'FinChowk — Phase 0 setup complete',
          style: Theme.of(context).textTheme.titleMedium,
        ),
      ),
    );
  }
}
