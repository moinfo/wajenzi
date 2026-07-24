import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../datasources/remote/purchase_orders_remote_datasource.dart';
import '../models/procurement/purchase_order_models.dart';

/// Thin facade over [PurchaseOrdersRemoteDataSource] for the Purchase Orders /
/// Record Deliveries / Supplier Receivings screens.
class PurchaseOrdersRepository {
  final PurchaseOrdersRemoteDataSource _ds;
  PurchaseOrdersRepository(this._ds);

  Future<List<PurchaseOrderListItem>> listPurchaseOrders({
    String? search,
    String? status,
  }) =>
      _ds.listPurchaseOrders(search: search, status: status);

  Future<PurchaseOrderDetail> getPurchaseOrder(int id) =>
      _ds.getPurchaseOrder(id);

  Future<void> submit(int id) => _ds.submit(id);
  Future<void> approve(int id, {String? comment}) =>
      _ds.approve(id, comment: comment);
  Future<void> reject(int id, String reason) => _ds.reject(id, reason);
  Future<void> close(int id) => _ds.close(id);
  Future<void> deletePurchaseOrder(int id) => _ds.deletePurchaseOrder(id);

  Future<void> uploadPayment(
    int id, {
    required String paymentDate,
    required String paymentReference,
    String? paymentNote,
    required String filePath,
  }) =>
      _ds.uploadPayment(
        id,
        paymentDate: paymentDate,
        paymentReference: paymentReference,
        paymentNote: paymentNote,
        filePath: filePath,
      );

  Future<List<PendingDeliveryDto>> listPendingDeliveries({String? search}) =>
      _ds.listPendingDeliveries(search: search);

  Future<void> storeDelivery(
    int purchaseId, {
    required String deliveryNoteNumber,
    required String date,
    required String condition,
    String? description,
    required List<Map<String, dynamic>> items,
    String? filePath,
  }) =>
      _ds.storeDelivery(
        purchaseId,
        deliveryNoteNumber: deliveryNoteNumber,
        date: date,
        condition: condition,
        description: description,
        items: items,
        filePath: filePath,
      );

  Future<List<ReceivingListItem>> listReceivings({
    String? search,
    String? status,
  }) =>
      _ds.listReceivings(search: search, status: status);

  Future<ReceivingDetail> getReceiving(int id) => _ds.getReceiving(id);

  Future<void> storeReceivingOverheads(
    int id, {
    double? loadingAmount,
    double? offloadingAmount,
    double? transportationAmount,
    String? notes,
    String? expenseDate,
  }) =>
      _ds.storeReceivingOverheads(
        id,
        loadingAmount: loadingAmount,
        offloadingAmount: offloadingAmount,
        transportationAmount: transportationAmount,
        notes: notes,
        expenseDate: expenseDate,
      );
}

final purchaseOrdersRepositoryProvider =
    Provider<PurchaseOrdersRepository>((ref) {
  return PurchaseOrdersRepository(
    ref.watch(purchaseOrdersRemoteDataSourceProvider),
  );
});
