import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _serviceInterestedsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/service-interesteds');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

String _serviceInterestedTr(
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

class ServiceInterestedsScreen extends ConsumerWidget {
  const ServiceInterestedsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncData = ref.watch(_serviceInterestedsProvider);
    final language = ref.watch(currentLanguageProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _serviceInterestedTr(
            language,
            en: 'Services Interested',
            sw: 'Huduma Zinazovutia',
            fr: 'Services interessants',
            ar: 'الخدمات المطلوبة',
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_serviceInterestedsProvider),
        child: asyncData.when(
          loading: () => LoadingWidget(
            message: _serviceInterestedTr(
              language,
              en: 'Loading service interesteds...',
              sw: 'Inapakia huduma zinazovutia...',
              fr: 'Chargement des services interessants...',
              ar: 'جارٍ تحميل الخدمات المطلوبة...',
            ),
          ),
          error: (error, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 48),
              const Icon(Icons.error_outline, size: 56, color: AppColors.error),
              const SizedBox(height: 12),
              Text(
                _serviceInterestedTr(
                  language,
                  en: 'Failed to load service interesteds',
                  sw: 'Imeshindikana kupakia huduma zinazovutia',
                  fr: 'Impossible de charger les services interessants',
                  ar: 'فشل تحميل الخدمات المطلوبة',
                ),
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              Text(vatErrorMessage(error), textAlign: TextAlign.center),
            ],
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(24),
                children: [
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: Column(
                      children: [
                        const Icon(Icons.design_services_outlined, size: 56, color: AppColors.primary),
                        const SizedBox(height: 12),
                        Text(
                          _serviceInterestedTr(
                            language,
                            en: 'No service interesteds found',
                            sw: 'Hakuna huduma zinazovutia zilizopatikana',
                            fr: 'Aucun service interessant trouve',
                            ar: 'لم يتم العثور على خدمات مطلوبة',
                          ),
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _serviceInterestedTr(
                            language,
                            en: 'Create a service interested to match the web settings page.',
                            sw: 'Unda huduma inayovutia ili ilingane na ukurasa wa mipangilio wa web.',
                            fr: 'Creez un service interessant pour l’aligner avec la page des parametres web.',
                            ar: 'أنشئ خدمة مطلوبة لتتطابق مع صفحة إعدادات الويب.',
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: () => _openForm(context, ref),
                          icon: const Icon(Icons.add),
                          label: Text(
                            _serviceInterestedTr(
                              language,
                              en: 'New Service Interested',
                              sw: 'Huduma Mpya Inayovutia',
                              fr: 'Nouveau service interessant',
                              ar: 'خدمة مطلوبة جديدة',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              );
            }

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 18,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 52,
                        height: 52,
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: const Icon(Icons.design_services_outlined, color: AppColors.primary),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _serviceInterestedTr(
                                language,
                                en: 'Services Interested',
                                sw: 'Huduma Zinazovutia',
                                fr: 'Services interessants',
                                ar: 'الخدمات المطلوبة',
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              _serviceInterestedTr(
                                language,
                                en: 'Showing ${items.length} records',
                                sw: 'Inaonyesha rekodi ${items.length}',
                                fr: '${items.length} enregistrements affiches',
                                ar: 'يتم عرض ${items.length} سجلات',
                              ),
                              style: const TextStyle(color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                ...List.generate(items.length, (index) {
                  final item = items[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                      leading: Container(
                        width: 38,
                        height: 38,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: Colors.grey.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          '${index + 1}',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      title: Text(
                        item['name']?.toString() ?? '-',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      trailing: PopupMenuButton<String>(
                        onSelected: (value) {
                          if (value == 'edit') {
                            _openForm(context, ref, item: item);
                          } else if (value == 'delete') {
                            _deleteItem(context, ref, item);
                          }
                        },
                        itemBuilder: (_) => [
                          PopupMenuItem(
                            value: 'edit',
                            child: Text(
                              _serviceInterestedTr(
                                language,
                                en: 'Edit',
                                sw: 'Hariri',
                                fr: 'Modifier',
                                ar: 'تعديل',
                              ),
                            ),
                          ),
                          PopupMenuItem(
                            value: 'delete',
                            child: Text(
                              _serviceInterestedTr(
                                language,
                                en: 'Delete',
                                sw: 'Futa',
                                fr: 'Supprimer',
                                ar: 'حذف',
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(context, ref),
        icon: const Icon(Icons.add),
        label: Text(
          _serviceInterestedTr(
            language,
            en: 'New Service Interested',
            sw: 'Huduma Mpya Inayovutia',
            fr: 'Nouveau service interessant',
            ar: 'خدمة مطلوبة جديدة',
          ),
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
        child: _ServiceInterestedFormSheet(item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_serviceInterestedsProvider);
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
          _serviceInterestedTr(
            language,
            en: 'Delete Service Interested',
            sw: 'Futa Huduma Inayovutia',
            fr: 'Supprimer le service interessant',
            ar: 'حذف الخدمة المطلوبة',
          ),
        ),
        content: Text(
          _serviceInterestedTr(
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
              _serviceInterestedTr(
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
              _serviceInterestedTr(
                language,
                en: 'Delete',
                sw: 'Futa',
                fr: 'Supprimer',
                ar: 'حذف',
              ),
            ),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/service-interesteds/${item['id']}');
      ref.invalidate(_serviceInterestedsProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _serviceInterestedTr(
              language,
              en: 'Service interested deleted successfully',
              sw: 'Huduma inayovutia imefutwa kwa mafanikio',
              fr: 'Le service interessant a ete supprime avec succes',
              ar: 'تم حذف الخدمة المطلوبة بنجاح',
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

class _ServiceInterestedFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _ServiceInterestedFormSheet({this.item});

  @override
  ConsumerState<_ServiceInterestedFormSheet> createState() =>
      _ServiceInterestedFormSheetState();
}

class _ServiceInterestedFormSheetState
    extends ConsumerState<_ServiceInterestedFormSheet> {
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
                      ? _serviceInterestedTr(
                          language,
                          en: 'Edit Service Interested',
                          sw: 'Hariri Huduma Inayovutia',
                          fr: 'Modifier le service interessant',
                          ar: 'تعديل الخدمة المطلوبة',
                        )
                      : _serviceInterestedTr(
                          language,
                          en: 'Create New Service Interested',
                          sw: 'Unda Huduma Mpya Inayovutia',
                          fr: 'Creer un nouveau service interessant',
                          ar: 'إنشاء خدمة مطلوبة جديدة',
                        ),
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 18),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    labelText: _serviceInterestedTr(
                      language,
                      en: 'Name',
                      sw: 'Jina',
                      fr: 'Nom',
                      ar: 'الاسم',
                    ),
                    border: const OutlineInputBorder(),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty)
                          ? _serviceInterestedTr(
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
                          ? _serviceInterestedTr(
                              language,
                              en: 'Saving...',
                              sw: 'Inahifadhi...',
                              fr: 'Enregistrement...',
                              ar: 'جارٍ الحفظ...',
                            )
                          : (_isEdit
                              ? _serviceInterestedTr(
                                  language,
                                  en: 'Update Service Interested',
                                  sw: 'Sasisha Huduma Inayovutia',
                                  fr: 'Mettre a jour le service interessant',
                                  ar: 'تحديث الخدمة المطلوبة',
                                )
                              : _serviceInterestedTr(
                                  language,
                                  en: 'Save Service Interested',
                                  sw: 'Hifadhi Huduma Inayovutia',
                                  fr: 'Enregistrer le service interessant',
                                  ar: 'حفظ الخدمة المطلوبة',
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

    final payload = {
      'name': _nameController.text.trim(),
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/service-interesteds/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/service-interesteds', data: payload);
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
