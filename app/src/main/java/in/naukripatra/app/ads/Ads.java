package in.naukripatra.app.ads;

import android.app.Activity;
import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;

import com.google.android.gms.ads.AdListener;
import com.google.android.gms.ads.AdLoader;
import com.google.android.gms.ads.AdRequest;
import com.google.android.gms.ads.AdSize;
import com.google.android.gms.ads.AdView;
import com.google.android.gms.ads.LoadAdError;
import com.google.android.gms.ads.MobileAds;
import com.google.android.gms.ads.RequestConfiguration;
import com.google.android.gms.ads.interstitial.InterstitialAd;
import com.google.android.gms.ads.interstitial.InterstitialAdLoadCallback;
import com.google.android.gms.ads.nativead.MediaView;
import com.google.android.gms.ads.nativead.NativeAd;
import com.google.android.gms.ads.nativead.NativeAdOptions;
import com.google.android.gms.ads.nativead.NativeAdView;
import com.google.android.ump.ConsentInformation;
import com.google.android.ump.ConsentRequestParameters;
import com.google.android.ump.UserMessagingPlatform;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.atomic.AtomicBoolean;

import in.naukripatra.app.BuildConfig;
import in.naukripatra.app.R;

/**
 * AdMob, using the app's existing production ad units.
 *
 * Ads are only requested after the Google consent (UMP) check has finished, as
 * required by AdMob. Screens call {@link #whenReady} and their ads load as soon as
 * that is done. No ad is ever placed next to the Apply button.
 */
public final class Ads {

    private static final String BANNER_ID = "ca-app-pub-5327551236542725/7762619727";
    private static final String INTERSTITIAL_ID = "ca-app-pub-5327551236542725/8880260278";
    private static final String NATIVE_ID = "ca-app-pub-5327551236542725/1988437882";

    /** An interstitial is shown at most on every 3rd job opened, never back-to-back. */
    private static final int INTERSTITIAL_EVERY = 3;
    private static final long INTERSTITIAL_MIN_GAP_MS = 60_000;

    private static final AtomicBoolean initStarted = new AtomicBoolean(false);
    private static boolean ready;
    private static final List<Runnable> pending = new ArrayList<>();

    private static InterstitialAd interstitial;
    private static boolean interstitialLoading;
    private static int opens;
    private static long lastShown;

    private Ads() {
    }

    // ============ Consent + init ============

    /** Runs the consent flow once per launch, then starts the Mobile Ads SDK. */
    public static void gatherConsent(Activity activity) {
        ConsentInformation info = UserMessagingPlatform.getConsentInformation(activity);
        ConsentRequestParameters params = new ConsentRequestParameters.Builder().build();
        info.requestConsentInfoUpdate(activity, params,
                () -> UserMessagingPlatform.loadAndShowConsentFormIfRequired(activity, error -> {
                    if (info.canRequestAds()) start(activity);
                }),
                error -> {
                    if (info.canRequestAds()) start(activity);
                });
        // Consent from a previous session lets ads start straight away.
        if (info.canRequestAds()) start(activity);
    }

    public static boolean privacyOptionsRequired(Context context) {
        return UserMessagingPlatform.getConsentInformation(context).getPrivacyOptionsRequirementStatus()
                == ConsentInformation.PrivacyOptionsRequirementStatus.REQUIRED;
    }

    public static void showPrivacyOptions(Activity activity) {
        UserMessagingPlatform.showPrivacyOptionsForm(activity, error -> {
        });
    }

    private static void start(Activity activity) {
        if (!initStarted.compareAndSet(false, true)) return;
        if (BuildConfig.DEBUG) {
            MobileAds.setRequestConfiguration(new RequestConfiguration.Builder()
                    .setTestDeviceIds(Collections.singletonList("88B1C3B0D3CA2BA5945D9C86AC90689B"))
                    .build());
        }
        Context app = activity.getApplicationContext();
        new Thread(() -> MobileAds.initialize(app, status -> {
            List<Runnable> run;
            synchronized (pending) {
                ready = true;
                run = new ArrayList<>(pending);
                pending.clear();
            }
            android.os.Handler main = new android.os.Handler(android.os.Looper.getMainLooper());
            main.post(() -> {
                for (Runnable r : run) r.run();
                loadInterstitial(app);
            });
        })).start();
    }

    /** Runs on the main thread once ads may be requested. */
    public static void whenReady(Runnable r) {
        synchronized (pending) {
            if (!ready) {
                pending.add(r);
                return;
            }
        }
        r.run();
    }

    // ============ Banner ============

    /** Adaptive banner that fills the container width; the container stays hidden if no ad. */
    public static AdView banner(Activity activity, FrameLayout container) {
        AdView adView = new AdView(activity);
        adView.setAdUnitId(BANNER_ID);
        container.setVisibility(View.GONE);
        whenReady(() -> container.post(() -> {
            if (activity.isFinishing() || activity.isDestroyed()) return;
            int widthPx = container.getWidth();
            if (widthPx == 0) widthPx = activity.getResources().getDisplayMetrics().widthPixels;
            int widthDp = (int) (widthPx / activity.getResources().getDisplayMetrics().density);
            adView.setAdSize(AdSize.getCurrentOrientationAnchoredAdaptiveBannerAdSize(activity, widthDp));
            container.removeAllViews();
            container.addView(adView);
            adView.setAdListener(new AdListener() {
                @Override
                public void onAdLoaded() {
                    container.setVisibility(View.VISIBLE);
                }

                @Override
                public void onAdFailedToLoad(@NonNull LoadAdError error) {
                    container.setVisibility(View.GONE);
                }
            });
            adView.loadAd(new AdRequest.Builder().build());
        }));
        return adView;
    }

    // ============ Native ============

    public interface NativeCallback {
        void onLoaded(@Nullable NativeAd ad);
    }

    public static void loadNative(Context context, NativeCallback callback) {
        Context app = context.getApplicationContext();
        whenReady(() -> new AdLoader.Builder(app, NATIVE_ID)
                .forNativeAd(callback::onLoaded)
                .withAdListener(new AdListener() {
                    @Override
                    public void onAdFailedToLoad(@NonNull LoadAdError error) {
                        callback.onLoaded(null);
                    }
                })
                .withNativeAdOptions(new NativeAdOptions.Builder()
                        .setAdChoicesPlacement(NativeAdOptions.ADCHOICES_TOP_RIGHT)
                        .build())
                .build()
                .loadAd(new AdRequest.Builder().build()));
    }

    /** Fills a native ad card. Pass withMedia=false for the compact list style. */
    public static void bindNative(FrameLayout container, NativeAd ad, boolean withMedia) {
        LayoutInflater inflater = LayoutInflater.from(container.getContext());
        NativeAdView view = (NativeAdView) inflater.inflate(
                withMedia ? R.layout.ad_native_large : R.layout.ad_native_small, container, false);

        TextView headline = view.findViewById(R.id.adHeadline);
        TextView body = view.findViewById(R.id.adBody);
        TextView cta = view.findViewById(R.id.adCta);
        ImageView icon = view.findViewById(R.id.adIcon);
        MediaView media = view.findViewById(R.id.adMedia);

        headline.setText(ad.getHeadline());
        view.setHeadlineView(headline);

        if (ad.getBody() != null) {
            body.setText(ad.getBody());
            body.setVisibility(View.VISIBLE);
        } else {
            body.setVisibility(View.GONE);
        }
        view.setBodyView(body);

        if (ad.getCallToAction() != null) {
            cta.setText(ad.getCallToAction());
            cta.setVisibility(View.VISIBLE);
        } else {
            cta.setVisibility(View.GONE);
        }
        view.setCallToActionView(cta);

        if (ad.getIcon() != null && ad.getIcon().getDrawable() != null) {
            icon.setImageDrawable(ad.getIcon().getDrawable());
            icon.setVisibility(View.VISIBLE);
        } else {
            icon.setVisibility(View.GONE);
        }
        view.setIconView(icon);

        if (media != null) {
            if (ad.getMediaContent() != null) {
                media.setMediaContent(ad.getMediaContent());
            }
            view.setMediaView(media);
        }

        view.setNativeAd(ad);
        container.removeAllViews();
        container.addView(view, new ViewGroup.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT,
                ViewGroup.LayoutParams.WRAP_CONTENT));
        container.setVisibility(View.VISIBLE);
    }

    // ============ Interstitial ============

    private static void loadInterstitial(Context context) {
        if (interstitial != null || interstitialLoading) return;
        interstitialLoading = true;
        InterstitialAd.load(context.getApplicationContext(), INTERSTITIAL_ID, new AdRequest.Builder().build(),
                new InterstitialAdLoadCallback() {
                    @Override
                    public void onAdLoaded(@NonNull InterstitialAd ad) {
                        interstitial = ad;
                        interstitialLoading = false;
                    }

                    @Override
                    public void onAdFailedToLoad(@NonNull LoadAdError error) {
                        interstitial = null;
                        interstitialLoading = false;
                    }
                });
    }

    /** Called when the user opens a job. Shows an interstitial on every 3rd open. */
    public static void onJobOpened(Activity activity) {
        if (!ready) return;
        opens++;
        long now = System.currentTimeMillis();
        if (opens % INTERSTITIAL_EVERY == 0 && interstitial != null
                && now - lastShown > INTERSTITIAL_MIN_GAP_MS) {
            InterstitialAd ad = interstitial;
            interstitial = null;
            lastShown = now;
            ad.show(activity);
            loadInterstitial(activity);
        } else if (interstitial == null) {
            loadInterstitial(activity);
        }
    }
}
