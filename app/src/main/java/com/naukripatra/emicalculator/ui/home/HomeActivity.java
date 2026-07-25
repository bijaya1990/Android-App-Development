package com.naukripatra.emicalculator.ui.home;

import android.content.Intent;
import android.os.Bundle;
import android.view.MenuItem;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivityHomeBinding;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.ui.calculator.CalculatorActivity;
import com.naukripatra.emicalculator.ui.settings.SettingsActivity;

public class HomeActivity extends AppCompatActivity {

    private ActivityHomeBinding binding;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityHomeBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        applyEdgeToEdgeInsets();

        binding.toolbar.inflateMenu(R.menu.menu_home);
        binding.toolbar.setOnMenuItemClickListener(this::onMenuItemClick);

        binding.recyclerLoanTypes.setHasFixedSize(true);
        binding.recyclerLoanTypes.setItemAnimator(null);
        binding.recyclerLoanTypes.setAdapter(new LoanCardAdapter(this::openCalculator));
    }

    private void applyEdgeToEdgeInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.toolbar, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), bars.top, view.getPaddingRight(), view.getPaddingBottom());
            return insets;
        });
        ViewCompat.setOnApplyWindowInsetsListener(binding.recyclerLoanTypes, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), view.getPaddingTop(), view.getPaddingRight(), bars.bottom);
            return insets;
        });
    }

    private boolean onMenuItemClick(MenuItem item) {
        if (item.getItemId() == R.id.action_settings) {
            startActivity(new Intent(this, SettingsActivity.class));
            overridePendingTransition(R.anim.slide_in_right, R.anim.fade_out);
            return true;
        }
        return false;
    }

    private void openCalculator(LoanType loanType) {
        Intent intent = new Intent(this, CalculatorActivity.class);
        intent.putExtra(CalculatorActivity.EXTRA_LOAN_TYPE, loanType.name());
        startActivity(intent);
        overridePendingTransition(R.anim.slide_in_right, R.anim.fade_out);
    }
}
