package in.naukripatra.app.ui.detail;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;
import android.widget.TextView;

import androidx.activity.EdgeToEdge;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;

import com.google.android.gms.ads.AdView;

import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.Api;
import in.naukripatra.app.data.JobDetail;
import in.naukripatra.app.ui.common.StateView;
import in.naukripatra.app.util.Links;
import in.naukripatra.app.util.Text;
import in.naukripatra.app.util.Ui;
import okhttp3.Call;

/** The complete post from naukripatra.in, styled to match the app (light and dark). */
public class ArticleActivity extends AppCompatActivity {

    private static final String EXTRA_ID = "post_id";
    private static final String EXTRA_TITLE = "title";

    private WebView web;
    private AdView banner;
    private Call call;
    private StateView stateView;
    private int postId;

    public static Intent intent(Context c, int id, String title) {
        return new Intent(c, ArticleActivity.class).putExtra(EXTRA_ID, id).putExtra(EXTRA_TITLE, title);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        EdgeToEdge.enable(this);
        super.onCreate(savedInstanceState);
        WindowCompat.getInsetsController(getWindow(), getWindow().getDecorView())
                .setAppearanceLightStatusBars(false);
        setContentView(R.layout.activity_article);
        Ui.padForInsets(findViewById(R.id.toolbar), Ui.TOP | Ui.START | Ui.END);
        Ui.padForInsets(findViewById(R.id.bannerAd), Ui.BOTTOM);

        postId = getIntent().getIntExtra(EXTRA_ID, 0);
        String title = getIntent().getStringExtra(EXTRA_TITLE);
        if (!Text.isEmpty(title)) ((TextView) findViewById(R.id.title)).setText(title);
        findViewById(R.id.btnBack).setOnClickListener(v -> getOnBackPressedDispatcher().onBackPressed());
        stateView = new StateView(findViewById(R.id.state));

        web = findViewById(R.id.web);
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(false);
        s.setAllowFileAccess(false);
        s.setAllowContentAccess(false);
        s.setLoadsImagesAutomatically(true);
        s.setTextZoom(100);
        web.setBackgroundColor(getColor(R.color.surface));
        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                // Links in the article open in the browser tab, never inside this page.
                Links.open(ArticleActivity.this, request.getUrl().toString());
                return true;
            }
        });

        banner = Ads.banner(this, (FrameLayout) findViewById(R.id.bannerAd));
        load();
    }

    private void load() {
        findViewById(R.id.progress).setVisibility(View.VISIBLE);
        stateView.hide();
        call = Api.get(Api.post(postId), JobDetail::fromJson, new Api.Result<JobDetail>() {
            @Override
            public void onSuccess(JobDetail d) {
                if (isFinishing()) return;
                findViewById(R.id.progress).setVisibility(View.GONE);
                ((TextView) findViewById(R.id.title)).setText(d.title);
                web.loadDataWithBaseURL(Api.SITE, page(d), "text/html", "UTF-8", null);
            }

            @Override
            public void onError(@NonNull String message) {
                if (isFinishing()) return;
                findViewById(R.id.progress).setVisibility(View.GONE);
                stateView.showError(message, () -> load());
            }
        });
    }

    private String hex(int colorRes) {
        return String.format("#%06X", 0xFFFFFF & getColor(colorRes));
    }

    private String page(JobDetail d) {
        String surface = hex(R.color.surface);
        String text = hex(R.color.text);
        String muted = hex(R.color.text_2);
        String accent = hex(R.color.link);
        String line = hex(R.color.line);
        String soft = hex(R.color.brand_50);
        String row = hex(R.color.surface_alt);
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'>"
                + "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
                + "<style>"
                + "html{-webkit-text-size-adjust:100%}"
                + "body{font-family:sans-serif;background:" + surface + ";color:" + text + ";margin:0;padding:18px 18px 28px;"
                + "font-size:16px;line-height:1.72;word-wrap:break-word;overflow-wrap:break-word}"
                + "h1.t{font-size:21px;line-height:1.35;margin:0 0 6px}"
                + ".m{color:" + muted + ";font-size:13px;margin:0 0 18px}"
                + "p{margin:0 0 14px}"
                + "h1,h2{font-size:18.5px;line-height:1.4;background:" + soft + ";border-left:4px solid " + accent + ";"
                + "border-radius:8px;padding:10px 12px;margin:24px 0 12px}"
                + "h3{font-size:16.5px;margin:20px 0 8px;padding-bottom:6px;border-bottom:2px solid " + line + "}"
                + "h4,h5,h6{font-size:15.5px;margin:16px 0 8px}"
                + "a{color:" + accent + ";font-weight:600}"
                + "img{max-width:100%;height:auto;border-radius:10px;margin:12px 0;display:block}"
                + "ul,ol{padding-left:22px;margin:10px 0 14px}li{margin-bottom:8px}"
                + "table{width:100%;border-collapse:collapse;margin:14px 0}"
                + "table tr{display:block;background:" + row + ";margin:0 0 8px;padding:10px 12px;border:1px solid " + line + ";border-radius:10px}"
                + "table th{display:none}"
                + "table td{display:block;padding:2px 0;border:none;font-size:15px}"
                + "table td:first-child{font-weight:700;color:" + accent + ";font-size:12.5px;text-transform:uppercase;letter-spacing:.4px}"
                + "blockquote{margin:14px 0;padding:10px 14px;border-left:4px solid " + accent + ";background:" + row + "}"
                + "pre,code{white-space:pre-wrap;word-break:break-word}"
                + "iframe,script{display:none}"
                + "</style></head><body>"
                + "<h1 class='t'>" + android.text.TextUtils.htmlEncode(d.title) + "</h1>"
                + "<p class='m'>" + android.text.TextUtils.htmlEncode(d.date + (d.readingTime.isEmpty() ? "" : " · " + d.readingTime)) + "</p>"
                + d.html
                + "</body></html>";
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (banner != null) banner.resume();
    }

    @Override
    protected void onPause() {
        if (banner != null) banner.pause();
        super.onPause();
    }

    @Override
    protected void onDestroy() {
        if (call != null) call.cancel();
        if (banner != null) banner.destroy();
        if (web != null) web.destroy();
        super.onDestroy();
    }
}
