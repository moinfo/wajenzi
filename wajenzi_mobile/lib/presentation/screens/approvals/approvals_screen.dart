import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/settings_provider.dart';

class ApprovalsScreen extends ConsumerWidget {
  const ApprovalsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);

    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: Text(isSwahili ? 'Idhini' : 'Approvals'),
          bottom: TabBar(
            tabs: [
              Tab(text: isSwahili ? 'Zinasubiri' : 'Pending'),
              Tab(text: isSwahili ? 'Zimeidhinishwa' : 'Approved'),
              Tab(text: isSwahili ? 'Zimekataliwa' : 'Rejected'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _ApprovalList(status: 'pending', isSwahili: isSwahili),
            _ApprovalList(status: 'approved', isSwahili: isSwahili),
            _ApprovalList(status: 'rejected', isSwahili: isSwahili),
          ],
        ),
      ),
    );
  }
}

class _ApprovalList extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _ApprovalList({required this.status, required this.isSwahili});

  String get _emptyStatusText {
    switch (status) {
      case 'pending':
        return isSwahili ? 'Hakuna idhini zinazosubiri' : 'No pending approvals';
      case 'approved':
        return isSwahili ? 'Hakuna idhini zilizoidhinishwa' : 'No approved approvals';
      case 'rejected':
        return isSwahili ? 'Hakuna idhini zilizokataliwa' : 'No rejected approvals';
      default:
        return isSwahili ? 'Hakuna idhini' : 'No approvals';
    }
  }

  @override
  Widget build(BuildContext context) {
    final items = status == 'pending' ? 5 : 3;

    if (items == 0) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.check_circle_outline,
              size: 64,
              color: AppColors.textHint,
            ),
            const SizedBox(height: 16),
            Text(
              _emptyStatusText,
              style: TextStyle(color: AppColors.textSecondary),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: items,
      itemBuilder: (context, index) {
        final types = ['site_daily_report', 'expense', 'material_request'];
        final type = types[index % types.length];

        return _ApprovalCard(
          type: type,
          title: _getTitle(type, index),
          subtitle: _getSubtitle(type),
          submittedBy: 'John Doe',
          date: DateTime.now().subtract(Duration(days: index)),
          status: status,
          isSwahili: isSwahili,
          onApprove: status == 'pending' ? () {} : null,
          onReject: status == 'pending' ? () {} : null,
        );
      },
    );
  }

  String _getTitle(String type, int index) {
    switch (type) {
      case 'site_daily_report':
        return isSwahili ? 'Ripoti ya Eneo - Eneo ${index + 1}' : 'Site Report - Site ${index + 1}';
      case 'expense':
        return isSwahili ? 'Matumizi - TZS ${150000 + (index * 25000)}' : 'Expense - TZS ${150000 + (index * 25000)}';
      case 'material_request':
        return isSwahili ? 'Ombi la Vifaa #${1001 + index}' : 'Material Request #${1001 + index}';
      default:
        return isSwahili ? 'Haijulikani' : 'Unknown';
    }
  }

  String _getSubtitle(String type) {
    switch (type) {
      case 'site_daily_report':
        return isSwahili ? 'Ripoti ya maendeleo ya kila siku' : 'Daily progress report';
      case 'expense':
        return isSwahili ? 'Madai ya matumizi ya mradi' : 'Project expense claim';
      case 'material_request':
        return isSwahili ? 'Saruji, Nondo, n.k.' : 'Cement, Steel bars, etc.';
      default:
        return '';
    }
  }
}

class _ApprovalCard extends StatelessWidget {
  final String type;
  final String title;
  final String subtitle;
  final String submittedBy;
  final DateTime date;
  final String status;
  final bool isSwahili;
  final VoidCallback? onApprove;
  final VoidCallback? onReject;

  const _ApprovalCard({
    required this.type,
    required this.title,
    required this.subtitle,
    required this.submittedBy,
    required this.date,
    required this.status,
    required this.isSwahili,
    this.onApprove,
    this.onReject,
  });

  IconData get typeIcon {
    switch (type) {
      case 'site_daily_report':
        return Icons.description;
      case 'expense':
        return Icons.receipt_long;
      case 'material_request':
        return Icons.inventory_2;
      default:
        return Icons.article;
    }
  }

  Color get typeColor {
    switch (type) {
      case 'site_daily_report':
        return AppColors.info;
      case 'expense':
        return AppColors.secondary;
      case 'material_request':
        return AppColors.primary;
      default:
        return AppColors.textSecondary;
    }
  }

  @override
  Widget build(BuildContext context) {
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
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: typeColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(typeIcon, color: typeColor, size: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: Theme.of(context).textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      Text(
                        subtitle,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: AppColors.textSecondary,
                            ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(Icons.person_outline, size: 16, color: AppColors.textHint),
                const SizedBox(width: 4),
                Text(
                  submittedBy,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                ),
                const SizedBox(width: 16),
                Icon(Icons.access_time, size: 16, color: AppColors.textHint),
                const SizedBox(width: 4),
                Text(
                  '${date.day}/${date.month}/${date.year}',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                ),
              ],
            ),
            if (status == 'pending') ...[
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: onReject,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.error,
                        side: BorderSide(color: AppColors.error),
                      ),
                      child: Text(isSwahili ? 'Kataa' : 'Reject'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: onApprove,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.success,
                        foregroundColor: Colors.white,
                      ),
                      child: Text(isSwahili ? 'Idhinisha' : 'Approve'),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
