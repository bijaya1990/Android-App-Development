package com.questionpapermaker.app.ui.sections

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowDownward
import androidx.compose.material.icons.filled.ArrowUpward
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.DragIndicator
import androidx.compose.material.icons.filled.ExpandLess
import androidx.compose.material.icons.filled.ExpandMore
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Switch
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.QuestionOption
import com.questionpapermaker.app.data.model.QuestionType
import com.questionpapermaker.app.data.model.SubQuestion
import com.questionpapermaker.app.engine.NumberedQuestion
import com.questionpapermaker.app.ui.common.Chip
import com.questionpapermaker.app.ui.common.EnumDropdown
import com.questionpapermaker.app.util.IdGenerator

/** Question types where reserving blank space for the student's working/answer is useful. */
private val WORKING_AREA_TYPES = setOf(
    QuestionType.NUMERICAL,
    QuestionType.PRACTICAL,
    QuestionType.PROGRAMMING,
    QuestionType.DIAGRAM_BASED,
    QuestionType.LONG_ANSWER,
    QuestionType.ESSAY
)

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

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outline),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.Top) {
                Icon(
                    Icons.Default.DragIndicator,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.outline,
                    modifier = Modifier.padding(top = 4.dp, end = 4.dp)
                )
                Text(
                    "${numbered.displayNumber}.",
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(top = 4.dp, end = 6.dp)
                )
                Column(modifier = Modifier.weight(1f)) {
                    BorderlessTextField(
                        value = question.text,
                        placeholder = "Type the question...",
                        onValueChange = { onUpdate(question.copy(text = it)) },
                        singleLine = !expanded
                    )
                    if (expanded) {
                        Row(modifier = Modifier.padding(top = 6.dp)) {
                            Chip(text = question.questionType.displayName)
                        }
                    }
                }
                MarksBox(
                    marks = question.marks,
                    editable = expanded,
                    onMarksChange = { onUpdate(question.copy(marks = it)) }
                )
                IconButton(onClick = onToggleExpand) {
                    Icon(if (expanded) Icons.Default.ExpandLess else Icons.Default.ExpandMore, contentDescription = "Expand")
                }
            }

            if (expanded) {
                HorizontalDivider(modifier = Modifier.padding(vertical = 10.dp))

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
                        shape = MaterialTheme.shapes.small,
                        modifier = Modifier.fillMaxWidth().padding(top = 8.dp)
                    )
                }

                if (question.questionType.supportsOptions) {
                    OptionsEditor(
                        options = question.options,
                        onChange = { onUpdate(question.copy(options = it)) }
                    )
                }

                if (question.questionType in WORKING_AREA_TYPES) {
                    ToggleRow(
                        label = "Show working area for student's answer",
                        checked = question.showWorkingArea,
                        onCheckedChange = { onUpdate(question.copy(showWorkingArea = it)) }
                    )
                    if (question.showWorkingArea) {
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(72.dp)
                                .padding(top = 4.dp)
                                .border(
                                    BorderStroke(1.dp, MaterialTheme.colorScheme.outline),
                                    RoundedCornerShape(6.dp)
                                ),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                "Student working area",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                SubQuestionsEditor(
                    subQuestions = question.subQuestions,
                    onChange = { onUpdate(question.copy(subQuestions = it)) }
                )

                InternalChoiceEditor(
                    question = question,
                    onUpdate = onUpdate
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 10.dp))

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
private fun MarksBox(marks: Double, editable: Boolean, onMarksChange: (Double) -> Unit) {
    if (editable) {
        OutlinedTextField(
            value = if (marks == 0.0) "" else formatMarksInput(marks),
            onValueChange = { raw -> onMarksChange(raw.toDoubleOrNull() ?: 0.0) },
            label = { Text("Marks") },
            singleLine = true,
            shape = MaterialTheme.shapes.small,
            modifier = Modifier.width(90.dp).padding(start = 8.dp)
        )
    } else {
        Column(
            modifier = Modifier
                .padding(start = 8.dp)
                .border(BorderStroke(1.dp, MaterialTheme.colorScheme.outline), RoundedCornerShape(4.dp))
                .padding(horizontal = 8.dp, vertical = 4.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(formatMarksInput(marks), fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodyMedium)
            Text(
                if (marks == 1.0) "Mark" else "Marks",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

private fun formatMarksInput(marks: Double): String =
    if (marks == marks.toLong().toDouble()) marks.toLong().toString() else marks.toString()

@Composable
private fun BorderlessTextField(
    value: String,
    placeholder: String,
    singleLine: Boolean,
    onValueChange: (String) -> Unit
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        placeholder = { Text(placeholder, color = MaterialTheme.colorScheme.onSurfaceVariant) },
        singleLine = singleLine,
        maxLines = if (singleLine) 1 else 6,
        colors = OutlinedTextFieldDefaults.colors(
            unfocusedContainerColor = Color.Transparent,
            focusedContainerColor = Color.Transparent,
            unfocusedBorderColor = Color.Transparent,
            focusedBorderColor = MaterialTheme.colorScheme.primary,
            unfocusedTextColor = MaterialTheme.colorScheme.onSurface,
            focusedTextColor = MaterialTheme.colorScheme.onSurface
        ),
        textStyle = MaterialTheme.typography.bodyLarge,
        modifier = Modifier.fillMaxWidth()
    )
}

@Composable
private fun ToggleRow(label: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Text(label, style = MaterialTheme.typography.bodyMedium)
        Switch(checked = checked, onCheckedChange = onCheckedChange)
    }
}

@Composable
private fun OptionsEditor(options: List<QuestionOption>, onChange: (List<QuestionOption>) -> Unit) {
    Column(modifier = Modifier.padding(top = 8.dp)) {
        Text("Options", style = MaterialTheme.typography.labelLarge)
        options.forEachIndexed { index, option ->
            Row(modifier = Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                RadioButton(
                    selected = option.isCorrect,
                    onClick = {
                        onChange(options.mapIndexed { i, o -> o.copy(isCorrect = i == index) })
                    }
                )
                Text(
                    text = "${NumberingStyle.UPPER_ALPHA.render(index + 1)}.",
                    modifier = Modifier.padding(end = 4.dp),
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                OutlinedTextField(
                    value = option.text,
                    onValueChange = { text ->
                        onChange(options.toMutableList().also { it[index] = option.copy(text = text) })
                    },
                    singleLine = true,
                    shape = MaterialTheme.shapes.small,
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
                    shape = MaterialTheme.shapes.small,
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
        ToggleRow(
            label = "Internal Choice (OR)",
            checked = question.hasInternalChoice,
            onCheckedChange = { onUpdate(question.copy(hasInternalChoice = it)) }
        )
        if (question.hasInternalChoice) {
            OutlinedTextField(
                value = question.alternativeText.orEmpty(),
                onValueChange = { onUpdate(question.copy(alternativeText = it)) },
                label = { Text("Alternative Question (OR)") },
                shape = MaterialTheme.shapes.small,
                modifier = Modifier.fillMaxWidth().padding(top = 4.dp)
            )
            OutlinedTextField(
                value = question.alternativeMarks?.let { formatMarksInput(it) }.orEmpty(),
                onValueChange = { raw -> onUpdate(question.copy(alternativeMarks = raw.toDoubleOrNull())) },
                label = { Text("Alternative Marks (optional)") },
                singleLine = true,
                shape = MaterialTheme.shapes.small,
                modifier = Modifier.width(220.dp).padding(top = 4.dp)
            )
        }
    }
}
