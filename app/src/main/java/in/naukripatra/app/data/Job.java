package in.naukripatra.app.data;

import androidx.annotation.NonNull;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

import in.naukripatra.app.util.Text;

/** One job in a list. Search results only carry title/date/excerpt, so most fields may be empty. */
public final class Job {

    public final int id;
    public final String title;
    public final String organization;
    public final String vacancy;
    public final String qualification;
    public final String salary;
    public final String lastDate;
    public final String location;
    public final String date;
    public final String excerpt;
    public final String link;
    /** Thumbnail from the API (1200x630 banner); may be empty. */
    public final String image;

    public Job(int id, String title, String organization, String vacancy, String qualification,
               String salary, String lastDate, String location, String date, String excerpt, String link) {
        this(id, title, organization, vacancy, qualification, salary, lastDate, location, date, excerpt, link, "");
    }

    public Job(int id, String title, String organization, String vacancy, String qualification,
               String salary, String lastDate, String location, String date, String excerpt, String link,
               String image) {
        this.id = id;
        this.title = title;
        this.organization = organization;
        this.vacancy = vacancy;
        this.qualification = qualification;
        this.salary = salary;
        this.lastDate = lastDate;
        this.location = location;
        this.date = date;
        this.excerpt = excerpt;
        this.link = link;
        this.image = image == null ? "" : image;
    }

    public static Job fromJson(JSONObject o) {
        return new Job(
                o.optInt("id"),
                Text.html(o.optString("title")),
                Text.clean(o.optString("organization")),
                Text.clean(o.optString("vacancy")),
                Text.clean(o.optString("qualification")),
                Text.clean(o.optString("salary")),
                Text.clean(o.optString("last_date")),
                Text.clean(o.optString("job_location")),
                Text.clean(o.optString("date")),
                Text.html(o.optString("excerpt")),
                o.optString("link"),
                Text.url(o.optString("image", o.optString("featured_image"))));
    }

    public static List<Job> listFrom(JSONArray array) {
        List<Job> list = new ArrayList<>();
        if (array == null) return list;
        for (int i = 0; i < array.length(); i++) {
            JSONObject o = array.optJSONObject(i);
            if (o != null && o.optInt("id") > 0) list.add(fromJson(o));
        }
        return list;
    }

    public JSONObject toJson() {
        JSONObject o = new JSONObject();
        try {
            o.put("id", id).put("title", title).put("organization", organization)
                    .put("vacancy", vacancy).put("qualification", qualification)
                    .put("salary", salary).put("last_date", lastDate)
                    .put("job_location", location).put("date", date)
                    .put("excerpt", excerpt).put("link", link).put("image", image);
        } catch (JSONException ignored) {
        }
        return o;
    }

    /** Short name shown in the coloured logo box, e.g. "SSC", "RRB", "OP". */
    @NonNull
    public String initials() {
        return Text.initials(organization.isEmpty() ? title : organization);
    }

    /** A page of jobs plus paging info. */
    public static final class Page {
        public final List<Job> jobs;
        public final int total;
        public final boolean hasMore;

        public Page(List<Job> jobs, int total, boolean hasMore) {
            this.jobs = jobs;
            this.total = total;
            this.hasMore = hasMore;
        }

        public static Page fromJson(JSONObject json, int page) {
            List<Job> jobs = listFrom(json.optJSONArray("data"));
            int total = json.optInt("total_posts", jobs.size());
            boolean more = json.has("has_more")
                    ? json.optBoolean("has_more")
                    : page < json.optInt("total_pages", page);
            return new Page(jobs, total, more && !jobs.isEmpty());
        }
    }
}
