import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/services/export_service.dart';
import 'motion.dart';

/// The Start Date / End Date chosen on a list screen (null = no limit).
typedef DateRangeValue = ({DateTime? start, DateTime? end});

/// First day of the current month.
DateTime firstOfCurrentMonth() {
  final now = DateTime.now();
  return DateTime(now.year, now.month, 1);
}

/// The default Start Date of every date picker: six months before today
/// (the same day of the month, or the last day when that month is shorter).
DateTime defaultStartDate() {
  final now = DateTime.now();
  final target = DateTime(now.year, now.month - 6, 1);
  final lastDay = DateTime(target.year, target.month + 1, 0).day;
  return DateTime(target.year, target.month, now.day > lastDay ? lastDay : now.day);
}

/// Today without the time: the default End Date of every list.
DateTime today() {
  final now = DateTime.now();
  return DateTime(now.year, now.month, now.day);
}

/// The default range of a list: the last six months, up to today.
DateRangeValue currentMonthRange() => (start: defaultStartDate(), end: today());

/// One range per screen id, so a screen's list provider and its filter bar share it.
/// It starts as the last six months.
final dateRangeProvider = StateProvider.autoDispose
    .family<DateRangeValue, String>((ref, id) => currentMonthRange());

/// `start_date` / `end_date` query parameters for a range (only the chosen ones).
Map<String, String> dateRangeQuery(DateRangeValue range) => {
      if (range.start != null) 'start_date': DateFilterBar.format(range.start)!,
      if (range.end != null) 'end_date': DateFilterBar.format(range.end)!,
    };

/// Print / Excel / PDF buttons and the Start Date / End Date bar of a list
/// screen, tied together: the export holds the rows of the chosen dates.
/// [id] must be the same id the screen's list provider reads with
/// `ref.watch(dateRangeProvider(id))`.
class ExportAndDateBar extends ConsumerWidget {
  final String id;
  final String? route;
  final Map<String, String?> extraParams;

  const ExportAndDateBar({
    super.key,
    required this.id,
    this.route,
    this.extraParams = const {},
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final range = ref.watch(dateRangeProvider(id));

    return Column(
      children: [
        if (route != null)
          ExportButtonsBar(
          options: webTableExportOptions(
            route!,
            params: {
              ...extraParams,
              'start_date': DateFilterBar.format(range.start),
              'end_date': DateFilterBar.format(range.end),
            },
          ),
        ),
        DateFilterBar(
          start: range.start,
          end: range.end,
          onStart: (d) => ref.read(dateRangeProvider(id).notifier).state =
              (start: d, end: range.end),
          onEnd: (d) => ref.read(dateRangeProvider(id).notifier).state =
              (start: range.start, end: d),
        ),
      ],
    );
  }
}

/// A small blue label above its field, like the labels of the web's filter form.
class LabeledField extends StatelessWidget {
  final String label;
  final Widget child;

  const LabeledField({super.key, required this.label, required this.child});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 4, left: 2),
          child: Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: Color(0xFF3B9DE0),
            ),
          ),
        ),
        child,
      ],
    );
  }
}

/// The Start Date / End Date (and extra filters) bar the web shows above its
/// lists. Always visible, never hidden in a panel. An empty date means "no
/// limit", as on the web (its inputs only filter once a date is chosen).
class DateFilterBar extends StatelessWidget {
  final DateTime? start;
  final DateTime? end;
  final ValueChanged<DateTime?> onStart;
  final ValueChanged<DateTime?> onEnd;

  /// Other filters shown next to the dates (a project dropdown, a status...).
  final List<Widget> extra;

  final String startLabel;
  final String endLabel;
  final EdgeInsetsGeometry padding;

  const DateFilterBar({
    super.key,
    required this.start,
    required this.end,
    required this.onStart,
    required this.onEnd,
    this.extra = const [],
    this.startLabel = 'Start Date',
    this.endLabel = 'End Date',
    this.padding = const EdgeInsets.fromLTRB(16, 10, 16, 4),
  });

  static final _fmt = DateFormat('yyyy-MM-dd');

  /// `yyyy-MM-dd`, or null when no date is chosen. Use it for query parameters.
  static String? format(DateTime? d) => d == null ? null : _fmt.format(d);

  Future<void> _pick(
    BuildContext context,
    DateTime? current,
    ValueChanged<DateTime?> onChanged,
    DateTime? first,
    DateTime? last,
  ) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: current ?? DateTime.now(),
      firstDate: first ?? DateTime(2000),
      lastDate: last ?? DateTime(2100),
    );
    if (picked != null) onChanged(DateTime(picked.year, picked.month, picked.day));
  }

  Widget _field(
    BuildContext context,
    String label,
    DateTime? value,
    ValueChanged<DateTime?> onChanged, {
    DateTime? first,
    DateTime? last,
  }) {
    return LabeledField(
      label: label,
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: () => _pick(context, value, onChanged, first, last),
        child: InputDecorator(
          decoration: InputDecoration(
            isDense: true,
            prefixIcon: const Icon(Icons.calendar_today_outlined, size: 18),
            prefixIconConstraints: const BoxConstraints(minWidth: 38, minHeight: 0),
            suffixIcon: value == null
                ? null
                : IconButton(
                    tooltip: 'Clear',
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                    icon: const Icon(Icons.close_rounded, size: 18),
                    onPressed: () => onChanged(null),
                  ),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
          ),
          child: Text(
            value == null ? '—' : _fmt.format(value),
            style: const TextStyle(fontSize: 14),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;

    return FadeSlideIn(
      child: Padding(
        padding: padding,
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: dark ? const Color(0xFF1E1E2E) : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: dark ? const Color(0xFF343448) : const Color(0xFFE3E8F0)),
            boxShadow: dark
                ? null
                : const [
                    BoxShadow(color: Color(0x10193340), blurRadius: 10, offset: Offset(0, 3)),
                  ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Expanded(
                    child: _field(context, startLabel, start, onStart, last: end),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _field(context, endLabel, end, onEnd, first: start),
                  ),
                ],
              ),
              for (final w in extra) ...[const SizedBox(height: 10), w],
            ],
          ),
        ),
      ),
    );
  }
}
