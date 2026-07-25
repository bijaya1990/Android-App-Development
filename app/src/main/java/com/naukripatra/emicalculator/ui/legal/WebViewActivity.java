package com.naukripatra.emicalculator.ui.legal;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowCompat;
import androidx.core.view.WindowInsetsCompat;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.ActivityWebviewBinding;

/** Displays a bundled legal document (Privacy Policy / Terms of Use) from assets — fully offline. */
public class WebViewActivity extends AppCompatActivity {

    private static final String EXTRA_ASSET_FILE = "extra_asset_file";
    private static final String EXTRA_TITLE = "extra_title";

    private ActivityWebviewBinding binding;

    public static void start(Context context, String assetFileName, String title) {
        Intent intent = new Intent(context, WebViewActivity.class);
        intent.putExtra(EXTRA_ASSET_FILE, assetFileName);
        intent.putExtra(EXTRA_TITLE, title);
        context.startActivity(intent);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        binding = ActivityWebviewBinding.inflate(getLayoutInflater());
        setContentView(binding.getRoot());

        applyEdgeToEdgeInsets();

        String assetFile = getIntent().getStringExtra(EXTRA_ASSET_FILE);
        String title = getIntent().getStringExtra(EXTRA_TITLE);

        binding.toolbar.setTitle(title);
        binding.toolbar.setNavigationOnClickListener(v -> {
            getOnBackPressedDispatcher().onBackPressed();
            overridePendingTransition(R.anim.slide_in_left, R.anim.slide_out_right);
        });

        binding.webView.getSettings().setJavaScriptEnabled(false);
        binding.webView.loadUrl("file:///android_asset/" + assetFile);
    }

    private void applyEdgeToEdgeInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.toolbar, (view, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            view.setPadding(view.getPaddingLeft(), bars.top, view.getPaddingRight(), view.getPaddingBottom());
            return insets;
        });
    }
}
