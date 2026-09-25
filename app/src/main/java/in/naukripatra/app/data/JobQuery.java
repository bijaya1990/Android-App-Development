package in.naukripatra.app.data;

import androidx.annotation.NonNull;

import java.util.Objects;

/** What a job list is showing: one API source plus its value. */
public final class JobQuery {

    public enum Type { LATEST, ALL_INDIA, USA, STATE, QUALIFICATION, SEARCH }

    public final Type type;
    public final String value;

    private JobQuery(Type type, String value) {
        this.type = type;
        this.value = value == null ? "" : value;
    }

    public static JobQuery latest() {
        return new JobQuery(Type.LATEST, "");
    }

    public static JobQuery allIndia() {
        return new JobQuery(Type.ALL_INDIA, "");
    }

    public static JobQuery usa() {
        return new JobQuery(Type.USA, "");
    }

    public static JobQuery state(String state) {
        return new JobQuery(Type.STATE, state);
    }

    public static JobQuery qualification(String qualification) {
        return new JobQuery(Type.QUALIFICATION, qualification);
    }

    public static JobQuery search(String keyword) {
        return new JobQuery(Type.SEARCH, keyword.trim());
    }

    @Override
    public boolean equals(Object o) {
        if (!(o instanceof JobQuery)) return false;
        JobQuery q = (JobQuery) o;
        return type == q.type && value.equals(q.value);
    }

    @Override
    public int hashCode() {
        return Objects.hash(type, value);
    }

    @NonNull
    @Override
    public String toString() {
        return type + ":" + value;
    }
}
