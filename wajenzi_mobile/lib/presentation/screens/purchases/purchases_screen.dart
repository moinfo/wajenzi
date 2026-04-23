import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'dart:io';
import 'package:dio/dio.dart';
import '../../../core/config/app_config.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

Future<Response<dynamic>> _getWithFallback(
  ApiClient api,
  String primaryPath, {
  String? fallbackPath,
  Map<String, dynamic>? queryParameters,
}) async {
  try {
    return await api.get(primaryPath, queryParameters: queryParameters);
  } on DioException catch (e) {
    final shouldRetry =
        e.response?.statusCode == null &&
        (e.type == DioExceptionType.connectionError ||
            e.type == DioExceptionType.connectionTimeout ||
            e.type == DioExceptionType.receiveTimeout ||
            e.type == DioExceptionType.unknown);

    if (shouldRetry) {
      await Future.delayed(const Duration(milliseconds: 250));
      return api.get(primaryPath, queryParameters: queryParameters);
    }

    if (e.response?.statusCode == 404 && fallbackPath != null) {
      return api.get(fallbackPath, queryParameters: queryParameters);
    }
    rethrow;
  }
}

Future<Response<dynamic>> _deleteWithFallback(
  ApiClient api,
  String primaryPath, {
  String? fallbackPath,
}) async {
  try {
    return await api.delete(primaryPath);
  } on DioException catch (e) {
    if (e.response?.statusCode == 404 && fallbackPath != null) {
      return api.delete(fallbackPath);
    }
    rethrow;
  }
}

Future<Response<dynamic>> _uploadWithFallback(
  ApiClient api,
  String primaryPath, {
  String? fallbackPath,
  required FormData data,
  Options? options,
}) async {
  try {
    return await api.uploadFile(primaryPath, data: data, options: options);
  } on DioException catch (e) {
    if (e.response?.statusCode == 404 && fallbackPath != null) {
      return api.uploadFile(fallbackPath, data: data, options: options);
    }
    rethrow;
  }
}

final _purchasesStartProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime.now(),
);
final _purchasesEndProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime.now(),
);
final _purchasesSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _purchasesProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final start = ref.watch(_purchasesStartProvider);
  final end = ref.watch(_purchasesEndProvider);
  final response = await _getWithFallback(
    api,
    '/purchases',
    fallbackPath: '/vat/purchases',
    queryParameters: {
      'start_date': vatDateFmt(start),
      'end_date': vatDateFmt(end),
    },
  );

  List items = [];
  Map<String, dynamic> meta = {};

  try {
    final dynamic responseData = response.data;
    if (responseData is Map) {
      final dynamic dataField = responseData['data'];
      if (dataField is Map) {
        items =
            (dataField['purchases'] as List?)?.cast<Map<String, dynamic>>() ??
            [];
        meta = (dataField['totals'] as Map<String, dynamic>?) ?? {};
      } else if (dataField is List) {
        items = dataField.cast<Map<String, dynamic>>();
      }
    }
  } catch (e, st) {
    debugPrint('Error parsing purchases: $e $st');
  }

  return {'items': items, 'meta': meta};
});

final _suppliersProvider = FutureProvider.autoDispose<List<dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/purchases/suppliers');
    return response.data['data'] as List? ?? [];
  } on DioException catch (e) {
    if (e.response?.statusCode != 404) rethrow;
    final fallback = await api.get('/vat/reference-data');
    final data = fallback.data['data'] as Map<String, dynamic>? ?? {};
    return data['suppliers'] as List? ?? [];
  }
});

final _purchaseDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await _getWithFallback(
        api,
        '/purchases/$id',
        fallbackPath: '/vat/purchases/$id',
      );
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class PurchasesScreen extends ConsumerWidget {
  const PurchasesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final purchasesAsync = ref.watch(_purchasesProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final searchTerm = ref.watch(_purchasesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _trLocale(
            context,
            en: 'Purchases',
            sw: 'Manunuzi',
            ar: 'المشتريات',
          ),
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showPurchaseForm(context, ref),
          tooltip: _trLocale(context, en: 'Add', sw: 'Ongeza', ar: 'إضافة'),
          child: const Icon(Icons.add),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_purchasesProvider);
        },
        child: purchasesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: Localizations.localeOf(context).languageCode == 'sw',
            onRetry: () => ref.invalidate(_purchasesProvider),
          ),
          data: (payload) {
            final List purchases = payload['items'] as List? ?? [];
            final Map<String, dynamic> meta =
                payload['meta'] as Map<String, dynamic>? ?? {};
            final filteredPurchases = purchases.where((rawPurchase) {
              if (searchTerm.isEmpty) return true;
              final purchase = rawPurchase as Map<String, dynamic>;
              final haystack = [
                purchase['date'],
                purchase['tax_invoice'],
                purchase['invoice_date'],
                purchase['goods'],
                purchase['status'],
                purchase['approval_summary'],
                purchase['total_amount'],
                purchase['amount_vat_exc'],
                purchase['vat_amount'],
                purchase['is_expense'],
                (purchase['supplier'] as Map<String, dynamic>?)?['name'],
                (purchase['supplier'] as Map<String, dynamic>?)?['vrn'],
              ].whereType<Object>().join(' ').toLowerCase();
              return haystack.contains(searchTerm);
            }).toList();

            if (filteredPurchases.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  _PurchasesFiltersBar(
                    isDark: isDarkMode,
                    isSwahili: Localizations.localeOf(context).languageCode == 'sw',
                  ),
                  const SizedBox(height: 24),
                  const SizedBox(height: 100),
                  Icon(
                    Icons.shopping_cart_outlined,
                    size: 56,
                    color: Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    purchases.isEmpty
                        ? _trLocale(
                            context,
                            en: 'No purchases found',
                            sw: 'Hakuna manunuzi yoyote',
                            ar: 'لم يتم العثور على مشتريات',
                          )
                        : _trLocale(
                            context,
                            en: 'No purchases match your search',
                            sw: 'Hakuna matokeo yanayolingana',
                            ar: 'لا توجد مشتريات تطابق بحثك',
                          ),
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 24),
                  Center(
                    child: ElevatedButton.icon(
                      onPressed: () => _showPurchaseForm(context, ref),
                      icon: const Icon(Icons.add),
                      label: Text(
                        _trLocale(
                          context,
                          en: 'Add Purchase',
                          sw: 'Ongeza Ununuzi',
                          ar: 'إضافة عملية شراء',
                        ),
                      ),
                    ),
                  ),
                ],
              );
            }

            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: filteredPurchases.length + 3,
              itemBuilder: (context, index) {
                if (index == 0) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _PurchasesFiltersBar(
                      isDark: isDarkMode,
                      isSwahili: Localizations.localeOf(context).languageCode == 'sw',
                    ),
                  );
                }
                if (index == 1) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _PurchasesTotalsCard(meta: meta, isDark: isDarkMode),
                  );
                }
                final purchaseIndex = index - 2;
                if (purchaseIndex == filteredPurchases.length) {
                  return const SizedBox(height: 80);
                }
                final purchase =
                    filteredPurchases[purchaseIndex] as Map<String, dynamic>;
                return _PurchaseCard(
                  index: purchaseIndex + 1,
                  purchase: purchase,
                  isSwahili: Localizations.localeOf(context).languageCode == 'sw',
                  isDarkMode: isDarkMode,
                  onEdit: () =>
                      _showPurchaseForm(context, ref, purchase: purchase),
                  onDelete: () => _deletePurchase(context, ref, purchase),
                  onTap: () => _showPurchaseDetails(context, ref, purchase),
                );
              },
            );
          },
        ),
      ),
    );
  }

  Future<void> _showPurchaseForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? purchase,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) =>
          _PurchaseFormSheet(purchase: purchase, isNew: purchase == null),
    );

    if (result == true) {
      ref.invalidate(_purchasesProvider);
    }
  }

  Future<void> _deletePurchase(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> purchase,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          _trLocale(
            context,
            en: 'Delete Purchase',
            sw: 'Futa Ununuzi',
            ar: 'حذف عملية الشراء',
          ),
        ),
        content: Text(
          _trLocale(
            context,
            en: 'Are you sure you want to delete purchase from ${purchase['date']}?',
            sw: 'Je, una uhakika unataka kufuta ununuzi wa tarehe ${purchase['date']}?',
            ar: 'هل أنت متأكد أنك تريد حذف عملية الشراء بتاريخ ${purchase['date']}؟',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              _trLocale(context, en: 'Cancel', sw: 'Ghairi', ar: 'إلغاء'),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            child: Text(
              _trLocale(context, en: 'Delete', sw: 'Futa', ar: 'حذف'),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      final api = ref.read(apiClientProvider);
      await _deleteWithFallback(
        api,
        '/purchases/${purchase['id']}',
        fallbackPath: '/vat/purchases/${purchase['id']}',
      );

      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _trLocale(context, en: 'Deleted', sw: 'Umefutwa', ar: 'تم الحذف'),
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }

      ref.invalidate(_purchasesProvider);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${_trLocale(context, en: 'Error', sw: 'Hitilafu', ar: 'خطأ')}: $e',
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  Future<void> _showPurchaseDetails(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> purchase,
  ) async {
    final isDarkMode = ref.read(isDarkModeProvider);
    var detail = purchase;

    try {
      final api = ref.read(apiClientProvider);
      final response = await _getWithFallback(
        api,
        '/purchases/${purchase['id']}',
        fallbackPath: '/vat/purchases/${purchase['id']}',
      );
      final data = response.data['data'];
      if (data is Map<String, dynamic>) {
        detail = data;
      }
    } catch (_) {}

    if (!context.mounted) return;

    final purchaseItems =
        (detail['purchase_items'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final itemsSubtotal = purchaseItems.fold<double>(
      0,
      (sum, item) => sum + _toDouble(item['total_price']),
    );
    final approvalFlow =
        detail['approval_flow'] as Map<String, dynamic>? ?? const {};
    final approvalSteps =
        (approvalFlow['steps'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final fileUrl = detail['file_url'] as String?;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => Container(
        height: MediaQuery.of(context).size.height * 0.7,
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
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
                            _trLocale(
                              context,
                              en: 'Purchase Details',
                              sw: 'Maelezo ya Ununuzi',
                              ar: 'تفاصيل الشراء',
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
                          onPressed: () => Navigator.pop(ctx),
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
                    _SectionTitle(
                      title: _trLocale(
                        context,
                        en: 'Purchase Details',
                        sw: 'Maelezo ya Ununuzi',
                        ar: 'تفاصيل الشراء',
                      ),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Supplier',
                        sw: 'Msambazaji',
                        ar: 'المورد',
                      ),
                      value:
                          (detail['supplier'] as Map<String, dynamic>?)?['name']
                              as String? ??
                          '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Supplier VRN',
                        sw: 'VRN ya Msambazaji',
                        ar: 'رقم VRN للمورد',
                      ),
                      value:
                          (detail['supplier'] as Map<String, dynamic>?)?['vrn']
                              as String? ??
                          '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Tax Invoice',
                        sw: 'Ankara ya Kodi',
                        ar: 'الفاتورة الضريبية',
                      ),
                      value: detail['tax_invoice'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Invoice Date',
                        sw: 'Tarehe ya Ankara',
                        ar: 'تاريخ الفاتورة',
                      ),
                      value: _formatDate(detail['invoice_date'] as String?),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Goods',
                        sw: 'Bidhaa',
                        ar: 'البضائع',
                      ),
                      value: detail['goods'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Total Amount',
                        sw: 'Jumla ya Kiasi',
                        ar: 'إجمالي المبلغ',
                      ),
                      value: _formatMoney(_toDouble(detail['total_amount'])),
                      isDarkMode: isDarkMode,
                      valueColor: const Color(0xFF3B82F6),
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Amount VAT EXC',
                        sw: 'Kiasi Bila VAT',
                        ar: 'المبلغ بدون ضريبة القيمة المضافة',
                      ),
                      value: _formatMoney(_toDouble(detail['amount_vat_exc'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Date',
                        sw: 'Tarehe',
                        ar: 'التاريخ',
                      ),
                      value: _formatDate(detail['date'] as String?),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'VAT Amount',
                        sw: 'Kiasi cha VAT',
                        ar: 'مبلغ ضريبة القيمة المضافة',
                      ),
                      value: _formatMoney(_toDouble(detail['vat_amount'])),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      label: _trLocale(
                        context,
                        en: 'Status',
                        sw: 'Hali',
                        ar: 'الحالة',
                      ),
                      value: detail['status'] as String? ?? 'PENDING',
                      isDarkMode: isDarkMode,
                    ),
                    if ((fileUrl)?.isNotEmpty ?? false) ...[
                      const SizedBox(height: 8),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          onPressed: () => _openAttachment(
                            context,
                            fileUrl!,
                            isSwahili: Localizations.localeOf(context).languageCode == 'sw',
                          ),
                          icon: const Icon(Icons.attach_file_rounded),
                          label: Text(
                            _trLocale(
                              context,
                              en: 'Open Attachment',
                              sw: 'Fungua Kiambatisho',
                              ar: 'فتح المرفق',
                            ),
                          ),
                        ),
                      ),
                    ],
                    if ((detail['notes'] as String?)?.isNotEmpty ?? false)
                      _DetailRow(
                        label: _trLocale(
                          context,
                          en: 'Notes',
                          sw: 'Maelezo',
                          ar: 'ملاحظات',
                        ),
                        value: detail['notes'] as String? ?? '-',
                        isDarkMode: isDarkMode,
                      ),
                    if (purchaseItems.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      _SectionTitle(
                        title: _trLocale(
                          context,
                          en: 'Order Items',
                          sw: 'Vipengee vya Oda',
                          ar: 'عناصر الطلب',
                        ),
                        isDarkMode: isDarkMode,
                      ),
                      const SizedBox(height: 12),
                      _OrderItemsTable(
                        items: purchaseItems,
                        isDarkMode: isDarkMode,
                      ),
                      const SizedBox(height: 8),
                      _DetailRow(
                        label: _trLocale(
                          context,
                          en: 'Subtotal',
                          sw: 'Jumla ndogo',
                          ar: 'الإجمالي الفرعي',
                        ),
                        value: _formatMoney(itemsSubtotal),
                        isDarkMode: isDarkMode,
                      ),
                      _DetailRow(
                        label: _trLocale(
                          context,
                          en: 'VAT (18%)',
                          sw: 'VAT (18%)',
                          ar: 'ضريبة القيمة المضافة (18%)',
                        ),
                        value: _formatMoney(_toDouble(detail['vat_amount'])),
                        isDarkMode: isDarkMode,
                      ),
                      _DetailRow(
                        label: _trLocale(
                          context,
                          en: 'Grand Total',
                          sw: 'Jumla Kuu',
                          ar: 'الإجمالي الكلي',
                        ),
                        value: _formatMoney(_toDouble(detail['total_amount'])),
                        isDarkMode: isDarkMode,
                        valueColor: const Color(0xFF3B82F6),
                      ),
                    ],
                    const SizedBox(height: 16),
                    _SectionTitle(
                      title: _trLocale(
                        context,
                        en: 'Approval Flow',
                        sw: 'Mtiririko wa Idhini',
                        ar: 'سير الموافقات',
                      ),
                      isDarkMode: isDarkMode,
                    ),
                    _FlowStatusCard(
                      statusLabel:
                          approvalFlow['status_label'] as String? ??
                          'In Progress',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    if (approvalSteps.isNotEmpty) ...[
                      Text(
                        _trLocale(
                          context,
                          en: 'Approvals',
                          sw: 'Idhini',
                          ar: 'الموافقات',
                        ),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 10),
                      _ApprovalFlowTable(
                        steps: approvalSteps,
                        isDarkMode: isDarkMode,
                      ),
                      const SizedBox(height: 10),
                      Text(
                        approvalFlow['status_label'] as String? ??
                            _trLocale(
                              context,
                              en: 'Approval completed!',
                              sw: 'Idhini imekamilika!',
                              ar: 'اكتملت الموافقة!',
                            ),
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                    ] else ...[
                      const SizedBox(height: 12),
                      Text(
                        approvalFlow['status_label'] as String? ??
                            _trLocale(
                              context,
                              en: 'Not Submitted',
                              sw: 'Haijatumwa',
                              ar: 'لم يتم الإرسال',
                            ),
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(String? date) {
    if (date == null || date.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date ?? '-';
    }
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatMoney(double amount) {
    if (amount <= 0) return '-';
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
  }

  Future<void> _openAttachment(
    BuildContext context,
    String fileUrl, {
    required bool isSwahili,
  }) async {
    final normalizedUrl = AppConfig.normalizeExternalUrl(fileUrl);
    if (normalizedUrl == null) return;

    final opened = await ExternalLauncherService.openUri(
      Uri.parse(normalizedUrl),
    );
    if (!opened && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _trLocale(
              context,
              en: 'Failed to open attachment',
              sw: 'Imeshindikana kufungua kiambatisho',
              ar: 'فشل فتح المرفق',
            ),
          ),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _PurchasesFiltersBar extends ConsumerWidget {
  final bool isDark;
  final bool isSwahili;

  const _PurchasesFiltersBar({required this.isDark, required this.isSwahili});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final searchController = TextEditingController(
      text: ref.watch(_purchasesSearchProvider),
    );
    searchController.selection = TextSelection.collapsed(
      offset: searchController.text.length,
    );

    return Column(
      children: [
        TextField(
          controller: searchController,
          onChanged: (value) =>
              ref.read(_purchasesSearchProvider.notifier).state = value,
          decoration: InputDecoration(
            prefixIcon: const Icon(Icons.search_rounded),
            hintText: _trLocale(
              context,
              en: 'Search',
              sw: 'Tafuta',
              ar: 'بحث',
            ),
            filled: true,
            fillColor: isDark ? vatDarkCard : Colors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: isDark
                    ? vatDarkBorder
                    : Colors.grey.withValues(alpha: 0.15),
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: isDark
                    ? vatDarkBorder
                    : Colors.grey.withValues(alpha: 0.15),
              ),
            ),
          ),
        ),
        const SizedBox(height: 8),
        VatDateRangeBar(
          startProvider: _purchasesStartProvider,
          endProvider: _purchasesEndProvider,
          isDark: isDark,
          isSwahili: isSwahili,
        ),
      ],
    );
  }
}

class _PurchasesTotalsCard extends StatelessWidget {
  final Map<String, dynamic> meta;
  final bool isDark;

  const _PurchasesTotalsCard({required this.meta, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        final itemWidth = width < 640 ? width : (width - 8) / 2;
        final chips = [
          VatSummaryChip(
            label: 'Total Amount',
            value: vatMoney(meta['total_amount']),
            color: vatAccentBlue,
            isDark: isDark,
          ),
          VatSummaryChip(
            label: 'Amount VAT EXC',
            value: vatMoney(meta['amount_vat_exc']),
            color: vatAccentTeal,
            isDark: isDark,
          ),
          VatSummaryChip(
            label: 'VAT Amount',
            value: vatMoney(meta['vat_amount']),
            color: const Color(0xFFF59E0B),
            isDark: isDark,
          ),
        ];

        return Wrap(
          spacing: 8,
          runSpacing: 8,
          children: chips
              .map((chip) => SizedBox(width: itemWidth, child: chip))
              .toList(),
        );
      },
    );
  }
}

class _PurchaseFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? purchase;
  final bool isNew;

  const _PurchaseFormSheet({this.purchase, required this.isNew});

  @override
  ConsumerState<_PurchaseFormSheet> createState() => _PurchaseFormSheetState();
}

class _PurchaseFormSheetState extends ConsumerState<_PurchaseFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _totalAmountController;
  late final TextEditingController _taxInvoiceController;
  int? _selectedSupplierId;
  int? _selectedItemId;
  int? _selectedPurchaseType;
  String _isExpense = 'NO';
  DateTime _selectedDate = DateTime.now();
  DateTime? _invoiceDate;
  bool _loading = false;
  File? _selectedFile;

  @override
  void initState() {
    super.initState();
    _totalAmountController = TextEditingController(
      text: widget.purchase?['total_amount']?.toString() ?? '',
    );
    _taxInvoiceController = TextEditingController(
      text: widget.purchase?['tax_invoice']?.toString() ?? '',
    );
    _selectedSupplierId = _normalizeNullableInt(
      widget.purchase?['supplier_id'],
    );
    _selectedItemId = _normalizeNullableInt(widget.purchase?['item_id']);
    _selectedPurchaseType = _normalizeNullableInt(
      widget.purchase?['purchase_type'],
    );
    _isExpense = widget.purchase?['is_expense'] as String? ?? 'NO';
    if (widget.purchase?['date'] != null) {
      try {
        _selectedDate = DateTime.parse(widget.purchase!['date'] as String);
      } catch (_) {}
    }
    if (widget.purchase?['invoice_date'] != null) {
      try {
        _invoiceDate = DateTime.parse(
          widget.purchase!['invoice_date'] as String,
        );
      } catch (_) {}
    }
  }

  int? _normalizeNullableInt(dynamic value) {
    if (value is int) {
      return value > 0 ? value : null;
    }
    if (value is String) {
      final parsed = int.tryParse(value);
      if (parsed == null || parsed <= 0) return null;
      return parsed;
    }
    return null;
  }

  @override
  void dispose() {
    _totalAmountController.dispose();
    _taxInvoiceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    final suppliersAsync = ref.watch(_suppliersProvider);
    final referenceAsync = ref.watch(vatReferenceDataProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        top: false,
        child: SingleChildScrollView(
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
                  decoration: BoxDecoration(
                    color: isDarkMode ? vatDarkCard : AppColors.primary,
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(24),
                    ),
                  ),
                  child: Column(
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.35),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Row(
                        children: [
                          IconButton(
                            onPressed: () => Navigator.pop(context),
                            icon: const Icon(
                              Icons.arrow_back_rounded,
                              color: Colors.white,
                            ),
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                          Expanded(
                            child: Text(
                              widget.isNew
                                  ? _trLocale(
                                      context,
                                      en: 'New Purchase',
                                      sw: 'Ununuzi Mpya',
                                      ar: 'عملية شراء جديدة',
                                    )
                                  : _trLocale(
                                      context,
                                      en: 'Edit Purchase',
                                      sw: 'Hariri Ununuzi',
                                      ar: 'تعديل عملية الشراء',
                                    ),
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                            ),
                          ),
                          const SizedBox(width: 48),
                        ],
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _trLocale(
                          context,
                          en: 'Is Expenses?',
                          sw: 'Je, ni Gharama?',
                          ar: 'هل هي مصروفات؟',
                        ),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: _isExpense,
                            isExpanded: true,
                            dropdownColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.white,
                            items: [
                              DropdownMenuItem(
                                value: 'NO',
                                child: Text(
                                  _trLocale(
                                    context,
                                    en: 'NO',
                                    sw: 'HAPANA',
                                    ar: 'لا',
                                  ),
                                ),
                              ),
                              DropdownMenuItem(
                                value: 'YES',
                                child: Text(
                                  _trLocale(
                                    context,
                                    en: 'YES',
                                    sw: 'NDIYO',
                                    ar: 'نعم',
                                  ),
                                ),
                              ),
                            ],
                            onChanged: (v) {
                              if (v != null) {
                                setState(() => _isExpense = v);
                              }
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _trLocale(
                          context,
                          en: 'Supplier',
                          sw: 'Msambazaji',
                          ar: 'المورد',
                        ),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: suppliersAsync.when(
                          loading: () => const Padding(
                            padding: EdgeInsets.all(16),
                            child: CircularProgressIndicator(),
                          ),
                          error: (_, __) => Padding(
                            padding: const EdgeInsets.all(16),
                            child: Text(
                              _trLocale(
                                context,
                                en: 'Failed',
                                sw: 'Imeshindikana',
                                ar: 'فشل',
                              ),
                            ),
                          ),
                          data: (suppliers) => DropdownButtonHideUnderline(
                            child: DropdownButton<int?>(
                              value: _selectedSupplierId,
                              isExpanded: true,
                              hint: const Text(''),
                              dropdownColor: isDarkMode
                                  ? const Color(0xFF2A2A3E)
                                  : Colors.white,
                              items: (suppliers as List)
                                  .map(
                                    (s) => DropdownMenuItem(
                                      value: s['id'] as int,
                                      child: Text(s['name'] as String? ?? ''),
                                    ),
                                  )
                                  .toList(),
                              onChanged: (v) =>
                                  setState(() => _selectedSupplierId = v),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _trLocale(
                          context,
                          en: 'Item',
                          sw: 'Bidhaa',
                          ar: 'الصنف',
                        ),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: referenceAsync.when(
                          loading: () => const Padding(
                            padding: EdgeInsets.all(16),
                            child: CircularProgressIndicator(),
                          ),
                          error: (_, __) => Padding(
                            padding: EdgeInsets.all(16),
                            child: Text(
                              _trLocale(
                                context,
                                en: 'Failed',
                                sw: 'Imeshindikana',
                                ar: 'فشل',
                              ),
                            ),
                          ),
                          data: (reference) {
                            final items =
                                (reference['items'] as List?)
                                    ?.cast<dynamic>() ??
                                [];
                            return DropdownButtonHideUnderline(
                              child: DropdownButton<int?>(
                                value: _selectedItemId,
                                isExpanded: true,
                                hint: const Text(''),
                                dropdownColor: isDarkMode
                                    ? const Color(0xFF2A2A3E)
                                    : Colors.white,
                                items: items
                                    .map(
                                      (item) => DropdownMenuItem<int?>(
                                        value: item['id'] as int?,
                                        child: Text(
                                          item['name'] as String? ?? '',
                                        ),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (v) =>
                                    setState(() => _selectedItemId = v),
                              ),
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _trLocale(
                          context,
                          en: 'Purchase Type',
                          sw: 'Aina ya Ununuzi',
                          ar: 'نوع الشراء',
                        ),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: referenceAsync.when(
                          loading: () => const Padding(
                            padding: EdgeInsets.all(16),
                            child: CircularProgressIndicator(),
                          ),
                          error: (_, __) => Padding(
                            padding: EdgeInsets.all(16),
                            child: Text(
                              _trLocale(
                                context,
                                en: 'Failed',
                                sw: 'Imeshindikana',
                                ar: 'فشل',
                              ),
                            ),
                          ),
                          data: (reference) {
                            final purchaseTypes =
                                (reference['purchase_types'] as List?)
                                    ?.cast<dynamic>() ??
                                [];
                            return DropdownButtonHideUnderline(
                              child: DropdownButton<int?>(
                                value: _selectedPurchaseType,
                                isExpanded: true,
                                hint: const Text(''),
                                dropdownColor: isDarkMode
                                    ? const Color(0xFF2A2A3E)
                                    : Colors.white,
                                items: purchaseTypes
                                    .map(
                                      (type) => DropdownMenuItem<int?>(
                                        value: type['id'] as int?,
                                        child: Text(
                                          type['name'] as String? ?? '',
                                        ),
                                      ),
                                    )
                                    .toList(),
                                onChanged: (v) =>
                                    setState(() => _selectedPurchaseType = v),
                              ),
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _trLocale(
                          context,
                          en: 'Total Amount',
                          sw: 'Jumla ya Kiasi',
                          ar: 'إجمالي المبلغ',
                        ),
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
                        controller: _totalAmountController,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          hintText: '0.00',
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                        ),
                        validator: (v) => (v == null || v.isEmpty)
                            ? _trLocale(
                                context,
                                en: 'Amount is required',
                                sw: 'Kiasi kinahitajika',
                                ar: 'المبلغ مطلوب',
                              )
                            : null,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _trLocale(
                          context,
                          en: 'Tax Invoice',
                          sw: 'Ankara ya Kodi',
                          ar: 'الفاتورة الضريبية',
                        ),
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
                        controller: _taxInvoiceController,
                        decoration: InputDecoration(
                          hintText: 'Tax Invoice',
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF2A2A3E)
                              : Colors.grey[100],
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _trLocale(
                          context,
                          en: 'Invoice Date',
                          sw: 'Tarehe ya Ankara',
                          ar: 'تاريخ الفاتورة',
                        ),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      InkWell(
                        onTap: () async {
                          final picked = await showDatePicker(
                            context: context,
                            initialDate: _invoiceDate ?? _selectedDate,
                            firstDate: DateTime(2020),
                            lastDate: DateTime.now().add(
                              const Duration(days: 365),
                            ),
                          );
                          if (picked != null) {
                            setState(() => _invoiceDate = picked);
                          }
                        },
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.grey[100],
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                Icons.calendar_today,
                                color: isDarkMode
                                    ? Colors.white54
                                    : AppColors.textSecondary,
                              ),
                              const SizedBox(width: 12),
                              Text(
                                DateFormat(
                                  'dd MMM yyyy',
                                ).format(_invoiceDate ?? _selectedDate),
                                style: TextStyle(
                                  color: isDarkMode
                                      ? Colors.white
                                      : AppColors.textPrimary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        _trLocale(
                          context,
                          en: 'Date',
                          sw: 'Tarehe',
                          ar: 'التاريخ',
                        ),
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      InkWell(
                        onTap: () async {
                          final picked = await showDatePicker(
                            context: context,
                            initialDate: _selectedDate,
                            firstDate: DateTime(2020),
                            lastDate: DateTime.now().add(
                              const Duration(days: 365),
                            ),
                          );
                          if (picked != null) {
                            setState(() => _selectedDate = picked);
                          }
                        },
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.grey[100],
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                Icons.calendar_today,
                                color: isDarkMode
                                    ? Colors.white54
                                    : AppColors.textSecondary,
                              ),
                              const SizedBox(width: 12),
                              Text(
                                DateFormat('dd MMM yyyy').format(_selectedDate),
                                style: TextStyle(
                                  color: isDarkMode
                                      ? Colors.white
                                      : AppColors.textPrimary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      VatFilePicker(
                        file: _selectedFile,
                        isDark: isDarkMode,
                        isSwahili: Localizations.localeOf(context).languageCode == 'sw',
                        onPicked: (file) =>
                            setState(() => _selectedFile = file),
                      ),
                      const SizedBox(height: 32),
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
                                  height: 20,
                                  width: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  widget.isNew
                                      ? _trLocale(
                                          context,
                                          en: 'Save',
                                          sw: 'Hifadhi',
                                          ar: 'حفظ',
                                        )
                                      : _trLocale(
                                          context,
                                          en: 'Update',
                                          sw: 'Sasisha',
                                          ar: 'تحديث',
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
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedSupplierId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _trLocale(
              context,
              en: 'Select Supplier',
              sw: 'Chagua Wasambazaji',
              ar: 'اختر المورد',
            ),
          ),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }
    if (_selectedItemId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _trLocale(
              context,
              en: 'Select Item',
              sw: 'Chagua Item',
              ar: 'اختر الصنف',
            ),
          ),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }
    if (_selectedPurchaseType == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _trLocale(
              context,
              en: 'Select Purchase Type',
              sw: 'Chagua Purchase Type',
              ar: 'اختر نوع الشراء',
            ),
          ),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    setState(() => _loading = true);

    try {
      final api = ref.read(apiClientProvider);
      final totalAmount = double.tryParse(_totalAmountController.text) ?? 0;
      final purchaseType = _selectedPurchaseType ?? 1;
      final amountVatExc = purchaseType == 1 ? (totalAmount * 100 / 118) : 0;
      final vatAmount = purchaseType == 1 ? (amountVatExc * 18 / 100) : 0;
      final data = {
        'supplier_id': _selectedSupplierId,
        'item_id': _selectedItemId,
        'purchase_type': _selectedPurchaseType,
        'is_expense': _isExpense,
        'date': DateFormat('yyyy-MM-dd').format(_selectedDate),
        'tax_invoice': _taxInvoiceController.text.isEmpty
            ? null
            : _taxInvoiceController.text,
        'invoice_date': _invoiceDate != null
            ? DateFormat('yyyy-MM-dd').format(_invoiceDate!)
            : DateFormat('yyyy-MM-dd').format(_selectedDate),
        'total_amount': totalAmount,
        'amount_vat_exc': amountVatExc,
        'vat_amount': vatAmount,
      };
      final formData = await vatBuildFormData(data, _selectedFile);

      if (widget.isNew) {
        await _uploadWithFallback(
          api,
          '/purchases',
          fallbackPath: '/vat/purchases',
          data: formData,
          options: Options(contentType: 'multipart/form-data'),
        );
      } else {
        formData.fields.add(const MapEntry('_method', 'PUT'));
        await _uploadWithFallback(
          api,
          '/purchases/${widget.purchase!['id']}',
          fallbackPath: '/vat/purchases/${widget.purchase!['id']}',
          data: formData,
          options: Options(contentType: 'multipart/form-data'),
        );
      }

      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${_trLocale(context, en: 'Error', sw: 'Hitilafu', ar: 'خطأ')}: $e',
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }
}

class _PurchaseCard extends StatelessWidget {
  final int index;
  final Map<String, dynamic> purchase;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onTap;

  const _PurchaseCard({
    required this.index,
    required this.purchase,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onEdit,
    required this.onDelete,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final status = purchase['status'] as String? ?? 'PENDING';
    final amount = _toDouble(purchase['total_amount']);
    final amountVatExc = _toDouble(purchase['amount_vat_exc']);
    final vatAmount = _toDouble(purchase['vat_amount']);
    final approvalSummary = purchase['approval_summary'] as String? ?? '-';
    final hasAttachment = purchase['has_attachment'] == true;
    final supplierName =
        (purchase['supplier'] as Map<String, dynamic>?)?['name'] as String? ??
        '-';
    final supplierVrn =
        (purchase['supplier'] as Map<String, dynamic>?)?['vrn'] as String? ??
        '-';

    Color statusColor;
    switch (status.toUpperCase()) {
      case 'APPROVED':
      case 'COMPLETED':
        statusColor = const Color(0xFF27AE60);
        break;
      case 'SUBMITTED':
      case 'PENDING':
      case 'CREATED':
        statusColor = const Color(0xFFF59E0B);
        break;
      case 'REJECTED':
        statusColor = const Color(0xFFEF4444);
        break;
      default:
        statusColor = const Color(0xFF95A5A6);
    }

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
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Center(
                      child: Text(
                        '$index',
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF3B82F6),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          supplierName,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _formatDate(purchase['date'] as String?),
                          style: TextStyle(
                            fontSize: 13,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        if ((purchase['tax_invoice'] as String?)?.isNotEmpty ??
                            false) ...[
                          const SizedBox(height: 2),
                          Text(
                            'Invoice: ${purchase['tax_invoice']} | VRN: $supplierVrn',
                            style: TextStyle(
                              fontSize: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : AppColors.textHint,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      status,
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              const Divider(height: 1),
              const SizedBox(height: 16),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _MetricChip(
                    label: 'Total Amount',
                    value: _formatMoney(amount),
                    isDarkMode: isDarkMode,
                    valueColor: const Color(0xFF3B82F6),
                  ),
                  _MetricChip(
                    label: 'Amount VAT EXC',
                    value: _formatMoney(amountVatExc),
                    isDarkMode: isDarkMode,
                  ),
                  _MetricChip(
                    label: 'VAT Amount',
                    value: _formatMoney(vatAmount),
                    isDarkMode: isDarkMode,
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _DetailLine(
                      label: 'Goods',
                      value: purchase['goods'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                  ),
                  if (hasAttachment)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Icon(
                        Icons.attach_file_rounded,
                        size: 18,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'edit') onEdit();
                      if (value == 'delete') onDelete();
                    },
                    itemBuilder: (ctx) => [
                      PopupMenuItem(
                        value: 'edit',
                        child: Row(
                          children: [
                            const Icon(Icons.edit, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              _trLocale(
                                context,
                                en: 'Edit',
                                sw: 'Hariri',
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
                              _trLocale(
                                context,
                                en: 'Delete',
                                sw: 'Futa',
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
              const SizedBox(height: 12),
              _DetailLine(
                label: _trLocale(
                  context,
                  en: 'Attachment',
                  sw: 'Kiambatisho',
                  ar: 'المرفق',
                ),
                value: hasAttachment
                    ? _trLocale(
                        context,
                        en: 'Attachment',
                        sw: 'Kiambatisho',
                        ar: 'مرفق',
                      )
                    : _trLocale(
                        context,
                        en: 'No File',
                        sw: 'Hakuna Faili',
                        ar: 'لا يوجد ملف',
                      ),
                isDarkMode: isDarkMode,
                valueColor: hasAttachment ? vatAccentBlue : null,
              ),
              const SizedBox(height: 6),
              _DetailLine(
                label: _trLocale(
                  context,
                  en: 'Approvals',
                  sw: 'Idhini',
                  ar: 'الموافقات',
                ),
                value: approvalSummary,
                isDarkMode: isDarkMode,
              ),
              const SizedBox(height: 6),
              _DetailLine(
                label: _trLocale(
                  context,
                  en: 'Status',
                  sw: 'Hali',
                  ar: 'الحالة',
                ),
                value: status,
                isDarkMode: isDarkMode,
                valueColor: statusColor,
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatDate(String? date) {
    if (date == null || date.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date ?? '-';
    }
  }

  double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? 0;
  }

  String _formatMoney(double amount) {
    if (amount <= 0) return '-';
    return 'TZS ${NumberFormat('#,##0', 'en').format(amount)}';
  }
}

class _MetricChip extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _MetricChip({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: isDarkMode
            ? Colors.white.withValues(alpha: 0.05)
            : Colors.grey.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              color:
                  valueColor ??
                  (isDarkMode ? Colors.white : AppColors.textPrimary),
            ),
          ),
        ],
      ),
    );
  }
}

class _OrderItemsTable extends StatelessWidget {
  final List<Map<String, dynamic>> items;
  final bool isDarkMode;

  const _OrderItemsTable({required this.items, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        headingRowColor: WidgetStatePropertyAll(
          isDarkMode ? Colors.white.withValues(alpha: 0.08) : Colors.grey[200],
        ),
        columns: [
          DataColumn(label: Text('#')),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Description', sw: 'Maelezo', ar: 'الوصف'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(
                context,
                en: 'BOQ Item',
                sw: 'Kipengee cha BOQ',
                ar: 'عنصر BOQ',
              ),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Unit', sw: 'Kipimo', ar: 'الوحدة'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Qty', sw: 'Kiasi', ar: 'الكمية'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(
                context,
                en: 'Unit Price',
                sw: 'Bei ya Kipimo',
                ar: 'سعر الوحدة',
              ),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Total', sw: 'Jumla', ar: 'الإجمالي'),
            ),
          ),
        ],
        rows: items.asMap().entries.map((entry) {
          final item = entry.value;
          return DataRow(
            cells: [
              DataCell(Text('${entry.key + 1}')),
              DataCell(Text(item['description']?.toString() ?? '-')),
              DataCell(
                Text(
                  ((item['boq_item']
                              as Map<String, dynamic>?)?['description'] ??
                          (item['boq_item']
                              as Map<String, dynamic>?)?['item_code'] ??
                          '-')
                      .toString(),
                ),
              ),
              DataCell(Text(item['unit']?.toString() ?? '-')),
              DataCell(Text(item['quantity']?.toString() ?? '-')),
              DataCell(Text(_moneyOrDash(item['unit_price']))),
              DataCell(Text(_moneyOrDash(item['total_price']))),
            ],
          );
        }).toList(),
      ),
    );
  }

  String _moneyOrDash(dynamic value) {
    final parsed = _asDouble(value);
    if (parsed == null) return '-';
    return NumberFormat('#,##0.00', 'en').format(parsed);
  }

  double? _asDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    return double.tryParse('$value');
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  final bool isDarkMode;

  const _SectionTitle({required this.title, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w700,
          color: isDarkMode ? Colors.white : AppColors.textPrimary,
        ),
      ),
    );
  }
}

class _FlowStatusCard extends StatelessWidget {
  final String statusLabel;
  final bool isDarkMode;

  const _FlowStatusCard({required this.statusLabel, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode
            ? Colors.white.withValues(alpha: 0.05)
            : Colors.grey.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        statusLabel,
        style: TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: isDarkMode ? Colors.white : AppColors.textPrimary,
        ),
      ),
    );
  }
}

class _ApprovalFlowTable extends StatelessWidget {
  final List<Map<String, dynamic>> steps;
  final bool isDarkMode;

  const _ApprovalFlowTable({required this.steps, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        headingRowColor: WidgetStatePropertyAll(
          isDarkMode ? Colors.white.withValues(alpha: 0.08) : Colors.grey[200],
        ),
        columns: [
          DataColumn(
            label: Text(
              _trLocale(context, en: 'By:', sw: 'Na:', ar: 'بواسطة:'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Date', sw: 'Tarehe', ar: 'التاريخ'),
            ),
          ),
        ],
        rows: steps
            .map(
              (step) => DataRow(
                cells: [
                  DataCell(
                    Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text((step['role_name'] ?? '-').toString()),
                        if ((step['approver_name'] ?? '').toString().isNotEmpty)
                          Text(
                            (step['approver_name'] ?? '').toString(),
                            style: const TextStyle(fontSize: 12),
                          ),
                      ],
                    ),
                  ),
                  DataCell(
                    Text(
                      ((step['date'] ?? '').toString().isEmpty
                              ? (step['action'] ?? '-')
                              : step['date'])
                          .toString(),
                    ),
                  ),
                ],
              ),
            )
            .toList(),
      ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _DetailLine({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 80,
          child: Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value.isEmpty ? '-' : value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color:
                  valueColor ??
                  (isDarkMode ? Colors.white : AppColors.textPrimary),
            ),
          ),
        ),
      ],
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color:
                    valueColor ??
                    (isDarkMode ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.isSwahili,
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
          _trLocale(
            context,
            en: 'Something went wrong',
            sw: 'Hitilafu imetokea',
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
              _trLocale(
                context,
                en: 'Try again',
                sw: 'Jaribu tena',
                ar: 'حاول مرة أخرى',
              ),
            ),
          ),
        ),
      ],
    );
  }
}

String _trLocale(
  BuildContext context, {
  required String en,
  required String sw,
  required String ar,
}) {
  final code = Localizations.localeOf(context).languageCode.toLowerCase();
  if (code == 'ar') return ar;
  if (code == 'sw') return sw;
  return en;
}
