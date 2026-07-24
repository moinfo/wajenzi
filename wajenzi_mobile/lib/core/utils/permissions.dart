import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../presentation/providers/auth_provider.dart';

/// Shared permission helpers for gating UI actions.
///
/// Permission names are the exact Spatie permission strings shared with the
/// web app (e.g. 'Add Material Request', 'Approve Material Transfer').
/// Never invent new names here — if a permission doesn't exist on the web
/// side, it doesn't exist at all.
///
/// Fails closed: a user whose cached session has no permissions list sees no
/// privileged actions. The API enforces the same permissions server-side, so
/// this is a UX gate, not the security boundary.
bool hasPermission(WidgetRef ref, String permission) {
  final auth = ref.watch(authStateProvider).valueOrNull;
  return auth?.user?.permissions?.contains(permission) ?? false;
}

bool hasAnyPermission(WidgetRef ref, List<String> permissions) {
  final auth = ref.watch(authStateProvider).valueOrNull;
  final userPermissions = auth?.user?.permissions;
  if (userPermissions == null) return false;
  return permissions.any(userPermissions.contains);
}

/// Role check for approval-step actions that the web gates by role rather
/// than permission (RingleSoft steps, e.g. 'Managing Director',
/// 'Site Supervisor'). Prefer the API's `approval_flow.can_be_approved`
/// readout when available; use this only for optimistic UI gating.
bool hasRole(WidgetRef ref, String role) {
  final auth = ref.watch(authStateProvider).valueOrNull;
  return auth?.user?.roles?.contains(role) ?? false;
}
