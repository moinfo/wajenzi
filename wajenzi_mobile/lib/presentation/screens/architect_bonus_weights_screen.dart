import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/network/api_client.dart';
import '../../core/router/app_router.dart';
import '../providers/settings_provider.dart';
import '../widgets/common/loading_widget.dart';

class ArchitectBonusWeightsScreen extends ConsumerStatefulWidget {
  const ArchitectBonusWeightsScreen({super.key});

  @override
  ConsumerState<ArchitectBonusWeightsScreen> createState() =>
      _ArchitectBonusWeightsScreenState();
}

class _ArchitectBonusWeightsScreenState
    extends ConsumerState<ArchitectBonusWeightsScreen> {
  final NumberFormat _money = NumberFormat('#,##0');
  bool _isLoading = false;
  bool _isSavingWeights = false;
  Map<String, double> _weights = <String, double>{};
  List<Map<String, dynamic>> _tiers = <Map<String, dynamic>>[];

  @override
  void initState() {
    super.initState();
    _loadWeights();
  }

  Future<void> _loadWeights() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/architect-bonus/weights');
      final data = response.data['data'] as Map<String, dynamic>? ?? {};
      final weights = (data['weights'] as List<dynamic>? ?? <dynamic>[])
          .map((item) => Map<String, dynamic>.from(item as Map))
          .toList();
      final tiers = (data['tiers'] as List<dynamic>? ?? <dynamic>[])
          .map((item) => Map<String, dynamic>.from(item as Map))
          .toList();

      setState(() {
        _weights = {
          for (final weight in weights)
            '${weight['factor']}':
                double.tryParse('${weight['weight'] ?? 0}') ?? 0,
        };
        _tiers = tiers;
        _isLoading = false;
      });
    } catch (error) {
      setState(() {
        _isLoading = false;
      });
      _showSnackBar(_humanizeError(error), isError: true);
    }
  }

  String _humanizeError(Object error) {
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map<String, dynamic>) {
        final message = data['message']?.toString();
        if (message != null && message.isNotEmpty) return message;
      }
      switch (error.response?.statusCode) {
        case 401:
          return 'Authentication required. Please login again.';
        case 403:
          return 'Permission denied. You may not have access to bonus settings.';
        case 404:
          return 'Bonus settings endpoint not found.';
      }
    }
    return 'Error loading bonus settings.';
  }

  void _showSnackBar(String message, {required bool isError}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

  double _totalWeight() {
    return _weights.values.fold<double>(0, (sum, weight) => sum + weight);
  }

  void _updateWeight(String factor, double value) {
    setState(() {
      _weights[factor] = value;
    });
  }

  Future<void> _saveWeights() async {
    final total = _totalWeight();
    if ((total - 1.0).abs() > 0.01) {
      _showSnackBar(
        'Weights must sum to 100%. Current total: ${(total * 100).toStringAsFixed(1)}%',
        isError: true,
      );
      return;
    }

    setState(() {
      _isSavingWeights = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.put(
        '/architect-bonus/weights',
        data: {'weights': _weights},
      );
      _showSnackBar(
        response.data['message']?.toString() ??
            'Bonus weights updated successfully.',
        isError: false,
      );
      await _loadWeights();
    } catch (error) {
      _showSnackBar(_humanizeError(error), isError: true);
    } finally {
      if (mounted) {
        setState(() {
          _isSavingWeights = false;
        });
      }
    }
  }

  Future<void> _showEditTierSheet(Map<String, dynamic> tier) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final formKey = GlobalKey<FormState>();
    final minController = TextEditingController(
      text: '${(double.tryParse('${tier['min_amount'] ?? 0}') ?? 0).toStringAsFixed(0)}',
    );
    final maxController = TextEditingController(
      text: '${(double.tryParse('${tier['max_amount'] ?? 0}') ?? 0).toStringAsFixed(0)}',
    );
    final unitsController = TextEditingController(
      text: '${tier['max_units'] ?? 0}',
    );
    var isSaving = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (sheetContext, setSheetState) {
            final maxUnits = int.tryParse(unitsController.text.trim()) ?? 0;
            final maxBonus = maxUnits * 10000;

            Future<void> saveTier() async {
              if (!formKey.currentState!.validate()) return;

              setSheetState(() {
                isSaving = true;
              });

              try {
                final api = ref.read(apiClientProvider);
                final response = await api.put(
                  '/architect-bonus/tier/${tier['id']}',
                  data: {
                    'min_amount':
                        double.parse(minController.text.trim()),
                    'max_amount':
                        double.parse(maxController.text.trim()),
                    'max_units': int.parse(unitsController.text.trim()),
                  },
                );

                if (!mounted) return;
                Navigator.pop(sheetContext);
                _showSnackBar(
                  response.data['message']?.toString() ??
                      'Tier updated successfully.',
                  isError: false,
                );
                await _loadWeights();
              } catch (error) {
                setSheetState(() {
                  isSaving = false;
                });
                _showSnackBar(_humanizeError(error), isError: true);
              }
            }

            return SafeArea(
              top: false,
              child: Container(
                decoration: BoxDecoration(
                  color: Theme.of(sheetContext).scaffoldBackgroundColor,
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(24),
                  ),
                ),
                padding: EdgeInsets.fromLTRB(
                  20,
                  16,
                  20,
                  20 + MediaQuery.of(sheetContext).viewInsets.bottom,
                ),
                child: Form(
                  key: formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 44,
                        height: 4,
                        decoration: BoxDecoration(
                          color: Colors.grey[400],
                          borderRadius: BorderRadius.circular(999),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              isSwahili ? 'Hariri Tier' : 'Edit Tier',
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          TextButton.icon(
                            onPressed: () => Navigator.pop(sheetContext),
                            icon: const Icon(Icons.close),
                            label: Text(isSwahili ? 'Funga' : 'Close'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: minController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'Min Amount (TZS) *',
                        ),
                        validator: (value) {
                          final parsed = double.tryParse((value ?? '').trim());
                          if (parsed == null || parsed < 0) {
                            return 'Enter a valid minimum amount';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: maxController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'Max Amount (TZS) *',
                        ),
                        validator: (value) {
                          final parsed = double.tryParse((value ?? '').trim());
                          final minParsed = double.tryParse(
                            minController.text.trim(),
                          );
                          if (parsed == null || minParsed == null) {
                            return 'Enter a valid maximum amount';
                          }
                          if (parsed <= minParsed) {
                            return 'Max amount must be greater than min amount';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: unitsController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Max Units *',
                        ),
                        validator: (value) {
                          final parsed = int.tryParse((value ?? '').trim());
                          if (parsed == null || parsed < 1) {
                            return 'Enter valid max units';
                          }
                          return null;
                        },
                        onChanged: (_) => setSheetState(() {}),
                      ),
                      const SizedBox(height: 12),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.blue.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          'Max Bonus: TZS ${_money.format(maxBonus)}',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () => Navigator.pop(sheetContext),
                              icon: const Icon(Icons.close),
                              label: Text(isSwahili ? 'Funga' : 'Close'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: ElevatedButton.icon(
                              onPressed: isSaving ? null : saveTier,
                              icon: isSaving
                                  ? const SizedBox(
                                      width: 16,
                                      height: 16,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Icon(Icons.save),
                              label: Text(isSwahili ? 'Hifadhi' : 'Save'),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  String _weightDescription(String factor, bool isSwahili) {
    switch (factor) {
      case 'schedule':
        return isSwahili
            ? 'Utendaji wa ratiba na kukamilisha kwa wakati.'
            : 'Schedule performance and on-time completion.';
      case 'quality':
        return isSwahili
            ? 'Ubora wa muundo na viwango vya kitaalamu.'
            : 'Design quality and technical standards.';
      case 'client':
        return isSwahili
            ? 'Ufanisi wa idhini ya mteja na marekebisho.'
            : 'Client approval efficiency and revision count.';
      default:
        return isSwahili
            ? 'Uzito wa utendaji.'
            : 'Performance weight.';
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final total = _totalWeight();
    final isTotalValid = (total - 1.0).abs() <= 0.01;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili
              ? 'Mipangilio ya Bonasi'
              : 'Bonus Settings',
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _isLoading ? null : _loadWeights,
            tooltip: isSwahili ? 'Onyesha Upya' : 'Refresh',
          ),
        ],
      ),
      body: _isLoading
          ? LoadingWidget(
              message: isSwahili
                  ? 'Inapakia mipangilio ya bonasi...'
                  : 'Loading bonus settings...',
            )
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili
                              ? 'Performance Weights'
                              : 'Performance Weights',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          isSwahili
                              ? 'Jumla ya uzito lazima iwe 100% kama kwenye web.'
                              : 'Weights must total 100%, matching the web settings page.',
                          style: TextStyle(color: Colors.grey[600]),
                        ),
                        const SizedBox(height: 16),
                        ..._weights.entries.map(
                          (entry) => _WeightEditor(
                            factor: entry.key,
                            value: entry.value,
                            description: _weightDescription(
                              entry.key,
                              isSwahili,
                            ),
                            onChanged: (value) =>
                                _updateWeight(entry.key, value),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: (isTotalValid ? Colors.green : Colors.red)
                                .withOpacity(0.08),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isTotalValid ? Colors.green : Colors.red,
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                isTotalValid
                                    ? Icons.check_circle
                                    : Icons.error_outline,
                                color: isTotalValid ? Colors.green : Colors.red,
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  isTotalValid
                                      ? 'Total ${(total * 100).toStringAsFixed(1)}% - Valid'
                                      : 'Total ${(total * 100).toStringAsFixed(1)}% - Must equal 100%',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    color:
                                        isTotalValid ? Colors.green : Colors.red,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed:
                                (_isSavingWeights || !isTotalValid)
                                ? null
                                : _saveWeights,
                            icon: _isSavingWeights
                                ? const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : const Icon(Icons.save),
                            label: Text(
                              isSwahili ? 'Hifadhi Uzito' : 'Save Weights',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili
                              ? 'Unit Tiers'
                              : 'Unit Tiers (1 Unit = TZS 10,000)',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          isSwahili
                              ? 'Hariri min amount, max amount, na max units kama kwenye web.'
                              : 'Edit min amount, max amount, and max units just like the web page.',
                          style: TextStyle(color: Colors.grey[600]),
                        ),
                        const SizedBox(height: 16),
                        ..._tiers.map(
                          (tier) => Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: _TierCard(
                              tier: tier,
                              formatMoney: (value) => _money.format(
                                value is num
                                    ? value
                                    : double.tryParse('$value') ?? 0,
                              ),
                              onEdit: () => _showEditTierSheet(tier),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
    );
  }
}

class _WeightEditor extends StatelessWidget {
  const _WeightEditor({
    required this.factor,
    required this.value,
    required this.description,
    required this.onChanged,
  });

  final String factor;
  final double value;
  final String description;
  final ValueChanged<double> onChanged;

  @override
  Widget build(BuildContext context) {
    final title = factor
        .split('_')
        .map((word) => word[0].toUpperCase() + word.substring(1))
        .join(' ');

    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.primary.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  '${(value * 100).toStringAsFixed(0)}%',
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(description, style: TextStyle(color: Colors.grey[600])),
          Slider(
            value: value,
            min: 0,
            max: 1,
            divisions: 20,
            onChanged: onChanged,
          ),
        ],
      ),
    );
  }
}

class _TierCard extends StatelessWidget {
  const _TierCard({
    required this.tier,
    required this.formatMoney,
    required this.onEdit,
  });

  final Map<String, dynamic> tier;
  final String Function(dynamic value) formatMoney;
  final VoidCallback onEdit;

  @override
  Widget build(BuildContext context) {
    final maxBonus = tier['max_bonus_amount'] ??
        ((int.tryParse('${tier['max_units'] ?? 0}') ?? 0) * 10000);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.shade300),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${tier['name'] ?? 'Tier'}',
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              OutlinedButton.icon(
                onPressed: onEdit,
                icon: const Icon(Icons.edit_outlined, size: 16),
                label: const Text('Edit'),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _TierMetric(
                label: 'Min Amount',
                value: 'TZS ${formatMoney(tier['min_amount'])}',
              ),
              _TierMetric(
                label: 'Max Amount',
                value: 'TZS ${formatMoney(tier['max_amount'])}',
              ),
              _TierMetric(
                label: 'Max Units',
                value: '${tier['max_units'] ?? 0}',
              ),
              _TierMetric(
                label: 'Max Bonus',
                value: 'TZS ${formatMoney(maxBonus)}',
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _TierMetric extends StatelessWidget {
  const _TierMetric({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.grey.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(label, style: TextStyle(fontSize: 11, color: Colors.grey[600])),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}
