package com.naukripatra.emicalculator.util;

/** Small helpers shared by the calculator screen's input validation. */
public final class ValidationUtils {

    private ValidationUtils() {
    }

    public static boolean isBlank(String text) {
        return text == null || text.trim().isEmpty();
    }

    /** Parses a numeric field, returning {@link Double#NaN} when the text isn't a valid non-negative number. */
    public static double parseOrNaN(String text) {
        if (isBlank(text)) {
            return Double.NaN;
        }
        try {
            double value = Double.parseDouble(text.trim());
            return value;
        } catch (NumberFormatException e) {
            return Double.NaN;
        }
    }

    public static boolean isNegative(double value) {
        return !Double.isNaN(value) && value < 0;
    }
}
