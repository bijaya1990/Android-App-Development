import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/theme/app_theme.dart';
import 'router.dart';

/// Root widget. Theme follows the system light/dark setting by default;
/// the manual override lives in Settings (Phase 3).
class FinChowkApp extends StatelessWidget {
  const FinChowkApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ProviderScope(
      child: MaterialApp.router(
        title: 'FinChowk',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        darkTheme: AppTheme.dark(),
        themeMode: ThemeMode.system,
        routerConfig: appRouter,
      ),
    );
  }
}
