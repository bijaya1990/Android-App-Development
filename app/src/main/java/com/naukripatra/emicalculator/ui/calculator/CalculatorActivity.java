package com.naukripatra.emicalculator.ui.calculator;

import android.content.Intent;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;

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
import com.naukripatra.emicalculator.ui.result.ResultActivity;

public class CalculatorActivity extends AppCompatActivity {

    public static final String EXTRA_LOAN_TYPE = "extra_loan_type";

    private ActivityCalculatorBinding binding;
    private CalculatorViewModel viewModel;
    private LoanType loanType;
    private boolean autoFillingLoanAmount = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityCalculatorBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        loanType = resolveLoanType();
        viewModel = new ViewModelProvider(this).get(CalculatorViewModel.class);

        applyEdgeToEdgeInsets();
        setupToolbar();
        applyLoanTypeVisibility();
        setupAutoLoanAmountCalculation();
        setupErrorClearing();

        binding.btnCalculate.setOnClickListener(v -> onCalculateClicked());
        binding.btnReset.setOnClickListener(v -> resetForm());
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

    private void setupToolbar() {
        binding.toolbar.setTitle(loanType.getTitleRes());
        binding.toolbar.setNavigationOnClickListener(v -> {
            getOnBackPressedDispatcher().onBackPressed();
            overridePendingTransition(R.anim.slide_in_left, R.anim.slide_out_right);
        });
    }

    private void applyEdgeToEdgeInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.toolbar, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), bars.top, view.getPaddingRight(), view.getPaddingBottom());
            return insets;
        });
    }

    private void applyLoanTypeVisibility() {
        int visibility = loanType.showsPriceAndDownPayment() ? android.view.View.VISIBLE : android.view.View.GONE;
        binding.inputTotalPriceLayout.setVisibility(visibility);
        binding.inputDownPaymentLayout.setVisibility(visibility);
        if (loanType.showsPriceAndDownPayment()) {
            binding.inputTotalPriceLayout.setHint(getString(R.string.label_total_price, loanType.getNoun()));
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
        clearErrorOnEdit(binding.inputTenureYears, binding.inputTenureYearsLayout);
        clearErrorOnEdit(binding.inputTenureMonths, binding.inputTenureMonthsLayout);
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
                textOf(binding.inputTenureYears),
                textOf(binding.inputTenureMonths));

        binding.inputTotalPriceLayout.setError(errorText(result.totalPriceError));
        binding.inputDownPaymentLayout.setError(errorText(result.downPaymentError));
        binding.inputLoanAmountLayout.setError(errorText(result.loanAmountError));
        binding.inputInterestRateLayout.setError(errorText(result.rateError));
        binding.inputTenureYearsLayout.setError(errorText(result.tenureError));
        binding.inputTenureMonthsLayout.setError(errorText(result.tenureError));

        if (!result.isValid()) {
            return;
        }

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
        binding.inputTenureYears.setText("");
        binding.inputTenureMonths.setText("");

        binding.inputTotalPriceLayout.setError(null);
        binding.inputDownPaymentLayout.setError(null);
        binding.inputLoanAmountLayout.setError(null);
        binding.inputInterestRateLayout.setError(null);
        binding.inputTenureYearsLayout.setError(null);
        binding.inputTenureMonthsLayout.setError(null);
    }

    private String errorText(Integer resId) {
        return resId == null ? null : getString(resId);
    }

    private String textOf(TextInputEditText editText) {
        return editText.getText() == null ? "" : editText.getText().toString();
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
