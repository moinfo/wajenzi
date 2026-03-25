import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _notificationsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/notifications');
  return {
    'items': (response.data['data'] as List? ?? const [])
        .cast<Map<String, dynamic>>(),
    'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
  };
});

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final notificationsAsync = ref.watch(_notificationsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Arifa' : 'Notifications'),
        actions: [
          TextButton(
            onPressed: () => _markAllAsRead(context, ref, isSwahili),
            child: Text(isSwahili ? 'Soma Zote' : 'Read All'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_notificationsProvider.future),
        child: notificationsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _NotificationErrorView(
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_notificationsProvider),
          ),
          data: (payload) {
            final notifications =
                (payload['items'] as List).cast<Map<String, dynamic>>();
            final meta = payload['meta'] as Map<String, dynamic>? ?? const {};

            if (notifications.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.notifications_none_rounded,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna arifa' : 'No notifications',
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
              itemCount: notifications.length + 2,
              itemBuilder: (context, index) {
                if (index == 0) {
                  final unreadCount = meta['unread_count'] ?? 0;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Text(
                      isSwahili
                          ? 'Zisizosomwa: $unreadCount'
                          : 'Unread: $unreadCount',
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

                if (index == notifications.length + 1) {
                  return const SizedBox(height: 90);
                }

                final item = notifications[index - 1];
                return _NotificationCard(
                  item: item,
                  isSwahili: isSwahili,
                  onTap: () => _markAsRead(context, ref, item['id']?.toString(), isSwahili),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

class _NotificationCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final VoidCallback onTap;

  const _NotificationCard({
    required this.item,
    required this.isSwahili,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final data = item['data'] as Map<String, dynamic>? ?? const {};
    final title = (data['title'] ?? data['message'] ?? item['type'] ?? '-').toString();
    final body = (data['body'] ?? data['description'] ?? data['message'] ?? '').toString();
    final createdAt = item['created_at'] as String?;
    final isRead = item['read_at'] != null;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isRead ? null : AppColors.primary.withValues(alpha: 0.05),
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.all(16),
        leading: CircleAvatar(
          backgroundColor: (isRead ? AppColors.textHint : AppColors.primary)
              .withValues(alpha: 0.12),
          child: Icon(
            isRead ? Icons.notifications_none_rounded : Icons.notifications_active_rounded,
            color: isRead ? AppColors.textHint : AppColors.primary,
          ),
        ),
        title: Text(
          title,
          style: TextStyle(
            fontWeight: isRead ? FontWeight.w600 : FontWeight.w700,
          ),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (body.isNotEmpty)
                Text(
                  body,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                ),
              const SizedBox(height: 6),
              Text(
                _formatDate(createdAt),
                style: const TextStyle(color: AppColors.textSecondary),
              ),
            ],
          ),
        ),
        trailing: isRead
            ? null
            : Container(
                width: 10,
                height: 10,
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
              ),
      ),
    );
  }
}

class _NotificationErrorView extends StatelessWidget {
  final bool isSwahili;
  final VoidCallback onRetry;

  const _NotificationErrorView({
    required this.isSwahili,
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
                  ? 'Imeshindikana kupakia arifa'
                  : 'Failed to load notifications',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
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

Future<void> _markAsRead(
  BuildContext context,
  WidgetRef ref,
  String? id,
  bool isSwahili,
) async {
  if (id == null || id.isEmpty) return;

  try {
    final api = ref.read(apiClientProvider);
    await api.put('/notifications/$id/read');
    ref.invalidate(_notificationsProvider);
  } catch (_) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Imeshindwa kusasisha arifa.'
                : 'Could not update the notification.',
          ),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

Future<void> _markAllAsRead(
  BuildContext context,
  WidgetRef ref,
  bool isSwahili,
) async {
  try {
    final api = ref.read(apiClientProvider);
    await api.put('/notifications/read-all');
    ref.invalidate(_notificationsProvider);
  } catch (_) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Imeshindwa kusasisha arifa zote.'
                : 'Could not update all notifications.',
          ),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

String _formatDate(String? date) {
  if (date == null || date.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy, HH:mm').format(DateTime.parse(date).toLocal());
  } catch (_) {
    return date;
  }
}
