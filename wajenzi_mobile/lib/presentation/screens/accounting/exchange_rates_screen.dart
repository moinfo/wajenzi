import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _exchangeRatesSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);
final _exchangeRatesYearFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => DateTime.now().year,
);
final _exchangeRatesMonthFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);

final _exchangeRatesProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/exchange-rates');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final items = data['data'] as List? ?? const [];
        return {
          'items': items
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return {
            'items': const <Map<String, dynamic>>[],
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _exchangeRateRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/exchange-rates/reference-data');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        return data['data'] is Map
            ? Map<String, dynamic>.from(data['data'] as Map)
            : const <String, dynamic>{};
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return const <String, dynamic>{};
        }
        rethrow;
      }
    });

class ExchangeRatesScreen extends ConsumerStatefulWidget {
  const ExchangeRatesScreen({super.key});

  @override
  ConsumerState<ExchangeRatesScreen> createState() =>
      _ExchangeRatesScreenState();
}

class _ExchangeRatesScreenState extends ConsumerState<ExchangeRatesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final ratesAsync = ref.watch(_exchangeRatesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_exchangeRatesSearchProvider).trim().toLowerCase();
    final yearFilter = ref.watch(_exchangeRatesYearFilterProvider);
    final monthFilter = ref.watch(_exchangeRatesMonthFilterProvider);

    final currentYear = DateTime.now().year;
    final years = List.generate(10, (i) => currentYear - i);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Vigezo vya Kubadilisha' : 'Exchange Rates'),
      ),
      floatingActionButton: ratesAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: isSwahili ? 'Ongeza Kigezo' : 'Add Rate',
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: isSwahili ? 'Ongeza Kigezo' : 'Add Rate',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_exchangeRatesProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) =>
                          ref
                                  .read(_exchangeRatesSearchProvider.notifier)
                                  .state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta kigezo...'
                            : 'Search rates...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _exchangeRatesSearchProvider
                                                  .notifier,
                                            )
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: [
                          _FilterDropdown(
                            label: isSwahili ? 'Mwaka' : 'Year',
                            value: yearFilter,
                            items: years,
                            itemLabel: (y) => y.toString(),
                            onChanged: (value) =>
                                ref
                                        .read(
                                          _exchangeRatesYearFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    value,
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _FilterDropdown(
                            label: isSwahili ? 'Mwezi' : 'Month',
                            value: monthFilter,
                            items: [null, ...List.generate(12, (i) => i + 1)],
                            itemLabel: (m) => m == null
                                ? (isSwahili ? 'Zote' : 'All')
                                : _getMonthName(m, isSwahili),
                            onChanged: (value) =>
                                ref
                                        .read(
                                          _exchangeRatesMonthFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    value,
                            isDarkMode: isDarkMode,
                          ),
                          const SizedBox(width: 8),
                          _StatusChip(
                            label: isSwahili ? 'Zima vichujio' : 'Clear',
                            isSelected: false,
                            onTap: () {
                              ref
                                      .read(
                                        _exchangeRatesYearFilterProvider
                                            .notifier,
                                      )
                                      .state =
                                  null;
                              ref
                                      .read(
                                        _exchangeRatesMonthFilterProvider
                                            .notifier,
                                      )
                                      .state =
                                  null;
                            },
                            isDarkMode: isDarkMode,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            ratesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _ExchangeRateErrorView(
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_exchangeRatesProvider),
                ),
              ),
              data: (payload) {
                if (payload['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.currency_exchange_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Exchange Rates haipatikani kwenye live API kwa sasa.'
                                  : 'Exchange Rates is not available on the live API right now.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[700],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }

                final rates = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final filteredRates = rates.where((rate) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      rate['foreign_currency_name'] ?? '',
                      rate['base_currency_name'] ?? '',
                      rate['foreign_currency_symbol'] ?? '',
                      rate['base_currency_symbol'] ?? '',
                      rate['rate']?.toString() ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  if (yearFilter != null && rate['year'] != yearFilter)
                    return false;
                  if (monthFilter != null && rate['month'] != monthFilter)
                    return false;
                  return true;
                }).toList();

                if (filteredRates.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.currency_exchange,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            rates.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna vigezo'
                                      : 'No exchange rates found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No matching results'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty ||
                              yearFilter != null ||
                              monthFilter != null) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () {
                                ref
                                        .read(
                                          _exchangeRatesSearchProvider.notifier,
                                        )
                                        .state =
                                    '';
                                ref
                                        .read(
                                          _exchangeRatesYearFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null;
                                ref
                                        .read(
                                          _exchangeRatesMonthFilterProvider
                                              .notifier,
                                        )
                                        .state =
                                    null;
                              },
                              icon: const Icon(Icons.clear),
                              label: Text(
                                isSwahili ? 'Futa vichujio' : 'Clear filters',
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final rate = filteredRates[index];
                      return _ExchangeRateCard(
                        rate: rate,
                        index: index,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onEdit: () => _openForm(context, ref, rate: rate),
                        onDelete: () => _deleteRate(context, ref, rate),
                      );
                    }, childCount: filteredRates.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  String _getMonthName(int month, bool isSwahili) {
    const months = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];
    const swahiliMonths = [
      'Januari',
      'Februari',
      'Machi',
      'Aprili',
      'Mei',
      'Juni',
      'Julai',
      'Agosti',
      'Septemba',
      'Oktoba',
      'Novemba',
      'Desemba',
    ];
    return isSwahili ? swahiliMonths[month - 1] : months[month - 1];
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? rate,
  }) async {
    final refs = await ref.read(_exchangeRateRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _ExchangeRateFormSheet(refs: refs, rate: rate),
    );
    if (result == true) ref.invalidate(_exchangeRatesProvider);
  }

  Future<void> _deleteRate(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> rate,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(isSwahili ? 'Futa Kigezo' : 'Delete Exchange Rate'),
        content: Text(
          isSwahili
              ? 'Futa ${rate['foreign_currency_name']} -> ${rate['base_currency_name']}?'
              : 'Delete ${rate['foreign_currency_name']} -> ${rate['base_currency_name']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(
              isSwahili ? 'Futa' : 'Delete',
              style: const TextStyle(color: AppColors.error),
            ),
          ),
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
            content: Text(
              isSwahili ? 'Kigezo kimefutwa' : 'Exchange rate deleted',
            ),
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
}

class _FilterDropdown<T> extends StatelessWidget {
  final String label;
  final T? value;
  final List<T?> items;
  final String Function(T?) itemLabel;
  final ValueChanged<T?> onChanged;
  final bool isDarkMode;

  const _FilterDropdown({
    required this.label,
    required this.value,
    required this.items,
    required this.itemLabel,
    required this.onChanged,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.3)),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<T?>(
          value: items.contains(value) ? value : null,
          hint: Text(
            label,
            style: TextStyle(fontSize: 13, color: Colors.grey[600]),
          ),
          isDense: true,
          dropdownColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
          items: items.map((item) {
            return DropdownMenuItem<T?>(
              value: item,
              child: Text(
                itemLabel(item),
                style: TextStyle(
                  fontSize: 13,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
            );
          }).toList(),
          onChanged: onChanged,
        ),
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final bool isDarkMode;

  const _StatusChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.grey.withValues(alpha: 0.3)),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w500,
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
      ),
    );
  }
}

class _ExchangeRateCard extends StatelessWidget {
  final Map<String, dynamic> rate;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ExchangeRateCard({
    required this.rate,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final foreignName = rate['foreign_currency_name']?.toString() ?? '-';
    final foreignSymbol = rate['foreign_currency_symbol']?.toString() ?? '';
    final baseName = rate['base_currency_name']?.toString() ?? '-';
    final baseSymbol = rate['base_currency_symbol']?.toString() ?? '';
    final exchangeRate = rate['rate'];
    final month = rate['month'];
    final year = rate['year'];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Center(
                    child: Text(
                      '${index + 1}',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '$foreignName $foreignSymbol',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(
                            Icons.arrow_downward,
                            size: 14,
                            color: Colors.grey[500],
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '$baseName $baseSymbol',
                            style: TextStyle(
                              fontSize: 13,
                              color: isDarkMode
                                  ? Colors.white70
                                  : AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (value) {
                    if (value == 'edit') {
                      onEdit();
                    } else if (value == 'delete') {
                      onDelete();
                    }
                  },
                  itemBuilder: (_) => [
                    PopupMenuItem(
                      value: 'edit',
                      child: Row(
                        children: [
                          const Icon(Icons.edit_rounded, size: 20),
                          const SizedBox(width: 8),
                          Text(isSwahili ? 'Hariri' : 'Edit'),
                        ],
                      ),
                    ),
                    PopupMenuItem(
                      value: 'delete',
                      child: Row(
                        children: [
                          const Icon(
                            Icons.delete_rounded,
                            size: 20,
                            color: AppColors.error,
                          ),
                          const SizedBox(width: 8),
                          Text(
                            isSwahili ? 'Futa' : 'Delete',
                            style: const TextStyle(color: AppColors.error),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.grey[50],
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _RateInfo(
                    label: isSwahili ? 'Kigezo' : 'Rate',
                    value: exchangeRate?.toString() ?? '-',
                    isDarkMode: isDarkMode,
                  ),
                  Container(
                    width: 1,
                    height: 30,
                    color: Colors.grey.withValues(alpha: 0.3),
                  ),
                  _RateInfo(
                    label: isSwahili ? 'Mwezi' : 'Month',
                    value: month?.toString() ?? '-',
                    isDarkMode: isDarkMode,
                  ),
                  Container(
                    width: 1,
                    height: 30,
                    color: Colors.grey.withValues(alpha: 0.3),
                  ),
                  _RateInfo(
                    label: isSwahili ? 'Mwaka' : 'Year',
                    value: year?.toString() ?? '-',
                    isDarkMode: isDarkMode,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RateInfo extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _RateInfo({
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: isDarkMode ? Colors.white54 : AppColors.textHint,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ],
    );
  }
}

class _ExchangeRateFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? rate;

  const _ExchangeRateFormSheet({required this.refs, this.rate});

  @override
  ConsumerState<_ExchangeRateFormSheet> createState() =>
      _ExchangeRateFormSheetState();
}

class _ExchangeRateFormSheetState
    extends ConsumerState<_ExchangeRateFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _rateController;
  late final TextEditingController _monthController;
  late final TextEditingController _yearController;
  int? _foreignCurrencyId;
  int? _baseCurrencyId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _rateController = TextEditingController(
      text: widget.rate?['rate']?.toString() ?? '',
    );
    _monthController = TextEditingController(
      text:
          widget.rate?['month']?.toString() ?? DateTime.now().month.toString(),
    );
    _yearController = TextEditingController(
      text: widget.rate?['year']?.toString() ?? DateTime.now().year.toString(),
    );
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

    final bgColor = isDarkMode ? const Color(0xFF1A1A2E) : Colors.white;
    final inputBg = isDarkMode ? const Color(0xFF0F1923) : Colors.grey[100];
    final textColor = isDarkMode ? Colors.white : AppColors.textPrimary;

    InputDecoration inputStyle(String label) => InputDecoration(
      labelText: label,
      filled: true,
      fillColor: inputBg,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
    );

    return Container(
      height: 0.85 * MediaQuery.of(context).size.height,
      decoration: BoxDecoration(
        color: bgColor,
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
              child: Form(
                key: _formKey,
                child: ListView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    MediaQuery.of(context).viewInsets.bottom + 24,
                  ),
                  children: [
                    Text(
                      widget.rate == null
                          ? (isSwahili ? 'Kigezo Kipya' : 'New Exchange Rate')
                          : (isSwahili
                                ? 'Hariri Kigezo'
                                : 'Edit Exchange Rate'),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value:
                          currencies.any(
                            (item) => _toInt(item['id']) == _foreignCurrencyId,
                          )
                          ? _foreignCurrencyId
                          : null,
                      validator: (value) => value == null
                          ? (isSwahili ? 'Hitajiwa' : 'Required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Fedha ya Kigeni *' : 'Foreign Currency *',
                      ),
                      dropdownColor: bgColor,
                      items: currencies
                          .map(
                            (item) => DropdownMenuItem<int>(
                              value: _toInt(item['id']),
                              child: Text(
                                '${item['name']} (${item['symbol'] ?? ''})',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) =>
                          setState(() => _foreignCurrencyId = value),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value:
                          currencies.any(
                            (item) => _toInt(item['id']) == _baseCurrencyId,
                          )
                          ? _baseCurrencyId
                          : null,
                      validator: (value) => value == null
                          ? (isSwahili ? 'Hitajiwa' : 'Required')
                          : null,
                      decoration: inputStyle(
                        isSwahili ? 'Fedha ya Msingi *' : 'Base Currency *',
                      ),
                      dropdownColor: bgColor,
                      items: currencies
                          .map(
                            (item) => DropdownMenuItem<int>(
                              value: _toInt(item['id']),
                              child: Text(
                                '${item['name']} (${item['symbol'] ?? ''})',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) =>
                          setState(() => _baseCurrencyId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _rateController,
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return isSwahili
                              ? 'Kigezo kinahitajika'
                              : 'Rate is required';
                        }
                        final rate = double.tryParse(value.trim());
                        if (rate == null || rate <= 0) {
                          return isSwahili ? 'Kigezo batili' : 'Invalid rate';
                        }
                        return null;
                      },
                      decoration: inputStyle(isSwahili ? 'Kigezo *' : 'Rate *'),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _monthController,
                            keyboardType: TextInputType.number,
                            validator: (value) {
                              final month = int.tryParse(value ?? '');
                              if (month == null || month < 1 || month > 12) {
                                return isSwahili
                                    ? 'Mwezi batili'
                                    : 'Invalid month';
                              }
                              return null;
                            },
                            decoration: inputStyle(
                              isSwahili ? 'Mwezi *' : 'Month *',
                            ),
                            style: TextStyle(color: textColor),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _yearController,
                            keyboardType: TextInputType.number,
                            validator: (value) {
                              final year = int.tryParse(value ?? '');
                              if (year == null || year < 2000 || year > 2100) {
                                return isSwahili
                                    ? 'Mwaka batili'
                                    : 'Invalid year';
                              }
                              return null;
                            },
                            decoration: inputStyle(
                              isSwahili ? 'Mwaka *' : 'Year *',
                            ),
                            style: TextStyle(color: textColor),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _saving ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              widget.rate == null
                                  ? (isSwahili ? 'Hifadhi' : 'Save')
                                  : (isSwahili ? 'Sasisha' : 'Update'),
                            ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_foreignCurrencyId == _baseCurrencyId) {
      final isSwahili = ref.read(isSwahiliProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Fedha ya kigeni na msingi lazima ziwe tofauti'
                : 'Foreign and base currency must be different',
          ),
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
            content: Text(
              vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider)),
            ),
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
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ],
      ),
    );
  }
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}
