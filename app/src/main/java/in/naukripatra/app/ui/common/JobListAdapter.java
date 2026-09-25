package in.naukripatra.app.ui.common;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.FrameLayout;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.gms.ads.nativead.NativeAd;

import java.util.ArrayList;
import java.util.List;

import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.Job;

/**
 * Job list with a native ad after every {@link #AD_EVERY} jobs and a loading row at
 * the end while the next page is fetched.
 */
public class JobListAdapter extends RecyclerView.Adapter<RecyclerView.ViewHolder> {

    public static final int TYPE_JOB = 0;
    public static final int TYPE_AD = 1;
    public static final int TYPE_LOADING = 2;
    public static final int TYPE_SKELETON = 3;

    private static final int AD_EVERY = 6;

    /** One row: a job, an ad slot or a placeholder. */
    private static final class Row {
        final int type;
        final Job job;
        NativeAd ad;
        boolean adRequested;
        boolean adFailed;

        Row(int type, Job job) {
            this.type = type;
            this.job = job;
        }
    }

    private final List<Row> rows = new ArrayList<>();
    private final JobViews.OnJobClick click;
    private final boolean withAds;
    private boolean loadingMore;
    private int jobCount;

    public JobListAdapter(JobViews.OnJobClick click, boolean withAds) {
        this.click = click;
        this.withAds = withAds;
        setHasStableIds(true);
    }

    public void showSkeleton(int count) {
        destroyAds();
        rows.clear();
        jobCount = 0;
        loadingMore = false;
        for (int i = 0; i < count; i++) rows.add(new Row(TYPE_SKELETON, null));
        notifyDataSetChanged();
    }

    public void setJobs(List<Job> jobs) {
        destroyAds();
        rows.clear();
        jobCount = 0;
        loadingMore = false;
        appendRows(jobs);
        notifyDataSetChanged();
    }

    public void addJobs(List<Job> jobs) {
        int start = rows.size();
        if (loadingMore) {
            loadingMore = false;
            notifyItemRemoved(start);
        }
        appendRows(jobs);
        notifyItemRangeInserted(start, rows.size() - start);
    }

    private void appendRows(List<Job> jobs) {
        for (Job job : jobs) {
            rows.add(new Row(TYPE_JOB, job));
            jobCount++;
            if (withAds && jobCount % AD_EVERY == 3) { // after the 3rd job, then every 6th
                rows.add(new Row(TYPE_AD, null));
            }
        }
    }

    public void setLoadingMore(boolean loading) {
        if (loading == loadingMore) return;
        loadingMore = loading;
        if (loading) notifyItemInserted(rows.size());
        else notifyItemRemoved(rows.size());
    }

    public int jobCount() {
        return jobCount;
    }

    public void refreshBookmarks() {
        notifyItemRangeChanged(0, getItemCount(), "bookmark");
    }

    public void destroyAds() {
        for (Row r : rows) {
            if (r.ad != null) {
                r.ad.destroy();
                r.ad = null;
            }
        }
    }

    /** Full-width rows (ads, loader) span all columns on tablets. */
    public boolean isFullSpan(int position) {
        int type = getItemViewType(position);
        return type == TYPE_LOADING || type == TYPE_AD;
    }

    @Override
    public int getItemViewType(int position) {
        if (position >= rows.size()) return TYPE_LOADING;
        return rows.get(position).type;
    }

    @Override
    public long getItemId(int position) {
        if (position >= rows.size()) return Long.MIN_VALUE;
        Row r = rows.get(position);
        if (r.type == TYPE_JOB) return r.job.id;
        return -1000L - position;
    }

    @Override
    public int getItemCount() {
        return rows.size() + (loadingMore ? 1 : 0);
    }

    @NonNull
    @Override
    public RecyclerView.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        LayoutInflater inf = LayoutInflater.from(parent.getContext());
        int layout;
        switch (viewType) {
            case TYPE_AD:
                layout = R.layout.item_ad;
                break;
            case TYPE_LOADING:
                layout = R.layout.item_loading;
                break;
            case TYPE_SKELETON:
                layout = R.layout.item_skeleton;
                break;
            default:
                layout = R.layout.item_job;
        }
        View v = inf.inflate(layout, parent, false);
        if (viewType == TYPE_SKELETON) Skeleton.pulse(v);
        return new RecyclerView.ViewHolder(v) {
        };
    }

    @Override
    public void onBindViewHolder(@NonNull RecyclerView.ViewHolder holder, int position) {
        int type = getItemViewType(position);
        if (type == TYPE_JOB) {
            JobViews.bind(holder.itemView, rows.get(position).job, click);
        } else if (type == TYPE_AD) {
            bindAd(holder.itemView, rows.get(position));
        }
    }

    @Override
    public void onBindViewHolder(@NonNull RecyclerView.ViewHolder holder, int position, @NonNull List<Object> payloads) {
        if (payloads.contains("bookmark") && getItemViewType(position) == TYPE_JOB) {
            Job job = rows.get(position).job;
            JobViews.bindBookmark(holder.itemView.findViewById(R.id.bookmark),
                    in.naukripatra.app.data.SavedJobs.isSaved(holder.itemView.getContext(), job.id), false);
            return;
        }
        super.onBindViewHolder(holder, position, payloads);
    }

    private void bindAd(View view, Row row) {
        FrameLayout container = view.findViewById(R.id.adContainer);
        View caption = view.findViewById(R.id.adCaption);
        if (row.ad != null) {
            Ads.bindNative(container, row.ad, false);
            caption.setVisibility(View.VISIBLE);
            return;
        }
        container.removeAllViews();
        container.setVisibility(View.GONE);
        caption.setVisibility(View.GONE);
        if (row.adRequested || row.adFailed) return;
        row.adRequested = true;
        Ads.loadNative(view.getContext(), ad -> {
            if (ad == null) {
                row.adFailed = true;
                return;
            }
            row.ad = ad;
            int index = rows.indexOf(row);
            if (index >= 0) notifyItemChanged(index);
            else ad.destroy();
        });
    }
}
