package com.naukripatra.emicalculator.ui.nav;

import android.app.Activity;
import android.content.Intent;

import androidx.core.content.ContextCompat;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.LayoutBottomNavBinding;
import com.naukripatra.emicalculator.ui.history.HistoryActivity;
import com.naukripatra.emicalculator.ui.home.HomeActivity;
import com.naukripatra.emicalculator.ui.settings.SettingsActivity;

/**
 * Binds the shared bottom navigation bar (Home / History / Settings) included on every
 * top-level screen, highlighting whichever destination is currently active.
 */
public final class BottomNavHelper {

    public enum Destination { HOME, HISTORY, SETTINGS }

    private BottomNavHelper() {
    }

    public static void setup(Activity activity, LayoutBottomNavBinding nav, Destination active) {
        int selectedColor = ContextCompat.getColor(activity, R.color.md_primary);
        int unselectedColor = ContextCompat.getColor(activity, R.color.md_on_surface_variant);

        style(nav.navHomeIcon, nav.navHomeLabel, active == Destination.HOME, selectedColor, unselectedColor);
        style(nav.navHistoryIcon, nav.navHistoryLabel, active == Destination.HISTORY, selectedColor, unselectedColor);
        style(nav.navSettingsIcon, nav.navSettingsLabel, active == Destination.SETTINGS, selectedColor, unselectedColor);

        nav.navHome.setOnClickListener(v -> {
            if (active != Destination.HOME) {
                Intent intent = new Intent(activity, HomeActivity.class);
                intent.setFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT | Intent.FLAG_ACTIVITY_CLEAR_TOP);
                activity.startActivity(intent);
            }
        });
        nav.navHistory.setOnClickListener(v -> {
            if (active != Destination.HISTORY) {
                activity.startActivity(new Intent(activity, HistoryActivity.class));
            }
        });
        nav.navSettings.setOnClickListener(v -> {
            if (active != Destination.SETTINGS) {
                activity.startActivity(new Intent(activity, SettingsActivity.class));
            }
        });
    }

    private static void style(android.widget.ImageView icon, android.widget.TextView label,
                               boolean selected, int selectedColor, int unselectedColor) {
        int color = selected ? selectedColor : unselectedColor;
        icon.setImageTintList(android.content.res.ColorStateList.valueOf(color));
        label.setTextColor(color);
    }
}
