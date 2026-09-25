package in.naukripatra.app.util;

import android.content.ActivityNotFoundException;
import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.widget.Toast;

import androidx.browser.customtabs.CustomTabColorSchemeParams;
import androidx.browser.customtabs.CustomTabsIntent;
import androidx.core.content.ContextCompat;

import in.naukripatra.app.R;

public final class Links {

    public static final String PLAY_URL = "https://play.google.com/store/apps/details?id=in.naukripatra.in";

    private Links() {
    }

    /** Opens a web page in an in-app browser tab, falling back to any browser. */
    public static void open(Context context, String url) {
        if (Text.isEmpty(url)) {
            Toast.makeText(context, R.string.link_unavailable, Toast.LENGTH_SHORT).show();
            return;
        }
        Uri uri = Uri.parse(url);
        try {
            CustomTabColorSchemeParams colors = new CustomTabColorSchemeParams.Builder()
                    .setToolbarColor(ContextCompat.getColor(context, R.color.brand_900))
                    .build();
            new CustomTabsIntent.Builder()
                    .setDefaultColorSchemeParams(colors)
                    .setShowTitle(true)
                    .build()
                    .launchUrl(context, uri);
        } catch (ActivityNotFoundException e) {
            try {
                context.startActivity(new Intent(Intent.ACTION_VIEW, uri));
            } catch (ActivityNotFoundException e2) {
                Toast.makeText(context, R.string.no_browser, Toast.LENGTH_SHORT).show();
            }
        }
    }

    public static void share(Context context, String title, String url) {
        String text = title + "\n\n" + url + "\n\nFind your dream job on NaukriPatra: " + PLAY_URL;
        Intent send = new Intent(Intent.ACTION_SEND)
                .setType("text/plain")
                .putExtra(Intent.EXTRA_SUBJECT, title)
                .putExtra(Intent.EXTRA_TEXT, text);
        context.startActivity(Intent.createChooser(send, context.getString(R.string.share_job)));
    }

    public static void shareApp(Context context) {
        Intent send = new Intent(Intent.ACTION_SEND)
                .setType("text/plain")
                .putExtra(Intent.EXTRA_TEXT, "Find your dream job with NaukriPatra - latest job updates every day.\n" + PLAY_URL);
        context.startActivity(Intent.createChooser(send, context.getString(R.string.share_app)));
    }

    public static void rateApp(Context context) {
        try {
            context.startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse("market://details?id=in.naukripatra.in")));
        } catch (ActivityNotFoundException e) {
            open(context, PLAY_URL);
        }
    }

    public static void email(Context context, String address) {
        Intent intent = new Intent(Intent.ACTION_SENDTO, Uri.parse("mailto:" + address));
        try {
            context.startActivity(intent);
        } catch (ActivityNotFoundException e) {
            Toast.makeText(context, address, Toast.LENGTH_LONG).show();
        }
    }
}
