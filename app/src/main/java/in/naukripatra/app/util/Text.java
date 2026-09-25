package in.naukripatra.app.util;

import android.net.Uri;
import android.text.TextUtils;

import androidx.annotation.NonNull;
import androidx.core.text.HtmlCompat;

import java.text.NumberFormat;
import java.util.Locale;

public final class Text {

    private Text() {
    }

    /** Decodes HTML entities such as &amp;amp; and strips tags. */
    @NonNull
    public static String html(String s) {
        if (s == null || s.isEmpty()) return "";
        if (s.indexOf('&') < 0 && s.indexOf('<') < 0) return s.trim();
        return HtmlCompat.fromHtml(s, HtmlCompat.FROM_HTML_MODE_LEGACY).toString().trim();
    }

    /** Empty for values that carry no information ("null", "N/A", "Not mentioned"...). */
    @NonNull
    public static String clean(String s) {
        String v = html(s);
        String l = v.toLowerCase(Locale.ROOT);
        if (l.equals("null") || l.equals("n/a") || l.equals("na") || l.equals("-") || l.equals("--")
                || l.equals("not specified") || l.equals("not mentioned") || l.equals("not available")) {
            return "";
        }
        return v;
    }

    /** Only http(s) links are kept, so a button never opens something broken. */
    @NonNull
    public static String url(String s) {
        if (s == null) return "";
        String v = s.trim();
        if (!(v.startsWith("https://") || v.startsWith("http://"))) return "";
        Uri uri = Uri.parse(v);
        return TextUtils.isEmpty(uri.getHost()) ? "" : v;
    }

    /** "https://www.ssc.gov.in/x" -> "ssc.gov.in". Empty for our own site. */
    @NonNull
    public static String host(String url) {
        if (url == null || url.isEmpty()) return "";
        String host = Uri.parse(url).getHost();
        if (host == null) return "";
        host = host.toLowerCase(Locale.ROOT);
        if (host.startsWith("www.")) host = host.substring(4);
        if (host.endsWith("naukripatra.in")) return "";
        return host;
    }

    /** "Staff Selection Commission" -> "SSC"; "Odisha Police" -> "OP". */
    @NonNull
    public static String initials(String name) {
        if (name == null) return "NP";
        // Prefer an acronym already written in brackets, e.g. "... (PSSCIVE), NCERT".
        int open = name.indexOf('(');
        int close = name.indexOf(')', open + 1);
        if (open >= 0 && close > open + 1) {
            String inside = name.substring(open + 1, close).trim();
            if (inside.length() <= 5 && inside.equals(inside.toUpperCase(Locale.ROOT)) && !inside.contains(" ")) {
                return inside;
            }
        }
        StringBuilder sb = new StringBuilder();
        for (String word : name.split("[\\s,/&:\\-–]+")) {
            if (word.isEmpty()) continue;
            String lw = word.toLowerCase(Locale.ROOT);
            if (lw.equals("of") || lw.equals("and") || lw.equals("the") || lw.equals("for") || lw.equals("in")) continue;
            char c = word.charAt(0);
            if (Character.isLetter(c)) sb.append(Character.toUpperCase(c));
            if (sb.length() == 3) break;
        }
        return sb.length() == 0 ? "NP" : sb.toString();
    }

    /** 1097 -> "1,097" using Indian grouping. */
    @NonNull
    public static String number(int n) {
        return NumberFormat.getIntegerInstance(Locale.forLanguageTag("en-IN")).format(n);
    }

    public static boolean isEmpty(String s) {
        return s == null || s.trim().isEmpty();
    }
}
