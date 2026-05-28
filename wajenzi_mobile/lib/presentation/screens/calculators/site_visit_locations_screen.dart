import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/calculators/calculator_models.dart';
import '../../../data/repositories/calculators_repository.dart';
import '../../providers/settings_provider.dart';
import 'calculator_shared.dart';

final _locationsProvider =
    FutureProvider.autoDispose<List<SiteVisitLocationDto>>((ref) async {
  return ref.watch(calculatorsRepositoryProvider).siteVisitLocations();
});

class SiteVisitLocationsScreen extends ConsumerWidget {
  const SiteVisitLocationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final rootKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_locationsProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootKey.currentState?.openDrawer(),
        ),
        title: Text(tr(lang,
            en: 'Site Visit Locations',
            sw: 'Maeneo ya Ziara',
            fr: 'Lieux de visite',
            ar: 'مواقع الزيارة')),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton.extended(
          onPressed: () => _openForm(context, ref),
          icon: const Icon(Icons.add_rounded),
          label: Text(tr(lang, en: 'New', sw: 'Mpya')),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_locationsProvider),
        child: CalcAsyncBody<List<SiteVisitLocationDto>>(
          async: async,
          onRetry: () => ref.invalidate(_locationsProvider),
          emptyIcon: Icons.location_on_outlined,
          emptyText: tr(lang,
              en: 'No locations yet', sw: 'Hakuna maeneo bado'),
          isEmpty: (d) => d.isEmpty,
          builder: (items) => ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final l = items[i];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: CalcCard(
                  isDarkMode: isDark,
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            width: 40,
                            height: 40,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              color: AppColors.brandBlue
                                  .withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(Icons.place_rounded,
                                color: AppColors.primary),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(l.name,
                                    style: const TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.w800)),
                                Text(
                                  tr(lang,
                                      en:
                                          'Base fee: TZS ${_thousands(l.baseCostTzs.round())}',
                                      sw:
                                          'Ada ya msingi: TZS ${_thousands(l.baseCostTzs.round())}'),
                                  style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey[600],
                                      fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                          PopupMenuButton<String>(
                            onSelected: (v) {
                              if (v == 'edit') _openForm(context, ref, item: l);
                              if (v == 'delete') _delete(context, ref, l);
                            },
                            itemBuilder: (_) => [
                              PopupMenuItem(
                                  value: 'edit',
                                  child: Text(tr(lang,
                                      en: 'Edit', sw: 'Hariri'))),
                              PopupMenuItem(
                                  value: 'delete',
                                  child: Text(
                                    tr(lang, en: 'Delete', sw: 'Futa'),
                                    style: const TextStyle(color: AppColors.error),
                                  )),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 6,
                        runSpacing: 6,
                        children: [
                          _presetChip(tr(lang, en: 'Travel', sw: 'Safari'), l.presetTravelTzs),
                          _presetChip(tr(lang, en: 'Local', sw: 'Ndani'), l.presetLocalTzs),
                          _presetChip(tr(lang, en: 'Allowance', sw: 'Posho'), l.presetAllowanceTzs),
                          _presetChip(tr(lang, en: 'Food', sw: 'Chakula'), l.presetFoodTzs),
                          _presetChip(tr(lang, en: 'Accom.', sw: 'Malazi'), l.presetAccommodationTzs),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  static Widget _presetChip(String label, double tzs) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.black.withValues(alpha: 0.04),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        '$label: TZS ${_thousands(tzs.round())}',
        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
      ),
    );
  }

  static String _thousands(int v) {
    final s = v.toString();
    final buf = StringBuffer();
    for (var i = 0; i < s.length; i++) {
      if (i != 0 && (s.length - i) % 3 == 0) buf.write(',');
      buf.write(s[i]);
    }
    return buf.toString();
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref,
      {SiteVisitLocationDto? item}) async {
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Padding(
        padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom),
        child: _LocationForm(item: item),
      ),
    );
    if (ok == true) ref.invalidate(_locationsProvider);
  }

  Future<void> _delete(BuildContext context, WidgetRef ref,
      SiteVisitLocationDto l) async {
    final lang = ref.read(currentLanguageProvider);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(tr(lang, en: 'Delete location?', sw: 'Futa eneo?')),
        content: Text(l.name),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(tr(lang, en: 'Cancel', sw: 'Ghairi'))),
          TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(tr(lang, en: 'Delete', sw: 'Futa'),
                  style: const TextStyle(color: AppColors.error))),
        ],
      ),
    );
    if (confirm != true) return;
    try {
      await ref
          .read(calculatorsRepositoryProvider)
          .deleteSiteVisitLocation(l.id);
      ref.invalidate(_locationsProvider);
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(calcErrorMessage(e, lang: lang)),
        backgroundColor: AppColors.error,
      ));
    }
  }
}

class _LocationForm extends ConsumerStatefulWidget {
  final SiteVisitLocationDto? item;
  const _LocationForm({this.item});

  @override
  ConsumerState<_LocationForm> createState() => _LocationFormState();
}

class _LocationFormState extends ConsumerState<_LocationForm> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name =
      TextEditingController(text: widget.item?.name ?? '');
  late final TextEditingController _base = TextEditingController(
      text: widget.item == null ? '' : widget.item!.baseCostTzs.toString());
  late final TextEditingController _travel = TextEditingController(
      text: widget.item == null ? '' : widget.item!.presetTravelTzs.toString());
  late final TextEditingController _local = TextEditingController(
      text: widget.item == null ? '' : widget.item!.presetLocalTzs.toString());
  late final TextEditingController _allowance = TextEditingController(
      text:
          widget.item == null ? '' : widget.item!.presetAllowanceTzs.toString());
  late final TextEditingController _food = TextEditingController(
      text: widget.item == null ? '' : widget.item!.presetFoodTzs.toString());
  late final TextEditingController _accom = TextEditingController(
      text: widget.item == null
          ? ''
          : widget.item!.presetAccommodationTzs.toString());
  late final TextEditingController _sortOrder = TextEditingController(
      text: (widget.item?.sortOrder ?? 0).toString());
  late bool _active = widget.item?.isActive ?? true;
  bool _saving = false;

  bool get _isEdit => widget.item != null;

  @override
  void dispose() {
    for (final c in [_name, _base, _travel, _local, _allowance, _food, _accom, _sortOrder]) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    return Container(
      constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.9),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E1E2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
          child: Form(
            key: _formKey,
            child: ListView(
              shrinkWrap: true,
              children: [
                Center(
                  child: Container(
                    width: 44,
                    height: 5,
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                Text(
                  _isEdit
                      ? tr(lang, en: 'Edit location', sw: 'Hariri eneo')
                      : tr(lang, en: 'New location', sw: 'Eneo jipya'),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _name,
                  decoration: InputDecoration(
                    labelText: tr(lang,
                        en: 'Location name (e.g. Dar es Salaam, Arusha)',
                        sw: 'Jina la eneo'),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? tr(lang, en: 'Required', sw: 'Inahitajika')
                      : null,
                ),
                const SizedBox(height: 12),
                _tzsField(_base,
                    label: tr(lang,
                        en: 'Base call-out fee (TZS)',
                        sw: 'Ada ya msingi (TZS)'),
                    required: true,
                    lang: lang),
                const SizedBox(height: 12),
                CalcSectionTitle(
                    label: tr(lang,
                        en: 'PRESET DAILY COSTS (TZS)',
                        sw: 'GHARAMA ZA KILA SIKU (TZS)')),
                Row(
                  children: [
                    Expanded(child: _tzsField(_travel, label: tr(lang, en: 'Travel', sw: 'Safari'), lang: lang)),
                    const SizedBox(width: 12),
                    Expanded(child: _tzsField(_local, label: tr(lang, en: 'Local transport', sw: 'Usafiri wa ndani'), lang: lang)),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(child: _tzsField(_allowance, label: tr(lang, en: 'Allowance', sw: 'Posho'), lang: lang)),
                    const SizedBox(width: 12),
                    Expanded(child: _tzsField(_food, label: tr(lang, en: 'Food', sw: 'Chakula'), lang: lang)),
                  ],
                ),
                const SizedBox(height: 12),
                _tzsField(_accom, label: tr(lang, en: 'Accommodation', sw: 'Malazi'), lang: lang),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _sortOrder,
                  decoration: InputDecoration(
                    labelText: tr(lang, en: 'Sort order', sw: 'Mpangilio'),
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                ),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(tr(lang, en: 'Active', sw: 'Hai')),
                  value: _active,
                  onChanged: (v) => setState(() => _active = v),
                ),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _submit,
                    child: Text(_saving
                        ? tr(lang, en: 'Saving…', sw: 'Inahifadhi…')
                        : tr(lang, en: 'Save', sw: 'Hifadhi')),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _tzsField(TextEditingController c,
      {required String label,
      bool required = false,
      required AppLanguage lang}) {
    return TextFormField(
      controller: c,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
      ),
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))],
      validator: (v) {
        if (!required && (v == null || v.trim().isEmpty)) return null;
        final n = double.tryParse((v ?? '').trim());
        if (n == null || n < 0) {
          return tr(lang, en: 'Enter ≥ 0', sw: 'Ingiza ≥ 0');
        }
        return null;
      },
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final lang = ref.read(currentLanguageProvider);
    final body = {
      'name': _name.text.trim(),
      'base_cost_tzs': double.tryParse(_base.text.trim()) ?? 0,
      'preset_travel_tzs': double.tryParse(_travel.text.trim()) ?? 0,
      'preset_local_tzs': double.tryParse(_local.text.trim()) ?? 0,
      'preset_allowance_tzs': double.tryParse(_allowance.text.trim()) ?? 0,
      'preset_food_tzs': double.tryParse(_food.text.trim()) ?? 0,
      'preset_accommodation_tzs': double.tryParse(_accom.text.trim()) ?? 0,
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_active': _active,
    };
    final repo = ref.read(calculatorsRepositoryProvider);
    try {
      if (_isEdit) {
        await repo.updateSiteVisitLocation(widget.item!.id, body);
      } else {
        await repo.createSiteVisitLocation(body);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(calcErrorMessage(e, lang: lang)),
        backgroundColor: AppColors.error,
      ));
    }
  }
}
