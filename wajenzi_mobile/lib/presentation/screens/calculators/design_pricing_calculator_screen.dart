import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/calculators/calculator_models.dart';
import '../../../data/repositories/calculators_repository.dart';
import '../../providers/settings_provider.dart';
import 'calculator_shared.dart';

final _designLookupProvider =
    FutureProvider.autoDispose<DesignPricingLookups>((ref) async {
  return ref.watch(calculatorsRepositoryProvider).designPricingLookups();
});

enum _Mode { standard, special, airbnb }

class DesignPricingCalculatorScreen extends ConsumerStatefulWidget {
  const DesignPricingCalculatorScreen({super.key});

  @override
  ConsumerState<DesignPricingCalculatorScreen> createState() =>
      _DesignPricingCalculatorScreenState();
}

class _DesignPricingCalculatorScreenState
    extends ConsumerState<DesignPricingCalculatorScreen> {
  _Mode _mode = _Mode.standard;
  CurrencyDto? _currency;

  // Standard
  String _rise = 'low';
  int _floors = 1; // for high-rise (G+x where extraFloors = floors-1 in JS terms; we'll send `floors`)
  int? _packageId;
  final Set<int> _selectedAddonIds = {};

  // Special
  int? _specialId;
  final _length = TextEditingController();
  final _width = TextEditingController();

  // AirBnB
  int _units = 1;

  ComputeResult? _result;
  bool _computing = false;
  String? _error;

  @override
  void dispose() {
    _length.dispose();
    _width.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final rootKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_designLookupProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootKey.currentState?.openDrawer(),
        ),
        title: Text(tr(lang,
            en: 'Design Pricing',
            sw: 'Bei ya Ubunifu',
            fr: 'Tarification design',
            ar: 'تسعير التصميم')),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_designLookupProvider),
        child: CalcAsyncBody<DesignPricingLookups>(
          async: async,
          onRetry: () => ref.invalidate(_designLookupProvider),
          emptyIcon: Icons.architecture_outlined,
          emptyText: tr(lang,
              en: 'No packages configured. Add some first.',
              sw: 'Hakuna pakeji. Ongeza kwanza.'),
          isEmpty: (l) =>
              l.lowPackages.isEmpty &&
              l.highPackages.isEmpty &&
              l.specialStructures.isEmpty,
          builder: (lookups) {
            _currency ??= _initialCurrency(lookups.currencies);
            return SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _currencyPicker(lookups, lang: lang, isDark: isDark),
                  const SizedBox(height: 12),
                  _modeTabs(lang),
                  const SizedBox(height: 12),
                  if (_mode == _Mode.standard)
                    _standardSection(lookups, lang: lang, isDark: isDark),
                  if (_mode == _Mode.special)
                    _specialSection(lookups, lang: lang, isDark: isDark),
                  if (_mode == _Mode.airbnb)
                    _airbnbSection(lookups, lang: lang, isDark: isDark),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed:
                          _computing ? null : () => _compute(lookups),
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
                  if (_result != null) ...[
                    const SizedBox(height: 16),
                    _resultPanel(lookups, isDark: isDark, lang: lang),
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

  Widget _currencyPicker(DesignPricingLookups lookups,
      {required AppLanguage lang, required bool isDark}) {
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
              tr(lang, en: 'Display currency', sw: 'Sarafu'),
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
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
                _currency =
                    lookups.currencies.firstWhere((c) => c.id == id);
              });
            },
          ),
        ],
      ),
    );
  }

  Widget _modeTabs(AppLanguage lang) {
    return SegmentedButton<_Mode>(
      segments: [
        ButtonSegment(
            value: _Mode.standard,
            label: Text(tr(lang,
                en: 'Standard',
                sw: 'Kawaida',
                fr: 'Standard',
                ar: 'قياسي'))),
        ButtonSegment(
            value: _Mode.special,
            label: Text(tr(lang,
                en: 'Special',
                sw: 'Maalum',
                fr: 'Spécial',
                ar: 'خاص'))),
        ButtonSegment(
            value: _Mode.airbnb,
            label: Text(tr(lang,
                en: 'AirBnB',
                sw: 'AirBnB',
                fr: 'AirBnB',
                ar: 'AirBnB'))),
      ],
      selected: {_mode},
      onSelectionChanged: (s) => setState(() {
        _mode = s.first;
        _result = null;
        _error = null;
      }),
    );
  }

  // ── Standard ───────────────────────────────────────────────────────────
  Widget _standardSection(DesignPricingLookups lookups,
      {required AppLanguage lang, required bool isDark}) {
    final pkgs =
        _rise == 'low' ? lookups.lowPackages : lookups.highPackages;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        CalcSectionTitle(
            label: tr(lang, en: 'BUILDING TYPE', sw: 'AINA YA JENGO')),
        Row(
          children: [
            Expanded(
              child: SegmentedButton<String>(
                segments: [
                  ButtonSegment(
                      value: 'low',
                      label: Text(tr(lang,
                          en: 'Low-rise', sw: 'Ghorofa moja'))),
                  ButtonSegment(
                      value: 'high',
                      label: Text(tr(lang,
                          en: 'High-rise', sw: 'Ghorofa nyingi'))),
                ],
                selected: {_rise},
                onSelectionChanged: (s) => setState(() {
                  _rise = s.first;
                  _packageId = null;
                  _result = null;
                }),
              ),
            ),
          ],
        ),
        if (_rise == 'high') ...[
          const SizedBox(height: 12),
          CalcCard(
            isDarkMode: isDark,
            child: Row(
              children: [
                Text(tr(lang, en: 'Storeys', sw: 'Ghorofa'),
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, fontSize: 13)),
                const Spacer(),
                IconButton.outlined(
                  onPressed:
                      _floors > 1 ? () => setState(() => _floors--) : null,
                  icon: const Icon(Icons.remove),
                ),
                SizedBox(
                  width: 70,
                  child: Text(
                    'G+${_floors - 1 < 0 ? 0 : _floors - 1}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                        fontSize: 18, fontWeight: FontWeight.w800),
                  ),
                ),
                IconButton.outlined(
                  onPressed: () => setState(() => _floors++),
                  icon: const Icon(Icons.add),
                ),
              ],
            ),
          ),
        ],
        CalcSectionTitle(
            label: tr(lang, en: 'SELECT PACKAGE', sw: 'CHAGUA PAKEJI')),
        ...pkgs.map((p) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: CalcCard(
                isDarkMode: isDark,
                color: _packageId == p.id
                    ? AppColors.primary.withValues(alpha: 0.08)
                    : null,
                onTap: () => setState(() {
                  _packageId = p.id;
                  _result = null;
                }),
                child: Row(
                  children: [
                    Icon(
                      _packageId == p.id
                          ? Icons.radio_button_checked
                          : Icons.radio_button_off,
                      color: _packageId == p.id ? AppColors.primary : Colors.grey,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(p.name,
                              style: const TextStyle(
                                  fontWeight: FontWeight.w800, fontSize: 14)),
                          if (p.includedServices.isNotEmpty) ...[
                            const SizedBox(height: 4),
                            Text(p.includedServices.join(' • '),
                                style: TextStyle(
                                    fontSize: 11, color: Colors.grey[600])),
                          ],
                        ],
                      ),
                    ),
                    Text(
                      _fromUsd(p.priceUsd, lookups),
                      style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          color: AppColors.primary,
                          fontSize: 14),
                    ),
                  ],
                ),
              ),
            )),
        if (lookups.addons.isNotEmpty) ...[
          CalcSectionTitle(
              label: tr(lang, en: 'ADD-ONS', sw: 'NYONGEZA')),
          ...lookups.addons.map((a) {
            final selected = _selectedAddonIds.contains(a.id);
            final price = _rise == 'low' ? a.priceLowUsd : a.priceHighUsd;
            return Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: CalcCard(
                isDarkMode: isDark,
                padding: const EdgeInsets.symmetric(
                    horizontal: 8, vertical: 4),
                onTap: () => setState(() {
                  selected
                      ? _selectedAddonIds.remove(a.id)
                      : _selectedAddonIds.add(a.id);
                }),
                child: Row(
                  children: [
                    Checkbox(
                      value: selected,
                      onChanged: (_) => setState(() {
                        selected
                            ? _selectedAddonIds.remove(a.id)
                            : _selectedAddonIds.add(a.id);
                      }),
                    ),
                    Expanded(child: Text(a.name, style: const TextStyle(fontSize: 13))),
                    Text('+ ${_fromUsd(price, lookups)}',
                        style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                            fontSize: 13)),
                  ],
                ),
              ),
            );
          }),
        ],
      ],
    );
  }

  // ── Special ─────────────────────────────────────────────────────────────
  Widget _specialSection(DesignPricingLookups lookups,
      {required AppLanguage lang, required bool isDark}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        CalcSectionTitle(label: tr(lang, en: 'SPECIAL STRUCTURE', sw: 'MUUNDO MAALUM')),
        DropdownButtonFormField<int>(
          initialValue: _specialId,
          decoration: const InputDecoration(border: OutlineInputBorder()),
          hint: Text(tr(lang, en: 'Choose…', sw: 'Chagua…')),
          items: lookups.specialStructures
              .map((s) => DropdownMenuItem<int>(
                    value: s.id,
                    child: Text(
                        '${s.name} · TZS ${s.rateTzsPerSqm.round()}/m²'),
                  ))
              .toList(),
          onChanged: (v) => setState(() {
            _specialId = v;
            _result = null;
          }),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _length,
                decoration: InputDecoration(
                  labelText: tr(lang, en: 'Length (m)', sw: 'Urefu (m)'),
                  border: const OutlineInputBorder(),
                ),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))
                ],
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: TextField(
                controller: _width,
                decoration: InputDecoration(
                  labelText: tr(lang, en: 'Width (m)', sw: 'Upana (m)'),
                  border: const OutlineInputBorder(),
                ),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))
                ],
              ),
            ),
          ],
        ),
      ],
    );
  }

  // ── AirBnB ──────────────────────────────────────────────────────────────
  Widget _airbnbSection(DesignPricingLookups lookups,
      {required AppLanguage lang, required bool isDark}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        CalcSectionTitle(
            label: tr(lang, en: 'AIRBNB / MULTI-UNIT', sw: 'NYUMBA NYINGI')),
        CalcCard(
          isDarkMode: isDark,
          child: Column(
            children: [
              Row(
                children: [
                  Text(tr(lang, en: 'Number of units', sw: 'Idadi ya nyumba'),
                      style: const TextStyle(
                          fontWeight: FontWeight.w700, fontSize: 13)),
                  const Spacer(),
                  IconButton.outlined(
                    onPressed: _units > 1 ? () => setState(() => _units--) : null,
                    icon: const Icon(Icons.remove),
                  ),
                  SizedBox(
                    width: 50,
                    child: Text(
                      '$_units',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                          fontSize: 20, fontWeight: FontWeight.w800),
                    ),
                  ),
                  IconButton.outlined(
                    onPressed: () => setState(() => _units++),
                    icon: const Icon(Icons.add),
                  ),
                ],
              ),
              if (_units > 2) ...[
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.warning.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.warning_amber_rounded,
                          color: AppColors.warning),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          tr(lang,
                              en:
                                  'More than 2 units must be escalated to the CEO/MD for further pricing.',
                              sw:
                                  'Zaidi ya nyumba 2 zinapaswa kufikishwa kwa CEO/MD.'),
                          style: const TextStyle(fontSize: 12),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  // ── Result ──────────────────────────────────────────────────────────────
  Widget _resultPanel(DesignPricingLookups lookups,
      {required bool isDark, required AppLanguage lang}) {
    final r = _result!;
    if (r.escalate) {
      return CalcCard(
        isDarkMode: isDark,
        color: AppColors.warning.withValues(alpha: 0.10),
        child: Row(
          children: [
            const Icon(Icons.warning_amber_rounded,
                color: AppColors.warning, size: 32),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                r.raw['message']?.toString() ??
                    tr(lang,
                        en: 'Escalate to CEO/MD',
                        sw: 'Fikisha kwa CEO/MD'),
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        ),
      );
    }
    final totalUsd = r.totalUsd;
    final totalTzs = r.totalTzs;
    final isSpecial = r.raw['mode'] == 'special';

    return CalcCard(
      isDarkMode: isDark,
      color: AppColors.brandGreen.withValues(alpha: 0.06),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(tr(lang, en: 'PRICE BREAKDOWN', sw: 'MCHANGANUO'),
              style: const TextStyle(
                  fontSize: 11,
                  letterSpacing: 0.5,
                  fontWeight: FontWeight.w800)),
          const SizedBox(height: 8),
          ...r.breakdown.map((row) {
            String value = '';
            if (row.containsKey('value_usd')) {
              value = _fromUsd((row['value_usd'] as num).toDouble(), lookups);
            } else if (row.containsKey('value_tzs')) {
              value = 'TZS ${(row['value_tzs'] as num).round()}';
            } else if (row.containsKey('value')) {
              value = row['value'].toString();
            }
            return CalcKvRow(label: row['label']?.toString() ?? '', value: value);
          }),
          const Divider(height: 24),
          if (isSpecial && totalTzs != null)
            CalcKvRow(
              label: tr(lang,
                  en: 'Total (VAT exclusive)',
                  sw: 'Jumla (bila VAT)'),
              value: 'TZS ${totalTzs.round()}',
              emphasised: true,
            )
          else if (totalUsd != null)
            CalcKvRow(
              label: tr(lang,
                  en: 'Total (VAT exclusive)',
                  sw: 'Jumla (bila VAT)'),
              value: _fromUsd(totalUsd, lookups),
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

  String _fromUsd(double usd, DesignPricingLookups lookups) {
    final c = _currency;
    if (c == null) return 'USD ${usd.round()}';
    return formatFromUsd(usd,
        displaySymbol: c.symbol,
        displayCode: c.code ?? c.symbol,
        displayRatePerUsd: c.rateToUsd);
  }

  Future<void> _compute(DesignPricingLookups lookups) async {
    final lang = ref.read(currentLanguageProvider);
    setState(() {
      _computing = true;
      _error = null;
    });
    final body = <String, dynamic>{'mode': _mode.name};
    if (_mode == _Mode.standard) {
      if (_packageId == null) {
        setState(() {
          _computing = false;
          _error = tr(lang, en: 'Pick a package first', sw: 'Chagua pakeji kwanza');
        });
        return;
      }
      body['rise_type'] = _rise;
      body['package_id'] = _packageId;
      body['floors'] = _floors;
      body['addon_ids'] = _selectedAddonIds.toList();
    } else if (_mode == _Mode.special) {
      if (_specialId == null) {
        setState(() {
          _computing = false;
          _error = tr(lang, en: 'Pick a structure', sw: 'Chagua muundo');
        });
        return;
      }
      body['special_id'] = _specialId;
      body['length_m'] = double.tryParse(_length.text.trim()) ?? 0;
      body['width_m'] = double.tryParse(_width.text.trim()) ?? 0;
    } else if (_mode == _Mode.airbnb) {
      body['units'] = _units;
    }
    body['display_currency'] = _currency?.code ?? 'USD';

    try {
      final r = await ref
          .read(calculatorsRepositoryProvider)
          .designPricingCompute(body);
      if (!mounted) return;
      setState(() => _result = r);
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = calcErrorMessage(e, lang: lang));
    } finally {
      if (mounted) setState(() => _computing = false);
    }
  }
}
