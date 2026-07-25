package com.naukripatra.emicalculator.ui.schedule;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.FileProvider;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.recyclerview.widget.LinearLayoutManager;

import com.google.android.material.snackbar.Snackbar;
import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivityScheduleBinding;
import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.model.LoanType;
import com.naukripatra.emicalculator.ui.nav.BottomNavHelper;
import com.naukripatra.emicalculator.util.AdConfig;
import com.naukripatra.emicalculator.util.CurrencyUtils;
import com.naukripatra.emicalculator.util.EmiCalculatorUtil;
import com.naukripatra.emicalculator.util.PdfReportGenerator;

import java.io.File;
import java.io.IOException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class ScheduleActivity extends AppCompatActivity {

    public static final String EXTRA_LOAN_TYPE = "extra_loan_type";
    public static final String EXTRA_LOAN_AMOUNT = "extra_loan_amount";
    public static final String EXTRA_INTEREST_RATE = "extra_interest_rate";
    public static final String EXTRA_TENURE_MONTHS = "extra_tenure_months";

    private ActivityScheduleBinding binding;
    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private LoanType loanType;
    private EmiResult result;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityScheduleBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        if (!loadFromIntent()) {
            finish();
            return;
        }

        applyEdgeToEdgeInsets();
        binding.btnBack.setOnClickListener(v -> {
            getOnBackPressedDispatcher().onBackPressed();
            overridePendingTransition(R.anim.slide_in_left, R.anim.slide_out_right);
        });

        populateRecap();
        binding.recyclerSchedule.setLayoutManager(new LinearLayoutManager(this));
        binding.recyclerSchedule.setAdapter(new ScheduleAdapter(result.getSchedule()));

        binding.btnSavePdf.setOnClickListener(v -> generateAndSharePdf());

        BottomNavHelper.setup(this, binding.bottomNav, BottomNavHelper.Destination.HISTORY);
        AdConfig.loadBanner(binding.adBannerContainer.adBanner);
    }

    private boolean loadFromIntent() {
        String typeName = getIntent().getStringExtra(EXTRA_LOAN_TYPE);
        if (typeName == null) {
            return false;
        }
        try {
            loanType = LoanType.valueOf(typeName);
        } catch (IllegalArgumentException e) {
            return false;
        }
        double loanAmount = getIntent().getDoubleExtra(EXTRA_LOAN_AMOUNT, 0);
        double rate = getIntent().getDoubleExtra(EXTRA_INTEREST_RATE, 0);
        int tenureMonths = getIntent().getIntExtra(EXTRA_TENURE_MONTHS, 0);
        if (loanAmount <= 0 || tenureMonths <= 0) {
            return false;
        }
        result = EmiCalculatorUtil.calculate(loanAmount, rate, tenureMonths);
        return true;
    }

    private void applyEdgeToEdgeInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.header, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), bars.top, view.getPaddingRight(), view.getPaddingBottom());
            return insets;
        });
        ViewCompat.setOnApplyWindowInsetsListener(binding.bottomNav.getRoot(), (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), view.getPaddingTop(), view.getPaddingRight(), bars.bottom);
            return insets;
        });
    }

    private void populateRecap() {
        binding.txtRecapAmount.setText(CurrencyUtils.formatWholeRupees(result.getLoanAmount()));
        binding.txtRecapMeta.setText(getString(R.string.schedule_recap_meta,
                getString(loanType.getTitleRes()),
                formatTenure(result.getTenureMonths()),
                CurrencyUtils.formatPercent(result.getInterestRate())));
        binding.txtRecapEmi.setText(CurrencyUtils.formatWholeRupees(result.getMonthlyEmi()));
        binding.txtRecapInterest.setText(CurrencyUtils.formatWholeRupees(result.getTotalInterest()));
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

    private void generateAndSharePdf() {
        Snackbar.make(binding.getRoot(), R.string.pdf_generating, Snackbar.LENGTH_SHORT).show();
        executor.execute(() -> {
            try {
                File file = PdfReportGenerator.generate(this, loanType, result);
                runOnUiThread(() -> shareFile(file));
            } catch (IOException e) {
                runOnUiThread(() -> Snackbar.make(binding.getRoot(), R.string.pdf_error, Snackbar.LENGTH_SHORT).show());
            }
        });
    }

    private void shareFile(File pdfFile) {
        Uri uri = FileProvider.getUriForFile(this, getPackageName() + ".fileprovider", pdfFile);
        Intent intent = new Intent(Intent.ACTION_SEND);
        intent.setType("application/pdf");
        intent.putExtra(Intent.EXTRA_STREAM, uri);
        intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
        startActivity(Intent.createChooser(intent, getString(R.string.pdf_chooser_title)));
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
