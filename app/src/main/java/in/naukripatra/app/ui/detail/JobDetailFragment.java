package in.naukripatra.app.ui.detail;

import android.content.Context;
import android.content.res.ColorStateList;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.FrameLayout;
import android.widget.GridLayout;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.RecyclerView;

import com.google.android.gms.ads.nativead.NativeAd;
import com.google.android.material.progressindicator.LinearProgressIndicator;

import org.json.JSONArray;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;

import in.naukripatra.app.R;
import in.naukripatra.app.ads.Ads;
import in.naukripatra.app.data.Api;
import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.JobDetail;
import in.naukripatra.app.data.Prefs;
import in.naukripatra.app.data.SavedJobs;
import in.naukripatra.app.ui.common.JobViews;
import in.naukripatra.app.ui.common.StateView;
import in.naukripatra.app.util.Deadline;
import in.naukripatra.app.util.Links;
import in.naukripatra.app.util.Text;
import in.naukripatra.app.util.Ui;
import okhttp3.Call;

/** Full job page. Used by JobDetailActivity on phones and in the tablet detail pane. */
public class JobDetailFragment extends Fragment {

    private static final String ARG_ID = "id";
    private static final String ARG_SLUG = "slug";
    private static final String ARG_PANE = "pane";

    private int postId;
    private String slug;
    private boolean inPane;

    private JobDetail detail;
    private View root;
    private View cover;
    private StateView stateView;
    private NativeAd nativeAd;
    private final List<Call> calls = new ArrayList<>();

    public static JobDetailFragment newInstance(int id, @Nullable String slug, boolean inPane) {
        JobDetailFragment f = new JobDetailFragment();
        Bundle b = new Bundle();
        b.putInt(ARG_ID, id);
        b.putString(ARG_SLUG, slug);
        b.putBoolean(ARG_PANE, inPane);
        f.setArguments(b);
        return f;
    }

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle state) {
        return inflater.inflate(R.layout.fragment_job_detail, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        root = v;
        Bundle args = requireArguments();
        postId = args.getInt(ARG_ID);
        slug = args.getString(ARG_SLUG);
        inPane = args.getBoolean(ARG_PANE);

        cover = v.findViewById(R.id.cover);
        stateView = new StateView(v.findViewById(R.id.state));

        if (inPane) {
            v.findViewById(R.id.btnBack).setVisibility(View.GONE);
            v.findViewById(R.id.coverBack).setVisibility(View.GONE);
        } else {
            Ui.padForInsets(v.findViewById(R.id.header), Ui.TOP);
            Ui.marginForInsets(v.findViewById(R.id.coverBack), Ui.TOP);
            Ui.padForInsets(v.findViewById(R.id.actionBar), Ui.BOTTOM);
            View.OnClickListener back = x -> requireActivity().getOnBackPressedDispatcher().onBackPressed();
            v.findViewById(R.id.btnBack).setOnClickListener(back);
            v.findViewById(R.id.coverBack).setOnClickListener(back);
        }
        ((TextView) v.findViewById(R.id.disclaimer)).setText(disclaimerText());

        start();
    }

    @Override
    public void onDestroyView() {
        for (Call c : calls) c.cancel();
        calls.clear();
        if (nativeAd != null) {
            nativeAd.destroy();
            nativeAd = null;
        }
        super.onDestroyView();
    }

    private CharSequence disclaimerText() {
        android.text.SpannableStringBuilder sb = new android.text.SpannableStringBuilder();
        sb.append(getString(R.string.disclaimer_title));
        sb.setSpan(new android.text.style.StyleSpan(android.graphics.Typeface.BOLD), 0, sb.length(), 0);
        sb.append(' ').append(getString(R.string.disclaimer_short));
        return sb;
    }

    // ============ Loading ============

    private void start() {
        cover.setVisibility(View.VISIBLE);
        root.findViewById(R.id.progress).setVisibility(View.VISIBLE);
        stateView.hide();
        if (postId > 0) {
            loadDetail();
        } else if (!Text.isEmpty(slug)) {
            calls.add(Api.getRaw(Api.postIdBySlug(slug), new Api.Result<String>() {
                @Override
                public void onSuccess(String body) {
                    if (!isAdded()) return;
                    try {
                        JSONArray array = new JSONArray(body);
                        if (array.length() > 0) {
                            postId = array.getJSONObject(0).getInt("id");
                            loadDetail();
                            return;
                        }
                    } catch (Exception ignored) {
                    }
                    showError(getString(R.string.job_not_found), false);
                }

                @Override
                public void onError(@NonNull String message) {
                    if (isAdded()) showError(message, true);
                }
            }));
        } else {
            showError(getString(R.string.job_not_found), false);
        }
    }

    private void loadDetail() {
        calls.add(Api.get(Api.post(postId), JobDetail::fromJson, new Api.Result<JobDetail>() {
            @Override
            public void onSuccess(JobDetail d) {
                if (!isAdded()) return;
                if (d.id == 0) {
                    showError(getString(R.string.job_not_found), false);
                    return;
                }
                detail = d;
                bind();
                cover.animate().alpha(0f).setDuration(180).withEndAction(() -> {
                    cover.setVisibility(View.GONE);
                    cover.setAlpha(1f);
                });
                loadRelated();
                loadAd();
            }

            @Override
            public void onError(@NonNull String message) {
                if (isAdded()) showError(message, true);
            }
        }));
    }

    private void showError(String message, boolean canRetry) {
        root.findViewById(R.id.progress).setVisibility(View.GONE);
        if (canRetry) {
            stateView.showError(message, this::start);
        } else {
            stateView.showEmpty(R.drawable.ic_search_off, message, "");
        }
    }

    // ============ Binding ============

    private void bind() {
        JobDetail d = detail;
        Context c = requireContext();
        View v = root;

        ((TextView) v.findViewById(R.id.title)).setText(d.title);
        TextView orgLogo = v.findViewById(R.id.orgLogo);
        orgLogo.setText(Text.initials(d.organization.isEmpty() ? d.title : d.organization));
        StringBuilder orgLine = new StringBuilder();
        if (!d.organization.isEmpty()) orgLine.append(d.organization).append(" · ");
        orgLine.append(getString(R.string.posted_on, d.date));
        ((TextView) v.findViewById(R.id.orgLine)).setText(orgLine);

        bindTags(v.findViewById(R.id.tags));
        bindDeadline();
        bindFacts(v.findViewById(R.id.facts));
        bindMatch();

        // Overview
        TextView overview = v.findViewById(R.id.overview);
        String summary = !d.excerpt.isEmpty() ? d.excerpt : d.postName;
        v.findViewById(R.id.overviewCard).setVisibility(summary.isEmpty() ? View.GONE : View.VISIBLE);
        overview.setText(summary);
        TextView source = v.findViewById(R.id.source);
        String host = d.sourceHost();
        if (!host.isEmpty()) {
            android.text.SpannableStringBuilder sb = new android.text.SpannableStringBuilder(getString(R.string.source_label));
            int start = sb.length();
            sb.append(host);
            sb.setSpan(new android.text.style.ForegroundColorSpan(ContextCompat.getColor(c, R.color.link)), start, sb.length(), 0);
            sb.setSpan(new android.text.style.UnderlineSpan(), start, sb.length(), 0);
            sb.append(' ').append(getString(R.string.source_official));
            source.setText(sb);
            Ui.startIcon(source, R.drawable.ic_verified, 16, ContextCompat.getColor(c, R.color.green));
            source.setVisibility(View.VISIBLE);
            source.setOnClickListener(x -> Links.open(c, d.sourceUrl()));
        } else {
            source.setVisibility(View.GONE);
        }

        bindInfoRows(v.findViewById(R.id.infoRows));
        bindLinks(v.findViewById(R.id.linkRows));

        View readFull = v.findViewById(R.id.btnReadFull);
        readFull.setVisibility(d.html.trim().isEmpty() ? View.GONE : View.VISIBLE);
        readFull.setOnClickListener(x -> openArticle());

        // Bookmark
        ImageButton save = v.findViewById(R.id.btnSave);
        bindSave(save);
        save.setOnClickListener(x -> {
            boolean saved = SavedJobs.toggle(c, d.toJob());
            bindSave(save);
            Toast.makeText(c, saved ? R.string.saved_toast : R.string.removed_toast, Toast.LENGTH_SHORT).show();
        });

        View.OnClickListener share = x -> Links.share(c, d.title,
                d.permalink.isEmpty() ? Api.SITE : d.permalink);
        v.findViewById(R.id.btnShareTop).setOnClickListener(share);
        v.findViewById(R.id.btnShare).setOnClickListener(share);

        bindActionBar();
    }

    private void bindSave(ImageButton save) {
        boolean saved = SavedJobs.isSaved(requireContext(), detail.id);
        save.setImageResource(saved ? R.drawable.ic_bookmark : R.drawable.ic_bookmark_border);
        save.setContentDescription(getString(saved ? R.string.saved : R.string.save));
    }

    private void bindTags(ViewGroup tags) {
        tags.removeAllViews();
        List<String> values = new ArrayList<>();
        for (String cat : detail.categories) {
            if (!cat.equalsIgnoreCase("Latest Jobs") && values.size() < 2) values.add(cat);
        }
        if (!detail.jobType.isEmpty() && detail.jobType.length() <= 28) values.add(detail.jobType);
        if (!detail.applicationMode.isEmpty() && detail.applicationMode.length() <= 22) values.add(detail.applicationMode);
        int days = Deadline.daysLeft(detail.lastDate);
        for (String value : values) tags.addView(tag(value, false));
        if (days != Deadline.UNKNOWN && days >= 0) tags.addView(tag("● Apply Open", true));
        tags.setVisibility(tags.getChildCount() == 0 ? View.GONE : View.VISIBLE);
    }

    private TextView tag(String text, boolean open) {
        TextView t = (TextView) getLayoutInflater().inflate(R.layout.item_tag, null, false);
        t.setText(text);
        if (open) t.setBackgroundTintList(ColorStateList.valueOf(0xFF059669));
        return t;
    }

    private void bindDeadline() {
        View card = root.findViewById(R.id.deadlineCard);
        JobDetail d = detail;
        if (d.lastDate.isEmpty()) {
            card.setVisibility(View.GONE);
            return;
        }
        card.setVisibility(View.VISIBLE);
        Date date = Deadline.parse(d.lastDate);
        TextView day = root.findViewById(R.id.dateDay);
        TextView month = root.findViewById(R.id.dateMonth);
        TextView full = root.findViewById(R.id.dateFull);
        LinearProgressIndicator progress = root.findViewById(R.id.dateProgress);
        TextView badge = root.findViewById(R.id.badge);
        View box = root.findViewById(R.id.dateBox);
        Deadline.bind(badge, d.lastDate);
        if (date == null) {
            box.setVisibility(View.GONE);
            progress.setVisibility(View.GONE);
            full.setText(d.lastDate);
            return;
        }
        box.setVisibility(View.VISIBLE);
        day.setText(new SimpleDateFormat("dd", Locale.ENGLISH).format(date));
        month.setText(new SimpleDateFormat("MMM", Locale.ENGLISH).format(date).toUpperCase(Locale.ENGLISH));
        full.setText(Deadline.weekday(d.lastDate) + ", " + Deadline.pretty(d.lastDate));

        // How much of the application window has passed since the post went up.
        Date posted = Deadline.parse(d.date);
        if (posted != null && date.after(posted)) {
            long span = date.getTime() - posted.getTime();
            long gone = System.currentTimeMillis() - posted.getTime();
            int pct = (int) Math.max(4, Math.min(100, gone * 100 / span));
            progress.setProgressCompat(pct, false);
            progress.setIndicatorColor(ContextCompat.getColor(requireContext(),
                    pct >= 80 ? R.color.red : pct >= 50 ? R.color.accent : R.color.green));
            progress.setVisibility(View.VISIBLE);
        } else {
            progress.setVisibility(View.GONE);
        }
    }

    private void bindFacts(GridLayout grid) {
        grid.removeAllViews();
        JobDetail d = detail;
        addFact(grid, R.string.total_posts, JobViews.postsLabel(d.vacancy), R.drawable.ic_groups, R.color.brand_100, R.color.brand_700);
        addFact(grid, R.string.salary, d.salary, R.drawable.ic_payments, R.color.green_100, R.color.green);
        addFact(grid, R.string.age_limit, d.ageLimit, R.drawable.ic_cake, R.color.purple_100, R.color.purple);
        addFact(grid, R.string.qualification, d.qualification, R.drawable.ic_school, R.color.accent_100, R.color.accent);
        grid.setVisibility(grid.getChildCount() == 0 ? View.GONE : View.VISIBLE);
    }

    private void addFact(GridLayout grid, int label, String value, int icon, int bg, int fg) {
        if (Text.isEmpty(value)) return;
        View tile = getLayoutInflater().inflate(R.layout.item_fact, grid, false);
        Ui.tintBackground(tile, bg);
        ImageView iv = tile.findViewById(R.id.icon);
        iv.setImageResource(icon);
        Ui.tintBackground(iv, fg);
        ((TextView) tile.findViewById(R.id.label)).setText(label);
        ((TextView) tile.findViewById(R.id.value)).setText(value);
        GridLayout.LayoutParams lp = new GridLayout.LayoutParams(
                GridLayout.spec(GridLayout.UNDEFINED), GridLayout.spec(GridLayout.UNDEFINED, 1f));
        lp.width = 0;
        int gap = Ui.dp(requireContext(), 5);
        lp.setMargins(gap, gap, gap, gap);
        tile.setLayoutParams(lp);
        grid.addView(tile);
    }

    private void bindMatch() {
        String q = Prefs.qualification(requireContext());
        View banner = root.findViewById(R.id.matchBanner);
        if (Prefs.matchesQualification(q, detail.qualification)) {
            ((TextView) root.findViewById(R.id.matchSub)).setText(getString(R.string.based_on, q));
            banner.setVisibility(View.VISIBLE);
        } else {
            banner.setVisibility(View.GONE);
        }
    }

    private void bindInfoRows(LinearLayout rows) {
        rows.removeAllViews();
        JobDetail d = detail;
        addRow(rows, R.string.organization, d.organization, R.drawable.ic_apartment, R.color.brand_100, R.color.brand_700);
        addRow(rows, R.string.post_name, d.postName, R.drawable.ic_badge, R.color.purple_100, R.color.purple);
        addRow(rows, R.string.department, d.department, R.drawable.ic_account_balance, R.color.teal_100, R.color.teal);
        addRow(rows, R.string.job_type, d.jobType, R.drawable.ic_work_history, R.color.amber_100, R.color.amber);
        addRow(rows, R.string.job_location, d.location, R.drawable.ic_location_on, R.color.accent_100, R.color.accent_text);
        addRow(rows, R.string.application_mode, d.applicationMode, R.drawable.ic_how_to_reg, R.color.green_100, R.color.green_text);
        addRow(rows, R.string.application_fee, d.applicationFee, R.drawable.ic_receipt_long, R.color.pink_100, R.color.pink);
        addRow(rows, R.string.selection_process, d.selectionProcess, R.drawable.ic_fact_check, R.color.brand_100, R.color.brand_700);
        root.findViewById(R.id.infoCard).setVisibility(rows.getChildCount() == 0 ? View.GONE : View.VISIBLE);
    }

    private void addRow(LinearLayout rows, int label, String value, int icon, int bg, int fg) {
        if (Text.isEmpty(value)) return;
        View row = getLayoutInflater().inflate(R.layout.item_info_row, rows, false);
        ImageView iv = row.findViewById(R.id.icon);
        iv.setImageResource(icon);
        Ui.tintBackground(iv, bg);
        iv.setImageTintList(ColorStateList.valueOf(ContextCompat.getColor(requireContext(), fg)));
        ((TextView) row.findViewById(R.id.label)).setText(label);
        ((TextView) row.findViewById(R.id.value)).setText(value);
        rows.addView(row);
    }

    private void bindLinks(LinearLayout rows) {
        rows.removeAllViews();
        JobDetail d = detail;
        addLink(rows, R.string.apply_online, d.applyLink, R.drawable.ic_open_in_new, true);
        addLink(rows, R.string.notification_pdf, d.notificationLink, R.drawable.ic_picture_as_pdf, false);
        if (!d.officialWebsite.equals(d.applyLink)) {
            addLink(rows, R.string.official_website, d.officialWebsite, R.drawable.ic_language, false);
        }
        root.findViewById(R.id.linksCard).setVisibility(rows.getChildCount() == 0 ? View.GONE : View.VISIBLE);
    }

    private void addLink(LinearLayout rows, int label, String url, int icon, boolean primary) {
        if (Text.isEmpty(url)) return;
        Context c = requireContext();
        View row = getLayoutInflater().inflate(R.layout.item_link, rows, false);
        int bg;
        int fg;
        if (primary) {
            bg = R.color.brand_700;
            fg = R.color.on_brand;
        } else if (icon == R.drawable.ic_picture_as_pdf) {
            bg = R.color.red_100;
            fg = R.color.red;
        } else {
            bg = R.color.surface_alt;
            fg = R.color.text;
        }
        Ui.tintBackground(row, bg);
        int color = ContextCompat.getColor(c, fg);
        ImageView iv = row.findViewById(R.id.icon);
        iv.setImageResource(icon == R.drawable.ic_open_in_new ? R.drawable.ic_article : icon);
        iv.setImageTintList(ColorStateList.valueOf(color));
        ImageView trail = row.findViewById(R.id.trail);
        trail.setImageResource(icon == R.drawable.ic_picture_as_pdf ? R.drawable.ic_open_in_new : R.drawable.ic_open_in_new);
        trail.setImageTintList(ColorStateList.valueOf(color));
        TextView tv = row.findViewById(R.id.label);
        tv.setText(label);
        tv.setTextColor(color);
        row.setOnClickListener(x -> Links.open(c, url));
        rows.addView(row);
    }

    private void bindActionBar() {
        JobDetail d = detail;
        View bar = root.findViewById(R.id.actionBar);
        bar.setVisibility(View.VISIBLE);

        View pdf = root.findViewById(R.id.btnPdf);
        pdf.setVisibility(d.notificationLink.isEmpty() ? View.GONE : View.VISIBLE);
        pdf.setOnClickListener(x -> Links.open(requireContext(), d.notificationLink));

        TextView apply = root.findViewById(R.id.btnApply);
        String target;
        int label;
        if (!d.applyLink.isEmpty()) {
            target = d.applyLink;
            label = R.string.apply_now;
        } else if (!d.officialWebsite.isEmpty()) {
            target = d.officialWebsite;
            label = R.string.visit_website;
        } else {
            target = null;
            label = R.string.read_details;
        }
        apply.setText(label);
        Ui.startIcon(apply, target == null ? R.drawable.ic_article : R.drawable.ic_open_in_new, 18,
                ContextCompat.getColor(requireContext(), R.color.on_brand));
        apply.setOnClickListener(x -> {
            if (target != null) Links.open(requireContext(), target);
            else openArticle();
        });
    }

    private void openArticle() {
        startActivity(ArticleActivity.intent(requireContext(), detail.id, detail.title));
    }

    // ============ Related + ad ============

    private void loadRelated() {
        calls.add(Api.get(Api.related(detail.id), json -> Job.listFrom(json.optJSONArray("data")),
                new Api.Result<List<Job>>() {
                    @Override
                    public void onSuccess(List<Job> jobs) {
                        if (!isAdded() || jobs.isEmpty()) return;
                        RecyclerView list = root.findViewById(R.id.similar);
                        list.setAdapter(new RelatedAdapter(jobs, job -> {
                            if (inPane) {
                                getParentFragmentManager().beginTransaction()
                                        .replace(R.id.detailPane, newInstance(job.id, null, true))
                                        .commit();
                            } else {
                                Ads.onJobOpened(requireActivity());
                                startActivity(JobDetailActivity.forId(requireContext(), job.id));
                            }
                        }));
                        list.setVisibility(View.VISIBLE);
                        root.findViewById(R.id.similarTitle).setVisibility(View.VISIBLE);
                    }

                    @Override
                    public void onError(@NonNull String message) {
                    }
                }));
    }

    private void loadAd() {
        FrameLayout container = root.findViewById(R.id.adContainer);
        Ads.loadNative(requireContext(), ad -> {
            if (ad == null) return;
            if (!isAdded() || getView() == null) {
                ad.destroy();
                return;
            }
            nativeAd = ad;
            Ads.bindNative(container, ad, true);
            root.findViewById(R.id.adCaption).setVisibility(View.VISIBLE);
        });
    }
}
