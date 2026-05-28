import 'dart:async';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/app_config.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../../data/models/landing_award_model.dart';
import '../../providers/awards_provider.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

class AwardsScreen extends ConsumerStatefulWidget {
  const AwardsScreen({super.key});

  @override
  ConsumerState<AwardsScreen> createState() => _AwardsScreenState();
}

class _AwardsScreenState extends ConsumerState<AwardsScreen> {
  // Use global settings from provider
  AppLanguage get _language => ref.watch(currentLanguageProvider);
  bool get _isDarkMode => ref.watch(isDarkModeProvider);
  bool get _isSwahili => ref.watch(isSwahiliProvider);
  bool get _isFrench => _language == AppLanguage.french;
  bool get _isArabic => _language == AppLanguage.arabic;

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
        final count = _awardsCount;
        if (count == 0) return;
        final nextPage = (_currentFeaturedPage + 1) % count;
        _featuredController.animateToPage(
          nextPage,
          duration: const Duration(milliseconds: 500),
          curve: Curves.easeInOut,
        );
      }
    });
  }

  // Dark mode colors
  Color get _bgColor =>
      _isDarkMode ? const Color(0xFF1A1A2E) : const Color(0xFFF0F4F8);
  Color get _cardBgColor =>
      _isDarkMode ? const Color(0xFF16213E) : Colors.white;
  Color get _textPrimaryColor =>
      _isDarkMode ? Colors.white : const Color(0xFF2C3E50);
  Color get _textSecondaryColor =>
      _isDarkMode ? Colors.white70 : const Color(0xFF7F8C8D);

  String _tr({required String en, String? sw, String? fr, String? ar}) {
    if (_isSwahili) return sw ?? en;
    if (_isFrench) return fr ?? en;
    if (_isArabic) return ar ?? en;
    return en;
  }

  Future<void> _launchWhatsApp([String? topic]) async {
    final message = _isSwahili
        ? 'Habari! Nahitaji taarifa zaidi kuhusu ${topic ?? "tuzo zenu"}.'
        : 'Hello! I would like to learn more about ${topic ?? "your awards"}.';
    final opened = await ExternalLauncherService.openWhatsApp(message);
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _tr(
              en: 'Could not open WhatsApp',
              sw: 'Imeshindwa kufungua WhatsApp',
              fr: 'Impossible d\'ouvrir WhatsApp',
              ar: 'تعذر فتح واتساب',
            ),
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  // Live awards from the portal CMS, falling back to bundled content while
  // loading or if nothing has been published. See [awardsProvider].
  List<_Award> get _awards {
    final models = ref.watch(awardsProvider).valueOrNull;
    if (models == null || models.isEmpty) return _fallbackAwards;
    return models
        .asMap()
        .entries
        .map((e) => _awardFromModel(e.value, e.key))
        .toList();
  }

  // Count usable outside build (e.g. the auto-scroll timer) — uses read().
  int get _awardsCount {
    final models = ref.read(awardsProvider).valueOrNull;
    return (models == null || models.isEmpty)
        ? _fallbackAwards.length
        : models.length;
  }

  static const List<Color> _awardAccents = [
    Color(0xFF193340), // brand dark blue
    Color(0xFF3BA154), // brand green
    Color(0xFF27505F), // purple
    Color(0xFF2E8043), // green dark
  ];
  static const List<IconData> _awardIcons = [
    Icons.emoji_events_rounded,
    Icons.workspace_premium_rounded,
    Icons.eco_rounded,
    Icons.lightbulb_rounded,
  ];

  _Award _awardFromModel(LandingAwardModel m, int index) {
    return _Award(
      year: m.year ?? '',
      title: m.title,
      subtitle: m.subtitle ?? '',
      organization: m.organization ?? '',
      description: m.description ?? '',
      image: AppConfig.resolvePortalMediaUrl(m.image) ?? '',
      color: _awardAccents[index % _awardAccents.length],
      icon: _awardIcons[index % _awardIcons.length],
    );
  }

  /// Award image — network (CMS) when a URL, asset for bundled fallback,
  /// else a branded gradient placeholder with the award icon.
  Widget _awardImage(_Award award, double iconSize) {
    Widget fallback() => Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [award.color.withValues(alpha: 0.7), award.color],
        ),
      ),
      child: Icon(award.icon, size: iconSize, color: Colors.white38),
    );
    final src = award.image;
    if (src.startsWith('http')) {
      return CachedNetworkImage(
        imageUrl: src,
        fit: BoxFit.cover,
        placeholder: (_, _) =>
            Container(color: award.color.withValues(alpha: 0.15)),
        errorWidget: (_, _, _) => fallback(),
      );
    }
    if (src.isEmpty) return fallback();
    return Image.asset(
      src,
      fit: BoxFit.cover,
      errorBuilder: (_, _, _) => fallback(),
    );
  }

  List<_Award> get _fallbackAwards => [
    _Award(
      year: '2024',
      title: _isSwahili
          ? 'Mkandarasi Bora wa Nyumba'
          : 'Outstanding Residential Contractor',
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
      color: const Color(0xFF193340),
      icon: Icons.emoji_events_rounded,
    ),
    _Award(
      year: '2023',
      title: _tr(
        en: 'Excellence in Construction',
        sw: 'Ubora katika Ujenzi',
        fr: 'Excellence en construction',
        ar: 'التميز في البناء',
      ),
      subtitle: _tr(
        en: 'Excellence in Construction Award',
        sw: 'Tuzo ya Ubora katika Ujenzi',
        fr: 'Prix d’excellence en construction',
        ar: 'جائزة التميز في البناء',
      ),
      organization: _tr(
        en: 'East African Builders Association',
        sw: 'Chama cha Wajenzi wa Afrika Mashariki',
        fr: 'Association des constructeurs d’Afrique de l’Est',
        ar: 'جمعية البنّائين في شرق أفريقيا',
      ),
      description: _tr(
        en: 'Awarded for demonstrating outstanding craftsmanship, technical innovation, and project management across multiple construction projects.',
        sw: 'Kutuzwa kwa kuonyesha ufundi bora, ubunifu wa kiufundi, na usimamizi wa mradi katika miradi mingi ya ujenzi.',
        fr: 'Décerné pour avoir démontré un savoir-faire exceptionnel, une innovation technique et une excellente gestion de projet sur plusieurs projets de construction.',
        ar: 'مُنحت تقديرًا لإظهار حرفة متميزة وابتكار تقني وإدارة مشاريع عالية المستوى عبر عدة مشاريع إنشائية.',
      ),
      image: 'assets/images/awards/BQ6A3837.jpeg',
      color: const Color(0xFF3BA154),
      icon: Icons.workspace_premium_rounded,
    ),
    _Award(
      year: '2022',
      title: _tr(
        en: 'Sustainable Building',
        sw: 'Ujenzi Endelevu',
        fr: 'Construction durable',
        ar: 'البناء المستدام',
      ),
      subtitle: _tr(
        en: 'Sustainable Building Leadership Award',
        sw: 'Tuzo ya Uongozi wa Ujenzi Endelevu',
        fr: 'Prix de leadership en construction durable',
        ar: 'جائزة الريادة في البناء المستدام',
      ),
      organization: _tr(
        en: 'Green Building Council Tanzania',
        sw: 'Baraza la Majengo ya Kijani Tanzania',
        fr: 'Conseil tanzanien du bâtiment durable',
        ar: 'مجلس البناء الأخضر في تنزانيا',
      ),
      description: _tr(
        en: 'Recognized for implementing sustainable practices, energy-efficient designs, and eco-friendly materials in construction projects.',
        sw: 'Kutambuliwa kwa kutekeleza mazoea endelevu, miundo inayotumia nishati kwa ufanisi, na vifaa rafiki kwa mazingira katika miradi ya ujenzi.',
        fr: 'Reconnu pour la mise en œuvre de pratiques durables, de conceptions économes en énergie et de matériaux écologiques dans les projets de construction.',
        ar: 'تم تكريمنا لتطبيق ممارسات مستدامة وتصاميم موفرة للطاقة ومواد صديقة للبيئة في مشاريع البناء.',
      ),
      image: 'assets/images/awards/BQ6A3840.jpeg',
      color: const Color(0xFF27AE60),
      icon: Icons.eco_rounded,
    ),
    _Award(
      year: '2021',
      title: _tr(
        en: 'Innovation Award',
        sw: 'Tuzo ya Ubunifu',
        fr: 'Prix de l’innovation',
        ar: 'جائزة الابتكار',
      ),
      subtitle: _tr(
        en: 'Innovation in Construction Award',
        sw: 'Tuzo ya Ubunifu katika Ujenzi',
        fr: 'Prix de l’innovation en construction',
        ar: 'جائزة الابتكار في البناء',
      ),
      organization: _tr(
        en: 'Tanzania Construction Innovation Council',
        sw: 'Baraza la Ubunifu wa Ujenzi Tanzania',
        fr: 'Conseil tanzanien de l’innovation dans la construction',
        ar: 'مجلس تنزانيا للابتكار في البناء',
      ),
      description: _tr(
        en: 'Recognized for implementing innovative construction techniques and sustainable building practices in residential projects.',
        sw: 'Kutambuliwa kwa kutekeleza mbinu za ujenzi za ubunifu na mazoea endelevu ya ujenzi katika miradi ya makazi.',
        fr: 'Reconnu pour l’application de techniques de construction innovantes et de pratiques durables dans les projets résidentiels.',
        ar: 'تم تكريمنا لتطبيق تقنيات بناء مبتكرة وممارسات بناء مستدامة في المشاريع السكنية.',
      ),
      image: 'assets/images/awards/BQ6A3837_2.jpeg',
      color: const Color(0xFF27505F),
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
        language: _language,
        onDarkModeToggle: () =>
            ref.read(settingsProvider.notifier).toggleDarkMode(),
        onLanguageChanged: (value) =>
            ref.read(settingsProvider.notifier).setLanguage(value),
      ),
      body: CustomScrollView(
        slivers: [
          // Hero Section
          SliverToBoxAdapter(child: _buildHeroSection()),

          // Stats Section
          SliverToBoxAdapter(child: _buildStatsSection()),

          // Featured Awards Carousel
          SliverToBoxAdapter(child: _buildFeaturedAwardsSection()),

          // All Awards Section Header
          SliverToBoxAdapter(child: _buildAwardsSectionHeader()),

          // Awards Timeline
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) => _buildAwardTimelineItem(
                  _awards[index],
                  index == _awards.length - 1,
                ),
                childCount: _awards.length,
              ),
            ),
          ),

          // Recognition Section
          SliverToBoxAdapter(child: _buildRecognitionSection()),

          // Footer spacing
          const SliverToBoxAdapter(child: SizedBox(height: 120)),
        ],
      ),
      bottomNavigationBar: CurvedBottomNav(
        selectedIndex: 4, // Awards is index 4
        isDarkMode: _isDarkMode,
        isSwahili: _isSwahili,
        language: _language,
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
            'assets/images/awards/Wins_Contractor.jpeg',
            fit: BoxFit.cover,
            errorBuilder: (_, _, _) => Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFFD4AF37), Color(0xFFFECC04)],
                ),
              ),
              child: const Center(
                child: Icon(
                  Icons.emoji_events_rounded,
                  size: 80,
                  color: Colors.white24,
                ),
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
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFF193340),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(
                      Icons.emoji_events_rounded,
                      color: Colors.white,
                      size: 14,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      _tr(
                        en: 'Recognition',
                        sw: 'Kutambuliwa',
                        fr: 'Reconnaissance',
                        ar: 'التقدير',
                      ),
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
                _tr(
                  en: 'Our Awards & Recognition',
                  sw: 'Tuzo na Kutambuliwa Kwetu',
                  fr: 'Nos Prix et Distinctions',
                  ar: 'جوائزنا وتقديرنا',
                ),
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
                    : _tr(
                        en: 'Recognition of our commitment to quality, innovation, and excellence in construction',
                        fr: 'Reconnaissance de notre engagement envers la qualite, l\'innovation et l\'excellence dans la construction',
                        ar: 'تقدير لالتزامنا بالجودة والابتكار والتميز في البناء',
                      ),
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
          colors: [Color(0xFF193340), Color(0xFF122833)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF193340).withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildStatItem(
            '4',
            _tr(en: 'Awards', sw: 'Tuzo', fr: 'Prix', ar: 'الجوائز'),
          ),
          _buildStatDivider(),
          _buildStatItem(
            '4',
            _tr(en: 'Years', sw: 'Miaka', fr: 'Ans', ar: 'سنوات'),
          ),
          _buildStatDivider(),
          _buildStatItem(
            '3',
            _tr(
              en: 'Organizations',
              sw: 'Mashirika',
              fr: 'Organisations',
              ar: 'المؤسسات',
            ),
          ),
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
                  color: const Color(0xFF193340),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _tr(
                      en: 'Our Achievements',
                      sw: 'Mafanikio Yetu',
                      fr: 'Nos réalisations',
                      ar: 'إنجازاتنا',
                    ),
                    style: const TextStyle(
                      color: Color(0xFF193340),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _tr(
                      en: 'Featured Awards',
                      sw: 'Tuzo Zilizoangaziwa',
                      fr: 'Prix mis en avant',
                      ar: 'الجوائز البارزة',
                    ),
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
                      ? const Color(0xFF193340)
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
          // Image with year badge - Clickable to view fullscreen
          GestureDetector(
            onTap: () => _showFullscreenImage(award.image, award.subtitle),
            child: Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(20),
                  ),
                  child: SizedBox(
                    height: 140,
                    width: double.infinity,
                    child: _awardImage(award, 60),
                  ),
                ),
                // Year badge
                Positioned(
                  top: 12,
                  left: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
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
                // View fullscreen indicator
                Positioned(
                  bottom: 8,
                  right: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.6),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.zoom_in_rounded,
                          color: Colors.white,
                          size: 14,
                        ),
                        SizedBox(width: 4),
                        Text(
                          'View',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
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

  void _showFullscreenImage(String imagePath, String title) {
    showDialog(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.95),
      builder: (context) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: EdgeInsets.zero,
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Image with pinch to zoom
            InteractiveViewer(
              minScale: 0.5,
              maxScale: 4.0,
              child: Center(
                child: imagePath.startsWith('http')
                    ? CachedNetworkImage(
                        imageUrl: imagePath,
                        fit: BoxFit.contain,
                        errorWidget: (_, _, _) => const Icon(
                          Icons.broken_image_rounded,
                          color: Colors.white54,
                          size: 80,
                        ),
                      )
                    : Image.asset(
                        imagePath,
                        fit: BoxFit.contain,
                        errorBuilder: (_, _, _) => const Icon(
                          Icons.broken_image_rounded,
                          color: Colors.white54,
                          size: 80,
                        ),
                      ),
              ),
            ),
            // Close button
            Positioned(
              top: MediaQuery.of(context).padding.top + 16,
              right: 16,
              child: GestureDetector(
                onTap: () => Navigator.pop(context),
                child: Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.close_rounded,
                    color: Colors.white,
                    size: 24,
                  ),
                ),
              ),
            ),
            // Title at bottom
            Positioned(
              bottom: MediaQuery.of(context).padding.bottom + 24,
              left: 20,
              right: 20,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: 0.6),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _isSwahili
                          ? 'Bana ili kukuza - Gusa kutoka'
                          : 'Pinch to zoom - Tap to close',
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.7),
                        fontSize: 12,
                      ),
                      textAlign: TextAlign.center,
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

  Widget _buildAwardsSectionHeader() {
    return Container(
      padding: const EdgeInsets.all(20),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 30,
            decoration: BoxDecoration(
              color: const Color(0xFF3BA154),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _tr(
                  en: 'Our Journey',
                  sw: 'Safari Yetu',
                  fr: 'Notre parcours',
                  ar: 'رحلتنا',
                ),
                style: const TextStyle(
                  color: Color(0xFF3BA154),
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 1,
                ),
              ),
              Text(
                _tr(
                  en: 'Awards Timeline',
                  sw: 'Ratiba ya Tuzo',
                  fr: 'Chronologie des prix',
                  ar: 'الجدول الزمني للجوائز',
                ),
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
                    colors: [award.color, award.color.withValues(alpha: 0.2)],
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
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
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
                  // Systematic approach button
                  Row(
                    children: [
                      Text(
                        _tr(
                          en: 'View Systematic Approach',
                          sw: 'Angalia Mfumo wa Utekelezaji',
                          fr: 'Voir l\'approche systematique',
                          ar: 'عرض المنهجية المنظمة',
                        ),
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
                        child: _awardImage(award, 80),
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
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 12,
                                  vertical: 6,
                                ),
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
                                  _tr(
                                    en: 'Awarded by',
                                    sw: 'Imetolewa na',
                                    fr: 'Décerné par',
                                    ar: 'ممنوحة من',
                                  ),
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
                      _tr(
                        en: 'About This Award',
                        sw: 'Kuhusu Tuzo Hii',
                        fr: 'À propos de ce prix',
                        ar: 'حول هذه الجائزة',
                      ),
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
                      _tr(
                        en: 'This award recognizes our commitment to the highest standards of quality and innovation in the construction industry. We continue to strive to exceed our clients\' expectations and deliver exceptional projects.',
                        sw: 'Tuzo hii inatambua kujitolea kwetu kwa viwango vya juu vya ubora na ubunifu katika sekta ya ujenzi. Tunaendelea kujitahidi kupita matarajio ya wateja wetu na kutoa miradi bora.',
                        fr: 'Ce prix reconnaît notre engagement envers les plus hauts standards de qualité et d’innovation dans le secteur de la construction. Nous continuons à nous efforcer de dépasser les attentes de nos clients et de livrer des projets exceptionnels.',
                        ar: 'تعترف هذه الجائزة بالتزامنا بأعلى معايير الجودة والابتكار في قطاع البناء. ونواصل السعي لتجاوز توقعات عملائنا وتقديم مشاريع استثنائية.',
                      ),
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
                        _launchWhatsApp(award.title);
                      },
                      icon: const Icon(Icons.share_rounded, size: 20),
                      label: Text(
                        _tr(
                          en: 'Share This Award',
                          sw: 'Shiriki Tuzo Hii',
                          fr: 'Partager ce prix',
                          ar: 'شارك هذه الجائزة',
                        ),
                      ),
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
              : [const Color(0xFF2C3E50), const Color(0xFF193340)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF193340).withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          const Icon(Icons.verified_rounded, color: Colors.white, size: 48),
          const SizedBox(height: 16),
          Text(
            _tr(
              en: 'Commitment to Excellence',
              sw: 'Kujitolea kwa Ubora',
              fr: 'Engagement envers l’excellence',
              ar: 'الالتزام بالتميّز',
            ),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 12),
          Text(
            _tr(
              en: 'These awards reflect our unwavering commitment to excellence, innovation, and customer satisfaction. We continue to strive to set new standards in the construction industry.',
              sw: 'Tuzo hizi zinaonyesha kujitolea kwetu bila kusita kwa ubora, ubunifu, na kuridhika kwa wateja. Tunaendelea kujitahidi kuweka viwango vipya katika sekta ya ujenzi.',
              fr: 'Ces prix reflètent notre engagement indéfectible envers l’excellence, l’innovation et la satisfaction client. Nous continuons à nous efforcer d’établir de nouvelles références dans le secteur de la construction.',
              ar: 'تعكس هذه الجوائز التزامنا الراسخ بالتميّز والابتكار ورضا العملاء. ونواصل السعي لوضع معايير جديدة في قطاع البناء.',
            ),
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.9),
              fontSize: 14,
              height: 1.5,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: _launchWhatsApp,
            icon: const Icon(Icons.handshake_rounded, size: 18),
            label: Text(
              _tr(
                en: 'Work With Us',
                sw: 'Fanya Kazi Nasi',
                fr: 'Travaillez avec nous',
                ar: 'اعمل معنا',
              ),
            ),
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
