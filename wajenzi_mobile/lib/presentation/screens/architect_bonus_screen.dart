import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/network/api_client.dart';
import '../../core/router/app_router.dart';
import '../providers/settings_provider.dart';
import '../widgets/common/empty_state_widget.dart';
import '../widgets/common/filter_bottom_sheet.dart';
import '../widgets/common/loading_widget.dart';

class ArchitectBonusScreen extends ConsumerStatefulWidget {
  const ArchitectBonusScreen({super.key});

  @override
  ConsumerState<ArchitectBonusScreen> createState() =>
      _ArchitectBonusScreenState();
}

class _ArchitectBonusScreenState extends ConsumerState<ArchitectBonusScreen> {
  final ScrollController _scrollController = ScrollController();
  final NumberFormat _money = NumberFormat('#,##0.##');

  List<dynamic> _tasks = <dynamic>[];
  Map<String, dynamic> _filters = <String, dynamic>{};
  Map<String, dynamic> _referenceData = <String, dynamic>{};
  Map<String, dynamic> _summary = <String, dynamic>{};
  bool _isLoading = false;
  bool _isLoadingMore = false;
  bool _hasMore = true;
  bool _isAdmin = false;
  int _currentPage = 1;

  @override
  void initState() {
    super.initState();
    _loadData(refresh: true);
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadData({bool refresh = false}) async {
    if (_isLoading) return;

    setState(() {
      _isLoading = true;
      if (refresh) {
        _currentPage = 1;
        _hasMore = true;
      }
    });

    try {
      final api = ref.read(apiClientProvider);
      final tasksResponse = await api.get(
        '/architect-bonus',
        queryParameters: {
          ..._filters,
          'page': _currentPage,
          'per_page': 20,
        },
      );
      final referenceResponse = await api.get('/architect-bonus/reference-data');

      final tasksData = tasksResponse.data['data'] as Map<String, dynamic>? ?? {};
      final referenceData =
          referenceResponse.data['data'] as Map<String, dynamic>? ?? {};
      final incomingTasks = (tasksData['data'] as List<dynamic>? ?? <dynamic>[]);
      final meta = tasksData['meta'] as Map<String, dynamic>? ?? {};

      setState(() {
        _tasks = refresh ? incomingTasks : <dynamic>[..._tasks, ...incomingTasks];
        _referenceData = referenceData;
        _summary = tasksData['summary'] as Map<String, dynamic>? ?? {};
        _isAdmin = referenceData['is_admin'] == true;
        _hasMore =
            (meta['current_page'] ?? 1) < (meta['last_page'] ?? 1);
      });
    } catch (error) {
      _showSnackBar(
        _humanizeError(error, fallback: 'Failed to load bonus tasks'),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _isLoadingMore = false;
        });
      }
    }
  }

  Future<void> _loadMoreData() async {
    if (_isLoading || _isLoadingMore || !_hasMore) return;

    setState(() {
      _isLoadingMore = true;
      _currentPage += 1;
    });

    await _loadData();
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;
    final position = _scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 160) {
      _loadMoreData();
    }
  }

  void _showSnackBar(String message, {bool isError = true}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

  String _humanizeError(Object error, {required String fallback}) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map<String, dynamic>) {
        final message = data['message']?.toString();
        if (message != null && message.isNotEmpty) {
          return message;
        }
        final validation = data['errors'];
        if (validation is Map) {
          for (final value in validation.values) {
            if (value is List && value.isNotEmpty) {
              return value.first.toString();
            }
            if (value != null) {
              return value.toString();
            }
          }
        }
      }
      if (error.response?.statusCode == 403) {
        return 'You do not have permission for this action.';
      }
      if (error.response?.statusCode == 404) {
        return 'Architect bonus endpoint was not found.';
      }
    }

    return fallback;
  }

  String _formatMoney(dynamic value) {
    final amount = double.tryParse('${value ?? 0}') ?? 0;
    return _money.format(amount);
  }

  String _formatDate(dynamic value) {
    final parsed = value is DateTime
        ? value
        : DateTime.tryParse('${value ?? ''}');
    if (parsed == null) return '-';
    return DateFormat('dd MMM yyyy').format(parsed);
  }

  String _statusLabel(String status) {
    return status.replaceAll('_', ' ').toUpperCase();
  }

  Color _statusColor(String? status) {
    switch ((status ?? '').toLowerCase()) {
      case 'paid':
        return Colors.green;
      case 'scored':
        return Colors.teal;
      case 'completed':
        return Colors.blue;
      case 'in_progress':
        return Colors.deepPurple;
      case 'no_bonus':
        return Colors.red;
      case 'pending':
      default:
        return Colors.grey;
    }
  }

  List<Map<String, dynamic>> _architectFilterOptions() {
    final architects =
        _referenceData['architects'] as List<dynamic>? ?? <dynamic>[];
    return architects
        .map(
          (item) => {
            'value': '${item['id']}',
            'label': '${item['name'] ?? 'Unknown'}',
          },
        )
        .toList();
  }

  List<Map<String, dynamic>> _statusFilterOptions() {
    final statuses =
        _referenceData['statuses'] as List<dynamic>? ?? <dynamic>[];
    return statuses
        .map(
          (item) => {
            'value': '${item['value']}',
            'label': '${item['label']}',
          },
        )
        .toList();
  }

  void _showFilterBottomSheet() {
    final isSwahili = ref.read(isSwahiliProvider);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) => FilterBottomSheet(
        title: isSwahili ? 'Chuja Kazi za Bonasi' : 'Filter Bonus Tasks',
        filters: _filters,
        options: {
          if (_architectFilterOptions().isNotEmpty)
            'architect_id': {
              'label': isSwahili ? 'Mhandisi' : 'Architect',
              'type': 'select',
              'options': _architectFilterOptions(),
            },
          if (_statusFilterOptions().isNotEmpty)
            'status': {
              'label': isSwahili ? 'Hali' : 'Status',
              'type': 'select',
              'options': _statusFilterOptions(),
            },
        },
        onApply: (filters) {
          Navigator.pop(sheetContext);
          setState(() {
            _filters = filters;
          });
          _loadData(refresh: true);
        },
        onReset: () {
          Navigator.pop(sheetContext);
          setState(() {
            _filters = <String, dynamic>{};
          });
          _loadData(refresh: true);
        },
      ),
    );
  }

  int _maxUnitsForBudget(double amount) {
    final tiers = _referenceData['tiers'] as List<dynamic>? ?? <dynamic>[];
    for (final tier in tiers) {
      final minAmount = double.tryParse('${tier['min_amount'] ?? 0}') ?? 0;
      final maxAmount = double.tryParse('${tier['max_amount'] ?? 0}') ?? 0;
      if (amount > minAmount && amount <= maxAmount) {
        return int.tryParse('${tier['max_units'] ?? 0}') ?? 0;
      }
    }

    if (tiers.isNotEmpty) {
      final highest = tiers.last;
      final highestMax = double.tryParse('${highest['max_amount'] ?? 0}') ?? 0;
      if (amount > highestMax) {
        return int.tryParse('${highest['max_units'] ?? 0}') ?? 0;
      }
    }

    return 0;
  }

  DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    return DateTime.tryParse(value.toString());
  }

  int _countWeekdays(DateTime start, DateTime end) {
    var count = 0;
    var current = DateTime(start.year, start.month, start.day);
    final target = DateTime(end.year, end.month, end.day);
    while (current.isBefore(target)) {
      if (current.weekday != DateTime.saturday &&
          current.weekday != DateTime.sunday) {
        count += 1;
      }
      current = current.add(const Duration(days: 1));
    }
    return count == 0 ? 1 : count;
  }

  Future<void> _showTaskFormSheet({Map<String, dynamic>? existingTask}) async {
    if (_referenceData.isEmpty) {
      await _loadData(refresh: true);
    }
    if (!mounted) return;

    final isSwahili = ref.read(isSwahiliProvider);
    final formKey = GlobalKey<FormState>();
    final projects = _referenceData['projects'] as List<dynamic>? ?? <dynamic>[];
    final architects =
        _referenceData['architects'] as List<dynamic>? ?? <dynamic>[];
    final leads = _referenceData['leads'] as List<dynamic>? ?? <dynamic>[];
    final isEditing = existingTask != null;

    int? selectedProjectId;
    int? selectedArchitectId = existingTask?['architect']?['id'] as int?;
    int? selectedLeadId = existingTask?['lead']?['id'] as int?;
    bool projectNameReadOnly = false;
    DateTime startDate =
        _parseDate(existingTask?['start_date']) ?? DateTime.now();
    DateTime scheduledCompletionDate =
        _parseDate(existingTask?['scheduled_completion_date']) ??
        DateTime.now().add(const Duration(days: 7));

    final projectNameController = TextEditingController(
      text: existingTask?['project_name']?.toString() ?? '',
    );
    final projectBudgetController = TextEditingController(
      text: existingTask == null
          ? ''
          : '${(double.tryParse('${existingTask['project_budget'] ?? 0}') ?? 0).toStringAsFixed(0)}',
    );
    final notesController = TextEditingController(
      text: existingTask?['notes']?.toString() ?? '',
    );

    for (final project in projects) {
      if ('${project['project_name']}' == '${existingTask?['project_name']}') {
        selectedProjectId = project['id'] as int?;
        projectNameReadOnly = true;
        break;
      }
    }

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (sheetContext, setSheetState) {
            final budget =
                double.tryParse(projectBudgetController.text.trim()) ?? 0;
            final maxUnits = _maxUnitsForBudget(budget);
            final mediaQuery = MediaQuery.of(sheetContext);

            Future<void> saveTask() async {
              if (!formKey.currentState!.validate()) return;

              final payload = <String, dynamic>{
                'project_id': selectedProjectId,
                'project_name': projectNameController.text.trim(),
                'architect_id': selectedArchitectId,
                'project_budget': double.tryParse(
                      projectBudgetController.text.trim(),
                    ) ??
                    0,
                'lead_id': selectedLeadId,
                'start_date': DateFormat('yyyy-MM-dd').format(startDate),
                'scheduled_completion_date': DateFormat(
                  'yyyy-MM-dd',
                ).format(scheduledCompletionDate),
                'notes': notesController.text.trim().isEmpty
                    ? null
                    : notesController.text.trim(),
              };

              try {
                final api = ref.read(apiClientProvider);
                final response = isEditing
                    ? await api.put(
                        '/architect-bonus/${existingTask['id']}',
                        data: payload,
                      )
                    : await api.post('/architect-bonus', data: payload);

                if (!mounted) return;
                Navigator.pop(sheetContext);
                await _loadData(refresh: true);
                _showSnackBar(
                  response.data['message']?.toString() ??
                      (isEditing
                          ? 'Bonus task updated successfully.'
                          : 'Bonus task created successfully.'),
                  isError: false,
                );
              } catch (error) {
                _showSnackBar(
                  _humanizeError(
                    error,
                    fallback: isEditing
                        ? 'Failed to update bonus task.'
                        : 'Failed to create bonus task.',
                  ),
                );
              }
            }

            return SafeArea(
              top: false,
              child: Container(
                constraints: BoxConstraints(
                  maxHeight: mediaQuery.size.height * 0.94,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(sheetContext).scaffoldBackgroundColor,
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(24),
                  ),
                ),
                child: Padding(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    20 + mediaQuery.viewInsets.bottom,
                  ),
                  child: Form(
                    key: formKey,
                    child: Column(
                      children: [
                        Container(
                          width: 44,
                          height: 4,
                          decoration: BoxDecoration(
                            color: Colors.grey[400],
                            borderRadius: BorderRadius.circular(999),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                isEditing
                                    ? (isSwahili
                                          ? 'Hariri Kazi ya Bonasi'
                                          : 'Edit Bonus Task')
                                    : (isSwahili
                                          ? 'Kazi Mpya ya Bonasi'
                                          : 'New Bonus Task'),
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            TextButton.icon(
                              onPressed: () => Navigator.pop(sheetContext),
                              icon: const Icon(Icons.close),
                              label: Text(isSwahili ? 'Funga' : 'Close'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Expanded(
                          child: SingleChildScrollView(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                DropdownButtonFormField<int>(
                                  value: selectedProjectId,
                                  isExpanded: true,
                                  decoration: InputDecoration(
                                    labelText: isSwahili
                                        ? 'Chagua Mradi'
                                        : 'Select Project',
                                  ),
                                  items: [
                                    DropdownMenuItem<int>(
                                      value: null,
                                      child: Text(
                                        isSwahili
                                            ? 'Weka mwenyewe'
                                            : 'Manual entry',
                                      ),
                                    ),
                                    ...projects.map(
                                      (project) => DropdownMenuItem<int>(
                                        value: project['id'] as int?,
                                        child: Text(
                                          '${project['project_name']}',
                                          overflow: TextOverflow.ellipsis,
                                          maxLines: 1,
                                        ),
                                      ),
                                    ),
                                  ],
                                  selectedItemBuilder: (context) => [
                                    Text(
                                      isSwahili
                                          ? 'Weka mwenyewe'
                                          : 'Manual entry',
                                      overflow: TextOverflow.ellipsis,
                                      maxLines: 1,
                                    ),
                                    ...projects.map(
                                      (project) => Text(
                                        '${project['project_name']}',
                                        overflow: TextOverflow.ellipsis,
                                        maxLines: 1,
                                      ),
                                    ),
                                  ],
                                  onChanged: (value) {
                                    setSheetState(() {
                                      selectedProjectId = value;
                                      if (value == null) {
                                        projectNameReadOnly = false;
                                        projectNameController.clear();
                                        projectBudgetController.clear();
                                      } else {
                                        final project = projects.firstWhere(
                                          (item) => item['id'] == value,
                                        );
                                        projectNameReadOnly = true;
                                        projectNameController.text =
                                            '${project['project_name'] ?? ''}';
                                        final contractValue =
                                            double.tryParse(
                                              '${project['contract_value'] ?? 0}',
                                            ) ??
                                            0;
                                        projectBudgetController.text =
                                            contractValue == 0
                                            ? ''
                                            : contractValue.toStringAsFixed(0);
                                      }
                                    });
                                  },
                                ),
                                const SizedBox(height: 12),
                                TextFormField(
                                  controller: projectNameController,
                                  readOnly: projectNameReadOnly,
                                  decoration: InputDecoration(
                                    labelText: isSwahili
                                        ? 'Jina la Mradi *'
                                        : 'Project Name *',
                                  ),
                                  validator: (value) {
                                    if ((value ?? '').trim().isEmpty) {
                                      return isSwahili
                                          ? 'Jina la mradi linahitajika'
                                          : 'Project name is required';
                                    }
                                    return null;
                                  },
                                ),
                                const SizedBox(height: 12),
                                DropdownButtonFormField<int>(
                                  value: selectedArchitectId,
                                  isExpanded: true,
                                  decoration: InputDecoration(
                                    labelText: isSwahili
                                        ? 'Mhandisi *'
                                        : 'Architect *',
                                  ),
                                  items: architects
                                      .map(
                                        (architect) => DropdownMenuItem<int>(
                                          value: architect['id'] as int?,
                                          child: Text(
                                            '${architect['name']}',
                                            overflow: TextOverflow.ellipsis,
                                            maxLines: 1,
                                          ),
                                        ),
                                      )
                                      .toList(),
                                  selectedItemBuilder: (context) => architects
                                      .map(
                                        (architect) => Text(
                                          '${architect['name']}',
                                          overflow: TextOverflow.ellipsis,
                                          maxLines: 1,
                                        ),
                                      )
                                      .toList(),
                                  onChanged: (value) {
                                    setSheetState(() {
                                      selectedArchitectId = value;
                                    });
                                  },
                                  validator: (value) => value == null
                                      ? (isSwahili
                                            ? 'Mhandisi anahitajika'
                                            : 'Architect is required')
                                      : null,
                                ),
                                const SizedBox(height: 12),
                                TextFormField(
                                  controller: projectBudgetController,
                                  keyboardType: const TextInputType.numberWithOptions(
                                    decimal: true,
                                  ),
                                  decoration: InputDecoration(
                                    labelText: isSwahili
                                        ? 'Bajeti ya Mradi (TZS) *'
                                        : 'Project Budget (TZS) *',
                                  ),
                                  onChanged: (_) => setSheetState(() {}),
                                  validator: (value) {
                                    final budget = double.tryParse(
                                      (value ?? '').trim(),
                                    );
                                    if (budget == null || budget < 0) {
                                      return isSwahili
                                          ? 'Weka bajeti sahihi'
                                          : 'Enter a valid budget';
                                    }
                                    if (_maxUnitsForBudget(budget) == 0) {
                                      return isSwahili
                                          ? 'Hakuna tier ya bonasi kwa bajeti hii'
                                          : 'No bonus tier found for this budget';
                                    }
                                    return null;
                                  },
                                ),
                                const SizedBox(height: 12),
                                Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.all(14),
                                  decoration: BoxDecoration(
                                    color: Colors.blue.withOpacity(0.08),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: Colors.blue.withOpacity(0.16),
                                    ),
                                  ),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        isSwahili
                                            ? 'Muhtasari wa Tier'
                                            : 'Tier Summary',
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                                      const SizedBox(height: 6),
                                      Text('Max Units: $maxUnits'),
                                      Text(
                                        'Max Bonus: TZS ${_formatMoney(maxUnits * 10000)}',
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  children: [
                                    Expanded(
                                      child: _DatePickerTile(
                                        label: isSwahili
                                            ? 'Tarehe ya Kuanza *'
                                            : 'Start Date *',
                                        value: _formatDate(startDate),
                                        onTap: () async {
                                          final selected =
                                              await showDatePicker(
                                            context: sheetContext,
                                            initialDate: startDate,
                                            firstDate: DateTime(2020),
                                            lastDate: DateTime(2100),
                                          );
                                          if (selected != null) {
                                            setSheetState(() {
                                              startDate = selected;
                                              if (!scheduledCompletionDate
                                                  .isAfter(startDate)) {
                                                scheduledCompletionDate =
                                                    startDate.add(
                                                      const Duration(days: 7),
                                                    );
                                              }
                                            });
                                          }
                                        },
                                      ),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: _DatePickerTile(
                                        label: isSwahili
                                            ? 'Tarehe ya Kumaliza *'
                                            : 'Scheduled Completion *',
                                        value: _formatDate(
                                          scheduledCompletionDate,
                                        ),
                                        onTap: () async {
                                          final selected =
                                              await showDatePicker(
                                            context: sheetContext,
                                            initialDate:
                                                scheduledCompletionDate,
                                            firstDate: startDate.add(
                                              const Duration(days: 1),
                                            ),
                                            lastDate: DateTime(2100),
                                          );
                                          if (selected != null) {
                                            setSheetState(() {
                                              scheduledCompletionDate =
                                                  selected;
                                            });
                                          }
                                        },
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                DropdownButtonFormField<int>(
                                  value: selectedLeadId,
                                  isExpanded: true,
                                  decoration: InputDecoration(
                                    labelText: isSwahili
                                        ? 'Unganisha Lead'
                                        : 'Link Lead',
                                  ),
                                  items: [
                                    DropdownMenuItem<int>(
                                      value: null,
                                      child: Text(
                                        isSwahili ? 'Hakuna' : 'None',
                                        overflow: TextOverflow.ellipsis,
                                        maxLines: 1,
                                      ),
                                    ),
                                    ...leads.map(
                                      (lead) => DropdownMenuItem<int>(
                                        value: lead['id'] as int?,
                                        child: Text(
                                          lead['lead_number'] == null
                                              ? '${lead['lead_name']}'
                                              : '${lead['lead_number']} - ${lead['lead_name']}',
                                          overflow: TextOverflow.ellipsis,
                                          maxLines: 1,
                                        ),
                                      ),
                                    ),
                                  ],
                                  selectedItemBuilder: (context) => [
                                    Text(
                                      isSwahili ? 'Hakuna' : 'None',
                                      overflow: TextOverflow.ellipsis,
                                      maxLines: 1,
                                    ),
                                    ...leads.map(
                                      (lead) => Text(
                                        lead['lead_number'] == null
                                            ? '${lead['lead_name']}'
                                            : '${lead['lead_number']} - ${lead['lead_name']}',
                                        overflow: TextOverflow.ellipsis,
                                        maxLines: 1,
                                      ),
                                    ),
                                  ],
                                  onChanged: (value) {
                                    setSheetState(() {
                                      selectedLeadId = value;
                                    });
                                  },
                                ),
                                const SizedBox(height: 12),
                                TextFormField(
                                  controller: notesController,
                                  maxLines: 3,
                                  decoration: InputDecoration(
                                    labelText:
                                        isSwahili ? 'Maelezo' : 'Notes',
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () => Navigator.pop(sheetContext),
                                icon: const Icon(Icons.close),
                                label: Text(isSwahili ? 'Funga' : 'Close'),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: saveTask,
                                icon: const Icon(Icons.save),
                                label: Text(
                                  isEditing
                                      ? (isSwahili ? 'Hifadhi' : 'Save')
                                      : (isSwahili ? 'Unda' : 'Create'),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _showScoreSheet(Map<String, dynamic> task) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final formKey = GlobalKey<FormState>();
    DateTime actualCompletionDate =
        _parseDate(task['actual_completion_date']) ?? DateTime.now();
    final designQualityController = TextEditingController(
      text: task['design_quality_score']?.toString() ?? '1.0',
    );
    final revisionsController = TextEditingController(
      text: task['client_revisions']?.toString() ?? '1',
    );

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (sheetContext, setSheetState) {
            final scheduledDays =
                int.tryParse('${task['scheduled_days'] ?? 0}') ?? 0;
            final startDate = _parseDate(task['start_date']) ?? DateTime.now();
            final actualDays = _countWeekdays(startDate, actualCompletionDate);
            final delay = actualDays - scheduledDays;
            final sp = actualDays == 0
                ? 0.0
                : (scheduledDays / actualDays).clamp(0.0, 1.1);
            final dq =
                double.tryParse(designQualityController.text.trim()) ?? 0.4;
            final revisions =
                int.tryParse(revisionsController.text.trim()) ?? 1;
            final ca = 1 / (revisions <= 0 ? 1 : revisions);
            final weights =
                _referenceData['weights'] as List<dynamic>? ?? <dynamic>[];
            var scheduleWeight = 0.4;
            var qualityWeight = 0.4;
            var clientWeight = 0.2;
            for (final item in weights) {
              switch ('${item['factor']}') {
                case 'schedule':
                  scheduleWeight =
                      double.tryParse('${item['weight'] ?? 0.4}') ?? 0.4;
                  break;
                case 'quality':
                  qualityWeight =
                      double.tryParse('${item['weight'] ?? 0.4}') ?? 0.4;
                  break;
                case 'client':
                  clientWeight =
                      double.tryParse('${item['weight'] ?? 0.2}') ?? 0.2;
                  break;
              }
            }
            final excessiveDelay = delay > (scheduledDays * 0.5);
            final performanceScore = excessiveDelay
                ? 0
                : (scheduleWeight * sp) +
                      (qualityWeight * dq) +
                      (clientWeight * ca);
            final maxUnits = int.tryParse('${task['max_units'] ?? 0}') ?? 0;
            final finalUnits = excessiveDelay
                ? 0
                : (maxUnits * performanceScore).round().clamp(0, maxUnits);
            final bonus = finalUnits * 10000;

            Future<void> submitScore() async {
              if (!formKey.currentState!.validate()) return;

              try {
                final api = ref.read(apiClientProvider);
                final response = await api.post(
                  '/architect-bonus/${task['id']}/score',
                  data: {
                    'actual_completion_date': DateFormat(
                      'yyyy-MM-dd',
                    ).format(actualCompletionDate),
                    'design_quality_score': double.parse(
                      designQualityController.text.trim(),
                    ),
                    'client_revisions': int.parse(
                      revisionsController.text.trim(),
                    ),
                  },
                );

                if (!mounted) return;
                Navigator.pop(sheetContext);
                await _loadData(refresh: true);
                _showSnackBar(
                  response.data['message']?.toString() ??
                      'Task scored successfully.',
                  isError: false,
                );
              } catch (error) {
                _showSnackBar(
                  _humanizeError(
                    error,
                    fallback: 'Failed to score bonus task.',
                  ),
                );
              }
            }

            return SafeArea(
              top: false,
              child: Container(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(sheetContext).size.height * 0.92,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(sheetContext).scaffoldBackgroundColor,
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(24),
                  ),
                ),
                child: Padding(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    20 + MediaQuery.of(sheetContext).viewInsets.bottom,
                  ),
                  child: Form(
                    key: formKey,
                    child: Column(
                      children: [
                        Container(
                          width: 44,
                          height: 4,
                          decoration: BoxDecoration(
                            color: Colors.grey[400],
                            borderRadius: BorderRadius.circular(999),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                isSwahili ? 'Pima Kazi' : 'Score Task',
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            TextButton.icon(
                              onPressed: () => Navigator.pop(sheetContext),
                              icon: const Icon(Icons.close),
                              label: Text(isSwahili ? 'Funga' : 'Close'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Expanded(
                          child: SingleChildScrollView(
                            child: Column(
                              children: [
                                _DetailCard(
                                  children: [
                                    _DetailRow(
                                      label: isSwahili ? 'Mradi' : 'Project',
                                      value: '${task['project_name'] ?? '-'}',
                                    ),
                                    _DetailRow(
                                      label: isSwahili
                                          ? 'Mhandisi'
                                          : 'Architect',
                                      value:
                                          '${task['architect']?['name'] ?? '-'}',
                                    ),
                                    _DetailRow(
                                      label: 'Max Units',
                                      value: '${task['max_units'] ?? '-'}',
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 12),
                                _DatePickerTile(
                                  label: isSwahili
                                      ? 'Tarehe halisi ya kumaliza *'
                                      : 'Actual Completion Date *',
                                  value: _formatDate(actualCompletionDate),
                                  onTap: () async {
                                    final selected = await showDatePicker(
                                      context: sheetContext,
                                      initialDate: actualCompletionDate,
                                      firstDate: startDate,
                                      lastDate: DateTime(2100),
                                    );
                                    if (selected != null) {
                                      setSheetState(() {
                                        actualCompletionDate = selected;
                                      });
                                    }
                                  },
                                ),
                                const SizedBox(height: 12),
                                TextFormField(
                                  controller: designQualityController,
                                  keyboardType:
                                      const TextInputType.numberWithOptions(
                                        decimal: true,
                                      ),
                                  decoration: const InputDecoration(
                                    labelText: 'Design Quality Score *',
                                  ),
                                  validator: (value) {
                                    final parsed = double.tryParse(
                                      (value ?? '').trim(),
                                    );
                                    if (parsed == null ||
                                        parsed < 0.4 ||
                                        parsed > 1.0) {
                                      return 'Design quality must be between 0.4 and 1.0';
                                    }
                                    return null;
                                  },
                                  onChanged: (_) => setSheetState(() {}),
                                ),
                                const SizedBox(height: 12),
                                TextFormField(
                                  controller: revisionsController,
                                  keyboardType: TextInputType.number,
                                  decoration: const InputDecoration(
                                    labelText: 'Client Revisions *',
                                  ),
                                  validator: (value) {
                                    final parsed = int.tryParse(
                                      (value ?? '').trim(),
                                    );
                                    if (parsed == null ||
                                        parsed < 1 ||
                                        parsed > 20) {
                                      return 'Client revisions must be between 1 and 20';
                                    }
                                    return null;
                                  },
                                  onChanged: (_) => setSheetState(() {}),
                                ),
                                const SizedBox(height: 12),
                                _DetailCard(
                                  title: isSwahili
                                      ? 'Muhtasari wa Hesabu'
                                      : 'Calculation Preview',
                                  children: [
                                    _DetailRow(
                                      label: 'SP',
                                      value: sp.toStringAsFixed(3),
                                    ),
                                    _DetailRow(
                                      label: 'DQ',
                                      value: dq.toStringAsFixed(3),
                                    ),
                                    _DetailRow(
                                      label: 'CA',
                                      value: ca.toStringAsFixed(3),
                                    ),
                                    _DetailRow(
                                      label: isSwahili
                                          ? 'Siku za kuchelewa'
                                          : 'Delay Days',
                                      value: '$delay',
                                    ),
                                    _DetailRow(
                                      label: 'Performance Score',
                                      value: performanceScore.toStringAsFixed(3),
                                    ),
                                    _DetailRow(
                                      label: isSwahili
                                          ? 'Units za mwisho'
                                          : 'Final Units',
                                      value: '$finalUnits',
                                    ),
                                    _DetailRow(
                                      label: isSwahili ? 'Bonasi' : 'Bonus',
                                      value: excessiveDelay
                                          ? (isSwahili
                                                ? 'Hakuna bonasi'
                                                : 'No bonus')
                                          : 'TZS ${_formatMoney(bonus)}',
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () => Navigator.pop(sheetContext),
                                icon: const Icon(Icons.close),
                                label: Text(isSwahili ? 'Funga' : 'Close'),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: submitScore,
                                icon: const Icon(Icons.star),
                                label: Text(
                                  isSwahili ? 'Wasilisha' : 'Submit Score',
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _confirmDelete(Map<String, dynamic> task) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Kazi' : 'Delete Task'),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta ${task['task_number']}?'
              : 'Are you sure you want to delete ${task['task_number']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Hapana' : 'Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.delete('/architect-bonus/${task['id']}');
      await _loadData(refresh: true);
      _showSnackBar(
        response.data['message']?.toString() ?? 'Bonus task deleted.',
        isError: false,
      );
    } catch (error) {
      _showSnackBar(
        _humanizeError(error, fallback: 'Failed to delete bonus task.'),
      );
    }
  }

  Future<void> _runTaskAction({
    required String path,
    required String fallbackError,
  }) async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post(path);
      await _loadData(refresh: true);
      _showSnackBar(
        response.data['message']?.toString() ?? 'Action completed successfully.',
        isError: false,
      );
    } catch (error) {
      _showSnackBar(_humanizeError(error, fallback: fallbackError));
    }
  }

  Future<void> _showTaskDetails(Map<String, dynamic> task) async {
    final isSwahili = ref.read(isSwahiliProvider);
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/architect-bonus/${task['id']}');
      final detail =
          response.data['data'] as Map<String, dynamic>? ?? task;

      if (!mounted) return;

      await showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        backgroundColor: Colors.transparent,
        builder: (sheetContext) {
          return SafeArea(
            top: false,
            child: Container(
              constraints: BoxConstraints(
                maxHeight: MediaQuery.of(sheetContext).size.height * 0.94,
              ),
              decoration: BoxDecoration(
                color: Theme.of(sheetContext).scaffoldBackgroundColor,
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(24),
                ),
              ),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
                child: Column(
                  children: [
                    Container(
                      width: 44,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey[400],
                        borderRadius: BorderRadius.circular(999),
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
                                '${detail['task_number'] ?? '-'}',
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${detail['project_name'] ?? '-'}',
                                style: TextStyle(color: Colors.grey[600]),
                              ),
                            ],
                          ),
                        ),
                        TextButton.icon(
                          onPressed: () => Navigator.pop(sheetContext),
                          icon: const Icon(Icons.close),
                          label: Text(isSwahili ? 'Funga' : 'Close'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Expanded(
                      child: SingleChildScrollView(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: _statusColor(
                                  detail['status']?.toString(),
                                ).withOpacity(0.1),
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Wrap(
                                spacing: 12,
                                runSpacing: 8,
                                children: [
                                  _MetricChip(
                                    label: isSwahili ? 'Hali' : 'Status',
                                    value: _statusLabel(
                                      '${detail['status'] ?? 'pending'}',
                                    ),
                                    color: _statusColor(
                                      detail['status']?.toString(),
                                    ),
                                  ),
                                  _MetricChip(
                                    label: isSwahili ? 'Bonasi' : 'Bonus',
                                    value:
                                        'TZS ${_formatMoney(detail['bonus_amount'])}',
                                    color: Colors.green,
                                  ),
                                  _MetricChip(
                                    label: isSwahili ? 'Units' : 'Units',
                                    value:
                                        '${detail['final_units'] ?? detail['max_units'] ?? '-'}',
                                    color: Colors.blue,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 12),
                            _DetailCard(
                              title: isSwahili
                                  ? 'Maelezo ya Kazi'
                                  : 'Task Details',
                              children: [
                                _DetailRow(
                                  label: isSwahili ? 'Mhandisi' : 'Architect',
                                  value:
                                      '${detail['architect']?['name'] ?? '-'}',
                                ),
                                _DetailRow(
                                  label: isSwahili
                                      ? 'Bajeti ya Mradi'
                                      : 'Project Budget',
                                  value:
                                      'TZS ${_formatMoney(detail['project_budget'])}',
                                ),
                                _DetailRow(
                                  label: 'Max Units',
                                  value: '${detail['max_units'] ?? '-'}',
                                ),
                                _DetailRow(
                                  label: isSwahili
                                      ? 'Tarehe ya kuanza'
                                      : 'Start Date',
                                  value: _formatDate(detail['start_date']),
                                ),
                                _DetailRow(
                                  label: isSwahili
                                      ? 'Mwisho uliopangwa'
                                      : 'Scheduled Completion',
                                  value: _formatDate(
                                    detail['scheduled_completion_date'],
                                  ),
                                ),
                                if (detail['actual_completion_date'] != null)
                                  _DetailRow(
                                    label: isSwahili
                                        ? 'Mwisho halisi'
                                        : 'Actual Completion',
                                    value: _formatDate(
                                      detail['actual_completion_date'],
                                    ),
                                  ),
                                if ((detail['notes'] ?? '').toString().isNotEmpty)
                                  _DetailRow(
                                    label: isSwahili ? 'Maelezo' : 'Notes',
                                    value: '${detail['notes']}',
                                  ),
                              ],
                            ),
                            if (detail['performance_score'] != null) ...[
                              const SizedBox(height: 12),
                              _DetailCard(
                                title: isSwahili
                                    ? 'Utendaji'
                                    : 'Performance Breakdown',
                                children: [
                                  _DetailRow(
                                    label: 'SP',
                                    value:
                                        '${detail['schedule_performance'] ?? '-'}',
                                  ),
                                  _DetailRow(
                                    label: 'DQ',
                                    value:
                                        '${detail['design_quality_score'] ?? '-'}',
                                  ),
                                  _DetailRow(
                                    label: 'CA',
                                    value:
                                        '${detail['client_approval_efficiency'] ?? '-'}',
                                  ),
                                  _DetailRow(
                                    label: 'Performance Score',
                                    value:
                                        '${detail['performance_score'] ?? '-'}',
                                  ),
                                ],
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Wrap(
                      spacing: 10,
                      runSpacing: 10,
                      children: [
                        if (detail['can_start'] == true)
                          FilledButton.icon(
                            onPressed: () async {
                              Navigator.pop(sheetContext);
                              await _runTaskAction(
                                path: '/architect-bonus/${detail['id']}/start',
                                fallbackError: 'Failed to start bonus task.',
                              );
                            },
                            icon: const Icon(Icons.play_arrow),
                            label: Text(isSwahili ? 'Anza' : 'Start'),
                          ),
                        if (detail['can_score'] == true)
                          FilledButton.icon(
                            onPressed: () async {
                              Navigator.pop(sheetContext);
                              await _showScoreSheet(detail);
                            },
                            icon: const Icon(Icons.star),
                            label: Text(isSwahili ? 'Pima' : 'Score'),
                          ),
                        if (detail['can_mark_paid'] == true)
                          FilledButton.icon(
                            onPressed: () async {
                              Navigator.pop(sheetContext);
                              await _runTaskAction(
                                path: '/architect-bonus/${detail['id']}/paid',
                                fallbackError:
                                    'Failed to mark bonus task as paid.',
                              );
                            },
                            icon: const Icon(Icons.payments_outlined),
                            label: Text(isSwahili ? 'Lipa' : 'Mark Paid'),
                          ),
                        if (detail['can_edit'] == true)
                          OutlinedButton.icon(
                            onPressed: () async {
                              Navigator.pop(sheetContext);
                              await _showTaskFormSheet(existingTask: detail);
                            },
                            icon: const Icon(Icons.edit_outlined),
                            label: Text(isSwahili ? 'Hariri' : 'Edit'),
                          ),
                        if (detail['can_delete'] == true)
                          OutlinedButton.icon(
                            onPressed: () async {
                              Navigator.pop(sheetContext);
                              await _confirmDelete(detail);
                            },
                            icon: const Icon(Icons.delete_outline),
                            label: Text(isSwahili ? 'Futa' : 'Delete'),
                          ),
                        OutlinedButton.icon(
                          onPressed: () => Navigator.pop(sheetContext),
                          icon: const Icon(Icons.close),
                          label: Text(isSwahili ? 'Funga' : 'Close'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      );
    } catch (error) {
      _showSnackBar(
        _humanizeError(error, fallback: 'Failed to load task details.'),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Bonasi ya Architect' : 'Architect Bonus'),
        actions: [
          if (_isAdmin)
            IconButton(
              icon: const Icon(Icons.tune),
              tooltip: isSwahili ? 'Uzito wa Bonasi' : 'Bonus Weights',
              onPressed: () => context.push('/architect-bonus/weights'),
            ),
          if (_isAdmin)
            IconButton(
              icon: const Icon(Icons.bar_chart),
              tooltip: isSwahili ? 'Ripoti' : 'Report',
              onPressed: () => context.push('/architect-bonus/module-report'),
            ),
          IconButton(
            icon: const Icon(Icons.filter_list),
            tooltip: isSwahili ? 'Chuja' : 'Filter',
            onPressed: _showFilterBottomSheet,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: isSwahili ? 'Onyesha upya' : 'Refresh',
            onPressed: () => _loadData(refresh: true),
          ),
        ],
      ),
      floatingActionButton: _isAdmin
          ? Padding(
              padding: const EdgeInsets.only(bottom: 80),
              child: FloatingActionButton(
                onPressed: () => _showTaskFormSheet(),
                tooltip: isSwahili ? 'Ongeza Kazi' : 'Add Task',
                child: const Icon(Icons.add),
              ),
            )
          : null,
      body: RefreshIndicator(
        onRefresh: () => _loadData(refresh: true),
        child: _isLoading && _tasks.isEmpty
            ? LoadingWidget(
                message: isSwahili
                    ? 'Inapakia kazi za bonasi...'
                    : 'Loading bonus tasks...',
              )
            : Column(
                children: [
                  if (_summary.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                      child: Row(
                        children: [
                          Expanded(
                            child: _SummaryCard(
                              title: isSwahili
                                  ? 'Jumla ya Bonasi'
                                  : 'Total Bonus',
                              value:
                                  'TZS ${_formatMoney(_summary['total_bonus_earned'])}',
                              icon: Icons.payments_outlined,
                              color: Colors.green,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _SummaryCard(
                              title: isSwahili
                                  ? 'Zilizokamilika'
                                  : 'Completed',
                              value:
                                  '${_summary['total_tasks_completed'] ?? 0}',
                              icon: Icons.task_alt,
                              color: Colors.blue,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _SummaryCard(
                              title: 'Pending',
                              value: '${_summary['pending_tasks'] ?? 0}',
                              icon: Icons.pending_actions,
                              color: Colors.orange,
                            ),
                          ),
                        ],
                      ),
                    ),
                  Expanded(
                    child: _tasks.isEmpty
                        ? EmptyStateWidget(
                            message: isSwahili
                                ? 'Hakuna kazi za bonasi zilizopatikana'
                                : 'No bonus tasks found',
                            icon: Icons.card_giftcard,
                          )
                        : ListView.builder(
                            controller: _scrollController,
                            padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
                            itemCount: _tasks.length + (_hasMore ? 1 : 0),
                            itemBuilder: (context, index) {
                              if (index >= _tasks.length) {
                                return const Padding(
                                  padding: EdgeInsets.all(24),
                                  child: Center(
                                    child: CircularProgressIndicator(),
                                  ),
                                );
                              }

                              final task =
                                  _tasks[index] as Map<String, dynamic>;
                              return _BonusTaskCard(
                                task: task,
                                isSwahili: isSwahili,
                                statusColor: _statusColor(
                                  task['status']?.toString(),
                                ),
                                formatMoney: _formatMoney,
                                formatDate: _formatDate,
                                onTap: () => _showTaskDetails(task),
                                onView: () => _showTaskDetails(task),
                                onEdit: (task['can_edit'] == true)
                                    ? () => _showTaskFormSheet(existingTask: task)
                                    : null,
                                onDelete: (task['can_delete'] == true)
                                    ? () => _confirmDelete(task)
                                    : null,
                              );
                            },
                          ),
                  ),
                ],
              ),
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String title;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(height: 8),
            Text(
              title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(color: Colors.grey[600], fontSize: 12),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 16,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BonusTaskCard extends StatelessWidget {
  const _BonusTaskCard({
    required this.task,
    required this.isSwahili,
    required this.statusColor,
    required this.formatMoney,
    required this.formatDate,
    required this.onTap,
    required this.onView,
    this.onEdit,
    this.onDelete,
  });

  final Map<String, dynamic> task;
  final bool isSwahili;
  final Color statusColor;
  final String Function(dynamic value) formatMoney;
  final String Function(dynamic value) formatDate;
  final VoidCallback onTap;
  final VoidCallback onView;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
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
                          '${task['task_number'] ?? '-'}',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${task['project_name'] ?? '-'}',
                          style: TextStyle(color: Colors.grey[700]),
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
                      color: statusColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      '${task['status'] ?? 'pending'}'
                          .replaceAll('_', ' ')
                          .toUpperCase(),
                      style: TextStyle(
                        color: statusColor,
                        fontWeight: FontWeight.w700,
                        fontSize: 11,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 12,
                runSpacing: 8,
                children: [
                  _InlineInfo(
                    icon: Icons.person_outline,
                    text: '${task['architect']?['name'] ?? '-'}',
                  ),
                  _InlineInfo(
                    icon: Icons.calendar_today_outlined,
                    text: formatDate(task['scheduled_completion_date']),
                  ),
                  _InlineInfo(
                    icon: Icons.payments_outlined,
                    text: 'TZS ${formatMoney(task['bonus_amount'])}',
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Budget: TZS ${formatMoney(task['project_budget'])}',
                      style: TextStyle(color: Colors.grey[700], fontSize: 13),
                    ),
                  ),
                  Text(
                    'Units: ${task['final_units'] ?? task['max_units'] ?? '-'}',
                    style: TextStyle(color: Colors.grey[700], fontSize: 13),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  OutlinedButton.icon(
                    onPressed: onView,
                    icon: const Icon(Icons.visibility_outlined),
                    label: Text(isSwahili ? 'Tazama' : 'View'),
                  ),
                  if (onEdit != null)
                    OutlinedButton.icon(
                      onPressed: onEdit,
                      icon: const Icon(Icons.edit_outlined),
                      label: Text(isSwahili ? 'Hariri' : 'Edit'),
                    ),
                  if (onDelete != null)
                    OutlinedButton.icon(
                      onPressed: onDelete,
                      icon: const Icon(Icons.delete_outline),
                      label: Text(isSwahili ? 'Futa' : 'Delete'),
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

class _InlineInfo extends StatelessWidget {
  const _InlineInfo({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 16, color: Colors.grey[600]),
        const SizedBox(width: 4),
        Text(
          text,
          style: TextStyle(fontSize: 12, color: Colors.grey[700]),
        ),
      ],
    );
  }
}

class _MetricChip extends StatelessWidget {
  const _MetricChip({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: TextStyle(fontSize: 11, color: Colors.grey[700]),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailCard extends StatelessWidget {
  const _DetailCard({this.title, required this.children});

  final String? title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (title != null) ...[
              Text(
                title!,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                ),
              ),
              const SizedBox(height: 10),
            ],
            ...children,
          ],
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 4,
            child: Text(
              label,
              style: TextStyle(color: Colors.grey[600]),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 5,
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}

class _DatePickerTile extends StatelessWidget {
  const _DatePickerTile({
    required this.label,
    required this.value,
    required this.onTap,
  });

  final String label;
  final String value;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          suffixIcon: const Icon(Icons.calendar_today),
        ),
        child: Text(value),
      ),
    );
  }
}
