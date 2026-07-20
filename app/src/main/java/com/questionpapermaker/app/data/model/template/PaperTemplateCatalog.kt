package com.questionpapermaker.app.data.model.template

import com.questionpapermaker.app.data.model.FooterStyle
import com.questionpapermaker.app.data.model.HeaderStyle
import com.questionpapermaker.app.data.model.QuestionType

/** The 15 built-in, ready-made templates teachers can start a paper from. */
object PaperTemplateCatalog {

    val all: List<PaperTemplate> = listOf(
        // ---------- School ----------
        PaperTemplate(
            id = "school_unit_test",
            name = "Unit Test",
            category = TemplateCategory.SCHOOL,
            description = "A short, single-chapter test -- MCQs plus a few short answers.",
            examType = "Unit Test",
            headerStyle = HeaderStyle.SIMPLE,
            footerStyle = FooterStyle.SIMPLE,
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Multiple Choice Questions",
                    instruction = "Choose the correct option for each question.",
                    defaultQuestionType = QuestionType.MULTIPLE_CHOICE,
                    defaultMarksPerQuestion = 1.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Short Answer Questions",
                    instruction = "Answer the following questions briefly.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 2.0
                )
            )
        ),
        PaperTemplate(
            id = "school_monthly_test",
            name = "Monthly Test",
            category = TemplateCategory.SCHOOL,
            description = "Covers a month's syllabus across three question types.",
            examType = "Monthly Test",
            headerStyle = HeaderStyle.SIMPLE,
            footerStyle = FooterStyle.SIMPLE,
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Objective Type",
                    instruction = "Answer all questions.",
                    defaultQuestionType = QuestionType.MULTIPLE_CHOICE,
                    defaultMarksPerQuestion = 1.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Short Answer Questions",
                    instruction = "Answer any five of the following questions.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 3.0
                ),
                TemplateSectionBlueprint(
                    title = "Section C: Long Answer Questions",
                    instruction = "Answer any two of the following questions.",
                    defaultQuestionType = QuestionType.LONG_ANSWER,
                    defaultMarksPerQuestion = 5.0
                )
            )
        ),
        PaperTemplate(
            id = "school_half_yearly",
            name = "Half-Yearly Examination",
            category = TemplateCategory.SCHOOL,
            description = "A full-syllabus mid-year exam with a formal layout and signature area.",
            examType = "Half-Yearly Examination",
            headerStyle = HeaderStyle.UNIVERSITY,
            footerStyle = FooterStyle.PROFESSIONAL,
            showSignatureArea = true,
            generalInstructions = "All questions are compulsory unless stated otherwise. Write your answers neatly.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Multiple Choice Questions",
                    instruction = "Choose the correct option.",
                    defaultQuestionType = QuestionType.MULTIPLE_CHOICE,
                    defaultMarksPerQuestion = 1.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Short Answer Questions",
                    instruction = "Answer any six of the following questions.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 3.0
                ),
                TemplateSectionBlueprint(
                    title = "Section C: Long Answer Questions",
                    instruction = "Answer any three of the following questions.",
                    defaultQuestionType = QuestionType.LONG_ANSWER,
                    defaultMarksPerQuestion = 5.0
                )
            )
        ),
        PaperTemplate(
            id = "school_annual",
            name = "Annual Examination",
            category = TemplateCategory.SCHOOL,
            description = "The full year-end examination format with a formal cover and signature line.",
            examType = "Annual Examination",
            headerStyle = HeaderStyle.UNIVERSITY,
            footerStyle = FooterStyle.PROFESSIONAL,
            showSignatureArea = true,
            generalInstructions = "This paper contains four sections. All sections are compulsory.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Multiple Choice Questions",
                    instruction = "Choose the correct option.",
                    defaultQuestionType = QuestionType.MULTIPLE_CHOICE,
                    defaultMarksPerQuestion = 1.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Very Short Answer Questions",
                    instruction = "Answer all questions in one or two sentences.",
                    defaultQuestionType = QuestionType.VERY_SHORT_ANSWER,
                    defaultMarksPerQuestion = 2.0
                ),
                TemplateSectionBlueprint(
                    title = "Section C: Short Answer Questions",
                    instruction = "Answer any five of the following questions.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 3.0
                ),
                TemplateSectionBlueprint(
                    title = "Section D: Long Answer Questions",
                    instruction = "Answer any three of the following questions.",
                    defaultQuestionType = QuestionType.LONG_ANSWER,
                    defaultMarksPerQuestion = 5.0
                )
            )
        ),

        // ---------- College ----------
        PaperTemplate(
            id = "college_internal_assessment",
            name = "Internal Assessment",
            category = TemplateCategory.COLLEGE,
            description = "A short, informal internal assessment test.",
            examType = "Internal Assessment",
            headerStyle = HeaderStyle.MODERN,
            footerStyle = FooterStyle.SIMPLE,
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Short Answer Questions",
                    instruction = "Answer any three of the following questions.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 5.0
                )
            )
        ),
        PaperTemplate(
            id = "college_mid_semester",
            name = "Mid Semester Examination",
            category = TemplateCategory.COLLEGE,
            description = "Standard mid-semester format with a course/programme header.",
            examType = "Mid Semester Examination",
            headerStyle = HeaderStyle.UNIVERSITY,
            footerStyle = FooterStyle.UNIVERSITY,
            showSignatureArea = true,
            generalInstructions = "Answer any five questions. Each question carries equal marks.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Short Answer Questions",
                    instruction = "Answer any five of the following questions.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 4.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Long Answer Questions",
                    instruction = "Answer any two of the following questions.",
                    defaultQuestionType = QuestionType.LONG_ANSWER,
                    defaultMarksPerQuestion = 10.0
                )
            )
        ),
        PaperTemplate(
            id = "college_end_semester",
            name = "End Semester Examination",
            category = TemplateCategory.COLLEGE,
            description = "The full end-of-semester exam format with a formal university header.",
            examType = "End Semester Examination",
            headerStyle = HeaderStyle.UNIVERSITY,
            footerStyle = FooterStyle.UNIVERSITY,
            showSignatureArea = true,
            generalInstructions = "This paper is divided into three sections. Answer as per the instructions in each section.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Short Answer Questions",
                    instruction = "Answer any five of the following questions.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 4.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Long Answer Questions",
                    instruction = "Answer any three of the following questions.",
                    defaultQuestionType = QuestionType.LONG_ANSWER,
                    defaultMarksPerQuestion = 8.0
                ),
                TemplateSectionBlueprint(
                    title = "Section C: Case Study / Essay",
                    instruction = "Answer any one of the following questions.",
                    defaultQuestionType = QuestionType.ESSAY,
                    defaultMarksPerQuestion = 16.0
                )
            )
        ),
        PaperTemplate(
            id = "college_practical",
            name = "Practical Examination",
            category = TemplateCategory.COLLEGE,
            description = "For lab and practical exams -- procedure, observation and viva sections.",
            examType = "Practical Examination",
            headerStyle = HeaderStyle.PROFESSIONAL,
            footerStyle = FooterStyle.PROFESSIONAL,
            showSignatureArea = true,
            generalInstructions = "Perform all experiments neatly and record your observations accurately.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Practical Tasks",
                    instruction = "Attempt any two of the following experiments/tasks.",
                    defaultQuestionType = QuestionType.PRACTICAL,
                    defaultMarksPerQuestion = 15.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Viva Voce",
                    instruction = "To be assessed by the examiner during the practical.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 5.0
                )
            )
        ),
        PaperTemplate(
            id = "college_viva",
            name = "Viva Examination",
            category = TemplateCategory.COLLEGE,
            description = "A simple question list for an oral/viva examination.",
            examType = "Viva Voce",
            headerStyle = HeaderStyle.SIMPLE,
            footerStyle = FooterStyle.SIMPLE,
            showSignatureArea = true,
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Viva Questions",
                    instruction = "To be asked orally by the examiner.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = null
                )
            )
        ),

        // ---------- Coaching ----------
        PaperTemplate(
            id = "coaching_weekly_test",
            name = "Weekly Test",
            category = TemplateCategory.COACHING,
            description = "A quick weekly progress check, mostly objective questions.",
            examType = "Weekly Test",
            headerStyle = HeaderStyle.MODERN,
            footerStyle = FooterStyle.SIMPLE,
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Objective Questions",
                    instruction = "Choose the correct option for each question.",
                    defaultQuestionType = QuestionType.MULTIPLE_CHOICE,
                    defaultMarksPerQuestion = 1.0
                )
            )
        ),
        PaperTemplate(
            id = "coaching_mock_test",
            name = "Mock Test",
            category = TemplateCategory.COACHING,
            description = "A full-length competitive-exam-style mock test.",
            examType = "Mock Test",
            headerStyle = HeaderStyle.MODERN,
            footerStyle = FooterStyle.SIMPLE,
            generalInstructions = "Negative marking may apply. Read each question carefully before answering.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Multiple Choice Questions",
                    instruction = "Choose the single best answer.",
                    defaultQuestionType = QuestionType.MULTIPLE_CHOICE,
                    defaultMarksPerQuestion = 2.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Numerical / Problem Solving",
                    instruction = "Solve and enter the correct value.",
                    defaultQuestionType = QuestionType.NUMERICAL,
                    defaultMarksPerQuestion = 4.0
                )
            )
        ),
        PaperTemplate(
            id = "coaching_practice_test",
            name = "Practice Test",
            category = TemplateCategory.COACHING,
            description = "A light-weight practice set for topic-wise revision.",
            examType = "Practice Test",
            headerStyle = HeaderStyle.MINIMAL,
            footerStyle = FooterStyle.SIMPLE,
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Practice Questions",
                    instruction = "Attempt as many questions as you can.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 2.0
                )
            )
        ),

        // ---------- General ----------
        PaperTemplate(
            id = "general_assignment",
            name = "Assignment",
            category = TemplateCategory.GENERAL,
            description = "A take-home assignment with open-ended questions.",
            examType = "Assignment",
            headerStyle = HeaderStyle.MINIMAL,
            footerStyle = FooterStyle.SIMPLE,
            generalInstructions = "Submit your handwritten or typed answers by the due date.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Assignment Questions",
                    instruction = "Answer all questions in your own words.",
                    defaultQuestionType = QuestionType.LONG_ANSWER,
                    defaultMarksPerQuestion = null
                )
            )
        ),
        PaperTemplate(
            id = "general_worksheet",
            name = "Worksheet",
            category = TemplateCategory.GENERAL,
            description = "A practice worksheet mixing short exercises, ungraded by default.",
            examType = "Worksheet",
            headerStyle = HeaderStyle.MINIMAL,
            footerStyle = FooterStyle.SIMPLE,
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Exercises",
                    instruction = "Complete all exercises below.",
                    defaultQuestionType = QuestionType.FILL_IN_THE_BLANK,
                    defaultMarksPerQuestion = null
                )
            )
        ),
        PaperTemplate(
            id = "general_model_paper",
            name = "Model Question Paper",
            category = TemplateCategory.GENERAL,
            description = "A sample/model paper shared with students ahead of the real exam.",
            examType = "Model Question Paper",
            headerStyle = HeaderStyle.PROFESSIONAL,
            footerStyle = FooterStyle.PROFESSIONAL,
            generalInstructions = "This is a model paper for practice. The actual exam pattern may vary slightly.",
            sections = listOf(
                TemplateSectionBlueprint(
                    title = "Section A: Multiple Choice Questions",
                    instruction = "Choose the correct option.",
                    defaultQuestionType = QuestionType.MULTIPLE_CHOICE,
                    defaultMarksPerQuestion = 1.0
                ),
                TemplateSectionBlueprint(
                    title = "Section B: Short Answer Questions",
                    instruction = "Answer any five of the following questions.",
                    defaultQuestionType = QuestionType.SHORT_ANSWER,
                    defaultMarksPerQuestion = 3.0
                ),
                TemplateSectionBlueprint(
                    title = "Section C: Long Answer Questions",
                    instruction = "Answer any two of the following questions.",
                    defaultQuestionType = QuestionType.LONG_ANSWER,
                    defaultMarksPerQuestion = 5.0
                )
            )
        )
    )

    fun byId(id: String): PaperTemplate? = all.firstOrNull { it.id == id }
}
