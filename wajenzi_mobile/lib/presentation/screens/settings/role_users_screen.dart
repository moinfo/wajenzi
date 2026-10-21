import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _roleUsersProvider = FutureProvider.autoDispose
    .family<List<Map<String, dynamic>>, int>((ref, roleId) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/roles/$roleId/users');
      final root = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final data = Map<String, dynamic>.from(root['data'] as Map? ?? const {});
      return (data['users'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

/// Mirrors the web "Assign Users to Role" form: every user with a checkbox,
/// saved as the role's full member list.
class RoleUsersScreen extends ConsumerStatefulWidget {
  final int roleId;
  final String? roleName;

  const RoleUsersScreen({super.key, required this.roleId, this.roleName});

  @override
  ConsumerState<RoleUsersScreen> createState() => _RoleUsersScreenState();
}

class _RoleUsersScreenState extends ConsumerState<RoleUsersScreen> {
  final _searchController = TextEditingController();
  Set<int>? _selected;
  String _query = '';
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
    final asyncData = ref.watch(_roleUsersProvider(widget.roleId));

    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.roleName == null
              ? tr('Assign Users', 'تعيين المستخدمين')
              : '${tr('Assign Users to Role', 'تعيين المستخدمين للدور')}: ${widget.roleName}',
        ),
      ),
      body: asyncData.when(
        loading: () => LoadingWidget(
          message: tr('Loading users...', 'جاري تحميل المستخدمين...'),
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
        data: (users) {
          final selected = _selected ??= {
            for (final u in users)
              if (u['assigned'] == true) u['id'] as int,
          };
          final visible = users
              .where(
                (u) =>
                    _query.isEmpty ||
                    (u['name']?.toString().toLowerCase().contains(_query) ??
                        false),
              )
              .toList();

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                child: TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: tr('Search users...', 'ابحث عن المستخدمين...'),
                    prefixIcon: const Icon(Icons.search),
                    border: const OutlineInputBorder(),
                    isDense: true,
                  ),
                  onChanged: (v) =>
                      setState(() => _query = v.trim().toLowerCase()),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  children: [
                    Text(
                      '${selected.length} ${tr('selected', 'محدد')}',
                      style: const TextStyle(color: AppColors.textSecondary),
                    ),
                    const Spacer(),
                    TextButton(
                      onPressed: () => setState(
                        () => selected.addAll(visible.map((u) => u['id'] as int)),
                      ),
                      child: Text(tr('Select visible', 'تحديد الظاهر')),
                    ),
                    TextButton(
                      onPressed: () => setState(selected.clear),
                      child: Text(tr('Clear', 'مسح')),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: ListView.builder(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  itemCount: visible.length,
                  itemBuilder: (context, index) {
                    final u = visible[index];
                    final id = u['id'] as int;
                    return Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: CheckboxListTile(
                        dense: true,
                        value: selected.contains(id),
                        title: Text(u['name']?.toString() ?? '-'),
                        subtitle: Text('ID: $id'),
                        onChanged: (checked) => setState(() {
                          checked == true
                              ? selected.add(id)
                              : selected.remove(id);
                        }),
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
                            : tr('Update Users', 'تحديث المستخدمين'),
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

    setState(() => _saving = true);
    try {
      await ref
          .read(apiClientProvider)
          .put(
            '/roles/${widget.roleId}/users',
            data: {'user_ids': selected.toList()},
          );
      ref.invalidate(_roleUsersProvider(widget.roleId));
      if (!mounted) return;
      messenger.showSnackBar(
        SnackBar(
          content: Text(tr('Users updated', 'تم تحديث المستخدمين')),
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
