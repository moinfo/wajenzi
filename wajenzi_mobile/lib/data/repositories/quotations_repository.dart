import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../datasources/remote/quotations_remote_datasource.dart';
import '../models/procurement/quotation_models.dart';

/// Repository facade over [QuotationsRemoteDataSource] for the Supplier
/// Quotations + Quotation Comparisons cluster.
class QuotationsRepository {
  final QuotationsRemoteDataSource _remote;
  QuotationsRepository(this._remote);

  // Supplier quotations
  Future<List<SupplierQuotationDto>> quotations({
    String? search,
    String? status,
    int? materialRequestId,
  }) =>
      _remote.listQuotations(
        search: search,
        status: status,
        materialRequestId: materialRequestId,
      );

  Future<SupplierQuotationDto> quotation(int id) => _remote.showQuotation(id);

  Future<QuotationReferenceData> referenceData() => _remote.referenceData();

  Future<List<SupplierRefDto>> availableSuppliers(int materialRequestId) =>
      _remote.availableSuppliers(materialRequestId);

  Future<void> createQuotation(Map<String, dynamic> body, {String? filePath}) =>
      _remote.createQuotation(body, filePath: filePath);

  Future<void> updateQuotation(int id, Map<String, dynamic> body, {String? filePath}) =>
      _remote.updateQuotation(id, body, filePath: filePath);

  Future<void> deleteQuotation(int id) => _remote.deleteQuotation(id);

  // Comparisons
  Future<List<ComparisonListItemDto>> comparisons({String? search, String? status}) =>
      _remote.listComparisons(search: search, status: status);

  Future<ComparisonDetailDto> comparison(int id) => _remote.showComparison(id);

  Future<ComparisonEligibilityDto> createEligibility(int materialRequestId) =>
      _remote.createEligibility(materialRequestId);

  Future<void> createComparison({
    required int materialRequestId,
    required int selectedQuotationId,
    required String recommendationReason,
  }) =>
      _remote.createComparison(
        materialRequestId: materialRequestId,
        selectedQuotationId: selectedQuotationId,
        recommendationReason: recommendationReason,
      );

  Future<void> approveComparison(int id, {String? comment}) =>
      _remote.approveComparison(id, comment: comment);

  Future<void> rejectComparison(int id, String reason) =>
      _remote.rejectComparison(id, reason);

  Future<Map<String, dynamic>> createPurchase(int id) => _remote.createPurchase(id);
}

final quotationsRepositoryProvider = Provider<QuotationsRepository>((ref) {
  return QuotationsRepository(ref.watch(quotationsRemoteDataSourceProvider));
});
