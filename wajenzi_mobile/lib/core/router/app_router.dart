import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../presentation/providers/auth_provider.dart';
import '../../presentation/screens/landing/landing_screen.dart';
import '../../presentation/screens/auth/login_screen.dart';
import '../../presentation/screens/dashboard/dashboard_screen.dart';
import '../../presentation/screens/attendance/attendance_screen.dart';
import '../../presentation/screens/reports/site_daily_report_list_screen.dart';
import '../../presentation/screens/expenses/expense_list_screen.dart';
import '../../presentation/screens/approvals/approvals_screen.dart';
import '../../presentation/screens/settings/settings_screen.dart';
import '../../presentation/screens/about/about_screen.dart';
import '../../presentation/screens/services/services_screen.dart';
import '../../presentation/screens/projects/projects_screen.dart';
import '../../presentation/screens/awards/awards_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authStateProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final isLoggedIn = authState.valueOrNull?.isAuthenticated ?? false;
      final isOnLanding = state.matchedLocation == '/';
      final isOnLogin = state.matchedLocation == '/login';
      final isOnAbout = state.matchedLocation == '/about';
      final isOnServices = state.matchedLocation == '/services';
      final isOnProjects = state.matchedLocation == '/projects';
      final isOnAwards = state.matchedLocation == '/awards';
      final isOnPublicPage = isOnLanding || isOnLogin || isOnAbout || isOnServices || isOnProjects || isOnAwards;

      // Allow access to public pages without auth
      if (!isLoggedIn && !isOnPublicPage) {
        return '/';
      }

      // Redirect to dashboard if already logged in (except for about page)
      if (isLoggedIn && (isOnLanding || isOnLogin)) {
        return '/dashboard';
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/',
        name: 'landing',
        builder: (context, state) => const LandingScreen(),
      ),
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/about',
        name: 'about',
        builder: (context, state) => const AboutScreen(),
      ),
      GoRoute(
        path: '/services',
        name: 'services',
        builder: (context, state) => const ServicesScreen(),
      ),
      GoRoute(
        path: '/projects',
        name: 'projects',
        builder: (context, state) => const ProjectsScreen(),
      ),
      GoRoute(
        path: '/awards',
        name: 'awards',
        builder: (context, state) => const AwardsScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) {
          return MainScaffold(child: child);
        },
        routes: [
          GoRoute(
            path: '/dashboard',
            name: 'dashboard',
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/attendance',
            name: 'attendance',
            builder: (context, state) => const AttendanceScreen(),
          ),
          GoRoute(
            path: '/reports',
            name: 'reports',
            builder: (context, state) => const SiteDailyReportListScreen(),
          ),
          GoRoute(
            path: '/expenses',
            name: 'expenses',
            builder: (context, state) => const ExpenseListScreen(),
          ),
          GoRoute(
            path: '/approvals',
            name: 'approvals',
            builder: (context, state) => const ApprovalsScreen(),
          ),
          GoRoute(
            path: '/settings',
            name: 'settings',
            builder: (context, state) => const SettingsScreen(),
          ),
        ],
      ),
    ],
  );
});

class MainScaffold extends StatelessWidget {
  final Widget child;

  const MainScaffold({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: child,
      bottomNavigationBar: const MainBottomNavBar(),
    );
  }
}

class MainBottomNavBar extends ConsumerWidget {
  const MainBottomNavBar({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final location = GoRouterState.of(context).matchedLocation;

    int currentIndex = 0;
    if (location.startsWith('/dashboard')) currentIndex = 0;
    if (location.startsWith('/attendance')) currentIndex = 1;
    if (location.startsWith('/reports')) currentIndex = 2;
    if (location.startsWith('/approvals')) currentIndex = 3;
    if (location.startsWith('/settings')) currentIndex = 4;

    return NavigationBar(
      selectedIndex: currentIndex,
      onDestinationSelected: (index) {
        switch (index) {
          case 0:
            context.go('/dashboard');
            break;
          case 1:
            context.go('/attendance');
            break;
          case 2:
            context.go('/reports');
            break;
          case 3:
            context.go('/approvals');
            break;
          case 4:
            context.go('/settings');
            break;
        }
      },
      destinations: const [
        NavigationDestination(
          icon: Icon(Icons.dashboard_outlined),
          selectedIcon: Icon(Icons.dashboard),
          label: 'Home',
        ),
        NavigationDestination(
          icon: Icon(Icons.access_time_outlined),
          selectedIcon: Icon(Icons.access_time),
          label: 'Attendance',
        ),
        NavigationDestination(
          icon: Icon(Icons.description_outlined),
          selectedIcon: Icon(Icons.description),
          label: 'Reports',
        ),
        NavigationDestination(
          icon: Icon(Icons.check_circle_outline),
          selectedIcon: Icon(Icons.check_circle),
          label: 'Approvals',
        ),
        NavigationDestination(
          icon: Icon(Icons.settings_outlined),
          selectedIcon: Icon(Icons.settings),
          label: 'Settings',
        ),
      ],
    );
  }
}
