// Plain Dart DTOs for the Material Requests procurement cluster.
//
// Hand-written `fromJson` (no codegen). snake_case JSON -> camelCase fields.
// Shapes mirror `Api/V1/MaterialRequestController::formatRequest` and
// `referenceData`.

double _asDouble(dynamic v) {
  if (v == null) return 0.0;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString()) ?? 0.0;
}

double? _asDoubleOrNull(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString());
}

int _asInt(dynamic v) {
  if (v == null) return 0;
  if (v is num) return v.toInt();
  return int.tryParse(v.toString()) ?? 0;
}

int? _asIntOrNull(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toInt();
  return int.tryParse(v.toString());
}

bool _asBool(dynamic v) {
  if (v == null) return false;
  if (v is bool) return v;
  final s = v.toString().toLowerCase();
  return s == '1' || s == 'true';
}

/// A BOQ item summary nested inside a request line item (detail view).
class MrItemBoqDto {
  final int id;
  final String itemCode;
  final String description;
  final String? unit;
  final double quantity;
  final double quantityRequested;
  final double quantityReceived;
  final double quantityRemaining;

  MrItemBoqDto({
    required this.id,
    required this.itemCode,
    required this.description,
    required this.unit,
    required this.quantity,
    required this.quantityRequested,
    required this.quantityReceived,
    required this.quantityRemaining,
  });

  factory MrItemBoqDto.fromJson(Map<String, dynamic> json) => MrItemBoqDto(
        id: _asInt(json['id']),
        itemCode: json['item_code']?.toString() ?? '',
        description: json['description']?.toString() ?? '',
        unit: json['unit']?.toString(),
        quantity: _asDouble(json['quantity']),
        quantityRequested: _asDouble(json['quantity_requested']),
        quantityReceived: _asDouble(json['quantity_received']),
        quantityRemaining: _asDouble(json['quantity_remaining']),
      );
}

/// A single line item of a material request.
class MaterialRequestItemDto {
  final int id;
  final int? boqItemId;
  final String? description;
  final String? specification;
  final double quantityRequested;
  final double? quantityApproved;
  final String? unit;
  final int sortOrder;
  final MrItemBoqDto? boqItem;

  MaterialRequestItemDto({
    required this.id,
    required this.boqItemId,
    required this.description,
    required this.specification,
    required this.quantityRequested,
    required this.quantityApproved,
    required this.unit,
    required this.sortOrder,
    required this.boqItem,
  });

  /// Preferred display label: BOQ code + description, else free-text.
  String get label {
    final code = boqItem?.itemCode;
    final desc = description ?? boqItem?.description ?? '';
    if (code != null && code.isNotEmpty) {
      return desc.isEmpty ? code : '$code — $desc';
    }
    return desc.isEmpty ? '—' : desc;
  }

  factory MaterialRequestItemDto.fromJson(Map<String, dynamic> json) {
    final boq = json['boq_item'];
    return MaterialRequestItemDto(
      id: _asInt(json['id']),
      boqItemId: _asIntOrNull(json['boq_item_id']),
      description: json['description']?.toString(),
      specification: json['specification']?.toString(),
      quantityRequested: _asDouble(json['quantity_requested']),
      quantityApproved: _asDoubleOrNull(json['quantity_approved']),
      unit: json['unit']?.toString(),
      sortOrder: _asInt(json['sort_order']),
      boqItem: boq is Map
          ? MrItemBoqDto.fromJson(Map<String, dynamic>.from(boq))
          : null,
    );
  }
}

/// One step in the RingleSoft approval flow readout.
class MrApprovalStepDto {
  final int? stepId;
  final String roleName;
  final String action;
  final String? approverName;
  final String? date;
  final String? comment;

  MrApprovalStepDto({
    required this.stepId,
    required this.roleName,
    required this.action,
    required this.approverName,
    required this.date,
    required this.comment,
  });

  factory MrApprovalStepDto.fromJson(Map<String, dynamic> json) =>
      MrApprovalStepDto(
        stepId: _asIntOrNull(json['step_id']),
        roleName: json['role_name']?.toString() ?? '',
        action: json['action']?.toString() ?? 'Pending',
        approverName: json['approver_name']?.toString(),
        date: json['date']?.toString(),
        comment: json['comment']?.toString(),
      );
}

/// Aggregated approval-flow state for a request (detail view).
class MrApprovalFlowDto {
  final String statusLabel;
  final String? nextRoleName;
  final bool isSubmitted;
  final bool isCompleted;
  final bool canBeSubmitted;
  final bool canBeApproved;
  final List<MrApprovalStepDto> steps;

  MrApprovalFlowDto({
    required this.statusLabel,
    required this.nextRoleName,
    required this.isSubmitted,
    required this.isCompleted,
    required this.canBeSubmitted,
    required this.canBeApproved,
    required this.steps,
  });

  factory MrApprovalFlowDto.fromJson(Map<String, dynamic> json) =>
      MrApprovalFlowDto(
        statusLabel: json['status_label']?.toString() ?? '',
        nextRoleName: json['next_role_name']?.toString(),
        isSubmitted: _asBool(json['is_submitted']),
        isCompleted: _asBool(json['is_completed']),
        canBeSubmitted: _asBool(json['can_be_submitted']),
        canBeApproved: _asBool(json['can_be_approved']),
        steps: ((json['steps'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => MrApprovalStepDto.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
}

/// A material request (list card = summary; detail adds items + flow).
class MaterialRequestDto {
  final int id;
  final String requestNumber;
  final int? projectId;
  final String? projectName;
  final int? requesterId;
  final String? requesterName;
  final int? approvedBy;
  final String? approverName;
  final String status;
  final String approvalStatus;
  final String priority;
  final String? requiredDate;
  final String? requestedDate;
  final String? approvedDate;
  final String? purpose;
  final int itemsCount;
  final String itemsSummary;
  final String? createdAt;

  // Detail-only fields (null / empty on list rows).
  final List<MaterialRequestItemDto> items;
  final MrApprovalFlowDto? approvalFlow;
  final String? rejectionReason;
  final bool canEditQuantities;
  final bool canDelete;
  final String? approvalPageUrl;

  MaterialRequestDto({
    required this.id,
    required this.requestNumber,
    required this.projectId,
    required this.projectName,
    required this.requesterId,
    required this.requesterName,
    required this.approvedBy,
    required this.approverName,
    required this.status,
    required this.approvalStatus,
    required this.priority,
    required this.requiredDate,
    required this.requestedDate,
    required this.approvedDate,
    required this.purpose,
    required this.itemsCount,
    required this.itemsSummary,
    required this.createdAt,
    required this.items,
    required this.approvalFlow,
    required this.rejectionReason,
    required this.canEditQuantities,
    required this.canDelete,
    required this.approvalPageUrl,
  });

  bool get isApproved => status.trim().toUpperCase() == 'APPROVED';
  bool get isPending => !isApproved;

  factory MaterialRequestDto.fromJson(Map<String, dynamic> json) {
    final flow = json['approval_flow'];
    return MaterialRequestDto(
      id: _asInt(json['id']),
      requestNumber: json['request_number']?.toString() ?? '',
      projectId: _asIntOrNull(json['project_id']),
      projectName: json['project_name']?.toString(),
      requesterId: _asIntOrNull(json['requester_id']),
      requesterName: json['requester_name']?.toString(),
      approvedBy: _asIntOrNull(json['approved_by']),
      approverName: json['approver_name']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      approvalStatus: json['approval_status']?.toString() ?? 'Pending',
      priority: json['priority']?.toString() ?? 'medium',
      requiredDate: json['required_date']?.toString(),
      requestedDate: json['requested_date']?.toString(),
      approvedDate: json['approved_date']?.toString(),
      purpose: json['purpose']?.toString(),
      itemsCount: _asInt(json['items_count']),
      itemsSummary: json['items_summary']?.toString() ?? '',
      createdAt: json['created_at']?.toString(),
      items: ((json['items'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) =>
              MaterialRequestItemDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      approvalFlow: flow is Map
          ? MrApprovalFlowDto.fromJson(Map<String, dynamic>.from(flow))
          : null,
      rejectionReason: json['rejection_reason']?.toString(),
      canEditQuantities: _asBool(json['can_edit_quantities']),
      canDelete: _asBool(json['can_delete']),
      approvalPageUrl: json['approval_page_url']?.toString(),
    );
  }
}

/// A page of material requests plus pagination metadata.
class MaterialRequestPage {
  final List<MaterialRequestDto> items;
  final int currentPage;
  final int lastPage;
  final int total;

  MaterialRequestPage({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  bool get hasMore => currentPage < lastPage;

  /// Handles both `data:{data:[...], meta:{...}}` and
  /// `data:{data:[...], current_page,...}` envelope shapes defensively.
  factory MaterialRequestPage.fromEnvelope(dynamic body) {
    final data = body is Map ? body['data'] : null;

    List rawList = const [];
    Map<String, dynamic> meta = const {};

    if (data is Map) {
      final inner = data['data'];
      if (inner is List) {
        rawList = inner;
        final m = data['meta'];
        if (m is Map) {
          meta = Map<String, dynamic>.from(m);
        } else {
          meta = Map<String, dynamic>.from(data);
        }
      } else {
        rawList = const [];
      }
    } else if (data is List) {
      rawList = data;
    }

    final items = rawList
        .whereType<Map>()
        .map((e) => MaterialRequestDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();

    final current = _asInt(meta['current_page']);
    final last = _asInt(meta['last_page']);
    return MaterialRequestPage(
      items: items,
      currentPage: current == 0 ? 1 : current,
      lastPage: last == 0 ? 1 : last,
      total: _asInt(meta['total']),
    );
  }
}

/// Project option for the create form picker.
class MrProjectOption {
  final int id;
  final String name;
  final String? documentNumber;

  MrProjectOption({
    required this.id,
    required this.name,
    required this.documentNumber,
  });

  factory MrProjectOption.fromJson(Map<String, dynamic> json) => MrProjectOption(
        id: _asInt(json['id']),
        name: json['name']?.toString() ?? '',
        documentNumber: json['document_number']?.toString(),
      );
}

/// A BOQ item selectable in the create form (with available qty + pending flag).
class MrBoqPickerItem {
  final int id;
  final String itemCode;
  final String description;
  final String? unit;
  final String? specification;
  final double quantity;
  final double quantityRequested;
  final double availableQuantity;
  final bool hasPendingRequest;

  MrBoqPickerItem({
    required this.id,
    required this.itemCode,
    required this.description,
    required this.unit,
    required this.specification,
    required this.quantity,
    required this.quantityRequested,
    required this.availableQuantity,
    required this.hasPendingRequest,
  });

  factory MrBoqPickerItem.fromJson(Map<String, dynamic> json) => MrBoqPickerItem(
        id: _asInt(json['id']),
        itemCode: json['item_code']?.toString() ?? '',
        description: json['description']?.toString() ?? '',
        unit: json['unit']?.toString(),
        specification: json['specification']?.toString(),
        quantity: _asDouble(json['quantity']),
        quantityRequested: _asDouble(json['quantity_requested']),
        availableQuantity: _asDouble(json['available_quantity']),
        hasPendingRequest: _asBool(json['has_pending_request']),
      );
}

/// Reference data for the create form.
class MrReferenceData {
  final List<MrProjectOption> projects;
  final List<String> priorities;
  final List<MrBoqPickerItem> boqItems;

  MrReferenceData({
    required this.projects,
    required this.priorities,
    required this.boqItems,
  });

  factory MrReferenceData.fromJson(Map<String, dynamic> json) => MrReferenceData(
        projects: ((json['projects'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => MrProjectOption.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
        priorities: ((json['priorities'] as List?) ?? const [])
            .map((e) => e.toString())
            .toList(),
        boqItems: ((json['boq_items'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => MrBoqPickerItem.fromJson(Map<String, dynamic>.from(e)))
            .toList(),
      );
}
