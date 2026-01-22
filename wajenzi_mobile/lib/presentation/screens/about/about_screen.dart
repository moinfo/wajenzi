import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

class AboutScreen extends ConsumerStatefulWidget {
  const AboutScreen({super.key});

  @override
  ConsumerState<AboutScreen> createState() => _AboutScreenState();
}

class _AboutScreenState extends ConsumerState<AboutScreen> {
  // Use global settings from provider
  bool get _isDarkMode => ref.watch(isDarkModeProvider);
  bool get _isSwahili => ref.watch(isSwahiliProvider);

  // Mission/Vision carousel
  final PageController _missionVisionController = PageController();
  int _currentMissionVisionPage = 0;
  Timer? _missionVisionTimer;

  // Leadership carousel
  final PageController _leadershipController = PageController();
  int _currentLeadershipPage = 0;
  Timer? _leadershipTimer;

  @override
  void initState() {
    super.initState();
    _startAutoScroll();
  }

  @override
  void dispose() {
    _missionVisionTimer?.cancel();
    _leadershipTimer?.cancel();
    _missionVisionController.dispose();
    _leadershipController.dispose();
    super.dispose();
  }

  void _startAutoScroll() {
    // Mission/Vision auto-scroll every 5 seconds
    _missionVisionTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      if (_missionVisionController.hasClients) {
        final nextPage = (_currentMissionVisionPage + 1) % 2;
        _missionVisionController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 500),
          curve: Curves.easeInOut,
        );
      }
    });

    // Leadership auto-scroll every 4 seconds (offset timing)
    _leadershipTimer = Timer.periodic(const Duration(seconds: 4), (timer) {
      if (_leadershipController.hasClients) {
        final nextPage = (_currentLeadershipPage + 1) % 3; // 3 leaders
        _leadershipController.animateToPage(
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
          // Hero Section with Image
          SliverToBoxAdapter(
            child: _buildHeroSection(),
          ),

          // Our Story Section
          SliverToBoxAdapter(
            child: _buildStorySection(),
          ),

          // Stats Section
          SliverToBoxAdapter(
            child: _buildStatsSection(),
          ),

          // Mission & Vision Section
          SliverToBoxAdapter(
            child: _buildMissionVisionSection(),
          ),

          // Core Values Section
          SliverToBoxAdapter(
            child: _buildCoreValuesSection(),
          ),

          // Leadership Team Section
          SliverToBoxAdapter(
            child: _buildLeadershipSection(),
          ),

          // Contact Us Section
          SliverToBoxAdapter(
            child: _buildContactSection(),
          ),

          // Footer
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 30),
              child: Column(
                children: [
                  const Text(
                    'WAJENZI PROFESSIONAL CO. LTD',
                    style: TextStyle(
                      color: Color(0xFF1ABC9C),
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 2,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _isSwahili
                        ? 'Mabingwa wa Uthabiti na Ubora'
                        : 'Masters of Consistency and Quality',
                    style: TextStyle(
                      color: _textSecondaryColor,
                      fontSize: 12,
                      fontStyle: FontStyle.italic,
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
        selectedIndex: 3, // About is index 3
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
          height: 280,
          width: double.infinity,
          child: Image.asset(
            'assets/images/about_us.jpg',
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
                  Icons.business,
                  size: 80,
                  color: Colors.white24,
                ),
              ),
            ),
          ),
        ),
        // Gradient Overlay
        Container(
          height: 280,
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
          bottom: 30,
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
                  _isSwahili ? 'Ilianzishwa 2012' : 'Founded in 2012',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                _isSwahili ? 'Historia Yetu' : 'Our Story',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _isSwahili
                    ? 'Kujenga Ndoto, Kuunda Uhalisia'
                    : 'Building Dreams, Creating Reality',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.9),
                  fontSize: 16,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStorySection() {
    return Container(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Award Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  const Color(0xFFD4AF37).withValues(alpha: 0.2),
                  const Color(0xFFD4AF37).withValues(alpha: 0.1),
                ],
              ),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFFD4AF37).withValues(alpha: 0.5),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(
                  Icons.emoji_events,
                  color: Color(0xFFD4AF37),
                  size: 20,
                ),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(
                    _isSwahili
                        ? 'Mkandarasi Bora wa Nyumba 2024 - CCIT'
                        : 'Outstanding Residential Contractor 2024 - CCIT',
                    style: const TextStyle(
                      color: Color(0xFFD4AF37),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          // Story paragraphs
          _buildParagraph(
            _isSwahili
                ? 'Wajenzi Professional Co. Ltd inajulikana kama mojawapo ya makampuni yanayoongoza ya ujenzi Afrika Mashariki. Ilianzishwa mwaka 2012 na Mhandisi Eliya N Kishaluli na kusajiliwa rasmi kama kampuni yenye kikomo mwaka 2020, tumekua kuwa kampuni ya ujenzi yenye tuzo.'
                : 'Wajenzi Professional Co. Ltd is recognized as one of the leading construction companies in East Africa. Founded in 2012 by Engineer Eliya N Kishaluli and officially registered as a company limited in 2020, we have steadily grown to become an award-winning construction firm.',
          ),
          const SizedBox(height: 16),
          _buildParagraph(
            _isSwahili
                ? 'Tukiwa na miradi zaidi ya 120 iliyokamilika kuanzia nyumba za makazi hadi majengo ya kibiashara, dhamira yetu ya ubora na uthabiti imetupata kutambuliwa kote katika eneo hili. Mwaka 2024, Wajenzi Professional ilituzwa tuzo ya Mkandarasi Bora wa Nyumba wa Mwaka na Chama cha Ujenzi na Miundombinu cha Tanzania (CCIT).'
                : 'With over 120 completed projects ranging from residential homes to commercial complexes, our commitment to quality and consistency has earned us recognition throughout the region. In 2024, Wajenzi Professional was honored with the Outstanding Residential Contractor of the Year award by the Chamber of Construction and Infrastructure of Tanzania (CCIT).',
          ),
          const SizedBox(height: 16),
          _buildParagraph(
            _isSwahili
                ? 'Jina la kampuni yetu "Wajenzi," ambalo linamaanisha "Builders" kwa Kiingereza, linaonyesha mizizi yetu ya ndani katika utamaduni wa hapa na dhamira yetu ya kujenga si tu majengo, bali pia mahusiano na jamii.'
                : 'Our company name "Wajenzi," which means "Builders" in Swahili, reflects our deep roots in the local culture and our commitment to building not just structures, but also relationships and communities.',
          ),
        ],
      ),
    );
  }

  Widget _buildParagraph(String text) {
    return Text(
      text,
      style: TextStyle(
        color: _textSecondaryColor,
        fontSize: 15,
        height: 1.6,
      ),
    );
  }

  Widget _buildStatsSection() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      padding: const EdgeInsets.all(24),
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
          _buildStatItem('120', '+', _isSwahili ? 'Miradi' : 'Projects'),
          _buildStatDivider(),
          _buildStatItem('50', '+', _isSwahili ? 'Timu' : 'Team Members'),
          _buildStatDivider(),
          _buildStatItem('12', '', _isSwahili ? 'Miaka' : 'Years Experience'),
        ],
      ),
    );
  }

  Widget _buildStatItem(String value, String suffix, String label) {
    return Column(
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 36,
                fontWeight: FontWeight.bold,
              ),
            ),
            if (suffix.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  suffix,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.9),
            fontSize: 12,
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  Widget _buildStatDivider() {
    return Container(
      width: 1,
      height: 50,
      color: Colors.white.withValues(alpha: 0.3),
    );
  }

  Widget _buildMissionVisionSection() {
    final missionVisionData = [
      {
        'icon': Icons.flag_rounded,
        'title': _isSwahili ? 'Dhamira Yetu' : 'Our Mission',
        'content': _isSwahili
            ? 'Kutoa suluhisho la ujenzi na usanifu bora, la bei nafuu, na endelevu ambalo linazidi matarajio ya wateja huku tukidumisha viwango vya juu vya uaminifu, taaluma, na uwajibikaji wa mazingira.'
            : 'To deliver affordable, sustainable, and high-quality construction and architectural solutions that exceed client expectations while maintaining the highest standards of integrity, professionalism, and environmental responsibility.',
        'subContent': _isSwahili
            ? 'Tumejitolea kutumia mbinu na vifaa vya ubunifu vinavyopunguza athari za mazingira huku tukiboresha utendaji, uimara, na mvuto wa kuvutia katika kila mradi tunaouchukua.'
            : 'We are committed to using innovative techniques and materials that reduce environmental impact while enhancing functionality, durability, and aesthetic appeal in every project we undertake.',
        'gradientColors': const [Color(0xFF3498DB), Color(0xFF2980B9)],
      },
      {
        'icon': Icons.visibility_rounded,
        'title': _isSwahili ? 'Maono Yetu' : 'Our Vision',
        'content': _isSwahili
            ? 'Kuwa mtoa huduma wa kimataifa anayeongoza wa huduma za ujenzi na usanifu za ubunifu, zinazotambuliwa kwa ubora, uendelevu, na athari za mabadiliko katika mazingira yaliyojengwa.'
            : 'To become a leading global provider of innovative construction and design services, recognized for excellence, sustainability, and transformative impact on the built environment.',
        'subContent': _isSwahili
            ? 'Tunataka kuweka viwango vipya katika sekta ya ujenzi kupitia ubunifu unaoendelea, maendeleo ya kitaaluma, na kujitolea bila kusita kwa ubora, kuunda majengo yanayosimama jaribio la wakati na kuchangia vyema kwa jamii zinazohudumia.'
            : 'We aspire to set new standards in the construction industry through continuous innovation, professional development, and a steadfast commitment to quality, creating buildings that stand the test of time and contribute positively to the communities they serve.',
        'gradientColors': const [Color(0xFF9B59B6), Color(0xFF8E44AD)],
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
                    _isSwahili ? 'Kusudi Letu' : 'Our Purpose',
                    style: const TextStyle(
                      color: Color(0xFF1ABC9C),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _isSwahili ? 'Dhamira na Maono' : 'Mission & Vision',
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
          const SizedBox(height: 8),
          Text(
            _isSwahili
                ? 'Kanuni zinazoongoza kampuni yetu mbele'
                : 'Guiding principles that drive our company forward',
            style: TextStyle(
              color: _textSecondaryColor,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 24),

          // Auto-swipe PageView Carousel
          SizedBox(
            height: 320,
            child: PageView.builder(
              controller: _missionVisionController,
              onPageChanged: (index) {
                setState(() => _currentMissionVisionPage = index);
              },
              itemCount: missionVisionData.length,
              itemBuilder: (context, index) {
                final data = missionVisionData[index];
                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  child: SizedBox.expand(
                    child: _buildMissionVisionCard(
                      icon: data['icon'] as IconData,
                      title: data['title'] as String,
                      content: data['content'] as String,
                      subContent: data['subContent'] as String,
                      gradientColors: data['gradientColors'] as List<Color>,
                    ),
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 16),

          // Page Indicators
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(
              missionVisionData.length,
              (index) => AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: _currentMissionVisionPage == index ? 24 : 8,
                height: 8,
                decoration: BoxDecoration(
                  color: _currentMissionVisionPage == index
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

  Widget _buildMissionVisionCard({
    required IconData icon,
    required String title,
    required String content,
    required String subContent,
    required List<Color> gradientColors,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(16),
        border: _isDarkMode
            ? Border.all(color: gradientColors[0].withValues(alpha: 0.3))
            : null,
        boxShadow: [
          BoxShadow(
            color: gradientColors[0].withValues(alpha: 0.1),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Fixed header
          Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: gradientColors),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: Colors.white, size: 22),
              ),
              const SizedBox(width: 12),
              Text(
                title,
                style: TextStyle(
                  color: _textPrimaryColor,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Scrollable content
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    content,
                    style: TextStyle(
                      color: _textSecondaryColor,
                      fontSize: 13,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    subContent,
                    style: TextStyle(
                      color: _textSecondaryColor.withValues(alpha: 0.8),
                      fontSize: 12,
                      height: 1.5,
                      fontStyle: FontStyle.italic,
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

  Widget _buildCoreValuesSection() {
    final values = [
      _CoreValue(
        title: _isSwahili ? 'MAOMBI' : 'PRAYERS',
        icon: Icons.self_improvement_rounded,
        color: const Color(0xFF9B59B6),
        description: _isSwahili
            ? 'Tunaamini katika nguvu ya maombi na imani kuongoza matendo yetu, kuunganisha timu yetu, na kuhamasisha ubora katika juhudi zetu zote.'
            : 'We believe in the power of prayer and faith to guide our actions, unite our team, and inspire excellence in all our endeavors.',
      ),
      _CoreValue(
        title: _isSwahili ? 'UBUNIFU' : 'INNOVATION',
        icon: Icons.lightbulb_rounded,
        color: const Color(0xFFF39C12),
        description: _isSwahili
            ? 'Tunaendeleza na kuingiza teknolojia na mbinu mpya kutoa suluhisho za kisasa kwa mahitaji ya ujenzi na usanifu ya wateja wetu.'
            : 'We develop and incorporate new technology and approaches to provide cutting-edge solutions for our clients\' construction and design needs.',
      ),
      _CoreValue(
        title: _isSwahili ? 'UBORA' : 'QUALITY',
        icon: Icons.verified_rounded,
        color: const Color(0xFF1ABC9C),
        description: _isSwahili
            ? 'Tunaamini ni jambo bora kufanya kitu kimoja vizuri sana. Kujitolea kwetu bila kusita kwa ubora kunaonyeshwa katika kila undani wa kazi yetu.'
            : 'We believe it\'s the best thing to do one thing really really well. Our uncompromising commitment to excellence is reflected in every detail of our work.',
      ),
      _CoreValue(
        title: _isSwahili ? 'KUSOMA' : 'READING',
        icon: Icons.menu_book_rounded,
        color: const Color(0xFF3498DB),
        description: _isSwahili
            ? 'Tunakumbatia kusoma kama chombo cha ukuaji, maarifa, na uboreshaji wa kuendelea. Timu yetu inahimizwa kujifunza daima.'
            : 'We embrace reading as a tool for growth, knowledge, and continuous improvement. Our team is encouraged to constantly learn.',
      ),
      _CoreValue(
        title: _isSwahili ? 'USHIRIKIANO' : 'TEAMWORK',
        icon: Icons.groups_rounded,
        color: const Color(0xFFE74C3C),
        description: _isSwahili
            ? 'Tunaamini suluhisho bora linakuja kutokana na kufanya kazi pamoja. Tunakuza mazingira ya ushirikiano ambapo ujuzi na mtazamo mbalimbali unathaminiwa.'
            : 'We believe the best solution comes from working together. We foster a collaborative environment where diverse skills and perspectives are valued.',
      ),
      _CoreValue(
        title: _isSwahili ? 'UAMINIFU' : 'INTEGRITY',
        icon: Icons.shield_rounded,
        color: const Color(0xFF2C3E50),
        description: _isSwahili
            ? 'Tunafanya jambo sahihi daima. Mazoea yetu ya biashara yanajengwa juu ya uaminifu, uwazi, na mwenendo wa kimaadili.'
            : 'We do the right thing always. Our business practices are founded on honesty, transparency, and ethical conduct.',
      ),
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
                  color: const Color(0xFFD4AF37),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _isSwahili ? 'Kanuni Zetu' : 'Our Guiding Principles',
                    style: const TextStyle(
                      color: Color(0xFFD4AF37),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _isSwahili ? 'Thamani za Msingi' : 'Core Values',
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
          const SizedBox(height: 8),
          Text(
            _isSwahili
                ? 'Kanuni hizi zinaongoza maamuzi yetu, kuunda utamaduni wetu, na kufafanua mbinu yetu kwa kila mradi tunaoufanya.'
                : 'These principles guide our decisions, shape our culture, and define our approach to every project we undertake.',
            style: TextStyle(
              color: _textSecondaryColor,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 24),

          // Horizontal scrolling values
          SizedBox(
            height: 180,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: values.length,
              itemBuilder: (context, index) {
                return Padding(
                  padding: EdgeInsets.only(
                    left: index == 0 ? 0 : 12,
                    right: index == values.length - 1 ? 0 : 0,
                  ),
                  child: _buildValueCard(values[index]),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildValueCard(_CoreValue value) {
    return Container(
      width: 200,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(16),
        border: _isDarkMode
            ? Border.all(color: value.color.withValues(alpha: 0.3))
            : null,
        boxShadow: [
          BoxShadow(
            color: value.color.withValues(alpha: 0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Icon and Title Row
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: value.color.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  value.icon,
                  color: value.color,
                  size: 22,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  value.title,
                  style: TextStyle(
                    color: value.color,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Description
          Expanded(
            child: Text(
              value.description,
              style: TextStyle(
                color: _textSecondaryColor,
                fontSize: 11,
                height: 1.4,
              ),
              maxLines: 5,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLeadershipSection() {
    final leaders = [
      _TeamMember(
        name: 'Eng. ELIYA N. KISHALULI',
        role: _isSwahili ? 'Mwanzilishi na Mkurugenzi Mtendaji' : 'Founder & CEO',
        image: 'assets/images/ELIYA_KISHALULI.jpeg',
        description: _isSwahili
            ? 'Akiwa na uzoefu wa zaidi ya miaka 15 katika ujenzi na uhandisi, Eng. Eliya alianzisha Wajenzi Professional akiwa na maono ya kubadilisha sekta ya ujenzi Afrika Mashariki.'
            : 'With over 15 years of experience in construction and engineering, Eng. Eliya founded Wajenzi Professional with a vision to transform the construction industry in East Africa.',
      ),
      _TeamMember(
        name: 'SARAH JOHN',
        role: _isSwahili ? 'Mkurugenzi Msimamizi' : 'Managing Director',
        image: 'assets/images/SARAH_JOHN.png',
        description: _isSwahili
            ? 'Mkurugenzi Msimamizi mwenye uzoefu katika ujenzi, mzuri katika usimamizi wa miradi, bajeti, na uongozi wa timu, na rekodi iliyothibitishwa ya kutoa miradi bora kwa wakati na ndani ya bajeti.'
            : 'Experienced Managing Director in construction, skilled in project management, budgeting, and team leadership, with a proven record of delivering quality projects on time and within budget.',
      ),
      _TeamMember(
        name: 'MOHAMEDI JOSEPH',
        role: _isSwahili ? 'Meneja wa Maendeleo ya Biashara' : 'Business Development Manager',
        image: 'assets/images/MOHAMED_JOSEPH.png',
        description: _isSwahili
            ? 'Meneja wa Maendeleo ya Biashara katika sekta ya ujenzi, mzuri katika mahusiano na wateja, upataji wa miradi, na ukuaji wa kimkakati, na rekodi iliyothibitishwa ya kuongeza mapato.'
            : 'Business Development Manager in the construction industry, skilled in client relations, project acquisition, and strategic growth, with a proven record of driving revenue and delivering successful projects.',
      ),
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
                  color: const Color(0xFF3498DB),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _isSwahili ? 'Viongozi Wabobezi' : 'Expert Leaders',
                    style: const TextStyle(
                      color: Color(0xFF3498DB),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _isSwahili ? 'Timu ya Uongozi' : 'Our Leadership Team',
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
          const SizedBox(height: 8),
          Text(
            _isSwahili
                ? 'Kutana na wataalamu wanaoongoza kampuni yetu kwa ujuzi na maono.'
                : 'Meet the professionals who guide our company with expertise and vision.',
            style: TextStyle(
              color: _textSecondaryColor,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 24),

          // Auto-swipe Leadership Carousel
          SizedBox(
            height: 220,
            child: PageView.builder(
              controller: _leadershipController,
              onPageChanged: (index) {
                setState(() => _currentLeadershipPage = index);
              },
              itemCount: leaders.length,
              itemBuilder: (context, index) {
                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 4),
                  child: _buildLeaderCard(leaders[index]),
                );
              },
            ),
          ),
          const SizedBox(height: 16),

          // Page Indicators
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(
              leaders.length,
              (index) => AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 4),
                width: _currentLeadershipPage == index ? 24 : 8,
                height: 8,
                decoration: BoxDecoration(
                  color: _currentLeadershipPage == index
                      ? const Color(0xFF3498DB)
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

  Widget _buildLeaderCard(_TeamMember leader) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(16),
        border: _isDarkMode
            ? Border.all(color: const Color(0xFF3498DB).withValues(alpha: 0.3))
            : null,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF3498DB).withValues(alpha: 0.1),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Profile Image
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: const Color(0xFF1ABC9C).withValues(alpha: 0.5),
                width: 2,
              ),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.asset(
                leader.image,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(
                  color: const Color(0xFF1ABC9C).withValues(alpha: 0.2),
                  child: const Icon(
                    Icons.person,
                    size: 40,
                    color: Color(0xFF1ABC9C),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(width: 16),
          // Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  leader.name,
                  style: TextStyle(
                    color: _textPrimaryColor,
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1ABC9C).withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    leader.role,
                    style: const TextStyle(
                      color: Color(0xFF1ABC9C),
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  leader.description,
                  style: TextStyle(
                    color: _textSecondaryColor,
                    fontSize: 12,
                    height: 1.5,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildContactSection() {
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.contact_mail_rounded,
                  color: Colors.white,
                  size: 26,
                ),
              ),
              const SizedBox(width: 16),
              Text(
                _isSwahili ? 'Wasiliana Nasi' : 'Contact Us',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Address
          _buildContactItem(
            icon: Icons.location_on_rounded,
            title: _isSwahili ? 'Anwani' : 'Address',
            content: 'Ground-Floor (07), PSSSF Commercial Complex, Dar es Salaam',
          ),
          const SizedBox(height: 16),

          // Phone
          _buildContactItem(
            icon: Icons.phone_rounded,
            title: _isSwahili ? 'Simu' : 'Phone',
            content: '+255 793 444 400',
          ),
          const SizedBox(height: 16),

          // Email
          _buildContactItem(
            icon: Icons.email_rounded,
            title: _isSwahili ? 'Barua Pepe' : 'Email',
            content: 'info@wajenziprofessional.co.tz',
          ),
          const SizedBox(height: 16),

          // Hours
          _buildContactItem(
            icon: Icons.access_time_rounded,
            title: _isSwahili ? 'Saa za Kazi' : 'Working Hours',
            content: _isSwahili
                ? 'Jumatatu - Ijumaa: 8:00 AM - 6:00 PM'
                : 'Mon - Fri: 8:00 AM - 6:00 PM',
          ),
        ],
      ),
    );
  }

  Widget _buildContactItem({
    required IconData icon,
    required String title,
    required String content,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(
            icon,
            color: Colors.white.withValues(alpha: 0.9),
            size: 18,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.7),
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                content,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _CoreValue {
  final String title;
  final IconData icon;
  final Color color;
  final String description;

  _CoreValue({
    required this.title,
    required this.icon,
    required this.color,
    required this.description,
  });
}

class _TeamMember {
  final String name;
  final String role;
  final String image;
  final String description;

  _TeamMember({
    required this.name,
    required this.role,
    required this.image,
    required this.description,
  });
}
