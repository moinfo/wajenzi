import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/calculators/calculator_models.dart';
import '../../../data/repositories/calculators_repository.dart';
import '../../providers/settings_provider.dart';
import 'calculator_shared.dart';

final _addonsProvider =
    FutureProvider.autoDispose<List<DesignAddonDto>>((ref) async {
  return ref.watch(calculatorsRepositoryProvider).designAddons();
});

class DesignAddonsScreen extends ConsumerWidget {
  const DesignAddonsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final rootKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_addonsProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootKey.currentState?.openDrawer(),
        ),
        title: Text(tr(lang,
            en: 'Design Add-ons',
            sw: 'Nyongeza za Ubunifu',
            fr: 'Suppléments',
            ar: 'الإضافات')),
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
        onRefresh: () async => ref.invalidate(_addonsProvider),
        child: CalcAsyncBody<List<DesignAddonDto>>(
          async: async,
          onRetry: () => ref.invalidate(_addonsProvider),
          emptyIcon: Icons.add_box_outlined,
          emptyText: tr(lang,
              en: 'No add-ons yet',
              sw: 'Hakuna nyongeza bado',
              fr: 'Aucun supplément'),
          isEmpty: (d) => d.isEmpty,
          builder: (items) => ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final a = items[i];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: CalcCard(
                  isDarkMode: isDark,
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 12),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(a.name,
                                style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w800)),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                _pricePill(
                                  label: tr(lang,
                                      en: 'Low',
                                      sw: 'Chini',
                                      fr: 'Bas',
                                      ar: 'منخفض'),
                                  value: 'USD ${a.priceLowUsd.round()}',
                                  color: AppColors.brandGreen,
                                ),
                                const SizedBox(width: 6),
                                _pricePill(
                                  label: tr(lang,
                                      en: 'High',
                                      sw: 'Juu',
                                      fr: 'Haut',
                                      ar: 'مرتفع'),
                                  value: 'USD ${a.priceHighUsd.round()}',
                                  color: AppColors.brandBlue,
                                ),
                                if (!a.isActive) ...[
                                  const SizedBox(width: 6),
                                  _pricePill(
                                    label: tr(lang,
                                        en: 'Inactive',
                                        sw: 'Si Hai',
                                        fr: 'Inactif',
                                        ar: 'غير نشط'),
                                    value: '',
                                    color: Colors.grey,
                                  ),
                                ],
                              ],
                            ),
                          ],
                        ),
                      ),
                      PopupMenuButton<String>(
                        onSelected: (v) {
                          if (v == 'edit') _openForm(context, ref, item: a);
                          if (v == 'delete') _delete(context, ref, a);
                        },
                        itemBuilder: (_) => [
                          PopupMenuItem(
                              value: 'edit',
                              child: Text(tr(lang,
                                  en: 'Edit',
                                  sw: 'Hariri',
                                  fr: 'Éditer',
                                  ar: 'تعديل'))),
                          PopupMenuItem(
                              value: 'delete',
                              child: Text(
                                tr(lang,
                                    en: 'Delete',
                                    sw: 'Futa',
                                    fr: 'Supprimer',
                                    ar: 'حذف'),
                                style: const TextStyle(color: AppColors.error),
                              )),
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

  Widget _pricePill(
      {required String label, required String value, required Color color}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        value.isEmpty ? label : '$label · $value',
        style: TextStyle(
            color: color, fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref,
      {DesignAddonDto? item}) async {
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Padding(
        padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom),
        child: _AddonForm(item: item),
      ),
    );
    if (ok == true) ref.invalidate(_addonsProvider);
  }

  Future<void> _delete(
      BuildContext context, WidgetRef ref, DesignAddonDto a) async {
    final lang = ref.read(currentLanguageProvider);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(tr(lang,
            en: 'Delete add-on?',
            sw: 'Futa nyongeza?',
            fr: 'Supprimer ?',
            ar: 'حذف؟')),
        content: Text(a.name),
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
      await ref.read(calculatorsRepositoryProvider).deleteDesignAddon(a.id);
      ref.invalidate(_addonsProvider);
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(calcErrorMessage(e, lang: lang)),
        backgroundColor: AppColors.error,
      ));
    }
  }
}

class _AddonForm extends ConsumerStatefulWidget {
  final DesignAddonDto? item;
  const _AddonForm({this.item});

  @override
  ConsumerState<_AddonForm> createState() => _AddonFormState();
}

class _AddonFormState extends ConsumerState<_AddonForm> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name =
      TextEditingController(text: widget.item?.name ?? '');
  late final TextEditingController _low = TextEditingController(
      text: widget.item == null ? '' : widget.item!.priceLowUsd.toString());
  late final TextEditingController _high = TextEditingController(
      text: widget.item == null ? '' : widget.item!.priceHighUsd.toString());
  late final TextEditingController _sortOrder = TextEditingController(
      text: (widget.item?.sortOrder ?? 0).toString());
  late bool _active = widget.item?.isActive ?? true;
  bool _saving = false;

  bool get _isEdit => widget.item != null;

  @override
  void dispose() {
    _name.dispose();
    _low.dispose();
    _high.dispose();
    _sortOrder.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    return Container(
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
                      ? tr(lang,
                          en: 'Edit add-on',
                          sw: 'Hariri nyongeza',
                          fr: 'Modifier supplément',
                          ar: 'تعديل')
                      : tr(lang,
                          en: 'New add-on',
                          sw: 'Nyongeza mpya',
                          fr: 'Nouveau supplément',
                          ar: 'جديد'),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _name,
                  decoration: InputDecoration(
                    labelText: tr(lang,
                        en: 'Name', sw: 'Jina', fr: 'Nom', ar: 'الاسم'),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? tr(lang, en: 'Required', sw: 'Inahitajika')
                      : null,
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _low,
                        decoration: InputDecoration(
                          labelText: tr(lang,
                              en: 'Low-rise price (USD)',
                              sw: 'Bei (ghorofa moja)',
                              fr: 'Prix bas (USD)',
                              ar: 'سعر منخفض'),
                          border: const OutlineInputBorder(),
                        ),
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))],
                        validator: (v) {
                          final n = double.tryParse((v ?? '').trim());
                          if (n == null || n < 0) return tr(lang, en: '#', sw: '#');
                          return null;
                        },
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _high,
                        decoration: InputDecoration(
                          labelText: tr(lang,
                              en: 'High-rise price (USD)',
                              sw: 'Bei (ghorofa nyingi)',
                              fr: 'Prix haut (USD)',
                              ar: 'سعر مرتفع'),
                          border: const OutlineInputBorder(),
                        ),
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))],
                        validator: (v) {
                          final n = double.tryParse((v ?? '').trim());
                          if (n == null || n < 0) return tr(lang, en: '#', sw: '#');
                          return null;
                        },
                      ),
                    ),
                  ],
                ),
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
                  title: Text(tr(lang, en: 'Active', sw: 'Hai', fr: 'Actif', ar: 'نشط')),
                  value: _active,
                  onChanged: (v) => setState(() => _active = v),
                ),
                const SizedBox(height: 6),
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
    final body = {
      'name': _name.text.trim(),
      'price_low_usd': double.tryParse(_low.text.trim()) ?? 0,
      'price_high_usd': double.tryParse(_high.text.trim()) ?? 0,
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_active': _active,
    };
    final repo = ref.read(calculatorsRepositoryProvider);
    try {
      if (_isEdit) {
        await repo.updateDesignAddon(widget.item!.id, body);
      } else {
        await repo.createDesignAddon(body);
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
