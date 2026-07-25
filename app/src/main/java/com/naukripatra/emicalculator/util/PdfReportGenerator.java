package com.naukripatra.emicalculator.util;

import android.content.Context;
import android.graphics.Canvas;
import android.graphics.Color;
import android.graphics.Paint;
import android.graphics.RectF;
import android.graphics.pdf.PdfDocument;

import androidx.core.content.ContextCompat;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.model.ScheduleEntry;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Locale;

/**
 * Builds a professional multi-page PDF report using the platform's built-in
 * {@link PdfDocument} — no third-party PDF dependency is required, which keeps
 * the app lightweight and avoids extra licensing considerations.
 */
public final class PdfReportGenerator {

    private static final int PAGE_WIDTH = 595;  // A4 at 72dpi
    private static final int PAGE_HEIGHT = 842;
    private static final int MARGIN = 40;

    private PdfReportGenerator() {
    }

    public static File generate(Context context, LoanType loanType, EmiResult result) throws IOException {
        PdfDocument document = new PdfDocument();
        int primaryColor = ContextCompat.getColor(context, R.color.md_primary);
        int principalColor = ContextCompat.getColor(context, R.color.chart_principal);
        int interestColor = ContextCompat.getColor(context, R.color.chart_interest);

        Paint title = textPaint(20, true, Color.BLACK);
        Paint subtitle = textPaint(12, false, Color.DKGRAY);
        Paint sectionHeader = textPaint(14, true, primaryColor);
        Paint label = textPaint(11, false, Color.DKGRAY);
        Paint value = textPaint(11, true, Color.BLACK);
        Paint small = textPaint(9, false, Color.GRAY);

        // ---- Page 1: Summary ----
        PdfDocument.Page page1 = document.startPage(new PdfDocument.PageInfo.Builder(PAGE_WIDTH, PAGE_HEIGHT, 1).create());
        Canvas canvas = page1.getCanvas();
        int y = MARGIN;

        canvas.drawText(context.getString(R.string.app_name), MARGIN, y, title);
        y += 20;
        canvas.drawText(context.getString(loanType.getTitleRes()) + " — EMI Report", MARGIN, y, subtitle);
        y += 30;

        canvas.drawText("Loan Details", MARGIN, y, sectionHeader);
        y += 10;
        canvas.drawLine(MARGIN, y, PAGE_WIDTH - MARGIN, y, linePaint());
        y += 20;

        y = drawRow(canvas, "Loan Amount", CurrencyUtils.formatWholeRupees(result.getLoanAmount()), y, label, value);
        y = drawRow(canvas, "Interest Rate", CurrencyUtils.formatPercent(result.getInterestRate()) + "% p.a.", y, label, value);
        y = drawRow(canvas, "Tenure", formatTenure(result.getTenureMonths()), y, label, value);
        y += 20;

        canvas.drawText("EMI Summary", MARGIN, y, sectionHeader);
        y += 10;
        canvas.drawLine(MARGIN, y, PAGE_WIDTH - MARGIN, y, linePaint());
        y += 20;

        y = drawRow(canvas, "Monthly EMI", CurrencyUtils.formatWholeRupees(result.getMonthlyEmi()), y, label, value);
        y = drawRow(canvas, "Principal Amount", CurrencyUtils.formatWholeRupees(result.getLoanAmount()), y, label, value);
        y = drawRow(canvas, "Total Interest", CurrencyUtils.formatWholeRupees(result.getTotalInterest()), y, label, value);
        y = drawRow(canvas, "Total Payment", CurrencyUtils.formatWholeRupees(result.getTotalPayment()), y, label, value);
        y += 30;

        canvas.drawText("Principal vs Interest", MARGIN, y, sectionHeader);
        y += 20;
        drawPieChart(canvas, PAGE_WIDTH / 2f, y + 90, 80,
                result.getLoanAmount(), result.getTotalInterest(), principalColor, interestColor);
        y += 200;

        drawLegendDot(canvas, MARGIN, y, principalColor);
        canvas.drawText("Principal", MARGIN + 16, y + 5, label);
        drawLegendDot(canvas, MARGIN + 120, y, interestColor);
        canvas.drawText("Interest", MARGIN + 136, y + 5, label);

        String generatedOn = new SimpleDateFormat("dd MMM yyyy, hh:mm a", Locale.getDefault()).format(new Date());
        canvas.drawText("Generated on " + generatedOn, MARGIN, PAGE_HEIGHT - MARGIN, small);
        document.finishPage(page1);

        // ---- Following pages: amortization schedule ----
        drawSchedulePages(document, result.getSchedule(), sectionHeader, label, value, small);

        File dir = new File(context.getCacheDir(), "pdf_reports");
        if (!dir.exists() && !dir.mkdirs()) {
            throw new IOException("Unable to create report directory");
        }
        String fileName = "EMI_Report_" + System.currentTimeMillis() + ".pdf";
        File outFile = new File(dir, fileName);
        try (FileOutputStream out = new FileOutputStream(outFile)) {
            document.writeTo(out);
        } finally {
            document.close();
        }
        return outFile;
    }

    private static void drawSchedulePages(PdfDocument document, List<ScheduleEntry> schedule,
                                           Paint sectionHeader, Paint label, Paint value, Paint small) {
        Paint rowText = textPaint(9, false, Color.DKGRAY);
        Paint headerText = textPaint(9, true, Color.BLACK);

        int rowHeight = 18;
        int usableHeight = PAGE_HEIGHT - MARGIN * 2 - 60;
        int rowsPerPage = usableHeight / rowHeight;

        int pageNumber = 2;
        int index = 0;
        int total = schedule.size();

        while (index < total) {
            PdfDocument.Page page = document.startPage(new PdfDocument.PageInfo.Builder(PAGE_WIDTH, PAGE_HEIGHT, pageNumber).create());
            Canvas canvas = page.getCanvas();
            int y = MARGIN;

            canvas.drawText("Amortization Schedule", MARGIN, y, sectionHeader);
            y += 20;

            float[] columnX = {MARGIN, MARGIN + 60, MARGIN + 190, MARGIN + 320, MARGIN + 440};
            canvas.drawText("Month", columnX[0], y, headerText);
            canvas.drawText("EMI", columnX[1], y, headerText);
            canvas.drawText("Principal", columnX[2], y, headerText);
            canvas.drawText("Interest", columnX[3], y, headerText);
            canvas.drawText("Balance", columnX[4], y, headerText);
            y += 8;
            canvas.drawLine(MARGIN, y, PAGE_WIDTH - MARGIN, y, linePaint());
            y += 14;

            int rowsOnThisPage = Math.min(rowsPerPage, total - index);
            for (int i = 0; i < rowsOnThisPage; i++) {
                ScheduleEntry entry = schedule.get(index + i);
                canvas.drawText(String.valueOf(entry.getMonth()), columnX[0], y, rowText);
                canvas.drawText(CurrencyUtils.formatPlainNumber(entry.getEmi()), columnX[1], y, rowText);
                canvas.drawText(CurrencyUtils.formatPlainNumber(entry.getPrincipal()), columnX[2], y, rowText);
                canvas.drawText(CurrencyUtils.formatPlainNumber(entry.getInterest()), columnX[3], y, rowText);
                canvas.drawText(CurrencyUtils.formatPlainNumber(entry.getBalance()), columnX[4], y, rowText);
                y += rowHeight;
            }

            canvas.drawText("Page " + (pageNumber - 1), PAGE_WIDTH - MARGIN - 40, PAGE_HEIGHT - MARGIN, small);
            document.finishPage(page);

            index += rowsOnThisPage;
            pageNumber++;
        }
    }

    private static void drawPieChart(Canvas canvas, float cx, float cy, float radius,
                                      double principal, double interest, int principalColor, int interestColor) {
        double total = principal + interest;
        float principalSweep = total == 0 ? 0 : (float) (principal / total * 360.0);

        RectF rect = new RectF(cx - radius, cy - radius, cx + radius, cy + radius);
        Paint arcPaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        arcPaint.setStyle(Paint.Style.FILL);

        arcPaint.setColor(principalColor);
        canvas.drawArc(rect, -90, principalSweep, true, arcPaint);

        arcPaint.setColor(interestColor);
        canvas.drawArc(rect, -90 + principalSweep, 360 - principalSweep, true, arcPaint);

        Paint holePaint = new Paint(Paint.ANTI_ALIAS_FLAG);
        holePaint.setColor(Color.WHITE);
        canvas.drawCircle(cx, cy, radius * 0.55f, holePaint);
    }

    private static void drawLegendDot(Canvas canvas, float x, float y, int color) {
        Paint dot = new Paint(Paint.ANTI_ALIAS_FLAG);
        dot.setColor(color);
        canvas.drawCircle(x + 4, y - 3, 5, dot);
    }

    private static int drawRow(Canvas canvas, String labelText, String valueText, int y, Paint label, Paint value) {
        canvas.drawText(labelText, MARGIN, y, label);
        canvas.drawText(valueText, PAGE_WIDTH - MARGIN - value.measureText(valueText), y, value);
        return y + 22;
    }

    private static String formatTenure(int months) {
        int years = months / 12;
        int remMonths = months % 12;
        if (years > 0 && remMonths > 0) {
            return years + " yr " + remMonths + " mo";
        } else if (years > 0) {
            return years + " yr";
        }
        return remMonths + " mo";
    }

    private static Paint textPaint(float size, boolean bold, int color) {
        Paint paint = new Paint(Paint.ANTI_ALIAS_FLAG);
        paint.setTextSize(size);
        paint.setColor(color);
        paint.setFakeBoldText(bold);
        return paint;
    }

    private static Paint linePaint() {
        Paint paint = new Paint();
        paint.setColor(Color.LTGRAY);
        paint.setStrokeWidth(1);
        return paint;
    }
}
