package com.naukripatra.emicalculator.model;

/** A single row of an amortization schedule. */
public class ScheduleEntry {

    private final int month;
    private final double emi;
    private final double principal;
    private final double interest;
    private final double balance;

    public ScheduleEntry(int month, double emi, double principal, double interest, double balance) {
        this.month = month;
        this.emi = emi;
        this.principal = principal;
        this.interest = interest;
        this.balance = balance;
    }

    public int getMonth() {
        return month;
    }

    public double getEmi() {
        return emi;
    }

    public double getPrincipal() {
        return principal;
    }

    public double getInterest() {
        return interest;
    }

    public double getBalance() {
        return balance;
    }
}
