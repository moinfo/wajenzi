import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _usagesSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _chartAccountUsagesProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/chart-account-usages');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final items = data['data'] as List? ?? const [];
        return {
          'items': items
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return {
            'items': const <Map<String, dynamic>>[],
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _chartAccountUsageRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/chart-account-usages/reference-data');
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

String _usageErrorMessage(Object error, bool isSwahili) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) return message;
    }
  }
  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class ChartAccountUsagesScreen extends ConsumerStatefulWidget {
  const ChartAccountUsagesScreen({super.key});

  @override
  ConsumerState<ChartAccountUsagesScreen> createState() =>
      _ChartAccountUsagesScreenState();
}

class _ChartAccountUsagesScreenState
    extends ConsumerState<ChartAccountUsagesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final usagesAsync = ref.watch(_chartAccountUsagesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_usagesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Matumizi ya Akaunti' : 'Chart Account Usages'),
      ),
      floatingActionButton: usagesAsync.maybeWhen(
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
        onRefresh: () async => ref.invalidate(_chartAccountUsagesProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_usagesSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta matumizi...'
                        : 'Search usages...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref.read(_usagesSearchProvider.notifier).state =
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
              ),
            ),
            usagesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _UsagesErrorView(
                  message: _usageErrorMessage(error, isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_chartAccountUsagesProvider),
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
                              Icons.rule_folder_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Chart Account Usages haipatikani kwenye live API kwa sasa.'
                                  : 'Chart Account Usages is not available on the live API right now.',
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

                final usages = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final filteredUsages = search.isEmpty
                    ? usages
                    : usages.where((usage) {
                        final haystack = [
                          usage['name'] ?? '',
                          usage['chart_account_code'] ?? '',
                          usage['chart_account_name'] ?? '',
                          usage['description'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (filteredUsages.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.rule_folder_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            usages.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna matumizi'
                                      : 'No usages found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () =>
                                  ref
                                          .read(_usagesSearchProvider.notifier)
                                          .state =
                                      '',
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa utafutaji' : 'Clear search',
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
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final usage = filteredUsages[index];
                      return _UsageCard(
                        usage: usage,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onView: () =>
                            _showDetails(context, usage, isSwahili, isDarkMode),
                        onEdit: () => _openForm(context, ref, usage: usage),
                        onDelete: () => _deleteUsage(context, ref, usage),
                      );
                    }, childCount: filteredUsages.length),
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
    Map<String, dynamic>? usage,
  }) async {
    final refs = await ref.read(_chartAccountUsageRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _ChartAccountUsageFormSheet(refs: refs, usage: usage),
    );
    if (result == true) ref.invalidate(_chartAccountUsagesProvider);
  }

  Future<void> _deleteUsage(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> usage,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Matumizi' : 'Delete Usage'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta ${usage['name']}?'
              : 'Delete ${usage['name']}?',
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
          .delete('/chart-account-usages/${usage['id']}');
      ref.invalidate(_chartAccountUsagesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Matumizi yamefutwa' : 'Usage deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_usageErrorMessage(error, isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(
    BuildContext context,
    Map<String, dynamic> usage,
    bool isSwahili,
    bool isDarkMode,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => Container(
        height: 0.55 * MediaQuery.of(context).size.height,
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
                            usage['name']?.toString() ?? '-',
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
                      label: isSwahili ? 'Jina' : 'Name',
                      value: usage['name']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Akaunti ya Chati' : 'Chart Account',
                      value:
                          '${usage['chart_account_code'] ?? '-'} - ${usage['chart_account_name'] ?? '-'}',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: isSwahili ? 'Maelezo' : 'Description',
                      value: usage['description']?.toString() ?? '-',
                      isDarkMode: isDarkMode,
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

class _UsageCard extends StatelessWidget {
  final Map<String, dynamic> usage;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _UsageCard({
    required this.usage,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
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
                  color: const Color(0xFF8B5CF6).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.rule,
                  color: Color(0xFF8B5CF6),
                  size: 24,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      usage['name']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${usage['chart_account_code'] ?? '-'} - ${usage['chart_account_name'] ?? '-'}',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (usage['description'] != null &&
                        usage['description'].toString().isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        usage['description'].toString(),
                        style: TextStyle(
                          fontSize: 11,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
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
}

class _ChartAccountUsageFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? usage;

  const _ChartAccountUsageFormSheet({required this.refs, this.usage});

  @override
  ConsumerState<_ChartAccountUsageFormSheet> createState() =>
      _ChartAccountUsageFormSheetState();
}

class _ChartAccountUsageFormSheetState
    extends ConsumerState<_ChartAccountUsageFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController = TextEditingController(
    text: widget.usage?['name']?.toString() ?? '',
  );
  late final TextEditingController _descriptionController =
      TextEditingController(
        text: widget.usage?['description']?.toString() ?? '',
      );
  int? _chartAccountId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final rawId = widget.usage?['charts_account_id'];
    if (rawId is int) {
      _chartAccountId = rawId;
    } else if (rawId is num) {
      _chartAccountId = rawId.toInt();
    } else if (rawId != null) {
      _chartAccountId = int.tryParse(rawId.toString());
    }
  }

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
    final accounts = _toMaps(widget.refs['charts_accounts']);

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
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(context).viewInsets.bottom + 24,
          ),
          child: Form(
            key: _formKey,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: Container(
                      width: 44,
                      height: 5,
                      decoration: BoxDecoration(
                        color: isDarkMode ? Colors.white24 : Colors.black12,
                        borderRadius: BorderRadius.circular(999),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    widget.usage == null
                        ? (isSwahili ? 'Matumizi Mapya' : 'New Usage')
                        : (isSwahili ? 'Hariri Matumizi' : 'Edit Usage'),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: textColor,
                    ),
                  ),
                  const SizedBox(height: 20),
                  TextFormField(
                    controller: _nameController,
                    decoration: inputStyle(isSwahili ? 'Jina *' : 'Name *'),
                    style: TextStyle(color: textColor),
                    validator: (v) => v == null || v.trim().isEmpty
                        ? (isSwahili ? 'Hitajiwa' : 'Required')
                        : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    value: _chartAccountId,
                    isExpanded: true,
                    decoration: inputStyle(
                      isSwahili ? 'Akaunti ya Chati *' : 'Chart of Account *',
                    ),
                    dropdownColor: bgColor,
                    items: accounts
                        .map(
                          (e) => DropdownMenuItem(
                            value: e['id'] as int,
                            child: Text(
                              '${e['code']} - ${e['account_name']}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        )
                        .toList(),
                    onChanged: (value) =>
                        setState(() => _chartAccountId = value),
                    validator: (v) => v == null
                        ? (isSwahili ? 'Hitajiwa' : 'Required')
                        : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _descriptionController,
                    maxLines: 4,
                    decoration: inputStyle(
                      isSwahili ? 'Maelezo' : 'Description',
                    ),
                    style: TextStyle(color: textColor),
                  ),
                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: _saving ? null : _submit,
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
                            widget.usage == null
                                ? (isSwahili ? 'Hifadhi' : 'Save')
                                : (isSwahili ? 'Sasisha' : 'Update'),
                          ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'name': _nameController.text.trim(),
        'charts_account_id': _chartAccountId,
        'description': _descriptionController.text.trim().isEmpty
            ? null
            : _descriptionController.text.trim(),
      };

      if (widget.usage == null) {
        await api.post('/chart-account-usages', data: data);
      } else {
        await api.put(
          '/chart-account-usages/${widget.usage!['id']}',
          data: data,
        );
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _usageErrorMessage(error, ref.read(isSwahiliProvider)),
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

class _UsagesErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _UsagesErrorView({
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

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}
