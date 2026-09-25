package in.naukripatra.app.ui.common;

import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;

import in.naukripatra.app.R;

/** Controls view_state.xml (empty and error messages). */
public final class StateView {

    private final View root;
    private final ImageView icon;
    private final TextView title;
    private final TextView body;
    private final View retry;

    public StateView(View root) {
        this.root = root;
        icon = root.findViewById(R.id.stateIcon);
        title = root.findViewById(R.id.stateTitle);
        body = root.findViewById(R.id.stateBody);
        retry = root.findViewById(R.id.stateRetry);
    }

    public void showEmpty(int iconRes, CharSequence t, CharSequence b) {
        icon.setImageResource(iconRes);
        title.setText(t);
        body.setText(b);
        retry.setVisibility(View.GONE);
        root.setVisibility(View.VISIBLE);
    }

    public void showError(CharSequence message, Runnable onRetry) {
        icon.setImageResource(R.drawable.ic_wifi_off);
        title.setText(R.string.error_title);
        body.setText(message);
        retry.setVisibility(View.VISIBLE);
        retry.setOnClickListener(v -> onRetry.run());
        root.setVisibility(View.VISIBLE);
    }

    public void hide() {
        root.setVisibility(View.GONE);
    }
}
