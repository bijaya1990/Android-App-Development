import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../theme/tokens.dart';

/// Spec 2.2 AppHeader: logo, app name, search icon (opens search — local,
/// no network call), menu icon (opens drawer). Variants: default,
/// search-active.
class AppHeader extends StatelessWidget implements PreferredSizeWidget {
  const AppHeader({
    super.key,
    this.searchActive = false,
    this.onSearchTap,
    this.onMenuTap,
  });

  final bool searchActive;
  final VoidCallback? onSearchTap;
  final VoidCallback? onMenuTap;

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  Widget build(BuildContext context) {
    final colors = context.appColors;
    return AppBar(
      titleSpacing: AppSpacing.lg,
      title: Row(
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(color: colors.primary, borderRadius: BorderRadius.circular(8)),
            alignment: Alignment.center,
            child: const Text('F', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          ),
          const SizedBox(width: AppSpacing.sm),
          const Text('FinChowk', style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600, fontSize: AppTypeScale.titleSize)),
        ],
      ),
      actions: [
        IconButton(
          icon: Icon(searchActive ? Icons.search : Icons.search_outlined, color: searchActive ? colors.primary : null),
          onPressed: onSearchTap,
          tooltip: 'Search offers',
        ),
        IconButton(icon: const Icon(Icons.menu), onPressed: onMenuTap, tooltip: 'Menu'),
      ],
    );
  }
}
