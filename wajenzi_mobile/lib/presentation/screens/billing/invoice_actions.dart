import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/motion.dart';

/// The four cards the web Invoices page shows above its list: Total Invoices
/// (with the overdue count), Total Billed, Amount Collected and Outstanding.
final invoiceSummaryProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final response = await ref.watch(apiClientProvider).get('/billing/invoices/summary');
  return Map<String, dynamic>.from(response.data['data'] as Map);
});

String _tzs(num v) => 'TZS ${NumberFormat('#,##0', 'en').format(v)}';

class InvoiceSummaryCards extends ConsumerWidget {
  const InvoiceSummaryCards({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final data = ref.watch(invoiceSummaryProvider).valueOrNull;
    if (data == null) return const SizedBox(height: 4);

    final isSwahili = ref.watch(isSwahiliProvider);
    final dark = Theme.of(context).brightness == Brightness.dark;
    num n(String k) => (data[k] as num?) ?? 0;
    final overdue = n('overdue_count').toInt();

    final cards = [
      (
        icon: Icons.description_rounded,
        color: const Color(0xFF1BC5BD),
        label: isSwahili ? 'JUMLA YA ANKARA' : 'TOTAL INVOICES',
        value: NumberFormat('#,##0', 'en').format(n('total_count')),
        sub: overdue > 0 ? '$overdue ${isSwahili ? 'zimechelewa' : 'overdue'}' : null,
        valueColor: null as Color?,
      ),
      (
        icon: Icons.payments_rounded,
        color: const Color(0xFF4285F4),
        label: isSwahili ? 'JUMLA ILIYOTOLEWA' : 'TOTAL BILLED',
        value: _tzs(n('total_amount')),
        sub: null,
        valueColor: null,
      ),
      (
        icon: Icons.check_circle_rounded,
        color: const Color(0xFF22C55E),
        label: isSwahili ? 'ILIYOKUSANYWA' : 'AMOUNT COLLECTED',
        value: _tzs(n('paid_amount')),
        sub: null,
        valueColor: const Color(0xFF22C55E),
      ),
      (
        icon: Icons.hourglass_bottom_rounded,
        color: const Color(0xFFF59E0B),
        label: isSwahili ? 'INAYODAIWA' : 'OUTSTANDING',
        value: _tzs(n('balance_amount')),
        sub: null,
        valueColor: const Color(0xFFF59E0B),
      ),
    ];

    return SizedBox(
      height: 94,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
        itemCount: cards.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, i) {
          final c = cards[i];
          return FadeSlideIn(
            index: i,
            child: Container(
              width: 214,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: dark ? const Color(0xFF1E1E2E) : Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: dark ? const Color(0xFF343448) : const Color(0xFFE3E8F0)),
              ),
              child: Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: c.color.withValues(alpha: dark ? 0.22 : 0.13),
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: Icon(c.icon, color: c.color, size: 22),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          c.label,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.4,
                            color: dark ? const Color(0xFFB0B0C8) : const Color(0xFF7A8794),
                          ),
                        ),
                        const SizedBox(height: 2),
                        FittedBox(
                          fit: BoxFit.scaleDown,
                          alignment: AlignmentDirectional.centerStart,
                          child: Text(
                            c.value,
                            style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: c.valueColor),
                          ),
                        ),
                        if (c.sub != null)
                          Text(c.sub!, style: const TextStyle(fontSize: 11, color: Color(0xFFE53E3E), fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

/// The web's Record Payment form: amount (up to the balance), payment date,
/// payment method and reference number. Returns true once the payment is saved.
Future<bool> showRecordPaymentDialog(
  BuildContext context,
  Map<String, dynamic> invoice,
) async {
  final saved = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    builder: (_) => _RecordPaymentSheet(invoice: invoice),
  );
  return saved == true;
}

class _RecordPaymentSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> invoice;
  const _RecordPaymentSheet({required this.invoice});

  @override
  ConsumerState<_RecordPaymentSheet> createState() => _RecordPaymentSheetState();
}

class _RecordPaymentSheetState extends ConsumerState<_RecordPaymentSheet> {
  static const _methods = {
    'cash': 'Cash',
    'bank_transfer': 'Bank Transfer',
    'cheque': 'Cheque',
    'credit_card': 'Credit Card',
    'mobile_money': 'Mobile Money',
    'online': 'Online Payment',
    'other': 'Other',
  };

  final _formKey = GlobalKey<FormState>();
  late final double _balance = double.tryParse('${widget.invoice['balance_amount']}') ?? 0;
  late final _amount = TextEditingController(text: _balance.toStringAsFixed(2));
  final _date = TextEditingController(text: DateFormat('yyyy-MM-dd').format(DateTime.now()));
  final _reference = TextEditingController();
  final _notes = TextEditingController();
  String _method = 'cash';
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _amount.dispose();
    _date.dispose();
    _reference.dispose();
    _notes.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await ref.read(apiClientProvider).post(
        '/billing/invoices/${widget.invoice['id']}/payments',
        data: {
          'amount': _amount.text.trim(),
          'payment_date': _date.text,
          'payment_method': _method,
          if (_reference.text.trim().isNotEmpty) 'reference_number': _reference.text.trim(),
          if (_notes.text.trim().isNotEmpty) 'notes': _notes.text.trim(),
        },
      );
      if (mounted) Navigator.pop(context, true);
    } on DioException catch (e) {
      final data = e.response?.data;
      var message = 'Failed to record the payment';
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          message = errors.values.map((v) => v is List ? v.join(' ') : '$v').join('\n');
        } else if (data['message'] != null) {
          message = '${data['message']}';
        }
      }
      if (mounted) setState(() => _error = message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    String? req(String? v) => (v == null || v.trim().isEmpty) ? (isSwahili ? 'Hitaji' : 'Required') : null;

    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                '${isSwahili ? 'Rekodi Malipo' : 'Record Payment'} · ${widget.invoice['document_number'] ?? ''}',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 4),
              Text(
                '${isSwahili ? 'Salio' : 'Balance'}: ${_tzs(_balance)}',
                style: const TextStyle(color: Color(0xFFF59E0B), fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _amount,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: InputDecoration(labelText: '${isSwahili ? 'Kiasi' : 'Amount'} *'),
                validator: (v) {
                  final n = double.tryParse(v ?? '');
                  if (n == null || n < 0.01) return isSwahili ? 'Weka kiasi sahihi' : 'Enter a valid amount';
                  if (n > _balance) return isSwahili ? 'Kiasi kinazidi salio' : 'More than the balance';
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _date,
                readOnly: true,
                decoration: InputDecoration(
                  labelText: '${isSwahili ? 'Tarehe ya Malipo' : 'Payment Date'} *',
                  suffixIcon: const Icon(Icons.calendar_today_outlined),
                ),
                validator: req,
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: DateTime.tryParse(_date.text) ?? DateTime.now(),
                    firstDate: DateTime(2000),
                    lastDate: DateTime(2100),
                  );
                  if (picked != null) setState(() => _date.text = DateFormat('yyyy-MM-dd').format(picked));
                },
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                isExpanded: true,
                initialValue: _method,
                decoration: InputDecoration(labelText: '${isSwahili ? 'Njia ya Malipo' : 'Payment Method'} *'),
                items: [for (final e in _methods.entries) DropdownMenuItem(value: e.key, child: Text(e.value))],
                onChanged: (v) => setState(() => _method = v ?? 'cash'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _reference,
                decoration: InputDecoration(labelText: isSwahili ? 'Namba ya Kumbukumbu' : 'Reference Number'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notes,
                maxLines: 2,
                decoration: InputDecoration(labelText: isSwahili ? 'Maelezo' : 'Notes'),
              ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Text(_error!, style: const TextStyle(color: Color(0xFFE74C3C))),
                ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                    : Text(isSwahili ? 'Hifadhi Malipo' : 'Record Payment'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
