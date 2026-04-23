import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _userStatusFilterProvider = StateProvider.autoDispose<String>(
  (ref) => 'ACTIVE',
);

final _settingsUsersReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/settings-users/reference-data');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    });

final _settingsUsersProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final status = ref.watch(_userStatusFilterProvider);
      final response = await api.get(
        '/settings-users',
        queryParameters: {'status': status},
      );
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

class UsersScreen extends ConsumerWidget {
  const UsersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final status = ref.watch(_userStatusFilterProvider);
    final asyncData = ref.watch(_settingsUsersProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          status == 'INACTIVE'
              ? tr('Inactive Users', 'المستخدمون غير النشطين')
              : tr('Active Users', 'المستخدمون النشطون'),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: Column(
        children: [
          _UsersFilterHeader(
            status: status,
            isArabic: isArabic,
            onStatusChanged: (value) {
              ref.read(_userStatusFilterProvider.notifier).state = value;
            },
            onCreate: () => _openForm(context, ref),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_settingsUsersReferenceProvider);
                ref.invalidate(_settingsUsersProvider);
              },
              child: asyncData.when(
                loading: () => LoadingWidget(
                  message: tr('Loading users...', 'جاري تحميل المستخدمين...'),
                ),
                error: (error, _) => ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(24),
                  children: [
                    const SizedBox(height: 48),
                    const Icon(
                      Icons.error_outline,
                      size: 56,
                      color: AppColors.error,
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Failed to load users',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(vatErrorMessage(error), textAlign: TextAlign.center),
                  ],
                ),
                data: (items) {
                  if (items.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(24),
                      children: [
                        Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(24),
                          ),
                          child: Column(
                            children: [
                              const Icon(
                                Icons.groups_outlined,
                                size: 56,
                                color: AppColors.primary,
                              ),
                              const SizedBox(height: 12),
                              Text(
                                status == 'INACTIVE'
                                    ? 'No inactive users found'
                                    : 'No active users found',
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                tr(
                                  'Create or activate users to match the web settings page.',
                                  'أنشئ المستخدمين أو فعّلهم ليتوافقوا مع صفحة الإعدادات على الويب.',
                                ),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 16),
                              ElevatedButton.icon(
                                onPressed: () => _openForm(context, ref),
                                icon: const Icon(Icons.add),
                                label: Text(tr('New User', 'مستخدم جديد')),
                              ),
                            ],
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 90),
                    children: [
                      ...List.generate(items.length, (index) {
                        final item = items[index];
                        return _UserCard(
                          index: index + 1,
                          item: item,
                          onEdit: () => _openForm(context, ref, item: item),
                          onDelete: () => _deleteItem(context, ref, item),
                          onToggleStatus: () =>
                              _toggleStatus(context, ref, item),
                        );
                      }),
                    ],
                  );
                },
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(context, ref),
        icon: const Icon(Icons.add),
        label: Text(tr('New User', 'مستخدم جديد')),
      ),
    );
  }

  Future<void> _openForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? item,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.94,
        child: _UserFormSheet(item: item),
      ),
    );
    if (result == true) {
      ref.invalidate(_settingsUsersProvider);
    }
  }

  Future<void> _deleteItem(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final isArabic = ref.read(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(tr('Delete User', 'حذف المستخدم')),
        content: Text(
          tr('Delete ${item['name']}?', 'هل تريد حذف ${item['name']}؟'),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(tr('Cancel', 'إلغاء')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(tr('Delete', 'حذف')),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/settings-users/${item['id']}');
      ref.invalidate(_settingsUsersProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            tr('User deleted successfully', 'تم حذف المستخدم بنجاح'),
          ),
          backgroundColor: AppColors.success,
        ),
      );
    } catch (error) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  Future<void> _toggleStatus(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
  ) async {
    final isArabic = ref.read(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final currentStatus = (item['status'] ?? '').toString().toUpperCase();
    final actionLabel = currentStatus == 'ACTIVE' ? 'deactivate' : 'activate';

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(
          currentStatus == 'ACTIVE'
              ? tr('Deactivate User', 'تعطيل المستخدم')
              : tr('Activate User', 'تفعيل المستخدم'),
        ),
        content: Text(
          currentStatus == 'ACTIVE'
              ? tr(
                  'Are you sure you want to deactivate ${item['name']}?',
                  'هل أنت متأكد من تعطيل ${item['name']}؟',
                )
              : tr(
                  'Are you sure you want to activate ${item['name']}?',
                  'هل أنت متأكد من تفعيل ${item['name']}؟',
                ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text(tr('Cancel', 'إلغاء')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(tr('Confirm', 'تأكيد')),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref
          .read(apiClientProvider)
          .post('/settings-users/${item['id']}/toggle-status');
      ref.invalidate(_settingsUsersProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            currentStatus == 'ACTIVE'
                ? tr('User deactivated successfully', 'تم تعطيل المستخدم بنجاح')
                : tr('User activated successfully', 'تم تفعيل المستخدم بنجاح'),
          ),
          backgroundColor: AppColors.success,
        ),
      );
    } catch (error) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _UsersFilterHeader extends StatelessWidget {
  final String status;
  final bool isArabic;
  final ValueChanged<String> onStatusChanged;
  final VoidCallback onCreate;

  const _UsersFilterHeader({
    required this.status,
    required this.isArabic,
    required this.onStatusChanged,
    required this.onCreate,
  });

  @override
  Widget build(BuildContext context) {
    String tr(String en, String ar) => isArabic ? ar : en;
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: SegmentedButton<String>(
                  segments: [
                    ButtonSegment<String>(
                      value: 'ACTIVE',
                      label: Text(tr('Active', 'نشط')),
                    ),
                    ButtonSegment<String>(
                      value: 'INACTIVE',
                      label: Text(tr('Inactive', 'غير نشط')),
                    ),
                  ],
                  selected: {status},
                  onSelectionChanged: (selection) =>
                      onStatusChanged(selection.first),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.04),
                  blurRadius: 18,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(
                    Icons.groups_outlined,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        status == 'INACTIVE'
                            ? tr('Inactive Users', 'المستخدمون غير النشطين')
                            : tr('Active Users', 'المستخدمون النشطون'),
                        style: const TextStyle(
                          fontSize: 19,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        tr(
                          'Manage users, profile details, attendance setup, and status.',
                          'إدارة المستخدمين وبيانات الملف الشخصي وإعدادات الحضور والحالة.',
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  onPressed: onCreate,
                  icon: const Icon(Icons.person_add_alt_1),
                  tooltip: tr('New User', 'مستخدم جديد'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _UserCard extends StatelessWidget {
  final int index;
  final Map<String, dynamic> item;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onToggleStatus;

  const _UserCard({
    required this.index,
    required this.item,
    required this.onEdit,
    required this.onDelete,
    required this.onToggleStatus,
  });

  @override
  Widget build(BuildContext context) {
    final isArabic = Localizations.localeOf(context).languageCode == 'ar';
    String tr(String en, String ar) => isArabic ? ar : en;
    final status = (item['status'] ?? '').toString();
    final attendanceStatus = (item['attendance_status'] ?? '').toString();
    final department = (item['department_name'] ?? '').toString();
    final attendanceType = (item['attendance_type_name'] ?? '').toString();
    final designation = (item['designation'] ?? '').toString();
    final email = (item['email'] ?? '').toString();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 38,
                  height: 38,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: Colors.grey.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '$index',
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        item['name']?.toString() ?? '-',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      if (designation.isNotEmpty)
                        Text(
                          designation,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: AppColors.textSecondary,
                          ),
                        ),
                    ],
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (value) {
                    if (value == 'edit') {
                      onEdit();
                    } else if (value == 'toggle') {
                      onToggleStatus();
                    } else if (value == 'delete') {
                      onDelete();
                    }
                  },
                  itemBuilder: (_) => [
                    PopupMenuItem(
                      value: 'edit',
                      child: Text(tr('Edit', 'تعديل')),
                    ),
                    PopupMenuItem(
                      value: 'toggle',
                      child: Text(
                        status == 'ACTIVE'
                            ? tr('Deactivate', 'تعطيل')
                            : tr('Activate', 'تفعيل'),
                      ),
                    ),
                    PopupMenuItem(
                      value: 'delete',
                      child: Text(tr('Delete', 'حذف')),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _InfoBadge(
                  label: status,
                  color: status == 'ACTIVE'
                      ? AppColors.success
                      : AppColors.warning,
                ),
                if (attendanceStatus.isNotEmpty)
                  _InfoBadge(
                    label: attendanceStatus,
                    color: attendanceStatus == 'ENABLED'
                        ? AppColors.primary
                        : AppColors.error,
                  ),
                if ((item['type'] ?? '').toString().isNotEmpty)
                  _InfoBadge(
                    label: (item['type'] ?? '').toString(),
                    color: const Color(0xFF6C63FF),
                  ),
              ],
            ),
            const SizedBox(height: 12),
            _UserInfoRow(
              icon: Icons.email_outlined,
              label: email.isEmpty
                  ? tr('No email', 'لا يوجد بريد إلكتروني')
                  : email,
            ),
            _UserInfoRow(
              icon: Icons.apartment_outlined,
              label: department.isEmpty
                  ? tr('No department', 'لا يوجد قسم')
                  : department,
            ),
            _UserInfoRow(
              icon: Icons.fingerprint,
              label: tr(
                'Device ID: ${(item['user_device_id'] ?? 'N/A').toString()}',
                'معرّف الجهاز: ${(item['user_device_id'] ?? 'N/A').toString()}',
              ),
            ),
            _UserInfoRow(
              icon: Icons.access_time,
              label: attendanceType.isEmpty
                  ? tr('No attendance type', 'لا يوجد نوع حضور')
                  : attendanceType,
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoBadge extends StatelessWidget {
  final String label;
  final Color color;

  const _InfoBadge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _UserInfoRow extends StatelessWidget {
  final IconData icon;
  final String label;

  const _UserInfoRow({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              label,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: AppColors.textSecondary),
            ),
          ),
        ],
      ),
    );
  }
}

class _UserFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? item;

  const _UserFormSheet({this.item});

  @override
  ConsumerState<_UserFormSheet> createState() => _UserFormSheetState();
}

class _UserFormSheetState extends ConsumerState<_UserFormSheet> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _nameController;
  late final TextEditingController _emailController;
  late final TextEditingController _phoneController;
  late final TextEditingController _addressController;
  late final TextEditingController _designationController;
  late final TextEditingController _employeeNumberController;
  late final TextEditingController _deviceIdController;
  late final TextEditingController _dobController;
  late final TextEditingController _employmentDateController;
  late final TextEditingController _tinController;
  late final TextEditingController _nationalIdController;

  bool _submitting = false;
  String? _gender;
  String? _type;
  String? _employmentType;
  String? _maritalStatus;
  String? _status;
  String? _attendanceStatus;
  int? _departmentId;
  int? _attendanceTypeId;

  bool get _isEdit => widget.item != null;

  @override
  void initState() {
    super.initState();
    final item = widget.item;
    _nameController = TextEditingController(
      text: item?['name']?.toString() ?? '',
    );
    _emailController = TextEditingController(
      text: item?['email']?.toString() ?? '',
    );
    _phoneController = TextEditingController(
      text: item?['phone_number']?.toString() ?? '',
    );
    _addressController = TextEditingController(
      text: item?['address']?.toString() ?? '',
    );
    _designationController = TextEditingController(
      text: item?['designation']?.toString() ?? '',
    );
    _employeeNumberController = TextEditingController(
      text: item?['employee_number']?.toString() ?? '',
    );
    _deviceIdController = TextEditingController(
      text: item?['user_device_id']?.toString() ?? '',
    );
    _dobController = TextEditingController(
      text: item?['dob']?.toString() ?? '',
    );
    _employmentDateController = TextEditingController(
      text: item?['employment_date']?.toString() ?? '',
    );
    _tinController = TextEditingController(
      text: item?['tin']?.toString() ?? '',
    );
    _nationalIdController = TextEditingController(
      text: item?['national_id']?.toString() ?? '',
    );

    _gender = item?['gender']?.toString() ?? 'MALE';
    _type = item?['type']?.toString() ?? 'STAFF';
    _employmentType = item?['employment_type']?.toString();
    _maritalStatus = item?['marital_status']?.toString();
    _status = item?['status']?.toString() ?? 'ACTIVE';
    _attendanceStatus = item?['attendance_status']?.toString() ?? 'ENABLED';
    _departmentId = item?['department_id'] as int?;
    _attendanceTypeId = item?['attendance_type_id'] as int?;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _designationController.dispose();
    _employeeNumberController.dispose();
    _deviceIdController.dispose();
    _dobController.dispose();
    _employmentDateController.dispose();
    _tinController.dispose();
    _nationalIdController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isArabic = ref.watch(currentLanguageProvider) == AppLanguage.arabic;
    String tr(String en, String ar) => isArabic ? ar : en;
    final referenceAsync = ref.watch(_settingsUsersReferenceProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: referenceAsync.when(
          loading: () => LoadingWidget(
            message: tr('Loading form data...', 'جاري تحميل بيانات النموذج...'),
          ),
          error: (error, _) => Padding(
            padding: const EdgeInsets.all(24),
            child: Center(
              child: Text(vatErrorMessage(error), textAlign: TextAlign.center),
            ),
          ),
          data: (reference) {
            final departments = (reference['departments'] as List? ?? const [])
                .whereType<Map>()
                .map((item) => Map<String, dynamic>.from(item))
                .toList();
            final attendanceTypes =
                (reference['attendance_types'] as List? ?? const [])
                    .whereType<Map>()
                    .map((item) => Map<String, dynamic>.from(item))
                    .toList();

            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.of(context).viewInsets.bottom + 20,
              ),
              child: Form(
                key: _formKey,
                child: ListView(
                  children: [
                    Center(
                      child: Container(
                        width: 44,
                        height: 5,
                        decoration: BoxDecoration(
                          color: Colors.black12,
                          borderRadius: BorderRadius.circular(999),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      _isEdit
                          ? tr('Edit User', 'تعديل المستخدم')
                          : tr('Create New User', 'إنشاء مستخدم جديد'),
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      tr(
                        'Core profile, attendance setup, and employment details for mobile admin use.',
                        'الملف الشخصي الأساسي وإعدادات الحضور وتفاصيل التوظيف لاستخدام إدارة التطبيق.',
                      ),
                      style: const TextStyle(color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 20),
                    _FormSection(
                      title: tr('Basic Details', 'البيانات الأساسية'),
                      children: [
                        _buildTextField(
                          _nameController,
                          tr('Name', 'الاسم'),
                          required: true,
                        ),
                        _buildTextField(
                          _emailController,
                          tr('Email', 'البريد الإلكتروني'),
                          keyboardType: TextInputType.emailAddress,
                        ),
                        _buildTextField(
                          _phoneController,
                          tr('Phone Number', 'رقم الهاتف'),
                          keyboardType: TextInputType.phone,
                        ),
                        _buildDropdown<String>(
                          label: tr('Gender', 'الجنس'),
                          value: _gender,
                          items: const ['MALE', 'FEMALE'],
                          onChanged: (value) => setState(() => _gender = value),
                        ),
                        _buildTextField(
                          _addressController,
                          tr('Address', 'العنوان'),
                        ),
                        _buildTextField(
                          _designationController,
                          tr('Designation', 'المسمى الوظيفي'),
                        ),
                      ],
                    ),
                    _FormSection(
                      title: tr('Employment', 'التوظيف'),
                      children: [
                        _buildTextField(
                          _employeeNumberController,
                          tr('Employee No.', 'رقم الموظف'),
                        ),
                        _buildTextField(
                          _deviceIdController,
                          tr('Device ID', 'معرّف الجهاز'),
                          keyboardType: TextInputType.number,
                        ),
                        _buildDropdown<String>(
                          label: tr('Employee Type', 'نوع الموظف'),
                          value: _type,
                          items: const ['STAFF', 'INTERN', 'EXTERNAL'],
                          onChanged: (value) => setState(() => _type = value),
                        ),
                        _buildTextField(
                          _dobController,
                          tr('Date of Birth', 'تاريخ الميلاد'),
                          hint: 'YYYY-MM-DD',
                        ),
                        _buildTextField(
                          _employmentDateController,
                          tr('Date of Job', 'تاريخ التوظيف'),
                          hint: 'YYYY-MM-DD',
                        ),
                        _buildTextField(
                          _tinController,
                          tr('TIN', 'الرقم الضريبي'),
                          keyboardType: TextInputType.number,
                        ),
                        _buildTextField(
                          _nationalIdController,
                          tr('National ID', 'الهوية الوطنية'),
                          keyboardType: TextInputType.number,
                        ),
                        _buildDropdown<String>(
                          label: tr('Employment Type', 'نوع التوظيف'),
                          value: _employmentType,
                          items: const ['FULL_TIME', 'CONTRACT', 'INTERN'],
                          onChanged: (value) =>
                              setState(() => _employmentType = value),
                          isRequired: false,
                        ),
                        _buildDropdown<String>(
                          label: tr('Marital Status', 'الحالة الاجتماعية'),
                          value: _maritalStatus,
                          items: const [
                            'SINGLE',
                            'MARRIED',
                            'DIVORCED',
                            'OTHER',
                          ],
                          onChanged: (value) =>
                              setState(() => _maritalStatus = value),
                          isRequired: false,
                        ),
                        _buildDropdown<String>(
                          label: tr('Status', 'الحالة'),
                          value: _status,
                          items: const ['ACTIVE', 'INACTIVE', 'DORMANT'],
                          onChanged: (value) => setState(() => _status = value),
                        ),
                      ],
                    ),
                    _FormSection(
                      title: tr('Attendance & Department', 'الحضور والقسم'),
                      children: [
                        _buildDropdown<int>(
                          label: tr('Department', 'القسم'),
                          value: _departmentId,
                          items: departments
                              .map((item) => item['id'] as int)
                              .toList(),
                          labelForItem: (value) {
                            final match = departments.firstWhere(
                              (item) => item['id'] == value,
                              orElse: () => const <String, dynamic>{},
                            );
                            return match['name']?.toString() ??
                                tr('Unknown', 'غير معروف');
                          },
                          onChanged: (value) =>
                              setState(() => _departmentId = value),
                          isRequired: false,
                        ),
                        _buildDropdown<int>(
                          label: tr('Attendance Type', 'نوع الحضور'),
                          value: _attendanceTypeId,
                          items: attendanceTypes
                              .map((item) => item['id'] as int)
                              .toList(),
                          labelForItem: (value) {
                            final match = attendanceTypes.firstWhere(
                              (item) => item['id'] == value,
                              orElse: () => const <String, dynamic>{},
                            );
                            return match['name']?.toString() ??
                                tr('Unknown', 'غير معروف');
                          },
                          onChanged: (value) =>
                              setState(() => _attendanceTypeId = value),
                          isRequired: false,
                        ),
                        _buildDropdown<String>(
                          label: tr('Attendance Status', 'حالة الحضور'),
                          value: _attendanceStatus,
                          items: const ['ENABLED', 'DISABLED'],
                          onChanged: (value) =>
                              setState(() => _attendanceStatus = value),
                          isRequired: false,
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _submitting ? null : _submit,
                        child: Text(
                          _submitting
                              ? tr('Saving...', 'جاري الحفظ...')
                              : (_isEdit
                                    ? tr('Update User', 'تحديث المستخدم')
                                    : tr('Save User', 'حفظ المستخدم')),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildTextField(
    TextEditingController controller,
    String label, {
    TextInputType? keyboardType,
    bool required = false,
    String? hint,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          labelText: label,
          hintText: hint,
          border: const OutlineInputBorder(),
        ),
        validator: required
            ? (value) => (value == null || value.trim().isEmpty)
                  ? '$label is required'
                  : null
            : null,
      ),
    );
  }

  Widget _buildDropdown<T>({
    required String label,
    required T? value,
    required List<T> items,
    required ValueChanged<T?> onChanged,
    String Function(T value)? labelForItem,
    bool isRequired = true,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: DropdownButtonFormField<T>(
        value: items.contains(value) ? value : null,
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
        ),
        items: items
            .map(
              (item) => DropdownMenuItem<T>(
                value: item,
                child: Text(
                  labelForItem != null ? labelForItem(item) : item.toString(),
                ),
              ),
            )
            .toList(),
        onChanged: onChanged,
        validator: isRequired
            ? (value) => value == null ? '$label is required' : null
            : null,
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);

    final payload = <String, dynamic>{
      'name': _nameController.text.trim(),
      'email': _emptyToNull(_emailController.text),
      'phone_number': _emptyToNull(_phoneController.text),
      'gender': _gender,
      'address': _emptyToNull(_addressController.text),
      'designation': _emptyToNull(_designationController.text),
      'employee_number': _emptyToNull(_employeeNumberController.text),
      'user_device_id': _deviceIdController.text.trim().isEmpty
          ? null
          : int.tryParse(_deviceIdController.text.trim()),
      'type': _type,
      'dob': _emptyToNull(_dobController.text),
      'employment_date': _emptyToNull(_employmentDateController.text),
      'tin': _emptyToNull(_tinController.text),
      'national_id': _emptyToNull(_nationalIdController.text),
      'employment_type': _employmentType,
      'marital_status': _maritalStatus,
      'status': _status,
      'department_id': _departmentId,
      'attendance_type_id': _attendanceTypeId,
      'attendance_status': _attendanceStatus,
    };

    try {
      final api = ref.read(apiClientProvider);
      if (_isEdit) {
        await api.put('/settings-users/${widget.item!['id']}', data: payload);
      } else {
        await api.post('/settings-users', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  String? _emptyToNull(String value) {
    final trimmed = value.trim();
    return trimmed.isEmpty ? null : trimmed;
  }
}

class _FormSection extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _FormSection({required this.title, required this.children});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }
}
