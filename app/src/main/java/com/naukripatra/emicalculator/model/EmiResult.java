package com.naukripatra.emicalculator.model;

import java.util.List;

/** Full outcome of an EMI calculation: headline figures plus the month-by-month schedule. */
public class EmiResult {

    private final double loanAmount;
    private final double interestRate;
    private final int tenureMonths;
    private final double monthlyEmi;
    private final double totalInterest;
    private final double totalPayment;
    private final List<ScheduleEntry> schedule;

    public EmiResult(double loanAmount, double interestRate, int tenureMonths, double monthlyEmi,
                      double totalInterest, double totalPayment, List<ScheduleEntry> schedule) {
        this.loanAmount = loanAmount;
        this.interestRate = interestRate;
        this.tenureMonths = tenureMonths;
        this.monthlyEmi = monthlyEmi;
        this.totalInterest = totalInterest;
        this.totalPayment = totalPayment;
        this.schedule = schedule;
    }

    public double getLoanAmount() {
        return loanAmount;
    }

    public double getInterestRate() {
        return interestRate;
    }

    public int getTenureMonths() {
        return tenureMonths;
    }

    public double getMonthlyEmi() {
        return monthlyEmi;
    }

    public double getTotalInterest() {
        return totalInterest;
    }

    public double getTotalPayment() {
        return totalPayment;
    }

    public List<ScheduleEntry> getSchedule() {
        return schedule;
    }
}
