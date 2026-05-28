import 'package:flutter/material.dart';

// ─── Shared parsing helpers ───────────────────────────────────────────────

double kpiToDouble(dynamic v) {
  if (v == null) return 0;
  if (v is num) return v.toDouble();
  if (v is String) return double.tryParse(v) ?? 0;
  return 0;
}

double? kpiToDoubleOrNull(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  if (v is String) {
    if (v.trim().isEmpty) return null;
    return double.tryParse(v);
  }
  return null;
}

int kpiToInt(dynamic v) {
  if (v == null) return 0;
  if (v is int) return v;
  if (v is num) return v.toInt();
  if (v is String) return int.tryParse(v) ?? 0;
  return 0;
}

String kpiToString(dynamic v) {
  if (v == null) return '';
  return v.toString();
}

String? kpiToStringOrNull(dynamic v) {
  if (v == null) return null;
  final s = v.toString();
  return s.isEmpty ? null : s;
}

// ─── Grade banding (mirror of server) ─────────────────────────────────────

/// Returns a human grade label for a percentage 0..100.
String kpiGradeLabel(double score) {
  if (score >= 90) return 'Excellent';
  if (score >= 80) return 'Very Good';
  if (score >= 70) return 'Good';
  if (score >= 60) return 'Average';
  if (score >= 50) return 'Poor';
  return 'Ungraded';
}

Color kpiGradeColor(double score) {
  if (score >= 90) return const Color(0xFF3BA154); // brand green
  if (score >= 80) return const Color(0xFF5BC077);
  if (score >= 70) return const Color(0xFFFECC04); // brand yellow
  if (score >= 60) return const Color(0xFFF39C12);
  if (score >= 50) return const Color(0xFFE67E22);
  return const Color(0xFF95A5A6); // grey / ungraded
}

// ─── Status helpers ───────────────────────────────────────────────────────

/// Brand-aligned color per review status code.
Color kpiStatusColor(String status) {
  switch (status) {
    case 'draft':
      return const Color(0xFF95A5A6); // grey
    case 'self_submitted':
      return const Color(0xFF2980B9); // blue
    case 'supervisor_reviewed':
      return const Color(0xFFFECC04); // amber / brand yellow
    case 'md_reviewed':
      return const Color(0xFF193340); // brand dark blue (purple-ish role)
    case 'completed':
      return const Color(0xFF3BA154); // brand green
    case 'returned':
      return const Color(0xFFE67E22); // orange
    case 'rejected':
      return const Color(0xFFE74C3C); // red
    default:
      return const Color(0xFF95A5A6);
  }
}

/// Simple named reference {id, name} used across KPI payloads.
class KpiRef {
  final int id;
  final String name;

  const KpiRef({required this.id, required this.name});

  factory KpiRef.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const KpiRef(id: 0, name: '');
    return KpiRef(
      id: kpiToInt(json['id']),
      name: kpiToString(json['name']),
    );
  }
}
