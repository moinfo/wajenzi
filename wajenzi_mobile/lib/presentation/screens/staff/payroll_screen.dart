import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _payslipsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final payslipsResponse = await api.get('/payroll/payslips');
  final loanResponse = await api.get('/payroll/loan-balance');

  return {
    'items': (payslipsResponse.data['data'] as List? ?? const [])
        .cast<Map<String, dynamic>>(),
    'meta': payslipsResponse.data['meta'] as Map<String, dynamic>? ?? const {},
    'loan': loanResponse.data['data'] as Map<String, dynamic>? ?? const {},
  };
});

final _payslipDetailProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/payroll/payslips/$id');
  return response.data['data'] as Map<String, dynamic>? ?? const {};
});

class PayrollScreen extends ConsumerWidget {
  const PayrollScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final payrollAsync = ref.watch(_payslipsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Slip za Mishahara' : 'Salary Slips'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_payslipsProvider.future),
        child: payrollAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _PayrollErrorView(
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_payslipsProvider),
          ),
          data: (payload) {
            final payslips =
                (payload['items'] as List).cast<Map<String, dynamic>>();
            final meta = payload['meta'] as Map<String, dynamic>? ?? const {};
            final loan = payload['loan'] as Map<String, dynamic>? ?? const {};

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _LoanSummaryCard(
                  loan: loan,
                  isSwahili: isSwahili,
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili
                      ? 'Mishahara iliyopatikana: ${meta['total'] ?? payslips.length}'
                      : 'Available payslips: ${meta['total'] ?? payslips.length}',
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 12),
                if (payslips.isEmpty)
                  _PayrollEmptyView(
                    label: isSwahili
                        ? 'Hakuna mishahara iliyopatikana'
                        : 'No payslips found',
                  )
                else
                  ...payslips.map(
                    (payslip) => _PayslipCard(
                      payslip: payslip,
                      isSwahili: isSwahili,
                      onTap: () => _showPayslipDetailSheet(
                        context,
                        ref,
                        _toInt(payslip['id']),
                        isSwahili,
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

class _LoanSummaryCard extends StatelessWidget {
  final Map<String, dynamic> loan;
  final bool isSwahili;

  const _LoanSummaryCard({
    required this.loan,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final hasActiveLoan = loan['has_active_loan'] == true;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: AppColors.primary.withValues(alpha: 0.15),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili ? 'Muhtasari wa Mkopo' : 'Loan Summary',
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              _MetricPill(
                label: isSwahili ? 'Jumla ya Mkopo' : 'Total Loan',
                value: 'TZS ${_formatMoney(loan['total_loan'])}',
              ),
              _MetricPill(
                label: isSwahili ? 'Jumla Iliyolipwa' : 'Total Paid',
                value: 'TZS ${_formatMoney(loan['total_paid'])}',
              ),
              _MetricPill(
                label: isSwahili ? 'Salio' : 'Balance',
                value: 'TZS ${_formatMoney(loan['balance'])}',
              ),
              _MetricPill(
                label: isSwahili ? 'Makato ya Mwezi' : 'Monthly Deduction',
                value: 'TZS ${_formatMoney(loan['monthly_deduction'])}',
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            hasActiveLoan
                ? (isSwahili ? 'Mkopo unaendelea' : 'Active loan in progress')
                : (isSwahili ? 'Hakuna mkopo unaoendelea' : 'No active loan'),
            style: TextStyle(
              color: hasActiveLoan ? AppColors.warning : AppColors.success,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricPill extends StatelessWidget {
  final String label;
  final String value;

  const _MetricPill({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.65),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _PayslipCard extends StatelessWidget {
  final Map<String, dynamic> payslip;
  final bool isSwahili;
  final VoidCallback onTap;

  const _PayslipCard({
    required this.payslip,
    required this.isSwahili,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.all(16),
        leading: CircleAvatar(
          backgroundColor: AppColors.success.withValues(alpha: 0.12),
          child: const Icon(Icons.payments_outlined, color: AppColors.success),
        ),
        title: Text(
          payslip['payroll_name'] as String? ?? '-',
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(_formatDate(payslip['submitted_date'] as String?)),
              Text(
                '${isSwahili ? 'Mshahara halisi' : 'Net salary'}: TZS ${_formatMoney(payslip['net_salary'])}',
              ),
            ],
          ),
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'TZS ${_formatMoney(payslip['net_salary'])}',
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                color: AppColors.success,
              ),
            ),
            const SizedBox(height: 8),
            const Icon(
              Icons.arrow_forward_ios_rounded,
              size: 14,
              color: AppColors.textHint,
            ),
          ],
        ),
      ),
    );
  }
}

void _showPayslipDetailSheet(
  BuildContext context,
  WidgetRef ref,
  int id,
  bool isSwahili,
) {
  if (id <= 0) return;

  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final payslipAsync = ref.watch(_payslipDetailProvider(id));
        return SafeArea(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.72,
            child: payslipAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _PayrollErrorView(
                isSwahili: isSwahili,
                onRetry: () => ref.invalidate(_payslipDetailProvider(id)),
              ),
              data: (detail) {
                final payslip =
                    detail['payslip'] as Map<String, dynamic>? ?? const {};
                final allowances =
                    (detail['allowances'] as List? ?? const [])
                        .cast<Map<String, dynamic>>();
                final deductions =
                    (detail['deductions'] as List? ?? const [])
                        .cast<Map<String, dynamic>>();

                return ListView(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                  children: [
                    Text(
                      payslip['payroll_name'] as String? ?? 'Payslip',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _PayrollDetailRow(
                      isSwahili ? 'Tarehe ya Kuwasilisha' : 'Submitted',
                      _formatDate(payslip['submitted_date'] as String?),
                    ),
                    _PayrollDetailRow(
                      isSwahili ? 'Kipindi' : 'Period',
                      '${_formatDate(payslip['start_date'] as String?)} - ${_formatDate(payslip['end_date'] as String?)}',
                    ),
                    _PayrollDetailRow(
                      isSwahili ? 'Mshahara wa Msingi' : 'Basic Salary',
                      'TZS ${_formatMoney(payslip['basicSalary'] ?? payslip['basic_salary'])}',
                    ),
                    _PayrollDetailRow(
                      isSwahili ? 'Posho' : 'Allowance',
                      'TZS ${_formatMoney(payslip['allowance'])}',
                    ),
                    _PayrollDetailRow(
                      isSwahili ? 'Makato' : 'Deduction',
                      'TZS ${_formatMoney(payslip['deduction'])}',
                    ),
                    _PayrollDetailRow(
                      'PAYE',
                      'TZS ${_formatMoney(payslip['paye'])}',
                    ),
                    _PayrollDetailRow(
                      isSwahili ? 'Makato ya Mkopo' : 'Loan Deduction',
                      'TZS ${_formatMoney(payslip['loanDeduction'] ?? payslip['loan_deduction'])}',
                    ),
                    _PayrollDetailRow(
                      isSwahili ? 'Mshahara Halisi' : 'Net Salary',
                      'TZS ${_formatMoney(payslip['netSalary'] ?? payslip['net_salary'])}',
                    ),
                    if (allowances.isNotEmpty) ...[
                      const SizedBox(height: 18),
                      Text(
                        isSwahili ? 'Posho' : 'Allowances',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 10),
                      ...allowances.map(
                        (item) => _PayrollDetailRow(
                          item['name'] as String? ?? '-',
                          'TZS ${_formatMoney(item['amount'])}',
                        ),
                      ),
                    ],
                    if (deductions.isNotEmpty) ...[
                      const SizedBox(height: 18),
                      Text(
                        isSwahili ? 'Makato' : 'Deductions',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 10),
                      ...deductions.map(
                        (item) => _PayrollDetailRow(
                          item['name'] as String? ?? '-',
                          [
                            'Employee: TZS ${_formatMoney(item['employee_contribution'])}',
                            'Employer: TZS ${_formatMoney(item['employer_contribution'])}',
                          ].join(' - '),
                        ),
                      ),
                    ],
                  ],
                );
              },
            ),
          ),
        );
      },
    ),
  );
}

class _PayrollDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _PayrollDetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? 'N/A' : value,
              style: const TextStyle(fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }
}

class _PayrollEmptyView extends StatelessWidget {
  final String label;

  const _PayrollEmptyView({
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 24),
      child: Column(
        children: [
          Icon(
            Icons.receipt_long_outlined,
            size: 56,
            color: Colors.grey[300],
          ),
          const SizedBox(height: 12),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

class _PayrollErrorView extends StatelessWidget {
  final bool isSwahili;
  final VoidCallback onRetry;

  const _PayrollErrorView({
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 64, color: AppColors.error),
            const SizedBox(height: 12),
            Text(
              isSwahili
                  ? 'Imeshindikana kupakia mishahara'
                  : 'Failed to load payroll',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
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

String _formatDate(String? date) {
  if (date == null || date.isEmpty) return 'N/A';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
  } catch (_) {
    return date;
  }
}

String _formatMoney(dynamic value) {
  final amount = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
  return NumberFormat('#,##0.00', 'en').format(amount);
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}
