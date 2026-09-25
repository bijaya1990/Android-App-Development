package in.naukripatra.app.ui.common;

import android.content.Context;
import android.view.View;
import android.widget.ImageButton;
import android.widget.TextView;
import android.widget.Toast;

import androidx.core.content.ContextCompat;
import androidx.core.widget.ImageViewCompat;

import in.naukripatra.app.R;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.SavedJobs;
import in.naukripatra.app.util.Deadline;
import in.naukripatra.app.util.Text;
import in.naukripatra.app.util.Ui;

/** Binds a {@link Job} into item_job.xml. Shared by every list in the app. */
public final class JobViews {

    private JobViews() {
    }

    public static void bind(View card, Job job, OnJobClick click) {
        Context c = card.getContext();
        TextView logo = card.findViewById(R.id.logo);
        TextView title = card.findViewById(R.id.title);
        TextView org = card.findViewById(R.id.org);
        TextView location = card.findViewById(R.id.location);
        TextView vacancy = card.findViewById(R.id.vacancy);
        TextView qualification = card.findViewById(R.id.qualification);
        TextView lastDate = card.findViewById(R.id.lastDate);
        TextView badge = card.findViewById(R.id.badge);
        TextView newTag = card.findViewById(R.id.newTag);
        View metaRow = card.findViewById(R.id.metaRow);

        Ui.bindThumb(card.findViewById(R.id.thumb), logo, job.image, job.initials(),
                job.organization.isEmpty() ? job.title : job.organization);
        title.setText(job.title);
        Ui.textOrGone(org, !job.organization.isEmpty() ? job.organization : job.date);

        int chipColor = ContextCompat.getColor(c, R.color.text_2);
        meta(location, job.location, R.drawable.ic_location_on, chipColor);
        meta(vacancy, job.vacancy.isEmpty() ? "" : postsLabel(job.vacancy), R.drawable.ic_groups, chipColor);
        meta(qualification, job.qualification, R.drawable.ic_school, chipColor);
        metaRow.setVisibility(location.getVisibility() == View.GONE && vacancy.getVisibility() == View.GONE
                && qualification.getVisibility() == View.GONE ? View.GONE : View.VISIBLE);

        if (!job.lastDate.isEmpty()) {
            lastDate.setText(c.getString(R.string.last_date_value, Deadline.pretty(job.lastDate)));
        } else {
            lastDate.setText(c.getString(R.string.posted_on, job.date));
        }
        Deadline.bind(badge, job.lastDate);
        newTag.setVisibility(Deadline.isNew(job.date) ? View.VISIBLE : View.GONE);

        ImageButton bookmark = card.findViewById(R.id.bookmark);
        bindBookmark(bookmark, SavedJobs.isSaved(c, job.id), false);
        bookmark.setOnClickListener(v -> {
            boolean saved = SavedJobs.toggle(c, job);
            bindBookmark(bookmark, saved, false);
            Toast.makeText(c, saved ? R.string.saved_toast : R.string.removed_toast, Toast.LENGTH_SHORT).show();
        });

        card.setOnClickListener(v -> click.onJobClick(job));
    }

    public static void bindBookmark(ImageButton button, boolean saved, boolean onBrand) {
        button.setImageResource(saved ? R.drawable.ic_bookmark : R.drawable.ic_bookmark_border);
        int color = onBrand ? R.color.on_brand : saved ? R.color.brand_700 : R.color.text_3;
        ImageViewCompat.setImageTintList(button,
                android.content.res.ColorStateList.valueOf(ContextCompat.getColor(button.getContext(), color)));
        button.setContentDescription(button.getContext().getString(saved ? R.string.saved : R.string.save));
    }

    private static void meta(TextView view, String text, int icon, int color) {
        if (Text.isEmpty(text)) {
            view.setVisibility(View.GONE);
            return;
        }
        view.setVisibility(View.VISIBLE);
        view.setText(text);
        Ui.startIcon(view, icon, 15, color);
    }

    /** "12" -> "12 Posts"; longer values are kept as written. */
    public static String postsLabel(String vacancy) {
        String v = vacancy.trim();
        if (v.matches("[\\d,]+")) {
            try {
                int n = Integer.parseInt(v.replace(",", ""));
                return Text.number(n) + (n == 1 ? " Post" : " Posts");
            } catch (NumberFormatException ignored) {
            }
        }
        return v;
    }

    public interface OnJobClick {
        void onJobClick(Job job);
    }
}
