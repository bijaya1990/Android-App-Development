package in.naukripatra.app.ui.detail;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertFalse;
import static org.junit.Assert.assertTrue;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.junit.Test;

import java.util.List;

/** Real table shapes taken from naukripatra.in posts. */
public class TableCardsTest {

    private static Document cards(String html) {
        return Jsoup.parseBodyFragment(TableCards.apply(html));
    }

    private static String line(Element row, String label) {
        for (Element l : row.select(".sline")) {
            if (l.selectFirst(".slabel").text().equals(label)) return l.selectFirst(".sval").text();
        }
        return null;
    }

    @Test
    public void twoColumnTablesAreKept() {
        String html = "<table><tr><td><strong>Organization</strong></td><td>BEL</td></tr>"
                + "<tr><td>Unit</td><td>NCS</td></tr></table>";
        Document d = cards(html);
        assertEquals(1, d.select("table").size());
        assertTrue(d.select(".stack").isEmpty());
    }

    @Test
    public void wideTableBecomesLabelledCards() {
        String html = "<div class=\"np-table-wrap\"><table><tr><th>S.No.</th><th>Post</th><th>Posts</th><th>Category</th><th>Pay Level</th></tr>"
                + "<tr><td>1</td><td>System Analyst</td><td>1</td><td>General</td><td>Level 9</td></tr>"
                + "<tr><td>2</td><td>Senior Clerk</td><td>1</td><td>PwBD</td><td>Level 4</td></tr>"
                + "<tr><td colspan=\"2\"><strong>Total</strong></td><td><strong>4</strong></td><td colspan=\"2\"></td></tr>"
                + "</table></div>";
        Document d = cards(html);
        assertTrue(d.select("table").isEmpty());
        assertTrue(d.select(".np-table-wrap").isEmpty());
        List<Element> rows = d.select(".srow");
        assertEquals(3, rows.size());
        Element first = rows.get(0);
        assertEquals("1", first.selectFirst(".sbadge").text());
        assertEquals("System Analyst", first.selectFirst(".stitle").text());
        assertEquals("Post", first.selectFirst(".scol").text());
        assertEquals("1", line(first, "Posts"));
        assertEquals("General", line(first, "Category"));
        assertEquals("Level 9", line(first, "Pay Level"));

        Element total = rows.get(2);
        assertTrue(total.hasClass("total"));
        assertEquals("4", line(total, "Posts"));
        assertEquals(null, line(total, "Category")); // empty cell skipped
    }

    @Test
    public void rowspanValueRepeatsOnEveryRow() {
        String html = "<table><tr><th>Date</th><th>Reporting Time</th><th>Post</th></tr>"
                + "<tr><td rowspan=\"2\">30.09.2026</td><td>9:00 AM</td><td>Assistant Professor</td></tr>"
                + "<tr><td>10:00 AM</td><td>Lab Assistant</td></tr>"
                + "<tr><td>01.10.2026</td><td>11:00 AM</td><td>Editor</td></tr></table>";
        List<Element> rows = cards(html).select(".srow");
        assertEquals(3, rows.size());
        assertEquals("30.09.2026", rows.get(1).selectFirst(".stitle").text());
        assertEquals("10:00 AM", line(rows.get(1), "Reporting Time"));
        assertEquals("Lab Assistant", line(rows.get(1), "Post"));
        assertEquals("01.10.2026", rows.get(2).selectFirst(".stitle").text());
        assertFalse(rows.get(0).hasClass("total"));
    }
}
