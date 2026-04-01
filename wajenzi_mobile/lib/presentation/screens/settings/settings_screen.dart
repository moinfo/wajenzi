import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authStateProvider).valueOrNull;
    final user = authState?.user;
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);

    final backgroundColor = isDarkMode
        ? const Color(0xFF0F172A)
        : const Color(0xFFF4F7FB);
    final surfaceColor = isDarkMode
        ? const Color(0xFF182235)
        : Colors.white;
    final mutedSurfaceColor = isDarkMode
        ? const Color(0xFF111827)
        : const Color(0xFFF8FAFC);
    final borderColor = isDarkMode
        ? Colors.white.withValues(alpha: 0.08)
        : const Color(0xFFE2E8F0);
    final titleColor = isDarkMode ? Colors.white : const Color(0xFF1E293B);
    final subtitleColor = isDarkMode
        ? Colors.white70
        : const Color(0xFF64748B);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Mipangilio' : 'Settings'),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: isDarkMode
                    ? const [Color(0xFF1D4ED8), Color(0xFF0F766E)]
                    : const [Color(0xFF2563EB), Color(0xFF10B981)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(24),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.12),
                  blurRadius: 28,
                  offset: const Offset(0, 14),
                ),
              ],
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 28,
                  backgroundColor: Colors.white.withValues(alpha: 0.18),
                  child: Text(
                    _initials(user?.name),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Mapendeleo ya Programu' : 'App Preferences',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        user?.name?.trim().isNotEmpty == true
                            ? user!.name
                            : (isSwahili
                                  ? 'Dhibiti mwonekano na akaunti yako'
                                  : 'Manage your account and app experience'),
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      if ((user?.email ?? '').isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          user!.email,
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          _SectionTitle(
            title: isSwahili ? 'Akaunti' : 'Account',
            subtitle: isSwahili
                ? 'Usalama wa akaunti yako'
                : 'Keep your account secure',
            titleColor: titleColor,
            subtitleColor: subtitleColor,
          ),
          const SizedBox(height: 10),
          _SettingsCard(
            color: surfaceColor,
            borderColor: borderColor,
            child: Column(
              children: [
                _ActionRow(
                  icon: Icons.lock_outline_rounded,
                  iconColor: AppColors.primary,
                  title: isSwahili ? 'Badilisha nenosiri' : 'Change password',
                  subtitle: isSwahili
                      ? 'Sasisha nenosiri lako la kuingia'
                      : 'Update your sign-in password',
                  titleColor: titleColor,
                  subtitleColor: subtitleColor,
                  onTap: () => context.push('/change-password'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          _SectionTitle(
            title: isSwahili ? 'Mandhari' : 'Appearance',
            subtitle: isSwahili
                ? 'Chagua mwonekano unaokufaa'
                : 'Choose the look that works best for you',
            titleColor: titleColor,
            subtitleColor: subtitleColor,
          ),
          const SizedBox(height: 10),
          _SettingsCard(
            color: surfaceColor,
            borderColor: borderColor,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isSwahili ? 'Mtindo wa programu' : 'App theme',
                  style: TextStyle(
                    color: titleColor,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  isSwahili
                      ? 'Badili kati ya mwanga na giza kwa usomaji rahisi.'
                      : 'Switch between light and dark mode for comfortable viewing.',
                  style: TextStyle(
                    color: subtitleColor,
                    fontSize: 13,
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: _ChoiceTile(
                        label: isSwahili ? 'Mwanga' : 'Light',
                        icon: Icons.light_mode_rounded,
                        selected: !isDarkMode,
                        onTap: () =>
                            ref.read(settingsProvider.notifier).setDarkMode(false),
                        surfaceColor: mutedSurfaceColor,
                        borderColor: borderColor,
                        titleColor: titleColor,
                        subtitleColor: subtitleColor,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _ChoiceTile(
                        label: isSwahili ? 'Giza' : 'Dark',
                        icon: Icons.dark_mode_rounded,
                        selected: isDarkMode,
                        onTap: () =>
                            ref.read(settingsProvider.notifier).setDarkMode(true),
                        surfaceColor: mutedSurfaceColor,
                        borderColor: borderColor,
                        titleColor: titleColor,
                        subtitleColor: subtitleColor,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          _SectionTitle(
            title: isSwahili ? 'Lugha' : 'Language',
            subtitle: isSwahili
                ? 'Chagua lugha ya matumizi'
                : 'Select your preferred language',
            titleColor: titleColor,
            subtitleColor: subtitleColor,
          ),
          const SizedBox(height: 10),
          _SettingsCard(
            color: surfaceColor,
            borderColor: borderColor,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isSwahili ? 'Lugha ya programu' : 'App language',
                  style: TextStyle(
                    color: titleColor,
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  isSwahili
                      ? 'Chagua Kiingereza au Kiswahili kwa maandishi ya programu.'
                      : 'Choose English or Swahili for app labels and messages.',
                  style: TextStyle(
                    color: subtitleColor,
                    fontSize: 13,
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: _ChoiceTile(
                        label: 'English',
                        icon: Icons.language_rounded,
                        selected: !isSwahili,
                        onTap: () =>
                            ref.read(settingsProvider.notifier).setLanguage(false),
                        surfaceColor: mutedSurfaceColor,
                        borderColor: borderColor,
                        titleColor: titleColor,
                        subtitleColor: subtitleColor,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _ChoiceTile(
                        label: 'Kiswahili',
                        icon: Icons.translate_rounded,
                        selected: isSwahili,
                        onTap: () =>
                            ref.read(settingsProvider.notifier).setLanguage(true),
                        surfaceColor: mutedSurfaceColor,
                        borderColor: borderColor,
                        titleColor: titleColor,
                        subtitleColor: subtitleColor,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _initials(String? name) {
    final value = name?.trim() ?? '';
    if (value.isEmpty) return 'W';
    final parts = value.split(RegExp(r'\s+')).where((part) => part.isNotEmpty);
    final letters = parts.take(2).map((part) => part[0].toUpperCase()).join();
    return letters.isEmpty ? 'W' : letters;
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  final String subtitle;
  final Color titleColor;
  final Color subtitleColor;

  const _SectionTitle({
    required this.title,
    required this.subtitle,
    required this.titleColor,
    required this.subtitleColor,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: TextStyle(
            color: titleColor,
            fontSize: 18,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: TextStyle(
            color: subtitleColor,
            fontSize: 13,
          ),
        ),
      ],
    );
  }
}

class _SettingsCard extends StatelessWidget {
  final Widget child;
  final Color color;
  final Color borderColor;

  const _SettingsCard({
    required this.child,
    required this.color,
    required this.borderColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _ActionRow extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final String subtitle;
  final Color titleColor;
  final Color subtitleColor;
  final VoidCallback onTap;

  const _ActionRow({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.subtitle,
    required this.titleColor,
    required this.subtitleColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(icon, color: iconColor),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: TextStyle(
                        color: titleColor,
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: TextStyle(
                        color: subtitleColor,
                        fontSize: 13,
                        height: 1.4,
                      ),
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_right_rounded,
                color: subtitleColor,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ChoiceTile extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;
  final Color surfaceColor;
  final Color borderColor;
  final Color titleColor;
  final Color subtitleColor;

  const _ChoiceTile({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
    required this.surfaceColor,
    required this.borderColor,
    required this.titleColor,
    required this.subtitleColor,
  });

  @override
  Widget build(BuildContext context) {
    final activeColor = AppColors.primary;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: selected
                ? activeColor.withValues(alpha: 0.12)
                : surfaceColor,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected ? activeColor : borderColor,
              width: selected ? 1.5 : 1,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(
                    icon,
                    color: selected ? activeColor : subtitleColor,
                    size: 22,
                  ),
                  const Spacer(),
                  Icon(
                    selected
                        ? Icons.check_circle_rounded
                        : Icons.radio_button_unchecked_rounded,
                    color: selected ? activeColor : subtitleColor,
                    size: 20,
                  ),
                ],
              ),
              const SizedBox(height: 18),
              Text(
                label,
                style: TextStyle(
                  color: titleColor,
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
