package in.naukripatra.app.ui.common;

import android.animation.ObjectAnimator;
import android.animation.ValueAnimator;
import android.view.View;

/** Soft pulsing animation for loading placeholders. */
public final class Skeleton {

    private Skeleton() {
    }

    public static void pulse(View view) {
        ObjectAnimator a = ObjectAnimator.ofFloat(view, View.ALPHA, 1f, 0.45f);
        a.setDuration(750);
        a.setRepeatMode(ValueAnimator.REVERSE);
        a.setRepeatCount(ValueAnimator.INFINITE);
        view.addOnAttachStateChangeListener(new View.OnAttachStateChangeListener() {
            @Override
            public void onViewAttachedToWindow(View v) {
                a.start();
            }

            @Override
            public void onViewDetachedFromWindow(View v) {
                a.cancel();
                v.setAlpha(1f);
            }
        });
    }
}
