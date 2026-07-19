package com.pdfimagetools.app.ui.main

import androidx.activity.compose.BackHandler
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.FolderOpen
import androidx.compose.material.icons.filled.Handyman
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.PhotoCamera
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.CenterAlignedTopAppBar
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import androidx.navigation.NavHostController
import com.pdfimagetools.app.navigation.Screen
import com.pdfimagetools.app.ui.components.BannerAdView
import com.pdfimagetools.app.ui.home.HomeTabContent
import com.pdfimagetools.app.ui.recent.RecentFilesContent
import com.pdfimagetools.app.ui.settings.SettingsContent
import com.pdfimagetools.app.ui.theme.OutlineVariant
import com.pdfimagetools.app.ui.theme.Primary
import com.pdfimagetools.app.ui.theme.PrimaryContainer
import com.pdfimagetools.app.ui.tools.ToolsTabContent

enum class MainTab(val label: String, val icon: ImageVector) {
    HOME("Home", Icons.Filled.Home),
    FILES("Files", Icons.Filled.FolderOpen),
    TOOLS("Tools", Icons.Filled.Handyman),
    ACCOUNT("Account", Icons.Filled.Person)
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MainScaffold(navController: NavHostController, onExitApp: () -> Unit) {
    var selectedTab by remember { mutableStateOf(MainTab.HOME) }
    var showExitDialog by remember { mutableStateOf(false) }

    BackHandler(enabled = true) {
        if (selectedTab != MainTab.HOME) {
            selectedTab = MainTab.HOME
        } else {
            showExitDialog = true
        }
    }

    Scaffold(
        topBar = {
            CenterAlignedTopAppBar(
                title = { Text(if (selectedTab == MainTab.TOOLS) "All Tools" else "Z Scanner") },
                actions = {
                    if (selectedTab == MainTab.TOOLS) {
                        IconButton(onClick = { /* search coming soon */ }) {
                            Icon(Icons.Filled.Search, contentDescription = "Search")
                        }
                    }
                }
            )
        },
        bottomBar = {
            Column {
                BannerAdView()
                MainBottomNavBar(
                    selectedTab = selectedTab,
                    onTabSelected = { selectedTab = it },
                    onScanClick = { navController.navigate(Screen.QuickScan.route) }
                )
            }
        }
    ) { padding ->
        Box(modifier = Modifier.fillMaxSize().padding(padding)) {
            when (selectedTab) {
                MainTab.HOME -> HomeTabContent(navController)
                MainTab.FILES -> RecentFilesContent()
                MainTab.TOOLS -> ToolsTabContent(navController)
                MainTab.ACCOUNT -> SettingsContent()
            }
        }
    }

    if (showExitDialog) {
        AlertDialog(
            onDismissRequest = { showExitDialog = false },
            title = { Text("Exit app?") },
            text = { Text("Are you sure you want to close Z Scanner?") },
            confirmButton = { TextButton(onClick = onExitApp) { Text("Exit") } },
            dismissButton = { TextButton(onClick = { showExitDialog = false }) { Text("Continue") } }
        )
    }
}

@Composable
private fun MainBottomNavBar(
    selectedTab: MainTab,
    onTabSelected: (MainTab) -> Unit,
    onScanClick: () -> Unit
) {
    Column(modifier = Modifier.fillMaxWidth().background(MaterialTheme.colorScheme.surface)) {
        androidx.compose.material3.HorizontalDivider(color = OutlineVariant, thickness = 1.dp)
        androidx.compose.foundation.layout.Row(
            modifier = Modifier
                .fillMaxWidth()
                .height(72.dp)
                .padding(horizontal = 8.dp),
            horizontalArrangement = Arrangement.SpaceAround,
            verticalAlignment = Alignment.CenterVertically
        ) {
            NavItem(MainTab.HOME, selectedTab, onTabSelected)
            NavItem(MainTab.FILES, selectedTab, onTabSelected)

            // Center scan FAB
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Box(
                    modifier = Modifier
                        .size(52.dp)
                        .background(PrimaryContainer, CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    IconButton(onClick = onScanClick) {
                        Icon(Icons.Filled.PhotoCamera, contentDescription = "Scan", tint = MaterialTheme.colorScheme.onPrimaryContainer)
                    }
                }
                Text("Scan", style = MaterialTheme.typography.labelSmall, color = Primary)
            }

            NavItem(MainTab.TOOLS, selectedTab, onTabSelected)
            NavItem(MainTab.ACCOUNT, selectedTab, onTabSelected)
        }
    }
}

@Composable
private fun NavItem(tab: MainTab, selectedTab: MainTab, onTabSelected: (MainTab) -> Unit) {
    val isSelected = tab == selectedTab
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = Modifier.padding(horizontal = 4.dp)
    ) {
        IconButton(onClick = { onTabSelected(tab) }) {
            Icon(
                imageVector = tab.icon,
                contentDescription = tab.label,
                tint = if (isSelected) Primary else MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Text(
            text = tab.label,
            style = MaterialTheme.typography.labelSmall,
            color = if (isSelected) Primary else MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}
