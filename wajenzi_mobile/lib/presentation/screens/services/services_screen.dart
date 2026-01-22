import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

class ServicesScreen extends ConsumerStatefulWidget {
  const ServicesScreen({super.key});

  @override
  ConsumerState<ServicesScreen> createState() => _ServicesScreenState();
}

class _ServicesScreenState extends ConsumerState<ServicesScreen> {
  // Use global settings from provider
  bool get _isDarkMode => ref.watch(isDarkModeProvider);
  bool get _isSwahili => ref.watch(isSwahiliProvider);

  // Dark mode colors
  Color get _bgColor => _isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);
  Color get _cardBgColor => _isDarkMode ? const Color(0xFF16213E) : Colors.white;
  Color get _textPrimaryColor => _isDarkMode ? Colors.white : const Color(0xFF2C3E50);
  Color get _textSecondaryColor => _isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D);

  List<_ServiceItem> get _services => [
    _ServiceItem(
      id: 'construction',
      title: _isSwahili ? 'Kazi za Ujenzi' : 'Construction Work',
      shortDescription: _isSwahili
          ? 'Huduma za ujenzi wa kitaalamu kwa miradi ya makazi, biashara, na taasisi kwa umakini na ubora.'
          : 'Expert construction services for residential, commercial, and institutional projects with attention to detail and quality.',
      fullDescription: _isSwahili
          ? 'Timu yetu ya wajenzi wenye uzoefu inatoa huduma kamili za ujenzi kutoka msingi hadi kukamilika. Tunashughulikia miradi yote - kuanzia nyumba za familia moja hadi majengo ya ghorofa nyingi na miundombinu ya kibiashara.'
          : 'Our experienced team of builders provides comprehensive construction services from foundation to completion. We handle all project types - from single-family homes to multi-story buildings and commercial complexes.',
      image: 'assets/images/construction_01.png',
      icon: Icons.construction_rounded,
      color: const Color(0xFF3498DB),
      features: _isSwahili
          ? ['Ujenzi wa Makazi', 'Majengo ya Biashara', 'Ukarabati & Upanuzi', 'Usimamizi wa Mradi']
          : ['Residential Construction', 'Commercial Buildings', 'Renovations & Extensions', 'Project Management'],
    ),
    _ServiceItem(
      id: 'architectural',
      title: _isSwahili ? 'Usanifu wa Majengo' : 'Architectural Design',
      shortDescription: _isSwahili
          ? 'Miundo ya usanifu ya ubunifu na inayofanya kazi ambayo inabadilisha maono yako kuwa ukweli kwa kuzingatia uendelevu.'
          : 'Creative and functional architectural designs that transform your vision into reality with sustainability in mind.',
      fullDescription: _isSwahili
          ? 'Wasanifu wetu wa majengo huchanganya ubunifu na utendaji kuunda nafasi zinazovutia na za vitendo. Kila muundo umeboreshwa kwa mtindo wako wa maisha, bajeti, na athari za mazingira.'
          : 'Our architects blend creativity with functionality to create stunning and practical spaces. Every design is optimized for your lifestyle, budget, and environmental impact.',
      image: 'assets/images/NEW_1 - Photo.jpg',
      icon: Icons.architecture_rounded,
      color: const Color(0xFF9B59B6),
      features: _isSwahili
          ? ['Miundo ya 2D & 3D', 'Upangaji wa Mambo ya Ndani', 'Usanifu wa Mazingira', 'Muundo Endelevu']
          : ['2D & 3D Designs', 'Interior Planning', 'Landscape Design', 'Sustainable Design'],
    ),
    _ServiceItem(
      id: 'boq',
      title: _isSwahili ? 'Orodha ya Vifaa' : 'Bill of Quantity',
      shortDescription: _isSwahili
          ? 'Huduma sahihi za makadirio ya gharama kukusaidia kupanga na kubajeti mradi wako wa ujenzi kwa ufanisi.'
          : 'Accurate cost estimation services to help you plan and budget your construction project effectively.',
      fullDescription: _isSwahili
          ? 'Makadirio yetu ya kina ya BOQ yanakupa picha kamili ya gharama za mradi wako. Tunaorodhesha vifaa vyote, kazi, na gharama ili uweze kupanga na kubajeti kwa usahihi.'
          : 'Our detailed BOQ estimates give you a complete picture of your project costs. We itemize all materials, labor, and expenses so you can plan and budget accurately.',
      image: 'assets/images/bill _of_quanties_01.png',
      icon: Icons.calculate_rounded,
      color: const Color(0xFFF39C12),
      features: _isSwahili
          ? ['Makadirio ya Gharama', 'Orodha ya Vifaa', 'Uchambuzi wa Kazi', 'Upangaji wa Bajeti']
          : ['Cost Estimation', 'Material Listing', 'Labor Analysis', 'Budget Planning'],
    ),
    _ServiceItem(
      id: 'structural',
      title: _isSwahili ? 'Usanifu wa Miundo' : 'Structural Design',
      shortDescription: _isSwahili
          ? 'Huduma za uhandisi wa miundo wa kitaalamu zinazohakikisha majengo yako ni salama, imara, na yaliyojengwa kudumu.'
          : 'Expert structural engineering services that ensure your buildings are safe, stable, and built to last.',
      fullDescription: _isSwahili
          ? 'Wahandisi wetu wa miundo hufanya uchambuzi wa kina na muundo ili kuhakikisha jengo lako linaweza kustahimili mizigo yote na hali za mazingira kwa usalama.'
          : 'Our structural engineers perform thorough analysis and design to ensure your building can safely withstand all loads and environmental conditions.',
      image: 'assets/images/structure_01.jpg',
      icon: Icons.account_balance_rounded,
      color: const Color(0xFF1ABC9C),
      features: _isSwahili
          ? ['Uchambuzi wa Miundo', 'Muundo wa Msingi', 'Hesabu za Mizigo', 'Tathmini ya Usalama']
          : ['Structural Analysis', 'Foundation Design', 'Load Calculations', 'Safety Assessment'],
    ),
    _ServiceItem(
      id: 'interior',
      title: _isSwahili ? 'Muundo wa Ndani' : 'Interior Design',
      shortDescription: _isSwahili
          ? 'Huduma za kubadilisha nafasi yako ya ndani kuwa mazingira mazuri na yanayofanya kazi yanayoonyesha mtindo wako.'
          : 'Transform your interior spaces into beautiful and functional environments that reflect your personal style.',
      fullDescription: _isSwahili
          ? 'Timu yetu ya wabuni wa ndani huunda nafasi zinazounganisha urembo na utendaji. Tunashughulikia kila kitu kutoka kuchagua rangi na fanicha hadi taa na mapambo.'
          : 'Our interior design team creates spaces that combine aesthetics with functionality. We handle everything from color selection and furniture to lighting and decor.',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.28.png',
      icon: Icons.chair_rounded,
      color: const Color(0xFFE74C3C),
      features: _isSwahili
          ? ['Mpangilio wa Nafasi', 'Uchaguzi wa Fanicha', 'Muundo wa Taa', 'Uchaguzi wa Rangi']
          : ['Space Planning', 'Furniture Selection', 'Lighting Design', 'Color Consultation'],
    ),
    _ServiceItem(
      id: 'consultation',
      title: _isSwahili ? 'Ushauri wa Ujenzi' : 'Construction Consultation',
      shortDescription: _isSwahili
          ? 'Ushauri wa kitaalamu kwa miradi yako ya ujenzi kutoka hatua ya mipango hadi kukamilika.'
          : 'Professional consultation for your construction projects from planning stage to completion.',
      fullDescription: _isSwahili
          ? 'Wataalamu wetu hutoa ushauri wa kina kuhusu miradi yako ya ujenzi. Tunakusaidia kuelewa chaguzi, kupunguza hatari, na kufanya maamuzi sahihi.'
          : 'Our experts provide comprehensive consultation on your construction projects. We help you understand options, minimize risks, and make informed decisions.',
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.51.07.png',
      icon: Icons.support_agent_rounded,
      color: const Color(0xFF2C3E50),
      features: _isSwahili
          ? ['Tathmini ya Mradi', 'Ushauri wa Bajeti', 'Ukaguzi wa Ubora', 'Usimamizi wa Hatari']
          : ['Project Assessment', 'Budget Advisory', 'Quality Inspection', 'Risk Management'],
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

          // Services Grid Header
          SliverToBoxAdapter(
            child: _buildSectionHeader(),
          ),

          // Services Grid
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 16,
                crossAxisSpacing: 16,
                childAspectRatio: 0.72,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, index) => _buildServiceCard(_services[index]),
                childCount: _services.length,
              ),
            ),
          ),

          // Why Choose Us Section
          SliverToBoxAdapter(
            child: _buildWhyChooseUsSection(),
          ),

          // CTA Section
          SliverToBoxAdapter(
            child: _buildCTASection(),
          ),

          // Footer spacing
          const SliverToBoxAdapter(
            child: SizedBox(height: 120),
          ),
        ],
      ),
      bottomNavigationBar: CurvedBottomNav(
        selectedIndex: 2, // Services is index 2
        isDarkMode: _isDarkMode,
        isSwahili: _isSwahili,
      ),
    );
  }

  Widget _buildHeroSection() {
    return Stack(
      children: [
        // Background Image
        SizedBox(
          height: 220,
          width: double.infinity,
          child: Image.asset(
            'assets/images/construction_01.png',
            fit: BoxFit.cover,
            errorBuilder: (_, __, ___) => Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF1ABC9C), Color(0xFF3498DB)],
                ),
              ),
              child: const Center(
                child: Icon(
                  Icons.design_services_rounded,
                  size: 80,
                  color: Colors.white24,
                ),
              ),
            ),
          ),
        ),
        // Gradient Overlay
        Container(
          height: 220,
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
        // Content
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
                child: Text(
                  _isSwahili ? 'Huduma Zetu' : 'Our Services',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                _isSwahili
                    ? 'Suluhisho Kamili za Ujenzi'
                    : 'Complete Construction Solutions',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _isSwahili
                    ? 'Kutoka wazo hadi ukweli - tunakuza ndoto zako'
                    : 'From concept to reality - we build your dreams',
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

  Widget _buildSectionHeader() {
    return Container(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
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
                    _isSwahili ? 'Tunachokifanya' : 'What We Do',
                    style: const TextStyle(
                      color: Color(0xFF1ABC9C),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _isSwahili ? 'Huduma Zetu' : 'Our Services',
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            _isSwahili
                ? 'Tunatoa huduma mbalimbali za ujenzi na usanifu ili kukidhi mahitaji yako yote ya ujenzi.'
                : 'We offer a wide range of construction and design services to meet all your building needs.',
            style: TextStyle(
              color: _textSecondaryColor,
              fontSize: 14,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildServiceCard(_ServiceItem service) {
    return GestureDetector(
      onTap: () => _showServiceDetails(service),
      child: Container(
        decoration: BoxDecoration(
          color: _cardBgColor,
          borderRadius: BorderRadius.circular(16),
          border: _isDarkMode
              ? Border.all(color: service.color.withValues(alpha: 0.3))
              : null,
          boxShadow: [
            BoxShadow(
              color: service.color.withValues(alpha: 0.1),
              blurRadius: 15,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image with label overlay
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                  child: SizedBox(
                    height: 120,
                    width: double.infinity,
                    child: Image.asset(
                      service.image,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [
                              service.color.withValues(alpha: 0.7),
                              service.color,
                            ],
                          ),
                        ),
                        child: Icon(
                          service.icon,
                          size: 50,
                          color: Colors.white.withValues(alpha: 0.5),
                        ),
                      ),
                    ),
                  ),
                ),
                // Label overlay at bottom
                Positioned(
                  bottom: 0,
                  left: 0,
                  right: 0,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withValues(alpha: 0.7),
                        ],
                      ),
                    ),
                    child: Text(
                      service.title.toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 9,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.5,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
              ],
            ),

            // Content
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Title with underline
                    Text(
                      service.title,
                      style: TextStyle(
                        color: _textPrimaryColor,
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Container(
                      margin: const EdgeInsets.only(top: 4, bottom: 8),
                      width: 30,
                      height: 2,
                      decoration: BoxDecoration(
                        color: service.color,
                        borderRadius: BorderRadius.circular(1),
                      ),
                    ),
                    // Description
                    Expanded(
                      child: Text(
                        service.shortDescription,
                        style: TextStyle(
                          color: _textSecondaryColor,
                          fontSize: 11,
                          height: 1.4,
                        ),
                        maxLines: 4,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    // Learn More button
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        border: Border.all(color: service.color),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        _isSwahili ? 'Jifunze Zaidi' : 'Learn More',
                        style: TextStyle(
                          color: service.color,
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
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

  void _showServiceDetails(_ServiceItem service) {
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
              // Handle
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
                    // Service Image
                    ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: SizedBox(
                        height: 180,
                        width: double.infinity,
                        child: Image.asset(
                          service.image,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [service.color.withValues(alpha: 0.7), service.color],
                              ),
                            ),
                            child: Icon(
                              service.icon,
                              size: 80,
                              color: Colors.white.withValues(alpha: 0.5),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Title with icon
                    Row(
                      children: [
                        Container(
                          width: 50,
                          height: 50,
                          decoration: BoxDecoration(
                            color: service.color.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(
                            service.icon,
                            color: service.color,
                            size: 26,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                service.title,
                                style: TextStyle(
                                  color: _textPrimaryColor,
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              Container(
                                margin: const EdgeInsets.only(top: 6),
                                width: 40,
                                height: 3,
                                decoration: BoxDecoration(
                                  color: service.color,
                                  borderRadius: BorderRadius.circular(2),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Full Description
                    Text(
                      service.fullDescription,
                      style: TextStyle(
                        color: _textSecondaryColor,
                        fontSize: 15,
                        height: 1.6,
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Features
                    Text(
                      _isSwahili ? 'Kinachojumuishwa:' : 'What\'s Included:',
                      style: TextStyle(
                        color: _textPrimaryColor,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    ...service.features.map((feature) => Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Row(
                        children: [
                          Container(
                            width: 24,
                            height: 24,
                            decoration: BoxDecoration(
                              color: service.color.withValues(alpha: 0.15),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.check_rounded,
                              color: service.color,
                              size: 14,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Text(
                            feature,
                            style: TextStyle(
                              color: _textSecondaryColor,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    )),
                    const SizedBox(height: 24),

                    // CTA Button
                    ElevatedButton(
                      onPressed: () {
                        Navigator.pop(context);
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(
                              _isSwahili
                                  ? 'Wasiliana nasi kwa ${service.title}'
                                  : 'Contact us for ${service.title}',
                            ),
                          ),
                        );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: service.color,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        _isSwahili ? 'Omba Huduma Hii' : 'Request This Service',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
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

  Widget _buildWhyChooseUsSection() {
    final reasons = [
      {
        'icon': Icons.verified_rounded,
        'title': _isSwahili ? 'Ubora wa Juu' : 'Premium Quality',
        'description': _isSwahili
            ? 'Tunatumia vifaa bora na mbinu za kisasa'
            : 'We use top-quality materials and modern techniques',
      },
      {
        'icon': Icons.schedule_rounded,
        'title': _isSwahili ? 'Kwa Wakati' : 'On-Time Delivery',
        'description': _isSwahili
            ? 'Tunakamilisha miradi kwa wakati uliokubaliwa'
            : 'We complete projects within agreed timelines',
      },
      {
        'icon': Icons.handshake_rounded,
        'title': _isSwahili ? 'Tunategemewa' : 'Trusted Partner',
        'description': _isSwahili
            ? 'Zaidi ya miradi 120 iliyokamilishwa kwa mafanikio'
            : 'Over 120 projects completed successfully',
      },
    ];

    return Container(
      margin: const EdgeInsets.all(20),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: _isDarkMode
              ? [const Color(0xFF0F3460), const Color(0xFF16213E)]
              : [const Color(0xFF1ABC9C), const Color(0xFF16A085)],
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _isSwahili ? 'Kwa Nini Utuchague?' : 'Why Choose Us?',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 20),
          ...reasons.map((reason) => Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    reason['icon'] as IconData,
                    color: Colors.white,
                    size: 22,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        reason['title'] as String,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        reason['description'] as String,
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.85),
                          fontSize: 13,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          )),
        ],
      ),
    );
  }

  Widget _buildCTASection() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(20),
        border: _isDarkMode
            ? Border.all(color: const Color(0xFF3498DB).withValues(alpha: 0.3))
            : null,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF3498DB).withValues(alpha: 0.1),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(
            Icons.chat_bubble_outline_rounded,
            color: const Color(0xFF3498DB),
            size: 48,
          ),
          const SizedBox(height: 16),
          Text(
            _isSwahili
                ? 'Una mradi akilini?'
                : 'Have a project in mind?',
            style: TextStyle(
              color: _textPrimaryColor,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            _isSwahili
                ? 'Wasiliana nasi leo kwa ushauri wa bure na makadirio ya mradi wako.'
                : 'Contact us today for a free consultation and estimate for your project.',
            style: TextStyle(
              color: _textSecondaryColor,
              fontSize: 14,
              height: 1.5,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(_isSwahili ? 'Inapiga simu...' : 'Calling...'),
                      ),
                    );
                  },
                  icon: const Icon(Icons.phone_rounded, size: 18),
                  label: Text(_isSwahili ? 'Piga Simu' : 'Call Us'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF1ABC9C),
                    side: const BorderSide(color: Color(0xFF1ABC9C)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(_isSwahili ? 'Inafungua WhatsApp...' : 'Opening WhatsApp...'),
                      ),
                    );
                  },
                  icon: const Icon(Icons.message_rounded, size: 18),
                  label: const Text('WhatsApp'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ServiceItem {
  final String id;
  final String title;
  final String shortDescription;
  final String fullDescription;
  final String image;
  final IconData icon;
  final Color color;
  final List<String> features;

  _ServiceItem({
    required this.id,
    required this.title,
    required this.shortDescription,
    required this.fullDescription,
    required this.image,
    required this.icon,
    required this.color,
    required this.features,
  });
}
