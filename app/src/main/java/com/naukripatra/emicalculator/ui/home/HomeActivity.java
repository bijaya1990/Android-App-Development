package com.naukripatra.emicalculator.ui.home;

import android.content.Intent;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivityHomeBinding;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.ui.calculator.CalculatorActivity;
import com.naukripatra.emicalculator.ui.nav.BottomNavHelper;
import com.naukripatra.emicalculator.ui.settings.SettingsActivity;
import com.naukripatra.emicalculator.util.AdConfig;

import java.util.Calendar;

public class HomeActivity extends AppCompatActivity {

    private ActivityHomeBinding binding;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityHomeBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        applyEdgeToEdgeInsets();

        binding.txtGreeting.setText(currentGreeting());
        binding.btnMenu.setOnClickListener(v -> startActivity(new Intent(this, SettingsActivity.class)));
        binding.btnAvatar.setOnClickListener(v -> startActivity(new Intent(this, SettingsActivity.class)));

        binding.recyclerLoanTypes.setHasFixedSize(true);
        binding.recyclerLoanTypes.setItemAnimator(null);
        binding.recyclerLoanTypes.setAdapter(new LoanCardAdapter(this::openCalculator));

        BottomNavHelper.setup(this, binding.bottomNav, BottomNavHelper.Destination.HOME);
        AdConfig.loadBanner(binding.adBannerContainer.adBanner);
    }

    private String currentGreeting() {
        int hour = Calendar.getInstance().get(Calendar.HOUR_OF_DAY);
        if (hour < 12) {
            return getString(R.string.home_greeting_morning);
        } else if (hour < 18) {
            return getString(R.string.home_greeting_afternoon);
        }
        return getString(R.string.home_greeting_evening);
    }

    private void applyEdgeToEdgeInsets() {
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
    }

    private void openCalculator(LoanType loanType) {
        Intent intent = new Intent(this, CalculatorActivity.class);
        intent.putExtra(CalculatorActivity.EXTRA_LOAN_TYPE, loanType.name());
        startActivity(intent);
        overridePendingTransition(R.anim.slide_in_right, R.anim.fade_out);
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
