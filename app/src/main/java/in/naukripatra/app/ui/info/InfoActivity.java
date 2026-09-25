package in.naukripatra.app.ui.info;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.text.method.LinkMovementMethod;
import android.text.util.Linkify;
import android.view.View;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;

import in.naukripatra.app.R;
import in.naukripatra.app.util.Ui;

/** About, Contact, Privacy Policy and Disclaimer pages. */
public class InfoActivity extends AppCompatActivity {

    public static final String ABOUT = "about";
    public static final String CONTACT = "contact";
    public static final String PRIVACY = "privacy";
    public static final String DISCLAIMER = "disclaimer";

    private static final String EXTRA_PAGE = "page";

    public static Intent intent(Context c, String page) {
        return new Intent(c, InfoActivity.class).putExtra(EXTRA_PAGE, page);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        EdgeToEdge.enable(this);
        super.onCreate(savedInstanceState);
        WindowCompat.getInsetsController(getWindow(), getWindow().getDecorView())
                .setAppearanceLightStatusBars(false);
        setContentView(R.layout.activity_info);
        Ui.padForInsets(findViewById(R.id.header), Ui.TOP | Ui.START | Ui.END);
        Ui.padForInsets(findViewById(R.id.sections), Ui.BOTTOM | Ui.START | Ui.END);
        findViewById(R.id.btnBack).setOnClickListener(v -> getOnBackPressedDispatcher().onBackPressed());

        String page = getIntent().getStringExtra(EXTRA_PAGE);
        String[][] content;
        String title;
        String subtitle;
        if (CONTACT.equals(page)) {
            title = getString(R.string.contact_us);
            subtitle = "We usually reply within 2 working days";
            content = InfoContent.CONTACT;
        } else if (PRIVACY.equals(page)) {
            title = getString(R.string.privacy_policy);
            subtitle = "Last updated: " + InfoContent.UPDATED;
            content = InfoContent.PRIVACY;
        } else if (DISCLAIMER.equals(page)) {
            title = getString(R.string.disclaimer);
            subtitle = "Please read before you apply";
            content = InfoContent.DISCLAIMER;
        } else {
            title = getString(R.string.about_us);
            subtitle = getString(R.string.tagline);
            content = InfoContent.ABOUT;
        }
        ((TextView) findViewById(R.id.title)).setText(title);
        ((TextView) findViewById(R.id.subtitle)).setText(subtitle);

        LinearLayout sections = findViewById(R.id.sections);
        for (String[] section : content) {
            View card = getLayoutInflater().inflate(R.layout.item_info_section, sections, false);
            TextView heading = card.findViewById(R.id.heading);
            TextView body = card.findViewById(R.id.body);
            if (section[0].isEmpty()) heading.setVisibility(View.GONE);
            else heading.setText(section[0]);
            body.setText(section[1]);
            Linkify.addLinks(body, Linkify.WEB_URLS | Linkify.EMAIL_ADDRESSES);
            body.setMovementMethod(LinkMovementMethod.getInstance());
            sections.addView(card);
        }
    }
}
