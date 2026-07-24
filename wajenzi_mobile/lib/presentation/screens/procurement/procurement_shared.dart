import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

export '../../../core/utils/permissions.dart';

/// Shared helpers for the procurement module screens.
///
/// Statuses in procurement are dual-tracked (model `status` column plus
/// RingleSoft `approval_status`) and case-inconsistent on the web side
/// ('pending' vs 'APPROVED'). Always normalise with [normalizeStatus] before
/// comparing or colouring.

String normalizeStatus(String? status) => (status ?? '').trim().toUpperCase();

Color procurementStatusColor(String? status) {
  switch (normalizeStatus(status)) {
    case 'APPROVED':
    case 'COMPLETE':
    case 'COMPLETED':
    case 'INSPECTED':
    case 'SELECTED':
    case 'CLOSED':
    case 'PAID':
      return Colors.green;
    case 'PENDING':
    case 'SUBMITTED':
    case 'IN_PROGRESS':
    case 'PARTIALLY_DELIVERED':
    case 'RECEIVED':
      return Colors.orange;
    case 'REJECTED':
    case 'DISCARDED':
    case 'FAILED':
      return Colors.red;
    case 'DRAFT':
    case 'NOT_STARTED':
    default:
      return Colors.blueGrey;
  }
}

class ProcurementStatusChip extends StatelessWidget {
  final String? status;
  const ProcurementStatusChip({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final color = procurementStatusColor(status);
    final label = normalizeStatus(status).replaceAll('_', ' ');
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(
        label.isEmpty ? 'N/A' : label,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

final NumberFormat _moneyFormat = NumberFormat('#,##0.00');
final NumberFormat _qtyFormat = NumberFormat('#,##0.##');
final DateFormat _dateFormat = DateFormat('dd MMM yyyy');

String fmtMoney(num? value) => value == null ? '-' : _moneyFormat.format(value);

String fmtQty(num? value) => value == null ? '-' : _qtyFormat.format(value);

String fmtDate(dynamic value) {
  if (value == null) return '-';
  final parsed = value is DateTime ? value : DateTime.tryParse(value.toString());
  return parsed == null ? '-' : _dateFormat.format(parsed);
}

/// Normalises Dio / API errors into a human-friendly message using the
/// standard {success, message, errors} envelope.
String procurementErrorMessage(Object error) {
  try {
    final dyn = error as dynamic;
    final data = dyn.response?.data;
    if (data is Map) {
      final message = data['message']?.toString();
      if (message != null && message.isNotEmpty) return message;
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
    }
  } catch (_) {}
  final fallback = error.toString();
  if (fallback.startsWith('Exception: ')) return fallback.substring(11);
  return 'Something went wrong. Please try again.';
}
