import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

/// The web's "Create New Payroll" modal: pick the month, review every staff
/// member's computed payroll, then create it. The server computes the values
/// and runs the web's own create_payroll, so the saved payroll is the same.
/// Pops `true` once created.
class PayrollCreateSheet extends ConsumerStatefulWidget {
  const PayrollCreateSheet({super.key});

  @override
  ConsumerState<PayrollCreateSheet> createState() => _PayrollCreateSheetState();
}

class _PayrollCreateSheetState extends ConsumerState<PayrollCreateSheet> {
  static const _months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
  ];

  late int _month = DateTime.now().month;
  late int _year = DateTime.now().year;
  List<Map<String, dynamic>> _rows = [];
  List<String> _headers = [];
  bool _loading = false;
  bool _creating = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadPreview();
  }

  String _message(DioException e, String fallback) {
    final data = e.response?.data;
    return data is Map && data['message'] != null
        ? data['message'].toString()
        : fallback;
  }

  Future<void> _loadPreview() async {
    setState(() {
      _loading = true;
      _error = null;
      _rows = [];
    });
    try {
      final response = await ref.read(apiClientProvider).get(
        '/payroll-administration/create-preview',
        queryParameters: {'month': _month, 'year': _year},
      );
      final meta = response.data['meta'] as Map<String, dynamic>? ?? const {};
      if (!mounted) return;
      setState(() {
        _rows = (response.data['data'] as List? ?? const [])
            .cast<Map<String, dynamic>>();
        _headers = (meta['headers'] as List? ?? const [])
            .map((e) => '$e')
            .toList();
      });
    } on DioException catch (e) {
      if (mounted) setState(() => _error = _message(e, 'Failed to load the payroll'));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _create(bool isSwahili) async {
    setState(() {
      _creating = true;
      _error = null;
    });
    try {
      await ref.read(apiClientProvider).post(
        '/payroll-administration/create',
        data: {'month': _month, 'year': _year},
      );
      if (mounted) Navigator.pop(context, true);
    } on DioException catch (e) {
      if (mounted) {
        setState(() => _error = _message(e, isSwahili ? 'Imeshindwa kuunda' : 'Failed to create the payroll'));
      }
    } finally {
      if (mounted) setState(() => _creating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final years = List<int>.generate(5, (i) => DateTime.now().year - 3 + i);
    final shown = _headers.where((h) => h != '#' && h != 'Name').toList();

    return Container(
      height: MediaQuery.of(context).size.height * 0.9,
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            isSwahili ? 'Unda Payroll Mpya' : 'Create New Payroll',
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: DropdownButtonFormField<int>(
      isExpanded: true,
                  initialValue: _month,
                  decoration: InputDecoration(labelText: isSwahili ? 'Mwezi' : 'Month'),
                  items: [
                    for (var i = 0; i < 12; i++)
                      DropdownMenuItem(value: i + 1, child: Text(_months[i])),
                  ],
                  onChanged: _creating
                      ? null
                      : (v) {
                          _month = v ?? _month;
                          _loadPreview();
                        },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DropdownButtonFormField<int>(
      isExpanded: true,
                  initialValue: _year,
                  decoration: InputDecoration(labelText: isSwahili ? 'Mwaka' : 'Year'),
                  items: [
                    for (final y in years)
                      DropdownMenuItem(value: y, child: Text('$y')),
                  ],
                  onChanged: _creating
                      ? null
                      : (v) {
                          _year = v ?? _year;
                          _loadPreview();
                        },
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Text(_error!, style: const TextStyle(color: AppColors.error)),
            ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : ListView(
                    children: [
                      if (_rows.isNotEmpty)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: Text(
                            '${_months[_month - 1]} $_year · ${_rows.length} ${isSwahili ? 'wafanyakazi' : 'staff'}',
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                        ),
                      for (final row in _rows)
                        Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          child: ExpansionTile(
                            title: Text(
                              '${row['Name'] ?? ''}',
                              style: const TextStyle(fontWeight: FontWeight.w600),
                            ),
                            subtitle: Text('NET ${row['NET'] ?? '-'}'),
                            childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                            children: [
                              for (final h in shown)
                                Padding(
                                  padding: const EdgeInsets.symmetric(vertical: 3),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [Text(h), Text('${row[h] ?? ''}')],
                                  ),
                                ),
                            ],
                          ),
                        ),
                    ],
                  ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: _creating ? null : () => Navigator.pop(context, false),
                  child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton(
                  onPressed: (_creating || _loading || _rows.isEmpty)
                      ? null
                      : () => _create(isSwahili),
                  child: _creating
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text(isSwahili ? 'Unda Payroll' : 'Create Payroll'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
