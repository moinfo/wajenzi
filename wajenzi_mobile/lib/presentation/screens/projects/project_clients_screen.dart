import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _clientSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _clientReferenceDataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-clients/reference-data');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

Future<dynamic> _getWithRetry(ApiClient api, String path) async {
  try {
    return await api.get(path);
  } on DioException catch (error) {
    final shouldRetry =
        error.response?.statusCode == null &&
        (error.type == DioExceptionType.connectionError ||
            error.type == DioExceptionType.connectionTimeout ||
            error.type == DioExceptionType.receiveTimeout ||
            error.type == DioExceptionType.unknown);

    if (!shouldRetry) rethrow;

    await Future.delayed(const Duration(milliseconds: 250));
    return api.get(path);
  }
}

final _clientsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await _getWithRetry(api, '/project-clients');

  final payload = response.data['data'];
  final collection = payload is Map<String, dynamic> ? payload : null;
  final items = collection?['data'] ?? payload;
  final meta =
      collection?['meta'] as Map<String, dynamic>? ??
      response.data['meta'] as Map<String, dynamic>? ??
      {};

  return {'items': items as List? ?? const [], 'meta': meta};
});

final _clientDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-clients/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class ProjectClientsScreen extends ConsumerWidget {
  const ProjectClientsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final clientsAsync = ref.watch(_clientsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final searchTerm = ref.watch(_clientSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Wateja wa Miradi' : 'Project Clients'),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showClientForm(context, ref),
          tooltip: isSwahili ? 'Ongeza Mteja' : 'Add Client',
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_clientsProvider);
        },
        child: clientsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_clientsProvider),
          ),
          data: (payload) {
            final allClients = (payload['items'] as List)
                .cast<Map<String, dynamic>>();
            final meta = payload['meta'] as Map<String, dynamic>? ?? {};
            final clients = searchTerm.isEmpty
                ? allClients
                : allClients.where((client) {
                    final haystack = [
                      client['document_number'],
                      client['first_name'],
                      client['last_name'],
                      client['full_name'],
                      client['email'],
                      client['phone_number'],
                      client['status'],
                      client['approval_status'],
                      client['approval_summary'],
                      client['projects_count'],
                      client['documents_count'],
                      client['last_login_at'],
                      client['portal_access_enabled'] == true ? 'active' : '',
                      client['has_account'] == true ? 'disabled' : 'no account',
                      (client['client_source'] as Map<String, dynamic>?)?['name'],
                    ].whereType<Object>().join(' ').toLowerCase();
                    return haystack.contains(searchTerm);
                  }).toList();

            if (clients.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  TextField(
                    onChanged: (value) =>
                        ref.read(_clientSearchProvider.notifier).state = value,
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.search_rounded),
                      hintText: isSwahili ? 'Search' : 'Search',
                      filled: true,
                      fillColor: isDarkMode
                          ? const Color(0xFF2A2A3E)
                          : Colors.white,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(
                          color: isDarkMode
                              ? Colors.white12
                              : Colors.grey.withValues(alpha: 0.15),
                        ),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide(
                          color: isDarkMode
                              ? Colors.white12
                              : Colors.grey.withValues(alpha: 0.15),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const SizedBox(height: 100),
                  Icon(
                    Icons.people_outline,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    allClients.isEmpty
                        ? (isSwahili ? 'Hakuna wateja' : 'No clients found')
                        : (isSwahili
                            ? 'Hakuna matokeo yanayolingana'
                            : 'No clients match your search'),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: isDarkMode
                          ? Colors.white54
                          : AppColors.textSecondary,
                    ),
                  ),
                  if (searchTerm.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    Center(
                      child: ElevatedButton.icon(
                        onPressed: () =>
                            ref.read(_clientSearchProvider.notifier).state = '',
                        icon: const Icon(Icons.arrow_back_rounded),
                        label: Text(isSwahili ? 'Rudi' : 'Back'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ],
              );
            }

            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: clients.length + 2,
              itemBuilder: (context, index) {
                if (index == 0) {
                  final total = meta['total'] ?? clients.length;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextField(
                          onChanged: (value) => ref
                              .read(_clientSearchProvider.notifier)
                              .state = value,
                          decoration: InputDecoration(
                            prefixIcon: const Icon(Icons.search_rounded),
                            hintText: isSwahili ? 'Search' : 'Search',
                            filled: true,
                            fillColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(
                                color: isDarkMode
                                    ? Colors.white12
                                    : Colors.grey.withValues(alpha: 0.15),
                              ),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: BorderSide(
                                color: isDarkMode
                                    ? Colors.white12
                                    : Colors.grey.withValues(alpha: 0.15),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          isSwahili
                              ? 'Jumla ya wateja: $total'
                              : 'Total clients: $total',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white70
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  );
                }

                if (index == clients.length + 1) {
                  return const SizedBox(height: 80);
                }

                final client = clients[index - 1];
                return _ClientCard(
                  client: client,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                  onEdit: () => _showClientForm(context, ref, client: client),
                  onDelete: () => _deleteClient(context, ref, client),
                  onTap: () => _showClientDetails(context, ref, client),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _showClientForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? client,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _ClientFormSheet(client: client, isNew: client == null),
    );

    if (result == true) {
      ref.invalidate(_clientsProvider);
    }
  }

  Future<void> _deleteClient(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> client,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa Mteja' : 'Delete Client'),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta ${client['first_name']} ${client['last_name']}?'
              : 'Are you sure you want to delete ${client['first_name']} ${client['last_name']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      await api.delete('/project-clients/${client['id']}');

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Umefutwa' : 'Deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }

      ref.invalidate(_clientsProvider);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showClientDetails(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> client,
  ) async {
    final isDarkMode = ref.read(isDarkModeProvider);
    Map<String, dynamic> detail = client;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/project-clients/${client['id']}');
      final data = response.data['data'];
      if (data is Map<String, dynamic>) {
        detail = data;
      }
    } catch (_) {}

    if (!context.mounted) return;
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _ProjectClientDetailSheet(
        initialDetail: detail,
        isDarkMode: isDarkMode,
      ),
    ).then((_) => ref.invalidate(_clientsProvider));
  }
}

class _ProjectClientDetailSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> initialDetail;
  final bool isDarkMode;

  const _ProjectClientDetailSheet({
    required this.initialDetail,
    required this.isDarkMode,
  });

  @override
  ConsumerState<_ProjectClientDetailSheet> createState() =>
      _ProjectClientDetailSheetState();
}

class _ProjectClientDetailSheetState
    extends ConsumerState<_ProjectClientDetailSheet> {
  late Map<String, dynamic> _detail;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _detail = widget.initialDetail;
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = widget.isDarkMode;
    final approvalFlow =
        _detail['approval_flow'] as Map<String, dynamic>? ?? const {};
    final isSwahili = ref.watch(isSwahiliProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
                decoration: BoxDecoration(
                  color: isDarkMode ? const Color(0xFF2A2A3E) : AppColors.primary,
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(24),
                  ),
                ),
                child: Column(
                  children: [
                    Center(
                      child: Container(
                        width: 42,
                        height: 4,
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.35),
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        IconButton(
                          onPressed: _busy ? null : () => Navigator.pop(context),
                          icon: const Icon(
                            Icons.arrow_back_rounded,
                            color: Colors.white,
                          ),
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                        ),
                        const Expanded(
                          child: Text(
                            'Project Client',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w700,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        const SizedBox(width: 48),
                      ],
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _PlainDetailRow(
                      label: 'Requested by :',
                      value: _detail['requested_by'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _PlainDetailRow(
                      label: 'Created Time :',
                      value: _detail['created_time'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 16),
                    _SectionHeader(title: 'Project Details', isDarkMode: isDarkMode),
                    const SizedBox(height: 12),
                    ...(((_detail['project_details'] as Map<String, dynamic>?) ?? {})
                        .entries
                        .map(
                          (entry) => _PlainDetailRow(
                            label: entry.key,
                            value: entry.value?.toString() ?? '-',
                            isDarkMode: isDarkMode,
                          ),
                        )),
                    const SizedBox(height: 16),
                    _SectionHeader(title: 'Approval Flow', isDarkMode: isDarkMode),
                    const SizedBox(height: 12),
                    _PlainDetailRow(
                      label: '',
                      value: approvalFlow['status_label'] as String? ?? 'In Progress',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _SectionHeader(title: 'Approvals', isDarkMode: isDarkMode),
                    const SizedBox(height: 12),
                    Text(
                      approvalFlow['message'] as String? ??
                          'This document is not yet submitted.',
                      style: TextStyle(
                        fontSize: 14,
                        color:
                            isDarkMode ? Colors.white70 : AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 16),
                    if (_busy)
                      const Padding(
                        padding: EdgeInsets.only(bottom: 12),
                        child: LinearProgressIndicator(),
                      ),
                    _buildActionButtons(context, approvalFlow, isSwahili),
                    if (((approvalFlow['steps'] as List?)?.isNotEmpty ?? false)) ...[
                      const SizedBox(height: 12),
                      ...((approvalFlow['steps'] as List)
                          .cast<Map<String, dynamic>>()
                          .map(
                            (step) => _ApprovalStepCard(
                              step: step,
                              isDarkMode: isDarkMode,
                            ),
                          )),
                    ],
                    if ((_detail['client_source'] as Map<String, dynamic>?)?['name'] !=
                        null) ...[
                      const SizedBox(height: 8),
                      _DetailRow(
                        label: isSwahili ? 'Chanzo' : 'Source',
                        value: (_detail['client_source']
                                as Map<String, dynamic>)['name']
                            as String,
                        isDarkMode: isDarkMode,
                        icon: Icons.source_outlined,
                      ),
                    ],
                    if (_detail['projects_count'] != null ||
                        _detail['documents_count'] != null) ...[
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          if (_detail['projects_count'] != null)
                            _CountBadge(
                              icon: Icons.folder_outlined,
                              count: (_detail['projects_count'] as num).toInt(),
                              label: isSwahili ? 'Miradi' : 'Projects',
                              color: const Color(0xFF3B82F6),
                            ),
                          if (_detail['documents_count'] != null) ...[
                            const SizedBox(width: 12),
                            _CountBadge(
                              icon: Icons.description_outlined,
                              count: (_detail['documents_count'] as num).toInt(),
                              label: isSwahili ? 'Hati' : 'Documents',
                              color: const Color(0xFF10B981),
                            ),
                          ],
                        ],
                      ),
                    ],
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

  Widget _buildActionButtons(
    BuildContext context,
    Map<String, dynamic> approvalFlow,
    bool isSwahili,
  ) {
    final actionButtons = <Widget>[];
    final nextAction = (approvalFlow['next_action'] as String? ?? 'APPROVE')
        .toUpperCase();
    final approveLabel = (approvalFlow['is_rejected'] == true)
        ? 'Re-Approve'
        : _titleCase(nextAction);

    if (approvalFlow['can_be_submitted'] == true) {
      actionButtons.add(
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _busy ? null : () => _performAction('submit'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.success,
              foregroundColor: Colors.white,
            ),
            child: const Text('Submit'),
          ),
        ),
      );
    }

    if (approvalFlow['can_be_approved'] == true) {
      actionButtons.add(
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            if (approvalFlow['can_be_discarded'] == true)
              OutlinedButton(
                onPressed: _busy ? null : () => _performAction('discard'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.error,
                ),
                child: const Text('Discard'),
              )
            else ...[
              if (approvalFlow['can_be_rejected'] == true)
                OutlinedButton(
                  onPressed: _busy ? null : () => _performAction('reject'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.error,
                  ),
                  child: const Text('Reject'),
                ),
              if (approvalFlow['can_be_returned'] == true)
                OutlinedButton(
                  onPressed: _busy ? null : () => _performAction('return'),
                  child: const Text('Return'),
                ),
            ],
            ElevatedButton(
              onPressed: _busy ? null : () => _performAction('approve'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.success,
                foregroundColor: Colors.white,
              ),
              child: Text(approveLabel),
            ),
          ],
        ),
      );
    }

    if (actionButtons.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: actionButtons
          .map((button) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: button,
              ))
          .toList(),
    );
  }

  Future<void> _performAction(String action) async {
    final id = (_detail['id'] as num?)?.toInt();
    if (id == null) return;

    String? comment;
    if (action == 'reject' || action == 'return') {
      comment = await _promptForComment(
        title: action == 'reject' ? 'Reject Request' : 'Return Request',
      );
      if (comment == null || comment.trim().isEmpty) return;
    } else if (action == 'approve' || action == 'discard') {
      comment = await _promptForComment(
        title: action == 'approve' ? 'Approval Comment' : 'Discard Request',
        required: false,
      );
      if (comment == null) return;
    }

    setState(() => _busy = true);

    try {
      final api = ref.read(apiClientProvider);
      late final response;
      switch (action) {
        case 'submit':
          response = await api.post('/project-clients/$id/submit');
          break;
        case 'approve':
          response = await api.post(
            '/project-clients/$id/approve',
            data: {'comment': comment},
          );
          break;
        case 'reject':
          response = await api.post(
            '/project-clients/$id/reject',
            data: {'comment': comment},
          );
          break;
        case 'return':
          response = await api.post(
            '/project-clients/$id/return',
            data: {'comment': comment},
          );
          break;
        case 'discard':
          response = await api.post(
            '/project-clients/$id/discard',
            data: {'comment': comment},
          );
          break;
        default:
          return;
      }

      final data = response.data['data'];
      if (data is Map<String, dynamic>) {
        setState(() => _detail = data);
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text((response.data['message'] ?? 'Success').toString()),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Future<String?> _promptForComment({
    required String title,
    bool required = true,
  }) async {
    final controller = TextEditingController();

    return showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          maxLines: 4,
          decoration: InputDecoration(
            hintText: required ? 'Comment is required' : 'Optional comment',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, null),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              final value = controller.text.trim();
              if (required && value.isEmpty) {
                return;
              }
              Navigator.pop(ctx, value);
            },
            child: const Text('Continue'),
          ),
        ],
      ),
    );
  }

  String _titleCase(String value) {
    final normalized = value.trim().toLowerCase();
    if (normalized.isEmpty) return 'Approve';
    return normalized[0].toUpperCase() + normalized.substring(1);
  }
}

class _ClientFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? client;
  final bool isNew;

  const _ClientFormSheet({this.client, required this.isNew});

  @override
  ConsumerState<_ClientFormSheet> createState() => _ClientFormSheetState();
}

class _ClientFormSheetState extends ConsumerState<_ClientFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _firstNameController;
  late final TextEditingController _lastNameController;
  late final TextEditingController _emailController;
  late final TextEditingController _phoneController;
  late final TextEditingController _addressController;
  late final TextEditingController _idNumberController;
  late final TextEditingController _passwordController;
  late final TextEditingController _confirmPasswordController;
  int? _clientSourceId;
  bool _portalAccessEnabled = true;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _firstNameController = TextEditingController(
      text: widget.client?['first_name'] as String? ?? '',
    );
    _lastNameController = TextEditingController(
      text: widget.client?['last_name'] as String? ?? '',
    );
    _emailController = TextEditingController(
      text: widget.client?['email'] as String? ?? '',
    );
    _phoneController = TextEditingController(
      text: widget.client?['phone_number'] as String? ?? '',
    );
    _addressController = TextEditingController(
      text: widget.client?['address'] as String? ?? '',
    );
    _idNumberController = TextEditingController(
      text: widget.client?['identification_number'] as String? ?? '',
    );
    _passwordController = TextEditingController();
    _confirmPasswordController = TextEditingController();
    _clientSourceId =
        (widget.client?['client_source'] as Map<String, dynamic>?)?['id'] as int?;
    _portalAccessEnabled = _asBool(widget.client?['portal_access_enabled'], true);
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _idNumberController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final referenceAsync = ref.watch(_clientReferenceDataProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        top: false,
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
                  decoration: BoxDecoration(
                    color: isDarkMode ? const Color(0xFF2A2A3E) : AppColors.primary,
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(24),
                    ),
                  ),
                  child: Column(
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.35),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Row(
                        children: [
                          IconButton(
                            onPressed: _loading ? null : () => Navigator.pop(context),
                            icon: const Icon(
                              Icons.arrow_back_rounded,
                              color: Colors.white,
                            ),
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                          Expanded(
                            child: Text(
                              widget.isNew ? 'New Client' : 'Edit Client',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                            ),
                          ),
                          const SizedBox(width: 48),
                        ],
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: referenceAsync.when(
                loading: () => [
                  const SizedBox(height: 32),
                  const Center(child: CircularProgressIndicator()),
                  const SizedBox(height: 32),
                ],
                error: (_, __) => [
                  const SizedBox(height: 16),
                  const Text('Failed to load client sources'),
                ],
                data: (referenceData) {
                  final clientSources =
                      (referenceData['client_sources'] as List? ?? const [])
                          .cast<Map<String, dynamic>>();
                  return [
                TextFormField(
                  controller: _firstNameController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina la Kwanza *' : 'First Name *',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  validator: (v) => (v == null || v.isEmpty)
                      ? (isSwahili ? 'Kituraisha kinahitajika' : 'Required')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _lastNameController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Jina la Mwisho *' : 'Last Name *',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  validator: (v) => (v == null || v.isEmpty)
                      ? (isSwahili ? 'Kituraisha kinahitajika' : 'Required')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _phoneController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Nambari ya Simu *' : 'Phone *',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  keyboardType: TextInputType.phone,
                  validator: (v) => (v == null || v.isEmpty)
                      ? (isSwahili ? 'Kituraisha kinahitajika' : 'Required')
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _emailController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Barua Pepe' : 'Email',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  keyboardType: TextInputType.emailAddress,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _addressController,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Anwani' : 'Address',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  maxLines: 2,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _idNumberController,
                  decoration: InputDecoration(
                    labelText: isSwahili
                        ? 'Nambari ya Kitambulisho'
                        : 'Identification Number',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value: _clientSourceId,
                  decoration: InputDecoration(
                    labelText: isSwahili ? 'Chanzo cha Mteja *' : 'Client Source *',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  items: clientSources
                      .map(
                        (source) => DropdownMenuItem<int>(
                          value: (source['id'] as num).toInt(),
                          child: Text(source['name']?.toString() ?? '-'),
                        ),
                      )
                      .toList(),
                  onChanged: (value) => setState(() => _clientSourceId = value),
                  validator: (value) => value == null
                      ? (isSwahili ? 'Required' : 'Required')
                      : null,
                ),
                const SizedBox(height: 24),
                Text(
                  'Client Portal Access',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _passwordController,
                  decoration: InputDecoration(
                    labelText: widget.isNew
                        ? 'Portal Password'
                        : 'Portal Password (leave blank to keep current)',
                    hintText: 'Enter password',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  obscureText: true,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _confirmPasswordController,
                  decoration: InputDecoration(
                    labelText: 'Confirm Password',
                    hintText: 'Confirm password',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
                  obscureText: true,
                  validator: (value) {
                    if (_passwordController.text.isNotEmpty &&
                        value != _passwordController.text) {
                      return isSwahili
                          ? 'Passwords hazifanani'
                          : 'Passwords do not match';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                SwitchListTile.adaptive(
                  value: _portalAccessEnabled,
                  onChanged: (value) =>
                      setState(() => _portalAccessEnabled = value),
                  title: const Text('Enable Portal Access'),
                  contentPadding: EdgeInsets.zero,
                ),
                const SizedBox(height: 32),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _loading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: _loading
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            widget.isNew
                                ? (isSwahili ? 'Hifadhi' : 'Save')
                                : (isSwahili ? 'Sasisha' : 'Update'),
                          ),
                  ),
                ),
                const SizedBox(height: 16),
                  ];
                },
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _loading = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'first_name': _firstNameController.text.trim(),
        'last_name': _lastNameController.text.trim(),
        'phone_number': _phoneController.text.trim(),
        'client_source_id': _clientSourceId,
        'portal_access_enabled': _portalAccessEnabled ? 1 : 0,
        'email': _emailController.text.trim().isEmpty
            ? null
            : _emailController.text.trim(),
        'address': _addressController.text.trim().isEmpty
            ? null
            : _addressController.text.trim(),
        'identification_number': _idNumberController.text.trim().isEmpty
            ? null
            : _idNumberController.text.trim(),
        'password': _passwordController.text.trim().isEmpty
            ? null
            : _passwordController.text.trim(),
        'password_confirmation': _confirmPasswordController.text.trim().isEmpty
            ? null
            : _confirmPasswordController.text.trim(),
      };

      if (widget.isNew) {
        await api.post('/project-clients', data: data);
      } else {
        await api.put('/project-clients/${widget.client!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  bool _asBool(Object? value, bool fallback) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) {
      final normalized = value.trim().toLowerCase();
      if (normalized == '1' || normalized == 'true' || normalized == 'yes') {
        return true;
      }
      if (normalized == '0' || normalized == 'false' || normalized == 'no') {
        return false;
      }
    }
    return fallback;
  }
}

class _ClientCard extends StatelessWidget {
  final Map<String, dynamic> client;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ClientCard({
    required this.client,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final fullName =
        client['full_name'] as String? ??
        '${client['first_name'] ?? ''} ${client['last_name'] ?? ''}'.trim();
    final status = client['status'] as String? ?? 'PENDING';
    final source =
        (client['client_source'] as Map<String, dynamic>?)?['name'] as String?;
    final projectsCount = client['projects_count']?.toString() ?? '0';
    final documentsCount = client['documents_count']?.toString() ?? '0';
    final documentNumber = client['document_number'] as String? ?? '-';
    final portalStatus = client['portal_access_enabled'] == true
        ? 'Active'
        : ((client['has_account'] == true)
            ? 'Disabled'
            : 'No Account');

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.person_rounded,
                  color: Color(0xFF3B82F6),
                ),
              ),
              const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      fullName.isNotEmpty ? fullName : '-',
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      documentNumber,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Wrap(
                      spacing: 8,
                      runSpacing: 6,
                      children: [
                        if (client['email'] != null)
                          _MiniBadge(
                            label: client['email'] as String,
                            color: const Color(0xFF3B82F6),
                          ),
                        _MiniBadge(
                          label: 'Projects $projectsCount',
                          color: const Color(0xFF10B981),
                        ),
                        _MiniBadge(
                          label: 'Documents $documentsCount',
                          color: const Color(0xFFF59E0B),
                        ),
                        if (source != null && source.isNotEmpty)
                          _MiniBadge(
                            label: source,
                            color: const Color(0xFF8B5CF6),
                          ),
                        _MiniBadge(
                          label: portalStatus,
                          color: const Color(0xFF06B6D4),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  _StatusBadge(status: status, isSwahili: isSwahili),
                  const SizedBox(height: 8),
                  if (client['phone_number'] != null)
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.phone_outlined,
                          size: 12,
                          color: AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          client['phone_number'] as String,
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  const SizedBox(height: 8),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'edit') {
                        onEdit();
                      } else if (value == 'delete') {
                        onDelete();
                      }
                    },
                    itemBuilder: (context) => const [
                      PopupMenuItem<String>(
                        value: 'edit',
                        child: Text('Edit'),
                      ),
                      PopupMenuItem<String>(
                        value: 'delete',
                        child: Text('Delete'),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _MiniBadge extends StatelessWidget {
  final String label;
  final Color color;

  const _MiniBadge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _StatusBadge({required this.status, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final color = switch (status.toUpperCase()) {
      'APPROVED' => AppColors.approved,
      'PENDING' => AppColors.pending,
      'REJECTED' => AppColors.rejected,
      'ACTIVE' => const Color(0xFF10B981),
      'INACTIVE' => const Color(0xFF6B7280),
      _ => AppColors.draft,
    };

    final label = switch (status.toUpperCase()) {
      'APPROVED' => isSwahili ? 'IMEDHINISHWA' : 'APPROVED',
      'PENDING' => isSwahili ? 'INASUBIRI' : 'PENDING',
      'REJECTED' => isSwahili ? 'IMEKATALIWA' : 'REJECTED',
      'ACTIVE' => isSwahili ? 'HAI' : 'ACTIVE',
      'INACTIVE' => isSwahili ? 'ISIYOHAI' : 'INACTIVE',
      _ => status.toUpperCase(),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w600,
          fontSize: 10,
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final IconData icon;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            icon,
            size: 18,
            color: isDarkMode ? Colors.white38 : AppColors.textHint,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode
                        ? Colors.white54
                        : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PlainDetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _PlainDetailRow({
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
          if (label.isNotEmpty)
            SizedBox(
              width: 120,
              child: Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
            ),
          Expanded(
            child: Text(
              value,
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

class _SectionHeader extends StatelessWidget {
  final String title;
  final bool isDarkMode;

  const _SectionHeader({required this.title, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w700,
        color: isDarkMode ? Colors.white : AppColors.textPrimary,
      ),
    );
  }
}

class _ApprovalStepCard extends StatelessWidget {
  final Map<String, dynamic> step;
  final bool isDarkMode;

  const _ApprovalStepCard({
    required this.step,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode
            ? Colors.white.withValues(alpha: 0.05)
            : Colors.grey.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${step['approver_name'] ?? '-'}',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            '${step['role_name'] ?? '-'}',
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
            ),
          ),
          if ((step['date']?.toString().isNotEmpty ?? false)) ...[
            const SizedBox(height: 4),
            Text(
              '${step['date']}',
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _CountBadge extends StatelessWidget {
  final IconData icon;
  final int count;
  final String label;
  final Color color;

  const _CountBadge({
    required this.icon,
    required this.count,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 6),
          Text(
            '$count',
            style: TextStyle(
              fontWeight: FontWeight.w700,
              color: color,
              fontSize: 14,
            ),
          ),
          const SizedBox(width: 4),
          Text(
            label,
            style: TextStyle(color: color.withValues(alpha: 0.8), fontSize: 12),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
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

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}

String _formatDateTime(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy, HH:mm').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}
