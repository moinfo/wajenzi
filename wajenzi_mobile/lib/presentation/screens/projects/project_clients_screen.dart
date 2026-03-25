import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _clientsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/project-clients');

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

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Wateja' : 'Clients'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza Mteja' : 'Add Client',
            onPressed: () => _showClientForm(context, ref),
          ),
        ],
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
            final clients = (payload['items'] as List)
                .cast<Map<String, dynamic>>();
            final meta = payload['meta'] as Map<String, dynamic>? ?? {};

            if (clients.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.people_outline,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna wateja' : 'No clients found',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: isDarkMode
                          ? Colors.white54
                          : AppColors.textSecondary,
                    ),
                  ),
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
                    child: Text(
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
  ) {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        padding: const EdgeInsets.all(20),
        child: SafeArea(
          top: false,
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Container(
                      width: 56,
                      height: 56,
                      decoration: BoxDecoration(
                        color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: const Icon(
                        Icons.person_rounded,
                        color: Color(0xFF3B82F6),
                        size: 28,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            client['full_name'] as String? ??
                                '${client['first_name'] ?? ''} ${client['last_name'] ?? ''}'
                                    .trim(),
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w700,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                          const SizedBox(height: 4),
                          _StatusBadge(
                            status: client['status'] as String? ?? 'PENDING',
                            isSwahili: isSwahili,
                          ),
                        ],
                      ),
                    ),
                    PopupMenuButton<String>(
                      onSelected: (value) async {
                        Navigator.pop(ctx);
                        if (value == 'edit') {
                          await _showClientForm(context, ref, client: client);
                        } else if (value == 'delete') {
                          await _deleteClient(context, ref, client);
                        }
                      },
                      itemBuilder: (ctx) => [
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
                const SizedBox(height: 24),
                _DetailRow(
                  label: isSwahili ? 'Barua pepe' : 'Email',
                  value: client['email'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                  icon: Icons.email_outlined,
                ),
                _DetailRow(
                  label: isSwahili ? 'Barua pepe' : 'Email',
                  value: client['email'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                  icon: Icons.email_outlined,
                ),
                _DetailRow(
                  label: isSwahili ? 'Nambari ya simu' : 'Phone',
                  value: client['phone_number'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                  icon: Icons.phone_outlined,
                ),
                _DetailRow(
                  label: isSwahili ? 'Anwani' : 'Address',
                  value: client['address'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                  icon: Icons.location_on_outlined,
                ),
                _DetailRow(
                  label: isSwahili ? 'Nambari ya kitambulisho' : 'ID Number',
                  value: client['identification_number'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                  icon: Icons.badge_outlined,
                ),
                if ((client['client_source']
                        as Map<String, dynamic>?)?['name'] !=
                    null)
                  _DetailRow(
                    label: isSwahili ? 'Chanzo' : 'Source',
                    value:
                        (client['client_source']
                                as Map<String, dynamic>)['name']
                            as String,
                    isDarkMode: isDarkMode,
                    icon: Icons.source_outlined,
                  ),
                _DetailRow(
                  label: isSwahili ? 'Tarehe ya kujisajili' : 'Registered',
                  value: _formatDate(client['created_at'] as String?),
                  isDarkMode: isDarkMode,
                  icon: Icons.calendar_today_outlined,
                ),
                if (client['projects_count'] != null ||
                    client['documents_count'] != null) ...[
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      if (client['projects_count'] != null)
                        _CountBadge(
                          icon: Icons.folder_outlined,
                          count: client['projects_count'] as int,
                          label: isSwahili ? 'Miradi' : 'Projects',
                          color: const Color(0xFF3B82F6),
                        ),
                      if (client['documents_count'] != null) ...[
                        const SizedBox(width: 12),
                        _CountBadge(
                          icon: Icons.description_outlined,
                          count: client['documents_count'] as int,
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
        ),
      ),
    );
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
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _idNumberController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

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
          padding: const EdgeInsets.all(20),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  widget.isNew
                      ? (isSwahili ? 'Mteja Mpya' : 'New Client')
                      : (isSwahili ? 'Hariri Mteja' : 'Edit Client'),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 24),
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
                        : 'ID Number',
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                  ),
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
        'email': _emailController.text.trim().isEmpty
            ? null
            : _emailController.text.trim(),
        'address': _addressController.text.trim().isEmpty
            ? null
            : _addressController.text.trim(),
        'identification_number': _idNumberController.text.trim().isEmpty
            ? null
            : _idNumberController.text.trim(),
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
}

class _ClientCard extends StatelessWidget {
  final Map<String, dynamic> client;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _ClientCard({
    required this.client,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final fullName =
        client['full_name'] as String? ??
        '${client['first_name'] ?? ''} ${client['last_name'] ?? ''}'.trim();
    final status = client['status'] as String? ?? 'PENDING';

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
                    Row(
                      children: [
                        if (client['email'] != null) ...[
                          Icon(
                            Icons.email_outlined,
                            size: 14,
                            color: AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              client['email'] as String,
                              style: const TextStyle(
                                fontSize: 12,
                                color: AppColors.textSecondary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
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
                ],
              ),
            ],
          ),
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
