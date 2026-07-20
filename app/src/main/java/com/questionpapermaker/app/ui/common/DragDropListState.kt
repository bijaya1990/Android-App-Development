package com.questionpapermaker.app.ui.common

import androidx.compose.foundation.lazy.LazyListState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.geometry.Offset

/**
 * Drives long-press drag-and-drop reordering for a [LazyListState]-backed list whose backing
 * list is reordered live (see [onMove]) so the LazyColumn's own key-based diffing/animation
 * takes care of the visual swap -- this class only tracks which index is currently being
 * dragged and how far it has been dragged, so the dragged row can be drawn glued to the finger
 * via [draggingItemOffset] while everything else just falls into its new place.
 *
 * [canDragTo] lets the caller restrict which target indices a drag may land on (e.g. "only
 * within the same section") -- swaps outside that range are silently ignored until a valid
 * target is under the finger.
 */
class DragDropListState(
    private val listState: LazyListState,
    private val canDragTo: (from: Int, to: Int) -> Boolean,
    private val onMove: (from: Int, to: Int) -> Unit
) {
    var draggingItemIndex by mutableStateOf<Int?>(null)
        private set

    private var draggingItemInitialOffset by mutableFloatStateOf(0f)
    private var draggedDistance by mutableFloatStateOf(0f)

    private val draggingItemLayoutInfo
        get() = listState.layoutInfo.visibleItemsInfo.firstOrNull { it.index == draggingItemIndex }

    /** How far (in px, vertical) the dragged row's natural layout slot is from where it should currently be drawn. */
    val draggingItemOffset: Float
        get() = draggingItemLayoutInfo?.let { item ->
            draggingItemInitialOffset + draggedDistance - item.offset
        } ?: 0f

    fun isDragging(index: Int): Boolean = draggingItemIndex == index

    fun onDragStart(index: Int) {
        val item = listState.layoutInfo.visibleItemsInfo.firstOrNull { it.index == index } ?: return
        draggingItemIndex = index
        draggingItemInitialOffset = item.offset.toFloat()
        draggedDistance = 0f
    }

    fun onDrag(delta: Offset) {
        draggedDistance += delta.y

        val currentIndex = draggingItemIndex ?: return
        val hovered = draggingItemLayoutInfo ?: return
        val startOffset = hovered.offset + draggingItemOffset
        val endOffset = startOffset + hovered.size
        val middle = startOffset + (endOffset - startOffset) / 2f

        val target = listState.layoutInfo.visibleItemsInfo.firstOrNull { candidate ->
            candidate.index != currentIndex &&
                middle >= candidate.offset &&
                middle <= candidate.offset + candidate.size
        }

        if (target != null && canDragTo(currentIndex, target.index)) {
            onMove(currentIndex, target.index)
            draggingItemIndex = target.index
        }
    }

    fun onDragEnd() {
        draggingItemIndex = null
        draggingItemInitialOffset = 0f
        draggedDistance = 0f
    }
}

@Composable
fun rememberDragDropListState(
    listState: LazyListState,
    canDragTo: (from: Int, to: Int) -> Boolean = { _, _ -> true },
    onMove: (from: Int, to: Int) -> Unit
): DragDropListState {
    val currentCanDragTo by rememberUpdatedState(canDragTo)
    val currentOnMove by rememberUpdatedState(onMove)
    return remember(listState) {
        DragDropListState(
            listState = listState,
            canDragTo = { from, to -> currentCanDragTo(from, to) },
            onMove = { from, to -> currentOnMove(from, to) }
        )
    }
}
