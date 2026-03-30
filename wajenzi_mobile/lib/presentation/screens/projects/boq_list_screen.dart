import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _boqsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/boqs');
  final payload = response.data['data'];
  final collection = payload is Map<String, dynamic> ? payload : null;
  final items = collection?['data'] ?? response.data['data'] as List? ?? [];
  final meta =
      collection?['meta'] as Map<String, dynamic>? ??
      response.data['meta'] as Map<String, dynamic>? ??
      {};
  return {'items': items, 'meta': meta};
});

final _boqProjectsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/boqs/projects');
  return response.data['data'] as List? ?? [];
});

final _boqDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/boqs/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class BoqListScreen extends ConsumerStatefulWidget {
  const BoqListScreen({super.key});

  @override
  ConsumerState<BoqListScreen> createState() => _BoqListScreenState();
}

class _BoqListScreenState extends ConsumerState<BoqListScreen> {
  int? _selectedProjectId;
  String? _selectedType;
  String? _selectedStatus;
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final boqsAsync = ref.watch(_boqsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Usimamizi wa BOQ' : 'BOQ Management'),
        actions: [],
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showBoqForm(context),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza BOQ' : 'Add BOQ',
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: isSwahili ? 'Tafuta BOQ...' : 'Search BOQ...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.filter_list),
                  onPressed: () => _showFilterSheet(context),
                ),
                filled: true,
                fillColor: isDarkMode
                    ? Colors.white.withValues(alpha: 0.05)
                    : Colors.grey.withValues(alpha: 0.1),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
              ),
              onChanged: (_) => setState(() {}),
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => ref.invalidate(_boqsProvider),
              child: boqsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_boqsProvider),
                ),
                data: (payload) {
                  final allBoqs = (payload['items'] as List)
                      .cast<Map<String, dynamic>>();
                  final searchQuery = _searchController.text.toLowerCase();
                  final boqs = searchQuery.isEmpty
                      ? allBoqs
                      : allBoqs.where((boq) {
                          final projectName =
                              (boq['project_name'] as String? ?? '')
                                  .toLowerCase();
                          final version = (boq['version']?.toString() ?? '')
                              .toLowerCase();
                          final status = (boq['status'] as String? ?? '')
                              .toLowerCase();
                          final type = (boq['type'] as String? ?? '')
                              .toLowerCase();
                          return projectName.contains(searchQuery) ||
                              version.contains(searchQuery) ||
                              status.contains(searchQuery) ||
                              type.contains(searchQuery);
                        }).toList();

                  if (boqs.isEmpty) {
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
                          searchQuery.isEmpty
                              ? (isSwahili
                                    ? 'Hakuna BOQ iliyopatikana'
                                    : 'No BOQs found')
                              : (isSwahili
                                    ? 'Hakuna matokeo ya utafutaji'
                                    : 'No search results'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: boqs.length + 1,
                    itemBuilder: (context, index) {
                      if (index == boqs.length)
                        return const SizedBox(height: 80);
                      final boq = boqs[index];
                      return _BoqCard(
                        boq: boq,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: () => _showBoqDetails(context, ref, boq),
                        onEdit: () => _showBoqForm(context, boq: boq),
                        onDelete: () => _deleteBoq(context, ref, boq),
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

  void _showFilterSheet(BuildContext context) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    final projectsAsync = ref.read(_boqProjectsProvider);

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setSheetState) => Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(ctx).viewInsets.bottom,
          ),
          child: SafeArea(
            top: false,
            child: Padding(
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
                    isSwahili ? 'Chuja BOQ' : 'Filter BOQs',
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
                          value: _selectedProjectId,
                          isExpanded: true,
                          hint: Text(
                            isSwahili ? 'All Projects' : 'All Projects',
                          ),
                          dropdownColor: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.white,
                          items: [
                            DropdownMenuItem<int?>(
                              value: null,
                              child: Text(
                                isSwahili ? 'All Projects' : 'All Projects',
                              ),
                            ),
                            ...projects.map(
                              (p) => DropdownMenuItem<int?>(
                                value: p['id'] as int,
                                child: Text(p['project_name'] as String? ?? ''),
                              ),
                            ),
                          ],
                          onChanged: (v) =>
                              setSheetState(() => _selectedProjectId = v),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    isSwahili ? 'Aina' : 'Type',
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
                      child: DropdownButton<String?>(
                        value: _selectedType,
                        isExpanded: true,
                        hint: Text(isSwahili ? 'All Types' : 'All Types'),
                        dropdownColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        items: [
                          DropdownMenuItem<String?>(
                            value: null,
                            child: Text(isSwahili ? 'All Types' : 'All Types'),
                          ),
                          DropdownMenuItem<String?>(
                            value: 'client',
                            child: const Text('Client'),
                          ),
                          DropdownMenuItem<String?>(
                            value: 'internal',
                            child: const Text('Internal'),
                          ),
                        ],
                        onChanged: (v) =>
                            setSheetState(() => _selectedType = v),
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.pop(ctx);
                        ref.invalidate(_boqsProvider);
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        isSwahili ? 'Omba' : 'Apply Filters',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _showBoqForm(BuildContext context, {Map<String, dynamic>? boq}) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    final isEditing = boq != null;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) =>
          _BoqFormSheet(boq: boq, onSaved: () => ref.invalidate(_boqsProvider)),
    );
  }

  void _showBoqDetails(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> boq,
  ) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    final boqId = boq['id'] as int?;
    if (boqId == null) return;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        height: MediaQuery.of(context).size.height * 0.85,
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Consumer(
            builder: (context, ref, _) {
              final detailAsync = ref.watch(_boqDetailProvider(boqId));
              return Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? Colors.white24
                                : Colors.grey[300],
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    boq['project_name'] as String? ?? 'BOQ',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w700,
                                      color: isDarkMode
                                          ? Colors.white
                                          : AppColors.textPrimary,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Version ${boq['version'] ?? 1}',
                                    style: TextStyle(
                                      fontSize: 13,
                                      color: isDarkMode
                                          ? Colors.white54
                                          : AppColors.textSecondary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            IconButton(
                              icon: const Icon(
                                Icons.edit,
                                color: Color(0xFF1ABC9C),
                              ),
                              onPressed: () {
                                Navigator.pop(ctx);
                                _showBoqForm(context, boq: boq);
                              },
                              tooltip: isSwahili ? 'Hariri' : 'Edit',
                            ),
                            IconButton(
                              icon: const Icon(Icons.delete, color: Colors.red),
                              onPressed: () => _deleteBoq(context, ref, boq),
                              tooltip: isSwahili ? 'Futa' : 'Delete',
                            ),
                            IconButton(
                              icon: const Icon(Icons.close),
                              onPressed: () => Navigator.pop(ctx),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  Expanded(
                    child: detailAsync.when(
                      loading: () =>
                          const Center(child: CircularProgressIndicator()),
                      error: (e, _) => Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.error_outline,
                              size: 48,
                              color: AppColors.error,
                            ),
                            const SizedBox(height: 12),
                            Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                            const SizedBox(height: 12),
                            ElevatedButton(
                              onPressed: () =>
                                  ref.invalidate(_boqDetailProvider(boqId)),
                              child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                            ),
                          ],
                        ),
                      ),
                      data: (detail) {
                        final items =
                            (detail['items'] as List?)
                                ?.cast<Map<String, dynamic>>() ??
                            [];
                        final totalAmount = _toDouble(detail['total_amount']);
                        return ListView(
                          padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                          children: [
                            Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: isDarkMode
                                    ? const Color(0xFF252540)
                                    : Colors.grey.withValues(alpha: 0.06),
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          isSwahili ? 'Vifurushi' : 'Items',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: isDarkMode
                                                ? Colors.white54
                                                : AppColors.textSecondary,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          '${detail['items_count'] ?? 0}',
                                          style: TextStyle(
                                            fontSize: 20,
                                            fontWeight: FontWeight.w700,
                                            color: isDarkMode
                                                ? Colors.white
                                                : AppColors.textPrimary,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Container(
                                    width: 1,
                                    height: 40,
                                    color: isDarkMode
                                        ? Colors.white24
                                        : Colors.grey[300],
                                  ),
                                  Expanded(
                                    child: Padding(
                                      padding: const EdgeInsets.only(left: 16),
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            isSwahili
                                                ? 'Thamani ya Jumla'
                                                : 'Total Value',
                                            style: TextStyle(
                                              fontSize: 12,
                                              color: isDarkMode
                                                  ? Colors.white54
                                                  : AppColors.textSecondary,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            _formatMoney(totalAmount),
                                            style: const TextStyle(
                                              fontSize: 16,
                                              fontWeight: FontWeight.w700,
                                              color: Color(0xFF27AE60),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili ? 'Vifurushi' : 'Items',
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: isDarkMode
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 8),
                            if (items.isEmpty)
                              Container(
                                padding: const EdgeInsets.all(32),
                                child: Center(
                                  child: Text(
                                    isSwahili
                                        ? 'Hakuna vifurushi'
                                        : 'No items found',
                                    style: TextStyle(
                                      color: isDarkMode
                                          ? Colors.white54
                                          : AppColors.textSecondary,
                                    ),
                                  ),
                                ),
                              )
                            else
                              ...items.map(
                                (item) => _BoqItemCard(
                                  item: item,
                                  isDarkMode: isDarkMode,
                                  isSwahili: isSwahili,
                                ),
                              ),
                          ],
                        );
                      },
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

  Future<void> _deleteBoq(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> boq,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final boqId = boq['id'] as int?;
    if (boqId == null) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete'),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta BOQ hii?'
              : 'Are you sure you want to delete this BOQ?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        final api = ref.read(apiClientProvider);
        final response = await api.delete('/boqs/$boqId');
        if (context.mounted) {
          ref.invalidate(_boqsProvider);
          final message =
              response.data['message'] ??
              (isSwahili ? 'BOQ imefutwa' : 'BOQ deleted');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(message), backgroundColor: Colors.green),
          );
        }
      } catch (e) {
        if (context.mounted) {
          String errorMsg = 'Error';
          if (e.toString().contains('403')) {
            errorMsg = isSwahili
                ? 'Hauwezi kufuta BOQ iliyokuwa na vifurushi'
                : 'Cannot delete BOQ with items';
          } else if (e.toString().contains('message')) {
            errorMsg = e.toString();
          }
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(errorMsg), backgroundColor: Colors.red),
          );
        }
      }
    }
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatMoney(double amount) {
    if (amount <= 0) return '-';
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
  }
}

class _BoqFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? boq;
  final VoidCallback onSaved;

  const _BoqFormSheet({this.boq, required this.onSaved});

  @override
  ConsumerState<_BoqFormSheet> createState() => _BoqFormSheetState();
}

class _BoqFormSheetState extends ConsumerState<_BoqFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _versionController = TextEditingController();
  int? _selectedProjectId;
  String _selectedType = 'client';
  bool _loading = false;
  List<dynamic> _projects = [];
  bool _loadingProjects = true;

  late final bool _isEditing;
  int? _boqId;

  @override
  void initState() {
    super.initState();
    _isEditing = widget.boq != null;
    _versionController.text = _isEditing
        ? (widget.boq!['version'] ?? 1).toString()
        : '1';
    _selectedType = _isEditing
        ? ((widget.boq!['type'] as String?)?.toLowerCase() ?? 'client')
        : 'client';
    _loadProjects();
  }

  Future<void> _loadProjects() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/boqs/projects');
      if (mounted) {
        setState(() {
          _projects = response.data['data'] as List? ?? [];
          _loadingProjects = false;
          if (_isEditing && widget.boq != null) {
            _boqId = widget.boq!['id'] as int?;
            _selectedProjectId = widget.boq!['project_id'] as int?;
          }
        });
      }
    } catch (e) {
      if (mounted) setState(() => _loadingProjects = false);
    }
  }

  @override
  void dispose() {
    _versionController.dispose();
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
        top: false,
        child: SingleChildScrollView(
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
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  _isEditing
                      ? (isSwahili ? 'Hariri BOQ' : 'Edit BOQ')
                      : (isSwahili ? 'BOQ Mpya' : 'New BOQ'),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
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
                  child: _loadingProjects
                      ? const Padding(
                          padding: EdgeInsets.all(16),
                          child: CircularProgressIndicator(),
                        )
                      : DropdownButtonHideUnderline(
                          child: DropdownButton<int?>(
                            value: _selectedProjectId,
                            hint: Text(
                              isSwahili ? 'Chagua Mradi' : 'Select Project',
                            ),
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            items: _projects
                                .map(
                                  (p) => DropdownMenuItem<int?>(
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
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili ? 'Toleo *' : 'Version *',
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
                            controller: _versionController,
                            keyboardType: TextInputType.number,
                            decoration: InputDecoration(
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
                                return isSwahili ? 'Required' : 'Required';
                              if (int.tryParse(v) == null)
                                return isSwahili ? 'Nambari' : 'Number';
                              return null;
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili ? 'Aina *' : 'Type *',
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
                              child: DropdownButton<String>(
                                value: _selectedType,
                                isExpanded: true,
                                dropdownColor: isDarkMode
                                    ? const Color(0xFF2A2A3E)
                                    : Colors.white,
                                items: [
                                  DropdownMenuItem(
                                    value: 'client',
                                    child: Text(isSwahili ? 'Mteja' : 'Client'),
                                  ),
                                  DropdownMenuItem(
                                    value: 'internal',
                                    child: Text(
                                      isSwahili ? 'Ndani' : 'Internal',
                                    ),
                                  ),
                                ],
                                onChanged: (v) => setState(
                                  () => _selectedType = v ?? 'client',
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 32),
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
                            height: 20,
                            width: 20,
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
                                : (isSwahili ? 'Unda BOQ' : 'Create BOQ'),
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
      final isSwahili = ref.read(isSwahiliProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isSwahili ? 'Chagua mradi' : 'Select a project'),
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
        'version': int.parse(_versionController.text.trim()),
        'type': _selectedType,
      };

      if (_isEditing && _boqId != null) {
        await api.put('/boqs/$_boqId', data: data);
      } else {
        await api.post('/boqs', data: data);
      }
      if (mounted) {
        widget.onSaved();
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

class _BoqCard extends StatelessWidget {
  final Map<String, dynamic> boq;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _BoqCard({
    required this.boq,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final status = boq['status'] as String? ?? '';
    final type = boq['type'] as String? ?? '';

    Color statusColor;
    switch (status.toUpperCase()) {
      case 'APPROVED':
      case 'COMPLETED':
        statusColor = const Color(0xFF27AE60);
        break;
      case 'SUBMITTED':
      case 'PENDING':
        statusColor = const Color(0xFFF59E0B);
        break;
      case 'REJECTED':
        statusColor = const Color(0xFFEF4444);
        break;
      default:
        statusColor = const Color(0xFF3498db);
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InkWell(
            onTap: onTap,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: const Color(0xFF3498db).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(
                          Icons.inventory_2_rounded,
                          color: Color(0xFF3498db),
                          size: 22,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              boq['project_name'] as String? ?? '-',
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 6,
                                    vertical: 2,
                                  ),
                                  decoration: BoxDecoration(
                                    color: const Color(
                                      0xFF3498db,
                                    ).withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: Text(
                                    'v${boq['version'] ?? 1}',
                                    style: const TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w600,
                                      color: Color(0xFF3498db),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                if (type.isNotEmpty)
                                  Text(
                                    type.toUpperCase(),
                                    style: TextStyle(
                                      fontSize: 10,
                                      fontWeight: FontWeight.w500,
                                      color: isDarkMode
                                          ? Colors.white38
                                          : AppColors.textHint,
                                    ),
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: statusColor.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          status,
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: statusColor,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Divider(height: 1),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: Row(
                          children: [
                            Icon(
                              Icons.list_rounded,
                              size: 14,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              '${boq['items_count'] ?? 0} ${isSwahili ? 'vifurushi' : 'items'}',
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
                      Text(
                        _formatMoney(_toDouble(boq['total_amount'])),
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF27AE60),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          Container(
            decoration: BoxDecoration(
              border: Border(
                top: BorderSide(color: Colors.grey.withValues(alpha: 0.2)),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton.icon(
                  onPressed: onEdit,
                  icon: const Icon(Icons.edit, size: 16),
                  label: Text(isSwahili ? 'Hariri' : 'Edit'),
                  style: TextButton.styleFrom(
                    foregroundColor: const Color(0xFF1ABC9C),
                  ),
                ),
                const SizedBox(width: 8),
                TextButton.icon(
                  onPressed: onDelete,
                  icon: const Icon(Icons.delete, size: 16),
                  label: Text(isSwahili ? 'Futa' : 'Delete'),
                  style: TextButton.styleFrom(foregroundColor: Colors.red),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatMoney(double amount) {
    if (amount <= 0) return '-';
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
  }
}

class _BoqItemCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;

  const _BoqItemCard({
    required this.item,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final itemType = item['item_type'] as String? ?? '';
    final isLabour = itemType.toLowerCase() == 'labour';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode
            ? const Color(0xFF252540)
            : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.05)
              : Colors.grey.withValues(alpha: 0.1),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (item['item_code'] != null &&
                  item['item_code'].toString().isNotEmpty) ...[
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6B7280).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    item['item_code'].toString(),
                    style: const TextStyle(
                      fontSize: 9,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF6B7280),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
              ],
              if (isLabour)
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF59E0B).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: const Text(
                    'LABOUR',
                    style: TextStyle(
                      fontSize: 9,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFFF59E0B),
                    ),
                  ),
                ),
              const Spacer(),
              Text(
                _formatMoney(_toDouble(item['total_price'])),
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF27AE60),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            item['description'] as String? ?? '-',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          if (item['specification'] != null &&
              item['specification'].toString().isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              item['specification'].toString(),
              style: TextStyle(
                fontSize: 11,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ],
          const SizedBox(height: 8),
          Row(
            children: [
              Text(
                '${_formatNumber(_toDouble(item['quantity']))} ${item['unit'] ?? ''}',
                style: TextStyle(
                  fontSize: 12,
                  color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              ),
              const Spacer(),
              Text(
                '@ ${_formatMoney(_toDouble(item['unit_price']))}',
                style: TextStyle(
                  fontSize: 11,
                  color: isDarkMode ? Colors.white38 : AppColors.textHint,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatMoney(double amount) {
    if (amount <= 0) return '-';
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
  }

  String _formatNumber(double amount) {
    return NumberFormat('#,##0.00', 'en').format(amount);
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
