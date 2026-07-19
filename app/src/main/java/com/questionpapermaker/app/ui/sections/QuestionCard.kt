package com.questionpapermaker.app.ui.sections

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowDownward
import androidx.compose.material.icons.filled.ArrowUpward
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.ExpandLess
import androidx.compose.material.icons.filled.ExpandMore
import androidx.compose.material3.Card
import androidx.compose.material3.Checkbox
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.model.QuestionOption
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.data.model.SubQuestion
import com.questionpapermaker.app.engine.NumberedQuestion
import com.questionpapermaker.app.ui.common.EnumDropdown
import com.questionpapermaker.app.util.IdGenerator

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun QuestionCard(
    numbered: NumberedQuestion,
    expanded: Boolean,
    canMoveUp: Boolean,
    canMoveDown: Boolean,
    onToggleExpand: () -> Unit,
    onUpdate: (QuestionEntity) -> Unit,
    onDelete: () -> Unit,
    onDuplicate: () -> Unit,
    onMoveUp: () -> Unit,
    onMoveDown: () -> Unit
) {
    val question = numbered.question

    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "${numbered.displayNumber}.",
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(end = 8.dp)
                )
                Text(
                    text = question.text.ifBlank { "Untitled question" },
                    maxLines = 1,
                    modifier = Modifier.weight(1f),
                    style = MaterialTheme.typography.bodyMedium
                )
                Text(
                    text = "${question.marks}",
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.padding(horizontal = 8.dp)
                )
                IconButton(onClick = onToggleExpand) {
                    Icon(if (expanded) Icons.Default.ExpandLess else Icons.Default.ExpandMore, contentDescription = "Expand")
                }
            }

            if (expanded) {
                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

                EnumDropdown(
                    label = "Question Type",
                    value = question.questionType,
                    options = QuestionType.entries,
                    displayName = { it.displayName },
                    onSelect = { onUpdate(question.copy(questionType = it)) },
                    modifier = Modifier.fillMaxWidth()
                )

                if (question.questionType == QuestionType.CUSTOM) {
                    OutlinedTextField(
                        value = question.customTypeLabel.orEmpty(),
                        onValueChange = { onUpdate(question.copy(customTypeLabel = it)) },
                        label = { Text("Custom Type Label") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                    )
                }

                OutlinedTextField(
                    value = question.text,
                    onValueChange = { onUpdate(question.copy(text = it)) },
                    label = { Text("Question Text") },
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                )

                Row(
                    modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedTextField(
                        value = if (question.marks == 0.0) "" else question.marks.toString(),
                        onValueChange = { raw ->
                            val marks = raw.toDoubleOrNull() ?: 0.0
                            onUpdate(question.copy(marks = marks))
                        },
                        label = { Text("Marks") },
                        singleLine = true,
                        modifier = Modifier.width(120.dp)
                    )
                }

                if (question.questionType.supportsOptions) {
                    OptionsEditor(
                        options = question.options,
                        onChange = { onUpdate(question.copy(options = it)) }
                    )
                }

                SubQuestionsEditor(
                    subQuestions = question.subQuestions,
                    onChange = { onUpdate(question.copy(subQuestions = it)) }
                )

                InternalChoiceEditor(
                    question = question,
                    onUpdate = onUpdate
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

                Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                    IconButton(onClick = onMoveUp, enabled = canMoveUp) {
                        Icon(Icons.Default.ArrowUpward, contentDescription = "Move up")
                    }
                    IconButton(onClick = onMoveDown, enabled = canMoveDown) {
                        Icon(Icons.Default.ArrowDownward, contentDescription = "Move down")
                    }
                    IconButton(onClick = onDuplicate) {
                        Icon(Icons.Default.ContentCopy, contentDescription = "Duplicate")
                    }
                    IconButton(onClick = onDelete) {
                        Icon(Icons.Default.Delete, contentDescription = "Delete", tint = MaterialTheme.colorScheme.error)
                    }
                }
            }
        }
    }
}

@Composable
private fun OptionsEditor(options: List<QuestionOption>, onChange: (List<QuestionOption>) -> Unit) {
    Column(modifier = Modifier.padding(top = 8.dp)) {
        Text("Options", style = MaterialTheme.typography.labelLarge)
        options.forEachIndexed { index, option ->
            Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Checkbox(
                    checked = option.isCorrect,
                    onCheckedChange = { checked ->
                        onChange(options.toMutableList().also { it[index] = option.copy(isCorrect = checked) })
                    }
                )
                OutlinedTextField(
                    value = option.text,
                    onValueChange = { text ->
                        onChange(options.toMutableList().also { it[index] = option.copy(text = text) })
                    },
                    singleLine = true,
                    modifier = Modifier.weight(1f)
                )
                IconButton(onClick = { onChange(options.toMutableList().also { it.removeAt(index) }) }) {
                    Icon(Icons.Default.Close, contentDescription = "Remove option")
                }
            }
        }
        TextButton(onClick = { onChange(options + QuestionOption(id = IdGenerator.newId())) }) {
            Icon(Icons.Default.Add, contentDescription = null)
            Text(" Add Option")
        }
    }
}

@Composable
private fun SubQuestionsEditor(subQuestions: List<SubQuestion>, onChange: (List<SubQuestion>) -> Unit) {
    Column(modifier = Modifier.padding(top = 8.dp)) {
        Text("Sub-questions", style = MaterialTheme.typography.labelLarge)
        subQuestions.forEachIndexed { index, sub ->
            Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                OutlinedTextField(
                    value = sub.text,
                    onValueChange = { text ->
                        onChange(subQuestions.toMutableList().also { it[index] = sub.copy(text = text) })
                    },
                    singleLine = true,
                    modifier = Modifier.weight(1f),
                    label = { Text("(${('a' + index)})") }
                )
                IconButton(onClick = { onChange(subQuestions.toMutableList().also { it.removeAt(index) }) }) {
                    Icon(Icons.Default.Close, contentDescription = "Remove sub-question")
                }
            }
        }
        TextButton(onClick = { onChange(subQuestions + SubQuestion(id = IdGenerator.newId())) }) {
            Icon(Icons.Default.Add, contentDescription = null)
            Text(" Add Sub-question")
        }
    }
}

@Composable
private fun InternalChoiceEditor(question: QuestionEntity, onUpdate: (QuestionEntity) -> Unit) {
    Column(modifier = Modifier.padding(top = 8.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text("Internal Choice (OR)", style = MaterialTheme.typography.labelLarge)
            Switch(
                checked = question.hasInternalChoice,
                onCheckedChange = { onUpdate(question.copy(hasInternalChoice = it)) }
            )
        }
        if (question.hasInternalChoice) {
            OutlinedTextField(
                value = question.alternativeText.orEmpty(),
                onValueChange = { onUpdate(question.copy(alternativeText = it)) },
                label = { Text("Alternative Question (OR)") },
                modifier = Modifier.fillMaxWidth().padding(top = 4.dp)
            )
            OutlinedTextField(
                value = question.alternativeMarks?.toString().orEmpty(),
                onValueChange = { raw -> onUpdate(question.copy(alternativeMarks = raw.toDoubleOrNull())) },
                label = { Text("Alternative Marks (optional, defaults to same marks)") },
                singleLine = true,
                modifier = Modifier.width(260.dp).padding(top = 4.dp)
            )
        }
    }
}
