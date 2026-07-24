import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/router/app_router.dart';
import '../../../data/models/paylog/paylog_models.dart';
import '../../../data/repositories/paylog_repository.dart';
import 'paylog_shared.dart';

final _channelsProvider =
    FutureProvider.autoDispose<List<PaymentChannelDto>>((ref) async {
  return ref.watch(paylogRepositoryProvider).channels();
});

/// Payment Channels management — list all channels (active + inactive),
/// create (name + type), and delete-with-confirm. Mirrors the web behaviour:
/// no edit, no active-toggle; `is_active` is forced true on the server.
class PaymentChannelsScreen extends ConsumerStatefulWidget {
  const PaymentChannelsScreen({super.key});

  @override
  ConsumerState<PaymentChannelsScreen> createState() =>
      _PaymentChannelsScreenState();
}

class _PaymentChannelsScreenState extends ConsumerState<PaymentChannelsScreen> {
  bool _busy = false;

  Future<void> _run(Future<void> Function() action, String okMsg) async {
    setState(() => _busy = true);
    try {
      await action();
      ref.invalidate(_channelsProvider);
      await ref.read(_channelsProvider.future);
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(okMsg)));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(paylogErrorMessage(e))));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_channelsProvider);
    final canManage = hasPermission(ref, 'Payment Channels');

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(tr('Payment Channels')),
      ),
      floatingActionButton: canManage
          ? FloatingActionButton.extended(
              onPressed: _busy ? null : _openAddForm,
              icon: const Icon(Icons.add_rounded),
              label: Text(tr('Add Channel')),
            )
          : null,
      body: Stack(
        children: [
          RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(_channelsProvider);
              await ref.read(_channelsProvider.future);
            },
            child: async.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => _ErrorView(
                error: e,
                onRetry: () => ref.invalidate(_channelsProvider),
              ),
              data: (channels) {
                if (channels.isEmpty) {
                  return const _EmptyView(
                    icon: Icons.account_balance_wallet_outlined,
                    label: 'No payment channels yet',
                  );
                }
                return ListView.builder(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(12, 8, 12, 90),
                  itemCount: channels.length,
                  itemBuilder: (context, i) => _ChannelCard(
                    channel: channels[i],
                    canManage: canManage,
                    onDelete: () => _confirmDelete(channels[i]),
                  ),
                );
              },
            ),
          ),
          if (_busy)
            Container(
              color: Colors.black.withValues(alpha: 0.12),
              child: const Center(child: CircularProgressIndicator()),
            ),
        ],
      ),
    );
  }

  void _confirmDelete(PaymentChannelDto channel) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Payment Channel'),
        content: Text('Delete "${channel.name}"? This cannot be undone.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
    if (ok != true) return;
    await _run(
      () => ref.read(paylogRepositoryProvider).deleteChannel(channel.id),
      'Payment channel deleted.',
    );
  }

  void _openAddForm() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _AddChannelSheet(
        onSubmit: (name, type) => _run(
          () => ref
              .read(paylogRepositoryProvider)
              .createChannel(name: name, type: type),
          'Payment channel created.',
        ),
      ),
    );
  }
}

class _ChannelCard extends StatelessWidget {
  final PaymentChannelDto channel;
  final bool canManage;
  final VoidCallback onDelete;

  const _ChannelCard({
    required this.channel,
    required this.canManage,
    required this.onDelete,
  });

  IconData get _typeIcon {
    switch (channel.type.toLowerCase()) {
      case 'bank':
        return Icons.account_balance_rounded;
      case 'mobile':
        return Icons.phone_android_rounded;
      case 'cash':
        return Icons.payments_rounded;
      default:
        return Icons.account_balance_wallet_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(vertical: 6),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 8, 12),
        child: Row(
          children: [
            Icon(_typeIcon, color: Theme.of(context).colorScheme.primary),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    channel.name,
                    style: const TextStyle(
                        fontSize: 15, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    channel.typeLabel,
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ),
            ),
            _ActiveBadge(active: channel.isActive),
            if (canManage)
              IconButton(
                icon: const Icon(Icons.delete_outline_rounded,
                    color: Colors.red),
                onPressed: onDelete,
                tooltip: 'Delete',
              ),
          ],
        ),
      ),
    );
  }
}

class _ActiveBadge extends StatelessWidget {
  final bool active;
  const _ActiveBadge({required this.active});

  @override
  Widget build(BuildContext context) {
    final color = active ? Colors.green : Colors.blueGrey;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(
        active ? 'ACTIVE' : 'INACTIVE',
        style: TextStyle(
            color: color, fontSize: 10, fontWeight: FontWeight.w700),
      ),
    );
  }
}

// ── Add channel bottom sheet ────────────────────────────────────────────────
class _AddChannelSheet extends StatefulWidget {
  final Future<void> Function(String name, String type) onSubmit;
  const _AddChannelSheet({required this.onSubmit});

  @override
  State<_AddChannelSheet> createState() => _AddChannelSheetState();
}

class _AddChannelSheetState extends State<_AddChannelSheet> {
  final _nameCtrl = TextEditingController();
  String _type = 'bank';
  bool _busy = false;

  static const _types = <MapEntry<String, String>>[
    MapEntry('bank', 'Bank'),
    MapEntry('mobile', 'Mobile'),
    MapEntry('cash', 'Cash'),
  ];

  @override
  void dispose() {
    _nameCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final name = _nameCtrl.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Channel name is required.')));
      return;
    }
    setState(() => _busy = true);
    try {
      await widget.onSubmit(name, _type);
      if (mounted) Navigator.pop(context);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Container(
      padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + bottom),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const Text('Add Payment Channel',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 16),
            TextField(
              controller: _nameCtrl,
              textCapitalization: TextCapitalization.words,
              decoration: const InputDecoration(
                labelText: 'Channel Name *',
                hintText: 'e.g. CRDB Bank, Vodacom M-Pesa',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _type,
              decoration: const InputDecoration(
                labelText: 'Type *',
                border: OutlineInputBorder(),
              ),
              items: _types
                  .map((e) => DropdownMenuItem(
                        value: e.key,
                        child: Text(e.value),
                      ))
                  .toList(),
              onChanged: (v) => setState(() => _type = v ?? 'bank'),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _busy ? null : _submit,
                style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14)),
                child: _busy
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Create'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── file-local status views ─────────────────────────────────────────────────
class _EmptyView extends StatelessWidget {
  final IconData icon;
  final String label;
  const _EmptyView({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 64, color: Theme.of(context).hintColor),
        const SizedBox(height: 12),
        Center(
          child:
              Text(label, style: TextStyle(color: Theme.of(context).hintColor)),
        ),
      ],
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;
  const _ErrorView({required this.error, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 100),
        Icon(Icons.error_outline_rounded,
            size: 56, color: Colors.red.withValues(alpha: 0.7)),
        const SizedBox(height: 12),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(paylogErrorMessage(error), textAlign: TextAlign.center),
          ),
        ),
        const SizedBox(height: 12),
        Center(
          child:
              OutlinedButton(onPressed: onRetry, child: const Text('Retry')),
        ),
      ],
    );
  }
}
