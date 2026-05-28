import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../providers/settings_provider.dart';

class LandingTopBar extends StatelessWidget implements PreferredSizeWidget {
  final bool isDarkMode;
  final AppLanguage language;
  final VoidCallback onDarkModeToggle;
  final ValueChanged<AppLanguage> onLanguageChanged;
  final bool showBackButton;

  const LandingTopBar({
    super.key,
    required this.isDarkMode,
    required this.language,
    required this.onDarkModeToggle,
    required this.onLanguageChanged,
    this.showBackButton = false,
  });

  bool get isSwahili => language == AppLanguage.swahili;
  Color get _textSecondaryColor =>
      isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D);
  Color get _appBarBgColor =>
      isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);

  String get _tagline => switch (language) {
    AppLanguage.swahili => 'Ujenzi · Usanifu · Uhandisi',
    AppLanguage.french => 'Construction · Architecture · Ingénierie',
    AppLanguage.arabic => 'البناء · العمارة · الهندسة',
    AppLanguage.english => 'Construction · Architecture · Engineering',
  };

  @override
  Size get preferredSize => const Size.fromHeight(70);

  @override
  Widget build(BuildContext context) {
    return AppBar(
      backgroundColor: _appBarBgColor,
      elevation: 0,
      toolbarHeight: 70,
      automaticallyImplyLeading: false,
      title: Row(
        children: [
          if (showBackButton) ...[
            _buildTopBarButton(
              context: context,
              onTap: () => Navigator.of(context).pop(),
              child: Icon(
                Icons.arrow_back_rounded,
                size: 20,
                color: isDarkMode ? Colors.white : const Color(0xFF2C3E50),
              ),
            ),
            const SizedBox(width: 12),
          ],
          // Logo
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFF193340).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFF193340).withValues(alpha: 0.3),
              ),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Padding(
                padding: const EdgeInsets.all(4),
                child: Image.asset(
                  'assets/images/logo.png',
                  fit: BoxFit.contain,
                  errorBuilder: (_, _, _) => const Icon(
                    Icons.business,
                    color: Color(0xFF193340),
                    size: 24,
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          // Name and Motto
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'Wajenzi Professionals',
                  style: TextStyle(
                    color: Color(0xFF193340),
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    letterSpacing: -0.2,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 1),
                Text(
                  _tagline,
                  style: TextStyle(
                    color: _textSecondaryColor,
                    fontSize: 10,
                    fontWeight: FontWeight.w500,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
      actions: [
        LandingActionsMenu(
          isDarkMode: isDarkMode,
          language: language,
          onDarkModeToggle: onDarkModeToggle,
          onLanguageChanged: onLanguageChanged,
          onLogin: () => context.go('/login'),
        ),
        const SizedBox(width: 12),
      ],
    );
  }

  Widget _buildTopBarButton({
    required BuildContext context,
    required VoidCallback onTap,
    required Widget child,
    bool isWide = false,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: isWide ? null : 40,
        height: 40,
        padding: isWide ? const EdgeInsets.symmetric(horizontal: 8) : null,
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF16213E) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isDarkMode
                ? Colors.white.withValues(alpha: 0.15)
                : Colors.grey.withValues(alpha: 0.25),
            width: 1,
          ),
        ),
        child: child,
      ),
    );
  }
}

/// Consolidated 3-dot (⋮) overflow menu: language, theme toggle and login —
/// shared by the landing screen and the shared [LandingTopBar].
class LandingActionsMenu extends StatelessWidget {
  final bool isDarkMode;
  final AppLanguage language;
  final VoidCallback onDarkModeToggle;
  final ValueChanged<AppLanguage> onLanguageChanged;
  final VoidCallback onLogin;

  const LandingActionsMenu({
    super.key,
    required this.isDarkMode,
    required this.language,
    required this.onDarkModeToggle,
    required this.onLanguageChanged,
    required this.onLogin,
  });

  static Widget _flag(AppLanguage v) => switch (v) {
    AppLanguage.english => const UKFlag(),
    AppLanguage.swahili => const TanzaniaFlag(),
    AppLanguage.french => const FranceFlag(),
    AppLanguage.arabic => const ArabicLanguageBadge(),
  };

  static String _label(AppLanguage v) => switch (v) {
    AppLanguage.english => 'English',
    AppLanguage.swahili => 'Kiswahili',
    AppLanguage.french => 'Français',
    AppLanguage.arabic => 'العربية',
  };

  @override
  Widget build(BuildContext context) {
    final txt = isDarkMode ? Colors.white : const Color(0xFF193340);
    final popupBg = isDarkMode ? const Color(0xFF1F2A44) : Colors.white;

    PopupMenuItem<String> langItem(AppLanguage l) {
      final active = l == language;
      return PopupMenuItem<String>(
        value: 'lang_${l.name}',
        height: 44,
        child: Row(
          children: [
            SizedBox(
              width: 22,
              height: 15,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(2),
                child: _flag(l),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                _label(l),
                style: TextStyle(
                  color: txt,
                  fontSize: 13,
                  fontWeight: active ? FontWeight.w700 : FontWeight.w500,
                ),
              ),
            ),
            if (active)
              const Icon(Icons.check_rounded, size: 16, color: Color(0xFF3BA154)),
          ],
        ),
      );
    }

    return PopupMenuButton<String>(
      tooltip: 'Menu',
      color: popupBg,
      elevation: 8,
      offset: const Offset(0, 50),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      onSelected: (v) {
        if (v.startsWith('lang_')) {
          final l = AppLanguage.values.firstWhere(
            (e) => 'lang_${e.name}' == v,
            orElse: () => AppLanguage.english,
          );
          onLanguageChanged(l);
        } else if (v == 'theme') {
          onDarkModeToggle();
        } else if (v == 'login') {
          onLogin();
        }
      },
      itemBuilder: (context) => [
        PopupMenuItem<String>(
          enabled: false,
          height: 28,
          child: Text(
            'LANGUAGE',
            style: TextStyle(
              fontSize: 10,
              letterSpacing: 1.2,
              fontWeight: FontWeight.w700,
              color: isDarkMode ? Colors.white38 : Colors.grey.shade500,
            ),
          ),
        ),
        for (final l in AppLanguage.values) langItem(l),
        const PopupMenuDivider(),
        PopupMenuItem<String>(
          value: 'theme',
          height: 44,
          child: Row(
            children: [
              Icon(
                isDarkMode ? Icons.light_mode_rounded : Icons.dark_mode_rounded,
                size: 18,
                color: const Color(0xFFFECC04),
              ),
              const SizedBox(width: 10),
              Text(
                isDarkMode ? 'Light mode' : 'Dark mode',
                style: TextStyle(color: txt, fontSize: 13, fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
        const PopupMenuDivider(),
        PopupMenuItem<String>(
          value: 'login',
          height: 44,
          child: Row(
            children: [
              const Icon(Icons.login_rounded, size: 18, color: Color(0xFF3BA154)),
              const SizedBox(width: 10),
              Text(
                'Login',
                style: TextStyle(color: txt, fontSize: 13, fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ],
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF16213E) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isDarkMode
                ? Colors.white.withValues(alpha: 0.15)
                : Colors.grey.withValues(alpha: 0.25),
            width: 1,
          ),
        ),
        child: Icon(
          Icons.more_vert_rounded,
          size: 20,
          color: isDarkMode ? Colors.white : const Color(0xFF193340),
        ),
      ),
    );
  }
}

// Tanzania Flag Widget
class TanzaniaFlag extends StatelessWidget {
  const TanzaniaFlag({super.key});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(painter: _TanzaniaFlagPainter());
  }
}

// UK Flag Widget
class UKFlag extends StatelessWidget {
  const UKFlag({super.key});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(painter: _UKFlagPainter());
  }
}

class FranceFlag extends StatelessWidget {
  const FranceFlag({super.key});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(painter: _FranceFlagPainter());
  }
}

class ArabicLanguageBadge extends StatelessWidget {
  const ArabicLanguageBadge({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFF006C35),
      alignment: Alignment.center,
      child: const Text(
        'ع',
        style: TextStyle(
          color: Colors.white,
          fontSize: 10,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}

// Custom painter for Tanzania flag diagonal stripes
class _TanzaniaFlagPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final greenPaint = Paint()..color = const Color(0xFF1EB53A);
    final bluePaint = Paint()..color = const Color(0xFF00A3DD);
    final blackPaint = Paint()..color = const Color(0xFF000000);
    final yellowPaint = Paint()..color = const Color(0xFFFCD116);

    // Draw green triangle (top-left)
    final greenPath = Path()
      ..moveTo(0, 0)
      ..lineTo(size.width, 0)
      ..lineTo(0, size.height)
      ..close();
    canvas.drawPath(greenPath, greenPaint);

    // Draw blue triangle (bottom-right)
    final bluePath = Path()
      ..moveTo(size.width, 0)
      ..lineTo(size.width, size.height)
      ..lineTo(0, size.height)
      ..close();
    canvas.drawPath(bluePath, bluePaint);

    // Yellow outer stripe
    final yellowPath1 = Path()
      ..moveTo(0, size.height * 0.3)
      ..lineTo(size.width * 0.7, 0)
      ..lineTo(size.width * 0.8, 0)
      ..lineTo(0, size.height * 0.45)
      ..close();
    canvas.drawPath(yellowPath1, yellowPaint);

    final yellowPath2 = Path()
      ..moveTo(size.width * 0.2, size.height)
      ..lineTo(size.width, size.height * 0.55)
      ..lineTo(size.width, size.height * 0.7)
      ..lineTo(size.width * 0.3, size.height)
      ..close();
    canvas.drawPath(yellowPath2, yellowPaint);

    // Black stripe (center)
    final blackPath = Path()
      ..moveTo(0, size.height * 0.45)
      ..lineTo(size.width * 0.8, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height * 0.3)
      ..lineTo(size.width * 0.2, size.height)
      ..lineTo(0, size.height)
      ..lineTo(0, size.height * 0.7)
      ..close();
    canvas.drawPath(blackPath, blackPaint);

    // Inner yellow stripes
    final yellowInner1 = Path()
      ..moveTo(0, size.height * 0.55)
      ..lineTo(size.width * 0.88, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height * 0.15)
      ..lineTo(0, size.height * 0.68)
      ..close();
    canvas.drawPath(yellowInner1, yellowPaint);

    final yellowInner2 = Path()
      ..moveTo(0, size.height * 0.85)
      ..lineTo(size.width * 0.12, size.height)
      ..lineTo(size.width * 0.0, size.height)
      ..close();
    canvas.drawPath(yellowInner2, yellowPaint);

    final yellowInner3 = Path()
      ..moveTo(size.width, size.height * 0.32)
      ..lineTo(size.width, size.height * 0.45)
      ..lineTo(size.width * 0.12, size.height)
      ..lineTo(0, size.height)
      ..lineTo(0, size.height * 0.85)
      ..close();
    canvas.drawPath(yellowInner3, yellowPaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

// Custom painter for UK flag
class _UKFlagPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    // Blue background
    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width, size.height),
      Paint()..color = const Color(0xFF012169),
    );

    // White diagonal cross
    final whiteDiagonal = Paint()
      ..color = Colors.white
      ..strokeWidth = size.height * 0.2
      ..style = PaintingStyle.stroke;

    canvas.drawLine(
      Offset.zero,
      Offset(size.width, size.height),
      whiteDiagonal,
    );
    canvas.drawLine(
      Offset(size.width, 0),
      Offset(0, size.height),
      whiteDiagonal,
    );

    // Red diagonal cross (thinner)
    final redDiagonal = Paint()
      ..color = const Color(0xFFC8102E)
      ..strokeWidth = size.height * 0.08
      ..style = PaintingStyle.stroke;

    canvas.drawLine(Offset.zero, Offset(size.width, size.height), redDiagonal);
    canvas.drawLine(Offset(size.width, 0), Offset(0, size.height), redDiagonal);

    // White cross (horizontal and vertical)
    final whiteCross = Paint()
      ..color = Colors.white
      ..strokeWidth = size.height * 0.35
      ..style = PaintingStyle.stroke;

    canvas.drawLine(
      Offset(size.width / 2, 0),
      Offset(size.width / 2, size.height),
      whiteCross,
    );
    canvas.drawLine(
      Offset(0, size.height / 2),
      Offset(size.width, size.height / 2),
      whiteCross,
    );

    // Red cross (horizontal and vertical)
    final redCross = Paint()
      ..color = const Color(0xFFC8102E)
      ..strokeWidth = size.height * 0.2
      ..style = PaintingStyle.stroke;

    canvas.drawLine(
      Offset(size.width / 2, 0),
      Offset(size.width / 2, size.height),
      redCross,
    );
    canvas.drawLine(
      Offset(0, size.height / 2),
      Offset(size.width, size.height / 2),
      redCross,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _FranceFlagPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final bluePaint = Paint()..color = const Color(0xFF0055A4);
    final whitePaint = Paint()..color = Colors.white;
    final redPaint = Paint()..color = const Color(0xFFEF4135);

    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width / 3, size.height),
      bluePaint,
    );
    canvas.drawRect(
      Rect.fromLTWH(size.width / 3, 0, size.width / 3, size.height),
      whitePaint,
    );
    canvas.drawRect(
      Rect.fromLTWH((size.width / 3) * 2, 0, size.width / 3, size.height),
      redPaint,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
