import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _leadSourcesSearchProvider = StateProvider<String>((ref) => '');

final _leadSourcesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/lead-sources');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

String _leadSourceTr(
  AppLanguage language, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) {
  switch (language) {
    case AppLanguage.swahili:
      return sw ?? en;
    case AppLanguage.french:
      return fr ?? en;
    case AppLanguage.arabic:
      return ar ?? en;
    case AppLanguage.english:
      return en;
  }
}

class LeadSourcesScreen extends ConsumerWidget {
  const LeadSourcesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final asyncData = ref.watch(_leadSourcesProvider);
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_leadSourcesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _leadSourceTr(
            language,
            en: 'Lead Sources',
            sw: 'Chanzo za Mauzo',
            fr: 'Sources de prospects',
            ar: 'مصادر العملاء المحتملين',
          ),
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openForm(context, ref),
          child: const Icon(Icons.add_rounded),
          tooltip: _leadSourceTr(
            language,
            en: 'Add Source',
            sw: 'Ongeza Chanzo',
            fr: 'Ajouter une source',
            ar: 'إضافة مصدر',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_leadSourcesProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_leadSourcesSearchProvider.notifier).state =
                          value,
                  decoration: InputDecoration(
                    hintText: _leadSourceTr(
                      language,
                      en: 'Search sources...',
                      sw: 'Tafuta chanzo...',
                      fr: 'Rechercher des sources...',
                      ar: 'ابحث عن المصادر...',
                    ),
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(
                                          _leadSourcesSearchProvider.notifier,
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
              ),
            ),
            asyncData.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.error_outline,
                        size: 64,
                        color: Colors.grey[400],
                      ),
                      const SizedBox(height: 16),
                      Text('$error', textAlign: TextAlign.center),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () => ref.invalidate(_leadSourcesProvider),
                        child: Text(
                          _leadSourceTr(
                            language,
                            en: 'Retry',
                            sw: 'Jaribu tena',
                            fr: 'Reessayer',
                            ar: 'أعد المحاولة',
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              data: (items) {
                final filtered = search.isEmpty
                    ? items
                    : items.where((item) {
                        final name =
                            item['name']?.toString().toLowerCase() ?? '';
                        return name.contains(search);
                      }).toList();

                if (filtered.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.hub_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            items.isEmpty
                                ? _leadSourceTr(
                                    language,
                                    en: 'No lead sources found',
                                    sw: 'Hakuna chanzo',
                                    fr: 'Aucune source de prospect trouvee',
                                    ar: 'لم يتم العثور على مصادر العملاء المحتملين',
                                  )
                                : _leadSourceTr(
                                    language,
                                    en: 'No matching results',
                                    sw: 'Hakuna matokeo yanayolingana',
                                    fr: 'Aucun resultat correspondant',
                                    ar: 'لا توجد نتائج مطابقة',
                                  ),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () =>
                                  ref
                                          .read(
                                            _leadSourcesSearchProvider.notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.clear),
                              label: Text(
                                _leadSourceTr(
                                  language,
                                  en: 'Clear search',
                                  sw: 'Futa utafutaji',
                                  fr: 'Effacer la recherche',
                                  ar: 'مسح البحث',
                                ),
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
                      final item = filtered[index];
                      return _LeadSourceCard(
                        item: item,
                        index: index,
                        onEdit: () => _openForm(context, ref, item: item),
                        onDelete: () => _deleteItem(context, ref, item),
                        language: language,
                        isDarkMode: isDarkMode,
                      );
                    }, childCount: filtered.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.52,
        child: _LeadSourceFormSheet(item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_leadSourcesProvider);
    }
  }

  Future<void> _deleteItem(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final language = ref.read(currentLanguageProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(
          _leadSourceTr(
            language,
            en: 'Delete Lead Source',
            sw: 'Futa Chanzo',
            fr: 'Supprimer la source du prospect',
            ar: 'حذف مصدر العميل المحتمل',
          ),
        ),
        content: Text(
          _leadSourceTr(
            language,
            en: 'Delete ${item['name']}?',
            sw: 'Futa ${item['name']}?',
            fr: 'Supprimer ${item['name']} ?',
            ar: 'هل تريد حذف ${item['name']}؟',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(
              _leadSourceTr(
                language,
                en: 'Cancel',
                sw: 'Ghairi',
                fr: 'Annuler',
                ar: 'إلغاء',
              ),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(
              _leadSourceTr(
                language,
                en: 'Delete',
                sw: 'Futa',
                fr: 'Supprimer',
                ar: 'حذف',
              ),
              style: const TextStyle(color: AppColors.error),
            ),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/lead-sources/${item['id']}');
      ref.invalidate(_leadSourcesProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _leadSourceTr(
              language,
              en: 'Lead source deleted successfully',
              sw: 'Chanzo kimefutwa',
              fr: 'Source du prospect supprimee avec succes',
              ar: 'تم حذف مصدر العميل المحتمل بنجاح',
            ),
          ),
          backgroundColor: AppColors.success,
        ),
      );
    } catch (error) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _LeadSourceCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final AppLanguage language;
  final bool isDarkMode;

  const _LeadSourceCard({
    required this.item,
    required this.index,
    required this.onEdit,
    required this.onDelete,
    required this.language,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 10,
        ),
        leading: Container(
          width: 38,
          height: 38,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(
            '${index + 1}',
            style: const TextStyle(
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
        ),
        title: Text(
          item['name']?.toString() ?? '-',
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
        ),
        trailing: PopupMenuButton<String>(
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
                  Text(
                    _leadSourceTr(
                      language,
                      en: 'Edit',
                      sw: 'Hariri',
                      fr: 'Modifier',
                      ar: 'تعديل',
                    ),
                  ),
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
                    _leadSourceTr(
                      language,
                      en: 'Delete',
                      sw: 'Futa',
                      fr: 'Supprimer',
                      ar: 'حذف',
                    ),
                    style: const TextStyle(color: AppColors.error),
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

class _LeadSourceFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _LeadSourceFormSheet({this.item});

  @override
  ConsumerState<_LeadSourceFormSheet> createState() =>
      _LeadSourceFormSheetState();
}

class _LeadSourceFormSheetState extends ConsumerState<_LeadSourceFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  bool _submitting = false;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.item?['name']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            20,
            16,
            20,
            MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Form(
            key: _formKey,
            child: ListView(
              children: [
                Center(
                  child: Container(
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(999),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  _isEdit
                      ? _leadSourceTr(
                          language,
                          en: 'Edit Lead Source',
                          sw: 'Hariri Chanzo',
                          fr: 'Modifier la source du prospect',
                          ar: 'تعديل مصدر العميل المحتمل',
                        )
                      : _leadSourceTr(
                          language,
                          en: 'Create New Lead Source',
                          sw: 'Unda Chanzo Mpya',
                          fr: 'Creer une nouvelle source de prospect',
                          ar: 'إنشاء مصدر عميل محتمل جديد',
                        ),
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: _leadSourceTr(
                      language,
                      en: 'Name',
                      sw: 'Jina',
                      fr: 'Nom',
                      ar: 'الاسم',
                    ),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? _leadSourceTr(
                          language,
                          en: 'Name is required',
                          sw: 'Jina linahitajika',
                          fr: 'Le nom est obligatoire',
                          ar: 'الاسم مطلوب',
                        )
                      : null,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    child: Text(
                      _submitting
                          ? _leadSourceTr(
                              language,
                              en: 'Saving...',
                              sw: 'Inahifadhi...',
                              fr: 'Enregistrement...',
                              ar: 'جارٍ الحفظ...',
                            )
                          : (_isEdit
                                ? _leadSourceTr(
                                    language,
                                    en: 'Update Lead Source',
                                    sw: 'Sasisha Chanzo',
                                    fr: 'Mettre a jour la source du prospect',
                                    ar: 'تحديث مصدر العميل المحتمل',
                                  )
                                : _leadSourceTr(
                                    language,
                                    en: 'Save Lead Source',
                                    sw: 'Hifadhi Chanzo',
                                    fr: 'Enregistrer la source du prospect',
                                    ar: 'حفظ مصدر العميل المحتمل',
                                  )),
                    ),
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
    setState(() => _submitting = true);

    final payload = {'name': _nameController.text.trim()};

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/lead-sources/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/lead-sources', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}
