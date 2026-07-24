import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';

/// Paid-payment summary report (GET labor/payments/report):
/// shows totals grouped by project and by artisan over a date/project window.
class LaborPaymentReportScreen extends ConsumerStatefulWidget {
  const LaborPaymentReportScreen({super.key});

  @override
  ConsumerState<LaborPaymentReportScreen> createState() =>
      _LaborPaymentReportScreenState();
}

class _LaborPaymentReportScreenState
    extends ConsumerState<LaborPaymentReportScreen> {
  Map<String, dynamic>? _report;
  bool _isLoading = true;
  String? _error;

  DateTime? _startDate;
  DateTime? _endDate;

  final NumberFormat _money = NumberFormat.currency(
    symbol: 'TZS ',
    decimalDigits: 0,
  );
  final DateFormat _dateFmt = DateFormat('yyyy-MM-dd');

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _startDate = DateTime(now.year, 1, 1);
    _endDate = now;
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final api = ref.read(apiClientProvider);
      final params = <String, dynamic>{};
      if (_startDate != null) params['start_date'] = _dateFmt.format(_startDate!);
      if (_endDate != null) params['end_date'] = _dateFmt.format(_endDate!);

      final response = await api.get(
        '/labor/payments/report',
        queryParameters: params,
      );
      if (response.statusCode == 200 && response.data['success'] == true) {
        setState(() {
          _report = Map<String, dynamic>.from(response.data['data']);
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.data['message']?.toString() ?? 'Failed to load report';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Error loading report: $e';
        _isLoading = false;
      });
    }
  }

  String _formatMoney(dynamic amount) {
    final value = amount is num
        ? amount.toDouble()
        : double.tryParse(amount?.toString() ?? '') ?? 0;
    return _money.format(value);
  }

  Future<void> _pickDate({required bool isStart}) async {
    final initial = isStart
        ? (_startDate ?? DateTime.now())
        : (_endDate ?? DateTime.now());
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      setState(() {
        if (isStart) {
          _startDate = picked;
        } else {
          _endDate = picked;
        }
      });
      _loadReport();
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ripoti ya Malipo' : 'Payment Report'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _isLoading ? null : _loadReport,
          ),
        ],
      ),
      body: Column(
        children: [
          _buildDateFilters(theme, isSwahili),
          const Divider(height: 1),
          Expanded(
            child: _isLoading
                ? const LoadingWidget(message: 'Loading report...')
                : _error != null
                    ? _buildError(isSwahili)
                    : _buildReport(theme, isSwahili),
          ),
        ],
      ),
    );
  }

  Widget _buildDateFilters(ThemeData theme, bool isSwahili) {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Row(
        children: [
          Expanded(
            child: OutlinedButton.icon(
              icon: const Icon(Icons.calendar_today, size: 16),
              label: Text(
                _startDate != null
                    ? _dateFmt.format(_startDate!)
                    : (isSwahili ? 'Tarehe ya kuanza' : 'Start date'),
                overflow: TextOverflow.ellipsis,
              ),
              onPressed: () => _pickDate(isStart: true),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: OutlinedButton.icon(
              icon: const Icon(Icons.calendar_today, size: 16),
              label: Text(
                _endDate != null
                    ? _dateFmt.format(_endDate!)
                    : (isSwahili ? 'Tarehe ya mwisho' : 'End date'),
                overflow: TextOverflow.ellipsis,
              ),
              onPressed: () => _pickDate(isStart: false),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildError(bool isSwahili) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 48, color: Colors.red),
            const SizedBox(height: 12),
            Text(_error ?? '', textAlign: TextAlign.center),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _loadReport,
              child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReport(ThemeData theme, bool isSwahili) {
    final r = _report!;
    final byProject = (r['by_project'] as List?) ?? [];
    final byArtisan = (r['by_artisan'] as List?) ?? [];
    final total = r['total_amount'];

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          color: theme.colorScheme.primaryContainer,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  isSwahili ? 'Jumla ya Malipo' : 'Total Paid',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: theme.colorScheme.onPrimaryContainer,
                  ),
                ),
                Text(
                  _formatMoney(total),
                  style: theme.textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: theme.colorScheme.onPrimaryContainer,
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        _groupSection(
          theme,
          isSwahili ? 'Kwa Mradi' : 'By Project',
          byProject,
          nameKey: 'project_name',
          emptyLabel: isSwahili ? 'Hakuna mradi' : 'Unassigned project',
        ),
        const SizedBox(height: 16),
        _groupSection(
          theme,
          isSwahili ? 'Kwa Fundi' : 'By Artisan',
          byArtisan,
          nameKey: 'artisan_name',
          emptyLabel: isSwahili ? 'Hakuna fundi' : 'Unassigned artisan',
        ),
      ],
    );
  }

  Widget _groupSection(
    ThemeData theme,
    String title,
    List<dynamic> rows, {
    required String nameKey,
    required String emptyLabel,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            if (rows.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  '—',
                  style: TextStyle(color: Colors.grey[600]),
                ),
              )
            else
              ...rows.map((row) {
                final m = row as Map<String, dynamic>;
                final name = (m[nameKey] ?? '').toString().isEmpty
                    ? emptyLabel
                    : m[nameKey].toString();
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              name,
                              style: const TextStyle(
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            Text(
                              '${m['count'] ?? 0} payment(s)',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      ),
                      Text(
                        _formatMoney(m['total']),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }
}
