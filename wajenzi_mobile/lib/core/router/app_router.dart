import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../presentation/providers/auth_provider.dart';
import '../../presentation/providers/settings_provider.dart';
import '../../presentation/screens/landing/landing_screen.dart';
import '../../presentation/screens/auth/login_screen.dart';
import '../../presentation/screens/dashboard/dashboard_screen.dart';
import '../../presentation/screens/dashboard/client_dashboard_screen.dart';
import '../../presentation/screens/attendance/attendance_screen.dart';
import '../../presentation/screens/reports/site_daily_report_list_screen.dart';
import '../../presentation/screens/expenses/expense_list_screen.dart';
import '../../presentation/screens/approvals/approvals_screen.dart';
import '../../presentation/screens/settings/settings_screen.dart';
import '../../presentation/screens/about/about_screen.dart';
import '../../presentation/screens/services/services_screen.dart';
import '../../presentation/screens/projects/projects_screen.dart';
import '../../presentation/screens/awards/awards_screen.dart';
import '../../presentation/screens/billing/client_billing_screen.dart';
import '../../presentation/screens/projects/client_project_detail_screen.dart';
import '../../presentation/screens/settings/profile_screen.dart';
import '../../presentation/screens/settings/change_password_screen.dart';
import '../../presentation/screens/settings/legal_screen.dart';
import '../../presentation/screens/dashboard/activities_screen.dart';
import '../../presentation/screens/dashboard/followups_screen.dart';
import '../../presentation/screens/dashboard/invoices_screen.dart';
import '../../presentation/widgets/curved_internal_nav.dart';

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
      GoRoute(
        path: '/profile',
        name: 'profile',
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: '/change-password',
        name: 'change-password',
        builder: (context, state) => const ChangePasswordScreen(),
      ),
      GoRoute(
        path: '/privacy-policy',
        name: 'privacy-policy',
        builder: (context, state) => LegalScreen.privacyPolicy(),
      ),
      GoRoute(
        path: '/terms-of-service',
        name: 'terms-of-service',
        builder: (context, state) => LegalScreen.termsOfService(),
      ),
      GoRoute(
        path: '/dashboard/activities',
        name: 'dashboard-activities',
        builder: (context, state) => const ActivitiesScreen(),
      ),
      GoRoute(
        path: '/dashboard/followups',
        name: 'dashboard-followups',
        builder: (context, state) => const FollowupsScreen(),
      ),
      GoRoute(
        path: '/dashboard/invoices',
        name: 'dashboard-invoices',
        builder: (context, state) => const InvoicesScreen(),
      ),
      GoRoute(
        path: '/project/:id',
        name: 'project-detail',
        builder: (context, state) => ClientProjectDetailScreen(
          projectId: int.parse(state.pathParameters['id']!),
          projectName: state.extra as String? ?? '',
        ),
      ),
      ShellRoute(
        builder: (context, state, child) {
          return MainScaffold(child: child);
        },
        routes: [
          GoRoute(
            path: '/dashboard',
            name: 'dashboard',
            builder: (context, state) => Consumer(
              builder: (context, ref, _) {
                final userType = ref.watch(userTypeProvider);
                if (userType == 'client') {
                  return const ClientDashboardScreen();
                }
                return const DashboardScreen();
              },
            ),
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
            path: '/billing',
            name: 'billing',
            builder: (context, state) => const ClientBillingScreen(),
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

class MainScaffold extends ConsumerWidget {
  final Widget child;

  const MainScaffold({super.key, required this.child});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final location = GoRouterState.of(context).matchedLocation;
    final userType = ref.watch(userTypeProvider);
    final isClient = userType == 'client';

    int currentIndex = 0;
    if (isClient) {
      if (location.startsWith('/dashboard')) currentIndex = 0;
      if (location.startsWith('/billing')) currentIndex = 1;
      if (location.startsWith('/settings')) currentIndex = 2;
    } else {
      if (location.startsWith('/dashboard')) currentIndex = 0;
      if (location.startsWith('/attendance')) currentIndex = 1;
      if (location.startsWith('/reports')) currentIndex = 2;
      if (location.startsWith('/approvals')) currentIndex = 3;
    }

    return Scaffold(
      extendBody: true,
      drawer: MainDrawer(isClient: isClient),
      body: child,
      bottomNavigationBar: CurvedInternalNav(
        selectedIndex: currentIndex,
        isClient: isClient,
      ),
    );
  }
}

class MainDrawer extends ConsumerWidget {
  final bool isClient;

  const MainDrawer({super.key, this.isClient = false});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;
    final isDarkMode = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);

    return Drawer(
      backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: SafeArea(
        child: Column(
          children: [
            // Header with user info
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: isDarkMode
                      ? [const Color(0xFF0D3B34), const Color(0xFF0A2E28)]
                      : [const Color(0xFF1ABC9C), const Color(0xFF16A085)],
                ),
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(24),
                  bottomRight: Radius.circular(24),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    radius: 35,
                    backgroundColor: isDarkMode
                        ? const Color(0xFF1ABC9C).withValues(alpha: 0.3)
                        : Colors.white.withValues(alpha: 0.2),
                    child: Text(
                      user?.name.substring(0, 1).toUpperCase() ?? 'U',
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    user?.name ?? 'User',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  if (user?.email != null)
                    Text(
                      user!.email!,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.white.withValues(alpha: 0.8),
                      ),
                    ),
                  if (user?.designation != null)
                    Container(
                      margin: const EdgeInsets.only(top: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: isDarkMode
                            ? const Color(0xFF1ABC9C).withValues(alpha: 0.2)
                            : Colors.white.withValues(alpha: 0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        user!.designation!,
                        style: TextStyle(
                          fontSize: 11,
                          color: isDarkMode ? const Color(0xFF1ABC9C) : Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Menu Items
            if (isClient) ...[
              _DrawerItem(
                icon: Icons.dashboard_rounded,
                label: isSwahili ? 'Dashibodi' : 'Dashboard',
                isDarkMode: isDarkMode,
                onTap: () {
                  Navigator.pop(context);
                  context.go('/dashboard');
                },
              ),
              _DrawerItem(
                icon: Icons.receipt_long_rounded,
                label: isSwahili ? 'Ankara' : 'Billing',
                isDarkMode: isDarkMode,
                onTap: () {
                  Navigator.pop(context);
                  context.go('/billing');
                },
              ),
              _DrawerItem(
                icon: Icons.settings_rounded,
                label: isSwahili ? 'Mipangilio' : 'Settings',
                isDarkMode: isDarkMode,
                onTap: () {
                  Navigator.pop(context);
                  context.go('/settings');
                },
              ),
            ] else ...[
              _DrawerItem(
                icon: Icons.settings_rounded,
                label: isSwahili ? 'Mipangilio' : 'Settings',
                isDarkMode: isDarkMode,
                onTap: () {
                  Navigator.pop(context);
                  context.go('/settings');
                },
              ),
              _DrawerItem(
                icon: Icons.receipt_long_rounded,
                label: isSwahili ? 'Matumizi' : 'Expenses',
                isDarkMode: isDarkMode,
                onTap: () {
                  Navigator.pop(context);
                  context.go('/expenses');
                },
              ),
            ],
            _DrawerItem(
              icon: Icons.help_outline_rounded,
              label: isSwahili ? 'Msaada' : 'Help & Support',
              isDarkMode: isDarkMode,
              onTap: () {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(isSwahili ? 'Inakuja hivi karibuni!' : 'Coming soon!'),
                  ),
                );
              },
            ),

            const Spacer(),

            // Logout button
            Padding(
              padding: const EdgeInsets.all(16),
              child: SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () async {
                    final confirm = await showDialog<bool>(
                      context: context,
                      builder: (context) => AlertDialog(
                        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
                        title: Text(
                          isSwahili ? 'Ondoka' : 'Logout',
                          style: TextStyle(
                            color: isDarkMode ? Colors.white : const Color(0xFF2C3E50),
                          ),
                        ),
                        content: Text(
                          isSwahili
                              ? 'Una uhakika unataka kuondoka?'
                              : 'Are you sure you want to logout?',
                          style: TextStyle(
                            color: isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D),
                          ),
                        ),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(context, false),
                            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
                          ),
                          TextButton(
                            onPressed: () => Navigator.pop(context, true),
                            child: Text(
                              isSwahili ? 'Ondoka' : 'Logout',
                              style: const TextStyle(color: Color(0xFFE74C3C)),
                            ),
                          ),
                        ],
                      ),
                    );

                    if (confirm == true && context.mounted) {
                      await ref.read(authStateProvider.notifier).logout();
                      if (context.mounted) {
                        context.go('/');
                      }
                    }
                  },
                  icon: const Icon(Icons.logout_rounded, size: 20),
                  label: Text(isSwahili ? 'Ondoka' : 'Logout'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFFE74C3C),
                    side: const BorderSide(color: Color(0xFFE74C3C)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _DrawerItem({
    required this.icon,
    required this.label,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(
        icon,
        color: isDarkMode ? Colors.white70 : const Color(0xFF2C3E50),
      ),
      title: Text(
        label,
        style: TextStyle(
          color: isDarkMode ? Colors.white : const Color(0xFF2C3E50),
          fontWeight: FontWeight.w500,
        ),
      ),
      onTap: onTap,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 4),
    );
  }
}
