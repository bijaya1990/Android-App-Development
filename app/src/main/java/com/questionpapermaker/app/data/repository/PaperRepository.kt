package com.questionpapermaker.app.data.repository

import com.questionpapermaker.app.data.database.AppDatabase
import com.questionpapermaker.app.data.database.entity.PaperEntity
import com.questionpapermaker.app.data.database.entity.QuestionEntity
import com.questionpapermaker.app.data.database.entity.SectionEntity
import com.questionpapermaker.app.data.model.NumberingStyle
import com.questionpapermaker.app.data.model.PaperWithContent
import com.questionpapermaker.app.data.model.SectionWithQuestions
import com.questionpapermaker.app.data.model.TeacherDefaults
import com.questionpapermaker.app.util.IdGenerator
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.distinctUntilChanged
import kotlinx.coroutines.flow.first

/**
 * Single entry point for all paper/section/question persistence. Screens talk to this,
 * never to the DAOs directly, so autosave and cross-entity rules live in one place.
 */
class PaperRepository(
    private val db: AppDatabase,
    private val defaultsRepository: DefaultsRepository
) {
    private val paperDao = db.paperDao()
    private val sectionDao = db.sectionDao()
    private val questionDao = db.questionDao()

    // ---------- Papers ----------

    fun observeActivePapers(): Flow<List<PaperEntity>> = paperDao.observeActivePapers()

    fun observeTrashedPapers(): Flow<List<PaperEntity>> = paperDao.observeTrashedPapers()

    fun searchPapers(query: String): Flow<List<PaperEntity>> =
        if (query.isBlank()) observeActivePapers() else paperDao.searchPapers(query)

    suspend fun getPaper(paperId: String): PaperEntity? = paperDao.getPaper(paperId)

    fun observePaper(paperId: String): Flow<PaperEntity?> = paperDao.observePaper(paperId)

    /** Creates a fresh draft paper pre-filled with the teacher's saved defaults. */
    suspend fun createDraftPaper(): PaperEntity {
        val defaults: TeacherDefaults = defaultsRepository.getDefaults()
        val now = System.currentTimeMillis()
        val paper = PaperEntity(
            id = IdGenerator.newId(),
            institutionName = defaults.institutionName,
            institutionLogoUri = defaults.institutionLogoUri,
            department = defaults.department,
            programme = defaults.programme,
            signatureLabel = defaults.signatureLabel,
            showSignatureArea = !defaults.signatureLabel.isNullOrBlank(),
            createdAt = now,
            updatedAt = now
        )
        paperDao.upsert(paper)
        return paper
    }

    suspend fun savePaper(paper: PaperEntity) {
        paperDao.upsert(paper.copy(updatedAt = System.currentTimeMillis()))
    }

    suspend fun duplicatePaper(paperId: String): PaperEntity? {
        val source = paperDao.getPaper(paperId) ?: return null
        val now = System.currentTimeMillis()
        val newPaperId = IdGenerator.newId()
        val copy = source.copy(
            id = newPaperId,
            createdAt = now,
            updatedAt = now,
            lastExportedAt = null,
            lastExportedPdfUri = null,
            isTrashed = false,
            trashedAt = null
        )
        paperDao.upsert(copy)

        val sections = sectionDao.getSections(paperId)
        val sectionIdMap = sections.associate { it.id to IdGenerator.newId() }
        val newSections = sections.map { it.copy(id = sectionIdMap.getValue(it.id), paperId = newPaperId) }
        sectionDao.upsertAll(newSections)

        val questionsToInsert = mutableListOf<QuestionEntity>()
        for (section in sections) {
            val originalQuestions = questionDao.observeQuestions(section.id).first()
            val newSectionId = sectionIdMap.getValue(section.id)
            originalQuestions.forEach { q ->
                questionsToInsert += q.copy(id = IdGenerator.newId(), sectionId = newSectionId)
            }
        }
        if (questionsToInsert.isNotEmpty()) questionDao.upsertAll(questionsToInsert)

        return copy
    }

    suspend fun moveToTrash(paperId: String) = paperDao.moveToTrash(paperId, System.currentTimeMillis())

    suspend fun restoreFromTrash(paperId: String) = paperDao.restoreFromTrash(paperId)

    suspend fun deleteForever(paperId: String) = paperDao.deleteById(paperId)

    // ---------- Aggregate (paper + sections + questions) ----------

    fun observePaperWithContent(paperId: String): Flow<PaperWithContent?> {
        val paperFlow = paperDao.observePaper(paperId)
        val sectionsFlow = sectionDao.observeSections(paperId)
        val questionsFlow = questionDao.observeQuestionsForPaper(paperId)

        return combine(paperFlow, sectionsFlow, questionsFlow) { paper, sections, questions ->
            if (paper == null) return@combine null
            val questionsBySection = questions.groupBy { it.sectionId }
            PaperWithContent(
                paper = paper,
                sections = sections.map { section ->
                    SectionWithQuestions(
                        section = section,
                        questions = questionsBySection[section.id].orEmpty().sortedBy { it.orderIndex }
                    )
                }
            )
        }.distinctUntilChanged()
    }

    // ---------- Sections ----------

    suspend fun addSection(paperId: String): SectionEntity {
        val existing = sectionDao.getSections(paperId)
        val nextIndex = existing.size
        val section = SectionEntity(
            id = IdGenerator.newId(),
            paperId = paperId,
            orderIndex = nextIndex,
            title = "Section ${NumberingStyle.UPPER_ALPHA.render(nextIndex + 1)}",
            instruction = "Answer all questions."
        )
        sectionDao.upsert(section)
        touchPaper(paperId)
        return section
    }

    suspend fun updateSection(section: SectionEntity) {
        sectionDao.update(section)
        touchPaper(section.paperId)
    }

    suspend fun deleteSection(section: SectionEntity) {
        sectionDao.delete(section)
        val remaining = sectionDao.getSections(section.paperId).mapIndexed { index, s -> s.copy(orderIndex = index) }
        sectionDao.upsertAll(remaining)
        touchPaper(section.paperId)
    }

    suspend fun reorderSections(paperId: String, orderedSectionIds: List<String>) {
        val sections = sectionDao.getSections(paperId).associateBy { it.id }
        val reordered = orderedSectionIds.mapIndexedNotNull { index, id ->
            sections[id]?.copy(orderIndex = index)
        }
        sectionDao.upsertAll(reordered)
        touchPaper(paperId)
    }

    // ---------- Questions ----------

    suspend fun addQuestion(section: SectionEntity): QuestionEntity {
        val count = questionDao.countForSection(section.id)
        val question = QuestionEntity(
            id = IdGenerator.newId(),
            sectionId = section.id,
            orderIndex = count,
            questionType = section.defaultQuestionType,
            marks = section.defaultMarksPerQuestion ?: 0.0
        )
        questionDao.upsert(question)
        touchPaper(section.paperId)
        return question
    }

    suspend fun updateQuestion(question: QuestionEntity, paperId: String) {
        questionDao.update(question)
        touchPaper(paperId)
    }

    suspend fun deleteQuestion(question: QuestionEntity, paperId: String) {
        questionDao.delete(question)
        renumberSection(question.sectionId)
        touchPaper(paperId)
    }

    suspend fun duplicateQuestion(question: QuestionEntity, paperId: String): QuestionEntity {
        val count = questionDao.countForSection(question.sectionId)
        val copy = question.copy(id = IdGenerator.newId(), orderIndex = count)
        questionDao.upsert(copy)
        touchPaper(paperId)
        return copy
    }

    suspend fun reorderQuestions(sectionId: String, orderedQuestionIds: List<String>, paperId: String) {
        val questions = questionDao.observeQuestions(sectionId).first().associateBy { it.id }
        val reordered = orderedQuestionIds.mapIndexedNotNull { index, id ->
            questions[id]?.copy(orderIndex = index)
        }
        questionDao.upsertAll(reordered)
        touchPaper(paperId)
    }

    private suspend fun renumberSection(sectionId: String) {
        val remaining = questionDao.observeQuestions(sectionId).first().sortedBy { it.orderIndex }
            .mapIndexed { index, q -> q.copy(orderIndex = index) }
        questionDao.upsertAll(remaining)
    }

    private suspend fun touchPaper(paperId: String) {
        val paper = paperDao.getPaper(paperId) ?: return
        paperDao.update(paper.copy(updatedAt = System.currentTimeMillis()))
    }
}
