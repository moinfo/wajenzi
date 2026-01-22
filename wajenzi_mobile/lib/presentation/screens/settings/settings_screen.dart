import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Mipangilio' : 'Settings'),
      ),
      body: ListView(
        children: [
          // Profile Section
          Container(
            padding: const EdgeInsets.all(20),
            color: AppColors.primary.withOpacity(0.05),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 36,
                  backgroundColor: AppColors.primary,
                  child: Text(
                    user?.name.substring(0, 1).toUpperCase() ?? 'U',
                    style: const TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user?.name ?? 'User',
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      Text(
                        user?.email ?? '',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: AppColors.textSecondary,
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
                IconButton(
                  icon: const Icon(Icons.edit_outlined),
                  onPressed: () {
                    // Navigate to edit profile
                  },
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Settings Sections
          _SettingsSection(
            title: isSwahili ? 'Akaunti' : 'Account',
            children: [
              _SettingsTile(
                icon: Icons.person_outline,
                title: isSwahili ? 'Wasifu' : 'Profile',
                onTap: () {},
              ),
              _SettingsTile(
                icon: Icons.lock_outline,
                title: isSwahili ? 'Badilisha Nenosiri' : 'Change Password',
                onTap: () {},
              ),
            ],
          ),

          _SettingsSection(
            title: isSwahili ? 'Mipangilio ya Programu' : 'App Settings',
            children: [
              _SettingsTile(
                icon: Icons.notifications_outlined,
                title: isSwahili ? 'Arifa' : 'Notifications',
                trailing: Switch(
                  value: true,
                  onChanged: (value) {},
                ),
              ),
              _SettingsTile(
                icon: Icons.sync_outlined,
                title: isSwahili ? 'Hali ya Usawazishaji' : 'Sync Status',
                subtitle: isSwahili ? 'Ulisawazishwa: dakika 5 zilizopita' : 'Last synced: 5 minutes ago',
                onTap: () {},
              ),
              _SettingsTile(
                icon: Icons.language,
                title: isSwahili ? 'Lugha' : 'Language',
                subtitle: isSwahili ? 'Kiswahili' : 'English',
                onTap: () {},
              ),
            ],
          ),

          _SettingsSection(
            title: isSwahili ? 'Kuhusu' : 'About',
            children: [
              _SettingsTile(
                icon: Icons.info_outline,
                title: isSwahili ? 'Kuhusu Wajenzi' : 'About Wajenzi',
                onTap: () {},
              ),
              _SettingsTile(
                icon: Icons.privacy_tip_outlined,
                title: isSwahili ? 'Sera ya Faragha' : 'Privacy Policy',
                onTap: () {},
              ),
              _SettingsTile(
                icon: Icons.description_outlined,
                title: isSwahili ? 'Masharti ya Huduma' : 'Terms of Service',
                onTap: () {},
              ),
            ],
          ),

          const SizedBox(height: 16),

          // Logout Button
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: OutlinedButton.icon(
              onPressed: () async {
                final confirm = await showDialog<bool>(
                  context: context,
                  builder: (context) => AlertDialog(
                    title: Text(isSwahili ? 'Ondoka' : 'Logout'),
                    content: Text(isSwahili
                        ? 'Una uhakika unataka kuondoka?'
                        : 'Are you sure you want to logout?'),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(context, false),
                        child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
                      ),
                      TextButton(
                        onPressed: () => Navigator.pop(context, true),
                        child: Text(isSwahili ? 'Ondoka' : 'Logout'),
                      ),
                    ],
                  ),
                );

                if (confirm == true) {
                  await ref.read(authStateProvider.notifier).logout();
                  if (context.mounted) {
                    context.go('/login');
                  }
                }
              },
              icon: const Icon(Icons.logout),
              label: Text(isSwahili ? 'Ondoka' : 'Logout'),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.error,
                side: BorderSide(color: AppColors.error),
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),

          const SizedBox(height: 24),

          // Version Info
          Center(
            child: Text(
              isSwahili ? 'Toleo 1.0.0' : 'Version 1.0.0',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: AppColors.textHint,
                  ),
            ),
          ),

          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

class _SettingsSection extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _SettingsSection({
    required this.title,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Text(
            title,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                  color: AppColors.textSecondary,
                  fontWeight: FontWeight.w600,
                ),
          ),
        ),
        ...children,
      ],
    );
  }
}

class _SettingsTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;

  const _SettingsTile({
    required this.icon,
    required this.title,
    this.subtitle,
    this.trailing,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: AppColors.textSecondary),
      title: Text(title),
      subtitle: subtitle != null ? Text(subtitle!) : null,
      trailing: trailing ?? (onTap != null ? const Icon(Icons.chevron_right) : null),
      onTap: onTap,
    );
  }
}
