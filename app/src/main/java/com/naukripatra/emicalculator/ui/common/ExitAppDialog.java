package com.naukripatra.emicalculator.ui.common;

import android.app.Activity;
import android.app.Dialog;
import android.graphics.Color;
import android.graphics.drawable.ColorDrawable;
import android.view.LayoutInflater;
import android.view.Window;

import com.naukripatra.emicalculator.databinding.DialogExitAppBinding;

/** A custom-styled "Exit app?" confirmation, shown on back-press from the app's root screen. */
public final class ExitAppDialog {

    private ExitAppDialog() {
    }

    public static void show(Activity activity) {
        Dialog dialog = new Dialog(activity);
        DialogExitAppBinding binding = DialogExitAppBinding.inflate(LayoutInflater.from(activity));
        dialog.setContentView(binding.getRoot());

        Window window = dialog.getWindow();
        if (window != null) {
            window.setBackgroundDrawable(new ColorDrawable(Color.TRANSPARENT));
        }

        binding.btnCancel.setOnClickListener(v -> dialog.dismiss());
        binding.btnExit.setOnClickListener(v -> {
            dialog.dismiss();
            activity.finishAffinity();
        });

        dialog.show();
    }
}
