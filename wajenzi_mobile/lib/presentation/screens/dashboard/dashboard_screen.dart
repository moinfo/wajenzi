import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () {
            Scaffold.of(context).openDrawer();
          },
        ),
        title: Text(isSwahili ? 'Dashibodi' : 'Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {
              // Navigate to notifications
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          // Refresh dashboard data
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Welcome Card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 30,
                        backgroundColor: AppColors.primary.withOpacity(0.1),
                        child: Text(
                          user?.name.substring(0, 1).toUpperCase() ?? 'U',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isSwahili ? 'Karibu tena,' : 'Welcome back,',
                              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: AppColors.textSecondary,
                                  ),
                            ),
                            Text(
                              user?.name ?? 'User',
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.bold,
                                  ),
                            ),
                            if (user?.designation != null)
                              Text(
                                user!.designation!,
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                      color: AppColors.textSecondary,
                                    ),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),

              // Quick Stats
              Text(
                isSwahili ? 'Takwimu za Haraka' : 'Quick Stats',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 12),
              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 2,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 1.5,
                children: [
                  _StatCard(
                    title: isSwahili ? 'Idhini Zinazosubiri' : 'Pending Approvals',
                    value: '5',
                    icon: Icons.pending_actions,
                    color: AppColors.warning,
                  ),
                  _StatCard(
                    title: isSwahili ? 'Ripoti za Leo' : 'Reports Today',
                    value: '2',
                    icon: Icons.description,
                    color: AppColors.info,
                  ),
                  _StatCard(
                    title: isSwahili ? 'Miradi Inayoendelea' : 'Active Projects',
                    value: '3',
                    icon: Icons.folder_open,
                    color: AppColors.success,
                  ),
                  _StatCard(
                    title: isSwahili ? 'Matumizi (Mwezi)' : 'Expenses (MTD)',
                    value: 'TZS 1.2M',
                    icon: Icons.payments,
                    color: AppColors.secondary,
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // Quick Actions
              Text(
                isSwahili ? 'Vitendo vya Haraka' : 'Quick Actions',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _QuickActionChip(
                    label: isSwahili ? 'Ripoti Mpya' : 'New Report',
                    icon: Icons.add,
                    onPressed: () {},
                  ),
                  _QuickActionChip(
                    label: isSwahili ? 'Rekodi Matumizi' : 'Record Expense',
                    icon: Icons.receipt_long,
                    onPressed: () {},
                  ),
                  _QuickActionChip(
                    label: isSwahili ? 'Kuingia' : 'Check In',
                    icon: Icons.login,
                    onPressed: () {},
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Icon(icon, color: color, size: 28),
            const Spacer(),
            Text(
              value,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            Text(
              title,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: AppColors.textSecondary,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickActionChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final VoidCallback onPressed;

  const _QuickActionChip({
    required this.label,
    required this.icon,
    required this.onPressed,
  });

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      avatar: Icon(icon, size: 18),
      label: Text(label),
      onPressed: onPressed,
    );
  }
}
