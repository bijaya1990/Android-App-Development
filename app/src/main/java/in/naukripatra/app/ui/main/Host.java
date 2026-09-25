package in.naukripatra.app.ui.main;

import in.naukripatra.app.data.Job;
import in.naukripatra.app.data.JobQuery;

/** Navigation the tabs ask of MainActivity. */
public interface Host {
    void openJobs(JobQuery query);

    void openSearch();

    void openJob(Job job);

    void openPreferences();
}
