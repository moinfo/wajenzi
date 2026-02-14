import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/settings_provider.dart';

class CurvedInternalNav extends ConsumerStatefulWidget {
  final int selectedIndex;
  final bool isClient;

  const CurvedInternalNav({
    super.key,
    this.selectedIndex = 0,
    this.isClient = false,
  });

  @override
  ConsumerState<CurvedInternalNav> createState() => _CurvedInternalNavState();
}

class _CurvedInternalNavState extends ConsumerState<CurvedInternalNav> {
  late int _selectedMenuIndex;

  bool get _isClient => widget.isClient;

  List<String> _getMenuLabels(bool isSwahili) {
    if (_isClient) {
      return isSwahili
          ? ['Nyumbani', 'Ankara', 'Mipangilio']
          : ['Home', 'Billing', 'Settings'];
    }
    return isSwahili
        ? ['Miradi', 'Ankara', 'Nyumbani', 'Ununuzi', 'Mahudhurio']
        : ['Projects', 'Billing', 'Home', 'Procurement', 'Attendance'];
  }

  List<IconData> get _menuIcons {
    if (_isClient) {
      return [
        Icons.dashboard_rounded,
        Icons.receipt_long_rounded,
        Icons.settings_rounded,
      ];
    }
    return [
      Icons.business_rounded,
      Icons.receipt_long_rounded,
      Icons.dashboard_rounded,
      Icons.inventory_2_rounded,
      Icons.access_time_rounded,
    ];
  }

  List<String> get _menuRoutes {
    if (_isClient) {
      return ['/dashboard', '/billing', '/settings'];
    }
    return ['/staff-projects', '/staff-billing', '/dashboard', '/procurement', '/attendance'];
  }

  @override
  void initState() {
    super.initState();
    _selectedMenuIndex = widget.selectedIndex;
  }

  @override
  void didUpdateWidget(CurvedInternalNav oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.selectedIndex != widget.selectedIndex) {
      _selectedMenuIndex = widget.selectedIndex;
    }
  }

  void _onItemTapped(int index) {
    setState(() => _selectedMenuIndex = index);
    context.go(_menuRoutes[index]);
  }

  @override
  Widget build(BuildContext context) {
    final bottomPadding = MediaQuery.of(context).padding.bottom;
    final isDarkMode = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final menuLabels = _getMenuLabels(isSwahili);

    // Reorder items so active is always in center (index 2)
    List<int> getReorderedIndices() {
      final itemCount = menuLabels.length;
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
    final centerPosition = menuLabels.length ~/ 2;

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
                activeIndex: centerPosition,
                itemCount: menuLabels.length,
              ),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: isDarkMode
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
                activeIndex: centerPosition,
                itemCount: menuLabels.length,
                isDark: isDarkMode,
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
                menuLabels.length,
                (position) => _buildNavItem(
                  reorderedIndices[position],
                  position == centerPosition,
                  menuLabels,
                  isDarkMode,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNavItem(int index, bool isCenter, List<String> menuLabels, bool isDarkMode) {
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
                      : isDarkMode
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
                      : isDarkMode
                          ? Colors.white.withValues(alpha: 0.7)
                          : const Color(0xFF2C3E50).withValues(alpha: 0.7),
                ),
                child: Text(menuLabels[index]),
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
