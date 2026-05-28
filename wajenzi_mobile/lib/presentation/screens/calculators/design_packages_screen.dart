import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/calculators/calculator_models.dart';
import '../../../data/repositories/calculators_repository.dart';
import '../../providers/settings_provider.dart';
import 'calculator_shared.dart';

final _packagesProvider =
    FutureProvider.autoDispose<List<DesignPackageDto>>((ref) async {
  return ref.watch(calculatorsRepositoryProvider).designPackages();
});

class DesignPackagesScreen extends ConsumerWidget {
  const DesignPackagesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final rootKey = ref.read(rootScaffoldKeyProvider);
    final async = ref.watch(_packagesProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootKey.currentState?.openDrawer(),
        ),
        title: Text(tr(lang,
            en: 'Design Packages',
            sw: 'Pakeji za Ubunifu',
            fr: 'Forfaits design',
            ar: 'حزم التصميم')),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton.extended(
          onPressed: () => _openForm(context, ref),
          icon: const Icon(Icons.add_rounded),
          label: Text(tr(lang, en: 'New', sw: 'Mpya', fr: 'Nouveau', ar: 'جديد')),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_packagesProvider),
        child: CalcAsyncBody<List<DesignPackageDto>>(
          async: async,
          onRetry: () => ref.invalidate(_packagesProvider),
          emptyIcon: Icons.inventory_2_outlined,
          emptyText: tr(lang,
              en: 'No design packages yet',
              sw: 'Hakuna pakeji bado',
              fr: 'Aucun forfait',
              ar: 'لا توجد حزم'),
          isEmpty: (d) => d.isEmpty,
          builder: (items) {
            final low = items.where((p) => p.riseType == 'low').toList();
            final high = items.where((p) => p.riseType == 'high').toList();
            return ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 100),
              children: [
                if (low.isNotEmpty)
                  CalcSectionTitle(
                      label: tr(lang,
                          en: 'LOW-RISE (single storey)',
                          sw: 'GHOROFA MOJA',
                          fr: 'PLAIN-PIED',
                          ar: 'طابق واحد')),
                ...low.map((p) =>
                    _packageTile(context, ref, p, isDark: isDark, lang: lang)),
                if (high.isNotEmpty)
                  CalcSectionTitle(
                      label: tr(lang,
                          en: 'HIGH-RISE (multi storey)',
                          sw: 'GHOROFA NYINGI',
                          fr: 'IMMEUBLE',
                          ar: 'متعدد الطوابق')),
                ...high.map((p) =>
                    _packageTile(context, ref, p, isDark: isDark, lang: lang)),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _packageTile(
    BuildContext context,
    WidgetRef ref,
    DesignPackageDto p, {
    required bool isDark,
    required AppLanguage lang,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: CalcCard(
        isDarkMode: isDark,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    p.name,
                    style: const TextStyle(
                        fontSize: 16, fontWeight: FontWeight.w800),
                  ),
                ),
                Text(
                  'USD ${p.priceUsd.round()}',
                  style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      color: AppColors.primary,
                      fontSize: 16),
                ),
                PopupMenuButton<String>(
                  onSelected: (v) {
                    if (v == 'edit') _openForm(context, ref, item: p);
                    if (v == 'delete') _delete(context, ref, p);
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
                          tr(lang, en: 'Delete', sw: 'Futa', fr: 'Supprimer', ar: 'حذف'),
                          style: const TextStyle(color: AppColors.error),
                        )),
                  ],
                ),
              ],
            ),
            if (p.includedServices.isNotEmpty) ...[
              const SizedBox(height: 6),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: p.includedServices
                    .map((s) => Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: AppColors.brandGreen.withValues(alpha: 0.12),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            s,
                            style: const TextStyle(
                                fontSize: 11, fontWeight: FontWeight.w600),
                          ),
                        ))
                    .toList(),
              ),
            ],
            if (!p.isActive) ...[
              const SizedBox(height: 6),
              Text(
                tr(lang, en: 'Inactive', sw: 'Si Hai', fr: 'Inactif', ar: 'غير نشط'),
                style: TextStyle(fontSize: 11, color: Colors.grey[600]),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(BuildContext context, WidgetRef ref,
      {DesignPackageDto? item}) async {
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Padding(
        padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom),
        child: _PackageForm(item: item),
      ),
    );
    if (ok == true) ref.invalidate(_packagesProvider);
  }

  Future<void> _delete(
      BuildContext context, WidgetRef ref, DesignPackageDto p) async {
    final lang = ref.read(currentLanguageProvider);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text(tr(lang,
            en: 'Delete package?',
            sw: 'Futa pakeji?',
            fr: 'Supprimer ?',
            ar: 'حذف الحزمة؟')),
        content: Text(p.name),
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
      await ref
          .read(calculatorsRepositoryProvider)
          .deleteDesignPackage(p.id);
      ref.invalidate(_packagesProvider);
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(calcErrorMessage(e, lang: lang)),
        backgroundColor: AppColors.error,
      ));
    }
  }
}

class _PackageForm extends ConsumerStatefulWidget {
  final DesignPackageDto? item;
  const _PackageForm({this.item});

  @override
  ConsumerState<_PackageForm> createState() => _PackageFormState();
}

class _PackageFormState extends ConsumerState<_PackageForm> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name =
      TextEditingController(text: widget.item?.name ?? '');
  late final TextEditingController _price = TextEditingController(
      text: widget.item == null ? '' : widget.item!.priceUsd.toString());
  late final TextEditingController _services = TextEditingController(
      text: widget.item == null
          ? ''
          : widget.item!.includedServices.join('\n'));
  late final TextEditingController _sortOrder = TextEditingController(
      text: (widget.item?.sortOrder ?? 0).toString());
  late String _riseType = widget.item?.riseType ?? 'low';
  late bool _active = widget.item?.isActive ?? true;
  bool _saving = false;

  bool get _isEdit => widget.item != null;

  @override
  void dispose() {
    _name.dispose();
    _price.dispose();
    _services.dispose();
    _sortOrder.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final lang = ref.watch(currentLanguageProvider);
    final isDark = ref.watch(isDarkModeProvider);
    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.88,
      ),
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
                          en: 'Edit design package',
                          sw: 'Hariri pakeji',
                          fr: 'Modifier le forfait',
                          ar: 'تعديل الحزمة')
                      : tr(lang,
                          en: 'New design package',
                          sw: 'Pakeji mpya',
                          fr: 'Nouveau forfait',
                          ar: 'حزمة جديدة'),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _name,
                  decoration: InputDecoration(
                    labelText: tr(lang,
                        en: 'Name (Silver, Gold, Platinum…)',
                        sw: 'Jina',
                        fr: 'Nom',
                        ar: 'الاسم'),
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
                      child: SegmentedButton<String>(
                        segments: [
                          ButtonSegment(
                              value: 'low',
                              label: Text(tr(lang,
                                  en: 'Low-rise',
                                  sw: 'Ghorofa moja',
                                  fr: 'Plain-pied',
                                  ar: 'طابق واحد'))),
                          ButtonSegment(
                              value: 'high',
                              label: Text(tr(lang,
                                  en: 'High-rise',
                                  sw: 'Ghorofa nyingi',
                                  fr: 'Immeuble',
                                  ar: 'متعدد'))),
                        ],
                        selected: {_riseType},
                        onSelectionChanged: (s) =>
                            setState(() => _riseType = s.first),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _price,
                  decoration: InputDecoration(
                    labelText: tr(lang,
                        en: 'Price (USD)', sw: 'Bei (USD)', fr: 'Prix (USD)', ar: 'السعر'),
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'^[0-9.]*'))
                  ],
                  validator: (v) {
                    final n = double.tryParse((v ?? '').trim());
                    if (n == null || n < 0) {
                      return tr(lang, en: 'Enter a number', sw: 'Ingiza nambari');
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _services,
                  decoration: InputDecoration(
                    labelText: tr(lang,
                        en: 'Included services (one per line)',
                        sw: 'Huduma zilizojumuishwa (moja kwa mstari)',
                        fr: 'Services inclus (un par ligne)',
                        ar: 'الخدمات (واحد لكل سطر)'),
                    border: const OutlineInputBorder(),
                    alignLabelWithHint: true,
                  ),
                  minLines: 3,
                  maxLines: 6,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _sortOrder,
                  decoration: InputDecoration(
                    labelText:
                        tr(lang, en: 'Sort order', sw: 'Mpangilio', fr: 'Ordre'),
                    border: const OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                ),
                const SizedBox(height: 6),
                SwitchListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(tr(lang,
                      en: 'Active', sw: 'Hai', fr: 'Actif', ar: 'نشط')),
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
    final services = _services.text
        .split('\n')
        .map((s) => s.trim())
        .where((s) => s.isNotEmpty)
        .toList();
    final body = <String, dynamic>{
      'name': _name.text.trim(),
      'rise_type': _riseType,
      'price_usd': double.tryParse(_price.text.trim()) ?? 0,
      'included_services': services,
      'sort_order': int.tryParse(_sortOrder.text.trim()) ?? 0,
      'is_active': _active,
    };
    final repo = ref.read(calculatorsRepositoryProvider);
    try {
      if (_isEdit) {
        await repo.updateDesignPackage(widget.item!.id, body);
      } else {
        await repo.createDesignPackage(body);
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
