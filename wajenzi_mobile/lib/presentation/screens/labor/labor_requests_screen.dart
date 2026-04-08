import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final laborRequestsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final laborRequestsProjectFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);
final laborRequestsStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final laborRequestsStartDateProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);
final laborRequestsEndDateProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _laborRequestsProvider = FutureProvider.autoDispose<Map<String, dynamic>>(
  (ref) async {
    final api = ref.watch(apiClientProvider);
    final projectId = ref.watch(laborRequestsProjectFilterProvider);
    final status = ref.watch(laborRequestsStatusFilterProvider);
    final startDate = ref.watch(laborRequestsStartDateProvider);
    final endDate = ref.watch(laborRequestsEndDateProvider);
    final search = ref.watch(laborRequestsSearchProvider);

    final response = await api.get(
      '/labor/requests',
      queryParameters: {
        if (projectId != null) 'project_id': projectId,
        if (status != null) 'status': status,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
        if (search.isNotEmpty) 'search': search,
      },
    );
    return response.data['data'] as Map<String, dynamic>? ?? const {};
  },
);

final _laborReferenceDataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/requests/reference-data');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class LaborRequestsScreen extends ConsumerWidget {
  const LaborRequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final requestsAsync = ref.watch(_laborRequestsProvider);
    final selectedProject = ref.watch(laborRequestsProjectFilterProvider);
    final selectedStatus = ref.watch(laborRequestsStatusFilterProvider);
    final startDate = ref.watch(laborRequestsStartDateProvider);
    final endDate = ref.watch(laborRequestsEndDateProvider);
    final search = ref.watch(laborRequestsSearchProvider);
    final referenceDataAsync = ref.watch(_laborReferenceDataProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Maombi ya Labor' : 'Labor Requests'),
        actions: [
          IconButton(
            icon: const Icon(Icons.dashboard_rounded),
            tooltip: isSwahili ? 'Dashibodi' : 'Dashboard',
            onPressed: () => context.go('/labor-dashboard'),
          ),
        ],
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () {
            showModalBottomSheet(
              context: context,
              isScrollControlled: true,
              backgroundColor: Colors.transparent,
              builder: (ctx) => _LaborRequestFormSheet(
                isSwahili: isSwahili,
                isDarkMode: isDarkMode,
              ),
            );
          },
          child: const Icon(Icons.add),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_laborRequestsProvider);
          ref.invalidate(_laborReferenceDataProvider);
        },
        child: requestsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _LaborErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () {
              ref.invalidate(_laborRequestsProvider);
              ref.invalidate(_laborReferenceDataProvider);
            },
          ),
          data: (payload) {
            final requests = (payload['data'] as List? ?? const [])
                .cast<dynamic>();
            final meta = payload['meta'] as Map<String, dynamic>? ?? const {};
            final filters =
                payload['filters'] as Map<String, dynamic>? ?? const {};
            final projects =
                referenceDataAsync.valueOrNull?['projects'] as List? ??
                const [];
            final statuses =
                referenceDataAsync.valueOrNull?['statuses'] as List? ??
                const [];

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Tarehe' : 'Date Range',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: _DateFilterField(
                              label: isSwahili ? 'Tangu' : 'From',
                              value: startDate,
                              onChanged: (val) =>
                                  ref
                                          .read(
                                            laborRequestsStartDateProvider
                                                .notifier,
                                          )
                                          .state =
                                      val,
                              isDarkMode: isDarkMode,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _DateFilterField(
                              label: isSwahili ? 'Hadi' : 'To',
                              value: endDate,
                              onChanged: (val) =>
                                  ref
                                          .read(
                                            laborRequestsEndDateProvider
                                                .notifier,
                                          )
                                          .state =
                                      val,
                              isDarkMode: isDarkMode,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
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
                      DropdownButtonFormField<int?>(
                        value: selectedProject,
                        isExpanded: true,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF252540)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'Miradi Yote' : 'All Projects',
                            ),
                          ),
                          ...projects.map(
                            (project) => DropdownMenuItem<int?>(
                              value: project['id'] as int?,
                              child: Text(
                                project['project_name'] as String? ?? '-',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          ref
                                  .read(
                                    laborRequestsProjectFilterProvider.notifier,
                                  )
                                  .state =
                              value;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Hali' : 'Status',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<String?>(
                        value: selectedStatus,
                        isExpanded: true,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF252540)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'Hali Zote' : 'All Statuses',
                            ),
                          ),
                          ...statuses.map(
                            (s) => DropdownMenuItem<String?>(
                              value: s['value'] as String?,
                              child: Text(s['label'] as String? ?? '-'),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          ref
                                  .read(
                                    laborRequestsStatusFilterProvider.notifier,
                                  )
                                  .state =
                              value;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (requests.isEmpty)
                  _SectionCard(
                    isDarkMode: isDarkMode,
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          children: [
                            Icon(
                              Icons.inbox_outlined,
                              size: 64,
                              color: isDarkMode ? Colors.white30 : Colors.grey,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Hakuna maombi yaliyopatikana'
                                  : 'No requests found',
                              style: TextStyle(
                                fontSize: 16,
                                color: isDarkMode
                                    ? Colors.white54
                                    : Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  )
                else
                  ...requests.map(
                    (item) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _RequestCard(
                        item: Map<String, dynamic>.from(item as Map),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () => _editLaborRequest(context, ref, Map<String, dynamic>.from(item)),
                        onDelete: () => _deleteLaborRequest(context, ref, Map<String, dynamic>.from(item)),
                      ),
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

  Future<void> _editLaborRequest(BuildContext context, WidgetRef ref, Map<String, dynamic> request) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _LaborRequestFormSheet(
        isSwahili: ref.read(isSwahiliProvider),
        isDarkMode: ref.read(isDarkModeProvider),
        existingRequest: request,
      ),
    );

    if (result == true) {
      ref.invalidate(_laborRequestsProvider);
    }
  }

  Future<void> _deleteLaborRequest(BuildContext context, WidgetRef ref, Map<String, dynamic> request) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa Ombi la Labor' : 'Delete Labor Request'),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta ombi la labor #${request['request_number']}?'
              : 'Are you sure you want to delete labor request #${request['request_number']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.delete('/labor/requests/${request['id']}');
      
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imefutwa' : 'Deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }

      ref.invalidate(_laborRequestsProvider);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

class _SectionCard extends StatelessWidget {
  final bool isDarkMode;
  final Widget child;

  const _SectionCard({required this.isDarkMode, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
      child: child,
    );
  }
}

class _RequestCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  const _RequestCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
    this.onEdit,
    this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
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
                      item['request_number'] as String? ?? '-',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 16,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    if (item['project'] != null)
                      Text(
                        (item['project'] as Map)['project_name'] as String? ??
                            '-',
                        style: TextStyle(
                          fontSize: 13,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: _badgeColor(
                    item['status_badge_class'] as String?,
                  ).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  (item['status'] as String? ?? '-').toUpperCase(),
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _badgeColor(item['status_badge_class'] as String?),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (item['work_description'] != null) ...[
            Text(
              item['work_description'] as String? ?? '-',
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 8),
          ],
          if (item['artisan'] != null) ...[
            Row(
              children: [
                Icon(
                  Icons.person_outline,
                  size: 16,
                  color: isDarkMode ? Colors.white54 : AppColors.textHint,
                ),
                const SizedBox(width: 4),
                Text(
                  (item['artisan'] as Map)['name'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
          ],
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _InfoChip(
                  label: isSwahili ? 'Kiasi' : 'Amount',
                  value: _formatCurrency(_toDouble(item['proposed_amount'])),
                  isDarkMode: isDarkMode,
                ),
              ),
              if (item['negotiated_amount'] != null) ...[
                const SizedBox(width: 8),
                Expanded(
                  child: _InfoChip(
                    label: isSwahili ? 'Mhimili' : 'Negotiated',
                    value: _formatCurrency(
                      _toDouble(item['negotiated_amount']),
                    ),
                    isDarkMode: isDarkMode,
                  ),
                ),
              ],
              if (item['final_amount'] != null) ...[
                const SizedBox(width: 8),
                Expanded(
                  child: _InfoChip(
                    label: isSwahili ? 'Mwisho' : 'Final',
                    value: _formatCurrency(_toDouble(item['final_amount'])),
                    isDarkMode: isDarkMode,
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              if (item['start_date'] != null) ...[
                Icon(
                  Icons.calendar_today_outlined,
                  size: 14,
                  color: isDarkMode ? Colors.white54 : AppColors.textHint,
                ),
                const SizedBox(width: 4),
                Text(
                  item['start_date'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
                const SizedBox(width: 12),
              ],
              if (item['estimated_duration_days'] != null) ...[
                Icon(
                  Icons.timer_outlined,
                  size: 14,
                  color: isDarkMode ? Colors.white54 : AppColors.textHint,
                ),
                const SizedBox(width: 4),
                Text(
                  '${item['estimated_duration_days']} ${isSwahili ? 'days' : 'days'}',
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          // Action buttons - only show for draft status
          if (item['status'] == 'draft')
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (onEdit != null)
                  TextButton.icon(
                    onPressed: onEdit,
                    icon: const Icon(Icons.edit_outlined, size: 16),
                    label: Text(
                      isSwahili ? 'Hariri' : 'Edit',
                      style: const TextStyle(fontSize: 12),
                    ),
                    style: TextButton.styleFrom(
                      foregroundColor: AppColors.primary,
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                  ),
                if (onDelete != null) ...[
                  const SizedBox(width: 8),
                  TextButton.icon(
                    onPressed: onDelete,
                    icon: const Icon(Icons.delete_outline, size: 16),
                    label: Text(
                      isSwahili ? 'Futa' : 'Delete',
                      style: const TextStyle(fontSize: 12),
                    ),
                    style: TextButton.styleFrom(
                      foregroundColor: AppColors.error,
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                  ),
                ],
              ],
            ),
        ],
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _InfoChip({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              color: isDarkMode ? Colors.white54 : AppColors.textHint,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _LaborErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _LaborErrorView({
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

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatCurrency(double amount) {
  return NumberFormat('#,##0.00', 'en_US').format(amount);
}

Color _badgeColor(String? badgeClass) {
  return switch (badgeClass) {
    'success' => const Color(0xFF16A34A),
    'warning' => const Color(0xFFF59E0B),
    'danger' => const Color(0xFFDC2626),
    'info' => const Color(0xFF0891B2),
    _ => const Color(0xFF6B7280),
  };
}

class _DateFilterField extends StatelessWidget {
  final String label;
  final String? value;
  final Function(String?) onChanged;
  final bool isDarkMode;

  const _DateFilterField({
    required this.label,
    this.value,
    required this.onChanged,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final date = await showDatePicker(
          context: context,
          initialDate: value != null
              ? DateTime.tryParse(value!) ?? DateTime.now()
              : DateTime.now(),
          firstDate: DateTime(2021),
          lastDate: DateTime(2030),
        );
        if (date != null) {
          onChanged(date.toIso8601String().split('T')[0]);
        }
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          filled: true,
          fillColor: isDarkMode ? const Color(0xFF252540) : Colors.grey[100],
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          suffixIcon: value != null
              ? IconButton(
                  icon: const Icon(Icons.clear, size: 18),
                  onPressed: () => onChanged(null),
                )
              : const Icon(Icons.calendar_today, size: 18),
        ),
        child: Text(
          value ?? (label == 'From' ? '2026-04-01' : '2026-04-01'),
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }
}

class _LaborRequestFormSheet extends ConsumerStatefulWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final Map<String, dynamic>? existingRequest;
  const _LaborRequestFormSheet({
    required this.isSwahili,
    required this.isDarkMode,
    this.existingRequest,
  });
  @override
  ConsumerState<_LaborRequestFormSheet> createState() =>
      _LaborRequestFormSheetState();
}

class _LaborRequestFormSheetState
    extends ConsumerState<_LaborRequestFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _locationController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _durationController = TextEditingController();
  final _startDateController = TextEditingController();
  final _endDateController = TextEditingController();
  DateTime? _startDate;
  DateTime? _endDate;
  String? _startDateError;
  String? _endDateError;
  final _proposedAmountController = TextEditingController();
  final _negotiatedAmountController = TextEditingController();
  final _materialsController = TextEditingController();
  final _paymentTermsController = TextEditingController();
  final _notesController = TextEditingController();
  int? _selectedProjectId, _selectedPhaseId, _selectedArtisanId;
  String _currency = 'TZS';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _populateFormWithExistingData();
  }

  void _populateFormWithExistingData() {
    if (widget.existingRequest != null) {
      final request = widget.existingRequest!;
      _locationController.text = request['work_location']?.toString() ?? '';
      _descriptionController.text = request['work_description']?.toString() ?? '';
      _durationController.text = request['estimated_duration_days']?.toString() ?? '';
      _startDateController.text = request['start_date']?.toString() ?? '';
      _endDateController.text = request['end_date']?.toString() ?? '';
      _proposedAmountController.text = request['proposed_amount']?.toString() ?? '';
      _negotiatedAmountController.text = request['negotiated_amount']?.toString() ?? '';
      _materialsController.text = request['materials_included'] == true ? 'Yes' : '';
      _paymentTermsController.text = request['payment_terms']?.toString() ?? '';
      _notesController.text = request['artisan_assessment']?.toString() ?? '';
      
      // Debug: Log the original project_id
      print('DEBUG: Original project_id: ${request['project_id']}');
      
      _selectedProjectId = _normalizeNullableInt(request['project_id']);
      _selectedPhaseId = _normalizeNullableInt(request['construction_phase_id']);
      _selectedArtisanId = _normalizeNullableInt(request['artisan_id']);
      _currency = request['currency'] as String? ?? 'TZS';
      
      // Debug: Log the normalized project_id
      print('DEBUG: Normalized _selectedProjectId: $_selectedProjectId');
      
      if (request['start_date'] != null) {
        try {
          _startDate = DateTime.parse(request['start_date'] as String);
        } catch (_) {}
      }
      if (request['end_date'] != null) {
        try {
          _endDate = DateTime.parse(request['end_date'] as String);
        } catch (_) {}
      }
    }
  }

  int? _normalizeNullableInt(dynamic value) {
    if (value is int) {
      return value > 0 ? value : null;
    }
    if (value is String) {
      final parsed = int.tryParse(value);
      if (parsed == null || parsed <= 0) return null;
      return parsed;
    }
    return null;
  }

  @override
  void dispose() {
    _locationController.dispose();
    _descriptionController.dispose();
    _durationController.dispose();
    _startDateController.dispose();
    _endDateController.dispose();
    _proposedAmountController.dispose();
    _negotiatedAmountController.dispose();
    _materialsController.dispose();
    _paymentTermsController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final refDataAsync = ref.watch(_laborReferenceDataProvider);
    final projects = refDataAsync.valueOrNull?['projects'] as List? ?? [];
    final artisans = refDataAsync.valueOrNull?['artisans'] as List? ?? [];
    final phases = _selectedProjectId != null
        ? (refDataAsync.valueOrNull?['construction_phases'] as List? ?? [])
              .where((p) => p['project_id'] == _selectedProjectId)
              .toList()
        : <dynamic>[];

    return FractionallySizedBox(
      heightFactor: 0.9,
      child: Container(
        decoration: BoxDecoration(
          color: widget.isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    Container(
                      width: 42,
                      height: 4,
                      decoration: BoxDecoration(
                        color: widget.isDarkMode
                            ? Colors.white24
                            : Colors.black12,
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      widget.existingRequest != null
                          ? (widget.isSwahili
                              ? 'Hariri Ombi la Labor'
                              : 'Edit Labor Request')
                          : (widget.isSwahili
                              ? 'Ombi Jipya la Labor'
                              : 'New Labor Request'),
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: refDataAsync.when(
                  loading: () =>
                      const Center(child: CircularProgressIndicator()),
                  error: (e, _) => Center(child: Text('Error: $e')),
                  data: (_) => Form(
                    key: _formKey,
                    child: ListView(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      children: [
                        _sectionTitle(
                          widget.isSwahili
                              ? 'Maelezo ya Ombi'
                              : 'Request Details',
                        ),
                        _dropdown(
                          label: widget.isSwahili ? 'Mradi *' : 'Project *',
                          value: _selectedProjectId,
                          items: projects
                              .map(
                                (p) => DropdownMenuItem(
                                  value: p['id'] as int,
                                  child: Text(p['project_name']?.toString() ?? '-'),
                                ),
                              )
                              .toList(),
                          onChanged: (v) => setState(() {
                            _selectedProjectId = v;
                            _selectedPhaseId = null;
                            _clearDateErrors();
                          }),
                          isDarkMode: widget.isDarkMode,
                        ),
                        _dropdown(
                          label: widget.isSwahili
                              ? 'Awamu ya Utendaji'
                              : 'Construction Phase',
                          value: _selectedPhaseId,
                          items: phases
                              .map(
                                (p) => DropdownMenuItem(
                                  value: p['id'] as int,
                                  child: Text(p['name']?.toString() ?? '-'),
                                ),
                              )
                              .toList(),
                          onChanged: _selectedProjectId == null
                              ? null
                              : (v) => setState(() => _selectedPhaseId = v),
                          isDarkMode: widget.isDarkMode,
                          enabled: _selectedProjectId != null,
                        ),
                        _dropdown(
                          label: widget.isSwahili ? 'Fundi' : 'Artisan',
                          value: _selectedArtisanId,
                          items: artisans
                              .map(
                                (a) => DropdownMenuItem(
                                  value: a['id'] as int,
                                  child: Text(a['name']?.toString() ?? '-'),
                                ),
                              )
                              .toList(),
                          onChanged: (v) =>
                              setState(() => _selectedArtisanId = v),
                          isDarkMode: widget.isDarkMode,
                          hint: widget.isSwahili
                              ? 'Inaweza kuhusishwa baada ya kuidhinishwa'
                              : 'Can be assigned later',
                        ),
                        _textField(
                          controller: _locationController,
                          label: widget.isSwahili
                              ? 'Mahali pa Kazi'
                              : 'Work Location',
                          hint: widget.isSwahili
                              ? 'mf. Block A'
                              : 'e.g., Block A',
                          isDarkMode: widget.isDarkMode,
                        ),
                        _textField(
                          controller: _descriptionController,
                          label: widget.isSwahili
                              ? 'Maelezo ya Kazi *'
                              : 'Work Description *',
                          maxLines: 3,
                          isDarkMode: widget.isDarkMode,
                        ),
                        Row(
                          children: [
                            Expanded(
                              child: _textField(
                                controller: _durationController,
                                label: widget.isSwahili
                                    ? 'Muda (Siku)'
                                    : 'Duration (Days)',
                                keyboardType: TextInputType.number,
                                isDarkMode: widget.isDarkMode,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _dateField(
                                controller: _startDateController,
                                label: widget.isSwahili
                                    ? 'Tarehe ya Kuanza'
                                    : 'Start Date',
                                selectedDate: _startDate,
                                onDateSelected: (date) => setState(() {
                              _startDate = date;
                              _startDateError = null;
                            }),
                                isDarkMode: widget.isDarkMode,
                                errorText: _startDateError,
                              ),
                            ),
                          ],
                        ),
                        _dateField(
                          controller: _endDateController,
                          label: widget.isSwahili
                              ? 'Tarehe ya Kumalizia'
                              : 'End Date',
                          selectedDate: _endDate,
                          onDateSelected: (date) => setState(() {
                            _endDate = date;
                            _clearDateErrors();
                          }),
                          isDarkMode: widget.isDarkMode,
                          firstDate: _startDate,
                          errorText: _endDateError,
                        ),
                        const SizedBox(height: 16),
                        _sectionTitle(widget.isSwahili ? 'Malipo' : 'Payment'),
                        _dropdown(
                          label: widget.isSwahili ? 'Sarafu' : 'Currency',
                          value: _currency,
                          items: const [
                            DropdownMenuItem(value: 'TZS', child: Text('TZS')),
                            DropdownMenuItem(value: 'USD', child: Text('USD')),
                          ],
                          onChanged: (v) =>
                              setState(() => _currency = v ?? 'TZS'),
                          isDarkMode: widget.isDarkMode,
                        ),
                        Row(
                          children: [
                            Expanded(
                              child: _textField(
                                controller: _proposedAmountController,
                                label: widget.isSwahili
                                    ? 'Kiasi *'
                                    : 'Proposed Amount *',
                                keyboardType: TextInputType.number,
                                isDarkMode: widget.isDarkMode,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _textField(
                                controller: _negotiatedAmountController,
                                label: widget.isSwahili
                                    ? 'Kiasi kilichokubaliana'
                                    : 'Negotiated Amount',
                                keyboardType: TextInputType.number,
                                isDarkMode: widget.isDarkMode,
                              ),
                            ),
                          ],
                        ),
                        _textField(
                          controller: _materialsController,
                          label: widget.isSwahili
                              ? 'Vifaa'
                              : 'Materials Included',
                          maxLines: 2,
                          isDarkMode: widget.isDarkMode,
                        ),
                        _textField(
                          controller: _paymentTermsController,
                          label: widget.isSwahili
                              ? 'Masharti ya Malipo'
                              : 'Payment Terms',
                          maxLines: 2,
                          isDarkMode: widget.isDarkMode,
                        ),
                        const SizedBox(height: 16),
                        _sectionTitle(widget.isSwahili ? 'Maoni' : 'Notes'),
                        _textField(
                          controller: _notesController,
                          label: widget.isSwahili
                              ? 'Maoni'
                              : 'Assessment Notes',
                          maxLines: 3,
                          isDarkMode: widget.isDarkMode,
                        ),
                        const SizedBox(height: 24),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _isLoading ? null : _submitForm,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                            ),
                            child: _isLoading
                                ? const CircularProgressIndicator(
                                    color: Colors.white,
                                  )
                                : Text(
                                    widget.isSwahili ? 'Wasilisha' : 'Submit',
                                  ),
                          ),
                        ),
                        const SizedBox(height: 24),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _sectionTitle(String title) => Padding(
    padding: const EdgeInsets.only(top: 16, bottom: 8),
    child: Text(
      title,
      style: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        color: widget.isDarkMode ? Colors.white : AppColors.textPrimary,
      ),
    ),
  );

  Widget _textField({
    required TextEditingController controller,
    required String label,
    String? hint,
    int maxLines = 1,
    TextInputType? keyboardType,
    required bool isDarkMode,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      style: TextStyle(
        color: isDarkMode ? Colors.white : AppColors.textPrimary,
      ),
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
      ),
    ),
  );

  Widget _dateField({
    required TextEditingController controller,
    required String label,
    required DateTime? selectedDate,
    required Function(DateTime?) onDateSelected,
    required bool isDarkMode,
    DateTime? firstDate,
    String? errorText,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: TextFormField(
      controller: controller,
      readOnly: true,
      style: TextStyle(
        color: isDarkMode ? Colors.white : AppColors.textPrimary,
      ),
      decoration: InputDecoration(
        labelText: label,
        hintText: isDarkMode ? 'Chagua tarehe' : 'Select date',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        errorText: errorText,
        suffixIcon: Icon(
          Icons.calendar_today,
          color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
        ),
      ),
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: selectedDate ?? DateTime.now(),
          firstDate: firstDate ?? DateTime(2020),
          lastDate: DateTime.now().add(const Duration(days: 365)),
        );
        if (picked != null) {
          onDateSelected(picked);
          controller.text = DateFormat('yyyy-MM-dd').format(picked);
        }
      },
    ),
  );

  Widget _dropdown({
    required String label,
    required dynamic value,
    required List<DropdownMenuItem> items,
    required Function(dynamic)? onChanged,
    required bool isDarkMode,
    String? hint,
    bool enabled = true,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: DropdownButtonFormField(
      value: value,
      items: items,
      onChanged: enabled ? onChanged : null,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
      ),
      dropdownColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
    ),
  );

  Future<void> _submitForm() async {
    // For edit mode, project_id is not required if it wasn't changed
    final isEdit = widget.existingRequest != null;
    
    // Debug: Log the current state
    print('DEBUG: _submitForm - isEdit: $isEdit, _selectedProjectId: $_selectedProjectId');
    
    if (!isEdit && _selectedProjectId == null) {
      _showError(widget.isSwahili ? 'Chagua mradi' : 'Select project');
      return;
    }
    if (_descriptionController.text.isEmpty) {
      _showError(widget.isSwahili ? 'Andika maelezo' : 'Enter description');
      return;
    }
    if (_proposedAmountController.text.isEmpty) {
      _showError(widget.isSwahili ? 'Andika kiasi' : 'Enter amount');
      return;
    }
    if (_startDate == null) {
      setState(() => _startDateError = widget.isSwahili ? 'Tarehe ya kuanza inahitajika' : 'Start date is required');
      _showError(widget.isSwahili ? 'Chagua tarehe ya kuanza' : 'Select start date');
      return;
    }
    if (_endDate == null) {
      setState(() => _endDateError = widget.isSwahili ? 'Tarehe ya mwisho inahitajika' : 'End date is required');
      _showError(widget.isSwahili ? 'Chagua tarehe ya mwisho' : 'Select end date');
      return;
    }
    if (_endDate!.isBefore(_startDate!)) {
      setState(() => _endDateError = widget.isSwahili ? 'Tarehe ya mwisho lazima iwe baada ya tarehe ya kuanza' : 'End date must be after start date');
      _showError(widget.isSwahili ? 'Tarehe ya mwisho lazima iwe baada ya tarehe ya kuanza' : 'End date must be after start date');
      return;
    }
    
    // Validate work description minimum length
    if (_descriptionController.text.length < 10) {
      _showError(widget.isSwahili ? 'Maelezo ya kazi yanahitaji angalau herufi 10' : 'Work description must be at least 10 characters');
      return;
    }
    
    // Validate duration if provided
    final durationText = _durationController.text.trim();
    if (durationText.isNotEmpty) {
      final duration = int.tryParse(durationText);
      if (duration == null || duration < 1) {
        _showError(widget.isSwahili ? 'Muda lazima uwe nambari chanya zaidi ya 0' : 'Duration must be a positive number greater than 0');
        return;
      }
    }
    
    // Validate proposed amount
    final proposedAmountText = _proposedAmountController.text.trim();
    if (proposedAmountText.isEmpty) {
      _showError(widget.isSwahili ? 'Kiasi kilichopendekezwa kinahitajika' : 'Proposed amount is required');
      return;
    }
    final proposedAmount = double.tryParse(proposedAmountText);
    if (proposedAmount == null || proposedAmount < 0) {
      _showError(widget.isSwahili ? 'Kiasi halisi cha nambari chanya kinahitajika' : 'Valid positive amount required');
      return;
    }
    
    // Validate negotiated amount if provided
    final negotiatedAmountText = _negotiatedAmountController.text.trim();
    if (negotiatedAmountText.isNotEmpty) {
      final negotiatedAmount = double.tryParse(negotiatedAmountText);
      if (negotiatedAmount == null || negotiatedAmount < 0) {
        _showError(widget.isSwahili ? 'Kiasi halisi cha nambari chanya kinahitajika' : 'Valid positive negotiated amount required');
        return;
      }
    }
    setState(() => _isLoading = true);
    try {
      final api = ref.read(apiClientProvider);
      final isEdit = widget.existingRequest != null;
      
      if (isEdit) {
        // Update existing request - include required fields and optional fields with values
        final Map<String, dynamic> updateData = {};
        
        // Always include required fields for updates
        updateData['work_description'] = _descriptionController.text;
        updateData['proposed_amount'] = _proposedAmountController.text;
        
        // Always include project_id in edit mode (preserve existing if not changed)
        if (isEdit) {
          // In edit mode, always include project_id to preserve the relationship
          if (_selectedProjectId != null) {
            updateData['project_id'] = _selectedProjectId;
          }
        } else {
          // In create mode, only include if selected
          if (_selectedProjectId != null) updateData['project_id'] = _selectedProjectId;
        }
        if (_selectedPhaseId != null) updateData['construction_phase_id'] = _selectedPhaseId;
        if (_selectedArtisanId != null) updateData['artisan_id'] = _selectedArtisanId;
        if (_locationController.text.isNotEmpty) updateData['work_location'] = _locationController.text;
        if (_durationController.text.isNotEmpty) {
          final duration = int.tryParse(_durationController.text);
          if (duration != null && duration > 0) updateData['estimated_duration_days'] = duration;
        }
        if (_startDateController.text.isNotEmpty) updateData['start_date'] = _startDateController.text;
        if (_endDateController.text.isNotEmpty) updateData['end_date'] = _endDateController.text;
        if (_currency != 'TZS') updateData['currency'] = _currency; // Only send if not default
        if (_negotiatedAmountController.text.isNotEmpty) updateData['negotiated_amount'] = _negotiatedAmountController.text;
        if (_materialsController.text.isNotEmpty) updateData['materials_included'] = true;
        if (_paymentTermsController.text.isNotEmpty) updateData['payment_terms'] = _paymentTermsController.text;
        if (_notesController.text.isNotEmpty) updateData['artisan_assessment'] = _notesController.text;
        
        await api.put(
          '/labor/requests/${widget.existingRequest!['id']}',
          data: updateData,
        );
      } else {
        // Create new request
        await api.post(
          '/labor/requests',
          data: {
            'project_id': _selectedProjectId,
            'construction_phase_id': _selectedPhaseId,
            'artisan_id': _selectedArtisanId,
            'work_location': _locationController.text,
            'work_description': _descriptionController.text,
            'estimated_duration_days': _durationController.text.isNotEmpty
                ? int.tryParse(_durationController.text)
                : null,
            'start_date': _startDateController.text,
            'end_date': _endDateController.text,
            'currency': _currency,
            'proposed_amount': _proposedAmountController.text,
            'negotiated_amount': _negotiatedAmountController.text.isNotEmpty
                ? _negotiatedAmountController.text
                : null,
            'materials_included': _materialsController.text.isNotEmpty,
            'payment_terms': _paymentTermsController.text,
            'artisan_assessment': _notesController.text,
          },
        );
      }
      
      if (mounted) {
        ref.invalidate(_laborRequestsProvider);
        Navigator.of(context).pop(true); // Return true to indicate success
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isEdit 
                  ? (widget.isSwahili ? 'Ombi limehaririwa' : 'Request updated')
                  : (widget.isSwahili ? 'Ombi limewasilishwa' : 'Request submitted'),
            ),
          ),
        );
      }
    } catch (e) {
      // Handle API validation errors specifically
      if (e.toString().contains('422')) {
        // Try to extract validation errors from the response
        String errorMessage = widget.isSwahili 
            ? 'Tafadhali angalia fomu kwa makosa ya uhalalishaji' 
            : 'Please check the form for validation errors';
        
        // Try to parse the error response for more details
        if (e.toString().contains('errors')) {
          // This is a rough attempt to extract validation errors
          // In a real implementation, you'd parse the JSON response properly
          _showError('$errorMessage: $e');
        } else {
          _showError(errorMessage);
        }
      } else {
        _showError('Error: $e');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _clearDateErrors() {
    setState(() {
      _startDateError = null;
      _endDateError = null;
    });
  }

  void _showError(String msg) => ScaffoldMessenger.of(
    context,
  ).showSnackBar(SnackBar(content: Text(msg), backgroundColor: Colors.red));
}
