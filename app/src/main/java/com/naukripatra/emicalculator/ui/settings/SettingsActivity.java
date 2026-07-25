package com.naukripatra.emicalculator.ui.settings;

import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

import com.naukripatra.emicalculator.BuildConfig;
import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivitySettingsBinding;
import com.naukripatra.emicalculator.ui.home.HomeActivity;
import com.naukripatra.emicalculator.ui.legal.ContactUsActivity;
import com.naukripatra.emicalculator.ui.legal.OpenSourceLicensesActivity;
import com.naukripatra.emicalculator.ui.legal.WebViewActivity;
import com.naukripatra.emicalculator.ui.nav.BottomNavHelper;
import com.naukripatra.emicalculator.util.AdConfig;
import com.naukripatra.emicalculator.util.PreferenceManager;

public class SettingsActivity extends AppCompatActivity {

    private ActivitySettingsBinding binding;
    private PreferenceManager preferenceManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivitySettingsBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        preferenceManager = new PreferenceManager(this);

        applyEdgeToEdgeInsets();
        binding.btnAvatar.setOnClickListener(v -> {
            Intent intent = new Intent(this, HomeActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT | Intent.FLAG_ACTIVITY_CLEAR_TOP);
            startActivity(intent);
        });

        setupAppearanceSection();
        setupGeneralSection();
        setupSupportAndLegalSection();

        BottomNavHelper.setup(this, binding.bottomNav, BottomNavHelper.Destination.SETTINGS);
        AdConfig.loadBanner(binding.adBannerContainer.adBanner);
    }

    private void applyEdgeToEdgeInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.header, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), bars.top, view.getPaddingRight(), view.getPaddingBottom());
            return insets;
        });
    }

    private void setupAppearanceSection() {
        binding.rowDarkMode.rowIcon.setImageResource(R.drawable.ic_dark_mode);
        binding.rowDarkMode.rowTitle.setText(R.string.settings_dark_mode);
        binding.rowDarkMode.rowSubtitle.setVisibility(View.VISIBLE);
        updateThemeSubtitle();
        binding.rowDarkMode.getRoot().setOnClickListener(v -> showThemePickerDialog());
    }

    private void updateThemeSubtitle() {
        int mode = preferenceManager.getThemeMode();
        int labelRes;
        if (mode == PreferenceManager.THEME_LIGHT) {
            labelRes = R.string.theme_light;
        } else if (mode == PreferenceManager.THEME_DARK) {
            labelRes = R.string.theme_dark;
        } else {
            labelRes = R.string.theme_system;
        }
        binding.rowDarkMode.rowSubtitle.setText(labelRes);
    }

    private void showThemePickerDialog() {
        String[] options = {
                getString(R.string.theme_system),
                getString(R.string.theme_light),
                getString(R.string.theme_dark)
        };
        int checked = preferenceManager.getThemeMode();
        new AlertDialog.Builder(this)
                .setTitle(R.string.choose_theme)
                .setSingleChoiceItems(options, checked, (dialog, which) -> {
                    preferenceManager.setThemeMode(which);
                    PreferenceManager.applyTheme(which);
                    updateThemeSubtitle();
                    dialog.dismiss();
                })
                .setNegativeButton(android.R.string.cancel, null)
                .show();
    }

    private void setupGeneralSection() {
        binding.rowRateApp.rowIcon.setImageResource(R.drawable.ic_star);
        binding.rowRateApp.rowTitle.setText(R.string.settings_rate_app);
        binding.rowRateApp.getRoot().setOnClickListener(v -> openPlayStore());

        binding.rowShareApp.rowIcon.setImageResource(R.drawable.ic_share);
        binding.rowShareApp.rowTitle.setText(R.string.settings_share_app);
        binding.rowShareApp.getRoot().setOnClickListener(v -> shareApp());
    }

    private void openPlayStore() {
        try {
            startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse("market://details?id=" + getPackageName())));
        } catch (ActivityNotFoundException e) {
            startActivity(new Intent(Intent.ACTION_VIEW,
                    Uri.parse("https://play.google.com/store/apps/details?id=" + getPackageName())));
        }
    }

    private void shareApp() {
        String storeLink = "https://play.google.com/store/apps/details?id=" + getPackageName();
        Intent intent = new Intent(Intent.ACTION_SEND);
        intent.setType("text/plain");
        intent.putExtra(Intent.EXTRA_TEXT, getString(R.string.share_app_text) + "\n\n" + storeLink);
        startActivity(Intent.createChooser(intent, getString(R.string.settings_share_app)));
    }

    private void setupSupportAndLegalSection() {
        binding.rowContactUs.rowIcon.setImageResource(R.drawable.ic_mail);
        binding.rowContactUs.rowTitle.setText(R.string.settings_contact);
        binding.rowContactUs.getRoot().setOnClickListener(v ->
                startActivity(new Intent(this, ContactUsActivity.class)));

        binding.rowPrivacyPolicy.rowIcon.setImageResource(R.drawable.ic_shield);
        binding.rowPrivacyPolicy.rowTitle.setText(R.string.settings_privacy_policy);
        binding.rowPrivacyPolicy.rowChevron.setImageResource(R.drawable.ic_open_in_new);
        binding.rowPrivacyPolicy.getRoot().setOnClickListener(v ->
                WebViewActivity.start(this, "privacy_policy.html", getString(R.string.privacy_policy_title)));

        binding.rowTermsOfUse.rowIcon.setImageResource(R.drawable.ic_gavel);
        binding.rowTermsOfUse.rowTitle.setText(R.string.settings_terms);
        binding.rowTermsOfUse.rowChevron.setImageResource(R.drawable.ic_open_in_new);
        binding.rowTermsOfUse.getRoot().setOnClickListener(v ->
                WebViewActivity.start(this, "terms_of_use.html", getString(R.string.terms_of_use_title)));

        binding.rowAppVersion.rowIcon.setImageResource(R.drawable.ic_check_circle);
        binding.rowAppVersion.rowTitle.setText(R.string.settings_version);
        binding.rowAppVersion.rowSubtitle.setVisibility(View.VISIBLE);
        binding.rowAppVersion.rowSubtitle.setText(BuildConfig.VERSION_NAME);
        binding.rowAppVersion.rowChevron.setVisibility(View.GONE);
        binding.rowAppVersion.getRoot().setClickable(false);
        binding.rowAppVersion.getRoot().setFocusable(false);

        binding.rowOpenSource.rowIcon.setImageResource(R.drawable.ic_library);
        binding.rowOpenSource.rowTitle.setText(R.string.settings_open_source);
        binding.rowOpenSource.getRoot().setOnClickListener(v ->
                startActivity(new Intent(this, OpenSourceLicensesActivity.class)));
    }

    @Override
    protected void onResume() {
        super.onResume();
        binding.adBannerContainer.adBanner.resume();
    }

    @Override
    protected void onPause() {
        binding.adBannerContainer.adBanner.pause();
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        binding.adBannerContainer.adBanner.destroy();
        super.onDestroy();
    }
}
