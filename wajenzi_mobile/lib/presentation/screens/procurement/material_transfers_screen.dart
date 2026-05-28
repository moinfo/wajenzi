import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _searchProvider = StateProvider.autoDispose<String>((_) => '');
final _statusFilterProvider = StateProvider.autoDispose<String?>((_) => null);

final _materialTransfersProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final search = ref.watch(_searchProvider);
  final status = ref.watch(_statusFilterProvider);
  final params = <String, dynamic>{'per_page': 100};
  if (search.isNotEmpty) params['search'] = search;
  if (status != null) params['status'] = status;
  final response =
      await api.get('/material-transfers', queryParameters: params);
  final body = response.data;
  final data = body is Map ? body['data'] : null;
  final list = data is Map ? data['data'] : (data is List ? data : null);
  if (list is! List) return const [];
  return list
      .whereType<Map>()
      .map((e) => e.cast<String, dynamic>())
      .toList();
});

final _transferDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/material-transfers/$id');
  return (response.data['data'] as Map?)?.cast<String, dynamic>() ?? const {};
});

final _referenceDataProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int?>((ref, fromProjectId) async {
  final api = ref.watch(apiClientProvider);
  final params = <String, dynamic>{};
  if (fromProjectId != null) params['from_project_id'] = fromProjectId;
  final response = await api.get(
    '/material-transfers/reference-data',
    queryParameters: params,
  );
  return (response.data['data'] as Map?)?.cast<String, dynamic>() ?? const {};
});

class MaterialTransfersScreen extends ConsumerWidget {
  const MaterialTransfersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final transfersAsync = ref.watch(_materialTransfersProvider);
    final search = ref.watch(_searchProvider);
    final status = ref.watch(_statusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Uhamishaji wa Vifaa' : 'Material Transfers'),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openCreateSheet(context, ref, isSwahili),
        icon: const Icon(Icons.add),
        label: Text(isSwahili ? 'Mpya' : 'New'),
      ),
      body: Column(
        children: [
          _SearchAndFilter(
            search: search,
            status: status,
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
            onSearchChange: (v) =>
                ref.read(_searchProvider.notifier).state = v,
            onStatusChange: (v) =>
                ref.read(_statusFilterProvider.notifier).state = v,
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_materialTransfersProvider);
                await ref.read(_materialTransfersProvider.future);
              },
              child: transfersAsync.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_materialTransfersProvider),
                ),
                data: (transfers) {
                  if (transfers.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: [
                        const SizedBox(height: 120),
                        Center(
                          child: Icon(
                            Icons.swap_horiz,
                            size: 64,
                            color: AppColors.textHint,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Center(
                          child: Text(
                            isSwahili
                                ? 'Hakuna uhamishaji'
                                : 'No transfers yet',
                            style: TextStyle(color: AppColors.textSecondary),
                          ),
                        ),
                      ],
                    );
                  }
                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 88),
                    itemCount: transfers.length,
                    itemBuilder: (context, index) {
                      return _TransferCard(
                        transfer: transfers[index],
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: () => _openDetail(
                          context,
                          ref,
                          transfers[index],
                          isSwahili,
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _openDetail(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> transfer,
    bool isSwahili,
  ) {
    final id = (transfer['id'] as num?)?.toInt();
    if (id == null) return;
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => MaterialTransferDetailScreen(id: id),
      ),
    );
  }

  void _openCreateSheet(
    BuildContext context,
    WidgetRef ref,
    bool isSwahili,
  ) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.92,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (_, controller) {
          return ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            child: Material(
              color: Theme.of(context).scaffoldBackgroundColor,
              child: _CreateTransferSheet(scrollController: controller),
            ),
          );
        },
      ),
    ).then((created) {
      if (created == true) {
        ref.invalidate(_materialTransfersProvider);
      }
    });
  }
}

class _SearchAndFilter extends StatelessWidget {
  final String search;
  final String? status;
  final bool isSwahili;
  final bool isDarkMode;
  final ValueChanged<String> onSearchChange;
  final ValueChanged<String?> onStatusChange;

  const _SearchAndFilter({
    required this.search,
    required this.status,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onSearchChange,
    required this.onStatusChange,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          TextField(
            decoration: InputDecoration(
              hintText: isSwahili
                  ? 'Tafuta nambari, mradi…'
                  : 'Search number, project…',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: search.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () => onSearchChange(''),
                    )
                  : null,
              filled: true,
              fillColor:
                  isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
            ),
            onChanged: onSearchChange,
          ),
          const SizedBox(height: 12),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _StatusChip(
                  label: isSwahili ? 'Zote' : 'All',
                  selected: status == null,
                  onTap: () => onStatusChange(null),
                  color: Colors.grey,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: isSwahili ? 'Inasubiri' : 'Pending',
                  selected: status == 'pending',
                  onTap: () => onStatusChange('pending'),
                  color: AppColors.warning,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: isSwahili ? 'Imepitiwa' : 'Approved',
                  selected: status == 'approved',
                  onTap: () => onStatusChange('approved'),
                  color: AppColors.success,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: isSwahili ? 'Imekataliwa' : 'Rejected',
                  selected: status == 'rejected',
                  onTap: () => onStatusChange('rejected'),
                  color: AppColors.error,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;
  final Color color;

  const _StatusChip({
    required this.label,
    required this.selected,
    required this.onTap,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: selected ? color : Colors.grey[100],
          border: Border.all(color: selected ? color : Colors.transparent),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: selected ? Colors.white : Colors.grey[700],
          ),
        ),
      ),
    );
  }
}

class _TransferCard extends StatelessWidget {
  final Map<String, dynamic> transfer;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _TransferCard({
    required this.transfer,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final status =
        (transfer['approval_status'] ?? transfer['status'] ?? '').toString();
    final statusColor = _statusColor(status);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: Theme.of(context).dividerColor.withValues(alpha: 0.5),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 6,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      transfer['transfer_number']?.toString() ?? '—',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      status.toUpperCase(),
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: _ProjectChip(
                      label: isSwahili ? 'Kutoka' : 'From',
                      project: transfer['from_project_name']?.toString() ?? '—',
                    ),
                  ),
                  const Icon(Icons.arrow_forward, size: 16),
                  Expanded(
                    child: _ProjectChip(
                      label: isSwahili ? 'Kwa' : 'To',
                      project: transfer['to_project_name']?.toString() ?? '—',
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(Icons.calendar_today,
                      size: 12, color: AppColors.textSecondary),
                  const SizedBox(width: 4),
                  Text(
                    transfer['transfer_date']?.toString() ?? '—',
                    style: TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Icon(Icons.inventory_2,
                      size: 12, color: AppColors.textSecondary),
                  const SizedBox(width: 4),
                  Text(
                    '${transfer['item_count'] ?? 0} '
                    '${isSwahili ? 'vipengele' : 'items'}',
                    style: TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    _money(transfer['total_cost']),
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
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

class _ProjectChip extends StatelessWidget {
  final String label;
  final String project;

  const _ProjectChip({required this.label, required this.project});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: TextStyle(
            fontSize: 9,
            fontWeight: FontWeight.w700,
            color: AppColors.textSecondary,
            letterSpacing: 0.5,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          project,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
          overflow: TextOverflow.ellipsis,
          maxLines: 1,
        ),
      ],
    );
  }
}

/// ----------------------------- Detail screen ------------------------------

class MaterialTransferDetailScreen extends ConsumerWidget {
  final int id;
  const MaterialTransferDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final detailAsync = ref.watch(_transferDetailProvider(id));

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Maelezo' : 'Transfer Detail'),
      ),
      body: detailAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          error: e,
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_transferDetailProvider(id)),
        ),
        data: (t) => _DetailBody(
          transfer: t,
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
          onRefresh: () async {
            ref.invalidate(_transferDetailProvider(id));
            ref.invalidate(_materialTransfersProvider);
            await ref.read(_transferDetailProvider(id).future);
          },
        ),
      ),
    );
  }
}

class _DetailBody extends ConsumerWidget {
  final Map<String, dynamic> transfer;
  final bool isSwahili;
  final bool isDarkMode;
  final Future<void> Function() onRefresh;

  const _DetailBody({
    required this.transfer,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onRefresh,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final items = ((transfer['items'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => e.cast<String, dynamic>())
        .toList();
    final status =
        (transfer['approval_status'] ?? transfer['status'] ?? '').toString();
    final canAct = status.toLowerCase() == 'pending' ||
        status.toUpperCase() == 'SUBMITTED' ||
        status.toUpperCase() == 'PENDING';

    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        padding: const EdgeInsets.all(16),
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          _SectionCard(
            isDarkMode: isDarkMode,
            title: transfer['transfer_number']?.toString() ?? '—',
            child: Column(
              children: [
                _InfoRow(
                  label: isSwahili ? 'Hali' : 'Status',
                  value: status.toUpperCase(),
                  valueColor: _statusColor(status),
                ),
                _InfoRow(
                  label: isSwahili ? 'Kutoka mradi' : 'From project',
                  value: transfer['from_project_name']?.toString() ?? '—',
                ),
                _InfoRow(
                  label: isSwahili ? 'Kwa mradi' : 'To project',
                  value: transfer['to_project_name']?.toString() ?? '—',
                ),
                _InfoRow(
                  label: isSwahili ? 'Tarehe' : 'Transfer date',
                  value: transfer['transfer_date']?.toString() ?? '—',
                ),
                _InfoRow(
                  label: isSwahili ? 'Inatarajiwa' : 'Expected arrival',
                  value:
                      transfer['expected_arrival_date']?.toString() ?? '—',
                ),
                _InfoRow(
                  label: isSwahili ? 'Mwombaji' : 'Requester',
                  value: transfer['requester_name']?.toString() ?? '—',
                ),
                _InfoRow(
                  label: isSwahili ? 'Gari' : 'Vehicle',
                  value: transfer['vehicle_info']?.toString() ?? '—',
                ),
                if ((transfer['notes']?.toString() ?? '').isNotEmpty)
                  _InfoRow(
                    label: isSwahili ? 'Maelezo' : 'Notes',
                    value: transfer['notes'].toString(),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            isDarkMode: isDarkMode,
            title: isSwahili ? 'Gharama' : 'Cost Breakdown',
            child: Column(
              children: [
                _InfoRow(
                  label: isSwahili ? 'Kupakia' : 'Loading',
                  value: _money(transfer['loading_cost']),
                ),
                _InfoRow(
                  label: isSwahili ? 'Kushusha' : 'Offloading',
                  value: _money(transfer['offloading_cost']),
                ),
                _InfoRow(
                  label: isSwahili ? 'Usafiri' : 'Transportation',
                  value: _money(transfer['transportation_cost']),
                ),
                const Divider(height: 16),
                _InfoRow(
                  label: isSwahili ? 'JUMLA' : 'TOTAL',
                  value: _money(transfer['total_cost']),
                  bold: true,
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _SectionCard(
            isDarkMode: isDarkMode,
            title:
                '${isSwahili ? 'Vipengele' : 'Items'} (${items.length})',
            child: items.isEmpty
                ? Text(isSwahili ? 'Hakuna vipengele' : 'No items',
                    style: TextStyle(color: AppColors.textSecondary))
                : Column(
                    children: items
                        .map((i) =>
                            _ItemRow(item: i, isSwahili: isSwahili))
                        .toList(),
                  ),
          ),
          if (canAct) ...[
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.error,
                      side: BorderSide(color: AppColors.error),
                    ),
                    onPressed: () => _doReject(context, ref),
                    icon: const Icon(Icons.close),
                    label: Text(isSwahili ? 'Kataa' : 'Reject'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.success,
                      foregroundColor: Colors.white,
                    ),
                    onPressed: () => _doApprove(context, ref),
                    icon: const Icon(Icons.check),
                    label: Text(isSwahili ? 'Pitisha' : 'Approve'),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Future<void> _doApprove(BuildContext context, WidgetRef ref) async {
    final id = (transfer['id'] as num?)?.toInt();
    if (id == null) return;
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) {
        return AlertDialog(
          title: Text(isSwahili ? 'Pitisha Uhamishaji' : 'Approve Transfer'),
          content: TextField(
            controller: controller,
            maxLines: 3,
            decoration: InputDecoration(
              hintText: isSwahili
                  ? 'Maelezo (hiari)'
                  : 'Comment (optional)',
              border: const OutlineInputBorder(),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(ctx).pop(true),
              child: Text(isSwahili ? 'Pitisha' : 'Approve'),
            ),
          ],
        );
      },
    );
    if (confirmed != true) return;
    try {
      final api = ref.read(apiClientProvider);
      await api.post(
        '/material-transfers/$id/approve',
        data: {'comment': controller.text},
      );
      ref.invalidate(_transferDetailProvider(id));
      ref.invalidate(_materialTransfersProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili ? 'Imeidhinishwa' : 'Approved',
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('${isSwahili ? 'Hitilafu' : 'Error'}: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  Future<void> _doReject(BuildContext context, WidgetRef ref) async {
    final id = (transfer['id'] as num?)?.toInt();
    if (id == null) return;
    final controller = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) {
        return AlertDialog(
          title: Text(isSwahili ? 'Kataa Uhamishaji' : 'Reject Transfer'),
          content: TextField(
            controller: controller,
            maxLines: 3,
            decoration: InputDecoration(
              hintText: isSwahili
                  ? 'Sababu ya kukataa'
                  : 'Rejection reason',
              border: const OutlineInputBorder(),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
              onPressed: controller.text.trim().isEmpty
                  ? null
                  : () => Navigator.of(ctx).pop(true),
              child: Text(isSwahili ? 'Kataa' : 'Reject'),
            ),
          ],
        );
      },
    );
    if (confirmed != true || controller.text.trim().isEmpty) return;
    try {
      final api = ref.read(apiClientProvider);
      await api.post(
        '/material-transfers/$id/reject',
        data: {'reason': controller.text.trim()},
      );
      ref.invalidate(_transferDetailProvider(id));
      ref.invalidate(_materialTransfersProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imekataliwa' : 'Rejected'),
            backgroundColor: AppColors.warning,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('${isSwahili ? 'Hitilafu' : 'Error'}: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

class _ItemRow extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;

  const _ItemRow({required this.item, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final source = item['source_boq_item_label'] ??
        item['source_stock_item_label'] ??
        '—';
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  item['description']?.toString() ?? '—',
                  style:
                      const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                ),
              ),
              Text(
                '${_qty(item['quantity'])} ${item['unit'] ?? ''}',
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ],
          ),
          if (source.toString().isNotEmpty && source != '—') ...[
            const SizedBox(height: 2),
            Text(
              '${isSwahili ? 'Chanzo' : 'Source'}: $source',
              style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
            ),
          ],
          if (item['specification'] != null &&
              item['specification'].toString().isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              item['specification'].toString(),
              style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
            ),
          ],
          const SizedBox(height: 6),
          const Divider(height: 1),
        ],
      ),
    );
  }
}

/// --------------------------- Create sheet ---------------------------------

class _CreateTransferSheet extends ConsumerStatefulWidget {
  final ScrollController scrollController;
  const _CreateTransferSheet({required this.scrollController});

  @override
  ConsumerState<_CreateTransferSheet> createState() =>
      _CreateTransferSheetState();
}

class _CreateTransferSheetState extends ConsumerState<_CreateTransferSheet> {
  final _formKey = GlobalKey<FormState>();
  int? _fromProjectId;
  int? _toProjectId;
  DateTime _transferDate = DateTime.now();
  DateTime? _expectedDate;
  final _vehicleCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  final _loadingCtrl = TextEditingController(text: '0');
  final _offloadingCtrl = TextEditingController(text: '0');
  final _transportCtrl = TextEditingController(text: '0');
  final List<_DraftItem> _items = [];
  bool _saving = false;

  @override
  void dispose() {
    _vehicleCtrl.dispose();
    _notesCtrl.dispose();
    _loadingCtrl.dispose();
    _offloadingCtrl.dispose();
    _transportCtrl.dispose();
    for (final item in _items) {
      item.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final referenceAsync = ref.watch(_referenceDataProvider(_fromProjectId));

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: referenceAsync.when(
          loading: () => const Padding(
            padding: EdgeInsets.all(48),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) => Padding(
            padding: const EdgeInsets.all(24),
            child: Text('${isSwahili ? 'Hitilafu' : 'Error'}: $e'),
          ),
          data: (ref0) {
            final projects = ((ref0['projects'] as List?) ?? const [])
                .whereType<Map>()
                .map((e) => e.cast<String, dynamic>())
                .toList();
            final subCats =
                ((ref0['expenses_sub_categories'] as List?) ?? const [])
                    .whereType<Map>()
                    .map((e) => e.cast<String, dynamic>())
                    .toList();
            final sourceBoq = ((ref0['source_boq_items'] as List?) ?? const [])
                .whereType<Map>()
                .map((e) => e.cast<String, dynamic>())
                .toList();
            final sourceStock =
                ((ref0['source_stock_items'] as List?) ?? const [])
                    .whereType<Map>()
                    .map((e) => e.cast<String, dynamic>())
                    .toList();

            return Form(
              key: _formKey,
              child: ListView(
                controller: widget.scrollController,
                padding: const EdgeInsets.all(16),
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey[300],
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Uhamishaji Mpya' : 'New Material Transfer',
                    style: const TextStyle(
                        fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<int>(
                    initialValue: _fromProjectId,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Kutoka mradi' : 'From project',
                      border: const OutlineInputBorder(),
                    ),
                    items: projects
                        .map((p) => DropdownMenuItem<int>(
                              value: (p['id'] as num).toInt(),
                              child: Text(
                                p['name']?.toString() ?? '—',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ))
                        .toList(),
                    onChanged: (v) {
                      setState(() {
                        _fromProjectId = v;
                        // Source items must be cleared when from-project changes.
                        for (final item in _items) {
                          item.sourceBoqItemId = null;
                          item.sourceStockItemId = null;
                        }
                      });
                    },
                    validator: (v) => v == null
                        ? (isSwahili
                            ? 'Chagua mradi wa kutoka'
                            : 'Select source project')
                        : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    initialValue: _toProjectId,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Kwa mradi' : 'To project',
                      border: const OutlineInputBorder(),
                    ),
                    items: projects
                        .where((p) => (p['id'] as num).toInt() != _fromProjectId)
                        .map((p) => DropdownMenuItem<int>(
                              value: (p['id'] as num).toInt(),
                              child: Text(
                                p['name']?.toString() ?? '—',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ))
                        .toList(),
                    onChanged: (v) => setState(() => _toProjectId = v),
                    validator: (v) => v == null
                        ? (isSwahili
                            ? 'Chagua mradi wa kwenda'
                            : 'Select destination project')
                        : null,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _DateField(
                          label:
                              isSwahili ? 'Tarehe ya uhamishaji' : 'Transfer date',
                          value: _transferDate,
                          onPick: (d) => setState(() => _transferDate = d),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _DateField(
                          label:
                              isSwahili ? 'Inatarajiwa' : 'Expected arrival',
                          value: _expectedDate,
                          onPick: (d) => setState(() => _expectedDate = d),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _vehicleCtrl,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Maelezo ya gari' : 'Vehicle info',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _notesCtrl,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Maelezo' : 'Notes',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(isSwahili ? 'Gharama' : 'Costs',
                      style: const TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _loadingCtrl,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Kupakia' : 'Loading',
                            border: const OutlineInputBorder(),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextFormField(
                          controller: _offloadingCtrl,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Kushusha' : 'Offloading',
                            border: const OutlineInputBorder(),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextFormField(
                          controller: _transportCtrl,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Usafiri' : 'Transport',
                            border: const OutlineInputBorder(),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  if (subCats.isNotEmpty)
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: isSwahili
                            ? 'Kategoria ya matumizi (hiari)'
                            : 'Expense sub-category (optional)',
                        border: const OutlineInputBorder(),
                      ),
                      items: subCats
                          .map((s) => DropdownMenuItem<int>(
                                value: (s['id'] as num).toInt(),
                                child: Text(
                                  s['name']?.toString() ?? '—',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ))
                          .toList(),
                      onChanged: (_) {/* stored on submit only — value irrelevant */},
                    ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          isSwahili
                              ? 'Vipengele (${_items.length})'
                              : 'Items (${_items.length})',
                          style:
                              const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                      TextButton.icon(
                        onPressed: _fromProjectId == null
                            ? null
                            : () =>
                                setState(() => _items.add(_DraftItem())),
                        icon: const Icon(Icons.add),
                        label: Text(isSwahili ? 'Ongeza' : 'Add'),
                      ),
                    ],
                  ),
                  if (_fromProjectId == null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: Text(
                        isSwahili
                            ? 'Chagua mradi wa kutoka kwanza'
                            : 'Select a source project first',
                        style: TextStyle(
                          fontSize: 12,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ),
                  for (int i = 0; i < _items.length; i++)
                    _DraftItemEditor(
                      item: _items[i],
                      index: i,
                      isSwahili: isSwahili,
                      sourceBoq: sourceBoq,
                      sourceStock: sourceStock,
                      onRemove: () => setState(() {
                        _items[i].dispose();
                        _items.removeAt(i);
                      }),
                    ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _saving ? null : _submit,
                      icon: _saving
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.save),
                      label: Text(
                        isSwahili ? 'Hifadhi na Wasilisha' : 'Save & Submit',
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_items.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider)
                ? 'Ongeza angalau kipengele kimoja'
                : 'Add at least one item',
          ),
        ),
      );
      return;
    }

    // Per-item validation — each row needs description + quantity + unit.
    for (final item in _items) {
      if (item.description.trim().isEmpty ||
          item.quantity <= 0 ||
          item.unit.trim().isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              ref.read(isSwahiliProvider)
                  ? 'Jaza maelezo, idadi, na kipimo kwa kila kipengele'
                  : 'Fill description, quantity, and unit for each item',
            ),
          ),
        );
        return;
      }
    }

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final payload = {
        'from_project_id': _fromProjectId,
        'to_project_id': _toProjectId,
        'transfer_date': _fmtDate(_transferDate),
        if (_expectedDate != null)
          'expected_arrival_date': _fmtDate(_expectedDate!),
        'vehicle_info': _vehicleCtrl.text.trim(),
        'notes': _notesCtrl.text.trim(),
        'loading_cost': double.tryParse(_loadingCtrl.text) ?? 0,
        'offloading_cost': double.tryParse(_offloadingCtrl.text) ?? 0,
        'transportation_cost': double.tryParse(_transportCtrl.text) ?? 0,
        'items': _items.map((i) => i.toJson()).toList(),
      };
      await api.post('/material-transfers', data: payload);
      if (mounted) {
        Navigator.of(context).pop(true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${ref.read(isSwahiliProvider) ? 'Hitilafu' : 'Error'}: $e',
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _DraftItem {
  int? sourceBoqItemId;
  int? sourceStockItemId;
  int? destinationBoqItemId;
  String description = '';
  double quantity = 0;
  String unit = '';
  final TextEditingController descCtrl = TextEditingController();
  final TextEditingController qtyCtrl = TextEditingController();
  final TextEditingController unitCtrl = TextEditingController();

  void dispose() {
    descCtrl.dispose();
    qtyCtrl.dispose();
    unitCtrl.dispose();
  }

  Map<String, dynamic> toJson() => {
        if (sourceBoqItemId != null) 'source_boq_item_id': sourceBoqItemId,
        if (sourceStockItemId != null)
          'source_stock_item_id': sourceStockItemId,
        if (destinationBoqItemId != null)
          'destination_boq_item_id': destinationBoqItemId,
        'description': descCtrl.text.trim(),
        'quantity': double.tryParse(qtyCtrl.text) ?? 0,
        'unit': unitCtrl.text.trim(),
      };
}

class _DraftItemEditor extends StatefulWidget {
  final _DraftItem item;
  final int index;
  final bool isSwahili;
  final List<Map<String, dynamic>> sourceBoq;
  final List<Map<String, dynamic>> sourceStock;
  final VoidCallback onRemove;

  const _DraftItemEditor({
    required this.item,
    required this.index,
    required this.isSwahili,
    required this.sourceBoq,
    required this.sourceStock,
    required this.onRemove,
  });

  @override
  State<_DraftItemEditor> createState() => _DraftItemEditorState();
}

class _DraftItemEditorState extends State<_DraftItemEditor> {
  @override
  Widget build(BuildContext context) {
    final boqItems = widget.sourceBoq
        .map((b) => DropdownMenuItem<int>(
              value: (b['id'] as num).toInt(),
              child: Text(
                '${b['item_code']} — ${b['description']} '
                '(${b['available_quantity']} ${b['unit']})',
                overflow: TextOverflow.ellipsis,
              ),
            ))
        .toList();
    final stockItems = widget.sourceStock
        .map((s) => DropdownMenuItem<int>(
              value: (s['id'] as num).toInt(),
              child: Text(
                '${s['description']} '
                '(${s['available_quantity']} ${s['unit']})',
                overflow: TextOverflow.ellipsis,
              ),
            ))
        .toList();

    return Card(
      margin: const EdgeInsets.only(top: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    '${widget.isSwahili ? 'Kipengele' : 'Item'} ${widget.index + 1}',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.delete, color: AppColors.error),
                  onPressed: widget.onRemove,
                ),
              ],
            ),
            if (boqItems.isNotEmpty)
              DropdownButtonFormField<int>(
                initialValue: widget.item.sourceBoqItemId,
                isExpanded: true,
                decoration: InputDecoration(
                  labelText: widget.isSwahili
                      ? 'Chanzo cha BOQ (hiari)'
                      : 'BOQ source (optional)',
                  border: const OutlineInputBorder(),
                ),
                items: [
                  DropdownMenuItem<int>(
                    value: null,
                    child: Text(widget.isSwahili ? '—' : '—'),
                  ),
                  ...boqItems,
                ],
                onChanged: (v) => setState(() {
                  widget.item.sourceBoqItemId = v;
                  if (v != null) {
                    final match = widget.sourceBoq
                        .firstWhere((b) => (b['id'] as num).toInt() == v);
                    widget.item.descCtrl.text =
                        match['description']?.toString() ?? '';
                    widget.item.unitCtrl.text =
                        match['unit']?.toString() ?? '';
                    widget.item.sourceStockItemId = null;
                  }
                }),
              ),
            const SizedBox(height: 8),
            if (stockItems.isNotEmpty)
              DropdownButtonFormField<int>(
                initialValue: widget.item.sourceStockItemId,
                isExpanded: true,
                decoration: InputDecoration(
                  labelText: widget.isSwahili
                      ? 'Chanzo cha hisa (hiari)'
                      : 'Free-stock source (optional)',
                  border: const OutlineInputBorder(),
                ),
                items: [
                  DropdownMenuItem<int>(
                    value: null,
                    child: Text(widget.isSwahili ? '—' : '—'),
                  ),
                  ...stockItems,
                ],
                onChanged: (v) => setState(() {
                  widget.item.sourceStockItemId = v;
                  if (v != null) {
                    final match = widget.sourceStock
                        .firstWhere((s) => (s['id'] as num).toInt() == v);
                    widget.item.descCtrl.text =
                        match['description']?.toString() ?? '';
                    widget.item.unitCtrl.text =
                        match['unit']?.toString() ?? '';
                    widget.item.sourceBoqItemId = null;
                  }
                }),
              ),
            const SizedBox(height: 8),
            TextField(
              controller: widget.item.descCtrl,
              decoration: InputDecoration(
                labelText: widget.isSwahili ? 'Maelezo' : 'Description',
                border: const OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: widget.item.qtyCtrl,
                    keyboardType:
                        const TextInputType.numberWithOptions(decimal: true),
                    decoration: InputDecoration(
                      labelText: widget.isSwahili ? 'Idadi' : 'Quantity',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: TextField(
                    controller: widget.item.unitCtrl,
                    decoration: InputDecoration(
                      labelText: widget.isSwahili ? 'Kipimo' : 'Unit',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final DateTime? value;
  final ValueChanged<DateTime> onPick;

  const _DateField({
    required this.label,
    required this.value,
    required this.onPick,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          firstDate: DateTime(2020),
          lastDate: DateTime(2100),
          initialDate: value ?? DateTime.now(),
        );
        if (picked != null) onPick(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
        ),
        child: Text(
          value == null ? '—' : _fmtDate(value!),
        ),
      ),
    );
  }
}

/// ---------------------------- shared helpers ------------------------------

class _SectionCard extends StatelessWidget {
  final String title;
  final bool isDarkMode;
  final Widget child;

  const _SectionCard({
    required this.title,
    required this.isDarkMode,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;
  final bool bold;

  const _InfoRow({
    required this.label,
    required this.value,
    this.valueColor,
    this.bold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: TextStyle(
                fontSize: 12,
                fontWeight: bold ? FontWeight.bold : FontWeight.w600,
                color: valueColor,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 80),
        Icon(Icons.error_outline, size: 48, color: AppColors.error),
        const SizedBox(height: 12),
        Center(
          child: Text(
            isSwahili
                ? 'Imeshindwa kupakia uhamishaji'
                : 'Failed to load transfers',
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
        ),
        const SizedBox(height: 8),
        Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              error.toString(),
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu Tena' : 'Retry'),
          ),
        ),
      ],
    );
  }
}

Color _statusColor(String status) {
  switch (status.toUpperCase()) {
    case 'APPROVED':
    case 'COMPLETED':
      return AppColors.success;
    case 'REJECTED':
      return AppColors.error;
    case 'PENDING':
    case 'SUBMITTED':
      return AppColors.warning;
    default:
      return AppColors.textSecondary;
  }
}

String _money(dynamic v) {
  final value =
      (v is num) ? v.toDouble() : double.tryParse(v?.toString() ?? '') ?? 0;
  return NumberFormat.currency(
    locale: 'en_TZ',
    symbol: 'TZS ',
    decimalDigits: 0,
  ).format(value);
}

String _qty(dynamic v) {
  final value =
      (v is num) ? v.toDouble() : double.tryParse(v?.toString() ?? '') ?? 0;
  return NumberFormat('#,##0.##', 'en_US').format(value);
}

String _fmtDate(DateTime d) =>
    '${d.year.toString().padLeft(4, '0')}-'
    '${d.month.toString().padLeft(2, '0')}-'
    '${d.day.toString().padLeft(2, '0')}';
