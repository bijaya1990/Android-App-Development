package in.naukripatra.app.ui.onboarding;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.widget.ArrayAdapter;
import com.google.android.material.button.MaterialButton;
import android.widget.CheckedTextView;
import android.widget.GridLayout;
import android.widget.ViewFlipper;

import androidx.activity.EdgeToEdge;
import androidx.activity.OnBackPressedCallback;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

import com.google.android.material.textfield.MaterialAutoCompleteTextView;

import java.util.ArrayList;
import java.util.List;

import in.naukripatra.app.R;
import in.naukripatra.app.data.Prefs;
import in.naukripatra.app.ui.main.MainActivity;
import in.naukripatra.app.util.Ui;

/** Two quick questions - qualification and state - used to personalise jobs. No login. */
public class OnboardingActivity extends AppCompatActivity {

    private static final String EXTRA_EDIT = "edit";

    private static final int[] QUAL_ICONS = {
            R.drawable.ic_menu_book, R.drawable.ic_article, R.drawable.ic_description, R.drawable.ic_engineering,
            R.drawable.ic_badge, R.drawable.ic_school, R.drawable.ic_fact_check, R.drawable.ic_work
    };
    private static final int[] QUAL_COLORS = {
            R.color.pink, R.color.accent, R.color.teal, R.color.amber,
            R.color.green, R.color.brand_700, R.color.purple, R.color.brand
    };

    private boolean editMode;
    private ViewFlipper flipper;
    private MaterialButton next;
    private final List<CheckedTextView> options = new ArrayList<>();
    private String qualification;
    private String state;

    public static Intent edit(Context c) {
        return new Intent(c, OnboardingActivity.class).putExtra(EXTRA_EDIT, true);
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        EdgeToEdge.enable(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_onboarding);
        Ui.padForInsets(findViewById(R.id.root), Ui.TOP | Ui.BOTTOM | Ui.START | Ui.END);

        editMode = getIntent().getBooleanExtra(EXTRA_EDIT, false);
        qualification = Prefs.qualification(this);
        state = Prefs.state(this);

        flipper = findViewById(R.id.flipper);
        next = findViewById(R.id.next);

        buildQualifications(findViewById(R.id.qualGrid));
        setupStates(findViewById(R.id.stateDropdown));

        findViewById(R.id.skip).setOnClickListener(v -> {
            if (flipper.getDisplayedChild() == 0) showStep(1);
            else finishOnboarding();
        });
        next.setOnClickListener(v -> {
            if (flipper.getDisplayedChild() == 0) showStep(1);
            else finishOnboarding();
        });

        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                if (flipper.getDisplayedChild() == 1) {
                    showStep(0);
                } else if (editMode) {
                    finish();
                } else {
                    finishOnboarding();
                }
            }
        });
        showStep(0);
    }

    private void buildQualifications(GridLayout grid) {
        for (int i = 0; i < Prefs.QUALIFICATIONS.length; i++) {
            String q = Prefs.QUALIFICATIONS[i];
            CheckedTextView option = (CheckedTextView) getLayoutInflater().inflate(R.layout.item_qual_option, grid, false);
            option.setText(q.equals("B.Tech") ? "B.Tech / B.E." : q);
            Ui.startIcon(option, QUAL_ICONS[i], 20, ContextCompat.getColor(this, QUAL_COLORS[i]));
            option.setChecked(q.equals(qualification));
            option.setOnClickListener(v -> select(q));
            GridLayout.LayoutParams lp = new GridLayout.LayoutParams(
                    GridLayout.spec(GridLayout.UNDEFINED), GridLayout.spec(GridLayout.UNDEFINED, 1f));
            lp.width = 0;
            int gap = Ui.dp(this, 5);
            lp.setMargins(gap, gap, gap, gap);
            option.setLayoutParams(lp);
            option.setTag(q);
            options.add(option);
            grid.addView(option);
        }
    }

    private void select(String q) {
        qualification = q;
        for (CheckedTextView o : options) o.setChecked(q.equals(o.getTag()));
    }

    private void setupStates(MaterialAutoCompleteTextView dropdown) {
        dropdown.setAdapter(new ArrayAdapter<>(this, R.layout.item_dropdown, Prefs.STATES));
        if (!state.isEmpty()) dropdown.setText(state, false);
        dropdown.setOnItemClickListener((parent, view, position, id) -> state = Prefs.STATES[position]);
    }

    private void showStep(int step) {
        flipper.setDisplayedChild(step);
        findViewById(R.id.bar2).setBackgroundTintList(android.content.res.ColorStateList.valueOf(
                ContextCompat.getColor(this, step == 1 ? R.color.brand_700 : R.color.line)));
        next.setText(step == 0 ? R.string.continue_btn : R.string.show_my_jobs);
        next.setIconResource(step == 0 ? R.drawable.ic_arrow_forward : R.drawable.ic_rocket_launch);
    }

    private void finishOnboarding() {
        Prefs.setQualification(this, qualification);
        Prefs.setState(this, state);
        Prefs.setOnboarded(this);
        if (!editMode) {
            startActivity(new Intent(this, MainActivity.class));
        }
        finish();
    }
}
