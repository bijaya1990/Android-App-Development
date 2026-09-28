import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:finchowk/core/theme/app_theme.dart';

/// Loads the bundled Poppins/Inter/Noto Sans Devanagari font files so
/// golden tests render the real type scale instead of the test harness's
/// placeholder font. Call once from `setUpAll` in each golden test file.
Future<void> loadAppFontsForGoldens() async {
  Future<void> load(String family, List<String> assetPaths) async {
    final loader = FontLoader(family);
    for (final path in assetPaths) {
      loader.addFont(rootBundle.load(path));
    }
    await loader.load();
  }

  await load('Poppins', [
    'assets/fonts/Poppins-SemiBold.ttf',
    'assets/fonts/Poppins-Bold.ttf',
  ]);
  await load('Inter', ['assets/fonts/Inter-Variable.ttf']);
  await load('NotoSansDevanagari', ['assets/fonts/NotoSansDevanagari-Regular.ttf']);
}

/// Wraps [child] in a themed, sized surface for a deterministic golden
/// capture: a `MaterialApp` on [brightness] with a fixed-width card.
Widget wrapForGolden(Widget child, {required Brightness brightness, double width = 360}) {
  return MaterialApp(
    theme: brightness == Brightness.light ? AppTheme.light() : AppTheme.dark(),
    debugShowCheckedModeBanner: false,
    home: Scaffold(
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: SizedBox(width: width, child: child),
        ),
      ),
    ),
  );
}
