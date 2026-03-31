import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_client.dart';
import '../services/external_launcher_service.dart';
import '../../presentation/providers/auth_provider.dart';
import '../../presentation/providers/settings_provider.dart';
import '../../presentation/screens/landing/landing_screen.dart';
import '../../presentation/screens/auth/login_screen.dart';
import '../../presentation/screens/dashboard/dashboard_screen.dart';
import '../../presentation/screens/dashboard/client_dashboard_screen.dart';
import '../../presentation/screens/attendance/attendance_screen.dart';
import '../../presentation/screens/expenses/expense_list_screen.dart';
import '../../presentation/screens/labor/labor_dashboard_screen.dart';
import '../../presentation/screens/labor/labor_requests_screen.dart';
import '../../presentation/screens/labor/labor_contracts_screen.dart';
import '../../presentation/screens/labor/labor_logs_screen.dart';
import '../../presentation/screens/labor/labor_inspections_screen.dart';
import '../../presentation/screens/labor/labor_payments_screen.dart';
import '../../presentation/screens/architect_bonus_screen.dart';
import '../../presentation/screens/architect_bonus_weights_screen.dart';
import '../../presentation/screens/provision_tax_screen.dart';
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
import '../../presentation/screens/projects/project_clients_screen.dart';
import '../../presentation/screens/projects/project_documents_screen.dart';
import '../../presentation/screens/projects/leads_screen.dart';
import '../../presentation/screens/projects/project_reports_screen.dart';
import '../../presentation/screens/projects/project_schedules_screen.dart';
import '../../presentation/screens/projects/project_types_screen.dart';
import '../../presentation/screens/projects/sites_screen.dart';
import '../../presentation/screens/projects/site_supervisor_assignments_screen.dart';
import '../../presentation/screens/projects/boq_list_screen.dart';
import '../../presentation/screens/materials/project_materials_screen.dart';
import '../../presentation/screens/materials/material_inventory_screen.dart';
import '../../presentation/screens/sales/sales_screen.dart';
import '../../presentation/screens/purchases/purchases_screen.dart';
import '../../presentation/screens/attendance/site_visits_screen.dart';
import '../../presentation/screens/billing/staff_billing_screen.dart';
import '../../presentation/screens/billing/billing_quotations_screen.dart';
import '../../presentation/screens/billing/billing_proformas_screen.dart';
import '../../presentation/screens/billing/billing_invoices_screen.dart';
import '../../presentation/screens/billing/billing_payments_screen.dart';
import '../../presentation/screens/billing/billing_emails_screen.dart';
import '../../presentation/screens/billing/billing_products_screen.dart';
import '../../presentation/screens/procurement/procurement_screen.dart';
import '../../presentation/screens/employee_profile/employee_profile_screen.dart';
import '../../presentation/screens/reports/site_daily_report_list_screen.dart';
import '../../presentation/screens/reports/project_daily_report_list_screen.dart';
import '../../presentation/screens/reports/sales_daily_report_list_screen.dart';
import '../../presentation/screens/reports/architect_bonus_report_screen.dart';
import '../../presentation/screens/reports/reports_hub_screen.dart';
import '../../presentation/screens/vat/vat_sales_screen.dart';
import '../../presentation/screens/vat/vat_purchases_screen.dart';
import '../../presentation/screens/vat/vat_auto_purchases_screen.dart';
import '../../presentation/screens/vat/vat_payments_screen.dart';
import '../../presentation/screens/accounting/accounting_screen.dart';
import '../../presentation/screens/accounting/account_types_screen.dart';
import '../../presentation/screens/accounting/chart_account_usages_screen.dart';
import '../../presentation/screens/accounting/charts_of_accounts_screen.dart';
import '../../presentation/screens/accounting/imprest_requests_screen.dart';
import '../../presentation/screens/accounting/exchange_rates_screen.dart';
import '../../presentation/screens/accounting/statutory_payments_screen.dart';
import '../../presentation/screens/accounting/statutory_category_report_screen.dart';
import '../../presentation/screens/accounting/statutory_payment_report_screen.dart';
import '../../presentation/screens/accounting/statutory_schedules_report_screen.dart';
import '../../presentation/screens/accounting/chart_account_variables_screen.dart';
import '../../presentation/screens/accounting/building_types_screen.dart';
import '../../presentation/screens/accounting/boq_item_categories_screen.dart';
import '../../presentation/screens/accounting/boq_items_screen.dart';
import '../../presentation/screens/accounting/boq_templates_screen.dart';
import '../../presentation/screens/accounting/construction_stages_screen.dart';
import '../../presentation/screens/accounting/settings_activities_screen.dart';
import '../../presentation/screens/accounting/settings_sub_activities_screen.dart';
import '../../presentation/screens/accounting/petty_cash_refill_requests_screen.dart';
import '../../presentation/screens/staff/staff_bank_details_screen.dart';
import '../../presentation/screens/staff/adjustments_screen.dart';
import '../../presentation/screens/staff/payroll_screen.dart';
import '../../presentation/screens/staff/payroll_administration_screen.dart';
import '../../presentation/screens/staff/allowances_screen.dart';
import '../../presentation/screens/staff/advance_salaries_screen.dart';
import '../../presentation/screens/staff/attendance_types_screen.dart';
import '../../presentation/screens/staff/crdb_bank_file_screen.dart';
import '../../presentation/screens/staff/staff_salaries_screen.dart';
import '../../presentation/screens/staff/staff_loans_screen.dart';
import '../../presentation/screens/staff/deductions_screen.dart';
import '../../presentation/screens/staff/deduction_subscriptions_screen.dart';
import '../../presentation/screens/staff/salary_slips_screen.dart';
import '../../presentation/screens/staff/leave_managements_screen.dart';
import '../../presentation/screens/staff/leave_requests_screen.dart';
import '../../presentation/screens/staff/leave_types_screen.dart';
import '../../presentation/screens/staff/leave_dashboard_screen.dart';
import '../../presentation/screens/notifications/notifications_screen.dart';
import '../../presentation/screens/messages/messages_screen.dart';
import '../../presentation/widgets/curved_internal_nav.dart';

final rootScaffoldKeyProvider = Provider<GlobalKey<ScaffoldState>>((ref) {
  return GlobalKey<ScaffoldState>();
});

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
      final isOnPublicPage =
          isOnLanding ||
          isOnLogin ||
          isOnAbout ||
          isOnServices ||
          isOnProjects ||
          isOnAwards;

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
            path: '/project-clients',
            name: 'project-clients',
            builder: (context, state) => const ProjectClientsScreen(),
          ),
          GoRoute(
            path: '/leads',
            name: 'leads',
            builder: (context, state) => const LeadsScreen(),
          ),
          GoRoute(
            path: '/project-documents',
            name: 'project-documents',
            builder: (context, state) => const ProjectDocumentsScreen(),
          ),
          GoRoute(
            path: '/project-reports',
            name: 'project-reports',
            builder: (context, state) => const ProjectReportsScreen(),
          ),
          GoRoute(
            path: '/reports',
            name: 'reports',
            builder: (context, state) => const ReportsHubScreen(),
          ),
          GoRoute(
            path: '/project-schedules',
            name: 'project-schedules',
            builder: (context, state) => const ProjectSchedulesScreen(),
          ),
          GoRoute(
            path: '/project-types',
            name: 'project-types',
            builder: (context, state) => const ProjectTypesScreen(),
          ),
          GoRoute(
            path: '/boqs',
            name: 'boqs',
            builder: (context, state) => const BoqListScreen(),
          ),
          GoRoute(
            path: '/project-materials',
            name: 'project-materials',
            builder: (context, state) => const ProjectMaterialsScreen(),
          ),
          GoRoute(
            path: '/material-inventory',
            name: 'material-inventory',
            builder: (context, state) => const MaterialInventoryScreen(),
          ),
          GoRoute(
            path: '/stock-register',
            name: 'stock-register',
            builder: (context, state) =>
                const MaterialInventoryScreen(stockRegisterMode: true),
          ),
          GoRoute(
            path: '/sales',
            name: 'sales',
            builder: (context, state) => const SalesScreen(),
          ),
          GoRoute(
            path: '/purchases',
            name: 'purchases',
            builder: (context, state) => const PurchasesScreen(),
          ),
          GoRoute(
            path: '/site-visits',
            name: 'site-visits',
            builder: (context, state) => const SiteVisitsScreen(),
          ),
          GoRoute(
            path: '/staff-billing',
            name: 'staff-billing',
            builder: (context, state) => const StaffBillingScreen(),
          ),
          GoRoute(
            path: '/billing-quotations',
            name: 'billing-quotations',
            builder: (context, state) => const BillingQuotationsScreen(),
          ),
          GoRoute(
            path: '/billing-proformas',
            name: 'billing-proformas',
            builder: (context, state) => const BillingProformasScreen(),
          ),
          GoRoute(
            path: '/billing-invoices',
            name: 'billing-invoices',
            builder: (context, state) => const BillingInvoicesScreen(),
          ),
          GoRoute(
            path: '/billing-payments',
            name: 'billing-payments',
            builder: (context, state) => const BillingPaymentsScreen(),
          ),
          GoRoute(
            path: '/billing-emails',
            name: 'billing-emails',
            builder: (context, state) => const BillingEmailsScreen(),
          ),
          GoRoute(
            path: '/billing-products',
            name: 'billing-products',
            builder: (context, state) => const BillingProductsScreen(),
          ),
          GoRoute(
            path: '/procurement',
            name: 'procurement',
            builder: (context, state) => const ProcurementScreen(),
          ),
          GoRoute(
            path: '/material-requests',
            name: 'material-requests',
            builder: (context, state) =>
                const ProcurementScreen(materialRequestsOnly: true),
          ),
          GoRoute(
            path: '/supplier-quotations',
            name: 'supplier-quotations',
            builder: (context, state) =>
                const ProcurementScreen(supplierQuotationsOnly: true),
          ),
          GoRoute(
            path: '/quotation-comparisons',
            name: 'quotation-comparisons',
            builder: (context, state) =>
                const ProcurementScreen(quotationComparisonsOnly: true),
          ),
          GoRoute(
            path: '/purchase-orders',
            name: 'purchase-orders',
            builder: (context, state) =>
                const ProcurementScreen(purchaseOrdersOnly: true),
          ),
          GoRoute(
            path: '/record-deliveries',
            name: 'record-deliveries',
            builder: (context, state) =>
                const ProcurementScreen(recordDeliveriesOnly: true),
          ),
          GoRoute(
            path: '/supplier-receivings',
            name: 'supplier-receivings',
            builder: (context, state) =>
                const ProcurementScreen(supplierReceivingsOnly: true),
          ),
          GoRoute(
            path: '/material-inspections',
            name: 'material-inspections',
            builder: (context, state) =>
                const ProcurementScreen(materialInspectionsOnly: true),
          ),
          GoRoute(
            path: '/attendance',
            name: 'attendance',
            builder: (context, state) => const AttendanceScreen(),
          ),
          GoRoute(
            path: '/labor-dashboard',
            name: 'labor-dashboard',
            builder: (context, state) => const LaborDashboardScreen(),
          ),
          GoRoute(
            path: '/labor-requests',
            name: 'labor-requests',
            builder: (context, state) => const LaborRequestsScreen(),
          ),
          GoRoute(
            path: '/labor-contracts',
            name: 'labor-contracts',
            builder: (context, state) => const LaborContractsScreen(),
          ),
          GoRoute(
            path: '/labor-logs',
            name: 'labor-logs',
            builder: (context, state) => const LaborLogsScreen(),
          ),
          GoRoute(
            path: '/labor-inspections',
            name: 'labor-inspections',
            builder: (context, state) => const LaborInspectionsScreen(),
          ),
          GoRoute(
            path: '/labor-payments',
            name: 'labor-payments',
            builder: (context, state) => const LaborPaymentsScreen(),
          ),
          GoRoute(
            path: '/architect-bonus',
            name: 'architect-bonus',
            builder: (context, state) => const ArchitectBonusScreen(),
          ),
          GoRoute(
            path: '/architect-bonus/weights',
            name: 'architect-bonus-weights',
            builder: (context, state) => const ArchitectBonusWeightsScreen(),
          ),
          GoRoute(
            path: '/provision-tax',
            name: 'provision-tax',
            builder: (context, state) => const ProvisionTaxScreen(),
          ),
          GoRoute(
            path: '/architect-bonus/report',
            name: 'architect-bonus-report',
            builder: (context, state) => const ArchitectBonusReportScreen(),
          ),
          GoRoute(
            path: '/site-daily-reports',
            name: 'site-daily-reports',
            builder: (context, state) => const SiteDailyReportListScreen(),
          ),
          GoRoute(
            path: '/sales-daily-reports',
            name: 'sales-daily-reports',
            builder: (context, state) => const SalesDailyReportListScreen(),
          ),
          GoRoute(
            path: '/project-daily-reports',
            name: 'project-daily-reports',
            builder: (context, state) => const ProjectDailyReportListScreen(),
          ),
          GoRoute(
            path: '/sites',
            name: 'sites',
            builder: (context, state) => const SitesScreen(),
          ),
          GoRoute(
            path: '/site-supervisor-assignments',
            name: 'site-supervisor-assignments',
            builder: (context, state) =>
                const SiteSupervisorAssignmentsScreen(),
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
            path: '/notifications',
            name: 'notifications',
            builder: (context, state) => const NotificationsScreen(),
          ),
          GoRoute(
            path: '/messages',
            name: 'messages',
            builder: (context, state) => const MessagesScreen(),
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
          GoRoute(
            path: '/employee-profile',
            name: 'employee-profile',
            builder: (context, state) => const EmployeeProfileScreen(),
          ),
          GoRoute(
            path: '/vat-sales',
            name: 'vat-sales',
            builder: (context, state) => const VatSalesScreen(),
          ),
          GoRoute(
            path: '/vat-purchases',
            name: 'vat-purchases',
            builder: (context, state) => const VatPurchasesScreen(),
          ),
          GoRoute(
            path: '/vat-auto-purchases',
            name: 'vat-auto-purchases',
            builder: (context, state) => const VatAutoPurchasesScreen(),
          ),
          GoRoute(
            path: '/vat-payments',
            name: 'vat-payments',
            builder: (context, state) => const VatPaymentsScreen(),
          ),
          GoRoute(
            path: '/staff-bank-details',
            name: 'staff-bank-details',
            builder: (context, state) => const StaffBankDetailsScreen(),
          ),
          GoRoute(
            path: '/adjustments',
            name: 'adjustments',
            builder: (context, state) => const AdjustmentsScreen(),
          ),
          GoRoute(
            path: '/attendance-types',
            name: 'attendance-types',
            builder: (context, state) => const AttendanceTypesScreen(),
          ),
          GoRoute(
            path: '/payroll',
            name: 'payroll',
            builder: (context, state) => const PayrollScreen(),
          ),
          GoRoute(
            path: '/payroll-crdb-bank-file',
            name: 'payroll-crdb-bank-file',
            builder: (context, state) => const CrdbBankFileScreen(),
          ),
          GoRoute(
            path: '/payroll-administration',
            name: 'payroll-administration',
            builder: (context, state) => const PayrollAdministrationScreen(),
          ),
          GoRoute(
            path: '/payroll/salary-slips',
            name: 'payroll-salary-slips',
            builder: (context, state) => const SalarySlipsScreen(),
          ),
          GoRoute(
            path: '/allowances',
            name: 'allowances',
            builder: (context, state) => const AllowancesScreen(),
          ),
          GoRoute(
            path: '/advance-salaries',
            name: 'advance-salaries',
            builder: (context, state) => const AdvanceSalariesScreen(),
          ),
          GoRoute(
            path: '/staff-salaries',
            name: 'staff-salaries',
            builder: (context, state) => const StaffSalariesScreen(),
          ),
          GoRoute(
            path: '/staff-loans',
            name: 'staff-loans',
            builder: (context, state) => const StaffLoansScreen(),
          ),
          GoRoute(
            path: '/deductions',
            name: 'deductions',
            builder: (context, state) => const DeductionsScreen(),
          ),
          GoRoute(
            path: '/deduction-subscriptions',
            name: 'deduction-subscriptions',
            builder: (context, state) => const DeductionSubscriptionsScreen(),
          ),
          GoRoute(
            path: '/leave-requests',
            name: 'leave-requests',
            builder: (context, state) => const LeaveRequestsScreen(),
          ),
          GoRoute(
            path: '/leave-managements',
            name: 'leave-managements',
            builder: (context, state) => const LeaveManagementsScreen(),
          ),
          GoRoute(
            path: '/leave-types',
            name: 'leave-types',
            builder: (context, state) => const LeaveTypesScreen(),
          ),
          GoRoute(
            path: '/leave-dashboard',
            name: 'leave-dashboard',
            builder: (context, state) => const LeaveDashboardScreen(),
          ),
          GoRoute(
            path: '/accounting',
            name: 'accounting',
            builder: (context, state) => const AccountingScreen(),
          ),
          GoRoute(
            path: '/charts-of-accounts',
            name: 'charts-of-accounts',
            builder: (context, state) => const ChartsOfAccountsScreen(),
          ),
          GoRoute(
            path: '/account-types',
            name: 'account-types',
            builder: (context, state) => const AccountTypesScreen(),
          ),
          GoRoute(
            path: '/chart-account-usages',
            name: 'chart-account-usages',
            builder: (context, state) => const ChartAccountUsagesScreen(),
          ),
          GoRoute(
            path: '/petty-cash-refill-requests',
            name: 'petty-cash-refill-requests',
            builder: (context, state) => const PettyCashRefillRequestsScreen(),
          ),
          GoRoute(
            path: '/imprest-requests',
            name: 'imprest-requests',
            builder: (context, state) => const ImprestRequestsScreen(),
          ),
          GoRoute(
            path: '/exchange-rates',
            name: 'exchange-rates',
            builder: (context, state) => const ExchangeRatesScreen(),
          ),
          GoRoute(
            path: '/statutory-payments',
            name: 'statutory-payments',
            builder: (context, state) => const StatutoryPaymentsScreen(),
          ),
          GoRoute(
            path: '/reports-statutory-category-report',
            name: 'reports-statutory-category-report',
            builder: (context, state) => const StatutoryCategoryReportScreen(),
          ),
          GoRoute(
            path: '/reports-statutory-payment-report',
            name: 'reports-statutory-payment-report',
            builder: (context, state) => const StatutoryPaymentReportScreen(),
          ),
          GoRoute(
            path: '/reports-statutory-schedules-report',
            name: 'reports-statutory-schedules-report',
            builder: (context, state) => const StatutorySchedulesReportScreen(),
          ),
          GoRoute(
            path: '/chart-account-variables',
            name: 'chart-account-variables',
            builder: (context, state) => const ChartAccountVariablesScreen(),
          ),
          GoRoute(
            path: '/building-types',
            name: 'building-types',
            builder: (context, state) => const BuildingTypesScreen(),
          ),
          GoRoute(
            path: '/boq-item-categories',
            name: 'boq-item-categories',
            builder: (context, state) => const BoqItemCategoriesScreen(),
          ),
          GoRoute(
            path: '/boq-items',
            name: 'boq-items',
            builder: (context, state) => const BoqItemsScreen(),
          ),
          GoRoute(
            path: '/boq-templates',
            name: 'boq-templates',
            builder: (context, state) => const BoqTemplatesScreen(),
          ),
          GoRoute(
            path: '/construction-stages',
            name: 'construction-stages',
            builder: (context, state) => const ConstructionStagesScreen(),
          ),
          GoRoute(
            path: '/settings-activities',
            name: 'settings-activities',
            builder: (context, state) => const SettingsActivitiesScreen(),
          ),
          GoRoute(
            path: '/settings-sub-activities',
            name: 'settings-sub-activities',
            builder: (context, state) => const SettingsSubActivitiesScreen(),
          ),
        ],
      ),
    ],
  );
});

class MainScaffold extends ConsumerWidget {
  final Widget child;

  const MainScaffold({super.key, required this.child});

  int _resolveClientTabIndex(String location) {
    if (location.startsWith('/billing')) return 1;
    if (location.startsWith('/settings') ||
        location.startsWith('/profile') ||
        location.startsWith('/change-password') ||
        location.startsWith('/privacy-policy') ||
        location.startsWith('/terms-of-service')) {
      return 2;
    }
    return 0;
  }

  int _resolveStaffTabIndex(String location) {
    if (location.startsWith('/staff-projects') ||
        location.startsWith('/employee-profile')) {
      return 0;
    }

    if (location.startsWith('/staff-billing') ||
        location.startsWith('/billing-quotations') ||
        location.startsWith('/billing-proformas') ||
        location.startsWith('/billing-invoices') ||
        location.startsWith('/billing-payments') ||
        location.startsWith('/billing-products') ||
        location.startsWith('/billing-emails') ||
        location.startsWith('/payroll') ||
        location.startsWith('/statutory-payments') ||
        location.startsWith('/accounting') ||
        location.startsWith('/vat-')) {
      return 1;
    }

    if (location.startsWith('/dashboard') ||
        location.startsWith('/expenses') ||
        location.startsWith('/approvals') ||
        location.startsWith('/leave-requests') ||
        location.startsWith('/settings') ||
        location.startsWith('/staff-bank-details') ||
        location.startsWith('/adjustments')) {
      return 2;
    }

    if (location.startsWith('/procurement')) return 3;
    if (location.startsWith('/attendance') ||
        location.startsWith('/site-daily-reports') ||
        location.startsWith('/site-visits')) {
      return 4;
    }

    return 2;
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final location = GoRouterState.of(context).matchedLocation;
    final userType = ref.watch(userTypeProvider);
    final isClient = userType == 'client';
    final scaffoldKey = ref.watch(rootScaffoldKeyProvider);

    final currentIndex = isClient
        ? _resolveClientTabIndex(location)
        : _resolveStaffTabIndex(location);

    return Scaffold(
      key: scaffoldKey,
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
final _drawerMenusProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/menus');
  return response.data['data'] as List? ?? [];
});

class MainDrawer extends ConsumerWidget {
  final bool isClient;

  const MainDrawer({super.key, this.isClient = false});

  void _navigateFromDrawer(BuildContext context, String route) {
    final router = GoRouter.of(context);
    Navigator.pop(context);
    router.go(route);
  }

  Future<void> _openMenuFromDrawer(
    BuildContext context, {
    required String label,
    required String? route,
    required String? url,
  }) async {
    final router = GoRouter.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final flutterRoute = resolveMobileMenuDestination(route: route, url: url);

    Navigator.pop(context);

    if (flutterRoute != null) {
      router.go(flutterRoute);
      return;
    }

    final opened = await ExternalLauncherService.openMenuUrl(url);
    if (!opened) {
      messenger.showSnackBar(
        SnackBar(
          content: Text('$label is not available in the mobile app yet.'),
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;
    final userInitial = (user?.name.isNotEmpty ?? false)
        ? user!.name.substring(0, 1).toUpperCase()
        : 'U';
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
                      userInitial,
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
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 4,
                      ),
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
                          color: isDarkMode
                              ? const Color(0xFF1ABC9C)
                              : Colors.white,
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
                  : _buildStaffMenu(
                      context,
                      ref,
                      menusAsync,
                      isDarkMode,
                      isSwahili,
                    ),
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
                        backgroundColor: isDarkMode
                            ? const Color(0xFF1A1A2E)
                            : Colors.white,
                        title: Text(
                          isSwahili ? 'Ondoka' : 'Logout',
                          style: TextStyle(
                            color: isDarkMode
                                ? Colors.white
                                : const Color(0xFF2C3E50),
                          ),
                        ),
                        content: Text(
                          isSwahili
                              ? 'Una uhakika unataka kuondoka?'
                              : 'Are you sure you want to logout?',
                          style: TextStyle(
                            color: isDarkMode
                                ? Colors.white70
                                : const Color(0xFF7F8C8D),
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

  Widget _buildClientMenu(
    BuildContext context,
    bool isDarkMode,
    bool isSwahili,
  ) {
    return ListView(
      padding: EdgeInsets.zero,
      children: [
        _DrawerItem(
          icon: Icons.dashboard_rounded,
          label: isSwahili ? 'Dashibodi' : 'Dashboard',
          isDarkMode: isDarkMode,
          onTap: () => _navigateFromDrawer(context, '/dashboard'),
        ),
        _DrawerItem(
          icon: Icons.receipt_long_rounded,
          label: isSwahili ? 'Ankara' : 'Billing',
          isDarkMode: isDarkMode,
          onTap: () => _navigateFromDrawer(context, '/billing'),
        ),
        _DrawerItem(
          icon: Icons.settings_rounded,
          label: isSwahili ? 'Mipangilio' : 'Settings',
          isDarkMode: isDarkMode,
          onTap: () => _navigateFromDrawer(context, '/settings'),
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
            onTap: () => _navigateFromDrawer(context, '/dashboard'),
          ),
          _DrawerItem(
            icon: Icons.settings_rounded,
            label: isSwahili ? 'Mipangilio' : 'Settings',
            isDarkMode: isDarkMode,
            onTap: () => _navigateFromDrawer(context, '/settings'),
          ),
        ],
      ),
      data: (menus) {
        return ListView(
          padding: EdgeInsets.zero,
          children: menus.map<Widget>((m) {
            final menu = m as Map<String, dynamic>;
            final name = menu['name'] as String? ?? '';
            final route = menu['route'] as String? ?? '';
            final menuUrl = menu['url'] as String?;
            final icon = _mapFaIcon(menu['icon'] as String? ?? '');
            final children =
                (menu['children'] as List?)?.cast<Map<String, dynamic>>() ?? [];

            if (children.isEmpty) {
              return _DrawerItem(
                icon: icon,
                label: name,
                isDarkMode: isDarkMode,
                onTap: () => _openMenuFromDrawer(
                  context,
                  label: name,
                  route: route,
                  url: menuUrl,
                ),
              );
            }

            return _ExpandableDrawerItem(
              icon: icon,
              label: name,
              isDarkMode: isDarkMode,
              children: children,
              onChildTap: (childName, childRoute, childUrl) =>
                  _openMenuFromDrawer(
                    context,
                    label: childName,
                    route: childRoute,
                    url: childUrl,
                  ),
            );
          }).toList(),
        );
      },
    );
  }
}

/// Maps web route names to Flutter routes (returns null if not yet implemented)
String? _mapWebRoute(String webRoute) {
  const map = <String, String>{
    'employee_profile': '/employee-profile',
    'home': '/dashboard',
    'dashboard': '/dashboard',
    'reports': '/reports',
    'attendance': '/attendance',
    'reports_daily_attendances_report': '/attendance',
    'reports_attendances_report': '/attendance',
    'settings': '/settings',
    'hr_settings': '/settings',
    'system_settings': '/settings',
    'user_settings': '/settings',
    'user_profile': '/profile',
    'user_inbox': '/dashboard',
    'user_notifications': '/notifications',
    'sales': '/sales',
    'sale': '/sales',
    'purchases': '/purchases',
    'purchase': '/purchases',
    'purchase_order': '/purchase-orders',
    'purchase_orders': '/purchase-orders',
    'purchase-orders': '/purchase-orders',
    'record_deliveries': '/record-deliveries',
    'record-deliveries': '/record-deliveries',
    'supplier_receiving': '/supplier-receivings',
    'supplier_receivings': '/supplier-receivings',
    'supplier_receivings_procurement': '/supplier-receivings',
    'supplier-receivings-procurement': '/supplier-receivings',
    'supplier-receivings': '/supplier-receivings',
    'receiving': '/procurement',
    'project_material_request': '/material-requests',
    'project_material_requests': '/material-requests',
    'material_inspection': '/material-inspections',
    'material_inspections': '/material-inspections',
    'quotation_comparisons': '/quotation-comparisons',
    'quotation_comparison': '/quotation-comparisons',
    'collection': '/staff-billing',
    'collections': '/staff-billing',
    'billing': '/staff-billing',
    'billing.index': '/staff-billing',
    'billing.dashboard': '/staff-billing',
    'billing.quotations.index': '/billing-quotations',
    'billing_quotations': '/billing-quotations',
    'billing-quotations': '/billing-quotations',
    'billing/quotations': '/billing-quotations',
    'billing.proformas.index': '/billing-proformas',
    'billing_proformas': '/billing-proformas',
    'billing-proformas': '/billing-proformas',
    'billing/proformas': '/billing-proformas',
    'billing.invoices.index': '/billing-invoices',
    'billing_invoices': '/billing-invoices',
    'billing-invoices': '/billing-invoices',
    'billing/invoices': '/billing-invoices',
    'billing.payments.index': '/billing-payments',
    'billing_payments': '/billing-payments',
    'billing-payments': '/billing-payments',
    'billing/payments': '/billing-payments',
    'billing.emails.index': '/billing-emails',
    'billing_emails': '/billing-emails',
    'billing-emails': '/billing-emails',
    'billing/emails': '/billing-emails',
    'billing.products.index': '/billing-products',
    'billing_products': '/billing-products',
    'billing-products': '/billing-products',
    'billing/products': '/billing-products',
    'billing.clients.index': '/staff-billing',
    'transaction_movement': '/accounting',
    'transaction_movements': '/accounting',
    'gross': '/accounting',
    'grosses': '/accounting',
    'bank_reconciliation': '/accounting',
    'bank_reconciliation_deposits': '/accounting',
    'bank_reconciliation_withdraws': '/accounting',
    'bank_reconciliation_transfers': '/accounting',
    'bank_reconciliation_sales_bank_deposited': '/accounting',
    'bank_reconciliation_suppliers_statement': '/accounting',
    'bank_reconciliation_bank_reconciliation_statement': '/accounting',
    'bank_deposit': '/accounting',
    'bank_deposits': '/accounting',
    'bank_withdraw': '/accounting',
    'bank_withdraws': '/accounting',
    'transfer': '/accounting',
    'transfer_reports': '/accounting',
    'financial_charges': '/accounting',
    'provision_tax': '/provision-tax',
    'provision-tax': '/provision-tax',
    'statutory_payments': '/statutory-payments',
    'statutory-payments': '/statutory-payments',
    'settings/statutory_payments': '/statutory-payments',
    'hr_settings_statutory_payments': '/statutory-payments',
    'auto_purchases': '/vat-auto-purchases',
    'vat_payment': '/vat-payments',
    'individual_vat_payment': '/vat-payments',
    'reports_vat_analysis': '/vat-sales',
    'reports_exempt_analysis': '/vat-purchases',
    'reports_vat_payment': '/vat-payments',
    'reports_statement_of_comprehensive_income_report': '/accounting',
    'reports_detailed_expenditure_statement_report': '/accounting',
    'reports_statement_of_financial_position_report': '/accounting',
    'reports_bank_report': '/accounting',
    'reports_bank_statement_report': '/accounting',
    'reports_business_position_report': '/accounting',
    'reports_business_position_details_report': '/accounting',
    'reports_bank_reconciliation_report': '/accounting',
    'reports_statement_report': '/accounting',
    'reports_collection_report': '/accounting',
    'reports_collection_per_system_report': '/accounting',
    'reports_gross_summary_report': '/accounting',
    'reports_expenses_report': '/expenses',
    'reports_expenses_per_system_report': '/expenses',
    'project_expenses': '/expenses',
    'reports_expenses_categories_report': '/expenses',
    'reports_expenses_sub_categories_report': '/expenses',
    'reports_purchases_report': '/procurement',
    'reports_purchases_by_supplier_report': '/procurement',
    'reports_supplier_receiving_report': '/procurement',
    'reports_supplier_transaction_report': '/procurement',
    'reports_sales_report': '/vat-sales',
    'reports_supplier_report': '/accounting',
    'reports_total_credit_suppliers_report': '/accounting',
    'reports_total_current_credit_suppliers_report': '/accounting',
    'reports_provision_report': '/accounting',
    'report': '/reports',
    'reports_index': '/reports',
    'reports.index': '/reports',
    'reports_statutory_payment_report': '/reports-statutory-payment-report',
    'reports/statutory_payment_report': '/reports-statutory-payment-report',
    'reports_statutory_category_report': '/reports-statutory-category-report',
    'reports/statutory_category_report': '/reports-statutory-category-report',
    'reports_statutory_schedules_report': '/reports-statutory-schedules-report',
    'reports/statutory_schedules_report': '/reports-statutory-schedules-report',
    'reports_deduction_report': '/employee-profile',
    'reports_allowance_subscriptions_report': '/employee-profile',
    'reports_annually_sales_summary_report': '/vat-sales',
    'reports_annually_purchases_summary_report': '/procurement',
    'reports_annually_expenses_summary_report': '/expenses',
    'reports_annually_expense_sub_categories_summary_report': '/expenses',
    'reports_annually_financial_charges_summary_report': '/accounting',
    'reports_annually_salaries_summary_report': '/employee-profile',
    'reports_annually_sdl_summary_report': '/employee-profile',
    'reports_annually_advance_salary_summary_report': '/employee-profile',
    'reports_annually_allowance_summary_report': '/employee-profile',
    'reports_annually_heslb_summary_report': '/employee-profile',
    'reports_annually_net_salary_summary_report': '/employee-profile',
    'reports_annually_nhif_summary_report': '/employee-profile',
    'reports_annually_nssf_summary_report': '/employee-profile',
    'reports_annually_deduction_report': '/employee-profile',
    'reports_annually_paye_summary_report': '/employee-profile',
    'reports_annually_wcf_summary_report': '/employee-profile',
    'reports_efd_report': '/vat-sales',
    'reports_detailed_efd_report': '/vat-sales',
    'reports_transaction_movement_report': '/accounting',
    'projects': '/staff-projects',
    'leads': '/leads',
    'staff': '/employee-profile',
    'project_clients': '/project-clients',
    'project_documents': '/project-documents',
    'project-document': '/project-documents',
    'project-documents': '/project-documents',
    'project_reports': '/project-reports',
    'project-report': '/project-reports',
    'project-reports': '/project-reports',
    'project_types': '/project-types',
    'project-type': '/project-types',
    'project-types': '/project-types',
    'project_boq': '/staff-projects',
    'project_boqs': '/boqs',
    'project_materials': '/project-materials',
    'project_material_inventory': '/material-inventory',
    'stock_register_select': '/stock-register',
    'stock_register': '/stock-register',
    'stock_register.movements': '/stock-register',
    'stock_register.issue': '/stock-register',
    'stock_register.adjust': '/stock-register',
    'project_site_visits': '/site-visits',
    'sites.index': '/sites',
    'sites.create': '/sites',
    'site-supervisor-assignments.index': '/site-supervisor-assignments',
    'site-supervisor-assignments.create': '/site-supervisor-assignments',
    'site-daily-reports.index': '/site-daily-reports',
    'site-daily-reports.create': '/site-daily-reports',
    'site-daily-reports.my-reports': '/site-daily-reports',
    'project_schedules': '/project-schedules',
    'project-schedule': '/project-schedules',
    'project-schedules': '/project-schedules',
    'project-schedules.index': '/project-schedules',
    'leaves_leave_request': '/leave-requests',
    'leaves/leave_request': '/leave-requests',
    'leave_request': '/leave-requests',
    'leave_managements': '/leave-managements',
    'leave-managements': '/leave-managements',
    'leaves_leave_managements': '/leave-managements',
    'leaves/leave_managements': '/leave-managements',
    'leave_types': '/leave-types',
    'leave-types': '/leave-types',
    'settings_leave_types': '/leave-types',
    'hr_settings_leave_types': '/leave-types',
    'settings/leave_types': '/leave-types',
    'leave_dashboard': '/leave-dashboard',
    'leave-dashboard': '/leave-dashboard',
    'leaves_leave_dashboard': '/leave-dashboard',
    'leaves/leave_dashboard': '/leave-dashboard',
    'salary_slips': '/payroll/salary-slips',
    'payroll_salary_slips': '/payroll/salary-slips',
    'payroll/salary_slips': '/payroll/salary-slips',
    'employee_salary_slip': '/payroll',
    'payroll_employee_salary_slip': '/payroll',
    'crdb_bank_file': '/payroll-crdb-bank-file',
    'crdb-bank-file': '/payroll-crdb-bank-file',
    'payroll_crdb_bank_file': '/payroll-crdb-bank-file',
    'payroll/crdb_bank_file': '/payroll-crdb-bank-file',
    'attendance_types': '/attendance-types',
    'attendance-types': '/attendance-types',
    'settings_attendance_types': '/attendance-types',
    'hr_settings_attendance_types': '/attendance-types',
    'settings/attendance_types': '/attendance-types',
    'approvals': '/approvals',
    'hr_settings_approvals': '/approvals',
    'payroll': '/payroll',
    'payroll_administration': '/payroll-administration',
    'payroll-administration': '/payroll-administration',
    'payroll_payroll_administration': '/payroll-administration',
    'allowances': '/allowances',
    'settings_allowances': '/allowances',
    'hr_settings_allowances': '/allowances',
    'deductions': '/deductions',
    'settings_deductions': '/deductions',
    'hr_settings_deductions': '/deductions',
    'payroll_deductions': '/deductions',
    'deduction_subscriptions': '/deduction-subscriptions',
    'deduction-subscriptions': '/deduction-subscriptions',
    'settings_deduction_subscriptions': '/deduction-subscriptions',
    'hr_settings_deduction_subscriptions': '/deduction-subscriptions',
    'payroll_deduction_subscriptions': '/deduction-subscriptions',
    'payroll_staff_bank_details': '/staff-bank-details',
    'staff_bank_details': '/staff-bank-details',
    'staff_bank_detail': '/staff-bank-details',
    'settings_staff_bank_details': '/staff-bank-details',
    'hr_settings_staff_bank_detail': '/staff-bank-details',
    'adjustment': '/adjustments',
    'adjustments': '/adjustments',
    'expenses': '/expenses',
    'accounting': '/accounting',
    'account_types': '/account-types',
    'account-types': '/account-types',
    'finance_financial_settings_account_types': '/account-types',
    'charts_of_accounts': '/charts-of-accounts',
    'charts-of-accounts': '/charts-of-accounts',
    'finance_financial_settings_charts_of_accounts': '/charts-of-accounts',
    'charts_of_account_usages': '/chart-account-usages',
    'charts-of-account-usages': '/chart-account-usages',
    'finance_financial_settings_charts_of_account_usages':
        '/chart-account-usages',
    'exchange_rates': '/exchange-rates',
    'exchange-rates': '/exchange-rates',
    'finance_financial_settings_exchange_rates': '/exchange-rates',
    'chart_of_account_variables': '/chart-account-variables',
    'chart-account-variables': '/chart-account-variables',
    'finance_financial_settings_chart_of_account_variables':
        '/chart-account-variables',
    'building_types': '/building-types',
    'building-types': '/building-types',
    'settings_building_types': '/building-types',
    'hr_settings_building_types': '/building-types',
    'boq_item_categories': '/boq-item-categories',
    'boq-item-categories': '/boq-item-categories',
    'settings_boq_item_categories': '/boq-item-categories',
    'hr_settings_boq_item_categories': '/boq-item-categories',
    'boq_items': '/boq-items',
    'boq-items': '/boq-items',
    'settings_boq_items': '/boq-items',
    'hr_settings_boq_items': '/boq-items',
    'boq_templates': '/boq-templates',
    'boq-templates': '/boq-templates',
    'settings_boq_templates': '/boq-templates',
    'hr_settings_boq_templates': '/boq-templates',
    'construction_stages': '/construction-stages',
    'construction-stages': '/construction-stages',
    'settings_construction_stages': '/construction-stages',
    'hr_settings_construction_stages': '/construction-stages',
    'activities': '/settings-activities',
    'settings_activities': '/settings-activities',
    'hr_settings_activities': '/settings-activities',
    'sub_activities': '/settings-sub-activities',
    'sub-activities': '/settings-sub-activities',
    'settings_sub_activities': '/settings-sub-activities',
    'hr_settings_sub_activities': '/settings-sub-activities',
    'procurement_dashboard': '/procurement',
    'site_daily_reports_my_reports': '/site-daily-reports',
    'site_daily_reports': '/site-daily-reports',
    'project_daily_reports': '/project-daily-reports',
    'project_daily_report': '/project-daily-reports',
    'client_dashboard': '/dashboard',
    'client_projects': '/projects',
    'client_billing': '/billing',
    'client_project': '/projects',
    'project': '/staff-projects',
    'project_create': '/staff-projects',
    'sales_daily_reports': '/sales-daily-reports',
    'sales_daily_report': '/sales-daily-reports',
    'sales-daily-reports': '/sales-daily-reports',
    'site_visits': '/site-visits',
    'material_requests': '/material-requests',
    'supplier_quotation': '/supplier-quotations',
    'supplier_quotations': '/supplier-quotations',
    'inspections': '/material-inspections',
    'bank_report': '/accounting',
    'bank_deposit_report': '/accounting',
    'bank_deposit_reports': '/accounting',
    'bank_withdraw_reports': '/accounting',
    'slip_review_report': '/accounting',
    'supplier_targets': '/accounting',
    'supplier_target_preparation': '/accounting',
    'supplier_targets_report': '/accounting',
    'supplier_commissions': '/accounting',
    'unrepresented_slip': '/accounting',
    'bank_reconciliations': '/accounting',
    'transfer_by_only_supplier_reports': '/accounting',
    'collection_search': '/accounting',
    'transaction_movement_search': '/accounting',
    'gross_search': '/accounting',
    'supplier_receiving_search': '/procurement',
    'expense': '/expenses',
    'expense_adjustable': '/adjustments',
    'site_management': '/attendance',
    'site_supervisor_assignments': '/attendance',
    'allowance_subscriptions': '/employee-profile',
    'staff_salaries': '/staff-salaries',
    'staff-salaries': '/staff-salaries',
    'settings_staff_salaries': '/staff-salaries',
    'hr_settings_staff_salary': '/staff-salaries',
    'advance_salaries': '/advance-salaries',
    'advance-salaries': '/advance-salaries',
    'settings_advance_salaries': '/advance-salaries',
    'hr_settings_advance_salary': '/advance-salaries',
    'advance_salary': '/advance-salaries',
    'settings_advance_salaries_6': '/advance-salaries',
    'staff_loans': '/staff-loans',
    'staff-loans': '/staff-loans',
    'settings_staff_loans': '/staff-loans',
    'hr_settings_staff_loan': '/staff-loans',
    'staff_loan': '/staff-loans',
    'petty_cash_refill_requests': '/petty-cash-refill-requests',
    'petty_cash_refill_request': '/petty-cash-refill-requests',
    'petty-cash-refill-requests': '/petty-cash-refill-requests',
    'finance_petty_cash_management_petty_cash_refill_requests':
        '/petty-cash-refill-requests',
    'imprest_requests': '/imprest-requests',
    'imprest_request': '/imprest-requests',
    'imprest-requests': '/imprest-requests',
    'finance_imprest_management_imprest_requests': '/imprest-requests',
    'statutory_payment': '/accounting',
    'hr_settings_statutory_payment': '/accounting',
    'client_sources': '/settings',
    'system_credit': '/accounting',
    'system_credits': '/accounting',
    'system_inventory': '/procurement',
    'system_inventories': '/procurement',
    'system_cash': '/accounting',
    'system_cashes': '/accounting',
    'system_capital': '/accounting',
    'system_capitals': '/accounting',
    'individual_projects': '/staff-projects',
    'individual_project_clients': '/project-clients',
    'individual_project_site_visits': '/site-visits',
    'site_daily_report': '/site-daily-reports',
    'labor.dashboard': '/labor-dashboard',
    'labor.requests.index': '/labor-requests',
    'labor.requests': '/labor-requests',
    'labor_requests': '/labor-requests',
    'labor/requests': '/labor-requests',
    'labor.contracts.index': '/labor-contracts',
    'labor.contracts': '/labor-contracts',
    'labor_contracts': '/labor-contracts',
    'labor/contracts': '/labor-contracts',
    'labor.logs.index': '/labor-logs',
    'labor.logs': '/labor-logs',
    'labor_logs': '/labor-logs',
    'labor/logs': '/labor-logs',
    'labor.inspections.index': '/labor-inspections',
    'labor.payments.index': '/labor-payments',
    'architect.bonus.index': '/architect-bonus',
    'architect.bonus.report': '/architect-bonus/report',
    'architect.bonus.weights': '/architect-bonus/weights',
    'architect-bonus': '/architect-bonus',
    'architect_bonus': '/architect-bonus',
    'architect-bonus/report': '/architect-bonus/report',
    'architect_bonus_report': '/architect-bonus/report',
    'eSMS': '/messages',
    'esms': '/messages',
    'bulk_sms': '/messages',
    'messages': '/messages',
    'requests.approval': '/attendance',
    'inspections.approval': '/attendance',
  };
  return map[webRoute];
}

String? _resolveWebRoute(String webRoute) {
  final direct = _mapWebRoute(webRoute);
  if (direct != null) return direct;

  final route = webRoute.toLowerCase();
  final normalizedRoute = route
      .replaceAll('.', '_')
      .replaceAll('-', '_')
      .replaceAll('/', '_');

  final normalizedDirect = _mapWebRoute(normalizedRoute);
  if (normalizedDirect != null) return normalizedDirect;

  if (route == '#' || route.isEmpty) return '/dashboard';
  if (route == 'reports' || route == 'report') return '/reports';
  if (route.contains('billing/quotation') ||
      route.contains('billing_quotation') ||
      route.contains('billing-quotation')) {
    return '/billing-quotations';
  }
  if (route.contains('billing/proforma') ||
      route.contains('billing_proforma') ||
      route.contains('billing-proforma')) {
    return '/billing-proformas';
  }
  if (route.contains('billing/invoice') ||
      route.contains('billing_invoice') ||
      route.contains('billing-invoice')) {
    return '/billing-invoices';
  }
  if (route.contains('billing/payment') ||
      route.contains('billing_payment') ||
      route.contains('billing-payment')) {
    return '/billing-payments';
  }
  if (route.contains('billing/product') ||
      route.contains('billing_product') ||
      route.contains('billing-product')) {
    return '/billing-products';
  }
  if (route.contains('billing/email') ||
      route.contains('billing_email') ||
      route.contains('billing-email')) {
    return '/billing-emails';
  }
  if (route.contains('provision_tax') || route.contains('provision-tax')) {
    return '/provision-tax';
  }
  if ((route.contains('architect') && route.contains('bonus')) &&
      route.contains('report')) {
    return '/architect-bonus/report';
  }
  if ((route.contains('architect') && route.contains('bonus')) &&
      route.contains('weights')) {
    return '/architect-bonus/weights';
  }
  if (route.contains('architect') && route.contains('bonus')) {
    return '/architect-bonus';
  }
  if (route.contains('sms') || route.contains('message')) {
    return '/messages';
  }
  if (route.contains('statutory_category_report') ||
      route.contains('statutory-category-report')) {
    return '/reports-statutory-category-report';
  }
  if (route.contains('statutory_payment_report') ||
      route.contains('statutory-payment-report')) {
    return '/reports-statutory-payment-report';
  }
  if (route.contains('statutory_schedules_report') ||
      route.contains('statutory-schedules-report')) {
    return '/reports-statutory-schedules-report';
  }
  if (route.contains('statutory_payment') ||
      route.contains('statutory-payment')) {
    return '/statutory-payments';
  }
  if (route.contains('expense')) return '/expenses';
  if (route.contains('approval')) return '/approvals';
  if (route.contains('procurement') ||
      route.contains('purchase') ||
      route.contains('quotation') ||
      route.contains('inspection') ||
      route.contains('receiving') ||
      route.contains('material_request')) {
    return route.contains('material_request')
        ? '/material-requests'
        : '/procurement';
  }
  if (route.contains('labor') && route.contains('request'))
    return '/labor-requests';
  if (route.contains('labor') && route.contains('contract'))
    return '/labor-contracts';
  if (route.contains('labor') && route.contains('log')) return '/labor-logs';
  if (route.contains('labor')) return '/labor-dashboard';
  if (route.contains('allowance') ||
      route.contains('salary') ||
      route.contains('loan') ||
      route.contains('deduction') ||
      route.contains('nhif') ||
      route.contains('nssf') ||
      route.contains('heslb') ||
      route.contains('paye') ||
      route.contains('wcf')) {
    return '/employee-profile';
  }
  if (route.contains('billing.') || route.contains('billing_')) {
    return '/staff-billing';
  }
  if (route.contains('project')) return '/staff-projects';
  if (route.contains('billing') || route.contains('invoice')) {
    return '/staff-billing';
  }
  if (route.contains('account') ||
      route.contains('bank_') ||
      route.contains('gross') ||
      route.contains('collection') ||
      route.contains('transaction_') ||
      route.contains('statutory') ||
      route.contains('financial_charge') ||
      route.contains('transfer')) {
    return '/accounting';
  }
  if (route.contains('sales') || route == 'sale') return '/sales';
  if (route.contains('efd')) return '/vat-sales';
  if (route.contains('bank_detail')) return '/staff-bank-details';
  if (route.contains('adjust')) return '/adjustments';
  if (route.contains('attendance_types') ||
      route.contains('attendance-types')) {
    return '/attendance-types';
  }
  if (route.contains('leave_request') || route.contains('leave-request')) {
    return '/leave-requests';
  }
  if (route.contains('leave_management') ||
      route.contains('leave-management')) {
    return '/leave-managements';
  }
  if (route.contains('leave_types') || route.contains('leave-types')) {
    return '/leave-types';
  }
  if (route.contains('crdb_bank_file') || route.contains('crdb-bank-file')) {
    return '/payroll-crdb-bank-file';
  }
  if (route.contains('payroll')) return '/payroll';
  if (route.contains('site_daily_report') ||
      route.contains('site-daily-report')) {
    return '/site-daily-reports';
  }
  if (route.contains('project_daily_report') ||
      route.contains('project-daily-report')) {
    return '/project-daily-reports';
  }
  if (route.contains('attendance') ||
      route.contains('site_visit') ||
      route.contains('site-visit') ||
      route.contains('site-supervisor') ||
      route.contains('site_') ||
      route.contains('site_management')) {
    return '/attendance';
  }
  if (route.contains('employee') || route.contains('staff')) {
    return '/employee-profile';
  }
  if (route.contains('report')) return '/dashboard';
  if (route.contains('setting')) return '/settings';
  if (route.contains('vat')) return '/vat-sales';

  return null;
}

String? resolveMobileMenuDestination({String? route, String? url}) {
  return _resolveMenuDestination(route: route, url: url);
}

String? _resolveMenuDestination({String? route, String? url}) {
  final normalizedRoute = route?.trim();
  if (normalizedRoute != null && normalizedRoute.isNotEmpty) {
    final direct = _resolveWebRoute(normalizedRoute);
    if (direct != null) return direct;
  }

  final normalizedUrl = url?.trim();
  if (normalizedUrl == null || normalizedUrl.isEmpty) {
    return null;
  }

  final uri = Uri.tryParse(normalizedUrl);
  final path = uri?.path.isNotEmpty == true ? uri!.path : normalizedUrl;
  final cleanedPath = path.trim().replaceFirst(RegExp(r'^/+'), '');

  if (cleanedPath.isEmpty) {
    return null;
  }

  return _resolveWebRoute(cleanedPath);
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
  final Future<void> Function(String name, String route, String? url)
  onChildTap;

  const _ExpandableDrawerItem({
    required this.icon,
    required this.label,
    required this.isDarkMode,
    required this.children,
    required this.onChildTap,
  });

  @override
  State<_ExpandableDrawerItem> createState() => _ExpandableDrawerItemState();
}

class _ExpandableDrawerItemState extends State<_ExpandableDrawerItem> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final textColor = widget.isDarkMode
        ? Colors.white
        : const Color(0xFF2C3E50);
    final subTextColor = widget.isDarkMode
        ? Colors.white70
        : const Color(0xFF5D6D7E);

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
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 20,
            vertical: 0,
          ),
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
                final childRoute = child['route'] as String? ?? '';
                final childUrl = child['url'] as String?;
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
                  onTap: () =>
                      widget.onChildTap(childName, childRoute, childUrl),
                  dense: true,
                  visualDensity: VisualDensity.compact,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                );
              }).toList(),
            ),
          ),
          crossFadeState: _expanded
              ? CrossFadeState.showSecond
              : CrossFadeState.showFirst,
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
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 0),
      dense: true,
      visualDensity: VisualDensity.compact,
    );
  }
}
