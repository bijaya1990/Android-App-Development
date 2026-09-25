package in.naukripatra.app.util;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertNull;

import org.junit.Test;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

/** The API writes last dates in many formats; these are the ones seen in real posts. */
public class DeadlineTest {

    private static String fmt(Date d) {
        return new SimpleDateFormat("yyyy-MM-dd", Locale.ENGLISH).format(d);
    }

    @Test
    public void parsesCommonFormats() {
        assertEquals("2026-10-01", fmt(Deadline.parse("01 October 2026")));
        assertEquals("2026-09-25", fmt(Deadline.parse("25 Sep 2026")));
        assertEquals("2026-09-16", fmt(Deadline.parse("September 16, 2026")));
        assertEquals("2026-10-16", fmt(Deadline.parse("16/10/2026")));
        assertEquals("2026-10-16", fmt(Deadline.parse("16.10.2026")));
        assertEquals("2026-10-16", fmt(Deadline.parse("16-10-2026")));
        assertEquals("2026-09-30", fmt(Deadline.parse("30 Sept 2026")));
        assertEquals("2026-10-05", fmt(Deadline.parse("05 October 2026 (Walk-in Interview)")));
    }

    @Test
    public void ignoresTextThatIsNotADate() {
        // A date buried inside a sentence is not the last date.
        assertNull(Deadline.parse("Within 15 days of publication (Advt dated 24.09.2026)"));
        assertNull(Deadline.parse("As per notification"));
        assertNull(Deadline.parse(""));
        assertNull(Deadline.parse(null));
        assertNull(Deadline.parse("31/02/2026"));
    }

    @Test
    public void unknownDatesHaveNoCountdown() {
        assertEquals(Deadline.UNKNOWN, Deadline.daysLeft("Soon"));
        assertNotNull(Deadline.pretty("Soon"));
        assertEquals("Soon", Deadline.pretty("Soon"));
    }
}
