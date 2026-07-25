package com.naukripatra.emicalculator.ui.history;

import android.content.Intent;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivityHistoryBinding;
import com.naukripatra.emicalculator.ui.home.HomeActivity;
import com.naukripatra.emicalculator.ui.nav.BottomNavHelper;
import com.naukripatra.emicalculator.ui.schedule.ScheduleActivity;
import com.naukripatra.emicalculator.util.AdConfig;
import com.naukripatra.emicalculator.util.LastCalculationStore;

/**
 * The History tab has no accounts or database to browse — it simply reopens the schedule
 * for the last EMI you calculated. When nothing has been calculated yet, it shows an empty
 * state instead of a blank screen.
 */
public class HistoryActivity extends AppCompatActivity {

    private ActivityHistoryBinding binding;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        LastCalculationStore.Saved saved = LastCalculationStore.load(this);
        if (saved != null) {
            Intent intent = new Intent(this, ScheduleActivity.class);
            intent.putExtra(ScheduleActivity.EXTRA_LOAN_TYPE, saved.loanType.name());
            intent.putExtra(ScheduleActivity.EXTRA_LOAN_AMOUNT, saved.loanAmount);
            intent.putExtra(ScheduleActivity.EXTRA_INTEREST_RATE, saved.interestRate);
            intent.putExtra(ScheduleActivity.EXTRA_TENURE_MONTHS, saved.tenureMonths);
            startActivity(intent);
            finish();
            return;
        }

        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityHistoryBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        ViewCompat.setOnApplyWindowInsetsListener(binding.header, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), bars.top, view.getPaddingRight(), view.getPaddingBottom());
            return insets;
        });
        ViewCompat.setOnApplyWindowInsetsListener(binding.bottomNav.getRoot(), (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), view.getPaddingTop(), view.getPaddingRight(), bars.bottom);
            return insets;
        });

        binding.btnGoHome.setOnClickListener(v -> {
            Intent intent = new Intent(this, HomeActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT | Intent.FLAG_ACTIVITY_CLEAR_TOP);
            startActivity(intent);
        });

        BottomNavHelper.setup(this, binding.bottomNav, BottomNavHelper.Destination.HISTORY);
        AdConfig.loadBanner(binding.adBannerContainer.adBanner);
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (binding != null) {
            binding.adBannerContainer.adBanner.resume();
        }
    }

    @Override
    protected void onPause() {
        if (binding != null) {
            binding.adBannerContainer.adBanner.pause();
        }
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        if (binding != null) {
            binding.adBannerContainer.adBanner.destroy();
        }
        super.onDestroy();
    }
}
