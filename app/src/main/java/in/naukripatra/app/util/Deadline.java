package in.naukripatra.app.util;

import android.content.Context;
import android.content.res.ColorStateList;
import android.widget.TextView;

import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.Locale;
import java.util.concurrent.TimeUnit;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import in.naukripatra.app.R;

/**
 * Turns the free-text "last_date" from the API into "5 days left" style badges.
 * Only dates written at the start of the text are trusted: values such as
 * "Within 15 days of publication (Advt dated 24.09.2026)" are shown as-is.
 */
public final class Deadline {

    public static final int UNKNOWN = Integer.MIN_VALUE;

    private static final Pattern NUMERIC = Pattern.compile("^(\\d{1,2})[./-](\\d{1,2})[./-](\\d{4})");
    private static final Pattern DAY_MONTH_YEAR = Pattern.compile("^(\\d{1,2})(?:st|nd|rd|th)?[\\s-]+([A-Za-z]{3,9})[,\\s-]+(\\d{4})");
    private static final Pattern MONTH_DAY_YEAR = Pattern.compile("^([A-Za-z]{3,9})\\s+(\\d{1,2})(?:st|nd|rd|th)?,?\\s+(\\d{4})");

    private Deadline() {
    }

    @Nullable
    public static Date parse(String text) {
        if (text == null) return null;
        String t = text.trim();
        try {
            Matcher m = NUMERIC.matcher(t);
            if (m.find()) {
                return strict("d/M/yyyy", m.group(1) + "/" + m.group(2) + "/" + m.group(3));
            }
            m = DAY_MONTH_YEAR.matcher(t);
            if (m.find()) {
                return month(m.group(1), m.group(2), m.group(3));
            }
            m = MONTH_DAY_YEAR.matcher(t);
            if (m.find()) {
                return month(m.group(2), m.group(1), m.group(3));
            }
        } catch (ParseException ignored) {
        }
        return null;
    }

    private static Date month(String day, String month, String year) throws ParseException {
        String pattern = month.length() <= 3 ? "d MMM yyyy" : "d MMMM yyyy";
        String mon = month.length() > 3 && month.length() < 5 ? month.substring(0, 3) : month; // "Sept"
        if (mon.length() == 3) pattern = "d MMM yyyy";
        return strict(pattern, day + " " + mon + " " + year);
    }

    private static Date strict(String pattern, String value) throws ParseException {
        SimpleDateFormat f = new SimpleDateFormat(pattern, Locale.ENGLISH);
        f.setLenient(false);
        return f.parse(value);
    }

    /** Whole days from today to the date; negative once it has passed. */
    public static int daysLeft(String text) {
        Date date = parse(text);
        if (date == null) return UNKNOWN;
        return (int) TimeUnit.MILLISECONDS.toDays(startOfDay(date) - startOfDay(new Date()));
    }

    /** True when the post was published today or yesterday. */
    public static boolean isNew(String postedDate) {
        int d = daysLeft(postedDate);
        return d != UNKNOWN && d >= -1 && d <= 0;
    }

    private static long startOfDay(Date date) {
        Calendar c = Calendar.getInstance();
        c.setTime(date);
        c.set(Calendar.HOUR_OF_DAY, 12); // noon avoids DST edge cases
        c.set(Calendar.MINUTE, 0);
        c.set(Calendar.SECOND, 0);
        c.set(Calendar.MILLISECOND, 0);
        return c.getTimeInMillis();
    }

    /** "30 Oct 2026", or the original text when it is not a plain date. */
    public static String pretty(String text) {
        Date date = parse(text);
        if (date == null) return text == null ? "" : text;
        return new SimpleDateFormat("d MMM yyyy", Locale.ENGLISH).format(date);
    }

    public static String weekday(String text) {
        Date date = parse(text);
        if (date == null) return "";
        return new SimpleDateFormat("EEEE", Locale.ENGLISH).format(date);
    }

    /** Fills a badge with the right label and colour. Hides it when the date is unknown. */
    public static void bind(TextView badge, String lastDate) {
        int days = daysLeft(lastDate);
        if (days == UNKNOWN) {
            badge.setVisibility(android.view.View.GONE);
            return;
        }
        badge.setVisibility(android.view.View.VISIBLE);
        Context c = badge.getContext();
        int fg;
        int bg;
        int icon;
        String label;
        if (days < 0) {
            label = "Closed";
            fg = R.color.text_2;
            bg = R.color.grey_100;
            icon = R.drawable.ic_schedule;
        } else if (days == 0) {
            label = "Last day today";
            fg = R.color.red;
            bg = R.color.red_100;
            icon = R.drawable.ic_local_fire_department;
        } else if (days <= 3) {
            label = days == 1 ? "1 day left" : days + " days left";
            fg = R.color.red;
            bg = R.color.red_100;
            icon = R.drawable.ic_local_fire_department;
        } else if (days <= 7) {
            label = days + " days left";
            fg = R.color.accent_text;
            bg = R.color.accent_100;
            icon = R.drawable.ic_schedule;
        } else {
            label = days + " days left";
            fg = R.color.green_text;
            bg = R.color.green_100;
            icon = R.drawable.ic_event_available;
        }
        badge.setText(label);
        int fgColor = ContextCompat.getColor(c, fg);
        badge.setTextColor(fgColor);
        badge.setBackgroundTintList(ColorStateList.valueOf(ContextCompat.getColor(c, bg)));
        Ui.startIcon(badge, icon, 14, fgColor);
    }
}
