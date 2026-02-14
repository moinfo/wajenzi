import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class LandingTopBar extends StatelessWidget implements PreferredSizeWidget {
  final bool isDarkMode;
  final bool isSwahili;
  final VoidCallback onDarkModeToggle;
  final VoidCallback onLanguageToggle;
  final bool showBackButton;
  final Widget? flagWidget;

  const LandingTopBar({
    super.key,
    required this.isDarkMode,
    required this.isSwahili,
    required this.onDarkModeToggle,
    required this.onLanguageToggle,
    this.showBackButton = false,
    this.flagWidget,
  });

  Color get _textPrimaryColor => isDarkMode ? Colors.white : const Color(0xFF2C3E50);
  Color get _textSecondaryColor => isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D);
  Color get _appBarBgColor => isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);

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
              color: const Color(0xFF1ABC9C).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFF1ABC9C).withValues(alpha: 0.3),
              ),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Padding(
                padding: const EdgeInsets.all(4),
                child: Image.asset(
                  'assets/images/logo.png',
                  fit: BoxFit.contain,
                  errorBuilder: (_, __, ___) => const Icon(
                    Icons.business,
                    color: Color(0xFF1ABC9C),
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
                  'WAJENZI',
                  style: TextStyle(
                    color: Color(0xFF1ABC9C),
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 1.5,
                  ),
                ),
                Text(
                  isSwahili
                      ? 'Mabingwa wa Uthabiti na Ubora'
                      : 'Masters of Consistency and Quality',
                  style: TextStyle(
                    color: _textSecondaryColor,
                    fontSize: 8,
                    fontStyle: FontStyle.italic,
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
        // Language Toggle with National Flags
        _buildTopBarButton(
          context: context,
          onTap: onLanguageToggle,
          child: Row(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (flagWidget != null) ...[
                ClipRRect(
                  borderRadius: BorderRadius.circular(2),
                  child: SizedBox(
                    width: 20,
                    height: 13,
                    child: flagWidget,
                  ),
                ),
                const SizedBox(width: 3),
              ],
              Text(
                isSwahili ? 'SW' : 'EN',
                style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w600,
                  color: _textPrimaryColor,
                ),
              ),
            ],
          ),
          isWide: true,
        ),
        const SizedBox(width: 8),
        // Dark Mode Toggle
        _buildTopBarButton(
          context: context,
          onTap: onDarkModeToggle,
          child: Icon(
            isDarkMode ? Icons.dark_mode : Icons.light_mode,
            size: 20,
            color: isDarkMode
                ? const Color(0xFF1ABC9C)
                : const Color(0xFFF39C12),
          ),
        ),
        const SizedBox(width: 8),
        // Login Icon Button
        _buildTopBarButton(
          context: context,
          onTap: () => context.go('/login'),
          child: Icon(
            Icons.login_rounded,
            size: 20,
            color: isDarkMode ? Colors.white : const Color(0xFF2C3E50),
          ),
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

// Tanzania Flag Widget
class TanzaniaFlag extends StatelessWidget {
  const TanzaniaFlag({super.key});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: _TanzaniaFlagPainter(),
    );
  }
}

// UK Flag Widget
class UKFlag extends StatelessWidget {
  const UKFlag({super.key});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: _UKFlagPainter(),
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

    canvas.drawLine(Offset.zero, Offset(size.width, size.height), whiteDiagonal);
    canvas.drawLine(Offset(size.width, 0), Offset(0, size.height), whiteDiagonal);

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

    canvas.drawLine(Offset(size.width / 2, 0), Offset(size.width / 2, size.height), whiteCross);
    canvas.drawLine(Offset(0, size.height / 2), Offset(size.width, size.height / 2), whiteCross);

    // Red cross (horizontal and vertical)
    final redCross = Paint()
      ..color = const Color(0xFFC8102E)
      ..strokeWidth = size.height * 0.2
      ..style = PaintingStyle.stroke;

    canvas.drawLine(Offset(size.width / 2, 0), Offset(size.width / 2, size.height), redCross);
    canvas.drawLine(Offset(0, size.height / 2), Offset(size.width, size.height / 2), redCross);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
