import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class LandingScreen extends StatefulWidget {
  const LandingScreen({super.key});

  @override
  State<LandingScreen> createState() => _LandingScreenState();
}

class _LandingScreenState extends State<LandingScreen> {
  int _selectedMenuIndex = 0;

  final List<String> _menuLabels = ['Home', 'Projects', 'Services', 'About', 'Awards'];
  final List<IconData> _menuIcons = [
    Icons.home_rounded,
    Icons.business_rounded,
    Icons.design_services_rounded,
    Icons.info_outline_rounded,
    Icons.emoji_events_rounded,
  ];

  final List<ProjectShowcase> _projects = [
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.10.png',
      title: 'HOTEL CONSTRUCTION',
      priceTZS: '6,911,200,000',
      priceUSD: '2,764,480',
      features: 'Bedrooms, restaurant, bar, parking, gym and spa',
      category: '3D DESIGN',
      likes: 156,
      timeAgo: '2 days ago',
      description: 'Luxury hotel project featuring modern architecture with premium amenities.',
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.20.png',
      title: 'RESIDENTIAL VILLA',
      priceTZS: '850,000,000',
      priceUSD: '340,000',
      features: '5 Bedrooms, swimming pool, garden, garage',
      category: 'COMPLETED',
      likes: 243,
      timeAgo: '1 week ago',
      description: 'Beautiful modern villa in Dar es Salaam with stunning views.',
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.28.png',
      title: 'OFFICE COMPLEX',
      priceTZS: '2,500,000,000',
      priceUSD: '1,000,000',
      features: 'Open offices, meeting rooms, cafeteria, parking',
      category: 'IN PROGRESS',
      likes: 89,
      timeAgo: '3 days ago',
      description: 'State-of-the-art commercial office building in the business district.',
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.31.png',
      title: 'APARTMENT COMPLEX',
      priceTZS: '4,200,000,000',
      priceUSD: '1,680,000',
      features: '24 Units, gym, rooftop lounge, security',
      category: 'DESIGN',
      likes: 178,
      timeAgo: '5 days ago',
      description: 'Modern apartment living with premium shared amenities.',
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.40.png',
      title: 'SHOPPING MALL',
      priceTZS: '12,500,000,000',
      priceUSD: '5,000,000',
      features: '150 shops, cinema, food court, parking',
      category: '3D DESIGN',
      likes: 312,
      timeAgo: '1 day ago',
      description: 'Modern shopping center with entertainment facilities.',
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.51.07.png',
      title: 'SCHOOL BUILDING',
      priceTZS: '1,800,000,000',
      priceUSD: '720,000',
      features: '30 classrooms, library, labs, sports field',
      category: 'COMPLETED',
      likes: 198,
      timeAgo: '2 weeks ago',
      description: 'Educational facility with modern learning environments.',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F4F8),
      extendBody: true,
      body: CustomScrollView(
        slivers: [
          // App Bar
          SliverAppBar(
            floating: true,
            snap: true,
            backgroundColor: const Color(0xFFF0F4F8),
            elevation: 0,
            toolbarHeight: 60,
            title: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: const Color(0xFF1ABC9C).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(10),
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
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      'WAJENZI',
                      style: TextStyle(
                        color: Color(0xFF1ABC9C),
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 2,
                      ),
                    ),
                    Text(
                      'Professional Co. Ltd',
                      style: TextStyle(
                        color: Color(0xFF7F8C8D),
                        fontSize: 10,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => context.go('/login'),
                style: TextButton.styleFrom(
                  backgroundColor: const Color(0xFF1ABC9C),
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(20),
                  ),
                ),
                child: const Text(
                  'Login',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(width: 16),
            ],
          ),

          // Projects Count Header
          SliverToBoxAdapter(
            child: Container(
              margin: const EdgeInsets.fromLTRB(12, 8, 12, 4),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1ABC9C), Color(0xFF16A085)],
                ),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  const Icon(Icons.construction, color: Colors.white, size: 20),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text(
                      'Featured Projects',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '${_projects.length} Projects',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Project Posts
          SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) => _buildProjectPost(_projects[index]),
              childCount: _projects.length,
            ),
          ),

          // Bottom CTA
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.all(20),
              margin: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF3498DB), Color(0xFF1ABC9C)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                children: [
                  const Text(
                    'Ready to Build Your Dream?',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Join Wajenzi and get access to professional construction services',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _buildStatBadge('120+', 'Projects'),
                      const SizedBox(width: 16),
                      _buildStatBadge('50+', 'Experts'),
                      const SizedBox(width: 16),
                      _buildStatBadge('200+', 'Completed'),
                    ],
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => context.go('/login'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: const Color(0xFF1ABC9C),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            'Get Started',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          SizedBox(width: 8),
                          Icon(Icons.arrow_forward_rounded),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Footer
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Column(
                children: [
                  Text(
                    'Powered by Moinfotech',
                    style: TextStyle(
                      color: Colors.grey[500],
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'v1.0.0',
                    style: TextStyle(
                      color: Colors.grey[400],
                      fontSize: 10,
                    ),
                  ),
                  const SizedBox(height: 100), // Space for bottom nav
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: _buildCurvedBottomNav(),
    );
  }

  Widget _buildCurvedBottomNav() {
    final bottomPadding = MediaQuery.of(context).padding.bottom;

    // Reorder items so active is always in center (index 2)
    List<int> getReorderedIndices() {
      final itemCount = _menuLabels.length;
      final centerIndex = itemCount ~/ 2; // Center position (2 for 5 items)
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
          // Dark Glassmorphism background with curved notch
          Positioned(
            top: 25,
            left: 0,
            right: 0,
            bottom: 0,
            child: ClipPath(
              clipper: CurvedNavClipper(
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
                      colors: [
                        Colors.black.withValues(alpha: 0.75),
                        Colors.black.withValues(alpha: 0.9),
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
              painter: CurvedNavBorderPainter(
                activeIndex: 2,
                itemCount: _menuLabels.length,
                isDark: true,
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
      onTap: () => setState(() => _selectedMenuIndex = index),
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
                  color: isCenter ? Colors.white : Colors.white.withValues(alpha: 0.7),
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
                      : Colors.white.withValues(alpha: 0.7),
                ),
                child: Text(_menuLabels[index]),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProjectPost(ProjectShowcase project) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: Stack(
          children: [
            // Background Image
            AspectRatio(
              aspectRatio: 0.75,
              child: Image.asset(
                project.image,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => _buildPlaceholderImage(project),
              ),
            ),

            // Gradient Overlay
            Positioned.fill(
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.transparent,
                      Colors.black.withValues(alpha: 0.3),
                      Colors.black.withValues(alpha: 0.8),
                    ],
                    stops: const [0.3, 0.6, 1.0],
                  ),
                ),
              ),
            ),

            // Top Header - Glassmorphism
            Positioned(
              top: 12,
              left: 12,
              right: 12,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.3),
                      ),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 36,
                          height: 36,
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.3),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Icon(
                            _getCategoryIcon(project.category),
                            color: Colors.white,
                            size: 20,
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                project.title,
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 13,
                                  color: Colors.white,
                                ),
                              ),
                              Text(
                                project.timeAgo,
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.8),
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: _getCategoryColor(project.category),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            project.category,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),

            // Bottom Content - Glassmorphism
            Positioned(
              bottom: 12,
              left: 12,
              right: 12,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 15, sigmaY: 15),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.25),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Price Row
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(
                                  colors: [Color(0xFFE74C3C), Color(0xFFC0392B)],
                                ),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'TZS ${_formatPrice(project.priceTZS)}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'USD ${project.priceUSD}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                            const Spacer(),
                            Row(
                              children: [
                                const Icon(Icons.favorite, color: Color(0xFFE74C3C), size: 20),
                                const SizedBox(width: 4),
                                Text(
                                  '${project.likes}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w600,
                                    fontSize: 13,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        // Description
                        Text(
                          project.description,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.9),
                            fontSize: 12,
                            height: 1.3,
                          ),
                        ),
                        const SizedBox(height: 10),
                        // Features
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFD4AF37).withValues(alpha: 0.3),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: const Color(0xFFD4AF37).withValues(alpha: 0.5),
                            ),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.check_circle, size: 14, color: Color(0xFFD4AF37)),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  project.features,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlaceholderImage(ProjectShowcase project) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            const Color(0xFF2C3E50),
            _getCategoryColor(project.category).withValues(alpha: 0.7),
          ],
        ),
      ),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              _getCategoryIcon(project.category),
              size: 60,
              color: Colors.white.withValues(alpha: 0.4),
            ),
            const SizedBox(height: 12),
            Text(
              project.title,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.6),
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatBadge(String value, String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.2),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.8),
              fontSize: 10,
            ),
          ),
        ],
      ),
    );
  }

  String _formatPrice(String price) {
    final numStr = price.replaceAll(',', '');
    final num = double.tryParse(numStr) ?? 0;
    if (num >= 1000000000) {
      return '${(num / 1000000000).toStringAsFixed(1)}B';
    } else if (num >= 1000000) {
      return '${(num / 1000000).toStringAsFixed(0)}M';
    }
    return price;
  }

  Color _getCategoryColor(String category) {
    switch (category) {
      case 'COMPLETED':
        return const Color(0xFF2ECC71);
      case 'IN PROGRESS':
        return const Color(0xFFF39C12);
      case '3D DESIGN':
        return const Color(0xFF3498DB);
      case 'DESIGN':
        return const Color(0xFF9B59B6);
      default:
        return const Color(0xFF1ABC9C);
    }
  }

  IconData _getCategoryIcon(String category) {
    switch (category) {
      case 'COMPLETED':
        return Icons.home_work;
      case 'IN PROGRESS':
        return Icons.construction;
      case '3D DESIGN':
        return Icons.view_in_ar;
      case 'DESIGN':
        return Icons.architecture;
      default:
        return Icons.business;
    }
  }
}

// Clipper for curved bottom navigation with notch
class CurvedNavClipper extends CustomClipper<Path> {
  final int activeIndex;
  final int itemCount;

  CurvedNavClipper({required this.activeIndex, required this.itemCount});

  @override
  Path getClip(Size size) {
    final itemWidth = size.width / itemCount;
    final centerX = (activeIndex * itemWidth) + (itemWidth / 2);

    // Notch dimensions
    const notchRadius = 40.0;
    const notchDepth = 8.0;
    const cornerRadius = 25.0;

    final path = Path();

    // Start from top left corner
    path.moveTo(0, cornerRadius);
    path.quadraticBezierTo(0, 0, cornerRadius, 0);

    // Draw to start of notch curve
    path.lineTo(centerX - notchRadius - 10, 0);

    // Smooth curve into notch
    path.cubicTo(
      centerX - notchRadius, 0,
      centerX - notchRadius + 5, notchDepth,
      centerX - notchRadius + 15, notchDepth + 25,
    );

    // Arc for the notch (semi-circle going down)
    path.arcToPoint(
      Offset(centerX + notchRadius - 15, notchDepth + 25),
      radius: const Radius.circular(notchRadius - 8),
      clockwise: false,
    );

    // Smooth curve out of notch
    path.cubicTo(
      centerX + notchRadius - 5, notchDepth,
      centerX + notchRadius, 0,
      centerX + notchRadius + 10, 0,
    );

    // Continue to top right corner
    path.lineTo(size.width - cornerRadius, 0);
    path.quadraticBezierTo(size.width, 0, size.width, cornerRadius);

    // Complete the rectangle
    path.lineTo(size.width, size.height);
    path.lineTo(0, size.height);
    path.close();

    return path;
  }

  @override
  bool shouldReclip(covariant CurvedNavClipper oldClipper) {
    return oldClipper.activeIndex != activeIndex;
  }
}

// Border painter for the curved navigation
class CurvedNavBorderPainter extends CustomPainter {
  final int activeIndex;
  final int itemCount;
  final bool isDark;

  CurvedNavBorderPainter({
    required this.activeIndex,
    required this.itemCount,
    this.isDark = false,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final itemWidth = size.width / itemCount;
    final centerX = (activeIndex * itemWidth) + (itemWidth / 2);

    // Notch dimensions - must match clipper
    const notchRadius = 40.0;
    const notchDepth = 8.0;
    const cornerRadius = 25.0;

    final path = Path();

    // Start from top left corner
    path.moveTo(0, cornerRadius);
    path.quadraticBezierTo(0, 0, cornerRadius, 0);

    // Draw to start of notch curve
    path.lineTo(centerX - notchRadius - 10, 0);

    // Smooth curve into notch
    path.cubicTo(
      centerX - notchRadius, 0,
      centerX - notchRadius + 5, notchDepth,
      centerX - notchRadius + 15, notchDepth + 25,
    );

    // Arc for the notch
    path.arcToPoint(
      Offset(centerX + notchRadius - 15, notchDepth + 25),
      radius: const Radius.circular(notchRadius - 8),
      clockwise: false,
    );

    // Smooth curve out of notch
    path.cubicTo(
      centerX + notchRadius - 5, notchDepth,
      centerX + notchRadius, 0,
      centerX + notchRadius + 10, 0,
    );

    // Continue to top right corner
    path.lineTo(size.width - cornerRadius, 0);
    path.quadraticBezierTo(size.width, 0, size.width, cornerRadius);

    // Draw the border - lighter for dark mode
    final borderPaint = Paint()
      ..color = isDark
          ? Colors.white.withValues(alpha: 0.2)
          : Colors.white.withValues(alpha: 0.6)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;

    canvas.drawPath(path, borderPaint);

    // Draw teal glow inside notch
    final glowPaint = Paint()
      ..color = const Color(0xFF1ABC9C).withValues(alpha: isDark ? 0.3 : 0.15)
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
  bool shouldRepaint(covariant CurvedNavBorderPainter oldDelegate) {
    return oldDelegate.activeIndex != activeIndex || oldDelegate.isDark != isDark;
  }
}

class ProjectShowcase {
  final String image;
  final String title;
  final String priceTZS;
  final String priceUSD;
  final String features;
  final String category;
  final int likes;
  final String timeAgo;
  final String description;

  ProjectShowcase({
    required this.image,
    required this.title,
    required this.priceTZS,
    required this.priceUSD,
    required this.features,
    required this.category,
    required this.likes,
    required this.timeAgo,
    required this.description,
  });
}
