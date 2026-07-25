package com.naukripatra.emicalculator.util;

import android.content.Context;
import android.content.SharedPreferences;

import com.naukripatra.emicalculator.model.LoanType;

/**
 * Remembers the most recent EMI calculation so the History tab can show a recap without a
 * database — this app has no accounts or sync, so "history" is simply "your last calculation."
 */
public final class LastCalculationStore {

    private static final String PREFS_NAME = "last_calculation";
    private static final String KEY_LOAN_TYPE = "loan_type";
    private static final String KEY_LOAN_AMOUNT = "loan_amount";
    private static final String KEY_RATE = "rate";
    private static final String KEY_TENURE_MONTHS = "tenure_months";

    private LastCalculationStore() {
    }

    public static void save(Context context, LoanType loanType, double loanAmount, double rate, int tenureMonths) {
        prefs(context).edit()
                .putString(KEY_LOAN_TYPE, loanType.name())
                .putFloat(KEY_LOAN_AMOUNT, (float) loanAmount)
                .putFloat(KEY_RATE, (float) rate)
                .putInt(KEY_TENURE_MONTHS, tenureMonths)
                .apply();
    }

    public static boolean hasSaved(Context context) {
        return prefs(context).contains(KEY_LOAN_TYPE);
    }

    public static Saved load(Context context) {
        SharedPreferences prefs = prefs(context);
        String typeName = prefs.getString(KEY_LOAN_TYPE, null);
        if (typeName == null) {
            return null;
        }
        LoanType loanType;
        try {
            loanType = LoanType.valueOf(typeName);
        } catch (IllegalArgumentException e) {
            return null;
        }
        double loanAmount = prefs.getFloat(KEY_LOAN_AMOUNT, 0f);
        double rate = prefs.getFloat(KEY_RATE, 0f);
        int tenureMonths = prefs.getInt(KEY_TENURE_MONTHS, 0);
        return new Saved(loanType, loanAmount, rate, tenureMonths);
    }

    private static SharedPreferences prefs(Context context) {
        return context.getApplicationContext().getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
    }

    public static final class Saved {
        public final LoanType loanType;
        public final double loanAmount;
        public final double interestRate;
        public final int tenureMonths;

        Saved(LoanType loanType, double loanAmount, double interestRate, int tenureMonths) {
            this.loanType = loanType;
            this.loanAmount = loanAmount;
            this.interestRate = interestRate;
            this.tenureMonths = tenureMonths;
        }
    }
}
