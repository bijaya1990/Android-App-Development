package com.naukripatra.emicalculator.util;

import android.animation.ValueAnimator;
import android.widget.TextView;

/** Counts a TextView's displayed rupee amount up from zero for a lively result reveal. */
public final class NumberAnimationUtils {

    private NumberAnimationUtils() {
    }

    public static void animateRupeeAmount(TextView textView, double targetValue, long durationMs) {
        ValueAnimator animator = ValueAnimator.ofFloat(0f, (float) targetValue);
        animator.setDuration(durationMs);
        animator.setInterpolator(new android.view.animation.DecelerateInterpolator());
        animator.addUpdateListener(animation -> {
            float animatedValue = (float) animation.getAnimatedValue();
            textView.setText(CurrencyUtils.formatWholeRupees(animatedValue));
        });
        animator.start();
    }
}
