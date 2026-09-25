package in.naukripatra.app.data;

import android.content.Context;
import android.content.SharedPreferences;

import androidx.appcompat.app.AppCompatDelegate;

/**
 * Small on-device settings. Uses the same "naukripatra" file as the previous app
 * version, so the qualification and theme users already chose carry over.
 */
public final class Prefs {

    private static final String FILE = "naukripatra";
    private static final String KEY_QUALIFICATION = "qualification";
    private static final String KEY_STATE = "state";
    private static final String KEY_ONBOARDED = "onboarded_v2";
    private static final String KEY_THEME = "theme_mode";

    public static final int THEME_SYSTEM = 0;
    public static final int THEME_LIGHT = 1;
    public static final int THEME_DARK = 2;

    /** Qualification values accepted by the qualification-wise-jobs API. */
    public static final String[] QUALIFICATIONS = {
            "8th Pass", "10th Pass", "12th Pass", "ITI", "Diploma", "Graduate", "Post Graduate", "B.Tech"
    };

    public static final String[] STATES = {
            "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat",
            "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh",
            "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", "Odisha", "Punjab",
            "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttar Pradesh",
            "Uttarakhand", "West Bengal", "Andaman and Nicobar Islands", "Chandigarh",
            "Dadra and Nagar Haveli and Daman and Diu", "Delhi", "Jammu and Kashmir", "Ladakh",
            "Lakshadweep", "Puducherry"
    };

    private Prefs() {
    }

    private static SharedPreferences p(Context c) {
        return c.getApplicationContext().getSharedPreferences(FILE, Context.MODE_PRIVATE);
    }

    public static String qualification(Context c) {
        String q = p(c).getString(KEY_QUALIFICATION, "");
        // Map values saved by the old app onto the new list.
        switch (q) {
            case "Graduation":
            case "Any Graduate":
            case "Bachelor Degree":
            case "B.A.":
            case "B.Sc.":
            case "B.Com.":
            case "BCA":
                return "Graduate";
            case "B.E.":
                return "B.Tech";
            case "M.Sc":
            case "M.Tech":
            case "MCA":
            case "MBA":
            case "Master Degree":
            case "Ph.D":
                return "Post Graduate";
            default:
                return q;
        }
    }

    public static void setQualification(Context c, String q) {
        p(c).edit().putString(KEY_QUALIFICATION, q == null ? "" : q).apply();
    }

    public static String state(Context c) {
        return p(c).getString(KEY_STATE, "");
    }

    public static void setState(Context c, String s) {
        p(c).edit().putString(KEY_STATE, s == null ? "" : s).apply();
    }

    public static boolean onboarded(Context c) {
        return p(c).getBoolean(KEY_ONBOARDED, false);
    }

    public static void setOnboarded(Context c) {
        p(c).edit().putBoolean(KEY_ONBOARDED, true).apply();
    }

    public static int theme(Context c) {
        return p(c).getInt(KEY_THEME, THEME_SYSTEM);
    }

    public static void setTheme(Context c, int mode) {
        p(c).edit().putInt(KEY_THEME, mode).apply();
        applyTheme(c);
    }

    public static void applyTheme(Context c) {
        int mode = theme(c);
        AppCompatDelegate.setDefaultNightMode(mode == THEME_LIGHT ? AppCompatDelegate.MODE_NIGHT_NO
                : mode == THEME_DARK ? AppCompatDelegate.MODE_NIGHT_YES
                : AppCompatDelegate.MODE_NIGHT_FOLLOW_SYSTEM);
    }

    /** Short abbreviation shown in the state dropdown, e.g. "OD". */
    public static String stateCode(String state) {
        switch (state) {
            case "Andhra Pradesh": return "AP";
            case "Arunachal Pradesh": return "AR";
            case "Assam": return "AS";
            case "Bihar": return "BR";
            case "Chhattisgarh": return "CG";
            case "Goa": return "GA";
            case "Gujarat": return "GJ";
            case "Haryana": return "HR";
            case "Himachal Pradesh": return "HP";
            case "Jharkhand": return "JH";
            case "Karnataka": return "KA";
            case "Kerala": return "KL";
            case "Madhya Pradesh": return "MP";
            case "Maharashtra": return "MH";
            case "Manipur": return "MN";
            case "Meghalaya": return "ML";
            case "Mizoram": return "MZ";
            case "Nagaland": return "NL";
            case "Odisha": return "OD";
            case "Punjab": return "PB";
            case "Rajasthan": return "RJ";
            case "Sikkim": return "SK";
            case "Tamil Nadu": return "TN";
            case "Telangana": return "TS";
            case "Tripura": return "TR";
            case "Uttar Pradesh": return "UP";
            case "Uttarakhand": return "UK";
            case "West Bengal": return "WB";
            case "Andaman and Nicobar Islands": return "AN";
            case "Chandigarh": return "CH";
            case "Dadra and Nagar Haveli and Daman and Diu": return "DD";
            case "Delhi": return "DL";
            case "Jammu and Kashmir": return "JK";
            case "Ladakh": return "LA";
            case "Lakshadweep": return "LD";
            case "Puducherry": return "PY";
            default: return "IN";
        }
    }

    /** Whether a job's qualification text mentions the user's saved qualification. */
    public static boolean matchesQualification(String saved, String jobText) {
        if (saved == null || saved.isEmpty() || jobText == null || jobText.isEmpty()) return false;
        String t = " " + jobText.toLowerCase(java.util.Locale.ROOT) + " ";
        String[] keys;
        switch (saved) {
            case "8th Pass": keys = new String[]{"8th"}; break;
            case "10th Pass": keys = new String[]{"10th", "matric", "sslc", "hsc exam"}; break;
            case "12th Pass": keys = new String[]{"12th", "intermediate", "+2", "higher secondary"}; break;
            case "ITI": keys = new String[]{"iti"}; break;
            case "Diploma": keys = new String[]{"diploma"}; break;
            case "Graduate": keys = new String[]{"graduate", "graduation", "degree", "bachelor", "b.a", "b.sc", "b.com"}; break;
            case "Post Graduate": keys = new String[]{"post graduate", "post-graduate", "postgraduate", "master", "m.a", "m.sc", "m.com", "mba", "mca", " pg "}; break;
            case "B.Tech": keys = new String[]{"b.tech", "b.e", "btech", "engineering"}; break;
            default: keys = new String[]{saved.toLowerCase(java.util.Locale.ROOT)};
        }
        for (String k : keys) {
            if (t.contains(k)) return true;
        }
        return false;
    }
}
