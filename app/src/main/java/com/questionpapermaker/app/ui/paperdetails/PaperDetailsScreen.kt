package com.questionpapermaker.app.ui.paperdetails

import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.Image
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
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp

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
                title = { Text("Paper Details") },
                navigationIcon = {
                    IconButton(onClick = onBack) { Icon(Icons.Default.ArrowBack, contentDescription = "Back") }
                },
                actions = {
                    IconButton(onClick = viewModel::saveAsDefaults) {
                        Icon(Icons.Default.Bookmark, contentDescription = "Save institution as default")
                    }
                }
            )
        },
        bottomBar = {
            Row(
                modifier = Modifier.fillMaxWidth().padding(16.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                OutlinedButton(onClick = onBack) { Text("Back") }
                Button(onClick = { viewModel.saveNow(onNext) }) { Text("Next: Paper Layout") }
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
            verticalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            SectionHeader("Institution")
            LabeledField(current.institutionName, "Institution Name") { viewModel.update { p -> p.copy(institutionName = it) } }
            OutlinedButton(onClick = { logoPicker.launch(arrayOf("image/*")) }) {
                Icon(Icons.Default.Image, contentDescription = null)
                Text(if (current.institutionLogoUri == null) "  Add Institution Logo" else "  Change Institution Logo")
            }

            SectionHeader("Exam Info", topPadding = 16.dp)
            LabeledField(current.examName, "Exam Name *") { viewModel.update { p -> p.copy(examName = it) } }
            LabeledField(current.examType, "Exam Type (e.g. Mid Semester Examination)") { viewModel.update { p -> p.copy(examType = it) } }
            LabeledField(current.subject, "Subject *") { viewModel.update { p -> p.copy(subject = it) } }
            LabeledField(current.courseName, "Course Name") { viewModel.update { p -> p.copy(courseName = it) } }
            LabeledField(current.courseCode, "Course Code") { viewModel.update { p -> p.copy(courseCode = it) } }

            SectionHeader("Academic Info", topPadding = 16.dp)
            LabeledField(current.programme, "Programme") { viewModel.update { p -> p.copy(programme = it) } }
            LabeledField(current.department, "Department") { viewModel.update { p -> p.copy(department = it) } }
            LabeledField(current.faculty, "Faculty") { viewModel.update { p -> p.copy(faculty = it) } }
            LabeledField(current.className, "Class") { viewModel.update { p -> p.copy(className = it) } }
            LabeledField(current.semester, "Semester") { viewModel.update { p -> p.copy(semester = it) } }
            LabeledField(current.academicSession, "Academic Session") { viewModel.update { p -> p.copy(academicSession = it) } }
            LabeledField(current.academicYear, "Academic Year") { viewModel.update { p -> p.copy(academicYear = it) } }

            SectionHeader("Schedule & Marks", topPadding = 16.dp)
            LabeledField(current.examDate, "Date") { viewModel.update { p -> p.copy(examDate = it) } }
            LabeledField(current.duration, "Duration (e.g. 3 Hours)") { viewModel.update { p -> p.copy(duration = it) } }
            LabeledField(current.maxMarks, "Maximum Marks *") { viewModel.update { p -> p.copy(maxMarks = it) } }

            SectionHeader("Instructions", topPadding = 16.dp)
            LabeledField(
                current.generalInstructions,
                "General Instructions",
                singleLine = false
            ) { viewModel.update { p -> p.copy(generalInstructions = it) } }

            HorizontalDivider(modifier = Modifier.padding(vertical = 24.dp))
        }
    }
}

@Composable
private fun SectionHeader(title: String, topPadding: androidx.compose.ui.unit.Dp = 0.dp) {
    Text(
        text = title,
        style = MaterialTheme.typography.titleMedium,
        color = MaterialTheme.colorScheme.primary,
        modifier = Modifier.padding(top = topPadding, bottom = 4.dp)
    )
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
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
    )
}
