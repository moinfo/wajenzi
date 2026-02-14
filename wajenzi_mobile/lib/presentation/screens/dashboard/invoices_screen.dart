import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../data/datasources/remote/staff_dashboard_api.dart';
import '../../providers/settings_provider.dart';
import '../../providers/staff_dashboard_provider.dart';

final _invoicesProvider =
    FutureProvider.autoDispose<List<DashboardInvoice>>((ref) async {
  final api = ref.watch(staffDashboardApiProvider);
  return api.fetchInvoices();
});

class InvoicesScreen extends ConsumerWidget {
  const InvoicesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final invoicesAsync = ref.watch(_invoicesProvider);
    final dashboardState = ref.watch(staffDashboardProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    final summary = dashboardState.valueOrNull?.invoicesSummary;

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ankara za Malipo' : 'Invoice Due Dates'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_invoicesProvider.future),
        child: invoicesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorBody(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_invoicesProvider),
          ),
          data: (invoices) => _Body(
            invoices: invoices,
            summary: summary,
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
          ),
        ),
      ),
    );
  }
}

// ─── Body ────────────────────────────────────────

class _Body extends StatelessWidget {
  final List<DashboardInvoice> invoices;
  final InvoicesSummary? summary;
  final bool isSwahili;
  final bool isDarkMode;

  const _Body({
    required this.invoices,
    this.summary,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        // ─── Status chips row ──────────────
        if (summary != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 20),
            child: Row(
              children: [
                _StatusChip(
                  count: summary!.overdue,
                  label: isSwahili ? 'ZILIZOCHELEWA' : 'OVERDUE',
                  icon: Icons.warning_rounded,
                  color: const Color(0xFFE74C3C),
                  bgColor: const Color(0xFFFDEDED),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.dueToday,
                  label: isSwahili ? 'LEO' : 'DUE TODAY',
                  icon: Icons.adjust_rounded,
                  color: const Color(0xFFE67E22),
                  bgColor: const Color(0xFFFEF5E7),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.upcoming,
                  label: isSwahili ? 'ZINAKUJA' : 'UPCOMING',
                  icon: Icons.calendar_today_rounded,
                  color: const Color(0xFF2980B9),
                  bgColor: const Color(0xFFEBF5FB),
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  count: summary!.paidThisMonth,
                  label: isSwahili ? 'ZIMELIPWA' : 'PAID',
                  icon: Icons.check_circle_rounded,
                  color: const Color(0xFF27AE60),
                  bgColor: const Color(0xFFEAFAF1),
                ),
              ],
            ),
          ),

        // ─── Invoice cards ────────────────
        if (invoices.isEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 60),
            child: Center(
              child: Column(
                children: [
                  Icon(Icons.receipt_long_outlined,
                      size: 56, color: Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna ankara kwa sasa'
                        : 'No invoices at the moment',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
          )
        else
          ...invoices.map((inv) => _InvoiceCard(
                invoice: inv,
                isDarkMode: isDarkMode,
              )),

        const SizedBox(height: 80),
      ],
    );
  }
}

// ─── Status Chip ─────────────────────────────────

class _StatusChip extends StatelessWidget {
  final int count;
  final String label;
  final IconData icon;
  final Color color;
  final Color bgColor;

  const _StatusChip({
    required this.count,
    required this.label,
    required this.icon,
    required this.color,
    required this.bgColor,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.2)),
        ),
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, size: 14, color: color),
                const SizedBox(width: 4),
                Text(
                  '$count',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 8,
                fontWeight: FontWeight.w600,
                color: color,
                letterSpacing: 0.5,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Invoice Card ────────────────────────────────

class _InvoiceCard extends StatelessWidget {
  final DashboardInvoice invoice;
  final bool isDarkMode;

  const _InvoiceCard({
    required this.invoice,
    required this.isDarkMode,
  });

  Color get _badgeColor {
    if (invoice.isOverdue) return const Color(0xFFE74C3C);
    // Due within 7 days → orange
    if (invoice.dueDate != null) {
      try {
        final due = DateTime.parse(invoice.dueDate!);
        final daysLeft = due.difference(DateTime.now()).inDays;
        if (daysLeft <= 7) return const Color(0xFFE67E22);
      } catch (_) {}
    }
    return const Color(0xFF27AE60);
  }

  Color get _borderColor {
    if (invoice.isOverdue) {
      return const Color(0xFFE74C3C).withValues(alpha: 0.3);
    }
    return Colors.grey.withValues(alpha: 0.15);
  }

  /// Returns a status chip label like "101D OVERDUE" or "5D LEFT"
  String get _dueLabel {
    if (invoice.isOverdue && invoice.daysOverdue > 0) {
      return '${invoice.daysOverdue}D OVERDUE';
    }
    if (invoice.dueDate != null) {
      try {
        final due = DateTime.parse(invoice.dueDate!);
        final daysLeft = due.difference(DateTime.now()).inDays;
        if (daysLeft >= 0) return '${daysLeft}D LEFT';
      } catch (_) {}
    }
    return '';
  }

  Color get _dueLabelColor {
    if (invoice.isOverdue) return const Color(0xFFE74C3C);
    if (invoice.dueDate != null) {
      try {
        final due = DateTime.parse(invoice.dueDate!);
        final daysLeft = due.difference(DateTime.now()).inDays;
        if (daysLeft <= 7) return const Color(0xFFE67E22);
      } catch (_) {}
    }
    return const Color(0xFF95A5A6);
  }

  IconData get _dueIcon {
    if (invoice.isOverdue) return Icons.error_rounded;
    return Icons.hourglass_bottom_rounded;
  }

  String _formatAmount(double amount) {
    final formatter = NumberFormat('#,##0', 'en');
    return 'TZS ${formatter.format(amount)}';
  }

  @override
  Widget build(BuildContext context) {
    DateTime? dueDate;
    try {
      if (invoice.dueDate != null) {
        dueDate = DateTime.parse(invoice.dueDate!);
      }
    } catch (_) {}

    final dueLabel = _dueLabel;
    final isPartial = invoice.status == 'partial_paid';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _borderColor, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Date badge ──
            if (dueDate != null)
              Container(
                width: 54,
                padding: const EdgeInsets.symmetric(vertical: 8),
                decoration: BoxDecoration(
                  color: _badgeColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Column(
                  children: [
                    Text(
                      '${dueDate.day}',
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: _badgeColor,
                        height: 1,
                      ),
                    ),
                    Text(
                      DateFormat('MMM').format(dueDate).toUpperCase(),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: _badgeColor,
                      ),
                    ),
                  ],
                ),
              ),
            if (dueDate != null) const SizedBox(width: 14),

            // ── Content ──
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Invoice number + due label
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          invoice.documentNumber ?? '',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      if (dueLabel.isNotEmpty)
                        Container(
                          margin: const EdgeInsets.only(left: 8),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: _dueLabelColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(_dueIcon, size: 12, color: _dueLabelColor),
                              const SizedBox(width: 4),
                              Text(
                                dueLabel,
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: _dueLabelColor,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),

                  // Client - Project
                  Text(
                    [
                      invoice.clientName ?? 'No Client',
                      if (invoice.projectName != null) invoice.projectName,
                    ].join(' - '),
                    style: TextStyle(
                      fontSize: 13,
                      color: isDarkMode
                          ? Colors.white60
                          : AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 6),

                  // Amount row + partial badge
                  Row(
                    children: [
                      Icon(
                        Icons.monetization_on_rounded,
                        size: 14,
                        color: isDarkMode
                            ? const Color(0xFFE67E22)
                            : const Color(0xFFE67E22),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        _formatAmount(invoice.balanceAmount),
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? const Color(0xFFE67E22)
                              : const Color(0xFFE67E22),
                        ),
                      ),
                      if (isPartial) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? Colors.white10
                                : Colors.grey[200],
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            'Partial',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w500,
                              color: isDarkMode
                                  ? Colors.white54
                                  : AppColors.textSecondary,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Error Body ──────────────────────────────────

class _ErrorBody extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorBody({
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
          error.toString(),
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
