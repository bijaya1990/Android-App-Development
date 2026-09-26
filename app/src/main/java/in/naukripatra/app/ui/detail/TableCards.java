package in.naukripatra.app.ui.detail;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.util.ArrayList;
import java.util.List;
import java.util.Locale;
import java.util.regex.Pattern;

/**
 * Tables with three or more columns are unreadable on a phone: the columns get
 * squeezed and values run together. This turns each such table into one card per
 * row, where every value is shown next to its column name ("Posts: 33").
 * Two-column (label / value) tables are left as they are.
 */
final class TableCards {

    private static final int MIN_COLUMNS = 3;
    private static final Pattern SERIAL = Pattern.compile("^(s\\.?\\s*no\\.?|sl\\.?\\s*no\\.?|sr\\.?\\s*no\\.?|no\\.?|#)$",
            Pattern.CASE_INSENSITIVE);

    private TableCards() {
    }

    static String apply(String html) {
        if (html == null || !html.contains("<table")) return html;
        try {
            Document doc = Jsoup.parseBodyFragment(html);
            doc.outputSettings().prettyPrint(false);
            boolean changed = false;
            for (Element table : doc.select("table")) {
                Element cards = convert(table);
                if (cards != null) {
                    Element wrap = table.parent();
                    // Replace the scroll wrapper too, so the cards use the full width.
                    if (wrap != null && wrap.hasClass("np-table-wrap") && wrap.select("table").size() == 1) {
                        wrap.replaceWith(cards);
                    } else {
                        table.replaceWith(cards);
                    }
                    changed = true;
                }
            }
            return changed ? doc.body().html() : html;
        } catch (Exception e) {
            return html;
        }
    }

    /** Returns the card markup, or null to keep the table unchanged. */
    private static Element convert(Element table) {
        Elements rows = table.select("tr");
        if (rows.size() < 2) return null;
        // Skip nested tables; only handle rows that belong to this table.
        List<Element> own = new ArrayList<>();
        for (Element tr : rows) {
            if (tr.closest("table") == table) own.add(tr);
        }
        if (own.size() < 2) return null;

        List<List<Element>> grid = grid(own);
        int columns = 0;
        for (List<Element> r : grid) columns = Math.max(columns, r.size());
        if (columns < MIN_COLUMNS) return null;

        // Header: the first row when it uses <th>, or when the table has no <th> at all.
        Element first = own.get(0);
        boolean headerIsTh = !first.select("> th").isEmpty();
        if (!headerIsTh && !table.select("th").isEmpty()) return null;
        List<String> labels = new ArrayList<>();
        for (Element cell : grid.get(0)) labels.add(cell == null ? "" : cell.text().trim());
        while (labels.size() < columns) labels.add("");

        boolean serial = SERIAL.matcher(labels.get(0)).matches();
        int titleCol = serial && columns > 1 ? 1 : 0;

        Element out = new Element("div").addClass("stack");
        for (int r = 1; r < grid.size(); r++) {
            List<Element> row = grid.get(r);
            if (isEmptyRow(row)) continue;
            Element card = out.appendElement("div").addClass("srow").addClass("c" + ((r - 1) % 4));
            String titleText = text(row, titleCol);
            if (titleText.toLowerCase(Locale.ROOT).startsWith("total")) card.addClass("total");

            Element head = card.appendElement("div").addClass("shead");
            if (serial && !text(row, 0).isEmpty() && !titleText.toLowerCase(Locale.ROOT).startsWith("total")) {
                head.appendElement("span").addClass("sbadge").text(text(row, 0));
            }
            Element headText = head.appendElement("div").addClass("stext");
            if (!labels.get(titleCol).isEmpty() && !card.hasClass("total")) {
                headText.appendElement("span").addClass("scol").text(labels.get(titleCol));
            }
            Element titleCell = cell(row, titleCol);
            Element title = headText.appendElement("span").addClass("stitle");
            if (titleCell != null) title.html(titleCell.html());

            Element lastCell = titleCell;
            for (int c = 0; c < columns; c++) {
                if (c == titleCol || (serial && c == 0)) continue;
                Element value = cell(row, c);
                // A cell spanning several columns is shown once.
                if (value == null || value == lastCell) continue;
                lastCell = value;
                String v = value.text().trim();
                if (v.isEmpty()) continue;
                Element line = card.appendElement("div").addClass("sline");
                line.appendElement("span").addClass("slabel").text(labels.get(c).isEmpty() ? "–" : labels.get(c));
                line.appendElement("span").addClass("sval").html(value.html());
            }
        }
        return out.childrenSize() == 0 ? null : out;
    }

    /** Expands colspan / rowspan so every row has one entry per column. */
    private static List<List<Element>> grid(List<Element> rows) {
        List<List<Element>> grid = new ArrayList<>();
        int[] spanLeft = new int[64];
        Element[] spanCell = new Element[64];
        for (Element tr : rows) {
            List<Element> out = new ArrayList<>();
            int col = 0;
            for (Element cell : tr.children()) {
                if (!cell.tagName().equals("td") && !cell.tagName().equals("th")) continue;
                while (col < 64 && spanLeft[col] > 0) {
                    set(out, col, spanCell[col]);
                    spanLeft[col]--;
                    col++;
                }
                int colspan = span(cell.attr("colspan"));
                int rowspan = span(cell.attr("rowspan"));
                for (int i = 0; i < colspan && col < 64; i++, col++) {
                    set(out, col, cell);
                    if (rowspan > 1) {
                        spanLeft[col] = rowspan - 1;
                        spanCell[col] = cell;
                    }
                }
            }
            // Row-spanning cells that sit after this row's last cell.
            for (; col < 64; col++) {
                if (spanLeft[col] > 0) {
                    set(out, col, spanCell[col]);
                    spanLeft[col]--;
                }
            }
            grid.add(out);
        }
        return grid;
    }

    private static void set(List<Element> out, int col, Element cell) {
        while (out.size() <= col) out.add(null);
        out.set(col, cell);
    }

    private static int span(String v) {
        try {
            int n = Integer.parseInt(v.trim());
            return Math.max(1, Math.min(n, 20));
        } catch (NumberFormatException e) {
            return 1;
        }
    }

    private static Element cell(List<Element> row, int c) {
        return c < row.size() ? row.get(c) : null;
    }

    private static String text(List<Element> row, int c) {
        Element e = cell(row, c);
        return e == null ? "" : e.text().trim();
    }

    private static boolean isEmptyRow(List<Element> row) {
        for (Element e : row) {
            if (e != null && !e.text().trim().isEmpty()) return false;
        }
        return true;
    }
}
