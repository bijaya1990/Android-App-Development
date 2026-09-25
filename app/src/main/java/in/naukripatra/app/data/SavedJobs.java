package in.naukripatra.app.data;

import android.content.Context;
import android.content.SharedPreferences;

import org.json.JSONArray;

import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.CopyOnWriteArrayList;

/** Bookmarked jobs, stored on the device only. */
public final class SavedJobs {

    public interface Listener {
        void onSavedJobsChanged();
    }

    private static final String FILE = "saved_jobs";
    private static final String KEY = "jobs";
    private static final List<Listener> LISTENERS = new CopyOnWriteArrayList<>();
    private static Map<Integer, Job> cache;

    private SavedJobs() {
    }

    private static SharedPreferences p(Context c) {
        return c.getApplicationContext().getSharedPreferences(FILE, Context.MODE_PRIVATE);
    }

    private static synchronized Map<Integer, Job> load(Context c) {
        if (cache != null) return cache;
        cache = new LinkedHashMap<>();
        try {
            JSONArray array = new JSONArray(p(c).getString(KEY, "[]"));
            for (Job job : Job.listFrom(array)) cache.put(job.id, job);
        } catch (Exception ignored) {
        }
        return cache;
    }

    public static synchronized boolean isSaved(Context c, int id) {
        return load(c).containsKey(id);
    }

    /** Newest first. */
    public static synchronized List<Job> all(Context c) {
        List<Job> list = new ArrayList<>(load(c).values());
        java.util.Collections.reverse(list);
        return list;
    }

    /** Adds or removes the job; returns true when it is now saved. */
    public static boolean toggle(Context c, Job job) {
        boolean saved;
        synchronized (SavedJobs.class) {
            Map<Integer, Job> map = load(c);
            if (map.containsKey(job.id)) {
                map.remove(job.id);
                saved = false;
            } else {
                map.put(job.id, job);
                saved = true;
            }
            JSONArray array = new JSONArray();
            for (Job j : map.values()) array.put(j.toJson());
            p(c).edit().putString(KEY, array.toString()).apply();
        }
        for (Listener l : LISTENERS) l.onSavedJobsChanged();
        return saved;
    }

    public static void addListener(Listener l) {
        LISTENERS.add(l);
    }

    public static void removeListener(Listener l) {
        LISTENERS.remove(l);
    }
}
