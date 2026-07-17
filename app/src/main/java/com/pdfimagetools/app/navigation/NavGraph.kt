package com.pdfimagetools.app.navigation

import androidx.compose.runtime.Composable
import androidx.navigation.NavHostController
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.pdfimagetools.app.core.image.ImageFormat
import com.pdfimagetools.app.ui.compressimage.CompressImageScreen
import com.pdfimagetools.app.ui.compresspdf.CompressPdfScreen
import com.pdfimagetools.app.ui.convertformat.ConvertFormatScreen
import com.pdfimagetools.app.ui.home.HomeScreen
import com.pdfimagetools.app.ui.imagetopdf.ImageToPdfScreen
import com.pdfimagetools.app.ui.mergepdf.MergePdfScreen
import com.pdfimagetools.app.ui.pdftoimage.PdfToImageScreen
import com.pdfimagetools.app.ui.recent.RecentFilesScreen
import com.pdfimagetools.app.ui.settings.SettingsScreen
import com.pdfimagetools.app.ui.splitpdf.SplitPdfScreen

@Composable
fun AppNavGraph(onExitApp: () -> Unit) {
    val navController: NavHostController = rememberNavController()

    NavHost(navController = navController, startDestination = Screen.Home.route) {
        composable(Screen.Home.route) {
            HomeScreen(navController = navController, onExitApp = onExitApp)
        }
        composable(Screen.ImageToPdf.route) {
            ImageToPdfScreen(onBack = { navController.popBackStack() })
        }
        composable(Screen.MergePdf.route) {
            MergePdfScreen(onBack = { navController.popBackStack() })
        }
        composable(Screen.SplitPdf.route) {
            SplitPdfScreen(onBack = { navController.popBackStack() })
        }
        composable(Screen.CompressPdf.route) {
            CompressPdfScreen(onBack = { navController.popBackStack() })
        }
        composable(Screen.CompressImage.route) {
            CompressImageScreen(onBack = { navController.popBackStack() })
        }
        composable(Screen.PdfToImage.route) {
            PdfToImageScreen(onBack = { navController.popBackStack() })
        }
        composable(
            route = Screen.ConvertFormat.route,
            arguments = listOf(navArgument(Screen.ConvertFormat.ARG_TARGET) { type = NavType.StringType })
        ) { backStackEntry ->
            val target = backStackEntry.arguments?.getString(Screen.ConvertFormat.ARG_TARGET)
            val format = if (target == Screen.ConvertFormat.TARGET_PNG) ImageFormat.PNG else ImageFormat.JPEG
            ConvertFormatScreen(targetFormat = format, onBack = { navController.popBackStack() })
        }
        composable(Screen.RecentFiles.route) {
            RecentFilesScreen(onBack = { navController.popBackStack() })
        }
        composable(Screen.Settings.route) {
            SettingsScreen(onBack = { navController.popBackStack() })
        }
    }
}
