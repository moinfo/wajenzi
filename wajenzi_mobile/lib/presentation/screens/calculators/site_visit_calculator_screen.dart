import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/calculators/calculator_models.dart';
import '../../../data/repositories/calculators_repository.dart';
import '../../providers/settings_provider.dart';
import 'calculator_shared.dart';

final _siteVisitLookupProvider =
    FutureProvider.autoDispose<SiteVisitCalculatorLookups>((ref) async {
  return ref.watch(calculatorsRepositoryProvider).siteVisitLookups();
});

class SiteVisitCalculatorScreen extends ConsumerStatefulWidget {
  const SiteVisitCalculatorScreen({super.key});

  @override
  ConsumerState<SiteVisitCalculatorScreen> createState() =>
      _SiteVisitCalculatorScreenState();
}

class _SiteVisitCalculatorScreenState
    extends ConsumerState<SiteVisitCalculatorScreen> {
  SiteVisitLocationDto? _selected;
  CurrencyDto? _currency;
  int _days = 1;

  final _travel = TextEditingController();
  final _local = TextEditingController();
  final _allowance = TextEditingController();
  final _food = TextEditingController();
  final _accom = TextEditingController();
  final _notes = TextEditingController();

  ComputeResult? _result;
  bool _computing = false;
  String? _error;

  @override
  void dispose() {
    for (final c in [_travel, _local, _allowance, _food, _accom, _notes]) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final rootKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_siteVisitLookupProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootKey.currentState?.openDrawer(),
        ),
        title: Text(tr(lang,
            en: 'Site Visit Calculator',
            sw: 'Kikokotoo cha Ziara',
            fr: 'Calculateur visite',
            ar: 'حاسبة الزيارة')),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_siteVisitLookupProvider),
        child: CalcAsyncBody<SiteVisitCalculatorLookups>(
          async: async,
          onRetry: () => ref.invalidate(_siteVisitLookupProvider),
          emptyIcon: Icons.location_off_outlined,
          emptyText: tr(lang,
              en: 'No locations configured. Add some first.',
              sw: 'Hakuna maeneo. Ongeza kwanza.'),
          isEmpty: (d) => d.locations.isEmpty,
          builder: (lookups) {
            _currency ??= _initialCurrency(lookups.currencies);
            return SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _currencyPicker(lookups, lang: lang, isDark: isDark),
                  CalcSectionTitle(
                      label: tr(lang,
                          en: 'CHOOSE A LOCATION', sw: 'CHAGUA ENEO')),
                  ...lookups.locations.map((l) => _locationCard(l,
                      lookups: lookups, isDark: isDark, lang: lang)),
                  if (_selected != null) ...[
                    CalcSectionTitle(
                        label: tr(lang,
                            en: 'COSTS (TZS)',
                            sw: 'GHARAMA (TZS)',
                            fr: 'COÛTS (TZS)',
                            ar: 'التكاليف')),
                    CalcCard(
                      isDarkMode: isDark,
                      child: Column(
                        children: [
                          _daysRow(lang),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(child: _tzsField(_travel, label: tr(lang, en: 'Travel', sw: 'Safari'))),
                              const SizedBox(width: 10),
                              Expanded(child: _tzsField(_local, label: tr(lang, en: 'Local', sw: 'Ndani'))),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Row(
                            children: [
                              Expanded(child: _tzsField(_allowance, label: tr(lang, en: 'Allowance', sw: 'Posho'))),
                              const SizedBox(width: 10),
                              Expanded(child: _tzsField(_food, label: tr(lang, en: 'Food', sw: 'Chakula'))),
                            ],
                          ),
                          const SizedBox(height: 10),
                          _tzsField(_accom, label: tr(lang, en: 'Accommodation', sw: 'Malazi')),
                          const SizedBox(height: 10),
                          TextField(
                            controller: _notes,
                            decoration: InputDecoration(
                              labelText: tr(lang,
                                  en: 'Project description (optional)',
                                  sw: 'Maelezo (hiari)'),
                              border: const OutlineInputBorder(),
                            ),
                            maxLines: 2,
                          ),
                          const SizedBox(height: 14),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: _computing ? null : () => _compute(lookups),
                              icon: const Icon(Icons.calculate_rounded),
                              label: Text(_computing
                                  ? tr(lang, en: 'Calculating…', sw: 'Inakokotoa…')
                                  : tr(lang,
                                      en: 'Calculate',
                                      sw: 'Kokotoa',
                                      fr: 'Calculer',
                                      ar: 'احسب')),
                            ),
                          ),
                          if (_error != null) ...[
                            const SizedBox(height: 8),
                            Text(_error!,
                                style: const TextStyle(color: AppColors.error)),
                          ],
                        ],
                      ),
                    ),
                    if (_result != null) ...[
                      const SizedBox(height: 16),
                      _resultPanel(lookups, isDark: isDark, lang: lang),
                    ],
                  ],
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  CurrencyDto? _initialCurrency(List<CurrencyDto> list) {
    if (list.isEmpty) return null;
    return list.firstWhere(
      (c) => c.code?.toUpperCase() == 'USD',
      orElse: () => list.first,
    );
  }

  Widget _currencyPicker(
    SiteVisitCalculatorLookups lookups, {
    required AppLanguage lang,
    required bool isDark,
  }) {
    return CalcCard(
      isDarkMode: isDark,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      child: Row(
        children: [
          const Icon(Icons.currency_exchange_rounded,
              size: 18, color: AppColors.primary),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              tr(lang, en: 'Display currency', sw: 'Sarafu ya kuonyesha'),
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ),
          DropdownButton<int>(
            value: _currency?.id,
            underline: const SizedBox.shrink(),
            items: lookups.currencies
                .map((c) => DropdownMenuItem<int>(
                      value: c.id,
                      child: Text('${c.code ?? c.symbol} — ${c.name}'),
                    ))
                .toList(),
            onChanged: (id) {
              setState(() {
                _currency = lookups.currencies.firstWhere((c) => c.id == id);
              });
            },
          ),
        ],
      ),
    );
  }

  Widget _locationCard(
    SiteVisitLocationDto l, {
    required SiteVisitCalculatorLookups lookups,
    required bool isDark,
    required AppLanguage lang,
  }) {
    final selected = _selected?.id == l.id;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: CalcCard(
        isDarkMode: isDark,
        color: selected
            ? AppColors.primary.withValues(alpha: 0.08)
            : null,
        onTap: () => setState(() {
          _selected = l;
          _travel.text = l.presetTravelTzs == 0 ? '' : l.presetTravelTzs.round().toString();
          _local.text = l.presetLocalTzs == 0 ? '' : l.presetLocalTzs.round().toString();
          _allowance.text = l.presetAllowanceTzs == 0 ? '' : l.presetAllowanceTzs.round().toString();
          _food.text = l.presetFoodTzs == 0 ? '' : l.presetFoodTzs.round().toString();
          _accom.text = l.presetAccommodationTzs == 0 ? '' : l.presetAccommodationTzs.round().toString();
          _result = null;
        }),
        child: Row(
          children: [
            Icon(
              selected ? Icons.check_circle_rounded : Icons.circle_outlined,
              color: selected ? AppColors.primary : Colors.grey,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(l.name,
                      style: const TextStyle(
                          fontSize: 14, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 2),
                  Text(
                    tr(lang,
                        en: 'Base: ${_displayFromTzs(l.baseCostTzs, lookups)}',
                        sw: 'Msingi: ${_displayFromTzs(l.baseCostTzs, lookups)}'),
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _daysRow(AppLanguage lang) {
    return Row(
      children: [
        Text(tr(lang, en: 'Days', sw: 'Siku'),
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
        const Spacer(),
        IconButton.outlined(
          onPressed: _days > 1 ? () => setState(() => _days--) : null,
          icon: const Icon(Icons.remove),
        ),
        SizedBox(
          width: 50,
          child: Text('$_days',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
        ),
        IconButton.outlined(
          onPressed: () => setState(() => _days++),
          icon: const Icon(Icons.add),
        ),
      ],
    );
  }

  Widget _tzsField(TextEditingController c, {required String label}) {
    return TextField(
      controller: c,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
        suffixText: 'TZS',
      ),
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))],
    );
  }

  Widget _resultPanel(
    SiteVisitCalculatorLookups lookups, {
    required bool isDark,
    required AppLanguage lang,
  }) {
    final r = _result!;
    final raw = r.raw;
    final total = (raw['total_tzs'] as num?)?.toDouble() ?? 0;
    return CalcCard(
      isDarkMode: isDark,
      color: AppColors.brandGreen.withValues(alpha: 0.06),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(tr(lang, en: 'COST BREAKDOWN', sw: 'GHARAMA'),
              style: const TextStyle(
                  fontSize: 11,
                  letterSpacing: 0.5,
                  fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          CalcKvRow(
            label: tr(lang, en: 'Base site visit fee', sw: 'Ada ya msingi'),
            value: _displayFromTzs((raw['base_tzs'] as num?)?.toDouble() ?? 0, lookups),
          ),
          CalcKvRow(
            label: tr(lang, en: 'Travel', sw: 'Safari'),
            value: _displayFromTzs((raw['travel_tzs'] as num?)?.toDouble() ?? 0, lookups),
          ),
          CalcKvRow(
            label: tr(lang, en: 'Local transport', sw: 'Usafiri'),
            value: _displayFromTzs((raw['local_tzs'] as num?)?.toDouble() ?? 0, lookups),
          ),
          CalcKvRow(
            label: tr(lang, en: 'Allowance', sw: 'Posho'),
            value: _displayFromTzs((raw['allowance_tzs'] as num?)?.toDouble() ?? 0, lookups),
          ),
          CalcKvRow(
            label: tr(lang, en: 'Food', sw: 'Chakula'),
            value: _displayFromTzs((raw['food_tzs'] as num?)?.toDouble() ?? 0, lookups),
          ),
          CalcKvRow(
            label: tr(lang, en: 'Accommodation', sw: 'Malazi'),
            value: _displayFromTzs((raw['accommodation_tzs'] as num?)?.toDouble() ?? 0, lookups),
          ),
          CalcKvRow(
            label: tr(lang, en: 'Per-day subtotal', sw: 'Jumla kwa siku'),
            value: _displayFromTzs((raw['per_day_tzs'] as num?)?.toDouble() ?? 0, lookups),
          ),
          CalcKvRow(
            label: tr(lang, en: 'Days', sw: 'Siku'),
            value: '$_days',
          ),
          const Divider(height: 24),
          CalcKvRow(
            label: tr(lang,
                en: 'Total (VAT exclusive)',
                sw: 'Jumla (bila VAT)'),
            value: _displayFromTzs(total, lookups),
            emphasised: true,
          ),
          if (r.invoiceText != null) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: isDark ? 0.05 : 0.6),
                borderRadius: BorderRadius.circular(10),
                border: Border(
                  left: BorderSide(color: AppColors.primary, width: 3),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(tr(lang,
                      en: 'INVOICE DESCRIPTION',
                      sw: 'MAELEZO YA ANKARA'),
                      style: const TextStyle(
                          fontSize: 10,
                          letterSpacing: 0.4,
                          fontWeight: FontWeight.w800,
                          color: AppColors.primary)),
                  const SizedBox(height: 6),
                  SelectableText(r.invoiceText!,
                      style: const TextStyle(fontSize: 12)),
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton.icon(
                      onPressed: () {
                        Clipboard.setData(ClipboardData(text: r.invoiceText!));
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                          content: Text(tr(lang,
                              en: 'Copied', sw: 'Imenakiliwa')),
                          backgroundColor: AppColors.success,
                        ));
                      },
                      icon: const Icon(Icons.copy_rounded, size: 16),
                      label: Text(tr(lang, en: 'Copy', sw: 'Nakili')),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  String _displayFromTzs(double tzs, SiteVisitCalculatorLookups lookups) {
    final c = _currency;
    if (c == null) return 'TZS ${tzs.round()}';
    return formatFromTzs(
      tzs,
      displayCode: c.code ?? c.symbol,
      displaySymbol: c.symbol,
      displayRatePerUsd: c.rateToUsd,
      tzsRatePerUsd: lookups.tzsRatePerUsd,
    );
  }

  Future<void> _compute(SiteVisitCalculatorLookups lookups) async {
    if (_selected == null) return;
    setState(() {
      _computing = true;
      _error = null;
    });
    final body = {
      'location_id': _selected!.id,
      'days': _days,
      'travel_tzs': double.tryParse(_travel.text.trim()) ?? 0,
      'local_tzs': double.tryParse(_local.text.trim()) ?? 0,
      'allowance_tzs': double.tryParse(_allowance.text.trim()) ?? 0,
      'food_tzs': double.tryParse(_food.text.trim()) ?? 0,
      'accommodation_tzs': double.tryParse(_accom.text.trim()) ?? 0,
      if (_notes.text.trim().isNotEmpty) 'notes': _notes.text.trim(),
    };
    try {
      final r = await ref
          .read(calculatorsRepositoryProvider)
          .siteVisitCompute(body);
      if (!mounted) return;
      setState(() => _result = r);
    } catch (e) {
      if (!mounted) return;
      final lang = ref.read(currentLanguageProvider);
      setState(() => _error = calcErrorMessage(e, lang: lang));
    } finally {
      if (mounted) setState(() => _computing = false);
    }
  }
}
