// Plain Dart DTOs for the Site Paylog module (Daily Payments, Payment
// Requests, Payment Channels, Daily/Monthly reports). Hand-written fromJson
// (no codegen). snake_case JSON from the API -> camelCase fields. Defensive
// guards throughout so a missing/null field never throws.
//
// Server model reference: SitePaymentRequest (header) hasMany SitePaylog
// (lines) + SitePaymentRequestFile (docs). Status surfaced to the UI is the
// server-computed display_status + status_color token (do NOT re-derive from
// the raw `status` column). Approval is RingleSoft role-based, so the server
// ships can_* flags and a next_step block — the client only renders them.

// ── shared parse helpers ──────────────────────────────────────────────────

double _asDouble(dynamic v) {
  if (v == null) return 0.0;
  if (v is num) return v.toDouble();
  // Strip thousands separators that the API might echo back.
  return double.tryParse(v.toString().replaceAll(',', '')) ?? 0.0;
}

int _asInt(dynamic v) {
  if (v == null) return 0;
  if (v is int) return v;
  if (v is num) return v.toInt();
  return int.tryParse(v.toString()) ?? 0;
}

bool _asBool(dynamic v) {
  if (v is bool) return v;
  if (v is num) return v != 0;
  final s = v?.toString().toLowerCase();
  return s == 'true' || s == '1';
}

String? _str(dynamic v) {
  if (v == null) return null;
  final s = v.toString();
  return s.isEmpty ? null : s;
}

/// Extracts a display name from either a nested `{id, name}` object or a bare
/// scalar. Falls back through common name keys (name / project_name / title).
String? _nameOf(dynamic v) {
  if (v is Map) {
    return _str(v['name']) ??
        _str(v['project_name']) ??
        _str(v['title']) ??
        _str(v['full_name']);
  }
  return _str(v);
}

int? _idOf(dynamic v) {
  if (v is Map && v['id'] != null) return _asInt(v['id']);
  return null;
}

/// Picks the first non-null numeric value across candidate keys.
double _pickDouble(Map json, List<String> keys) {
  for (final k in keys) {
    if (json[k] != null) return _asDouble(json[k]);
  }
  return 0.0;
}

List<Map<String, dynamic>> _mapList(dynamic raw) {
  if (raw is List) {
    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }
  return const [];
}

// ── small value objects ────────────────────────────────────────────────────

class NamedRefDto {
  final int id;
  final String name;

  NamedRefDto({required this.id, required this.name});

  factory NamedRefDto.fromJson(Map<String, dynamic> json) => NamedRefDto(
        id: _asInt(json['id']),
        name: _nameOf(json) ?? 'Item ${json['id'] ?? ''}'.trim(),
      );
}

/// A {code, label} pair for dropdowns (categories, statuses).
class CodeLabelDto {
  final String code;
  final String label;

  CodeLabelDto({required this.code, required this.label});
}

/// The pending RingleSoft step that the current user may or may not act on.
class NextStepDto {
  final int order;
  final String role;
  final String action; // VERIFY | APPROVE

  NextStepDto({required this.order, required this.role, required this.action});

  factory NextStepDto.fromJson(Map<String, dynamic> json) => NextStepDto(
        order: _asInt(json['order']),
        role: json['role']?.toString() ?? '',
        action: (json['action']?.toString() ?? '').toUpperCase(),
      );

  /// Button label — "Verify" for step 1 (Procurement), "Approve" for MD.
  String get actionLabel {
    switch (action) {
      case 'VERIFY':
        return 'Verify';
      case 'APPROVE':
        return 'Approve';
      default:
        return action.isEmpty
            ? 'Approve'
            : action[0].toUpperCase() + action.substring(1).toLowerCase();
    }
  }
}

// ── payment channels ────────────────────────────────────────────────────────

class PaymentChannelDto {
  final int id;
  final String name;
  final String type; // bank | mobile | cash
  final bool isActive;

  PaymentChannelDto({
    required this.id,
    required this.name,
    required this.type,
    required this.isActive,
  });

  factory PaymentChannelDto.fromJson(Map<String, dynamic> json) =>
      PaymentChannelDto(
        id: _asInt(json['id']),
        name: json['name']?.toString() ?? '',
        type: json['type']?.toString() ?? '',
        isActive: _asBool(json['is_active']),
      );

  String get typeLabel =>
      type.isEmpty ? '-' : type[0].toUpperCase() + type.substring(1);
}

// ── paylog line item (SitePaylog) ───────────────────────────────────────────

class SitePaylogLineDto {
  final int id;
  final String payeeName;
  final String? reason;
  final String category; // material | labour
  final String? channelName;
  final String? accountName;
  final double amount;
  final String? paymentDate;
  final String? status;
  final String? loggedByName;

  SitePaylogLineDto({
    required this.id,
    required this.payeeName,
    required this.reason,
    required this.category,
    required this.channelName,
    required this.accountName,
    required this.amount,
    required this.paymentDate,
    required this.status,
    required this.loggedByName,
  });

  factory SitePaylogLineDto.fromJson(Map<String, dynamic> json) =>
      SitePaylogLineDto(
        id: _asInt(json['id']),
        payeeName: json['payee_name']?.toString() ?? '',
        reason: _str(json['reason']),
        category: (json['category']?.toString() ?? 'material').toLowerCase(),
        channelName: _nameOf(json['channel']) ??
            _str(json['channel_name']) ??
            _nameOf(json['payment_channel']),
        accountName: _str(json['account_name']),
        amount: _asDouble(json['amount']),
        paymentDate: _str(json['payment_date']),
        status: _str(json['status']),
        loggedByName: _nameOf(json['creator']) ??
            _nameOf(json['created_by']) ??
            _str(json['logged_by']),
      );

  bool get isLabour => category == 'labour';
  String get categoryLabel => isLabour ? 'Labour' : 'Material';
}

// ── payment request supporting document ─────────────────────────────────────

class SitePaymentRequestFileDto {
  final int id;
  final String filePath; // e.g. /storage/uploads/site_payment_requests/..
  final String? originalName;

  SitePaymentRequestFileDto({
    required this.id,
    required this.filePath,
    required this.originalName,
  });

  factory SitePaymentRequestFileDto.fromJson(Map<String, dynamic> json) =>
      SitePaymentRequestFileDto(
        id: _asInt(json['id']),
        filePath: json['file_path']?.toString() ?? '',
        originalName: _str(json['original_name']),
      );

  String get displayName {
    if (originalName != null && originalName!.isNotEmpty) return originalName!;
    final parts = filePath.split('/');
    return parts.isEmpty ? 'Document' : parts.last;
  }
}

// ── payment request (header) — used in both list and detail ─────────────────

class SitePaymentRequestDto {
  final int id;
  final String requestNumber;
  final String status; // raw column: PENDING|APPROVED|PAID|REJECTED
  final String displayStatus; // server displayStatus()
  final String statusColor; // token: success|primary|danger|warning|info
  final String? paymentDate;
  final double totalAmount;

  // List-screen aggregates (may be absent on detail).
  final double materialTotal;
  final double labourTotal;
  final int linesCount;

  // Related entities (flattened names + ids where available).
  final int? siteId;
  final String? siteName;
  final int? projectId;
  final String? projectName;
  final String? creatorName;

  // Detail-only collections.
  final List<SitePaylogLineDto> lines;
  final List<SitePaymentRequestFileDto> files;

  // Approval contract (server-computed).
  final NextStepDto? nextStep;
  final bool canSubmit;
  final bool canApprove;
  final bool canReject;
  final bool canRecordPayment;

  // Finance record-payment fields (present once PAID).
  final String? paymentReference;
  final String? paidDate;
  final String? paymentNote;
  final String? paymentSlipPath;
  final String? paidByName;

  SitePaymentRequestDto({
    required this.id,
    required this.requestNumber,
    required this.status,
    required this.displayStatus,
    required this.statusColor,
    required this.paymentDate,
    required this.totalAmount,
    required this.materialTotal,
    required this.labourTotal,
    required this.linesCount,
    required this.siteId,
    required this.siteName,
    required this.projectId,
    required this.projectName,
    required this.creatorName,
    required this.lines,
    required this.files,
    required this.nextStep,
    required this.canSubmit,
    required this.canApprove,
    required this.canReject,
    required this.canRecordPayment,
    required this.paymentReference,
    required this.paidDate,
    required this.paymentNote,
    required this.paymentSlipPath,
    required this.paidByName,
  });

  factory SitePaymentRequestDto.fromJson(Map<String, dynamic> json) {
    final site = json['site'];
    final project = json['project'];
    final lines = _mapList(json['lines'])
        .map(SitePaylogLineDto.fromJson)
        .toList();
    final files = _mapList(json['files'])
        .map(SitePaymentRequestFileDto.fromJson)
        .toList();

    final rawStatus =
        (json['status']?.toString() ?? 'PENDING').toUpperCase();
    final nextStepRaw = json['next_step'];

    return SitePaymentRequestDto(
      id: _asInt(json['id']),
      requestNumber: json['request_number']?.toString() ??
          json['document_number']?.toString() ??
          'PAY-${json['id']}',
      status: rawStatus,
      displayStatus: json['display_status']?.toString() ?? rawStatus,
      statusColor: json['status_color']?.toString() ?? 'info',
      paymentDate: _str(json['payment_date']),
      totalAmount: _asDouble(json['total_amount']),
      materialTotal: _pickDouble(json, ['material_total', 'material']),
      labourTotal: _pickDouble(json, ['labour_total', 'labour']),
      linesCount: json['lines_count'] != null
          ? _asInt(json['lines_count'])
          : lines.length,
      siteId: _idOf(site) ??
          (json['site_id'] != null ? _asInt(json['site_id']) : null),
      siteName: _nameOf(site) ?? _str(json['site_name']),
      projectId: _idOf(project) ??
          (json['project_id'] != null ? _asInt(json['project_id']) : null),
      projectName: _nameOf(project) ?? _str(json['project_name']),
      creatorName: _nameOf(json['creator']) ??
          _nameOf(json['user']) ??
          _str(json['created_by_name']),
      lines: lines,
      files: files,
      nextStep: nextStepRaw is Map
          ? NextStepDto.fromJson(Map<String, dynamic>.from(nextStepRaw))
          : null,
      canSubmit: _asBool(json['can_submit']),
      canApprove: _asBool(json['can_approve']),
      canReject: _asBool(json['can_reject']),
      canRecordPayment: _asBool(json['can_record_payment']),
      paymentReference: _str(json['payment_reference']),
      paidDate: _str(json['paid_date']),
      paymentNote: _str(json['payment_note']),
      paymentSlipPath: _str(json['payment_slip']),
      paidByName: _nameOf(json['payer']) ?? _str(json['paid_by_name']),
    );
  }

  bool get isPaid => status == 'PAID';
}

// ── paginated result (payment requests list) ────────────────────────────────

class PaginatedResult<T> {
  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  PaginatedResult({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  bool get hasMore => currentPage < lastPage;
}

// ── daily summary (Daily Payments screen) ───────────────────────────────────

class DailySummaryDto {
  final List<SitePaymentRequestDto> requests;
  final double material;
  final double labour;
  final double all;

  DailySummaryDto({
    required this.requests,
    required this.material,
    required this.labour,
    required this.all,
  });

  factory DailySummaryDto.fromJson(Map<String, dynamic> json) {
    final requests = _mapList(json['requests'])
        .map(SitePaymentRequestDto.fromJson)
        .toList();
    final totals = json['totals'] is Map
        ? Map<String, dynamic>.from(json['totals'] as Map)
        : json;
    final material = _pickDouble(totals, ['material', 'material_total']);
    final labour = _pickDouble(totals, ['labour', 'labour_total']);
    final all = totals['all'] != null || totals['total'] != null
        ? _pickDouble(totals, ['all', 'total'])
        : material + labour;
    return DailySummaryDto(
      requests: requests,
      material: material,
      labour: labour,
      all: all,
    );
  }
}

// ── daily report (line-level, regardless of approval state) ─────────────────

class DailyReportDto {
  final List<SitePaylogLineDto> rows;
  final double material;
  final double labour;
  final double all;

  DailyReportDto({
    required this.rows,
    required this.material,
    required this.labour,
    required this.all,
  });

  factory DailyReportDto.fromJson(Map<String, dynamic> json) {
    final rows = _mapList(json['rows'] ?? json['lines'])
        .map(SitePaylogLineDto.fromJson)
        .toList();
    final totals = json['totals'] is Map
        ? Map<String, dynamic>.from(json['totals'] as Map)
        : json;
    final material = _pickDouble(totals, ['material', 'material_total']);
    final labour = _pickDouble(totals, ['labour', 'labour_total']);
    final all = totals['all'] != null || totals['total'] != null
        ? _pickDouble(totals, ['all', 'total'])
        : material + labour;
    return DailyReportDto(
      rows: rows,
      material: material,
      labour: labour,
      all: all,
    );
  }
}

// ── monthly report (summary + ledger) ───────────────────────────────────────

class MonthlySummaryRowDto {
  final String category;
  final int count;
  final double total;

  MonthlySummaryRowDto({
    required this.category,
    required this.count,
    required this.total,
  });

  factory MonthlySummaryRowDto.fromJson(Map<String, dynamic> json) =>
      MonthlySummaryRowDto(
        category: json['category']?.toString() ?? '',
        count: _asInt(json['count']),
        total: _asDouble(json['total']),
      );
}

class MonthlyReportDto {
  final List<MonthlySummaryRowDto> summary;
  final List<SitePaylogLineDto> ledger;
  final double grandTotal;
  final String? month;

  MonthlyReportDto({
    required this.summary,
    required this.ledger,
    required this.grandTotal,
    required this.month,
  });

  factory MonthlyReportDto.fromJson(Map<String, dynamic> json) {
    final summary = _mapList(json['summary'])
        .map(MonthlySummaryRowDto.fromJson)
        .toList();
    final ledgerRaw = json['ledger'] ?? json['lines'] ?? json['rows'];
    final ledger =
        _mapList(ledgerRaw).map(SitePaylogLineDto.fromJson).toList();
    return MonthlyReportDto(
      summary: summary,
      ledger: ledger,
      grandTotal: _pickDouble(json, ['grand_total', 'grandTotal', 'total']),
      month: _str(json['month']),
    );
  }
}

// ── reference data (sites, projects, channels, categories, statuses) ────────

class PaylogReferenceData {
  final List<NamedRefDto> sites;
  final List<NamedRefDto> projects;
  final List<PaymentChannelDto> channels; // active channels for entry
  final List<CodeLabelDto> categories;
  final List<CodeLabelDto> statuses;

  PaylogReferenceData({
    required this.sites,
    required this.projects,
    required this.channels,
    required this.categories,
    required this.statuses,
  });

  factory PaylogReferenceData.fromJson(Map<String, dynamic> json) =>
      PaylogReferenceData(
        sites: _mapList(json['sites']).map(NamedRefDto.fromJson).toList(),
        projects:
            _mapList(json['projects']).map(NamedRefDto.fromJson).toList(),
        channels: _mapList(json['channels'])
            .map(PaymentChannelDto.fromJson)
            .toList(),
        categories: _parseCodeLabels(json['categories']),
        statuses: _parseCodeLabels(json['statuses']),
      );
}

/// Accepts either a `{code: label}` map or a list of `{code|value, label|name}`
/// objects (or a list of bare strings) and normalises to [CodeLabelDto]s.
List<CodeLabelDto> _parseCodeLabels(dynamic raw) {
  final out = <CodeLabelDto>[];
  if (raw is Map) {
    raw.forEach((k, v) {
      out.add(CodeLabelDto(
        code: k.toString(),
        label: v?.toString() ?? k.toString(),
      ));
    });
  } else if (raw is List) {
    for (final e in raw) {
      if (e is Map) {
        final code = (e['code'] ?? e['value'] ?? e['id'] ?? e['name'])
                ?.toString() ??
            '';
        final label =
            (e['label'] ?? e['name'] ?? e['title'] ?? code)?.toString() ??
                code;
        out.add(CodeLabelDto(code: code, label: label));
      } else if (e != null) {
        out.add(CodeLabelDto(code: e.toString(), label: e.toString()));
      }
    }
  }
  return out;
}
