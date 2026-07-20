package com.questionpapermaker.app.ui.sections

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.border
import androidx.compose.foundation.gestures.detectDragGesturesAfterLongPress
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowDownward
import androidx.compose.material.icons.filled.ArrowUpward
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material.icons.filled.ContentPaste
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.PictureAsPdf
import androidx.compose.material.icons.filled.Redo
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Undo
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExtendedFloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.SectionWithQuestions
import com.questionpapermaker.app.engine.MarksEngine
import com.questionpapermaker.app.engine.NumberedQuestion
import com.questionpapermaker.app.ui.common.EnumDropdown
import com.questionpapermaker.app.ui.common.rememberDragDropListState
import kotlin.math.roundToInt

/** Flattened row model so the whole paper (sections + questions) renders in a single LazyColumn -- only visible rows are composed, which is what keeps 500+ question papers smooth. */
private sealed interface SectionRow {
    data class Header(val section: SectionEntity, val breakdown: String, val canMoveUp: Boolean, val canMoveDown: Boolean) : SectionRow
    data class QuestionItem(val numbered: NumberedQuestion, val canMoveUp: Boolean, val canMoveDown: Boolean) : SectionRow
    data class AddQuestionButton(val section: SectionEntity) : SectionRow
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SectionBuilderScreen(
    viewModel: SectionBuilderViewModel,
    onBack: () -> Unit,
    onNext: () -> Unit,
    onSmartPaste: () -> Unit
) {
    val numberedSections by viewModel.numberedSections.collectAsState()
    val issues by viewModel.validationIssues.collectAsState()
    val canUndo by viewModel.canUndo.collectAsState()
    val canRedo by viewModel.canRedo.collectAsState()
    var expandedIds by remember { mutableStateOf(setOf<String>()) }

    // While a question is being dragged, its section's question order is driven by this local
    // override (a list of question ids) instead of the database order -- committed to the
    // repository only once the drag ends, so a cancelled/aborted drag never writes anything.
    var draggingSectionId by remember { mutableStateOf<String?>(null) }
    var dragOverrideOrder by remember { mutableStateOf<List<String>?>(null) }

    var searchActive by remember { mutableStateOf(false) }
    var searchQuery by remember { mutableStateOf("") }
    val trimmedQuery = searchQuery.trim()

    val rows = remember(numberedSections, draggingSectionId, dragOverrideOrder, trimmedQuery) {
        buildList {
            numberedSections.forEachIndexed { sectionIndex, ns ->
                val breakdown = MarksEngine.formatBreakdown(
                    SectionWithQuestions(ns.section, ns.questions.map { it.question })
                )

                val overrideOrder = dragOverrideOrder
                val orderedQuestions = if (ns.section.id == draggingSectionId && overrideOrder != null) {
                    val byId = ns.questions.associateBy { it.question.id }
                    overrideOrder.mapNotNull { byId[it] }
                } else {
                    ns.questions
                }
                val visibleQuestions = if (trimmedQuery.isEmpty()) {
                    orderedQuestions
                } else {
                    orderedQuestions.filter { it.question.text.contains(trimmedQuery, ignoreCase = true) }
                }
                if (trimmedQuery.isNotEmpty() && visibleQuestions.isEmpty()) return@forEachIndexed

                add(SectionRow.Header(ns.section, breakdown, sectionIndex > 0, sectionIndex < numberedSections.size - 1))
                visibleQuestions.forEachIndexed { qIndex, nq ->
                    add(SectionRow.QuestionItem(nq, qIndex > 0, qIndex < visibleQuestions.size - 1))
                }
                if (trimmedQuery.isEmpty()) {
                    add(SectionRow.AddQuestionButton(ns.section))
                }
            }
        }
    }

    val listState = rememberLazyListState()
    val dragDropState = rememberDragDropListState(
        listState = listState,
        canDragTo = { from, to ->
            val fromRow = rows.getOrNull(from)
            val toRow = rows.getOrNull(to)
            fromRow is SectionRow.QuestionItem && toRow is SectionRow.QuestionItem &&
                fromRow.numbered.question.sectionId == toRow.numbered.question.sectionId
        },
        onMove = { from, to ->
            val fromRow = rows.getOrNull(from) as? SectionRow.QuestionItem
            val toRow = rows.getOrNull(to) as? SectionRow.QuestionItem
            if (fromRow != null && toRow != null) {
                val sectionId = fromRow.numbered.question.sectionId
                val baseOrder = dragOverrideOrder ?: numberedSections
                    .firstOrNull { it.section.id == sectionId }
                    ?.questions?.map { it.question.id }
                if (baseOrder != null) {
                    val fromPos = baseOrder.indexOf(fromRow.numbered.question.id)
                    val toPos = baseOrder.indexOf(toRow.numbered.question.id)
                    if (fromPos != -1 && toPos != -1) {
                        draggingSectionId = sectionId
                        dragOverrideOrder = baseOrder.toMutableList().apply { add(toPos, removeAt(fromPos)) }
                    }
                }
            }
        }
    )

    fun commitDrag() {
        val sectionId = draggingSectionId
        val order = dragOverrideOrder
        if (sectionId != null && order != null) {
            viewModel.reorderQuestions(sectionId, order)
        }
        draggingSectionId = null
        dragOverrideOrder = null
        dragDropState.onDragEnd()
    }

    fun expand(id: String) {
        expandedIds = expandedIds + id
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    if (searchActive) {
                        OutlinedTextField(
                            value = searchQuery,
                            onValueChange = { searchQuery = it },
                            placeholder = { Text("Search questions in this paper...") },
                            singleLine = true,
                            colors = OutlinedTextFieldDefaults.colors(
                                unfocusedBorderColor = Color.Transparent,
                                focusedBorderColor = Color.Transparent
                            ),
                            modifier = Modifier.fillMaxWidth()
                        )
                    } else {
                        Text("Question Editor")
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                },
                actions = {
                    if (searchActive) {
                        IconButton(onClick = { searchActive = false; searchQuery = "" }) {
                            Icon(Icons.Default.Close, contentDescription = "Close search")
                        }
                    } else {
                        IconButton(onClick = viewModel::undo, enabled = canUndo) {
                            Icon(Icons.Default.Undo, contentDescription = "Undo")
                        }
                        IconButton(onClick = viewModel::redo, enabled = canRedo) {
                            Icon(Icons.Default.Redo, contentDescription = "Redo")
                        }
                        IconButton(onClick = { searchActive = true }) {
                            Icon(Icons.Default.Search, contentDescription = "Search questions")
                        }
                        IconButton(onClick = onSmartPaste) {
                            Icon(Icons.Default.ContentPaste, contentDescription = "Smart Paste")
                        }
                        OutlinedButton(onClick = onNext, modifier = Modifier.padding(end = 8.dp), shape = MaterialTheme.shapes.large) {
                            Icon(Icons.Default.Visibility, contentDescription = null, modifier = Modifier.height(18.dp))
                            Text("  Live Preview")
                        }
                    }
                }
            )
        },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                text = { Text("Add Question") },
                icon = { Icon(Icons.Default.Add, contentDescription = null) },
                onClick = {
                    val targetSection = numberedSections.lastOrNull()?.section
                    if (targetSection != null) {
                        viewModel.addQuestion(targetSection) { newId -> expand(newId) }
                    } else {
                        viewModel.addSection()
                    }
                }
            )
        },
        bottomBar = {
            Column(modifier = Modifier.navigationBarsPadding()) {
                if (issues.isNotEmpty()) {
                    ValidationBanner(issueCount = issues.size)
                }
                Row(
                    modifier = Modifier.fillMaxWidth().padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    OutlinedButton(onClick = onBack, shape = MaterialTheme.shapes.small) {
                        Text("Back to Layout")
                    }
                    androidx.compose.material3.Button(onClick = onNext, shape = MaterialTheme.shapes.small) {
                        Icon(Icons.Default.PictureAsPdf, contentDescription = null, modifier = Modifier.height(18.dp))
                        Text("  Preview PDF")
                    }
                }
            }
        }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding)) {
            LazyColumn(
                state = listState,
                modifier = Modifier.weight(1f).padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                item { Spacer(modifier = Modifier.height(4.dp)) }
                itemsIndexed(rows, key = { _, row ->
                    when (row) {
                        is SectionRow.Header -> "header_${row.section.id}"
                        is SectionRow.QuestionItem -> "question_${row.numbered.question.id}"
                        is SectionRow.AddQuestionButton -> "add_${row.section.id}"
                    }
                }) { index, row ->
                    val currentIndex by rememberUpdatedState(index)
                    val isDraggingThis = row is SectionRow.QuestionItem && dragDropState.isDragging(index)

                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .offset {
                                IntOffset(x = 0, y = if (isDraggingThis) dragDropState.draggingItemOffset.roundToInt() else 0)
                            }
                            .animateItem()
                    ) {
                        when (row) {
                            is SectionRow.Header -> SectionHeaderCard(
                                section = row.section,
                                breakdown = row.breakdown,
                                canMoveUp = row.canMoveUp,
                                canMoveDown = row.canMoveDown,
                                onUpdate = viewModel::updateSection,
                                onDelete = { viewModel.deleteSection(row.section) },
                                onDuplicate = { viewModel.duplicateSection(row.section) },
                                onMoveUp = { viewModel.moveSection(row.section, -1) },
                                onMoveDown = { viewModel.moveSection(row.section, 1) }
                            )

                            is SectionRow.QuestionItem -> QuestionCard(
                                numbered = row.numbered,
                                expanded = row.numbered.question.id in expandedIds,
                                canMoveUp = row.canMoveUp,
                                canMoveDown = row.canMoveDown,
                                onToggleExpand = {
                                    val id = row.numbered.question.id
                                    expandedIds = if (id in expandedIds) expandedIds - id else expandedIds + id
                                },
                                onUpdate = viewModel::updateQuestion,
                                onDelete = { viewModel.deleteQuestion(row.numbered.question) },
                                onDuplicate = { viewModel.duplicateQuestion(row.numbered.question) },
                                onMoveUp = { viewModel.moveQuestion(row.numbered.question, -1) },
                                onMoveDown = { viewModel.moveQuestion(row.numbered.question, 1) },
                                dragHandleModifier = if (trimmedQuery.isNotEmpty()) {
                                    Modifier
                                } else Modifier.pointerInput(row.numbered.question.id) {
                                    detectDragGesturesAfterLongPress(
                                        onDragStart = { dragDropState.onDragStart(currentIndex) },
                                        onDrag = { change, dragAmount ->
                                            change.consume()
                                            dragDropState.onDrag(dragAmount)
                                        },
                                        onDragEnd = { commitDrag() },
                                        onDragCancel = { commitDrag() }
                                    )
                                }
                            )

                            is SectionRow.AddQuestionButton -> Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .border(
                                        BorderStroke(1.dp, MaterialTheme.colorScheme.outline),
                                        RoundedCornerShape(8.dp)
                                    )
                            ) {
                                TextButton(
                                    onClick = { viewModel.addQuestion(row.section) { newId -> expand(newId) } },
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Icon(Icons.Default.Add, contentDescription = null)
                                    Text("  Add another question to ${row.section.title.ifBlank { "this section" }}")
                                }
                            }
                        }
                    }
                }
                if (trimmedQuery.isNotEmpty() && rows.none { it is SectionRow.QuestionItem }) {
                    item {
                        Text(
                            "No questions match \"$trimmedQuery\".",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.fillMaxWidth().padding(vertical = 24.dp)
                        )
                    }
                }
                item {
                    OutlinedButton(
                        onClick = viewModel::addSection,
                        modifier = Modifier.fillMaxWidth().padding(vertical = 16.dp),
                        shape = MaterialTheme.shapes.small
                    ) {
                        Icon(Icons.Default.Add, contentDescription = null)
                        Text("  Add Section")
                    }
                }
                item { Spacer(modifier = Modifier.height(72.dp)) }
            }
        }
    }
}

@Composable
private fun ValidationBanner(issueCount: Int) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(Icons.Default.WarningAmber, contentDescription = null, tint = MaterialTheme.colorScheme.error)
        Text(
            "  $issueCount item${if (issueCount == 1) "" else "s"} need attention before export",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.error
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun SectionHeaderCard(
    section: SectionEntity,
    breakdown: String,
    canMoveUp: Boolean,
    canMoveDown: Boolean,
    onUpdate: (SectionEntity) -> Unit,
    onDelete: () -> Unit,
    onDuplicate: () -> Unit,
    onMoveUp: () -> Unit,
    onMoveDown: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.35f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = section.title,
                    onValueChange = { onUpdate(section.copy(title = it)) },
                    textStyle = MaterialTheme.typography.headlineSmall.copy(fontWeight = FontWeight.Bold),
                    colors = OutlinedTextFieldDefaults.colors(
                        unfocusedContainerColor = Color.Transparent,
                        focusedContainerColor = Color.Transparent,
                        unfocusedBorderColor = Color.Transparent,
                        focusedBorderColor = MaterialTheme.colorScheme.primary
                    ),
                    singleLine = true,
                    modifier = Modifier.weight(1f)
                )
                IconButton(onClick = onMoveUp, enabled = canMoveUp) {
                    Icon(Icons.Default.ArrowUpward, contentDescription = "Move section up")
                }
                IconButton(onClick = onMoveDown, enabled = canMoveDown) {
                    Icon(Icons.Default.ArrowDownward, contentDescription = "Move section down")
                }
                IconButton(onClick = onDuplicate) {
                    Icon(Icons.Default.ContentCopy, contentDescription = "Duplicate section")
                }
                IconButton(onClick = onDelete) {
                    Icon(Icons.Default.Delete, contentDescription = "Delete section", tint = MaterialTheme.colorScheme.error)
                }
            }

            OutlinedTextField(
                value = section.instruction.orEmpty(),
                onValueChange = { onUpdate(section.copy(instruction = it)) },
                placeholder = { Text("Answer all questions in this section.") },
                colors = OutlinedTextFieldDefaults.colors(
                    unfocusedContainerColor = Color.Transparent,
                    focusedContainerColor = Color.Transparent,
                    unfocusedBorderColor = Color.Transparent,
                    focusedBorderColor = MaterialTheme.colorScheme.primary
                ),
                singleLine = true,
                modifier = Modifier.fillMaxWidth()
            )

            Row(
                modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                EnumDropdown(
                    label = "Numbering",
                    value = section.numberingStyle,
                    options = NumberingStyle.entries,
                    displayName = { it.displayName },
                    onSelect = { onUpdate(section.copy(numberingStyle = it)) },
                    modifier = Modifier.weight(1f)
                )
                OutlinedTextField(
                    value = section.defaultMarksPerQuestion?.toString().orEmpty(),
                    onValueChange = { raw -> onUpdate(section.copy(defaultMarksPerQuestion = raw.toDoubleOrNull())) },
                    label = { Text("Marks/Q") },
                    singleLine = true,
                    shape = MaterialTheme.shapes.small,
                    modifier = Modifier.width(100.dp)
                )
            }

            Text(
                text = breakdown,
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.primary,
                modifier = Modifier.padding(top = 8.dp)
            )
        }
    }
}
