package com.questionpapermaker.app.navigation

import androidx.compose.runtime.Composable
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NamedNavArgument
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.questionpapermaker.app.data.AppContainer
import com.questionpapermaker.app.ui.exportsuccess.ExportSuccessScreen
import com.questionpapermaker.app.ui.exportsuccess.ExportSuccessViewModel
import com.questionpapermaker.app.ui.home.HomeScreen
import com.questionpapermaker.app.ui.home.HomeViewModel
import com.questionpapermaker.app.ui.layout.PaperLayoutScreen
import com.questionpapermaker.app.ui.paperdetails.PaperDetailsScreen
import com.questionpapermaker.app.ui.paperdetails.PaperEditorViewModel
import com.questionpapermaker.app.ui.preview.PreviewScreen
import com.questionpapermaker.app.ui.preview.PreviewViewModel
import com.questionpapermaker.app.ui.sections.SectionBuilderScreen
import com.questionpapermaker.app.ui.sections.SectionBuilderViewModel
import com.questionpapermaker.app.ui.splash.SplashScreen
import com.questionpapermaker.app.util.GenericViewModelFactory

private val paperIdArgument: List<NamedNavArgument> = listOf(
    navArgument(Destination.ARG_PAPER_ID) { type = NavType.StringType }
)

@Composable
fun QuestionPaperMakerNavHost(container: AppContainer) {
    val navController = rememberNavController()

    NavHost(navController = navController, startDestination = Destination.Splash.route) {
        composable(Destination.Splash.route) {
            SplashScreen(
                onTimeout = {
                    navController.navigate(Destination.Home.route) {
                        popUpTo(Destination.Splash.route) { inclusive = true }
                    }
                }
            )
        }

        composable(Destination.Home.route) {
            val viewModel: HomeViewModel = viewModel(
                factory = GenericViewModelFactory { HomeViewModel(container.paperRepository) }
            )
            HomeScreen(
                viewModel = viewModel,
                onCreatePaper = { id -> navController.navigate(Destination.PaperDetails.createRoute(id)) },
                onOpenPaper = { id -> navController.navigate(Destination.SectionBuilder.createRoute(id)) },
                onOpenDetails = { id -> navController.navigate(Destination.PaperDetails.createRoute(id)) },
                onOpenPreview = { id -> navController.navigate(Destination.Preview.createRoute(id)) }
            )
        }

        composable(Destination.PaperDetails.route, arguments = paperIdArgument) { backStackEntry ->
            val paperId = backStackEntry.arguments?.getString(Destination.ARG_PAPER_ID) ?: return@composable
            val viewModel: PaperEditorViewModel = viewModel(
                factory = GenericViewModelFactory {
                    PaperEditorViewModel(paperId, container.paperRepository, container.defaultsRepository)
                }
            )
            PaperDetailsScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() },
                onNext = { navController.navigate(Destination.PaperLayout.createRoute(paperId)) }
            )
        }

        composable(Destination.PaperLayout.route, arguments = paperIdArgument) { backStackEntry ->
            val paperId = backStackEntry.arguments?.getString(Destination.ARG_PAPER_ID) ?: return@composable
            val viewModel: PaperEditorViewModel = viewModel(
                factory = GenericViewModelFactory {
                    PaperEditorViewModel(paperId, container.paperRepository, container.defaultsRepository)
                }
            )
            PaperLayoutScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() },
                onNext = { navController.navigate(Destination.SectionBuilder.createRoute(paperId)) },
                onPreview = { navController.navigate(Destination.Preview.createRoute(paperId)) }
            )
        }

        composable(Destination.SectionBuilder.route, arguments = paperIdArgument) { backStackEntry ->
            val paperId = backStackEntry.arguments?.getString(Destination.ARG_PAPER_ID) ?: return@composable
            val viewModel: SectionBuilderViewModel = viewModel(
                factory = GenericViewModelFactory { SectionBuilderViewModel(paperId, container.paperRepository) }
            )
            SectionBuilderScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() },
                onNext = { navController.navigate(Destination.Preview.createRoute(paperId)) }
            )
        }

        composable(Destination.Preview.route, arguments = paperIdArgument) { backStackEntry ->
            val paperId = backStackEntry.arguments?.getString(Destination.ARG_PAPER_ID) ?: return@composable
            val viewModel: PreviewViewModel = viewModel(
                factory = GenericViewModelFactory {
                    PreviewViewModel(paperId, container.paperRepository, container.pdfExporter, container.appContext)
                }
            )
            PreviewScreen(
                viewModel = viewModel,
                onBack = { navController.popBackStack() },
                onExported = {
                    navController.navigate(Destination.ExportSuccess.createRoute(paperId))
                }
            )
        }

        composable(Destination.ExportSuccess.route, arguments = paperIdArgument) { backStackEntry ->
            val paperId = backStackEntry.arguments?.getString(Destination.ARG_PAPER_ID) ?: return@composable
            val viewModel: ExportSuccessViewModel = viewModel(
                factory = GenericViewModelFactory { ExportSuccessViewModel(paperId, container.paperRepository) }
            )
            ExportSuccessScreen(
                viewModel = viewModel,
                onBackToHome = {
                    navController.popBackStack(Destination.Home.route, inclusive = false)
                }
            )
        }
    }
}
