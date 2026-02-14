import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _selectedDateProvider = StateProvider.autoDispose<DateTime>((ref) => DateTime.now());

final _dailyReportProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, String>((ref, date) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/attendance/daily-report', queryParameters: {'date': date});
  return response.data['data'] as Map<String, dynamic>;
});

class AttendanceScreen extends ConsumerWidget {
  const AttendanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final selectedDate = ref.watch(_selectedDateProvider);
    final dateStr = DateFormat('yyyy-MM-dd').format(selectedDate);
    final reportAsync = ref.watch(_dailyReportProvider(dateStr));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Mahudhurio' : 'Attendance'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_dailyReportProvider(dateStr).future),
        child: reportAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_dailyReportProvider(dateStr)),
          ),
          data: (data) => _ReportBody(
            data: data,
            selectedDate: selectedDate,
            isDarkMode: isDarkMode,
            isSwahili: isSwahili,
          ),
        ),
      ),
    );
  }
}

class _ReportBody extends ConsumerWidget {
  final Map<String, dynamic> data;
  final DateTime selectedDate;
  final bool isDarkMode;
  final bool isSwahili;

  const _ReportBody({
    required this.data,
    required this.selectedDate,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final stats = data['stats'] as Map<String, dynamic>? ?? {};
    final staff = (data['staff'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final searchController = TextEditingController();

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        // Date picker row
        _DatePickerRow(
          selectedDate: selectedDate,
          isDarkMode: isDarkMode,
          isSwahili: isSwahili,
          onDateChanged: (date) =>
              ref.read(_selectedDateProvider.notifier).state = date,
        ),
        const SizedBox(height: 16),

        // Stats row
        _StatsRow(stats: stats, isDarkMode: isDarkMode, isSwahili: isSwahili),
        const SizedBox(height: 16),

        // Search bar
        _SearchBar(
          controller: searchController,
          isDarkMode: isDarkMode,
          isSwahili: isSwahili,
        ),
        const SizedBox(height: 12),

        // Staff list
        if (staff.isEmpty)
          Padding(
            padding: const EdgeInsets.only(top: 40),
            child: Column(
              children: [
                Icon(Icons.people_outline, size: 56, color: Colors.grey[300]),
                const SizedBox(height: 12),
                Text(
                  isSwahili ? 'Hakuna wafanyakazi' : 'No staff found',
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
              ],
            ),
          )
        else
          ...staff.map((s) => _StaffCard(staff: s, isDarkMode: isDarkMode)),

        const SizedBox(height: 80),
      ],
    );
  }
}

class _DatePickerRow extends StatelessWidget {
  final DateTime selectedDate;
  final bool isDarkMode;
  final bool isSwahili;
  final ValueChanged<DateTime> onDateChanged;

  const _DatePickerRow({
    required this.selectedDate,
    required this.isDarkMode,
    required this.isSwahili,
    required this.onDateChanged,
  });

  @override
  Widget build(BuildContext context) {
    final isToday = DateUtils.isSameDay(selectedDate, DateTime.now());

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.08)
              : Colors.grey.withValues(alpha: 0.12),
        ),
      ),
      child: Row(
        children: [
          IconButton(
            onPressed: () => onDateChanged(
              selectedDate.subtract(const Duration(days: 1)),
            ),
            icon: const Icon(Icons.chevron_left_rounded),
            iconSize: 22,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
          ),
          Expanded(
            child: GestureDetector(
              onTap: () async {
                final picked = await showDatePicker(
                  context: context,
                  initialDate: selectedDate,
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now(),
                );
                if (picked != null) onDateChanged(picked);
              },
              child: Column(
                children: [
                  Text(
                    DateFormat('EEEE, dd MMM yyyy').format(selectedDate),
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                  if (isToday)
                    Text(
                      isSwahili ? 'Leo' : 'Today',
                      style: const TextStyle(
                        fontSize: 11,
                        color: Color(0xFF1ABC9C),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                ],
              ),
            ),
          ),
          IconButton(
            onPressed: DateUtils.isSameDay(selectedDate, DateTime.now())
                ? null
                : () => onDateChanged(
                      selectedDate.add(const Duration(days: 1)),
                    ),
            icon: const Icon(Icons.chevron_right_rounded),
            iconSize: 22,
            padding: EdgeInsets.zero,
            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
          ),
        ],
      ),
    );
  }
}

class _StatsRow extends StatelessWidget {
  final Map<String, dynamic> stats;
  final bool isDarkMode;
  final bool isSwahili;

  const _StatsRow({
    required this.stats,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final items = [
      _StatItem(
        label: isSwahili ? 'Jumla' : 'Total',
        value: '${stats['total_users'] ?? 0}',
        color: const Color(0xFF3B82F6),
        icon: Icons.people_rounded,
      ),
      _StatItem(
        label: isSwahili ? 'Walio' : 'Present',
        value: '${stats['present'] ?? 0}',
        color: const Color(0xFF27AE60),
        icon: Icons.check_circle_rounded,
      ),
      _StatItem(
        label: isSwahili ? 'Kwa wakati' : 'On Time',
        value: '${stats['on_time'] ?? 0}',
        color: const Color(0xFF1ABC9C),
        icon: Icons.schedule_rounded,
      ),
      _StatItem(
        label: isSwahili ? 'Wachelewa' : 'Late',
        value: '${stats['late'] ?? 0}',
        color: const Color(0xFFF59E0B),
        icon: Icons.warning_rounded,
      ),
      _StatItem(
        label: isSwahili ? 'Hawako' : 'Absent',
        value: '${stats['absent'] ?? 0}',
        color: const Color(0xFFEF4444),
        icon: Icons.cancel_rounded,
      ),
    ];

    return Row(
      children: items
          .map((item) => Expanded(
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  decoration: BoxDecoration(
                    color: isDarkMode
                        ? item.color.withValues(alpha: 0.15)
                        : item.color.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: item.color.withValues(alpha: 0.2),
                    ),
                  ),
                  child: Column(
                    children: [
                      Icon(item.icon, size: 18, color: item.color),
                      const SizedBox(height: 4),
                      Text(
                        item.value,
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: item.color,
                        ),
                      ),
                      Text(
                        item.label,
                        style: TextStyle(
                          fontSize: 9,
                          color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              ))
          .toList(),
    );
  }
}

class _StatItem {
  final String label;
  final String value;
  final Color color;
  final IconData icon;

  const _StatItem({
    required this.label,
    required this.value,
    required this.color,
    required this.icon,
  });
}

class _SearchBar extends StatelessWidget {
  final TextEditingController controller;
  final bool isDarkMode;
  final bool isSwahili;

  const _SearchBar({
    required this.controller,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 42,
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : const Color(0xFFF5F6FA),
        borderRadius: BorderRadius.circular(12),
      ),
      child: TextField(
        controller: controller,
        style: TextStyle(
          fontSize: 13,
          color: isDarkMode ? Colors.white : AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          hintText: isSwahili ? 'Tafuta mfanyakazi...' : 'Search staff...',
          hintStyle: TextStyle(
            fontSize: 13,
            color: isDarkMode ? Colors.white38 : AppColors.textHint,
          ),
          prefixIcon: Icon(
            Icons.search_rounded,
            size: 18,
            color: isDarkMode ? Colors.white38 : AppColors.textHint,
          ),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 12),
        ),
      ),
    );
  }
}

class _StaffCard extends StatelessWidget {
  final Map<String, dynamic> staff;
  final bool isDarkMode;

  const _StaffCard({required this.staff, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final name = staff['name'] as String? ?? '';
    final department = staff['department'] as String? ?? 'N/A';
    final checkIn = staff['check_in'] as String?;
    final status = staff['status'] as String? ?? 'ABSENT';
    final deviceId = staff['device_id'];

    Color statusColor;
    String statusLabel;
    IconData statusIcon;

    switch (status) {
      case 'ON_TIME':
        statusColor = const Color(0xFF27AE60);
        statusLabel = 'ON TIME';
        statusIcon = Icons.check_circle_rounded;
        break;
      case 'LATE':
        statusColor = const Color(0xFFF59E0B);
        statusLabel = 'LATE';
        statusIcon = Icons.warning_rounded;
        break;
      default:
        statusColor = const Color(0xFFEF4444);
        statusLabel = 'ABSENT';
        statusIcon = Icons.cancel_rounded;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.06)
              : Colors.grey.withValues(alpha: 0.1),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.15 : 0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Avatar
          CircleAvatar(
            radius: 20,
            backgroundColor: statusColor.withValues(alpha: 0.12),
            child: Text(
              name.isNotEmpty ? name[0].toUpperCase() : '?',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: statusColor,
              ),
            ),
          ),
          const SizedBox(width: 12),

          // Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Row(
                  children: [
                    Icon(Icons.apartment_rounded,
                        size: 11,
                        color: isDarkMode ? Colors.white38 : AppColors.textHint),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        department,
                        style: TextStyle(
                          fontSize: 11,
                          color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                if (deviceId != null) ...[
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Icon(Icons.fingerprint_rounded,
                          size: 11,
                          color: isDarkMode ? Colors.white38 : AppColors.textHint),
                      const SizedBox(width: 4),
                      Text(
                        'ID: $deviceId',
                        style: TextStyle(
                          fontSize: 10,
                          color: isDarkMode ? Colors.white38 : AppColors.textHint,
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),

          // Check-in time + Status
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              if (checkIn != null)
                Text(
                  checkIn,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              const SizedBox(height: 4),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(statusIcon, size: 10, color: statusColor),
                    const SizedBox(width: 4),
                    Text(
                      statusLabel,
                      style: TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        color: statusColor,
                      ),
                    ),
                  ],
                ),
              ),
            ],
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
