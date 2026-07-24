// Plain Dart DTOs for the Material Inspections feature.
//
// Hand-written `fromJson` (no codegen). snake_case JSON -> camelCase fields.
// Mirrors the envelope produced by
// `App\Http\Controllers\Api\V1\MaterialInspectionController`.

/// The six fixed inspection criteria keys, in display order. Used as a
/// fallback ordering when the server does not echo `criteria_labels`.
const List<String> kInspectionCriteriaKeys = [
  'packaging_intact',
  'quantity_correct',
  'specification_match',
  'no_visible_defects',
  'proper_labeling',
  'storage_suitable',
];

/// Human labels for the six fixed criteria (same copy as the web form).
const Map<String, String> kInspectionCriteriaLabels = {
  'packaging_intact': 'Packaging is intact and undamaged',
  'quantity_correct': 'Quantity matches delivery note',
  'specification_match': 'Specifications match order requirements',
  'no_visible_defects': 'No visible defects or damage',
  'proper_labeling': 'Proper labeling and documentation',
  'storage_suitable': 'Materials are suitable for storage',
};

class InspectionReceivingSummary {
  final int id;
  final String? supplierName;
  final String? receivingNumber;
  final String? deliveryNoteNumber;
  final String? status;
  final String? purchaseNumber;

  InspectionReceivingSummary({
    required this.id,
    this.supplierName,
    this.receivingNumber,
    this.deliveryNoteNumber,
    this.status,
    this.purchaseNumber,
  });

  factory InspectionReceivingSummary.fromJson(Map<String, dynamic> json) {
    return InspectionReceivingSummary(
      id: (json['id'] as num).toInt(),
      supplierName: json['supplier_name']?.toString(),
      receivingNumber: json['receiving_number']?.toString(),
      deliveryNoteNumber: json['delivery_note_number']?.toString(),
      status: json['status']?.toString(),
      purchaseNumber: json['purchase_number']?.toString(),
    );
  }
}

class InspectionBoqItem {
  final int id;
  final String? description;
  final String? itemCode;
  final String? unit;

  InspectionBoqItem({
    required this.id,
    this.description,
    this.itemCode,
    this.unit,
  });

  factory InspectionBoqItem.fromJson(Map<String, dynamic> json) {
    return InspectionBoqItem(
      id: (json['id'] as num).toInt(),
      description: json['description']?.toString(),
      itemCode: json['item_code']?.toString(),
      unit: json['unit']?.toString(),
    );
  }
}

class InspectionApprovalStep {
  final int? stepId;
  final String roleName;
  final String? stepAction;
  final String action;
  final String? approverName;
  final String? date;
  final String? comment;

  InspectionApprovalStep({
    required this.stepId,
    required this.roleName,
    required this.stepAction,
    required this.action,
    required this.approverName,
    required this.date,
    required this.comment,
  });

  factory InspectionApprovalStep.fromJson(Map<String, dynamic> json) {
    return InspectionApprovalStep(
      stepId: json['step_id'] == null
          ? null
          : (json['step_id'] as num).toInt(),
      roleName: json['role_name']?.toString() ?? '',
      stepAction: json['step_action']?.toString(),
      action: json['action']?.toString() ?? 'Pending',
      approverName: json['approver_name']?.toString(),
      date: json['date']?.toString(),
      comment: json['comment']?.toString(),
    );
  }
}

class InspectionApprovalFlow {
  final String? statusLabel;
  final String? nextRoleName;
  final String? nextAction;
  final bool isSubmitted;
  final bool isCompleted;
  final bool canBeSubmitted;
  final bool canBeApproved;
  final List<InspectionApprovalStep> steps;

  InspectionApprovalFlow({
    this.statusLabel,
    this.nextRoleName,
    this.nextAction,
    this.isSubmitted = false,
    this.isCompleted = false,
    this.canBeSubmitted = false,
    this.canBeApproved = false,
    this.steps = const [],
  });

  factory InspectionApprovalFlow.fromJson(Map<String, dynamic> json) {
    return InspectionApprovalFlow(
      statusLabel: json['status_label']?.toString(),
      nextRoleName: json['next_role_name']?.toString(),
      nextAction: json['next_action']?.toString(),
      isSubmitted: json['is_submitted'] == true,
      isCompleted: json['is_completed'] == true,
      canBeSubmitted: json['can_be_submitted'] == true,
      canBeApproved: json['can_be_approved'] == true,
      steps: ((json['steps'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) =>
              InspectionApprovalStep.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class InspectionDto {
  final int id;
  final String? inspectionNumber;
  final int? supplierReceivingId;
  final InspectionReceivingSummary? supplierReceiving;
  final int? projectId;
  final String? projectName;
  final InspectionBoqItem? boqItem;
  final int? inspectorId;
  final String? inspectorName;
  final String? inspectionDate;
  final double quantityDelivered;
  final double quantityAccepted;
  final double quantityRejected;
  final double acceptanceRate;
  final String? status;
  final String? approvalStatus;
  final String? overallCondition;
  final String? overallResult;
  final String? rejectionReason;
  final String? notes;
  final bool stockUpdated;
  final String? createdAt;

  // Full-detail extras (present only on show/store/approve responses).
  final String? verifierName;
  final Map<String, bool> criteriaChecklist;
  final Map<String, String> criteriaLabels;
  final String? stockUpdatedAt;
  final bool canUpdateStock;
  final InspectionApprovalFlow? approvalFlow;

  InspectionDto({
    required this.id,
    this.inspectionNumber,
    this.supplierReceivingId,
    this.supplierReceiving,
    this.projectId,
    this.projectName,
    this.boqItem,
    this.inspectorId,
    this.inspectorName,
    this.inspectionDate,
    this.quantityDelivered = 0.0,
    this.quantityAccepted = 0.0,
    this.quantityRejected = 0.0,
    this.acceptanceRate = 0.0,
    this.status,
    this.approvalStatus,
    this.overallCondition,
    this.overallResult,
    this.rejectionReason,
    this.notes,
    this.stockUpdated = false,
    this.createdAt,
    this.verifierName,
    this.criteriaChecklist = const {},
    this.criteriaLabels = const {},
    this.stockUpdatedAt,
    this.canUpdateStock = false,
    this.approvalFlow,
  });

  factory InspectionDto.fromJson(Map<String, dynamic> json) {
    final receivingRaw = json['supplier_receiving'];
    final boqRaw = json['boq_item'];
    final flowRaw = json['approval_flow'];

    final checklist = <String, bool>{};
    final rawChecklist = json['criteria_checklist'];
    if (rawChecklist is Map) {
      rawChecklist.forEach((k, v) {
        checklist[k.toString()] = v == true || v == 1 || v == '1' || v == 'true';
      });
    }

    final labels = <String, String>{};
    final rawLabels = json['criteria_labels'];
    if (rawLabels is Map) {
      rawLabels.forEach((k, v) => labels[k.toString()] = v?.toString() ?? '');
    }

    return InspectionDto(
      id: (json['id'] as num).toInt(),
      inspectionNumber: json['inspection_number']?.toString(),
      supplierReceivingId: json['supplier_receiving_id'] == null
          ? null
          : (json['supplier_receiving_id'] as num).toInt(),
      supplierReceiving: receivingRaw is Map
          ? InspectionReceivingSummary.fromJson(
              Map<String, dynamic>.from(receivingRaw))
          : null,
      projectId: json['project_id'] == null
          ? null
          : (json['project_id'] as num).toInt(),
      projectName: json['project_name']?.toString(),
      boqItem: boqRaw is Map
          ? InspectionBoqItem.fromJson(Map<String, dynamic>.from(boqRaw))
          : null,
      inspectorId: json['inspector_id'] == null
          ? null
          : (json['inspector_id'] as num).toInt(),
      inspectorName: json['inspector_name']?.toString(),
      inspectionDate: json['inspection_date']?.toString(),
      quantityDelivered: _asDouble(json['quantity_delivered']),
      quantityAccepted: _asDouble(json['quantity_accepted']),
      quantityRejected: _asDouble(json['quantity_rejected']),
      acceptanceRate: _asDouble(json['acceptance_rate']),
      status: json['status']?.toString(),
      approvalStatus: json['approval_status']?.toString(),
      overallCondition: json['overall_condition']?.toString(),
      overallResult: json['overall_result']?.toString(),
      rejectionReason: json['rejection_reason']?.toString(),
      notes: json['notes']?.toString(),
      stockUpdated: json['stock_updated'] == true,
      createdAt: json['created_at']?.toString(),
      verifierName: json['verifier_name']?.toString(),
      criteriaChecklist: checklist,
      criteriaLabels: labels,
      stockUpdatedAt: json['stock_updated_at']?.toString(),
      canUpdateStock: json['can_update_stock'] == true,
      approvalFlow: flowRaw is Map
          ? InspectionApprovalFlow.fromJson(Map<String, dynamic>.from(flowRaw))
          : null,
    );
  }
}

class PendingReceivingDto {
  final int id;
  final String? receivingNumber;
  final String? supplierName;
  final String? deliveryDate;
  final double quantityDelivered;
  final String? condition;
  final String? purchaseNumber;
  final String? projectName;

  PendingReceivingDto({
    required this.id,
    this.receivingNumber,
    this.supplierName,
    this.deliveryDate,
    this.quantityDelivered = 0.0,
    this.condition,
    this.purchaseNumber,
    this.projectName,
  });

  factory PendingReceivingDto.fromJson(Map<String, dynamic> json) {
    return PendingReceivingDto(
      id: (json['id'] as num).toInt(),
      receivingNumber: json['receiving_number']?.toString(),
      supplierName: json['supplier_name']?.toString(),
      deliveryDate: json['delivery_date']?.toString(),
      quantityDelivered: _asDouble(json['quantity_delivered']),
      condition: json['condition']?.toString(),
      purchaseNumber: json['purchase_number']?.toString(),
      projectName: json['project_name']?.toString(),
    );
  }
}

/// Result of the paginated list endpoint (inspections + the to-inspect queue).
class InspectionListResult {
  final List<InspectionDto> inspections;
  final List<PendingReceivingDto> pendingReceivings;
  final int currentPage;
  final int lastPage;
  final int total;

  InspectionListResult({
    required this.inspections,
    required this.pendingReceivings,
    this.currentPage = 1,
    this.lastPage = 1,
    this.total = 0,
  });
}

/// Receiving summary shown at the top of the create-inspection form.
class InspectionCreateReceiving {
  final int id;
  final String? receivingNumber;
  final String? deliveryNoteNumber;
  final String? date;
  final String? condition;
  final String? status;
  final double quantityOrdered;
  final double quantityDelivered;
  final int? projectId;
  final String? projectName;
  final String? supplierName;
  final String? purchaseNumber;

  InspectionCreateReceiving({
    required this.id,
    this.receivingNumber,
    this.deliveryNoteNumber,
    this.date,
    this.condition,
    this.status,
    this.quantityOrdered = 0.0,
    this.quantityDelivered = 0.0,
    this.projectId,
    this.projectName,
    this.supplierName,
    this.purchaseNumber,
  });

  factory InspectionCreateReceiving.fromJson(Map<String, dynamic> json) {
    return InspectionCreateReceiving(
      id: (json['id'] as num).toInt(),
      receivingNumber: json['receiving_number']?.toString(),
      deliveryNoteNumber: json['delivery_note_number']?.toString(),
      date: json['date']?.toString(),
      condition: json['condition']?.toString(),
      status: json['status']?.toString(),
      quantityOrdered: _asDouble(json['quantity_ordered']),
      quantityDelivered: _asDouble(json['quantity_delivered']),
      projectId: json['project_id'] == null
          ? null
          : (json['project_id'] as num).toInt(),
      projectName: json['project_name']?.toString(),
      supplierName: json['supplier_name']?.toString(),
      purchaseNumber: json['purchase_number']?.toString(),
    );
  }
}

/// Bundle returned by the create-data endpoint.
class InspectionCreateData {
  final InspectionCreateReceiving receiving;
  final int? existingInspectionId;
  final List<InspectionBoqItem> boqItems;

  /// Ordered map of criteria key -> label (insertion order preserved).
  final Map<String, String> criteriaChecklist;

  InspectionCreateData({
    required this.receiving,
    required this.existingInspectionId,
    required this.boqItems,
    required this.criteriaChecklist,
  });

  factory InspectionCreateData.fromJson(Map<String, dynamic> json) {
    final checklist = <String, String>{};
    final rawChecklist = json['criteria_checklist'];
    if (rawChecklist is Map) {
      rawChecklist.forEach(
          (k, v) => checklist[k.toString()] = v?.toString() ?? '');
    }
    if (checklist.isEmpty) {
      checklist.addAll(kInspectionCriteriaLabels);
    }

    return InspectionCreateData(
      receiving: InspectionCreateReceiving.fromJson(
          Map<String, dynamic>.from(json['receiving'] as Map)),
      existingInspectionId: json['existing_inspection_id'] == null
          ? null
          : (json['existing_inspection_id'] as num).toInt(),
      boqItems: ((json['boq_items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => InspectionBoqItem.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      criteriaChecklist: checklist,
    );
  }
}

double _asDouble(dynamic v) {
  if (v == null) return 0.0;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString()) ?? 0.0;
}
