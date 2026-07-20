package com.questionpapermaker.app.ui.settings

/** One heading + body block of a legal document. */
data class LegalSection(val heading: String, val body: String)

/**
 * Privacy Policy / Terms of Use copy. Kept as plain data here (not fetched remotely) since the
 * app is offline-first -- these must be readable with no network connection.
 */
object LegalContent {

    const val LAST_UPDATED = "20 July 2026"

    val privacyPolicySections = listOf(
        LegalSection(
            "Overview",
            "Question Paper Maker (\"the App\") is built offline-first: every institution, exam, " +
                "section and question you create is stored only in the App's local database on " +
                "your own device. We do not require an account, we do not ask you to sign in, and " +
                "we do not transmit your exam papers, question banks, or institution logos to any " +
                "server. This policy explains what limited data does leave your device, and why."
        ),
        LegalSection(
            "Data You Create",
            "Paper details, sections, questions, layout preferences and any institution logo image " +
                "you upload are saved locally using Android's Room database and app-private storage. " +
                "This data stays on your device unless you deliberately choose to export, print or " +
                "share a generated PDF using the App's Share/Print actions -- at that point the file " +
                "is handed to whichever app or printer you selected, the same as sharing any other " +
                "file from your phone."
        ),
        LegalSection(
            "Advertising",
            "The App shows banner and interstitial advertisements served through Google AdMob. To " +
                "serve and measure ads, Google and its advertising partners may collect and process " +
                "data such as your advertising ID, device and app information, general location " +
                "(derived from IP address), and ad interaction data, in accordance with Google's own " +
                "privacy policy (https://policies.google.com/privacy) and, where applicable, its " +
                "Partner Policy for AdMob. You can review or reset your advertising ID, and opt out " +
                "of personalized ads, from your device's Settings → Privacy → Ads menu. Ads " +
                "are not shown while you are actively editing a paper -- only around the PDF export flow."
        ),
        LegalSection(
            "Permissions",
            "The App requests Internet and network-state access solely to load advertisements. It " +
                "does not request camera, contacts, location, microphone, or storage-wide permissions. " +
                "When you choose an institution logo image, Android's system file picker is used, and " +
                "the App is only granted access to that one file you selected."
        ),
        LegalSection(
            "Data Retention & Deletion",
            "Because everything is stored locally, you are always in control: deleting a paper inside " +
                "the App removes it immediately, and uninstalling the App removes the entire local " +
                "database and any cached files. We have no server copy to delete because none exists."
        ),
        LegalSection(
            "Children's Privacy",
            "The App is a productivity tool intended for teachers, professors and institutions, and is " +
                "not directed at children under 13. We do not knowingly collect personal information " +
                "from children. If the App is used in a classroom setting by a minor under adult " +
                "supervision, no personal data about that minor is collected by the App itself."
        ),
        LegalSection(
            "Changes to This Policy",
            "If this policy changes, the \"Last updated\" date below will change and, for material " +
                "changes, we will make reasonable efforts to surface a notice inside the App."
        ),
        LegalSection(
            "Contact",
            "Questions about this policy can be sent to the contact details on the Contact Us page."
        )
    )

    val termsOfUseSections = listOf(
        LegalSection(
            "Acceptance of Terms",
            "By installing or using Question Paper Maker (\"the App\"), you agree to these Terms of " +
                "Use. If you do not agree, please do not use the App."
        ),
        LegalSection(
            "License",
            "You are granted a personal, non-exclusive, non-transferable licence to use the App on " +
                "devices you own or control, for creating, formatting and exporting examination " +
                "papers and related documents."
        ),
        LegalSection(
            "Your Content",
            "You retain full ownership of every question, paper, template and institution asset you " +
                "create in the App. We claim no rights over your content -- it is stored locally on " +
                "your device and we never see it, use it, or sell it."
        ),
        LegalSection(
            "Advertising & Third-Party Content",
            "The App is supported by advertisements served via Google AdMob. Advertisements are " +
                "provided by third-party advertisers; we do not control, and are not responsible for, " +
                "the content of individual ads. Interacting with an ad may take you outside the App to " +
                "a third-party website or store listing."
        ),
        LegalSection(
            "Acceptable Use",
            "You agree not to use the App to create, store or distribute unlawful, infringing, or " +
                "academically dishonest material (for example, leaked or stolen examination content), " +
                "and not to attempt to reverse engineer, decompile, or interfere with the App's normal " +
                "operation."
        ),
        LegalSection(
            "Disclaimer of Warranty",
            "The App is provided \"as is\" without warranties of any kind, express or implied. While " +
                "we take reasonable care with formatting, page-break and export accuracy, you are " +
                "responsible for proofreading and verifying any document before printing or " +
                "distributing it for an actual examination."
        ),
        LegalSection(
            "Limitation of Liability",
            "To the maximum extent permitted by law, we are not liable for any indirect, incidental, " +
                "or consequential damages arising from your use of the App, including data loss, " +
                "printing errors, or examination scheduling issues."
        ),
        LegalSection(
            "Changes",
            "These Terms may be updated from time to time; continued use of the App after an update " +
                "constitutes acceptance of the revised Terms."
        ),
        LegalSection(
            "Governing Law",
            "These Terms are governed by the laws of India, without regard to its conflict of law " +
                "provisions."
        ),
        LegalSection(
            "Contact",
            "Questions about these Terms can be sent to the contact details on the Contact Us page."
        )
    )
}
