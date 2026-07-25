package com.naukripatra.emicalculator.ui.result;

import androidx.annotation.NonNull;
import androidx.fragment.app.Fragment;
import androidx.fragment.app.FragmentActivity;
import androidx.viewpager2.adapter.FragmentStateAdapter;

public class ResultPagerAdapter extends FragmentStateAdapter {

    public ResultPagerAdapter(@NonNull FragmentActivity activity) {
        super(activity);
    }

    @NonNull
    @Override
    public Fragment createFragment(int position) {
        switch (position) {
            case 1:
                return new ChartFragment();
            case 2:
                return new ScheduleFragment();
            case 0:
            default:
                return new SummaryFragment();
        }
    }

    @Override
    public int getItemCount() {
        return 3;
    }
}
