import 'dart:async';
import 'package:flutter/material.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

class AwardsScreen extends StatefulWidget {
  const AwardsScreen({super.key});

  @override
  State<AwardsScreen> createState() => _AwardsScreenState();
}

class _AwardsScreenState extends State<AwardsScreen> {
  bool _isDarkMode = false;
  bool _isSwahili = false;

  // Featured award carousel
  final PageController _featuredController = PageController();
  int _currentFeaturedPage = 0;
  Timer? _featuredTimer;

  @override
  void initState() {
    super.initState();
    _startAutoScroll();
  }

  @override
  void dispose() {
    _featuredTimer?.cancel();
    _featuredController.dispose();
    super.dispose();
  }

  void _startAutoScroll() {
    _featuredTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      if (_featuredController.hasClients) {
        final nextPage = (_currentFeaturedPage + 1) % _awards.length;
        _featuredController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 500),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  // Dark mode colors
  Color get _bgColor => _isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);
  Color get _cardBgColor => _isDarkMode ? const Color(0xFF16213E) : Colors.white;
  Color get _textPrimaryColor => _isDarkMode ? Colors.white : const Color(0xFF2C3E50);
  Color get _textSecondaryColor => _isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D);

  List<_Award> get _awards => [
    _Award(
      year: '2024',
      title: _isSwahili ? 'Mkandarasi Bora wa Nyumba' : 'Outstanding Residential Contractor',
      subtitle: _isSwahili
          ? 'Mkandarasi Bora wa Nyumba wa Mwaka'
          : 'Outstanding Residential Contractor of the Year',
      organization: _isSwahili
          ? 'Chama cha Ujenzi na Miundombinu cha Tanzania (CCIT)'
          : 'Chamber of Construction and Infrastructure of Tanzania (CCIT)',
      description: _isSwahili
          ? 'Kutambuliwa kwa ubora katika miradi ya ujenzi wa makazi, kutoa ubora wa kipekee, ubunifu, na kuridhika kwa wateja.'
          : 'Recognized for excellence in residential construction projects, delivering exceptional quality, innovation, and client satisfaction.',
      image: 'assets/images/awards/BQ6A3834.jpeg',
      color: const Color(0xFF1ABC9C),
      icon: Icons.emoji_events_rounded,
    ),
    _Award(
      year: '2023',
      title: _isSwahili ? 'Ubora katika Ujenzi' : 'Excellence in Construction',
      subtitle: _isSwahili
          ? 'Tuzo ya Ubora katika Ujenzi'
          : 'Excellence in Construction Award',
      organization: _isSwahili
          ? 'Chama cha Wajenzi wa Afrika Mashariki'
          : 'East African Builders Association',
      description: _isSwahili
          ? 'Kutuzwa kwa kuonyesha ufundi bora, ubunifu wa kiufundi, na usimamizi wa mradi katika miradi mingi ya ujenzi.'
          : 'Awarded for demonstrating outstanding craftsmanship, technical innovation, and project management across multiple construction projects.',
      image: 'assets/images/awards/BQ6A3837.jpeg',
      color: const Color(0xFF3498DB),
      icon: Icons.workspace_premium_rounded,
    ),
    _Award(
      year: '2022',
      title: _isSwahili ? 'Ujenzi Endelevu' : 'Sustainable Building',
      subtitle: _isSwahili
          ? 'Tuzo ya Uongozi wa Ujenzi Endelevu'
          : 'Sustainable Building Leadership Award',
      organization: _isSwahili
          ? 'Baraza la Majengo ya Kijani Tanzania'
          : 'Green Building Council Tanzania',
      description: _isSwahili
          ? 'Kutambuliwa kwa kutekeleza mazoea endelevu, miundo inayotumia nishati kwa ufanisi, na vifaa rafiki kwa mazingira katika miradi ya ujenzi.'
          : 'Recognized for implementing sustainable practices, energy-efficient designs, and eco-friendly materials in construction projects.',
      image: 'assets/images/awards/BQ6A3840.jpeg',
      color: const Color(0xFF27AE60),
      icon: Icons.eco_rounded,
    ),
    _Award(
      year: '2021',
      title: _isSwahili ? 'Tuzo ya Ubunifu' : 'Innovation Award',
      subtitle: _isSwahili
          ? 'Tuzo ya Ubunifu katika Ujenzi'
          : 'Innovation in Construction Award',
      organization: _isSwahili
          ? 'Baraza la Ubunifu wa Ujenzi Tanzania'
          : 'Tanzania Construction Innovation Council',
      description: _isSwahili
          ? 'Kutambuliwa kwa kutekeleza mbinu za ujenzi za ubunifu na mazoea endelevu ya ujenzi katika miradi ya makazi.'
          : 'Recognized for implementing innovative construction techniques and sustainable building practices in residential projects.',
      image: 'assets/images/awards/BQ6A3837_2.jpeg',
      color: const Color(0xFF9B59B6),
      icon: Icons.lightbulb_rounded,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgColor,
      extendBody: true,
      appBar: LandingTopBar(
        isDarkMode: _isDarkMode,
        isSwahili: _isSwahili,
        onDarkModeToggle: () => setState(() => _isDarkMode = !_isDarkMode),
        onLanguageToggle: () => setState(() => _isSwahili = !_isSwahili),
        flagWidget: _isSwahili ? const TanzaniaFlag() : const UKFlag(),
      ),
      body: CustomScrollView(
        slivers: [
          // Hero Section
          SliverToBoxAdapter(
            child: _buildHeroSection(),
          ),

          // Stats Section
          SliverToBoxAdapter(
            child: _buildStatsSection(),
          ),

          // Featured Awards Carousel
          SliverToBoxAdapter(
            child: _buildFeaturedAwardsSection(),
          ),

          // All Awards Section Header
          SliverToBoxAdapter(
            child: _buildAwardsSectionHeader(),
          ),

          // Awards Timeline
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) => _buildAwardTimelineItem(_awards[index], index == _awards.length - 1),
                childCount: _awards.length,
              ),
            ),
          ),

          // Recognition Section
          SliverToBoxAdapter(
            child: _buildRecognitionSection(),
          ),

          // Footer spacing
          const SliverToBoxAdapter(
            child: SizedBox(height: 120),
          ),
        ],
      ),
      bottomNavigationBar: CurvedBottomNav(
        selectedIndex: 4, // Awards is index 4
        isDarkMode: _isDarkMode,
        isSwahili: _isSwahili,
      ),
    );
  }

  Widget _buildHeroSection() {
    return Stack(
      children: [
        SizedBox(
          height: 220,
          width: double.infinity,
          child: Image.asset(
            'assets/images/awards/BQ6A3834.jpeg',
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFFD4AF37), Color(0xFFF39C12)],
                ),
              ),
              child: const Center(
                child: Icon(Icons.emoji_events_rounded, size: 80, color: Colors.white24),
              ),
            ),
          ),
        ),
        Container(
          height: 220,
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Colors.black.withValues(alpha: 0.4),
                Colors.black.withValues(alpha: 0.8),
              ],
            ),
          ),
        ),
        Positioned(
          bottom: 24,
          left: 20,
          right: 20,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFF1ABC9C),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.emoji_events_rounded, color: Colors.white, size: 14),
                    const SizedBox(width: 6),
                    Text(
                      _isSwahili ? 'Kutambuliwa' : 'Recognition',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              Text(
                _isSwahili ? 'Tuzo na Kutambuliwa Kwetu' : 'Our Awards & Recognition',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _isSwahili
                    ? 'Kutambuliwa kwa kujitolea kwetu kwa ubora, ubunifu, na ubora katika ujenzi'
                    : 'Recognition of our commitment to quality, innovation, and excellence in construction',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.9),
                  fontSize: 13,
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStatsSection() {
    return Container(
      margin: const EdgeInsets.all(20),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1ABC9C), Color(0xFF16A085)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1ABC9C).withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildStatItem('4', _isSwahili ? 'Tuzo' : 'Awards'),
          _buildStatDivider(),
          _buildStatItem('4', _isSwahili ? 'Miaka' : 'Years'),
          _buildStatDivider(),
          _buildStatItem('3', _isSwahili ? 'Mashirika' : 'Organizations'),
        ],
      ),
    );
  }

  Widget _buildStatItem(String value, String label) {
    return Column(
      children: [
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 32,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.9),
            fontSize: 12,
          ),
        ),
      ],
    );
  }

  Widget _buildStatDivider() {
    return Container(
      width: 1,
      height: 40,
      color: Colors.white.withValues(alpha: 0.3),
    );
  }

  Widget _buildFeaturedAwardsSection() {
    return Container(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Section Header
          Row(
            children: [
              Container(
                width: 4,
                height: 30,
                decoration: BoxDecoration(
                  color: const Color(0xFF1ABC9C),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _isSwahili ? 'Mafanikio Yetu' : 'Our Achievements',
                    style: const TextStyle(
                      color: Color(0xFF1ABC9C),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _isSwahili ? 'Tuzo Zilizoangaziwa' : 'Featured Awards',
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Awards Carousel
          SizedBox(
            height: 320,
            child: PageView.builder(
              controller: _featuredController,
              onPageChanged: (index) {
                setState(() => _currentFeaturedPage = index);
              },
              itemCount: _awards.length,
              itemBuilder: (context, index) {
                return _buildFeaturedAwardCard(_awards[index]);
              },
            ),
          ),
          const SizedBox(height: 16),

          // Page Indicators
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(
              _awards.length,
              (index) => AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: _currentFeaturedPage == index ? 24 : 8,
                height: 8,
                decoration: BoxDecoration(
                  color: _currentFeaturedPage == index
                      ? const Color(0xFF1ABC9C)
                      : (_isDarkMode ? Colors.white24 : Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFeaturedAwardCard(_Award award) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 4),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(20),
        border: _isDarkMode
            ? Border.all(color: award.color.withValues(alpha: 0.3))
            : null,
        boxShadow: [
          BoxShadow(
            color: award.color.withValues(alpha: 0.15),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Image with year badge
          Stack(
            children: [
              ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                child: SizedBox(
                  height: 140,
                  width: double.infinity,
                  child: Image.asset(
                    award.image,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [award.color.withValues(alpha: 0.7), award.color],
                        ),
                      ),
                      child: Icon(award.icon, size: 60, color: Colors.white38),
                    ),
                  ),
                ),
              ),
              // Year badge
              Positioned(
                top: 12,
                left: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: award.color,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    award.year,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
              // Trophy icon
              Positioned(
                top: 12,
                right: 12,
                child: Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.9),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(award.icon, color: award.color, size: 22),
                ),
              ),
            ],
          ),

          // Content
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title
                  Text(
                    award.title,
                    style: TextStyle(
                      color: award.color,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    award.subtitle,
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 6),
                  // Organization
                  Row(
                    children: [
                      Icon(
                        Icons.business_rounded,
                        size: 14,
                        color: _textSecondaryColor,
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          award.organization,
                          style: TextStyle(
                            color: _textSecondaryColor,
                            fontSize: 11,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  // Description
                  Expanded(
                    child: Text(
                      award.description,
                      style: TextStyle(
                        color: _textSecondaryColor,
                        fontSize: 12,
                        height: 1.4,
                      ),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAwardsSectionHeader() {
    return Container(
      padding: const EdgeInsets.all(20),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 30,
            decoration: BoxDecoration(
              color: const Color(0xFF3498DB),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _isSwahili ? 'Safari Yetu' : 'Our Journey',
                style: const TextStyle(
                  color: Color(0xFF3498DB),
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 1,
                ),
              ),
              Text(
                _isSwahili ? 'Ratiba ya Tuzo' : 'Awards Timeline',
                style: TextStyle(
                  color: _textPrimaryColor,
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAwardTimelineItem(_Award award, bool isLast) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Timeline
        Column(
          children: [
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: award.color,
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(
                    color: award.color.withValues(alpha: 0.4),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Icon(award.icon, color: Colors.white, size: 24),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 120,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      award.color,
                      award.color.withValues(alpha: 0.2),
                    ],
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(width: 16),
        // Content Card
        Expanded(
          child: GestureDetector(
            onTap: () => _showAwardDetails(award),
            child: Container(
              margin: EdgeInsets.only(bottom: isLast ? 0 : 20),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: _cardBgColor,
                borderRadius: BorderRadius.circular(16),
                border: _isDarkMode
                    ? Border.all(color: award.color.withValues(alpha: 0.3))
                    : null,
                boxShadow: [
                  BoxShadow(
                    color: award.color.withValues(alpha: 0.1),
                    blurRadius: 15,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Year badge
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: award.color.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      award.year,
                      style: TextStyle(
                        color: award.color,
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  // Title
                  Text(
                    award.subtitle,
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  // Organization
                  Text(
                    award.organization,
                    style: TextStyle(
                      color: award.color,
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 10),
                  // Description
                  Text(
                    award.description,
                    style: TextStyle(
                      color: _textSecondaryColor,
                      fontSize: 12,
                      height: 1.4,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 12),
                  // View Details button
                  Row(
                    children: [
                      Text(
                        _isSwahili ? 'Angalia Maelezo' : 'View Details',
                        style: TextStyle(
                          color: award.color,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Icon(
                        Icons.arrow_forward_rounded,
                        color: award.color,
                        size: 14,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  void _showAwardDetails(_Award award) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (context, scrollController) => Container(
          decoration: BoxDecoration(
            color: _cardBgColor,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: _isDarkMode ? Colors.white24 : Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(20),
                  children: [
                    // Award Image
                    ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: SizedBox(
                        height: 200,
                        width: double.infinity,
                        child: Image.asset(
                          award.image,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [award.color.withValues(alpha: 0.7), award.color],
                              ),
                            ),
                            child: Icon(award.icon, size: 80, color: Colors.white38),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Year and Icon
                    Row(
                      children: [
                        Container(
                          width: 60,
                          height: 60,
                          decoration: BoxDecoration(
                            color: award.color.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Icon(award.icon, color: award.color, size: 30),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                decoration: BoxDecoration(
                                  color: award.color,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  award.year,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                award.title,
                                style: TextStyle(
                                  color: award.color,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Award Title
                    Text(
                      award.subtitle,
                      style: TextStyle(
                        color: _textPrimaryColor,
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Organization
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: _isDarkMode
                            ? Colors.white.withValues(alpha: 0.05)
                            : Colors.grey.shade100,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.business_rounded,
                            color: award.color,
                            size: 24,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _isSwahili ? 'Imetolewa na' : 'Awarded by',
                                  style: TextStyle(
                                    color: _textSecondaryColor,
                                    fontSize: 11,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  award.organization,
                                  style: TextStyle(
                                    color: _textPrimaryColor,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Description
                    Text(
                      _isSwahili ? 'Kuhusu Tuzo Hii' : 'About This Award',
                      style: TextStyle(
                        color: _textPrimaryColor,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      award.description,
                      style: TextStyle(
                        color: _textSecondaryColor,
                        fontSize: 15,
                        height: 1.6,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      _isSwahili
                          ? 'Tuzo hii inatambua kujitolea kwetu kwa viwango vya juu vya ubora na ubunifu katika sekta ya ujenzi. Tunaendelea kujitahidi kupita matarajio ya wateja wetu na kutoa miradi bora.'
                          : 'This award recognizes our commitment to the highest standards of quality and innovation in the construction industry. We continue to strive to exceed our clients\' expectations and deliver exceptional projects.',
                      style: TextStyle(
                        color: _textSecondaryColor,
                        fontSize: 14,
                        height: 1.6,
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Share Button
                    ElevatedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(
                              _isSwahili ? 'Inashiriki tuzo...' : 'Sharing award...',
                            ),
                          ),
                        );
                      },
                      icon: const Icon(Icons.share_rounded, size: 20),
                      label: Text(_isSwahili ? 'Shiriki Tuzo Hii' : 'Share This Award'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: award.color,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
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
    );
  }

  Widget _buildRecognitionSection() {
    return Container(
      margin: const EdgeInsets.all(20),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: _isDarkMode
              ? [const Color(0xFF0F3460), const Color(0xFF16213E)]
              : [const Color(0xFF2C3E50), const Color(0xFF1ABC9C)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1ABC9C).withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          const Icon(
            Icons.verified_rounded,
            color: Colors.white,
            size: 48,
          ),
          const SizedBox(height: 16),
          Text(
            _isSwahili
                ? 'Kujitolea kwa Ubora'
                : 'Commitment to Excellence',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          Text(
            _isSwahili
                ? 'Tuzo hizi zinaonyesha kujitolea kwetu bila kusita kwa ubora, ubunifu, na kuridhika kwa wateja. Tunaendelea kujitahidi kuweka viwango vipya katika sekta ya ujenzi.'
                : 'These awards reflect our unwavering commitment to excellence, innovation, and customer satisfaction. We continue to strive to set new standards in the construction industry.',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.9),
              fontSize: 14,
              height: 1.5,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(
                    _isSwahili ? 'Wasiliana nasi...' : 'Contacting us...',
                  ),
                ),
              );
            },
            icon: const Icon(Icons.handshake_rounded, size: 18),
            label: Text(_isSwahili ? 'Fanya Kazi Nasi' : 'Work With Us'),
            style: OutlinedButton.styleFrom(
              foregroundColor: Colors.white,
              side: const BorderSide(color: Colors.white),
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Award {
  final String year;
  final String title;
  final String subtitle;
  final String organization;
  final String description;
  final String image;
  final Color color;
  final IconData icon;

  _Award({
    required this.year,
    required this.title,
    required this.subtitle,
    required this.organization,
    required this.description,
    required this.image,
    required this.color,
    required this.icon,
  });
}
