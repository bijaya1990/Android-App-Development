package com.naukripatra.emicalculator.ui.schedule;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.model.ScheduleEntry;
import com.naukripatra.emicalculator.util.CurrencyUtils;

import java.util.List;

/** Renders the month-by-month amortization table on the Schedule tab. */
public class ScheduleAdapter extends RecyclerView.Adapter<ScheduleAdapter.RowViewHolder> {

    private final List<ScheduleEntry> entries;

    public ScheduleAdapter(List<ScheduleEntry> entries) {
        this.entries = entries;
    }

    @NonNull
    @Override
    public RowViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_schedule_row, parent, false);
        return new RowViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull RowViewHolder holder, int position) {
        holder.bind(entries.get(position));
    }

    @Override
    public int getItemCount() {
        return entries.size();
    }

    static class RowViewHolder extends RecyclerView.ViewHolder {
        private final TextView month;
        private final TextView emi;
        private final TextView principal;
        private final TextView interest;
        private final TextView balance;

        RowViewHolder(@NonNull View itemView) {
            super(itemView);
            month = itemView.findViewById(R.id.txtMonth);
            emi = itemView.findViewById(R.id.txtEmi);
            principal = itemView.findViewById(R.id.txtPrincipal);
            interest = itemView.findViewById(R.id.txtInterest);
            balance = itemView.findViewById(R.id.txtBalance);
        }

        void bind(ScheduleEntry entry) {
            month.setText(String.valueOf(entry.getMonth()));
            emi.setText(CurrencyUtils.formatPlainNumber(entry.getEmi()));
            principal.setText(CurrencyUtils.formatPlainNumber(entry.getPrincipal()));
            interest.setText(CurrencyUtils.formatPlainNumber(entry.getInterest()));
            balance.setText(CurrencyUtils.formatPlainNumber(entry.getBalance()));

            int background = entry.getMonth() % 2 == 0
                    ? androidx.core.content.ContextCompat.getColor(itemView.getContext(), R.color.md_surface_container_low)
                    : android.graphics.Color.TRANSPARENT;
            itemView.setBackgroundColor(background);
        }
    }
}
