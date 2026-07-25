package com.naukripatra.emicalculator.ui.result;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.ViewModelProvider;
import androidx.recyclerview.widget.LinearLayoutManager;

import com.naukripatra.emicalculator.databinding.FragmentScheduleBinding;
import com.naukripatra.emicalculator.model.EmiResult;
import com.naukripatra.emicalculator.ui.schedule.ScheduleAdapter;

public class ScheduleFragment extends Fragment {

    private FragmentScheduleBinding binding;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        binding = FragmentScheduleBinding.inflate(inflater, container, false);
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

        binding.recyclerSchedule.setLayoutManager(new LinearLayoutManager(requireContext()));
        binding.recyclerSchedule.setHasFixedSize(true);
        binding.recyclerSchedule.setAdapter(new ScheduleAdapter(result.getSchedule()));
    }

    @Override
    public void onDestroyView() {
        super.onDestroyView();
        binding = null;
    }
}
