package com.naukripatra.emicalculator.ui.common;

import android.app.Activity;

import com.google.android.material.dialog.MaterialAlertDialogBuilder;
import com.naukripatra.emicalculator.R;

/** A plain "Exit?" confirmation, shown on back-press from the app's root screen. */
public final class ExitAppDialog {

    private ExitAppDialog() {
    }

    public static void show(Activity activity) {
        new MaterialAlertDialogBuilder(activity)
                .setTitle(R.string.exit_app_title)
                .setMessage(R.string.exit_app_message)
                .setPositiveButton(R.string.exit_app_confirm, (dialog, which) -> activity.finishAffinity())
                .setNegativeButton(R.string.exit_app_cancel, (dialog, which) -> dialog.dismiss())
                .show();
    }
}
