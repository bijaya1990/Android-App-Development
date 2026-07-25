package com.naukripatra.emicalculator.ui.result;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.ViewModelProvider;

import com.github.mikephil.charting.animation.Easing;
import com.github.mikephil.charting.charts.PieChart;
import com.github.mikephil.charting.data.PieData;
import com.github.mikephil.charting.data.PieDataSet;
import com.github.mikephil.charting.data.PieEntry;
import com.naukripatra.emicalculator.R;
import com.naukripatra.emicalculator.databinding.FragmentChartBinding;
import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.util.CurrencyUtils;

import java.util.ArrayList;
import java.util.List;

public class ChartFragment extends Fragment {

    private FragmentChartBinding binding;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        binding = FragmentChartBinding.inflate(inflater, container, false);
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

        binding.txtChartPrincipal.setText(CurrencyUtils.formatWholeRupees(result.getLoanAmount()));
        binding.txtChartInterest.setText(CurrencyUtils.formatWholeRupees(result.getTotalInterest()));

        setupPieChart(result);
    }

    private void setupPieChart(EmiResult result) {
        PieChart pieChart = binding.pieChart;

        List<PieEntry> entries = new ArrayList<>();
        entries.add(new PieEntry((float) result.getLoanAmount(), getString(R.string.chart_principal_label)));
        entries.add(new PieEntry((float) result.getTotalInterest(), getString(R.string.chart_interest_label)));

        PieDataSet dataSet = new PieDataSet(entries, "");
        int principalColor = ContextCompat.getColor(requireContext(), R.color.chart_principal);
        int interestColor = ContextCompat.getColor(requireContext(), R.color.chart_interest);
        dataSet.setColors(principalColor, interestColor);
        dataSet.setValueTextSize(0f);
        dataSet.setDrawValues(false);
        dataSet.setSliceSpace(3f);

        PieData pieData = new PieData(dataSet);

        pieChart.setData(pieData);
        pieChart.setUsePercentValues(true);
        pieChart.setDrawEntryLabels(false);
        pieChart.getDescription().setEnabled(false);
        pieChart.getLegend().setEnabled(false);
        pieChart.setHoleRadius(58f);
        pieChart.setTransparentCircleRadius(62f);
        pieChart.setHoleColor(android.graphics.Color.TRANSPARENT);
        pieChart.setCenterText(getString(R.string.tab_chart));
        pieChart.setCenterTextSize(14f);
        pieChart.setRotationEnabled(false);
        pieChart.setExtraOffsets(8f, 8f, 8f, 8f);
        pieChart.animateY(800, Easing.EaseInOutQuad);
        pieChart.invalidate();
    }

    @Override
    public void onDestroyView() {
        super.onDestroyView();
        binding = null;
    }
}
