import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatNumber(double amount) {
  return NumberFormat('#,##0.00', 'en').format(amount);
}

String _formatDate(String raw) {
  if (raw.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}

class InventoryFilter {
  final int? projectId;
  final int? materialId;

  InventoryFilter({this.projectId, this.materialId});

  InventoryFilter copyWith({
    int? projectId,
    int? materialId,
    bool clearProject = false,
    bool clearMaterial = false,
  }) {
    return InventoryFilter(
      projectId: clearProject ? null : (projectId ?? this.projectId),
      materialId: clearMaterial ? null : (materialId ?? this.materialId),
    );
  }

  Map<String, String> toQueryParams() {
    final params = <String, String>{};
    if (projectId != null) params['project_id'] = projectId.toString();
    if (materialId != null) params['material_id'] = materialId.toString();
    return params;
  }
}

final inventoryFilterProvider = StateProvider.autoDispose<InventoryFilter>(
  (ref) => InventoryFilter(),
);

final _inventoryProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(inventoryFilterProvider);
  final response = await api.get(
    '/material-inventory',
    queryParameters: filter.toQueryParams(),
  );
  final data = response.data['data'] as List? ?? [];

  double totalQuantity = 0;
  for (var item in data) {
    totalQuantity += _toDouble(item['quantity']);
  }

  return {
    'items': data,
    'total_count': data.length,
    'total_quantity': totalQuantity,
  };
});

final _inventoryProjectsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/material-inventory/projects');
  return response.data['data'] as List? ?? [];
});

final _inventoryMaterialsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/material-inventory/materials');
  return response.data['data'] as List? ?? [];
});

final _inventoryDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/material-inventory/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class MaterialInventoryScreen extends ConsumerStatefulWidget {
  const MaterialInventoryScreen({super.key});

  @override
  ConsumerState<MaterialInventoryScreen> createState() =>
      _MaterialInventoryScreenState();
}

class _MaterialInventoryScreenState
    extends ConsumerState<MaterialInventoryScreen> {
  @override
  Widget build(BuildContext context) {
    final inventoryAsync = ref.watch(_inventoryProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(inventoryFilterProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Hisa ya Vifurushi' : 'Material Inventory'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _showInventoryForm(context),
            tooltip: isSwahili ? 'Ongeza' : 'Add',
          ),
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: () => _showFilterSheet(context),
            tooltip: isSwahili ? 'Chuja' : 'Filter',
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_inventoryProvider),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 10,
                  ),
                ],
              ),
              child: inventoryAsync.when(
                loading: () => const SizedBox(
                  height: 60,
                  child: Center(child: CircularProgressIndicator()),
                ),
                error: (_, __) => const SizedBox.shrink(),
                data: (payload) {
                  final totalCount = payload['total_count'] as int;
                  final totalQty = payload['total_quantity'] as double;

                  return Row(
                    children: [
                      Expanded(
                        child: _StatCard(
                          title: isSwahili ? 'Jumla' : 'Total',
                          value: '$totalCount',
                          icon: Icons.inventory_2,
                          color: const Color(0xFF3498DB),
                          isDarkMode: isDarkMode,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _StatCard(
                          title: isSwahili ? 'Jumla ya Kiasi' : 'Total Qty',
                          value: _formatNumber(totalQty),
                          icon: Icons.analytics,
                          color: const Color(0xFF27AE60),
                          isDarkMode: isDarkMode,
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
            Expanded(
              child: inventoryAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_inventoryProvider),
                ),
                data: (payload) {
                  final inventory = (payload['items'] as List)
                      .cast<Map<String, dynamic>>();

                  if (inventory.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(32),
                      children: [
                        const SizedBox(height: 100),
                        Icon(
                          Icons.inventory_2_outlined,
                          size: 56,
                          color: Colors.grey[300],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          isSwahili
                              ? 'Hakuna inventory iliyopatikana'
                              : 'No inventory found',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        if (filter.projectId != null ||
                            filter.materialId != null) ...[
                          const SizedBox(height: 16),
                          TextButton(
                            onPressed: () =>
                                ref
                                        .read(inventoryFilterProvider.notifier)
                                        .state =
                                    InventoryFilter(),
                            child: Text(
                              isSwahili ? 'Ondoa vichujio' : 'Clear filters',
                            ),
                          ),
                        ],
                      ],
                    );
                  }

                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: inventory.length + 1,
                    itemBuilder: (context, index) {
                      if (index == inventory.length)
                        return const SizedBox(height: 80);
                      return _InventoryCard(
                        inventory: inventory[index],
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: () =>
                            _showInventoryDetail(context, inventory[index]),
                        onEdit: () => _showInventoryForm(
                          context,
                          inventory: inventory[index],
                        ),
                        onDelete: () =>
                            _deleteInventory(context, ref, inventory[index]),
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showFilterSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _FilterSheet(parentRef: ref),
    );
  }

  void _showInventoryForm(
    BuildContext context, {
    Map<String, dynamic>? inventory,
  }) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _InventoryFormSheet(inventory: inventory),
    ).then((result) {
      if (result == true) ref.invalidate(_inventoryProvider);
    });
  }

  void _showInventoryDetail(
    BuildContext context,
    Map<String, dynamic> inventory,
  ) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _InventoryDetailSheet(inventory: inventory),
    );
  }

  Future<void> _deleteInventory(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> inventory,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete',
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta inventory hii?'
              : 'Are you sure you want to delete this inventory?',
          style: TextStyle(
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              isSwahili ? 'Futa' : 'Delete',
              style: const TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      try {
        final api = ref.read(apiClientProvider);
        await api.delete('/material-inventory/${inventory['id']}');
        ref.invalidate(_inventoryProvider);
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                isSwahili ? 'Inventory imefutwa' : 'Inventory deleted',
              ),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
          );
        }
      }
    }
  }
}

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;
  final bool isDarkMode;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: isDarkMode ? Colors.white : color,
            ),
          ),
          Text(
            title,
            style: TextStyle(
              fontSize: 10,
              color: isDarkMode ? Colors.white54 : Colors.grey[600],
            ),
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

class _FilterSheet extends ConsumerWidget {
  final WidgetRef parentRef;

  const _FilterSheet({required this.parentRef});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(inventoryFilterProvider);
    final projectsAsync = ref.watch(_inventoryProjectsProvider);
    final materialsAsync = ref.watch(_inventoryMaterialsProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                isSwahili ? 'Chuja Inventory' : 'Filter Inventory',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                isSwahili ? 'Mradi' : 'Project',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: isDarkMode
                      ? const Color(0xFF2A2A3E)
                      : Colors.grey[100],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: projectsAsync.when(
                  loading: () => const Padding(
                    padding: EdgeInsets.all(16),
                    child: CircularProgressIndicator(),
                  ),
                  error: (_, __) => Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                  ),
                  data: (projects) => DropdownButtonHideUnderline(
                    child: DropdownButton<int?>(
                      value: filter.projectId,
                      hint: Text(isSwahili ? 'All Projects' : 'All Projects'),
                      isExpanded: true,
                      dropdownColor: isDarkMode
                          ? const Color(0xFF2A2A3E)
                          : Colors.white,
                      items: [
                        DropdownMenuItem(
                          value: null,
                          child: Text(
                            isSwahili ? 'All Projects' : 'All Projects',
                          ),
                        ),
                        ...projects.map(
                          (p) => DropdownMenuItem(
                            value: p['id'] as int,
                            child: Text(p['project_name'] as String? ?? '-'),
                          ),
                        ),
                      ],
                      onChanged: (v) =>
                          parentRef
                              .read(inventoryFilterProvider.notifier)
                              .state = filter.copyWith(
                            projectId: v,
                            clearProject: v == null,
                          ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                isSwahili ? 'Kifurushi' : 'Material',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: isDarkMode
                      ? const Color(0xFF2A2A3E)
                      : Colors.grey[100],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: materialsAsync.when(
                  loading: () => const Padding(
                    padding: EdgeInsets.all(16),
                    child: CircularProgressIndicator(),
                  ),
                  error: (_, __) => Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                  ),
                  data: (materials) => DropdownButtonHideUnderline(
                    child: DropdownButton<int?>(
                      value: filter.materialId,
                      hint: Text(isSwahili ? 'All Materials' : 'All Materials'),
                      isExpanded: true,
                      dropdownColor: isDarkMode
                          ? const Color(0xFF2A2A3E)
                          : Colors.white,
                      items: [
                        DropdownMenuItem(
                          value: null,
                          child: Text(
                            isSwahili ? 'All Materials' : 'All Materials',
                          ),
                        ),
                        ...materials.map(
                          (m) => DropdownMenuItem(
                            value: m['id'] as int,
                            child: Text(m['name'] as String? ?? '-'),
                          ),
                        ),
                      ],
                      onChanged: (v) =>
                          parentRef
                              .read(inventoryFilterProvider.notifier)
                              .state = filter.copyWith(
                            materialId: v,
                            clearMaterial: v == null,
                          ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () {
                        parentRef.read(inventoryFilterProvider.notifier).state =
                            InventoryFilter();
                      },
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        side: BorderSide(
                          color: isDarkMode
                              ? Colors.white24
                              : Colors.grey[300]!,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        isSwahili ? 'Ondoa' : 'Clear',
                        style: TextStyle(
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        isSwahili ? 'Omba' : 'Apply',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
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

class _InventoryCard extends StatelessWidget {
  final Map<String, dynamic> inventory;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _InventoryCard({
    required this.inventory,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final quantity = _toDouble(inventory['quantity']);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF9B59B6).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.inventory_2,
                      color: Color(0xFF9B59B6),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          inventory['material_name'] as String? ?? '-',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          inventory['project_name'] as String? ?? '-',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'view')
                        onTap();
                      else if (value == 'edit')
                        onEdit();
                      else if (value == 'delete')
                        onDelete();
                    },
                    itemBuilder: (ctx) => [
                      PopupMenuItem(
                        value: 'view',
                        child: Row(
                          children: [
                            const Icon(Icons.visibility, size: 20),
                            const SizedBox(width: 8),
                            Text(isSwahili ? 'Tazama' : 'View'),
                          ],
                        ),
                      ),
                      PopupMenuItem(
                        value: 'edit',
                        child: Row(
                          children: [
                            const Icon(Icons.edit, size: 20),
                            const SizedBox(width: 8),
                            Text(isSwahili ? 'Hariri' : 'Edit'),
                          ],
                        ),
                      ),
                      PopupMenuItem(
                        value: 'delete',
                        child: Row(
                          children: [
                            const Icon(
                              Icons.delete,
                              size: 20,
                              color: Colors.red,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              isSwahili ? 'Futa' : 'Delete',
                              style: const TextStyle(color: Colors.red),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const Divider(height: 20),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili ? 'Kiasi' : 'Quantity',
                          style: TextStyle(
                            fontSize: 11,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${_formatNumber(quantity)} ${inventory['unit'] ?? ''}',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: quantity > 0
                                ? const Color(0xFF27AE60)
                                : const Color(0xFFEF4444),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        isSwahili ? 'Imesasishwa' : 'Updated',
                        style: TextStyle(
                          fontSize: 11,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _formatDate(inventory['updated_at'] as String? ?? ''),
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatNumber(double amount) {
    return NumberFormat('#,##0.00', 'en').format(amount);
  }

  String _formatDate(String raw) {
    if (raw.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(raw));
    } catch (_) {
      return raw;
    }
  }
}

class _InventoryDetailSheet extends ConsumerWidget {
  final Map<String, dynamic> inventory;

  const _InventoryDetailSheet({required this.inventory});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      height: MediaQuery.of(context).size.height * 0.6,
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          isSwahili
                              ? 'Maelezo ya Inventory'
                              : 'Inventory Details',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                children: [
                  _DetailRow(
                    label: isSwahili ? 'Mradi' : 'Project',
                    value: inventory['project_name'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Kifurushi' : 'Material',
                    value: inventory['material_name'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Kiasi' : 'Quantity',
                    value:
                        '${_formatNumber(_toDouble(inventory['quantity']))} ${inventory['unit'] ?? ''}',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Kitengo' : 'Unit',
                    value: inventory['unit'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Imeundwa' : 'Created',
                    value: _formatDate(
                      inventory['created_at'] as String? ?? '',
                    ),
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Imesasishwa' : 'Updated',
                    value: _formatDate(
                      inventory['updated_at'] as String? ?? '',
                    ),
                    dark: isDarkMode,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool dark;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.dark,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: dark
            ? const Color(0xFF252540)
            : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: dark ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: dark ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InventoryFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? inventory;

  const _InventoryFormSheet({this.inventory});

  @override
  ConsumerState<_InventoryFormSheet> createState() =>
      _InventoryFormSheetState();
}

class _InventoryFormSheetState extends ConsumerState<_InventoryFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _quantityController = TextEditingController();
  int? _selectedProjectId;
  int? _selectedMaterialId;
  bool _loading = false;
  List<dynamic> _projects = [];
  List<dynamic> _materials = [];
  bool _loadingData = true;

  late final bool _isEditing;
  int? _inventoryId;

  @override
  void initState() {
    super.initState();
    _isEditing = widget.inventory != null;
    _quantityController.text = _isEditing
        ? _toDouble(widget.inventory!['quantity']).toString()
        : '0';
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final api = ref.read(apiClientProvider);
      final responses = await Future.wait([
        api.get('/material-inventory/projects'),
        api.get('/material-inventory/materials'),
      ]);

      if (mounted) {
        setState(() {
          _projects = responses[0].data['data'] as List? ?? [];
          _materials = responses[1].data['data'] as List? ?? [];
          _loadingData = false;

          if (_isEditing && widget.inventory != null) {
            _inventoryId = widget.inventory!['id'] as int?;
            _selectedProjectId = widget.inventory!['project_id'] as int?;
            _selectedMaterialId = widget.inventory!['material_id'] as int?;
          }
        });
      }
    } catch (e) {
      if (mounted) setState(() => _loadingData = false);
    }
  }

  @override
  void dispose() {
    _quantityController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        child: _loadingData
            ? const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator()),
              )
            : SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? Colors.white24
                                : Colors.grey[300],
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        _isEditing
                            ? (isSwahili
                                  ? 'Hariri Inventory'
                                  : 'Edit Inventory')
                            : (isSwahili ? 'Inventory Mpya' : 'New Inventory'),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        isSwahili ? 'Mradi *' : 'Project *',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int?>(
                            value: _selectedProjectId,
                            hint: Text(
                              isSwahili ? 'Chagua mradi' : 'Select project',
                            ),
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            items: _projects
                                .map(
                                  (p) => DropdownMenuItem(
                                    value: p['id'] as int,
                                    child: Text(
                                      p['project_name'] as String? ?? '-',
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (v) =>
                                setState(() => _selectedProjectId = v),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        isSwahili ? 'Kifurushi *' : 'Material *',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int?>(
                            value: _selectedMaterialId,
                            hint: Text(
                              isSwahili
                                  ? 'Chagua kifurushi'
                                  : 'Select material',
                            ),
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            items: _materials
                                .map(
                                  (m) => DropdownMenuItem(
                                    value: m['id'] as int,
                                    child: Text(m['name'] as String? ?? '-'),
                                  ),
                                )
                                .toList(),
                            onChanged: (v) =>
                                setState(() => _selectedMaterialId = v),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        isSwahili ? 'Kiasi *' : 'Quantity *',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _quantityController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: InputDecoration(
                          hintText: '0.00',
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        validator: (v) {
                          if (v == null || v.isEmpty)
                            return isSwahili
                                ? 'Kiasi yahitajika'
                                : 'Quantity required';
                          if (double.tryParse(v) == null)
                            return isSwahili
                                ? 'Nambari batili'
                                : 'Invalid number';
                          return null;
                        },
                      ),
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _loading ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _loading
                              ? const SizedBox(
                                  width: 24,
                                  height: 24,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  _isEditing
                                      ? (isSwahili
                                            ? 'Hifadhi Mabadiliko'
                                            : 'Save Changes')
                                      : (isSwahili
                                            ? 'Unda Inventory'
                                            : 'Create Inventory'),
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedProjectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider) ? 'Chagua mradi' : 'Select a project',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }
    if (_selectedMaterialId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider)
                ? 'Chagua kifurushi'
                : 'Select a material',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'project_id': _selectedProjectId,
        'material_id': _selectedMaterialId,
        'quantity': double.parse(_quantityController.text),
      };

      if (_isEditing && _inventoryId != null) {
        await api.put('/material-inventory/$_inventoryId', data: data);
      } else {
        await api.post('/material-inventory', data: data);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
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
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          '$error',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}
