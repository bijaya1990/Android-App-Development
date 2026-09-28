import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';

/// Spec 2.2 SettingsTile: switch, chevron, or radio trailing variant. Used
/// by the Settings screen and the 7 legal/info screens' entry rows.
class SettingsTile extends StatelessWidget {
  const SettingsTile.chevron({super.key, required this.title, this.subtitle, this.onTap})
      : trailing = SettingsTileTrailing.chevron,
        value = null,
        onChanged = null;

  const SettingsTile.toggle({
    super.key,
    required this.title,
    this.subtitle,
    required bool this.value,
    required ValueChanged<bool> this.onChanged,
  })  : trailing = SettingsTileTrailing.toggle,
        onTap = null;

  const SettingsTile.radio({super.key, required this.title, this.subtitle, required bool this.value, this.onTap})
      : trailing = SettingsTileTrailing.radio,
        onChanged = null;

  final String title;
  final String? subtitle;
  final SettingsTileTrailing trailing;
  final bool? value;
  final ValueChanged<bool>? onChanged;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      title: Text(title, style: Theme.of(context).textTheme.titleMedium),
      subtitle: subtitle == null
          ? null
          : Text(subtitle!, style: TextStyle(fontSize: AppTypeScale.captionSize, color: colors.textSecondary)),
      trailing: switch (trailing) {
        SettingsTileTrailing.chevron => Icon(Icons.chevron_right, color: colors.textSecondary),
        SettingsTileTrailing.toggle => Switch(value: value!, onChanged: onChanged),
        SettingsTileTrailing.radio => Radio<bool>(value: true, groupValue: value, onChanged: (_) => onTap?.call()),
      },
      onTap: onTap,
    );
  }
}

enum SettingsTileTrailing { chevron, toggle, radio }
