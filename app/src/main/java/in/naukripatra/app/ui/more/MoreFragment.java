package in.naukripatra.app.ui.more;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.provider.Settings;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.GridLayout;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;

import com.google.android.material.dialog.MaterialAlertDialogBuilder;
import com.google.android.material.materialswitch.MaterialSwitch;
import com.onesignal.Continue;
import com.onesignal.OneSignal;

import in.naukripatra.app.BuildConfig;
import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.JobQuery;
import in.naukripatra.app.data.Prefs;
import in.naukripatra.app.ui.common.Dialogs;
import in.naukripatra.app.ui.info.InfoActivity;
import in.naukripatra.app.ui.main.Host;
import in.naukripatra.app.util.Links;
import in.naukripatra.app.util.Ui;

public class MoreFragment extends Fragment {

    private Host host;
    private View alertsRow;

    @Override
    public void onAttach(@NonNull Context context) {
        super.onAttach(context);
        host = (Host) context;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle state) {
        return inflater.inflate(R.layout.fragment_more, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        Ui.padForInsets(v.findViewById(R.id.header), Ui.TOP);
        v.findViewById(R.id.prefsCard).setOnClickListener(x -> host.openPreferences());

        GridLayout browse = v.findViewById(R.id.browse);
        addTile(browse, R.string.all_india_jobs, R.drawable.ic_public, R.color.brand_100, R.color.brand_700,
                () -> host.openJobs(JobQuery.allIndia()));
        addTile(browse, R.string.cat_usa, R.drawable.ic_flight_takeoff, R.color.teal_100, R.color.teal,
                () -> host.openJobs(JobQuery.usa()));
        addTile(browse, R.string.state_wise, R.drawable.ic_location_on, R.color.accent_100, R.color.accent,
                this::pickState);
        addTile(browse, R.string.qualification_wise, R.drawable.ic_school, R.color.purple_100, R.color.purple,
                this::pickQualification);

        LinearLayout settings = v.findViewById(R.id.settingsRows);
        addRow(settings, R.drawable.ic_dark_mode, R.color.brand_700, getString(R.string.theme), themeLabel(),
                () -> Dialogs.theme(requireActivity()));
        alertsRow = addRow(settings, R.drawable.ic_notifications_active, R.color.accent,
                getString(R.string.notifications), null, this::toggleAlerts);
        alertsRow.findViewById(R.id.toggle).setVisibility(View.VISIBLE);
        alertsRow.findViewById(R.id.chevron).setVisibility(View.GONE);
        if (Ads.privacyOptionsRequired(requireContext())) {
            addRow(settings, R.drawable.ic_privacy_tip, R.color.teal, getString(R.string.ad_privacy), null,
                    () -> Ads.showPrivacyOptions(requireActivity()));
        }

        LinearLayout app = v.findViewById(R.id.appRows);
        addRow(app, R.drawable.ic_share, R.color.green, getString(R.string.share_app), null,
                () -> Links.shareApp(requireContext()));
        addRow(app, R.drawable.ic_star, R.color.amber, getString(R.string.rate_app), null,
                () -> Links.rateApp(requireContext()));
        addRow(app, R.drawable.ic_info, R.color.brand_700, getString(R.string.about_us), null,
                () -> startActivity(InfoActivity.intent(requireContext(), InfoActivity.ABOUT)));
        addRow(app, R.drawable.ic_mail, R.color.pink, getString(R.string.contact_us), null,
                () -> startActivity(InfoActivity.intent(requireContext(), InfoActivity.CONTACT)));
        addRow(app, R.drawable.ic_privacy_tip, R.color.purple, getString(R.string.privacy_policy), null,
                () -> startActivity(InfoActivity.intent(requireContext(), InfoActivity.PRIVACY)));
        addRow(app, R.drawable.ic_gavel, R.color.text_2, getString(R.string.disclaimer), null,
                () -> startActivity(InfoActivity.intent(requireContext(), InfoActivity.DISCLAIMER)));

        ((TextView) v.findViewById(R.id.version)).setText(getString(R.string.version, BuildConfig.VERSION_NAME));
    }

    @Override
    public void onResume() {
        super.onResume();
        View v = getView();
        if (v == null) return;
        String q = Prefs.qualification(requireContext());
        String s = Prefs.state(requireContext());
        String title;
        if (q.isEmpty() && s.isEmpty()) title = getString(R.string.prefs_not_set);
        else if (q.isEmpty()) title = s;
        else if (s.isEmpty()) title = q;
        else title = q + " · " + s;
        ((TextView) v.findViewById(R.id.prefsTitle)).setText(title);
        updateAlerts();
    }

    private String themeLabel() {
        switch (Prefs.theme(requireContext())) {
            case Prefs.THEME_LIGHT:
                return getString(R.string.theme_light);
            case Prefs.THEME_DARK:
                return getString(R.string.theme_dark);
            default:
                return getString(R.string.theme_system);
        }
    }

    // ============ Job alerts (OneSignal push) ============

    private boolean alertsOn() {
        return OneSignal.getNotifications().getPermission()
                && OneSignal.getUser().getPushSubscription().getOptedIn();
    }

    private void updateAlerts() {
        if (alertsRow == null) return;
        boolean on = alertsOn();
        ((MaterialSwitch) alertsRow.findViewById(R.id.toggle)).setChecked(on);
        ((TextView) alertsRow.findViewById(R.id.value)).setText(on ? R.string.notifications_on : R.string.notifications_off);
    }

    private void toggleAlerts() {
        if (alertsOn()) {
            OneSignal.getUser().getPushSubscription().optOut();
        } else if (!OneSignal.getNotifications().getPermission()) {
            if (OneSignal.getNotifications().getCanRequestPermission()) {
                OneSignal.getNotifications().requestPermission(true, Continue.none());
            } else {
                openNotificationSettings();
            }
            OneSignal.getUser().getPushSubscription().optIn();
        } else {
            OneSignal.getUser().getPushSubscription().optIn();
        }
        alertsRow.postDelayed(this::updateAlerts, 400);
    }

    private void openNotificationSettings() {
        Intent intent;
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.O) {
            intent = new Intent(Settings.ACTION_APP_NOTIFICATION_SETTINGS)
                    .putExtra(Settings.EXTRA_APP_PACKAGE, requireContext().getPackageName());
        } else {
            intent = new Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                    android.net.Uri.fromParts("package", requireContext().getPackageName(), null));
        }
        try {
            startActivity(intent);
        } catch (Exception ignored) {
        }
    }

    // ============ Pickers ============

    private void pickState() {
        String[] states = Prefs.STATES;
        new MaterialAlertDialogBuilder(requireContext())
                .setTitle(R.string.select_state)
                .setItems(states, (d, which) -> host.openJobs(JobQuery.state(states[which])))
                .show();
    }

    private void pickQualification() {
        String[] items = Prefs.QUALIFICATIONS;
        new MaterialAlertDialogBuilder(requireContext())
                .setTitle(R.string.choose_qualification)
                .setItems(items, (d, which) -> host.openJobs(JobQuery.qualification(items[which])))
                .show();
    }

    // ============ Row builders ============

    private View addRow(LinearLayout parent, int icon, int color, String label, @Nullable String value, Runnable action) {
        View row = getLayoutInflater().inflate(R.layout.item_more_row, parent, false);
        ImageView iv = row.findViewById(R.id.icon);
        iv.setImageResource(icon);
        iv.setImageTintList(android.content.res.ColorStateList.valueOf(ContextCompat.getColor(requireContext(), color)));
        ((TextView) row.findViewById(R.id.label)).setText(label);
        Ui.textOrGone(row.findViewById(R.id.value), value);
        ((ImageView) row.findViewById(R.id.chevron)).setImageTintList(
                android.content.res.ColorStateList.valueOf(ContextCompat.getColor(requireContext(), R.color.text_3)));
        row.setOnClickListener(x -> action.run());
        if (parent.getChildCount() > 0) {
            View divider = new View(requireContext());
            divider.setBackgroundColor(ContextCompat.getColor(requireContext(), R.color.line));
            parent.addView(divider, new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, 1));
        }
        parent.addView(row);
        return row;
    }

    private void addTile(GridLayout grid, int label, int icon, int bg, int fg, Runnable action) {
        View tile = getLayoutInflater().inflate(R.layout.item_browse_tile, grid, false);
        Ui.tintBackground(tile, bg);
        ImageView iv = tile.findViewById(R.id.icon);
        iv.setImageResource(icon);
        Ui.tintBackground(iv, fg);
        ((TextView) tile.findViewById(R.id.label)).setText(label);
        GridLayout.LayoutParams lp = new GridLayout.LayoutParams(
                GridLayout.spec(GridLayout.UNDEFINED), GridLayout.spec(GridLayout.UNDEFINED, 1f));
        lp.width = 0;
        int gap = Ui.dp(requireContext(), 5);
        lp.setMargins(gap, gap, gap, gap);
        tile.setLayoutParams(lp);
        tile.setOnClickListener(x -> action.run());
        grid.addView(tile);
    }
}
