import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

// --- State providers ---

final _selectedStaffIdProvider = StateProvider.autoDispose<int?>((ref) => null);
final _startDateProvider = StateProvider.autoDispose<DateTime>(
    (ref) => DateTime(DateTime.now().year, 1, 1));
final _endDateProvider = StateProvider.autoDispose<DateTime>(
    (ref) => DateTime.now());

final _staffListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/employee-profile/staff-list');
  return (response.data['data'] as List).cast<Map<String, dynamic>>();
});

final _profileProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final staffId = ref.watch(_selectedStaffIdProvider);
  final startDate = ref.watch(_startDateProvider);
  final endDate = ref.watch(_endDateProvider);
  final params = <String, dynamic>{
    'start_date': DateFormat('yyyy-MM-dd').format(startDate),
    'end_date': DateFormat('yyyy-MM-dd').format(endDate),
  };
  if (staffId != null) params['staff_id'] = staffId;
  final response =
      await api.get('/employee-profile', queryParameters: params);
  return response.data['data'] as Map<String, dynamic>;
});

// --- Colors ---

const _darkCard = Color(0xFF1A2332);
const _darkBg = Color(0xFF0F1923);
const _darkBorder = Color(0xFF243447);
const _accentTeal = Color(0xFF2FACB2);
const _accentBlue = Color(0xFF3F9CE8);

// --- Screen ---

class EmployeeProfileScreen extends ConsumerWidget {
  const EmployeeProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileAsync = ref.watch(_profileProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);

    return Scaffold(
      backgroundColor: isDark ? _darkBg : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Wasifu wa Mfanyakazi' : 'Employee Profile'),
        backgroundColor: isDark ? _darkCard : null,
      ),
      body: Column(
        children: [
          // Filters bar
          _FiltersBar(isDark: isDark, isSwahili: isSwahili),
          // Content
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(_profileProvider.future),
              child: profileAsync.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_profileProvider),
                ),
                data: (data) => _ProfileBody(
                  data: data,
                  isDark: isDark,
                  isSwahili: isSwahili,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ----------- Filters Bar (Employee Selector + Date Range) -----------

class _FiltersBar extends ConsumerWidget {
  final bool isDark;
  final bool isSwahili;
  const _FiltersBar({required this.isDark, required this.isSwahili});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final staffListAsync = ref.watch(_staffListProvider);
    final selectedStaffId = ref.watch(_selectedStaffIdProvider);
    final startDate = ref.watch(_startDateProvider);
    final endDate = ref.watch(_endDateProvider);
    final dateFmt = DateFormat('dd MMM yyyy');

    return Container(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
      decoration: BoxDecoration(
        color: isDark ? _darkCard : Colors.white,
        border: Border(
          bottom: BorderSide(
            color: isDark ? _darkBorder : Colors.grey.withValues(alpha: 0.15),
          ),
        ),
      ),
      child: Column(
        children: [
          // Employee selector
          staffListAsync.when(
            loading: () => const SizedBox(height: 42),
            error: (_, _) => const SizedBox.shrink(),
            data: (staffList) {
              return Container(
                height: 42,
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: isDark ? _darkBg : const Color(0xFFF5F6FA),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(
                    color: isDark ? _darkBorder : Colors.grey.withValues(alpha: 0.2),
                  ),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<int?>(
                    value: selectedStaffId,
                    isExpanded: true,
                    isDense: true,
                    icon: Icon(Icons.unfold_more_rounded,
                        size: 18,
                        color: isDark ? Colors.white38 : AppColors.textHint),
                    dropdownColor: isDark ? _darkCard : Colors.white,
                    hint: Text(
                      isSwahili ? 'Chagua mfanyakazi...' : 'Select employee...',
                      style: TextStyle(
                        fontSize: 13,
                        color: isDark ? Colors.white38 : AppColors.textHint,
                      ),
                    ),
                    style: TextStyle(
                      fontSize: 13,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                    items: [
                      DropdownMenuItem<int?>(
                        value: null,
                        child: Text(
                          isSwahili ? 'Mimi (default)' : 'Me (default)',
                          style: TextStyle(
                            fontSize: 13,
                            fontStyle: FontStyle.italic,
                            color: isDark ? Colors.white54 : AppColors.textSecondary,
                          ),
                        ),
                      ),
                      ...staffList.map((s) => DropdownMenuItem<int?>(
                            value: s['id'] as int,
                            child: Text(
                              '${s['name']} — ${s['department'] ?? ''}',
                              style: const TextStyle(fontSize: 13),
                              overflow: TextOverflow.ellipsis,
                            ),
                          )),
                    ],
                    onChanged: (v) =>
                        ref.read(_selectedStaffIdProvider.notifier).state = v,
                  ),
                ),
              );
            },
          ),
          const SizedBox(height: 8),
          // Date range row
          Row(
            children: [
              Expanded(
                child: _DateChip(
                  label: dateFmt.format(startDate),
                  icon: Icons.calendar_today_rounded,
                  isDark: isDark,
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: startDate,
                      firstDate: DateTime(2020),
                      lastDate: endDate,
                    );
                    if (picked != null) {
                      ref.read(_startDateProvider.notifier).state = picked;
                    }
                  },
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                child: Icon(Icons.arrow_forward_rounded,
                    size: 16,
                    color: isDark ? Colors.white24 : AppColors.textHint),
              ),
              Expanded(
                child: _DateChip(
                  label: dateFmt.format(endDate),
                  icon: Icons.event_rounded,
                  isDark: isDark,
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: endDate,
                      firstDate: startDate,
                      lastDate: DateTime.now(),
                    );
                    if (picked != null) {
                      ref.read(_endDateProvider.notifier).state = picked;
                    }
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _DateChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool isDark;
  final VoidCallback onTap;
  const _DateChip(
      {required this.label,
      required this.icon,
      required this.isDark,
      required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 36,
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(
          color: isDark ? _darkBg : const Color(0xFFF5F6FA),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isDark ? _darkBorder : Colors.grey.withValues(alpha: 0.2),
          ),
        ),
        child: Row(
          children: [
            Icon(icon, size: 14, color: _accentTeal),
            const SizedBox(width: 6),
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: isDark ? Colors.white : AppColors.textPrimary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ----------- Profile Body -----------

class _ProfileBody extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isDark;
  final bool isSwahili;

  const _ProfileBody({
    required this.data,
    required this.isDark,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final personal = data['personal_info'] as Map<String, dynamic>? ?? {};
    final financial =
        data['financial_summary'] as Map<String, dynamic>? ?? {};
    final loans =
        (data['loan_history'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final advances =
        (data['advance_salaries'] as List?)?.cast<Map<String, dynamic>>() ??
            [];
    final payrolls =
        (data['payroll_history'] as List?)?.cast<Map<String, dynamic>>() ??
            [];
    final assets =
        (data['assets'] as List?)?.cast<Map<String, dynamic>>() ?? [];

    // Bottom padding for nav bar
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: EdgeInsets.fromLTRB(16, 12, 16, bottomPad),
      children: [
        // Profile header card
        _ProfileCard(personal: personal, financial: financial, isDark: isDark),
        const SizedBox(height: 14),

        // Financial summary cards
        _FinancialCards(
            financial: financial, isDark: isDark, isSwahili: isSwahili),
        const SizedBox(height: 14),

        // General Info
        _GeneralInfoCard(
            personal: personal, isDark: isDark, isSwahili: isSwahili),
        const SizedBox(height: 14),

        // Loan History
        if (loans.isNotEmpty) ...[
          _SectionCard(
            title: isSwahili ? 'Historia ya Mkopo' : 'Loan History',
            icon: Icons.account_balance_rounded,
            isDark: isDark,
            child: _LoanTable(
                loans: loans, isDark: isDark, isSwahili: isSwahili),
          ),
          const SizedBox(height: 14),
        ],

        // Advance Salaries
        if (advances.isNotEmpty) ...[
          _SectionCard(
            title: isSwahili
                ? 'Historia ya Mshahara wa Mapema'
                : 'Advance Salaries',
            icon: Icons.payments_rounded,
            isDark: isDark,
            child: _AdvanceSalaryTable(
                advances: advances, isDark: isDark, isSwahili: isSwahili),
          ),
          const SizedBox(height: 14),
        ],

        // Payroll History
        if (payrolls.isNotEmpty) ...[
          _SectionCard(
            title: isSwahili ? 'Historia ya Mshahara' : 'Payroll History',
            icon: Icons.receipt_long_rounded,
            isDark: isDark,
            child: _PayrollList(
                payrolls: payrolls, isDark: isDark, isSwahili: isSwahili),
          ),
          const SizedBox(height: 14),
        ],

        // Assets
        if (assets.isNotEmpty) ...[
          _SectionCard(
            title: isSwahili ? 'Mali na Faida' : 'Assets & Benefits',
            icon: Icons.inventory_2_rounded,
            isDark: isDark,
            child: _AssetsTable(assets: assets, isDark: isDark),
          ),
        ],
      ],
    );
  }
}

// ----------- Profile Card -----------

class _ProfileCard extends StatelessWidget {
  final Map<String, dynamic> personal;
  final Map<String, dynamic> financial;
  final bool isDark;

  const _ProfileCard({
    required this.personal,
    required this.financial,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    final name = personal['name'] as String? ?? '';
    final empNo = personal['employee_number'] as String? ?? '';
    final designation = personal['designation'] as String? ?? '';
    final status = personal['status'] as String? ?? '';
    final basicSalary =
        (financial['basic_salary'] as num?)?.toDouble() ?? 0;
    final loanBalance =
        (financial['loan_balance'] as num?)?.toDouble() ?? 0;

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [_accentBlue, _accentTeal],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: _accentBlue.withValues(alpha: 0.3),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
      child: Column(
        children: [
          CircleAvatar(
            radius: 32,
            backgroundColor: Colors.white.withValues(alpha: 0.2),
            child: Text(
              name.isNotEmpty ? name[0].toUpperCase() : '?',
              style: const TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            name,
            style: const TextStyle(
                fontSize: 18, fontWeight: FontWeight.w700, color: Colors.white),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          if (designation.isNotEmpty) ...[
            const SizedBox(height: 2),
            Text(
              designation,
              style:
                  TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.8)),
            ),
          ],
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _Badge(label: 'HRM/$empNo', color: Colors.white.withValues(alpha: 0.2)),
              const SizedBox(width: 8),
              _Badge(
                label: status,
                color: status == 'ACTIVE'
                    ? Colors.green.withValues(alpha: 0.35)
                    : Colors.red.withValues(alpha: 0.35),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _MiniStat(label: 'Basic Salary', value: _fmt(basicSalary)),
                Container(
                  height: 28,
                  width: 1,
                  color: Colors.white.withValues(alpha: 0.25),
                ),
                _MiniStat(label: 'Loan Balance', value: _fmt(loanBalance)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  final String label;
  final Color color;
  const _Badge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: const TextStyle(
            fontSize: 11, fontWeight: FontWeight.w600, color: Colors.white),
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  const _MiniStat({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(label,
            style: TextStyle(
                fontSize: 10, color: Colors.white.withValues(alpha: 0.65))),
        const SizedBox(height: 2),
        Text(value,
            style: const TextStyle(
                fontSize: 15, fontWeight: FontWeight.w700, color: Colors.white)),
      ],
    );
  }
}

// ----------- Financial Cards -----------

class _FinancialCards extends StatelessWidget {
  final Map<String, dynamic> financial;
  final bool isDark;
  final bool isSwahili;

  const _FinancialCards({
    required this.financial,
    required this.isDark,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final items = [
      _FinItem('Gross', (financial['gross_pay'] as num?)?.toDouble() ?? 0,
          Icons.trending_up_rounded, _accentBlue),
      _FinItem(
          isSwahili ? 'Makato' : 'Deductions',
          (financial['total_deductions'] as num?)?.toDouble() ?? 0,
          Icons.trending_down_rounded,
          const Color(0xFFEF5350)),
      _FinItem(
          isSwahili ? 'Posho' : 'Allowances',
          (financial['allowances'] as num?)?.toDouble() ?? 0,
          Icons.card_giftcard_rounded,
          const Color(0xFF66BB6A)),
      _FinItem('Net Pay', (financial['net_pay'] as num?)?.toDouble() ?? 0,
          Icons.account_balance_wallet_rounded, _accentTeal),
    ];

    Widget buildCard(_FinItem item) {
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isDark ? _darkCard : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border(left: BorderSide(color: item.color, width: 3.5)),
          boxShadow: isDark
              ? null
              : [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(item.icon, size: 14, color: item.color),
                const SizedBox(width: 5),
                Text(
                  item.label,
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w500,
                    color: isDark ? Colors.white54 : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              _fmt(item.value),
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w700,
                color: isDark ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      children: [
        Row(
          children: [
            Expanded(child: buildCard(items[0])),
            const SizedBox(width: 10),
            Expanded(child: buildCard(items[1])),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(child: buildCard(items[2])),
            const SizedBox(width: 10),
            Expanded(child: buildCard(items[3])),
          ],
        ),
      ],
    );
  }
}

class _FinItem {
  final String label;
  final double value;
  final IconData icon;
  final Color color;
  const _FinItem(this.label, this.value, this.icon, this.color);
}

// ----------- General Info Card -----------

class _GeneralInfoCard extends StatelessWidget {
  final Map<String, dynamic> personal;
  final bool isDark;
  final bool isSwahili;

  const _GeneralInfoCard({
    required this.personal,
    required this.isDark,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final rows = <_InfoRow>[
      _InfoRow(Icons.work_outline_rounded, isSwahili ? 'Cheo' : 'Designation',
          personal['designation'] as String? ?? '-'),
      _InfoRow(
          Icons.calendar_today_rounded,
          isSwahili ? 'Tarehe Kuajiriwa' : 'Employed',
          personal['employment_date'] as String? ?? '-'),
      _InfoRow(Icons.apartment_rounded, isSwahili ? 'Idara' : 'Department',
          personal['department'] as String? ?? '-'),
      _InfoRow(Icons.person_outline_rounded, isSwahili ? 'Jinsia' : 'Gender',
          personal['gender'] as String? ?? '-'),
      _InfoRow(Icons.cake_rounded, isSwahili ? 'Kuzaliwa' : 'DOB',
          personal['dob'] as String? ?? '-'),
      _InfoRow(Icons.phone_rounded, isSwahili ? 'Simu' : 'Phone',
          personal['phone'] as String? ?? '-'),
      _InfoRow(
          Icons.email_outlined, 'Email', personal['email'] as String? ?? '-'),
      _InfoRow(Icons.location_on_outlined, isSwahili ? 'Anwani' : 'Address',
          personal['address'] as String? ?? '-'),
      _InfoRow(Icons.badge_outlined, 'NIDA',
          personal['national_id'] as String? ?? '-'),
      _InfoRow(
          Icons.receipt_outlined, 'TIN', personal['tin'] as String? ?? '-'),
      _InfoRow(Icons.account_balance_rounded,
          isSwahili ? 'Akaunti' : 'Account', personal['account_number'] as String? ?? '-'),
    ];

    return _card(
      isDark: isDark,
      child: Column(
        children: [
          _sectionHeader(
            Icons.info_outline_rounded,
            isSwahili ? 'Taarifa Binafsi' : 'General Information',
            isDark,
          ),
          ...rows.map((row) => _infoTile(row, isDark)),
          const SizedBox(height: 6),
        ],
      ),
    );
  }

  Widget _infoTile(_InfoRow row, bool isDark) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      child: Row(
        children: [
          Icon(row.icon,
              size: 15, color: isDark ? Colors.white30 : AppColors.textHint),
          const SizedBox(width: 8),
          SizedBox(
            width: 85,
            child: Text(
              row.label,
              style: TextStyle(
                fontSize: 11,
                color: isDark ? Colors.white.withValues(alpha: 0.40) : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              row.value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: isDark ? Colors.white.withValues(alpha: 0.85) : AppColors.textPrimary,
              ),
              textAlign: TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow {
  final IconData icon;
  final String label;
  final String value;
  const _InfoRow(this.icon, this.label, this.value);
}

// ----------- Section Card Wrapper -----------

class _SectionCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final bool isDark;
  final Widget child;

  const _SectionCard({
    required this.title,
    required this.icon,
    required this.isDark,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return _card(
      isDark: isDark,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionHeader(icon, title, isDark),
          child,
        ],
      ),
    );
  }
}

// ----------- Loan Table -----------

class _LoanTable extends StatelessWidget {
  final List<Map<String, dynamic>> loans;
  final bool isDark;
  final bool isSwahili;

  const _LoanTable(
      {required this.loans, required this.isDark, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    double totalLoan = 0;
    for (final l in loans) {
      totalLoan += (l['amount'] as num?)?.toDouble() ?? 0;
    }
    return Column(
      children: [
        _TableHeader(columns: [
          isSwahili ? 'Tarehe' : 'Date',
          isSwahili ? 'Makato' : 'Deduct',
          isSwahili ? 'Kiasi' : 'Amount',
        ], isDark: isDark),
        ...loans.map((l) => _TableRow(
              values: [
                l['date'] as String? ?? '',
                _fmt((l['deduction'] as num?)?.toDouble() ?? 0),
                _fmt((l['amount'] as num?)?.toDouble() ?? 0),
              ],
              isDark: isDark,
            )),
        _TableRow(
          values: ['', isSwahili ? 'Jumla' : 'Total', _fmt(totalLoan)],
          isDark: isDark,
          isBold: true,
        ),
      ],
    );
  }
}

// ----------- Advance Salary Table -----------

class _AdvanceSalaryTable extends StatelessWidget {
  final List<Map<String, dynamic>> advances;
  final bool isDark;
  final bool isSwahili;

  const _AdvanceSalaryTable(
      {required this.advances,
      required this.isDark,
      required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    double total = 0;
    for (final a in advances) {
      total += (a['amount'] as num?)?.toDouble() ?? 0;
    }
    return Column(
      children: [
        _TableHeader(columns: [
          isSwahili ? 'Tarehe' : 'Date',
          isSwahili ? 'Maelezo' : 'Desc.',
          isSwahili ? 'Kiasi' : 'Amount',
        ], isDark: isDark),
        ...advances.map((a) => _TableRow(
              values: [
                a['date'] as String? ?? '',
                a['description'] as String? ?? '',
                _fmt((a['amount'] as num?)?.toDouble() ?? 0),
              ],
              isDark: isDark,
            )),
        _TableRow(
          values: ['', isSwahili ? 'Jumla' : 'Total', _fmt(total)],
          isDark: isDark,
          isBold: true,
        ),
      ],
    );
  }
}

// ----------- Payroll List -----------

class _PayrollList extends StatelessWidget {
  final List<Map<String, dynamic>> payrolls;
  final bool isDark;
  final bool isSwahili;

  const _PayrollList(
      {required this.payrolls,
      required this.isDark,
      required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: payrolls.map((p) {
        final period = p['period'] as String? ?? '';
        final net = (p['net'] as num?)?.toDouble() ?? 0;
        final salary = (p['salary'] as num?)?.toDouble() ?? 0;
        final allowance = (p['allowance'] as num?)?.toDouble() ?? 0;
        final gross = (p['gross'] as num?)?.toDouble() ?? 0;
        final nssf = (p['nssf'] as num?)?.toDouble() ?? 0;
        final paye = (p['paye'] as num?)?.toDouble() ?? 0;
        final advance = (p['advance'] as num?)?.toDouble() ?? 0;
        final loan = (p['loan'] as num?)?.toDouble() ?? 0;

        return Theme(
          data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
          child: ExpansionTile(
            tilePadding: const EdgeInsets.symmetric(horizontal: 14),
            childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
            iconColor: isDark ? Colors.white38 : AppColors.textHint,
            collapsedIconColor: isDark ? Colors.white24 : AppColors.textHint,
            title: Text(
              period,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDark ? Colors.white : AppColors.textPrimary,
              ),
            ),
            subtitle: Text(
              'Net: ${_fmt(net)}',
              style: const TextStyle(
                fontSize: 11,
                color: _accentTeal,
                fontWeight: FontWeight.w600,
              ),
            ),
            children: [
              _detailRow('Salary', salary, isDark),
              _detailRow('Allowance', allowance, isDark),
              _detailRow('Gross', gross, isDark),
              _detailRow('NSSF', nssf, isDark),
              _detailRow('PAYE', paye, isDark),
              _detailRow('Advance', advance, isDark),
              _detailRow('Loan', loan, isDark),
              Divider(
                  height: 10,
                  color: isDark ? _darkBorder : Colors.grey.withValues(alpha: 0.2)),
              _detailRow('Net Pay', net, isDark,
                  bold: true, color: _accentTeal),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _detailRow(String label, double value, bool isDark,
      {bool bold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: bold ? FontWeight.w700 : FontWeight.w400,
                color: color ?? (isDark ? Colors.white.withValues(alpha: 0.45) : AppColors.textSecondary),
              )),
          Text(_fmt(value),
              style: TextStyle(
                fontSize: 12,
                fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
                color: color ?? (isDark ? Colors.white.withValues(alpha: 0.85) : AppColors.textPrimary),
              )),
        ],
      ),
    );
  }
}

// ----------- Assets Table -----------

class _AssetsTable extends StatelessWidget {
  final List<Map<String, dynamic>> assets;
  final bool isDark;
  const _AssetsTable({required this.assets, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: assets.map((a) {
        return ListTile(
          dense: true,
          leading: Icon(Icons.devices_other_rounded,
              size: 18, color: isDark ? Colors.white30 : AppColors.textHint),
          title: Text(a['name'] as String? ?? '',
              style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: isDark ? Colors.white : AppColors.textPrimary)),
          subtitle: Text(a['description'] as String? ?? '',
              style: TextStyle(
                  fontSize: 11,
                  color: isDark ? Colors.white.withValues(alpha: 0.45) : AppColors.textSecondary)),
          trailing: Text(a['asset'] as String? ?? '',
              style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  color: isDark ? Colors.white60 : AppColors.textPrimary)),
        );
      }).toList(),
    );
  }
}

// ----------- Shared Table Widgets -----------

class _TableHeader extends StatelessWidget {
  final List<String> columns;
  final bool isDark;
  const _TableHeader({required this.columns, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
      color: isDark ? _darkBg.withValues(alpha: 0.5) : _accentBlue.withValues(alpha: 0.06),
      child: Row(
        children: columns.asMap().entries.map((e) {
          return Expanded(
            child: Text(
              e.value,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                color: isDark ? Colors.white.withValues(alpha: 0.40) : _accentBlue,
              ),
              textAlign: e.key == columns.length - 1
                  ? TextAlign.right
                  : TextAlign.left,
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _TableRow extends StatelessWidget {
  final List<String> values;
  final bool isDark;
  final bool isBold;
  const _TableRow(
      {required this.values, required this.isDark, this.isBold = false});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
      decoration: BoxDecoration(
        border: Border(
          bottom: BorderSide(
            color: isDark ? _darkBorder.withValues(alpha: 0.4) : Colors.grey.withValues(alpha: 0.1),
          ),
        ),
      ),
      child: Row(
        children: values.asMap().entries.map((e) {
          return Expanded(
            child: Text(
              e.value,
              style: TextStyle(
                fontSize: 11,
                fontWeight: isBold ? FontWeight.w700 : FontWeight.w400,
                color: isDark ? Colors.white.withValues(alpha: 0.85) : AppColors.textPrimary,
              ),
              textAlign: e.key == values.length - 1
                  ? TextAlign.right
                  : TextAlign.left,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          );
        }).toList(),
      ),
    );
  }
}

// ----------- Error View -----------

class _ErrorView extends StatelessWidget {
  final bool isSwahili;
  final VoidCallback onRetry;
  const _ErrorView({required this.isSwahili, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 80),
        const Icon(Icons.error_outline, size: 56, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 20),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh, size: 18),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}

// ----------- Shared helpers -----------

Widget _card({required bool isDark, required Widget child}) {
  return Container(
    decoration: BoxDecoration(
      color: isDark ? _darkCard : Colors.white,
      borderRadius: BorderRadius.circular(14),
      border: isDark ? Border.all(color: _darkBorder, width: 0.5) : null,
      boxShadow: isDark
          ? null
          : [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
    ),
    child: child,
  );
}

Widget _sectionHeader(IconData icon, String title, bool isDark) {
  return Column(
    children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 8),
        child: Row(
          children: [
            Icon(icon, size: 16, color: isDark ? _accentTeal : AppColors.textSecondary),
            const SizedBox(width: 8),
            Text(
              title,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDark ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ],
        ),
      ),
      Divider(
          height: 1,
          color: isDark ? _darkBorder : Colors.grey.withValues(alpha: 0.15)),
    ],
  );
}

final _currencyFormat = NumberFormat('#,##0', 'en_US');
String _fmt(double amount) => _currencyFormat.format(amount);
