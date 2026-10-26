import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

/// The tiles of the web Settings page, as the server lists them for this user
/// (a tile shows only when the user has the permission named like the tile).
final _settingsHubProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/settings-hub');
  return (response.data['data'] as List? ?? const [])
      .cast<Map<String, dynamic>>();
});

/// Destinations the route resolver falls back to when it has no real match.
const _genericDestinations = {
  '/dashboard',
  '/settings',
  '/settings-hub',
  '/reports',
  '/employee-profile',
  '/attendance',
  '/vat-sales',
  '/finance',
  '/accounting',
  '/staff-projects',
};

class SettingsHubScreen extends ConsumerStatefulWidget {
  const SettingsHubScreen({super.key});

  @override
  ConsumerState<SettingsHubScreen> createState() => _SettingsHubScreenState();
}

class _SettingsHubScreenState extends ConsumerState<SettingsHubScreen> {
  String _query = '';

  void _open(Map<String, dynamic> tile) {
    final route = tile['route']?.toString();
    final path = tile['path']?.toString() ?? '/';
    final native = resolveMobileMenuDestination(route: route, url: path);

    if (native != null && !_genericDestinations.contains(native)) {
      context.push(native);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${tile['name']} is not available in the app yet.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_settingsHubProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Mipangilio' : 'Settings'),
        actions: [
          IconButton(
            tooltip: isSwahili ? 'Mapendeleo ya App' : 'App preferences',
            icon: const Icon(Icons.tune_rounded),
            onPressed: () => context.push('/settings'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_settingsHubProvider.future),
        child: async.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (_, __) => ListView(
            children: [
              const SizedBox(height: 120),
              Center(
                child: TextButton.icon(
                  onPressed: () => ref.invalidate(_settingsHubProvider),
                  icon: const Icon(Icons.refresh),
                  label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                ),
              ),
            ],
          ),
          data: (tiles) {
            final q = _query.trim().toLowerCase();
            final shown = tiles
                .where((t) => q.isEmpty || '${t['name']}'.toLowerCase().contains(q))
                .toList();

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                TextField(
                  onChanged: (v) => setState(() => _query = v),
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.search_rounded),
                    hintText: isSwahili ? 'Tafuta mipangilio…' : 'Search settings…',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    isDense: true,
                  ),
                ),
                const SizedBox(height: 12),
                if (shown.isEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 48),
                    child: Center(
                      child: Text(isSwahili ? 'Hakuna matokeo' : 'No settings found'),
                    ),
                  ),
                for (final tile in shown)
                  Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: Icon(Icons.settings_outlined, color: AppColors.accent(context)),
                      title: Text('${tile['name']}'),
                      trailing: const Icon(Icons.chevron_right_rounded),
                      onTap: () => _open(tile),
                    ),
                  ),
                const SizedBox(height: 90),
              ],
            );
          },
        ),
      ),
    );
  }
}
