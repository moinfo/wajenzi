import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/calculators/calculator_models.dart';
import '../../../data/repositories/calculators_repository.dart';
import '../../providers/settings_provider.dart';
import 'calculator_shared.dart';

final _structuresProvider =
    FutureProvider.autoDispose<List<DesignSpecialStructureDto>>((ref) async {
  return ref.watch(calculatorsRepositoryProvider).specialStructures();
});

class SpecialStructuresScreen extends ConsumerWidget {
  const SpecialStructuresScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final rootKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_structuresProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootKey.currentState?.openDrawer(),
        ),
        title: Text(tr(lang,
            en: 'Special Structure Rates',
            sw: 'Viwango vya Miundo Maalum',
            fr: 'Tarifs structures spéciales',
            ar: 'أسعار الهياكل الخاصة')),
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
        onRefresh: () async => ref.invalidate(_structuresProvider),
        child: CalcAsyncBody<List<DesignSpecialStructureDto>>(
          async: async,
          onRetry: () => ref.invalidate(_structuresProvider),
          emptyIcon: Icons.foundation_outlined,
          emptyText: tr(lang,
              en: 'No special structures yet',
              sw: 'Hakuna miundo maalum bado'),
          isEmpty: (d) => d.isEmpty,
          builder: (items) => ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
            itemCount: items.length,
            itemBuilder: (_, i) {
              final s = items[i];
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: CalcCard(
                  isDarkMode: isDark,
                  padding: const EdgeInsets.symmetric(
                      horizontal: 14, vertical: 12),
                  child: Row(
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: AppColors.brandYellow.withValues(alpha: 0.2),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.foundation_rounded,
                            color: AppColors.brandBlue),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(s.name,
                                style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w800)),
                            const SizedBox(height: 4),
                            Text(
                              'TZS ${_fmtTzs(s.rateTzsPerSqm)} / m²',
                              style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.primary),
                            ),
                            if (!s.isActive)
                              Text(
                                tr(lang,
                                    en: 'Inactive',
                                    sw: 'Si Hai',
                                    fr: 'Inactif',
                                    ar: 'غير نشط'),
                                style: TextStyle(
                                    fontSize: 11, color: Colors.grey[600]),
                              ),
                          ],
                        ),
                      ),
                      PopupMenuButton<String>(
                        onSelected: (v) {
                          if (v == 'edit') _openForm(context, ref, item: s);
                          if (v == 'delete') _delete(context, ref, s);
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

  static String _fmtTzs(double v) {
    final n = v.round();
    final s = n.toString();
    final buf = StringBuffer();
    for (var i = 0; i < s.length; i++) {
      if (i != 0 && (s.length - i) % 3 == 0) buf.write(',');
      buf.write(s[i]);
    }
    return buf.toString();
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref,
      {DesignSpecialStructureDto? item}) async {
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Padding(
        padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom),
        child: _StructureForm(item: item),
      ),
    );
    if (ok == true) ref.invalidate(_structuresProvider);
  }

  Future<void> _delete(BuildContext context, WidgetRef ref,
      DesignSpecialStructureDto s) async {
    final lang = ref.read(currentLanguageProvider);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(tr(lang,
            en: 'Delete special structure?', sw: 'Futa muundo maalum?')),
        content: Text(s.name),
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
          .deleteSpecialStructure(s.id);
      ref.invalidate(_structuresProvider);
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(calcErrorMessage(e, lang: lang)),
        backgroundColor: AppColors.error,
      ));
    }
  }
}

class _StructureForm extends ConsumerStatefulWidget {
  final DesignSpecialStructureDto? item;
  const _StructureForm({this.item});

  @override
  ConsumerState<_StructureForm> createState() => _StructureFormState();
}

class _StructureFormState extends ConsumerState<_StructureForm> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name =
      TextEditingController(text: widget.item?.name ?? '');
  late final TextEditingController _rate = TextEditingController(
      text: widget.item == null ? '' : widget.item!.rateTzsPerSqm.toString());
  late final TextEditingController _sortOrder = TextEditingController(
      text: (widget.item?.sortOrder ?? 0).toString());
  late bool _active = widget.item?.isActive ?? true;
  bool _saving = false;

  bool get _isEdit => widget.item != null;

  @override
  void dispose() {
    _name.dispose();
    _rate.dispose();
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
                          en: 'Edit special structure',
                          sw: 'Hariri muundo maalum')
                      : tr(lang,
                          en: 'New special structure',
                          sw: 'Muundo maalum mpya'),
                  style:
                      const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _name,
                  decoration: InputDecoration(
                    labelText: tr(lang,
                        en: 'Structure name', sw: 'Jina la muundo'),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? tr(lang, en: 'Required', sw: 'Inahitajika')
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _rate,
                  decoration: InputDecoration(
                    labelText:
                        tr(lang, en: 'Rate (TZS / m²)', sw: 'Kiwango (TZS / m²)'),
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))],
                  validator: (v) {
                    final n = double.tryParse((v ?? '').trim());
                    if (n == null || n < 0) {
                      return tr(lang,
                          en: 'Enter a positive number',
                          sw: 'Ingiza nambari chanya');
                    }
                    return null;
                  },
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final lang = ref.read(currentLanguageProvider);
    final body = {
      'name': _name.text.trim(),
      'rate_tzs_per_sqm': double.tryParse(_rate.text.trim()) ?? 0,
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_active': _active,
    };
    final repo = ref.read(calculatorsRepositoryProvider);
    try {
      if (_isEdit) {
        await repo.updateSpecialStructure(widget.item!.id, body);
      } else {
        await repo.createSpecialStructure(body);
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
