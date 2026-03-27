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
  final root = response.data['data'] as Map<String, dynamic>? ?? const {};
  final data = root['data'] as List? ?? const [];
  final stats = root['stats'] as Map<String, dynamic>? ?? const {};

  double totalQuantity = 0;
  for (var item in data) {
    totalQuantity += _toDouble(item['quantity_available'] ?? item['quantity']);
  }

  return {
    'items': data,
    'total_count': data.length,
    'total_quantity': totalQuantity,
    'stats': stats,
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
  final bool stockRegisterMode;

  const MaterialInventoryScreen({
    super.key,
    this.stockRegisterMode = false,
  });

  @override
  ConsumerState<MaterialInventoryScreen> createState() =>
      _MaterialInventoryScreenState();
}

class _MaterialInventoryScreenState
    extends ConsumerState<MaterialInventoryScreen> {
  @override
  Widget build(BuildContext context) {
    final inventoryAsync = ref.watch(_inventoryProvider);
    final projectsAsync = ref.watch(_inventoryProjectsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(inventoryFilterProvider);
    final selectedProject = filter.projectId;
    final selectedProjectName = projectsAsync.maybeWhen(
      data: (projects) {
        for (final project in projects) {
          final map = Map<String, dynamic>.from(project as Map);
          if (map['id'] == selectedProject) {
            return map['project_name'] as String? ??
                map['name'] as String? ??
                '-';
          }
        }
        return null;
      },
      orElse: () => null,
    );

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.stockRegisterMode
              ? (isSwahili ? 'Daftari la Stock' : 'Stock Register')
              : (isSwahili ? 'Hisa ya Vifurushi' : 'Material Inventory'),
        ),
        actions: [
          if (!widget.stockRegisterMode)
            IconButton(
              icon: const Icon(Icons.add),
              onPressed: () => _showInventoryForm(context),
              tooltip: isSwahili ? 'Ongeza' : 'Add',
            ),
          if (widget.stockRegisterMode && selectedProject != null)
            PopupMenuButton<String>(
              onSelected: (value) {
                if (value == 'issue') {
                  _showIssueMaterialsForm(context, selectedProject);
                } else if (value == 'movements') {
                  _showStockMovements(context, selectedProject);
                } else if (value == 'change_project') {
                  ref.read(inventoryFilterProvider.notifier).state =
                      filter.copyWith(clearProject: true, clearMaterial: true);
                }
              },
              itemBuilder: (ctx) => [
                PopupMenuItem(
                  value: 'issue',
                  child: Text(
                    isSwahili ? 'Toa Vifaa' : 'Issue Materials',
                  ),
                ),
                PopupMenuItem(
                  value: 'movements',
                  child: Text(isSwahili ? 'Mienendo' : 'Movements'),
                ),
                PopupMenuItem(
                  value: 'change_project',
                  child: Text(
                    isSwahili ? 'Badili Mradi' : 'Change Project',
                  ),
                ),
              ],
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
                  final stats = payload['stats'] as Map<String, dynamic>? ?? const {};

                  return widget.stockRegisterMode
                      ? Wrap(
                          spacing: 12,
                          runSpacing: 12,
                          children: [
                            SizedBox(
                              width: 160,
                              child: _StatCard(
                                title: isSwahili ? 'Jumla ya Bidhaa' : 'Total Items',
                                value: '${stats['total'] ?? totalCount}',
                                icon: Icons.inventory_2,
                                color: const Color(0xFF3498DB),
                                isDarkMode: isDarkMode,
                              ),
                            ),
                            SizedBox(
                              width: 160,
                              child: _StatCard(
                                title: isSwahili ? 'Zipo Stock' : 'In Stock',
                                value: '${stats['in_stock'] ?? 0}',
                                icon: Icons.check_circle,
                                color: const Color(0xFF27AE60),
                                isDarkMode: isDarkMode,
                              ),
                            ),
                            SizedBox(
                              width: 160,
                              child: _StatCard(
                                title: isSwahili ? 'Stock Ndogo' : 'Low Stock',
                                value: '${stats['low_stock'] ?? 0}',
                                icon: Icons.warning_amber_rounded,
                                color: const Color(0xFFF59E0B),
                                isDarkMode: isDarkMode,
                              ),
                            ),
                            SizedBox(
                              width: 160,
                              child: _StatCard(
                                title: isSwahili ? 'Hakuna Stock' : 'Out of Stock',
                                value: '${stats['out_of_stock'] ?? 0}',
                                icon: Icons.remove_shopping_cart_rounded,
                                color: const Color(0xFFEF4444),
                                isDarkMode: isDarkMode,
                              ),
                            ),
                          ],
                        )
                      : Row(
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
            if (widget.stockRegisterMode && selectedProject != null)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
                color: isDarkMode ? const Color(0xFF141427) : Colors.white,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isSwahili ? 'Mradi Uliochaguliwa' : 'Selected Project',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      selectedProjectName ?? '${isSwahili ? 'Mradi' : 'Project'} #$selectedProject',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        _ActionChipButton(
                          icon: Icons.outbox_rounded,
                          label: isSwahili ? 'Toa Vifaa' : 'Issue Materials',
                          onTap: () =>
                              _showIssueMaterialsForm(context, selectedProject),
                        ),
                        _ActionChipButton(
                          icon: Icons.history_rounded,
                          label: isSwahili ? 'Mienendo' : 'Movements',
                          onTap: () =>
                              _showStockMovements(context, selectedProject),
                        ),
                        _ActionChipButton(
                          icon: Icons.swap_horiz_rounded,
                          label: isSwahili ? 'Badili Mradi' : 'Change Project',
                          onTap: () {
                            ref.read(inventoryFilterProvider.notifier).state =
                                filter.copyWith(
                                  clearProject: true,
                                  clearMaterial: true,
                                );
                          },
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            if (widget.stockRegisterMode && selectedProject == null)
              Expanded(
                child: projectsAsync.when(
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (e, _) => _ErrorView(
                    error: e,
                    isSwahili: isSwahili,
                    onRetry: () => ref.invalidate(_inventoryProjectsProvider),
                  ),
                  data: (projects) => ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      Text(
                        isSwahili ? 'Chagua Mradi' : 'Select a Project',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 12),
                      ...projects.map(
                        (project) => _ProjectCard(
                          project: Map<String, dynamic>.from(project as Map),
                          isDarkMode: isDarkMode,
                          onTap: () {
                           ref.read(inventoryFilterProvider.notifier).state =
                                filter.copyWith(projectId: project['id'] as int?);
                          },
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else
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
                        if (index == inventory.length) {
                          return const SizedBox(height: 80);
                        }
                        return _InventoryCard(
                          inventory: inventory[index],
                          isSwahili: isSwahili,
                          isDarkMode: isDarkMode,
                          stockRegisterMode: widget.stockRegisterMode,
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
      builder: (ctx) => _InventoryDetailSheet(
        inventory: inventory,
        stockRegisterMode: widget.stockRegisterMode,
        onAdjust: widget.stockRegisterMode
            ? () => _showAdjustStockForm(context, inventory)
            : null,
        onViewMovements: widget.stockRegisterMode
            ? () => _showStockMovements(
                context,
                inventory['project_id'] as int?,
                boqItemId: inventory['boq_item_id'] as int?,
              )
            : null,
      ),
    );
  }

  void _showIssueMaterialsForm(BuildContext context, int? projectId) {
    if (projectId == null) return;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _IssueMaterialsSheet(projectId: projectId),
    ).then((result) {
      if (result == true) {
        ref.invalidate(_inventoryProvider);
      }
    });
  }

  void _showAdjustStockForm(
    BuildContext context,
    Map<String, dynamic> inventory,
  ) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _AdjustStockSheet(inventory: inventory),
    ).then((result) {
      if (result == true) {
        ref.invalidate(_inventoryProvider);
      }
    });
  }

  void _showStockMovements(
    BuildContext context,
    int? projectId, {
    int? boqItemId,
  }) {
    if (projectId == null) return;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _StockMovementsSheet(
        projectId: projectId,
        boqItemId: boqItemId,
      ),
    ).then((result) {
      if (result == true) {
        ref.invalidate(_inventoryProvider);
      }
    });
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
  final bool stockRegisterMode;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _InventoryCard({
    required this.inventory,
    required this.isSwahili,
    required this.isDarkMode,
    required this.stockRegisterMode,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final quantity = _toDouble(inventory['quantity']);
    final quantityUsed = _toDouble(inventory['quantity_used']);
    final quantityAvailable = _toDouble(
      inventory['quantity_available'] ?? inventory['quantity'],
    );
    final minimumStock = _toDouble(inventory['minimum_stock_level']);
    final status = inventory['stock_status'] as String? ?? '';
    final statusLabel = inventory['stock_status_label'] as String? ?? '-';
    final statusColor = switch (status) {
      'out_of_stock' => const Color(0xFFEF4444),
      'low_stock' => const Color(0xFFF59E0B),
      _ => const Color(0xFF27AE60),
    };

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
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF3498DB).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.inventory_2,
                      color: Color(0xFF3498DB),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          stockRegisterMode
                              ? (inventory['item_code'] as String? ?? '-')
                              : (inventory['material_name'] as String? ?? '-'),
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          stockRegisterMode
                              ? (inventory['description'] as String? ??
                                  inventory['material_name'] as String? ??
                                  '-')
                              : (inventory['project_name'] as String? ?? '-'),
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (stockRegisterMode) ...[
                          const SizedBox(height: 4),
                          Text(
                            inventory['project_name'] as String? ?? '-',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textSecondary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (stockRegisterMode)
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        statusLabel,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: statusColor,
                        ),
                      ),
                    )
                  else
                    PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') onTap();
                        if (value == 'edit') onEdit();
                        if (value == 'delete') onDelete();
                      },
                      itemBuilder: (ctx) => [
                        PopupMenuItem(
                          value: 'view',
                          child: Text(isSwahili ? 'Tazama' : 'View'),
                        ),
                        PopupMenuItem(
                          value: 'edit',
                          child: Text(isSwahili ? 'Hariri' : 'Edit'),
                        ),
                        PopupMenuItem(
                          value: 'delete',
                          child: Text(
                            isSwahili ? 'Futa' : 'Delete',
                            style: const TextStyle(color: Colors.red),
                          ),
                        ),
                      ],
                    ),
                ],
              ),
              const Divider(height: 20),
              if (stockRegisterMode)
                Wrap(
                  spacing: 16,
                  runSpacing: 10,
                  children: [
                    _MiniStat(
                      label: isSwahili ? 'Received' : 'Received',
                      value:
                          '${_formatNumber(quantity)} ${inventory['unit'] ?? ''}'.trim(),
                      dark: isDarkMode,
                    ),
                    _MiniStat(
                      label: isSwahili ? 'Used' : 'Used',
                      value:
                          '${_formatNumber(quantityUsed)} ${inventory['unit'] ?? ''}'.trim(),
                      dark: isDarkMode,
                    ),
                    _MiniStat(
                      label: isSwahili ? 'Available' : 'Available',
                      value:
                          '${_formatNumber(quantityAvailable)} ${inventory['unit'] ?? ''}'.trim(),
                      dark: isDarkMode,
                    ),
                    _MiniStat(
                      label: isSwahili ? 'Min Stock' : 'Min Stock',
                      value:
                          '${_formatNumber(minimumStock)} ${inventory['unit'] ?? ''}'.trim(),
                      dark: isDarkMode,
                    ),
                  ],
                )
              else
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
              if (stockRegisterMode) ...[
                const SizedBox(height: 12),
                Text(
                  '${isSwahili ? 'Updated' : 'Updated'}: ${_formatDate(inventory['updated_at'] as String? ?? '')}',
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                  ),
                ),
              ],
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

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  final bool dark;

  const _MiniStat({
    required this.label,
    required this.value,
    required this.dark,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(minWidth: 120),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: dark ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: dark ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _ProjectCard extends StatelessWidget {
  final Map<String, dynamic> project;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _ProjectCard({
    required this.project,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFF3498DB).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.warehouse_rounded,
                  color: Color(0xFF3498DB),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      project['name'] as String? ??
                          project['project_name'] as String? ??
                          '-',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    if ((project['code'] as String?)?.isNotEmpty ?? false) ...[
                      const SizedBox(height: 4),
                      Text(
                        project['code'] as String,
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded),
            ],
          ),
        ),
      ),
    );
  }
}

class _InventoryDetailSheet extends ConsumerWidget {
  final Map<String, dynamic> inventory;
  final bool stockRegisterMode;
  final VoidCallback? onAdjust;
  final VoidCallback? onViewMovements;

  const _InventoryDetailSheet({
    required this.inventory,
    this.stockRegisterMode = false,
    this.onAdjust,
    this.onViewMovements,
  });

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
                          stockRegisterMode
                              ? (isSwahili
                                  ? 'Maelezo ya Stock'
                                  : 'Stock Item Details')
                              : (isSwahili
                                  ? 'Maelezo ya Inventory'
                                  : 'Inventory Details'),
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
                    label: isSwahili ? 'Nyenzo' : 'Material',
                    value: inventory['material_name'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Mradi' : 'Project',
                    value: inventory['project_name'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  if ((inventory['item_code'] as String?)?.isNotEmpty ?? false)
                    _DetailRow(
                      label: isSwahili ? 'Item Code' : 'Item Code',
                      value: inventory['item_code'] as String,
                      dark: isDarkMode,
                    ),
                  _DetailRow(
                    label: isSwahili ? 'Maelezo' : 'Description',
                    value: inventory['description'] as String? ??
                        inventory['material_name'] as String? ??
                        '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Received' : 'Qty Received',
                    value:
                        '${_formatNumber(_toDouble(inventory['quantity']))} ${inventory['unit'] ?? ''}',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Used' : 'Qty Used',
                    value:
                        '${_formatNumber(_toDouble(inventory['quantity_used']))} ${inventory['unit'] ?? ''}',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Available' : 'Qty Available',
                    value:
                        '${_formatNumber(_toDouble(inventory['quantity_available']))} ${inventory['unit'] ?? ''}',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Min Stock' : 'Min Stock',
                    value:
                        '${_formatNumber(_toDouble(inventory['minimum_stock_level']))} ${inventory['unit'] ?? ''}',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Status' : 'Stock Status',
                    value: inventory['stock_status_label'] as String? ?? '-',
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
                      inventory['last_updated'] as String? ??
                          inventory['updated_at'] as String? ??
                          '',
                    ),
                    dark: isDarkMode,
                  ),
                  if (stockRegisterMode) ...[
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 10,
                      runSpacing: 10,
                      children: [
                        if (onAdjust != null)
                          SizedBox(
                            width: 170,
                            child: ElevatedButton.icon(
                              onPressed: () {
                                Navigator.pop(context);
                                onAdjust?.call();
                              },
                              icon: const Icon(Icons.tune_rounded),
                              label: Text(
                                isSwahili ? 'Rekebisha' : 'Adjust Stock',
                              ),
                            ),
                          ),
                        if (onViewMovements != null)
                          SizedBox(
                            width: 170,
                            child: OutlinedButton.icon(
                              onPressed: () {
                                Navigator.pop(context);
                                onViewMovements?.call();
                              },
                              icon: const Icon(Icons.history_rounded),
                              label: Text(
                                isSwahili ? 'Historia' : 'View History',
                              ),
                            ),
                          ),
                      ],
                    ),
                  ],
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: dark ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w500,
              color: dark ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionChipButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _ActionChipButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      avatar: Icon(icon, size: 18),
      label: Text(label),
      onPressed: onTap,
    );
  }
}

class _IssueItemRowData {
  int? inventoryId;
  final TextEditingController quantityController = TextEditingController();
  final TextEditingController locationController = TextEditingController();
  final TextEditingController notesController = TextEditingController();

  void dispose() {
    quantityController.dispose();
    locationController.dispose();
    notesController.dispose();
  }
}

class _IssueMaterialsSheet extends ConsumerStatefulWidget {
  final int projectId;

  const _IssueMaterialsSheet({required this.projectId});

  @override
  ConsumerState<_IssueMaterialsSheet> createState() => _IssueMaterialsSheetState();
}

class _IssueMaterialsSheetState extends ConsumerState<_IssueMaterialsSheet> {
  final List<_IssueItemRowData> _rows = [_IssueItemRowData()];
  bool _loading = false;

  @override
  void dispose() {
    for (final row in _rows) {
      row.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final inventoryAsync = ref.watch(_inventoryProvider);

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
          child: inventoryAsync.when(
            loading: () => const Padding(
              padding: EdgeInsets.all(32),
              child: Center(child: CircularProgressIndicator()),
            ),
            error: (e, _) => _ErrorView(
              error: e,
              isSwahili: isSwahili,
              onRetry: () => ref.invalidate(_inventoryProvider),
            ),
            data: (payload) {
              final allItems = (payload['items'] as List)
                  .cast<Map<String, dynamic>>()
                  .where(
                    (item) =>
                        (item['project_id'] == widget.projectId) &&
                        _toDouble(item['quantity_available']) > 0,
                  )
                  .toList();

              return Column(
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
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          isSwahili ? 'Toa Vifaa' : 'Issue Materials',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: isDarkMode ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    isSwahili
                        ? 'Chagua vifaa vya kutoa na kiasi chake.'
                        : 'Choose the materials to issue and how much to issue.',
                    style: TextStyle(
                      color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 16),
                  ...List.generate(_rows.length, (index) {
                    final row = _rows[index];
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: isDarkMode
                            ? const Color(0xFF252540)
                            : Colors.grey.withValues(alpha: 0.05),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  '${isSwahili ? 'Item' : 'Item'} ${index + 1}',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    color: isDarkMode
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                ),
                              ),
                              if (_rows.length > 1)
                                IconButton(
                                  onPressed: () {
                                    setState(() {
                                      final removed = _rows.removeAt(index);
                                      removed.dispose();
                                    });
                                  },
                                  icon: const Icon(Icons.delete_outline),
                                ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<int>(
                            value: row.inventoryId,
                            isExpanded: true,
                            decoration: InputDecoration(
                              labelText: isSwahili ? 'Nyenzo' : 'Material',
                            ),
                            items: allItems
                                .map(
                                  (item) => DropdownMenuItem<int>(
                                    value: item['id'] as int?,
                                    child: Text(
                                      '${item['item_code'] ?? '-'} - ${item['description'] ?? item['material_name'] ?? '-'}',
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (value) {
                              setState(() {
                                row.inventoryId = value;
                              });
                            },
                          ),
                          if (row.inventoryId != null) ...[
                            const SizedBox(height: 8),
                            Text(
                              _buildAvailabilityText(
                                allItems.firstWhere((item) => item['id'] == row.inventoryId),
                                isSwahili,
                              ),
                              style: TextStyle(
                                fontSize: 12,
                                color: isDarkMode
                                    ? Colors.white54
                                    : AppColors.textSecondary,
                              ),
                            ),
                          ],
                          const SizedBox(height: 10),
                          TextFormField(
                            controller: row.quantityController,
                            keyboardType: const TextInputType.numberWithOptions(
                              decimal: true,
                            ),
                            decoration: InputDecoration(
                              labelText: isSwahili ? 'Kiasi' : 'Qty to Issue',
                            ),
                          ),
                          const SizedBox(height: 10),
                          TextFormField(
                            controller: row.locationController,
                            decoration: InputDecoration(
                              labelText: isSwahili ? 'Mahali' : 'Location',
                            ),
                          ),
                          const SizedBox(height: 10),
                          TextFormField(
                            controller: row.notesController,
                            maxLines: 2,
                            decoration: InputDecoration(
                              labelText: isSwahili ? 'Maelezo' : 'Notes',
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: TextButton.icon(
                      onPressed: () {
                        setState(() {
                          _rows.add(_IssueItemRowData());
                        });
                      },
                      icon: const Icon(Icons.add),
                      label: Text(isSwahili ? 'Ongeza Mstari' : 'Add Row'),
                    ),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _loading
                          ? null
                          : () => _submit(context, allItems, isSwahili),
                      icon: _loading
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.outbox_rounded),
                      label: Text(
                        isSwahili ? 'Toa Vifaa' : 'Issue Materials',
                      ),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  String _buildAvailabilityText(Map<String, dynamic> item, bool isSwahili) {
    final unit = item['unit'] ?? '';
    return '${isSwahili ? 'Kilichopo' : 'Available'}: ${_formatNumber(_toDouble(item['quantity_available']))} $unit';
  }

  Future<void> _submit(
    BuildContext context,
    List<Map<String, dynamic>> allItems,
    bool isSwahili,
  ) async {
    final payloadItems = <Map<String, dynamic>>[];

    for (final row in _rows) {
      if (row.inventoryId == null) continue;
      final quantity = _toDouble(row.quantityController.text.trim());
      final item = allItems.firstWhere((entry) => entry['id'] == row.inventoryId);
      final available = _toDouble(item['quantity_available']);
      if (quantity <= 0 || quantity > available) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili
                  ? 'Kiasi cha kutoa si sahihi.'
                  : 'One of the issue quantities is invalid.',
            ),
            backgroundColor: Colors.red,
          ),
        );
        return;
      }
      payloadItems.add({
        'inventory_id': row.inventoryId,
        'quantity': quantity,
        'location': row.locationController.text.trim(),
        'notes': row.notesController.text.trim(),
      });
    }

    if (payloadItems.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili ? 'Chagua angalau item moja.' : 'Pick at least one item.',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.post(
        '/material-inventory/issue',
        data: {
          'project_id': widget.projectId,
          'items': payloadItems,
        },
      );
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }
}

class _AdjustStockSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> inventory;

  const _AdjustStockSheet({required this.inventory});

  @override
  ConsumerState<_AdjustStockSheet> createState() => _AdjustStockSheetState();
}

class _AdjustStockSheetState extends ConsumerState<_AdjustStockSheet> {
  late final TextEditingController _newQuantityController;
  final TextEditingController _reasonController = TextEditingController();
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _newQuantityController = TextEditingController(
      text: _toDouble(widget.inventory['quantity']).toStringAsFixed(2),
    );
  }

  @override
  void dispose() {
    _newQuantityController.dispose();
    _reasonController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final unit = widget.inventory['unit'] ?? '';

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
              Row(
                children: [
                  Expanded(
                    child: Text(
                      isSwahili ? 'Rekebisha Stock' : 'Adjust Stock',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              _DetailRow(
                label: isSwahili ? 'Item Code' : 'Item Code',
                value: widget.inventory['item_code'] as String? ?? '-',
                dark: isDarkMode,
              ),
              _DetailRow(
                label: isSwahili ? 'Maelezo' : 'Description',
                value: widget.inventory['description'] as String? ??
                    widget.inventory['material_name'] as String? ??
                    '-',
                dark: isDarkMode,
              ),
              _DetailRow(
                label: isSwahili ? 'Kilichopo' : 'Qty Available',
                value:
                    '${_formatNumber(_toDouble(widget.inventory['quantity_available']))} $unit',
                dark: isDarkMode,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _newQuantityController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(
                  labelText: isSwahili
                      ? 'Kiasi Kipya Cha Jumla'
                      : 'New Total Quantity',
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _reasonController,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: isSwahili ? 'Sababu' : 'Reason for Adjustment',
                ),
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _loading ? null : () => _submit(context, isSwahili),
                  icon: _loading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.tune_rounded),
                  label: Text(
                    isSwahili ? 'Wasilisha Rekebisho' : 'Submit Adjustment',
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit(BuildContext context, bool isSwahili) async {
    final newQuantity = _toDouble(_newQuantityController.text.trim());
    final reason = _reasonController.text.trim();
    if (reason.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili ? 'Sababu inahitajika.' : 'Reason is required.',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.post(
        '/material-inventory/adjust',
        data: {
          'inventory_id': widget.inventory['id'],
          'new_quantity': newQuantity,
          'reason': reason,
        },
      );
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }
}

class _StockMovementsSheet extends ConsumerStatefulWidget {
  final int projectId;
  final int? boqItemId;

  const _StockMovementsSheet({
    required this.projectId,
    this.boqItemId,
  });

  @override
  ConsumerState<_StockMovementsSheet> createState() => _StockMovementsSheetState();
}

class _StockMovementsSheetState extends ConsumerState<_StockMovementsSheet> {
  String _movementType = '';
  bool _loading = false;

  Future<List<Map<String, dynamic>>> _fetchMovements() async {
    final api = ref.read(apiClientProvider);
    final response = await api.get(
      '/material-inventory/movements',
      queryParameters: {
        'project_id': widget.projectId,
        'limit': 100,
        if (_movementType.isNotEmpty) 'movement_type': _movementType,
        if (widget.boqItemId != null) 'boq_item_id': widget.boqItemId,
      },
    );
    final root = response.data['data'] as Map<String, dynamic>? ?? const {};
    return (root['data'] as List? ?? const []).cast<Map<String, dynamic>>();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      height: MediaQuery.of(context).size.height * 0.82,
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            Padding(
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
                          isSwahili ? 'Historia ya Stock' : 'Stock Movements',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: isDarkMode ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _movementType,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Aina ya Mwendendo' : 'Movement Type',
                    ),
                    items: const [
                      DropdownMenuItem(value: '', child: Text('All Types')),
                      DropdownMenuItem(value: 'received', child: Text('Received')),
                      DropdownMenuItem(value: 'issued', child: Text('Issued')),
                      DropdownMenuItem(
                        value: 'adjustment',
                        child: Text('Adjustment'),
                      ),
                      DropdownMenuItem(value: 'returned', child: Text('Returned')),
                      DropdownMenuItem(value: 'transfer', child: Text('Transfer')),
                    ],
                    onChanged: (value) {
                      setState(() {
                        _movementType = value ?? '';
                      });
                    },
                  ),
                ],
              ),
            ),
            Expanded(
              child: FutureBuilder<List<Map<String, dynamic>>>(
                future: _fetchMovements(),
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (snapshot.hasError) {
                    return _ErrorView(
                      error: snapshot.error ?? 'Unknown error',
                      isSwahili: isSwahili,
                      onRetry: () => setState(() {}),
                    );
                  }

                  final items = snapshot.data ?? const [];
                  if (items.isEmpty) {
                    return Center(
                      child: Text(
                        isSwahili
                            ? 'Hakuna historia iliyopatikana.'
                            : 'No movement history found.',
                      ),
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
                    itemCount: items.length,
                    itemBuilder: (context, index) {
                      final movement = items[index];
                      final isVerified = movement['is_verified'] == true;
                      final type = movement['movement_type'] as String? ?? '';
                      final signedPrefix =
                          type == 'issued' || type == 'transfer' ? '-' : '+';
                      final color = switch (type) {
                        'issued' => const Color(0xFFF59E0B),
                        'adjustment' => const Color(0xFF3498DB),
                        'returned' => const Color(0xFF6366F1),
                        'transfer' => const Color(0xFF6B7280),
                        _ => const Color(0xFF27AE60),
                      };

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(14),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          movement['movement_number'] as String? ?? '-',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          '${movement['boq_item']?['item_code'] ?? '-'} - ${movement['boq_item']?['description'] ?? '-'}',
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
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
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 10,
                                      vertical: 6,
                                    ),
                                    decoration: BoxDecoration(
                                      color: color.withValues(alpha: 0.12),
                                      borderRadius: BorderRadius.circular(999),
                                    ),
                                    child: Text(
                                      movement['movement_type_label'] as String? ?? '-',
                                      style: TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w600,
                                        color: color,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Wrap(
                                spacing: 16,
                                runSpacing: 10,
                                children: [
                                  _MiniStat(
                                    label: isSwahili ? 'Date' : 'Date',
                                    value: _formatDate(
                                      movement['movement_date'] as String? ?? '',
                                    ),
                                    dark: isDarkMode,
                                  ),
                                  _MiniStat(
                                    label: isSwahili ? 'Qty' : 'Qty',
                                    value:
                                        '$signedPrefix${_formatNumber(_toDouble(movement['quantity']))} ${movement['unit'] ?? ''}',
                                    dark: isDarkMode,
                                  ),
                                  _MiniStat(
                                    label: isSwahili ? 'Balance' : 'Balance After',
                                    value:
                                        '${_formatNumber(_toDouble(movement['balance_after']))} ${movement['unit'] ?? ''}',
                                    dark: isDarkMode,
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              if ((movement['location'] as String?)?.isNotEmpty ?? false)
                                Text(
                                  '${isSwahili ? 'Location' : 'Location'}: ${movement['location']}',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: isDarkMode
                                        ? Colors.white70
                                        : AppColors.textPrimary,
                                  ),
                                ),
                              if ((movement['notes'] as String?)?.isNotEmpty ?? false) ...[
                                const SizedBox(height: 6),
                                Text(
                                  movement['notes'] as String,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: isDarkMode
                                        ? Colors.white54
                                        : AppColors.textSecondary,
                                  ),
                                ),
                              ],
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      isVerified
                                          ? '${isSwahili ? 'Verified' : 'Verified'}: ${movement['verified_by'] ?? '-'}'
                                          : '${isSwahili ? 'Performed By' : 'Performed By'}: ${movement['performed_by'] ?? '-'}',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: isDarkMode
                                            ? Colors.white54
                                            : AppColors.textSecondary,
                                      ),
                                    ),
                                  ),
                                  if (!isVerified)
                                    TextButton.icon(
                                      onPressed: _loading
                                          ? null
                                          : () => _verify(context, movement['id'] as int?),
                                      icon: const Icon(Icons.check_circle_outline),
                                      label: Text(
                                        isSwahili ? 'Thibitisha' : 'Verify',
                                      ),
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
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _verify(BuildContext context, int? movementId) async {
    if (movementId == null) return;
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.post('/material-inventory/movements/$movementId/verify');
      if (mounted) {
        setState(() {});
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
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
