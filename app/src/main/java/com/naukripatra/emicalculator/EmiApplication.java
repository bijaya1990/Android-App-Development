package com.naukripatra.emicalculator;

import android.app.Application;

import com.google.android.material.color.DynamicColors;
import com.naukripatra.emicalculator.util.PreferenceManager;

public class EmiApplication extends Application {

    @Override
    public void onCreate() {
        super.onCreate();
        DynamicColors.applyToActivitiesIfAvailable(this);
        PreferenceManager.applyTheme(new PreferenceManager(this).getThemeMode());
    }
}
