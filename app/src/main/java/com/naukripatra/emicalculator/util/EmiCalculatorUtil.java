package com.naukripatra.emicalculator.util;

import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.model.ScheduleEntry;

import java.util.ArrayList;
import java.util.List;

/**
 * Pure financial math for EMI calculation and amortization schedules.
 * Standard reducing-balance EMI formula:
 *   EMI = P * r * (1 + r)^n / ((1 + r)^n - 1)
 * where r is the monthly interest rate and n is the tenure in months.
 */
public final class EmiCalculatorUtil {

    private EmiCalculatorUtil() {
    }

    public static EmiResult calculate(double loanAmount, double annualInterestRate, int tenureMonths) {
        double monthlyRate = annualInterestRate / 12.0 / 100.0;
        double emi;

        if (monthlyRate == 0) {
            emi = loanAmount / tenureMonths;
        } else {
            double factor = Math.pow(1 + monthlyRate, tenureMonths);
            emi = loanAmount * monthlyRate * factor / (factor - 1);
        }

        List<ScheduleEntry> schedule = new ArrayList<>(tenureMonths);
        double balance = loanAmount;
        double totalInterest = 0;

        for (int month = 1; month <= tenureMonths; month++) {
            double interestForMonth = balance * monthlyRate;
            double principalForMonth = emi - interestForMonth;

            // Absorb rounding drift into the final installment so the balance lands exactly on zero.
            if (month == tenureMonths) {
                principalForMonth = balance;
                emi = principalForMonth + interestForMonth;
            }

            balance -= principalForMonth;
            if (balance < 0) {
                balance = 0;
            }
            totalInterest += interestForMonth;

            schedule.add(new ScheduleEntry(month, emi, principalForMonth, interestForMonth, balance));
        }

        double firstEmi = schedule.isEmpty() ? emi : schedule.get(0).getEmi();
        double totalPayment = loanAmount + totalInterest;

        return new EmiResult(loanAmount, annualInterestRate, tenureMonths, firstEmi, totalInterest, totalPayment, schedule);
    }
}
