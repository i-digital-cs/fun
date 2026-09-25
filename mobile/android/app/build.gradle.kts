plugins {
    id("com.android.application")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

import java.util.Properties

// Apply Google Services only when google-services.json is present
// (see mobile/docs/FIREBASE_PUSH.md). Keeps debug builds working without Firebase.
if (file("google-services.json").exists()) {
    apply(plugin = "com.google.gms.google-services")
}

// BUILD-001: Production release signing + ARM ABIs (see mobile/docs/RELEASE.md).
val keystorePropertiesFile = rootProject.file("key.properties")
val keystoreProperties = Properties()
if (keystorePropertiesFile.exists()) {
    keystorePropertiesFile.inputStream().use { keystoreProperties.load(it) }
}

val allowDebugReleaseSigning =
    (project.findProperty("allowDebugReleaseSigning") as String?) == "true"

android {
    namespace = "com.funity.fun_mobile"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    defaultConfig {
        applicationId = "com.funity.fun_mobile"
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
        // Production devices are ARM. Do NOT restrict to x86_64 (emulator-only).
        // Emulator still runs via Flutter's default multi-ABI / host tools.
    }

    signingConfigs {
        if (keystorePropertiesFile.exists()) {
            create("release") {
                keyAlias = keystoreProperties["keyAlias"] as String
                keyPassword = keystoreProperties["keyPassword"] as String
                storeFile = file(keystoreProperties["storeFile"] as String)
                storePassword = keystoreProperties["storePassword"] as String
            }
        }
    }

    buildTypes {
        release {
            when {
                keystorePropertiesFile.exists() -> {
                    signingConfig = signingConfigs.getByName("release")
                }
                allowDebugReleaseSigning -> {
                    // Local-only: flutter build/run --release -PallowDebugReleaseSigning=true
                    signingConfig = signingConfigs.getByName("debug")
                }
                // Else: leave unset. Fail closed only when a *release* task runs
                // (see afterEvaluate below) so assembleDebug / flutter run still work.
            }
        }
    }
}

// BUILD-001: fail closed for release packaging only — not during debug configure.
afterEvaluate {
    if (keystorePropertiesFile.exists() || allowDebugReleaseSigning) {
        return@afterEvaluate
    }
    val releaseSigningMessage =
        "Release signing is not configured. Add android/key.properties " +
            "(see android/key.properties.example) or pass " +
            "-PallowDebugReleaseSigning=true for local non-store testing only."
    tasks.matching { task ->
        val n = task.name
        n.contains("Release", ignoreCase = true) &&
            (
                n.startsWith("assemble") ||
                    n.startsWith("bundle") ||
                    n.startsWith("package") ||
                    n.startsWith("sign")
                )
    }.configureEach {
        doFirst {
            throw GradleException(releaseSigningMessage)
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
    }
}

flutter {
    source = "../.."
}
