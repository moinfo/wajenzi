# Flutter
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }
-dontwarn io.flutter.embedding.**

# flutter_secure_storage
-keep class com.it_nomads.fluttersecurestorage.** { *; }

# workmanager
-keep class be.tramckrijte.workmanager.** { *; }
-keep class androidx.work.** { *; }

# Kotlin
-keep class kotlin.** { *; }
-keep class kotlin.Metadata { *; }
-dontwarn kotlin.**
-keepclassmembers class **$WhenMappings { <fields>; }
-keepclassmembers class kotlin.Metadata { public <methods>; }

# Firebase
-keep class com.google.firebase.** { *; }
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.firebase.**
-dontwarn com.google.android.gms.**

# SQLite / Drift
-keep class org.sqlite.** { *; }
-keep class org.sqlite.database.** { *; }

# Geolocator
-keep class com.baseflow.geolocator.** { *; }

# Permission handler
-keep class com.baseflow.permissionhandler.** { *; }

# Image picker / cropper
-keep class io.flutter.plugins.imagepicker.** { *; }
-keep class com.yalantis.ucrop.** { *; }

# Gson / JSON (if used internally by plugins)
-keepattributes Signature
-keepattributes *Annotation*
-dontwarn sun.misc.**
