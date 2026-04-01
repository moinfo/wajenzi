import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/empty_state_widget.dart';

class ArchitectBonusReportScreen extends ConsumerStatefulWidget {
  const ArchitectBonusReportScreen({super.key});

  @override
  ConsumerState<ArchitectBonusReportScreen> createState() =>
      _ArchitectBonusReportScreenState();
}

class _ArchitectBonusReportScreenState
    extends ConsumerState<ArchitectBonusReportScreen> {
  bool _isLoading = false;
  Map<String, dynamic> _reportData = {};
  String? _selectedMonth;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _selectedMonth = DateTime.now().toString().substring(0, 7); // YYYY-MM
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get(
        '/architect-bonus/report',
        queryParameters: {'month': _selectedMonth},
      );

      if (response.statusCode == 200) {
        setState(() {
          _reportData = response.data['data'] is Map
              ? Map<String, dynamic>.from(response.data['data'] as Map)
              : <String, dynamic>{};
          _isLoading = false;
        });
      }
    } catch (e) {
      String errorMessage = 'Error loading bonus report';

      if (e.toString().contains('401') ||
          e.toString().contains('Unauthorized')) {
        errorMessage = 'Authentication required. Please login again.';
      } else if (e.toString().contains('403') ||
          e.toString().contains('Forbidden')) {
        errorMessage =
            'Permission denied. You may not have access to bonus reports.';
      } else if (e.toString().contains('404')) {
        errorMessage =
            'Bonus report endpoint not found. Please check API configuration.';
      } else if (e.toString().contains('Connection')) {
        errorMessage =
            'Cannot connect to server. Please check your internet connection.';
      }

      setState(() {
        _isLoading = false;
        _errorMessage = errorMessage;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            duration: const Duration(seconds: 3),
            action: SnackBarAction(
              label: 'Retry',
              onPressed: () => _loadReport(),
            ),
          ),
        );
      }
    }
  }

  Future<void> _selectMonth() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.parse('${_selectedMonth}-01'),
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );

    if (picked != null) {
      setState(() {
        _selectedMonth = picked.toString().substring(0, 7); // YYYY-MM
      });
      _loadReport();
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => context.go('/reports'),
        ),
        title: Text(isSwahili ? 'Ripoti ya Bonasi' : 'Bonus Report'),
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_today),
            onPressed: _selectMonth,
            tooltip: isSwahili ? 'Chagua Mwezi' : 'Select Month',
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: isSwahili ? 'Onyesha Upya' : 'Refresh',
            onPressed: _loadReport,
          ),
        ],
      ),
      body: _isLoading
          ? LoadingWidget(
              message: isSwahili ? 'Inapakia ripoti...' : 'Loading report...',
            )
          : _errorMessage != null
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.error_outline,
                      size: 52,
                      color: Colors.red,
                    ),
                    const SizedBox(height: 12),
                    Text(_errorMessage!, textAlign: TextAlign.center),
                  ],
                ),
              ),
            )
          : _reportData.isEmpty
          ? EmptyStateWidget(
              message: isSwahili
                  ? 'Hakuna data ya ripoti'
                  : 'No report data available',
              icon: Icons.bar_chart,
            )
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Report Header
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili ? 'Ripoti ya Bonasi' : 'Bonus Report',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            '${isSwahili ? 'Mwezi' : 'Month'}: ${_formatMonth(_selectedMonth ?? '')}',
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
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
                        child: _SummaryCard(
                          title: isSwahili ? 'Jumla ya Kazi' : 'Total Tasks',
                          value: (_reportData['total_tasks'] ?? 0).toString(),
                          icon: Icons.task_alt,
                          color: Colors.orange,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),

                  // Architect Summary
                  Text(
                    isSwahili ? 'Muhtasari wa Mapatanzio' : 'Architect Summary',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),

                  // Architect Cards
                  ...(_reportData['architect_summary'] as List? ?? []).map((
                    architect,
                  ) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 16),
                      child: _ArchitectSummaryCard(
                        architect: architect,
                        isSwahili: isSwahili,
                      ),
                    );
                  }).toList(),
                  const SizedBox(height: 8),
                  Text(
                    isSwahili ? 'Maelezo ya Kazi' : 'Task Details',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  ...(_reportData['tasks'] as List? ?? []).map((task) {
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _TaskDetailCard(
                        task: Map<String, dynamic>.from(task as Map),
                        isSwahili: isSwahili,
                      ),
                    );
                  }).toList(),
                ],
              ),
            ),
    );
  }

  String _formatMonth(String monthString) {
    if (monthString.length < 7) return monthString;

    final year = monthString.substring(0, 4);
    final month = monthString.substring(5, 7);

    final months = [
      '01',
      '02',
      '03',
      '04',
      '05',
      '06',
      '07',
      '08',
      '09',
      '10',
      '11',
      '12',
    ];
    final monthNames = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];

    final monthIndex = months.indexOf(month);
    if (monthIndex < 0) return monthString;
    return '${monthNames[monthIndex]} $year';
  }

  String _formatCurrency(dynamic value) {
    final number = value is num
        ? value.toDouble()
        : double.tryParse(value?.toString() ?? '') ?? 0;
    return number.toStringAsFixed(number.truncateToDouble() == number ? 0 : 2);
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
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 24),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ArchitectSummaryCard extends StatelessWidget {
  final dynamic architect;
  final bool isSwahili;

  const _ArchitectSummaryCard({
    super.key,
    required this.architect,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  child: Text(
                    architect['architect']?['name']
                            ?.substring(0, 1)
                            .toUpperCase() ??
                        'A',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  backgroundColor: Colors.blue,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        architect['architect']?['name'] ??
                            (isSwahili
                                ? 'Mapatanzio Haijulikani'
                                : 'Unknown Architect'),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${isSwahili ? 'Alama ya Utendaji' : 'Performance Score'}: ${architect['avg_performance']?.toString() ?? '0'}',
                        style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _MetricItem(
                  label: isSwahili ? 'Kazi' : 'Tasks',
                  value: architect['tasks_count']?.toString() ?? '0',
                  icon: Icons.task_alt,
                ),
                _MetricItem(
                  label: isSwahili ? 'Vifurushi' : 'Units',
                  value: architect['total_units']?.toString() ?? '0',
                  icon: Icons.work,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _MetricItem(
                  label: isSwahili ? 'Bonasi' : 'Bonus',
                  value: 'TZS ${_formatNumber(architect['total_bonus'])}',
                  icon: Icons.trending_up,
                  color: Colors.green,
                ),
                _MetricItem(
                  label: isSwahili ? 'Wastani wa Utendaji' : 'Avg Performance',
                  value: architect['avg_performance']?.toString() ?? '0',
                  icon: Icons.speed,
                  color: Colors.orange,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _TaskDetailCard extends StatelessWidget {
  final Map<String, dynamic> task;
  final bool isSwahili;

  const _TaskDetailCard({required this.task, required this.isSwahili});

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'paid':
      case 'scored':
        return Colors.green;
      case 'no_bonus':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final architect = task['architect'] is Map
        ? Map<String, dynamic>.from(task['architect'] as Map)
        : const <String, dynamic>{};
    final status =
        task['status_label']?.toString() ?? task['status']?.toString() ?? '-';
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        task['task_number']?.toString() ??
                            (isSwahili ? 'Kazi Isiyojulikana' : 'Unknown Task'),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        task['project_name']?.toString() ?? '-',
                        style: TextStyle(color: Colors.grey[600]),
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
                    color: _statusColor(status).withOpacity(0.12),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    status,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: _statusColor(status),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              '${isSwahili ? 'Mapatanzio' : 'Architect'}: ${architect['name'] ?? '-'}',
              style: TextStyle(color: Colors.grey[700]),
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 12,
              runSpacing: 10,
              children: [
                _MetricChip(
                  label: isSwahili ? 'UP' : 'SP',
                  value: _formatNumber(task['schedule_performance']),
                ),
                _MetricChip(
                  label: isSwahili ? 'UB' : 'DQ',
                  value: _formatNumber(task['design_quality_score']),
                ),
                _MetricChip(
                  label: isSwahili ? 'UP' : 'CA',
                  value: _formatNumber(task['client_approval_efficiency']),
                ),
                _MetricChip(
                  label: isSwahili ? 'UT' : 'PS',
                  value: _formatPercent(task['performance_score']),
                ),
                _MetricChip(
                  label: isSwahili ? 'Vifurushi' : 'Units',
                  value: _formatNumber(task['final_units']),
                ),
                _MetricChip(
                  label: isSwahili ? 'Bonasi' : 'Bonus',
                  value: 'TZS ${_formatNumber(task['bonus_amount'])}',
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricChip extends StatelessWidget {
  final String label;
  final String value;

  const _MetricChip({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.grey.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[600])),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}

class _MetricItem extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color? color;

  const _MetricItem({
    super.key,
    required this.label,
    required this.value,
    required this.icon,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, size: 20, color: color ?? Colors.grey[600]),
        const SizedBox(height: 4),
        Text(label, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
        Text(
          value,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: color ?? Colors.black87,
          ),
        ),
      ],
    );
  }
}

String _formatNumber(dynamic value) {
  final number = value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '') ?? 0;
  return number.toStringAsFixed(number.truncateToDouble() == number ? 0 : 2);
}

String _formatPercent(dynamic value) {
  final number = value is num
      ? value.toDouble()
      : double.tryParse(value?.toString() ?? '') ?? 0;
  if (number <= 0) return '-';
  return '${(number * 100).round()}%';
}
