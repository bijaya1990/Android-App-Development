package com.questionpapermaker.app.ui.paperdetails

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowForward
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.HorizontalDivider
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
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.questionpapermaker.app.ui.common.DraftSavedIndicator
import com.questionpapermaker.app.ui.common.ExpandableSection
import com.questionpapermaker.app.ui.common.FormCard

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PaperDetailsScreen(
    viewModel: PaperEditorViewModel,
    onBack: () -> Unit,
    onNext: () -> Unit
) {
    val paper by viewModel.paper.collectAsState()
    val context = LocalContext.current

    val logoPicker = rememberLauncherForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        if (uri != null) {
            runCatching {
                context.contentResolver.takePersistableUriPermission(uri, android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            viewModel.update { it.copy(institutionLogoUri = uri.toString()) }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Question Paper Maker") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                },
                actions = {
                    DraftSavedIndicator(modifier = Modifier.padding(end = 8.dp))
                    IconButton(onClick = viewModel::saveAsDefaults) {
                        Icon(Icons.Default.Bookmark, contentDescription = "Save institution as default")
                    }
                }
            )
        },
        bottomBar = {
            Row(
                modifier = Modifier.fillMaxWidth().navigationBarsPadding().padding(16.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                OutlinedButton(onClick = onBack, shape = MaterialTheme.shapes.small) {
                    Icon(Icons.Default.ArrowBack, contentDescription = null, modifier = Modifier.height(18.dp))
                    Text("  Back")
                }
                Button(onClick = { viewModel.saveNow(onNext) }, shape = MaterialTheme.shapes.small) {
                    Text("Next: Layout  ")
                    Icon(Icons.Default.ArrowForward, contentDescription = null, modifier = Modifier.height(18.dp))
                }
            }
        }
    ) { padding ->
        val current = paper
        if (current == null) return@Scaffold

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .padding(horizontal = 16.dp)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            Column(modifier = Modifier.padding(top = 12.dp)) {
                Text("Paper Details", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
                Text(
                    "Configure the header information for this exam paper.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }

            FormCard(modifier = Modifier.fillMaxWidth()) {
                Text("Institution Logo (Optional)", style = MaterialTheme.typography.labelLarge)
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 8.dp)
                        .border(
                            BorderStroke(1.dp, MaterialTheme.colorScheme.outline),
                            RoundedCornerShape(8.dp)
                        )
                        .clickable { logoPicker.launch(arrayOf("image/*")) }
                        .padding(vertical = 24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Icon(Icons.Default.CloudUpload, contentDescription = null, tint = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(
                        text = if (current.institutionLogoUri == null) "Click to upload" else "Change logo",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.padding(top = 8.dp)
                    )
                    Text(
                        text = "SVG, PNG, JPG (Max 2MB)",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                HorizontalDivider(modifier = Modifier.padding(vertical = 16.dp))

                LabeledField(current.institutionName, "Institution Name *") { viewModel.update { p -> p.copy(institutionName = it) } }
                LabeledField(current.examName, "Exam Name *") { viewModel.update { p -> p.copy(examName = it) } }
                LabeledField(current.subject, "Subject *") { viewModel.update { p -> p.copy(subject = it) } }
                LabeledField(current.courseCode, "Course Code") { viewModel.update { p -> p.copy(courseCode = it) } }
                LabeledField(current.academicYear, "Term / Academic Year") { viewModel.update { p -> p.copy(academicYear = it) } }
                LabeledField(current.duration, "Duration (Minutes) *") { viewModel.update { p -> p.copy(duration = it) } }
                LabeledField(current.maxMarks, "Maximum Marks *") { viewModel.update { p -> p.copy(maxMarks = it) } }

                HorizontalDivider(modifier = Modifier.padding(vertical = 8.dp))

                ExpandableSection(label = "Add General Instructions", initiallyExpanded = !current.generalInstructions.isNullOrBlank()) {
                    LabeledField(
                        current.generalInstructions,
                        "General Instructions",
                        singleLine = false
                    ) { viewModel.update { p -> p.copy(generalInstructions = it) } }
                }
            }

            FormCard(modifier = Modifier.fillMaxWidth()) {
                ExpandableSection(label = "More Details (Optional)") {
                    LabeledField(current.examType, "Exam Type (e.g. Mid Semester Examination)") { viewModel.update { p -> p.copy(examType = it) } }
                    LabeledField(current.courseName, "Course Name") { viewModel.update { p -> p.copy(courseName = it) } }
                    LabeledField(current.programme, "Programme") { viewModel.update { p -> p.copy(programme = it) } }
                    LabeledField(current.department, "Department") { viewModel.update { p -> p.copy(department = it) } }
                    LabeledField(current.faculty, "Faculty") { viewModel.update { p -> p.copy(faculty = it) } }
                    LabeledField(current.className, "Class") { viewModel.update { p -> p.copy(className = it) } }
                    LabeledField(current.semester, "Semester") { viewModel.update { p -> p.copy(semester = it) } }
                    LabeledField(current.academicSession, "Academic Session") { viewModel.update { p -> p.copy(academicSession = it) } }
                    LabeledField(current.examDate, "Date") { viewModel.update { p -> p.copy(examDate = it) } }
                }
            }
        }
    }
}

@Composable
private fun LabeledField(
    value: String?,
    label: String,
    singleLine: Boolean = true,
    onValueChange: (String) -> Unit
) {
    OutlinedTextField(
        value = value.orEmpty(),
        onValueChange = onValueChange,
        label = { Text(label) },
        singleLine = singleLine,
        shape = MaterialTheme.shapes.small,
        modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)
    )
}
