import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../../widgets/common/error_widget.dart';
import '../../widgets/common/empty_state_widget.dart';
import '../../widgets/common/filter_bottom_sheet.dart';

class LaborPaymentsScreen extends ConsumerStatefulWidget {
  const LaborPaymentsScreen({super.key});

  @override
  ConsumerState<LaborPaymentsScreen> createState() =>
      _LaborPaymentsScreenState();
}

class _LaborPaymentsScreenState extends ConsumerState<LaborPaymentsScreen> {
  final ScrollController _scrollController = ScrollController();
  List<dynamic> _payments = [];
  Map<String, dynamic> _filters = {};
  Map<String, dynamic> _referenceData = {};
  bool _isLoading = false;
  bool _hasMore = true;
  int _currentPage = 1;

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
        _payments.clear();
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

      // Load payments and reference data in parallel
      final paymentsResponse = await api.get(
        '/labor/payments',
        queryParameters: {
          ..._filters,
          'per_page': '20',
          'page': _currentPage.toString(),
        },
      );

      final referenceDataResponse = await api.get(
        '/labor/payments/reference-data',
      );

      if (paymentsResponse.statusCode == 200 &&
          referenceDataResponse.statusCode == 200) {
        final paymentsData = paymentsResponse.data['data'];
        final referenceData = referenceDataResponse.data['data'];

        setState(() {
          if (refresh) {
            _payments = paymentsData['data'] ?? [];
          } else {
            _payments.addAll(paymentsData['data'] ?? []);
          }
          _referenceData = referenceData;
          _hasMore =
              (paymentsData['meta']['current_page'] ?? 1) <
              (paymentsData['meta']['last_page'] ?? 1);
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });

      String errorMessage = 'Error loading payments';

      // Check for authentication errors
      if (e.toString().contains('401') ||
          e.toString().contains('Unauthorized')) {
        errorMessage = 'Authentication required. Please login again.';
      } else if (e.toString().contains('403') ||
          e.toString().contains('Forbidden')) {
        errorMessage =
            'Permission denied. You may not have access to payments.';
      } else if (e.toString().contains('404')) {
        errorMessage =
            'Payments endpoint not found. Please check API configuration.';
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
        '/labor/payments',
        queryParameters: {
          ..._filters,
          'per_page': '20',
          'page': _currentPage.toString(),
        },
      );

      if (response.statusCode == 200) {
        final data = response.data['data'];
        setState(() {
          _payments.addAll(data['data'] ?? []);
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
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading more payments: $e')),
        );
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

    // Add date range filters
    options['start_date'] = {'label': 'Start Date', 'type': 'date'};

    options['end_date'] = {'label': 'End Date', 'type': 'date'};

    if (_referenceData['contracts'] != null) {
      options['contract_id'] = {
        'label': 'Contract',
        'type': 'select',
        'options': (_referenceData['contracts'] as List)
            .map(
              (contract) => {
                'value': contract['id'],
                'label':
                    '${contract['contract_number']} - ${contract['artisan_name']}',
              },
            )
            .toList(),
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
        title: 'Filter Payments',
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
      case 'approved':
        return Colors.blue;
      case 'due':
        return Colors.orange;
      case 'pending':
        return Colors.grey;
      case 'held':
        return Colors.red;
      default:
        return Colors.grey;
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
        title: Text(isSwahili ? 'Malipo ya Labor' : 'Labor Payments'),
        actions: [
          IconButton(
            icon: const Icon(Icons.dashboard_rounded),
            tooltip: isSwahili ? 'Dashibodi' : 'Dashboard',
            onPressed: () => context.go('/labor-dashboard'),
          ),
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
        child: _isLoading && _payments.isEmpty
            ? const LoadingWidget(message: 'Loading payments...')
            : _payments.isEmpty
            ? const EmptyStateWidget(
                message: 'No payments found',
                icon: Icons.payment,
              )
            : ListView.builder(
                controller: _scrollController,
                padding: const EdgeInsets.all(16),
                itemCount: _payments.length + (_hasMore ? 1 : 0),
                itemBuilder: (context, index) {
                  if (index == _payments.length) {
                    return const Padding(
                      padding: EdgeInsets.all(16),
                      child: Center(child: CircularProgressIndicator()),
                    );
                  }

                  final payment = _payments[index];
                  return PaymentCard(
                    payment: payment,
                    onTap: () {
                      // Navigate to payment details
                    },
                  );
                },
              ),
      ),
    );
  }
}

class PaymentCard extends StatelessWidget {
  final dynamic payment;
  final VoidCallback onTap;

  const PaymentCard({super.key, required this.payment, required this.onTap});

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'paid':
        return Colors.green;
      case 'approved':
        return Colors.blue;
      case 'due':
        return Colors.orange;
      case 'pending':
        return Colors.grey;
      case 'held':
        return Colors.red;
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
                          payment['phase_name'] ?? 'Unknown Phase',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Phase ${payment['phase_number'] ?? ''}',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: _getStatusColor(payment['status'] ?? ''),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      payment['status'] ?? '',
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
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Amount',
                        style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                      ),
                      Text(
                        'TZS ${payment['amount']?.toString() ?? '0'}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        'Due Date',
                        style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                      ),
                      Text(
                        payment['due_date'] ?? 'Not set',
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              if (payment['description'] != null &&
                  payment['description'].toString().isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  payment['description'],
                  style: TextStyle(fontSize: 12, color: Colors.grey[700]),
                ),
              ],
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.person_outline, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 4),
                  Text(
                    payment['contract']?['artisan_name'] ?? 'Unknown',
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
                  const Spacer(),
                  if (payment['paid_at'] != null) ...[
                    Icon(Icons.check_circle, size: 16, color: Colors.green),
                    const SizedBox(width: 4),
                    Text(
                      'Paid on ${payment['paid_at']}',
                      style: TextStyle(fontSize: 12, color: Colors.green),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
