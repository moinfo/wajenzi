import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

/// The web Payroll page: the "Staff Payroll" table for the current month and
/// the "Create Payroll" button (shown only with the "Add Payroll" permission,
/// and not once the month's payroll has been created).
final _staffPayrollProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/payroll/staff-payroll');
  return {
    'items': (response.data['data'] as List? ?? const [])
        .cast<Map<String, dynamic>>(),
    'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
  };
});

class StaffPayrollScreen extends ConsumerStatefulWidget {
  const StaffPayrollScreen({super.key});

  @override
  ConsumerState<StaffPayrollScreen> createState() => _StaffPayrollScreenState();
}

class _StaffPayrollScreenState extends ConsumerState<StaffPayrollScreen> {
  bool _creating = false;

  static const _columns = <(String, String)>[
    ('basicSalary', 'Basic Salary'),
    ('allowance', 'Allowance'),
    ('grossPay', 'Gross Pay'),
    ('employerPension', 'Employer Pension'),
    ('employeePension', 'Employee Pension'),
    ('employerHealth', 'Employer Health'),
    ('employeeHealth', 'Employee Health'),
    ('taxable', 'Taxable'),
    ('paye', 'PAYE'),
    ('wpf', 'WCF'),
    ('sdl', 'SDL'),
    ('heslb', 'HESLB'),
    ('advanceSalary', 'Advance Salary'),
    ('totalLoan', 'Total Loan'),
    ('loanDeduction', 'Loan Deduction'),
    ('loanBalance', 'Loan Balance'),
  ];

  String _money(dynamic v) {
    final n = double.tryParse('${v ?? ''}'.replaceAll(',', ''));
    return n == null ? '${v ?? '-'}' : NumberFormat('#,##0.00', 'en').format(n);
  }

  Future<void> _create(bool isSwahili) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Unda Mishahara' : 'Create Payroll'),
        content: Text(
          isSwahili
              ? 'Unda mishahara ya mwezi huu kwa wafanyakazi wote?'
              : 'Create the payroll for this month for all staff?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Unda' : 'Create'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _creating = true);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref.read(apiClientProvider).post('/payroll/staff-payroll');
      ref.invalidate(_staffPayrollProvider);
      messenger.showSnackBar(
        SnackBar(
          content: Text(
            isSwahili ? 'Mishahara imeundwa' : 'Payroll created for this month',
          ),
        ),
      );
    } on DioException catch (e) {
      final message = e.response?.data is Map
          ? (e.response!.data['message']?.toString() ?? '')
          : '';
      messenger.showSnackBar(
        SnackBar(
          content: Text(message.isEmpty ? 'Changes are not saved' : message),
          backgroundColor: AppColors.error,
        ),
      );
    } finally {
      if (mounted) setState(() => _creating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(_staffPayrollProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    final meta = async.valueOrNull?['meta'] as Map<String, dynamic>? ?? const {};
    final canCreate = meta['can_create'] == true;
    final alreadyCreated = meta['already_created'] == true;

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Mishahara ya Wafanyakazi' : 'Staff Payroll'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_staffPayrollProvider.future),
        child: async.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (_, __) => ListView(
            children: [
              const SizedBox(height: 120),
              Center(
                child: TextButton.icon(
                  onPressed: () => ref.invalidate(_staffPayrollProvider),
                  icon: const Icon(Icons.refresh),
                  label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                ),
              ),
            ],
          ),
          data: (payload) {
            final items = (payload['items'] as List).cast<Map<String, dynamic>>();
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                Text(
                  '${meta['month'] ?? ''} · ${items.length} ${isSwahili ? 'wafanyakazi' : 'staff'}',
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 12),
                if (alreadyCreated)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Chip(
                      avatar: const Icon(Icons.schedule, size: 18),
                      label: Text(
                        isSwahili
                            ? 'Mishahara ya mwezi huu imeshaundwa'
                            : 'Already Created Payroll This Month',
                      ),
                    ),
                  )
                else if (canCreate)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: FilledButton.icon(
                      onPressed: _creating ? null : () => _create(isSwahili),
                      icon: _creating
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.add),
                      label: Text(isSwahili ? 'Unda Mishahara' : 'Create Payroll'),
                    ),
                  ),
                ...items.map(
                  (row) => Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: ExpansionTile(
                      title: Text(
                        '${row['name'] ?? ''}',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      subtitle: Text('NET ${_money(row['net'])}'),
                      childrenPadding:
                          const EdgeInsets.fromLTRB(16, 0, 16, 12),
                      children: [
                        for (final (key, label) in _columns)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 3),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(label),
                                Text(_money(row[key])),
                              ],
                            ),
                          ),
                      ],
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
}
