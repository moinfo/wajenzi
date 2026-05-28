import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/calculators/calculator_models.dart';
import '../../../data/repositories/calculators_repository.dart';
import '../../providers/settings_provider.dart';
import 'calculator_shared.dart';

final _currenciesProvider =
    FutureProvider.autoDispose<List<CurrencyDto>>((ref) async {
  return ref.watch(calculatorsRepositoryProvider).currencies();
});

final _currenciesSearchProvider = StateProvider<String>((ref) => '');

class CurrenciesScreen extends ConsumerWidget {
  const CurrenciesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lang = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final rootKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_currenciesProvider);
    final search = ref.watch(_currenciesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootKey.currentState?.openDrawer(),
        ),
        title: Text(tr(lang,
            en: 'Currencies', sw: 'Sarafu', fr: 'Devises', ar: 'العملات')),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton.extended(
          onPressed: () => _openForm(context, ref),
          icon: const Icon(Icons.add_rounded),
          label: Text(tr(lang,
              en: 'New', sw: 'Mpya', fr: 'Nouveau', ar: 'جديد')),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_currenciesProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (v) =>
                      ref.read(_currenciesSearchProvider.notifier).state = v,
                  decoration: InputDecoration(
                    hintText: tr(lang,
                        en: 'Search code, name or symbol…',
                        sw: 'Tafuta msimbo, jina au alama…',
                        fr: 'Rechercher code, nom ou symbole…',
                        ar: 'بحث الكود أو الاسم أو الرمز…'),
                    prefixIcon: const Icon(Icons.search_rounded),
                    filled: true,
                    fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
              ),
            ),
            SliverFillRemaining(
              hasScrollBody: true,
              child: CalcAsyncBody<List<CurrencyDto>>(
                async: async,
                onRetry: () => ref.invalidate(_currenciesProvider),
                emptyIcon: Icons.attach_money_rounded,
                emptyText: tr(lang,
                    en: 'No currencies yet',
                    sw: 'Hakuna sarafu bado',
                    fr: 'Aucune devise',
                    ar: 'لا توجد عملات'),
                isEmpty: (data) {
                  final filtered = _filter(data, search);
                  return filtered.isEmpty;
                },
                builder: (data) {
                  final filtered = _filter(data, search);
                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                    itemCount: filtered.length,
                    itemBuilder: (_, i) {
                      final c = filtered[i];
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: CalcCard(
                          isDarkMode: isDarkMode,
                          padding: const EdgeInsets.symmetric(
                              horizontal: 14, vertical: 12),
                          child: Row(
                            children: [
                              Container(
                                width: 44,
                                height: 44,
                                alignment: Alignment.center,
                                decoration: BoxDecoration(
                                  color: AppColors.primary
                                      .withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  c.symbol,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Text(
                                          c.code ?? c.symbol,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w800,
                                            fontSize: 15,
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        if (!c.isActive)
                                          Container(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 8, vertical: 2),
                                            decoration: BoxDecoration(
                                              color: Colors.grey
                                                  .withValues(alpha: 0.2),
                                              borderRadius:
                                                  BorderRadius.circular(20),
                                            ),
                                            child: Text(
                                              tr(lang,
                                                  en: 'Inactive',
                                                  sw: 'Si Hai',
                                                  fr: 'Inactif',
                                                  ar: 'غير نشط'),
                                              style: const TextStyle(
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.w600),
                                            ),
                                          ),
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      c.name,
                                      style: TextStyle(
                                          fontSize: 12,
                                          color: Colors.grey[600]),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      tr(lang,
                                          en:
                                              '1 USD = ${_fmt(c.rateToUsd)} ${c.code ?? c.symbol}',
                                          sw:
                                              '1 USD = ${_fmt(c.rateToUsd)} ${c.code ?? c.symbol}'),
                                      style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w600),
                                    ),
                                  ],
                                ),
                              ),
                              PopupMenuButton<String>(
                                onSelected: (v) {
                                  if (v == 'edit') {
                                    _openForm(context, ref, item: c);
                                  } else if (v == 'delete') {
                                    _delete(context, ref, c);
                                  }
                                },
                                itemBuilder: (_) => [
                                  PopupMenuItem(
                                    value: 'edit',
                                    child: Row(children: [
                                      const Icon(Icons.edit_rounded, size: 18),
                                      const SizedBox(width: 8),
                                      Text(tr(lang,
                                          en: 'Edit',
                                          sw: 'Hariri',
                                          fr: 'Éditer',
                                          ar: 'تعديل')),
                                    ]),
                                  ),
                                  PopupMenuItem(
                                    value: 'delete',
                                    child: Row(children: [
                                      const Icon(Icons.delete_rounded,
                                          size: 18, color: AppColors.error),
                                      const SizedBox(width: 8),
                                      Text(tr(lang,
                                          en: 'Delete',
                                          sw: 'Futa',
                                          fr: 'Supprimer',
                                          ar: 'حذف')),
                                    ]),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }

  static List<CurrencyDto> _filter(List<CurrencyDto> items, String q) {
    if (q.isEmpty) return items;
    return items.where((c) {
      final hay =
          '${c.code ?? ''} ${c.name} ${c.symbol}'.toLowerCase();
      return hay.contains(q);
    }).toList();
  }

  static String _fmt(double v) {
    if (v == v.roundToDouble()) return v.toStringAsFixed(0);
    return v.toStringAsFixed(2);
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref,
      {CurrencyDto? item}) async {
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: _CurrencyForm(item: item),
      ),
    );
    if (ok == true) ref.invalidate(_currenciesProvider);
  }

  Future<void> _delete(BuildContext context, WidgetRef ref, CurrencyDto c) async {
    final lang = ref.read(currentLanguageProvider);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(tr(lang,
            en: 'Delete currency',
            sw: 'Futa sarafu',
            fr: 'Supprimer',
            ar: 'حذف العملة')),
        content: Text('${c.code ?? c.symbol} — ${c.name}'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(tr(lang,
                  en: 'Cancel', sw: 'Ghairi', fr: 'Annuler', ar: 'إلغاء'))),
          TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(
                tr(lang, en: 'Delete', sw: 'Futa', fr: 'Supprimer', ar: 'حذف'),
                style: const TextStyle(color: AppColors.error),
              )),
        ],
      ),
    );
    if (confirm != true) return;
    try {
      await ref.read(calculatorsRepositoryProvider).deleteCurrency(c.id);
      ref.invalidate(_currenciesProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(tr(lang,
            en: 'Currency deleted',
            sw: 'Sarafu imefutwa',
            fr: 'Devise supprimée',
            ar: 'تم حذف العملة')),
        backgroundColor: AppColors.success,
      ));
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(calcErrorMessage(e, lang: lang)),
        backgroundColor: AppColors.error,
      ));
    }
  }
}

class _CurrencyForm extends ConsumerStatefulWidget {
  final CurrencyDto? item;
  const _CurrencyForm({this.item});

  @override
  ConsumerState<_CurrencyForm> createState() => _CurrencyFormState();
}

class _CurrencyFormState extends ConsumerState<_CurrencyForm> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name = TextEditingController(text: widget.item?.name ?? '');
  late final TextEditingController _symbol = TextEditingController(text: widget.item?.symbol ?? '');
  late final TextEditingController _code = TextEditingController(text: widget.item?.code ?? '');
  late final TextEditingController _rate = TextEditingController(
      text: widget.item == null ? '' : widget.item!.rateToUsd.toString());
  late bool _active = widget.item?.isActive ?? true;
  bool _saving = false;

  bool get _isEdit => widget.item != null;

  @override
  void dispose() {
    _name.dispose();
    _symbol.dispose();
    _code.dispose();
    _rate.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E2E) : Colors.white,
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
                      ? tr(lang,
                          en: 'Edit currency',
                          sw: 'Hariri sarafu',
                          fr: 'Modifier',
                          ar: 'تعديل العملة')
                      : tr(lang,
                          en: 'New currency',
                          sw: 'Sarafu mpya',
                          fr: 'Nouvelle devise',
                          ar: 'عملة جديدة'),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _name,
                  decoration: InputDecoration(
                    labelText: tr(lang, en: 'Name', sw: 'Jina', fr: 'Nom', ar: 'الاسم'),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? tr(lang, en: 'Required', sw: 'Inahitajika', fr: 'Requis', ar: 'مطلوب')
                      : null,
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _code,
                        decoration: InputDecoration(
                          labelText: tr(lang,
                              en: 'Code (USD, TZS…)',
                              sw: 'Msimbo',
                              fr: 'Code',
                              ar: 'الكود'),
                          border: const OutlineInputBorder(),
                        ),
                        textCapitalization: TextCapitalization.characters,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _symbol,
                        decoration: InputDecoration(
                          labelText: tr(lang,
                              en: 'Symbol',
                              sw: 'Alama',
                              fr: 'Symbole',
                              ar: 'الرمز'),
                          border: const OutlineInputBorder(),
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty)
                            ? tr(lang, en: 'Required', sw: 'Inahitajika', fr: 'Requis', ar: 'مطلوب')
                            : null,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _rate,
                  decoration: InputDecoration(
                    labelText: tr(lang,
                        en: 'Rate per 1 USD',
                        sw: 'Kiwango kwa 1 USD',
                        fr: 'Taux pour 1 USD',
                        ar: 'السعر لكل 1 دولار'),
                    helperText: tr(lang,
                        en: 'Example: 2640 means 1 USD = 2640 of this currency',
                        sw: 'Mfano: 2640 ina maana 1 USD = sarafu 2640'),
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*')),
                  ],
                  validator: (v) {
                    final n = double.tryParse((v ?? '').trim());
                    if (n == null || n <= 0) {
                      return tr(lang,
                          en: 'Enter a positive number',
                          sw: 'Ingiza nambari chanya');
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(tr(lang,
                      en: 'Active', sw: 'Hai', fr: 'Actif', ar: 'نشط')),
                  value: _active,
                  onChanged: (v) => setState(() => _active = v),
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _saving ? null : _submit,
                    child: Text(_saving
                        ? tr(lang, en: 'Saving…', sw: 'Inahifadhi…')
                        : tr(lang,
                            en: 'Save',
                            sw: 'Hifadhi',
                            fr: 'Enregistrer',
                            ar: 'حفظ')),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final lang = ref.read(currentLanguageProvider);
    final repo = ref.read(calculatorsRepositoryProvider);
    final body = <String, dynamic>{
      'name': _name.text.trim(),
      'symbol': _symbol.text.trim(),
      'rate_to_usd': double.tryParse(_rate.text.trim()) ?? 1,
      'is_active': _active,
    };
    final code = _code.text.trim();
    if (code.isNotEmpty) body['code'] = code;
    try {
      if (_isEdit) {
        await repo.updateCurrency(widget.item!.id, body);
      } else {
        await repo.createCurrency(body);
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
