plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
    id("org.jetbrains.kotlin.plugin.compose")
}

android {
    namespace = "com.pdfimagetools.app"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.pdfimagetools.app"
        minSdk = 26
        targetSdk = 36
        versionCode = 1
        versionName = "1.0.0"

        vectorDrawables {
            useSupportLibrary = true
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
        debug {
            isMinifyEnabled = false
            applicationIdSuffix = ".debug"
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        compose = true
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
            excludes += "/META-INF/DEPENDENCIES"
        }
    }
}

// Guava publishes ListenableFuture two ways: bundled inside the real "guava" jar, and as a
// tiny standalone "listenablefuture" module (real class at version 1.0, or a deliberately
// EMPTY placeholder at version 9999.0-empty-to-avoid-conflict-with-guava — used whenever real
// Guava is also present, so the two don't both define the same class). CameraX depends on the
// real listenablefuture:1.0; AdMob's dependency chain pulls in the empty 9999.0 stub. Plain
// exclude()/force() only cover paths this project's authors can see in the graph, and can miss
// a transitive path buried inside Play Services. Dependency substitution is unconditional: it
// rewrites EVERY request for "listenablefuture", from anywhere, into the real Guava artifact,
// so com.google.common.util.concurrent.ListenableFuture is guaranteed to resolve to a real class.
configurations.all {
    resolutionStrategy {
        dependencySubstitution {
            substitute(module("com.google.guava:listenablefuture"))
                .using(module("com.google.guava:guava:33.6.0-android"))
                .because("Both real Guava and the standalone listenablefuture module define the same ListenableFuture class; redirecting every request to real Guava avoids the empty 9999.0 placeholder shadowing it.")
        }
    }
}

dependencies {
    // Core / Kotlin
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.9.0")

    // Compose
    implementation(platform("androidx.compose:compose-bom:2024.12.01"))
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.ui:ui-graphics")
    implementation("androidx.compose.ui:ui-tooling-preview")
    implementation("androidx.compose.ui:ui-text-google-fonts")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.material:material-icons-extended")
    implementation("androidx.activity:activity-compose:1.9.3")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.8.7")
    implementation("androidx.lifecycle:lifecycle-viewmodel-compose:2.8.7")
    implementation("androidx.lifecycle:lifecycle-runtime-compose:2.8.7")
    implementation("androidx.navigation:navigation-compose:2.8.5")
    debugImplementation("androidx.compose.ui:ui-tooling")
    debugImplementation("androidx.compose.ui:ui-test-manifest")

    // Image loading
    implementation("io.coil-kt:coil-compose:2.7.0")

    // Image EXIF handling
    implementation("androidx.exifinterface:exifinterface:1.3.7")

    // PDF processing
    implementation("com.tom-roush:pdfbox-android:2.0.27.0")

    // Google Mobile Ads. (See the dependency substitution above for why a plain implementation()
    // here would otherwise leave ListenableFuture unresolvable for CameraX.)
    implementation("com.google.android.gms:play-services-ads:23.6.0")

    // Custom camera-based document scanner
    implementation("androidx.camera:camera-core:1.6.0")
    implementation("androidx.camera:camera-camera2:1.6.0")
    implementation("androidx.camera:camera-lifecycle:1.6.0")
    implementation("androidx.camera:camera-view:1.6.0")

    testImplementation("junit:junit:4.13.2")
    androidTestImplementation("androidx.test.ext:junit:1.2.1")
    androidTestImplementation("androidx.test.espresso:espresso-core:3.6.1")
    androidTestImplementation(platform("androidx.compose:compose-bom:2024.12.01"))
    androidTestImplementation("androidx.compose.ui:ui-test-junit4")
}
