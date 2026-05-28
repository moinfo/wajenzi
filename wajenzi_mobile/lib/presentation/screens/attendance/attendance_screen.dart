import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

String _attendanceTr(
  AppLanguage language, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) {
  return switch (language) {
    AppLanguage.swahili => sw ?? en,
    AppLanguage.french => fr ?? en,
    AppLanguage.arabic => ar ?? en,
    AppLanguage.english => en,
  };
}

final _selectedDateProvider = StateProvider.autoDispose<DateTime>((ref) => DateTime.now());
final _searchQueryProvider = StateProvider.autoDispose<String>((ref) => '');

final _dailyReportProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, String>((ref, params) async {
  final parts = params.split('|');
  final date = parts.isNotEmpty ? parts[0] : '';
  final search = parts.length > 1 ? parts[1] : '';
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/attendance/daily-report',
    queryParameters: {
      'date': date,
      if (search.trim().isNotEmpty) 'search': search,
    },
  );
  return response.data['data'] as Map<String, dynamic>;
});

class AttendanceScreen extends ConsumerStatefulWidget {
  const AttendanceScreen({super.key});

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  late final TextEditingController _searchController;

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final selectedDate = ref.watch(_selectedDateProvider);
    final searchQuery = ref.watch(_searchQueryProvider);
    final dateStr = DateFormat('yyyy-MM-dd').format(selectedDate);
    final reportParams = '$dateStr|$searchQuery';
    final reportAsync = ref.watch(_dailyReportProvider(reportParams));
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _attendanceTr(
            language,
            en: 'Attendance',
            sw: 'Mahudhurio',
            fr: 'Présence',
            ar: 'الحضور',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_dailyReportProvider(reportParams).future),
        child: reportAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            language: language,
            onRetry: () => ref.invalidate(_dailyReportProvider(reportParams)),
          ),
          data: (data) => _ReportBody(
            data: data,
            selectedDate: selectedDate,
            isDarkMode: isDarkMode,
            language: language,
            searchController: _searchController,
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
  final AppLanguage language;
  final TextEditingController searchController;

  const _ReportBody({
    required this.data,
    required this.selectedDate,
    required this.isDarkMode,
    required this.language,
    required this.searchController,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final stats = data['stats'] as Map<String, dynamic>? ?? {};
    final staff = (data['staff'] as List?)?.cast<Map<String, dynamic>>() ?? [];

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        // Date picker row
        _DatePickerRow(
          selectedDate: selectedDate,
          isDarkMode: isDarkMode,
          language: language,
          onDateChanged: (date) =>
              ref.read(_selectedDateProvider.notifier).state = date,
        ),
        const SizedBox(height: 16),

        // Stats row
        _StatsRow(stats: stats, isDarkMode: isDarkMode, language: language),
        const SizedBox(height: 16),

        // Search bar
        _SearchBar(
          controller: searchController,
          isDarkMode: isDarkMode,
          language: language,
          onChanged: (value) =>
              ref.read(_searchQueryProvider.notifier).state = value.trim(),
          onClear: () {
            searchController.clear();
            ref.read(_searchQueryProvider.notifier).state = '';
          },
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
                  _attendanceTr(
                    language,
                    en: 'No staff found',
                    sw: 'Hakuna wafanyakazi',
                    fr: 'Aucun employé trouvé',
                    ar: 'لم يتم العثور على موظفين',
                  ),
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
              ],
            ),
          )
        else
          ...staff.map((s) => _StaffCard(staff: s, isDarkMode: isDarkMode, language: language)),

        const SizedBox(height: 80),
      ],
    );
  }
}

class _DatePickerRow extends StatelessWidget {
  final DateTime selectedDate;
  final bool isDarkMode;
  final AppLanguage language;
  final ValueChanged<DateTime> onDateChanged;

  const _DatePickerRow({
    required this.selectedDate,
    required this.isDarkMode,
    required this.language,
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
                      _attendanceTr(
                        language,
                        en: 'Today',
                        sw: 'Leo',
                        fr: 'Aujourd’hui',
                        ar: 'اليوم',
                      ),
                      style: const TextStyle(
                        fontSize: 11,
                        color: Color(0xFF193340),
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
  final AppLanguage language;

  const _StatsRow({
    required this.stats,
    required this.isDarkMode,
    required this.language,
  });

  @override
  Widget build(BuildContext context) {
    final items = [
      _StatItem(
        label: _attendanceTr(
          language,
          en: 'Total',
          sw: 'Jumla',
          fr: 'Total',
          ar: 'الإجمالي',
        ),
        value: '${stats['total_users'] ?? 0}',
        color: const Color(0xFF3B82F6),
        icon: Icons.people_rounded,
      ),
      _StatItem(
        label: _attendanceTr(language, en: 'Present', sw: 'Walio', fr: 'Présents', ar: 'الحاضرون'),
        value: '${stats['present'] ?? 0}',
        color: const Color(0xFF27AE60),
        icon: Icons.check_circle_rounded,
      ),
      _StatItem(
        label: _attendanceTr(language, en: 'On Time', sw: 'Kwa wakati', fr: 'À l’heure', ar: 'في الوقت'),
        value: '${stats['on_time'] ?? 0}',
        color: const Color(0xFF193340),
        icon: Icons.schedule_rounded,
      ),
      _StatItem(
        label: _attendanceTr(language, en: 'Late', sw: 'Wachelewa', fr: 'En retard', ar: 'متأخرون'),
        value: '${stats['late'] ?? 0}',
        color: const Color(0xFFF59E0B),
        icon: Icons.warning_rounded,
      ),
      _StatItem(
        label: _attendanceTr(language, en: 'Absent', sw: 'Hawako', fr: 'Absents', ar: 'غائبون'),
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
  final AppLanguage language;
  final ValueChanged<String> onChanged;
  final VoidCallback onClear;

  const _SearchBar({
    required this.controller,
    required this.isDarkMode,
    required this.language,
    required this.onChanged,
    required this.onClear,
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
        onChanged: onChanged,
        style: TextStyle(
          fontSize: 13,
          color: isDarkMode ? Colors.white : AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          hintText: _attendanceTr(
            language,
            en: 'Search staff...',
            sw: 'Tafuta mfanyakazi...',
            fr: 'Rechercher un employé...',
            ar: 'ابحث عن موظف...',
          ),
          hintStyle: TextStyle(
            fontSize: 13,
            color: isDarkMode ? Colors.white38 : AppColors.textHint,
          ),
          prefixIcon: Icon(
            Icons.search_rounded,
            size: 18,
            color: isDarkMode ? Colors.white38 : AppColors.textHint,
          ),
          suffixIcon: controller.text.isEmpty
              ? null
              : IconButton(
                  onPressed: onClear,
                  icon: Icon(
                    Icons.close_rounded,
                    size: 18,
                    color: isDarkMode ? Colors.white38 : AppColors.textHint,
                  ),
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
  final AppLanguage language;

  const _StaffCard({required this.staff, required this.isDarkMode, required this.language});

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
        statusLabel = _attendanceTr(language, en: 'ON TIME', sw: 'KWA WAKATI', fr: 'À L’HEURE', ar: 'في الوقت');
        statusIcon = Icons.check_circle_rounded;
        break;
      case 'LATE':
        statusColor = const Color(0xFFF59E0B);
        statusLabel = _attendanceTr(language, en: 'LATE', sw: 'AMECHELEWA', fr: 'EN RETARD', ar: 'متأخر');
        statusIcon = Icons.warning_rounded;
        break;
      default:
        statusColor = const Color(0xFFEF4444);
        statusLabel = _attendanceTr(language, en: 'ABSENT', sw: 'HAJAPO', fr: 'ABSENT', ar: 'غائب');
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
  final AppLanguage language;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.language,
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
          _attendanceTr(
            language,
            en: 'Something went wrong',
            sw: 'Hitilafu imetokea',
            fr: 'Un problème est survenu',
            ar: 'حدث خطأ ما',
          ),
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(
              _attendanceTr(
                language,
                en: 'Try again',
                sw: 'Jaribu tena',
                fr: 'Réessayer',
                ar: 'حاول مرة أخرى',
              ),
            ),
          ),
        ),
      ],
    );
  }
}
