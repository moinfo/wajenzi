import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _billingStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _billingTypeProvider = StateProvider.autoDispose<String?>((ref) => null);

Future<dynamic> _getWithRetry(
  ApiClient api,
  String path, {
  Map<String, dynamic>? queryParameters,
}) async {
  try {
    return await api.get(path, queryParameters: queryParameters);
  } on DioException catch (error) {
    final shouldRetry =
        error.response?.statusCode == null &&
        (error.type == DioExceptionType.connectionError ||
            error.type == DioExceptionType.connectionTimeout ||
            error.type == DioExceptionType.receiveTimeout ||
            error.type == DioExceptionType.unknown);

    if (!shouldRetry) rethrow;

    await Future.delayed(const Duration(milliseconds: 250));
    return api.get(path, queryParameters: queryParameters);
  }
}

final _billingDocumentsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final status = ref.watch(_billingStatusProvider);
      final type = ref.watch(_billingTypeProvider);
      final response = await _getWithRetry(
        api,
        '/billing/documents',
        queryParameters: {
          if (status != null && status.isNotEmpty) 'status': status,
          if (type != null && type.isNotEmpty) 'document_type': type,
        },
      );
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return {
        'items': (data['data'] as List? ?? const [])
            .whereType<Map>()
            .map((item) => Map<String, dynamic>.from(item))
            .toList(),
        'meta': data['meta'] is Map
            ? Map<String, dynamic>.from(data['meta'] as Map)
            : const <String, dynamic>{},
      };
    });

final _billingDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/billing/documents/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

final _billingPaymentsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await _getWithRetry(api, '/billing/payments');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return {
        'items': (data['data'] as List? ?? const [])
            .whereType<Map>()
            .map((item) => Map<String, dynamic>.from(item))
            .toList(),
        'meta': data['meta'] is Map
            ? Map<String, dynamic>.from(data['meta'] as Map)
            : const <String, dynamic>{},
      };
    });

final _billingClientsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await _getWithRetry(api, '/billing/clients');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _billingDashboardProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await _getWithRetry(api, '/billing/dashboard');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

class StaffBillingScreen extends ConsumerStatefulWidget {
  const StaffBillingScreen({super.key});

  @override
  ConsumerState<StaffBillingScreen> createState() => _StaffBillingScreenState();
}

class _StaffBillingScreenState extends ConsumerState<StaffBillingScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);

    return DefaultTabController(
      length: 4,
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            icon: const Icon(Icons.menu_rounded),
            onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
          ),
          title: Text(isSwahili ? 'Ankara' : 'Billing'),
          bottom: TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            labelColor: Colors.white,
            unselectedLabelColor: Colors.white70,
            indicatorColor: Colors.white,
            indicatorWeight: 3,
            labelStyle: const TextStyle(
              fontWeight: FontWeight.w700,
              fontSize: 14,
            ),
            unselectedLabelStyle: const TextStyle(
              fontWeight: FontWeight.w500,
              fontSize: 14,
            ),
            tabs: [
              Tab(text: isSwahili ? 'Dashibodi' : 'Overview'),
              Tab(text: isSwahili ? 'Nyaraka' : 'Documents'),
              Tab(text: isSwahili ? 'Malipo' : 'Payments'),
              Tab(text: isSwahili ? 'Wateja' : 'Clients'),
            ],
          ),
        ),
        body: const TabBarView(
          children: [
            _DashboardTab(),
            _DocumentsTab(),
            _PaymentsTab(),
            _ClientsTab(),
          ],
        ),
      ),
    );
  }
}

class _DashboardTab extends ConsumerWidget {
  const _DashboardTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardAsync = ref.watch(_billingDashboardProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return RefreshIndicator(
      onRefresh: () async => ref.invalidate(_billingDashboardProvider),
      child: dashboardAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: vatErrorMessage(e, isSwahili: isSwahili),
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_billingDashboardProvider),
        ),
        data: (data) {
          final metrics = data['metrics'] as Map<String, dynamic>? ?? const {};
          final recentInvoices = (data['recent_invoices'] as List? ?? const [])
              .cast<Map<String, dynamic>>();
          final overdueInvoices =
              (data['overdue_invoices'] as List? ?? const [])
                  .cast<Map<String, dynamic>>();
          final recentPayments = (data['recent_payments'] as List? ?? const [])
              .cast<Map<String, dynamic>>();
          final statusBreakdown =
              (data['status_breakdown'] as List? ?? const [])
                  .cast<Map<String, dynamic>>();
          final monthlyRevenue = (data['monthly_revenue'] as List? ?? const [])
              .cast<Map<String, dynamic>>();

          return ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            children: [
              _MetricsGrid(
                metrics: metrics,
                isSwahili: isSwahili,
                isDarkMode: isDarkMode,
              ),
              const SizedBox(height: 16),
              _DashboardSection(
                title: isSwahili
                    ? 'Ankara za Hivi Karibuni'
                    : 'Recent Invoices',
                child: recentInvoices.isEmpty
                    ? _SectionEmpty(
                        label: isSwahili ? 'Hakuna ankara' : 'No invoices',
                      )
                    : Column(
                        children: recentInvoices
                            .map(
                              (item) => _MiniDocRow(
                                item: item,
                                isDarkMode: isDarkMode,
                              ),
                            )
                            .toList(),
                      ),
              ),
              const SizedBox(height: 16),
              _DashboardSection(
                title: isSwahili ? 'Ankara Zilizochelewa' : 'Overdue Invoices',
                child: overdueInvoices.isEmpty
                    ? _SectionEmpty(
                        label: isSwahili
                            ? 'Hakuna ankara zilizochelewa'
                            : 'No overdue invoices',
                      )
                    : Column(
                        children: overdueInvoices
                            .map(
                              (item) => _MiniDocRow(
                                item: item,
                                isDarkMode: isDarkMode,
                                overdue: true,
                              ),
                            )
                            .toList(),
                      ),
              ),
              const SizedBox(height: 16),
              _DashboardSection(
                title: isSwahili
                    ? 'Malipo ya Hivi Karibuni'
                    : 'Recent Payments',
                child: recentPayments.isEmpty
                    ? _SectionEmpty(
                        label: isSwahili ? 'Hakuna malipo' : 'No payments',
                      )
                    : Column(
                        children: recentPayments
                            .map(
                              (item) => _MiniPaymentRow(
                                item: item,
                                isDarkMode: isDarkMode,
                              ),
                            )
                            .toList(),
                      ),
              ),
              const SizedBox(height: 16),
              _DashboardSection(
                title: isSwahili
                    ? 'Hali ya Ankara'
                    : 'Invoice Status Breakdown',
                child: statusBreakdown.isEmpty
                    ? _SectionEmpty(
                        label: isSwahili ? 'Hakuna takwimu' : 'No breakdown',
                      )
                    : Column(
                        children: statusBreakdown
                            .map((item) => _StatusBreakdownRow(item: item))
                            .toList(),
                      ),
              ),
              const SizedBox(height: 16),
              _DashboardSection(
                title: isSwahili
                    ? 'Mwelekeo wa Mapato'
                    : 'Monthly Revenue Trend',
                child: monthlyRevenue.isEmpty
                    ? _SectionEmpty(
                        label: isSwahili ? 'Hakuna data' : 'No trend data',
                      )
                    : _RevenueTrend(
                        monthlyRevenue: monthlyRevenue,
                        isDarkMode: isDarkMode,
                      ),
              ),
              const SizedBox(height: 24),
            ],
          );
        },
      ),
    );
  }
}

class _DocumentsTab extends ConsumerWidget {
  const _DocumentsTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final docsAsync = ref.watch(_billingDocumentsProvider);
    final status = ref.watch(_billingStatusProvider);
    final type = ref.watch(_billingTypeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Column(
      children: [
        _DocumentFilters(
          selectedStatus: status,
          selectedType: type,
          isSwahili: isSwahili,
          onStatusChanged: (value) =>
              ref.read(_billingStatusProvider.notifier).state = value,
          onTypeChanged: (value) =>
              ref.read(_billingTypeProvider.notifier).state = value,
        ),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () async => ref.invalidate(_billingDocumentsProvider),
            child: docsAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (e, _) => _ErrorView(
                message: vatErrorMessage(e, isSwahili: isSwahili),
                isSwahili: isSwahili,
                onRetry: () => ref.invalidate(_billingDocumentsProvider),
              ),
              data: (payload) {
                final docs = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final meta =
                    payload['meta'] as Map<String, dynamic>? ?? const {};

                if (docs.isEmpty) {
                  return _EmptyView(
                    icon: Icons.receipt_long_outlined,
                    label: isSwahili
                        ? 'Hakuna nyaraka za billing'
                        : 'No billing documents',
                  );
                }
                return ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(16),
                  children: [
                    Text(
                      isSwahili
                          ? 'Jumla ya nyaraka: ${meta['total'] ?? docs.length}'
                          : 'Total documents: ${meta['total'] ?? docs.length}',
                      style: TextStyle(
                        color: isDarkMode
                            ? Colors.white70
                            : AppColors.textSecondary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    ...docs.map(
                      (doc) => _BillingCard(
                        doc: doc,
                        isDarkMode: isDarkMode,
                        onTap: () => _showBillingDetailSheet(
                          context,
                          ref,
                          _toInt(doc['id']),
                        ),
                      ),
                    ),
                    const SizedBox(height: 80),
                  ],
                );
              },
            ),
          ),
        ),
      ],
    );
  }
}

class _PaymentsTab extends ConsumerWidget {
  const _PaymentsTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final paymentsAsync = ref.watch(_billingPaymentsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return RefreshIndicator(
      onRefresh: () async => ref.invalidate(_billingPaymentsProvider),
      child: paymentsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: vatErrorMessage(e, isSwahili: isSwahili),
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_billingPaymentsProvider),
        ),
        data: (payload) {
          final items = (payload['items'] as List).cast<Map<String, dynamic>>();
          if (items.isEmpty) {
            return _EmptyView(
              icon: Icons.payments_outlined,
              label: isSwahili ? 'Hakuna malipo' : 'No billing payments',
            );
          }
          return ListView.builder(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            itemCount: items.length + 1,
            itemBuilder: (context, index) {
              if (index == items.length) return const SizedBox(height: 80);
              final payment = items[index];
              final doc =
                  payment['document'] as Map<String, dynamic>? ?? const {};
              final client =
                  payment['client'] as Map<String, dynamic>? ?? const {};
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: ListTile(
                  onTap: () => _showPaymentSheet(context, payment, isDarkMode),
                  contentPadding: const EdgeInsets.all(16),
                  leading: CircleAvatar(
                    backgroundColor: AppColors.success.withValues(alpha: 0.1),
                    child: const Icon(Icons.payments, color: AppColors.success),
                  ),
                  title: Text(
                    payment['payment_number']?.toString() ?? '-',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  subtitle: Text(
                    '${doc['document_number'] ?? '-'}\n${client['full_name'] ?? client['name'] ?? '-'}',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        _money(payment['amount']),
                        style: const TextStyle(
                          color: AppColors.success,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        payment['payment_method']?.toString() ?? '-',
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
              );
            },
          );
        },
      ),
    );
  }
}

class _ClientsTab extends ConsumerWidget {
  const _ClientsTab();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final clientsAsync = ref.watch(_billingClientsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return RefreshIndicator(
      onRefresh: () async => ref.invalidate(_billingClientsProvider),
      child: clientsAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => _ErrorView(
          message: vatErrorMessage(e, isSwahili: isSwahili),
          isSwahili: isSwahili,
          onRetry: () => ref.invalidate(_billingClientsProvider),
        ),
        data: (clients) {
          if (clients.isEmpty) {
            return _EmptyView(
              icon: Icons.people_outline,
              label: isSwahili ? 'Hakuna wateja' : 'No billing clients',
            );
          }
          return ListView.builder(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16),
            itemCount: clients.length + 1,
            itemBuilder: (context, index) {
              if (index == clients.length) return const SizedBox(height: 80);
              final client = clients[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: ListTile(
                  onTap: () => _showClientSheet(context, client, isDarkMode),
                  contentPadding: const EdgeInsets.all(16),
                  leading: CircleAvatar(
                    backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                    child: const Icon(
                      Icons.person_outline,
                      color: AppColors.primary,
                    ),
                  ),
                  title: Text(
                    client['full_name']?.toString() ?? '-',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  subtitle: Text(
                    '${client['email'] ?? '-'}\n${client['phone_number'] ?? '-'}',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}

class _DocumentFilters extends StatelessWidget {
  final String? selectedStatus;
  final String? selectedType;
  final bool isSwahili;
  final ValueChanged<String?> onStatusChanged;
  final ValueChanged<String?> onTypeChanged;

  const _DocumentFilters({
    required this.selectedStatus,
    required this.selectedType,
    required this.isSwahili,
    required this.onStatusChanged,
    required this.onTypeChanged,
  });

  @override
  Widget build(BuildContext context) {
    final statuses = <String?, String>{
      null: isSwahili ? 'Status Zote' : 'All Statuses',
      'draft': isSwahili ? 'Rasimu' : 'Draft',
      'sent': isSwahili ? 'Imetumwa' : 'Sent',
      'paid': isSwahili ? 'Imelipwa' : 'Paid',
      'overdue': isSwahili ? 'Imechelewa' : 'Overdue',
    };
    final types = <String?, String>{
      null: isSwahili ? 'Aina Zote' : 'All Types',
      'invoice': isSwahili ? 'Ankara' : 'Invoice',
      'quotation': isSwahili ? 'Bei' : 'Quotation',
      'proforma': 'Proforma',
    };

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      child: Column(
        children: [
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: statuses.entries
                  .map(
                    (entry) => Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ChoiceChip(
                        selected: selectedStatus == entry.key,
                        label: Text(entry.value),
                        onSelected: (_) => onStatusChanged(entry.key),
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),
          const SizedBox(height: 8),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: types.entries
                  .map(
                    (entry) => Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ChoiceChip(
                        selected: selectedType == entry.key,
                        label: Text(entry.value),
                        onSelected: (_) => onTypeChanged(entry.key),
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricsGrid extends StatelessWidget {
  final Map<String, dynamic> metrics;
  final bool isSwahili;
  final bool isDarkMode;

  const _MetricsGrid({
    required this.metrics,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final cards = [
      (
        icon: Icons.receipt_long_rounded,
        color: AppColors.primary,
        label: isSwahili ? 'Jumla ya Ankara' : 'Total Invoices',
        value: '${metrics['total_invoices'] ?? 0}',
      ),
      (
        icon: Icons.people_rounded,
        color: AppColors.success,
        label: isSwahili ? 'Wateja Hai' : 'Active Clients',
        value: '${metrics['total_clients'] ?? 0}',
      ),
      (
        icon: Icons.show_chart_rounded,
        color: AppColors.info,
        label: isSwahili ? 'Mapato' : 'Total Revenue',
        value: _money(metrics['total_revenue']),
      ),
      (
        icon: Icons.email_rounded,
        color: AppColors.warning,
        label: isSwahili ? 'Barua Zilizotumwa' : 'Emails Sent',
        value: '${metrics['total_emails_sent'] ?? 0}',
      ),
    ];

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: cards.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 1.25,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
      ),
      itemBuilder: (context, index) {
        final card = cards[index];
        return Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: card.color.withValues(alpha: 0.16)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: card.color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(card.icon, color: card.color),
              ),
              Text(
                card.value,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                ),
              ),
              Text(
                card.label,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 12,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _DashboardSection extends StatelessWidget {
  final String title;
  final Widget child;

  const _DashboardSection({required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).brightness == Brightness.dark
            ? const Color(0xFF1E1E30)
            : Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _SectionEmpty extends StatelessWidget {
  final String label;

  const _SectionEmpty({required this.label});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Text(
        label,
        style: const TextStyle(color: AppColors.textSecondary),
      ),
    );
  }
}

class _MiniDocRow extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool overdue;

  const _MiniDocRow({
    required this.item,
    required this.isDarkMode,
    this.overdue = false,
  });

  @override
  Widget build(BuildContext context) {
    final client = item['client'] as Map<String, dynamic>? ?? const {};
    final status = item['status']?.toString() ?? '';
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['document_number']?.toString() ?? '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  client['full_name']?.toString() ??
                      client['name']?.toString() ??
                      '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                _money(overdue ? item['balance_amount'] : item['total_amount']),
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  color: overdue ? AppColors.error : AppColors.primary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                status.replaceAll('_', ' '),
                style: TextStyle(fontSize: 12, color: _statusColor(status)),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MiniPaymentRow extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;

  const _MiniPaymentRow({required this.item, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final document = item['document'] as Map<String, dynamic>? ?? const {};
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['payment_number']?.toString() ?? '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 2),
                Text(
                  document['document_number']?.toString() ?? '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                _money(item['amount']),
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  color: AppColors.success,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                item['payment_method']?.toString() ?? '-',
                style: const TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 12,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatusBreakdownRow extends StatelessWidget {
  final Map<String, dynamic> item;

  const _StatusBreakdownRow({required this.item});

  @override
  Widget build(BuildContext context) {
    final status = item['status']?.toString() ?? '';
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              status.replaceAll('_', ' '),
              style: TextStyle(
                color: _statusColor(status),
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Text(
            '${item['count'] ?? 0}',
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(width: 12),
          Text(
            _money(item['amount']),
            style: const TextStyle(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

class _RevenueTrend extends StatelessWidget {
  final List<Map<String, dynamic>> monthlyRevenue;
  final bool isDarkMode;

  const _RevenueTrend({required this.monthlyRevenue, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    double maxRevenue = 0;
    for (final item in monthlyRevenue) {
      final value = (item['revenue'] as num?)?.toDouble() ?? 0;
      if (value > maxRevenue) maxRevenue = value;
    }
    if (maxRevenue <= 0) maxRevenue = 1;

    return SizedBox(
      height: 180,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: monthlyRevenue.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final item = monthlyRevenue[index];
          final revenue = (item['revenue'] as num?)?.toDouble() ?? 0;
          final heightFactor = revenue / maxRevenue;
          return SizedBox(
            width: 48,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                Text(
                  revenue >= 1000
                      ? '${(revenue / 1000).toStringAsFixed(0)}k'
                      : revenue.toStringAsFixed(0),
                  style: const TextStyle(
                    fontSize: 10,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 6),
                Container(
                  height: 110 * heightFactor.clamp(0.08, 1.0),
                  decoration: BoxDecoration(
                    color: AppColors.info,
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  item['month']?.toString().split(' ').first ?? '-',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _BillingCard extends StatelessWidget {
  final Map<String, dynamic> doc;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _BillingCard({
    required this.doc,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final docNumber = doc['document_number'] as String? ?? '';
    final client =
        doc['client'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    final clientName =
        client['full_name'] as String? ??
        client['name'] as String? ??
        client['first_name'] as String? ??
        '';
    final status = doc['status'] as String? ?? '';
    final totalAmount = doc['total_amount'];
    final dueDate = doc['due_date'] as String?;
    final type = doc['document_type'] as String? ?? '';

    final statusColor = _statusColor(status);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isDarkMode
                ? Colors.white.withValues(alpha: 0.08)
                : Colors.grey.withValues(alpha: 0.12),
          ),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                Icons.receipt_long_rounded,
                color: statusColor,
                size: 22,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    docNumber,
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '$type • ${clientName.isEmpty ? '-' : clientName}',
                    style: TextStyle(
                      fontSize: 12,
                      color: isDarkMode
                          ? Colors.white54
                          : AppColors.textSecondary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(
                        Icons.monetization_on_rounded,
                        size: 12,
                        color: Color(0xFFE67E22),
                      ),
                      const SizedBox(width: 4),
                      Flexible(
                        child: Text(
                          _money(totalAmount),
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFFE67E22),
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (dueDate != null && dueDate.isNotEmpty) ...[
                        const SizedBox(width: 8),
                        Flexible(
                          child: Text(
                            dueDate,
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    status.replaceAll('_', ' '),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: statusColor,
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Icon(
                  Icons.arrow_forward_ios_rounded,
                  size: 14,
                  color: isDarkMode ? Colors.white38 : AppColors.textHint,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

void _showBillingDetailSheet(BuildContext context, WidgetRef ref, int id) {
  if (id <= 0) return;

  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_billingDetailProvider(id));
        final isSwahili = ref.watch(isSwahiliProvider);
        return SafeArea(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.75,
            child: detailAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _ErrorView(
                message: vatErrorMessage(error, isSwahili: isSwahili),
                isSwahili: isSwahili,
                onRetry: () => ref.invalidate(_billingDetailProvider(id)),
              ),
              data: (detail) {
                final items =
                    (detail['items'] as List?)?.cast<Map<String, dynamic>>() ??
                    [];
                final payments =
                    (detail['payments'] as List?)
                        ?.cast<Map<String, dynamic>>() ??
                    [];
                final client = detail['client'] as Map<String, dynamic>?;
                final project = detail['project'] as Map<String, dynamic>?;

                return ListView(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                  children: [
                    Text(
                      detail['document_number'] as String? ??
                          'Billing Document',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _BillingDetailRow(
                      'Type',
                      detail['document_type'] as String? ?? 'N/A',
                    ),
                    _BillingDetailRow(
                      'Client',
                      client?['full_name'] as String? ??
                          client?['name'] as String? ??
                          'N/A',
                    ),
                    _BillingDetailRow(
                      'Project',
                      project?['project_name'] as String? ?? 'N/A',
                    ),
                    _BillingDetailRow(
                      'Status',
                      detail['status'] as String? ?? 'N/A',
                    ),
                    _BillingDetailRow(
                      'Issue Date',
                      detail['issue_date'] as String? ?? '-',
                    ),
                    _BillingDetailRow(
                      'Due Date',
                      detail['due_date'] as String? ?? '-',
                    ),
                    _BillingDetailRow(
                      'Total Amount',
                      _money(detail['total_amount']),
                    ),
                    _BillingDetailRow(
                      'Paid Amount',
                      _money(detail['paid_amount']),
                    ),
                    _BillingDetailRow(
                      'Balance',
                      _money(detail['balance_amount']),
                    ),
                    if ((detail['notes'] as String? ?? '').isNotEmpty)
                      _BillingDetailRow('Notes', detail['notes'] as String),
                    if (items.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const Text(
                        'Items',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 10),
                      ...items.map(
                        (item) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.grey.withValues(alpha: 0.06),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item['description'] as String? ?? 'Item',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${item['quantity'] ?? 0} ${item['unit'] ?? ''} - ${_money(item['total_amount'])}',
                                  style: const TextStyle(
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                    if (payments.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      const Text(
                        'Payments',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 10),
                      ...payments.map(
                        (payment) => Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: _BillingDetailRow(
                            payment['payment_method'] as String? ?? 'Payment',
                            _money(payment['amount']),
                          ),
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

void _showPaymentSheet(
  BuildContext context,
  Map<String, dynamic> payment,
  bool isDarkMode,
) {
  final document = payment['document'] as Map<String, dynamic>? ?? const {};
  final client = payment['client'] as Map<String, dynamic>? ?? const {};

  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.62,
      child: Container(
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            children: [
              const SizedBox(height: 12),
              Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: isDarkMode ? Colors.white24 : Colors.black12,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  children: [
                    Text(
                      payment['payment_number']?.toString() ?? 'Payment',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _BillingDetailRow('Amount', _money(payment['amount'])),
                    _BillingDetailRow(
                      'Date',
                      payment['payment_date']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Method',
                      payment['payment_method']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Status',
                      payment['status']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Document',
                      document['document_number']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Client',
                      client['full_name']?.toString() ??
                          client['name']?.toString() ??
                          '-',
                    ),
                    _BillingDetailRow(
                      'Reference',
                      payment['reference_number']?.toString().isNotEmpty == true
                          ? payment['reference_number'].toString()
                          : '-',
                    ),
                    _BillingDetailRow(
                      'Bank',
                      payment['bank_name']?.toString().isNotEmpty == true
                          ? payment['bank_name'].toString()
                          : '-',
                    ),
                    _BillingDetailRow(
                      'Transaction',
                      payment['transaction_id']?.toString().isNotEmpty == true
                          ? payment['transaction_id'].toString()
                          : '-',
                    ),
                    _BillingDetailRow(
                      'Notes',
                      payment['notes']?.toString().isNotEmpty == true
                          ? payment['notes'].toString()
                          : '-',
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

void _showClientSheet(
  BuildContext context,
  Map<String, dynamic> client,
  bool isDarkMode,
) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.62,
      child: Container(
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            children: [
              const SizedBox(height: 12),
              Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: isDarkMode ? Colors.white24 : Colors.black12,
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  children: [
                    Text(
                      client['full_name']?.toString() ?? 'Client',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _BillingDetailRow(
                      'Email',
                      client['email']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Phone',
                      client['phone_number']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Address',
                      client['address']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Status',
                      client['status']?.toString() ?? '-',
                    ),
                    _BillingDetailRow(
                      'Portal Access',
                      client['portal_access_enabled'] == true
                          ? 'Enabled'
                          : 'Disabled',
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class _BillingDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _BillingDetailRow(this.label, this.value);

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

class _EmptyView extends StatelessWidget {
  final IconData icon;
  final String label;

  const _EmptyView({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 120),
        Icon(icon, size: 56, color: Colors.grey[300]),
        const SizedBox(height: 12),
        Center(
          child: Text(
            label,
            style: const TextStyle(color: AppColors.textSecondary),
            textAlign: TextAlign.center,
          ),
        ),
      ],
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.message,
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
        Text(message, textAlign: TextAlign.center),
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

String _money(dynamic amount) {
  final val = amount is num
      ? amount.toDouble()
      : double.tryParse('$amount') ?? 0;
  final formatter = NumberFormat('#,##0.##', 'en');
  return 'TZS ${formatter.format(val)}';
}

Color _statusColor(String status) {
  switch (status.toLowerCase()) {
    case 'paid':
    case 'completed':
      return const Color(0xFF27AE60);
    case 'partial_paid':
    case 'sent':
      return const Color(0xFFF59E0B);
    case 'overdue':
    case 'void':
      return const Color(0xFFEF4444);
    default:
      return const Color(0xFF3B82F6);
  }
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}
