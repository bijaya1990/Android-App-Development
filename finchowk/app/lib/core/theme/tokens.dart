import 'package:flutter/material.dart';

/// Raw design tokens — the "Trust + Energy" palette, type scale, spacing,
/// radius and elevation values from the FinChowk Claude Code Prompt,
/// section 1.3.
///
/// No Figma file was available when this was written (see
/// DESIGN_NOTES.md), so these are taken verbatim from the source document
/// rather than a Figma Variables export. If a Figma file is connected
/// later and its values differ, Figma wins per spec 2.1 and this file is
/// the one to regenerate — nothing here should be hand-edited without
/// updating that provenance note.
abstract final class AppColorTokens {
  // Core palette — light
  static const primaryLight = Color(0xFF4F46E5);
  static const secondaryLight = Color(0xFFF97316);
  static const accentLight = Color(0xFF10B981);
  static const backgroundLight = Color(0xFFF8FAFC);
  static const surfaceLight = Color(0xFFFFFFFF);
  static const textPrimaryLight = Color(0xFF0F172A);
  static const textSecondaryLight = Color(0xFF475569);
  static const borderLight = Color(0xFFE2E8F0);
  static const warningBgLight = Color(0xFFFFF7ED);

  // Core palette — dark
  static const primaryDark = Color(0xFF818CF8);
  static const secondaryDark = Color(0xFFFB923C);
  static const accentDark = Color(0xFF34D399);
  static const backgroundDark = Color(0xFF0B1020);
  static const surfaceDark = Color(0xFF151B2E);
  static const textPrimaryDark = Color(0xFFF1F5F9);
  static const textSecondaryDark = Color(0xFF94A3B8);
  static const borderDark = Color(0xFF26304A);
  static const warningBgDark = Color(0xFF2A1A0B);
}

/// One category's card gradient + icon. The same gradient hex values are
/// used in both themes: light mode renders it solid, dark mode renders it
/// as an 18% opacity tint (spec 1.3, "Category colours" note) — apply that
/// tint at the call site via `AppCategoryGradient.forBrightness`.
class AppCategoryGradient {
  const AppCategoryGradient({required this.start, required this.end, required this.icon});

  final Color start;
  final Color end;
  final IconData icon;

  List<Color> forBrightness(Brightness brightness) {
    if (brightness == Brightness.light) return [start, end];
    const dimOpacity = 0.18;
    return [start.withOpacity(dimOpacity), end.withOpacity(dimOpacity)];
  }
}

/// Category key -> gradient/icon, keyed the same as `categories/{id}.key`
/// in Firestore (spec 4.2) so a repository can look these up directly.
abstract final class AppCategoryTokens {
  static const personalLoan = AppCategoryGradient(
    start: Color(0xFF6366F1),
    end: Color(0xFF8B5CF6),
    icon: Icons.account_balance_wallet_outlined,
  );
  static const businessLoan = AppCategoryGradient(
    start: Color(0xFF0EA5E9),
    end: Color(0xFF2563EB),
    icon: Icons.storefront_outlined,
  );
  static const creditCards = AppCategoryGradient(
    start: Color(0xFFEC4899),
    end: Color(0xFFF43F5E),
    icon: Icons.credit_card_outlined,
  );
  static const bankAccount = AppCategoryGradient(
    start: Color(0xFF14B8A6),
    end: Color(0xFF0D9488),
    icon: Icons.account_balance_outlined,
  );
  static const dematInvestment = AppCategoryGradient(
    start: Color(0xFF8B5CF6),
    end: Color(0xFFD946EF),
    icon: Icons.trending_up_outlined,
  );
  static const homeLoan = AppCategoryGradient(
    start: Color(0xFFF59E0B),
    end: Color(0xFFF97316),
    icon: Icons.home_outlined,
  );
  static const insurance = AppCategoryGradient(
    start: Color(0xFF10B981),
    end: Color(0xFF059669),
    icon: Icons.shield_outlined,
  );
  static const otherOffers = AppCategoryGradient(
    start: Color(0xFF64748B),
    end: Color(0xFF334155),
    icon: Icons.grid_view_outlined,
  );

  static const all = <String, AppCategoryGradient>{
    'personal_loan': personalLoan,
    'business_loan': businessLoan,
    'credit_cards': creditCards,
    'bank_account': bankAccount,
    'demat_investment': dematInvestment,
    'home_loan': homeLoan,
    'insurance': insurance,
    'other_offers': otherOffers,
  };
}

/// Type scale — font sizes and line heights, in logical pixels
/// (`size/lineHeight` pairs from spec 1.3). Headings use Poppins 600/700,
/// everything else Inter, with Noto Sans Devanagari as the Hindi fallback.
abstract final class AppTypeScale {
  static const displaySize = 28.0;
  static const displayLineHeight = 34.0;

  static const h1Size = 22.0;
  static const h1LineHeight = 28.0;

  static const h2Size = 18.0;
  static const h2LineHeight = 24.0;

  static const titleSize = 16.0;
  static const titleLineHeight = 22.0;

  static const bodySize = 15.0;
  static const bodyLineHeight = 22.0;

  static const captionSize = 13.0;
  static const captionLineHeight = 18.0;

  static const buttonSize = 15.0;
  static const buttonLineHeight = 20.0;

  /// Never render body text smaller than this, even under user text-scale
  /// settings that would otherwise shrink it below the design minimum.
  static const minBodySize = 14.0;
}

/// 4-pt spacing grid.
abstract final class AppSpacing {
  static const xs = 4.0;
  static const sm = 8.0;
  static const md = 12.0;
  static const lg = 16.0;
  static const xl = 20.0;
  static const xxl = 24.0;
  static const xxxl = 32.0;

  static const screenPadding = 16.0;
}

/// Corner radii.
abstract final class AppRadius {
  static const card = 20.0;
  static const button = 14.0;
  static const chip = 999.0;
  static const sheet = 28.0;
}

/// Motion durations — only short fades/scales on tap, never decorative or
/// looping animations (spec 1.3 "Motion").
abstract final class AppMotion {
  static const fast = Duration(milliseconds: 150);
  static const slow = Duration(milliseconds: 200);
}
