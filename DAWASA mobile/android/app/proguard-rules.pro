# Add project specific ProGuard rules here.

# Preserve line numbers for crash reporting
-keepattributes SourceFile,LineNumberTable
-renamesourcefileattribute SourceFile

# Capacitor bridge — must not be obfuscated
-keep class com.getcapacitor.** { *; }
-keep class com.getcapacitor.annotation.** { *; }
-keepclassmembers class * extends com.getcapacitor.Plugin {
    @com.getcapacitor.annotation.CapacitorPlugin <fields>;
    @com.getcapacitor.PluginMethod public *;
}

# App main activity
-keep class com.dawasa.mobileapp.** { *; }

# WebView JavaScript interface
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}

# AndroidX / Appcompat
-keep class androidx.** { *; }
-dontwarn androidx.**

# Cordova plugins (Capacitor uses these internally)
-keep class org.apache.cordova.** { *; }
-dontwarn org.apache.cordova.**
