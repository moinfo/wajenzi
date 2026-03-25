import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _projectReportsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/project-reports');
  return {
    'items': response.data['data'] as List? ?? const [],
    'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
  };
});

class ProjectReportsScreen extends ConsumerWidget {
  const ProjectReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportsAsync = ref.watch(_projectReportsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ripoti za Miradi' : 'Project Reports'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_projectReportsProvider),
        child: reportsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ReportsErrorView(
            error: '$e',
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_projectReportsProvider),
          ),
          data: (payload) {
            final items = (payload['items'] as List).cast<Map<String, dynamic>>();
            final meta = payload['meta'] as Map<String, dynamic>;

            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.assessment_outlined,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna ripoti za miradi'
                        : 'No project reports found',
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
              itemCount: items.length + 2,
              itemBuilder: (context, index) {
                if (index == 0) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Row(
                      children: [
                        Expanded(
                          child: _StatChip(
                            label: isSwahili ? 'Jumla' : 'Total',
                            value: '${meta['total'] ?? items.length}',
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: _StatChip(
                            label: isSwahili ? 'Ripoti za Siku' : 'Daily',
                            value: '${meta['daily_reports'] ?? 0}',
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: _StatChip(
                            label: isSwahili ? 'Ziara' : 'Visits',
                            value: '${meta['site_visits'] ?? 0}',
                          ),
                        ),
                      ],
                    ),
                  );
                }

                if (index == items.length + 1) {
                  return const SizedBox(height: 80);
                }

                final item = items[index - 1];
                return _ReportItemCard(
                  item: item,
                  isSwahili: isSwahili,
                  isDarkMode: isDarkMode,
                );
              },
            );
          },
        ),
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  final String label;
  final String value;

  const _StatChip({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF3498DB).withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
          const SizedBox(height: 4),
          Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}

class _ReportItemCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final bool isDarkMode;

  const _ReportItemCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final isVisit = item['type'] == 'site_visit';
    final accent = isVisit ? const Color(0xFFF39C12) : const Color(0xFF1ABC9C);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    isVisit
                        ? (isSwahili ? 'Ziara ya Tovuti' : 'Site Visit')
                        : (isSwahili ? 'Ripoti ya Siku' : 'Daily Report'),
                    style: TextStyle(
                      color: accent,
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                const Spacer(),
                Text(
                  item['report_date'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              item['project_name'] as String? ?? '-',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              item['summary'] as String? ?? '-',
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Icon(Icons.person_outline, size: 16, color: accent),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    item['author_name'] as String? ?? '-',
                    style: TextStyle(
                      fontSize: 12,
                      color: isDarkMode ? Colors.white70 : AppColors.textPrimary,
                    ),
                  ),
                ),
                Text(
                  item['status'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: accent,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ReportsErrorView extends StatelessWidget {
  final String error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ReportsErrorView({
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
          error,
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
