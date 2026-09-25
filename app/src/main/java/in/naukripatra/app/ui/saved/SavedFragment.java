package in.naukripatra.app.ui.saved;

import android.content.Context;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import java.util.List;

import in.naukripatra.app.R;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.SavedJobs;
import in.naukripatra.app.ui.common.JobViews;
import in.naukripatra.app.ui.common.StateView;
import in.naukripatra.app.ui.main.Host;
import in.naukripatra.app.util.Deadline;
import in.naukripatra.app.util.Ui;

public class SavedFragment extends Fragment implements SavedJobs.Listener {

    private Host host;
    private Adapter adapter;
    private StateView stateView;

    @Override
    public void onAttach(@NonNull Context context) {
        super.onAttach(context);
        host = (Host) context;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle state) {
        return inflater.inflate(R.layout.fragment_saved, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        Ui.padForInsets(v.findViewById(R.id.topBar), Ui.TOP);
        RecyclerView list = v.findViewById(R.id.list);
        int columns = getResources().getInteger(R.integer.job_columns);
        GridLayoutManager lm = new GridLayoutManager(requireContext(), columns);
        adapter = new Adapter();
        lm.setSpanSizeLookup(new GridLayoutManager.SpanSizeLookup() {
            @Override
            public int getSpanSize(int position) {
                return adapter.hasAlert() && position == 0 ? lm.getSpanCount() : 1;
            }
        });
        list.setLayoutManager(lm);
        list.setAdapter(adapter);
        stateView = new StateView(v.findViewById(R.id.state));
        SavedJobs.addListener(this);
        refresh();
    }

    @Override
    public void onDestroyView() {
        SavedJobs.removeListener(this);
        super.onDestroyView();
    }

    @Override
    public void onSavedJobsChanged() {
        if (getView() != null) refresh();
    }

    private void refresh() {
        List<Job> jobs = SavedJobs.all(requireContext());
        int closing = 0;
        for (Job j : jobs) {
            int d = Deadline.daysLeft(j.lastDate);
            if (d != Deadline.UNKNOWN && d >= 0 && d <= 7) closing++;
        }
        adapter.set(jobs, closing);
        if (jobs.isEmpty()) {
            stateView.showEmpty(R.drawable.ic_bookmark_border, getString(R.string.saved_empty_title),
                    getString(R.string.saved_empty_body));
        } else {
            stateView.hide();
        }
    }

    private class Adapter extends RecyclerView.Adapter<RecyclerView.ViewHolder> {
        private List<Job> jobs = java.util.Collections.emptyList();
        private int closing;

        void set(List<Job> jobs, int closing) {
            this.jobs = jobs;
            this.closing = closing;
            notifyDataSetChanged();
        }

        boolean hasAlert() {
            return closing > 0;
        }

        @Override
        public int getItemViewType(int position) {
            return hasAlert() && position == 0 ? 1 : 0;
        }

        @NonNull
        @Override
        public RecyclerView.ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int type) {
            int layout = type == 1 ? R.layout.item_saved_alert : R.layout.item_job;
            return new RecyclerView.ViewHolder(LayoutInflater.from(parent.getContext()).inflate(layout, parent, false)) {
            };
        }

        @Override
        public void onBindViewHolder(@NonNull RecyclerView.ViewHolder h, int position) {
            if (getItemViewType(position) == 1) {
                ((TextView) h.itemView.findViewById(R.id.alertTitle)).setText(closing == 1
                        ? getString(R.string.saved_closing_one) : getString(R.string.saved_closing, closing));
                return;
            }
            Job job = jobs.get(position - (hasAlert() ? 1 : 0));
            JobViews.bind(h.itemView, job, host::openJob);
        }

        @Override
        public int getItemCount() {
            return jobs.size() + (hasAlert() ? 1 : 0);
        }
    }
}
