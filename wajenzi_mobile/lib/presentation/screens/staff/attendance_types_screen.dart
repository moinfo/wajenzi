import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _attendanceTypesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/attendance-types');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

class AttendanceTypesScreen extends ConsumerWidget {
  const AttendanceTypesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final itemsAsync = ref.watch(_attendanceTypesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Aina za Mahudhurio' : 'Attendance Types'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_attendanceTypesProvider),
        child: itemsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ErrorView(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_attendanceTypesProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.access_time_outlined,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna aina za mahudhurio'
                        : 'No attendance types found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: items.length + 1,
              itemBuilder: (context, index) {
                if (index == items.length) return const SizedBox(height: 90);
                final item = items[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    onTap: () => _showDetailSheet(context, item, isDarkMode),
                    contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(
                        Icons.schedule,
                        color: AppColors.primary,
                      ),
                    ),
                    title: Text(
                      item['name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${_trimText(item['description'], fallback: '-')}\n${item['users_count'] ?? 0} ${isSwahili ? 'watumiaji' : 'users'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDetailSheet(context, item, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, item: item);
                        } else if (value == 'delete') {
                          _deleteItem(context, ref, item);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(
                          value: 'view',
                          child: Text(isSwahili ? 'Tazama' : 'View'),
                        ),
                        PopupMenuItem(
                          value: 'edit',
                          child: Text(isSwahili ? 'Hariri' : 'Edit'),
                        ),
                        PopupMenuItem(
                          value: 'delete',
                          child: Text(isSwahili ? 'Futa' : 'Delete'),
                        ),
                      ],
                    ),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.82,
        child: _AttendanceTypeFormSheet(item: item),
      ),
    );
    if (result == true) ref.invalidate(_attendanceTypesProvider);
  }

  Future<void> _deleteItem(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title:
            Text(isSwahili ? 'Futa Aina ya Mahudhurio' : 'Delete Attendance Type'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta ${item['name']}?'
              : 'Delete ${item['name']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/attendance-types/${item['id']}');
      ref.invalidate(_attendanceTypesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili
                  ? 'Aina ya mahudhurio imefutwa'
                  : 'Attendance type deleted',
            ),
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
}

void _showDetailSheet(
  BuildContext context,
  Map<String, dynamic> item,
  bool isDarkMode,
) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.56,
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
                      item['name']?.toString() ?? 'Attendance Type',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _DetailRow(
                      'Description',
                      _trimText(item['description'], fallback: '-'),
                    ),
                    _DetailRow('Users Count', '${item['users_count'] ?? 0}'),
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

class _AttendanceTypeFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _AttendanceTypeFormSheet({this.item});

  @override
  ConsumerState<_AttendanceTypeFormSheet> createState() =>
      _AttendanceTypeFormSheetState();
}

class _AttendanceTypeFormSheetState
    extends ConsumerState<_AttendanceTypeFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController =
      TextEditingController(text: widget.item?['name']?.toString() ?? '');
  late final TextEditingController _descriptionController =
      TextEditingController(text: widget.item?['description']?.toString() ?? '');
  bool _saving = false;

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
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
                padding: EdgeInsets.fromLTRB(
                  20,
                  16,
                  20,
                  MediaQuery.of(context).viewInsets.bottom + 24,
                ),
                children: [
                  Text(
                    widget.item == null
                        ? (isSwahili
                            ? 'Aina Mpya ya Mahudhurio'
                            : 'New Attendance Type')
                        : (isSwahili
                            ? 'Hariri Aina ya Mahudhurio'
                            : 'Edit Attendance Type'),
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        _input(
                          _nameController,
                          isSwahili ? 'Jina *' : 'Name *',
                          isDarkMode,
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _descriptionController,
                          isSwahili ? 'Maelezo' : 'Description',
                          isDarkMode,
                          maxLines: 4,
                          required: false,
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
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
                                  widget.item == null
                                      ? (isSwahili ? 'Hifadhi' : 'Save')
                                      : (isSwahili ? 'Sasisha' : 'Update'),
                                ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _input(
    TextEditingController controller,
    String label,
    bool isDarkMode, {
    int maxLines = 1,
    bool required = true,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      validator: (value) {
        if (!required) return null;
        if (value == null || value.trim().isEmpty) return 'Required';
        return null;
      },
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        alignLabelWithHint: maxLines > 1,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final isSwahili = ref.read(isSwahiliProvider);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'name': _nameController.text.trim(),
        'description': _descriptionController.text.trim(),
      };

      if (widget.item == null) {
        await api.post('/attendance-types', data: data);
      } else {
        await api.put('/attendance-types/${widget.item!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(child: Text(value.isEmpty ? '-' : value)),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.isSwahili,
    required this.message,
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
                  ? 'Imeshindikana kupakia aina za mahudhurio'
                  : 'Failed to load attendance types',
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
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

String _trimText(dynamic value, {String fallback = ''}) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? fallback : text;
}
