import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';

final clientProfileProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  return ref.watch(authStateProvider.notifier).getClientProfile();
});

String _profileTr(
  AppLanguage language, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) {
  return switch (language) {
    AppLanguage.swahili => sw ?? en,
    AppLanguage.french => fr ?? en,
    AppLanguage.arabic => ar ?? en,
    AppLanguage.english => en,
  };
}

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

    final error = await ref.read(authStateProvider.notifier).updateClientProfile(
      firstName: _firstNameController.text.trim(),
      lastName: _lastNameController.text.trim(),
      email: _emailController.text.trim(),
      phoneNumber: _phoneController.text.trim().isEmpty ? null : _phoneController.text.trim(),
      address: _addressController.text.trim().isEmpty ? null : _addressController.text.trim(),
    );

    setState(() {
      _isSaving = false;
      if (error == null) _isEditing = false;
    });

    if (mounted) {
      final language = ref.read(currentLanguageProvider);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            error ??
                _profileTr(
                  language,
                  en: 'Profile updated successfully',
                  sw: 'Wasifu umesasishwa',
                  fr: 'Profil mis a jour avec succes',
                  ar: 'تم تحديث الملف الشخصي بنجاح',
                ),
          ),
          backgroundColor: error == null ? AppColors.success : AppColors.error,
        ),
      );
      if (error == null) {
        ref.invalidate(clientProfileProvider);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);
    final profileAsync = ref.watch(clientProfileProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _profileTr(
            language,
            en: 'Profile',
            sw: 'Wasifu',
            fr: 'Profil',
            ar: 'الملف الشخصي',
          ),
        ),
        actions: [
          if (!_isEditing)
            IconButton(
              icon: const Icon(Icons.edit_outlined),
              onPressed: () => setState(() => _isEditing = true),
              tooltip: _profileTr(
                language,
                en: 'Edit',
                sw: 'Hariri',
                fr: 'Modifier',
                ar: 'تعديل',
              ),
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
              child: Text(
                _profileTr(
                  language,
                  en: 'Cancel',
                  sw: 'Ghairi',
                  fr: 'Annuler',
                  ar: 'إلغاء',
                ),
              ),
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
              Text(
                _profileTr(
                  language,
                  en: 'Failed to load profile',
                  sw: 'Imeshindikana kupakia wasifu',
                  fr: 'Impossible de charger le profil',
                  ar: 'تعذر تحميل الملف الشخصي',
                ),
              ),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () => ref.invalidate(clientProfileProvider),
                child: Text(
                  _profileTr(
                    language,
                    en: 'Retry',
                    sw: 'Jaribu tena',
                    fr: 'Reessayer',
                    ar: 'إعادة المحاولة',
                  ),
                ),
              ),
            ],
          ),
        ),
        data: (data) {
          _populateFields(data);

          if (_isEditing) return _buildEditForm(language, data);
          return _buildProfileView(language, data);
        },
      ),
    );
  }

  Widget _buildProfileView(AppLanguage language, Map<String, dynamic> data) {
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
                  '$projectsCount ${_profileTr(language, en: 'projects', sw: 'miradi', fr: 'projets', ar: 'مشاريع')}',
                  style: TextStyle(color: AppColors.primary, fontWeight: FontWeight.w600, fontSize: 13),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),

        // Info cards
        _ProfileInfoCard(
          title: _profileTr(
            language,
            en: 'Basic Information',
            sw: 'Maelezo ya Msingi',
            fr: 'Informations de base',
            ar: 'المعلومات الأساسية',
          ),
          items: [
            _InfoRow(
              icon: Icons.person_outline,
              label: _profileTr(language, en: 'First Name', sw: 'Jina la Kwanza', fr: 'Prenom', ar: 'الاسم الأول'),
              value: data['first_name'] as String? ?? '-',
            ),
            _InfoRow(
              icon: Icons.person_outline,
              label: _profileTr(language, en: 'Last Name', sw: 'Jina la Mwisho', fr: 'Nom', ar: 'اسم العائلة'),
              value: data['last_name'] as String? ?? '-',
            ),
            _InfoRow(
              icon: Icons.badge_outlined,
              label: _profileTr(language, en: 'ID Number', sw: 'Nambari ya Kitambulisho', fr: 'Numero d\'identite', ar: 'رقم الهوية'),
              value: idNumber.isNotEmpty ? idNumber : '-',
            ),
          ],
        ),
        const SizedBox(height: 16),

        _ProfileInfoCard(
          title: _profileTr(
            language,
            en: 'Contact',
            sw: 'Mawasiliano',
            fr: 'Contact',
            ar: 'التواصل',
          ),
          items: [
            _InfoRow(
              icon: Icons.email_outlined,
              label: _profileTr(language, en: 'Email', sw: 'Barua Pepe', fr: 'E-mail', ar: 'البريد الإلكتروني'),
              value: email.isNotEmpty ? email : '-',
            ),
            _InfoRow(
              icon: Icons.phone_outlined,
              label: _profileTr(language, en: 'Phone', sw: 'Simu', fr: 'Telephone', ar: 'الهاتف'),
              value: phone.isNotEmpty ? phone : '-',
            ),
            _InfoRow(
              icon: Icons.location_on_outlined,
              label: _profileTr(language, en: 'Address', sw: 'Anwani', fr: 'Adresse', ar: 'العنوان'),
              value: address.isNotEmpty ? address : '-',
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildEditForm(AppLanguage language, Map<String, dynamic> data) {
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
              labelText: _profileTr(language, en: 'First Name', sw: 'Jina la Kwanza', fr: 'Prenom', ar: 'الاسم الأول'),
              prefixIcon: const Icon(Icons.person_outline),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            validator: (v) => v == null || v.trim().isEmpty
                ? _profileTr(language, en: 'First name is required', sw: 'Jina la kwanza linahitajika', fr: 'Le prenom est requis', ar: 'الاسم الأول مطلوب')
                : null,
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _lastNameController,
            decoration: InputDecoration(
              labelText: _profileTr(language, en: 'Last Name', sw: 'Jina la Mwisho', fr: 'Nom', ar: 'اسم العائلة'),
              prefixIcon: const Icon(Icons.person_outline),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            validator: (v) => v == null || v.trim().isEmpty
                ? _profileTr(language, en: 'Last name is required', sw: 'Jina la mwisho linahitajika', fr: 'Le nom est requis', ar: 'اسم العائلة مطلوب')
                : null,
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _emailController,
            keyboardType: TextInputType.emailAddress,
            decoration: InputDecoration(
              labelText: _profileTr(language, en: 'Email', sw: 'Barua Pepe', fr: 'E-mail', ar: 'البريد الإلكتروني'),
              prefixIcon: const Icon(Icons.email_outlined),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            validator: (v) {
              if (v == null || v.trim().isEmpty) {
                return _profileTr(language, en: 'Email is required', sw: 'Barua pepe inahitajika', fr: 'L\'e-mail est requis', ar: 'البريد الإلكتروني مطلوب');
              }
              if (!v.contains('@')) {
                return _profileTr(language, en: 'Invalid email address', sw: 'Barua pepe si sahihi', fr: 'Adresse e-mail invalide', ar: 'عنوان البريد الإلكتروني غير صالح');
              }
              return null;
            },
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              labelText: _profileTr(language, en: 'Phone Number', sw: 'Nambari ya Simu', fr: 'Numero de telephone', ar: 'رقم الهاتف'),
              prefixIcon: const Icon(Icons.phone_outlined),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 16),

          TextFormField(
            controller: _addressController,
            maxLines: 2,
            decoration: InputDecoration(
              labelText: _profileTr(language, en: 'Address', sw: 'Anwani', fr: 'Adresse', ar: 'العنوان'),
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
                      _profileTr(
                        language,
                        en: 'Save Changes',
                        sw: 'Hifadhi Mabadiliko',
                        fr: 'Enregistrer les modifications',
                        ar: 'حفظ التغييرات',
                      ),
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
