import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'router.dart';

/// Root widget. Theming is a Phase-0 placeholder — the real light/dark
/// [ThemeData] built from Figma tokens lands in Phase 1 as
/// `core/theme/app_theme.dart`.
class FinChowkApp extends StatelessWidget {
  const FinChowkApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ProviderScope(
      child: MaterialApp.router(
        title: 'FinChowk',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(useMaterial3: true, colorSchemeSeed: const Color(0xFF4F46E5)),
        routerConfig: appRouter,
      ),
    );
  }
}
