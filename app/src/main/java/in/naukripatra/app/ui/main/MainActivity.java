package in.naukripatra.app.ui.main;

import android.content.Intent;
import android.os.Build;
import android.os.Bundle;

import androidx.activity.EdgeToEdge;
import androidx.activity.OnBackPressedCallback;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.splashscreen.SplashScreen;
import androidx.core.view.WindowCompat;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentManager;
import androidx.fragment.app.FragmentTransaction;

import com.google.android.material.navigation.NavigationBarView;
import com.onesignal.Continue;
import com.onesignal.OneSignal;

import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.JobQuery;
import in.naukripatra.app.data.Prefs;
import in.naukripatra.app.ui.detail.JobDetailActivity;
import in.naukripatra.app.ui.home.HomeFragment;
import in.naukripatra.app.ui.jobs.JobsFragment;
import in.naukripatra.app.ui.more.MoreFragment;
import in.naukripatra.app.ui.onboarding.OnboardingActivity;
import in.naukripatra.app.ui.saved.SavedFragment;

/**
 * Home / Jobs / Saved / More. The four tabs are created once and shown or hidden,
 * so switching tabs is instant and each keeps its scroll position.
 */
public class MainActivity extends AppCompatActivity implements Host {

    private static final String KEY_TAB = "tab";
    private static final String[] TAGS = {"home", "jobs", "saved", "more"};

    private NavigationBarView nav;
    private int currentTab = R.id.tab_home;
    private OnBackPressedCallback backToHome;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        SplashScreen.installSplashScreen(this);
        EdgeToEdge.enable(this);
        super.onCreate(savedInstanceState);

        if (!Prefs.onboarded(this)) {
            startActivity(new Intent(this, OnboardingActivity.class));
            finish();
            return;
        }

        setContentView(R.layout.activity_main);
        nav = findViewById(R.id.nav);

        FragmentManager fm = getSupportFragmentManager();
        if (savedInstanceState == null) {
            FragmentTransaction t = fm.beginTransaction().setReorderingAllowed(true);
            t.add(R.id.container, new HomeFragment(), TAGS[0]);
            t.add(R.id.container, new JobsFragment(), TAGS[1]);
            t.add(R.id.container, new SavedFragment(), TAGS[2]);
            t.add(R.id.container, new MoreFragment(), TAGS[3]);
            t.commitNow();
        } else {
            currentTab = savedInstanceState.getInt(KEY_TAB, R.id.tab_home);
        }
        showTab(currentTab);
        nav.setSelectedItemId(currentTab);
        nav.setOnItemSelectedListener(item -> {
            showTab(item.getItemId());
            return true;
        });
        nav.setOnItemReselectedListener(item -> {
        });

        backToHome = new OnBackPressedCallback(currentTab != R.id.tab_home) {
            @Override
            public void handleOnBackPressed() {
                nav.setSelectedItemId(R.id.tab_home);
            }
        };
        getOnBackPressedDispatcher().addCallback(this, backToHome);

        Ads.gatherConsent(this);
        askNotificationPermission();
    }

    @Override
    protected void onSaveInstanceState(@NonNull Bundle outState) {
        super.onSaveInstanceState(outState);
        outState.putInt(KEY_TAB, currentTab);
    }

    private void showTab(int tabId) {
        currentTab = tabId;
        int index = indexOf(tabId);
        FragmentManager fm = getSupportFragmentManager();
        FragmentTransaction t = fm.beginTransaction().setReorderingAllowed(true);
        for (int i = 0; i < TAGS.length; i++) {
            Fragment f = fm.findFragmentByTag(TAGS[i]);
            if (f == null) continue;
            if (i == index) t.show(f);
            else t.hide(f);
        }
        t.commitNowAllowingStateLoss();
        if (backToHome != null) backToHome.setEnabled(tabId != R.id.tab_home);
        // Home and More have a blue header; Jobs and Saved have a light top bar.
        boolean lightTop = (tabId == R.id.tab_jobs || tabId == R.id.tab_saved) && !isNight();
        WindowCompat.getInsetsController(getWindow(), getWindow().getDecorView())
                .setAppearanceLightStatusBars(lightTop);
    }

    private boolean isNight() {
        return in.naukripatra.app.util.Ui.isNight(this);
    }

    private static int indexOf(int tabId) {
        if (tabId == R.id.tab_jobs) return 1;
        if (tabId == R.id.tab_saved) return 2;
        if (tabId == R.id.tab_more) return 3;
        return 0;
    }

    private JobsFragment jobs() {
        return (JobsFragment) getSupportFragmentManager().findFragmentByTag(TAGS[1]);
    }

    /** Android 13+ needs permission for job alert notifications. Asked once, after onboarding. */
    private void askNotificationPermission() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU) return;
        if (OneSignal.getNotifications().getPermission()) return;
        android.content.SharedPreferences p = getSharedPreferences("naukripatra", MODE_PRIVATE);
        if (p.getBoolean("asked_notifications_v2", false)) return;
        p.edit().putBoolean("asked_notifications_v2", true).apply();
        OneSignal.getNotifications().requestPermission(false, Continue.none());
    }

    // ============ Host ============

    @Override
    public void openJobs(JobQuery query) {
        JobsFragment f = jobs();
        if (f != null) f.show(query);
        nav.setSelectedItemId(R.id.tab_jobs);
    }

    @Override
    public void openSearch() {
        nav.setSelectedItemId(R.id.tab_jobs);
        JobsFragment f = jobs();
        if (f != null) f.focusSearch();
    }

    @Override
    public void openJob(Job job) {
        Ads.onJobOpened(this);
        startActivity(JobDetailActivity.forId(this, job.id));
    }

    @Override
    public void openPreferences() {
        startActivity(OnboardingActivity.edit(this));
    }
}
