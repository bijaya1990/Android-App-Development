import 'package:flutter/material.dart';

import 'tokens.dart';

/// Theme-aware semantic colours (spec 1.3 palette), exposed as a
/// [ThemeExtension] so widgets read `Theme.of(context).extension<AppColors>()`
/// instead of branching on [Brightness] themselves.
@immutable
class AppColors extends ThemeExtension<AppColors> {
  const AppColors({
    required this.primary,
    required this.secondary,
    required this.accent,
    required this.background,
    required this.surface,
    required this.textPrimary,
    required this.textSecondary,
    required this.border,
    required this.warningBg,
  });

  final Color primary;
  final Color secondary;
  final Color accent;
  final Color background;
  final Color surface;
  final Color textPrimary;
  final Color textSecondary;
  final Color border;
  final Color warningBg;

  static const light = AppColors(
    primary: AppColorTokens.primaryLight,
    secondary: AppColorTokens.secondaryLight,
    accent: AppColorTokens.accentLight,
    background: AppColorTokens.backgroundLight,
    surface: AppColorTokens.surfaceLight,
    textPrimary: AppColorTokens.textPrimaryLight,
    textSecondary: AppColorTokens.textSecondaryLight,
    border: AppColorTokens.borderLight,
    warningBg: AppColorTokens.warningBgLight,
  );

  static const dark = AppColors(
    primary: AppColorTokens.primaryDark,
    secondary: AppColorTokens.secondaryDark,
    accent: AppColorTokens.accentDark,
    background: AppColorTokens.backgroundDark,
    surface: AppColorTokens.surfaceDark,
    textPrimary: AppColorTokens.textPrimaryDark,
    textSecondary: AppColorTokens.textSecondaryDark,
    border: AppColorTokens.borderDark,
    warningBg: AppColorTokens.warningBgDark,
  );

  @override
  AppColors copyWith({
    Color? primary,
    Color? secondary,
    Color? accent,
    Color? background,
    Color? surface,
    Color? textPrimary,
    Color? textSecondary,
    Color? border,
    Color? warningBg,
  }) {
    return AppColors(
      primary: primary ?? this.primary,
      secondary: secondary ?? this.secondary,
      accent: accent ?? this.accent,
      background: background ?? this.background,
      surface: surface ?? this.surface,
      textPrimary: textPrimary ?? this.textPrimary,
      textSecondary: textSecondary ?? this.textSecondary,
      border: border ?? this.border,
      warningBg: warningBg ?? this.warningBg,
    );
  }

  @override
  AppColors lerp(ThemeExtension<AppColors>? other, double t) {
    if (other is! AppColors) return this;
    return AppColors(
      primary: Color.lerp(primary, other.primary, t)!,
      secondary: Color.lerp(secondary, other.secondary, t)!,
      accent: Color.lerp(accent, other.accent, t)!,
      background: Color.lerp(background, other.background, t)!,
      surface: Color.lerp(surface, other.surface, t)!,
      textPrimary: Color.lerp(textPrimary, other.textPrimary, t)!,
      textSecondary: Color.lerp(textSecondary, other.textSecondary, t)!,
      border: Color.lerp(border, other.border, t)!,
      warningBg: Color.lerp(warningBg, other.warningBg, t)!,
    );
  }
}

/// Convenience accessor: `context.appColors.primary`.
extension AppColorsContext on BuildContext {
  AppColors get appColors => Theme.of(this).extension<AppColors>()!;
}

/// Builds the light/dark [ThemeData] from [AppColorTokens] + [AppTypeScale].
/// Elevation is deliberately flat: cards use a 1px border plus a very soft
/// shadow rather than Material's default elevation shadows (spec 1.3).
abstract final class AppTheme {
  static ThemeData light() => _build(AppColors.light, Brightness.light);
  static ThemeData dark() => _build(AppColors.dark, Brightness.dark);

  static ThemeData _build(AppColors colors, Brightness brightness) {
    final textTheme = _textTheme(colors);
    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      scaffoldBackgroundColor: colors.background,
      colorScheme: ColorScheme.fromSeed(
        seedColor: colors.primary,
        brightness: brightness,
        primary: colors.primary,
        secondary: colors.secondary,
        surface: colors.surface,
        error: const Color(0xFFDC2626),
      ),
      extensions: [colors],
      textTheme: textTheme,
      appBarTheme: AppBarTheme(
        backgroundColor: colors.surface,
        foregroundColor: colors.textPrimary,
        elevation: 0,
        titleTextStyle: textTheme.titleLarge,
      ),
      cardTheme: CardTheme(
        color: colors.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.card),
          side: BorderSide(color: colors.border, width: 1),
        ),
      ),
      dividerTheme: DividerThemeData(color: colors.border, thickness: 1, space: 1),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: colors.secondary,
          foregroundColor: Colors.white,
          // A bounded minimum (not Size.fromHeight, which sets width to
          // infinity) so the button still lays out when it isn't wrapped in
          // Expanded/a full-width SizedBox — callers opt into full width
          // explicitly (see OfferCard, AppConfirmBottomSheet).
          minimumSize: const Size(64, 48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.button)),
          textStyle: textTheme.labelLarge,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: colors.primary,
          side: BorderSide(color: colors.border),
          minimumSize: const Size(64, 48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(AppRadius.button)),
          textStyle: textTheme.labelLarge,
        ),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: colors.surface,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadius.sheet)),
        ),
      ),
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: FadeUpwardsPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
        },
      ),
    );
  }

  static TextTheme _textTheme(AppColors colors) {
    const headingFontFamily = 'Poppins';
    const headingFallback = ['NotoSansDevanagari'];
    const bodyFontFamily = 'Inter';
    const bodyFallback = ['NotoSansDevanagari'];

    TextStyle heading({required double size, required double height, required FontWeight weight}) {
      return TextStyle(
        fontFamily: headingFontFamily,
        fontFamilyFallback: headingFallback,
        fontSize: size,
        height: height / size,
        fontWeight: weight,
        color: colors.textPrimary,
      );
    }

    TextStyle body({
      required double size,
      required double height,
      FontWeight weight = FontWeight.w400,
      Color? color,
    }) {
      return TextStyle(
        fontFamily: bodyFontFamily,
        fontFamilyFallback: bodyFallback,
        fontSize: size,
        height: height / size,
        fontWeight: weight,
        color: color ?? colors.textPrimary,
      );
    }

    return TextTheme(
      displayMedium: heading(
        size: AppTypeScale.displaySize,
        height: AppTypeScale.displayLineHeight,
        weight: FontWeight.w700,
      ),
      headlineMedium: heading(
        size: AppTypeScale.h1Size,
        height: AppTypeScale.h1LineHeight,
        weight: FontWeight.w700,
      ),
      titleLarge: heading(
        size: AppTypeScale.h2Size,
        height: AppTypeScale.h2LineHeight,
        weight: FontWeight.w600,
      ),
      titleMedium: heading(
        size: AppTypeScale.titleSize,
        height: AppTypeScale.titleLineHeight,
        weight: FontWeight.w600,
      ),
      bodyLarge: body(size: AppTypeScale.bodySize, height: AppTypeScale.bodyLineHeight),
      bodyMedium: body(
        size: AppTypeScale.bodySize,
        height: AppTypeScale.bodyLineHeight,
        color: colors.textSecondary,
      ),
      bodySmall: body(
        size: AppTypeScale.captionSize,
        height: AppTypeScale.captionLineHeight,
        color: colors.textSecondary,
      ),
      labelLarge: body(
        size: AppTypeScale.buttonSize,
        height: AppTypeScale.buttonLineHeight,
        weight: FontWeight.w600,
      ),
    );
  }
}
