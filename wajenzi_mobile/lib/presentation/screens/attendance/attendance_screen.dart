import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/settings_provider.dart';

class AttendanceScreen extends ConsumerStatefulWidget {
  const AttendanceScreen({super.key});

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  bool _hasCheckedIn = false;
  bool _hasCheckedOut = false;
  bool _isLoading = false;

  bool get _isSwahili => ref.watch(isSwahiliProvider);

  Future<void> _handleCheckIn() async {
    setState(() => _isLoading = true);

    // TODO: Implement check-in with GPS
    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      _hasCheckedIn = true;
      _isLoading = false;
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_isSwahili ? 'Kuingia kumerekodiwa kikamilifu' : 'Check-in recorded successfully')),
      );
    }
  }

  Future<void> _handleCheckOut() async {
    setState(() => _isLoading = true);

    // TODO: Implement check-out with GPS
    await Future.delayed(const Duration(seconds: 1));

    setState(() {
      _hasCheckedOut = true;
      _isLoading = false;
    });

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_isSwahili ? 'Kutoka kumerekodiwa kikamilifu' : 'Check-out recorded successfully')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_isSwahili ? 'Mahudhurio' : 'Attendance'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Today's Status Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    Text(
                      _isSwahili ? 'Leo' : 'Today',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _formatDate(DateTime.now()),
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                    ),
                    const SizedBox(height: 24),

                    // Status indicators
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        _StatusIndicator(
                          label: _isSwahili ? 'Kuingia' : 'Check In',
                          time: _hasCheckedIn ? '08:30 AM' : '--:--',
                          isComplete: _hasCheckedIn,
                        ),
                        Container(
                          width: 1,
                          height: 50,
                          color: Colors.grey.shade300,
                        ),
                        _StatusIndicator(
                          label: _isSwahili ? 'Kutoka' : 'Check Out',
                          time: _hasCheckedOut ? '05:30 PM' : '--:--',
                          isComplete: _hasCheckedOut,
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // Action Button
                    SizedBox(
                      width: double.infinity,
                      height: 56,
                      child: ElevatedButton.icon(
                        onPressed: _isLoading
                            ? null
                            : (!_hasCheckedIn
                                ? _handleCheckIn
                                : (!_hasCheckedOut ? _handleCheckOut : null)),
                        icon: _isLoading
                            ? const SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : Icon(
                                !_hasCheckedIn
                                    ? Icons.login
                                    : (!_hasCheckedOut
                                        ? Icons.logout
                                        : Icons.check_circle),
                              ),
                        label: Text(
                          !_hasCheckedIn
                              ? (_isSwahili ? 'Kuingia' : 'Check In')
                              : (!_hasCheckedOut
                                  ? (_isSwahili ? 'Kutoka' : 'Check Out')
                                  : (_isSwahili ? 'Imekamilika' : 'Completed')),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: !_hasCheckedIn
                              ? AppColors.success
                              : (!_hasCheckedOut
                                  ? AppColors.warning
                                  : AppColors.textHint),
                          foregroundColor: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Recent History
            Text(
              _isSwahili ? 'Historia ya Hivi Karibuni' : 'Recent History',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 12),

            // Placeholder history items
            _HistoryItem(
              date: DateTime.now().subtract(const Duration(days: 1)),
              checkIn: '08:45 AM',
              checkOut: '05:30 PM',
              status: _isSwahili ? 'Alikuwepo' : 'Present',
              isLate: false,
            ),
            _HistoryItem(
              date: DateTime.now().subtract(const Duration(days: 2)),
              checkIn: '09:15 AM',
              checkOut: '06:00 PM',
              status: _isSwahili ? 'Amechelewa' : 'Late',
              isLate: true,
            ),
            _HistoryItem(
              date: DateTime.now().subtract(const Duration(days: 3)),
              checkIn: '08:30 AM',
              checkOut: '05:45 PM',
              status: _isSwahili ? 'Alikuwepo' : 'Present',
              isLate: false,
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime date) {
    final months = _isSwahili
        ? ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des']
        : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    final days = _isSwahili
        ? ['Jmt', 'Jmt', 'Jmt', 'Alk', 'Ijm', 'Jma', 'Jpi']
        : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    return '${days[date.weekday - 1]}, ${months[date.month - 1]} ${date.day}';
  }
}

class _StatusIndicator extends StatelessWidget {
  final String label;
  final String time;
  final bool isComplete;

  const _StatusIndicator({
    required this.label,
    required this.time,
    required this.isComplete,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(
          isComplete ? Icons.check_circle : Icons.circle_outlined,
          color: isComplete ? AppColors.success : AppColors.textHint,
          size: 32,
        ),
        const SizedBox(height: 8),
        Text(
          label,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: AppColors.textSecondary,
              ),
        ),
        Text(
          time,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
                color: isComplete ? AppColors.textPrimary : AppColors.textHint,
              ),
        ),
      ],
    );
  }
}

class _HistoryItem extends StatelessWidget {
  final DateTime date;
  final String checkIn;
  final String checkOut;
  final String status;
  final bool isLate;

  const _HistoryItem({
    required this.date,
    required this.checkIn,
    required this.checkOut,
    required this.status,
    required this.isLate,
  });

  @override
  Widget build(BuildContext context) {

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: isLate
              ? AppColors.warning.withOpacity(0.1)
              : AppColors.success.withOpacity(0.1),
          child: Icon(
            isLate ? Icons.schedule : Icons.check,
            color: isLate ? AppColors.warning : AppColors.success,
          ),
        ),
        title: Text(
          '${date.day}/${date.month}/${date.year}',
          style: const TextStyle(fontWeight: FontWeight.w500),
        ),
        subtitle: Text('$checkIn - $checkOut'),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          decoration: BoxDecoration(
            color: isLate
                ? AppColors.warning.withOpacity(0.1)
                : AppColors.success.withOpacity(0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            status,
            style: TextStyle(
              color: isLate ? AppColors.warning : AppColors.success,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ),
    );
  }
}
