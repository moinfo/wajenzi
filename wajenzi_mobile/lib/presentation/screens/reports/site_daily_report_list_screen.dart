import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';

class SiteDailyReportListScreen extends ConsumerWidget {
  const SiteDailyReportListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Site Daily Reports'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: () {
              // Show filters
            },
          ),
        ],
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 5,
        itemBuilder: (context, index) {
          return _ReportCard(
            siteName: 'Site ${index + 1} - Main Building',
            date: DateTime.now().subtract(Duration(days: index)),
            status: index == 0 ? 'draft' : (index == 1 ? 'pending' : 'approved'),
            progress: 45 + (index * 10),
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          // Navigate to create report
        },
        icon: const Icon(Icons.add),
        label: const Text('New Report'),
      ),
    );
  }
}

class _ReportCard extends StatelessWidget {
  final String siteName;
  final DateTime date;
  final String status;
  final int progress;

  const _ReportCard({
    required this.siteName,
    required this.date,
    required this.status,
    required this.progress,
  });

  Color get statusColor {
    switch (status) {
      case 'draft':
        return AppColors.draft;
      case 'pending':
        return AppColors.pending;
      case 'approved':
        return AppColors.approved;
      case 'rejected':
        return AppColors.rejected;
      default:
        return AppColors.draft;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
          // Navigate to report detail
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      siteName,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      status.toUpperCase(),
                      style: TextStyle(
                        color: statusColor,
                        fontWeight: FontWeight.w600,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 8),
                  Text(
                    '${date.day}/${date.month}/${date.year}',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: AppColors.textSecondary,
                        ),
                  ),
                  const SizedBox(width: 24),
                  Icon(Icons.trending_up, size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 8),
                  Text(
                    '$progress% Progress',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: AppColors.textSecondary,
                        ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              LinearProgressIndicator(
                value: progress / 100,
                backgroundColor: AppColors.primary.withOpacity(0.1),
                valueColor: AlwaysStoppedAnimation<Color>(AppColors.primary),
                borderRadius: BorderRadius.circular(4),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
