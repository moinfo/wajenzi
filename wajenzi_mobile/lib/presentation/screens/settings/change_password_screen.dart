import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/auth_provider.dart';
import '../../providers/settings_provider.dart';

String _passwordTr(
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

class ChangePasswordScreen extends ConsumerStatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  ConsumerState<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends ConsumerState<ChangePasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  bool _obscureCurrent = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;
  bool _isSaving = false;

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);

    final error = await ref.read(authStateProvider.notifier).changePassword(
          currentPassword: _currentPasswordController.text,
          newPassword: _newPasswordController.text,
          newPasswordConfirmation: _confirmPasswordController.text,
        );

    setState(() => _isSaving = false);

    if (!mounted) return;

    final language = ref.read(currentLanguageProvider);

    if (error == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _passwordTr(
              language,
              en: 'Password changed successfully',
              sw: 'Nenosiri limebadilishwa',
              fr: 'Mot de passe modifie avec succes',
              ar: 'تم تغيير كلمة المرور بنجاح',
            ),
          ),
          backgroundColor: AppColors.success,
        ),
      );
      Navigator.of(context).pop();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error), backgroundColor: AppColors.error),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final language = ref.watch(currentLanguageProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _passwordTr(
            language,
            en: 'Change Password',
            sw: 'Badilisha Nenosiri',
            fr: 'Changer le mot de passe',
            ar: 'تغيير كلمة المرور',
          ),
        ),
        leading: context.canPop()
            ? IconButton(
                icon: const Icon(Icons.arrow_back_rounded),
                onPressed: () => context.pop(),
              )
            : IconButton(
                icon: const Icon(Icons.arrow_back_rounded),
                onPressed: () => context.go('/settings'),
              ),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Lock icon header
            Center(
              child: Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.lock_outline, size: 48, color: AppColors.primary),
              ),
            ),
            const SizedBox(height: 8),
            Center(
              child: Text(
                _passwordTr(
                  language,
                  en: 'Enter your current password and a new password',
                  sw: 'Weka nenosiri lako la sasa na nenosiri jipya',
                  fr: 'Saisissez votre mot de passe actuel et un nouveau mot de passe',
                  ar: 'أدخل كلمة المرور الحالية وكلمة مرور جديدة',
                ),
                style: TextStyle(color: AppColors.textSecondary, fontSize: 14),
                textAlign: TextAlign.center,
              ),
            ),
            const SizedBox(height: 24),

            TextFormField(
              controller: _currentPasswordController,
              obscureText: _obscureCurrent,
              decoration: InputDecoration(
                labelText: _passwordTr(
                  language,
                  en: 'Current Password',
                  sw: 'Nenosiri la Sasa',
                  fr: 'Mot de passe actuel',
                  ar: 'كلمة المرور الحالية',
                ),
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  icon: Icon(_obscureCurrent ? Icons.visibility_off : Icons.visibility),
                  onPressed: () => setState(() => _obscureCurrent = !_obscureCurrent),
                ),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              validator: (v) => v == null || v.isEmpty
                  ? _passwordTr(
                      language,
                      en: 'Current password is required',
                      sw: 'Nenosiri la sasa linahitajika',
                      fr: 'Le mot de passe actuel est requis',
                      ar: 'كلمة المرور الحالية مطلوبة',
                    )
                  : null,
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _newPasswordController,
              obscureText: _obscureNew,
              decoration: InputDecoration(
                labelText: _passwordTr(
                  language,
                  en: 'New Password',
                  sw: 'Nenosiri Jipya',
                  fr: 'Nouveau mot de passe',
                  ar: 'كلمة المرور الجديدة',
                ),
                prefixIcon: const Icon(Icons.lock_reset),
                suffixIcon: IconButton(
                  icon: Icon(_obscureNew ? Icons.visibility_off : Icons.visibility),
                  onPressed: () => setState(() => _obscureNew = !_obscureNew),
                ),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              validator: (v) {
                if (v == null || v.isEmpty) {
                  return _passwordTr(
                    language,
                    en: 'New password is required',
                    sw: 'Nenosiri jipya linahitajika',
                    fr: 'Le nouveau mot de passe est requis',
                    ar: 'كلمة المرور الجديدة مطلوبة',
                  );
                }
                if (v.length < 8) {
                  return _passwordTr(
                    language,
                    en: 'Password must be at least 8 characters',
                    sw: 'Nenosiri lazima liwe angalau herufi 8',
                    fr: 'Le mot de passe doit comporter au moins 8 caracteres',
                    ar: 'يجب أن تكون كلمة المرور 8 أحرف على الأقل',
                  );
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            TextFormField(
              controller: _confirmPasswordController,
              obscureText: _obscureConfirm,
              decoration: InputDecoration(
                labelText: _passwordTr(
                  language,
                  en: 'Confirm New Password',
                  sw: 'Thibitisha Nenosiri Jipya',
                  fr: 'Confirmer le nouveau mot de passe',
                  ar: 'تأكيد كلمة المرور الجديدة',
                ),
                prefixIcon: const Icon(Icons.lock_reset),
                suffixIcon: IconButton(
                  icon: Icon(_obscureConfirm ? Icons.visibility_off : Icons.visibility),
                  onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
                ),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              validator: (v) {
                if (v == null || v.isEmpty) {
                  return _passwordTr(
                    language,
                    en: 'Please confirm your password',
                    sw: 'Tafadhali thibitisha nenosiri',
                    fr: 'Veuillez confirmer votre mot de passe',
                    ar: 'يرجى تأكيد كلمة المرور',
                  );
                }
                if (v != _newPasswordController.text) {
                  return _passwordTr(
                    language,
                    en: 'Passwords do not match',
                    sw: 'Nenosiri hazilingani',
                    fr: 'Les mots de passe ne correspondent pas',
                    ar: 'كلمتا المرور غير متطابقتين',
                  );
                }
                return null;
              },
            ),
            const SizedBox(height: 32),

            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _isSaving ? null : _submit,
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
                        _passwordTr(
                          language,
                          en: 'Change Password',
                          sw: 'Badilisha Nenosiri',
                          fr: 'Changer le mot de passe',
                          ar: 'تغيير كلمة المرور',
                        ),
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
