package com.naukripatra.emicalculator.ui.calculator;

/** Outcome of validating the calculator form: either a per-field error map, or parsed numeric inputs ready to calculate. */
public class FormValidationResult {

    public Integer totalPriceError;
    public Integer downPaymentError;
    public Integer loanAmountError;
    public Integer rateError;
    public Integer tenureError;

    public double loanAmount;
    public double interestRate;
    public int tenureMonths;

    public boolean isValid() {
        return totalPriceError == null && downPaymentError == null && loanAmountError == null
                && rateError == null && tenureError == null;
    }
}
