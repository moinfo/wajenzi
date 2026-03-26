import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _exchangeRatesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/exchange-rates');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _exchangeRateRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/exchange-rates/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class ExchangeRatesScreen extends ConsumerWidget {
  const ExchangeRatesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ratesAsync = ref.watch(_exchangeRatesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Exchange Rates' : 'Exchange Rates'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_exchangeRatesProvider),
        child: ratesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ExchangeRateErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_exchangeRatesProvider),
          ),
          data: (rates) {
            if (rates.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.currency_exchange, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna exchange rates' : 'No exchange rates found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: rates.length + 1,
              itemBuilder: (context, index) {
                if (index == rates.length) return const SizedBox(height: 80);
                final rate = rates[index];
                final pair = '${rate['foreign_currency_name'] ?? '-'} (${rate['foreign_currency_symbol'] ?? ''}) -> ${rate['base_currency_name'] ?? '-'} (${rate['base_currency_symbol'] ?? ''})';
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    isThreeLine: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                      child: const Icon(Icons.currency_exchange, color: AppColors.primary),
                    ),
                    title: Text(
                      pair,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${isSwahili ? 'Rate' : 'Rate'}: ${rate['rate']}\n${isSwahili ? 'Period' : 'Period'}: ${rate['month']}/${rate['year']}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDetails(context, rate, isSwahili, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, rate: rate);
                        } else if (value == 'delete') {
                          _deleteRate(context, ref, rate);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
                      ],
                    ),
                    onTap: () => _showDetails(context, rate, isSwahili, isDarkMode),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? rate}) async {
    final refs = await ref.read(_exchangeRateRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.84,
        child: _ExchangeRateFormSheet(refs: refs, rate: rate),
      ),
    );
    if (result == true) ref.invalidate(_exchangeRatesProvider);
  }

  Future<void> _deleteRate(BuildContext context, WidgetRef ref, Map<String, dynamic> rate) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Exchange Rate' : 'Delete Exchange Rate'),
        content: Text(isSwahili ? 'Je, unataka kufuta record hii?' : 'Delete this exchange rate?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/exchange-rates/${rate['id']}');
      ref.invalidate(_exchangeRatesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Exchange rate imefutwa' : 'Exchange rate deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(BuildContext context, Map<String, dynamic> rate, bool isSwahili, bool isDarkMode) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.58,
        child: Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            top: false,
            child: Column(
              children: [
                const SizedBox(height: 12),
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                    children: [
                      Text(
                        '${rate['foreign_currency_name'] ?? '-'} -> ${rate['base_currency_name'] ?? '-'}',
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 16),
                      _detailLine('Foreign Currency', '${rate['foreign_currency_name'] ?? '-'} (${rate['foreign_currency_symbol'] ?? ''})'),
                      _detailLine('Base Currency', '${rate['base_currency_name'] ?? '-'} (${rate['base_currency_symbol'] ?? ''})'),
                      _detailLine('Rate', rate['rate']),
                      _detailLine('Month', rate['month']),
                      _detailLine('Year', rate['year']),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _detailLine(String label, dynamic value) {
    final text = (value ?? '').toString().trim();
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(fontSize: 13, color: AppColors.textPrimary),
          children: [
            TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w700)),
            TextSpan(text: text.isEmpty ? '-' : text),
          ],
        ),
      ),
    );
  }
}

class _ExchangeRateFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? rate;

  const _ExchangeRateFormSheet({
    required this.refs,
    this.rate,
  });

  @override
  ConsumerState<_ExchangeRateFormSheet> createState() => _ExchangeRateFormSheetState();
}

class _ExchangeRateFormSheetState extends ConsumerState<_ExchangeRateFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _rateController = TextEditingController(text: widget.rate?['rate']?.toString() ?? '');
  late final TextEditingController _monthController = TextEditingController(text: widget.rate?['month']?.toString() ?? '');
  late final TextEditingController _yearController = TextEditingController(text: widget.rate?['year']?.toString() ?? '');
  int? _foreignCurrencyId;
  int? _baseCurrencyId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _foreignCurrencyId = _toNullableInt(widget.rate?['foreign_currency_id']);
    _baseCurrencyId = _toNullableInt(widget.rate?['base_currency_id']);
  }

  @override
  void dispose() {
    _rateController.dispose();
    _monthController.dispose();
    _yearController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final currencies = _toMaps(widget.refs['currencies']);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Column(
          children: [
            const SizedBox(height: 12),
            Container(
              width: 44,
              height: 5,
              decoration: BoxDecoration(
                color: isDarkMode ? Colors.white24 : Colors.black12,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            Expanded(
              child: ListView(
                padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.of(context).viewInsets.bottom + 24),
                children: [
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          widget.rate == null ? 'New Exchange Rate' : 'Edit Exchange Rate',
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Foreign Currency *' : 'Foreign Currency *',
                          items: currencies.map((e) => {'id': e['id'], 'name': '${e['name']} (${e['symbol'] ?? ''})'}).toList(),
                          value: _foreignCurrencyId,
                          onChanged: (value) => setState(() => _foreignCurrencyId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Base Currency *' : 'Base Currency *',
                          items: currencies.map((e) => {'id': e['id'], 'name': '${e['name']} (${e['symbol'] ?? ''})'}).toList(),
                          value: _baseCurrencyId,
                          onChanged: (value) => setState(() => _baseCurrencyId = value),
                        ),
                        const SizedBox(height: 12),
                        _input(_rateController, isSwahili ? 'Rate *' : 'Rate *', isDarkMode, keyboardType: const TextInputType.numberWithOptions(decimal: true)),
                        const SizedBox(height: 12),
                        _input(_monthController, isSwahili ? 'Month *' : 'Month *', isDarkMode, keyboardType: TextInputType.number, validator: (value) {
                          final month = int.tryParse(value ?? '');
                          if (month == null || month < 1 || month > 12) return 'Enter month 1-12';
                          return null;
                        }),
                        const SizedBox(height: 12),
                        _input(_yearController, isSwahili ? 'Year *' : 'Year *', isDarkMode, keyboardType: TextInputType.number, validator: (value) {
                          final year = int.tryParse(value ?? '');
                          if (year == null || year < 2000 || year > 2100) return 'Enter valid year';
                          return null;
                        }),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.rate == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _input(
    TextEditingController controller,
    String label,
    bool isDarkMode, {
    TextInputType? keyboardType,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: validator ?? (value) => value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dropdown({
    required bool isDarkMode,
    required String label,
    required List<Map<String, dynamic>> items,
    required int? value,
    required ValueChanged<int?> onChanged,
  }) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: items.any((item) => _toInt(item['id']) == value) ? value : null,
      validator: (selected) => selected == null ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map((item) => DropdownMenuItem<int>(
                value: _toInt(item['id']),
                child: Text(item['name']?.toString() ?? '-', overflow: TextOverflow.ellipsis),
              ))
          .toList(),
      onChanged: onChanged,
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_foreignCurrencyId == _baseCurrencyId) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Foreign and base currency must be different'),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'foreign_currency_id': _foreignCurrencyId,
        'base_currency_id': _baseCurrencyId,
        'rate': _rateController.text.trim(),
        'month': _monthController.text.trim(),
        'year': _yearController.text.trim(),
      };

      if (widget.rate == null) {
        await api.post('/exchange-rates', data: data);
      } else {
        await api.put('/exchange-rates/${widget.rate!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider))),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _ExchangeRateErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ExchangeRateErrorView({
    required this.message,
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
        Text(isSwahili ? 'Hitilafu imetokea' : 'Something went wrong', textAlign: TextAlign.center),
        const SizedBox(height: 8),
        Text(message, textAlign: TextAlign.center),
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

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}
