import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';

final _projectDocumentsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _projectDocumentsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-documents');

      final payload = response.data['data'];
      final collection = payload is Map<String, dynamic> ? payload : null;
      final items = collection?['data'] ?? payload;
      final meta =
          collection?['meta'] as Map<String, dynamic>? ??
          response.data['meta'] as Map<String, dynamic>? ??
          {};

      return {'items': items as List? ?? const [], 'meta': meta};
    });

String _projectDocumentErrorMessage(Object error, bool isSwahili) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }
    }
  }

  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class ProjectDocumentsScreen extends ConsumerStatefulWidget {
  const ProjectDocumentsScreen({super.key});

  @override
  ConsumerState<ProjectDocumentsScreen> createState() =>
      _ProjectDocumentsScreenState();
}

class _ProjectDocumentsScreenState
    extends ConsumerState<ProjectDocumentsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final documentsAsync = ref.watch(_projectDocumentsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref
        .watch(_projectDocumentsSearchProvider)
        .trim()
        .toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Nyaraka za Miradi' : 'Project Documents'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_projectDocumentsProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_projectDocumentsSearchProvider.notifier).state =
                          value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta nyaraka...'
                        : 'Search documents...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(
                                          _projectDocumentsSearchProvider
                                              .notifier,
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
              ),
            ),
            documentsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _DocumentsErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_projectDocumentsProvider),
                ),
              ),
              data: (payload) {
                final allItems = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final meta = payload['meta'] as Map<String, dynamic>? ?? {};

                final documents = search.isEmpty
                    ? allItems
                    : allItems.where((doc) {
                        final haystack = [
                          doc['file_name'] ?? '',
                          doc['project_name'] ?? '',
                          doc['document_type'] ?? '',
                          doc['uploaded_by_name'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (documents.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.folder_open_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna nyaraka za miradi'
                                      : 'No project documents found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No documents match your search'),
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
                                          .read(
                                            _projectDocumentsSearchProvider
                                                .notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.arrow_back_rounded),
                              label: Text(isSwahili ? 'Rudi' : 'Back'),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                final total = meta['total'] ?? documents.length;

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Text(
                          isSwahili
                              ? 'Jumla ya nyaraka: $total'
                              : 'Total documents: $total',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white70
                                : AppColors.textSecondary,
                          ),
                        ),
                      ),
                      ...documents.map(
                        (document) => _DocumentCard(
                          document: document,
                          isSwahili: isSwahili,
                          isDarkMode: isDarkMode,
                          onTap: () => _showDocumentDetails(context, document),
                        ),
                      ),
                      const SizedBox(height: 80),
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

  Future<void> _showDocumentDetails(
    BuildContext context,
    Map<String, dynamic> document,
  ) async {
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
                    Expanded(
                      child: Text(
                        document['file_name'] as String? ?? '-',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                _InfoRow(
                  label: isSwahili ? 'Mradi' : 'Project',
                  value: document['project_name'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                ),
                _InfoRow(
                  label: isSwahili ? 'Aina' : 'Type',
                  value: document['document_type'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                ),
                _InfoRow(
                  label: isSwahili ? 'Hali' : 'Status',
                  value: document['status'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                ),
                _InfoRow(
                  label: isSwahili ? 'Ukubwa' : 'Size',
                  value: _formatFileSize(document['file_size']),
                  isDarkMode: isDarkMode,
                ),
                _InfoRow(
                  label: isSwahili ? 'Mpakiaji' : 'Uploaded by',
                  value: document['uploaded_by_name'] as String? ?? '-',
                  isDarkMode: isDarkMode,
                ),
                _InfoRow(
                  label: isSwahili ? 'Tarehe' : 'Date',
                  value: _formatDate(document['created_at']),
                  isDarkMode: isDarkMode,
                ),
                if ((document['description'] as String?)?.trim().isNotEmpty ==
                    true)
                  _InfoRow(
                    label: isSwahili ? 'Maelezo' : 'Description',
                    value: document['description'] as String,
                    isDarkMode: isDarkMode,
                  ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () => _openDocument(context, document),
                    icon: const Icon(Icons.open_in_new_rounded),
                    label: Text(isSwahili ? 'Fungua Hati' : 'Open Document'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _openDocument(
    BuildContext context,
    Map<String, dynamic> document,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final url = document['file_url'] as String? ?? '';
    final uri = Uri.tryParse(url);

    final opened = uri != null && await ExternalLauncherService.openUri(uri);
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili ? 'Imeshindwa kufungua hati' : 'Could not open document',
          ),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _DocumentCard extends StatelessWidget {
  final Map<String, dynamic> document;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _DocumentCard({
    required this.document,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
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
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: const Color(0xFF3498DB).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.description_outlined,
                  color: Color(0xFF3498DB),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      document['file_name'] as String? ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      document['project_name'] as String? ??
                          (isSwahili ? 'Mradi haujulikani' : 'Unknown project'),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white70
                            : AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        _MiniBadge(
                          text: document['document_type'] as String? ?? '-',
                          color: AppColors.primary,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _formatFileSize(document['file_size']),
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded),
            ],
          ),
        ),
      ),
    );
  }
}

class _MiniBadge extends StatelessWidget {
  final String text;
  final Color color;

  const _MiniBadge({required this.text, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _InfoRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value.isEmpty ? '-' : value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _DocumentsErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _DocumentsErrorView({
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
          _projectDocumentErrorMessage(error, isSwahili),
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

String _formatFileSize(dynamic rawSize) {
  final size = switch (rawSize) {
    int value => value.toDouble(),
    double value => value,
    String value => double.tryParse(value) ?? 0,
    _ => 0,
  };

  if (size >= 1024 * 1024 * 1024) {
    return '${(size / (1024 * 1024 * 1024)).toStringAsFixed(1)} GB';
  }
  if (size >= 1024 * 1024) {
    return '${(size / (1024 * 1024)).toStringAsFixed(1)} MB';
  }
  if (size >= 1024) {
    return '${(size / 1024).toStringAsFixed(1)} KB';
  }
  return '${size.toStringAsFixed(0)} B';
}

String _formatDate(dynamic rawDate) {
  if (rawDate is! String || rawDate.isEmpty) {
    return '-';
  }

  final parsed = DateTime.tryParse(rawDate);
  if (parsed == null) {
    return rawDate;
  }

  return DateFormat('dd MMM yyyy, HH:mm').format(parsed.toLocal());
}
