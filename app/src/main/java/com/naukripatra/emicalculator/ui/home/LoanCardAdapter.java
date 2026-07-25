package com.naukripatra.emicalculator.ui.home;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.core.graphics.ColorUtils;
import androidx.recyclerview.widget.RecyclerView;

import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.model.LoanType;

/** Feeds the four premium loan-type cards on the home screen into a 2-column grid. */
public class LoanCardAdapter extends RecyclerView.Adapter<LoanCardAdapter.CardViewHolder> {

    public interface OnLoanTypeClickListener {
        void onLoanTypeClick(LoanType loanType);
    }

    private final LoanType[] loanTypes = LoanType.values();
    private final OnLoanTypeClickListener listener;

    public LoanCardAdapter(OnLoanTypeClickListener listener) {
        this.listener = listener;
    }

    @NonNull
    @Override
    public CardViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_loan_card, parent, false);
        return new CardViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull CardViewHolder holder, int position) {
        holder.bind(loanTypes[position], listener);
    }

    @Override
    public int getItemCount() {
        return loanTypes.length;
    }

    static class CardViewHolder extends RecyclerView.ViewHolder {
        private final View badgeBackground;
        private final ImageView imgIcon;
        private final TextView title;
        private final TextView desc;

        CardViewHolder(@NonNull View itemView) {
            super(itemView);
            badgeBackground = itemView.findViewById(R.id.badgeBackground);
            imgIcon = itemView.findViewById(R.id.imgLoanIcon);
            title = itemView.findViewById(R.id.txtLoanTitle);
            desc = itemView.findViewById(R.id.txtLoanDesc);
        }

        void bind(LoanType loanType, OnLoanTypeClickListener listener) {
            int accentColor = itemView.getContext().getColor(loanType.getAccentColorRes());

            imgIcon.setImageResource(loanType.getIconRes());
            imgIcon.setImageTintList(android.content.res.ColorStateList.valueOf(accentColor));
            badgeBackground.setBackgroundTintList(
                    android.content.res.ColorStateList.valueOf(ColorUtils.setAlphaComponent(accentColor, 40)));

            title.setText(loanType.getTitleRes());
            desc.setText(loanType.getDescRes());

            itemView.setOnClickListener(v -> {
                if (listener != null) {
                    listener.onLoanTypeClick(loanType);
                }
            });
        }
    }
}
