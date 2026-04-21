import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _leadsSearchProvider = StateProvider.autoDispose<String>((ref) => '');

class _LeadFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final int? statusId;
  final int? sourceId;

  _LeadFilter({this.startDate, this.endDate, this.statusId, this.sourceId});

  _LeadFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    int? statusId,
    int? sourceId,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearStatus = false,
    bool clearSource = false,
  }) {
    return _LeadFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      statusId: clearStatus ? null : (statusId ?? this.statusId),
      sourceId: clearSource ? null : (sourceId ?? this.sourceId),
    );
  }

  Map<String, String> toQueryParams() {
    final params = <String, String>{};
    if (startDate != null)
      params['start_date'] = DateFormat('yyyy-MM-dd').format(startDate!);
    if (endDate != null)
      params['end_date'] = DateFormat('yyyy-MM-dd').format(endDate!);
    if (statusId != null) params['lead_status_id'] = statusId.toString();
    if (sourceId != null) params['lead_source_id'] = sourceId.toString();
    return params;
  }
}

final _leadsFilterProvider = StateProvider.autoDispose<_LeadFilter>(
  (ref) => _LeadFilter(),
);

final _leadsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final filter = ref.watch(_leadsFilterProvider);
  try {
    late final dynamic response;
    int retryCount = 0;
    const maxRetries = 2;

    while (true) {
      try {
        response = await api.get(
          '/leads',
          queryParameters: {...filter.toQueryParams(), 'per_page': '50'},
        );
        break;
      } on DioException catch (error) {
        final shouldRetry =
            error.response?.statusCode == null &&
            (error.type == DioExceptionType.connectionError ||
                error.type == DioExceptionType.connectionTimeout ||
                error.type == DioExceptionType.receiveTimeout ||
                error.type == DioExceptionType.unknown);

        if (!shouldRetry || retryCount >= maxRetries) rethrow;

        retryCount++;
        final delay = Duration(milliseconds: 250 * (1 << retryCount));
        await Future.delayed(delay);
      }
    }
    final data = response.data is Map<String, dynamic>
        ? response.data as Map<String, dynamic>
        : const <String, dynamic>{};

    if (data['success'] == false) {
      throw Exception(data['message'] ?? 'Unknown error');
    }

    return {
      'items': (data['data'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList(),
      'meta': data['meta'] is Map
          ? Map<String, dynamic>.from(data['meta'] as Map)
          : const <String, dynamic>{},
      'unavailable_on_live': false,
    };
  } on DioException catch (error) {
    final statusCode = error.response?.statusCode ?? 0;
    if (statusCode == 404 || statusCode >= 500) {
      return {
        'items': const <Map<String, dynamic>>[],
        'meta': const <String, dynamic>{},
        'unavailable_on_live': true,
      };
    }
    rethrow;
  }
});

final _leadRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/leads/reference-data');
    final data = response.data is Map<String, dynamic>
        ? response.data as Map<String, dynamic>
        : const <String, dynamic>{};

    final refs = data['data'] is Map
        ? Map<String, dynamic>.from(data['data'] as Map)
        : const <String, dynamic>{};

    return {...refs, 'unavailable_on_live': false};
  } on DioException catch (error) {
    final statusCode = error.response?.statusCode ?? 0;
    if (statusCode == 404 || statusCode >= 500) {
      return const {
        'lead_sources': <Map<String, dynamic>>[],
        'lead_statuses': <Map<String, dynamic>>[],
        'service_interesteds': <Map<String, dynamic>>[],
        'salespeople': <Map<String, dynamic>>[],
        'clients': <Map<String, dynamic>>[],
        'unavailable_on_live': true,
      };
    }
    rethrow;
  }
});

String _leadTr(
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

String _leadMessage(Object error, AppLanguage language) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) {
          final message = first.first?.toString();
          if (message != null && message.trim().isNotEmpty) {
            return message;
          }
        }
        if (first is String && first.trim().isNotEmpty) {
          return first;
        }
      }
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }
    }
  }

  return _leadTr(
    language,
    en: 'Something went wrong',
    sw: 'Hitilafu imetokea',
    fr: 'Une erreur est survenue',
    ar: 'حدث خطأ ما',
  );
}

class LeadsScreen extends ConsumerStatefulWidget {
  const LeadsScreen({super.key});

  @override
  ConsumerState<LeadsScreen> createState() => _LeadsScreenState();
}

class _LeadsScreenState extends ConsumerState<LeadsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final leadsAsync = ref.watch(_leadsProvider);
    final refsAsync = ref.watch(_leadRefsProvider);
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(_leadsFilterProvider);
    final search = ref.watch(_leadsSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _leadTr(
            language,
            en: 'Leads',
            sw: 'Lead',
            fr: 'Prospects',
            ar: 'العملاء المحتملون',
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_leadsProvider),
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
                          ref.read(_leadsSearchProvider.notifier).state = value,
                      decoration: InputDecoration(
                        hintText: _leadTr(
                          language,
                          en: 'Search leads...',
                          sw: 'Tafuta lead...',
                          fr: 'Rechercher des prospects...',
                          ar: 'ابحث عن العملاء المحتملين...',
                        ),
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(_leadsSearchProvider.notifier)
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
                    const SizedBox(height: 12),
                    refsAsync.when(
                      loading: () => const SizedBox.shrink(),
                      error: (_, __) => const SizedBox.shrink(),
                      data: (refs) {
                        final refsUnavailable =
                            refs['unavailable_on_live'] == true;

                        return Column(
                          children: [
                            if (refsUnavailable)
                              Container(
                                width: double.infinity,
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: Colors.orange.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  _leadTr(
                                    language,
                                    en:
                                        'Lead helper endpoints are missing on the live API right now. You can still view existing leads, but reference-data filters and the add form are disabled.',
                                    sw:
                                        'Lead helpers hazipo kwenye live API kwa sasa. Unaweza kutazama lead zilizopo, lakini filters za reference data na form ya kuongeza zimezimwa.',
                                    fr:
                                        'Les points de terminaison d’assistance des prospects manquent actuellement sur l’API live. Vous pouvez toujours consulter les prospects existants, mais les filtres de donnees de reference et le formulaire d’ajout sont desactives.',
                                    ar:
                                        'نقاط نهاية المساعدة الخاصة بالعملاء المحتملين غير متوفرة حاليا في واجهة البرمجة الحية. لا يزال بإمكانك عرض العملاء الحاليين، لكن عوامل التصفية وواجهة الإضافة معطلة.',
                                  ),
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: isDarkMode
                                        ? Colors.white70
                                        : AppColors.textSecondary,
                                  ),
                                ),
                              ),
                            if (refsUnavailable) const SizedBox(height: 12),
                            if (!refsUnavailable)
                              _LeadFilters(
                                refs: refs,
                                filter: filter,
                                language: language,
                                isDarkMode: isDarkMode,
                              ),
                          ],
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
            leadsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _ErrorView(
                  message: _leadMessage(error, language),
                  language: language,
                  onRetry: () => ref.invalidate(_leadsProvider),
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
                              Icons.person_search_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _leadTr(
                                language,
                                en:
                                    'Lead Management is not available on the live API right now.',
                                sw:
                                    'Lead Management haipatikani kwenye live API kwa sasa.',
                                fr:
                                    'La gestion des prospects n’est pas disponible sur l’API live pour le moment.',
                                ar:
                                    'إدارة العملاء المحتملين غير متاحة حاليا على واجهة البرمجة الحية.',
                              ),
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[700],
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _leadTr(
                                language,
                                en:
                                    'The /api/v1/leads route is missing on live, so the native screen cannot load data yet.',
                                sw:
                                    'Njia ya /api/v1/leads haipo live, kwa hiyo screen ya native haiwezi kupakia data bado.',
                                fr:
                                    'La route /api/v1/leads est absente en production, donc l’ecran natif ne peut pas encore charger les donnees.',
                                ar:
                                    'مسار /api/v1/leads غير موجود على البيئة الحية، لذلك لا يمكن للشاشة الأصلية تحميل البيانات بعد.',
                              ),
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 13,
                                color: Colors.grey[600],
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
                final total = payload['meta'] is Map<String, dynamic>
                    ? payload['meta']['total'] ?? allItems.length
                    : allItems.length;

                final leads = search.isEmpty
                    ? allItems
                    : allItems.where((lead) {
                        final haystack = [
                          lead['name'] ?? '',
                          lead['lead_number'] ?? '',
                          lead['phone'] ?? '',
                          lead['email'] ?? '',
                          lead['city'] ?? '',
                          lead['lead_status_name'] ?? '',
                          lead['lead_source_name'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (leads.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.person_search_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? _leadTr(
                                    language,
                                    en: 'No leads found',
                                    sw: 'Hakuna lead zilizopatikana',
                                    fr: 'Aucun prospect trouve',
                                    ar: 'لم يتم العثور على عملاء محتملين',
                                  )
                                : _leadTr(
                                    language,
                                    en: 'No leads match your search',
                                    sw: 'Hakuna matokeo yanayolingana',
                                    fr:
                                        'Aucun prospect ne correspond a votre recherche',
                                    ar:
                                        'لا توجد نتائج مطابقة لبحثك',
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
                                          .read(_leadsSearchProvider.notifier)
                                          .state =
                                      '',
                              icon: const Icon(Icons.arrow_back_rounded),
                              label: Text(
                                _leadTr(
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
                    delegate: SliverChildListDelegate([
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Text(
                          _leadTr(
                            language,
                            en: 'Total leads: $total',
                            sw: 'Jumla ya lead: $total',
                            fr: 'Total des prospects : $total',
                            ar: 'إجمالي العملاء المحتملين: $total',
                          ),
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white70
                                : AppColors.textSecondary,
                          ),
                        ),
                      ),
                      ...leads.map(
                        (lead) => _LeadCard(
                          lead: lead,
                          language: language,
                          onView: () => _showDetails(context, lead),
                          onEdit: () => _openForm(context, lead: lead),
                          onDelete: () => _deleteLead(context, lead),
                        ),
                      ),
                      const SizedBox(height: 80),
                    ]),
                  ),
                );
              },
            ),
          ],
        ),
      ),
      floatingActionButton: refsAsync.maybeWhen(
        data: (refs) {
          if (refs['unavailable_on_live'] == true) {
            return null;
          }
          return Padding(
            padding: const EdgeInsets.only(bottom: 80),
            child: FloatingActionButton(
              onPressed: () => _openForm(context),
              child: const Icon(Icons.add_rounded),
              tooltip: _leadTr(
                language,
                en: 'Add',
                sw: 'Ongeza',
                fr: 'Ajouter',
                ar: 'إضافة',
              ),
            ),
          );
        },
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context),
            child: const Icon(Icons.add_rounded),
            tooltip: _leadTr(
              language,
              en: 'Add',
              sw: 'Ongeza',
              fr: 'Ajouter',
              ar: 'إضافة',
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context, {
    Map<String, dynamic>? lead,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _LeadFormSheet(lead: lead),
    );

    if (result == true) {
      ref.invalidate(_leadsProvider);
    }
  }

  Future<void> _deleteLead(
    BuildContext context,
    Map<String, dynamic> lead,
  ) async {
    final language = ref.read(currentLanguageProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          _leadTr(
            language,
            en: 'Delete Lead',
            sw: 'Futa Lead',
            fr: 'Supprimer le prospect',
            ar: 'حذف العميل المحتمل',
          ),
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          _leadTr(
            language,
            en: 'Delete ${lead['name']}?',
            sw: 'Je, unataka kufuta ${lead['name']}?',
            fr: 'Supprimer ${lead['name']} ?',
            ar: 'هل تريد حذف ${lead['name']}؟',
          ),
          style: TextStyle(
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(
              _leadTr(
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
              _leadTr(
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
      await ref.read(apiClientProvider).delete('/leads/${lead['id']}');

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _leadTr(
                language,
                en: 'Lead deleted',
                sw: 'Lead imefutwa',
                fr: 'Prospect supprime',
                ar: 'تم حذف العميل المحتمل',
              ),
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }

      ref.invalidate(_leadsProvider);
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_leadMessage(error, language)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(BuildContext context, Map<String, dynamic> lead) {
    final language = ref.read(currentLanguageProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.88,
        ),
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            children: [
              _LeadSheetHeader(
                title: lead['name'] as String? ?? '-',
                onBack: () => Navigator.pop(context),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _info('Lead No', lead['lead_number']),
                      _info(
                        _leadTr(
                          language,
                          en: 'Status',
                          sw: 'Hali',
                          fr: 'Statut',
                          ar: 'الحالة',
                        ),
                        lead['lead_status_name'] ?? lead['status'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Source',
                          sw: 'Chanzo',
                          fr: 'Source',
                          ar: 'المصدر',
                        ),
                        lead['lead_source_name'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Service',
                          sw: 'Huduma',
                          fr: 'Service',
                          ar: 'الخدمة',
                        ),
                        lead['service_interested_name'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Salesperson',
                          sw: 'Muuza',
                          fr: 'Commercial',
                          ar: 'مندوب المبيعات',
                        ),
                        lead['salesperson_name'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Client',
                          sw: 'Mteja',
                          fr: 'Client',
                          ar: 'العميل',
                        ),
                        lead['client_name'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Phone',
                          sw: 'Simu',
                          fr: 'Telephone',
                          ar: 'الهاتف',
                        ),
                        lead['phone'],
                      ),
                      _info('Email', lead['email']),
                      _info(
                        _leadTr(
                          language,
                          en: 'Address',
                          sw: 'Anwani',
                          fr: 'Adresse',
                          ar: 'العنوان',
                        ),
                        lead['address'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'City',
                          sw: 'Mji',
                          fr: 'Ville',
                          ar: 'المدينة',
                        ),
                        lead['city'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Site Location',
                          sw: 'Eneo',
                          fr: 'Emplacement du site',
                          ar: 'موقع المشروع',
                        ),
                        lead['site_location'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Estimated Value',
                          sw: 'Thamani ya Makadirio',
                          fr: 'Valeur estimee',
                          ar: 'القيمة التقديرية',
                        ),
                        lead['estimated_value'],
                      ),
                      _info(
                        _leadTr(
                          language,
                          en: 'Notes',
                          sw: 'Kumbukumbu',
                          fr: 'Notes',
                          ar: 'ملاحظات',
                        ),
                        lead['notes'],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _info(String label, dynamic value) {
    final displayValue = (value ?? '').toString().trim();
    final isDarkMode = ref.read(isDarkModeProvider);

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            displayValue.isEmpty ? '-' : displayValue,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _LeadFilters extends ConsumerWidget {
  final Map<String, dynamic> refs;
  final _LeadFilter filter;
  final AppLanguage language;
  final bool isDarkMode;

  const _LeadFilters({
    required this.refs,
    required this.filter,
    required this.language,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final statuses = (refs['lead_statuses'] as List? ?? [])
        .cast<Map<String, dynamic>>();
    final sources = (refs['lead_sources'] as List? ?? [])
        .cast<Map<String, dynamic>>();

    return ExpansionTile(
      title: Text(
        _leadTr(
          language,
          en: 'Filters',
          sw: 'Vichungi',
          fr: 'Filtres',
          ar: 'عوامل التصفية',
        ),
      ),
      initiallyExpanded:
          filter.statusId != null ||
          filter.sourceId != null ||
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
        _Drop<int>(
          label: _leadTr(
            language,
            en: 'Status',
            sw: 'Hali',
            fr: 'Statut',
            ar: 'الحالة',
          ),
          value: filter.statusId,
          items: statuses,
          onChanged: (v) => ref.read(_leadsFilterProvider.notifier).state =
              filter.copyWith(statusId: v, clearStatus: v == null),
          displayField: 'name',
          allLabel: _leadTr(
            language,
            en: 'All',
            sw: 'Zote',
            fr: 'Tous',
            ar: 'الكل',
          ),
        ),
        _Drop<int>(
          label: _leadTr(
            language,
            en: 'Source',
            sw: 'Chanzo',
            fr: 'Source',
            ar: 'المصدر',
          ),
          value: filter.sourceId,
          items: sources,
          onChanged: (v) => ref.read(_leadsFilterProvider.notifier).state =
              filter.copyWith(sourceId: v, clearSource: v == null),
          displayField: 'name',
          allLabel: _leadTr(
            language,
            en: 'All',
            sw: 'Zote',
            fr: 'Tous',
            ar: 'الكل',
          ),
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
                    ref.read(_leadsFilterProvider.notifier).state = filter
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
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _leadTr(
                                language,
                                en: 'Start Date',
                                sw: 'Tarehe ya Kuanza',
                                fr: 'Date de debut',
                                ar: 'تاريخ البدء',
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
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
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontSize: 14),
                            ),
                          ],
                        ),
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
                    ref.read(_leadsFilterProvider.notifier).state = filter
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
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _leadTr(
                                language,
                                en: 'End Date',
                                sw: 'Tarehe ya Mwisho',
                                fr: 'Date de fin',
                                ar: 'تاريخ الانتهاء',
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
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
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(fontSize: 14),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
        if (filter.statusId != null ||
            filter.sourceId != null ||
            filter.startDate != null ||
            filter.endDate != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: OutlinedButton(
              onPressed: () =>
                  ref.read(_leadsFilterProvider.notifier).state = _LeadFilter(),
              child: Text(
                _leadTr(
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

class _Drop<T> extends StatelessWidget {
  final String label;
  final T? value;
  final List<Map<String, dynamic>> items;
  final void Function(T?) onChanged;
  final String displayField;
  final String allLabel;

  const _Drop({
    required this.label,
    required this.value,
    required this.items,
    required this.onChanged,
    required this.displayField,
    required this.allLabel,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<T>(
        value: value,
        isExpanded: true,
        decoration: InputDecoration(labelText: label),
        items: [
          DropdownMenuItem<T>(
            value: null,
            child: Text(allLabel, overflow: TextOverflow.ellipsis),
          ),
          ...items.map(
            (item) => DropdownMenuItem<T>(
              value: item['id'] as T,
              child: Text(
                item[displayField]?.toString() ?? '-',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
        selectedItemBuilder: (context) => [
          Text(allLabel, maxLines: 1, overflow: TextOverflow.ellipsis),
          ...items.map(
            (item) => Text(
              item[displayField]?.toString() ?? '-',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
        onChanged: onChanged,
      ),
    );
  }
}

class _LeadCard extends StatelessWidget {
  final Map<String, dynamic> lead;
  final AppLanguage language;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _LeadCard({
    required this.lead,
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
          child: Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.person_search,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      lead['name'] as String? ?? '-',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${lead['lead_number'] ?? '-'} - ${lead['phone'] ?? '-'}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        if (lead['lead_status_name'] != null) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              lead['lead_status_name'] as String? ?? '-',
                              style: const TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w600,
                                color: AppColors.primary,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'view') {
                    onView();
                  } else if (value == 'edit') {
                    onEdit();
                  } else if (value == 'delete') {
                    onDelete();
                  }
                },
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'view',
                    child: Row(
                      children: [
                        const Icon(Icons.visibility, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          _leadTr(
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
                          _leadTr(
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
                          color: AppColors.error,
                        ),
                        const SizedBox(width: 8),
                        Text(
                          _leadTr(
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
            ],
          ),
        ),
      ),
    );
  }
}

class _LeadSheetHeader extends StatelessWidget {
  final String title;
  final VoidCallback onBack;

  const _LeadSheetHeader({required this.title, required this.onBack});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
      decoration: const BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          Container(
            width: 44,
            height: 5,
            decoration: BoxDecoration(
              color: Colors.white38,
              borderRadius: BorderRadius.circular(999),
            ),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              IconButton(
                onPressed: onBack,
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              ),
              Expanded(
                child: Text(
                  title,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(width: 48),
            ],
          ),
        ],
      ),
    );
  }
}

class _LeadFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? lead;

  const _LeadFormSheet({this.lead});

  @override
  ConsumerState<_LeadFormSheet> createState() => _LeadFormSheetState();
}

class _LeadFormSheetState extends ConsumerState<_LeadFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _phoneController;
  late final TextEditingController _emailController;
  late final TextEditingController _dateController;
  late final TextEditingController _addressController;
  late final TextEditingController _cityController;
  late final TextEditingController _siteController;
  late final TextEditingController _estimatedValueController;
  late final TextEditingController _notesController;

  int? _clientId;
  int? _sourceId;
  int? _serviceId;
  int? _statusId;
  int? _salespersonId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(
      text: widget.lead?['name']?.toString() ?? '',
    );
    _phoneController = TextEditingController(
      text: widget.lead?['phone']?.toString() ?? '',
    );
    _emailController = TextEditingController(
      text: widget.lead?['email']?.toString() ?? '',
    );
    _dateController = TextEditingController(
      text: widget.lead?['lead_date']?.toString() ?? '',
    );
    _addressController = TextEditingController(
      text: widget.lead?['address']?.toString() ?? '',
    );
    _cityController = TextEditingController(
      text: widget.lead?['city']?.toString() ?? '',
    );
    _siteController = TextEditingController(
      text: widget.lead?['site_location']?.toString() ?? '',
    );
    _estimatedValueController = TextEditingController(
      text: widget.lead?['estimated_value']?.toString() ?? '',
    );
    _notesController = TextEditingController(
      text: widget.lead?['notes']?.toString() ?? '',
    );

    _clientId = _toInt(widget.lead?['client_id']);
    _sourceId = _toInt(widget.lead?['lead_source_id']);
    _serviceId = _toInt(widget.lead?['service_interested_id']);
    _statusId = _toInt(widget.lead?['lead_status_id']);
    _salespersonId = _toInt(widget.lead?['salesperson_id']);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _dateController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _siteController.dispose();
    _estimatedValueController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final refsAsync = ref.watch(_leadRefsProvider);

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.94,
      ),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: refsAsync.when(
          loading: () => const Padding(
            padding: EdgeInsets.all(32),
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (error, _) => Padding(
            padding: const EdgeInsets.all(24),
            child: Text(_leadMessage(error, language)),
          ),
          data: (refs) => Column(
            children: [
              _LeadSheetHeader(
                title: widget.lead == null
                    ? _leadTr(
                        language,
                        en: 'New Lead',
                        sw: 'Lead Mpya',
                        fr: 'Nouveau prospect',
                        ar: 'عميل محتمل جديد',
                      )
                    : _leadTr(
                        language,
                        en: 'Edit Lead',
                        sw: 'Hariri Lead',
                        fr: 'Modifier le prospect',
                        ar: 'تعديل العميل المحتمل',
                      ),
                onBack: () => Navigator.pop(context),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    MediaQuery.of(context).viewInsets.bottom + 24,
                  ),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        _input(
                          _nameController,
                          _leadTr(
                            language,
                            en: 'Name *',
                            sw: 'Jina *',
                            fr: 'Nom *',
                            ar: 'الاسم *',
                          ),
                          required: true,
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _phoneController,
                          _leadTr(
                            language,
                            en: 'Phone *',
                            sw: 'Simu *',
                            fr: 'Telephone *',
                            ar: 'الهاتف *',
                          ),
                          required: true,
                          keyboardType: TextInputType.phone,
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _emailController,
                          'Email',
                          keyboardType: TextInputType.emailAddress,
                          validator: (value) {
                            final text = value?.trim() ?? '';
                            if (text.isEmpty) return null;
                            final emailPattern = RegExp(
                              r'^[^\s@]+@[^\s@]+\.[^\s@]+$',
                            );
                            if (!emailPattern.hasMatch(text)) {
                              return _leadTr(
                                language,
                                en: 'Enter a valid email',
                                sw: 'Weka barua pepe sahihi',
                                fr: 'Entrez une adresse e-mail valide',
                                ar: 'أدخل بريدا إلكترونيا صالحا',
                              );
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _dateController,
                          _leadTr(
                            language,
                            en: 'Date',
                            sw: 'Tarehe',
                            fr: 'Date',
                            ar: 'التاريخ',
                          ),
                          readOnly: true,
                          suffixIcon: const Icon(Icons.calendar_today_rounded),
                          onTap: () async {
                            final initialDate =
                                DateTime.tryParse(_dateController.text) ??
                                DateTime.now();
                            final picked = await showDatePicker(
                              context: context,
                              initialDate: initialDate,
                              firstDate: DateTime(2020),
                              lastDate: DateTime.now().add(
                                const Duration(days: 3650),
                              ),
                            );
                            if (picked != null) {
                              _dateController.text = DateFormat(
                                'yyyy-MM-dd',
                              ).format(picked);
                            }
                          },
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _addressController,
                          _leadTr(
                            language,
                            en: 'Address',
                            sw: 'Anwani',
                            fr: 'Adresse',
                            ar: 'العنوان',
                          ),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          _leadTr(
                            language,
                            en: 'Lead Source *',
                            sw: 'Chanzo cha Lead *',
                            fr: 'Source du prospect *',
                            ar: 'مصدر العميل المحتمل *',
                          ),
                          refs['lead_sources'],
                          _sourceId,
                          (value) => setState(() => _sourceId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          _leadTr(
                            language,
                            en: 'Service *',
                            sw: 'Huduma *',
                            fr: 'Service *',
                            ar: 'الخدمة *',
                          ),
                          _mapOptions(refs['service_interesteds']),
                          _serviceId,
                          (value) => setState(() => _serviceId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          _leadTr(
                            language,
                            en: 'Status *',
                            sw: 'Hali *',
                            fr: 'Statut *',
                            ar: 'الحالة *',
                          ),
                          refs['lead_statuses'],
                          _statusId,
                          (value) => setState(() => _statusId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          _leadTr(
                            language,
                            en: 'Salesperson *',
                            sw: 'Muuza *',
                            fr: 'Commercial *',
                            ar: 'مندوب المبيعات *',
                          ),
                          refs['salespeople'],
                          _salespersonId,
                          (value) => setState(() => _salespersonId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          _leadTr(
                            language,
                            en: 'Client',
                            sw: 'Mteja',
                            fr: 'Client',
                            ar: 'العميل',
                          ),
                          _clientOptions(refs['clients']),
                          _clientId,
                          (value) => setState(() => _clientId = value),
                          required: false,
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _cityController,
                          _leadTr(
                            language,
                            en: 'City',
                            sw: 'Mji',
                            fr: 'Ville',
                            ar: 'المدينة',
                          ),
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _siteController,
                          _leadTr(
                            language,
                            en: 'Site Location',
                            sw: 'Eneo la Site',
                            fr: 'Emplacement du site',
                            ar: 'موقع المشروع',
                          ),
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _estimatedValueController,
                          _leadTr(
                            language,
                            en: 'Estimated Value',
                            sw: 'Thamani ya Makadirio',
                            fr: 'Valeur estimee',
                            ar: 'القيمة التقديرية',
                          ),
                          keyboardType: TextInputType.number,
                        ),
                        const SizedBox(height: 12),
                        _input(
                          _notesController,
                          _leadTr(
                            language,
                            en: 'Notes',
                            sw: 'Maelezo',
                            fr: 'Notes',
                            ar: 'ملاحظات',
                          ),
                          maxLines: 3,
                        ),
                        const SizedBox(height: 20),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _saving ? null : _submit,
                            child: _saving
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : Text(
                                    widget.lead == null
                                        ? _leadTr(
                                            language,
                                            en: 'Save',
                                            sw: 'Hifadhi',
                                            fr: 'Enregistrer',
                                            ar: 'حفظ',
                                          )
                                        : _leadTr(
                                            language,
                                            en: 'Update',
                                            sw: 'Sasisha',
                                            fr: 'Mettre a jour',
                                            ar: 'تحديث',
                                          ),
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _input(
    TextEditingController controller,
    String label, {
    bool required = false,
    int maxLines = 1,
    TextInputType? keyboardType,
    bool readOnly = false,
    Widget? suffixIcon,
    VoidCallback? onTap,
    String? Function(String?)? validator,
  }) {
    final isDarkMode = ref.read(isDarkModeProvider);
    final language = ref.read(currentLanguageProvider);

    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      readOnly: readOnly,
      onTap: onTap,
      validator:
          validator ??
          (required
              ? (value) =>
                    (value == null || value.trim().isEmpty)
                    ? _leadTr(
                        language,
                        en: 'Required',
                        sw: 'Inahitajika',
                        fr: 'Obligatoire',
                        ar: 'مطلوب',
                      )
                    : null
              : null),
      decoration: InputDecoration(
        labelText: label,
        suffixIcon: suffixIcon,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dropdown(
    String label,
    dynamic rawItems,
    int? value,
    ValueChanged<int?> onChanged, {
    bool required = true,
  }) {
    final isDarkMode = ref.read(isDarkModeProvider);
    final language = ref.read(currentLanguageProvider);
    final items = _mapOptions(rawItems);

    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: items.any((item) => _toInt(item['id']) == value) ? value : null,
      validator: required
          ? (selected) => selected == null
                ? _leadTr(
                    language,
                    en: 'Required',
                    sw: 'Inahitajika',
                    fr: 'Obligatoire',
                    ar: 'مطلوب',
                  )
                : null
          : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map(
            (item) => DropdownMenuItem<int>(
              value: _toInt(item['id']),
              child: Text(
                item['name']?.toString() ?? '-',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(),
      selectedItemBuilder: (context) => items
          .map(
            (item) => Text(
              item['name']?.toString() ?? '-',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          )
          .toList(),
      onChanged: onChanged,
    );
  }

  List<Map<String, dynamic>> _clientOptions(dynamic rawItems) {
    final list = rawItems as List? ?? const [];

    return list.whereType<Map>().map((item) {
      final map = Map<String, dynamic>.from(item);
      final fullName = '${map['first_name'] ?? ''} ${map['last_name'] ?? ''}'
          .trim();

      return {'id': map['id'], 'name': fullName.isEmpty ? '-' : fullName};
    }).toList();
  }

  List<Map<String, dynamic>> _mapOptions(dynamic rawItems) {
    final list = rawItems as List? ?? const [];
    return list
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _saving = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = <String, dynamic>{
        'client_id': _clientId,
        'name': _nameController.text.trim(),
        'phone': _phoneController.text.trim(),
        'email': _nullableText(_emailController),
        'lead_date': _nullableText(_dateController),
        'address': _nullableText(_addressController),
        'city': _nullableText(_cityController),
        'site_location': _nullableText(_siteController),
        'estimated_value': _nullableText(_estimatedValueController),
        'notes': _nullableText(_notesController),
        'lead_source_id': _sourceId,
        'service_interested_id': _serviceId,
        'lead_status_id': _statusId,
        'salesperson_id': _salespersonId,
      };

      if (widget.lead == null) {
        await api.post('/leads', data: data);
      } else {
        await api.put('/leads/${widget.lead!['id']}', data: data);
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _leadMessage(error, ref.read(currentLanguageProvider)),
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  String? _nullableText(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : value;
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final AppLanguage language;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.message,
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
          _leadTr(
            language,
            en: 'Something went wrong',
            sw: 'Hitilafu imetokea',
            fr: 'Une erreur est survenue',
            ar: 'حدث خطأ ما',
          ),
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(message, textAlign: TextAlign.center),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(
              _leadTr(
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

int? _toInt(dynamic value) {
  if (value is int) {
    return value;
  }

  return int.tryParse(value?.toString() ?? '');
}
