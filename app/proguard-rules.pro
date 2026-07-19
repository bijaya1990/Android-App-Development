# PdfBox-Android uses reflection and bundled resources for font/encoding data.
-keep class com.tom_roush.** { *; }
-dontwarn com.tom_roush.**
-dontwarn javax.imageio.**
-dontwarn org.osgi.framework.**

# Google Mobile Ads
-keep class com.google.android.gms.ads.** { *; }
-dontwarn com.google.android.gms.ads.**

# OpenCV's Java classes register native (JNI) methods by exact signature; R8 renaming them
# would break that binding at runtime.
-keep class org.opencv.** { *; }
-dontwarn org.opencv.**

# Keep data/entity classes used for JSON (de)serialization of recent files & settings
-keep class com.pdfimagetools.app.data.** { *; }
