package com.questionpapermaker.app.ui.smartpaste

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ContentPaste
import androidx.compose.material.icons.filled.UploadFile
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.docx.DocxTextExtractor
import com.questionpapermaker.app.engine.ParsedQuestion
import com.questionpapermaker.app.engine.ParsedSection
import com.questionpapermaker.app.pdf.PdfTextExtractor
import com.questionpapermaker.app.ui.common.Chip
import com.questionpapermaker.app.ui.common.FormCard
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.BufferedReader
import java.io.InputStreamReader

@Composable
fun SmartPasteScreen(
    viewModel: SmartPasteViewModel,
    pdfTextExtractor: PdfTextExtractor,
    onBack: () -> Unit,
    onImported: () -> Unit
) {
    val step by viewModel.step.collectAsState()
    when (step) {
        SmartPasteStep.INPUT -> SmartPasteInputStep(viewModel = viewModel, pdfTextExtractor = pdfTextExtractor, onBack = onBack)
        SmartPasteStep.PREVIEW -> ImportPreviewStep(
            viewModel = viewModel,
            onBack = viewModel::backToInput,
            onImported = onImported
        )
    }
}

private enum class ImportFileKind { TXT, DOCX, PDF }

private fun detectFileKind(context: Context, uri: Uri): ImportFileKind {
    val mime = context.contentResolver.getType(uri)
    when (mime) {
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document" -> return ImportFileKind.DOCX
        "application/pdf" -> return ImportFileKind.PDF
        "text/plain" -> return ImportFileKind.TXT
    }
    val name = context.contentResolver.query(uri, arrayOf(OpenableColumns.DISPLAY_NAME), null, null, null)
        ?.use { cursor ->
            if (cursor.moveToFirst()) {
                val index = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                if (index >= 0) cursor.getString(index) else null
            } else {
                null
            }
        }
        .orEmpty()
        .lowercase()
    return when {
        name.endsWith(".docx") -> ImportFileKind.DOCX
        name.endsWith(".pdf") -> ImportFileKind.PDF
        else -> ImportFileKind.TXT
    }
}

private fun extractTextFromUri(context: Context, uri: Uri, pdfTextExtractor: PdfTextExtractor): String {
    val kind = detectFileKind(context, uri)
    return context.contentResolver.openInputStream(uri)?.use { stream ->
        when (kind) {
            ImportFileKind.DOCX -> DocxTextExtractor.extractText(stream)
            ImportFileKind.PDF -> pdfTextExtractor.extractText(stream)
            ImportFileKind.TXT -> BufferedReader(InputStreamReader(stream, Charsets.UTF_8)).readText()
        }
    }.orEmpty()
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun SmartPasteInputStep(
    viewModel: SmartPasteViewModel,
    pdfTextExtractor: PdfTextExtractor,
    onBack: () -> Unit
) {
    val rawText by viewModel.rawText.collectAsState()
    val context = LocalContext.current
    val clipboardManager = LocalClipboardManager.current
    val coroutineScope = rememberCoroutineScope()
    var isImportingFile by remember { mutableStateOf(false) }
    var importError by remember { mutableStateOf<String?>(null) }

    val filePickerLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.OpenDocument()
    ) { uri: Uri? ->
        if (uri == null) return@rememberLauncherForActivityResult
        isImportingFile = true
        importError = null
        coroutineScope.launch {
            val text = withContext(Dispatchers.IO) {
                runCatching { extractTextFromUri(context, uri, pdfTextExtractor) }
            }
            isImportingFile = false
            text.onSuccess { extracted ->
                if (extracted.isBlank()) {
                    importError = "No readable text was found in that file."
                } else {
                    viewModel.onTextChange(extracted)
                }
            }.onFailure {
                importError = "Couldn't read that file. Try a .txt, .docx or .pdf file."
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Smart Paste") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                }
            )
        },
        bottomBar = {
            Row(modifier = Modifier.fillMaxWidth().navigationBarsPadding().padding(16.dp)) {
                Button(
                    onClick = viewModel::parse,
                    enabled = rawText.isNotBlank(),
                    modifier = Modifier.fillMaxWidth(),
                    shape = MaterialTheme.shapes.small
                ) {
                    Text("Parse & Preview")
                }
            }
        }
    ) { padding ->
        Column(
            modifier = Modifier.fillMaxSize().padding(padding).padding(16.dp)
        ) {
            Text(
                "Paste questions copied from ChatGPT, Claude, Gemini, Word, Google Docs, a PDF, or a plain text file. " +
                    "Question wording is never changed -- only numbering, section headings and marks are auto-detected.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(modifier = Modifier.height(12.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedButton(
                    onClick = {
                        clipboardManager.getText()?.text?.let { viewModel.onTextChange(it) }
                    },
                    modifier = Modifier.weight(1f),
                    shape = MaterialTheme.shapes.small
                ) {
                    Icon(Icons.Default.ContentPaste, contentDescription = null, modifier = Modifier.height(18.dp))
                    Text("  Paste Clipboard")
                }
                OutlinedButton(
                    onClick = {
                        filePickerLauncher.launch(
                            arrayOf(
                                "text/plain",
                                "application/pdf",
                                "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            )
                        )
                    },
                    enabled = !isImportingFile,
                    modifier = Modifier.weight(1f),
                    shape = MaterialTheme.shapes.small
                ) {
                    if (isImportingFile) {
                        CircularProgressIndicator(modifier = Modifier.height(18.dp), strokeWidth = 2.dp)
                    } else {
                        Icon(Icons.Default.UploadFile, contentDescription = null, modifier = Modifier.height(18.dp))
                        Text("  Import File")
                    }
                }
            }
            if (importError != null) {
                Text(
                    importError.orEmpty(),
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.error,
                    modifier = Modifier.padding(top = 6.dp)
                )
            }
            Text(
                "Supports .txt, .docx and .pdf files.",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(top = 6.dp)
            )
            Spacer(modifier = Modifier.height(12.dp))
            OutlinedTextField(
                value = rawText,
                onValueChange = viewModel::onTextChange,
                modifier = Modifier.fillMaxWidth().weight(1f).heightIn(min = 220.dp),
                placeholder = { Text("Paste your questions here...") }
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ImportPreviewStep(
    viewModel: SmartPasteViewModel,
    onBack: () -> Unit,
    onImported: () -> Unit
) {
    val result by viewModel.result.collectAsState()
    val excludedSections by viewModel.excludedSections.collectAsState()
    val excludedQuestions by viewModel.excludedQuestions.collectAsState()
    val importing by viewModel.importing.collectAsState()
    val sections = result?.sections.orEmpty()
    val warnings = result?.warnings.orEmpty()
    val selectedCount = viewModel.selectedQuestionCount()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Import Preview") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                }
            )
        },
        bottomBar = {
            Column(modifier = Modifier.navigationBarsPadding()) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        "$selectedCount question${if (selectedCount == 1) "" else "s"} selected",
                        style = MaterialTheme.typography.bodyMedium
                    )
                    Button(
                        onClick = { viewModel.confirmImport(onImported) },
                        enabled = selectedCount > 0 && !importing,
                        shape = MaterialTheme.shapes.small
                    ) {
                        if (importing) {
                            CircularProgressIndicator(modifier = Modifier.height(18.dp), strokeWidth = 2.dp)
                        } else {
                            Text("Import into Paper")
                        }
                    }
                }
            }
        }
    ) { padding ->
        LazyColumn(
            modifier = Modifier.fillMaxSize().padding(padding).padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item { Spacer(modifier = Modifier.height(4.dp)) }
            if (warnings.isNotEmpty()) {
                item {
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            warnings.forEach { warning ->
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(
                                        Icons.Default.WarningAmber,
                                        contentDescription = null,
                                        tint = MaterialTheme.colorScheme.onErrorContainer,
                                        modifier = Modifier.height(18.dp)
                                    )
                                    Text(
                                        "  $warning",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onErrorContainer
                                    )
                                }
                            }
                        }
                    }
                }
            }
            itemsIndexed(sections) { sectionIndex, section ->
                SectionPreviewCard(
                    section = section,
                    included = sectionIndex !in excludedSections,
                    onToggleSection = { viewModel.toggleSection(sectionIndex) },
                    isQuestionIncluded = { questionIndex -> (sectionIndex to questionIndex) !in excludedQuestions },
                    onToggleQuestion = { questionIndex -> viewModel.toggleQuestion(sectionIndex, questionIndex) }
                )
            }
            item { Spacer(modifier = Modifier.height(72.dp)) }
        }
    }
}

@Composable
private fun SectionPreviewCard(
    section: ParsedSection,
    included: Boolean,
    onToggleSection: () -> Unit,
    isQuestionIncluded: (Int) -> Boolean,
    onToggleQuestion: (Int) -> Unit
) {
    FormCard(modifier = Modifier.fillMaxWidth()) {
        Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
            Checkbox(checked = included, onCheckedChange = { onToggleSection() })
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = section.title,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold
                )
                if (!section.instruction.isNullOrBlank()) {
                    Text(
                        text = section.instruction,
                        style = MaterialTheme.typography.bodySmall,
                        fontStyle = FontStyle.Italic,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            Chip(text = "${section.questions.size} Q")
        }

        section.questions.forEachIndexed { questionIndex, question ->
            Spacer(modifier = Modifier.height(8.dp))
            QuestionPreviewRow(
                question = question,
                included = included && isQuestionIncluded(questionIndex),
                enabled = included,
                onToggle = { onToggleQuestion(questionIndex) }
            )
        }
    }
}

@Composable
private fun QuestionPreviewRow(
    question: ParsedQuestion,
    included: Boolean,
    enabled: Boolean,
    onToggle: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(modifier = Modifier.padding(10.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxWidth()) {
                Checkbox(checked = included, onCheckedChange = { onToggle() }, enabled = enabled)
                Text(
                    text = question.text.ifBlank { "(empty question)" },
                    style = MaterialTheme.typography.bodyMedium,
                    modifier = Modifier.weight(1f)
                )
                if (question.marks != null) {
                    Chip(text = "${question.marks} m")
                }
            }
            Row(
                modifier = Modifier.padding(start = 40.dp, top = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                Chip(
                    text = question.questionType.displayName,
                    containerColor = MaterialTheme.colorScheme.secondaryContainer,
                    contentColor = MaterialTheme.colorScheme.onSecondaryContainer
                )
                if (question.options.isNotEmpty()) {
                    Chip(text = "${question.options.size} options")
                }
                if (question.subQuestions.isNotEmpty()) {
                    Chip(text = "${question.subQuestions.size} sub-parts")
                }
                if (question.hasInternalChoice) {
                    Chip(text = "OR choice")
                }
            }
        }
    }
}
