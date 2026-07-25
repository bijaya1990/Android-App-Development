package com.naukripatra.emicalculator.util;

import java.text.DecimalFormat;
import java.text.DecimalFormatSymbols;
import java.util.Locale;

/** Formats amounts using Indian digit grouping (e.g. 12,34,567) with a rupee symbol. */
public final class CurrencyUtils {

    private static final DecimalFormat AMOUNT_FORMAT;

    static {
        DecimalFormatSymbols symbols = new DecimalFormatSymbols(new Locale("en", "IN"));
        AMOUNT_FORMAT = new DecimalFormat("##,##,##0", symbols);
    }

    private CurrencyUtils() {
    }

    public static String formatWholeRupees(double amount) {
        return "₹" + AMOUNT_FORMAT.format(Math.round(amount));
    }

    public static String formatPlainNumber(double amount) {
        return AMOUNT_FORMAT.format(Math.round(amount));
    }

    public static String formatPercent(double rate) {
        if (rate == Math.floor(rate)) {
            return String.format(Locale.getDefault(), "%.0f", rate);
        }
        return String.format(Locale.getDefault(), "%.2f", rate);
    }
}
