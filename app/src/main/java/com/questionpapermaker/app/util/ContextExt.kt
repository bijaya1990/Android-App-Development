package com.questionpapermaker.app.util

import android.app.Activity
import android.content.Context
import android.content.ContextWrapper

/** Unwraps whatever Compose/theming wrapper is around a Context to find the hosting Activity, if any. */
tailrec fun Context.findActivity(): Activity? = when (this) {
    is Activity -> this
    is ContextWrapper -> baseContext.findActivity()
    else -> null
}
