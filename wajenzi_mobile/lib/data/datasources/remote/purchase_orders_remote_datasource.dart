import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../models/procurement/purchase_order_models.dart';

/// Thin wrapper around [ApiClient] for the Purchase Orders / Record Deliveries
/// / Supplier Receivings cluster. Base URL already carries `/api/v1`, so paths
/// here are relative like `/procurement/...`.
class PurchaseOrdersRemoteDataSource {
  final ApiClient _api;
  PurchaseOrdersRemoteDataSource(this._api);

  // ── Purchase Orders ─────────────────────────────────────────────────────
  Future<List<PurchaseOrderListItem>> listPurchaseOrders({
    String? search,
    String? status,
  }) async {
    final qp = <String, dynamic>{'per_page': 100, 'procurement_only': true};
    if (search != null && search.isNotEmpty) qp['search'] = search;
    if (status != null && status.isNotEmpty) qp['status'] = status;
    final res = await _api.get('/procurement/purchases', queryParameters: qp);
    return _list(res.data)
        .map((e) => PurchaseOrderListItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<PurchaseOrderDetail> getPurchaseOrder(int id) async {
    final res = await _api.get('/procurement/purchases/$id');
    return PurchaseOrderDetail.fromJson(_single(res.data));
  }

  Future<void> submit(int id) =>
      _api.post('/procurement/purchases/$id/submit');

  Future<void> approve(int id, {String? comment}) => _api.post(
        '/procurement/purchases/$id/approve',
        data: {if (comment != null && comment.isNotEmpty) 'comment': comment},
      );

  Future<void> reject(int id, String reason) => _api.post(
        '/procurement/purchases/$id/reject',
        data: {'reason': reason},
      );

  Future<void> close(int id) => _api.post('/procurement/purchases/$id/close');

  Future<void> deletePurchaseOrder(int id) => _api.delete('/purchases/$id');

  Future<void> uploadPayment(
    int id, {
    required String paymentDate,
    required String paymentReference,
    String? paymentNote,
    required String filePath,
  }) async {
    final form = FormData.fromMap({
      'payment_date': paymentDate,
      'payment_reference': paymentReference,
      if (paymentNote != null && paymentNote.isNotEmpty)
        'payment_note': paymentNote,
      'payment_attachment': await MultipartFile.fromFile(
        filePath,
        filename: filePath.split('/').last,
      ),
    });
    await _api.uploadFile('/procurement/purchases/$id/payment', data: form);
  }

  // ── Record Deliveries ───────────────────────────────────────────────────
  Future<List<PendingDeliveryDto>> listPendingDeliveries({
    String? search,
  }) async {
    final qp = <String, dynamic>{'per_page': 100};
    if (search != null && search.isNotEmpty) qp['search'] = search;
    final res =
        await _api.get('/procurement/pending-deliveries', queryParameters: qp);
    return _list(res.data)
        .map((e) => PendingDeliveryDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  /// Records a delivery against an approved PO. [items] is a list of
  /// {purchase_item_id, quantity}. Optional delivery-note scan/photo.
  Future<void> storeDelivery(
    int purchaseId, {
    required String deliveryNoteNumber,
    required String date,
    required String condition,
    String? description,
    required List<Map<String, dynamic>> items,
    String? filePath,
  }) async {
    final map = <String, dynamic>{
      'delivery_note_number': deliveryNoteNumber,
      'date': date,
      'condition': condition,
      if (description != null && description.isNotEmpty)
        'description': description,
    };
    for (var i = 0; i < items.length; i++) {
      map['items[$i][purchase_item_id]'] = items[i]['purchase_item_id'];
      map['items[$i][quantity]'] = items[i]['quantity'];
    }
    if (filePath != null) {
      map['file'] = await MultipartFile.fromFile(
        filePath,
        filename: filePath.split('/').last,
      );
    }
    await _api.uploadFile(
      '/procurement/purchases/$purchaseId/deliveries',
      data: FormData.fromMap(map),
    );
  }

  // ── Supplier Receivings ─────────────────────────────────────────────────
  Future<List<ReceivingListItem>> listReceivings({
    String? search,
    String? status,
  }) async {
    final qp = <String, dynamic>{'per_page': 100};
    if (search != null && search.isNotEmpty) qp['search'] = search;
    if (status != null && status.isNotEmpty) qp['status'] = status;
    final res = await _api.get('/procurement/receivings', queryParameters: qp);
    return _list(res.data)
        .map((e) => ReceivingListItem.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<ReceivingDetail> getReceiving(int id) async {
    final res = await _api.get('/procurement/receivings/$id');
    return ReceivingDetail.fromJson(_single(res.data));
  }

  Future<void> storeReceivingOverheads(
    int id, {
    double? loadingAmount,
    double? offloadingAmount,
    double? transportationAmount,
    String? notes,
    String? expenseDate,
  }) async {
    await _api.post(
      '/procurement/receivings/$id/overheads',
      data: {
        'loading_amount': loadingAmount ?? 0,
        'offloading_amount': offloadingAmount ?? 0,
        'transportation_amount': transportationAmount ?? 0,
        if (notes != null && notes.isNotEmpty) 'overhead_notes': notes,
        if (expenseDate != null && expenseDate.isNotEmpty)
          'expense_date': expenseDate,
      },
    );
  }

  // ── Envelope helpers ────────────────────────────────────────────────────
  /// Extracts a list payload from {data:[...]} or {data:{data:[...]}}.
  List<Map> _list(dynamic raw) {
    if (raw is List) return raw.whereType<Map>().toList();
    if (raw is! Map) return const [];
    final data = raw['data'];
    if (data is List) return data.whereType<Map>().toList();
    if (data is Map) {
      final nested = data['data'];
      if (nested is List) return nested.whereType<Map>().toList();
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
}

final purchaseOrdersRemoteDataSourceProvider =
    Provider<PurchaseOrdersRemoteDataSource>((ref) {
  return PurchaseOrdersRemoteDataSource(ref.watch(apiClientProvider));
});
