import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../datasources/remote/paylog_remote_datasource.dart';
import '../models/paylog/paylog_models.dart';

/// Thin facade over [PaylogRemoteDataSource] for the Site Paylog screens
/// (Daily Payments, Payment Requests, Payment Channels, Daily/Monthly reports).
class PaylogRepository {
  final PaylogRemoteDataSource _ds;
  PaylogRepository(this._ds);

  Future<PaylogReferenceData> referenceData() => _ds.referenceData();

  // Payment Channels
  Future<List<PaymentChannelDto>> channels() => _ds.channels();
  Future<void> createChannel({required String name, required String type}) =>
      _ds.createChannel(name: name, type: type);
  Future<void> deleteChannel(int id) => _ds.deleteChannel(id);

  // Reports
  Future<DailyReportDto> reportsDaily({
    required int siteId,
    required String date,
  }) =>
      _ds.reportsDaily(siteId: siteId, date: date);

  Future<MonthlyReportDto> reportsMonthly({
    required int siteId,
    required String month,
  }) =>
      _ds.reportsMonthly(siteId: siteId, month: month);

  // Payment Requests
  Future<PaginatedResult<SitePaymentRequestDto>> requestsList({
    int? siteId,
    String? status,
    int page = 1,
  }) =>
      _ds.requestsList(siteId: siteId, status: status, page: page);

  Future<DailySummaryDto> dailySummary({
    required int siteId,
    required String date,
  }) =>
      _ds.dailySummary(siteId: siteId, date: date);

  Future<SitePaymentRequestDto> requestShow(int id) => _ds.requestShow(id);

  Future<void> storeBulk({
    required int siteId,
    int? projectId,
    required String paymentDate,
    required List<Map<String, dynamic>> payments,
    List<String> documentPaths = const [],
  }) =>
      _ds.storeBulk(
        siteId: siteId,
        projectId: projectId,
        paymentDate: paymentDate,
        payments: payments,
        documentPaths: documentPaths,
      );

  Future<void> destroyRequest(int id) => _ds.destroyRequest(id);

  // Approval actions
  Future<void> submit(int id) => _ds.submit(id);
  Future<void> approve(int id, {String? comment}) =>
      _ds.approve(id, comment: comment);
  Future<void> reject(int id, String comment) => _ds.reject(id, comment);

  // Finance record-payment
  Future<void> recordPayment(
    int id, {
    required String paymentReference,
    required String paymentDate,
    String? paymentNote,
    String? paymentSlipPath,
  }) =>
      _ds.recordPayment(
        id,
        paymentReference: paymentReference,
        paymentDate: paymentDate,
        paymentNote: paymentNote,
        paymentSlipPath: paymentSlipPath,
      );
}

final paylogRepositoryProvider = Provider<PaylogRepository>((ref) {
  return PaylogRepository(ref.watch(paylogRemoteDataSourceProvider));
});
