import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _staffBankSearchProvider = StateProvider.autoDispose<String>((ref) => '');

String _staffBankTr(
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

final _staffBankDetailsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/staff-bank-details');
        final data = response.data is Map<String, dynamic>
            ? response.data as Map<String, dynamic>
            : const <String, dynamic>{};
        final items = data['data'] as List? ?? const [];
        return {
          'items': items
              .whereType<Map>()
              .map((item) => Map<String, dynamic>.from(item))
              .toList(),
          'unavailable_on_live': false,
        };
      } on DioException catch (error) {
        if ((error.response?.statusCode ?? 0) == 404) {
          return {
            'items': const <Map<String, dynamic>>[],
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

final _staffBankRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>(
  (ref) async {
    final api = ref.watch(apiClientProvider);
    try {
      final response = await api.get('/staff-bank-details/reference-data');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    } on DioException catch (error) {
      if ((error.response?.statusCode ?? 0) == 404) {
        return const <String, dynamic>{};
      }
      rethrow;
    }
  },
);

class StaffBankDetailsScreen extends ConsumerStatefulWidget {
  const StaffBankDetailsScreen({super.key});

  @override
  ConsumerState<StaffBankDetailsScreen> createState() =>
      _StaffBankDetailsScreenState();
}

class _StaffBankDetailsScreenState
    extends ConsumerState<StaffBankDetailsScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final detailsAsync = ref.watch(_staffBankDetailsProvider);
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final search = ref.watch(_staffBankSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _staffBankTr(
            language,
            en: 'Staff Bank Details',
            sw: 'Akaunti za Benki',
            fr: 'Coordonnées bancaires du personnel',
            ar: 'بيانات الحسابات البنكية للموظفين',
          ),
        ),
      ),
      floatingActionButton: detailsAsync.maybeWhen(
        data: (payload) => payload['unavailable_on_live'] == true
            ? null
            : Padding(
                padding: const EdgeInsets.only(bottom: 80),
                child: FloatingActionButton(
                  onPressed: () => _openForm(context, ref),
                  child: const Icon(Icons.add_rounded),
                  tooltip: _staffBankTr(
                    language,
                    en: 'Add Bank Detail',
                    sw: 'Ongeza Taarifa',
                    fr: 'Ajouter des coordonnées bancaires',
                    ar: 'إضافة بيانات بنكية',
                  ),
                ),
              ),
        orElse: () => Padding(
          padding: const EdgeInsets.only(bottom: 80),
          child: FloatingActionButton(
            onPressed: () => _openForm(context, ref),
            child: const Icon(Icons.add_rounded),
            tooltip: _staffBankTr(
              language,
              en: 'Add Bank Detail',
              sw: 'Ongeza Taarifa',
              fr: 'Ajouter des coordonnées bancaires',
              ar: 'إضافة بيانات بنكية',
            ),
          ),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_staffBankDetailsProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  onChanged: (value) =>
                      ref.read(_staffBankSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: _staffBankTr(
                      language,
                      en: 'Search bank details...',
                      sw: 'Tafuta taarifa za benki...',
                      fr: 'Rechercher des coordonnées bancaires...',
                      ar: 'ابحث في البيانات البنكية...',
                    ),
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () =>
                                ref
                                        .read(_staffBankSearchProvider.notifier)
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
            detailsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _StaffBankErrorView(
                  message: vatErrorMessage(
                    error,
                    isSwahili: language == AppLanguage.swahili,
                  ),
                  language: language,
                  onRetry: () => ref.invalidate(_staffBankDetailsProvider),
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
                              Icons.account_balance_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              _staffBankTr(
                                language,
                                en: 'Staff Bank Details is not available on the live API right now.',
                                sw: 'Staff Bank Details haipatikani kwenye live API kwa sasa.',
                                fr: 'Les coordonnées bancaires du personnel ne sont pas disponibles sur l’API live pour le moment.',
                                ar: 'بيانات الحسابات البنكية للموظفين غير متاحة على واجهة الـ API المباشرة حالياً.',
                              ),
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[700],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }

                final details = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final filteredDetails = details.where((detail) {
                  if (search.isNotEmpty) {
                    final haystack = [
                      detail['staff_name'] ?? '',
                      detail['bank_name'] ?? '',
                      detail['account_number'] ?? '',
                      detail['branch'] ?? '',
                    ].join(' ').toLowerCase();
                    if (!haystack.contains(search)) return false;
                  }
                  return true;
                }).toList();

                if (filteredDetails.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.account_balance_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            details.isEmpty
                                ? _staffBankTr(
                                    language,
                                    en: 'No bank details found',
                                    sw: 'Hakuna taarifa za benki',
                                    fr: 'Aucune coordonnée bancaire trouvée',
                                    ar: 'لم يتم العثور على بيانات بنكية',
                                  )
                                : _staffBankTr(
                                    language,
                                    en: 'No matching results',
                                    sw: 'Hakuna matokeo yanayolingana',
                                    fr: 'Aucun résultat correspondant',
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
                                            _staffBankSearchProvider.notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.clear),
                              label: Text(
                                _staffBankTr(
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
                      final detail = filteredDetails[index];
                      return _StaffBankCard(
                        detail: detail,
                        index: index,
                        language: language,
                        isDarkMode: isDarkMode,
                        onEdit: () => _openForm(context, ref, detail: detail),
                        onDelete: () => _deleteDetail(context, ref, detail),
                        onTap: () => _showDetails(
                          context,
                          detail,
                          isDarkMode,
                          language,
                        ),
                      );
                    }, childCount: filteredDetails.length),
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
    Map<String, dynamic>? detail,
  }) async {
    final refs = await ref.read(_staffBankRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _StaffBankDetailFormSheet(refs: refs, detail: detail),
    );
    if (result == true) ref.invalidate(_staffBankDetailsProvider);
  }

  Future<void> _deleteDetail(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> detail,
  ) async {
    final language = ref.read(currentLanguageProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(
          _staffBankTr(
            language,
            en: 'Delete Bank Detail',
            sw: 'Futa Taarifa za Benki',
            fr: 'Supprimer les coordonnées bancaires',
            ar: 'حذف البيانات البنكية',
          ),
        ),
        content: Text(
          _staffBankTr(
            language,
            en: 'Delete bank detail for "${detail['staff_name']}"?',
            sw: 'Je, unataka kufuta taarifa za "${detail['staff_name']}"?',
            fr: 'Supprimer les coordonnées bancaires de "${detail['staff_name']}" ?',
            ar: 'هل تريد حذف البيانات البنكية الخاصة بـ "${detail['staff_name']}"؟',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(
              _staffBankTr(
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
              _staffBankTr(
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
      await ref
          .read(apiClientProvider)
          .delete('/staff-bank-details/${detail['id']}');
      ref.invalidate(_staffBankDetailsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _staffBankTr(
                language,
                en: 'Bank detail deleted',
                sw: 'Taarifa za benki zimefutwa',
                fr: 'Coordonnées bancaires supprimées',
                ar: 'تم حذف البيانات البنكية',
              ),
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              vatErrorMessage(error, isSwahili: language == AppLanguage.swahili),
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  void _showDetails(
    BuildContext context,
    Map<String, dynamic> detail,
    bool isDarkMode,
    AppLanguage language,
  ) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.58,
        child: Container(
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            top: false,
            child: Column(
              children: [
                const SizedBox(height: 12),
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                    children: [
                      Text(
                        detail['staff_name']?.toString() ?? 'Bank Detail',
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 18),
                      _BankDetailRow(
                        _staffBankTr(
                          language,
                          en: 'Bank',
                          sw: 'Benki',
                          fr: 'Banque',
                          ar: 'البنك',
                        ),
                        detail['bank_name']?.toString() ?? 'N/A',
                        isDarkMode,
                      ),
                      _BankDetailRow(
                        _staffBankTr(
                          language,
                          en: 'Account Number',
                          sw: 'Namba ya Akaunti',
                          fr: 'Numéro de compte',
                          ar: 'رقم الحساب',
                        ),
                        detail['account_number']?.toString() ?? 'N/A',
                        isDarkMode,
                      ),
                      _BankDetailRow(
                        _staffBankTr(
                          language,
                          en: 'Branch',
                          sw: 'Tawi',
                          fr: 'Agence',
                          ar: 'الفرع',
                        ),
                        detail['branch']?.toString() ?? 'N/A',
                        isDarkMode,
                      ),
                      _BankDetailRow(
                        _staffBankTr(
                          language,
                          en: 'Created',
                          sw: 'Imeundwa',
                          fr: 'Créé',
                          ar: 'تاريخ الإنشاء',
                        ),
                        _formatDate(detail['created_at']?.toString()),
                        isDarkMode,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _StaffBankErrorView extends StatelessWidget {
  final String message;
  final AppLanguage language;
  final VoidCallback onRetry;

  const _StaffBankErrorView({
    required this.message,
    required this.language,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            _staffBankTr(
              language,
              en: 'Something went wrong',
              sw: 'Hitilafu imetokea',
              fr: 'Un problème est survenu',
              ar: 'حدث خطأ ما',
            ),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text(message, textAlign: TextAlign.center),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(
              _staffBankTr(
                language,
                en: 'Retry',
                sw: 'Jaribu tena',
                fr: 'Réessayer',
                ar: 'أعد المحاولة',
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StaffBankCard extends StatelessWidget {
  final Map<String, dynamic> detail;
  final int index;
  final AppLanguage language;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _StaffBankCard({
    required this.detail,
    required this.index,
    required this.language,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '${index + 1}',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      detail['staff_name']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.blue.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        detail['bank_name']?.toString() ?? '-',
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          color: Colors.blue,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Icon(
                          Icons.credit_card,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          detail['account_number']?.toString() ?? '-',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                    if (detail['branch']?.toString().isNotEmpty ?? false) ...[
                      const SizedBox(height: 2),
                      Row(
                        children: [
                          Icon(
                            Icons.location_on,
                            size: 12,
                            color: isDarkMode
                                ? Colors.white38
                                : AppColors.textHint,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            detail['branch']?.toString() ?? '-',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white54
                                  : AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ],
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
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'view',
                    child: Row(
                      children: [
                        const Icon(Icons.visibility_rounded, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          _staffBankTr(
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
                        const Icon(Icons.edit_rounded, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          _staffBankTr(
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
                          _staffBankTr(
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

class _BankDetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;

  const _BankDetailRow(this.label, this.value, this.isDarkMode);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? 'N/A' : value,
              style: TextStyle(
                fontSize: 14,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StaffBankDetailFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? detail;

  const _StaffBankDetailFormSheet({required this.refs, this.detail});

  @override
  ConsumerState<_StaffBankDetailFormSheet> createState() =>
      _StaffBankDetailFormSheetState();
}

class _StaffBankDetailFormSheetState
    extends ConsumerState<_StaffBankDetailFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _accountNumberController;
  late final TextEditingController _branchController;
  int? _staffId;
  int? _bankId;
  bool _saving = false;

  bool get _isEdit => widget.detail != null;

  @override
  void initState() {
    super.initState();
    _accountNumberController = TextEditingController(
      text: widget.detail?['account_number']?.toString() ?? '',
    );
    _branchController = TextEditingController(
      text: widget.detail?['branch']?.toString() ?? '',
    );
    _staffId = _toNullableInt(widget.detail?['staff_id']);
    _bankId = _toNullableInt(widget.detail?['bank_id']);
  }

  @override
  void dispose() {
    _accountNumberController.dispose();
    _branchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final staffs = _toMaps(widget.refs['staffs']);
    final banks = _toMaps(widget.refs['banks']);

    final bgColor = isDarkMode ? const Color(0xFF1A1A2E) : Colors.white;
    final inputBg = isDarkMode ? const Color(0xFF0F1923) : Colors.grey[100];
    final textColor = isDarkMode ? Colors.white : AppColors.textPrimary;

    InputDecoration inputStyle(String label) => InputDecoration(
      labelText: label,
      filled: true,
      fillColor: inputBg,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
    );

    return Container(
      height: 0.84 * MediaQuery.of(context).size.height,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Column(
          children: [
            const SizedBox(height: 12),
            Container(
              width: 44,
              height: 5,
              decoration: BoxDecoration(
                color: isDarkMode ? Colors.white24 : Colors.black12,
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            Expanded(
              child: Form(
                key: _formKey,
                child: ListView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    MediaQuery.of(context).viewInsets.bottom + 24,
                  ),
                  children: [
                    Text(
                      _isEdit
                          ? _staffBankTr(
                              language,
                              en: 'Edit Bank Detail',
                              sw: 'Hariri Taarifa za Benki',
                              fr: 'Modifier les coordonnées bancaires',
                              ar: 'تعديل البيانات البنكية',
                            )
                          : _staffBankTr(
                              language,
                              en: 'New Bank Detail',
                              sw: 'Taarifa Mpya za Benki',
                              fr: 'Nouvelles coordonnées bancaires',
                              ar: 'بيانات بنكية جديدة',
                            ),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                        color: textColor,
                      ),
                    ),
                    const SizedBox(height: 20),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value:
                          staffs.any((item) => _toInt(item['id']) == _staffId)
                          ? _staffId
                          : null,
                      decoration: inputStyle(
                        _staffBankTr(
                          language,
                          en: 'Staff *',
                          sw: 'Mfanyakazi *',
                          fr: 'Employé *',
                          ar: 'الموظف *',
                        ),
                      ),
                      dropdownColor: bgColor,
                      validator: (selected) => selected == null
                          ? _staffBankTr(
                              language,
                              en: 'Required',
                              sw: 'Hitaji',
                              fr: 'Obligatoire',
                              ar: 'مطلوب',
                            )
                          : null,
                      items: staffs
                          .map(
                            (item) => DropdownMenuItem<int>(
                              value: _toInt(item['id']),
                              child: Text(
                                item['name']?.toString() ?? '-',
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(color: textColor),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _staffId = value),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      value: banks.any((item) => _toInt(item['id']) == _bankId)
                          ? _bankId
                          : null,
                      decoration: inputStyle(
                        _staffBankTr(
                          language,
                          en: 'Bank *',
                          sw: 'Benki *',
                          fr: 'Banque *',
                          ar: 'البنك *',
                        ),
                      ),
                      dropdownColor: bgColor,
                      validator: (selected) => selected == null
                          ? _staffBankTr(
                              language,
                              en: 'Required',
                              sw: 'Hitaji',
                              fr: 'Obligatoire',
                              ar: 'مطلوب',
                            )
                          : null,
                      items: banks
                          .map(
                            (item) => DropdownMenuItem<int>(
                              value: _toInt(item['id']),
                              child: Text(
                                item['name']?.toString() ?? '-',
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(color: textColor),
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _bankId = value),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _accountNumberController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? _staffBankTr(
                              language,
                              en: 'Account number is required',
                              sw: 'Namba ya akaunti inahitajika',
                              fr: 'Le numéro de compte est requis',
                              ar: 'رقم الحساب مطلوب',
                            )
                          : null,
                      decoration: inputStyle(
                        _staffBankTr(
                          language,
                          en: 'Account Number *',
                          sw: 'Namba ya Akaunti *',
                          fr: 'Numéro de compte *',
                          ar: 'رقم الحساب *',
                        ),
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _branchController,
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? _staffBankTr(
                              language,
                              en: 'Branch is required',
                              sw: 'Tawi linahitajika',
                              fr: 'L’agence est requise',
                              ar: 'الفرع مطلوب',
                            )
                          : null,
                      decoration: inputStyle(
                        _staffBankTr(
                          language,
                          en: 'Branch *',
                          sw: 'Tawi *',
                          fr: 'Agence *',
                          ar: 'الفرع *',
                        ),
                      ),
                      style: TextStyle(color: textColor),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _saving ? null : _submit,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              _isEdit
                                  ? _staffBankTr(
                                      language,
                                      en: 'Update',
                                      sw: 'Sasisha',
                                      fr: 'Mettre à jour',
                                      ar: 'تحديث',
                                    )
                                  : _staffBankTr(
                                      language,
                                      en: 'Save',
                                      sw: 'Hifadhi',
                                      fr: 'Enregistrer',
                                      ar: 'حفظ',
                                    ),
                            ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'staff_id': _staffId,
        'bank_id': _bankId,
        'account_number': _accountNumberController.text.trim(),
        'branch': _branchController.text.trim(),
      };

      if (_isEdit) {
        await api.put(
          '/staff-bank-details/${widget.detail!['id']}',
          data: data,
        );
      } else {
        await api.post('/staff-bank-details', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider)),
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    // Handle format like "2026-03-31 00:00:00"
    String normalized = raw.replaceAll(' ', 'T');
    return DateFormat('dd MMM yyyy').format(DateTime.parse(normalized));
  } catch (_) {
    // Try to extract just the date part
    final datePart = raw.split(' ').first;
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(datePart));
    } catch (_) {
      return raw;
    }
  }
}
