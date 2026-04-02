import 'package:dio/dio.dart';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _pettyCashSearchProvider = StateProvider.autoDispose<String>((ref) => '');
final _pettyCashStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _pettyCashRefillRequestsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/petty-cash-refill-requests');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final items = data['data'] as List? ?? const [];

        return {
          'items': items
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
          'meta': data['meta'] is Map
              ? Map<String, dynamic>.from(data['meta'] as Map)
              : const <String, dynamic>{},
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return {
            'items': const <Map<String, dynamic>>[],
            'meta': const <String, dynamic>{},
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _pettyCashRefillRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get(
          '/petty-cash-refill-requests/reference-data',
        );
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        return data['data'] is Map
            ? Map<String, dynamic>.from(data['data'] as Map)
            : const <String, dynamic>{};
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return const <String, dynamic>{};
        }
        rethrow;
      }
    });

class PettyCashRefillRequestsScreen extends ConsumerStatefulWidget {
  const PettyCashRefillRequestsScreen({super.key});

  @override
  ConsumerState<PettyCashRefillRequestsScreen> createState() =>
      _PettyCashRefillRequestsScreenState();
}

class _PettyCashRefillRequestsScreenState
    extends ConsumerState<PettyCashRefillRequestsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final requestsAsync = ref.watch(_pettyCashRefillRequestsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_pettyCashSearchProvider).trim().toLowerCase();
    final statusFilter = ref.watch(_pettyCashStatusFilterProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Petty Cash Refill' : 'Petty Cash Refill'),
      ),
      floatingActionButton: requestsAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: isSwahili ? 'Ongeza' : 'Add',
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza' : 'Add',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_pettyCashRefillRequestsProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) =>
                          ref.read(_pettyCashSearchProvider.notifier).state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta ombi...'
                            : 'Search requests...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _pettyCashSearchProvider.notifier,
                                            )
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _StatusChip(
                            label: isSwahili ? 'Zote' : 'All',
                            isSelected: statusFilter == null,
                            onTap: () =>
                                ref
                                        .read(
                                          _pettyCashStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null,
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'Pending',
                            isSelected: statusFilter == 'PENDING',
                            onTap: () =>
                                ref
                                        .read(
                                          _pettyCashStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'PENDING',
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'Approved',
                            isSelected: statusFilter == 'APPROVED',
                            onTap: () =>
                                ref
                                        .read(
                                          _pettyCashStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'APPROVED',
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: 'Rejected',
                            isSelected: statusFilter == 'REJECTED',
                            onTap: () =>
                                ref
                                        .read(
                                          _pettyCashStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    'REJECTED',
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            requestsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _ErrorState(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () =>
                      ref.invalidate(_pettyCashRefillRequestsProvider),
                ),
              ),
              data: (payload) {
                if (payload['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.account_balance_wallet_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Petty Cash Refill Requests haipatikani kwenye live API kwa sasa.'
                                  : 'Petty Cash Refill Requests is not available on the live API right now.',
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
                    ),
                  );
                }

                final items =
                    (payload['items'] as List?)
                        ?.whereType<Map<String, dynamic>>()
                        .toList() ??
                    const <Map<String, dynamic>>[];
                final meta = payload['meta'] is Map<String, dynamic>
                    ? payload['meta'] as Map<String, dynamic>
                    : const <String, dynamic>{};

                final filteredItems = items.where((item) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      item['document_number'] ?? '',
                      item['date'] ?? '',
                      item['chart_account_code'] ?? '',
                      item['chart_account_name'] ?? '',
                      item['requested_user_name'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  if (statusFilter != null && statusFilter.isNotEmpty) {
                    if ((item['status'] ?? '').toString().toUpperCase() !=
                        statusFilter.toUpperCase())
                      return false;
                  }
                  return true;
                }).toList();

                if (filteredItems.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.inbox_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            items.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna ombi'
                                      : 'No refill requests')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty || statusFilter != null) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () {
                                ref
                                        .read(_pettyCashSearchProvider.notifier)
                                        .state =
                                    '';
                                ref
                                        .read(
                                          _pettyCashStatusFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null;
                              },
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa vichujio' : 'Clear filters',
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      _SummaryCard(
                        meta: meta,
                        isDarkMode: isDarkMode,
                        isSwahili: isSwahili,
                      ),
                      const SizedBox(height: 16),
                      ...filteredItems.map(
                        (item) => _RequestCard(
                          item: item,
                          isSwahili: isSwahili,
                          isDarkMode: isDarkMode,
                          onView: () => _showDetails(context, ref, item),
                          onEdit: () => _openForm(context, ref, request: item),
                          onDelete: () => _deleteRequest(context, ref, item),
                        ),
                      ),
                    ]),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? request,
  }) async {
    final refs = await ref.read(_pettyCashRefillRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _PettyCashRefillFormSheet(refs: refs, request: request),
    );

    if (result == true) {
      ref.invalidate(_pettyCashRefillRequestsProvider);
      ref.invalidate(_pettyCashRefillRefsProvider);
    }
  }

  Future<void> _deleteRequest(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> request,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Ombi' : 'Delete Request'),
        content: Text(
          '${isSwahili ? 'Futa' : 'Delete'} ${request['document_number']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(
              isSwahili ? 'Futa' : 'Delete',
              style: const TextStyle(color: AppColors.error),
            ),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref
          .read(apiClientProvider)
          .delete('/petty-cash-refill-requests/${request['id']}');
      ref.invalidate(_pettyCashRefillRequestsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Ombi limefutwa' : 'Request deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> request,
  ) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => Container(
        height: 0.7 * MediaQuery.of(context).size.height,
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
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            request['document_number']?.toString() ?? '-',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _DetailRow(
                      label: isSwahili ? 'Tarehe' : 'Date',
                      value: request['date']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Akaunti' : 'Account',
                      value:
                          '${request['chart_account_code'] ?? '-'} - ${request['chart_account_name'] ?? '-'}',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Salio' : 'Balance',
                      value: vatMoney(request['balance']),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Kiasi cha Refill' : 'Refill Amount',
                      value: vatMoney(request['refill_amount']),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Aliyeomba' : 'Requested By',
                      value: request['requested_user_name']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Hali' : 'Status',
                      value: request['status']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    if ((request['file_url']?.toString().isNotEmpty ??
                        false)) ...[
                      const SizedBox(height: 16),
                      OutlinedButton.icon(
                        onPressed: () async {
                          final uri = Uri.tryParse(
                            request['file_url'].toString(),
                          );
                          final opened =
                              uri != null &&
                              await ExternalLauncherService.openUri(uri);
                          if (!opened && context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(
                                  isSwahili
                                      ? 'Imeshindikana kufungua faili'
                                      : 'Unable to open attachment',
                                ),
                              ),
                            );
                          }
                        },
                        icon: const Icon(Icons.attach_file),
                        label: Text(
                          isSwahili ? 'Fungua attachment' : 'Open attachment',
                        ),
                      ),
                    ],
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

class _StatusChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final bool isDarkMode;

  const _StatusChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.primary.withValues(alpha: 0.15)
              : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.white),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected
                ? AppColors.primary
                : Colors.grey.withValues(alpha: 0.3),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
            color: isSelected
                ? AppColors.primary
                : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isDarkMode ? Colors.white54 : AppColors.textHint,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: TextStyle(
                fontSize: 14,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _RequestCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _RequestCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final status = item['status']?.toString() ?? '-';
    final statusColor = _getStatusColor(status);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: InkWell(
        onTap: onView,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF59E0B).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.account_balance_wallet,
                  color: Color(0xFFF59E0B),
                  size: 24,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['document_number']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${item['date'] ?? '-'} • ${vatMoney(item['refill_amount'])}',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: statusColor.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        status,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: statusColor,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'view') {
                    onView();
                  } else if (value == 'edit') {
                    onEdit();
                  } else if (value == 'delete') {
                    onDelete();
                  }
                },
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'view',
                    child: Row(
                      children: [
                        const Icon(Icons.visibility, size: 20),
                        const SizedBox(width: 8),
                        Text(isSwahili ? 'Tazama' : 'View'),
                      ],
                    ),
                  ),
                  PopupMenuItem(
                    value: 'edit',
                    child: Row(
                      children: [
                        const Icon(Icons.edit, size: 20),
                        const SizedBox(width: 8),
                        Text(isSwahili ? 'Hariri' : 'Edit'),
                      ],
                    ),
                  ),
                  PopupMenuItem(
                    value: 'delete',
                    child: Row(
                      children: [
                        const Icon(
                          Icons.delete,
                          size: 20,
                          color: AppColors.error,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          isSwahili ? 'Futa' : 'Delete',
                          style: const TextStyle(color: AppColors.error),
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
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toUpperCase()) {
      case 'APPROVED':
        return AppColors.success;
      case 'REJECTED':
        return AppColors.error;
      case 'PENDING':
        return const Color(0xFFF59E0B);
      default:
        return AppColors.textSecondary;
    }
  }
}

class _SummaryCard extends StatelessWidget {
  final Map<String, dynamic> meta;
  final bool isDarkMode;
  final bool isSwahili;

  const _SummaryCard({
    required this.meta,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDarkMode ? Colors.white10 : Colors.black12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili ? 'Muhtasari' : 'Summary',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          _SummaryLine(
            label: isSwahili ? 'Salio la sasa' : 'Current Balance',
            value: vatMoney(meta['current_balance']),
            isDarkMode: isDarkMode,
          ),
          _SummaryLine(
            label: isSwahili ? 'Kikomo' : 'Limit',
            value: vatMoney(meta['petty_cash_limit']),
            isDarkMode: isDarkMode,
          ),
          _SummaryLine(
            label: isSwahili ? 'Refill inayopendekezwa' : 'Suggested Refill',
            value: vatMoney(meta['suggested_refill_amount']),
            isDarkMode: isDarkMode,
          ),
        ],
      ),
    );
  }
}

class _SummaryLine extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _SummaryLine({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _PettyCashRefillFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? request;

  const _PettyCashRefillFormSheet({required this.refs, this.request});

  @override
  ConsumerState<_PettyCashRefillFormSheet> createState() =>
      _PettyCashRefillFormSheetState();
}

class _PettyCashRefillFormSheetState
    extends ConsumerState<_PettyCashRefillFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _dateController;
  File? _file;
  bool _saving = false;
  int? _chartAccountId;

  @override
  void initState() {
    super.initState();
    final accounts = _toMaps(widget.refs['charts_accounts']);
    _chartAccountId =
        _toNullableInt(widget.request?['charts_account_id']) ??
        (accounts.isNotEmpty ? _toNullableInt(accounts.first['id']) : null);
    _dateController = TextEditingController(
      text: widget.request?['date']?.toString() ?? vatDateFmt(DateTime.now()),
    );
  }

  @override
  void dispose() {
    _dateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final accounts = _toMaps(widget.refs['charts_accounts']);
    final balance =
        widget.request?['balance'] ?? widget.refs['current_balance'];
    final refillAmount =
        widget.request?['refill_amount'] ??
        widget.refs['suggested_refill_amount'];

    final bgColor = isDarkMode ? const Color(0xFF1A2332) : Colors.white;
    final inputBg = isDarkMode ? const Color(0xFF0F1923) : Colors.grey[100];
    final textColor = isDarkMode ? Colors.white : AppColors.textPrimary;

    InputDecoration inputStyle(String label) => InputDecoration(
      labelText: label,
      filled: true,
      fillColor: inputBg,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
    );

    return Container(
      height: 0.85 * MediaQuery.of(context).size.height,
      decoration: BoxDecoration(
        color: bgColor,
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
              child: Form(
                key: _formKey,
                child: ListView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    MediaQuery.of(context).viewInsets.bottom + 24,
                  ),
                  children: [
                    Text(
                      widget.request == null
                          ? (isSwahili
                                ? 'Ombi Jipya la Refill'
                                : 'New Refill Request')
                          : (isSwahili
                                ? 'Hariri Ombi la Refill'
                                : 'Edit Refill Request'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value:
                          accounts.any(
                            (item) => _toInt(item['id']) == _chartAccountId,
                          )
                          ? _chartAccountId
                          : null,
                      validator: (value) => value == null
                          ? (isSwahili ? 'Hitajiwa' : 'Required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Akaunti *' : 'Account *',
                      ),
                      dropdownColor: bgColor,
                      items: accounts
                          .map(
                            (item) => DropdownMenuItem<int>(
                              value: _toInt(item['id']),
                              child: Text(
                                '${item['code']} - ${item['account_name']}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) =>
                          setState(() => _chartAccountId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      initialValue: vatMoney(balance),
                      readOnly: true,
                      decoration: inputStyle(isSwahili ? 'Salio' : 'Balance'),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      initialValue: vatMoney(refillAmount),
                      readOnly: true,
                      decoration: inputStyle(
                        isSwahili ? 'Kiasi cha Refill' : 'Refill Amount',
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _dateController,
                      readOnly: true,
                      validator: (value) =>
                          value == null || value.trim().isEmpty
                          ? (isSwahili ? 'Hitajiwa' : 'Required')
                          : null,
                      decoration: inputStyle(isSwahili ? 'Tarehe *' : 'Date *')
                          .copyWith(
                            suffixIcon: const Icon(
                              Icons.calendar_today_outlined,
                            ),
                          ),
                      style: TextStyle(color: textColor),
                      onTap: () async {
                        final initial =
                            DateTime.tryParse(_dateController.text) ??
                            DateTime.now();
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: initial,
                          firstDate: DateTime(2020),
                          lastDate: DateTime(2100),
                        );
                        if (picked != null) {
                          _dateController.text = vatDateFmt(picked);
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    VatFilePicker(
                      isDark: isDarkMode,
                      isSwahili: isSwahili,
                      file: _file,
                      onPicked: (picked) => setState(() => _file = picked),
                    ),
                    if (_file == null &&
                        (widget.request?['file_url']?.toString().isNotEmpty ??
                            false)) ...[
                      const SizedBox(height: 8),
                      Text(
                        isSwahili
                            ? 'Attachment ya sasa ipo'
                            : 'Current attachment exists',
                        style: TextStyle(
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                    ],
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _saving
                          ? null
                          : () => _submit(balance, refillAmount),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              widget.request == null
                                  ? (isSwahili ? 'Hifadhi' : 'Save')
                                  : (isSwahili ? 'Sasisha' : 'Update'),
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

  Future<void> _submit(dynamic balance, dynamic refillAmount) async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final formData = await vatBuildFormData({
        'charts_account_id': _chartAccountId,
        'date': _dateController.text.trim(),
        'balance': balance,
        'refill_amount': refillAmount,
      }, _file);

      if (widget.request == null) {
        await api.post('/petty-cash-refill-requests', data: formData);
      } else {
        await api.post(
          '/petty-cash-refill-requests/${widget.request!['id']}',
          data: formData,
        );
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider)),
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _ErrorState extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ErrorState({
    required this.isSwahili,
    required this.message,
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

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}
