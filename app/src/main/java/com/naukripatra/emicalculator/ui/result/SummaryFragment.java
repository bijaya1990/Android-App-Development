package com.naukripatra.emicalculator.ui.result;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.ViewModelProvider;

import com.naukripatra.emicalculator.databinding.FragmentSummaryBinding;
import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.util.CurrencyUtils;
import com.naukripatra.emicalculator.util.NumberAnimationUtils;

public class SummaryFragment extends Fragment {

    private FragmentSummaryBinding binding;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        binding = FragmentSummaryBinding.inflate(inflater, container, false);
        return binding.getRoot();
    }

    @Override
    public void onViewCreated(@NonNull View view, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(view, savedInstanceState);
        ResultViewModel viewModel = new ViewModelProvider(requireActivity()).get(ResultViewModel.class);
        EmiResult result = viewModel.getResult();
        if (result == null) {
            return;
        }

        NumberAnimationUtils.animateRupeeAmount(binding.txtMonthlyEmi, result.getMonthlyEmi(), 700);
        NumberAnimationUtils.animateRupeeAmount(binding.txtPrincipal, result.getLoanAmount(), 700);
        NumberAnimationUtils.animateRupeeAmount(binding.txtInterest, result.getTotalInterest(), 700);
        NumberAnimationUtils.animateRupeeAmount(binding.txtTotalPayment, result.getTotalPayment(), 900);

        binding.txtDetailLoanAmount.setText(CurrencyUtils.formatWholeRupees(result.getLoanAmount()));
        binding.txtDetailRate.setText(CurrencyUtils.formatPercent(result.getInterestRate()) + "%");
        binding.txtDetailTenure.setText(formatTenure(result.getTenureMonths()));
    }

    private String formatTenure(int months) {
        int years = months / 12;
        int rem = months % 12;
        if (years > 0 && rem > 0) {
            return years + " yr " + rem + " mo";
        } else if (years > 0) {
            return years + " yr";
        }
        return rem + " mo";
    }

    @Override
    public void onDestroyView() {
        super.onDestroyView();
        binding = null;
    }
}
