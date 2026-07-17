package com.pdfimagetools.app.domain

import android.app.Activity
import com.pdfimagetools.app.core.ads.AdsManager
import com.pdfimagetools.app.core.notifications.NotificationHelper
import com.pdfimagetools.app.core.storage.SaveTarget
import com.pdfimagetools.app.core.storage.SavedFile
import com.pdfimagetools.app.core.storage.StorageManager
import com.pdfimagetools.app.data.RecentFilesStore
import com.pdfimagetools.app.ui.common.ProcessedOutput

/**
 * Shared "tap Save" pipeline used by every tool screen: show the rewarded interstitial (or skip
 * it if unavailable — the user is never blocked), write each output into public storage, record
 * it in Recent files, and raise a save notification.
 */
class SaveOrchestrator(
    private val storageManager: StorageManager,
    private val adsManager: AdsManager,
    private val notificationHelper: NotificationHelper,
    private val recentFilesStore: RecentFilesStore
) {
    suspend fun saveOutputs(
        activity: Activity,
        outputs: List<ProcessedOutput>,
        target: SaveTarget,
        toolName: String
    ): Map<String, SavedFile> {
        adsManager.awaitRewardedInterstitial(activity)

        val result = mutableMapOf<String, SavedFile>()
        outputs.forEach { output ->
            val savedFile = storageManager.saveFileToPublicStorage(
                source = output.file,
                displayName = output.displayName,
                mimeType = output.mimeType,
                target = target
            )
            recentFilesStore.add(
                displayName = savedFile.displayName,
                uriString = savedFile.uri.toString(),
                mimeType = savedFile.mimeType,
                sizeBytes = savedFile.sizeBytes,
                toolName = toolName
            )
            notificationHelper.notifyFileSaved(savedFile.displayName, savedFile.uri, savedFile.mimeType)
            result[output.file.absolutePath] = savedFile
        }
        return result
    }
}
