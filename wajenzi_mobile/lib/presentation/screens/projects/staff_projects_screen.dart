import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _projectsProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/projects');
  final data = response.data['data'];
  if (data is List) return data;
  if (data is Map && data['data'] is List) return data['data'] as List;
  return [];
});

final _projectStatsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/projects/stats');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

final _projectDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/projects/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class StaffProjectsScreen extends ConsumerStatefulWidget {
  const StaffProjectsScreen({super.key});

  @override
  ConsumerState<StaffProjectsScreen> createState() =>
      _StaffProjectsScreenState();
}

class _StaffProjectsScreenState extends ConsumerState<StaffProjectsScreen> {
  @override
  Widget build(BuildContext context) {
    final projectsAsync = ref.watch(_projectsProvider);
    final statsAsync = ref.watch(_projectStatsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Miradi' : 'Projects'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza Mradi' : 'Add Project',
            onPressed: () => _showProjectForm(context),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_projectsProvider);
          ref.invalidate(_projectStatsProvider);
        },
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(
              child: statsAsync.when(
                loading: () => const SizedBox.shrink(),
                error: (_, __) => const SizedBox.shrink(),
                data: (stats) =>
                    _StatsSection(stats: stats, isSwahili: isSwahili),
              ),
            ),
            projectsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _ErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_projectsProvider),
                ),
              ),
              data: (projects) {
                if (projects.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.folder_open,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            isSwahili ? 'Hakuna miradi' : 'No projects found',
                            style: TextStyle(
                              color: Colors.grey[600],
                              fontSize: 16,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }
                return SliverPadding(
                  padding: const EdgeInsets.all(16),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _ProjectCard(
                        project: projects[index] as Map<String, dynamic>,
                        isSwahili: isSwahili,
                        onTap: () => _showProjectDetail(
                          context,
                          projects[index] as Map<String, dynamic>,
                        ),
                      ),
                      childCount: projects.length,
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showProjectForm(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => const _ProjectFormSheet(),
    ).then((result) {
      if (result == true) {
        ref.invalidate(_projectsProvider);
        ref.invalidate(_projectStatsProvider);
      }
    });
  }

  void _showProjectDetail(BuildContext context, Map<String, dynamic> project) {
    final id = project['id'] as int?;
    if (id == null) return;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _ProjectDetailSheet(projectId: id),
    );
  }
}

Color getStatusColor(String status) {
  switch (status.toUpperCase()) {
    case 'APPROVED':
    case 'ACTIVE':
      return const Color(0xFF27AE60);
    case 'COMPLETED':
      return const Color(0xFF3498DB);
    case 'PENDING':
    case 'CREATED':
      return const Color(0xFFF39C12);
    case 'REJECTED':
    case 'CANCELLED':
      return const Color(0xFFE74C3C);
    default:
      return const Color(0xFF95A5A6);
  }
}

class _StatsSection extends StatelessWidget {
  final Map<String, dynamic> stats;
  final bool isSwahili;

  const _StatsSection({required this.stats, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final total = stats['total'] ?? 0;
    final active = stats['active'] ?? 0;
    final completed = stats['completed'] ?? 0;
    final delayed = stats['delayed'] ?? 0;
    final totalValue = _toDouble(stats['total_value']);

    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1ABC9C), Color(0xFF3498DB)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          Text(
            isSwahili ? 'Muhtasari wa Miradi' : 'Project Summary',
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 12,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _StatBox(
                label: isSwahili ? 'Jumla' : 'Total',
                value: '$total',
                icon: Icons.folder,
                isWhite: true,
              ),
              _StatBox(
                label: isSwahili ? 'Haina' : 'Active',
                value: '$active',
                icon: Icons.play_circle,
                isWhite: true,
              ),
              _StatBox(
                label: isSwahili ? 'Imemalizika' : 'Done',
                value: '$completed',
                icon: Icons.check_circle,
                isWhite: true,
              ),
              _StatBox(
                label: isSwahili ? 'Kuchelewa' : 'Delayed',
                value: '$delayed',
                icon: Icons.warning,
                isWhite: true,
              ),
            ],
          ),
          if (totalValue > 0) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                '${isSwahili ? 'Thamani' : 'Value'}: TZS ${NumberFormat.compact().format(totalValue)}',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }
}

class _StatBox extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final bool isWhite;

  const _StatBox({
    required this.label,
    required this.value,
    required this.icon,
    required this.isWhite,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, color: Colors.white.withValues(alpha: 0.9), size: 20),
        const SizedBox(height: 6),
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.8),
            fontSize: 10,
          ),
        ),
      ],
    );
  }
}

class _ProjectCard extends StatelessWidget {
  final Map<String, dynamic> project;
  final bool isSwahili;
  final VoidCallback onTap;

  const _ProjectCard({
    required this.project,
    required this.isSwahili,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final name =
        project['project_name'] as String? ?? project['name'] as String? ?? '-';
    final status = project['status'] as String? ?? 'PENDING';
    final location = project['location'] as String?;
    final client = project['client'] as Map<String, dynamic>?;
    final contractValue = _toDouble(project['contract_value']);
    final statusColor = getStatusColor(status);
    final clientName =
        client?['name'] as String? ??
        '${client?['first_name'] ?? ''} ${client?['last_name'] ?? ''}'.trim();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
                      color: statusColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(Icons.business, color: statusColor, size: 24),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            Icon(
                              Icons.person_outline,
                              size: 14,
                              color: Colors.grey[500],
                            ),
                            const SizedBox(width: 4),
                            Expanded(
                              child: Text(
                                clientName.isEmpty ? '-' : clientName,
                                style: TextStyle(
                                  fontSize: 13,
                                  color: Colors.grey[600],
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  _StatusChip(status: status, isSwahili: isSwahili),
                ],
              ),
              if (location != null || contractValue > 0) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    if (location != null) ...[
                      Icon(
                        Icons.location_on,
                        size: 16,
                        color: const Color(0xFF1ABC9C),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        location,
                        style: TextStyle(fontSize: 13, color: Colors.grey[600]),
                      ),
                      const SizedBox(width: 16),
                    ],
                    if (contractValue > 0) ...[
                      Icon(
                        Icons.attach_money,
                        size: 16,
                        color: const Color(0xFF1ABC9C),
                      ),
                      Text(
                        'TZS ${NumberFormat.compact().format(contractValue)}',
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF1ABC9C),
                        ),
                      ),
                    ],
                  ],
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
}

class _StatusChip extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _StatusChip({required this.status, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final color = getStatusColor(status);
    String label;
    switch (status.toUpperCase()) {
      case 'APPROVED':
        label = isSwahili ? 'IMEDHINISHWA' : 'APPROVED';
        break;
      case 'COMPLETED':
        label = isSwahili ? 'IMEMALIZIKA' : 'COMPLETED';
        break;
      case 'PENDING':
        label = isSwahili ? 'INASUBIRI' : 'PENDING';
        break;
      case 'CREATED':
        label = isSwahili ? 'IMEUNDWA' : 'CREATED';
        break;
      default:
        label = status;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _ProjectDetailSheet extends ConsumerWidget {
  final int projectId;

  const _ProjectDetailSheet({required this.projectId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detailAsync = ref.watch(_projectDetailProvider(projectId));
    final isSwahili = ref.watch(isSwahiliProvider);

    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
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
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        isSwahili ? 'Maelezo ya Mradi' : 'Project Details',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
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
            child: detailAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => Center(child: Text('Error: $e')),
              data: (project) {
                final name =
                    project['project_name'] as String? ??
                    project['name'] as String? ??
                    '-';
                final status = project['status'] as String? ?? 'PENDING';
                final location = project['location'] as String?;
                final description = project['description'] as String?;
                final contractValue = _toDouble(project['contract_value']);
                final client = project['client'] as Map<String, dynamic>?;
                final projectType =
                    project['project_type'] as Map<String, dynamic>?;
                final startDate = project['start_date'] as String?;
                final endDate = project['expected_end_date'] as String?;
                final clientName =
                    client?['name'] as String? ??
                    '${client?['first_name'] ?? ''} ${client?['last_name'] ?? ''}'
                        .trim();

                return ListView(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: getStatusColor(
                                    status,
                                  ).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(
                                  Icons.business,
                                  color: getStatusColor(status),
                                  size: 28,
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      name,
                                      style: const TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    _StatusChip(
                                      status: status,
                                      isSwahili: isSwahili,
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    _DetailItem(
                      label: isSwahili ? 'Mteja' : 'Client',
                      value: clientName.isEmpty ? '-' : clientName,
                      icon: Icons.person,
                    ),
                    if (projectType != null)
                      _DetailItem(
                        label: isSwahili ? 'Aina ya Mradi' : 'Project Type',
                        value: projectType['name'] as String? ?? '-',
                        icon: Icons.category,
                      ),
                    if (location != null)
                      _DetailItem(
                        label: isSwahili ? 'Mahali' : 'Location',
                        value: location,
                        icon: Icons.location_on,
                      ),
                    if (startDate != null)
                      _DetailItem(
                        label: isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                        value: _formatDate(startDate),
                        icon: Icons.calendar_today,
                      ),
                    if (endDate != null)
                      _DetailItem(
                        label: isSwahili ? 'Tarehe ya Kumaliza' : 'End Date',
                        value: _formatDate(endDate),
                        icon: Icons.event,
                      ),
                    if (contractValue > 0)
                      _DetailItem(
                        label: isSwahili
                            ? 'Thamani ya Mkataba'
                            : 'Contract Value',
                        value:
                            'TZS ${NumberFormat('#,##0').format(contractValue)}',
                        icon: Icons.attach_money,
                        valueColor: const Color(0xFF1ABC9C),
                      ),
                    if (description != null && description.isNotEmpty)
                      _DetailItem(
                        label: isSwahili ? 'Maelezo' : 'Description',
                        value: description,
                        icon: Icons.description,
                      ),
                  ],
                );
              },
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

  String _formatDate(String? date) {
    if (date == null) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date;
    }
  }
}

class _DetailItem extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color? valueColor;

  const _DetailItem({
    required this.label,
    required this.value,
    required this.icon,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: const Color(0xFF1ABC9C).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 20, color: const Color(0xFF1ABC9C)),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w500,
                    color: valueColor ?? Colors.black87,
                  ),
                ),
              ],
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
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text('$error', style: TextStyle(color: Colors.grey[600])),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ],
      ),
    );
  }
}

class _ProjectFormSheet extends ConsumerStatefulWidget {
  const _ProjectFormSheet();

  @override
  ConsumerState<_ProjectFormSheet> createState() => _ProjectFormSheetState();
}

class _ProjectFormSheetState extends ConsumerState<_ProjectFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _locationController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _contractValueController = TextEditingController();
  String _status = 'PENDING';
  bool _loading = false;

  @override
  void dispose() {
    _nameController.dispose();
    _locationController.dispose();
    _descriptionController.dispose();
    _contractValueController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
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
                      color: Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  isSwahili ? 'Mradi Mpya' : 'New Project',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 24),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina la Mradi *' : 'Project Name *',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.business),
                  ),
                  validator: (v) => (v == null || v.isEmpty)
                      ? (isSwahili ? 'Kituraisha kinahitajika' : 'Required')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _locationController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Mahali' : 'Location',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.location_on),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _contractValueController,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Thamani ya Mkataba'
                        : 'Contract Value',
                    prefixText: 'TZS ',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.attach_money),
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili ? 'Hali' : 'Status',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: ['PENDING', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED']
                      .map((s) {
                        final isSelected = _status == s;
                        return ChoiceChip(
                          label: Text(_statusLabel(s, isSwahili)),
                          selected: isSelected,
                          selectedColor: const Color(
                            0xFF1ABC9C,
                          ).withValues(alpha: 0.2),
                          onSelected: (sel) {
                            if (sel) setState(() => _status = s);
                          },
                        );
                      })
                      .toList(),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Maelezo' : 'Description',
                    filled: true,
                    fillColor: Colors.grey[100],
                    prefixIcon: const Icon(Icons.description),
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _loading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF1ABC9C),
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
                            isSwahili ? 'Hifadhi' : 'Save',
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

  String _statusLabel(String s, bool isSwahili) {
    switch (s) {
      case 'PENDING':
        return isSwahili ? 'Inasubiri' : 'Pending';
      case 'IN_PROGRESS':
        return isSwahili ? 'Inaendelea' : 'In Progress';
      case 'COMPLETED':
        return isSwahili ? 'Imemalizika' : 'Completed';
      case 'CANCELLED':
        return isSwahili ? 'Imefutwa' : 'Cancelled';
      default:
        return s;
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      await api.post(
        '/projects',
        data: {
          'name': _nameController.text.trim(),
          'location': _locationController.text.trim().isEmpty
              ? null
              : _locationController.text.trim(),
          'description': _descriptionController.text.trim().isEmpty
              ? null
              : _descriptionController.text.trim(),
          'contract_value': double.tryParse(_contractValueController.text) ?? 0,
          'status': _status,
        },
      );
      if (mounted) Navigator.pop(context, true);
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
