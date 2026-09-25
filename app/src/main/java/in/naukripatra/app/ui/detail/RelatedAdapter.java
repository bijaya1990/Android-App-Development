package in.naukripatra.app.ui.detail;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import java.util.List;

import in.naukripatra.app.R;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.ui.common.JobViews;
import in.naukripatra.app.util.Ui;

class RelatedAdapter extends RecyclerView.Adapter<RecyclerView.ViewHolder> {

    private final List<Job> jobs;
    private final JobViews.OnJobClick click;

    RelatedAdapter(List<Job> jobs, JobViews.OnJobClick click) {
        this.jobs = jobs;
        this.click = click;
    }

    @NonNull
    @Override
    public RecyclerView.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_related, parent, false);
        return new RecyclerView.ViewHolder(v) {
        };
    }

    @Override
    public void onBindViewHolder(@NonNull RecyclerView.ViewHolder holder, int position) {
        Job job = jobs.get(position);
        View v = holder.itemView;
        Ui.bindLogo(v.findViewById(R.id.logo), job.initials(), job.title);
        ((TextView) v.findViewById(R.id.title)).setText(job.title);
        ((TextView) v.findViewById(R.id.date)).setText(v.getContext().getString(R.string.posted_on, job.date));
        v.setOnClickListener(x -> click.onJobClick(job));
    }

    @Override
    public int getItemCount() {
        return jobs.size();
    }
}
