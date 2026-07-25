package com.naukripatra.emicalculator.ui.result;

import androidx.lifecycle.ViewModel;

import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.util.EmiCalculatorUtil;

/**
 * Shared between {@link ResultActivity} and its Summary/Chart/Schedule fragments so the
 * EMI calculation runs exactly once and survives configuration changes.
 */
public class ResultViewModel extends ViewModel {

    private EmiResult result;
    private LoanType loanType;
    private boolean initialized = false;

    public void init(LoanType loanType, double loanAmount, double interestRate, int tenureMonths) {
        if (initialized) {
            return;
        }
        this.loanType = loanType;
        this.result = EmiCalculatorUtil.calculate(loanAmount, interestRate, tenureMonths);
        this.initialized = true;
    }

    public EmiResult getResult() {
        return result;
    }

    public LoanType getLoanType() {
        return loanType;
    }
}
