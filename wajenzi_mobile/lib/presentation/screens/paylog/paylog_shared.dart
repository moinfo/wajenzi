import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

// The paylog module owns its own shared helpers; it deliberately does NOT
// import procurement_shared. Re-export permission helpers so screens can gate
// actions via a single import.
export '../../../core/utils/permissions.dart';

/// Inline i18n passthrough. Paylog screens use plain English strings; this
/// keeps a single seam should localisation land later.
String tr(String source) => source;

/// Maps the server `status_color` token (Bootstrap-flavoured:
/// success|primary|danger|warning|info) to a Flutter [Color]. This is the
/// single source of truth — DTOs carry the token, the UI resolves it here.
Color paylogStatusColor(String? token) {
  switch ((token ?? 'info').trim().toLowerCase()) {
    case 'success':
      return Colors.green;
    case 'primary':
      return Colors.blue;
    case 'danger':
      return Colors.red;
    case 'warning':
      return Colors.orange;
    case 'info':
    default:
      return Colors.blueGrey;
  }
}

/// A pill showing a payment request's [displayStatus] tinted by its
/// [statusColor] token.
class PaylogStatusChip extends StatelessWidget {
  final String label;
  final String? colorToken;

  const PaylogStatusChip({
    super.key,
    required this.label,
    required this.colorToken,
  });

  @override
  Widget build(BuildContext context) {
    final color = paylogStatusColor(colorToken);
    final text = label.trim().isEmpty ? 'N/A' : label.trim();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.5)),
      ),
      child: Text(
        text,
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
final DateFormat _dateFormat = DateFormat('dd MMM yyyy');

/// Formats a TZS amount with thousands separators (no currency symbol).
String fmtMoney(num? value) => value == null ? '-' : _moneyFormat.format(value);

String fmtDate(dynamic value) {
  if (value == null) return '-';
  final parsed =
      value is DateTime ? value : DateTime.tryParse(value.toString());
  return parsed == null ? '-' : _dateFormat.format(parsed);
}

/// Normalises Dio / API errors into a human-friendly message using the
/// standard {success, message, errors} envelope.
String paylogErrorMessage(Object error) {
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
