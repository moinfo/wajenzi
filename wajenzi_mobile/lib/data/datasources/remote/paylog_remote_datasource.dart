import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../models/paylog/paylog_models.dart';

/// Thin wrapper around [ApiClient] for the Site Paylog module. The base URL
/// already carries `/api/v1`, so paths here are relative like
/// `/site-paylog/...`. Envelope is `{success, data, message}`; paginated
/// lists nest `data.data + data.meta{current_page,last_page,per_page,total}`.
class PaylogRemoteDataSource {
  final ApiClient _api;
  PaylogRemoteDataSource(this._api);

  // ── Reference data ────────────────────────────────────────────────────────
  Future<PaylogReferenceData> referenceData() async {
    final res = await _api.get('/site-paylog/reference-data');
    return PaylogReferenceData.fromJson(_single(res.data));
  }

  // ── Payment Channels ──────────────────────────────────────────────────────
  Future<List<PaymentChannelDto>> channels() async {
    final res = await _api.get('/site-paylog/channels');
    return _list(res.data)
        .map(PaymentChannelDto.fromJson)
        .toList();
  }

  Future<void> createChannel({
    required String name,
    required String type,
  }) =>
      _api.post('/site-paylog/channels', data: {'name': name, 'type': type});

  Future<void> deleteChannel(int id) =>
      _api.delete('/site-paylog/channels/$id');

  // ── Reports ───────────────────────────────────────────────────────────────
  Future<DailyReportDto> reportsDaily({
    required int siteId,
    required String date,
  }) async {
    final res = await _api.get(
      '/site-paylog/reports/daily',
      queryParameters: {'site_id': siteId, 'date': date},
    );
    return DailyReportDto.fromJson(_single(res.data));
  }

  Future<MonthlyReportDto> reportsMonthly({
    required int siteId,
    required String month, // YYYY-MM
  }) async {
    final res = await _api.get(
      '/site-paylog/reports/monthly',
      queryParameters: {'site_id': siteId, 'month': month},
    );
    return MonthlyReportDto.fromJson(_single(res.data));
  }

  // ── Payment Requests: list / summary / detail ─────────────────────────────
  Future<PaginatedResult<SitePaymentRequestDto>> requestsList({
    int? siteId,
    String? status,
    int page = 1,
  }) async {
    final qp = <String, dynamic>{'page': page, 'per_page': 25};
    if (siteId != null) qp['site_id'] = siteId;
    if (status != null && status.isNotEmpty) qp['status'] = status;
    final res = await _api.get('/site-paylog/requests', queryParameters: qp);
    return _paginated(res.data, SitePaymentRequestDto.fromJson);
  }

  Future<DailySummaryDto> dailySummary({
    required int siteId,
    required String date,
  }) async {
    final res = await _api.get(
      '/site-paylog/daily-summary',
      queryParameters: {'site_id': siteId, 'date': date},
    );
    return DailySummaryDto.fromJson(_single(res.data));
  }

  Future<SitePaymentRequestDto> requestShow(int id) async {
    final res = await _api.get('/site-paylog/requests/$id');
    return SitePaymentRequestDto.fromJson(_single(res.data));
  }

  // ── Create (multipart: payments[] + documents[]) ──────────────────────────
  Future<void> storeBulk({
    required int siteId,
    int? projectId,
    required String paymentDate,
    required List<Map<String, dynamic>> payments,
    List<String> documentPaths = const [],
  }) async {
    final map = <String, dynamic>{
      'site_id': siteId,
      'payment_date': paymentDate,
    };
    if (projectId != null) map['project_id'] = projectId;

    for (var i = 0; i < payments.length; i++) {
      final p = payments[i];
      map['payments[$i][payee_name]'] = p['payee_name'];
      map['payments[$i][reason]'] = p['reason'];
      map['payments[$i][category]'] = p['category'];
      map['payments[$i][amount]'] = p['amount'];
      final channelId = p['payment_channel_id'];
      if (channelId != null) {
        map['payments[$i][payment_channel_id]'] = channelId;
      }
      final accountName = p['account_name'];
      if (accountName != null && accountName.toString().isNotEmpty) {
        map['payments[$i][account_name]'] = accountName;
      }
    }

    if (documentPaths.isNotEmpty) {
      final files = <MultipartFile>[];
      for (final path in documentPaths) {
        files.add(await MultipartFile.fromFile(
          path,
          filename: path.split('/').last,
        ));
      }
      map['documents[]'] = files;
    }

    await _api.uploadFile('/site-paylog/requests', data: FormData.fromMap(map));
  }

  Future<void> destroyRequest(int id) =>
      _api.delete('/site-paylog/requests/$id');

  // ── Approval actions (RingleSoft, role-gated server-side) ─────────────────
  Future<void> submit(int id) =>
      _api.post('/site-paylog/requests/$id/submit');

  Future<void> approve(int id, {String? comment}) => _api.post(
        '/site-paylog/requests/$id/approve',
        data: {if (comment != null && comment.isNotEmpty) 'comment': comment},
      );

  Future<void> reject(int id, String comment) => _api.post(
        '/site-paylog/requests/$id/reject',
        data: {'comment': comment},
      );

  // ── Finance record-payment (multipart: payment_slip) ──────────────────────
  Future<void> recordPayment(
    int id, {
    required String paymentReference,
    required String paymentDate,
    String? paymentNote,
    String? paymentSlipPath,
  }) async {
    final map = <String, dynamic>{
      'payment_reference': paymentReference,
      'payment_date': paymentDate,
    };
    if (paymentNote != null && paymentNote.isNotEmpty) {
      map['payment_note'] = paymentNote;
    }
    if (paymentSlipPath != null) {
      map['payment_slip'] = await MultipartFile.fromFile(
        paymentSlipPath,
        filename: paymentSlipPath.split('/').last,
      );
    }
    await _api.uploadFile(
      '/site-paylog/requests/$id/pay',
      data: FormData.fromMap(map),
    );
  }

  // ── Envelope helpers ──────────────────────────────────────────────────────

  /// Extracts a list payload from {data:[...]} or {data:{data:[...]}}.
  List<Map<String, dynamic>> _list(dynamic raw) {
    if (raw is List) {
      return raw.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
    }
    if (raw is! Map) return const [];
    final data = raw['data'];
    if (data is List) {
      return data.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
    }
    if (data is Map) {
      final nested = data['data'];
      if (nested is List) {
        return nested
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
      }
    }
    return const [];
  }

  Map<String, dynamic> _single(dynamic raw) {
    if (raw is Map && raw['data'] is Map) {
      return Map<String, dynamic>.from(raw['data'] as Map);
    }
    if (raw is Map) return Map<String, dynamic>.from(raw);
    return const {};
  }

  /// Unwraps a Laravel paginator whether the meta lives under `data.meta`
  /// (custom envelope) or flat alongside `data.data` (raw paginator).
  PaginatedResult<T> _paginated<T>(
    dynamic raw,
    T Function(Map<String, dynamic>) parse,
  ) {
    Map<String, dynamic> container = const {};
    if (raw is Map && raw['data'] is Map) {
      container = Map<String, dynamic>.from(raw['data'] as Map);
    } else if (raw is Map) {
      container = Map<String, dynamic>.from(raw);
    }

    final itemsRaw = container['data'];
    final items = (itemsRaw is List ? itemsRaw : const [])
        .whereType<Map>()
        .map((e) => parse(Map<String, dynamic>.from(e)))
        .toList();

    final meta = container['meta'] is Map
        ? Map<String, dynamic>.from(container['meta'] as Map)
        : container;

    int metaInt(String key, int fallback) {
      final v = meta[key];
      if (v is num) return v.toInt();
      return int.tryParse(v?.toString() ?? '') ?? fallback;
    }

    return PaginatedResult<T>(
      items: items,
      currentPage: metaInt('current_page', 1),
      lastPage: metaInt('last_page', 1),
      perPage: metaInt('per_page', items.length),
      total: metaInt('total', items.length),
    );
  }
}

final paylogRemoteDataSourceProvider = Provider<PaylogRemoteDataSource>((ref) {
  return PaylogRemoteDataSource(ref.watch(apiClientProvider));
});
