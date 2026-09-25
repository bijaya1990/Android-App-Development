package in.naukripatra.app.data;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.List;

import in.naukripatra.app.util.Text;

/** Everything the /post/{id} endpoint returns for one job. */
public final class JobDetail {

    public int id;
    public String title = "";
    public String date = "";
    public String modifiedDate = "";
    public String excerpt = "";
    public String permalink = "";
    public String readingTime = "";

    public String organization = "";
    public String postName = "";
    public String department = "";
    public String vacancy = "";
    public String jobType = "";
    public String qualification = "";
    public String salary = "";
    public String ageLimit = "";
    public String applicationFee = "";
    public String lastDate = "";
    public String location = "";
    public String applicationMode = "";
    public String selectionProcess = "";

    public String applyLink = "";
    public String notificationLink = "";
    public String officialWebsite = "";

    public String html = "";
    public final List<String> categories = new ArrayList<>();

    public static JobDetail fromJson(JSONObject json) {
        JobDetail d = new JobDetail();
        JSONObject post = json.optJSONObject("post");
        if (post != null) {
            d.id = post.optInt("id");
            d.title = Text.html(post.optString("title"));
            d.date = Text.clean(post.optString("date"));
            d.modifiedDate = Text.clean(post.optString("modified_date"));
            d.excerpt = Text.html(post.optString("excerpt"));
            d.permalink = post.optString("permalink");
            d.readingTime = Text.clean(post.optString("reading_time"));
        }
        JSONObject s = json.optJSONObject("summary");
        if (s != null) {
            d.organization = Text.clean(s.optString("organization"));
            d.postName = Text.clean(s.optString("post_name"));
            d.department = Text.clean(s.optString("department"));
            d.vacancy = Text.clean(s.optString("vacancy"));
            d.jobType = Text.clean(s.optString("job_type"));
            d.qualification = Text.clean(s.optString("qualification"));
            d.salary = Text.clean(s.optString("salary"));
            d.ageLimit = Text.clean(s.optString("age_limit"));
            d.applicationFee = Text.clean(s.optString("application_fee"));
            d.lastDate = Text.clean(s.optString("last_date"));
            d.location = Text.clean(s.optString("job_location"));
            d.applicationMode = Text.clean(s.optString("application_mode"));
            d.selectionProcess = Text.clean(s.optString("selection_process"));
        }
        JSONObject links = json.optJSONObject("important_links");
        if (links != null) {
            d.applyLink = Text.url(links.optString("apply_online"));
            d.notificationLink = Text.url(links.optString("notification_pdf"));
            d.officialWebsite = Text.url(links.optString("official_website"));
        }
        JSONObject content = json.optJSONObject("content");
        if (content != null) d.html = content.optString("html");
        JSONObject taxonomy = json.optJSONObject("taxonomy");
        if (taxonomy != null) {
            JSONArray cats = taxonomy.optJSONArray("categories");
            if (cats != null) {
                for (int i = 0; i < cats.length(); i++) {
                    String c = Text.html(cats.optString(i));
                    if (!c.isEmpty()) d.categories.add(c);
                }
            }
        }
        return d;
    }

    /** Snapshot used for bookmarks, so a saved job shows the same card as in lists. */
    public Job toJob() {
        return new Job(id, title, organization, vacancy, qualification, salary, lastDate,
                location, date, excerpt, permalink);
    }

    /** Host of the official site, shown as the source of this post. */
    public String sourceHost() {
        String url = !officialWebsite.isEmpty() ? officialWebsite : applyLink;
        return Text.host(url);
    }

    public String sourceUrl() {
        return !officialWebsite.isEmpty() ? officialWebsite : applyLink;
    }
}
