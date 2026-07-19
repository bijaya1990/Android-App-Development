package com.questionpapermaker.app.ui.sections

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowDownward
import androidx.compose.material.icons.filled.ArrowUpward
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.SectionWithQuestions
import com.questionpapermaker.app.engine.MarksEngine
import com.questionpapermaker.app.engine.NumberedQuestion
import com.questionpapermaker.app.ui.common.EnumDropdown

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
    onNext: () -> Unit
) {
    val numberedSections by viewModel.numberedSections.collectAsState()
    val issues by viewModel.validationIssues.collectAsState()
    var expandedIds by remember { mutableStateOf(setOf<String>()) }

    val rows = remember(numberedSections) {
        buildList {
            numberedSections.forEachIndexed { sectionIndex, ns ->
                val breakdown = MarksEngine.formatBreakdown(
                    SectionWithQuestions(ns.section, ns.questions.map { it.question })
                )
                add(SectionRow.Header(ns.section, breakdown, sectionIndex > 0, sectionIndex < numberedSections.size - 1))
                ns.questions.forEachIndexed { qIndex, nq ->
                    add(SectionRow.QuestionItem(nq, qIndex > 0, qIndex < ns.questions.size - 1))
                }
                add(SectionRow.AddQuestionButton(ns.section))
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Build Sections") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                }
            )
        },
        bottomBar = {
            Column {
                if (issues.isNotEmpty()) {
                    ValidationBanner(issueCount = issues.size)
                }
                Row(
                    modifier = Modifier.fillMaxWidth().padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    OutlinedButton(onClick = onBack) { Text("Back") }
                    Button(onClick = onNext) {
                        Icon(Icons.Default.Visibility, contentDescription = null)
                        Text("  Preview")
                    }
                }
            }
        }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding)) {
            LazyColumn(
                modifier = Modifier.weight(1f).padding(horizontal = 16.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                item { Spacer(modifier = Modifier.height(4.dp)) }
                items(rows, key = { row ->
                    when (row) {
                        is SectionRow.Header -> "header_${row.section.id}"
                        is SectionRow.QuestionItem -> "question_${row.numbered.question.id}"
                        is SectionRow.AddQuestionButton -> "add_${row.section.id}"
                    }
                }) { row ->
                    when (row) {
                        is SectionRow.Header -> SectionHeaderCard(
                            section = row.section,
                            breakdown = row.breakdown,
                            canMoveUp = row.canMoveUp,
                            canMoveDown = row.canMoveDown,
                            onUpdate = viewModel::updateSection,
                            onDelete = { viewModel.deleteSection(row.section) },
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
                            onMoveDown = { viewModel.moveQuestion(row.numbered.question, 1) }
                        )

                        is SectionRow.AddQuestionButton -> TextButton(onClick = { viewModel.addQuestion(row.section) }) {
                            Icon(Icons.Default.Add, contentDescription = null)
                            Text("  Add Question")
                        }
                    }
                }
                item {
                    OutlinedButton(
                        onClick = viewModel::addSection,
                        modifier = Modifier.fillMaxWidth().padding(vertical = 16.dp)
                    ) {
                        Icon(Icons.Default.Add, contentDescription = null)
                        Text("  Add Section")
                    }
                }
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
    onMoveUp: () -> Unit,
    onMoveDown: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = section.title,
                    onValueChange = { onUpdate(section.copy(title = it)) },
                    label = { Text("Section Title") },
                    singleLine = true,
                    modifier = Modifier.weight(1f)
                )
                IconButton(onClick = onMoveUp, enabled = canMoveUp) {
                    Icon(Icons.Default.ArrowUpward, contentDescription = "Move section up")
                }
                IconButton(onClick = onMoveDown, enabled = canMoveDown) {
                    Icon(Icons.Default.ArrowDownward, contentDescription = "Move section down")
                }
                IconButton(onClick = onDelete) {
                    Icon(Icons.Default.Delete, contentDescription = "Delete section", tint = MaterialTheme.colorScheme.error)
                }
            }

            OutlinedTextField(
                value = section.instruction.orEmpty(),
                onValueChange = { onUpdate(section.copy(instruction = it)) },
                label = { Text("Instruction (e.g. Answer any five)") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
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
                    modifier = Modifier.width(100.dp)
                )
            }

            Text(
                text = breakdown,
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.SemiBold,
                modifier = Modifier.padding(top = 8.dp)
            )
        }
    }
}
