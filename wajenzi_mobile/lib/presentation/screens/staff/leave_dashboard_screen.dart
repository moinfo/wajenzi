import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _leaveBalanceProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/leave-requests/balance');
    final data = response.data is Map<String, dynamic>
        ? Map<String, dynamic>.from(response.data as Map<String, dynamic>)
        : <String, dynamic>{};
    return {
      ...data,
      'unavailable_on_live': false,
    };
  } on DioException catch (error) {
    if ((error.response?.statusCode ?? 0) == 404) {
      return const {
        'data': <String, dynamic>{'balances': <Map<String, dynamic>>[]},
        'unavailable_on_live': true,
      };
    }
    rethrow;
  }
});

final _recentLeaveRequestsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/leave-requests');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final items = data['data'] as List? ?? const [];
        final mapped = items
            .whereType<Map>()
            .map((item) => Map<String, dynamic>.from(item))
            .toList()
          ..sort((a, b) {
            final dateA =
                DateTime.tryParse(a['created_at']?.toString() ?? '') ??
                DateTime(2000);
            final dateB =
                DateTime.tryParse(b['created_at']?.toString() ?? '') ??
                DateTime(2000);
            return dateB.compareTo(dateA);
          });
        return {
          'items': mapped,
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return {
            'items': const <Map<String, dynamic>>[],
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _leaveTypesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/leave-requests/types');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

class LeaveDashboardScreen extends ConsumerStatefulWidget {
  const LeaveDashboardScreen({super.key});

  @override
  ConsumerState<LeaveDashboardScreen> createState() =>
      _LeaveDashboardScreenState();
}

class _LeaveDashboardScreenState extends ConsumerState<LeaveDashboardScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final balanceAsync = ref.watch(_leaveBalanceProvider);
    final requestsAsync = ref.watch(_recentLeaveRequestsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Dashibodi ya Likizo' : 'Leave Dashboard'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_leaveBalanceProvider);
          ref.invalidate(_recentLeaveRequestsProvider);
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                isSwahili ? 'Mwanacharaja la Likizo' : 'Leave Balance',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 12),
              balanceAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorCard(
                  message: e.toString(),
                  onRetry: () => ref.invalidate(_leaveBalanceProvider),
                  isSwahili: isSwahili,
                ),
                data: (balanceData) {
                  if (balanceData['unavailable_on_live'] == true) {
                    return _ErrorCard(
                      message: isSwahili
                          ? 'Leave Dashboard haipatikani kwenye live API kwa sasa.'
                          : 'Leave Dashboard is not available on the live API right now.',
                      onRetry: () => ref.invalidate(_leaveBalanceProvider),
                      isSwahili: isSwahili,
                    );
                  }
                  final balances = balanceData['data'] is Map
                      ? (balanceData['data'] as Map)['balances'] as List? ?? []
                      : <dynamic>[];
                  if (balances.isEmpty) {
                    return _EmptyBalanceCard(
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                    );
                  }
                  return Column(
                    children: balances.whereType<Map>().map((balance) {
                      final total = _toDouble(balance['days_allowed']);
                      final used = _toDouble(balance['days_used']);
                      final remaining = _toDouble(balance['days_remaining']);
                      return _LeaveBalanceCard(
                        name: balance['name']?.toString() ?? '-',
                        total: total,
                        used: used,
                        remaining: remaining,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      );
                    }).toList(),
                  );
                },
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    isSwahili ? 'Maombi ya Hivi Karibuni' : 'Recent Requests',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  TextButton(
                    onPressed: () {
                      context.push('/leave-requests');
                    },
                    child: Text(
                      isSwahili ? 'Tazama Zote' : 'View All',
                      style: const TextStyle(color: AppColors.primary),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              requestsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorCard(
                  message: e.toString(),
                  onRetry: () => ref.invalidate(_recentLeaveRequestsProvider),
                  isSwahili: isSwahili,
                ),
                data: (payload) {
                  final requests = (payload['items'] as List? ?? const [])
                      .whereType<Map<String, dynamic>>()
                      .toList();
                  if (requests.isEmpty) {
                    return _EmptyRequestsCard(
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                    );
                  }
                  return Column(
                    children: requests.take(5).map((request) {
                      return _LeaveRequestCard(
                        request: request,
                        index: requests.indexOf(request) + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      );
                    }).toList(),
                  );
                },
              ),
              const SizedBox(height: 100),
            ],
          ),
        ),
      ),
    );
  }

  void _showLeaveRequestForm(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.9,
        child: _LeaveRequestFormSheet(
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
        ),
      ),
    );
  }
}

class _LeaveBalanceCard extends StatelessWidget {
  final String name;
  final double total;
  final double used;
  final double remaining;
  final bool isSwahili;
  final bool isDarkMode;

  const _LeaveBalanceCard({
    required this.name,
    required this.total,
    required this.used,
    required this.remaining,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final progress = total > 0 ? (used / total).clamp(0.0, 1.0) : 0.0;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: isDarkMode ? Colors.white12 : Colors.grey[200]!,
        ),
      ),
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                  child: const Icon(
                    Icons.event_note,
                    color: AppColors.primary,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    name,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                _StatItem(
                  label: isSwahili ? 'Jumla' : 'Total',
                  value: '${remaining.toInt()}',
                  color: AppColors.primary,
                ),
                _StatItem(
                  label: isSwahili ? 'Imeaumiwa' : 'Used',
                  value: '${used.toInt()}',
                  color: Colors.orange,
                ),
                _StatItem(
                  label: isSwahili ? 'Inabaki' : 'Remaining',
                  value: '${remaining.toInt()}',
                  color: AppColors.success,
                ),
              ],
            ),
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: progress,
                backgroundColor: isDarkMode ? Colors.white12 : Colors.grey[200],
                valueColor: AlwaysStoppedAnimation<Color>(
                  remaining > 0 ? AppColors.success : Colors.red,
                ),
                minHeight: 6,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _StatItem({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(label, style: TextStyle(fontSize: 12, color: Colors.grey[500])),
        ],
      ),
    );
  }
}

class _LeaveRequestCard extends StatelessWidget {
  final Map<String, dynamic> request;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;

  const _LeaveRequestCard({
    required this.request,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final status = request['status']?.toString().toUpperCase() ?? 'PENDING';
    final statusColor = _getStatusColor(status);
    final leaveType = request['leave_type'] as Map?;
    final startDate = request['start_date']?.toString();
    final endDate = request['end_date']?.toString();
    final totalDays = request['total_days'];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: isDarkMode ? Colors.white12 : Colors.grey[200]!,
        ),
      ),
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: InkWell(
        onTap: () =>
            _showRequestDetails(context, request, isSwahili, isDarkMode),
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '$index',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: statusColor,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      leaveType?['name']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${_formatDate(startDate)} - ${_formatDate(endDate)}',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: statusColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            status,
                            style: TextStyle(
                              fontSize: 10,
                              color: statusColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '$totalDays ${isSwahili ? 'siku' : 'days'}',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_right,
                color: isDarkMode ? Colors.white54 : Colors.grey[400],
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showRequestDetails(
    BuildContext context,
    Map<String, dynamic> request,
    bool isSwahili,
    bool isDarkMode,
  ) {
    final leaveType = request['leave_type'] as Map?;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.5,
        child: Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            child: Column(
              children: [
                const SizedBox(height: 12),
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                    children: [
                      Row(
                        children: [
                          CircleAvatar(
                            radius: 24,
                            backgroundColor: AppColors.primary.withValues(
                              alpha: 0.1,
                            ),
                            child: const Icon(
                              Icons.event_note,
                              color: AppColors.primary,
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  leaveType?['name']?.toString() ?? '-',
                                  style: const TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 10,
                                    vertical: 2,
                                  ),
                                  decoration: BoxDecoration(
                                    color: _getStatusColor(
                                      request['status']
                                              ?.toString()
                                              .toUpperCase() ??
                                          'PENDING',
                                    ).withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Text(
                                    request['status']
                                            ?.toString()
                                            .toUpperCase() ??
                                        'PENDING',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: _getStatusColor(
                                        request['status']
                                                ?.toString()
                                                .toUpperCase() ??
                                            'PENDING',
                                      ),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                      _DetailRow(
                        icon: Icons.calendar_today_outlined,
                        label: isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                        value: _formatDate(request['start_date']?.toString()),
                        isDarkMode: isDarkMode,
                      ),
                      _DetailRow(
                        icon: Icons.event_outlined,
                        label: isSwahili ? 'Tarehe ya Kumaliza' : 'End Date',
                        value: _formatDate(request['end_date']?.toString()),
                        isDarkMode: isDarkMode,
                      ),
                      _DetailRow(
                        icon: Icons.timelapse_outlined,
                        label: isSwahili ? 'Jumla ya Siku' : 'Total Days',
                        value:
                            '${request['total_days']} ${isSwahili ? 'siku' : 'days'}',
                        isDarkMode: isDarkMode,
                      ),
                      if (request['reason']?.toString().trim().isNotEmpty ==
                          true)
                        _DetailRow(
                          icon: Icons.description_outlined,
                          label: isSwahili ? 'Sababu' : 'Reason',
                          value: request['reason']?.toString() ?? '-',
                          isDarkMode: isDarkMode,
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final bool isDarkMode;

  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: AppColors.primary),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white54
                        : AppColors.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 15,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
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

class _LeaveRequestFormSheet extends ConsumerStatefulWidget {
  final bool isSwahili;
  final bool isDarkMode;

  const _LeaveRequestFormSheet({
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  ConsumerState<_LeaveRequestFormSheet> createState() =>
      _LeaveRequestFormSheetState();
}

class _LeaveRequestFormSheetState
    extends ConsumerState<_LeaveRequestFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _reasonController = TextEditingController();
  final _startDateController = TextEditingController();
  final _endDateController = TextEditingController();

  int? _selectedTypeId;
  bool _saving = false;

  @override
  void dispose() {
    _reasonController.dispose();
    _startDateController.dispose();
    _endDateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final leaveTypesAsync = ref.watch(_leaveTypesProvider);

    return Container(
      decoration: BoxDecoration(
        color: widget.isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            const SizedBox(height: 12),
            Container(
              width: 44,
              height: 5,
              decoration: BoxDecoration(
                color: widget.isDarkMode ? Colors.white24 : Colors.black12,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            Expanded(
              child: ListView(
                padding: EdgeInsets.fromLTRB(
                  20,
                  16,
                  20,
                  MediaQuery.of(context).viewInsets.bottom + 24,
                ),
                children: [
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          children: [
                            CircleAvatar(
                              radius: 24,
                              backgroundColor: AppColors.primary.withValues(
                                alpha: 0.1,
                              ),
                              child: const Icon(
                                Icons.add,
                                color: AppColors.primary,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Text(
                              widget.isSwahili
                                  ? 'Omba Likizo'
                                  : 'Apply for Leave',
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),
                        leaveTypesAsync.when(
                          loading: () =>
                              const Center(child: CircularProgressIndicator()),
                          error: (e, _) => Text('Error loading leave types'),
                          data: (types) => _buildDropdown(types),
                        ),
                        const SizedBox(height: 16),
                        _buildDateInput(
                          controller: _startDateController,
                          label: widget.isSwahili
                              ? 'Tarehe ya Kuanza *'
                              : 'Start Date *',
                        ),
                        const SizedBox(height: 16),
                        _buildDateInput(
                          controller: _endDateController,
                          label: widget.isSwahili
                              ? 'Tarehe ya Kumaliza *'
                              : 'End Date *',
                        ),
                        const SizedBox(height: 16),
                        _buildInput(
                          controller: _reasonController,
                          label: widget.isSwahili ? 'Sababu *' : 'Reason *',
                          maxLines: 4,
                        ),
                        const SizedBox(height: 24),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  widget.isSwahili ? 'Wasilisha' : 'Submit',
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDropdown(List<Map<String, dynamic>> types) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: types.any((t) => _toInt(t['id']) == _selectedTypeId)
          ? _selectedTypeId
          : null,
      validator: (selected) => selected == null ? 'Required' : null,
      decoration: InputDecoration(
        labelText: widget.isSwahili ? 'Aina ya Likizo *' : 'Leave Type *',
        prefixIcon: const Icon(Icons.event_note_outlined, size: 20),
        filled: true,
        fillColor: widget.isDarkMode
            ? const Color(0xFF2A2A3E)
            : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
      ),
      items: types
          .map(
            (type) => DropdownMenuItem<int>(
              value: _toInt(type['id']),
              child: Text(
                '${type['name']} (${type['days_allowed']} ${widget.isSwahili ? 'siku' : 'days'})',
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(),
      onChanged: (value) => setState(() => _selectedTypeId = value),
    );
  }

  Widget _buildDateInput({
    required TextEditingController controller,
    required String label,
  }) {
    return TextFormField(
      controller: controller,
      readOnly: true,
      validator: (value) =>
          value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.calendar_today_outlined, size: 20),
        filled: true,
        fillColor: widget.isDarkMode
            ? const Color(0xFF2A2A3E)
            : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
      ),
      onTap: () async {
        final initialDate =
            DateTime.tryParse(controller.text) ?? DateTime.now();
        final picked = await showDatePicker(
          context: context,
          initialDate: initialDate,
          firstDate: DateTime.now(),
          lastDate: DateTime(2100),
        );
        if (picked != null) {
          controller.text = DateFormat('yyyy-MM-dd').format(picked);
        }
      },
    );
  }

  Widget _buildInput({
    required TextEditingController controller,
    required String label,
    int maxLines = 1,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      validator: (value) =>
          value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.description_outlined, size: 20),
        filled: true,
        fillColor: widget.isDarkMode
            ? const Color(0xFF2A2A3E)
            : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'leave_type_id': _selectedTypeId,
        'start_date': _startDateController.text.trim(),
        'end_date': _endDateController.text.trim(),
        'reason': _reasonController.text.trim(),
      };

      await api.post('/leave-requests', data: data);

      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              widget.isSwahili
                  ? 'Maombi yamewasilishwa'
                  : 'Leave request submitted',
            ),
            backgroundColor: AppColors.success,
          ),
        );
        ref.invalidate(_recentLeaveRequestsProvider);
        ref.invalidate(_leaveBalanceProvider);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(error.toString()),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _EmptyBalanceCard extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;

  const _EmptyBalanceCard({required this.isSwahili, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: isDarkMode ? Colors.white12 : Colors.grey[200]!,
        ),
      ),
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Icon(Icons.event_note_outlined, size: 48, color: Colors.grey[400]),
            const SizedBox(height: 12),
            Text(
              isSwahili ? 'Hakuna taarifa za likizo' : 'No leave balance data',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyRequestsCard extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;

  const _EmptyRequestsCard({required this.isSwahili, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: isDarkMode ? Colors.white12 : Colors.grey[200]!,
        ),
      ),
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Icon(Icons.inbox_outlined, size: 48, color: Colors.grey[400]),
            const SizedBox(height: 12),
            Text(
              isSwahili ? 'Hakuna maombi ya likizo' : 'No leave requests',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }
}

class _ErrorCard extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  final bool isSwahili;

  const _ErrorCard({
    required this.message,
    required this.onRetry,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: AppColors.error),
      ),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const Icon(Icons.error_outline, size: 48, color: AppColors.error),
            const SizedBox(height: 12),
            Text(
              isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
            ),
          ],
        ),
      ),
    );
  }
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  final date = DateTime.tryParse(raw);
  if (date == null) return raw;
  return DateFormat('dd MMM yyyy').format(date);
}

Color _getStatusColor(String status) {
  switch (status.toUpperCase()) {
    case 'APPROVED':
      return AppColors.success;
    case 'REJECTED':
      return AppColors.error;
    case 'PENDING':
      return Colors.orange;
    default:
      return Colors.grey;
  }
}
