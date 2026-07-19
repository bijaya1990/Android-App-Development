package com.questionpapermaker.app.util

import java.util.UUID

object IdGenerator {
    fun newId(): String = UUID.randomUUID().toString()
}
