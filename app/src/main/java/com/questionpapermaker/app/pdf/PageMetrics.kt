package com.questionpapermaker.app.pdf

import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.model.MarginPreset
import com.questionpapermaker.app.data.model.PageOrientation

/** Resolved page geometry, in PDF points (1/72 inch), derived from a paper's layout settings. */
data class PageMetrics(
    val pageWidth: Float,
    val pageHeight: Float,
    val marginTop: Float,
    val marginBottom: Float,
    val marginLeft: Float,
    val marginRight: Float
) {
    val contentLeft: Float get() = marginLeft
    val contentRight: Float get() = pageWidth - marginRight
    val contentWidth: Float get() = contentRight - contentLeft
    val contentTop: Float get() = marginTop
    val contentBottom: Float get() = pageHeight - marginBottom

    companion object {
        fun from(paper: PaperEntity): PageMetrics {
            val isLandscape = paper.orientation == PageOrientation.LANDSCAPE
            val rawWidth = paper.pageSize.widthPt
            val rawHeight = paper.pageSize.heightPt
            val width = if (isLandscape) maxOf(rawWidth, rawHeight) else minOf(rawWidth, rawHeight)
            val height = if (isLandscape) minOf(rawWidth, rawHeight) else maxOf(rawWidth, rawHeight)

            val defaultMargin = paper.marginPreset.pointsPerSide
            val top = if (paper.marginPreset == MarginPreset.CUSTOM) paper.customMarginTopPt ?: defaultMargin else defaultMargin
            val bottom = if (paper.marginPreset == MarginPreset.CUSTOM) paper.customMarginBottomPt ?: defaultMargin else defaultMargin
            val left = if (paper.marginPreset == MarginPreset.CUSTOM) paper.customMarginLeftPt ?: defaultMargin else defaultMargin
            val right = if (paper.marginPreset == MarginPreset.CUSTOM) paper.customMarginRightPt ?: defaultMargin else defaultMargin

            return PageMetrics(width, height, top, bottom, left, right)
        }
    }
}
