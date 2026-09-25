package in.naukripatra.app.ui.home;

import android.content.Context;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.FrameLayout;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.gms.ads.AdView;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.Api;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.JobQuery;
import in.naukripatra.app.data.Prefs;
import in.naukripatra.app.data.SavedJobs;
import in.naukripatra.app.ui.common.Dialogs;
import in.naukripatra.app.ui.common.JobListAdapter;
import in.naukripatra.app.ui.common.StateView;
import in.naukripatra.app.ui.main.Host;
import in.naukripatra.app.util.Deadline;
import in.naukripatra.app.util.Text;
import in.naukripatra.app.util.Ui;
import okhttp3.Call;

public class HomeFragment extends Fragment implements SavedJobs.Listener {

    private static final int LATEST_ON_HOME = 10;

    private Host host;
    private SwipeRefreshLayout refresh;
    private JobListAdapter latestAdapter;
    private JobListAdapter closingAdapter;
    private FeaturedAdapter featuredAdapter;
    private StateView stateView;
    private AdView banner;
    private final List<Call> calls = new ArrayList<>();

    private String loadedQualification;
    private String loadedState;

    @Override
    public void onAttach(@NonNull Context context) {
        super.onAttach(context);
        host = (Host) context;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle state) {
        return inflater.inflate(R.layout.fragment_home, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        Ui.padForInsets(v.findViewById(R.id.header), Ui.TOP);

        refresh = v.findViewById(R.id.refresh);
        refresh.setColorSchemeResources(R.color.brand_700, R.color.cyan);
        refresh.setOnRefreshListener(() -> load(true));

        v.findViewById(R.id.btnTheme).setOnClickListener(x -> Dialogs.theme(requireActivity()));
        v.findViewById(R.id.searchBar).setOnClickListener(x -> host.openSearch());
        v.findViewById(R.id.latestAll).setOnClickListener(x -> host.openJobs(JobQuery.latest()));
        v.findViewById(R.id.btnMoreJobs).setOnClickListener(x -> host.openJobs(JobQuery.latest()));
        v.findViewById(R.id.btnSetup).setOnClickListener(x -> host.openPreferences());
        v.findViewById(R.id.forYouAll).setOnClickListener(x -> {
            String q = Prefs.qualification(requireContext());
            if (!q.isEmpty()) host.openJobs(JobQuery.qualification(q));
        });
        v.findViewById(R.id.stat1).setOnClickListener(x -> host.openJobs(JobQuery.latest()));
        v.findViewById(R.id.stat3).setOnClickListener(x -> host.openJobs(JobQuery.usa()));

        buildCategories(v.findViewById(R.id.categories));

        int columns = getResources().getInteger(R.integer.job_columns);

        RecyclerView latest = v.findViewById(R.id.latestList);
        latest.setLayoutManager(new GridLayoutManager(requireContext(), columns));
        latestAdapter = new JobListAdapter(host::openJob, false);
        latest.setAdapter(latestAdapter);
        latest.setItemAnimator(null);

        RecyclerView closing = v.findViewById(R.id.closingList);
        closing.setLayoutManager(new GridLayoutManager(requireContext(), columns));
        closingAdapter = new JobListAdapter(host::openJob, false);
        closing.setAdapter(closingAdapter);
        closing.setItemAnimator(null);

        RecyclerView featured = v.findViewById(R.id.forYouList);
        featuredAdapter = new FeaturedAdapter(host::openJob);
        featured.setAdapter(featuredAdapter);

        stateView = new StateView(v.findViewById(R.id.state));

        banner = Ads.banner(requireActivity(), v.findViewById(R.id.bannerAd));

        SavedJobs.addListener(this);
        latestAdapter.showSkeleton(4);
        load(false);
    }

    @Override
    public void onResume() {
        super.onResume();
        if (banner != null) banner.resume();
        // Preferences may have changed in onboarding / More.
        Context c = requireContext();
        if (!Prefs.qualification(c).equals(loadedQualification) || !Prefs.state(c).equals(loadedState)) {
            loadPersonal(false);
        }
    }

    @Override
    public void onPause() {
        if (banner != null) banner.pause();
        super.onPause();
    }

    @Override
    public void onDestroyView() {
        SavedJobs.removeListener(this);
        for (Call c : calls) c.cancel();
        calls.clear();
        if (banner != null) banner.destroy();
        super.onDestroyView();
    }

    @Override
    public void onSavedJobsChanged() {
        if (latestAdapter == null) return;
        latestAdapter.refreshBookmarks();
        closingAdapter.refreshBookmarks();
        featuredAdapter.notifyDataSetChanged();
    }

    // ============ Data ============

    private void load(boolean fresh) {
        stateView.hide();
        calls.add(Api.get(Api.jobs(JobQuery.latest(), 1, 20), fresh,
                json -> Job.Page.fromJson(json, 1), new Api.Result<Job.Page>() {
                    @Override
                    public void onSuccess(Job.Page page) {
                        if (!isAdded()) return;
                        refresh.setRefreshing(false);
                        setStat(R.id.stat1Value, page.total);
                        showLatest(page.jobs);
                    }

                    @Override
                    public void onError(@NonNull String message) {
                        if (!isAdded()) return;
                        refresh.setRefreshing(false);
                        latestAdapter.setJobs(Collections.emptyList());
                        stateView.showError(message, () -> {
                            latestAdapter.showSkeleton(4);
                            load(true);
                        });
                    }
                }));

        calls.add(Api.get(Api.jobs(JobQuery.usa(), 1, 1), fresh,
                json -> Job.Page.fromJson(json, 1), new Api.Result<Job.Page>() {
                    @Override
                    public void onSuccess(Job.Page page) {
                        if (isAdded()) setStat(R.id.stat3Value, page.total);
                    }

                    @Override
                    public void onError(@NonNull String message) {
                    }
                }));

        loadPersonal(fresh);
    }

    /** Parts of Home that depend on the user's qualification and state. */
    private void loadPersonal(boolean fresh) {
        View v = getView();
        if (v == null) return;
        Context c = requireContext();
        String qualification = Prefs.qualification(c);
        String state = Prefs.state(c);
        loadedQualification = qualification;
        loadedState = state;

        // Stat 2: jobs in the user's state, or All India.
        TextView label2 = v.findViewById(R.id.stat2Label);
        JobQuery stat2Query = state.isEmpty() ? JobQuery.allIndia() : JobQuery.state(state);
        label2.setText(state.isEmpty() ? getString(R.string.stat_all_india) : getString(R.string.stat_in_state, state));
        v.findViewById(R.id.stat2).setOnClickListener(x -> host.openJobs(stat2Query));
        calls.add(Api.get(Api.jobs(stat2Query, 1, 1), fresh, json -> Job.Page.fromJson(json, 1),
                new Api.Result<Job.Page>() {
                    @Override
                    public void onSuccess(Job.Page page) {
                        if (isAdded()) setStat(R.id.stat2Value, page.total);
                    }

                    @Override
                    public void onError(@NonNull String message) {
                    }
                }));

        // For You row.
        View header = v.findViewById(R.id.forYouHeader);
        View list = v.findViewById(R.id.forYouList);
        View setup = v.findViewById(R.id.setupCard);
        if (qualification.isEmpty()) {
            header.setVisibility(View.GONE);
            list.setVisibility(View.GONE);
            setup.setVisibility(View.VISIBLE);
            return;
        }
        setup.setVisibility(View.GONE);
        header.setVisibility(View.VISIBLE);
        list.setVisibility(View.VISIBLE);
        TextView sub = v.findViewById(R.id.forYouSub);
        sub.setText("· " + qualification + (state.isEmpty() ? "" : " · " + state));
        calls.add(Api.get(Api.jobs(JobQuery.qualification(qualification), 1, 10), fresh,
                json -> Job.Page.fromJson(json, 1), new Api.Result<Job.Page>() {
                    @Override
                    public void onSuccess(Job.Page page) {
                        if (!isAdded()) return;
                        List<Job> open = new ArrayList<>();
                        for (Job j : page.jobs) {
                            int d = Deadline.daysLeft(j.lastDate);
                            if (d == Deadline.UNKNOWN || d >= 0) open.add(j);
                        }
                        featuredAdapter.setJobs(open);
                        boolean empty = open.isEmpty();
                        header.setVisibility(empty ? View.GONE : View.VISIBLE);
                        list.setVisibility(empty ? View.GONE : View.VISIBLE);
                    }

                    @Override
                    public void onError(@NonNull String message) {
                        if (!isAdded()) return;
                        header.setVisibility(View.GONE);
                        list.setVisibility(View.GONE);
                    }
                }));
    }

    private void showLatest(List<Job> jobs) {
        View v = getView();
        if (v == null) return;
        latestAdapter.setJobs(jobs.subList(0, Math.min(LATEST_ON_HOME, jobs.size())));
        v.findViewById(R.id.btnMoreJobs).setVisibility(jobs.isEmpty() ? View.GONE : View.VISIBLE);

        // Closing soon: open jobs whose last date is within 5 days, soonest first.
        List<Job> closing = new ArrayList<>();
        for (Job j : jobs) {
            int d = Deadline.daysLeft(j.lastDate);
            if (d != Deadline.UNKNOWN && d >= 0 && d <= 5) closing.add(j);
        }
        Collections.sort(closing, (a, b) -> Integer.compare(Deadline.daysLeft(a.lastDate), Deadline.daysLeft(b.lastDate)));
        if (closing.size() > 3) closing = closing.subList(0, 3);
        boolean show = !closing.isEmpty();
        v.findViewById(R.id.closingHeader).setVisibility(show ? View.VISIBLE : View.GONE);
        v.findViewById(R.id.closingList).setVisibility(show ? View.VISIBLE : View.GONE);
        closingAdapter.setJobs(closing);
    }

    private void setStat(int id, int value) {
        View v = getView();
        if (v != null) ((TextView) v.findViewById(id)).setText(Text.number(value));
    }

    // ============ Categories ============

    private void buildCategories(LinearLayout row) {
        addCategory(row, R.string.cat_all_india, R.drawable.ic_public, R.color.brand_100, R.color.brand_700,
                () -> host.openJobs(JobQuery.allIndia()));
        addCategory(row, R.string.cat_my_state, R.drawable.ic_location_on, R.color.accent_100, R.color.accent_text,
                () -> {
                    String s = Prefs.state(requireContext());
                    if (s.isEmpty()) host.openPreferences();
                    else host.openJobs(JobQuery.state(s));
                });
        addCategory(row, R.string.cat_usa, R.drawable.ic_flight_takeoff, R.color.teal_100, R.color.teal,
                () -> host.openJobs(JobQuery.usa()));
        addCategory(row, R.string.cat_for_me, R.drawable.ic_school, R.color.purple_100, R.color.purple,
                () -> {
                    String q = Prefs.qualification(requireContext());
                    if (q.isEmpty()) host.openPreferences();
                    else host.openJobs(JobQuery.qualification(q));
                });
        addSearchCategory(row, "Railway", "railway", R.drawable.ic_train, R.color.green_100, R.color.green_text);
        addSearchCategory(row, "Bank", "bank", R.drawable.ic_account_balance, R.color.purple_100, R.color.purple);
        addSearchCategory(row, "Police", "police", R.drawable.ic_local_police, R.color.pink_100, R.color.pink);
        addSearchCategory(row, "Defence", "defence", R.drawable.ic_shield, R.color.teal_100, R.color.teal);
        addSearchCategory(row, "Teaching", "teacher", R.drawable.ic_menu_book, R.color.amber_100, R.color.amber);
        addSearchCategory(row, "Nursing", "nurse", R.drawable.ic_health_and_safety, R.color.red_100, R.color.red);
        addSearchCategory(row, "Engineering", "engineer", R.drawable.ic_engineering, R.color.brand_100, R.color.brand_700);
        addSearchCategory(row, "Apprentice", "apprentice", R.drawable.ic_work_history, R.color.green_100, R.color.green_text);
    }

    private void addSearchCategory(LinearLayout row, String label, String keyword, int icon, int bg, int fg) {
        addCategory(row, label, icon, bg, fg, () -> host.openJobs(JobQuery.search(keyword)));
    }

    private void addCategory(LinearLayout row, int label, int icon, int bg, int fg, Runnable action) {
        addCategory(row, getString(label), icon, bg, fg, action);
    }

    private void addCategory(LinearLayout row, String label, int icon, int bg, int fg, Runnable action) {
        View item = getLayoutInflater().inflate(R.layout.item_category, row, false);
        ImageView iv = item.findViewById(R.id.icon);
        iv.setImageResource(icon);
        iv.setImageTintList(android.content.res.ColorStateList.valueOf(ContextCompat.getColor(requireContext(), fg)));
        Ui.tintBackground(iv, bg);
        ((TextView) item.findViewById(R.id.label)).setText(label);
        item.setContentDescription(label);
        item.setOnClickListener(x -> action.run());
        row.addView(item);
    }
}
