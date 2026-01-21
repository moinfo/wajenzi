import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

class LandingScreen extends StatefulWidget {
  const LandingScreen({super.key});

  @override
  State<LandingScreen> createState() => _LandingScreenState();
}

class _LandingScreenState extends State<LandingScreen> {
  int _selectedMenuIndex = 0;
  bool _isDarkMode = false;
  bool _isSwahili = false;

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

  // Dark mode colors
  Color get _bgColor => _isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);
  Color get _cardBgColor => _isDarkMode ? const Color(0xFF16213E) : Colors.white;
  Color get _textPrimaryColor => _isDarkMode ? Colors.white : const Color(0xFF2C3E50);
  Color get _textSecondaryColor => _isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D);
  Color get _appBarBgColor => _isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);

  // Consistent top bar button builder
  Widget _buildTopBarButton({
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
          color: _isDarkMode ? const Color(0xFF16213E) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: _isDarkMode
                ? Colors.white.withValues(alpha: 0.15)
                : Colors.grey.withValues(alpha: 0.25),
            width: 1,
          ),
        ),
        child: child,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgColor,
      extendBody: true,
      body: CustomScrollView(
        slivers: [
          // App Bar
          SliverAppBar(
            floating: true,
            snap: true,
            backgroundColor: _appBarBgColor,
            elevation: 0,
            toolbarHeight: 70,
            title: Row(
              children: [
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
                        _isSwahili
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
                onTap: () => setState(() => _isSwahili = !_isSwahili),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(2),
                      child: SizedBox(
                        width: 20,
                        height: 13,
                        child: _isSwahili ? const TanzaniaFlag() : const UKFlag(),
                      ),
                    ),
                    const SizedBox(width: 3),
                    Text(
                      _isSwahili ? 'SW' : 'EN',
                      style: TextStyle(
                        fontSize: 9,
                        fontWeight: FontWeight.w600,
                        color: _isDarkMode ? Colors.white : const Color(0xFF2C3E50),
                      ),
                    ),
                  ],
                ),
                isWide: true,
              ),
              const SizedBox(width: 8),
              // Dark Mode Toggle
              _buildTopBarButton(
                onTap: () => setState(() => _isDarkMode = !_isDarkMode),
                child: Icon(
                  _isDarkMode ? Icons.dark_mode : Icons.light_mode,
                  size: 20,
                  color: _isDarkMode
                      ? const Color(0xFF1ABC9C)
                      : const Color(0xFFF39C12),
                ),
              ),
              const SizedBox(width: 8),
              // Cart Icon Button
              _buildTopBarButton(
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(_isSwahili ? 'Kikapu kitupu' : 'Cart is empty'),
                      duration: const Duration(seconds: 2),
                    ),
                  );
                },
                child: Stack(
                  children: [
                    Center(
                      child: Icon(
                        Icons.shopping_cart_outlined,
                        size: 20,
                        color: _isDarkMode ? Colors.white : const Color(0xFF2C3E50),
                      ),
                    ),
                    // Cart badge
                    Positioned(
                      right: 2,
                      top: 2,
                      child: Container(
                        width: 14,
                        height: 14,
                        decoration: const BoxDecoration(
                          color: Color(0xFFE74C3C),
                          shape: BoxShape.circle,
                        ),
                        child: const Center(
                          child: Text(
                            '0',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 8,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              // Login Icon Button
              _buildTopBarButton(
                onTap: () => context.go('/login'),
                child: Icon(
                  Icons.login_rounded,
                  size: 20,
                  color: _isDarkMode ? Colors.white : const Color(0xFF2C3E50),
                ),
              ),
              const SizedBox(width: 12),
            ],
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
                gradient: LinearGradient(
                  colors: _isDarkMode
                      ? [const Color(0xFF0F3460), const Color(0xFF16213E)]
                      : [const Color(0xFF3498DB), const Color(0xFF1ABC9C)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                border: _isDarkMode
                    ? Border.all(color: const Color(0xFF1ABC9C).withValues(alpha: 0.3))
                    : null,
              ),
              child: Column(
                children: [
                  Text(
                    _isSwahili
                        ? 'Uko Tayari Kujenga Ndoto Yako?'
                        : 'Ready to Build Your Dream?',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _isSwahili
                        ? 'Jiunge na Wajenzi upate huduma za ujenzi za kitaalamu'
                        : 'Join Wajenzi and get access to professional construction services',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _buildStatBadge('120+', _isSwahili ? 'Miradi' : 'Projects'),
                      const SizedBox(width: 16),
                      _buildStatBadge('50+', _isSwahili ? 'Wataalamu' : 'Experts'),
                      const SizedBox(width: 16),
                      _buildStatBadge('200+', _isSwahili ? 'Imekamilika' : 'Completed'),
                    ],
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => context.go('/login'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _isDarkMode
                            ? const Color(0xFF1ABC9C)
                            : Colors.white,
                        foregroundColor: _isDarkMode
                            ? Colors.white
                            : const Color(0xFF1ABC9C),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            _isSwahili ? 'Anza Sasa' : 'Get Started',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Icon(Icons.arrow_forward_rounded),
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
                      color: _isDarkMode ? Colors.white38 : Colors.grey[500],
                      fontSize: 12,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'v1.0.0',
                    style: TextStyle(
                      color: _isDarkMode ? Colors.white24 : Colors.grey[400],
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
      bottomNavigationBar: CurvedBottomNav(
        selectedIndex: _selectedMenuIndex,
        isDarkMode: _isDarkMode,
        isSwahili: _isSwahili,
        onItemTapped: (index) => setState(() => _selectedMenuIndex = index),
      ),
    );
  }

  Widget _buildProjectPost(ProjectShowcase project) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        border: _isDarkMode
            ? Border.all(color: const Color(0xFF1ABC9C).withValues(alpha: 0.2))
            : null,
        boxShadow: [
          BoxShadow(
            color: _isDarkMode
                ? const Color(0xFF1ABC9C).withValues(alpha: 0.1)
                : Colors.black.withValues(alpha: 0.1),
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
