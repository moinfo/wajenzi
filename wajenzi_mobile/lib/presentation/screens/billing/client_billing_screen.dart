import 'dart:io';
import 'dart:ui';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/config/app_config.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/services/storage_service.dart';
import '../../providers/client_billing_provider.dart';
import '../../providers/settings_provider.dart';
import '../../../data/datasources/remote/client_api.dart';

class ClientBillingScreen extends ConsumerStatefulWidget {
  const ClientBillingScreen({super.key});

  @override
  ConsumerState<ClientBillingScreen> createState() => _ClientBillingScreenState();
}

class _ClientBillingScreenState extends ConsumerState<ClientBillingScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(clientBillingProvider.notifier).fetchBilling();
    });
  }

  String _formatCurrency(double amount) {
    final formatter = NumberFormat('#,##0.00', 'en');
    return 'TZS ${formatter.format(amount)}';
  }

  String _formatCurrencyShort(double amount) {
    if (amount >= 1e9) return 'TZS ${(amount / 1e9).toStringAsFixed(1)}B';
    if (amount >= 1e6) return 'TZS ${(amount / 1e6).toStringAsFixed(1)}M';
    if (amount >= 1e3) return 'TZS ${(amount / 1e3).toStringAsFixed(0)}K';
    return 'TZS ${amount.toStringAsFixed(0)}';
  }

  String _formatDate(String? date) {
    if (date == null) return '—';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date;
    }
  }

  // ─── PDF Download ──────────────────────────────

  Future<void> _downloadPdf(BillingDocument doc) async {
    final messenger = ScaffoldMessenger.of(context);
    final isSwahili = ref.read(isSwahiliProvider);

    messenger.showSnackBar(SnackBar(
      content: Text(isSwahili ? 'Inapakua...' : 'Downloading...'),
      duration: const Duration(seconds: 1),
    ));

    try {
      final token = await ref.read(storageServiceProvider).getToken();
      final url = '${AppConfig.clientBaseUrl}/billing/${doc.id}/pdf';
      final dir = await getTemporaryDirectory();
      final fileName = '${doc.documentType}-${doc.documentNumber ?? doc.id}.pdf';
      final filePath = '${dir.path}/$fileName';

      await Dio().download(
        url,
        filePath,
        options: Options(headers: {'Authorization': 'Bearer $token'}),
      );

      final file = File(filePath);
      if (await file.exists()) {
        final uri = Uri.file(filePath);
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri);
        } else {
          messenger.showSnackBar(SnackBar(
            content: Text(isSwahili
                ? 'Imehifadhiwa: $fileName'
                : 'Saved: $fileName'),
          ));
        }
      }
    } catch (e) {
      messenger.showSnackBar(SnackBar(
        content: Text(isSwahili ? 'Imeshindwa kupakua' : 'Download failed'),
        backgroundColor: AppColors.error,
      ));
    }
  }

  // ─── Detail Bottom Sheet ───────────────────────

  void _showDocumentDetail(BillingDocument doc, bool isSwahili, bool isDarkMode) {
    final isInvoice = doc.documentType == 'invoice';
    final typeLabel = {
      'invoice': isSwahili ? 'Ankara' : 'Invoice',
      'quote': isSwahili ? 'Nukuu' : 'Quotation',
      'proforma': isSwahili ? 'Ankara ya Awali' : 'Proforma Invoice',
      'credit_note': isSwahili ? 'Noti ya Mkopo' : 'Credit Note',
    }[doc.documentType] ?? doc.documentType;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        maxChildSize: 0.9,
        minChildSize: 0.4,
        builder: (context, scrollController) => Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              // Drag handle
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  children: [
                    // Type + number header
                    Row(
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                typeLabel,
                                style: TextStyle(
                                  fontSize: 13,
                                  color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                doc.documentNumber ?? '—',
                                style: TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                                ),
                              ),
                            ],
                          ),
                        ),
                        _buildStatusBadge(
                          doc.isOverdue && doc.status != 'paid' ? 'overdue' : doc.status,
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Info rows
                    _DetailRow(
                      label: isSwahili ? 'Mradi' : 'Project',
                      value: doc.projectName ?? '—',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Tarehe' : 'Issue Date',
                      value: _formatDate(doc.issueDate),
                      isDarkMode: isDarkMode,
                    ),
                    if (isInvoice)
                      _DetailRow(
                        label: isSwahili ? 'Tarehe ya mwisho' : 'Due Date',
                        value: _formatDate(doc.dueDate),
                        isDarkMode: isDarkMode,
                        valueColor: doc.isOverdue ? AppColors.error : null,
                      ),
                    if (!isInvoice && doc.validUntilDate != null)
                      _DetailRow(
                        label: isSwahili ? 'Halali hadi' : 'Valid Until',
                        value: _formatDate(doc.validUntilDate),
                        isDarkMode: isDarkMode,
                      ),
                    if (doc.paymentTerms != null)
                      _DetailRow(
                        label: isSwahili ? 'Masharti' : 'Payment Terms',
                        value: doc.paymentTerms!,
                        isDarkMode: isDarkMode,
                      ),
                    const SizedBox(height: 16),

                    // Amounts
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: isDarkMode
                            ? Colors.white.withValues(alpha: 0.05)
                            : AppColors.background,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          _AmountRow(
                            label: isSwahili ? 'Jumla' : 'Total Amount',
                            value: _formatCurrency(doc.totalAmount),
                            color: isDarkMode ? Colors.white : AppColors.textPrimary,
                            isBold: true,
                          ),
                          if (isInvoice) ...[
                            const SizedBox(height: 8),
                            _AmountRow(
                              label: isSwahili ? 'Limelipwa' : 'Paid',
                              value: _formatCurrency(doc.paidAmount),
                              color: AppColors.success,
                            ),
                            const SizedBox(height: 8),
                            Divider(
                              color: isDarkMode ? Colors.white12 : Colors.grey[200],
                            ),
                            const SizedBox(height: 8),
                            _AmountRow(
                              label: isSwahili ? 'Deni' : 'Balance Due',
                              value: _formatCurrency(doc.balanceAmount),
                              color: doc.balanceAmount > 0
                                  ? AppColors.error
                                  : AppColors.success,
                              isBold: true,
                            ),
                          ],
                        ],
                      ),
                    ),

                    // Payments list (for invoices)
                    if (isInvoice && doc.payments.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      Text(
                        isSwahili ? 'Malipo' : 'Payments',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 10),
                      ...doc.payments.map((p) => Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: isDarkMode
                                  ? Colors.white.withValues(alpha: 0.05)
                                  : AppColors.background,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        p.paymentNumber ?? '—',
                                        style: TextStyle(
                                          fontSize: 13,
                                          fontWeight: FontWeight.w600,
                                          color: isDarkMode ? Colors.white : AppColors.textPrimary,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        '${_formatDate(p.paymentDate)} • ${p.paymentMethod ?? "—"}',
                                        style: const TextStyle(
                                          fontSize: 11,
                                          color: AppColors.textSecondary,
                                        ),
                                      ),
                                      if (p.referenceNumber != null)
                                        Text(
                                          p.referenceNumber!,
                                          style: const TextStyle(
                                            fontSize: 11,
                                            color: AppColors.textSecondary,
                                          ),
                                        ),
                                    ],
                                  ),
                                ),
                                Text(
                                  _formatCurrency(p.amount),
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.success,
                                  ),
                                ),
                              ],
                            ),
                          )),
                    ],

                    const SizedBox(height: 24),

                    // Download PDF button
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          _downloadPdf(doc);
                        },
                        icon: const Icon(Icons.picture_as_pdf_rounded),
                        label: Text(isSwahili ? 'Pakua PDF' : 'Download PDF'),
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showPaymentDetail(BillingPayment payment, bool isSwahili, bool isDarkMode) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              margin: const EdgeInsets.only(bottom: 16),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: isDarkMode ? Colors.white24 : Colors.grey[300],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Text(
              isSwahili ? 'Maelezo ya Malipo' : 'Payment Details',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 16),
            _DetailRow(
              label: isSwahili ? 'Nambari' : 'Payment No.',
              value: payment.paymentNumber ?? '—',
              isDarkMode: isDarkMode,
            ),
            if (payment.invoiceNumber != null)
              _DetailRow(
                label: isSwahili ? 'Ankara' : 'Invoice',
                value: payment.invoiceNumber!,
                isDarkMode: isDarkMode,
              ),
            _DetailRow(
              label: isSwahili ? 'Tarehe' : 'Date',
              value: _formatDate(payment.paymentDate),
              isDarkMode: isDarkMode,
            ),
            _DetailRow(
              label: isSwahili ? 'Njia' : 'Method',
              value: payment.paymentMethod ?? '—',
              isDarkMode: isDarkMode,
            ),
            if (payment.referenceNumber != null)
              _DetailRow(
                label: isSwahili ? 'Rejea' : 'Reference',
                value: payment.referenceNumber!,
                isDarkMode: isDarkMode,
              ),
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.success.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                children: [
                  Text(
                    isSwahili ? 'Kiasi' : 'Amount',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _formatCurrency(payment.amount),
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: AppColors.success,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  // ─── Build ─────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final billingState = ref.watch(clientBillingProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => Scaffold.of(context).openDrawer(),
        ),
        title: Text(isSwahili ? 'Ankara' : 'Billing & Invoices'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(clientBillingProvider.notifier).fetchBilling(),
        child: billingState.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _buildErrorView(error, isSwahili),
          data: (data) => _buildContent(context, data, isSwahili, isDarkMode),
        ),
      ),
    );
  }

  Widget _buildErrorView(Object error, bool isSwahili) {
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
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: () =>
                ref.read(clientBillingProvider.notifier).fetchBilling(),
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }

  Widget _buildContent(
    BuildContext context,
    ClientBillingData data,
    bool isSwahili,
    bool isDarkMode,
  ) {
    final payments = data.allPayments;

    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili
                ? 'Ankara, nukuu na malipo yako yote'
                : 'All your invoices, quotations, and payments',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: AppColors.textSecondary,
                ),
          ),
          const SizedBox(height: 16),

          // Summary cards 2x2
          Row(
            children: [
              Expanded(
                child: _SummaryCard(
                  title: isSwahili ? 'Jumla Ankara' : 'Total Invoiced',
                  value: _formatCurrencyShort(data.totalInvoiced),
                  icon: Icons.receipt_long_rounded,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _SummaryCard(
                  title: isSwahili ? 'Jumla Limelipwa' : 'Total Paid',
                  value: _formatCurrencyShort(data.totalPaid),
                  icon: Icons.check_circle_rounded,
                  color: AppColors.success,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _SummaryCard(
                  title: isSwahili ? 'Deni' : 'Balance Due',
                  value: _formatCurrencyShort(data.balanceDue),
                  icon: Icons.warning_rounded,
                  color: AppColors.warning,
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _SummaryCard(
                  title: isSwahili ? 'Zilizochelewa' : 'Overdue',
                  value: '${data.overdueCount}',
                  subtitle: isSwahili ? 'ankara' : 'invoices',
                  icon: Icons.schedule_rounded,
                  color: AppColors.error,
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Invoices
          _BillingSection(
            title: isSwahili ? 'Ankara' : 'Invoices',
            icon: Icons.receipt_rounded,
            count: data.invoices.length,
            initiallyExpanded: true,
            isDarkMode: isDarkMode,
            children: data.invoices
                .map((doc) => _InvoiceCard(
                      doc: doc,
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                      formatCurrency: _formatCurrency,
                      formatDate: _formatDate,
                      onTap: () => _showDocumentDetail(doc, isSwahili, isDarkMode),
                      onDownload: () => _downloadPdf(doc),
                    ))
                .toList(),
          ),

          // Quotations
          if (data.quotes.isNotEmpty) ...[
            const SizedBox(height: 16),
            _BillingSection(
              title: isSwahili ? 'Nukuu' : 'Quotations',
              icon: Icons.request_quote_rounded,
              count: data.quotes.length,
              isDarkMode: isDarkMode,
              children: data.quotes
                  .map((doc) => _DocumentCard(
                        doc: doc,
                        dateLabel: isSwahili ? 'Halali hadi' : 'Valid until',
                        dateValue: _formatDate(doc.validUntilDate),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        formatCurrency: _formatCurrency,
                        formatDate: _formatDate,
                        onTap: () => _showDocumentDetail(doc, isSwahili, isDarkMode),
                        onDownload: () => _downloadPdf(doc),
                      ))
                  .toList(),
            ),
          ],

          // Proformas
          if (data.proformas.isNotEmpty) ...[
            const SizedBox(height: 16),
            _BillingSection(
              title: isSwahili ? 'Ankara za Awali' : 'Proforma Invoices',
              icon: Icons.description_rounded,
              count: data.proformas.length,
              isDarkMode: isDarkMode,
              children: data.proformas
                  .map((doc) => _DocumentCard(
                        doc: doc,
                        dateLabel: isSwahili ? 'Halali hadi' : 'Valid until',
                        dateValue: _formatDate(doc.validUntilDate),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        formatCurrency: _formatCurrency,
                        formatDate: _formatDate,
                        onTap: () => _showDocumentDetail(doc, isSwahili, isDarkMode),
                        onDownload: () => _downloadPdf(doc),
                      ))
                  .toList(),
            ),
          ],

          // Credit notes
          if (data.creditNotes.isNotEmpty) ...[
            const SizedBox(height: 16),
            _BillingSection(
              title: isSwahili ? 'Noti za Mkopo' : 'Credit Notes',
              icon: Icons.money_off_rounded,
              count: data.creditNotes.length,
              isDarkMode: isDarkMode,
              children: data.creditNotes
                  .map((doc) => _DocumentCard(
                        doc: doc,
                        dateLabel: isSwahili ? 'Tarehe' : 'Date',
                        dateValue: _formatDate(doc.issueDate),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        formatCurrency: _formatCurrency,
                        formatDate: _formatDate,
                        onTap: () => _showDocumentDetail(doc, isSwahili, isDarkMode),
                        onDownload: () => _downloadPdf(doc),
                      ))
                  .toList(),
            ),
          ],

          // Payments
          if (payments.isNotEmpty) ...[
            const SizedBox(height: 16),
            _BillingSection(
              title: isSwahili ? 'Malipo' : 'Payments',
              icon: Icons.payments_rounded,
              count: payments.length,
              isDarkMode: isDarkMode,
              children: payments
                  .map((p) => _PaymentCard(
                        payment: p,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        formatCurrency: _formatCurrency,
                        formatDate: _formatDate,
                        onTap: () => _showPaymentDetail(p, isSwahili, isDarkMode),
                      ))
                  .toList(),
            ),
          ],

          const SizedBox(height: 100),
        ],
      ),
    );
  }
}

// ═════════════════════════════════════════════════
// Reusable Widgets
// ═════════════════════════════════════════════════

// ─── Glass Container ─────────────────────────────

class _GlassContainer extends StatelessWidget {
  final Widget child;
  final bool isDarkMode;
  final EdgeInsetsGeometry? margin;

  const _GlassContainer({
    required this.child,
    required this.isDarkMode,
    this.margin,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.15)
              : Colors.white.withValues(alpha: 0.6),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.06),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
          child: Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: isDarkMode
                    ? [
                        Colors.white.withValues(alpha: 0.08),
                        Colors.white.withValues(alpha: 0.04),
                      ]
                    : [
                        Colors.white.withValues(alpha: 0.75),
                        Colors.white.withValues(alpha: 0.55),
                      ],
              ),
            ),
            child: child,
          ),
        ),
      ),
    );
  }
}

// ─── Summary Card ────────────────────────────────

class _SummaryCard extends StatelessWidget {
  final String title;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color color;
  final bool isDarkMode;

  const _SummaryCard({
    required this.title,
    required this.value,
    this.subtitle,
    required this.icon,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return _GlassContainer(
      isDarkMode: isDarkMode,
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    title.toUpperCase(),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 0.5,
                      color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Icon(icon, size: 18, color: color.withValues(alpha: 0.6)),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: color,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            if (subtitle != null) ...[
              const SizedBox(height: 2),
              Text(
                subtitle!,
                style: TextStyle(
                  fontSize: 12,
                  color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// ─── Billing Section (collapsible) ──────────────

class _BillingSection extends StatefulWidget {
  final String title;
  final IconData icon;
  final int count;
  final bool initiallyExpanded;
  final bool isDarkMode;
  final List<Widget> children;

  const _BillingSection({
    required this.title,
    required this.icon,
    required this.count,
    this.initiallyExpanded = false,
    required this.isDarkMode,
    required this.children,
  });

  @override
  State<_BillingSection> createState() => _BillingSectionState();
}

class _BillingSectionState extends State<_BillingSection> {
  late bool _expanded;

  @override
  void initState() {
    super.initState();
    _expanded = widget.initiallyExpanded;
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        GestureDetector(
          onTap: () => setState(() => _expanded = !_expanded),
          child: Row(
            children: [
              Icon(widget.icon, size: 20, color: AppColors.primary),
              const SizedBox(width: 8),
              Text(
                widget.title,
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: widget.isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${widget.count}',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const Spacer(),
              AnimatedRotation(
                turns: _expanded ? 0.5 : 0,
                duration: const Duration(milliseconds: 200),
                child: Icon(
                  Icons.keyboard_arrow_down_rounded,
                  color: widget.isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ),
        if (_expanded) ...[
          const SizedBox(height: 12),
          if (widget.children.isEmpty)
            Padding(
              padding: const EdgeInsets.only(left: 28),
              child: Text(
                'No items',
                style: TextStyle(
                  color: widget.isDarkMode ? Colors.white38 : AppColors.textHint,
                ),
              ),
            )
          else
            ...widget.children,
        ],
      ],
    );
  }
}

// ─── Status Badge ────────────────────────────────

Color _statusColor(String? status) {
  switch (status?.toLowerCase()) {
    case 'paid':
      return AppColors.success;
    case 'partial_paid':
    case 'partially_paid':
      return AppColors.warning;
    case 'pending':
    case 'sent':
      return AppColors.info;
    case 'overdue':
      return AppColors.error;
    case 'viewed':
      return const Color(0xFF9B59B6);
    case 'accepted':
      return AppColors.success;
    case 'rejected':
    case 'declined':
      return AppColors.error;
    default:
      return AppColors.draft;
  }
}

String _statusLabel(String? status) {
  if (status == null) return '';
  return status.replaceAll('_', ' ').toUpperCase();
}

Widget _buildStatusBadge(String? status) {
  final color = _statusColor(status);
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(8),
    ),
    child: Text(
      _statusLabel(status),
      style: TextStyle(
        fontSize: 10,
        fontWeight: FontWeight.w700,
        color: color,
        letterSpacing: 0.3,
      ),
    ),
  );
}

// ─── Invoice Card ────────────────────────────────

class _InvoiceCard extends StatelessWidget {
  final BillingDocument doc;
  final bool isSwahili;
  final bool isDarkMode;
  final String Function(double) formatCurrency;
  final String Function(String?) formatDate;
  final VoidCallback onTap;
  final VoidCallback onDownload;

  const _InvoiceCard({
    required this.doc,
    required this.isSwahili,
    required this.isDarkMode,
    required this.formatCurrency,
    required this.formatDate,
    required this.onTap,
    required this.onDownload,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: _GlassContainer(
        isDarkMode: isDarkMode,
        margin: const EdgeInsets.only(bottom: 10),
        child: Padding(
          padding: const EdgeInsets.all(14),
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
                          doc.documentNumber ?? '—',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                        if (doc.projectName != null)
                          Text(
                            doc.projectName!,
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppColors.textSecondary,
                            ),
                          ),
                      ],
                    ),
                  ),
                  _buildStatusBadge(
                    doc.isOverdue && doc.status != 'paid' ? 'overdue' : doc.status,
                  ),
                  const SizedBox(width: 8),
                  _DownloadButton(onPressed: onDownload),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  _InfoChip(
                    icon: Icons.calendar_today_rounded,
                    label: formatDate(doc.issueDate),
                    isDarkMode: isDarkMode,
                  ),
                  const SizedBox(width: 12),
                  _InfoChip(
                    icon: Icons.event_rounded,
                    label: '${isSwahili ? "Hadi" : "Due"} ${formatDate(doc.dueDate)}',
                    isDarkMode: isDarkMode,
                    isWarning: doc.isOverdue,
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: isDarkMode
                      ? Colors.white.withValues(alpha: 0.05)
                      : Colors.grey.withValues(alpha: 0.06),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: _AmountColumn(
                        label: isSwahili ? 'Kiasi' : 'Amount',
                        value: formatCurrency(doc.totalAmount),
                        color: isDarkMode ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    Expanded(
                      child: _AmountColumn(
                        label: isSwahili ? 'Limelipwa' : 'Paid',
                        value: formatCurrency(doc.paidAmount),
                        color: AppColors.success,
                      ),
                    ),
                    Expanded(
                      child: _AmountColumn(
                        label: isSwahili ? 'Deni' : 'Balance',
                        value: formatCurrency(doc.balanceAmount),
                        color: doc.balanceAmount > 0 ? AppColors.error : AppColors.success,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Generic Document Card ───────────────────────

class _DocumentCard extends StatelessWidget {
  final BillingDocument doc;
  final String dateLabel;
  final String dateValue;
  final bool isSwahili;
  final bool isDarkMode;
  final String Function(double) formatCurrency;
  final String Function(String?) formatDate;
  final VoidCallback onTap;
  final VoidCallback onDownload;

  const _DocumentCard({
    required this.doc,
    required this.dateLabel,
    required this.dateValue,
    required this.isSwahili,
    required this.isDarkMode,
    required this.formatCurrency,
    required this.formatDate,
    required this.onTap,
    required this.onDownload,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: _GlassContainer(
        isDarkMode: isDarkMode,
        margin: const EdgeInsets.only(bottom: 10),
        child: Padding(
          padding: const EdgeInsets.all(14),
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
                          doc.documentNumber ?? '—',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                        if (doc.projectName != null)
                          Text(
                            doc.projectName!,
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppColors.textSecondary,
                            ),
                          ),
                      ],
                    ),
                  ),
                  _buildStatusBadge(doc.status),
                  const SizedBox(width: 8),
                  _DownloadButton(onPressed: onDownload),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _InfoChip(
                    icon: Icons.calendar_today_rounded,
                    label: formatDate(doc.issueDate),
                    isDarkMode: isDarkMode,
                  ),
                  _InfoChip(
                    icon: Icons.event_rounded,
                    label: '$dateLabel: $dateValue',
                    isDarkMode: isDarkMode,
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                formatCurrency(doc.totalAmount),
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Payment Card ────────────────────────────────

class _PaymentCard extends StatelessWidget {
  final BillingPayment payment;
  final bool isSwahili;
  final bool isDarkMode;
  final String Function(double) formatCurrency;
  final String Function(String?) formatDate;
  final VoidCallback onTap;

  const _PaymentCard({
    required this.payment,
    required this.isSwahili,
    required this.isDarkMode,
    required this.formatCurrency,
    required this.formatDate,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: _GlassContainer(
        isDarkMode: isDarkMode,
        margin: const EdgeInsets.only(bottom: 10),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      payment.paymentNumber ?? '—',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                  ),
                  Text(
                    formatCurrency(payment.amount),
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: AppColors.success,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 12,
                runSpacing: 6,
                children: [
                  if (payment.invoiceNumber != null)
                    _InfoChip(
                      icon: Icons.receipt_rounded,
                      label: payment.invoiceNumber!,
                      isDarkMode: isDarkMode,
                    ),
                  _InfoChip(
                    icon: Icons.calendar_today_rounded,
                    label: formatDate(payment.paymentDate),
                    isDarkMode: isDarkMode,
                  ),
                  if (payment.paymentMethod != null)
                    _InfoChip(
                      icon: Icons.payment_rounded,
                      label: payment.paymentMethod!,
                      isDarkMode: isDarkMode,
                    ),
                ],
              ),
              if (payment.referenceNumber != null) ...[
                const SizedBox(height: 6),
                Text(
                  'Ref: ${payment.referenceNumber}',
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode ? Colors.white38 : AppColors.textSecondary,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Small Helpers ───────────────────────────────

class _DownloadButton extends StatelessWidget {
  final VoidCallback onPressed;

  const _DownloadButton({required this.onPressed});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onPressed,
      child: Container(
        padding: const EdgeInsets.all(6),
        decoration: BoxDecoration(
          color: AppColors.error.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: const Icon(
          Icons.picture_as_pdf_rounded,
          size: 18,
          color: AppColors.error,
        ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isDarkMode;
  final bool isWarning;

  const _InfoChip({
    required this.icon,
    required this.label,
    required this.isDarkMode,
    this.isWarning = false,
  });

  @override
  Widget build(BuildContext context) {
    final color = isWarning
        ? AppColors.error
        : (isDarkMode ? Colors.white54 : AppColors.textSecondary);
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 13, color: color),
        const SizedBox(width: 4),
        Text(label, style: TextStyle(fontSize: 11, color: color)),
      ],
    );
  }
}

class _AmountColumn extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _AmountColumn({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 10, color: AppColors.textSecondary),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: color,
          ),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ],
    );
  }
}

// ─── Detail Sheet Helpers ────────────────────────

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: valueColor ?? (isDarkMode ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AmountRow extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final bool isBold;

  const _AmountRow({
    required this.label,
    required this.value,
    required this.color,
    this.isBold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: isBold ? FontWeight.w600 : FontWeight.normal,
            color: AppColors.textSecondary,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: isBold ? 16 : 13,
            fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
            color: color,
          ),
        ),
      ],
    );
  }
}
