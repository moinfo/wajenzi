import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../widgets/common/loading_widget.dart';
import '../vat/vat_shared.dart';

final _userStatusFilterProvider = StateProvider.autoDispose<String>((ref) => 'ACTIVE');

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
    final status = ref.watch(_userStatusFilterProvider);
    final asyncData = ref.watch(_settingsUsersProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(status == 'INACTIVE' ? 'Inactive Users' : 'Active Users'),
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
                loading: () => const LoadingWidget(message: 'Loading users...'),
                error: (error, _) => ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(24),
                  children: [
                    const SizedBox(height: 48),
                    const Icon(Icons.error_outline, size: 56, color: AppColors.error),
                    const SizedBox(height: 12),
                    const Text(
                      'Failed to load users',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
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
                              const Icon(Icons.groups_outlined, size: 56, color: AppColors.primary),
                              const SizedBox(height: 12),
                              Text(
                                status == 'INACTIVE'
                                    ? 'No inactive users found'
                                    : 'No active users found',
                                textAlign: TextAlign.center,
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Create or activate users to match the web settings page.',
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 16),
                              ElevatedButton.icon(
                                onPressed: () => _openForm(context, ref),
                                icon: const Icon(Icons.add),
                                label: const Text('New User'),
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
                          onToggleStatus: () => _toggleStatus(context, ref, item),
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
        label: const Text('New User'),
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
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete User'),
        content: Text('Delete ${item['name']}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Delete'),
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
        const SnackBar(
          content: Text('User deleted successfully'),
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
    final currentStatus = (item['status'] ?? '').toString().toUpperCase();
    final actionLabel = currentStatus == 'ACTIVE' ? 'deactivate' : 'activate';

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text('${actionLabel[0].toUpperCase()}${actionLabel.substring(1)} User'),
        content: Text('Are you sure you want to $actionLabel ${item['name']}?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Confirm'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).post('/settings-users/${item['id']}/toggle-status');
      ref.invalidate(_settingsUsersProvider);
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('User ${currentStatus == 'ACTIVE' ? 'deactivated' : 'activated'} successfully'),
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
  final ValueChanged<String> onStatusChanged;
  final VoidCallback onCreate;

  const _UsersFilterHeader({
    required this.status,
    required this.onStatusChanged,
    required this.onCreate,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: SegmentedButton<String>(
                  segments: const [
                    ButtonSegment<String>(value: 'ACTIVE', label: Text('Active')),
                    ButtonSegment<String>(value: 'INACTIVE', label: Text('Inactive')),
                  ],
                  selected: {status},
                  onSelectionChanged: (selection) => onStatusChanged(selection.first),
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
                  child: const Icon(Icons.groups_outlined, color: AppColors.primary),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        status == 'INACTIVE' ? 'Inactive Users' : 'Active Users',
                        style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Manage users, profile details, attendance setup, and status.',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                IconButton(
                  onPressed: onCreate,
                  icon: const Icon(Icons.person_add_alt_1),
                  tooltip: 'New User',
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
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                      ),
                      if (designation.isNotEmpty)
                        Text(
                          designation,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: AppColors.textSecondary),
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
                    const PopupMenuItem(value: 'edit', child: Text('Edit')),
                    PopupMenuItem(
                      value: 'toggle',
                      child: Text(status == 'ACTIVE' ? 'Deactivate' : 'Activate'),
                    ),
                    const PopupMenuItem(value: 'delete', child: Text('Delete')),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _InfoBadge(label: status, color: status == 'ACTIVE' ? AppColors.success : AppColors.warning),
                if (attendanceStatus.isNotEmpty)
                  _InfoBadge(
                    label: attendanceStatus,
                    color: attendanceStatus == 'ENABLED' ? AppColors.primary : AppColors.error,
                  ),
                if ((item['type'] ?? '').toString().isNotEmpty)
                  _InfoBadge(label: (item['type'] ?? '').toString(), color: const Color(0xFF6C63FF)),
              ],
            ),
            const SizedBox(height: 12),
            _UserInfoRow(icon: Icons.email_outlined, label: email.isEmpty ? 'No email' : email),
            _UserInfoRow(
              icon: Icons.apartment_outlined,
              label: department.isEmpty ? 'No department' : department,
            ),
            _UserInfoRow(
              icon: Icons.fingerprint,
              label: 'Device ID: ${(item['user_device_id'] ?? 'N/A').toString()}',
            ),
            _UserInfoRow(
              icon: Icons.access_time,
              label: attendanceType.isEmpty ? 'No attendance type' : attendanceType,
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
    _nameController = TextEditingController(text: item?['name']?.toString() ?? '');
    _emailController = TextEditingController(text: item?['email']?.toString() ?? '');
    _phoneController = TextEditingController(text: item?['phone_number']?.toString() ?? '');
    _addressController = TextEditingController(text: item?['address']?.toString() ?? '');
    _designationController = TextEditingController(text: item?['designation']?.toString() ?? '');
    _employeeNumberController = TextEditingController(text: item?['employee_number']?.toString() ?? '');
    _deviceIdController = TextEditingController(text: item?['user_device_id']?.toString() ?? '');
    _dobController = TextEditingController(text: item?['dob']?.toString() ?? '');
    _employmentDateController = TextEditingController(text: item?['employment_date']?.toString() ?? '');
    _tinController = TextEditingController(text: item?['tin']?.toString() ?? '');
    _nationalIdController = TextEditingController(text: item?['national_id']?.toString() ?? '');

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
    final referenceAsync = ref.watch(_settingsUsersReferenceProvider);

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: referenceAsync.when(
          loading: () => const LoadingWidget(message: 'Loading form data...'),
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
            final attendanceTypes = (reference['attendance_types'] as List? ?? const [])
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
                      _isEdit ? 'Edit User' : 'Create New User',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Core profile, attendance setup, and employment details for mobile admin use.',
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 20),
                    _FormSection(
                      title: 'Basic Details',
                      children: [
                        _buildTextField(_nameController, 'Name', required: true),
                        _buildTextField(_emailController, 'Email', keyboardType: TextInputType.emailAddress),
                        _buildTextField(_phoneController, 'Phone Number', keyboardType: TextInputType.phone),
                        _buildDropdown<String>(
                          label: 'Gender',
                          value: _gender,
                          items: const ['MALE', 'FEMALE'],
                          onChanged: (value) => setState(() => _gender = value),
                        ),
                        _buildTextField(_addressController, 'Address'),
                        _buildTextField(_designationController, 'Designation'),
                      ],
                    ),
                    _FormSection(
                      title: 'Employment',
                      children: [
                        _buildTextField(_employeeNumberController, 'Employee No.'),
                        _buildTextField(_deviceIdController, 'Device ID', keyboardType: TextInputType.number),
                        _buildDropdown<String>(
                          label: 'Employee Type',
                          value: _type,
                          items: const ['STAFF', 'INTERN', 'EXTERNAL'],
                          onChanged: (value) => setState(() => _type = value),
                        ),
                        _buildTextField(_dobController, 'Date of Birth', hint: 'YYYY-MM-DD'),
                        _buildTextField(_employmentDateController, 'Date of Job', hint: 'YYYY-MM-DD'),
                        _buildTextField(_tinController, 'TIN', keyboardType: TextInputType.number),
                        _buildTextField(_nationalIdController, 'National ID', keyboardType: TextInputType.number),
                        _buildDropdown<String>(
                          label: 'Employment Type',
                          value: _employmentType,
                          items: const ['FULL_TIME', 'CONTRACT', 'INTERN'],
                          onChanged: (value) => setState(() => _employmentType = value),
                          isRequired: false,
                        ),
                        _buildDropdown<String>(
                          label: 'Marital Status',
                          value: _maritalStatus,
                          items: const ['SINGLE', 'MARRIED', 'DIVORCED', 'OTHER'],
                          onChanged: (value) => setState(() => _maritalStatus = value),
                          isRequired: false,
                        ),
                        _buildDropdown<String>(
                          label: 'Status',
                          value: _status,
                          items: const ['ACTIVE', 'INACTIVE', 'DORMANT'],
                          onChanged: (value) => setState(() => _status = value),
                        ),
                      ],
                    ),
                    _FormSection(
                      title: 'Attendance & Department',
                      children: [
                        _buildDropdown<int>(
                          label: 'Department',
                          value: _departmentId,
                          items: departments.map((item) => item['id'] as int).toList(),
                          labelForItem: (value) {
                            final match = departments.firstWhere(
                              (item) => item['id'] == value,
                              orElse: () => const <String, dynamic>{},
                            );
                            return match['name']?.toString() ?? 'Unknown';
                          },
                          onChanged: (value) => setState(() => _departmentId = value),
                          isRequired: false,
                        ),
                        _buildDropdown<int>(
                          label: 'Attendance Type',
                          value: _attendanceTypeId,
                          items: attendanceTypes.map((item) => item['id'] as int).toList(),
                          labelForItem: (value) {
                            final match = attendanceTypes.firstWhere(
                              (item) => item['id'] == value,
                              orElse: () => const <String, dynamic>{},
                            );
                            return match['name']?.toString() ?? 'Unknown';
                          },
                          onChanged: (value) => setState(() => _attendanceTypeId = value),
                          isRequired: false,
                        ),
                        _buildDropdown<String>(
                          label: 'Attendance Status',
                          value: _attendanceStatus,
                          items: const ['ENABLED', 'DISABLED'],
                          onChanged: (value) => setState(() => _attendanceStatus = value),
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
                          _submitting ? 'Saving...' : (_isEdit ? 'Update User' : 'Save User'),
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
            ? (value) => (value == null || value.trim().isEmpty) ? '$label is required' : null
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
                child: Text(labelForItem != null ? labelForItem(item) : item.toString()),
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
