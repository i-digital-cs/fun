package com.funnity.app

import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import android.os.Bundle
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        ensureFunnityNotificationChannel()
    }

    private fun ensureFunnityNotificationChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val manager = getSystemService(NotificationManager::class.java) ?: return
        val channel = NotificationChannel(
            "funnity_alerts",
            "Funnity alerts",
            NotificationManager.IMPORTANCE_HIGH,
        ).apply {
            description = "Join requests, accepts, and chat alerts"
        }
        manager.createNotificationChannel(channel)
    }
}