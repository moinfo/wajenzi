import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _salarySlipsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/salary-slips');
    final data = response.data is Map<String, dynamic>
        ? response.data as Map<String, dynamic>
        : const <String, dynamic>{};
    final payload = data['data'] is Map
        ? Map<String, dynamic>.from(data['data'] as Map)
        : const <String, dynamic>{};
    return {
      ...payload,
      'mode': 'legacy',
      'unavailable_on_live': false,
    };
  } on DioException catch (error) {
    if ((error.response?.statusCode ?? 0) == 404) {
      return const {
        'mode': 'unavailable',
        'items': <Map<String, dynamic>>[],
        'unavailable_on_live': true,
      };
    }
    rethrow;
  }
});

final _selectedStaffProvider = StateProvider.autoDispose<int?>((ref) => null);
final _selectedMonthProvider = StateProvider.autoDispose<int?>((ref) => null);
final _selectedYearProvider = StateProvider.autoDispose<int?>((ref) => null);

final _payslipProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>?, ({int staffId, int month, int year})>((
      ref,
      params,
    ) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get(
          '/salary-slips/payslip',
          queryParameters: {
            'staff_id': params.staffId,
            'month': params.month,
            'year': params.year,
          },
        );
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        if (data['success'] == true) {
          return data['data'] is Map
              ? Map<String, dynamic>.from(data['data'] as Map)
              : null;
        }
        return null;
      } on DioException catch (error) {
        final statusCode = error.response?.statusCode ?? 0;
        if (statusCode == 404 || statusCode >= 500) {
          return null;
        }
        rethrow;
      } catch (e) {
        return null;
      }
    });

class SalarySlipsScreen extends ConsumerStatefulWidget {
  const SalarySlipsScreen({super.key});

  @override
  ConsumerState<SalarySlipsScreen> createState() => _SalarySlipsScreenState();
}

class _SalarySlipsScreenState extends ConsumerState<SalarySlipsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final data = ref.read(_salarySlipsProvider).valueOrNull;
      if (data != null) {
        final months = data['months'] as List? ?? [];
        final years = data['years'] as List? ?? [];
        final staffs = data['staffs'] as List? ?? [];
        if (staffs.isNotEmpty) {
          ref.read(_selectedStaffProvider.notifier).state =
              _toInt(staffs.first['id']);
        }
        if (months.isNotEmpty) {
          ref.read(_selectedMonthProvider.notifier).state =
              _toInt(months.first['id']);
        }
        if (years.isNotEmpty) {
          ref.read(_selectedYearProvider.notifier).state =
              _toInt(years.first['id']);
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final dataAsync = ref.watch(_salarySlipsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final selectedStaff = ref.watch(_selectedStaffProvider);
    final selectedMonth = ref.watch(_selectedMonthProvider);
    final selectedYear = ref.watch(_selectedYearProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Salary Slips' : 'Salary Slips'),
      ),
      body: dataAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _SalarySlipsErrorView(
          message: vatErrorMessage(error, isSwahili: isSwahili),
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_salarySlipsProvider),
        ),
        data: (data) {
          if (data['unavailable_on_live'] == true) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.receipt_long_outlined, size: 64, color: Colors.grey[400]),
                    const SizedBox(height: 16),
                    Text(
                      isSwahili
                          ? 'Salary Slips haipatikani kwenye live API kwa sasa.'
                          : 'Salary Slips is not available on the live API right now.',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: Colors.grey[700],
                      ),
                    ),
                  ],
                ),
              ),
            );
          }

          final staffs = _toMaps(data['staffs']);
          final months = _toMaps(data['months']);
          final years = _toMaps(data['years']);

          return CustomScrollView(
            slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Chagua Muajiriwa' : 'Select Employee',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF0F1923)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: isDarkMode
                                ? Colors.white24
                                : Colors.grey[300]!,
                          ),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int?>(
                            isExpanded: true,
                            value: selectedStaff,
                            hint: Text(
                              isSwahili
                                  ? 'Chagua muajiriwa...'
                                  : 'Select employee...',
                              style: TextStyle(
                                color: isDarkMode
                                    ? Colors.white54
                                    : AppColors.textHint,
                              ),
                            ),
                            dropdownColor: isDarkMode
                                ? const Color(0xFF1A1A2E)
                                : Colors.white,
                            items: staffs
                                .map(
                                  (item) => DropdownMenuItem<int?>(
                                    value: _toInt(item['id']),
                                    child: Text(
                                      item['name']?.toString() ?? '-',
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        color: isDarkMode
                                            ? Colors.white
                                            : AppColors.textPrimary,
                                      ),
                                    ),
                                  ),
                                )
                                .toList(),
                            onChanged: (value) =>
                                ref
                                        .read(_selectedStaffProvider.notifier)
                                        .state =
                                    value,
                          ),
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
                                  isSwahili ? 'Mwezi' : 'Month',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: isDarkMode
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                  ),
                                  decoration: BoxDecoration(
                                    color: isDarkMode
                                        ? const Color(0xFF0F1923)
                                        : Colors.grey[100],
                                    borderRadius: BorderRadius.circular(10),
                                    border: Border.all(
                                      color: isDarkMode
                                          ? Colors.white24
                                          : Colors.grey[300]!,
                                    ),
                                  ),
                                  child: DropdownButtonHideUnderline(
                                    child: DropdownButton<int?>(
                                      isExpanded: true,
                                      value: selectedMonth,
                                      dropdownColor: isDarkMode
                                          ? const Color(0xFF1A1A2E)
                                          : Colors.white,
                                      items: months
                                          .map(
                                            (item) => DropdownMenuItem<int?>(
                                              value: item['id'] as int,
                                              child: Text(
                                                item['name']?.toString() ?? '-',
                                                style: TextStyle(
                                                  color: isDarkMode
                                                      ? Colors.white
                                                      : AppColors.textPrimary,
                                                ),
                                              ),
                                            ),
                                          )
                                          .toList(),
                                      onChanged: (value) =>
                                          ref
                                                  .read(
                                                    _selectedMonthProvider
                                                        .notifier,
                                                  )
                                                  .state =
                                              value,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  isSwahili ? 'Mwaka' : 'Year',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                    color: isDarkMode
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                  ),
                                  decoration: BoxDecoration(
                                    color: isDarkMode
                                        ? const Color(0xFF0F1923)
                                        : Colors.grey[100],
                                    borderRadius: BorderRadius.circular(10),
                                    border: Border.all(
                                      color: isDarkMode
                                          ? Colors.white24
                                          : Colors.grey[300]!,
                                    ),
                                  ),
                                  child: DropdownButtonHideUnderline(
                                    child: DropdownButton<int?>(
                                      isExpanded: true,
                                      value: selectedYear,
                                      dropdownColor: isDarkMode
                                          ? const Color(0xFF1A1A2E)
                                          : Colors.white,
                                      items: years
                                          .map(
                                            (item) => DropdownMenuItem<int?>(
                                              value: item['id'] as int,
                                              child: Text(
                                                item['name']?.toString() ?? '-',
                                                style: TextStyle(
                                                  color: isDarkMode
                                                      ? Colors.white
                                                      : AppColors.textPrimary,
                                                ),
                                              ),
                                            ),
                                          )
                                          .toList(),
                                      onChanged: (value) =>
                                          ref
                                                  .read(
                                                    _selectedYearProvider
                                                        .notifier,
                                                  )
                                                  .state =
                                              value,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              SliverFillRemaining(
                child: _PayslipView(
                  staffId: selectedStaff,
                  month: selectedMonth,
                  year: selectedYear,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _PayslipView extends ConsumerWidget {
  final int? staffId;
  final int? month;
  final int? year;
  final bool isSwahili;
  final bool isDarkMode;

  const _PayslipView({
    required this.staffId,
    required this.month,
    required this.year,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (staffId == null || month == null || year == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.person_search, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              isSwahili
                  ? 'Chagua muajiriwa kuonyesha payslip'
                  : 'Select an employee to view payslip',
              style: TextStyle(fontSize: 16, color: Colors.grey[600]),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      );
    }

    final payslipAsync = ref.watch(
      _payslipProvider((staffId: staffId!, month: month!, year: year!)),
    );

    return payslipAsync.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (error, _) => Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text(
              isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 8),
            Text(
              vatErrorMessage(error, isSwahili: isSwahili),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
      data: (payslip) {
        if (payslip == null) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.warning_amber_rounded,
                  size: 64,
                  color: Colors.orange[400],
                ),
                const SizedBox(height: 16),
                Text(
                  isSwahili
                      ? 'Hakuna payroll iliyoidhinishwa kwa mwezi huu'
                      : 'No approved payroll for this month',
                  style: TextStyle(fontSize: 16, color: Colors.grey[600]),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          );
        }

        return _PayslipContent(
          payslip: payslip,
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
        );
      },
    );
  }
}

class _PayslipContent extends StatelessWidget {
  final Map<String, dynamic> payslip;
  final bool isSwahili;
  final bool isDarkMode;

  const _PayslipContent({
    required this.payslip,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final payroll = payslip['payroll'] as Map<String, dynamic>? ?? {};
    final employee = payslip['employee'] as Map<String, dynamic>? ?? {};
    final bank = payslip['bank'] as Map<String, dynamic>? ?? {};
    final allowances = _toMaps(payslip['allowances']);
    final deductions = _toMaps(payslip['deductions']);

    final netSalary = _toDouble(payslip['net_salary']);
    final grossSalary = _toDouble(payslip['gross_salary']);
    final basicSalary = _toDouble(payslip['basic_salary']);
    final totalDeductions = _toDouble(payslip['total_deductions']);

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      '${payroll['month_name'] ?? '-'} ${payroll['year'] ?? ''}',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode
                            ? Colors.white70
                            : AppColors.textSecondary,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        payroll['payroll_number']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: isDarkMode
                        ? Colors.white.withValues(alpha: 0.05)
                        : Colors.grey[50],
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    children: [
                      Text(
                        employee['name']?.toString() ?? '-',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'HRM/LE/PO-${employee['employee_number'] ?? '-'}',
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        employee['designation']?.toString() ?? '-',
                        style: TextStyle(
                          fontSize: 12,
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _InfoCard(
                        label: isSwahili ? 'Benki' : 'Bank',
                        value: bank['name']?.toString() ?? '-',
                        isDarkMode: isDarkMode,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _InfoCard(
                        label: isSwahili ? 'Akaunti' : 'Account',
                        value: bank['account_number']?.toString() ?? '-',
                        isDarkMode: isDarkMode,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isSwahili ? 'Mapato' : 'Earnings',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: AppColors.success,
                  ),
                ),
                const SizedBox(height: 8),
                _SalaryRow(
                  label: isSwahili ? 'Mshahara Msingi' : 'Basic Salary',
                  amount: basicSalary,
                  isDarkMode: isDarkMode,
                ),
                ...allowances.map(
                  (allowance) => _SalaryRow(
                    label: allowance['name']?.toString() ?? '-',
                    amount: _toDouble(allowance['amount']),
                    isDarkMode: isDarkMode,
                  ),
                ),
                const Divider(height: 16),
                _SalaryRow(
                  label: isSwahili ? 'Jumla ya Mapato' : 'Gross Salary',
                  amount: grossSalary,
                  isDarkMode: isDarkMode,
                  isBold: true,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isSwahili ? 'Makato' : 'Deductions',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: AppColors.error,
                  ),
                ),
                const SizedBox(height: 8),
                ...deductions.map(
                  (deduction) => _SalaryRow(
                    label: deduction['name']?.toString() ?? '-',
                    amount: _toDouble(deduction['amount']),
                    isDarkMode: isDarkMode,
                  ),
                ),
                const Divider(height: 16),
                _SalaryRow(
                  label: isSwahili ? 'Jumla ya Makato' : 'Total Deductions',
                  amount: totalDeductions,
                  isDarkMode: isDarkMode,
                  isBold: true,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF2563EB), Color(0xFF22C55E)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.3),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              children: [
                Text(
                  isSwahili ? 'MSHIHIRI WA MKATO' : 'NET SALARY',
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Colors.white70,
                    letterSpacing: 1.5,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'TZS ${NumberFormat('#,##0.00').format(netSalary)}',
                  style: const TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 100),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _InfoCard({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode
            ? Colors.white.withValues(alpha: 0.05)
            : Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
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
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

class _SalaryRow extends StatelessWidget {
  final String label;
  final double amount;
  final bool isDarkMode;
  final bool isBold;

  const _SalaryRow({
    required this.label,
    required this.amount,
    required this.isDarkMode,
    this.isBold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: isBold ? 14 : 13,
              fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          Text(
            'TZS ${NumberFormat('#,##0.00').format(amount)}',
            style: TextStyle(
              fontSize: isBold ? 14 : 13,
              fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
              fontFamily: 'monospace',
            ),
          ),
        ],
      ),
    );
  }
}

class _SalarySlipsErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SalarySlipsErrorView({
    required this.message,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ],
      ),
    );
  }
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

double _toDouble(dynamic value) {
  if (value is double) return value;
  if (value is int) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

List<Map<String, dynamic>> _buildMonthsFromPayslips(
  List<Map<String, dynamic>> items,
) {
  final seen = <int>{};
  final result = <Map<String, dynamic>>[];
  for (final item in items) {
    final month = _toInt(item['month']);
    if (month <= 0 || seen.contains(month)) continue;
    seen.add(month);
    result.add({
      'id': month,
      'name': DateFormat('MMMM').format(DateTime(2000, month)),
    });
  }
  return result;
}

List<Map<String, dynamic>> _buildYearsFromPayslips(
  List<Map<String, dynamic>> items,
) {
  final seen = <int>{};
  final result = <Map<String, dynamic>>[];
  for (final item in items) {
    final year = _toInt(item['year']);
    if (year <= 0 || seen.contains(year)) continue;
    seen.add(year);
    result.add({'id': year, 'name': year.toString()});
  }
  return result;
}

Map<String, dynamic> _normalizeLivePayslipDetail(Map<String, dynamic> detail) {
  final payslip = detail['payslip'] is Map
      ? Map<String, dynamic>.from(detail['payslip'] as Map)
      : const <String, dynamic>{};
  final allowances = _toMaps(detail['allowances']);
  final deductions = _toMaps(detail['deductions']).map((item) {
    return {
      ...item,
      'amount': _toDouble(item['employee_contribution']),
    };
  }).toList();

  return {
    'payroll': {
      'month_name': payslip['payroll_name']?.toString().split(' - ').last.split(' ').first,
      'year': payslip['payroll_name']?.toString().split(' ').last,
      'payroll_number': payslip['payroll_name']?.toString().split(' - ').first,
    },
    'employee': const <String, dynamic>{},
    'bank': const <String, dynamic>{},
    'allowances': allowances,
    'deductions': deductions,
    'basic_salary': _toDouble(payslip['basic_salary']),
    'gross_salary': _toDouble(payslip['basic_salary']) + _toDouble(payslip['allowance']),
    'net_salary': _toDouble(payslip['net_salary']),
    'total_deductions':
        _toDouble(payslip['deduction']) + _toDouble(payslip['loan_deduction']),
  };
}
