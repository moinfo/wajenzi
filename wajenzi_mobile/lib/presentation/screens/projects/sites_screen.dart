import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _sitesSearchProvider = StateProvider.autoDispose<String>((ref) => '');

String _sitesTr(
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

class _SitesFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final String? status;

  _SitesFilter({this.startDate, this.endDate, this.status});

  _SitesFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    String? status,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearStatus = false,
  }) {
    return _SitesFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      status: clearStatus ? null : (status ?? this.status),
    );
  }

  Map<String, String> toQueryParams() {
    final params = <String, String>{};
    if (startDate != null)
      params['start_date'] = DateFormat('yyyy-MM-dd').format(startDate!);
    if (endDate != null)
      params['end_date'] = DateFormat('yyyy-MM-dd').format(endDate!);
    if (status != null && status!.isNotEmpty) params['status'] = status!;
    return params;
  }
}

final _sitesFilterProvider = StateProvider.autoDispose<_SitesFilter>(
  (ref) => _SitesFilter(),
);

final _sitesProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(_sitesFilterProvider);
  final search = ref.watch(_sitesSearchProvider);

  try {
    final response = await api.get(
      '/sites',
      queryParameters: {
        if (search.isNotEmpty) 'search': search,
        ...filter.toQueryParams(),
      },
    );

    final data = response.data is Map<String, dynamic>
        ? response.data as Map<String, dynamic>
        : const <String, dynamic>{};
    final payload = data['data'] is Map
        ? Map<String, dynamic>.from(data['data'] as Map)
        : const <String, dynamic>{};

    return {
      'items': (payload['sites'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList(),
      'stats': payload['stats'] is Map
          ? Map<String, dynamic>.from(payload['stats'] as Map)
          : const <String, dynamic>{},
      'unavailable_on_live': false,
    };
  } on Exception catch (e) {
    if ('$e'.contains('404')) {
      return {
        'items': const <Map<String, dynamic>>[],
        'stats': const <String, dynamic>{},
        'unavailable_on_live': true,
      };
    }
    rethrow;
  }
});

final _siteDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>?, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/sites/$id');
      return response.data['data'] as Map<String, dynamic>?;
    });

String _siteErrorMessage(Object error, AppLanguage language) {
  final errStr = error.toString();
  if (errStr.contains('422')) {
    if (errStr.contains('name')) {
      return _sitesTr(
        language,
        en: 'Site name already exists',
        sw: 'Jina la eneo limewahi kutumika',
        fr: 'Le nom du site existe déjà',
        ar: 'اسم الموقع مستخدم بالفعل',
      );
    }
    if (errStr.contains('location')) {
      return _sitesTr(
        language,
        en: 'Location is required',
        sw: 'Mahali ni lazima',
        fr: 'L’emplacement est requis',
        ar: 'الموقع مطلوب',
      );
    }
  }
  return _sitesTr(
    language,
    en: 'Something went wrong',
    sw: 'Hitilafu imetokea',
    fr: 'Un problème est survenu',
    ar: 'حدث خطأ ما',
  );
}

class SitesScreen extends ConsumerStatefulWidget {
  const SitesScreen({super.key});

  @override
  ConsumerState<SitesScreen> createState() => _SitesScreenState();
}

class _SitesScreenState extends ConsumerState<SitesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final sitesAsync = ref.watch(_sitesProvider);
    final isDark = ref.watch(isDarkModeProvider);
    final language = ref.watch(currentLanguageProvider);
    final filter = ref.watch(_sitesFilterProvider);
    final search = ref.watch(_sitesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _sitesTr(
            language,
            en: 'Project Sites',
            sw: 'Maeneo ya Mradi',
            fr: 'Sites du projet',
            ar: 'مواقع المشروع',
          ),
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showSiteForm(context),
          child: const Icon(Icons.add_rounded),
          tooltip: _sitesTr(
            language,
            en: 'Add',
            sw: 'Ongeza',
            fr: 'Ajouter',
            ar: 'إضافة',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_sitesProvider.future),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) =>
                          ref.read(_sitesSearchProvider.notifier).state = value,
                      decoration: InputDecoration(
                        hintText: _sitesTr(
                          language,
                          en: 'Search sites...',
                          sw: 'Tafuta maeneo...',
                          fr: 'Rechercher des sites...',
                          ar: 'ابحث في المواقع...',
                        ),
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(_sitesSearchProvider.notifier)
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDark
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
                    const SizedBox(height: 12),
                    _SiteFilters(
                      filter: filter,
                      language: language,
                      isDarkMode: isDark,
                    ),
                  ],
                ),
              ),
            ),
            sitesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _SitesErrorView(
                  error: e,
                  language: language,
                  onRetry: () => ref.invalidate(_sitesProvider),
                ),
              ),
              data: (payload) {
                if (payload['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.location_off_rounded,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _sitesTr(
                                language,
                                en: 'Project Sites is not available on the live API right now.',
                                sw: 'Project Sites haipatikani kwenye live API kwa sasa.',
                                fr: 'Les sites du projet ne sont pas disponibles sur l’API live pour le moment.',
                                ar: 'مواقع المشروع غير متاحة على واجهة الـ API المباشرة حالياً.',
                              ),
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey[700],
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }

                final allItems = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final sites = search.isEmpty
                    ? allItems
                    : allItems.where((site) {
                        final haystack = [
                          site['name'] ?? '',
                          site['location'] ?? '',
                          site['status'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (sites.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.location_off_rounded,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? _sitesTr(
                                    language,
                                    en: 'No sites found',
                                    sw: 'Hakuna maeneo',
                                    fr: 'Aucun site trouvé',
                                    ar: 'لم يتم العثور على مواقع',
                                  )
                                : _sitesTr(
                                    language,
                                    en: 'No sites match your search',
                                    sw: 'Hakuna matokeo yanayolingana',
                                    fr: 'Aucun site ne correspond à votre recherche',
                                    ar: 'لا توجد مواقع تطابق بحثك',
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
                                          .read(_sitesSearchProvider.notifier)
                                          .state =
                                      '',
                              icon: const Icon(Icons.arrow_back_rounded),
                              label: Text(
                                _sitesTr(
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
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _SiteCard(
                        site: sites[index],
                        isDark: isDark,
                        language: language,
                        onView: () => _showSiteDetail(context, sites[index]),
                        onEdit: () =>
                            _showSiteForm(context, site: sites[index]),
                        onDelete: () => _deleteSite(context, sites[index]),
                      ),
                      childCount: sites.length,
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showSiteDetail(BuildContext context, Map<String, dynamic> site) {
    final isDark = ref.read(isDarkModeProvider);
    final language = ref.read(currentLanguageProvider);
    final detailAsync = ref.read(_siteDetailProvider(site['id'] as int).future);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return FutureBuilder<Map<String, dynamic>?>(
          future: detailAsync,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator()),
              );
            }

            final detail = snapshot.data?['site'] ?? site;
            final recentReports =
                (snapshot.data?['recent_reports'] as List?) ?? [];

            return DraggableScrollableSheet(
              initialChildSize: 0.7,
              minChildSize: 0.4,
              maxChildSize: 0.9,
              expand: false,
              builder: (ctx, scrollController) {
                return Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        children: [
                          Container(
                            width: 42,
                            height: 4,
                            decoration: BoxDecoration(
                              color: Colors.grey[400],
                              borderRadius: BorderRadius.circular(2),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  detail['name'] ??
                                      _sitesTr(
                                        language,
                                        en: 'Site Details',
                                        sw: 'Maelezo ya Tovuti',
                                        fr: 'Details du site',
                                        ar: 'تفاصيل الموقع',
                                      ),
                                  style: TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                    color: isDark
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
                        controller: scrollController,
                        padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                        children: [
                          _DetailRow(
                            label: _sitesTr(
                              language,
                              en: 'Location',
                              sw: 'Mahali',
                              fr: 'Emplacement',
                              ar: 'الموقع',
                            ),
                            value: detail['location'] ?? '-',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: _sitesTr(
                              language,
                              en: 'Status',
                              sw: 'Hali',
                              fr: 'Statut',
                              ar: 'الحالة',
                            ),
                            value: detail['status'] ?? '-',
                            isDark: isDark,
                            valueColor: _getStatusColor(detail['status']),
                          ),
                          _DetailRow(
                            label: _sitesTr(
                              language,
                              en: 'Supervisor',
                              sw: 'Mratibu',
                              fr: 'Superviseur',
                              ar: 'المشرف',
                            ),
                            value:
                                (detail['current_supervisor']
                                    as Map<String, dynamic>?)?['name'] ??
                                '-',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: _sitesTr(
                              language,
                              en: 'Progress',
                              sw: 'Maendeleo',
                              fr: 'Progression',
                              ar: 'التقدم',
                            ),
                            value:
                                '${_toPercent(detail['progress_percentage'], decimals: 1)}%',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: _sitesTr(
                              language,
                              en: 'Start Date',
                              sw: 'Tarehe ya Kuanza',
                              fr: 'Date de début',
                              ar: 'تاريخ البدء',
                            ),
                            value: detail['start_date'] ?? '-',
                            isDark: isDark,
                          ),
                          _DetailRow(
                            label: _sitesTr(
                              language,
                              en: 'Expected End',
                              sw: 'Tarehe ya Kukamilika',
                              fr: 'Fin prévue',
                              ar: 'تاريخ الانتهاء المتوقع',
                            ),
                            value: detail['expected_end_date'] ?? '-',
                            isDark: isDark,
                          ),
                          if (detail['actual_end_date'] != null)
                            _DetailRow(
                              label: _sitesTr(
                                language,
                                en: 'Actual End',
                                sw: 'Tarehe ya Kumalizia',
                                fr: 'Fin réelle',
                                ar: 'تاريخ الانتهاء الفعلي',
                              ),
                              value: detail['actual_end_date'] ?? '-',
                              isDark: isDark,
                            ),
                          if (detail['description'] != null &&
                              (detail['description'] as String).isNotEmpty) ...[
                            const SizedBox(height: 16),
                            Text(
                              _sitesTr(
                                language,
                                en: 'Description',
                                sw: 'Maelezo',
                                fr: 'Description',
                                ar: 'الوصف',
                              ),
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: isDark
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: isDark
                                    ? const Color(0xFF252540)
                                    : Colors.grey.withValues(alpha: 0.05),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                detail['description'] ?? '',
                                style: TextStyle(
                                  fontSize: 14,
                                  color: isDark
                                      ? Colors.white70
                                      : AppColors.textSecondary,
                                ),
                              ),
                            ),
                          ],
                          if (recentReports.isNotEmpty) ...[
                            const SizedBox(height: 20),
                            Text(
                              _sitesTr(
                                language,
                                en: 'Recent Reports',
                                sw: 'Ripoti za Hivi Karibuni',
                                fr: 'Rapports récents',
                                ar: 'التقارير الأخيرة',
                              ),
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                                color: isDark
                                    ? Colors.white
                                    : AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 8),
                            ...recentReports.map(
                              (report) => ListTile(
                                dense: true,
                                contentPadding: EdgeInsets.zero,
                                leading: Icon(
                                  Icons.description_rounded,
                                  color: _getStatusColor(report['status']),
                                  size: 20,
                                ),
                                title: Text(
                                  report['report_date'] ?? '-',
                                  style: TextStyle(
                                    fontSize: 13,
                                    color: isDark
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                ),
                                subtitle: Text(
                                  report['prepared_by'] ?? '-',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: isDark
                                        ? Colors.white54
                                        : AppColors.textHint,
                                  ),
                                ),
                                trailing: Text(
                                  '${_toPercent(report['progress_percentage'], decimals: 0)}%',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: isDark
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                );
              },
            );
          },
        );
      },
    );
  }

  void _showSiteForm(BuildContext context, {Map<String, dynamic>? site}) {
    final isDark = ref.read(isDarkModeProvider);
    final language = ref.read(currentLanguageProvider);
    final isEdit = site != null;
    final nameCtrl = TextEditingController(text: site?['name'] ?? '');
    final locationCtrl = TextEditingController(text: site?['location'] ?? '');
    final descCtrl = TextEditingController(text: site?['description'] ?? '');
    String status = site?['status'] ?? 'ACTIVE';
    final startDateCtrl = TextEditingController(
      text: site?['start_date'] ?? '',
    );
    final expectedEndDateCtrl = TextEditingController(
      text: site?['expected_end_date'] ?? '',
    );
    final actualEndDateCtrl = TextEditingController(
      text: site?['actual_end_date'] ?? '',
    );
    final formKey = GlobalKey<FormState>();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setState) {
            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.of(ctx).viewInsets.bottom + 20,
              ),
              child: Form(
                key: formKey,
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: Colors.grey[400],
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        isEdit
                            ? _sitesTr(
                                language,
                                en: 'Edit Site',
                                sw: 'Hariri Eneo',
                                fr: 'Modifier le site',
                                ar: 'تعديل الموقع',
                              )
                            : _sitesTr(
                                language,
                                en: 'Add Site',
                                sw: 'Ongeza Eneo',
                                fr: 'Ajouter un site',
                                ar: 'إضافة موقع',
                              ),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 24),
                      _FormField(
                        controller: nameCtrl,
                        label: _sitesTr(
                          language,
                          en: 'Name *',
                          sw: 'Jina *',
                          fr: 'Nom *',
                          ar: 'الاسم *',
                        ),
                        isDark: isDark,
                        validator: (v) =>
                            (v == null || v.isEmpty)
                                ? _sitesTr(
                                    language,
                                    en: 'Required',
                                    sw: 'Hitaji',
                                    fr: 'Obligatoire',
                                    ar: 'مطلوب',
                                  )
                                : null,
                      ),
                      _FormField(
                        controller: locationCtrl,
                        label: _sitesTr(
                          language,
                          en: 'Location *',
                          sw: 'Mahali *',
                          fr: 'Emplacement *',
                          ar: 'الموقع *',
                        ),
                        isDark: isDark,
                        validator: (v) =>
                            (v == null || v.isEmpty)
                                ? _sitesTr(
                                    language,
                                    en: 'Required',
                                    sw: 'Hitaji',
                                    fr: 'Obligatoire',
                                    ar: 'مطلوب',
                                  )
                                : null,
                      ),
                      _FormField(
                        controller: descCtrl,
                        label: _sitesTr(
                          language,
                          en: 'Description',
                          sw: 'Maelezo',
                          fr: 'Description',
                          ar: 'الوصف',
                        ),
                        isDark: isDark,
                        maxLines: 3,
                      ),
                      _StatusDropdown(
                        value: status,
                        isDark: isDark,
                        language: language,
                        onChanged: (v) =>
                            setState(() => status = v ?? 'ACTIVE'),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: _DateField(
                              controller: startDateCtrl,
                              label: _sitesTr(
                                language,
                                en: 'Start Date',
                                sw: 'Tarehe ya Kuanza',
                                fr: 'Date de début',
                                ar: 'تاريخ البدء',
                              ),
                              isDark: isDark,
                              ctx: ctx,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _DateField(
                              controller: expectedEndDateCtrl,
                              label: _sitesTr(
                                language,
                                en: 'Expected End',
                                sw: 'Tarehe ya Kukamilika',
                                fr: 'Fin prévue',
                                ar: 'تاريخ الانتهاء المتوقع',
                              ),
                              isDark: isDark,
                              ctx: ctx,
                            ),
                          ),
                        ],
                      ),
                      if (isEdit) ...[
                        const SizedBox(height: 8),
                        _DateField(
                          controller: actualEndDateCtrl,
                          label: _sitesTr(
                            language,
                            en: 'Actual End Date',
                            sw: 'Tarehe ya Kumalizia',
                            fr: 'Date de fin réelle',
                            ar: 'تاريخ الانتهاء الفعلي',
                          ),
                          isDark: isDark,
                          ctx: ctx,
                        ),
                      ],
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: () async {
                            if (!formKey.currentState!.validate()) return;
                            final api = ref.read(apiClientProvider);
                            final data = {
                              'name': nameCtrl.text,
                              'location': locationCtrl.text,
                              'description': descCtrl.text,
                              'status': status,
                              if (startDateCtrl.text.isNotEmpty)
                                'start_date': startDateCtrl.text,
                              if (expectedEndDateCtrl.text.isNotEmpty)
                                'expected_end_date': expectedEndDateCtrl.text,
                              if (actualEndDateCtrl.text.isNotEmpty)
                                'actual_end_date': actualEndDateCtrl.text,
                            };
                            try {
                              final siteId = site?['id'];
                              if (isEdit && siteId != null && siteId != 0) {
                                await api.put('/sites/$siteId', data: data);
                              } else {
                                await api.post('/sites', data: data);
                              }
                              ref.invalidate(_sitesProvider);
                              if (ctx.mounted) Navigator.pop(ctx);
                            } catch (e) {
                              if (ctx.mounted) {
                                ScaffoldMessenger.of(ctx).showSnackBar(
                                  SnackBar(
                                    content: Text(
                                      _siteErrorMessage(e, language),
                                    ),
                                    backgroundColor: Colors.red,
                                  ),
                                );
                              }
                            }
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: Text(
                            isEdit
                                ? _sitesTr(
                                    language,
                                    en: 'Update',
                                    sw: 'Sasisha',
                                    fr: 'Mettre à jour',
                                    ar: 'تحديث',
                                  )
                                : _sitesTr(
                                    language,
                                    en: 'Save',
                                    sw: 'Hifadhi',
                                    fr: 'Enregistrer',
                                    ar: 'حفظ',
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
            );
          },
        );
      },
    );
  }

  Future<void> _deleteSite(
    BuildContext context,
    Map<String, dynamic> site,
  ) async {
    final isDark = ref.read(isDarkModeProvider);
    final language = ref.read(currentLanguageProvider);

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          _sitesTr(
            language,
            en: 'Confirm',
            sw: 'Thibitisha',
            fr: 'Confirmer',
            ar: 'تأكيد',
          ),
          style: TextStyle(
            color: isDark ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          _sitesTr(
            language,
            en: 'Are you sure you want to delete this site?',
            sw: 'Una uhakika unataka kufuta eneo hili?',
            fr: 'Voulez-vous vraiment supprimer ce site ?',
            ar: 'هل أنت متأكد أنك تريد حذف هذا الموقع؟',
          ),
          style: TextStyle(
            color: isDark ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              _sitesTr(
                language,
                en: 'Cancel',
                sw: 'Hapana',
                fr: 'Annuler',
                ar: 'إلغاء',
              ),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: Text(
              _sitesTr(
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
      final api = ref.read(apiClientProvider);
      final response = await api.delete('/sites/${site['id']}');

      // Debug: log raw response
      debugPrint('DELETE RESPONSE: ${response.data}');
      debugPrint('DELETE STATUS: ${response.statusCode}');

      Map<String, dynamic>? data;
      if (response.data is Map<String, dynamic>) {
        data = response.data;
      } else if (response.data is Map) {
        data = Map<String, dynamic>.from(response.data);
      }

      final success = data?['success'] == true;
      final message = data?['message']?.toString() ?? '';

      debugPrint('DELETE success: $success, message: $message');

      if (success) {
        ref.invalidate(_sitesProvider);
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                _sitesTr(
                  language,
                  en: 'Site deleted',
                  sw: 'Eneo limefutwa',
                  fr: 'Site supprimé',
                  ar: 'تم حذف الموقع',
                ),
              ),
              backgroundColor: AppColors.success,
            ),
          );
        }
      } else {
        if (context.mounted) {
          String errorMsg;
          if (message.contains('reports')) {
            errorMsg = _sitesTr(
              language,
              en: 'Cannot delete site with reports. Delete reports first.',
              sw: 'Hauwezi kufuta eneo lenye ripoti. Futa ripoti kwanza.',
              fr: 'Impossible de supprimer un site avec des rapports. Supprimez d’abord les rapports.',
              ar: 'لا يمكن حذف موقع يحتوي على تقارير. احذف التقارير أولاً.',
            );
          } else {
            errorMsg = message.isNotEmpty
                ? message
                : _sitesTr(
                    language,
                    en: 'Error',
                    sw: 'Hitilafu',
                    fr: 'Erreur',
                    ar: 'خطأ',
                  );
          }
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(errorMsg), backgroundColor: Colors.red),
          );
        }
      }
    } on DioException catch (e) {
      if (context.mounted) {
        String errorMsg;
        if (e.response?.statusCode == 422) {
          final errData = e.response?.data as Map<String, dynamic>?;
          final msg = errData?['message']?.toString() ?? '';
          if (msg.contains('reports')) {
            errorMsg = _sitesTr(
              language,
              en: 'Cannot delete site with reports. Delete reports first.',
              sw: 'Hauwezi kufuta eneo lenye ripoti. Futa ripoti kwanza.',
              fr: 'Impossible de supprimer un site avec des rapports. Supprimez d’abord les rapports.',
              ar: 'لا يمكن حذف موقع يحتوي على تقارير. احذف التقارير أولاً.',
            );
          } else {
            errorMsg = msg.isNotEmpty
                ? msg
                : _sitesTr(
                    language,
                    en: 'Cannot delete',
                    sw: 'Hauwezi kufuta',
                    fr: 'Impossible de supprimer',
                    ar: 'لا يمكن الحذف',
                  );
          }
        } else {
          errorMsg = _siteErrorMessage(e, language);
        }
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(errorMsg), backgroundColor: Colors.red),
        );
      }
    }
  }

  Color _getStatusColor(String? status) {
    switch (status) {
      case 'ACTIVE':
        return const Color(0xFF27AE60);
      case 'INACTIVE':
        return const Color(0xFFF59E0B);
      case 'COMPLETED':
        return const Color(0xFF3B82F6);
      default:
        return Colors.grey;
    }
  }

  String _toPercent(dynamic value, {int decimals = 0}) {
    if (value == null) return '0';
    final n = (value is num)
        ? value.toDouble()
        : double.tryParse(value.toString()) ?? 0;
    return n.toStringAsFixed(decimals);
  }
}

class _SiteFilters extends ConsumerWidget {
  final _SitesFilter filter;
  final AppLanguage language;
  final bool isDarkMode;

  const _SiteFilters({
    required this.filter,
    required this.language,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ExpansionTile(
      title: Text(
        _sitesTr(
          language,
          en: 'Filters',
          sw: 'Vichungi',
          fr: 'Filtres',
          ar: 'عوامل التصفية',
        ),
      ),
      initiallyExpanded:
          filter.status != null ||
          filter.startDate != null ||
          filter.endDate != null,
      childrenPadding: const EdgeInsets.fromLTRB(0, 0, 0, 8),
      backgroundColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      collapsedBackgroundColor: isDarkMode
          ? const Color(0xFF2A2A3E)
          : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      collapsedShape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
      ),
      children: [
        _StatusFilterChips(
          language: language,
          isDarkMode: isDarkMode,
          selectedStatus: filter.status,
          onChanged: (value) => ref.read(_sitesFilterProvider.notifier).state =
              filter.copyWith(status: value, clearStatus: value == null),
        ),
        Row(
          children: [
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.startDate ?? DateTime.now(),
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now(),
                  );
                  if (picked != null)
                    ref.read(_sitesFilterProvider.notifier).state = filter
                        .copyWith(startDate: picked);
                },
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 20),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _sitesTr(
                              language,
                              en: 'Start Date',
                              sw: 'Tarehe ya Kuanza',
                              fr: 'Date de début',
                              ar: 'تاريخ البدء',
                            ),
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                            ),
                          ),
                          Text(
                            filter.startDate != null
                                ? DateFormat(
                                    'dd MMM yyyy',
                                  ).format(filter.startDate!)
                                : '-',
                            style: const TextStyle(fontSize: 14),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.endDate ?? DateTime.now(),
                    firstDate: filter.startDate ?? DateTime(2020),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null)
                    ref.read(_sitesFilterProvider.notifier).state = filter
                        .copyWith(endDate: picked);
                },
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 20),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _sitesTr(
                              language,
                              en: 'End Date',
                              sw: 'Tarehe ya Mwisho',
                              fr: 'Date de fin',
                              ar: 'تاريخ الانتهاء',
                            ),
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                            ),
                          ),
                          Text(
                            filter.endDate != null
                                ? DateFormat(
                                    'dd MMM yyyy',
                                  ).format(filter.endDate!)
                                : '-',
                            style: const TextStyle(fontSize: 14),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
        if (filter.status != null ||
            filter.startDate != null ||
            filter.endDate != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: OutlinedButton(
              onPressed: () => ref.read(_sitesFilterProvider.notifier).state =
                  _SitesFilter(),
              child: Text(
                _sitesTr(
                  language,
                  en: 'Clear',
                  sw: 'Futa',
                  fr: 'Effacer',
                  ar: 'مسح',
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _StatusFilterChips extends StatelessWidget {
  final AppLanguage language;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _StatusFilterChips({
    required this.language,
    required this.isDarkMode,
    required this.selectedStatus,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final options = <String?, String>{
      null: _sitesTr(
        language,
        en: 'All',
        sw: 'Zote',
        fr: 'Tous',
        ar: 'الكل',
      ),
      'ACTIVE': _sitesTr(
        language,
        en: 'Active',
        sw: 'Hai',
        fr: 'Actif',
        ar: 'نشط',
      ),
      'INACTIVE': _sitesTr(
        language,
        en: 'Inactive',
        sw: 'Isiyo Endelea',
        fr: 'Inactif',
        ar: 'غير نشط',
      ),
      'COMPLETED': _sitesTr(
        language,
        en: 'Completed',
        sw: 'Imekamilika',
        fr: 'Terminé',
        ar: 'مكتمل',
      ),
    };

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: options.entries.map((entry) {
          final selected = selectedStatus == entry.key;
          return Padding(
            padding: const EdgeInsets.only(right: 8, bottom: 12),
            child: ChoiceChip(
              selected: selected,
              label: Text(entry.value),
              onSelected: (_) => onChanged(entry.key),
              selectedColor: AppColors.primary.withValues(alpha: 0.15),
              labelStyle: TextStyle(
                color: selected
                    ? AppColors.primary
                    : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
                fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
              ),
              side: BorderSide(
                color: selected
                    ? AppColors.primary
                    : (isDarkMode
                          ? Colors.white12
                          : AppColors.textHint.withValues(alpha: 0.4)),
              ),
              backgroundColor: isDarkMode
                  ? const Color(0xFF1A2332)
                  : Colors.white,
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _SiteCard extends StatelessWidget {
  final Map<String, dynamic> site;
  final bool isDark;
  final AppLanguage language;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _SiteCard({
    required this.site,
    required this.isDark,
    required this.language,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onView,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          site['name'] ?? '-',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: isDark
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          site['location'] ?? '-',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDark
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  Column(
                    children: [
                      GestureDetector(
                        onTap: onEdit,
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.primary,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.edit, size: 14, color: Colors.white),
                              Text(
                                " ${_sitesTr(language, en: 'Edit', sw: 'Hariri', fr: 'Modifier', ar: 'تعديل')}",
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 6),
                      GestureDetector(
                        onTap: onDelete,
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.red,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.delete, size: 14, color: Colors.white),
                              Text(
                                " ${_sitesTr(language, en: 'Delete', sw: 'Futa', fr: 'Supprimer', ar: 'حذف')}",
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: _getStatusColor(
                    site['status'],
                  ).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  site['status'] ?? '-',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: _getStatusColor(site['status']),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        Icon(
                          Icons.person_rounded,
                          size: 14,
                          color: isDark ? Colors.white38 : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            (site['current_supervisor']
                                    as Map<String, dynamic>?)?['name'] ??
                                '-',
                            style: TextStyle(
                              fontSize: 12,
                              color: isDark
                                  ? Colors.white54
                                  : AppColors.textSecondary,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: isDark
                          ? Colors.white.withValues(alpha: 0.05)
                          : Colors.grey.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.trending_up_rounded,
                          size: 12,
                          color: Color(0xFF27AE60),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          '${_toPercent(site['progress_percentage'], decimals: 0)}%',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: isDark
                                ? Colors.white70
                                : AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(
                    Icons.calendar_today_rounded,
                    size: 12,
                    color: isDark ? Colors.white38 : AppColors.textHint,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    '${_sitesTr(language, en: 'Start', sw: 'Kuanza', fr: 'Début', ar: 'البداية')}: ${site['start_date'] ?? '-'}',
                    style: TextStyle(
                      fontSize: 11,
                      color: isDark ? Colors.white38 : AppColors.textSecondary,
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

  Color _getStatusColor(String? status) {
    switch (status) {
      case 'ACTIVE':
        return const Color(0xFF27AE60);
      case 'INACTIVE':
        return const Color(0xFFF59E0B);
      case 'COMPLETED':
        return const Color(0xFF3B82F6);
      default:
        return Colors.grey;
    }
  }

  String _toPercent(dynamic value, {int decimals = 0}) {
    if (value == null) return '0';
    final n = (value is num)
        ? value.toDouble()
        : double.tryParse(value.toString()) ?? 0;
    return n.toStringAsFixed(decimals);
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;
  final Color? valueColor;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDark,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDark ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color:
                    valueColor ??
                    (isDark ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FormField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final bool isDark;
  final int maxLines;
  final String? Function(String?)? validator;

  const _FormField({
    required this.controller,
    required this.label,
    required this.isDark,
    this.maxLines = 1,
    this.validator,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: TextFormField(
        controller: controller,
        maxLines: maxLines,
        validator: validator,
        style: TextStyle(
          fontSize: 14,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: TextStyle(
            fontSize: 12,
            color: isDark ? Colors.white54 : AppColors.textHint,
          ),
          filled: true,
          fillColor: isDark
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
        ),
      ),
    );
  }
}

class _StatusDropdown extends StatelessWidget {
  final String value;
  final bool isDark;
  final AppLanguage language;
  final ValueChanged<String?> onChanged;

  const _StatusDropdown({
    required this.value,
    required this.isDark,
    required this.language,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: DropdownButtonFormField<String>(
        value: value,
        items: [
          DropdownMenuItem(
            value: 'ACTIVE',
            child: Text(
              _sitesTr(
                language,
                en: 'Active',
                sw: 'Wastani',
                fr: 'Actif',
                ar: 'نشط',
              ),
            ),
          ),
          DropdownMenuItem(
            value: 'INACTIVE',
            child: Text(
              _sitesTr(
                language,
                en: 'Inactive',
                sw: 'Isiyo Endelevu',
                fr: 'Inactif',
                ar: 'غير نشط',
              ),
            ),
          ),
          DropdownMenuItem(
            value: 'COMPLETED',
            child: Text(
              _sitesTr(
                language,
                en: 'Completed',
                sw: 'Imalizika',
                fr: 'Termine',
                ar: 'مكتمل',
              ),
            ),
          ),
        ],
        onChanged: onChanged,
        dropdownColor: isDark ? const Color(0xFF1A1A2E) : Colors.white,
        decoration: InputDecoration(
          labelText: _sitesTr(
            language,
            en: 'Status',
            sw: 'Hali',
            fr: 'Statut',
            ar: 'الحالة',
          ),
          labelStyle: TextStyle(
            fontSize: 12,
            color: isDark ? Colors.white54 : AppColors.textHint,
          ),
          filled: true,
          fillColor: isDark
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
        ),
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final bool isDark;
  final BuildContext ctx;

  const _DateField({
    required this.controller,
    required this.label,
    required this.isDark,
    required this.ctx,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: TextFormField(
        controller: controller,
        readOnly: true,
        onTap: () async {
          final date = await showDatePicker(
            context: ctx,
            initialDate: DateTime.now(),
            firstDate: DateTime(2020),
            lastDate: DateTime(2030),
          );
          if (date != null) {
            controller.text =
                '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
          }
        },
        style: TextStyle(
          fontSize: 14,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: TextStyle(
            fontSize: 12,
            color: isDark ? Colors.white54 : AppColors.textHint,
          ),
          filled: true,
          fillColor: isDark
              ? const Color(0xFF0F1923)
              : Colors.grey.withValues(alpha: 0.05),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(
              color: isDark
                  ? const Color(0xFF243447)
                  : Colors.grey.withValues(alpha: 0.2),
            ),
          ),
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
          suffixIcon: Icon(
            Icons.calendar_today_rounded,
            size: 18,
            color: isDark ? Colors.white38 : AppColors.textHint,
          ),
        ),
      ),
    );
  }
}

class _SitesErrorView extends StatelessWidget {
  final Object error;
  final AppLanguage language;
  final VoidCallback onRetry;

  const _SitesErrorView({
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
        SizedBox(height: MediaQuery.of(context).size.height * 0.2),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          _sitesTr(
            language,
            en: 'Something went wrong',
            sw: 'Hitilafu imetokea',
            fr: 'Une erreur est survenue',
            ar: 'حدث خطأ ما',
          ),
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(
              _sitesTr(
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
