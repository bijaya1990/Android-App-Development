package com.naukripatra.emicalculator.model;

import com.naukripatra.emicalculator.R;

/**
 * All loan products the calculator supports. Each type drives which input
 * fields are visible on the calculator screen and how those fields are
 * labelled, so the same screen and view model can be reused for every card
 * on the home screen.
 */
public enum LoanType {
    CAR(R.string.card_car_title, R.string.card_car_desc, "Car", R.drawable.ic_car, R.color.accent_car, true,
            R.drawable.bg_hero_car, R.string.hero_car_title, R.string.hero_car_subtitle),
    HOME(R.string.card_home_title, R.string.card_home_desc, "Home", R.drawable.ic_home, R.color.accent_home, true,
            R.drawable.bg_hero_home, R.string.hero_home_title, R.string.hero_home_subtitle),
    PERSONAL(R.string.card_personal_title, R.string.card_personal_desc, "Personal", R.drawable.ic_person, R.color.accent_personal, false,
            R.drawable.bg_hero_personal, R.string.hero_personal_title, R.string.hero_personal_subtitle),
    BIKE(R.string.card_bike_title, R.string.card_bike_desc, "Bike", R.drawable.ic_bike, R.color.accent_bike, true,
            R.drawable.bg_hero_bike, R.string.hero_bike_title, R.string.hero_bike_subtitle);

    private final int titleRes;
    private final int descRes;
    private final String noun;
    private final int iconRes;
    private final int accentColorRes;
    private final boolean showsPriceAndDownPayment;
    private final int heroBackgroundRes;
    private final int heroTitleRes;
    private final int heroSubtitleRes;

    LoanType(int titleRes, int descRes, String noun, int iconRes, int accentColorRes, boolean showsPriceAndDownPayment,
             int heroBackgroundRes, int heroTitleRes, int heroSubtitleRes) {
        this.titleRes = titleRes;
        this.descRes = descRes;
        this.noun = noun;
        this.iconRes = iconRes;
        this.accentColorRes = accentColorRes;
        this.showsPriceAndDownPayment = showsPriceAndDownPayment;
        this.heroBackgroundRes = heroBackgroundRes;
        this.heroTitleRes = heroTitleRes;
        this.heroSubtitleRes = heroSubtitleRes;
    }

    public int getTitleRes() {
        return titleRes;
    }

    public int getDescRes() {
        return descRes;
    }

    /** Short noun used to compose labels like "Total Car Price". */
    public String getNoun() {
        return noun;
    }

    public int getIconRes() {
        return iconRes;
    }

    public int getAccentColorRes() {
        return accentColorRes;
    }

    /** Personal loans skip Total Price / Down Payment and expose Loan Amount directly. */
    public boolean showsPriceAndDownPayment() {
        return showsPriceAndDownPayment;
    }

    public int getHeroBackgroundRes() {
        return heroBackgroundRes;
    }

    public int getHeroTitleRes() {
        return heroTitleRes;
    }

    public int getHeroSubtitleRes() {
        return heroSubtitleRes;
    }
}
