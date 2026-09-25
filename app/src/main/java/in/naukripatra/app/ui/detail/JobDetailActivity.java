package in.naukripatra.app.ui.detail;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;

import in.naukripatra.app.R;

public class JobDetailActivity extends AppCompatActivity {

    private static final String EXTRA_ID = "post_id";
    private static final String EXTRA_SLUG = "slug";

    public static Intent forId(Context c, int id) {
        return new Intent(c, JobDetailActivity.class).putExtra(EXTRA_ID, id);
    }

    public static Intent forSlug(Context c, String slug) {
        return new Intent(c, JobDetailActivity.class).putExtra(EXTRA_SLUG, slug);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        EdgeToEdge.enable(this);
        super.onCreate(savedInstanceState);
        // White status bar icons over the blue header.
        WindowCompat.getInsetsController(getWindow(), getWindow().getDecorView())
                .setAppearanceLightStatusBars(false);
        setContentView(R.layout.activity_container);
        if (savedInstanceState == null) {
            int id = getIntent().getIntExtra(EXTRA_ID, 0);
            String slug = getIntent().getStringExtra(EXTRA_SLUG);
            getSupportFragmentManager().beginTransaction()
                    .replace(R.id.container, JobDetailFragment.newInstance(id, slug, false))
                    .commit();
        }
    }
}
