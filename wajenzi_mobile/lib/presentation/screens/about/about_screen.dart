import 'dart:async';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/app_config.dart';
import '../../../data/models/landing_about_model.dart';
import '../../providers/about_provider.dart';
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
  AppLanguage get _language => ref.watch(currentLanguageProvider);
  bool get _isDarkMode => ref.watch(isDarkModeProvider);
  bool get _isSwahili => ref.watch(isSwahiliProvider);
  bool get _isFrench => _language == AppLanguage.french;
  bool get _isArabic => _language == AppLanguage.arabic;

  String _tr({required String en, String? sw, String? fr, String? ar}) {
    if (_isSwahili) return sw ?? en;
    if (_isFrench) return fr ?? en;
    if (_isArabic) return ar ?? en;
    return en;
  }

  // Live "About" content from the portal CMS. Null while loading / on error /
  // if nothing has been published, in which case the bundled fallbacks below
  // are used. See [aboutProvider].
  LandingAboutModel? get _about => ref.watch(aboutProvider).valueOrNull;

  // Count usable outside build (the auto-scroll timer) — uses read(), not watch().
  int get _leadersCount {
    final team = ref.read(aboutProvider).valueOrNull?.team ?? const [];
    return team.isNotEmpty ? team.length : _fallbackLeaders.length;
  }

  /// Returns the CMS [value] when it is a non-empty string, else [fallback].
  String _orFallback(String? value, String fallback) {
    final v = value?.trim();
    return (v == null || v.isEmpty) ? fallback : v;
  }

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
        final count = _leadersCount;
        if (count <= 1) return;
        final nextPage = (_currentLeadershipPage + 1) % count;
        _leadershipController.animateToPage(
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
          // Hero Section with Image
          SliverToBoxAdapter(child: _buildHeroSection()),

          // Our Story Section
          SliverToBoxAdapter(child: _buildStorySection()),

          // Stats Section
          SliverToBoxAdapter(child: _buildStatsSection()),

          // Mission & Vision Section
          SliverToBoxAdapter(child: _buildMissionVisionSection()),

          // Core Values Section
          SliverToBoxAdapter(child: _buildCoreValuesSection()),

          // Leadership Team Section
          SliverToBoxAdapter(child: _buildLeadershipSection()),

          // Contact Us Section
          SliverToBoxAdapter(child: _buildContactSection()),

          // Footer
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.symmetric(vertical: 30),
              child: Column(
                children: [
                  const Text(
                    'WAJENZI PROFESSIONAL CO. LTD',
                    style: TextStyle(
                      color: Color(0xFF193340),
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 2,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _tr(
                      en: 'Masters of Consistency and Quality',
                      sw: 'Mabingwa wa Uthabiti na Ubora',
                      fr: 'Experts en constance et en qualite',
                      ar: 'رواد الثبات والجودة',
                    ),
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
        language: _language,
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
            errorBuilder: (_, _, _) => Container(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFF193340), Color(0xFF3BA154)],
                ),
              ),
              child: const Center(
                child: Icon(Icons.business, size: 80, color: Colors.white24),
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
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFF193340),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  () {
                    final year = _about?.foundedYear?.trim();
                    if (year != null && year.isNotEmpty) {
                      return _tr(
                        en: 'Founded in $year',
                        sw: 'Ilianzishwa $year',
                        fr: 'Fondee en $year',
                        ar: 'تأسست عام $year',
                      );
                    }
                    return _tr(
                      en: 'Founded in 2012',
                      sw: 'Ilianzishwa 2012',
                      fr: 'Fondee en 2012',
                      ar: 'تأسست عام 2012',
                    );
                  }(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                _tr(
                  en: 'Our Story',
                  sw: 'Historia Yetu',
                  fr: 'Notre Histoire',
                  ar: 'قصتنا',
                ),
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _orFallback(
                  _about?.tagline,
                  _tr(
                    en: 'Building Dreams, Creating Reality',
                    sw: 'Kujenga Ndoto, Kuunda Uhalisia',
                    fr: 'Construire les reves, creer la realite',
                    ar: 'نبني الأحلام ونصنع الواقع',
                  ),
                ),
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
                    _tr(
                      en: 'Outstanding Residential Contractor 2024 - CCIT',
                      sw: 'Mkandarasi Bora wa Nyumba 2024 - CCIT',
                      fr: 'Entrepreneur residentiel exceptionnel 2024 - CCIT',
                      ar: 'أفضل مقاول سكني 2024 - CCIT',
                    ),
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
          // Story paragraphs — from CMS when published, else bundled content.
          ..._buildStoryParagraphs(),
        ],
      ),
    );
  }

  /// Story body: the CMS `story` (split on blank lines into paragraphs) when
  /// available, otherwise the bundled multilingual paragraphs.
  List<Widget> _buildStoryParagraphs() {
    final cmsStory = _about?.story?.trim();
    if (cmsStory != null && cmsStory.isNotEmpty) {
      final paragraphs = cmsStory
          .split(RegExp(r'\n\s*\n'))
          .map((p) => p.trim())
          .where((p) => p.isNotEmpty)
          .toList();
      final widgets = <Widget>[];
      for (var i = 0; i < paragraphs.length; i++) {
        if (i > 0) widgets.add(const SizedBox(height: 16));
        widgets.add(_buildParagraph(paragraphs[i]));
      }
      return widgets;
    }
    return [
      _buildParagraph(
        _tr(
          en: 'Wajenzi Professional Co. Ltd is recognized as one of the leading construction companies in East Africa. Founded in 2012 by Engineer Eliya N Kishaluli and officially registered as a company limited in 2020, we have steadily grown to become an award-winning construction firm.',
          sw: 'Wajenzi Professional Co. Ltd inajulikana kama mojawapo ya makampuni yanayoongoza ya ujenzi Afrika Mashariki. Ilianzishwa mwaka 2012 na Mhandisi Eliya N Kishaluli na kusajiliwa rasmi kama kampuni yenye kikomo mwaka 2020, tumekua kuwa kampuni ya ujenzi yenye tuzo.',
          fr: 'Wajenzi Professional Co. Ltd est reconnue comme l\'une des principales entreprises de construction en Afrique de l\'Est. Fondee en 2012 par l\'ingenieur Eliya N Kishaluli et officiellement enregistree comme societe en 2020, elle est devenue une entreprise de construction primee.',
          ar: 'تُعرف شركة Wajenzi Professional Co. Ltd بأنها واحدة من الشركات الرائدة في مجال البناء في شرق أفريقيا. تأسست عام 2012 على يد المهندس Eliya N Kishaluli وسُجلت رسميًا كشركة محدودة عام 2020، ونمت لتصبح شركة إنشاءات حائزة على جوائز.',
        ),
      ),
      const SizedBox(height: 16),
      _buildParagraph(
        _isSwahili
            ? 'Tukiwa na miradi zaidi ya 120 iliyokamilika kuanzia nyumba za makazi hadi majengo ya kibiashara, dhamira yetu ya ubora na uthabiti imetupata kutambuliwa kote katika eneo hili. Mwaka 2024, Wajenzi Professional ilituzwa tuzo ya Mkandarasi Bora wa Nyumba wa Mwaka na Chama cha Ujenzi na Miundombinu cha Tanzania (CCIT).'
            : 'With over 120 completed projects ranging from residential homes to commercial complexes, our commitment to quality and consistency has earned us recognition throughout the region. In 2024, Wajenzi Professional was honored with the Outstanding Residential Contractor of the Year award by the Chamber of Construction and Infrastructure of Tanzania (CCIT).',
      ),
      const SizedBox(height: 16),
      _buildParagraph(
        _tr(
          en: 'Our company name "Wajenzi," which means "Builders" in Swahili, reflects our deep roots in the local culture and our commitment to building not just structures, but also relationships and communities.',
          sw: 'Jina la kampuni yetu "Wajenzi," ambalo linamaanisha "Builders" kwa Kiingereza, linaonyesha mizizi yetu ya ndani katika utamaduni wa hapa na dhamira yetu ya kujenga si tu majengo, bali pia mahusiano na jamii.',
          fr: 'Le nom de notre entreprise, "Wajenzi", qui signifie "constructeurs" en swahili, reflete nos racines profondes dans la culture locale et notre engagement a construire non seulement des structures, mais aussi des relations et des communautes.',
          ar: 'اسم شركتنا "Wajenzi" الذي يعني "البناؤون" باللغة السواحيلية يعكس جذورنا العميقة في الثقافة المحلية والتزامنا ببناء ليس فقط المنشآت بل أيضًا العلاقات والمجتمعات.',
        ),
      ),
    ];
  }

  Widget _buildParagraph(String text) {
    return Text(
      text,
      style: TextStyle(color: _textSecondaryColor, fontSize: 15, height: 1.6),
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
            '120',
            '+',
            _tr(en: 'Projects', sw: 'Miradi', fr: 'Projets', ar: 'المشاريع'),
          ),
          _buildStatDivider(),
          _buildStatItem(
            '50',
            '+',
            _tr(
              en: 'Team Members',
              sw: 'Timu',
              fr: 'Equipe',
              ar: 'أعضاء الفريق',
            ),
          ),
          _buildStatDivider(),
          _buildStatItem(
            '12',
            '',
            _tr(
              en: 'Years Experience',
              sw: 'Miaka',
              fr: 'Ans d\'experience',
              ar: 'سنوات الخبرة',
            ),
          ),
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
        'title': _tr(
          en: 'Our Mission',
          sw: 'Dhamira Yetu',
          fr: 'Notre Mission',
          ar: 'مهمتنا',
        ),
        'content': _orFallback(
          _about?.mission,
          _isSwahili
              ? 'Kutoa suluhisho la ujenzi na usanifu bora, la bei nafuu, na endelevu ambalo linazidi matarajio ya wateja huku tukidumisha viwango vya juu vya uaminifu, taaluma, na uwajibikaji wa mazingira.'
              : _tr(
                  en: 'To deliver affordable, sustainable, and high-quality construction and architectural solutions that exceed client expectations while maintaining the highest standards of integrity, professionalism, and environmental responsibility.',
                  fr: 'Fournir des solutions de construction et de conception architecturale abordables, durables et de haute qualité qui dépassent les attentes des clients tout en maintenant les plus hauts standards d\'intégrité, de professionnalisme et de responsabilité environnementale.',
                  ar: 'تقديم حلول إنشائية ومعمارية ميسورة التكلفة ومستدامة وعالية الجودة تتجاوز توقعات العملاء مع الحفاظ على أعلى معايير النزاهة والاحترافية والمسؤولية البيئية.',
                ),
        ),
        'subContent': _isSwahili
            ? 'Tumejitolea kutumia mbinu na vifaa vya ubunifu vinavyopunguza athari za mazingira huku tukiboresha utendaji, uimara, na mvuto wa kuvutia katika kila mradi tunaouchukua.'
            : _tr(
                en: 'We are committed to using innovative techniques and materials that reduce environmental impact while enhancing functionality, durability, and aesthetic appeal in every project we undertake.',
                fr: 'Nous nous engageons à utiliser des techniques et des matériaux innovants qui réduisent l\'impact environnemental tout en améliorant la fonctionnalité, la durabilité et l\'attrait esthétique de chaque projet.',
                ar: 'نلتزم باستخدام تقنيات ومواد مبتكرة تقلل الأثر البيئي مع تعزيز الوظيفة والمتانة والجاذبية الجمالية في كل مشروع ننفذه.',
              ),
        'gradientColors': const [Color(0xFF3BA154), Color(0xFF2E8043)],
      },
      {
        'icon': Icons.visibility_rounded,
        'title': _tr(
          en: 'Our Vision',
          sw: 'Maono Yetu',
          fr: 'Notre Vision',
          ar: 'رؤيتنا',
        ),
        'content': _orFallback(
          _about?.vision,
          _isSwahili
              ? 'Kuwa mtoa huduma wa kimataifa anayeongoza wa huduma za ujenzi na usanifu za ubunifu, zinazotambuliwa kwa ubora, uendelevu, na athari za mabadiliko katika mazingira yaliyojengwa.'
              : _tr(
                  en: 'To become a leading global provider of innovative construction and design services, recognized for excellence, sustainability, and transformative impact on the built environment.',
                  fr: 'Devenir un fournisseur mondial de référence en services innovants de construction et de conception, reconnu pour l\'excellence, la durabilité et son impact transformateur sur l\'environnement bâti.',
                  ar: 'أن نصبح مزودًا عالميًا رائدًا لخدمات البناء والتصميم المبتكرة، معروفًا بالتميز والاستدامة والأثر التحويلي في البيئة العمرانية.',
                ),
        ),
        'subContent': _isSwahili
            ? 'Tunataka kuweka viwango vipya katika sekta ya ujenzi kupitia ubunifu unaoendelea, maendeleo ya kitaaluma, na kujitolea bila kusita kwa ubora, kuunda majengo yanayosimama jaribio la wakati na kuchangia vyema kwa jamii zinazohudumia.'
            : _tr(
                en: 'We aspire to set new standards in the construction industry through continuous innovation, professional development, and a steadfast commitment to quality, creating buildings that stand the test of time and contribute positively to the communities they serve.',
                fr: 'Nous aspirons à établir de nouvelles références dans le secteur de la construction grâce à l\'innovation continue, au développement professionnel et à un engagement constant envers la qualité, en créant des bâtiments durables qui apportent une contribution positive aux communautés servies.',
                ar: 'نطمح إلى وضع معايير جديدة في قطاع البناء من خلال الابتكار المستمر والتطوير المهني والالتزام الثابت بالجودة، وإنشاء مبانٍ تصمد أمام الزمن وتسهم إيجابًا في المجتمعات التي نخدمها.',
              ),
        'gradientColors': const [Color(0xFF27505F), Color(0xFF8E44AD)],
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
                      en: 'Our Purpose',
                      sw: 'Kusudi Letu',
                      fr: 'Notre objectif',
                      ar: 'هدفنا',
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
                      en: 'Mission & Vision',
                      sw: 'Dhamira na Maono',
                      fr: 'Mission et vision',
                      ar: 'الرسالة والرؤية',
                    ),
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
            _tr(
              en: 'Guiding principles that drive our company forward',
              sw: 'Kanuni zinazoongoza kampuni yetu mbele',
              fr: 'Des principes directeurs qui font avancer notre entreprise',
              ar: 'مبادئ توجيهية تدفع شركتنا إلى الأمام',
            ),
            style: TextStyle(color: _textSecondaryColor, fontSize: 14),
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

  // Icon/color palette used to decorate CMS-supplied core values (which carry
  // only title + description), cycled by index.
  static const List<Color> _valueAccents = [
    Color(0xFF27505F),
    Color(0xFFFECC04),
    Color(0xFF193340),
    Color(0xFF3BA154),
    Color(0xFFE74C3C),
    Color(0xFF2C3E50),
  ];
  static const List<IconData> _valueIcons = [
    Icons.self_improvement_rounded,
    Icons.lightbulb_rounded,
    Icons.verified_rounded,
    Icons.menu_book_rounded,
    Icons.groups_rounded,
    Icons.shield_rounded,
  ];

  // Core values from the portal CMS when published, else bundled content.
  List<_CoreValue> get _coreValues {
    final cms = _about?.values;
    if (cms != null && cms.isNotEmpty) {
      return cms
          .asMap()
          .entries
          .map(
            (e) => _CoreValue(
              title: e.value.title.toUpperCase(),
              icon: _valueIcons[e.key % _valueIcons.length],
              color: _valueAccents[e.key % _valueAccents.length],
              description: e.value.description ?? '',
            ),
          )
          .toList();
    }
    return _fallbackCoreValues;
  }

  List<_CoreValue> get _fallbackCoreValues => [
      _CoreValue(
        title: _tr(
          en: 'PRAYERS',
          sw: 'MAOMBI',
          fr: 'PRIÈRE',
          ar: 'الدعاء',
        ),
        icon: Icons.self_improvement_rounded,
        color: const Color(0xFF27505F),
        description: _isSwahili
            ? 'Tunaamini katika nguvu ya maombi na imani kuongoza matendo yetu, kuunganisha timu yetu, na kuhamasisha ubora katika juhudi zetu zote.'
            : _tr(
                en: 'We believe in the power of prayer and faith to guide our actions, unite our team, and inspire excellence in all our endeavors.',
                fr: 'Nous croyons au pouvoir de la prière et de la foi pour guider nos actions, unir notre équipe et inspirer l\'excellence dans tous nos efforts.',
                ar: 'نؤمن بقوة الدعاء والإيمان في توجيه أعمالنا وتوحيد فريقنا وإلهام التميز في جميع مساعينا.',
              ),
      ),
      _CoreValue(
        title: _tr(
          en: 'INNOVATION',
          sw: 'UBUNIFU',
          fr: 'INNOVATION',
          ar: 'الابتكار',
        ),
        icon: Icons.lightbulb_rounded,
        color: const Color(0xFFFECC04),
        description: _isSwahili
            ? 'Tunaendeleza na kuingiza teknolojia na mbinu mpya kutoa suluhisho za kisasa kwa mahitaji ya ujenzi na usanifu ya wateja wetu.'
            : _tr(
                en: 'We develop and incorporate new technology and approaches to provide cutting-edge solutions for our clients\' construction and design needs.',
                fr: 'Nous développons et intégrons de nouvelles technologies et approches afin de fournir des solutions de pointe aux besoins de construction et de conception de nos clients.',
                ar: 'نطوّر ونعتمد تقنيات وأساليب جديدة لتقديم حلول متقدمة لاحتياجات عملائنا في البناء والتصميم.',
              ),
      ),
      _CoreValue(
        title: _tr(
          en: 'QUALITY',
          sw: 'UBORA',
          fr: 'QUALITÉ',
          ar: 'الجودة',
        ),
        icon: Icons.verified_rounded,
        color: const Color(0xFF193340),
        description: _isSwahili
            ? 'Tunaamini ni jambo bora kufanya kitu kimoja vizuri sana. Kujitolea kwetu bila kusita kwa ubora kunaonyeshwa katika kila undani wa kazi yetu.'
            : _tr(
                en: 'We believe it\'s the best thing to do one thing really really well. Our uncompromising commitment to excellence is reflected in every detail of our work.',
                fr: 'Nous croyons qu\'il vaut mieux faire une chose exceptionnellement bien. Notre engagement sans compromis envers l\'excellence se reflète dans chaque détail de notre travail.',
                ar: 'نؤمن بأن أفضل ما يمكن فعله هو إتقان الشيء على أكمل وجه. ويظهر التزامنا الراسخ بالتميز في كل تفاصيل عملنا.',
              ),
      ),
      _CoreValue(
        title: _tr(
          en: 'READING',
          sw: 'KUSOMA',
          fr: 'LECTURE',
          ar: 'القراءة',
        ),
        icon: Icons.menu_book_rounded,
        color: const Color(0xFF3BA154),
        description: _isSwahili
            ? 'Tunakumbatia kusoma kama chombo cha ukuaji, maarifa, na uboreshaji wa kuendelea. Timu yetu inahimizwa kujifunza daima.'
            : _tr(
                en: 'We embrace reading as a tool for growth, knowledge, and continuous improvement. Our team is encouraged to constantly learn.',
                fr: 'Nous considérons la lecture comme un outil de croissance, de connaissance et d\'amélioration continue. Notre équipe est encouragée à apprendre en permanence.',
                ar: 'نعتبر القراءة أداة للنمو والمعرفة والتحسين المستمر. ونشجع فريقنا على التعلم الدائم.',
              ),
      ),
      _CoreValue(
        title: _tr(
          en: 'TEAMWORK',
          sw: 'USHIRIKIANO',
          fr: 'TRAVAIL D\'ÉQUIPE',
          ar: 'العمل الجماعي',
        ),
        icon: Icons.groups_rounded,
        color: const Color(0xFFE74C3C),
        description: _isSwahili
            ? 'Tunaamini suluhisho bora linakuja kutokana na kufanya kazi pamoja. Tunakuza mazingira ya ushirikiano ambapo ujuzi na mtazamo mbalimbali unathaminiwa.'
            : _tr(
                en: 'We believe the best solution comes from working together. We foster a collaborative environment where diverse skills and perspectives are valued.',
                fr: 'Nous croyons que les meilleures solutions naissent du travail collectif. Nous favorisons un environnement collaboratif où les compétences et points de vue divers sont valorisés.',
                ar: 'نؤمن بأن أفضل الحلول تأتي من العمل معًا. ونرعى بيئة تعاونية تُقدَّر فيها المهارات ووجهات النظر المتنوعة.',
              ),
      ),
      _CoreValue(
        title: _tr(
          en: 'INTEGRITY',
          sw: 'UAMINIFU',
          fr: 'INTÉGRITÉ',
          ar: 'النزاهة',
        ),
        icon: Icons.shield_rounded,
        color: const Color(0xFF2C3E50),
        description: _isSwahili
            ? 'Tunafanya jambo sahihi daima. Mazoea yetu ya biashara yanajengwa juu ya uaminifu, uwazi, na mwenendo wa kimaadili.'
            : _tr(
                en: 'We do the right thing always. Our business practices are founded on honesty, transparency, and ethical conduct.',
                fr: 'Nous faisons toujours ce qui est juste. Nos pratiques commerciales reposent sur l\'honnêteté, la transparence et une conduite éthique.',
                ar: 'نقوم دائمًا بما هو صائب. وتقوم ممارساتنا التجارية على الصدق والشفافية والسلوك الأخلاقي.',
              ),
      ),
    ];

  Widget _buildCoreValuesSection() {
    final values = _coreValues;

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
                    _tr(
                      en: 'Our Guiding Principles',
                      sw: 'Kanuni Zetu',
                      fr: 'Nos principes directeurs',
                      ar: 'مبادئنا التوجيهية',
                    ),
                    style: const TextStyle(
                      color: Color(0xFFD4AF37),
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 1,
                    ),
                  ),
                  Text(
                    _tr(
                      en: 'Core Values',
                      sw: 'Thamani za Msingi',
                      fr: 'Valeurs fondamentales',
                      ar: 'القيم الأساسية',
                    ),
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
            _tr(
              en: 'These principles guide our decisions, shape our culture, and define our approach to every project we undertake.',
              sw: 'Kanuni hizi zinaongoza maamuzi yetu, kuunda utamaduni wetu, na kufafanua mbinu yetu kwa kila mradi tunaoufanya.',
              fr: 'Ces principes guident nos décisions, façonnent notre culture et définissent notre approche pour chaque projet que nous réalisons.',
              ar: 'توجّه هذه المبادئ قراراتنا، وتشكل ثقافتنا، وتحدد نهجنا في كل مشروع ننفذه.',
            ),
            style: TextStyle(color: _textSecondaryColor, fontSize: 14),
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
                child: Icon(value.icon, color: value.color, size: 22),
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

  // Leadership team from the portal CMS when published, else bundled content.
  List<_TeamMember> get _leaders {
    final cms = _about?.team;
    if (cms != null && cms.isNotEmpty) {
      return cms
          .map(
            (m) => _TeamMember(
              name: m.name,
              role: m.role ?? '',
              image: m.image ?? '',
              description: m.bio ?? '',
            ),
          )
          .toList();
    }
    return _fallbackLeaders;
  }

  List<_TeamMember> get _fallbackLeaders => [
      _TeamMember(
        name: 'Eng. ELIYA N. KISHALULI',
        role: _isSwahili
            ? 'Mwanzilishi na Mkurugenzi Mtendaji'
            : _tr(
                en: 'Founder & CEO',
                fr: 'Fondateur et PDG',
                ar: 'المؤسس والرئيس التنفيذي',
              ),
        image: 'assets/images/ELIYA_KISHALULI.jpeg',
        description: _isSwahili
            ? 'Akiwa na uzoefu wa zaidi ya miaka 15 katika ujenzi na uhandisi, Eng. Eliya alianzisha Wajenzi Professional akiwa na maono ya kubadilisha sekta ya ujenzi Afrika Mashariki.'
            : _tr(
                en: 'With over 15 years of experience in construction and engineering, Eng. Eliya founded Wajenzi Professional with a vision to transform the construction industry in East Africa.',
                fr: 'Avec plus de 15 ans d\'expérience dans la construction et l\'ingénierie, l\'ingénieur Eliya a fondé Wajenzi Professional avec la vision de transformer le secteur de la construction en Afrique de l\'Est.',
                ar: 'بخبرة تزيد على 15 عامًا في البناء والهندسة، أسس المهندس إليا شركة Wajenzi Professional برؤية تهدف إلى تحويل قطاع البناء في شرق أفريقيا.',
              ),
      ),
      _TeamMember(
        name: 'SARAH JOHN',
        role: _tr(
          en: 'Managing Director',
          sw: 'Mkurugenzi Msimamizi',
          fr: 'Directrice générale',
          ar: 'المديرة العامة',
        ),
        image: 'assets/images/SARAH_JOHN.png',
        description: _isSwahili
            ? 'Mkurugenzi Msimamizi mwenye uzoefu katika ujenzi, mzuri katika usimamizi wa miradi, bajeti, na uongozi wa timu, na rekodi iliyothibitishwa ya kutoa miradi bora kwa wakati na ndani ya bajeti.'
            : _tr(
                en: 'Experienced Managing Director in construction, skilled in project management, budgeting, and team leadership, with a proven record of delivering quality projects on time and within budget.',
                fr: 'Directrice générale expérimentée dans la construction, compétente en gestion de projet, budgétisation et leadership d\'équipe, avec un solide historique de livraison de projets de qualité dans les délais et le budget.',
                ar: 'مديرة عامة ذات خبرة في قطاع البناء، متمرسة في إدارة المشاريع وإعداد الميزانيات وقيادة الفرق، ولديها سجل مثبت في تسليم مشاريع عالية الجودة في الوقت المحدد وضمن الميزانية.',
              ),
      ),
      _TeamMember(
        name: 'MOHAMEDI JOSEPH',
        role: _isSwahili
            ? 'Meneja wa Maendeleo ya Biashara'
            : _tr(
                en: 'Business Development Manager',
                fr: 'Responsable du développement commercial',
                ar: 'مدير تطوير الأعمال',
              ),
        image: 'assets/images/MOHAMED_JOSEPH.png',
        description: _isSwahili
            ? 'Meneja wa Maendeleo ya Biashara katika sekta ya ujenzi, mzuri katika mahusiano na wateja, upataji wa miradi, na ukuaji wa kimkakati, na rekodi iliyothibitishwa ya kuongeza mapato.'
            : _tr(
                en: 'Business Development Manager in the construction industry, skilled in client relations, project acquisition, and strategic growth, with a proven record of driving revenue and delivering successful projects.',
                fr: 'Responsable du développement commercial dans le secteur de la construction, spécialisé dans la relation client, l\'acquisition de projets et la croissance stratégique, avec un solide historique d\'augmentation des revenus et de réalisation de projets réussis.',
                ar: 'مدير تطوير أعمال في قطاع البناء، يتمتع بخبرة في علاقات العملاء واستقطاب المشاريع والنمو الاستراتيجي، مع سجل مثبت في زيادة الإيرادات وتنفيذ مشاريع ناجحة.',
              ),
      ),
    ];

  Widget _buildLeadershipSection() {
    final leaders = _leaders;

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
                      en: 'Expert Leaders',
                      sw: 'Viongozi Wabobezi',
                      fr: 'Des leaders experts',
                      ar: 'قادة خبراء',
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
                      en: 'Our Leadership Team',
                      sw: 'Timu ya Uongozi',
                      fr: 'Notre équipe dirigeante',
                      ar: 'فريقنا القيادي',
                    ),
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
            _tr(
              en: 'Meet the professionals who guide our company with expertise and vision.',
              sw: 'Kutana na wataalamu wanaoongoza kampuni yetu kwa ujuzi na maono.',
              fr: 'Rencontrez les professionnels qui dirigent notre entreprise avec expertise et vision.',
              ar: 'تعرّف على الخبراء الذين يقودون شركتنا بخبرة ورؤية.',
            ),
            style: TextStyle(color: _textSecondaryColor, fontSize: 14),
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
                      ? const Color(0xFF3BA154)
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

  /// Team-member photo — network (CMS, resolved to an absolute URL) when an
  /// http URL, the bundled asset for fallback content, else a person icon.
  Widget _leaderImage(String image) {
    Widget placeholder() => Container(
      color: const Color(0xFF193340).withValues(alpha: 0.2),
      child: const Icon(Icons.person, size: 40, color: Color(0xFF193340)),
    );
    final resolved = AppConfig.resolvePortalMediaUrl(image) ?? image;
    if (resolved.startsWith('http')) {
      return CachedNetworkImage(
        imageUrl: resolved,
        fit: BoxFit.cover,
        placeholder: (_, _) =>
            Container(color: const Color(0xFF193340).withValues(alpha: 0.1)),
        errorWidget: (_, _, _) => placeholder(),
      );
    }
    if (resolved.isEmpty) return placeholder();
    return Image.asset(
      resolved,
      fit: BoxFit.cover,
      errorBuilder: (_, _, _) => placeholder(),
    );
  }

  Widget _buildLeaderCard(_TeamMember leader) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(16),
        border: _isDarkMode
            ? Border.all(color: const Color(0xFF3BA154).withValues(alpha: 0.3))
            : null,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF3BA154).withValues(alpha: 0.1),
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
                color: const Color(0xFF193340).withValues(alpha: 0.5),
                width: 2,
              ),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: _leaderImage(leader.image),
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
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 3,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF193340).withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    leader.role,
                    style: const TextStyle(
                      color: Color(0xFF193340),
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
                _tr(
                  en: 'Contact Us',
                  sw: 'Wasiliana Nasi',
                  fr: 'Contactez-nous',
                  ar: 'اتصل بنا',
                ),
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
            title: _tr(
              en: 'Address',
              sw: 'Anwani',
              fr: 'Adresse',
              ar: 'العنوان',
            ),
            content: _orFallback(
              _about?.address,
              'Ground-Floor (07), PSSSF Commercial Complex, Dar es Salaam',
            ),
          ),
          const SizedBox(height: 16),

          // Phone
          _buildContactItem(
            icon: Icons.phone_rounded,
            title: _tr(en: 'Phone', sw: 'Simu', fr: 'Telephone', ar: 'الهاتف'),
            content: _orFallback(_about?.phone, '+255 793 444 400'),
          ),
          const SizedBox(height: 16),

          // Email
          _buildContactItem(
            icon: Icons.email_rounded,
            title: _tr(
              en: 'Email',
              sw: 'Barua Pepe',
              fr: 'E-mail',
              ar: 'البريد الإلكتروني',
            ),
            content: _orFallback(
              _about?.email,
              'info@wajenziprofessional.co.tz',
            ),
          ),
          const SizedBox(height: 16),

          // Hours
          _buildContactItem(
            icon: Icons.access_time_rounded,
            title: _tr(
              en: 'Working Hours',
              sw: 'Saa za Kazi',
              fr: 'Heures d\'ouverture',
              ar: 'ساعات العمل',
            ),
            content: _orFallback(
              _about?.workingHours,
              _tr(
                en: 'Mon - Fri: 8:00 AM - 6:00 PM',
                sw: 'Jumatatu - Ijumaa: 8:00 AM - 6:00 PM',
                fr: 'Lun - Ven : 8h00 - 18h00',
                ar: 'الاثنين - الجمعة: 8:00 ص - 6:00 م',
              ),
            ),
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
