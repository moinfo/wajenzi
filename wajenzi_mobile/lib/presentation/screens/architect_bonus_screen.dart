import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_client.dart';
import '../providers/auth_provider.dart';
import '../widgets/common/loading_widget.dart';
import '../widgets/common/error_widget.dart';
import '../widgets/common/empty_state_widget.dart';
import '../widgets/common/filter_bottom_sheet.dart';

class ArchitectBonusScreen extends ConsumerStatefulWidget {
  const ArchitectBonusScreen({super.key});

  @override
  ConsumerState<ArchitectBonusScreen> createState() =>
      _ArchitectBonusScreenState();
}

class _ArchitectBonusScreenState extends ConsumerState<ArchitectBonusScreen> {
  final ScrollController _scrollController = ScrollController();
  List<dynamic> _tasks = [];
  Map<String, dynamic> _filters = {};
  Map<String, dynamic> _referenceData = {};
  Map<String, dynamic> _summary = {};
  bool _isLoading = false;
  bool _hasMore = true;
  int _currentPage = 1;
  bool _isAdmin = false;

  @override
  void initState() {
    super.initState();
    _loadData();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadData({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _currentPage = 1;
        _tasks.clear();
        _hasMore = true;
        _isLoading = true;
      });
    } else {
      setState(() {
        _isLoading = true;
      });
    }

    try {
      final api = ref.read(apiClientProvider);

      // Load tasks and reference data in parallel
      final tasksResponse = await api.get(
        '/architect-bonus',
        queryParameters: {
          ..._filters,
          'per_page': '20',
          'page': _currentPage.toString(),
        },
      );

      final referenceDataResponse = await api.get(
        '/architect-bonus/reference-data',
      );

      if (tasksResponse.statusCode == 200 &&
          referenceDataResponse.statusCode == 200) {
        final tasksData = tasksResponse.data['data'];
        final referenceData = referenceDataResponse.data['data'];

        setState(() {
          if (refresh) {
            _tasks = tasksData['data'] ?? [];
          } else {
            _tasks.addAll(tasksData['data'] ?? []);
          }
          _referenceData = referenceData;
          _summary = tasksData['summary'] ?? {};
          _isAdmin = referenceData['is_admin'] ?? false;
          _hasMore =
              (tasksData['meta']['current_page'] ?? 1) <
              (tasksData['meta']['last_page'] ?? 1);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });

      String errorMessage = 'Error loading bonus tasks';

      // Check for authentication errors
      if (e.toString().contains('401') ||
          e.toString().contains('Unauthorized')) {
        errorMessage = 'Authentication required. Please login again.';
      } else if (e.toString().contains('403') ||
          e.toString().contains('Forbidden')) {
        errorMessage =
            'Permission denied. You may not have access to bonus tasks.';
      } else if (e.toString().contains('404')) {
        errorMessage =
            'Bonus tasks endpoint not found. Please check API configuration.';
      } else if (e.toString().contains('Connection')) {
        errorMessage =
            'Cannot connect to server. Please check your internet connection.';
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            duration: const Duration(seconds: 3),
            action: SnackBarAction(
              label: 'Retry',
              onPressed: () => _loadData(refresh: true),
            ),
          ),
        );
      }
    }
  }

  Future<void> _loadMoreData() async {
    if (_isLoading || !_hasMore) return;

    setState(() {
      _currentPage++;
      _isLoading = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get(
        '/architect-bonus',
        queryParameters: {
          ..._filters,
          'per_page': '20',
          'page': _currentPage.toString(),
        },
      );

      if (response.statusCode == 200) {
        final data = response.data['data'];
        setState(() {
          _tasks.addAll(data['data'] ?? []);
          _hasMore =
              (data['meta']['current_page'] ?? 1) <
              (data['meta']['last_page'] ?? 1);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error loading more tasks: $e')));
      }
    }
  }

  void _onScroll() {
    if (_scrollController.position.pixels ==
        _scrollController.position.maxScrollExtent) {
      if (!_isLoading && _hasMore) {
        _loadMoreData();
      }
    }
  }

  void _showFilterBottomSheet() {
    // Convert reference data to options format expected by FilterBottomSheet
    Map<String, Map<String, dynamic>> options = {};

    if (_referenceData['architects'] != null) {
      options['architect_id'] = {
        'label': 'Architect',
        'type': 'select',
        'options': _referenceData['architects'],
      };
    }

    if (_referenceData['statuses'] != null) {
      options['status'] = {
        'label': 'Status',
        'type': 'select',
        'options': _referenceData['statuses'],
      };
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => FilterBottomSheet(
        title: 'Filter Bonus Tasks',
        filters: _filters,
        options: options,
        onApply: (filters) {
          setState(() {
            _filters = filters;
          });
          _loadData(refresh: true);
          Navigator.pop(context);
        },
        onReset: () {
          setState(() {
            _filters = {};
          });
          _loadData(refresh: true);
          Navigator.pop(context);
        },
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'paid':
        return Colors.green;
      case 'scored':
        return Colors.orange;
      case 'completed':
        return Colors.blue;
      case 'in_progress':
        return Colors.purple;
      case 'pending':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Architect Bonus'),
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        foregroundColor: Colors.white,
        actions: [
          if (_isAdmin) ...[
            IconButton(
              icon: const Icon(Icons.add),
              onPressed: () {
                // Navigate to create task
              },
            ),
            IconButton(
              icon: const Icon(Icons.tune),
              tooltip: 'Weights Configuration',
              onPressed: () {
                context.push('/architect-bonus/weights');
              },
            ),
            IconButton(
              icon: const Icon(Icons.bar_chart),
              tooltip: 'View Report',
              onPressed: () {
                context.push('/architect-bonus/report');
              },
            ),
          ],
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterBottomSheet,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => _loadData(refresh: true),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => _loadData(refresh: true),
        child: _isLoading
            ? const LoadingWidget(message: 'Loading bonus tasks...')
            : _tasks.isEmpty && _summary.isEmpty
                ? const EmptyStateWidget(
                    message: 'No bonus tasks found',
                    icon: Icons.card_giftcard,
                  )
                : Column(
                    children: [
                      // Summary Cards
                      if (_summary.isNotEmpty) ...[
                        Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            children: [
                              Expanded(
                                child: _SummaryCard(
                                  title: 'Total Bonus Earned',
                                  value:
                                      'TZS ${(_summary['total_bonus_earned'] ?? 0).toString()}',
                                  icon: Icons.trending_up,
                                  color: Colors.green,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: _SummaryCard(
                                  title: 'Tasks Completed',
                                  value:
                                      (_summary['total_tasks_completed'] ??
                                              0)
                                          .toString(),
                                  icon: Icons.check_circle,
                                  color: Colors.blue,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            children: [
                              Expanded(
                                child: _SummaryCard(
                                  title: 'Pending Tasks',
                                  value:
                                      (_summary['pending_tasks'] ??
                                              0)
                                          .toString(),
                                  icon: Icons.pending,
                                  color: Colors.orange,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                      // Tasks List
                      Expanded(
                        child: _tasks.isEmpty
                            ? Center(
                                child: Text(
                                  'No tasks found for current filters',
                                  style: TextStyle(
                                    color: Colors.grey[600],
                                  ),
                                ),
                              )
                            : ListView.builder(
                                controller: _scrollController,
                                padding: const EdgeInsets.all(16),
                                itemCount: _tasks.length + (_hasMore ? 1 : 0),
                                itemBuilder: (context, index) {
                                  if (index == _tasks.length) {
                                    return const Padding(
                                      padding: EdgeInsets.all(16),
                                      child: Center(child: CircularProgressIndicator()),
                                    );
                                  }

                                  final task = _tasks[index];
                                  return BonusTaskCard(
                                    task: task,
                                    onTap: () {
                                      // Navigate to task details
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
}

class _SummaryCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const _SummaryCard({
    super.key,
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 20),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    title,
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                    overflow: TextOverflow.ellipsis,
                    maxLines: 1,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 3),
            Text(
              value,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: color,
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
          ],
        ),
      ),
    );
  }
}

class BonusTaskCard extends StatelessWidget {
  final dynamic task;
  final VoidCallback onTap;

  const BonusTaskCard({super.key, required this.task, required this.onTap});

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'paid':
        return Colors.green;
      case 'scored':
        return Colors.orange;
      case 'completed':
        return Colors.blue;
      case 'in_progress':
        return Colors.purple;
      case 'pending':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          task['task_number'] ?? 'Unknown Task',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 4),
                        Text(
                          task['project_name'] ?? 'No Project',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[600],
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 6,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: _getStatusColor(task['status'] ?? ''),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      task['status'] ?? '',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                task['task_description'] ?? '',
                style: TextStyle(fontSize: 14, color: Colors.grey[700]),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.person_outline, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      task['architect']?['name'] ?? 'Unknown Architect',
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 8),
                  if (task['bonus_amount'] != null) ...[
                    Icon(Icons.card_giftcard, size: 16, color: Colors.green),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        'TZS ${task['bonus_amount']?.toString() ?? '0'}',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: Colors.green,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      task['due_date'] ?? 'No due date',
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Weight: ${task['bonus_weight'] ?? 1}',
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
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
