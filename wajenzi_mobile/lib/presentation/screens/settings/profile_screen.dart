import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';

final clientProfileProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  return ref.watch(authStateProvider.notifier).getClientProfile();
});

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  bool _isEditing = false;
  bool _isSaving = false;
  bool _loaded = false;

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    super.dispose();
  }

  void _populateFields(Map<String, dynamic> data) {
    if (_loaded) return;
    _firstNameController.text = data['first_name'] as String? ?? '';
    _lastNameController.text = data['last_name'] as String? ?? '';
    _emailController.text = data['email'] as String? ?? '';
    _phoneController.text = data['phone_number'] as String? ?? '';
    _addressController.text = data['address'] as String? ?? '';
    _loaded = true;
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);

    final success = await ref.read(authStateProvider.notifier).updateClientProfile(
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim(),
      phoneNumber: _phoneController.text.trim().isEmpty ? null : _phoneController.text.trim(),
      address: _addressController.text.trim().isEmpty ? null : _addressController.text.trim(),
    );

    setState(() {
      _isSaving = false;
      if (success) _isEditing = false;
    });

    if (mounted) {
      final isSwahili = ref.read(isSwahiliProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success
              ? (isSwahili ? 'Wasifu umesasishwa' : 'Profile updated successfully')
              : (isSwahili ? 'Imeshindikana kusasisha wasifu' : 'Failed to update profile')),
          backgroundColor: success ? AppColors.success : AppColors.error,
        ),
      );
      if (success) {
        ref.invalidate(clientProfileProvider);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final profileAsync = ref.watch(clientProfileProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Wasifu' : 'Profile'),
        actions: [
          if (!_isEditing)
            IconButton(
              icon: const Icon(Icons.edit_outlined),
              onPressed: () => setState(() => _isEditing = true),
              tooltip: isSwahili ? 'Hariri' : 'Edit',
            )
          else
            TextButton(
              onPressed: () {
                setState(() {
                  _isEditing = false;
                  _loaded = false; // re-populate from server data
                });
                ref.invalidate(clientProfileProvider);
              },
              child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
            ),
        ],
      ),
      body: profileAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline, size: 48, color: AppColors.error),
              const SizedBox(height: 12),
              Text(isSwahili ? 'Imeshindikana kupakia wasifu' : 'Failed to load profile'),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () => ref.invalidate(clientProfileProvider),
                child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
              ),
            ],
          ),
        ),
        data: (data) {
          _populateFields(data);

          if (_isEditing) return _buildEditForm(isSwahili, data);
          return _buildProfileView(isSwahili, data);
        },
      ),
    );
  }

  Widget _buildProfileView(bool isSwahili, Map<String, dynamic> data) {
    final fullName = data['full_name'] as String? ?? '';
    final email = data['email'] as String? ?? '';
    final phone = data['phone_number'] as String? ?? '';
    final address = data['address'] as String? ?? '';
    final idNumber = data['identification_number'] as String? ?? '';
    final projectsCount = data['projects_count'] ?? 0;

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Avatar & Name header
        Center(
          child: Column(
            children: [
              CircleAvatar(
                radius: 48,
                backgroundColor: AppColors.primary,
                child: Text(
                  fullName.isNotEmpty ? fullName[0].toUpperCase() : 'U',
                  style: const TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Colors.white),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                fullName,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 4),
              Text(
                email,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '$projectsCount ${isSwahili ? 'miradi' : 'projects'}',
                  style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.w600, fontSize: 13),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),

        // Info cards
        _ProfileInfoCard(
          title: isSwahili ? 'Maelezo ya Msingi' : 'Basic Information',
          items: [
            _InfoRow(
              icon: Icons.person_outline,
              label: isSwahili ? 'Jina la Kwanza' : 'First Name',
              value: data['first_name'] as String? ?? '-',
            ),
            _InfoRow(
              icon: Icons.person_outline,
              label: isSwahili ? 'Jina la Mwisho' : 'Last Name',
              value: data['last_name'] as String? ?? '-',
            ),
            _InfoRow(
              icon: Icons.badge_outlined,
              label: isSwahili ? 'Nambari ya Kitambulisho' : 'ID Number',
              value: idNumber.isNotEmpty ? idNumber : '-',
            ),
          ],
        ),
        const SizedBox(height: 16),

        _ProfileInfoCard(
          title: isSwahili ? 'Mawasiliano' : 'Contact',
          items: [
            _InfoRow(
              icon: Icons.email_outlined,
              label: isSwahili ? 'Barua Pepe' : 'Email',
              value: email.isNotEmpty ? email : '-',
            ),
            _InfoRow(
              icon: Icons.phone_outlined,
              label: isSwahili ? 'Simu' : 'Phone',
              value: phone.isNotEmpty ? phone : '-',
            ),
            _InfoRow(
              icon: Icons.location_on_outlined,
              label: isSwahili ? 'Anwani' : 'Address',
              value: address.isNotEmpty ? address : '-',
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildEditForm(bool isSwahili, Map<String, dynamic> data) {
    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Avatar (non-editable)
          Center(
            child: CircleAvatar(
              radius: 40,
              backgroundColor: AppColors.primary,
              child: Text(
                _firstNameController.text.isNotEmpty ? _firstNameController.text[0].toUpperCase() : 'U',
                style: const TextStyle(fontSize: 32, fontWeight: FontWeight.bold, color: Colors.white),
              ),
            ),
          ),
          const SizedBox(height: 24),

          TextFormField(
            controller: _firstNameController,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Jina la Kwanza' : 'First Name',
              prefixIcon: const Icon(Icons.person_outline),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            validator: (v) => v == null || v.trim().isEmpty
                ? (isSwahili ? 'Jina la kwanza linahitajika' : 'First name is required')
                : null,
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _lastNameController,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Jina la Mwisho' : 'Last Name',
              prefixIcon: const Icon(Icons.person_outline),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            validator: (v) => v == null || v.trim().isEmpty
                ? (isSwahili ? 'Jina la mwisho linahitajika' : 'Last name is required')
                : null,
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _emailController,
            keyboardType: TextInputType.emailAddress,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Barua Pepe' : 'Email',
              prefixIcon: const Icon(Icons.email_outlined),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            validator: (v) {
              if (v == null || v.trim().isEmpty) {
                return isSwahili ? 'Barua pepe inahitajika' : 'Email is required';
              }
              if (!v.contains('@')) {
                return isSwahili ? 'Barua pepe si sahihi' : 'Invalid email address';
              }
              return null;
            },
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Nambari ya Simu' : 'Phone Number',
              prefixIcon: const Icon(Icons.phone_outlined),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _addressController,
            maxLines: 2,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Anwani' : 'Address',
              prefixIcon: const Icon(Icons.location_on_outlined),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 24),

          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _isSaving ? null : _save,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: _isSaving
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : Text(
                      isSwahili ? 'Hifadhi Mabadiliko' : 'Save Changes',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProfileInfoCard extends StatelessWidget {
  final String title;
  final List<_InfoRow> items;

  const _ProfileInfoCard({required this.title, required this.items});

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Colors.grey.withValues(alpha: 0.2)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: AppColors.textSecondary,
                  ),
            ),
            const SizedBox(height: 12),
            ...items.map((item) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: item,
                )),
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({required this.icon, required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 20, color: AppColors.textHint),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 12, color: AppColors.textHint)),
              const SizedBox(height: 2),
              Text(value, style: const TextStyle(fontSize: 15)),
            ],
          ),
        ),
      ],
    );
  }
}
