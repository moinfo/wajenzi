import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';

/// WhatsApp Marketing — contacts pipeline + ad campaigns.
///
/// Mirrors the portal feature at `whatsapp_marketing.index`. Send-message uses
/// the native WhatsApp app via [ExternalLauncherService.openWhatsApp] fallback
/// chain (wa.me → api.whatsapp.com → portal).
class WhatsAppMarketingScreen extends ConsumerStatefulWidget {
  const WhatsAppMarketingScreen({super.key});

  @override
  ConsumerState<WhatsAppMarketingScreen> createState() =>
      _WhatsAppMarketingScreenState();
}

class _WhatsAppMarketingScreenState
    extends ConsumerState<WhatsAppMarketingScreen>
    with SingleTickerProviderStateMixin {
  late final TabController _tab =
      TabController(length: 2, vsync: this);
  String _search = '';
  String? _stageFilter;

  @override
  void dispose() {
    _tab.dispose();
    super.dispose();
  }

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final scaffoldKey = ref.watch(rootScaffoldKeyProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => scaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _tr(isSwahili, 'WhatsApp Marketing', 'Masoko ya WhatsApp'),
          style: AppType.display(18),
        ),
        bottom: TabBar(
          controller: _tab,
          indicatorColor: AppColors.brandYellow,
          indicatorWeight: 3,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: [
            Tab(text: _tr(isSwahili, 'Contacts', 'Wateja')),
            Tab(text: _tr(isSwahili, 'Campaigns', 'Kampeni')),
          ],
        ),
      ),
      floatingActionButton: AnimatedBuilder(
        animation: _tab,
        builder: (_, __) {
          if (_tab.index == 0) {
            return FloatingActionButton.extended(
              icon: const Icon(Icons.person_add_alt_rounded),
              label: Text(_tr(isSwahili, 'New Contact', 'Mteja Mpya')),
              onPressed: () async {
                final saved = await _openContactSheet(context);
                if (saved == true) setState(() {});
              },
            );
          }
          return FloatingActionButton.extended(
            icon: const Icon(Icons.campaign_outlined),
            label: Text(_tr(isSwahili, 'New Campaign', 'Kampeni Mpya')),
            onPressed: () async {
              final saved = await _openCampaignSheet(context);
              if (saved == true) setState(() {});
            },
          );
        },
      ),
      body: TabBarView(
        controller: _tab,
        children: [
          _ContactsTab(
            search: _search,
            stageFilter: _stageFilter,
            onSearchChanged: (s) => setState(() => _search = s),
            onStageChanged: (s) => setState(() => _stageFilter = s),
          ),
          const _CampaignsTab(),
        ],
      ),
    );
  }

  Future<bool?> _openContactSheet(BuildContext context,
      {Map<String, dynamic>? contact}) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _ContactFormSheet(contact: contact),
      ),
    );
  }

  Future<bool?> _openCampaignSheet(BuildContext context,
      {Map<String, dynamic>? campaign}) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.82,
        child: _CampaignFormSheet(campaign: campaign),
      ),
    );
  }
}

// ───────────────────────────────────── Contacts tab ─────────────────────────

class _ContactsTab extends ConsumerStatefulWidget {
  final String search;
  final String? stageFilter;
  final ValueChanged<String> onSearchChanged;
  final ValueChanged<String?> onStageChanged;

  const _ContactsTab({
    required this.search,
    required this.stageFilter,
    required this.onSearchChanged,
    required this.onStageChanged,
  });

  @override
  ConsumerState<_ContactsTab> createState() => _ContactsTabState();
}

class _ContactsTabState extends ConsumerState<_ContactsTab> {
  late Future<Map<String, dynamic>> _future;
  final _searchCtrl = TextEditingController();

  static const _stagePalette = <String, Color>{
    'lead': AppColors.brandBlue,
    'new_customer': AppColors.brandBlue,
    'new_order': AppColors.brandYellow,
    'follow_up': AppColors.brandYellow,
    'pending_payment': AppColors.error,
    'paid': AppColors.brandGreen,
    'order_complete': AppColors.brandGreen,
  };

  @override
  void initState() {
    super.initState();
    _searchCtrl.text = widget.search;
    _future = _load();
  }

  @override
  void didUpdateWidget(_ContactsTab old) {
    super.didUpdateWidget(old);
    if (old.search != widget.search ||
        old.stageFilter != widget.stageFilter) {
      _future = _load();
    }
  }

  Future<Map<String, dynamic>> _load() async {
    final res = await ref.read(apiClientProvider).get(
      '/whatsapp-marketing',
      queryParameters: {
        if (widget.search.trim().isNotEmpty) 'search': widget.search.trim(),
        if (widget.stageFilter != null) 'stage': widget.stageFilter,
      },
    );
    final data = res.data is Map ? Map<String, dynamic>.from(res.data as Map) : {};
    return data['data'] is Map
        ? Map<String, dynamic>.from(data['data'] as Map)
        : <String, dynamic>{};
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);

    return RefreshIndicator(
      onRefresh: () async => setState(() => _future = _load()),
      child: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _ErrorView(
              message: snap.error.toString(),
              onRetry: () => setState(() => _future = _load()),
            );
          }
          final data = snap.data ?? const {};
          final contacts = (data['contacts'] as List? ?? const [])
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
          final stats = data['stats'] is Map
              ? Map<String, dynamic>.from(data['stats'] as Map)
              : const <String, dynamic>{};
          final stageCounts = data['stage_counts'] is Map
              ? Map<String, dynamic>.from(data['stage_counts'] as Map)
              : const <String, dynamic>{};

          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            children: [
              _WaHero(stats: stats, isSwahili: isSwahili),
              const SizedBox(height: 16),
              TextField(
                controller: _searchCtrl,
                decoration: InputDecoration(
                  hintText: _tr(isSwahili, 'Search contacts…',
                      'Tafuta wateja…'),
                  prefixIcon: const Icon(Icons.search_rounded),
                  suffixIcon: _searchCtrl.text.isEmpty
                      ? null
                      : IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchCtrl.clear();
                            widget.onSearchChanged('');
                          },
                        ),
                  filled: true,
                  fillColor: isDark
                      ? Colors.white.withValues(alpha: 0.06)
                      : Colors.black.withValues(alpha: 0.04),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide.none,
                  ),
                ),
                onSubmitted: widget.onSearchChanged,
                onChanged: (_) => setState(() {}),
              ),
              const SizedBox(height: 12),
              SizedBox(
                height: 36,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    _stageChip(null, _tr(isSwahili, 'All', 'Yote'),
                        contacts.length),
                    for (final entry in stageCounts.entries)
                      _stageChip(
                        entry.key,
                        _labelize(entry.key),
                        (entry.value as num).toInt(),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              if (contacts.isEmpty)
                _EmptyState(
                  icon: Icons.contact_phone_outlined,
                  title: _tr(isSwahili, 'No contacts.', 'Hakuna wateja.'),
                )
              else
                ...contacts.map(
                  (c) => _ContactCard(
                    contact: c,
                    isSwahili: isSwahili,
                    onChanged: () => setState(() => _future = _load()),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Widget _stageChip(String? value, String label, int count) {
    final active = widget.stageFilter == value;
    final color = _stagePalette[value ?? ''] ?? AppColors.brandBlue;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text('$label ($count)',
            style: TextStyle(
                color: active ? Colors.white : color,
                fontWeight: FontWeight.w700)),
        selected: active,
        selectedColor: color,
        backgroundColor: color.withValues(alpha: 0.10),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: BorderSide.none,
        ),
        onSelected: (_) => widget.onStageChanged(active ? null : value),
      ),
    );
  }

  String _labelize(String value) =>
      value.split('_').map((w) => w.isEmpty ? w : '${w[0].toUpperCase()}${w.substring(1)}').join(' ');
}

class _WaHero extends StatelessWidget {
  final Map<String, dynamic> stats;
  final bool isSwahili;
  const _WaHero({required this.stats, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF075E54), Color(0xFF25D366)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.chat_bubble_rounded,
                  color: Colors.white, size: 28),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  isSwahili
                      ? 'Wateja wa WhatsApp'
                      : 'WhatsApp Contacts Pipeline',
                  style: AppType.display(20, color: Colors.white),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              _heroStat(
                isSwahili ? 'Jumla' : 'Total',
                '${stats['total_contacts'] ?? 0}',
              ),
              _heroStat(
                isSwahili ? 'Wamebadilika' : 'Converted',
                '${stats['converted'] ?? 0}',
              ),
              _heroStat(
                isSwahili ? 'Kiwango' : 'Conv. rate',
                '${stats['conversion_rate'] ?? 0}%',
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _heroStat(String label, String value) => Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value,
                style: AppType.display(22,
                    color: Colors.white, weight: FontWeight.w800)),
            Text(label, style: const TextStyle(color: Colors.white70)),
          ],
        ),
      );
}

class _ContactCard extends ConsumerWidget {
  final Map<String, dynamic> contact;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _ContactCard({
    required this.contact,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final important = contact['is_important'] == true;
    final stage = contact['stage']?.toString() ?? '';
    final color = _stageColor(stage);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                if (important)
                  const Padding(
                    padding: EdgeInsets.only(right: 6),
                    child: Icon(Icons.star_rounded,
                        color: AppColors.warning, size: 18),
                  ),
                Expanded(
                  child: Text(
                    '${contact['name'] ?? '-'}',
                    style: AppType.display(15, weight: FontWeight.w700),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '${contact['stage_label'] ?? '-'}',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: color,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              '${contact['phone'] ?? ''}',
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontFamily: 'monospace',
              ),
            ),
            if ((contact['campaign_name'] ?? '').toString().isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 2),
                child: Text(
                  '${isSwahili ? 'Kampeni' : 'Campaign'}: ${contact['campaign_name']}',
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
              ),
            if ((contact['notes'] ?? '').toString().isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 6),
                child: Text('${contact['notes']}',
                    maxLines: 2, overflow: TextOverflow.ellipsis),
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    minimumSize: const Size(0, 36),
                  ),
                  onPressed: () => _sendWhatsApp(context, contact),
                  icon: const Icon(Icons.send_rounded, size: 16),
                  label: Text(isSwahili ? 'Tuma' : 'Send'),
                ),
                const SizedBox(width: 8),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    minimumSize: const Size(0, 36),
                  ),
                  onPressed: () => _changeStage(context, ref, contact),
                  icon: const Icon(Icons.swap_horiz, size: 16),
                  label: Text(isSwahili ? 'Hatua' : 'Stage'),
                ),
                const Spacer(),
                IconButton(
                  tooltip: isSwahili ? 'Hariri' : 'Edit',
                  onPressed: () => _editContact(context, contact),
                  icon: const Icon(Icons.edit_outlined),
                ),
                IconButton(
                  tooltip: isSwahili ? 'Futa' : 'Delete',
                  onPressed: () => _deleteContact(context, ref, contact),
                  icon: const Icon(Icons.delete_outline,
                      color: AppColors.error),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Color _stageColor(String stage) => switch (stage) {
        'paid' || 'order_complete' => AppColors.brandGreen,
        'pending_payment' => AppColors.error,
        'new_order' || 'follow_up' => AppColors.brandYellow,
        _ => AppColors.brandBlue,
      };

  Future<void> _sendWhatsApp(
      BuildContext context, Map<String, dynamic> contact) async {
    final phone = (contact['phone'] ?? '').toString().trim();
    if (phone.isEmpty) return;
    final name = (contact['name'] ?? '').toString();
    final message = name.isEmpty ? '' : 'Hi $name, ';
    final waPhone = phone.replaceAll(RegExp(r'[^0-9+]'), '');
    final encoded = Uri.encodeComponent(message);
    final uri = Uri.parse('whatsapp://send?phone=$waPhone&text=$encoded');
    final ok = await ExternalLauncherService.openUri(uri);
    if (!ok) {
      // Fallback to public WhatsApp web URL via launcher service.
      await ExternalLauncherService.openUri(
          Uri.parse('https://wa.me/${waPhone.replaceFirst('+', '')}?text=$encoded'));
    }
  }

  Future<void> _editContact(
      BuildContext context, Map<String, dynamic> contact) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _ContactFormSheet(contact: contact),
      ),
    );
    if (saved == true) onChanged();
  }

  Future<void> _deleteContact(BuildContext context, WidgetRef ref,
      Map<String, dynamic> contact) async {
    final id = contact['id'];
    if (id == null) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete contact?'),
        content: Text('Remove ${contact['name'] ?? '-'}?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Delete')),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref.read(apiClientProvider).delete('/whatsapp-marketing/contacts/$id');
      onChanged();
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text('Delete failed: $e')));
      }
    }
  }

  Future<void> _changeStage(
      BuildContext context, WidgetRef ref, Map<String, dynamic> contact) async {
    final id = contact['id'];
    if (id == null) return;
    final picked = await showModalBottomSheet<String>(
      context: context,
      builder: (_) => ListView(
        shrinkWrap: true,
        children: [
          for (final entry in const [
            ('lead', 'Lead'),
            ('new_customer', 'New Customer'),
            ('new_order', 'New Order'),
            ('follow_up', 'Follow Up'),
            ('pending_payment', 'Pending Payment'),
            ('paid', 'Paid'),
            ('order_complete', 'Order Complete'),
          ])
            ListTile(
              title: Text(entry.$2),
              trailing: contact['stage'] == entry.$1
                  ? const Icon(Icons.check, color: AppColors.brandGreen)
                  : null,
              onTap: () => Navigator.pop(context, entry.$1),
            ),
        ],
      ),
    );
    if (picked == null) return;
    try {
      await ref.read(apiClientProvider).patch(
            '/whatsapp-marketing/contacts/$id/stage',
            data: {'stage': picked},
          );
      onChanged();
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text('Update failed: $e')));
      }
    }
  }
}

// ───────────────────────────────────── Campaigns tab ────────────────────────

class _CampaignsTab extends ConsumerStatefulWidget {
  const _CampaignsTab();

  @override
  ConsumerState<_CampaignsTab> createState() => _CampaignsTabState();
}

class _CampaignsTabState extends ConsumerState<_CampaignsTab> {
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<Map<String, dynamic>>> _load() async {
    final res =
        await ref.read(apiClientProvider).get('/whatsapp-marketing/campaigns');
    final data = res.data is Map ? Map<String, dynamic>.from(res.data as Map) : {};
    return ((data['data'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    return RefreshIndicator(
      onRefresh: () async => setState(() => _future = _load()),
      child: FutureBuilder<List<Map<String, dynamic>>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _ErrorView(
              message: snap.error.toString(),
              onRetry: () => setState(() => _future = _load()),
            );
          }
          final list = snap.data ?? const [];
          if (list.isEmpty) {
            return ListView(
              children: [
                SizedBox(
                  height: 240,
                  child: _EmptyState(
                    icon: Icons.campaign_outlined,
                    title: _tr(isSwahili, 'No campaigns yet.',
                        'Hakuna kampeni bado.'),
                  ),
                ),
              ],
            );
          }
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            itemCount: list.length,
            itemBuilder: (_, i) {
              final c = list[i];
              final closed = (c['status'] ?? 'active') == 'closed';
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text('${c['name'] ?? '-'}',
                                style: AppType.display(15,
                                    weight: FontWeight.w700)),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: (closed
                                      ? AppColors.draft
                                      : AppColors.brandGreen)
                                  .withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              closed
                                  ? _tr(isSwahili, 'Closed', 'Imefungwa')
                                  : _tr(isSwahili, 'Active', 'Inafanya'),
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: closed
                                    ? AppColors.draft
                                    : AppColors.brandGreen,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${c['start_date'] ?? ''} → ${c['end_date'] ?? '–'}',
                        style:
                            const TextStyle(color: AppColors.textSecondary),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 12,
                        runSpacing: 6,
                        children: [
                          _miniStat(
                            label: _tr(isSwahili, 'Leads', 'Wateja'),
                            value: '${c['contacts_count'] ?? 0}',
                            color: AppColors.brandBlue,
                          ),
                          _miniStat(
                            label: _tr(isSwahili, 'Converted', 'Walibadilika'),
                            value: '${c['converted_count'] ?? 0}',
                            color: AppColors.brandGreen,
                          ),
                          if (c['budget'] != null)
                            _miniStat(
                              label: _tr(isSwahili, 'Budget', 'Bajeti'),
                              value: '${c['budget']}',
                              color: AppColors.brandYellow,
                            ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          if (!closed)
                            TextButton.icon(
                              onPressed: () async {
                                await ref.read(apiClientProvider).patch(
                                    '/whatsapp-marketing/campaigns/${c['id']}/close');
                                setState(() => _future = _load());
                              },
                              icon: const Icon(Icons.archive_outlined),
                              label: Text(
                                  _tr(isSwahili, 'Close', 'Funga')),
                            ),
                          const Spacer(),
                          IconButton(
                            tooltip: _tr(isSwahili, 'Edit', 'Hariri'),
                            onPressed: () async {
                              final saved =
                                  await showModalBottomSheet<bool>(
                                context: context,
                                isScrollControlled: true,
                                backgroundColor: Colors.transparent,
                                builder: (_) => FractionallySizedBox(
                                  heightFactor: 0.82,
                                  child: _CampaignFormSheet(campaign: c),
                                ),
                              );
                              if (saved == true) {
                                setState(() => _future = _load());
                              }
                            },
                            icon: const Icon(Icons.edit_outlined),
                          ),
                          IconButton(
                            tooltip: _tr(isSwahili, 'Delete', 'Futa'),
                            onPressed: () async {
                              final ok = await showDialog<bool>(
                                context: context,
                                builder: (ctx) => AlertDialog(
                                  title: Text(_tr(isSwahili,
                                      'Delete campaign?',
                                      'Futa kampeni?')),
                                  content: Text('${c['name'] ?? '-'}'),
                                  actions: [
                                    TextButton(
                                        onPressed: () =>
                                            Navigator.pop(ctx, false),
                                        child: const Text('Cancel')),
                                    TextButton(
                                        onPressed: () =>
                                            Navigator.pop(ctx, true),
                                        child: const Text('Delete')),
                                  ],
                                ),
                              );
                              if (ok != true) return;
                              await ref.read(apiClientProvider).delete(
                                  '/whatsapp-marketing/campaigns/${c['id']}');
                              setState(() => _future = _load());
                            },
                            icon: const Icon(Icons.delete_outline,
                                color: AppColors.error),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Widget _miniStat({
    required String label,
    required String value,
    required Color color,
  }) =>
      Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(value,
                style: TextStyle(
                    fontWeight: FontWeight.w800, color: color, fontSize: 13)),
            const SizedBox(width: 6),
            Text(label, style: const TextStyle(fontSize: 11)),
          ],
        ),
      );
}

// ───────────────────────────────────── Form sheets ──────────────────────────

class _ContactFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? contact;
  const _ContactFormSheet({this.contact});

  @override
  ConsumerState<_ContactFormSheet> createState() =>
      _ContactFormSheetState();
}

class _ContactFormSheetState extends ConsumerState<_ContactFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final _nameCtrl =
      TextEditingController(text: widget.contact?['name']?.toString() ?? '');
  late final _phoneCtrl =
      TextEditingController(text: widget.contact?['phone']?.toString() ?? '');
  late final _notesCtrl =
      TextEditingController(text: widget.contact?['notes']?.toString() ?? '');
  String _stage = '';
  String _source = 'whatsapp_ad';
  int? _campaignId;
  int? _assignedTo;
  bool _important = false;
  bool _saving = false;
  bool _refsLoading = true;
  List<Map<String, dynamic>> _stages = const [];
  List<Map<String, dynamic>> _sources = const [];
  List<Map<String, dynamic>> _campaigns = const [];
  List<Map<String, dynamic>> _users = const [];

  @override
  void initState() {
    super.initState();
    _stage = widget.contact?['stage']?.toString() ?? '';
    _source = widget.contact?['source']?.toString() ?? 'whatsapp_ad';
    _campaignId = widget.contact?['campaign_id'] as int?;
    _assignedTo = widget.contact?['assigned_to'] as int?;
    _important = widget.contact?['is_important'] == true;
    _loadRefs();
  }

  Future<void> _loadRefs() async {
    try {
      final res = await ref
          .read(apiClientProvider)
          .get('/whatsapp-marketing/reference-data');
      final d =
          (res.data is Map ? res.data['data'] : null) as Map<String, dynamic>?;
      if (d == null) return;
      setState(() {
        _stages = ((d['stages'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
        _sources = ((d['sources'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
        _campaigns = ((d['campaigns'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
        _users = ((d['users'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
        if (_stage.isEmpty && _stages.isNotEmpty) {
          _stage = _stages.first['value']?.toString() ?? 'lead';
        }
      });
    } catch (_) {} finally {
      if (mounted) setState(() => _refsLoading = false);
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final payload = {
        'name': _nameCtrl.text.trim(),
        'phone': _phoneCtrl.text.trim(),
        'stage': _stage,
        'source': _source,
        if (_campaignId != null) 'campaign_id': _campaignId,
        if (_assignedTo != null) 'assigned_to': _assignedTo,
        'notes': _notesCtrl.text.trim(),
        'is_important': _important,
      };
      final id = widget.contact?['id'];
      if (id != null) {
        await api.put('/whatsapp-marketing/contacts/$id', data: payload);
      } else {
        await api.post('/whatsapp-marketing/contacts', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('Save failed: $e')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);
    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A1A1A) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: _refsLoading
            ? const Center(child: CircularProgressIndicator())
            : Form(
                key: _formKey,
                child: ListView(
                  padding: EdgeInsets.fromLTRB(20, 16, 20,
                      MediaQuery.of(context).viewInsets.bottom + 28),
                  children: [
                    Text(
                      widget.contact == null
                          ? (isSwahili ? 'Mteja Mpya' : 'New Contact')
                          : (isSwahili ? 'Hariri Mteja' : 'Edit Contact'),
                      textAlign: TextAlign.center,
                      style: AppType.display(20),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _nameCtrl,
                      decoration: _dec(isSwahili ? 'Jina' : 'Name'),
                      validator: (v) =>
                          (v ?? '').trim().isEmpty ? 'Required' : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _phoneCtrl,
                      keyboardType: TextInputType.phone,
                      decoration: _dec(isSwahili ? 'Simu' : 'Phone'),
                      validator: (v) =>
                          (v ?? '').trim().isEmpty ? 'Required' : null,
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: _stage.isEmpty ? null : _stage,
                      decoration: _dec(isSwahili ? 'Hatua' : 'Stage'),
                      items: _stages
                          .map((s) => DropdownMenuItem<String>(
                                value: s['value']?.toString(),
                                child: Text('${s['label'] ?? '-'}'),
                              ))
                          .toList(),
                      onChanged: (v) => setState(() => _stage = v ?? _stage),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: _source,
                      decoration: _dec(isSwahili ? 'Chanzo' : 'Source'),
                      items: _sources
                          .map((s) => DropdownMenuItem<String>(
                                value: s['value']?.toString(),
                                child: Text('${s['label'] ?? '-'}'),
                              ))
                          .toList(),
                      onChanged: (v) =>
                          setState(() => _source = v ?? _source),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      value: _campaignId,
                      decoration:
                          _dec(isSwahili ? 'Kampeni' : 'Campaign (optional)'),
                      items: [
                        const DropdownMenuItem<int?>(
                            value: null, child: Text('—')),
                        ..._campaigns.map((c) => DropdownMenuItem<int?>(
                              value: c['id'] as int?,
                              child: Text('${c['name'] ?? '-'}'),
                            )),
                      ],
                      onChanged: (v) => setState(() => _campaignId = v),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
                      value: _assignedTo,
                      decoration: _dec(
                          isSwahili ? 'Mwajiriwa' : 'Assigned to (optional)'),
                      items: [
                        const DropdownMenuItem<int?>(
                            value: null, child: Text('—')),
                        ..._users.map((u) => DropdownMenuItem<int?>(
                              value: u['id'] as int?,
                              child: Text('${u['name'] ?? '-'}'),
                            )),
                      ],
                      onChanged: (v) => setState(() => _assignedTo = v),
                    ),
                    const SizedBox(height: 12),
                    SwitchListTile(
                      value: _important,
                      onChanged: (v) => setState(() => _important = v),
                      title: Text(isSwahili ? 'Muhimu' : 'Important'),
                    ),
                    const SizedBox(height: 4),
                    TextFormField(
                      controller: _notesCtrl,
                      maxLines: 4,
                      decoration: _dec(isSwahili ? 'Maelezo' : 'Notes'),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _saving ? null : _save,
                      child: _saving
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.white,
                              ),
                            )
                          : Text(isSwahili ? 'Hifadhi' : 'Save'),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}

class _CampaignFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? campaign;
  const _CampaignFormSheet({this.campaign});

  @override
  ConsumerState<_CampaignFormSheet> createState() =>
      _CampaignFormSheetState();
}

class _CampaignFormSheetState extends ConsumerState<_CampaignFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final _nameCtrl =
      TextEditingController(text: widget.campaign?['name']?.toString() ?? '');
  late final _budgetCtrl = TextEditingController(
      text: widget.campaign?['budget']?.toString() ?? '');
  late final _notesCtrl =
      TextEditingController(text: widget.campaign?['notes']?.toString() ?? '');
  late DateTime _start =
      _parseDate(widget.campaign?['start_date']?.toString()) ?? DateTime.now();
  late DateTime? _end =
      _parseDate(widget.campaign?['end_date']?.toString());
  bool _saving = false;

  static DateTime? _parseDate(String? s) {
    if (s == null || s.isEmpty) return null;
    return DateTime.tryParse(s);
  }

  String _fmt(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  void dispose() {
    _nameCtrl.dispose();
    _budgetCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final payload = {
        'name': _nameCtrl.text.trim(),
        'start_date': _fmt(_start),
        if (_end != null) 'end_date': _fmt(_end!),
        if (_budgetCtrl.text.trim().isNotEmpty)
          'budget': double.tryParse(_budgetCtrl.text.trim()),
        'notes': _notesCtrl.text.trim(),
      };
      final id = widget.campaign?['id'];
      if (id != null) {
        await api.put('/whatsapp-marketing/campaigns/$id', data: payload);
      } else {
        await api.post('/whatsapp-marketing/campaigns', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('Save failed: $e')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);
    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A1A1A) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.fromLTRB(20, 16, 20,
                MediaQuery.of(context).viewInsets.bottom + 28),
            children: [
              Text(
                widget.campaign == null
                    ? (isSwahili ? 'Kampeni Mpya' : 'New Campaign')
                    : (isSwahili ? 'Hariri Kampeni' : 'Edit Campaign'),
                textAlign: TextAlign.center,
                style: AppType.display(20),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _nameCtrl,
                decoration: _dec(isSwahili ? 'Jina' : 'Name'),
                validator: (v) =>
                    (v ?? '').trim().isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _start,
                          firstDate: DateTime(2020),
                          lastDate:
                              DateTime.now().add(const Duration(days: 365)),
                        );
                        if (picked != null) {
                          setState(() => _start = picked);
                        }
                      },
                      child: InputDecorator(
                        decoration: _dec(isSwahili ? 'Anza' : 'Start date'),
                        child: Text(_fmt(_start)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: InkWell(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _end ?? _start,
                          firstDate: _start,
                          lastDate:
                              DateTime.now().add(const Duration(days: 365 * 2)),
                        );
                        if (picked != null) {
                          setState(() => _end = picked);
                        }
                      },
                      child: InputDecorator(
                        decoration: _dec(isSwahili ? 'Mwisho' : 'End date'),
                        child: Text(_end == null ? '—' : _fmt(_end!)),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _budgetCtrl,
                keyboardType: const TextInputType.numberWithOptions(
                    decimal: true, signed: false),
                decoration: _dec(isSwahili ? 'Bajeti' : 'Budget'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notesCtrl,
                maxLines: 4,
                decoration: _dec(isSwahili ? 'Maelezo' : 'Notes'),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white,
                        ),
                      )
                    : Text(isSwahili ? 'Hifadhi' : 'Save'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  const _EmptyState({required this.icon, required this.title});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 36),
      child: Column(
        children: [
          Icon(icon, size: 48, color: Colors.black26),
          const SizedBox(height: 12),
          Text(title, style: const TextStyle(color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 54, color: AppColors.error),
            const SizedBox(height: 10),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

InputDecoration _dec(String label) => InputDecoration(
      labelText: label,
      filled: true,
      fillColor: Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
    );
