import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _accountingProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/accounting');
  return response.data['data'] as Map<String, dynamic>;
});

class AccountingScreen extends ConsumerWidget {
  const AccountingScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final accountingAsync = ref.watch(_accountingProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Uhasibu' : 'Accounting'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_accountingProvider.future),
        child: accountingAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _AccountingErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_accountingProvider),
          ),
          data: (data) => _AccountingBody(
            data: data,
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
          ),
        ),
      ),
    );
  }
}

class _AccountingBody extends StatelessWidget {
  final Map<String, dynamic> data;
  final bool isSwahili;
  final bool isDarkMode;

  const _AccountingBody({
    required this.data,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final outstanding =
        data['outstanding_invoices'] as Map<String, dynamic>? ?? const {};
    final overdue =
        data['overdue_invoices'] as Map<String, dynamic>? ?? const {};
    final recentInvoices =
        (data['recent_invoices'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final recentPayments =
        (data['recent_payments'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final revenue =
        (data['revenue_by_month'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final collections =
        (data['collections_by_month'] as List?)?.cast<Map<String, dynamic>>() ??
        [];

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        Row(
          children: [
            Expanded(
              child: _MetricCard(
                title: isSwahili ? 'Ankara Zisizolipwa' : 'Outstanding',
                value: '${outstanding['count'] ?? 0}',
                subtitle: 'TZS ${_money(outstanding['total'])}',
                color: AppColors.info,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _MetricCard(
                title: isSwahili ? 'Ankara Zilizochelewa' : 'Overdue',
                value: '${overdue['count'] ?? 0}',
                subtitle: 'TZS ${_money(overdue['total'])}',
                color: AppColors.error,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        _SectionCard(
          title: isSwahili ? 'Mapato kwa Mwezi' : 'Revenue by Month',
          isDarkMode: isDarkMode,
          child: revenue.isEmpty
              ? _EmptySection(label: isSwahili ? 'Hakuna data' : 'No data')
              : Column(
                  children: revenue
                      .map(
                        (row) => _MonthRow(
                          label: _monthLabel(row),
                          amount: _money(row['total']),
                          isDarkMode: isDarkMode,
                        ),
                      )
                      .toList(),
                ),
        ),
        const SizedBox(height: 16),
        _SectionCard(
          title: isSwahili ? 'Makusanyo kwa Mwezi' : 'Collections by Month',
          isDarkMode: isDarkMode,
          child: collections.isEmpty
              ? _EmptySection(label: isSwahili ? 'Hakuna data' : 'No data')
              : Column(
                  children: collections
                      .map(
                        (row) => _MonthRow(
                          label: _monthLabel(row),
                          amount: _money(row['total']),
                          isDarkMode: isDarkMode,
                        ),
                      )
                      .toList(),
                ),
        ),
        const SizedBox(height: 16),
        _SectionCard(
          title: isSwahili ? 'Ankara za Hivi Karibuni' : 'Recent Invoices',
          isDarkMode: isDarkMode,
          child: recentInvoices.isEmpty
              ? _EmptySection(
                  label: isSwahili ? 'Hakuna ankara' : 'No invoices',
                )
              : Column(
                  children: recentInvoices
                      .map(
                        (invoice) => _InvoiceRow(
                          invoice: invoice,
                          isDarkMode: isDarkMode,
                        ),
                      )
                      .toList(),
                ),
        ),
        const SizedBox(height: 16),
        _SectionCard(
          title: isSwahili ? 'Malipo ya Hivi Karibuni' : 'Recent Payments',
          isDarkMode: isDarkMode,
          child: recentPayments.isEmpty
              ? _EmptySection(
                  label: isSwahili ? 'Hakuna malipo' : 'No payments',
                )
              : Column(
                  children: recentPayments
                      .map(
                        (payment) => _PaymentRow(
                          payment: payment,
                          isDarkMode: isDarkMode,
                        ),
                      )
                      .toList(),
                ),
        ),
        const SizedBox(height: 16),
        _ProvisionTaxSection(isSwahili: isSwahili, isDarkMode: isDarkMode),
        const SizedBox(height: 90),
      ],
    );
  }
}

class _MetricCard extends StatelessWidget {
  final String title;
  final String value;
  final String subtitle;
  final Color color;

  const _MetricCard({
    required this.title,
    required this.value,
    required this.subtitle,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final Widget child;
  final bool isDarkMode;

  const _SectionCard({
    required this.title,
    required this.child,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            child,
          ],
        ),
      ),
    );
  }
}

class _MonthRow extends StatelessWidget {
  final String label;
  final String amount;
  final bool isDarkMode;

  const _MonthRow({
    required this.label,
    required this.amount,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
              ),
            ),
          ),
          Text(
            'TZS $amount',
            style: const TextStyle(
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }
}

class _InvoiceRow extends StatelessWidget {
  final Map<String, dynamic> invoice;
  final bool isDarkMode;

  const _InvoiceRow({required this.invoice, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final status = invoice['status'] as String? ?? '-';

    return ListTile(
      onTap: () => _showAccountingRecordSheet(
        context,
        title: invoice['document_number'] as String? ?? 'Invoice',
        rows: [
          _AccountingSheetRow(
            'Client',
            invoice['client_name'] as String? ?? 'N/A',
          ),
          _AccountingSheetRow(
            'Project',
            invoice['project_name'] as String? ?? 'N/A',
          ),
          _AccountingSheetRow('Status', status.toUpperCase()),
          _AccountingSheetRow(
            'Issue Date',
            invoice['issue_date'] as String? ?? '-',
          ),
          _AccountingSheetRow(
            'Due Date',
            invoice['due_date'] as String? ?? '-',
          ),
          _AccountingSheetRow(
            'Balance',
            'TZS ${_money(invoice['balance_amount'])}',
          ),
          _AccountingSheetRow(
            'Total Amount',
            'TZS ${_money(invoice['total_amount'])}',
          ),
        ],
      ),
      contentPadding: EdgeInsets.zero,
      leading: CircleAvatar(
        backgroundColor: AppColors.info.withValues(alpha: 0.1),
        child: const Icon(Icons.receipt_long, color: AppColors.info),
      ),
      title: Text(
        invoice['document_number'] as String? ?? '-',
        style: TextStyle(
          fontWeight: FontWeight.w600,
          color: isDarkMode ? Colors.white : AppColors.textPrimary,
        ),
      ),
      subtitle: Text(
        '${invoice['client_name'] ?? '-'} - ${invoice['project_name'] ?? '-'}',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            'TZS ${_money(invoice['balance_amount'])}',
            style: const TextStyle(
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
            ),
          ),
          Text(
            status.toUpperCase(),
            style: TextStyle(
              fontSize: 10,
              color: _statusColor(status),
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _PaymentRow extends StatelessWidget {
  final Map<String, dynamic> payment;
  final bool isDarkMode;

  const _PaymentRow({required this.payment, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      onTap: () => _showAccountingRecordSheet(
        context,
        title: payment['invoice_number'] as String? ?? 'Payment',
        rows: [
          _AccountingSheetRow(
            'Invoice',
            payment['invoice_number'] as String? ?? 'N/A',
          ),
          _AccountingSheetRow('Amount', 'TZS ${_money(payment['amount'])}'),
          _AccountingSheetRow(
            'Method',
            payment['payment_method'] as String? ?? 'N/A',
          ),
          _AccountingSheetRow(
            'Collected By',
            payment['collected_by'] as String? ?? 'N/A',
          ),
          _AccountingSheetRow('Date', payment['date'] as String? ?? '-'),
        ],
      ),
      contentPadding: EdgeInsets.zero,
      leading: CircleAvatar(
        backgroundColor: AppColors.success.withValues(alpha: 0.1),
        child: const Icon(Icons.payments_rounded, color: AppColors.success),
      ),
      title: Text(
        payment['invoice_number'] as String? ?? '-',
        style: TextStyle(
          fontWeight: FontWeight.w600,
          color: isDarkMode ? Colors.white : AppColors.textPrimary,
        ),
      ),
      subtitle: Text(
        '${payment['payment_method'] ?? '-'} - ${payment['date'] ?? '-'}',
      ),
      trailing: Text(
        'TZS ${_money(payment['amount'])}',
        style: const TextStyle(
          fontWeight: FontWeight.w700,
          color: AppColors.success,
        ),
      ),
    );
  }
}

class _EmptySection extends StatelessWidget {
  final String label;

  const _EmptySection({required this.label});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Text(
        label,
        style: const TextStyle(color: AppColors.textSecondary),
      ),
    );
  }
}

class _AccountingSheetRow {
  final String label;
  final String value;

  const _AccountingSheetRow(this.label, this.value);
}

void _showAccountingRecordSheet(
  BuildContext context, {
  required String title,
  required List<_AccountingSheetRow> rows,
}) {
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => SafeArea(
      child: ListView(
        shrinkWrap: true,
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 18),
          ...rows.map(
            (row) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 120,
                    child: Text(
                      row.label,
                      style: const TextStyle(
                        color: AppColors.textSecondary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  Expanded(
                    child: Text(
                      row.value,
                      style: const TextStyle(fontSize: 14),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    ),
  );
}

class _AccountingErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _AccountingErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          '$error',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}

String _monthLabel(Map<String, dynamic> row) {
  final month = int.tryParse('${row['month']}') ?? 1;
  final year = int.tryParse('${row['year']}') ?? DateTime.now().year;
  return DateFormat('MMM yyyy').format(DateTime(year, month));
}

String _money(dynamic value) {
  final amount = value is num
      ? value.toDouble()
      : double.tryParse('$value') ?? 0;
  return NumberFormat('#,##0.00', 'en_US').format(amount);
}

Color _statusColor(String status) {
  switch (status.toLowerCase()) {
    case 'paid':
      return AppColors.success;
    case 'overdue':
      return AppColors.error;
    case 'unpaid':
      return AppColors.warning;
    default:
      return AppColors.info;
  }
}

class _ProvisionTaxSection extends ConsumerStatefulWidget {
  final bool isSwahili;
  final bool isDarkMode;

  const _ProvisionTaxSection({
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  ConsumerState<_ProvisionTaxSection> createState() =>
      _ProvisionTaxSectionState();
}

class _ProvisionTaxSectionState extends ConsumerState<_ProvisionTaxSection> {
  bool _isLoading = false;
  List<dynamic> _taxes = [];
  Map<String, dynamic> _summary = {};

  @override
  void initState() {
    super.initState();
    _loadProvisionTaxes();
  }

  Future<void> _loadProvisionTaxes() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get(
        '/provision-tax',
        queryParameters: {
          'per_page': '5', // Only show recent ones in accounting screen
        },
      );

      if (response.statusCode == 200) {
        final data = response.data['data'];
        setState(() {
          _taxes = data['data'] ?? [];
          _summary = data['summary'] ?? {};
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  String _formatCurrency(double amount) {
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(amount);
  }

  @override
  Widget build(BuildContext context) {
    return _SectionCard(
      title: widget.isSwahili ? 'Usalala wa Kodi' : 'Provision Tax',
      isDarkMode: widget.isDarkMode,
      child: Column(
        children: [
          // Summary Row
          Row(
            children: [
              Expanded(
                child: _ProvisionTaxMetric(
                  label: widget.isSwahili ? 'Jumla Kuu' : 'Total Amount',
                  value: _formatCurrency(
                    (_summary['total_amount'] ?? 0).toDouble(),
                  ),
                  color: AppColors.info,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _ProvisionTaxMetric(
                  label: widget.isSwahili ? 'Rekodi' : 'Records',
                  value: (_summary['count'] ?? 0).toString(),
                  color: AppColors.success,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Recent Taxes List
          if (_isLoading)
            const Center(child: CircularProgressIndicator())
          else if (_taxes.isEmpty)
            _EmptySection(label: widget.isSwahili ? 'Hakuna data' : 'No data')
          else
            Column(
              children: _taxes
                  .take(3)
                  .map(
                    (tax) => _ProvisionTaxRow(
                      tax: tax,
                      isDarkMode: widget.isDarkMode,
                      isSwahili: widget.isSwahili,
                    ),
                  )
                  .toList(),
            ),
          const SizedBox(height: 8),
          // View All Button
          if (_taxes.isNotEmpty)
            TextButton(
              onPressed: () {
                context.push('/provision-tax');
              },
              child: Text(
                widget.isSwahili ? 'Ona Zote' : 'View All',
                style: TextStyle(
                  color: Theme.of(context).colorScheme.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ProvisionTaxMetric extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _ProvisionTaxMetric({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w500,
            ),
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
            overflow: TextOverflow.ellipsis,
            maxLines: 1,
          ),
        ],
      ),
    );
  }
}

class _ProvisionTaxRow extends StatelessWidget {
  final dynamic tax;
  final bool isDarkMode;
  final bool isSwahili;

  const _ProvisionTaxRow({
    required this.tax,
    required this.isDarkMode,
    required this.isSwahili,
  });

  String _formatCurrency(dynamic amount) {
    final number = amount is num
        ? amount.toDouble()
        : double.tryParse(amount?.toString() ?? '') ?? 0;
    final formatter = NumberFormat.currency(
      locale: 'en_TZ',
      symbol: 'TZS ',
      decimalDigits: 0,
    );
    return formatter.format(number);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  tax['description'] ?? 'No Description',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                  overflow: TextOverflow.ellipsis,
                  maxLines: 1,
                ),
              ),
              Text(
                _formatCurrency(tax['amount'] ?? 0),
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: AppColors.success,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Icon(
                Icons.calendar_today,
                size: 12,
                color: AppColors.textSecondary,
              ),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  tax['date'] ?? 'No Date',
                  style: TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (tax['bank'] != null) ...[
                const SizedBox(width: 8),
                Icon(
                  Icons.account_balance,
                  size: 12,
                  color: AppColors.textSecondary,
                ),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    tax['bank']['bank_name'] ?? 'Unknown Bank',
                    style: TextStyle(
                      fontSize: 11,
                      color: AppColors.textSecondary,
                    ),
                    overflow: TextOverflow.ellipsis,
                    maxLines: 1,
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}
