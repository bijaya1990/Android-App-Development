# PdfBox-Android uses reflection and bundled resources for font/encoding data.
-keep class com.tom_roush.** { *; }
-dontwarn com.tom_roush.**
-dontwarn javax.imageio.**
-dontwarn org.osgi.framework.**

# Google Mobile Ads
-keep class com.google.android.gms.ads.** { *; }
-dontwarn com.google.android.gms.ads.**

# ML Kit Document Scanner (Play services module)
-keep class com.google.mlkit.vision.documentscanner.** { *; }
-dontwarn com.google.mlkit.vision.documentscanner.**

# Keep data/entity classes used for JSON (de)serialization of recent files & settings
-keep class com.pdfimagetools.app.data.** { *; }
