package in.naukripatra.app.ui.jobs;

import android.content.Context;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.KeyEvent;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.view.inputmethod.EditorInfo;
import android.view.inputmethod.InputMethodManager;
import android.widget.ArrayAdapter;
import android.widget.EditText;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.google.android.material.chip.ChipGroup;
import com.google.android.material.textfield.MaterialAutoCompleteTextView;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;

import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.Api;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.JobQuery;
import in.naukripatra.app.data.Prefs;
import in.naukripatra.app.data.SavedJobs;
import in.naukripatra.app.ui.common.JobListAdapter;
import in.naukripatra.app.ui.common.StateView;
import in.naukripatra.app.ui.detail.JobDetailFragment;
import in.naukripatra.app.ui.main.Host;
import in.naukripatra.app.util.Text;
import in.naukripatra.app.util.Ui;
import okhttp3.Call;

/**
 * All job lists: Latest, All India, USA, For Me, a state, or a search.
 * The state dropdown sits on top of every list. On tablets the selected job opens
 * in a detail pane next to the list.
 */
public class JobsFragment extends Fragment implements SavedJobs.Listener {

    private static final int PAGE_SIZE = 20;
    private static final String KEY_TYPE = "q_type";
    private static final String KEY_VALUE = "q_value";

    private Host host;
    private JobQuery query = JobQuery.latest();
    /** Last non-search list, restored when the search box is cleared. */
    private JobQuery browseQuery = JobQuery.latest();

    private EditText search;
    private View clearSearch;
    private MaterialAutoCompleteTextView stateDropdown;
    private ChipGroup chips;
    private TextView count;
    private SwipeRefreshLayout refresh;
    private RecyclerView list;
    private JobListAdapter adapter;
    private StateView stateView;
    @Nullable
    private View detailPane;

    private int page;
    private boolean loading;
    private boolean hasMore;
    private int total;
    private int requestId;
    private Call call;
    private boolean updatingUi;
    private boolean focusSearchWhenReady;
    private int selectedJobId;

    private final Handler handler = new Handler(Looper.getMainLooper());
    private final Runnable debouncedSearch = this::submitSearch;

    @Override
    public void onAttach(@NonNull Context context) {
        super.onAttach(context);
        host = (Host) context;
    }

    @Override
    public void onCreate(@Nullable Bundle state) {
        super.onCreate(state);
        if (state != null) {
            String type = state.getString(KEY_TYPE);
            String value = state.getString(KEY_VALUE, "");
            if (type != null) query = fromSaved(JobQuery.Type.valueOf(type), value);
            if (query.type != JobQuery.Type.SEARCH) browseQuery = query;
        }
    }

    @Override
    public void onSaveInstanceState(@NonNull Bundle out) {
        super.onSaveInstanceState(out);
        out.putString(KEY_TYPE, query.type.name());
        out.putString(KEY_VALUE, query.value);
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle state) {
        return inflater.inflate(R.layout.fragment_jobs, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        Ui.padForInsets(v.findViewById(R.id.topBar), Ui.TOP);
        search = v.findViewById(R.id.search);
        clearSearch = v.findViewById(R.id.clearSearch);
        stateDropdown = v.findViewById(R.id.stateDropdown);
        chips = v.findViewById(R.id.chips);
        count = v.findViewById(R.id.count);
        refresh = v.findViewById(R.id.refresh);
        list = v.findViewById(R.id.list);
        stateView = new StateView(v.findViewById(R.id.state));
        detailPane = v.findViewById(R.id.detailPane);

        refresh.setColorSchemeResources(R.color.brand_700, R.color.cyan);
        refresh.setProgressViewOffset(false, Ui.dp(requireContext(), 30), Ui.dp(requireContext(), 80));
        refresh.setOnRefreshListener(() -> reload(true));

        setupList();
        setupSearch();
        setupStateDropdown();
        setupChips();

        SavedJobs.addListener(this);
        syncControls();
        reload(false);
        if (focusSearchWhenReady) focusSearch();
    }

    @Override
    public void onDestroyView() {
        SavedJobs.removeListener(this);
        handler.removeCallbacks(debouncedSearch);
        if (call != null) call.cancel();
        adapter.destroyAds();
        super.onDestroyView();
    }

    @Override
    public void onSavedJobsChanged() {
        if (adapter != null) adapter.refreshBookmarks();
    }

    // ============ Public API (from Home / MainActivity) ============

    public void show(JobQuery q) {
        query = q;
        if (q.type != JobQuery.Type.SEARCH) browseQuery = q;
        if (getView() == null) return;
        syncControls();
        reload(false);
        list.scrollToPosition(0);
    }

    public void focusSearch() {
        if (search == null) {
            focusSearchWhenReady = true;
            return;
        }
        focusSearchWhenReady = false;
        search.requestFocus();
        search.post(() -> {
            InputMethodManager imm = (InputMethodManager) requireContext().getSystemService(Context.INPUT_METHOD_SERVICE);
            if (imm != null) imm.showSoftInput(search, InputMethodManager.SHOW_IMPLICIT);
        });
    }

    // ============ Setup ============

    private void setupList() {
        int columns = getResources().getInteger(R.integer.job_columns);
        // The list pane on tablets is narrow, so it stays one column there.
        if (detailPane != null) columns = 1;
        GridLayoutManager lm = new GridLayoutManager(requireContext(), columns);
        adapter = new JobListAdapter(this::openJob, true);
        lm.setSpanSizeLookup(new GridLayoutManager.SpanSizeLookup() {
            @Override
            public int getSpanSize(int position) {
                return adapter.isFullSpan(position) ? lm.getSpanCount() : 1;
            }
        });
        list.setLayoutManager(lm);
        list.setAdapter(adapter);
        list.setHasFixedSize(true);
        list.setItemViewCacheSize(8);
        list.addOnScrollListener(new RecyclerView.OnScrollListener() {
            @Override
            public void onScrolled(@NonNull RecyclerView rv, int dx, int dy) {
                if (dy <= 0 || loading || !hasMore) return;
                if (lm.findLastVisibleItemPosition() >= adapter.getItemCount() - 5) loadNext();
            }
        });
    }

    private void setupSearch() {
        search.setOnEditorActionListener((tv, actionId, event) -> {
            boolean enter = event != null && event.getKeyCode() == KeyEvent.KEYCODE_ENTER
                    && event.getAction() == KeyEvent.ACTION_DOWN;
            if (actionId == EditorInfo.IME_ACTION_SEARCH || enter) {
                handler.removeCallbacks(debouncedSearch);
                submitSearch();
                hideKeyboard();
                return true;
            }
            return false;
        });
        search.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int c, int a) {
            }

            @Override
            public void onTextChanged(CharSequence s, int start, int b, int c) {
            }

            @Override
            public void afterTextChanged(Editable s) {
                clearSearch.setVisibility(s.length() > 0 ? View.VISIBLE : View.GONE);
                if (updatingUi) return;
                handler.removeCallbacks(debouncedSearch);
                String text = s.toString().trim();
                if (text.isEmpty()) {
                    if (query.type == JobQuery.Type.SEARCH) show(browseQuery);
                } else if (text.length() >= 3) {
                    handler.postDelayed(debouncedSearch, 600);
                }
            }
        });
        clearSearch.setOnClickListener(x -> {
            search.setText("");
            hideKeyboard();
        });
    }

    private void submitSearch() {
        String text = search.getText().toString().trim();
        if (text.isEmpty()) return;
        JobQuery q = JobQuery.search(text);
        if (!q.equals(query)) show(q);
    }

    private void setupStateDropdown() {
        List<String> items = new ArrayList<>();
        items.add(getString(R.string.all_states));
        items.addAll(Arrays.asList(Prefs.STATES));
        stateDropdown.setAdapter(new ArrayAdapter<>(requireContext(), R.layout.item_dropdown, items));
        stateDropdown.setOnItemClickListener((parent, view, position, id) -> {
            if (updatingUi) return;
            if (position == 0) show(JobQuery.latest());
            else show(JobQuery.state(items.get(position)));
        });
    }

    private void setupChips() {
        chips.setOnCheckedStateChangeListener((group, checked) -> {
            if (updatingUi || checked.isEmpty()) return;
            int id = checked.get(0);
            if (id == R.id.chipLatest) {
                show(JobQuery.latest());
            } else if (id == R.id.chipAllIndia) {
                show(JobQuery.allIndia());
            } else if (id == R.id.chipUsa) {
                show(JobQuery.usa());
            } else if (id == R.id.chipForMe) {
                String q = Prefs.qualification(requireContext());
                if (q.isEmpty()) {
                    syncControls();
                    host.openPreferences();
                } else {
                    show(JobQuery.qualification(q));
                }
            }
        });
    }

    /** Makes the search box, dropdown and chips reflect {@link #query}. */
    private void syncControls() {
        updatingUi = true;
        String searchText = query.type == JobQuery.Type.SEARCH ? query.value : "";
        if (!search.getText().toString().trim().equals(searchText)) search.setText(searchText);
        stateDropdown.setText(query.type == JobQuery.Type.STATE ? query.value : getString(R.string.all_states), false);

        String qualification = Prefs.qualification(requireContext());
        ((TextView) chips.findViewById(R.id.chipForMe)).setText(
                qualification.isEmpty() ? getString(R.string.cat_for_me) : qualification);

        switch (query.type) {
            case LATEST:
                chips.check(R.id.chipLatest);
                break;
            case ALL_INDIA:
                chips.check(R.id.chipAllIndia);
                break;
            case USA:
                chips.check(R.id.chipUsa);
                break;
            case QUALIFICATION:
                chips.check(R.id.chipForMe);
                break;
            default:
                chips.clearCheck();
        }
        updatingUi = false;
    }

    // ============ Loading ============

    private void reload(boolean fresh) {
        if (call != null) call.cancel();
        page = 0;
        hasMore = true;
        loading = false;
        total = 0;
        stateView.hide();
        count.setText("");
        if (!fresh) adapter.showSkeleton(6);
        load(1, fresh);
    }

    private void loadNext() {
        adapter.setLoadingMore(true);
        load(page + 1, false);
    }

    private void load(int pageToLoad, boolean fresh) {
        loading = true;
        final int id = ++requestId;
        final JobQuery q = query;
        call = Api.get(Api.jobs(q, pageToLoad, PAGE_SIZE), fresh, json -> Job.Page.fromJson(json, pageToLoad),
                new Api.Result<Job.Page>() {
                    @Override
                    public void onSuccess(Job.Page result) {
                        if (!isAdded() || id != requestId) return;
                        loading = false;
                        refresh.setRefreshing(false);
                        page = pageToLoad;
                        hasMore = result.hasMore;
                        if (pageToLoad == 1) {
                            total = result.total;
                            adapter.setJobs(result.jobs);
                            updateCount();
                            if (result.jobs.isEmpty()) {
                                stateView.showEmpty(R.drawable.ic_search_off, getString(R.string.no_jobs_title),
                                        getString(R.string.no_jobs_body));
                            }
                        } else {
                            adapter.addJobs(result.jobs);
                        }
                    }

                    @Override
                    public void onError(@NonNull String message) {
                        if (!isAdded() || id != requestId) return;
                        loading = false;
                        refresh.setRefreshing(false);
                        if (pageToLoad == 1) {
                            adapter.setJobs(new ArrayList<>());
                            count.setText("");
                            stateView.showError(message, () -> reload(true));
                        } else {
                            // Keep what is shown; the next scroll tries again.
                            adapter.setLoadingMore(false);
                        }
                    }
                });
    }

    private void updateCount() {
        String n = Text.number(total);
        String text;
        switch (query.type) {
            case STATE:
                text = getString(R.string.jobs_found_in, n, query.value);
                break;
            case SEARCH:
            case QUALIFICATION:
                text = getString(R.string.jobs_found_for, n, query.value);
                break;
            case USA:
                text = getString(R.string.jobs_found_in, n, "USA");
                break;
            case ALL_INDIA:
                text = getString(R.string.jobs_found_for, n, getString(R.string.cat_all_india));
                break;
            default:
                text = getString(R.string.jobs_found, n);
        }
        count.setText(text);
    }

    // ============ Opening a job ============

    private void openJob(Job job) {
        if (detailPane == null) {
            host.openJob(job);
            return;
        }
        if (job.id == selectedJobId) return;
        selectedJobId = job.id;
        View placeholder = getView() == null ? null : getView().findViewById(R.id.detailPlaceholder);
        if (placeholder != null) placeholder.setVisibility(View.GONE);
        Ads.onJobOpened(requireActivity());
        getChildFragmentManager().beginTransaction()
                .setReorderingAllowed(true)
                .setCustomAnimations(android.R.anim.fade_in, android.R.anim.fade_out)
                .replace(R.id.detailPane, JobDetailFragment.newInstance(job.id, null, true))
                .commit();
    }

    private void hideKeyboard() {
        InputMethodManager imm = (InputMethodManager) requireContext().getSystemService(Context.INPUT_METHOD_SERVICE);
        if (imm != null) imm.hideSoftInputFromWindow(search.getWindowToken(), 0);
        search.clearFocus();
    }

    private static JobQuery fromSaved(JobQuery.Type type, String value) {
        switch (type) {
            case ALL_INDIA:
                return JobQuery.allIndia();
            case USA:
                return JobQuery.usa();
            case STATE:
                return JobQuery.state(value);
            case QUALIFICATION:
                return JobQuery.qualification(value);
            case SEARCH:
                return JobQuery.search(value);
            default:
                return JobQuery.latest();
        }
    }
}
