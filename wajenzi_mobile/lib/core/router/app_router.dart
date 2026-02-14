import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_client.dart';
import '../../presentation/providers/auth_provider.dart';
import '../../presentation/providers/settings_provider.dart';
import '../../presentation/screens/landing/landing_screen.dart';
import '../../presentation/screens/auth/login_screen.dart';
import '../../presentation/screens/dashboard/dashboard_screen.dart';
import '../../presentation/screens/dashboard/client_dashboard_screen.dart';
import '../../presentation/screens/attendance/attendance_screen.dart';
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
import '../../presentation/screens/projects/staff_projects_screen.dart';
import '../../presentation/screens/billing/staff_billing_screen.dart';
import '../../presentation/screens/procurement/procurement_screen.dart';
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
            path: '/staff-projects',
            name: 'staff-projects',
            builder: (context, state) => const StaffProjectsScreen(),
          ),
          GoRoute(
            path: '/staff-billing',
            name: 'staff-billing',
            builder: (context, state) => const StaffBillingScreen(),
          ),
          GoRoute(
            path: '/procurement',
            name: 'procurement',
            builder: (context, state) => const ProcurementScreen(),
          ),
          GoRoute(
            path: '/attendance',
            name: 'attendance',
            builder: (context, state) => const AttendanceScreen(),
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
      if (location.startsWith('/staff-projects')) currentIndex = 0;
      if (location.startsWith('/staff-billing')) currentIndex = 1;
      if (location.startsWith('/dashboard')) currentIndex = 2;
      if (location.startsWith('/procurement')) currentIndex = 3;
      if (location.startsWith('/attendance')) currentIndex = 4;
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

// Provider fetches menus once per session, auto-disposed on logout
final _drawerMenusProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/menus');
  return response.data['data'] as List? ?? [];
});

class MainDrawer extends ConsumerWidget {
  final bool isClient;

  const MainDrawer({super.key, this.isClient = false});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;
    final isDarkMode = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final menusAsync = isClient ? null : ref.watch(_drawerMenusProvider);

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
                      user!.email,
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
            const SizedBox(height: 8),

            // Menu items
            Expanded(
              child: isClient
                  ? _buildClientMenu(context, isDarkMode, isSwahili)
                  : _buildStaffMenu(context, ref, menusAsync, isDarkMode, isSwahili),
            ),

            // Logout button
            Padding(
              padding: const EdgeInsets.all(16),
              child: SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () async {
                    final confirm = await showDialog<bool>(
                      context: context,
                      builder: (ctx) => AlertDialog(
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
                            onPressed: () => Navigator.pop(ctx, false),
                            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
                          ),
                          TextButton(
                            onPressed: () => Navigator.pop(ctx, true),
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

  Widget _buildClientMenu(BuildContext context, bool isDarkMode, bool isSwahili) {
    return ListView(
      padding: EdgeInsets.zero,
      children: [
        _DrawerItem(
          icon: Icons.dashboard_rounded,
          label: isSwahili ? 'Dashibodi' : 'Dashboard',
          isDarkMode: isDarkMode,
          onTap: () { Navigator.pop(context); context.go('/dashboard'); },
        ),
        _DrawerItem(
          icon: Icons.receipt_long_rounded,
          label: isSwahili ? 'Ankara' : 'Billing',
          isDarkMode: isDarkMode,
          onTap: () { Navigator.pop(context); context.go('/billing'); },
        ),
        _DrawerItem(
          icon: Icons.settings_rounded,
          label: isSwahili ? 'Mipangilio' : 'Settings',
          isDarkMode: isDarkMode,
          onTap: () { Navigator.pop(context); context.go('/settings'); },
        ),
      ],
    );
  }

  Widget _buildStaffMenu(
    BuildContext context,
    WidgetRef ref,
    AsyncValue<List<dynamic>>? menusAsync,
    bool isDarkMode,
    bool isSwahili,
  ) {
    if (menusAsync == null) return const SizedBox.shrink();

    return menusAsync.when(
      loading: () => const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: CircularProgressIndicator(strokeWidth: 2),
        ),
      ),
      error: (_, _) => ListView(
        padding: EdgeInsets.zero,
        children: [
          // Fallback to basic items on error
          _DrawerItem(
            icon: Icons.dashboard_rounded,
            label: 'Dashboard',
            isDarkMode: isDarkMode,
            onTap: () { Navigator.pop(context); context.go('/dashboard'); },
          ),
          _DrawerItem(
            icon: Icons.settings_rounded,
            label: isSwahili ? 'Mipangilio' : 'Settings',
            isDarkMode: isDarkMode,
            onTap: () { Navigator.pop(context); context.go('/settings'); },
          ),
        ],
      ),
      data: (menus) {
        return ListView(
          padding: EdgeInsets.zero,
          children: menus.map<Widget>((m) {
            final menu = m as Map<String, dynamic>;
            final name = menu['name'] as String? ?? '';
            final icon = _mapFaIcon(menu['icon'] as String? ?? '');
            final children = (menu['children'] as List?)?.cast<Map<String, dynamic>>() ?? [];

            if (children.isEmpty) {
              return _DrawerItem(
                icon: icon,
                label: name,
                isDarkMode: isDarkMode,
                onTap: () {
                  Navigator.pop(context);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('$name — coming soon'),
                      duration: const Duration(seconds: 1),
                    ),
                  );
                },
              );
            }

            return _ExpandableDrawerItem(
              icon: icon,
              label: name,
              isDarkMode: isDarkMode,
              children: children,
            );
          }).toList(),
        );
      },
    );
  }
}

/// Maps FontAwesome class names to Material Icons
IconData _mapFaIcon(String faClass) {
  // Mapping of FA icon classes to Material Icons
  const map = <String, IconData>{
    'fa fa-home': Icons.home_rounded,
    'si si-users': Icons.person_rounded,
    'fa fa-university': Icons.account_balance_rounded,
    'fa fa-flag': Icons.business_rounded,
    'fa fa-balance-scale': Icons.balance_rounded,
    'fa fa-building': Icons.apartment_rounded,
    'fa fa-users': Icons.groups_rounded,
    'fa fa-file-invoice': Icons.receipt_long_rounded,
    'fa fa-shopping-cart': Icons.shopping_cart_rounded,
    'fa fa-hard-hat': Icons.engineering_rounded,
    'fa fa-envelope': Icons.email_rounded,
    'fa fa-percent': Icons.percent_rounded,
    'fa fa-database': Icons.assessment_rounded,
    'fa fa-cog': Icons.settings_rounded,
    // Common child icons
    'fa fa-list': Icons.list_rounded,
    'fa fa-check': Icons.check_circle_rounded,
    'fa fa-calendar': Icons.calendar_today_rounded,
    'fa fa-share': Icons.payments_rounded,
    'fa fa-thumbs-up': Icons.inventory_rounded,
    'fa fa-comment': Icons.warehouse_rounded,
    'fa fa-clipboard': Icons.assignment_rounded,
    'fa fa-tags': Icons.label_rounded,
    'fa fa-file': Icons.description_rounded,
    'fa fa-book': Icons.menu_book_rounded,
    'fa fa-chart-line': Icons.show_chart_rounded,
    'fa fa-tachometer-alt': Icons.dashboard_rounded,
    'fa fa-quote-left': Icons.request_quote_rounded,
    'fa fa-file-invoice-dollar': Icons.receipt_rounded,
    'fa fa-credit-card': Icons.credit_card_rounded,
    'fa fa-box': Icons.inventory_2_rounded,
    'fa fa-shopping-bag': Icons.shopping_bag_rounded,
    'fa fa-calculator': Icons.calculate_rounded,
    'fa fa-car': Icons.directions_car_rounded,
    'fa fa-clipboard-list': Icons.fact_check_rounded,
    'fa fa-balance-scale-left': Icons.compare_arrows_rounded,
    'fa fa-truck': Icons.local_shipping_rounded,
    'fa fa-dolly': Icons.delivery_dining_rounded,
    'fa fa-search-plus': Icons.search_rounded,
    'fa fa-warehouse': Icons.warehouse_rounded,
    'fa fa-file-contract': Icons.handshake_rounded,
    'fa fa-clipboard-check': Icons.assignment_turned_in_rounded,
    'fa fa-money-bill-wave': Icons.payments_rounded,
    'fa fa-gavel': Icons.gavel_rounded,
    'fa fa-archive': Icons.archive_rounded,
    'fa fa-certificate': Icons.verified_rounded,
    'fa fa-cloud': Icons.cloud_rounded,
    'fa fa-bookmark': Icons.bookmark_rounded,
    'fa fa-upload': Icons.upload_rounded,
    'fa fa-plus': Icons.add_circle_rounded,
    'fa fa-minus': Icons.remove_circle_rounded,
    'fa fa-refresh': Icons.sync_rounded,
    'fa fa-bell': Icons.notifications_rounded,
    'fa fa-thermometer': Icons.thermostat_rounded,
    'fa fa-briefcase': Icons.work_rounded,
    'fa fa-exclamation-circle': Icons.warning_rounded,
    'fa fa-graduation-cap': Icons.school_rounded,
    'fa fa-clock': Icons.schedule_rounded,
    'fa fa-layer-group': Icons.layers_rounded,
    'fa fa-wrench': Icons.build_rounded,
    'fa fa-puzzle-piece': Icons.extension_rounded,
    'fa fa-file-text': Icons.article_rounded,
    'fa fa-user-tie': Icons.person_rounded,
  };

  return map[faClass] ?? Icons.circle_outlined;
}

class _ExpandableDrawerItem extends StatefulWidget {
  final IconData icon;
  final String label;
  final bool isDarkMode;
  final List<Map<String, dynamic>> children;

  const _ExpandableDrawerItem({
    required this.icon,
    required this.label,
    required this.isDarkMode,
    required this.children,
  });

  @override
  State<_ExpandableDrawerItem> createState() => _ExpandableDrawerItemState();
}

class _ExpandableDrawerItemState extends State<_ExpandableDrawerItem> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final textColor = widget.isDarkMode ? Colors.white : const Color(0xFF2C3E50);
    final subTextColor = widget.isDarkMode ? Colors.white70 : const Color(0xFF5D6D7E);

    return Column(
      children: [
        ListTile(
          leading: Icon(widget.icon, color: textColor.withValues(alpha: 0.8)),
          title: Text(
            widget.label,
            style: TextStyle(
              color: textColor,
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
          ),
          trailing: AnimatedRotation(
            turns: _expanded ? 0.5 : 0,
            duration: const Duration(milliseconds: 200),
            child: Icon(
              Icons.expand_more_rounded,
              color: textColor.withValues(alpha: 0.5),
              size: 20,
            ),
          ),
          onTap: () => setState(() => _expanded = !_expanded),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 0),
          dense: true,
          visualDensity: VisualDensity.compact,
        ),
        AnimatedCrossFade(
          firstChild: const SizedBox.shrink(),
          secondChild: Padding(
            padding: const EdgeInsets.only(left: 28),
            child: Column(
              children: widget.children.map((child) {
                final childName = child['name'] as String? ?? '';
                final childIcon = _mapFaIcon(child['icon'] as String? ?? '');
                return ListTile(
                  leading: Icon(childIcon, size: 18, color: subTextColor),
                  title: Text(
                    childName,
                    style: TextStyle(
                      color: subTextColor,
                      fontSize: 13,
                      fontWeight: FontWeight.w400,
                    ),
                  ),
                  onTap: () {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('$childName — coming soon'),
                        duration: const Duration(seconds: 1),
                      ),
                    );
                  },
                  dense: true,
                  visualDensity: VisualDensity.compact,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                );
              }).toList(),
            ),
          ),
          crossFadeState: _expanded ? CrossFadeState.showSecond : CrossFadeState.showFirst,
          duration: const Duration(milliseconds: 200),
        ),
      ],
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
          fontSize: 14,
        ),
      ),
      onTap: onTap,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 0),
      dense: true,
      visualDensity: VisualDensity.compact,
    );
  }
}
