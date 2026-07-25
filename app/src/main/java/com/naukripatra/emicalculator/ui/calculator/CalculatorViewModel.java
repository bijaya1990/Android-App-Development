package com.naukripatra.emicalculator.ui.calculator;

import androidx.lifecycle.LiveData;
import androidx.lifecycle.MutableLiveData;
import androidx.lifecycle.ViewModel;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.util.ValidationUtils;

/**
 * Holds the calculator screen's derived state: the auto-computed loan amount
 * (Total Price − Down Payment) and full form validation. Kept independent of
 * any Android view so it survives configuration changes and stays unit-testable.
 */
public class CalculatorViewModel extends ViewModel {

    private final MutableLiveData<String> autoLoanAmount = new MutableLiveData<>();

    public LiveData<String> getAutoLoanAmount() {
        return autoLoanAmount;
    }

    /** Recomputes Loan Amount = Total Price − Down Payment whenever either source field changes. */
    public void recomputeLoanAmount(String totalPriceText, String downPaymentText) {
        double total = ValidationUtils.parseOrNaN(totalPriceText);
        double down = ValidationUtils.parseOrNaN(downPaymentText);

        if (Double.isNaN(total)) {
            return;
        }
        double downPayment = Double.isNaN(down) ? 0 : down;
        double loanAmount = total - downPayment;
        if (loanAmount < 0) {
            loanAmount = 0;
        }
        autoLoanAmount.setValue(formatPlain(loanAmount));
    }

    private String formatPlain(double value) {
        if (value == Math.rint(value)) {
            return String.valueOf((long) value);
        }
        return String.valueOf(value);
    }

    public FormValidationResult validate(LoanType loanType, String totalPriceText, String downPaymentText,
                                          String loanAmountText, String rateText, String yearsText, String monthsText) {
        FormValidationResult result = new FormValidationResult();

        double totalPrice = Double.NaN;
        double downPayment = Double.NaN;

        if (loanType.showsPriceAndDownPayment()) {
            totalPrice = ValidationUtils.parseOrNaN(totalPriceText);
            if (ValidationUtils.isBlank(totalPriceText)) {
                result.totalPriceError = R.string.error_required;
            } else if (Double.isNaN(totalPrice)) {
                result.totalPriceError = R.string.error_invalid_number;
            } else if (ValidationUtils.isNegative(totalPrice)) {
                result.totalPriceError = R.string.error_negative;
            }

            downPayment = ValidationUtils.parseOrNaN(downPaymentText);
            if (ValidationUtils.isBlank(downPaymentText)) {
                result.downPaymentError = R.string.error_required;
            } else if (Double.isNaN(downPayment)) {
                result.downPaymentError = R.string.error_invalid_number;
            } else if (ValidationUtils.isNegative(downPayment)) {
                result.downPaymentError = R.string.error_negative;
            } else if (result.totalPriceError == null && downPayment > totalPrice) {
                result.downPaymentError = R.string.error_down_payment_exceeds;
            }
        }

        double loanAmount = ValidationUtils.parseOrNaN(loanAmountText);
        if (ValidationUtils.isBlank(loanAmountText)) {
            result.loanAmountError = R.string.error_required;
        } else if (Double.isNaN(loanAmount)) {
            result.loanAmountError = R.string.error_invalid_number;
        } else if (ValidationUtils.isNegative(loanAmount)) {
            result.loanAmountError = R.string.error_negative;
        } else if (loanAmount <= 0) {
            result.loanAmountError = R.string.error_loan_amount_zero;
        }

        double rate = ValidationUtils.parseOrNaN(rateText);
        if (ValidationUtils.isBlank(rateText)) {
            result.rateError = R.string.error_required;
        } else if (Double.isNaN(rate)) {
            result.rateError = R.string.error_invalid_number;
        } else if (ValidationUtils.isNegative(rate)) {
            result.rateError = R.string.error_negative;
        } else if (rate <= 0) {
            result.rateError = R.string.error_rate_zero;
        }

        double years = ValidationUtils.isBlank(yearsText) ? 0 : ValidationUtils.parseOrNaN(yearsText);
        double months = ValidationUtils.isBlank(monthsText) ? 0 : ValidationUtils.parseOrNaN(monthsText);
        boolean tenureInvalidNumber = (!ValidationUtils.isBlank(yearsText) && Double.isNaN(years))
                || (!ValidationUtils.isBlank(monthsText) && Double.isNaN(months));
        int tenureMonths = 0;
        if (tenureInvalidNumber) {
            result.tenureError = R.string.error_invalid_number;
        } else if (ValidationUtils.isNegative(years) || ValidationUtils.isNegative(months)) {
            result.tenureError = R.string.error_negative;
        } else {
            tenureMonths = (int) Math.round(years) * 12 + (int) Math.round(months);
            if (tenureMonths <= 0) {
                result.tenureError = R.string.error_tenure_zero;
            }
        }

        result.loanAmount = loanAmount;
        result.interestRate = rate;
        result.tenureMonths = tenureMonths;
        return result;
    }
}
