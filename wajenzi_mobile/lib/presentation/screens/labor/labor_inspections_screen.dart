import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/empty_state_widget.dart';
import '../../widgets/common/filter_bottom_sheet.dart';

class LaborInspectionsScreen extends ConsumerStatefulWidget {
  const LaborInspectionsScreen({super.key});

  @override
  ConsumerState<LaborInspectionsScreen> createState() => _LaborInspectionsScreenState();
}

class _LaborInspectionsScreenState extends ConsumerState<LaborInspectionsScreen> {
  final ScrollController _scrollController = ScrollController();
  int _currentPage = 1;
  bool _isLoading = false;
  bool _hasMore = true;
  
  List<dynamic> _inspections = [];
  List<dynamic> _projects = [];
  List<dynamic> _contracts = [];
  Map<String, dynamic> _filters = {};
  
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

  void _onScroll() {
    if (_scrollController.position.pixels == _scrollController.position.maxScrollExtent) {
      _loadMoreData();
    }
  }

  Future<void> _loadData({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _currentPage = 1;
        _inspections.clear();
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

      final inspectionsResponse = await api.get('/labor/inspections', queryParameters: {
        ..._filters,
        'per_page': '20',
        'page': _currentPage.toString(),
      });
      
      final referenceDataResponse = await api.get('/labor/inspections/reference-data');

      if (inspectionsResponse.statusCode == 200 && referenceDataResponse.statusCode == 200) {
        final inspectionsData = inspectionsResponse.data['data'] as Map<String, dynamic>? ?? const {};
        final referenceData = referenceDataResponse.data['data'] as Map<String, dynamic>? ?? const {};

        setState(() {
          if (refresh) {
            _inspections = inspectionsData['data'] ?? [];
          } else {
            _inspections.addAll(inspectionsData['data'] ?? []);
          }
          
          _projects = referenceData['projects'] ?? [];
          _contracts = referenceData['contracts'] ?? [];
          
          _hasMore = (inspectionsData['meta']['current_page'] ?? 1) < (inspectionsData['meta']['last_page'] ?? 1);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      
      String errorMessage = 'Error loading inspections';
      
      // Check for authentication errors
      if (e.toString().contains('401') || e.toString().contains('Unauthorized')) {
        errorMessage = 'Authentication required. Please login again.';
      } else if (e.toString().contains('403') || e.toString().contains('Forbidden')) {
        errorMessage = 'Permission denied. You may not have access to inspections.';
      } else if (e.toString().contains('404')) {
        errorMessage = 'Inspections endpoint not found. Please check API configuration.';
      } else if (e.toString().contains('Connection')) {
        errorMessage = 'Cannot connect to server. Please check your internet connection.';
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
      final response = await api.get('/labor/inspections', queryParameters: {
        ..._filters,
        'per_page': '20',
        'page': _currentPage.toString(),
      });

      if (response.statusCode == 200) {
        final data = response.data['data'] as Map<String, dynamic>? ?? const {};
        setState(() {
          _inspections.addAll(data['data'] ?? []);
          _hasMore = (data['meta']['current_page'] ?? 1) < (data['meta']['last_page'] ?? 1);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _currentPage--; // Reset page number on error
      });
    }
  }

  void _showFilterBottomSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => FilterBottomSheet(
        title: 'Filter Inspections',
        filters: _filters,
        options: {
          'project_id': {
            'label': 'Project',
            'type': 'select',
            'options': _projects.map((p) => {
              'value': p['id'].toString(),
              'label': p['project_name'],
            }).toList(),
          },
          'contract_id': {
            'label': 'Contract',
            'type': 'select',
            'options': _contracts.map((c) => {
              'value': c['id'].toString(),
              'label': '${c['contract_number']} - ${c['artisan_name']}',
            }).toList(),
          },
          'status': {
            'label': 'Status',
            'type': 'select',
            'options': [
              {'value': 'draft', 'label': 'Draft'},
              {'value': 'pending', 'label': 'Pending'},
              {'value': 'verified', 'label': 'Verified'},
              {'value': 'approved', 'label': 'Approved'},
              {'value': 'rejected', 'label': 'Rejected'},
            ],
          },
          'inspection_type': {
            'label': 'Type',
            'type': 'select',
            'options': [
              {'value': 'routine', 'label': 'Routine'},
              {'value': 'progress', 'label': 'Progress'},
              {'value': 'milestone', 'label': 'Milestone'},
              {'value': 'quality', 'label': 'Quality'},
              {'value': 'final', 'label': 'Final'},
              {'value': 'safety', 'label': 'Safety'},
            ],
          },
          'start_date': {
            'label': 'Start Date',
            'type': 'date',
          },
          'end_date': {
            'label': 'End Date',
            'type': 'date',
          },
        },
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
      case 'approved':
        return Colors.green;
      case 'verified':
        return Colors.blue;
      case 'pending':
        return Colors.orange;
      case 'rejected':
        return Colors.red;
      case 'draft':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  Color _getQualityColor(String quality) {
    switch (quality.toLowerCase()) {
      case 'excellent':
        return Colors.green;
      case 'good':
        return Colors.blue;
      case 'acceptable':
        return Colors.orange;
      case 'poor':
      case 'unacceptable':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Color _getResultColor(String result) {
    switch (result.toLowerCase()) {
      case 'pass':
        return Colors.green;
      case 'conditional':
        return Colors.orange;
      case 'fail':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    final user = authState.valueOrNull?.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Labor Inspections'),
        backgroundColor: Theme.of(context).colorScheme.inversePrimary,
        actions: [
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
        child: Column(
          children: [
            // Filter chips
            if (_filters.isNotEmpty)
              Container(
                padding: const EdgeInsets.all(8),
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: _filters.entries.map((entry) {
                      return Padding(
                        padding: const EdgeInsets.only(right: 8),
                        child: Chip(
                          label: Text(_getFilterLabel(entry.key, entry.value)),
                          onDeleted: () {
                            setState(() {
                              _filters.remove(entry.key);
                            });
                            _loadData(refresh: true);
                          },
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ),
            
            // Inspections list
            Expanded(
              child: _isLoading && _inspections.isEmpty
                  ? const LoadingWidget()
                  : _inspections.isEmpty
                      ? const EmptyStateWidget(
                          message: 'No inspections found',
                          icon: Icons.search_off,
                        )
                      : ListView.builder(
                          controller: _scrollController,
                          padding: const EdgeInsets.all(16),
                          itemCount: _inspections.length + (_hasMore ? 1 : 0),
                          itemBuilder: (context, index) {
                            if (index == _inspections.length) {
                              return const Padding(
                                padding: EdgeInsets.all(16),
                                child: Center(child: CircularProgressIndicator()),
                              );
                            }

                            final inspection = _inspections[index];
                            return _buildInspectionCard(inspection);
                          },
                        ),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          // TODO: Navigate to create inspection screen
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Create inspection feature coming soon')),
          );
        },
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildInspectionCard(Map<String, dynamic> inspection) {
    final contract = inspection['contract'] ?? {};
    final inspector = inspection['inspector'] ?? {};
    
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      child: InkWell(
        onTap: () {
          // TODO: Navigate to inspection details
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Inspection details feature coming soon')),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header with inspection number and status
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          inspection['inspection_number'] ?? 'Unknown',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          inspection['inspection_date'] ?? 'No date',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: _getStatusColor(inspection['status'] ?? 'draft'),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      inspection['status']?.toString().toUpperCase() ?? 'DRAFT',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
              
              const SizedBox(height: 12),
              
              // Contract and artisan info
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Contract: ${contract['contract_number'] ?? 'Unknown'}',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Artisan: ${contract['artisan_name'] ?? 'Unknown'}',
                          style: TextStyle(
                            fontSize: 14,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              
              const SizedBox(height: 12),
              
              // Inspection details row
              Row(
                children: [
                  // Type badge
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade100,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      inspection['inspection_type']?.toString().toUpperCase() ?? 'UNKNOWN',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: Colors.blue.shade800,
                      ),
                    ),
                  ),
                  
                  const SizedBox(width: 8),
                  
                  // Completion percentage
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${inspection['completion_percentage'] ?? 0}% Complete',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        LinearProgressIndicator(
                          value: (inspection['completion_percentage'] ?? 0) / 100,
                          backgroundColor: Colors.grey.shade300,
                          valueColor: AlwaysStoppedAnimation<Color>(Colors.blue),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              
              const SizedBox(height: 8),
              
              // Quality and result badges
              Row(
                children: [
                  if (inspection['work_quality'] != null) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: _getQualityColor(inspection['work_quality']).withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        'Quality: ${inspection['work_quality']}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: _getQualityColor(inspection['work_quality']),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                  ],
                  
                  if (inspection['result'] != null) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: _getResultColor(inspection['result']).withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        'Result: ${inspection['result']}',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: _getResultColor(inspection['result']),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
              
              // Inspector info
              if (inspector['name'] != null) ...[
                const SizedBox(height: 8),
                Text(
                  'Inspector: ${inspector['name']}',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  String _getFilterLabel(String key, dynamic value) {
    switch (key) {
      case 'project_id':
        final project = _projects.firstWhere((p) => p['id'].toString() == value.toString(), orElse: () => null);
        return 'Project: ${project?['project_name'] ?? value}';
      case 'contract_id':
        final contract = _contracts.firstWhere((c) => c['id'].toString() == value.toString(), orElse: () => null);
        return 'Contract: ${contract?['contract_number'] ?? value}';
      case 'status':
        return 'Status: ${value.toString().toUpperCase()}';
      case 'inspection_type':
        return 'Type: ${value.toString().toUpperCase()}';
      case 'start_date':
        return 'From: $value';
      case 'end_date':
        return 'To: $value';
      default:
        return '$key: $value';
    }
  }
}
