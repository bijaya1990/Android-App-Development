package in.naukripatra.app.ui.home;

import android.graphics.drawable.GradientDrawable;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import java.util.ArrayList;
import java.util.List;

import in.naukripatra.app.R;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.SavedJobs;
import in.naukripatra.app.ui.common.JobViews;
import in.naukripatra.app.util.Deadline;
import in.naukripatra.app.util.Text;
import in.naukripatra.app.util.Ui;

/** Colourful horizontal cards for the "For You" row. */
class FeaturedAdapter extends RecyclerView.Adapter<FeaturedAdapter.Holder> {

    private static final int[][] GRADIENTS = {
            {0xFF7A3CF0, 0xFFA66BFF},
            {0xFF0E9F9A, 0xFF35C8B8},
            {0xFF1D4ED8, 0xFF06B6D4},
            {0xFFE0357A, 0xFFFF7AA8},
            {0xFFEA580C, 0xFFFFA24D},
    };

    private final List<Job> jobs = new ArrayList<>();
    private final JobViews.OnJobClick click;

    FeaturedAdapter(JobViews.OnJobClick click) {
        this.click = click;
        setHasStableIds(true);
    }

    void setJobs(List<Job> list) {
        jobs.clear();
        jobs.addAll(list);
        notifyDataSetChanged();
    }

    @Override
    public long getItemId(int position) {
        return jobs.get(position).id;
    }

    @NonNull
    @Override
    public Holder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        return new Holder(LayoutInflater.from(parent.getContext()).inflate(R.layout.item_job_featured, parent, false));
    }

    @Override
    public void onBindViewHolder(@NonNull Holder h, int position) {
        Job job = jobs.get(position);
        int[] colors = GRADIENTS[position % GRADIENTS.length];
        GradientDrawable bg = new GradientDrawable(GradientDrawable.Orientation.TL_BR, colors);
        bg.setCornerRadius(Ui.dp(h.itemView.getContext(), 18));
        h.itemView.setBackground(bg);

        h.logo.setText(job.initials());
        h.title.setText(job.title);

        StringBuilder meta = new StringBuilder();
        if (!job.vacancy.isEmpty()) meta.append(JobViews.postsLabel(job.vacancy));
        if (!job.location.isEmpty()) {
            if (meta.length() > 0) meta.append(" · ");
            meta.append(job.location);
        }
        if (meta.length() == 0) meta.append(job.organization);
        Ui.textOrGone(h.meta, meta.toString());

        int days = Deadline.daysLeft(job.lastDate);
        String deadline;
        if (days == Deadline.UNKNOWN) {
            deadline = Text.isEmpty(job.lastDate) ? h.itemView.getContext().getString(R.string.posted_on, job.date)
                    : job.lastDate;
        } else if (days < 0) {
            deadline = "Closed on " + Deadline.pretty(job.lastDate);
        } else {
            deadline = "Apply by " + Deadline.pretty(job.lastDate) + " · "
                    + (days == 0 ? "last day" : days == 1 ? "1 day left" : days + " days left");
        }
        h.deadline.setText(deadline);

        JobViews.bindBookmark(h.bookmark, SavedJobs.isSaved(h.itemView.getContext(), job.id), true);
        h.bookmark.setImageResource(SavedJobs.isSaved(h.itemView.getContext(), job.id)
                ? R.drawable.ic_bookmark : R.drawable.ic_bookmark_border);
        h.bookmark.setOnClickListener(v -> {
            SavedJobs.toggle(v.getContext(), job);
            notifyItemChanged(h.getBindingAdapterPosition());
        });
        h.itemView.setOnClickListener(v -> click.onJobClick(job));
    }

    @Override
    public int getItemCount() {
        return jobs.size();
    }

    static class Holder extends RecyclerView.ViewHolder {
        final TextView logo, title, meta, deadline;
        final ImageButton bookmark;

        Holder(View v) {
            super(v);
            logo = v.findViewById(R.id.logo);
            title = v.findViewById(R.id.title);
            meta = v.findViewById(R.id.meta);
            deadline = v.findViewById(R.id.deadline);
            bookmark = v.findViewById(R.id.bookmark);
        }
    }
}
