import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Pick a date range with two plain fields, Start Date and End Date, like the
/// web's filter, instead of the full-screen range calendar. Each field opens the
/// normal one-day calendar. Same call shape as `showDateRangePicker`, so it is
/// a drop-in replacement. Returns null when cancelled.
Future<DateTimeRange?> showDateRangeSheet({
  required BuildContext context,
  required DateTime firstDate,
  required DateTime lastDate,
  DateTimeRange? initialDateRange,
}) {
  return showModalBottomSheet<DateTimeRange>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    showDragHandle: true,
    builder: (_) => _DateRangeSheet(
      firstDate: firstDate,
      lastDate: lastDate,
      initial: initialDateRange,
    ),
  );
}

class _DateRangeSheet extends StatefulWidget {
  final DateTime firstDate;
  final DateTime lastDate;
  final DateTimeRange? initial;

  const _DateRangeSheet({
    required this.firstDate,
    required this.lastDate,
    this.initial,
  });

  @override
  State<_DateRangeSheet> createState() => _DateRangeSheetState();
}

class _DateRangeSheetState extends State<_DateRangeSheet> {
  static final _fmt = DateFormat('yyyy-MM-dd');

  late DateTime? _start = widget.initial?.start;
  late DateTime? _end = widget.initial?.end;
  String? _error;

  Future<void> _pick({required bool start}) async {
    final current = start ? _start : _end;
    final first = start ? widget.firstDate : (_start ?? widget.firstDate);
    final last = start ? (_end ?? widget.lastDate) : widget.lastDate;
    var initial = current ?? (start ? (_end ?? DateTime.now()) : DateTime.now());
    if (initial.isBefore(first)) initial = first;
    if (initial.isAfter(last)) initial = last;

    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: first,
      lastDate: last,
    );
    if (picked == null) return;
    setState(() {
      _error = null;
      if (start) {
        _start = DateTime(picked.year, picked.month, picked.day);
      } else {
        _end = DateTime(picked.year, picked.month, picked.day);
      }
    });
  }

  void _apply() {
    if (_start == null || _end == null) {
      setState(() => _error = 'Choose both the start and the end date');
      return;
    }
    if (_end!.isBefore(_start!)) {
      setState(() => _error = 'The end date is before the start date');
      return;
    }
    Navigator.pop(context, DateTimeRange(start: _start!, end: _end!));
  }

  Widget _field(String label, DateTime? value, VoidCallback onTap) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 4, left: 2),
          child: Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: dark ? const Color(0xFF8CC8FF) : const Color(0xFF3B9DE0),
            ),
          ),
        ),
        InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: onTap,
          child: InputDecorator(
            decoration: InputDecoration(
              isDense: true,
              prefixIcon: const Icon(Icons.calendar_today_outlined, size: 18),
              prefixIconConstraints: const BoxConstraints(minWidth: 38, minHeight: 0),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: Text(
              value == null ? '—' : _fmt.format(value),
              style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
            ),
          ),
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 4, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text('Select dates', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: _field('Start Date', _start, () => _pick(start: true))),
              const SizedBox(width: 12),
              Expanded(child: _field('End Date', _end, () => _pick(start: false))),
            ],
          ),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(top: 10),
              child: Text(_error!, style: const TextStyle(color: Color(0xFFE74C3C), fontSize: 12.5)),
            ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Cancel'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton(onPressed: _apply, child: const Text('Apply')),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
