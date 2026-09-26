package in.naukripatra.app.ui.detail;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;

import androidx.activity.EdgeToEdge;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;

import com.google.android.gms.ads.AdView;

import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.Api;
import in.naukripatra.app.data.JobDetail;
import in.naukripatra.app.ui.common.JobViews;
import in.naukripatra.app.ui.common.StateView;
import in.naukripatra.app.util.Deadline;
import in.naukripatra.app.util.Links;
import in.naukripatra.app.util.Ui;
import okhttp3.Call;

/**
 * The complete post from naukripatra.in, presented as a styled page: gradient
 * hero, banner, key facts, numbered colourful sections, readable tables and the
 * official links. Follows light and dark mode.
 */
public class ArticleActivity extends AppCompatActivity {

    private static final String EXTRA_ID = "post_id";
    private static final String EXTRA_TITLE = "title";

    private WebView web;
    private AdView banner;
    private Call call;
    private StateView stateView;
    private int postId;
    private JobDetail detail;

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
        findViewById(R.id.btnBack).setOnClickListener(v -> getOnBackPressedDispatcher().onBackPressed());
        findViewById(R.id.btnShare).setOnClickListener(v -> {
            if (detail != null) {
                Links.share(this, detail.title, detail.permalink.isEmpty() ? Api.SITE : detail.permalink);
            }
        });
        stateView = new StateView(findViewById(R.id.state));

        web = findViewById(R.id.web);
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(false);
        s.setAllowFileAccess(false);
        s.setAllowContentAccess(false);
        s.setLoadsImagesAutomatically(true);
        s.setTextZoom(100);
        web.setBackgroundColor(getColor(R.color.bg));
        web.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                // Links open in the browser tab, never inside this page.
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
                detail = d;
                findViewById(R.id.progress).setVisibility(View.GONE);
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

    // ============ Page ============

    private String hex(int colorRes) {
        return String.format("#%06X", 0xFFFFFF & getColor(colorRes));
    }

    private static String esc(String s) {
        return TextUtils.htmlEncode(s == null ? "" : s);
    }

    private String page(JobDetail d) {
        boolean night = Ui.isNight(this);
        String bg = hex(R.color.bg);
        String surface = hex(R.color.surface);
        String surfaceAlt = hex(R.color.surface_alt);
        String text = hex(R.color.text);
        String text2 = hex(R.color.text_2);
        String line = hex(R.color.line);
        String link = hex(R.color.link);

        // Section colours cycle through the app palette: {tint, strong}.
        String[][] sections = {
                {hex(R.color.brand_100), hex(R.color.brand_700)},
                {hex(R.color.green_100), hex(R.color.green)},
                {hex(R.color.purple_100), hex(R.color.purple)},
                {hex(R.color.accent_100), hex(R.color.accent)},
                {hex(R.color.pink_100), hex(R.color.pink)},
                {hex(R.color.teal_100), hex(R.color.teal)},
        };

        StringBuilder css = new StringBuilder()
                .append("*{box-sizing:border-box}")
                .append("html{-webkit-text-size-adjust:100%}")
                .append("body{margin:0;background:").append(bg).append(";color:").append(text)
                .append(";font-family:sans-serif;font-size:16px;line-height:1.72;word-wrap:break-word;overflow-wrap:break-word}")
                // Hero continues the toolbar gradient.
                .append(".hero{background:linear-gradient(135deg,").append(hex(R.color.header_start)).append(" 0%,")
                .append(hex(R.color.header_mid)).append(" 60%,").append(hex(R.color.header_end)).append(" 100%);")
                .append("color:#fff;padding:6px 18px 64px;border-radius:0 0 28px 28px}")
                .append(".chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}")
                .append(".chip{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.22);border-radius:99px;")
                .append("padding:4px 11px;font-size:12px;font-weight:700}")
                .append(".chip.live{background:#059669;border-color:#059669}")
                .append(".hero h1{font-size:22px;line-height:1.32;margin:0 0 10px;font-weight:800}")
                .append(".meta{display:flex;flex-wrap:wrap;gap:14px;font-size:13px;opacity:.92}")
                .append(".meta span{display:inline-flex;align-items:center;gap:5px}")
                .append(".wrap{padding:0 16px 28px;margin-top:-46px}")
                .append(".banner{display:block;width:100%;border-radius:18px;box-shadow:0 10px 28px rgba(8,20,60,.28);margin-bottom:14px;background:").append(surfaceAlt).append("}")
                // Key facts
                .append(".facts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:0 0 14px}")
                .append(".fact{border-radius:16px;padding:12px;min-height:92px}")
                .append(".fact .i{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:8px}")
                .append(".fact .l{font-size:11.5px;font-weight:700;color:").append(text2).append(";text-transform:uppercase;letter-spacing:.4px}")
                .append(".fact .v{font-size:14.5px;font-weight:800;line-height:1.3;margin-top:2px}")
                .append(".days{display:inline-block;margin-top:5px;font-size:11.5px;font-weight:800;padding:2px 9px;border-radius:99px;color:#fff}")
                // Content card
                .append(".card{background:").append(surface).append(";border:1px solid ").append(line)
                .append(";border-radius:20px;padding:18px 16px;box-shadow:0 2px 10px rgba(16,30,80,.05)}")
                .append(".content{counter-reset:sec}")
                .append(".content>*:first-child{margin-top:0}")
                .append("p{margin:0 0 14px}")
                .append("strong,b{font-weight:800}")
                .append("a{color:").append(link).append(";font-weight:700;text-decoration:none;border-bottom:1.5px solid ").append(link).append("}")
                .append("img{max-width:100%;height:auto;border-radius:12px;margin:10px 0;display:block}")
                // Numbered colourful section headings
                .append("h2{counter-increment:sec;display:flex;align-items:center;gap:10px;font-size:17.5px;line-height:1.35;")
                .append("margin:26px -4px 12px;padding:11px 12px;border-radius:14px;font-weight:800}")
                .append("h2::before{content:counter(sec,decimal-leading-zero);flex:none;width:30px;height:30px;border-radius:9px;")
                .append("display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff}")
                .append("h3{font-size:16px;margin:20px 0 8px;font-weight:800;display:flex;align-items:center;gap:8px}")
                .append("h3::before{content:'';width:8px;height:8px;border-radius:50%;background:").append(hex(R.color.accent)).append(";flex:none}")
                .append("h4,h5,h6{font-size:15px;margin:16px 0 8px}")
                // Lists
                .append("ul,ol{list-style:none;padding:0;margin:8px 0 16px}")
                .append("li{position:relative;padding:8px 10px 8px 36px;margin-bottom:7px;border-radius:12px;background:").append(surfaceAlt).append("}")
                .append("ul>li::before{content:'';position:absolute;left:12px;top:15px;width:12px;height:12px;border-radius:50%;")
                .append("background:").append(hex(R.color.green)).append(";box-shadow:0 0 0 4px ").append(hex(R.color.green_100)).append("}")
                .append("ol{counter-reset:li}")
                .append("ol>li{counter-increment:li}")
                .append("ol>li::before{content:counter(li);position:absolute;left:8px;top:8px;width:22px;height:22px;border-radius:7px;")
                .append("background:").append(hex(R.color.brand_700)).append(";color:#fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center}")
                // Tables: rounded, brand header row, zebra rows, bold tinted first column
                .append(".np-table-wrap,.table-wrap{overflow-x:auto;margin:10px 0 16px;padding:1px;border-radius:15px}")
                .append("table{width:100%;min-width:0!important;border-collapse:separate!important;border-spacing:0;border:1px solid ").append(line)
                .append("!important;border-radius:14px;overflow:hidden;margin:10px 0 16px;font-size:14.5px;line-height:1.5}")
                .append(".np-table-wrap table{margin:0}")
                .append("th,td{border:none!important;border-bottom:1px solid ").append(line).append("!important;padding:10px 12px!important;text-align:left;vertical-align:top}")
                .append("tr:last-child td{border-bottom:none!important}")
                .append("th{background:").append(hex(R.color.brand_700)).append(";color:#fff;font-weight:800;font-size:13px}")
                .append("tr:nth-child(even) td{background:").append(surfaceAlt).append("}")
                .append("td:first-child{font-weight:700;color:").append(night ? hex(R.color.brand) : hex(R.color.brand_700)).append("}")
                .append("td:first-child strong{color:inherit}")
                // Highlight boxes written inline in posts become soft callouts
                .append("div[style*='border']{border:none!important;border-left:5px solid ").append(hex(R.color.accent))
                .append("!important;background:").append(hex(R.color.accent_100)).append("!important;border-radius:14px!important;")
                .append("padding:14px 14px!important;margin:12px 0 16px!important;color:").append(text).append("}")
                .append("blockquote{margin:14px 0;padding:12px 14px;border-left:5px solid ").append(hex(R.color.purple))
                .append(";background:").append(hex(R.color.purple_100)).append(";border-radius:14px}")
                .append("pre,code{white-space:pre-wrap;word-break:break-word}")
                .append("svg{flex:none}")
                .append("iframe,script,style,form{display:none!important}")
                // Official links + disclaimer
                .append(".btns{display:flex;flex-direction:column;gap:10px;margin:16px 0}")
                .append(".btn{display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:16px;color:#fff!important;")
                .append("border:none!important;font-weight:800;font-size:15px;box-shadow:0 6px 16px rgba(16,30,80,.18)}")
                .append(".btn .sub{display:block;font-weight:600;font-size:12px;opacity:.9}")
                .append(".btn.web{background:linear-gradient(90deg,#0891B2,#1D4ED8)}")
                .append(".btn.pdf{background:linear-gradient(90deg,#F97316,#FB923C)}")
                .append(".disc{display:flex;gap:10px;padding:13px 14px;border-radius:16px;font-size:13px;line-height:1.55;")
                .append("background:").append(hex(R.color.disclaimer_bg)).append(";border:1px solid ").append(hex(R.color.disclaimer_line))
                .append(";color:").append(hex(R.color.disclaimer_text)).append("}")
                .append(".src{font-size:13px;color:").append(text2).append(";margin:0 0 14px}");
        for (int i = 0; i < sections.length; i++) {
            css.append(".content h2:nth-of-type(").append(sections.length).append("n+").append(i + 1).append("){background:")
                    .append(sections[i][0]).append(";color:").append(night ? text : sections[i][1]).append("}")
                    .append(".content h2:nth-of-type(").append(sections.length).append("n+").append(i + 1).append(")::before{background:")
                    .append(sections[i][1]).append("}");
        }

        StringBuilder html = new StringBuilder();
        html.append("<!DOCTYPE html><html><head><meta charset='UTF-8'>")
                .append("<meta name='viewport' content='width=device-width, initial-scale=1.0'>")
                .append("<style>").append(css).append("</style></head><body>");

        // Hero
        html.append("<div class='hero'><div class='chips'>");
        int chips = 0;
        for (String cat : d.categories) {
            if (!cat.equalsIgnoreCase("Latest Jobs") && chips < 2) {
                html.append("<span class='chip'>").append(esc(cat)).append("</span>");
                chips++;
            }
        }
        int days = Deadline.daysLeft(d.lastDate);
        if (days != Deadline.UNKNOWN && days >= 0) html.append("<span class='chip live'>&#9679; Apply Open</span>");
        html.append("</div><h1>").append(esc(d.title)).append("</h1><div class='meta'>");
        html.append("<span>").append(ArticleIcons.svg(ArticleIcons.DATE, "#fff", 16)).append(esc(d.date)).append("</span>");
        if (!d.readingTime.isEmpty()) {
            html.append("<span>").append(ArticleIcons.svg(ArticleIcons.CLOCK, "#fff", 16)).append(esc(d.readingTime)).append("</span>");
        }
        if (!d.organization.isEmpty()) {
            html.append("<span>").append(ArticleIcons.svg(ArticleIcons.ORG, "#fff", 16)).append(esc(d.organization)).append("</span>");
        }
        html.append("</div></div><div class='wrap'>");

        // Banner
        if (!d.image.isEmpty()) {
            html.append("<img class='banner' src='").append(esc(d.image)).append("' alt=''>");
        } else {
            html.append("<div style='height:50px'></div>");
        }

        // Key facts
        StringBuilder facts = new StringBuilder();
        fact(facts, "Total Posts", JobViews.postsLabel(d.vacancy), ArticleIcons.POSTS, sections[0]);
        fact(facts, "Salary", d.salary, ArticleIcons.SALARY, sections[1]);
        String dateValue = d.lastDate.isEmpty() ? "" : esc(Deadline.pretty(d.lastDate)) + daysBadge(days);
        factRaw(facts, "Last Date", dateValue, ArticleIcons.DATE, sections[4]);
        fact(facts, "Location", d.location, ArticleIcons.LOCATION, sections[3]);
        fact(facts, "Qualification", d.qualification, ArticleIcons.QUAL, sections[2]);
        fact(facts, "Age Limit", d.ageLimit, ArticleIcons.CHECK, sections[5]);
        if (facts.length() > 0) html.append("<div class='facts'>").append(facts).append("</div>");

        // Article
        html.append("<div class='card content'>").append(d.html).append("</div>");

        // Official links
        boolean hasWeb = !d.officialWebsite.isEmpty();
        boolean hasPdf = !d.notificationLink.isEmpty();
        if (hasWeb || hasPdf) {
            html.append("<div class='btns'>");
            if (hasWeb) {
                html.append("<a class='btn web' href='").append(esc(d.officialWebsite)).append("'>")
                        .append(ArticleIcons.svg(ArticleIcons.WEB, "#fff", 26))
                        .append("<span>").append(esc(getString(R.string.official_website)))
                        .append("<span class='sub'>").append(esc(d.sourceHost())).append("</span></span></a>");
            }
            if (hasPdf) {
                html.append("<a class='btn pdf' href='").append(esc(d.notificationLink)).append("'>")
                        .append(ArticleIcons.svg(ArticleIcons.PDF, "#fff", 26))
                        .append("<span>").append(esc(getString(R.string.notification_pdf)))
                        .append("<span class='sub'>Official notification</span></span></a>");
            }
            html.append("</div>");
        }

        // Disclaimer
        html.append("<div class='disc'>").append(ArticleIcons.svg(ArticleIcons.INFO, hex(R.color.amber), 22))
                .append("<div><b>").append(esc(getString(R.string.disclaimer_title))).append("</b> ")
                .append(esc(getString(R.string.disclaimer_short))).append("</div></div>");

        html.append("</div></body></html>");
        return html.toString();
    }

    private String daysBadge(int days) {
        if (days == Deadline.UNKNOWN) return "";
        String color;
        String label;
        if (days < 0) {
            color = "#64748B";
            label = "Closed";
        } else if (days <= 3) {
            color = "#E5383B";
            label = days == 0 ? "Last day" : days == 1 ? "1 day left" : days + " days left";
        } else if (days <= 7) {
            color = "#EA580C";
            label = days + " days left";
        } else {
            color = "#059669";
            label = days + " days left";
        }
        return "<br><span class='days' style='background:" + color + "'>" + label + "</span>";
    }

    private void fact(StringBuilder out, String label, String value, String icon, String[] colors) {
        if (value == null || value.trim().isEmpty()) return;
        factRaw(out, label, esc(value), icon, colors);
    }

    private void factRaw(StringBuilder out, String label, String valueHtml, String icon, String[] colors) {
        if (valueHtml == null || valueHtml.isEmpty()) return;
        out.append("<div class='fact' style='background:").append(colors[0]).append("'>")
                .append("<div class='i' style='background:").append(colors[1]).append("'>")
                .append(ArticleIcons.svg(icon, "#fff", 18)).append("</div>")
                .append("<div class='l'>").append(esc(label)).append("</div>")
                .append("<div class='v'>").append(valueHtml).append("</div></div>");
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
