// Plain Dart DTOs for the Purchase Orders / Record Deliveries / Supplier
// Receivings cluster. Hand-written fromJson (no codegen). snake_case JSON →
// camelCase fields. Defensive guards throughout.

class PurchaseOrderListItem {
  final int id;
  final String documentNumber;
  final String? projectName;
  final String? supplierName;
  final String? materialRequestNumber;
  final double totalAmount;
  final int purchaseItemsCount;
  final String? date;
  final String status;
  final String? paymentStatus;
  final bool isPaymentUploaded;
  final bool isClosed;

  PurchaseOrderListItem({
    required this.id,
    required this.documentNumber,
    required this.projectName,
    required this.supplierName,
    required this.materialRequestNumber,
    required this.totalAmount,
    required this.purchaseItemsCount,
    required this.date,
    required this.status,
    required this.paymentStatus,
    required this.isPaymentUploaded,
    required this.isClosed,
  });

  factory PurchaseOrderListItem.fromJson(Map<String, dynamic> json) {
    final project = json['project'];
    final supplier = json['supplier'];
    final mr = json['material_request'];
    return PurchaseOrderListItem(
      id: (json['id'] as num).toInt(),
      documentNumber: json['document_number']?.toString() ??
          'PO-${json['id']}',
      projectName: project is Map ? project['name']?.toString() : null,
      supplierName: supplier is Map ? supplier['name']?.toString() : null,
      materialRequestNumber:
          mr is Map ? mr['request_number']?.toString() : null,
      totalAmount: _asDouble(json['total_amount']),
      purchaseItemsCount: (json['purchase_items_count'] as num?)?.toInt() ?? 0,
      date: json['date']?.toString(),
      status: json['status']?.toString() ??
          json['approval_status']?.toString() ??
          'PENDING',
      paymentStatus: json['payment_status']?.toString(),
      isPaymentUploaded: json['is_payment_uploaded'] == true,
      isClosed: json['is_closed'] == true,
    );
  }
}

class PurchaseItemDto {
  final int id;
  final String description;
  final String? unit;
  final double quantity;
  final double quantityReceived;
  final double quantityPending;
  final double unitPrice;
  final double totalPrice;
  final String? materialName;

  PurchaseItemDto({
    required this.id,
    required this.description,
    required this.unit,
    required this.quantity,
    required this.quantityReceived,
    required this.quantityPending,
    required this.unitPrice,
    required this.totalPrice,
    required this.materialName,
  });

  factory PurchaseItemDto.fromJson(Map<String, dynamic> json) {
    return PurchaseItemDto(
      id: (json['id'] as num).toInt(),
      description: json['description']?.toString() ?? 'Item',
      unit: json['unit']?.toString(),
      quantity: _asDouble(json['quantity']),
      quantityReceived: _asDouble(json['quantity_received']),
      quantityPending: _asDouble(json['quantity_pending']),
      unitPrice: _asDouble(json['unit_price']),
      totalPrice: _asDouble(json['total_price']),
      materialName: json['material_name']?.toString(),
    );
  }

  bool get isFullyReceived => quantityPending <= 0 && quantity > 0;
}

class ApprovalStepDto {
  final String roleName;
  final String action;
  final String? approverName;
  final String? date;
  final String? comment;

  ApprovalStepDto({
    required this.roleName,
    required this.action,
    required this.approverName,
    required this.date,
    required this.comment,
  });

  factory ApprovalStepDto.fromJson(Map<String, dynamic> json) {
    return ApprovalStepDto(
      roleName: json['role_name']?.toString() ?? 'Step',
      action: json['action']?.toString() ?? 'Pending',
      approverName: json['approver_name']?.toString(),
      date: json['date']?.toString(),
      comment: json['comment']?.toString(),
    );
  }
}

class ApprovalFlowDto {
  final String statusLabel;
  final String? nextRoleName;
  final bool isSubmitted;
  final bool isCompleted;
  final bool canBeSubmitted;
  final bool canBeApproved;
  final List<ApprovalStepDto> steps;

  ApprovalFlowDto({
    required this.statusLabel,
    required this.nextRoleName,
    required this.isSubmitted,
    required this.isCompleted,
    required this.canBeSubmitted,
    required this.canBeApproved,
    required this.steps,
  });

  factory ApprovalFlowDto.fromJson(Map<String, dynamic> json) {
    return ApprovalFlowDto(
      statusLabel: json['status_label']?.toString() ?? '',
      nextRoleName: json['next_role_name']?.toString(),
      isSubmitted: json['is_submitted'] == true,
      isCompleted: json['is_completed'] == true,
      canBeSubmitted: json['can_be_submitted'] == true,
      canBeApproved: json['can_be_approved'] == true,
      steps: ((json['steps'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => ApprovalStepDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class PaymentUploaderDto {
  final int id;
  final String name;
  PaymentUploaderDto({required this.id, required this.name});
  factory PaymentUploaderDto.fromJson(Map<String, dynamic> json) =>
      PaymentUploaderDto(
        id: (json['id'] as num).toInt(),
        name: json['name']?.toString() ?? '',
      );
}

class PurchaseOrderDetail {
  final int id;
  final String documentNumber;
  final String? projectName;
  final String? supplierName;
  final String? materialRequestNumber;
  final double totalAmount;
  final double amountVatExc;
  final double vatAmount;
  final String? date;
  final String status;
  final String? paymentStatus;
  final bool isPaymentUploaded;
  final bool isClosed;
  final String? paymentTerms;
  final String? expectedDeliveryDate;
  final String? notes;
  final String? createdByName;
  final List<PurchaseItemDto> items;
  final ApprovalFlowDto? approvalFlow;
  // Payment
  final String? paymentDate;
  final String? paymentReference;
  final String? paymentNote;
  final String? paymentAttachmentUrl;
  final PaymentUploaderDto? paymentUploadedBy;
  final bool canUploadPayment;
  final bool canClose;

  PurchaseOrderDetail({
    required this.id,
    required this.documentNumber,
    required this.projectName,
    required this.supplierName,
    required this.materialRequestNumber,
    required this.totalAmount,
    required this.amountVatExc,
    required this.vatAmount,
    required this.date,
    required this.status,
    required this.paymentStatus,
    required this.isPaymentUploaded,
    required this.isClosed,
    required this.paymentTerms,
    required this.expectedDeliveryDate,
    required this.notes,
    required this.createdByName,
    required this.items,
    required this.approvalFlow,
    required this.paymentDate,
    required this.paymentReference,
    required this.paymentNote,
    required this.paymentAttachmentUrl,
    required this.paymentUploadedBy,
    required this.canUploadPayment,
    required this.canClose,
  });

  factory PurchaseOrderDetail.fromJson(Map<String, dynamic> json) {
    final project = json['project'];
    final supplier = json['supplier'];
    final mr = json['material_request'];
    final user = json['user'];
    final flow = json['approval_flow'];
    final uploader = json['payment_uploaded_by'];
    return PurchaseOrderDetail(
      id: (json['id'] as num).toInt(),
      documentNumber:
          json['document_number']?.toString() ?? 'PO-${json['id']}',
      projectName: project is Map ? project['name']?.toString() : null,
      supplierName: supplier is Map ? supplier['name']?.toString() : null,
      materialRequestNumber:
          mr is Map ? mr['request_number']?.toString() : null,
      totalAmount: _asDouble(json['total_amount']),
      amountVatExc: _asDouble(json['amount_vat_exc']),
      vatAmount: _asDouble(json['vat_amount']),
      date: json['date']?.toString(),
      status: json['status']?.toString() ??
          json['approval_status']?.toString() ??
          'PENDING',
      paymentStatus: json['payment_status']?.toString(),
      isPaymentUploaded: json['is_payment_uploaded'] == true,
      isClosed: json['is_closed'] == true,
      paymentTerms: json['payment_terms']?.toString(),
      expectedDeliveryDate: json['expected_delivery_date']?.toString(),
      notes: json['notes']?.toString(),
      createdByName: user is Map ? user['name']?.toString() : null,
      items: ((json['purchase_items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => PurchaseItemDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      approvalFlow: flow is Map
          ? ApprovalFlowDto.fromJson(Map<String, dynamic>.from(flow))
          : null,
      paymentDate: json['payment_date']?.toString(),
      paymentReference: json['payment_reference']?.toString(),
      paymentNote: json['payment_note']?.toString(),
      paymentAttachmentUrl: json['payment_attachment_url']?.toString(),
      paymentUploadedBy: uploader is Map
          ? PaymentUploaderDto.fromJson(Map<String, dynamic>.from(uploader))
          : null,
      canUploadPayment: json['can_upload_payment'] == true,
      canClose: json['can_close'] == true,
    );
  }
}

class PendingDeliveryDto {
  final int id;
  final String documentNumber;
  final String? projectName;
  final String? supplierName;
  final String? materialRequestNumber;
  final int purchaseItemsCount;
  final int fullyReceivedCount;
  final int partiallyReceivedCount;
  final int pendingCount;
  final String? date;
  final String status;

  PendingDeliveryDto({
    required this.id,
    required this.documentNumber,
    required this.projectName,
    required this.supplierName,
    required this.materialRequestNumber,
    required this.purchaseItemsCount,
    required this.fullyReceivedCount,
    required this.partiallyReceivedCount,
    required this.pendingCount,
    required this.date,
    required this.status,
  });

  factory PendingDeliveryDto.fromJson(Map<String, dynamic> json) {
    final project = json['project'];
    final supplier = json['supplier'];
    final mr = json['material_request'];
    return PendingDeliveryDto(
      id: (json['id'] as num).toInt(),
      documentNumber:
          json['document_number']?.toString() ?? 'PO-${json['id']}',
      projectName: project is Map ? project['name']?.toString() : null,
      supplierName: supplier is Map ? supplier['name']?.toString() : null,
      materialRequestNumber:
          mr is Map ? mr['request_number']?.toString() : null,
      purchaseItemsCount: (json['purchase_items_count'] as num?)?.toInt() ?? 0,
      fullyReceivedCount: (json['fully_received_count'] as num?)?.toInt() ?? 0,
      partiallyReceivedCount:
          (json['partially_received_count'] as num?)?.toInt() ?? 0,
      pendingCount: (json['pending_count'] as num?)?.toInt() ?? 0,
      date: json['date']?.toString(),
      status: json['status']?.toString() ?? 'APPROVED',
    );
  }
}

class ReceivingListItem {
  final int id;
  final String receivingNumber;
  final String? purchaseDocumentNumber;
  final String? projectName;
  final String? supplierName;
  final String? date;
  final String? deliveryNoteNumber;
  final double quantityDelivered;
  final String? condition;
  final String status;
  final bool needsInspection;

  ReceivingListItem({
    required this.id,
    required this.receivingNumber,
    required this.purchaseDocumentNumber,
    required this.projectName,
    required this.supplierName,
    required this.date,
    required this.deliveryNoteNumber,
    required this.quantityDelivered,
    required this.condition,
    required this.status,
    required this.needsInspection,
  });

  factory ReceivingListItem.fromJson(Map<String, dynamic> json) {
    final purchase = json['purchase'];
    final project = json['project'];
    final supplier = json['supplier'];
    return ReceivingListItem(
      id: (json['id'] as num).toInt(),
      receivingNumber:
          json['receiving_number']?.toString() ?? 'SR-${json['id']}',
      purchaseDocumentNumber:
          purchase is Map ? purchase['document_number']?.toString() : null,
      projectName: project is Map ? project['name']?.toString() : null,
      supplierName: supplier is Map ? supplier['name']?.toString() : null,
      date: json['date']?.toString(),
      deliveryNoteNumber: json['delivery_note_number']?.toString(),
      quantityDelivered: _asDouble(json['quantity_delivered']),
      condition: json['condition']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      needsInspection: json['needs_inspection'] == true,
    );
  }
}

class ReceivingItemDto {
  final int id;
  final String? description;
  final String? unit;
  final double quantity;
  final double quantityReceived;
  final String? status;

  ReceivingItemDto({
    required this.id,
    required this.description,
    required this.unit,
    required this.quantity,
    required this.quantityReceived,
    required this.status,
  });

  factory ReceivingItemDto.fromJson(Map<String, dynamic> json) {
    return ReceivingItemDto(
      id: (json['id'] as num).toInt(),
      description: json['description']?.toString(),
      unit: json['unit']?.toString(),
      quantity: _asDouble(json['quantity']),
      quantityReceived: _asDouble(json['quantity_received']),
      status: json['status']?.toString(),
    );
  }
}

class OverheadsDto {
  final double loading;
  final double offloading;
  final double transportation;
  final String? notes;
  final String? expenseDate;

  OverheadsDto({
    required this.loading,
    required this.offloading,
    required this.transportation,
    required this.notes,
    required this.expenseDate,
  });

  factory OverheadsDto.fromJson(Map<String, dynamic> json) {
    return OverheadsDto(
      loading: _asDouble(json['loading']),
      offloading: _asDouble(json['offloading']),
      transportation: _asDouble(json['transportation']),
      notes: json['notes']?.toString(),
      expenseDate: json['expense_date']?.toString(),
    );
  }
}

class ReceivingDetail {
  final int id;
  final String receivingNumber;
  final String? purchaseDocumentNumber;
  final String? projectName;
  final String? supplierName;
  final String? receivedByName;
  final String? date;
  final String? deliveryNoteNumber;
  final double quantityOrdered;
  final double quantityDelivered;
  final String? condition;
  final String status;
  final String? description;
  final bool needsInspection;
  final bool hasInspection;
  final int projectId;
  final List<ReceivingItemDto> items;
  final List<Map<String, dynamic>> inspections;
  final OverheadsDto? overheads;

  ReceivingDetail({
    required this.id,
    required this.receivingNumber,
    required this.purchaseDocumentNumber,
    required this.projectName,
    required this.supplierName,
    required this.receivedByName,
    required this.date,
    required this.deliveryNoteNumber,
    required this.quantityOrdered,
    required this.quantityDelivered,
    required this.condition,
    required this.status,
    required this.description,
    required this.needsInspection,
    required this.hasInspection,
    required this.projectId,
    required this.items,
    required this.inspections,
    required this.overheads,
  });

  factory ReceivingDetail.fromJson(Map<String, dynamic> json) {
    final purchase = json['purchase'];
    final project = json['project'];
    final supplier = json['supplier'];
    final receivedBy = json['received_by'];
    final overheads = json['overheads'];
    return ReceivingDetail(
      id: (json['id'] as num).toInt(),
      receivingNumber:
          json['receiving_number']?.toString() ?? 'SR-${json['id']}',
      purchaseDocumentNumber:
          purchase is Map ? purchase['document_number']?.toString() : null,
      projectName: project is Map ? project['name']?.toString() : null,
      supplierName: supplier is Map ? supplier['name']?.toString() : null,
      receivedByName:
          receivedBy is Map ? receivedBy['name']?.toString() : null,
      date: json['date']?.toString(),
      deliveryNoteNumber: json['delivery_note_number']?.toString(),
      quantityOrdered: _asDouble(json['quantity_ordered']),
      quantityDelivered: _asDouble(json['quantity_delivered']),
      condition: json['condition']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      description: json['description']?.toString(),
      needsInspection: json['needs_inspection'] == true,
      hasInspection: json['has_inspection'] == true,
      projectId: (project is Map && project['id'] is num)
          ? (project['id'] as num).toInt()
          : 0,
      items: ((json['purchase_items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => ReceivingItemDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      inspections: ((json['inspections'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList(),
      overheads: overheads is Map
          ? OverheadsDto.fromJson(Map<String, dynamic>.from(overheads))
          : null,
    );
  }
}

double _asDouble(dynamic v) {
  if (v == null) return 0.0;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString()) ?? 0.0;
}
