# Room
-keep class androidx.room.RoomDatabase
-dontwarn androidx.room.paging.**

# Keep data model / entity classes so Room + kotlinx.serialization reflection keeps working
-keep class com.questionpapermaker.app.data.database.entity.** { *; }
-keep class com.questionpapermaker.app.data.model.** { *; }

# kotlinx.serialization
-keepattributes *Annotation*, InnerClasses
-dontnote kotlinx.serialization.AnnotationsKt
-keepclassmembers class kotlinx.serialization.json.** {
    *** Companion;
}
-keepclasseswithmembers class **$$serializer {
    *** INSTANCE;
}
