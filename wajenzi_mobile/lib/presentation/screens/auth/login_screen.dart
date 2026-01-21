import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isLoading = false;
  bool _isDarkMode = false;
  bool _isSwahili = false;

  // Translations
  Map<String, String> get _t => _isSwahili
      ? {
          'welcome': 'Karibu Tena',
          'signInContinue': 'Ingia kuendelea',
          'email': 'Barua Pepe',
          'password': 'Nywila',
          'forgotPassword': 'Umesahau Nywila?',
          'signIn': 'Ingia',
          'tagline': 'Wataalamu wa Uthabiti na Ubora',
        }
      : {
          'welcome': 'Welcome Back',
          'signInContinue': 'Sign in to continue',
          'email': 'Email Address',
          'password': 'Password',
          'forgotPassword': 'Forgot Password?',
          'signIn': 'Sign In',
          'tagline': 'Masters of Consistency and Quality',
        };

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final success = await ref.read(authStateProvider.notifier).login(
          _emailController.text.trim(),
          _passwordController.text,
        );

    if (!mounted) return;

    setState(() => _isLoading = false);

    if (success) {
      context.go('/dashboard');
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authStateProvider);
    final error = authState.valueOrNull?.error;
    final size = MediaQuery.of(context).size;

    // Dark mode colors
    final gradientColors = _isDarkMode
        ? [const Color(0xFF0D1B2A), const Color(0xFF1B263B), const Color(0xFF415A77)]
        : [const Color(0xFF3498DB), const Color(0xFF1ABC9C), const Color(0xFF2ECC71)];

    final cardColor = _isDarkMode ? const Color(0xFF1B263B).withValues(alpha: 0.95) : const Color(0xFFE8F8F5);  // Light mint card
    final textColor = _isDarkMode ? Colors.white : const Color(0xFF2C3E50);
    final subtextColor = _isDarkMode ? const Color(0xFF778DA9) : Colors.grey.shade500;
    final inputBgColor = _isDarkMode ? const Color(0xFF0D1B2A) : Colors.white;  // White inputs on tinted card
    final inputBorderColor = _isDarkMode ? const Color(0xFF415A77) : const Color(0xFF1ABC9C).withValues(alpha: 0.4);
    final inputHintColor = _isDarkMode ? const Color(0xFF778DA9) : Colors.grey.shade400;

    return Scaffold(
      body: Container(
        height: size.height,
        width: size.width,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: gradientColors,
            stops: const [0.0, 0.5, 1.0],
          ),
        ),
        child: Stack(
          children: [
            // Animated background shapes
            Positioned(
              top: -size.height * 0.15,
              right: -size.width * 0.3,
              child: Container(
                width: size.width * 0.8,
                height: size.width * 0.8,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      Colors.white.withValues(alpha: 0.15),
                      Colors.white.withValues(alpha: 0.0),
                    ],
                  ),
                ),
              ),
            ),
            Positioned(
              bottom: size.height * 0.3,
              left: -size.width * 0.4,
              child: Container(
                width: size.width * 0.7,
                height: size.width * 0.7,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: RadialGradient(
                    colors: [
                      const Color(0xFF2ECC71).withValues(alpha: 0.2),
                      Colors.transparent,
                    ],
                  ),
                ),
              ),
            ),
            // Floating particles effect
            ...List.generate(6, (index) {
              final random = math.Random(index);
              return Positioned(
                top: random.nextDouble() * size.height * 0.4,
                left: random.nextDouble() * size.width,
                child: Container(
                  width: 4 + random.nextDouble() * 8,
                  height: 4 + random.nextDouble() * 8,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: 0.1 + random.nextDouble() * 0.2),
                  ),
                ),
              );
            }),
            // Main content
            SafeArea(
              child: SingleChildScrollView(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 28),
                  child: Column(
                    children: [
                      const SizedBox(height: 8),
                      // Top bar with dark mode and language toggle
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          // Dark mode toggle
                          GestureDetector(
                            onTap: () => setState(() => _isDarkMode = !_isDarkMode),
                            child: Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Icon(
                                _isDarkMode ? Icons.light_mode_rounded : Icons.dark_mode_rounded,
                                color: Colors.white,
                                size: 22,
                              ),
                            ),
                          ),
                          // Language toggle
                          GestureDetector(
                            onTap: () => setState(() => _isSwahili = !_isSwahili),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    _isSwahili ? '🇹🇿' : '🇬🇧',
                                    style: const TextStyle(fontSize: 20),
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    _isSwahili ? 'SW' : 'EN',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w600,
                                      fontSize: 14,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                      SizedBox(height: size.height * 0.03),
                      // Logo with glow effect
                      Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: _isDarkMode
                                  ? const Color(0xFF4CC9F0).withValues(alpha: 0.5)
                                  : const Color(0xFF1ABC9C).withValues(alpha: 0.4),
                              blurRadius: _isDarkMode ? 40 : 30,
                              spreadRadius: _isDarkMode ? 8 : 5,
                            ),
                          ],
                        ),
                        child: Container(
                          width: 90,
                          height: 90,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.2),
                                blurRadius: 20,
                                offset: const Offset(0, 10),
                              ),
                            ],
                          ),
                          child: ClipOval(
                            child: Padding(
                              padding: const EdgeInsets.all(15),
                              child: Image.asset(
                                'assets/images/logo.png',
                                fit: BoxFit.contain,
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 14),
                      // App name with shimmer-like effect
                      ShaderMask(
                        shaderCallback: (bounds) => const LinearGradient(
                          colors: [Colors.white, Color(0xFF80DEEA)],
                        ).createShader(bounds),
                        child: const Text(
                          'WAJENZI',
                          style: TextStyle(
                            fontSize: 36,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                            letterSpacing: 6,
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        _t['tagline']!,
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.white.withValues(alpha: 0.85),
                          letterSpacing: 1.5,
                          fontWeight: FontWeight.w400,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: size.height * 0.03),
                      // Login card
                      Container(
                        padding: const EdgeInsets.all(28),
                        decoration: BoxDecoration(
                          color: cardColor,
                          borderRadius: BorderRadius.circular(24),
                          border: _isDarkMode
                              ? Border.all(
                                  color: const Color(0xFF415A77).withValues(alpha: 0.5),
                                  width: 1,
                                )
                              : null,
                          boxShadow: [
                            BoxShadow(
                              color: _isDarkMode
                                  ? const Color(0xFF4CC9F0).withValues(alpha: 0.1)
                                  : Colors.black.withValues(alpha: 0.15),
                              blurRadius: 40,
                              offset: const Offset(0, 20),
                            ),
                          ],
                        ),
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Text(
                                _t['welcome']!,
                                style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: textColor,
                                ),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 6),
                              Text(
                                _t['signInContinue']!,
                                style: TextStyle(
                                  fontSize: 14,
                                  color: subtextColor,
                                ),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 28),
                              // Error message
                              if (error != null) ...[
                                Container(
                                  padding: const EdgeInsets.all(14),
                                  decoration: BoxDecoration(
                                    color: AppColors.error.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(
                                      color: AppColors.error.withValues(alpha: 0.3),
                                    ),
                                  ),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.error_outline,
                                          color: AppColors.error, size: 20),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Text(
                                          error,
                                          style: const TextStyle(
                                            color: AppColors.error,
                                            fontSize: 13,
                                          ),
                                        ),
                                      ),
                                      GestureDetector(
                                        onTap: () {
                                          ref.read(authStateProvider.notifier).clearError();
                                        },
                                        child: const Icon(Icons.close,
                                            size: 18, color: AppColors.error),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 20),
                              ],
                              // Email field
                              _buildTextField(
                                controller: _emailController,
                                hint: _t['email']!,
                                icon: Icons.email_outlined,
                                keyboardType: TextInputType.emailAddress,
                                bgColor: inputBgColor,
                                borderColor: inputBorderColor,
                                textColor: textColor,
                                hintColor: inputHintColor,
                                isDarkMode: _isDarkMode,
                                validator: (value) {
                                  if (value == null || value.isEmpty) {
                                    return _isSwahili ? 'Tafadhali weka barua pepe' : 'Please enter your email';
                                  }
                                  if (!value.contains('@')) {
                                    return _isSwahili ? 'Tafadhali weka barua pepe halali' : 'Please enter a valid email';
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 18),
                              // Password field
                              _buildTextField(
                                controller: _passwordController,
                                hint: _t['password']!,
                                icon: Icons.lock_outline,
                                isPassword: true,
                                obscureText: _obscurePassword,
                                bgColor: inputBgColor,
                                borderColor: inputBorderColor,
                                textColor: textColor,
                                hintColor: inputHintColor,
                                isDarkMode: _isDarkMode,
                                onTogglePassword: () {
                                  setState(() => _obscurePassword = !_obscurePassword);
                                },
                                onSubmit: (_) => _handleLogin(),
                                validator: (value) {
                                  if (value == null || value.isEmpty) {
                                    return _isSwahili ? 'Tafadhali weka nywila' : 'Please enter your password';
                                  }
                                  if (value.length < 6) {
                                    return _isSwahili ? 'Nywila lazima iwe angalau herufi 6' : 'Password must be at least 6 characters';
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 12),
                              // Forgot password
                              Align(
                                alignment: Alignment.centerRight,
                                child: TextButton(
                                  onPressed: () {},
                                  style: TextButton.styleFrom(
                                    padding: EdgeInsets.zero,
                                    minimumSize: Size.zero,
                                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                  ),
                                  child: Text(
                                    _t['forgotPassword']!,
                                    style: const TextStyle(
                                      color: Color(0xFF1ABC9C),
                                      fontSize: 13,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 24),
                              // Sign in button
                              Container(
                                height: 56,
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(16),
                                  gradient: _isLoading
                                      ? null
                                      : LinearGradient(
                                          colors: _isDarkMode
                                              ? [
                                                  const Color(0xFF4361EE),
                                                  const Color(0xFF4CC9F0),
                                                  const Color(0xFF2ECC71),
                                                ]
                                              : [
                                                  const Color(0xFF3498DB),
                                                  const Color(0xFF1ABC9C),
                                                  const Color(0xFF2ECC71),
                                                ],
                                        ),
                                  color: _isLoading
                                      ? (_isDarkMode ? const Color(0xFF415A77) : Colors.grey.shade300)
                                      : null,
                                  boxShadow: _isLoading
                                      ? null
                                      : [
                                          BoxShadow(
                                            color: _isDarkMode
                                                ? const Color(0xFF4CC9F0).withValues(alpha: 0.4)
                                                : const Color(0xFF1ABC9C).withValues(alpha: 0.4),
                                            blurRadius: 20,
                                            offset: const Offset(0, 10),
                                          ),
                                        ],
                                ),
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _handleLogin,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.transparent,
                                    shadowColor: Colors.transparent,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                  ),
                                  child: _isLoading
                                      ? const SizedBox(
                                          height: 24,
                                          width: 24,
                                          child: CircularProgressIndicator(
                                            strokeWidth: 2.5,
                                            color: Colors.white,
                                          ),
                                        )
                                      : Row(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            Text(
                                              _t['signIn']!,
                                              style: const TextStyle(
                                                fontSize: 17,
                                                fontWeight: FontWeight.w600,
                                                color: Colors.white,
                                                letterSpacing: 1,
                                              ),
                                            ),
                                            const SizedBox(width: 10),
                                            const Icon(
                                              Icons.arrow_forward_rounded,
                                              color: Colors.white,
                                              size: 22,
                                            ),
                                          ],
                                        ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      // Footer
                      Column(
                        children: [
                          Text(
                            'Powered by',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.5),
                              fontSize: 11,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Moinfotech',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.8),
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              letterSpacing: 1,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'v1.0.0',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.6),
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                      SizedBox(height: MediaQuery.of(context).padding.bottom + 30),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType? keyboardType,
    bool isPassword = false,
    bool obscureText = false,
    VoidCallback? onTogglePassword,
    ValueChanged<String>? onSubmit,
    String? Function(String?)? validator,
    Color? bgColor,
    Color? borderColor,
    Color? textColor,
    Color? hintColor,
    bool isDarkMode = false,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: bgColor ?? const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: borderColor ?? const Color(0xFFBDC3C7),
          width: isDarkMode ? 1.5 : 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: isDarkMode
                ? Colors.black.withValues(alpha: 0.2)
                : Colors.black.withValues(alpha: 0.05),
            blurRadius: isDarkMode ? 8 : 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        obscureText: obscureText,
        textInputAction: isPassword ? TextInputAction.done : TextInputAction.next,
        onFieldSubmitted: onSubmit,
        style: TextStyle(
          fontSize: 15,
          color: textColor ?? const Color(0xFF2C3E50),
        ),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(
            color: hintColor ?? Colors.grey.shade400,
            fontSize: 15,
          ),
          prefixIcon: Container(
            margin: const EdgeInsets.only(left: 4),
            child: Icon(
              icon,
              color: isDarkMode ? const Color(0xFF4CC9F0) : const Color(0xFF1ABC9C),
              size: 22,
            ),
          ),
          suffixIcon: isPassword
              ? IconButton(
                  icon: Icon(
                    obscureText ? Icons.visibility_off_outlined : Icons.visibility_outlined,
                    color: hintColor ?? Colors.grey.shade400,
                    size: 22,
                  ),
                  onPressed: onTogglePassword,
                )
              : null,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
        ),
        validator: validator,
      ),
    );
  }
}
