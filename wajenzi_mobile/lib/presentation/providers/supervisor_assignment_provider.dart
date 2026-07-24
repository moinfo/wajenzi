import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/supervisor_assignment_api.dart';

/// Loads the KPI reviewer matrix and holds pending (unsaved) edits.
final supervisorAssignmentProvider = StateNotifierProvider<
    SupervisorAssignmentNotifier,
    AsyncValue<SupervisorAssignmentData>>((ref) {
  return SupervisorAssignmentNotifier(
      ref.watch(supervisorAssignmentApiProvider));
});

class SupervisorAssignmentNotifier
    extends StateNotifier<AsyncValue<SupervisorAssignmentData>> {
  final SupervisorAssignmentApi _api;

  /// userId → supervisorId (null = explicitly unassigned) for rows the user
  /// has changed but not yet saved.
  final Map<int, int?> _pending = {};

  bool _saving = false;

  SupervisorAssignmentNotifier(this._api)
      : super(const AsyncValue.loading()) {
    load();
  }

  bool get saving => _saving;

  bool get hasPendingChanges => _pending.isNotEmpty;

  Future<void> load() async {
    state = const AsyncValue.loading();
    _pending.clear();
    try {
      final data = await _api.fetchAssignments();
      if (mounted) state = AsyncValue.data(data);
    } catch (e, st) {
      if (mounted) state = AsyncValue.error(e, st);
    }
  }

  Future<void> refresh() => load();

  /// The currently-displayed supervisor for [userId] — the pending edit if the
  /// row was touched, otherwise the persisted value.
  int? currentSupervisorFor(int userId, int? persisted) {
    if (_pending.containsKey(userId)) return _pending[userId];
    return persisted;
  }

  /// Record a pending change. If it matches the persisted value the entry is
  /// dropped so it no longer counts as dirty.
  void setSupervisor(int userId, int? supervisorId, int? persisted) {
    if (supervisorId == persisted) {
      _pending.remove(userId);
    } else {
      _pending[userId] = supervisorId;
    }
    // Nudge listeners so the dirty/badge state re-renders. Reuse current data.
    final current = state;
    if (current is AsyncData<SupervisorAssignmentData>) {
      state = AsyncValue.data(current.value);
    }
  }

  /// Persist all pending edits. Returns the changed count, or throws.
  Future<int> save() async {
    if (_pending.isEmpty) return 0;
    _saving = true;
    _touch();
    try {
      final changed = await _api.saveAssignments(Map<int, int?>.from(_pending));
      _saving = false;
      // Reload to reflect persisted supervisor names + refreshed stats.
      await load();
      return changed;
    } catch (e) {
      _saving = false;
      _touch();
      rethrow;
    }
  }

  void _touch() {
    final current = state;
    if (current is AsyncData<SupervisorAssignmentData>) {
      state = AsyncValue.data(current.value);
    }
  }
}
