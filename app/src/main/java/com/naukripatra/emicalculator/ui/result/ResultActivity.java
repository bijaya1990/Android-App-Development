package com.naukripatra.emicalculator.ui.result;

import android.content.ClipData;
import android.content.ClipboardManager;
import android.content.Context;
import android.content.Intent;
import android.graphics.Color;
import android.os.Bundle;
import android.os.ParcelFileDescriptor;
import android.print.PageRange;
import android.print.PrintAttributes;
import android.print.PrintDocumentAdapter;
import android.print.PrintDocumentInfo;
import android.print.PrintManager;
import android.view.MenuItem;
import android.widget.PopupMenu;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.lifecycle.ViewModelProvider;

import com.github.mikephil.charting.animation.Easing;
import com.github.mikephil.charting.charts.PieChart;
import com.github.mikephil.charting.data.PieData;
import com.github.mikephil.charting.data.PieDataSet;
import com.github.mikephil.charting.data.PieEntry;
import com.google.android.material.snackbar.Snackbar;
import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivityResultBinding;
import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.ui.nav.BottomNavHelper;
import com.naukripatra.emicalculator.ui.schedule.ScheduleActivity;
import com.naukripatra.emicalculator.util.AdConfig;
import com.naukripatra.emicalculator.util.CurrencyUtils;
import com.naukripatra.emicalculator.util.LastCalculationStore;
import com.naukripatra.emicalculator.util.PdfReportGenerator;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.Locale;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ResultActivity extends AppCompatActivity {

    public static final String EXTRA_LOAN_TYPE = "extra_loan_type";
    public static final String EXTRA_LOAN_AMOUNT = "extra_loan_amount";
    public static final String EXTRA_INTEREST_RATE = "extra_interest_rate";
    public static final String EXTRA_TENURE_MONTHS = "extra_tenure_months";

    private ActivityResultBinding binding;
    private ResultViewModel viewModel;
    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private File lastGeneratedPdf;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityResultBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        viewModel = new ViewModelProvider(this).get(ResultViewModel.class);
        initViewModelFromIntent();
        applyEdgeToEdgeInsets();
        setupHeader();
        populateSummary();
        setupPieChart();
        setupActions();

        BottomNavHelper.setup(this, binding.bottomNav, BottomNavHelper.Destination.HOME);
        AdConfig.loadBanner(binding.adBannerContainer.adBanner);
    }

    private void initViewModelFromIntent() {
        String loanTypeName = getIntent().getStringExtra(EXTRA_LOAN_TYPE);
        LoanType loanType;
        try {
            loanType = LoanType.valueOf(loanTypeName);
        } catch (Exception e) {
            loanType = LoanType.CAR;
        }
        double loanAmount = getIntent().getDoubleExtra(EXTRA_LOAN_AMOUNT, 0);
        double rate = getIntent().getDoubleExtra(EXTRA_INTEREST_RATE, 0);
        int tenureMonths = getIntent().getIntExtra(EXTRA_TENURE_MONTHS, 0);
        viewModel.init(loanType, loanAmount, rate, tenureMonths);
        LastCalculationStore.save(this, loanType, loanAmount, rate, tenureMonths);
    }

    private void applyEdgeToEdgeInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.header, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), bars.top, view.getPaddingRight(), view.getPaddingBottom());
            return insets;
        });
    }

    private void setupHeader() {
        binding.btnBack.setOnClickListener(v -> {
            getOnBackPressedDispatcher().onBackPressed();
            overridePendingTransition(R.anim.slide_in_left, R.anim.slide_out_right);
        });
        binding.btnShare.setOnClickListener(v -> shareAsText());
        binding.btnMore.setOnClickListener(this::showMoreMenu);
    }

    private void showMoreMenu(android.view.View anchor) {
        PopupMenu popup = new PopupMenu(this, anchor);
        popup.getMenuInflater().inflate(R.menu.menu_result_more, popup.getMenu());
        popup.setOnMenuItemClickListener(this::onMoreMenuItemClick);
        popup.show();
    }

    private boolean onMoreMenuItemClick(MenuItem item) {
        int id = item.getItemId();
        if (id == R.id.action_print) {
            generatePdfThen(this::printPdf);
            return true;
        } else if (id == R.id.action_copy) {
            copyToClipboard();
            return true;
        }
        return false;
    }

    private void populateSummary() {
        EmiResult result = viewModel.getResult();
        if (result == null) {
            return;
        }

        binding.txtMonthlyEmi.setText(CurrencyUtils.formatWholeRupees(result.getMonthlyEmi()));
        binding.txtNextDue.setText(getString(R.string.result_next_due, formatNextDueDate()));
        binding.txtPrincipal.setText(CurrencyUtils.formatWholeRupees(result.getLoanAmount()));
        binding.txtInterest.setText(CurrencyUtils.formatWholeRupees(result.getTotalInterest()));
        binding.txtTotalPayment.setText(CurrencyUtils.formatWholeRupees(result.getTotalPayment()));

        binding.txtLegendPrincipal.setText(CurrencyUtils.formatWholeRupees(result.getLoanAmount()));
        binding.txtLegendInterest.setText(CurrencyUtils.formatWholeRupees(result.getTotalInterest()));

        int principalPercent = (int) Math.round(result.getLoanAmount() / result.getTotalPayment() * 100);
        binding.txtPrincipalPercent.setText(getString(R.string.repayment_split_principal_pct, principalPercent));

        double payoutRatio = result.getTotalPayment() / result.getLoanAmount();
        int interestSharePercent = 100 - principalPercent;
        binding.txtInsights.setText(getString(R.string.expert_insights_body,
                CurrencyUtils.formatPercent(result.getInterestRate()),
                String.format(Locale.getDefault(), "%.2f", payoutRatio),
                interestSharePercent + "%"));
    }

    private String formatNextDueDate() {
        Calendar calendar = Calendar.getInstance();
        calendar.add(Calendar.MONTH, 1);
        return new SimpleDateFormat("MMM dd, yyyy", Locale.getDefault()).format(calendar.getTime());
    }

    private void setupPieChart() {
        EmiResult result = viewModel.getResult();
        if (result == null) {
            return;
        }
        PieChart pieChart = binding.pieChart;

        List<PieEntry> entries = new ArrayList<>();
        entries.add(new PieEntry((float) result.getLoanAmount(), getString(R.string.chart_principal_label)));
        entries.add(new PieEntry((float) result.getTotalInterest(), getString(R.string.chart_interest_label)));

        PieDataSet dataSet = new PieDataSet(entries, "");
        int principalColor = ContextCompat.getColor(this, R.color.chart_principal);
        int interestColor = ContextCompat.getColor(this, R.color.chart_interest);
        dataSet.setColors(principalColor, interestColor);
        dataSet.setDrawValues(false);
        dataSet.setSliceSpace(2f);

        pieChart.setData(new PieData(dataSet));
        pieChart.setDrawEntryLabels(false);
        pieChart.getDescription().setEnabled(false);
        pieChart.getLegend().setEnabled(false);
        pieChart.setHoleRadius(72f);
        pieChart.setTransparentCircleRadius(72f);
        pieChart.setHoleColor(Color.TRANSPARENT);
        pieChart.setRotationEnabled(false);
        pieChart.setExtraOffsets(0f, 0f, 0f, 0f);
        pieChart.animateY(700, Easing.EaseInOutQuad);
        pieChart.invalidate();
    }

    private void setupActions() {
        binding.btnViewSchedule.setOnClickListener(v -> {
            Intent intent = new Intent(this, ScheduleActivity.class);
            intent.putExtra(ScheduleActivity.EXTRA_LOAN_TYPE, viewModel.getLoanType().name());
            intent.putExtra(ScheduleActivity.EXTRA_LOAN_AMOUNT, viewModel.getResult().getLoanAmount());
            intent.putExtra(ScheduleActivity.EXTRA_INTEREST_RATE, viewModel.getResult().getInterestRate());
            intent.putExtra(ScheduleActivity.EXTRA_TENURE_MONTHS, viewModel.getResult().getTenureMonths());
            startActivity(intent);
            overridePendingTransition(R.anim.slide_in_right, R.anim.fade_out);
        });
        binding.btnGeneratePdf.setOnClickListener(v -> generatePdfThen(this::sharePdf));
    }

    private String buildShareText() {
        EmiResult result = viewModel.getResult();
        LoanType loanType = viewModel.getLoanType();
        return getString(R.string.share_text_template,
                getString(loanType.getTitleRes()),
                CurrencyUtils.formatWholeRupees(result.getLoanAmount()),
                CurrencyUtils.formatPercent(result.getInterestRate()),
                formatTenure(result.getTenureMonths()),
                CurrencyUtils.formatWholeRupees(result.getMonthlyEmi()),
                CurrencyUtils.formatWholeRupees(result.getTotalInterest()),
                CurrencyUtils.formatWholeRupees(result.getTotalPayment()));
    }

    private String formatTenure(int months) {
        int years = months / 12;
        int rem = months % 12;
        if (years > 0 && rem > 0) {
            return years + " yr " + rem + " mo";
        } else if (years > 0) {
            return years + " yr";
        }
        return rem + " mo";
    }

    private void shareAsText() {
        Intent intent = new Intent(Intent.ACTION_SEND);
        intent.setType("text/plain");
        intent.putExtra(Intent.EXTRA_TEXT, buildShareText());
        startActivity(Intent.createChooser(intent, getString(R.string.share_chooser_title)));
    }

    private void copyToClipboard() {
        ClipboardManager clipboard = (ClipboardManager) getSystemService(Context.CLIPBOARD_SERVICE);
        clipboard.setPrimaryClip(ClipData.newPlainText("EMI Result", buildShareText()));
        Snackbar.make(binding.getRoot(), R.string.copied_to_clipboard, Snackbar.LENGTH_SHORT).show();
    }

    private interface PdfReadyCallback {
        void onReady(File pdfFile);
    }

    private void generatePdfThen(PdfReadyCallback callback) {
        if (lastGeneratedPdf != null && lastGeneratedPdf.exists()) {
            callback.onReady(lastGeneratedPdf);
            return;
        }
        Snackbar.make(binding.getRoot(), R.string.pdf_generating, Snackbar.LENGTH_SHORT).show();
        executor.execute(() -> {
            try {
                File file = PdfReportGenerator.generate(this, viewModel.getLoanType(), viewModel.getResult());
                lastGeneratedPdf = file;
                runOnUiThread(() -> callback.onReady(file));
            } catch (IOException e) {
                runOnUiThread(() -> Snackbar.make(binding.getRoot(), R.string.pdf_error, Snackbar.LENGTH_SHORT).show());
            }
        });
    }

    private void sharePdf(File pdfFile) {
        android.net.Uri uri = FileProvider.getUriForFile(this, getPackageName() + ".fileprovider", pdfFile);
        Intent intent = new Intent(Intent.ACTION_SEND);
        intent.setType("application/pdf");
        intent.putExtra(Intent.EXTRA_STREAM, uri);
        intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
        startActivity(Intent.createChooser(intent, getString(R.string.pdf_chooser_title)));
    }

    private void printPdf(File pdfFile) {
        PrintManager printManager = (PrintManager) getSystemService(Context.PRINT_SERVICE);
        if (printManager == null) {
            Toast.makeText(this, R.string.no_pdf_app_found, Toast.LENGTH_SHORT).show();
            return;
        }
        printManager.print(getString(R.string.app_name), new FilePrintDocumentAdapter(pdfFile), null);
    }

    /** Streams an already-rendered PDF file straight into the system print pipeline. */
    private static class FilePrintDocumentAdapter extends PrintDocumentAdapter {
        private final File file;

        FilePrintDocumentAdapter(File file) {
            this.file = file;
        }

        @Override
        public void onLayout(PrintAttributes oldAttributes, PrintAttributes newAttributes,
                              android.os.CancellationSignal cancellationSignal,
                              LayoutResultCallback callback, Bundle extras) {
            if (cancellationSignal.isCanceled()) {
                callback.onLayoutCancelled();
                return;
            }
            PrintDocumentInfo info = new PrintDocumentInfo.Builder(file.getName())
                    .setContentType(PrintDocumentInfo.CONTENT_TYPE_DOCUMENT)
                    .build();
            callback.onLayoutFinished(info, true);
        }

        @Override
        public void onWrite(PageRange[] pages, ParcelFileDescriptor destination,
                             android.os.CancellationSignal cancellationSignal, WriteResultCallback callback) {
            try (InputStream in = new FileInputStream(file);
                 FileOutputStream out = new FileOutputStream(destination.getFileDescriptor())) {
                byte[] buffer = new byte[8192];
                int bytesRead;
                while ((bytesRead = in.read(buffer)) >= 0) {
                    out.write(buffer, 0, bytesRead);
                }
                callback.onWriteFinished(new PageRange[]{PageRange.ALL_PAGES});
            } catch (IOException e) {
                callback.onWriteFailed(e.getMessage());
            }
        }
    }

    @Override
    protected void onResume() {
        super.onResume();
        binding.adBannerContainer.adBanner.resume();
    }

    @Override
    protected void onPause() {
        binding.adBannerContainer.adBanner.pause();
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        binding.adBannerContainer.adBanner.destroy();
        executor.shutdown();
    }
}
