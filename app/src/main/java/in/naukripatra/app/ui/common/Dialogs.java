package in.naukripatra.app.ui.common;

import android.app.Activity;

import com.google.android.material.dialog.MaterialAlertDialogBuilder;

import in.naukripatra.app.R;
import in.naukripatra.app.data.Prefs;

public final class Dialogs {

    private Dialogs() {
    }

    public static void theme(Activity activity) {
        CharSequence[] labels = {
                activity.getString(R.string.theme_system),
                activity.getString(R.string.theme_light),
                activity.getString(R.string.theme_dark)
        };
        new MaterialAlertDialogBuilder(activity)
                .setTitle(R.string.choose_theme)
                .setSingleChoiceItems(labels, Prefs.theme(activity), (d, which) -> {
                    d.dismiss();
                    Prefs.setTheme(activity, which);
                })
                .setNegativeButton(android.R.string.cancel, null)
                .show();
    }
}
