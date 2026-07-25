package com.naukripatra.emicalculator.ui.calculator;

import android.content.Intent;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.View;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.lifecycle.ViewModelProvider;

import com.google.android.material.textfield.TextInputEditText;
import com.google.android.material.textfield.TextInputLayout;
import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivityCalculatorBinding;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.ui.nav.BottomNavHelper;
import com.naukripatra.emicalculator.ui.result.ResultActivity;
import com.naukripatra.emicalculator.util.AdConfig;
import com.naukripatra.emicalculator.util.InterstitialAdManager;

public class CalculatorActivity extends AppCompatActivity {

    public static final String EXTRA_LOAN_TYPE = "extra_loan_type";

    private ActivityCalculatorBinding binding;
    private CalculatorViewModel viewModel;
    private LoanType loanType;
    private boolean autoFillingLoanAmount = false;
    private boolean tenureInYears = true;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityCalculatorBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        loanType = resolveLoanType();
        viewModel = new ViewModelProvider(this).get(CalculatorViewModel.class);

        applyEdgeToEdgeInsets();
        setupHeader();
        setupHero();
        applyLoanTypeVisibility();
        setupTenureToggle();
        setupAutoLoanAmountCalculation();
        setupErrorClearing();

        binding.btnCalculate.setOnClickListener(v -> onCalculateClicked());
        binding.btnReset.setOnClickListener(v -> resetForm());

        BottomNavHelper.setup(this, binding.bottomNav, BottomNavHelper.Destination.HOME);
        AdConfig.loadBanner(this, binding.adBannerContainer.adBanner);
        InterstitialAdManager.preload(this);
    }

    private LoanType resolveLoanType() {
        String name = getIntent().getStringExtra(EXTRA_LOAN_TYPE);
        if (name == null) {
            return LoanType.CAR;
        }
        try {
            return LoanType.valueOf(name);
        } catch (IllegalArgumentException e) {
            return LoanType.CAR;
        }
    }

    private void setupHeader() {
        binding.txtTitle.setText(loanType.getTitleRes());
        binding.btnBack.setOnClickListener(v -> {
            getOnBackPressedDispatcher().onBackPressed();
            overridePendingTransition(R.anim.slide_in_left, R.anim.slide_out_right);
        });
    }

    private void setupHero() {
        binding.heroBanner.setBackgroundResource(loanType.getHeroBackgroundRes());
        binding.heroWatermark.setImageResource(loanType.getIconRes());
        binding.heroTitle.setText(loanType.getHeroTitleRes());
        binding.heroSubtitle.setText(loanType.getHeroSubtitleRes());
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

    private void applyLoanTypeVisibility() {
        int visibility = loanType.showsPriceAndDownPayment() ? View.VISIBLE : View.GONE;
        binding.inputTotalPriceLayout.setVisibility(visibility);
        binding.inputDownPaymentLayout.setVisibility(visibility);
        if (loanType.showsPriceAndDownPayment()) {
            binding.inputTotalPriceLayout.setHint(getString(R.string.label_total_price, loanType.getNoun()));
        }
    }

    private void setupTenureToggle() {
        binding.btnYears.setOnClickListener(v -> setTenureMode(true));
        binding.btnMonths.setOnClickListener(v -> setTenureMode(false));
        setTenureMode(true);
    }

    private void setTenureMode(boolean years) {
        tenureInYears = years;
        if (years) {
            binding.btnYears.setBackgroundResource(R.drawable.bg_pill_selected);
            binding.btnYears.setBackgroundTintList(android.content.res.ColorStateList.valueOf(getColor(R.color.md_primary)));
            binding.btnYears.setTextColor(getColor(R.color.md_on_primary));
            binding.btnMonths.setBackground(null);
            binding.btnMonths.setTextColor(getColor(R.color.md_on_surface_variant));
            binding.inputTenureLayout.setSuffixText(getString(R.string.tenure_unit_years));
        } else {
            binding.btnMonths.setBackgroundResource(R.drawable.bg_pill_selected);
            binding.btnMonths.setBackgroundTintList(android.content.res.ColorStateList.valueOf(getColor(R.color.md_primary)));
            binding.btnMonths.setTextColor(getColor(R.color.md_on_primary));
            binding.btnYears.setBackground(null);
            binding.btnYears.setTextColor(getColor(R.color.md_on_surface_variant));
            binding.inputTenureLayout.setSuffixText(getString(R.string.tenure_unit_months));
        }
    }

    private void setupAutoLoanAmountCalculation() {
        TextWatcher recomputeWatcher = new SimpleTextWatcher(() -> {
            if (loanType.showsPriceAndDownPayment()) {
                viewModel.recomputeLoanAmount(
                        textOf(binding.inputTotalPrice), textOf(binding.inputDownPayment));
            }
        });
        binding.inputTotalPrice.addTextChangedListener(recomputeWatcher);
        binding.inputDownPayment.addTextChangedListener(recomputeWatcher);

        viewModel.getAutoLoanAmount().observe(this, amountText -> {
            autoFillingLoanAmount = true;
            binding.inputLoanAmount.setText(amountText);
            binding.inputLoanAmount.setSelection(amountText.length());
            autoFillingLoanAmount = false;
        });
    }

    private void setupErrorClearing() {
        clearErrorOnEdit(binding.inputTotalPrice, binding.inputTotalPriceLayout);
        clearErrorOnEdit(binding.inputDownPayment, binding.inputDownPaymentLayout);
        clearErrorOnEdit(binding.inputLoanAmount, binding.inputLoanAmountLayout);
        clearErrorOnEdit(binding.inputInterestRate, binding.inputInterestRateLayout);
        clearErrorOnEdit(binding.inputTenure, binding.inputTenureLayout);
    }

    private void clearErrorOnEdit(TextInputEditText editText, TextInputLayout layout) {
        editText.addTextChangedListener(new SimpleTextWatcher(() -> {
            if (editText == binding.inputLoanAmount && autoFillingLoanAmount) {
                return;
            }
            layout.setError(null);
        }));
    }

    private void onCalculateClicked() {
        FormValidationResult result = viewModel.validate(
                loanType,
                textOf(binding.inputTotalPrice),
                textOf(binding.inputDownPayment),
                textOf(binding.inputLoanAmount),
                textOf(binding.inputInterestRate),
                textOf(binding.inputTenure),
                tenureInYears);

        binding.inputTotalPriceLayout.setError(errorText(result.totalPriceError));
        binding.inputDownPaymentLayout.setError(errorText(result.downPaymentError));
        binding.inputLoanAmountLayout.setError(errorText(result.loanAmountError));
        binding.inputInterestRateLayout.setError(errorText(result.rateError));
        binding.inputTenureLayout.setError(errorText(result.tenureError));

        if (!result.isValid()) {
            return;
        }

        InterstitialAdManager.showIfEligible(this, () -> navigateToResult(result));
    }

    private void navigateToResult(FormValidationResult result) {
        Intent intent = new Intent(this, ResultActivity.class);
        intent.putExtra(ResultActivity.EXTRA_LOAN_TYPE, loanType.name());
        intent.putExtra(ResultActivity.EXTRA_LOAN_AMOUNT, result.loanAmount);
        intent.putExtra(ResultActivity.EXTRA_INTEREST_RATE, result.interestRate);
        intent.putExtra(ResultActivity.EXTRA_TENURE_MONTHS, result.tenureMonths);
        startActivity(intent);
        overridePendingTransition(R.anim.slide_in_right, R.anim.fade_out);
    }

    private void resetForm() {
        binding.inputTotalPrice.setText("");
        binding.inputDownPayment.setText("");
        binding.inputLoanAmount.setText("");
        binding.inputInterestRate.setText("");
        binding.inputTenure.setText("");

        binding.inputTotalPriceLayout.setError(null);
        binding.inputDownPaymentLayout.setError(null);
        binding.inputLoanAmountLayout.setError(null);
        binding.inputInterestRateLayout.setError(null);
        binding.inputTenureLayout.setError(null);
    }

    private String errorText(Integer resId) {
        return resId == null ? null : getString(resId);
    }

    private String textOf(TextInputEditText editText) {
        return editText.getText() == null ? "" : editText.getText().toString();
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

    /** Collapses the four-argument TextWatcher interface into a single "something changed" callback. */
    private static class SimpleTextWatcher implements TextWatcher {
        interface Callback {
            void onChanged();
        }

        private final Callback callback;

        SimpleTextWatcher(Callback callback) {
            this.callback = callback;
        }

        @Override
        public void beforeTextChanged(CharSequence s, int start, int count, int after) {
        }

        @Override
        public void onTextChanged(CharSequence s, int start, int before, int count) {
        }

        @Override
        public void afterTextChanged(Editable s) {
            callback.onChanged();
        }
    }
}
