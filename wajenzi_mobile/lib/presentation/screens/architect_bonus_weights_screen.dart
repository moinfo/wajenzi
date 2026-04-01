import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/api_client.dart';
import '../../core/router/app_router.dart';
import '../providers/auth_provider.dart';
import '../providers/settings_provider.dart';
import '../widgets/common/loading_widget.dart';
import '../widgets/common/error_widget.dart';

class ArchitectBonusWeightsScreen extends ConsumerStatefulWidget {
  const ArchitectBonusWeightsScreen({super.key});

  @override
  ConsumerState<ArchitectBonusWeightsScreen> createState() =>
      _ArchitectBonusWeightsScreenState();
}

class _ArchitectBonusWeightsScreenState
    extends ConsumerState<ArchitectBonusWeightsScreen> {
  bool _isLoading = false;
  bool _isSaving = false;
  Map<String, dynamic> _weightsData = {};
  Map<String, double> _weights = {};
  List<dynamic> _tiers = [];

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

      if (response.statusCode == 200) {
        final data = response.data['data'];

        setState(() {
          _weightsData = data;
          _weights = {
            for (var weight in data['weights'])
              weight['factor']: weight['weight'].toDouble(),
          };
          _tiers = data['tiers'] ?? [];
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });

      String errorMessage = 'Error loading weights configuration';

      if (e.toString().contains('401') ||
          e.toString().contains('Unauthorized')) {
        errorMessage = 'Authentication required. Please login again.';
      } else if (e.toString().contains('403') ||
          e.toString().contains('Forbidden')) {
        errorMessage =
            'Permission denied. You may not have access to weights configuration.';
      } else if (e.toString().contains('404')) {
        errorMessage =
            'Weights endpoint not found. Please check API configuration.';
      } else if (e.toString().contains('Connection')) {
        errorMessage =
            'Cannot connect to server. Please check your internet connection.';
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            duration: const Duration(seconds: 3),
            action: SnackBarAction(
              label: 'Retry',
              onPressed: () => _loadWeights(),
            ),
          ),
        );
      }
    }
  }

  Future<void> _saveWeights() async {
    // Validate weights sum to 1.0
    final total = _weights.values.fold<double>(
      0.0,
      (sum, weight) => sum + weight,
    );
    if ((total - 1.0).abs() > 0.01) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Weights must sum to 100%. Current total: ${(total * 100).toStringAsFixed(1)}%',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() {
      _isSaving = true;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.put(
        '/architect-bonus/weights',
        data: {'weights': _weights.map((key, value) => MapEntry(key, value))},
      );

      if (response.statusCode == 200) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Bonus weights updated successfully'),
              backgroundColor: Colors.green,
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error updating weights: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      setState(() {
        _isSaving = false;
      });
    }
  }

  void _updateWeight(String factor, double value) {
    setState(() {
      _weights[factor] = value;
    });
  }

  double _getTotalWeight() {
    return _weights.values.fold<double>(0.0, (sum, weight) => sum + weight);
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final totalWeight = _getTotalWeight();
    final isTotalValid = (totalWeight - 1.0).abs() <= 0.01;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () =>
              ref.read(rootScaffoldKeyProvider).currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili
              ? 'Mipangilio ya Uzito wa Bonasi'
              : 'Bonus Weights Configuration',
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: isSwahili ? 'Onyesha Upya' : 'Refresh',
            onPressed: _isLoading ? null : _loadWeights,
          ),
        ],
      ),
      body: _isLoading
          ? LoadingWidget(
              message: isSwahili
                  ? 'Inapakia mipangilio ya uzito...'
                  : 'Loading weights configuration...',
            )
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Weights Configuration Card
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili
                                ? 'Uzito wa Vifactori vya Utendaji'
                                : 'Performance Factor Weights',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.grey[800],
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            isSwahili
                                ? 'Rekebisha uzito wa kila factor katika hesabu ya bonasi. Jumla lazima iwe 100%.'
                                : 'Adjust the weight of each factor in bonus calculation. Weights must sum to 100%.',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey[600],
                            ),
                          ),
                          const SizedBox(height: 20),

                          // Weight Sliders
                          ..._weights.entries.map((entry) {
                            return _WeightSlider(
                              factor: entry.key,
                              value: entry.value,
                              description: _getWeightDescription(
                                entry.key,
                                isSwahili,
                              ),
                              onChanged: (value) =>
                                  _updateWeight(entry.key, value),
                              isSwahili: isSwahili,
                            );
                          }).toList(),

                          const SizedBox(height: 20),

                          // Total Weight Indicator
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: isTotalValid
                                  ? Colors.green.withOpacity(0.1)
                                  : Colors.red.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(
                                color: isTotalValid ? Colors.green : Colors.red,
                                width: 1,
                              ),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  isTotalValid
                                      ? Icons.check_circle
                                      : Icons.error,
                                  color: isTotalValid
                                      ? Colors.green
                                      : Colors.red,
                                  size: 20,
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Text(
                                    '${isSwahili ? 'Jumla' : 'Total'}: ${(totalWeight * 100).toStringAsFixed(1)}%',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 13,
                                      color: isTotalValid
                                          ? Colors.green
                                          : Colors.red,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                    maxLines: 1,
                                  ),
                                ),
                                if (!isTotalValid) ...[
                                  const SizedBox(width: 4),
                                  Text(
                                    isSwahili
                                        ? 'Lazima iwe 100%'
                                        : 'Must be 100%',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Colors.red,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Bonus Tiers Card
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili
                                ? 'Kiwango cha Vifurushi vya Bonasi'
                                : 'Bonus Unit Tiers',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.grey[800],
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            isSwahili
                                ? 'Kiwango cha sasa cha hesabu ya bonasi kulingana na vitengo vya utendaji.'
                                : 'Current bonus calculation tiers based on performance units.',
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.grey[600],
                            ),
                          ),
                          const SizedBox(height: 16),

                          // Tiers List
                          ..._tiers
                              .map(
                                (tier) =>
                                    _TierCard(tier: tier, isSwahili: isSwahili),
                              )
                              .toList(),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Save Button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: (_isSaving || !isTotalValid)
                          ? null
                          : _saveWeights,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Theme.of(context).colorScheme.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                      child: _isSaving
                          ? Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    valueColor: AlwaysStoppedAnimation<Color>(
                                      Colors.white,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Text(isSwahili ? 'Inahifadhi...' : 'Saving...'),
                              ],
                            )
                          : Text(isSwahili ? 'Hifadhi Uzito' : 'Save Weights'),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  String _getWeightDescription(String factor, bool isSwahili) {
    switch (factor) {
      case 'schedule':
        return isSwahili
            ? 'Utendaji wa Ratiba - Kumaliza kwa wakati na kushikamana na makasha'
            : 'Schedule Performance - On-time completion and deadline adherence';
      case 'quality':
        return isSwahili
            ? 'Ubora wa Muundo - Ubora wa kiufundi na viwango vya usanifu'
            : 'Design Quality - Technical excellence and design standards';
      case 'client':
        return isSwahili
            ? 'Ufanisi wa Idhini ya Mteja - Kuridhika kwa mteja na idadi ya marekebisho'
            : 'Client Approval Efficiency - Client satisfaction and revision count';
      default:
        return isSwahili
            ? 'Uzito wa factori ya utendaji'
            : 'Performance factor weight';
    }
  }
}

class _WeightSlider extends StatelessWidget {
  final String factor;
  final double value;
  final String description;
  final ValueChanged<double> onChanged;
  final bool isSwahili;

  const _WeightSlider({
    super.key,
    required this.factor,
    required this.value,
    required this.description,
    required this.onChanged,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final displayName = factor
        .split('_')
        .map((word) => word[0].toUpperCase() + word.substring(1))
        .join(' ');

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                displayName,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
                overflow: TextOverflow.ellipsis,
                maxLines: 1,
              ),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '${(value * 100).toStringAsFixed(0)}%',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 11,
                  color: Theme.of(context).colorScheme.primary,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          description,
          style: TextStyle(fontSize: 12, color: Colors.grey[600]),
        ),
        const SizedBox(height: 12),
        Slider(
          value: value,
          min: 0.0,
          max: 1.0,
          divisions: 20,
          onChanged: onChanged,
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}

class _TierCard extends StatelessWidget {
  final dynamic tier;
  final bool isSwahili;

  const _TierCard({super.key, required this.tier, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey[300]!),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Icon(
              Icons.trending_up,
              color: Theme.of(context).colorScheme.primary,
              size: 18,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  tier['name'] ??
                      (isSwahili ? 'Kiwango Kisichojulikana' : 'Unknown Tier'),
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                  ),
                  overflow: TextOverflow.ellipsis,
                  maxLines: 1,
                ),
                const SizedBox(height: 2),
                Text(
                  '${isSwahili ? 'Vitengo' : 'Units'}: ${tier['min_amount']?.toString() ?? '0'} - ${tier['max_amount']?.toString() ?? '∞'}',
                  style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                  overflow: TextOverflow.ellipsis,
                  maxLines: 1,
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            '${tier['bonus_percentage']?.toString() ?? '0'}%',
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 12,
              color: Theme.of(context).colorScheme.primary,
            ),
          ),
        ],
      ),
    );
  }
}
