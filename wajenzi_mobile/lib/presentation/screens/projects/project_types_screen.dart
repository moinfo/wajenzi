import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _projectTypesSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _projectTypesProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/project-types');
  final payload = response.data['data'];
  final collection = payload is Map<String, dynamic> ? payload : null;
  final items = collection?['data'] ?? payload;
  return items as List? ?? const [];
});

final _projectTypeDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-types/$id');
      final payload = response.data['data'];
      return payload is Map<String, dynamic> ? payload : const {};
    });

String _projectTypeTr(
  AppLanguage language, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) {
  return switch (language) {
    AppLanguage.swahili => sw ?? en,
    AppLanguage.french => fr ?? en,
    AppLanguage.arabic => ar ?? en,
    AppLanguage.english => en,
  };
}

String _projectTypeErrorMessage(Object error, AppLanguage language) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }
    }
  }

  return _projectTypeTr(
    language,
    en: 'Something went wrong',
    sw: 'Hitilafu imetokea',
    fr: 'Un probleme est survenu',
    ar: 'حدث خطأ ما',
  );
}

class ProjectTypesScreen extends ConsumerStatefulWidget {
  const ProjectTypesScreen({super.key});

  @override
  ConsumerState<ProjectTypesScreen> createState() => _ProjectTypesScreenState();
}

class _ProjectTypesScreenState extends ConsumerState<ProjectTypesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final typesAsync = ref.watch(_projectTypesProvider);
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_projectTypesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _projectTypeTr(
            language,
            en: 'Project Types',
            sw: 'Aina za Mradi',
            fr: 'Types de projets',
            ar: 'أنواع المشاريع',
          ),
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showTypeForm(context),
          child: const Icon(Icons.add_rounded),
          tooltip: _projectTypeTr(
            language,
            en: 'Add',
            sw: 'Ongeza',
            fr: 'Ajouter',
            ar: 'إضافة',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_projectTypesProvider.future),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_projectTypesSearchProvider.notifier).state =
                          value,
                  decoration: InputDecoration(
                    hintText: _projectTypeTr(
                      language,
                      en: 'Search project types...',
                      sw: 'Tafuta aina za mradi...',
                      fr: 'Rechercher des types de projets...',
                      ar: 'ابحث عن أنواع المشاريع...',
                    ),
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(
                                          _projectTypesSearchProvider.notifier,
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
            typesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _ErrorView(
                  error: e,
                  language: language,
                  onRetry: () => ref.invalidate(_projectTypesProvider),
                ),
              ),
              data: (allItems) {
                final types = search.isEmpty
                    ? allItems
                    : allItems.where((type) {
                        final haystack = [
                          type['name'] ?? '',
                          type['description'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (types.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.category_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? _projectTypeTr(
                                    language,
                                    en: 'No project types found',
                                    sw: 'Hakuna aina za mradi zilizopatikana',
                                    fr: 'Aucun type de projet trouve',
                                    ar: 'لم يتم العثور على أنواع مشاريع',
                                  )
                                : _projectTypeTr(
                                    language,
                                    en: 'No types match your search',
                                    sw: 'Hakuna matokeo yanayolingana',
                                    fr: 'Aucun type ne correspond a votre recherche',
                                    ar: 'لا توجد أنواع مطابقة لبحثك',
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
                                            _projectTypesSearchProvider
                                                .notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.arrow_back_rounded),
                              label: Text(
                                _projectTypeTr(
                                  language,
                                  en: 'Back',
                                  sw: 'Rudi',
                                  fr: 'Retour',
                                  ar: 'رجوع',
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
                      return _TypeCard(
                        type: types[index],
                        language: language,
                        isDarkMode: isDarkMode,
                        onTap: () => _showTypeDetail(context, types[index]),
                        onEdit: () =>
                            _showTypeForm(context, type: types[index]),
                        onDelete: () => _deleteType(context, types[index]),
                      );
                    }, childCount: types.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showTypeForm(BuildContext context, {Map<String, dynamic>? type}) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _TypeFormSheet(type: type),
    ).then((result) {
      if (result == true) ref.invalidate(_projectTypesProvider);
    });
  }

  void _showTypeDetail(BuildContext context, Map<String, dynamic> type) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _TypeDetailSheet(type: type),
    );
  }

  Future<void> _deleteType(
    BuildContext context,
    Map<String, dynamic> type,
  ) async {
    final language = ref.read(currentLanguageProvider);
    final isDarkMode = ref.read(isDarkModeProvider);
    final typeId = _projectTypeId(type);

    if (typeId == null || typeId <= 0) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _projectTypeTr(
                language,
                en: 'This project type has an invalid ID',
                sw: 'Aina hii ya mradi ina kitambulisho batili',
                fr: 'Ce type de projet a un identifiant invalide',
                ar: 'هذا النوع من المشاريع يحتوي على معرّف غير صالح',
              ),
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          _projectTypeTr(
            language,
            en: 'Confirm Delete',
            sw: 'Thibitisha Kufuta',
            fr: 'Confirmer la suppression',
            ar: 'تأكيد الحذف',
          ),
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          _projectTypeTr(
            language,
            en: 'Are you sure you want to delete this project type?',
            sw: 'Je, una uhakika unataka kufuta aina hii ya mradi?',
            fr: 'Voulez-vous vraiment supprimer ce type de projet ?',
            ar: 'هل أنت متأكد أنك تريد حذف هذا النوع من المشاريع؟',
          ),
          style: TextStyle(
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              _projectTypeTr(
                language,
                en: 'Cancel',
                sw: 'Ghairi',
                fr: 'Annuler',
                ar: 'إلغاء',
              ),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              _projectTypeTr(
                language,
                en: 'Delete',
                sw: 'Futa',
                fr: 'Supprimer',
                ar: 'حذف',
              ),
              style: const TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      try {
        final api = ref.read(apiClientProvider);
        await api.delete('/project-types/$typeId');
        ref.invalidate(_projectTypesProvider);
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                _projectTypeTr(
                  language,
                  en: 'Type deleted',
                  sw: 'Aina imefutwa',
                  fr: 'Type supprime',
                  ar: 'تم حذف النوع',
                ),
              ),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_projectTypeErrorMessage(e, language)),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }
}

class _TypeCard extends StatelessWidget {
  final Map<String, dynamic> type;
  final AppLanguage language;
  final bool isDarkMode;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _TypeCard({
    required this.type,
    required this.language,
    required this.isDarkMode,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final projectsCount = type['projects_count'] as int? ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF9B59B6).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.category, color: Color(0xFF9B59B6)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          type['name'] as String? ?? '-',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          type['description'] as String? ?? '-',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'view') {
                        onTap();
                      } else if (value == 'edit') {
                        onEdit();
                      } else if (value == 'delete') {
                        onDelete();
                      }
                    },
                    itemBuilder: (ctx) => [
                      PopupMenuItem(
                        value: 'view',
                        child: Row(
                          children: [
                            const Icon(Icons.visibility, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              _projectTypeTr(
                                language,
                                en: 'View',
                                sw: 'Tazama',
                                fr: 'Voir',
                                ar: 'عرض',
                              ),
                            ),
                          ],
                        ),
                      ),
                      PopupMenuItem(
                        value: 'edit',
                        child: Row(
                          children: [
                            const Icon(Icons.edit, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              _projectTypeTr(
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
                              Icons.delete,
                              size: 20,
                              color: Colors.red,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              _projectTypeTr(
                                language,
                                en: 'Delete',
                                sw: 'Futa',
                                fr: 'Supprimer',
                                ar: 'حذف',
                              ),
                              style: const TextStyle(color: Colors.red),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const Divider(height: 20),
              Row(
                children: [
                  Icon(
                    Icons.folder_outlined,
                    size: 16,
                    color: isDarkMode ? Colors.white54 : Colors.grey[600],
                  ),
                  const SizedBox(width: 6),
                  Text(
                    '$projectsCount ${_projectTypeTr(language, en: 'projects', sw: 'miradi', fr: 'projets', ar: 'مشاريع')}',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: isDarkMode
                          ? Colors.white70
                          : AppColors.textPrimary,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TypeDetailSheet extends ConsumerWidget {
  final Map<String, dynamic> type;

  const _TypeDetailSheet({required this.type});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      height: MediaQuery.of(context).size.height * 0.5,
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          _projectTypeTr(
                            language,
                            en: 'Type Details',
                            sw: 'Maelezo ya Aina',
                            fr: 'Details du type',
                            ar: 'تفاصيل النوع',
                          ),
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                children: [
                  _DetailRow(
                    label: _projectTypeTr(language, en: 'Name', sw: 'Jina', fr: 'Nom', ar: 'الاسم'),
                    value: type['name'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: _projectTypeTr(language, en: 'Description', sw: 'Maelezo', fr: 'Description', ar: 'الوصف'),
                    value: type['description'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: _projectTypeTr(language, en: 'Projects', sw: 'Miradi', fr: 'Projets', ar: 'المشاريع'),
                    value: '${type['projects_count'] ?? 0}',
                    dark: isDarkMode,
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

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool dark;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.dark,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: dark
            ? const Color(0xFF252540)
            : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: dark ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value.isEmpty ? '-' : value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: dark ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _TypeFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? type;

  const _TypeFormSheet({this.type});

  @override
  ConsumerState<_TypeFormSheet> createState() => _TypeFormSheetState();
}

class _TypeFormSheetState extends ConsumerState<_TypeFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _descriptionController = TextEditingController();
  bool _loading = false;

  late final bool _isEditing;
  int? _typeId;

  @override
  void initState() {
    super.initState();
    _isEditing = widget.type != null;
    if (_isEditing) {
      _typeId = _projectTypeId(widget.type!);
      _nameController.text = widget.type!['name'] as String? ?? '';
      _descriptionController.text =
          widget.type!['description'] as String? ?? '';
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  _isEditing
                      ? _projectTypeTr(
                          language,
                          en: 'Edit Type',
                          sw: 'Hariri Aina',
                          fr: 'Modifier le type',
                          ar: 'تعديل النوع',
                        )
                      : _projectTypeTr(
                          language,
                          en: 'New Type',
                          sw: 'Aina Mpya',
                          fr: 'Nouveau type',
                          ar: 'نوع جديد',
                        ),
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  _projectTypeTr(language, en: 'Name *', sw: 'Jina *', fr: 'Nom *', ar: 'الاسم *'),
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode
                        ? Colors.white70
                        : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    hintText: _projectTypeTr(
                      language,
                      en: 'Type name',
                      sw: 'Jina la aina',
                      fr: 'Nom du type',
                      ar: 'اسم النوع',
                    ),
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) {
                      return _projectTypeTr(
                        language,
                        en: 'Name required',
                        sw: 'Jina yahitajika',
                        fr: 'Le nom est requis',
                        ar: 'الاسم مطلوب',
                      );
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                Text(
                  _projectTypeTr(language, en: 'Description', sw: 'Maelezo', fr: 'Description', ar: 'الوصف'),
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode
                        ? Colors.white70
                        : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _descriptionController,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText: _projectTypeTr(
                      language,
                      en: 'Description (optional)',
                      sw: 'Maelezo (hiari)',
                      fr: 'Description (optionnelle)',
                      ar: 'الوصف (اختياري)',
                    ),
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _loading ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: _loading
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            _isEditing
                                ? _projectTypeTr(
                                    language,
                                    en: 'Save Changes',
                                    sw: 'Hifadhi Mabadiliko',
                                    fr: 'Enregistrer les modifications',
                                    ar: 'حفظ التغييرات',
                                  )
                                : _projectTypeTr(
                                    language,
                                    en: 'Create Type',
                                    sw: 'Unda Aina',
                                    fr: 'Creer le type',
                                    ar: 'إنشاء النوع',
                                  ),
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                  ),
                ),
                const SizedBox(height: 16),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'name': _nameController.text,
        'description': _descriptionController.text,
      };

      if (_isEditing && _typeId != null) {
        await api.put('/project-types/$_typeId', data: data);
      } else {
        await api.post('/project-types', data: data);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _projectTypeErrorMessage(e, ref.read(currentLanguageProvider)),
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

int? _projectTypeId(Map<String, dynamic> type) {
  final raw = type['id'];
  if (raw is int) return raw;
  if (raw is num) return raw.toInt();
  if (raw is String) return int.tryParse(raw.trim());
  return null;
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final AppLanguage language;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.language,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          _projectTypeTr(
            language,
            en: 'Something went wrong',
            sw: 'Hitilafu imetokea',
            fr: 'Un probleme est survenu',
            ar: 'حدث خطأ ما',
          ),
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          '$error',
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(
              _projectTypeTr(
                language,
                en: 'Try again',
                sw: 'Jaribu tena',
                fr: 'Reessayer',
                ar: 'حاول مرة أخرى',
              ),
            ),
          ),
        ),
      ],
    );
  }
}
