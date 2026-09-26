package in.naukripatra.app.util;

import android.content.Context;
import android.content.res.ColorStateList;
import android.graphics.drawable.Drawable;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.ColorInt;
import androidx.annotation.DrawableRes;
import androidx.appcompat.content.res.AppCompatResources;
import androidx.core.content.ContextCompat;
import androidx.core.graphics.Insets;
import androidx.core.graphics.drawable.DrawableCompat;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import in.naukripatra.app.R;

public final class Ui {

    private Ui() {
    }

    public static int dp(Context c, float dp) {
        return Math.round(dp * c.getResources().getDisplayMetrics().density);
    }

    /** Puts a small tinted icon before the text of a TextView. */
    public static void startIcon(TextView view, @DrawableRes int icon, int sizeDp, @ColorInt int color) {
        Drawable d = AppCompatResources.getDrawable(view.getContext(), icon);
        if (d == null) return;
        d = DrawableCompat.wrap(d.mutate());
        DrawableCompat.setTint(d, color);
        int size = dp(view.getContext(), sizeDp);
        d.setBounds(0, 0, size, size);
        view.setCompoundDrawablesRelative(d, null, null, null);
    }

    /** Same as {@link #startIcon} but after the text. */
    public static void endIcon(TextView view, @DrawableRes int icon, int sizeDp, @ColorInt int color) {
        startIcon(view, icon, sizeDp, color);
        Drawable d = view.getCompoundDrawablesRelative()[0];
        view.setCompoundDrawablesRelative(null, null, d, null);
    }

    public static void startIcon(TextView view, @DrawableRes int icon, int sizeDp) {
        startIcon(view, icon, sizeDp, view.getCurrentTextColor());
    }

    /** Sets text and hides the view when there is nothing to show. */
    public static void textOrGone(TextView view, String text) {
        if (Text.isEmpty(text)) {
            view.setVisibility(View.GONE);
        } else {
            view.setText(text);
            view.setVisibility(View.VISIBLE);
        }
    }

    public static void tintBackground(View view, int colorRes) {
        view.setBackgroundTintList(ColorStateList.valueOf(ContextCompat.getColor(view.getContext(), colorRes)));
    }

    /** Logo box colours, picked from the organisation name so each one keeps its colour. */
    private static final int[][] LOGO_COLORS = {
            {R.color.brand_100, R.color.brand_700},
            {R.color.green_100, R.color.green_text},
            {R.color.accent_100, R.color.accent_text},
            {R.color.purple_100, R.color.purple},
            {R.color.pink_100, R.color.pink},
            {R.color.teal_100, R.color.teal},
            {R.color.amber_100, R.color.amber},
    };

    public static void bindLogo(TextView logo, String initials, String key) {
        logo.setText(initials);
        int[] pair = LOGO_COLORS[Math.abs((key == null ? "" : key).hashCode()) % LOGO_COLORS.length];
        Context c = logo.getContext();
        logo.setBackgroundTintList(ColorStateList.valueOf(ContextCompat.getColor(c, pair[0])));
        logo.setTextColor(ContextCompat.getColor(c, pair[1]));
        logo.setTextSize(android.util.TypedValue.COMPLEX_UNIT_SP, initials.length() > 3 ? 10.5f : 13f);
    }

    /**
     * Shows the API thumbnail with rounded corners. The initials box underneath stays
     * as the placeholder and remains visible if there is no image or it fails to load.
     */
    public static void bindThumb(android.widget.ImageView thumb, TextView fallback, String url,
                                 String initials, String key) {
        bindThumb(thumb, fallback, url, initials, key, 10);
    }

    public static void bindThumb(android.widget.ImageView thumb, TextView fallback, String url,
                                 String initials, String key, int radiusDp) {
        bindLogo(fallback, initials, key);
        com.bumptech.glide.Glide.with(thumb).clear(thumb);
        if (Text.isEmpty(url)) {
            thumb.setVisibility(View.GONE);
            return;
        }
        thumb.setVisibility(View.VISIBLE);
        com.bumptech.glide.Glide.with(thumb)
                .load(url)
                .transform(new com.bumptech.glide.load.resource.bitmap.CenterCrop(),
                        new com.bumptech.glide.load.resource.bitmap.RoundedCorners(dp(thumb.getContext(), radiusDp)))
                .transition(com.bumptech.glide.load.resource.drawable.DrawableTransitionOptions.withCrossFade(150))
                .listener(new com.bumptech.glide.request.RequestListener<Drawable>() {
                    @Override
                    public boolean onLoadFailed(@androidx.annotation.Nullable com.bumptech.glide.load.engine.GlideException e,
                                                Object model,
                                                @androidx.annotation.NonNull com.bumptech.glide.request.target.Target<Drawable> target,
                                                boolean isFirstResource) {
                        thumb.setVisibility(View.GONE);
                        return true;
                    }

                    @Override
                    public boolean onResourceReady(@androidx.annotation.NonNull Drawable resource,
                                                   @androidx.annotation.NonNull Object model,
                                                   com.bumptech.glide.request.target.Target<Drawable> target,
                                                   @androidx.annotation.NonNull com.bumptech.glide.load.DataSource dataSource,
                                                   boolean isFirstResource) {
                        return false;
                    }
                })
                .into(thumb);
    }

    /**
     * Gentle attention pulse (fade + slight grow) that repeats while the view is on
     * screen. Follows the system "remove animations" setting automatically.
     */
    public static void blink(View view) {
        android.animation.PropertyValuesHolder alpha =
                android.animation.PropertyValuesHolder.ofFloat(View.ALPHA, 1f, 0.72f);
        android.animation.PropertyValuesHolder sx =
                android.animation.PropertyValuesHolder.ofFloat(View.SCALE_X, 1f, 1.025f);
        android.animation.PropertyValuesHolder sy =
                android.animation.PropertyValuesHolder.ofFloat(View.SCALE_Y, 1f, 1.025f);
        android.animation.ObjectAnimator a =
                android.animation.ObjectAnimator.ofPropertyValuesHolder(view, alpha, sx, sy);
        a.setDuration(650);
        a.setRepeatCount(android.animation.ValueAnimator.INFINITE);
        a.setRepeatMode(android.animation.ValueAnimator.REVERSE);
        a.setInterpolator(new android.view.animation.AccelerateDecelerateInterpolator());
        view.addOnAttachStateChangeListener(new View.OnAttachStateChangeListener() {
            @Override
            public void onViewAttachedToWindow(View v) {
                a.start();
            }

            @Override
            public void onViewDetachedFromWindow(View v) {
                a.cancel();
                v.setAlpha(1f);
                v.setScaleX(1f);
                v.setScaleY(1f);
            }
        });
        if (view.isAttachedToWindow()) a.start();
    }

    // ============ Edge-to-edge insets ============

    public static final int TOP = 1;
    public static final int BOTTOM = 2;
    public static final int START = 4;
    public static final int END = 8;

    /** Adds system bar / cutout insets on the given sides on top of the XML padding. */
    public static void padForInsets(View view, int sides) {
        final int l = view.getPaddingLeft();
        final int t = view.getPaddingTop();
        final int r = view.getPaddingRight();
        final int b = view.getPaddingBottom();
        ViewCompat.setOnApplyWindowInsetsListener(view, (v, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars()
                    | WindowInsetsCompat.Type.displayCutout());
            boolean rtl = v.getLayoutDirection() == View.LAYOUT_DIRECTION_RTL;
            int left = (sides & (rtl ? END : START)) != 0 ? bars.left : 0;
            int right = (sides & (rtl ? START : END)) != 0 ? bars.right : 0;
            v.setPadding(l + left,
                    t + ((sides & TOP) != 0 ? bars.top : 0),
                    r + right,
                    b + ((sides & BOTTOM) != 0 ? bars.bottom : 0));
            return insets;
        });
        ViewCompat.requestApplyInsets(view);
    }

    /** Same as {@link #padForInsets} but grows a view's margin instead of its padding. */
    public static void marginForInsets(View view, int sides) {
        ViewGroup.MarginLayoutParams lp = (ViewGroup.MarginLayoutParams) view.getLayoutParams();
        final int t = lp.topMargin;
        final int b = lp.bottomMargin;
        ViewCompat.setOnApplyWindowInsetsListener(view, (v, insets) -> {
            Insets bars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            ViewGroup.MarginLayoutParams p = (ViewGroup.MarginLayoutParams) v.getLayoutParams();
            p.topMargin = t + ((sides & TOP) != 0 ? bars.top : 0);
            p.bottomMargin = b + ((sides & BOTTOM) != 0 ? bars.bottom : 0);
            v.setLayoutParams(p);
            return insets;
        });
        ViewCompat.requestApplyInsets(view);
    }

    public static boolean isNight(Context c) {
        int mode = c.getResources().getConfiguration().uiMode
                & android.content.res.Configuration.UI_MODE_NIGHT_MASK;
        return mode == android.content.res.Configuration.UI_MODE_NIGHT_YES;
    }
}
