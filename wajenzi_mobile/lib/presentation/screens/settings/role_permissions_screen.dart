import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

class _RolePermissionsData {
  final String roleName;
  final List<Map<String, dynamic>> permissions;
  final Set<int> assignedIds;

  const _RolePermissionsData({
    required this.roleName,
    required this.permissions,
    required this.assignedIds,
  });
}

final _rolePermissionsProvider = FutureProvider.autoDispose
    .family<_RolePermissionsData, int>((ref, roleId) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/roles/$roleId/permissions');
      final root = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final data = Map<String, dynamic>.from(root['data'] as Map? ?? const {});
      final role = Map<String, dynamic>.from(data['role'] as Map? ?? const {});
      final permissions = (data['permissions'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
      final assigned = (data['assigned_ids'] as List? ?? const [])
          .map((id) => int.tryParse(id.toString()))
          .whereType<int>()
          .toSet();
      return _RolePermissionsData(
        roleName: role['name']?.toString() ?? '',
        permissions: permissions,
        assignedIds: assigned,
      );
    });

/// Mirrors the web "Manage Permissions" page: assigned / available lists with
/// search, a type filter, per-item add/remove and bulk actions.
class RolePermissionsScreen extends ConsumerStatefulWidget {
  final int roleId;
  final String? roleName;

  const RolePermissionsScreen({super.key, required this.roleId, this.roleName});

  @override
  ConsumerState<RolePermissionsScreen> createState() =>
      _RolePermissionsScreenState();
}

class _RolePermissionsScreenState extends ConsumerState<RolePermissionsScreen> {
  final _searchController = TextEditingController();
  Set<int>? _selected;
  String _query = '';
  String _type = '';
  bool _showAssigned = true;
  bool _saving = false;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final asyncData = ref.watch(_rolePermissionsProvider(widget.roleId));

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.roleName == null
              ? tr('Manage Permissions', 'إدارة الصلاحيات')
              : '${tr('Permissions', 'الصلاحيات')}: ${widget.roleName}',
        ),
      ),
      body: asyncData.when(
        loading: () => LoadingWidget(
          message: tr('Loading permissions...', 'جاري تحميل الصلاحيات...'),
        ),
        error: (error, _) => ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 48),
            const Icon(Icons.error_outline, size: 56, color: AppColors.error),
            const SizedBox(height: 12),
            Text(vatErrorMessage(error), textAlign: TextAlign.center),
          ],
        ),
        data: (data) {
          final selected = _selected ??= {...data.assignedIds};
          final types =
              data.permissions
                  .map((p) => p['permission_type']?.toString() ?? '')
                  .where((t) => t.isNotEmpty)
                  .toSet()
                  .toList()
                ..sort();

          final visible = data.permissions.where((p) {
            final id = p['id'] as int;
            if (selected.contains(id) != _showAssigned) return false;
            if (_type.isNotEmpty && p['permission_type'] != _type) return false;
            return _query.isEmpty ||
                (p['name']?.toString().toLowerCase().contains(_query) ??
                    false);
          }).toList();

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: tr(
                      'Search permissions...',
                      'ابحث عن الصلاحيات...',
                    ),
                    prefixIcon: const Icon(Icons.search),
                    border: const OutlineInputBorder(),
                    isDense: true,
                  ),
                  onChanged: (v) =>
                      setState(() => _query = v.trim().toLowerCase()),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                child: DropdownButtonFormField<String>(
      isExpanded: true,
                  initialValue: _type,
                  isDense: true,
                  decoration: InputDecoration(
                    labelText: tr('Filter by Type', 'تصفية حسب النوع'),
                    border: const OutlineInputBorder(),
                  ),
                  items: [
                    DropdownMenuItem(
                      value: '',
                      child: Text(tr('All Types', 'كل الأنواع')),
                    ),
                    for (final t in types)
                      DropdownMenuItem(
                        value: t,
                        child: Text(
                          '$t (${data.permissions.where((p) => p['permission_type'] == t).length})',
                        ),
                      ),
                  ],
                  onChanged: (v) => setState(() => _type = v ?? ''),
                ),
              ),
              Padding(
                padding: const EdgeInsets.all(16),
                child: SegmentedButton<bool>(
                  showSelectedIcon: false,
                  segments: [
                    ButtonSegment(
                      value: true,
                      label: Text(
                        '${tr('Assigned', 'المعينة')} (${selected.length})',
                      ),
                    ),
                    ButtonSegment(
                      value: false,
                      label: Text(
                        '${tr('Available', 'المتاحة')} (${data.permissions.length - selected.length})',
                      ),
                    ),
                  ],
                  selected: {_showAssigned},
                  onSelectionChanged: (s) =>
                      setState(() => _showAssigned = s.first),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Align(
                  alignment: AlignmentDirectional.centerEnd,
                  child: _showAssigned
                      ? TextButton.icon(
                          onPressed: selected.isEmpty
                              ? null
                              : () => setState(() => selected.clear()),
                          icon: const Icon(Icons.close, size: 18),
                          label: Text(tr('Remove All', 'إزالة الكل')),
                          style: TextButton.styleFrom(
                            foregroundColor: AppColors.error,
                          ),
                        )
                      : TextButton.icon(
                          onPressed: visible.isEmpty
                              ? null
                              : () => setState(
                                  () => selected.addAll(
                                    visible.map((p) => p['id'] as int),
                                  ),
                                ),
                          icon: const Icon(Icons.add, size: 18),
                          label: Text(tr('Add All Visible', 'إضافة كل الظاهر')),
                        ),
                ),
              ),
              Expanded(
                child: visible.isEmpty
                    ? Center(
                        child: Text(
                          tr('No permissions', 'لا توجد صلاحيات'),
                          style: const TextStyle(
                            color: AppColors.textSecondary,
                          ),
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                        itemCount: visible.length,
                        itemBuilder: (context, index) {
                          final p = visible[index];
                          final id = p['id'] as int;
                          return Card(
                            margin: const EdgeInsets.only(bottom: 8),
                            child: ListTile(
                              dense: true,
                              title: Text(p['name']?.toString() ?? '-'),
                              subtitle: Text(
                                p['permission_type']?.toString() ?? '',
                              ),
                              trailing: IconButton(
                                icon: Icon(
                                  _showAssigned
                                      ? Icons.remove_circle_outline
                                      : Icons.add_circle_outline,
                                  color: _showAssigned
                                      ? AppColors.error
                                      : AppColors.success,
                                ),
                                onPressed: () => setState(() {
                                  _showAssigned
                                      ? selected.remove(id)
                                      : selected.add(id);
                                }),
                              ),
                            ),
                          );
                        },
                      ),
              ),
              SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                  child: SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _saving ? null : () => _save(selected),
                      icon: const Icon(Icons.save),
                      label: Text(
                        _saving
                            ? tr('Saving...', 'جاري الحفظ...')
                            : tr('Update Permissions', 'تحديث الصلاحيات'),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _save(Set<int> selected) async {
    final isArabic = ref.read(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final messenger = ScaffoldMessenger.of(context);

    // Same rule as the web: a role must keep at least one permission.
    if (selected.isEmpty) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(
            tr(
              'No permissions selected. Please select at least one permission.',
              'لم يتم اختيار صلاحيات. يرجى اختيار صلاحية واحدة على الأقل.',
            ),
          ),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      await ref
          .read(apiClientProvider)
          .put(
            '/roles/${widget.roleId}/permissions',
            data: {'permission_ids': selected.toList()},
          );
      ref.invalidate(_rolePermissionsProvider(widget.roleId));
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(
          content: Text(tr('Permissions updated', 'تم تحديث الصلاحيات')),
          backgroundColor: AppColors.success,
        ),
      );
      Navigator.of(context).maybePop();
    } catch (error) {
      if (!mounted) return;
      setState(() => _saving = false);
      messenger.showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}
