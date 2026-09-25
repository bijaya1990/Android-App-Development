package in.naukripatra.app;

import android.app.Application;
import android.content.Intent;

import com.onesignal.OneSignal;
import com.onesignal.debug.LogLevel;

import in.naukripatra.app.data.Api;
import in.naukripatra.app.data.Prefs;
import in.naukripatra.app.ui.detail.JobDetailActivity;
import in.naukripatra.app.ui.main.MainActivity;

public class NaukriPatraApp extends Application {

    private static final String ONESIGNAL_APP_ID = "42dd3edc-c09d-43bb-9b55-ff01f30154f8";

    @Override
    public void onCreate() {
        super.onCreate();
        Prefs.applyTheme(this);
        Api.init(this);

        if (BuildConfig.DEBUG) {
            OneSignal.getDebug().setLogLevel(LogLevel.WARN);
        }
        OneSignal.initWithContext(this, ONESIGNAL_APP_ID);

        // Launch URLs are opened inside the app (see manifest: suppressLaunchURLs),
        // so a tapped job notification lands on that job's detail page.
        OneSignal.getNotifications().addClickListener(event -> {
            String url = event.getNotification().getLaunchURL();
            Intent intent;
            String slug = slugFrom(url);
            if (slug.isEmpty()) {
                intent = new Intent(this, MainActivity.class);
            } else {
                intent = JobDetailActivity.forSlug(this, slug);
            }
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            startActivity(intent);
        });
    }

    /** "https://naukripatra.in/ssc-cgl-2026/" -> "ssc-cgl-2026" (only for our own site). */
    static String slugFrom(String url) {
        if (url == null) return "";
        android.net.Uri uri = android.net.Uri.parse(url.trim());
        String host = uri.getHost();
        if (host == null || !host.endsWith("naukripatra.in")) return "";
        java.util.List<String> parts = uri.getPathSegments();
        if (parts.isEmpty()) return "";
        return parts.get(parts.size() - 1);
    }
}
