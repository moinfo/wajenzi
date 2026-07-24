// Plain DTOs for the Supplier Quotations + Quotation Comparisons cluster.
// Hand-written `fromJson` (no codegen). snake_case JSON -> camelCase fields.

class QuotationItemDto {
  final int id;
  final int? materialRequestItemId;
  final int? boqItemId;
  final String description;
  final double quantity;
  final String? unit;
  final double unitPrice;
  final double totalPrice;
  final int sortOrder;

  const QuotationItemDto({
    required this.id,
    required this.materialRequestItemId,
    required this.boqItemId,
    required this.description,
    required this.quantity,
    required this.unit,
    required this.unitPrice,
    required this.totalPrice,
    required this.sortOrder,
  });

  factory QuotationItemDto.fromJson(Map<String, dynamic> json) {
    return QuotationItemDto(
      id: (json['id'] as num).toInt(),
      materialRequestItemId: (json['material_request_item_id'] as num?)?.toInt(),
      boqItemId: (json['boq_item_id'] as num?)?.toInt(),
      description: json['description']?.toString() ?? '',
      quantity: _asDouble(json['quantity']),
      unit: json['unit']?.toString(),
      unitPrice: _asDouble(json['unit_price']),
      totalPrice: _asDouble(json['total_price']),
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
    );
  }
}

class SupplierQuotationDto {
  final int id;
  final String quotationNumber;
  final int? supplierId;
  final String supplierName;
  final int? materialRequestId;
  final String materialRequestNumber;
  final String projectName;
  final String? quotationDate;
  final String? validUntil;
  final int? deliveryTimeDays;
  final String? paymentTerms;
  final double totalAmount;
  final double vatAmount;
  final double grandTotal;
  final double effectiveUnitPrice;
  final String? file;
  final String status;
  final bool isExpired;
  final bool isSelected;
  final String? notes;
  final List<QuotationItemDto> items;

  const SupplierQuotationDto({
    required this.id,
    required this.quotationNumber,
    required this.supplierId,
    required this.supplierName,
    required this.materialRequestId,
    required this.materialRequestNumber,
    required this.projectName,
    required this.quotationDate,
    required this.validUntil,
    required this.deliveryTimeDays,
    required this.paymentTerms,
    required this.totalAmount,
    required this.vatAmount,
    required this.grandTotal,
    required this.effectiveUnitPrice,
    required this.file,
    required this.status,
    required this.isExpired,
    required this.isSelected,
    required this.notes,
    required this.items,
  });

  factory SupplierQuotationDto.fromJson(Map<String, dynamic> json) {
    final mr = json['material_request'];
    final mrMap = mr is Map ? Map<String, dynamic>.from(mr) : const <String, dynamic>{};
    final supplier = json['supplier'];
    final supplierMap =
        supplier is Map ? Map<String, dynamic>.from(supplier) : const <String, dynamic>{};
    return SupplierQuotationDto(
      id: (json['id'] as num).toInt(),
      quotationNumber: json['quotation_number']?.toString() ?? '',
      supplierId: (json['supplier_id'] as num?)?.toInt(),
      supplierName: json['supplier_name']?.toString() ??
          supplierMap['name']?.toString() ??
          '',
      materialRequestId: (json['material_request_id'] as num?)?.toInt(),
      materialRequestNumber: mrMap['request_number']?.toString() ?? '',
      projectName: mrMap['project_name']?.toString() ?? '',
      quotationDate: json['quotation_date']?.toString(),
      validUntil: json['valid_until']?.toString(),
      deliveryTimeDays: (json['delivery_time_days'] as num?)?.toInt(),
      paymentTerms: json['payment_terms']?.toString(),
      totalAmount: _asDouble(json['total_amount']),
      vatAmount: _asDouble(json['vat_amount']),
      grandTotal: _asDouble(json['grand_total']),
      effectiveUnitPrice: _asDouble(json['effective_unit_price']),
      file: json['file']?.toString(),
      status: json['status']?.toString() ?? '',
      isExpired: json['is_expired'] == true,
      isSelected: json['is_selected'] == true,
      notes: json['notes']?.toString(),
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => QuotationItemDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class SupplierRefDto {
  final int id;
  final String name;
  final String? vrn;

  const SupplierRefDto({required this.id, required this.name, required this.vrn});

  factory SupplierRefDto.fromJson(Map<String, dynamic> json) {
    return SupplierRefDto(
      id: (json['id'] as num).toInt(),
      name: json['name']?.toString() ?? '',
      vrn: json['vrn']?.toString(),
    );
  }
}

class ReferenceMrItemDto {
  final int id;
  final String description;
  final double quantityRequested;
  final double? quantityApproved;
  final String? unit;
  final int? boqItemId;

  const ReferenceMrItemDto({
    required this.id,
    required this.description,
    required this.quantityRequested,
    required this.quantityApproved,
    required this.unit,
    required this.boqItemId,
  });

  /// Quantity to quote against — approved when set, otherwise requested.
  double get effectiveQuantity => quantityApproved ?? quantityRequested;

  factory ReferenceMrItemDto.fromJson(Map<String, dynamic> json) {
    return ReferenceMrItemDto(
      id: (json['id'] as num).toInt(),
      description: json['description']?.toString() ?? '',
      quantityRequested: _asDouble(json['quantity_requested']),
      quantityApproved: json['quantity_approved'] == null
          ? null
          : _asDouble(json['quantity_approved']),
      unit: json['unit']?.toString(),
      boqItemId: (json['boq_item_id'] as num?)?.toInt(),
    );
  }
}

class ReferenceMaterialRequestDto {
  final int id;
  final String requestNumber;
  final String projectName;
  final String status;
  final List<ReferenceMrItemDto> items;

  const ReferenceMaterialRequestDto({
    required this.id,
    required this.requestNumber,
    required this.projectName,
    required this.status,
    required this.items,
  });

  factory ReferenceMaterialRequestDto.fromJson(Map<String, dynamic> json) {
    return ReferenceMaterialRequestDto(
      id: (json['id'] as num).toInt(),
      requestNumber: json['request_number']?.toString() ?? '',
      projectName: json['project_name']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => ReferenceMrItemDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class QuotationReferenceData {
  final List<SupplierRefDto> suppliers;
  final List<ReferenceMaterialRequestDto> materialRequests;

  const QuotationReferenceData({
    required this.suppliers,
    required this.materialRequests,
  });

  factory QuotationReferenceData.fromJson(Map<String, dynamic> json) {
    return QuotationReferenceData(
      suppliers: ((json['suppliers'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => SupplierRefDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      materialRequests: ((json['material_requests'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => ReferenceMaterialRequestDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

// ── Comparisons ────────────────────────────────────────────────────────────

class ComparisonListItemDto {
  final int id;
  final String comparisonNumber;
  final String? comparisonDate;
  final String status;
  final int? materialRequestId;
  final String materialRequestNumber;
  final String projectName;
  final String selectedSupplierName;
  final double selectedAmount;
  final int quotationCount;
  final String preparedByName;
  final bool canCreatePurchase;

  const ComparisonListItemDto({
    required this.id,
    required this.comparisonNumber,
    required this.comparisonDate,
    required this.status,
    required this.materialRequestId,
    required this.materialRequestNumber,
    required this.projectName,
    required this.selectedSupplierName,
    required this.selectedAmount,
    required this.quotationCount,
    required this.preparedByName,
    required this.canCreatePurchase,
  });

  factory ComparisonListItemDto.fromJson(Map<String, dynamic> json) {
    return ComparisonListItemDto(
      id: (json['id'] as num).toInt(),
      comparisonNumber: json['comparison_number']?.toString() ?? '',
      comparisonDate: json['comparison_date']?.toString(),
      status: json['status']?.toString() ?? '',
      materialRequestId: (json['material_request_id'] as num?)?.toInt(),
      materialRequestNumber: json['material_request_number']?.toString() ?? '',
      projectName: json['project_name']?.toString() ?? '',
      selectedSupplierName: json['selected_supplier_name']?.toString() ?? '',
      selectedAmount: _asDouble(json['selected_amount']),
      quotationCount: (json['quotation_count'] as num?)?.toInt() ?? 0,
      preparedByName: json['prepared_by_name']?.toString() ?? '',
      canCreatePurchase: json['can_create_purchase'] == true,
    );
  }
}

class ComparisonQuotationDto {
  final int id;
  final String quotationNumber;
  final String supplierName;
  final String status;
  final String? quotationDate;
  final double grandTotal;
  final bool isSelected;
  final List<QuotationItemDto> items;

  const ComparisonQuotationDto({
    required this.id,
    required this.quotationNumber,
    required this.supplierName,
    required this.status,
    required this.quotationDate,
    required this.grandTotal,
    required this.isSelected,
    required this.items,
  });

  factory ComparisonQuotationDto.fromJson(Map<String, dynamic> json) {
    return ComparisonQuotationDto(
      id: (json['id'] as num).toInt(),
      quotationNumber: json['quotation_number']?.toString() ?? '',
      supplierName: json['supplier_name']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      quotationDate: json['quotation_date']?.toString(),
      grandTotal: _asDouble(json['grand_total']),
      isSelected: json['is_selected'] == true,
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => QuotationItemDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class ComparisonDetailDto {
  final int id;
  final String comparisonNumber;
  final String? comparisonDate;
  final String status;
  final String? recommendationReason;
  final int quotationCount;
  final double averageQuotationPrice;
  final double priceVariance;
  final double savings;
  final String materialRequestNumber;
  final String projectName;
  final String selectedQuotationNumber;
  final String selectedSupplierName;
  final double selectedAmount;
  final String preparedByName;
  final String approvedByName;
  final String? approvedDate;
  final bool canCreatePurchase;
  final List<ComparisonQuotationDto> quotations;

  const ComparisonDetailDto({
    required this.id,
    required this.comparisonNumber,
    required this.comparisonDate,
    required this.status,
    required this.recommendationReason,
    required this.quotationCount,
    required this.averageQuotationPrice,
    required this.priceVariance,
    required this.savings,
    required this.materialRequestNumber,
    required this.projectName,
    required this.selectedQuotationNumber,
    required this.selectedSupplierName,
    required this.selectedAmount,
    required this.preparedByName,
    required this.approvedByName,
    required this.approvedDate,
    required this.canCreatePurchase,
    required this.quotations,
  });

  factory ComparisonDetailDto.fromJson(Map<String, dynamic> json) {
    final mr = json['material_request'];
    final mrMap = mr is Map ? Map<String, dynamic>.from(mr) : const <String, dynamic>{};
    final sel = json['selected_quotation'];
    final selMap = sel is Map ? Map<String, dynamic>.from(sel) : const <String, dynamic>{};
    final prep = json['prepared_by'];
    final prepMap = prep is Map ? Map<String, dynamic>.from(prep) : const <String, dynamic>{};
    final appr = json['approved_by'];
    final apprMap = appr is Map ? Map<String, dynamic>.from(appr) : const <String, dynamic>{};
    return ComparisonDetailDto(
      id: (json['id'] as num).toInt(),
      comparisonNumber: json['comparison_number']?.toString() ?? '',
      comparisonDate: json['comparison_date']?.toString(),
      status: json['status']?.toString() ?? '',
      recommendationReason: json['recommendation_reason']?.toString(),
      quotationCount: (json['quotation_count'] as num?)?.toInt() ?? 0,
      averageQuotationPrice: _asDouble(json['average_quotation_price']),
      priceVariance: _asDouble(json['price_variance']),
      savings: _asDouble(json['savings']),
      materialRequestNumber: mrMap['request_number']?.toString() ?? '',
      projectName: mrMap['project_name']?.toString() ?? '',
      selectedQuotationNumber: selMap['quotation_number']?.toString() ?? '',
      selectedSupplierName: selMap['supplier_name']?.toString() ?? '',
      selectedAmount: _asDouble(selMap['grand_total']),
      preparedByName: prepMap['name']?.toString() ?? '',
      approvedByName: apprMap['name']?.toString() ?? '',
      approvedDate: json['approved_date']?.toString(),
      canCreatePurchase: json['can_create_purchase'] == true,
      quotations: ((json['quotations'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => ComparisonQuotationDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

// ── Comparison create eligibility ────────────────────────────────────────────

class EligItemDto {
  final int materialRequestItemId;
  final int? boqItemId;
  final String description;
  final String? unit;
  final double quantity;

  const EligItemDto({
    required this.materialRequestItemId,
    required this.boqItemId,
    required this.description,
    required this.unit,
    required this.quantity,
  });

  factory EligItemDto.fromJson(Map<String, dynamic> json) {
    return EligItemDto(
      materialRequestItemId: (json['material_request_item_id'] as num).toInt(),
      boqItemId: (json['boq_item_id'] as num?)?.toInt(),
      description: json['description']?.toString() ?? '',
      unit: json['unit']?.toString(),
      quantity: _asDouble(json['quantity']),
    );
  }
}

class EligQuotationDto {
  final int id;
  final String quotationNumber;
  final int? supplierId;
  final String supplierName;
  final String status;
  final String? quotationDate;
  final String? validUntil;
  final int? deliveryTimeDays;
  final String? paymentTerms;
  final bool isExpired;
  final double totalAmount;
  final double vatAmount;
  final double grandTotal;

  const EligQuotationDto({
    required this.id,
    required this.quotationNumber,
    required this.supplierId,
    required this.supplierName,
    required this.status,
    required this.quotationDate,
    required this.validUntil,
    required this.deliveryTimeDays,
    required this.paymentTerms,
    required this.isExpired,
    required this.totalAmount,
    required this.vatAmount,
    required this.grandTotal,
  });

  factory EligQuotationDto.fromJson(Map<String, dynamic> json) {
    return EligQuotationDto(
      id: (json['id'] as num).toInt(),
      quotationNumber: json['quotation_number']?.toString() ?? '',
      supplierId: (json['supplier_id'] as num?)?.toInt(),
      supplierName: json['supplier_name']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      quotationDate: json['quotation_date']?.toString(),
      validUntil: json['valid_until']?.toString(),
      deliveryTimeDays: (json['delivery_time_days'] as num?)?.toInt(),
      paymentTerms: json['payment_terms']?.toString(),
      isExpired: json['is_expired'] == true,
      totalAmount: _asDouble(json['total_amount']),
      vatAmount: _asDouble(json['vat_amount']),
      grandTotal: _asDouble(json['grand_total']),
    );
  }
}

class ComparisonEligibilityDto {
  final bool canCreate;
  final String? blockReason;
  final int minimumRequired;
  final int quotationCount;
  final int materialRequestId;
  final String materialRequestNumber;
  final String projectName;
  final List<EligItemDto> items;
  final List<EligQuotationDto> quotations;

  /// matrix[materialRequestItemId][quotationId] => unit price for that cell.
  final Map<int, Map<int, double>> itemPriceMatrix;

  final double analysisLowest;
  final double analysisHighest;
  final double analysisAverage;
  final double analysisVariance;

  const ComparisonEligibilityDto({
    required this.canCreate,
    required this.blockReason,
    required this.minimumRequired,
    required this.quotationCount,
    required this.materialRequestId,
    required this.materialRequestNumber,
    required this.projectName,
    required this.items,
    required this.quotations,
    required this.itemPriceMatrix,
    required this.analysisLowest,
    required this.analysisHighest,
    required this.analysisAverage,
    required this.analysisVariance,
  });

  factory ComparisonEligibilityDto.fromJson(Map<String, dynamic> json) {
    final mr = json['material_request'];
    final mrMap = mr is Map ? Map<String, dynamic>.from(mr) : const <String, dynamic>{};
    final analysis = json['analysis'];
    final analysisMap =
        analysis is Map ? Map<String, dynamic>.from(analysis) : const <String, dynamic>{};

    final matrix = <int, Map<int, double>>{};
    final rawMatrix = json['item_price_matrix'];
    if (rawMatrix is Map) {
      rawMatrix.forEach((mrItemKey, quotes) {
        final mrItemId = int.tryParse(mrItemKey.toString());
        if (mrItemId == null || quotes is! Map) return;
        final row = <int, double>{};
        quotes.forEach((quoteKey, cell) {
          final quoteId = int.tryParse(quoteKey.toString());
          if (quoteId == null || cell is! Map) return;
          row[quoteId] = _asDouble(cell['unit_price']);
        });
        matrix[mrItemId] = row;
      });
    }

    return ComparisonEligibilityDto(
      canCreate: json['can_create'] == true,
      blockReason: json['block_reason']?.toString(),
      minimumRequired: (json['minimum_required'] as num?)?.toInt() ?? 3,
      quotationCount: (json['quotation_count'] as num?)?.toInt() ?? 0,
      materialRequestId: (mrMap['id'] as num?)?.toInt() ?? 0,
      materialRequestNumber: mrMap['request_number']?.toString() ?? '',
      projectName: mrMap['project_name']?.toString() ?? '',
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => EligItemDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      quotations: ((json['quotations'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => EligQuotationDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      itemPriceMatrix: matrix,
      analysisLowest: _asDouble(analysisMap['lowest']),
      analysisHighest: _asDouble(analysisMap['highest']),
      analysisAverage: _asDouble(analysisMap['average']),
      analysisVariance: _asDouble(analysisMap['variance']),
    );
  }
}

double _asDouble(dynamic v) {
  if (v == null) return 0.0;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString()) ?? 0.0;
}
