import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

// WhatsApp contact number (Tanzania format)
const String _wajenziWhatsApp = '+255123456789'; // Replace with actual number

// Format large numbers to abbreviated form (e.g., 6.9B, 2.8M)
String _formatNumber(double number) {
  if (number >= 1000000000) {
    return '${(number / 1000000000).toStringAsFixed(1)}B';
  } else if (number >= 1000000) {
    return '${(number / 1000000).toStringAsFixed(1)}M';
  } else if (number >= 1000) {
    return '${(number / 1000).toStringAsFixed(1)}K';
  }
  return number.toStringAsFixed(0);
}

class ProjectsScreen extends ConsumerStatefulWidget {
  const ProjectsScreen({super.key});

  @override
  ConsumerState<ProjectsScreen> createState() => _ProjectsScreenState();
}

class _ProjectsScreenState extends ConsumerState<ProjectsScreen> {
  // Use global settings from provider
  bool get _isDarkMode => ref.watch(isDarkModeProvider);
  bool get _isSwahili => ref.watch(isSwahiliProvider);

  // Featured project carousel
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
    _featuredTimer = Timer.periodic(const Duration(seconds: 6), (timer) {
      if (_featuredController.hasClients) {
        final nextPage = (_currentFeaturedPage + 1) % _featuredProjects.length;
        _featuredController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 500),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  // Launch WhatsApp with pre-filled message about a project
  Future<void> _launchWhatsApp(String projectName) async {
    final message = _isSwahili
        ? 'Habari! Napenda kupata taarifa zaidi kuhusu mradi: $projectName'
        : 'Hello! I am interested in learning more about the project: $projectName';

    final encodedMessage = Uri.encodeComponent(message);
    final whatsappUrl = Uri.parse('https://wa.me/$_wajenziWhatsApp?text=$encodedMessage');

    if (await canLaunchUrl(whatsappUrl)) {
      await launchUrl(whatsappUrl, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              _isSwahili ? 'Imeshindwa kufungua WhatsApp' : 'Could not open WhatsApp',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  // Dark mode colors
  Color get _bgColor => _isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);
  Color get _cardBgColor => _isDarkMode ? const Color(0xFF16213E) : Colors.white;
  Color get _textPrimaryColor => _isDarkMode ? Colors.white : const Color(0xFF2C3E50);
  Color get _textSecondaryColor => _isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D);

  List<_Project> get _projects => [
    _Project(
      name: _isSwahili ? 'Villa ya Kifahari Dar es Salaam' : 'Luxury Villa in Dar es Salaam',
      status: _isSwahili ? 'Inaendelea' : 'In Progress',
      type: _isSwahili ? 'Makazi' : 'Residential',
      category: _isSwahili ? 'Makazi ya Kisasa' : 'Modern Residential',
      description: _isSwahili
          ? 'Nyumba ya kisasa yenye vyumba 4 vya kulala, sebule, chumba cha kulia, jiko, kufulia, choo cha wageni, chumba cha kusoma na gym.'
          : 'A modern 4-bedroom residential with living rooms, dining, kitchen, laundry, public toilet, study room and gym.',
      location: 'Arusha',
      year: '2025',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.10.png',
      isCompleted: false,
      priceTZS: 850000000, // 850M TZS
      priceUSD: 340000,    // 340K USD
      likes: 89,
    ),
    _Project(
      name: 'Palm Gardens Residences',
      status: _isSwahili ? 'Inaendelea' : 'In Progress',
      type: _isSwahili ? 'Makazi' : 'Residential',
      category: _isSwahili ? 'Makazi ya Kisasa' : 'Modern Residential',
      description: _isSwahili
          ? 'Nyumba ya kisasa yenye vyumba 4 vya kulala, sebule, chumba cha kulia, jiko, kufulia, choo cha wageni, chumba cha kusoma, saluni na gym.'
          : 'A modern 4-bedroom residential with living rooms, dining, kitchen, laundry, public toilet, study room, saloon and gym.',
      location: 'Dar es Salaam',
      year: '2025',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.20.png',
      isCompleted: false,
      priceTZS: 1200000000, // 1.2B TZS
      priceUSD: 480000,     // 480K USD
      likes: 124,
    ),
    _Project(
      name: _isSwahili ? 'Makazi' : 'Residentials',
      status: _isSwahili ? 'Imekamilika' : 'Completed',
      type: _isSwahili ? 'Makazi' : 'Residential',
      category: _isSwahili ? 'Makazi' : 'Residential',
      description: _isSwahili
          ? 'Ina sebule, jiko, vyumba vitatu vya kuishi peke yake, chumba kimoja kikubwa cha kulala, sebule ya familia, saluni, chumba cha kulia, kufulia na stoo.'
          : 'It contains living room, kitchen, three self contained rooms, one master bed room, family lounge, saloon, dining, laundry and store.',
      location: 'Dar es Salaam',
      year: '2025',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.28.png',
      isCompleted: true,
      priceTZS: 650000000, // 650M TZS
      priceUSD: 260000,    // 260K USD
      likes: 78,
    ),
    _Project(
      name: 'Skyline Business Center',
      status: _isSwahili ? 'Inaendelea' : 'In Progress',
      type: _isSwahili ? 'Biashara' : 'Commercial',
      category: 'Kariakoo Commercial Complex',
      description: _isSwahili
          ? 'Ni jengo la biashara lenye maduka arobaini.'
          : 'A commercial complex that has forty shops.',
      location: 'Dar es Salaam',
      year: '2025',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.31.png',
      isCompleted: false,
      priceTZS: 6900000000, // 6.9B TZS
      priceUSD: 2764000,    // 2.8M USD
      likes: 156,
    ),
    _Project(
      name: 'Savannah Shopping Mall',
      status: _isSwahili ? 'Imekamilika' : 'Completed',
      type: _isSwahili ? 'Biashara' : 'Commercial',
      category: _isSwahili ? 'Ghorofa' : 'Apartments',
      description: _isSwahili
          ? 'G + Ghorofa mbili yenye ghorofa kumi na mbili, iko Kimara.'
          : 'G + Two floors Apartment with twelve apartments, located in Kimara.',
      location: 'Dar es Salaam',
      year: '2025',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.40.png',
      isCompleted: true,
      priceTZS: 2500000000, // 2.5B TZS
      priceUSD: 1000000,    // 1M USD
      likes: 203,
    ),
    _Project(
      name: 'Horizon Hotel & Suites',
      status: _isSwahili ? 'Imekamilika' : 'Completed',
      type: _isSwahili ? 'Biashara' : 'Commercial',
      category: _isSwahili ? 'Ghorofa' : 'Apartments',
      description: _isSwahili
          ? 'G + Ghorofa mbili yenye ghorofa kumi na mbili, iko Kimara.'
          : 'G + Two floors Apartment with twelve apartments, located in Kimara.',
      location: 'Dar es Salaam',
      year: '2024',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.51.07.png',
      isCompleted: true,
      priceTZS: 3200000000, // 3.2B TZS
      priceUSD: 1280000,    // 1.3M USD
      likes: 167,
    ),
    _Project(
      name: 'Unity International School',
      status: _isSwahili ? 'Imekamilika' : 'Completed',
      type: _isSwahili ? 'Biashara' : 'Commercial',
      category: _isSwahili ? 'Ghorofa' : 'Apartment',
      description: _isSwahili
          ? 'Ghorofa mbili yenye vyumba kumi na viwili, iko Arusha.'
          : 'Two floors Apartment with twelve apartments, located in Arusha.',
      location: 'Arusha',
      year: '2024',
      image: 'assets/images/construction_01.png',
      isCompleted: true,
      priceTZS: 1800000000, // 1.8B TZS
      priceUSD: 720000,     // 720K USD
      likes: 95,
    ),
    _Project(
      name: _isSwahili ? 'Ukarabati wa Jengo la Urithi' : 'Heritage Building Restoration',
      status: _isSwahili ? 'Imekamilika' : 'Completed',
      type: _isSwahili ? 'Ukarabati' : 'Renovation',
      category: _isSwahili ? 'Ukarabati wa Jengo la Urithi' : 'Heritage Building Restoration',
      description: _isSwahili
          ? 'Ukarabati makini wa jengo la karne ya 19 la kikoloni, kuchanganya uhifadhi wa kihistoria na utendaji wa kisasa na viwango vya usalama.'
          : 'Careful restoration of a 19th century colonial-era building, combining historical preservation with modern functionality and safety standards.',
      location: 'Zanzibar',
      year: '2020',
      image: 'assets/images/structure_01.jpg',
      isCompleted: true,
      priceTZS: 450000000, // 450M TZS
      priceUSD: 180000,    // 180K USD
      likes: 142,
    ),
  ];

  List<_FeaturedProject> get _featuredProjects => [
    _FeaturedProject(
      name: _isSwahili ? 'Makazi ya Kifahari ya Pwani' : 'Oceanfront Luxury Residences',
      tagline: _isSwahili ? 'Maisha ya Kifahari na Mandhari ya Bahari' : 'Premium Living with Stunning Ocean Views',
      type: _isSwahili ? 'Makazi' : 'Residential',
      location: 'Dar es Salaam',
      size: '8,500 sq.m',
      yearCompleted: '2023',
      duration: _isSwahili ? 'Miezi 18' : '18 Months',
      client: 'Ocean View',
      description: _isSwahili
          ? 'Mradi wa Makazi ya Kifahari ya Pwani una ghorofa nane za kipekee zenye madirisha makubwa yanayotoa mandhari ya Bahari ya Hindi. Kila nyumba inachanganya usanifu wa kisasa na kanuni za muundo endelevu.'
          : 'The Oceanfront Luxury Residences project features eight exclusive apartments with floor-to-ceiling windows offering panoramic views of the Indian Ocean. Each residence combines modern architecture with sustainable design principles.',
      extendedDescription: _isSwahili
          ? 'Mradi huu wa kipekee unajumuisha mbinu za ujenzi za kisasa, vifaa vya hali ya juu, na teknolojia ya nyumba smart kutoa uzoefu wa kuishi usio na kifani unaooana na mazingira ya asili.'
          : 'This signature project incorporates advanced construction techniques, premium materials, and smart home technology to deliver an unparalleled living experience that harmonizes with the natural surroundings.',
      features: [
        {'icon': Icons.solar_power_rounded, 'label': _isSwahili ? 'Nguvu za Jua' : 'Solar Power'},
        {'icon': Icons.water_drop_rounded, 'label': _isSwahili ? 'Uvunaji Mvua' : 'Rain Harvest'},
        {'icon': Icons.park_rounded, 'label': _isSwahili ? 'Bustani' : 'Gardens'},
        {'icon': Icons.pool_rounded, 'label': _isSwahili ? 'Bwawa' : 'Pool'},
      ],
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.10.png',
    ),
    _FeaturedProject(
      name: 'Palm Gardens Residences',
      tagline: _isSwahili ? 'Uzuri wa Asili katika Moyo wa Jiji' : 'Natural Beauty in the Heart of the City',
      type: _isSwahili ? 'Makazi' : 'Residential',
      location: 'Dar es Salaam',
      size: '6,200 sq.m',
      yearCompleted: '2025',
      duration: _isSwahili ? 'Miezi 14' : '14 Months',
      client: 'Green Living Corp',
      description: _isSwahili
          ? 'Palm Gardens Residences ni mradi wa makazi ya kisasa yenye vyumba vinne vya kulala, iliyoundwa kwa maisha ya familia ya kisasa.'
          : 'Palm Gardens Residences is a modern residential project featuring four-bedroom homes designed for contemporary family living.',
      extendedDescription: _isSwahili
          ? 'Kila nyumba ina sebule pana, jiko la kisasa, chumba cha kulia, kufulia, choo cha wageni, chumba cha kusoma, saluni na gym.'
          : 'Each home features spacious living rooms, modern kitchen, dining area, laundry, guest toilet, study room, saloon and gym.',
      features: [
        {'icon': Icons.fitness_center_rounded, 'label': 'Gym'},
        {'icon': Icons.local_parking_rounded, 'label': _isSwahili ? 'Maegesho' : 'Parking'},
        {'icon': Icons.security_rounded, 'label': _isSwahili ? 'Ulinzi 24/7' : '24/7 Security'},
        {'icon': Icons.wifi_rounded, 'label': 'Smart Home'},
      ],
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.20.png',
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
        onDarkModeToggle: () => ref.read(settingsProvider.notifier).toggleDarkMode(),
        onLanguageToggle: () => ref.read(settingsProvider.notifier).toggleLanguage(),
        flagWidget: _isSwahili ? const TanzaniaFlag() : const UKFlag(),
      ),
      body: CustomScrollView(
        slivers: [
          // Hero Section
          SliverToBoxAdapter(
            child: _buildHeroSection(),
          ),

          // Featured Project Section
          SliverToBoxAdapter(
            child: _buildFeaturedProjectSection(),
          ),

          // Projects Section Header
          SliverToBoxAdapter(
            child: _buildProjectsSectionHeader(),
          ),

          // Projects List
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) => Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: _buildProjectCard(_projects[index]),
                ),
                childCount: _projects.length,
              ),
            ),
          ),

          // Service Delivery Process
          SliverToBoxAdapter(
            child: _buildServiceDeliveryProcess(),
          ),

          // Footer spacing
          const SliverToBoxAdapter(
            child: SizedBox(height: 120),
          ),
        ],
      ),
      bottomNavigationBar: CurvedBottomNav(
        selectedIndex: 1, // Projects is index 1
        isDarkMode: _isDarkMode,
        isSwahili: _isSwahili,
      ),
    );
  }

  Widget _buildHeroSection() {
    return Stack(
      children: [
        SizedBox(
          height: 200,
          width: double.infinity,
          child: Image.asset(
            'assets/images/post/Screenshot 2026-01-21 at 14.50.40.png',
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF3498DB), Color(0xFF1ABC9C)],
                ),
              ),
              child: const Center(
                child: Icon(Icons.business_rounded, size: 80, color: Colors.white24),
              ),
            ),
          ),
        ),
        Container(
          height: 200,
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Colors.black.withValues(alpha: 0.3),
                Colors.black.withValues(alpha: 0.7),
              ],
            ),
          ),
        ),
        Positioned(
          bottom: 20,
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
                child: Text(
                  _isSwahili ? 'Kazi Zetu' : 'Our Work',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Text(
                _isSwahili ? 'Miradi Yetu' : 'Our Projects',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                _isSwahili
                    ? 'Angalia miradi yetu iliyokamilishwa na inayoendelea'
                    : 'Explore our completed and ongoing projects',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.9),
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildFeaturedProjectSection() {
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
                  color: const Color(0xFFD4AF37),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _isSwahili ? 'Mradi wa Kipekee' : 'Featured Project',
                    style: const TextStyle(
                      color: Color(0xFFD4AF37),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _isSwahili ? 'Kazi Yetu ya Kipekee' : 'Our Signature Work',
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
          const SizedBox(height: 8),
          Text(
            _isSwahili
                ? 'Angalia mradi wetu unaoonyesha kujitolea kwetu kwa ubora, ubunifu, na mazoea endelevu ya ujenzi.'
                : 'Explore our flagship project that showcases our commitment to excellence, innovation, and sustainable construction practices.',
            style: TextStyle(
              color: _textSecondaryColor,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 20),

          // Featured Project Carousel
          SizedBox(
            height: 480,
            child: PageView.builder(
              controller: _featuredController,
              onPageChanged: (index) {
                setState(() => _currentFeaturedPage = index);
              },
              itemCount: _featuredProjects.length,
              itemBuilder: (context, index) {
                return _buildFeaturedProjectCard(_featuredProjects[index]);
              },
            ),
          ),
          const SizedBox(height: 16),

          // Page Indicators
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(
              _featuredProjects.length,
              (index) => AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: _currentFeaturedPage == index ? 24 : 8,
                height: 8,
                decoration: BoxDecoration(
                  color: _currentFeaturedPage == index
                      ? const Color(0xFFD4AF37)
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

  Widget _buildFeaturedProjectCard(_FeaturedProject project) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 4),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(20),
        border: _isDarkMode
            ? Border.all(color: const Color(0xFFD4AF37).withValues(alpha: 0.3))
            : null,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFD4AF37).withValues(alpha: 0.15),
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
                  height: 160,
                  width: double.infinity,
                  child: Image.asset(
                    project.image,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [Color(0xFFD4AF37), Color(0xFFF39C12)],
                        ),
                      ),
                      child: const Icon(Icons.villa_rounded, size: 60, color: Colors.white38),
                    ),
                  ),
                ),
              ),
              Positioned(
                top: 12,
                left: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: const Color(0xFFD4AF37),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    '${project.yearCompleted} ${_isSwahili ? 'Imekamilika' : 'Year Completed'}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
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
                    project.name,
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    project.tagline,
                    style: TextStyle(
                      color: const Color(0xFFD4AF37),
                      fontSize: 12,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Project details grid
                  Row(
                    children: [
                      _buildDetailChip(Icons.category_rounded, project.type),
                      const SizedBox(width: 8),
                      _buildDetailChip(Icons.location_on_rounded, project.location),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      _buildDetailChip(Icons.square_foot_rounded, project.size),
                      const SizedBox(width: 8),
                      _buildDetailChip(Icons.timer_rounded, project.duration),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Description
                  Expanded(
                    child: Text(
                      project.description,
                      style: TextStyle(
                        color: _textSecondaryColor,
                        fontSize: 12,
                        height: 1.4,
                      ),
                      maxLines: 3,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),

                  // Features & WhatsApp Button
                  Row(
                    children: [
                      // Features (take remaining space)
                      Expanded(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: project.features.take(3).map((f) => Column(
                            children: [
                              Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  color: const Color(0xFF1ABC9C).withValues(alpha: 0.15),
                                  shape: BoxShape.circle,
                                ),
                                child: Icon(
                                  f['icon'] as IconData,
                                  color: const Color(0xFF1ABC9C),
                                  size: 16,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                f['label'] as String,
                                style: TextStyle(
                                  color: _textSecondaryColor,
                                  fontSize: 8,
                                ),
                              ),
                            ],
                          )).toList(),
                        ),
                      ),
                      // WhatsApp Button
                      GestureDetector(
                        onTap: () => _launchWhatsApp(project.name),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFF25D366),
                            borderRadius: BorderRadius.circular(10),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFF25D366).withValues(alpha: 0.4),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(
                                Icons.chat_rounded,
                                color: Colors.white,
                                size: 16,
                              ),
                              const SizedBox(width: 6),
                              Text(
                                _isSwahili ? 'Uliza' : 'Inquire',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailChip(IconData icon, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        decoration: BoxDecoration(
          color: _isDarkMode ? Colors.white.withValues(alpha: 0.1) : Colors.grey.shade100,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: const Color(0xFF1ABC9C)),
            const SizedBox(width: 4),
            Flexible(
              child: Text(
                label,
                style: TextStyle(
                  color: _textSecondaryColor,
                  fontSize: 10,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProjectsSectionHeader() {
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
                _isSwahili ? 'Miradi Yote' : 'All Projects',
                style: const TextStyle(
                  color: Color(0xFF3498DB),
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 1,
                ),
              ),
              Text(
                _isSwahili ? 'Kazi Zetu' : 'Our Portfolio',
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

  // Get formatted price based on language selection
  String _getFormattedPrice(_Project project) {
    if (_isSwahili && project.priceTZS != null) {
      return 'TZS ${_formatNumber(project.priceTZS!)}';
    } else if (!_isSwahili && project.priceUSD != null) {
      return 'USD ${_formatNumber(project.priceUSD!)}';
    }
    return '';
  }

  Widget _buildProjectCard(_Project project) {
    final statusColor = project.isCompleted
        ? const Color(0xFF27AE60)
        : const Color(0xFFF39C12);
    final priceText = _getFormattedPrice(project);

    return GestureDetector(
      onTap: () => _showProjectDetails(project),
      child: Container(
        decoration: BoxDecoration(
          color: _cardBgColor,
          borderRadius: BorderRadius.circular(16),
          border: _isDarkMode
              ? Border.all(color: statusColor.withValues(alpha: 0.3))
              : null,
          boxShadow: [
            BoxShadow(
              color: statusColor.withValues(alpha: 0.1),
              blurRadius: 15,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Row(
          children: [
            // Image
            ClipRRect(
              borderRadius: const BorderRadius.horizontal(left: Radius.circular(16)),
              child: SizedBox(
                width: 120,
                height: 160,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Image.asset(
                      project.image,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [statusColor.withValues(alpha: 0.7), statusColor],
                          ),
                        ),
                        child: Icon(
                          Icons.business_rounded,
                          size: 40,
                          color: Colors.white.withValues(alpha: 0.5),
                        ),
                      ),
                    ),
                    // Status badge
                    Positioned(
                      top: 8,
                      left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: statusColor,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          project.status,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 8,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                    // Price badge at bottom
                    if (priceText.isNotEmpty)
                      Positioned(
                        bottom: 0,
                        left: 0,
                        right: 0,
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 6),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [
                                Colors.transparent,
                                Colors.black.withValues(alpha: 0.8),
                              ],
                            ),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF1ABC9C),
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: Text(
                                  priceText,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              if (project.likes > 0) ...[
                                const SizedBox(width: 6),
                                const Icon(
                                  Icons.favorite,
                                  color: Color(0xFFE74C3C),
                                  size: 12,
                                ),
                                const SizedBox(width: 2),
                                Text(
                                  '${project.likes}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),

            // Content
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Title
                    Text(
                      project.name,
                      style: TextStyle(
                        color: _textPrimaryColor,
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    // Category
                    Text(
                      project.category,
                      style: TextStyle(
                        color: statusColor,
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    // Description
                    Text(
                      project.description,
                      style: TextStyle(
                        color: _textSecondaryColor,
                        fontSize: 11,
                        height: 1.3,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    // Location & Year
                    Row(
                      children: [
                        Icon(
                          Icons.location_on_rounded,
                          size: 12,
                          color: _textSecondaryColor,
                        ),
                        const SizedBox(width: 2),
                        Flexible(
                          child: Text(
                            project.location,
                            style: TextStyle(
                              color: _textSecondaryColor,
                              fontSize: 10,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Icon(
                          Icons.calendar_today_rounded,
                          size: 12,
                          color: _textSecondaryColor,
                        ),
                        const SizedBox(width: 2),
                        Text(
                          project.year,
                          style: TextStyle(
                            color: _textSecondaryColor,
                            fontSize: 10,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    // WhatsApp Inquiry Button
                    GestureDetector(
                      onTap: () => _launchWhatsApp(project.name),
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF25D366),
                          borderRadius: BorderRadius.circular(8),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF25D366).withValues(alpha: 0.4),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.chat_rounded,
                              color: Colors.white,
                              size: 14,
                            ),
                            const SizedBox(width: 6),
                            Text(
                              _isSwahili ? 'Uliza WhatsApp' : 'Inquire WhatsApp',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showProjectDetails(_Project project) {
    final statusColor = project.isCompleted
        ? const Color(0xFF27AE60)
        : const Color(0xFFF39C12);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.8,
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
                    // Image
                    ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: SizedBox(
                        height: 200,
                        width: double.infinity,
                        child: Image.asset(
                          project.image,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [statusColor.withValues(alpha: 0.7), statusColor],
                              ),
                            ),
                            child: const Icon(Icons.business_rounded, size: 80, color: Colors.white38),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Status badge
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: statusColor,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            project.status,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFF3498DB).withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            project.type,
                            style: const TextStyle(
                              color: Color(0xFF3498DB),
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Title
                    Text(
                      project.name,
                      style: TextStyle(
                        color: _textPrimaryColor,
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      project.category,
                      style: TextStyle(
                        color: statusColor,
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Location & Year
                    Row(
                      children: [
                        _buildInfoItem(Icons.location_on_rounded, project.location),
                        const SizedBox(width: 16),
                        _buildInfoItem(Icons.calendar_today_rounded, project.year),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Price Card
                    if (_getFormattedPrice(project).isNotEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [Color(0xFF1ABC9C), Color(0xFF16A085)],
                          ),
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF1ABC9C).withValues(alpha: 0.3),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Row(
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _isSwahili ? 'Bei ya Mradi' : 'Project Value',
                                  style: TextStyle(
                                    color: Colors.white.withValues(alpha: 0.9),
                                    fontSize: 12,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  _getFormattedPrice(project),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 24,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                            const Spacer(),
                            if (project.likes > 0)
                              Row(
                                children: [
                                  const Icon(
                                    Icons.favorite,
                                    color: Colors.white,
                                    size: 20,
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    '${project.likes}',
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 18,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    _isSwahili ? 'wanapenda' : 'interested',
                                    style: TextStyle(
                                      color: Colors.white.withValues(alpha: 0.9),
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                          ],
                        ),
                      ),
                    const SizedBox(height: 16),

                    // Description
                    Text(
                      _isSwahili ? 'Maelezo' : 'Description',
                      style: TextStyle(
                        color: _textPrimaryColor,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      project.description,
                      style: TextStyle(
                        color: _textSecondaryColor,
                        fontSize: 14,
                        height: 1.6,
                      ),
                    ),
                    const SizedBox(height: 24),

                    // WhatsApp Inquiry Button
                    ElevatedButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _launchWhatsApp(project.name);
                      },
                      icon: const Icon(Icons.chat_rounded, size: 20),
                      label: Text(
                        _isSwahili ? 'Uliza kupitia WhatsApp' : 'Inquire via WhatsApp',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF25D366),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        elevation: 4,
                        shadowColor: const Color(0xFF25D366).withValues(alpha: 0.5),
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

  Widget _buildInfoItem(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 18, color: const Color(0xFF1ABC9C)),
        const SizedBox(width: 6),
        Text(
          text,
          style: TextStyle(
            color: _textSecondaryColor,
            fontSize: 14,
          ),
        ),
      ],
    );
  }

  Widget _buildServiceDeliveryProcess() {
    final steps = [
      {
        'number': '1',
        'title': _isSwahili ? 'Ushauri' : 'Consultation',
        'description': _isSwahili
            ? 'Tunaanza na ushauri wa kina kuelewa mahitaji yako na vikwazo vya bajeti.'
            : 'We begin with a thorough consultation to understand your needs and budget constraints.',
        'items': _isSwahili
            ? ['Uchambuzi wa Mahitaji', 'Ziara ya Eneo', 'Maoni ya Awali']
            : ['Requirement Analysis', 'Site Visit', 'Initial Feedback'],
        'color': const Color(0xFF3498DB),
      },
      {
        'number': '2',
        'title': _isSwahili ? 'Mipango' : 'Planning',
        'description': _isSwahili
            ? 'Timu yetu inaunda mipango na miundo ya kina inayofaa mahitaji ya mradi wako.'
            : 'Our team develops detailed plans and designs tailored to your project requirements.',
        'items': _isSwahili
            ? ['Uundaji wa Dhana', 'Muundo wa Kina', 'Makadirio ya Gharama']
            : ['Concept Development', 'Detailed Design', 'Cost Estimation'],
        'color': const Color(0xFF9B59B6),
      },
      {
        'number': '3',
        'title': _isSwahili ? 'Utekelezaji' : 'Execution',
        'description': _isSwahili
            ? 'Tunatekeleza mipango kwa umakini, udhibiti wa ubora, na usimamizi wa ratiba.'
            : 'We implement the plans with attention to detail, quality control, and timeline management.',
        'items': _isSwahili
            ? ['Uhamasishaji wa Rasilimali', 'Udhibiti wa Ubora', 'Ripoti za Maendeleo']
            : ['Resource Mobilization', 'Quality Control', 'Progress Reporting'],
        'color': const Color(0xFFF39C12),
      },
      {
        'number': '4',
        'title': _isSwahili ? 'Kukamilisha' : 'Completion',
        'description': _isSwahili
            ? 'Ukaguzi wa mwisho unahakikisha kila kitu kinakidhi viwango vyetu vya juu kabla ya kukabidhiwa mradi.'
            : 'Final inspections ensure everything meets our high standards before project handover.',
        'items': _isSwahili
            ? ['Ukaguzi wa Mwisho', 'Mchakato wa Kukabidhiwa', 'Msaada Unaoendelea']
            : ['Final Inspection', 'Handover Process', 'Ongoing Support'],
        'color': const Color(0xFF1ABC9C),
      },
    ];

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
                    _isSwahili ? 'Mchakato Wetu' : 'Our Service Delivery Process',
                    style: const TextStyle(
                      color: Color(0xFF1ABC9C),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _isSwahili ? 'Mbinu ya Kimfumo' : 'Systematic Approach',
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
          const SizedBox(height: 8),
          Text(
            _isSwahili
                ? 'Tunafuata mbinu ya kimfumo kuhakikisha ubora, ufanisi, na kuridhika kwa mteja katika kila hatua.'
                : 'We follow a systematic approach to ensure quality, efficiency, and client satisfaction at every stage.',
            style: TextStyle(
              color: _textSecondaryColor,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 24),

          // Steps
          ...steps.asMap().entries.map((entry) {
            final index = entry.key;
            final step = entry.value;
            final isLast = index == steps.length - 1;

            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Number and line
                Column(
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: step['color'] as Color,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          step['number'] as String,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                    if (!isLast)
                      Container(
                        width: 2,
                        height: 100,
                        color: (step['color'] as Color).withValues(alpha: 0.3),
                      ),
                  ],
                ),
                const SizedBox(width: 16),
                // Content
                Expanded(
                  child: Padding(
                    padding: EdgeInsets.only(bottom: isLast ? 0 : 20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          step['title'] as String,
                          style: TextStyle(
                            color: _textPrimaryColor,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          step['description'] as String,
                          style: TextStyle(
                            color: _textSecondaryColor,
                            fontSize: 12,
                            height: 1.4,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 6,
                          children: (step['items'] as List<String>).map((item) => Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: (step['color'] as Color).withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              item,
                              style: TextStyle(
                                color: step['color'] as Color,
                                fontSize: 10,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          )).toList(),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            );
          }),
        ],
      ),
    );
  }
}

class _Project {
  final String name;
  final String status;
  final String type;
  final String category;
  final String description;
  final String location;
  final String year;
  final String image;
  final bool isCompleted;
  final double? priceTZS; // Price in Tanzanian Shillings
  final double? priceUSD; // Price in US Dollars
  final int likes; // Number of likes/interest

  _Project({
    required this.name,
    required this.status,
    required this.type,
    required this.category,
    required this.description,
    required this.location,
    required this.year,
    required this.image,
    required this.isCompleted,
    this.priceTZS,
    this.priceUSD,
    this.likes = 0,
  });
}

class _FeaturedProject {
  final String name;
  final String tagline;
  final String type;
  final String location;
  final String size;
  final String yearCompleted;
  final String duration;
  final String client;
  final String description;
  final String extendedDescription;
  final List<Map<String, dynamic>> features;
  final String image;

  _FeaturedProject({
    required this.name,
    required this.tagline,
    required this.type,
    required this.location,
    required this.size,
    required this.yearCompleted,
    required this.duration,
    required this.client,
    required this.description,
    required this.extendedDescription,
    required this.features,
    required this.image,
  });
}
