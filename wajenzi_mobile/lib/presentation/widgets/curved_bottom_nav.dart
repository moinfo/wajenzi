import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class CurvedBottomNav extends StatefulWidget {
  final int selectedIndex;
  final bool isDarkMode;
  final bool isSwahili;
  final Function(int)? onItemTapped;

  const CurvedBottomNav({
    super.key,
    this.selectedIndex = 0,
    this.isDarkMode = false,
    this.isSwahili = false,
    this.onItemTapped,
  });

  @override
  State<CurvedBottomNav> createState() => _CurvedBottomNavState();
}

class _CurvedBottomNavState extends State<CurvedBottomNav> {
  late int _selectedMenuIndex;

  List<String> get _menuLabels => widget.isSwahili
      ? ['Nyumbani', 'Miradi', 'Huduma', 'Kuhusu', 'Tuzo']
      : ['Home', 'Projects', 'Services', 'About', 'Awards'];

  final List<IconData> _menuIcons = [
    Icons.home_rounded,
    Icons.business_rounded,
    Icons.design_services_rounded,
    Icons.info_outline_rounded,
    Icons.emoji_events_rounded,
  ];

  // Route paths for each menu item
  final List<String?> _menuRoutes = [
    '/',          // Home - Landing
    '/projects',  // Projects
    '/services',  // Services
    '/about',     // About
    '/awards',    // Awards
  ];

  @override
  void initState() {
    super.initState();
    _selectedMenuIndex = widget.selectedIndex;
  }

  @override
  void didUpdateWidget(CurvedBottomNav oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.selectedIndex != widget.selectedIndex) {
      _selectedMenuIndex = widget.selectedIndex;
    }
  }

  void _onItemTapped(int index) {
    setState(() => _selectedMenuIndex = index);

    if (widget.onItemTapped != null) {
      widget.onItemTapped!(index);
    }

    // Navigate to the route if defined
    final route = _menuRoutes[index];
    if (route != null) {
      if (route == '/') {
        context.go(route);
      } else {
        context.push(route);
      }
    } else {
      // Show coming soon message for unimplemented routes
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            widget.isSwahili ? 'Inakuja hivi karibuni!' : 'Coming soon!',
          ),
          duration: const Duration(seconds: 1),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomPadding = MediaQuery.of(context).padding.bottom;

    // Reorder items so active is always in center (index 2)
    List<int> getReorderedIndices() {
      final itemCount = _menuLabels.length;
      final centerIndex = itemCount ~/ 2;
      final offset = _selectedMenuIndex - centerIndex;

      List<int> indices = [];
      for (int i = 0; i < itemCount; i++) {
        int newIndex = (i + offset) % itemCount;
        if (newIndex < 0) newIndex += itemCount;
        indices.add(newIndex);
      }
      return indices;
    }

    final reorderedIndices = getReorderedIndices();

    return SizedBox(
      height: 90 + bottomPadding,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          // Glassmorphism background with curved notch
          Positioned(
            top: 25,
            left: 0,
            right: 0,
            bottom: 0,
            child: ClipPath(
              clipper: _CurvedNavClipper(
                activeIndex: 2,
                itemCount: _menuLabels.length,
              ),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: widget.isDarkMode
                          ? [
                              Colors.black.withValues(alpha: 0.75),
                              Colors.black.withValues(alpha: 0.9),
                            ]
                          : [
                              Colors.white.withValues(alpha: 0.85),
                              Colors.white.withValues(alpha: 0.95),
                            ],
                    ),
                  ),
                ),
              ),
            ),
          ),
          // Border overlay for glass effect
          Positioned(
            top: 25,
            left: 0,
            right: 0,
            bottom: 0,
            child: CustomPaint(
              painter: _CurvedNavBorderPainter(
                activeIndex: 2,
                itemCount: _menuLabels.length,
                isDark: widget.isDarkMode,
              ),
            ),
          ),
          // Menu items - reordered so active is in center
          Positioned(
            top: 18,
            left: 0,
            right: 0,
            bottom: bottomPadding,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: List.generate(
                _menuLabels.length,
                (position) => _buildNavItem(reorderedIndices[position], position == 2),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNavItem(int index, bool isCenter) {
    return GestureDetector(
      onTap: () => _onItemTapped(index),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        width: 60,
        height: 75,
        child: Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.center,
          children: [
            // Icon with glassmorphism for active
            AnimatedPositioned(
              duration: const Duration(milliseconds: 300),
              curve: Curves.easeOutBack,
              top: isCenter ? -12 : 12,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                width: isCenter ? 58 : 38,
                height: isCenter ? 58 : 38,
                decoration: BoxDecoration(
                  gradient: isCenter
                      ? const LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [Color(0xFF1ABC9C), Color(0xFF16A085)],
                        )
                      : null,
                  color: isCenter ? null : Colors.transparent,
                  shape: BoxShape.circle,
                  boxShadow: isCenter
                      ? [
                          BoxShadow(
                            color: const Color(0xFF1ABC9C).withValues(alpha: 0.6),
                            blurRadius: 20,
                            offset: const Offset(0, 8),
                          ),
                        ]
                      : null,
                  border: isCenter
                      ? Border.all(
                          color: Colors.white.withValues(alpha: 0.3),
                          width: 2,
                        )
                      : null,
                ),
                child: Icon(
                  _menuIcons[index],
                  size: isCenter ? 26 : 20,
                  color: isCenter
                      ? Colors.white
                      : widget.isDarkMode
                          ? Colors.white.withValues(alpha: 0.7)
                          : const Color(0xFF2C3E50).withValues(alpha: 0.7),
                ),
              ),
            ),
            // Label
            AnimatedPositioned(
              duration: const Duration(milliseconds: 300),
              bottom: isCenter ? 2 : 6,
              child: AnimatedDefaultTextStyle(
                duration: const Duration(milliseconds: 300),
                style: TextStyle(
                  fontSize: isCenter ? 11 : 10,
                  fontWeight: isCenter ? FontWeight.w700 : FontWeight.w500,
                  color: isCenter
                      ? const Color(0xFF1ABC9C)
                      : widget.isDarkMode
                          ? Colors.white.withValues(alpha: 0.7)
                          : const Color(0xFF2C3E50).withValues(alpha: 0.7),
                ),
                child: Text(_menuLabels[index]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Clipper for curved bottom navigation with notch
class _CurvedNavClipper extends CustomClipper<Path> {
  final int activeIndex;
  final int itemCount;

  _CurvedNavClipper({required this.activeIndex, required this.itemCount});

  @override
  Path getClip(Size size) {
    final itemWidth = size.width / itemCount;
    final centerX = (activeIndex * itemWidth) + (itemWidth / 2);

    const notchRadius = 40.0;
    const notchDepth = 8.0;
    const cornerRadius = 25.0;

    final path = Path();

    path.moveTo(0, cornerRadius);
    path.quadraticBezierTo(0, 0, cornerRadius, 0);
    path.lineTo(centerX - notchRadius - 10, 0);

    path.cubicTo(
      centerX - notchRadius, 0,
      centerX - notchRadius + 5, notchDepth,
      centerX - notchRadius + 15, notchDepth + 25,
    );

    path.arcToPoint(
      Offset(centerX + notchRadius - 15, notchDepth + 25),
      radius: const Radius.circular(notchRadius - 8),
      clockwise: false,
    );

    path.cubicTo(
      centerX + notchRadius - 5, notchDepth,
      centerX + notchRadius, 0,
      centerX + notchRadius + 10, 0,
    );

    path.lineTo(size.width - cornerRadius, 0);
    path.quadraticBezierTo(size.width, 0, size.width, cornerRadius);
    path.lineTo(size.width, size.height);
    path.lineTo(0, size.height);
    path.close();

    return path;
  }

  @override
  bool shouldReclip(covariant _CurvedNavClipper oldClipper) {
    return oldClipper.activeIndex != activeIndex;
  }
}

// Border painter for the curved navigation
class _CurvedNavBorderPainter extends CustomPainter {
  final int activeIndex;
  final int itemCount;
  final bool isDark;

  _CurvedNavBorderPainter({
    required this.activeIndex,
    required this.itemCount,
    this.isDark = false,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final itemWidth = size.width / itemCount;
    final centerX = (activeIndex * itemWidth) + (itemWidth / 2);

    const notchRadius = 40.0;
    const notchDepth = 8.0;
    const cornerRadius = 25.0;

    final path = Path();

    path.moveTo(0, cornerRadius);
    path.quadraticBezierTo(0, 0, cornerRadius, 0);
    path.lineTo(centerX - notchRadius - 10, 0);

    path.cubicTo(
      centerX - notchRadius, 0,
      centerX - notchRadius + 5, notchDepth,
      centerX - notchRadius + 15, notchDepth + 25,
    );

    path.arcToPoint(
      Offset(centerX + notchRadius - 15, notchDepth + 25),
      radius: const Radius.circular(notchRadius - 8),
      clockwise: false,
    );

    path.cubicTo(
      centerX + notchRadius - 5, notchDepth,
      centerX + notchRadius, 0,
      centerX + notchRadius + 10, 0,
    );

    path.lineTo(size.width - cornerRadius, 0);
    path.quadraticBezierTo(size.width, 0, size.width, cornerRadius);

    final borderPaint = Paint()
      ..color = isDark
          ? Colors.white.withValues(alpha: 0.2)
          : const Color(0xFF2C3E50).withValues(alpha: 0.15)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;

    canvas.drawPath(path, borderPaint);

    final glowPaint = Paint()
      ..color = const Color(0xFF1ABC9C).withValues(alpha: isDark ? 0.3 : 0.4)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3
      ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 8);

    final notchPath = Path();
    notchPath.moveTo(centerX - notchRadius + 15, notchDepth + 25);
    notchPath.arcToPoint(
      Offset(centerX + notchRadius - 15, notchDepth + 25),
      radius: const Radius.circular(notchRadius - 8),
      clockwise: false,
    );
    canvas.drawPath(notchPath, glowPaint);
  }

  @override
  bool shouldRepaint(covariant _CurvedNavBorderPainter oldDelegate) {
    return oldDelegate.activeIndex != activeIndex || oldDelegate.isDark != isDark;
  }
}
