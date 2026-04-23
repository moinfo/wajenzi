import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

class LandingScreen extends ConsumerStatefulWidget {
  const LandingScreen({super.key});

  @override
  ConsumerState<LandingScreen> createState() => _LandingScreenState();
}

class _LandingScreenState extends ConsumerState<LandingScreen> {
  int _selectedMenuIndex = 0;

  AppLanguage get _language => ref.watch(currentLanguageProvider);
  bool get _isDarkMode => ref.watch(isDarkModeProvider);
  bool get _isSwahili => ref.watch(isSwahiliProvider);
  bool get _isFrench => _language == AppLanguage.french;
  bool get _isArabic => _language == AppLanguage.arabic;

  List<ProjectShowcase> get _projects => [
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.10.png',
      title: _tr(
        en: 'Hotel Construction',
        sw: 'Ujenzi wa Hoteli',
        fr: 'Construction d’hôtel',
        ar: 'بناء فندق',
      ),
      priceTZS: '6,911,200,000',
      priceUSD: '2,764,480',
      features: [
        _tr(en: 'Bedrooms', sw: 'Vyumba', fr: 'Chambres', ar: 'غرف النوم'),
        _tr(en: 'Restaurant', sw: 'Mkahawa', fr: 'Restaurant', ar: 'مطعم'),
        _tr(en: 'Bar', sw: 'Baa', fr: 'Bar', ar: 'بار'),
        _tr(en: 'Parking', sw: 'Maegesho', fr: 'Parking', ar: 'مواقف'),
        _tr(en: 'Gym', sw: 'Gym', fr: 'Salle de sport', ar: 'نادي رياضي'),
        _tr(en: 'Spa', sw: 'Spa', fr: 'Spa', ar: 'سبا'),
      ],
      category: _tr(
        en: '3D Design',
        sw: 'Ubunifu wa 3D',
        fr: 'Design 3D',
        ar: 'تصميم ثلاثي الأبعاد',
      ),
      likes: 156,
      timeAgo: _tr(
        en: '2 days ago',
        sw: 'siku 2 zilizopita',
        fr: 'il y a 2 jours',
        ar: 'منذ يومين',
      ),
      description: _tr(
        en: 'Luxury hotel project featuring modern architecture with premium amenities.',
        sw: 'Mradi wa hoteli ya kifahari wenye usanifu wa kisasa na huduma za kiwango cha juu.',
        fr: 'Projet hôtelier de luxe présentant une architecture moderne et des équipements haut de gamme.',
        ar: 'مشروع فندق فاخر يتميز بعمارة حديثة ومرافق متميزة.',
      ),
      isFeatured: true,
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.20.png',
      title: _tr(
        en: 'Residential Villa',
        sw: 'Villa ya Makazi',
        fr: 'Villa résidentielle',
        ar: 'فيلا سكنية',
      ),
      priceTZS: '850,000,000',
      priceUSD: '340,000',
      features: ['5 Bedrooms', 'Swimming Pool', 'Garden', 'Garage'],
      category: _tr(
        en: 'Completed',
        sw: 'Imekamilika',
        fr: 'Terminé',
        ar: 'مكتمل',
      ),
      likes: 243,
      timeAgo: _tr(
        en: '1 week ago',
        sw: 'wiki 1 iliyopita',
        fr: 'il y a 1 semaine',
        ar: 'منذ أسبوع',
      ),
      description: _tr(
        en: 'Beautiful modern villa in Dar es Salaam with stunning views.',
        sw: 'Villa nzuri ya kisasa jijini Dar es Salaam yenye mandhari ya kuvutia.',
        fr: 'Belle villa moderne à Dar es Salaam avec des vues imprenables.',
        ar: 'فيلا حديثة جميلة في دار السلام بإطلالات خلابة.',
      ),
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.28.png',
      title: 'Office Complex',
      priceTZS: '2,500,000,000',
      priceUSD: '1,000,000',
      features: ['Open Offices', 'Meeting Rooms', 'Cafeteria', 'Parking'],
      category: _tr(
        en: 'In Progress',
        sw: 'Inaendelea',
        fr: 'En cours',
        ar: 'قيد التنفيذ',
      ),
      likes: 89,
      timeAgo: _tr(
        en: '3 days ago',
        sw: 'siku 3 zilizopita',
        fr: 'il y a 3 jours',
        ar: 'منذ 3 أيام',
      ),
      description: _tr(
        en: 'State-of-the-art commercial office building in the business district.',
        sw: 'Jengo la kisasa la ofisi za biashara katika eneo la biashara.',
        fr: 'Immeuble de bureaux commerciaux ultramoderne dans le quartier des affaires.',
        ar: 'مبنى مكاتب تجاري حديث للغاية في الحي التجاري.',
      ),
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.31.png',
      title: 'Apartment Complex',
      priceTZS: '4,200,000,000',
      priceUSD: '1,680,000',
      features: ['24 Units', 'Gym', 'Rooftop Lounge', 'Security'],
      category: _tr(
        en: 'Design',
        sw: 'Ubunifu',
        fr: 'Conception',
        ar: 'تصميم',
      ),
      likes: 178,
      timeAgo: _tr(
        en: '5 days ago',
        sw: 'siku 5 zilizopita',
        fr: 'il y a 5 jours',
        ar: 'منذ 5 أيام',
      ),
      description: _tr(
        en: 'Modern apartment living with premium shared amenities.',
        sw: 'Maisha ya kisasa ya ghorofa yenye huduma bora za pamoja.',
        fr: 'Vie en appartement moderne avec des commodités partagées haut de gamme.',
        ar: 'سكن شقق حديث مع مرافق مشتركة متميزة.',
      ),
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.50.40.png',
      title: 'Shopping Mall',
      priceTZS: '12,500,000,000',
      priceUSD: '5,000,000',
      features: ['150 Shops', 'Cinema', 'Food Court', 'Parking'],
      category: _tr(
        en: '3D Design',
        sw: 'Ubunifu wa 3D',
        fr: 'Design 3D',
        ar: 'تصميم ثلاثي الأبعاد',
      ),
      likes: 312,
      timeAgo: _tr(
        en: '1 day ago',
        sw: 'siku 1 iliyopita',
        fr: 'il y a 1 jour',
        ar: 'منذ يوم واحد',
      ),
      description: _tr(
        en: 'Modern shopping center with entertainment and retail facilities.',
        sw: 'Kituo cha kisasa cha ununuzi chenye burudani na huduma za rejareja.',
        fr: 'Centre commercial moderne avec espaces de divertissement et boutiques.',
        ar: 'مركز تسوق حديث يضم مرافق ترفيهية وتجارية.',
      ),
    ),
    ProjectShowcase(
      image: 'assets/images/post/Screenshot 2026-01-21 at 14.51.07.png',
      title: 'School Building',
      priceTZS: '1,800,000,000',
      priceUSD: '720,000',
      features: ['30 Classrooms', 'Library', 'Labs', 'Sports Field'],
      category: _tr(
        en: 'Completed',
        sw: 'Imekamilika',
        fr: 'Terminé',
        ar: 'مكتمل',
      ),
      likes: 198,
      timeAgo: _tr(
        en: '2 weeks ago',
        sw: 'wiki 2 zilizopita',
        fr: 'il y a 2 semaines',
        ar: 'منذ أسبوعين',
      ),
      description: _tr(
        en: 'Educational facility with modern learning environments.',
        sw: 'Kituo cha elimu chenye mazingira ya kisasa ya kujifunzia.',
        fr: 'Établissement éducatif avec des environnements d’apprentissage modernes.',
        ar: 'منشأة تعليمية تضم بيئات تعلم حديثة.',
      ),
    ),
  ];

  Color get _bgColor =>
      _isDarkMode ? const Color(0xFF0F0F1A) : const Color(0xFFF0F4F8);

  Color get _surfaceColor =>
      _isDarkMode ? const Color(0xFF1A1A2E) : Colors.white;

  String _tr({required String en, String? sw, String? fr, String? ar}) {
    if (_isSwahili) return sw ?? en;
    if (_isFrench) return fr ?? en;
    if (_isArabic) return ar ?? en;
    return en;
  }

  Widget _languageFlag() {
    switch (_language) {
      case AppLanguage.swahili:
        return const TanzaniaFlag();
      case AppLanguage.french:
        return const FranceFlag();
      case AppLanguage.arabic:
        return const ArabicLanguageBadge();
      case AppLanguage.english:
        return const UKFlag();
    }
  }

  Future<void> _launchWhatsApp(String projectName) async {
    final message = _tr(
      en: 'Hello! I am interested in learning more about the project: $projectName',
      sw: 'Habari! Napenda kupata taarifa zaidi kuhusu mradi: $projectName',
      fr: 'Bonjour ! Je souhaite en savoir plus sur le projet : $projectName',
      ar: 'مرحبًا! أرغب في معرفة المزيد عن المشروع: $projectName',
    );
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

  Future<void> _launchPhone() async {
    final opened = await ExternalLauncherService.callCompany();
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _tr(
              en: 'Could not open dialer',
              sw: 'Imeshindwa kupiga simu',
              fr: 'Impossible d\'ouvrir le composeur',
              ar: 'تعذر فتح تطبيق الاتصال',
            ),
          ),
        ),
      );
    }
  }

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
          // ── App Bar ─────────────────────────────────────────────────
          SliverAppBar(
            floating: true,
            snap: true,
            backgroundColor: _isDarkMode
                ? const Color(0xFF1A1A2E)
                : const Color(0xFFF0F4F8),
            elevation: 0,
            toolbarHeight: 70,
            title: Row(
              children: [
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
                        errorBuilder: (_, _, _) => const Icon(
                          Icons.business,
                          color: Color(0xFF1ABC9C),
                          size: 24,
                        ),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text(
                        'Wajenzi Professionals',
                        style: TextStyle(
                          color: Color(0xFF1ABC9C),
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        _tr(
                          en: 'Masters of Consistency and Quality',
                          sw: 'Mabingwa wa Uthabiti na Ubora',
                          fr: 'Experts en constance et en qualite',
                          ar: 'رواد الثبات والجودة',
                        ),
                        style: TextStyle(
                          color: _isDarkMode
                              ? Colors.white54
                              : Colors.grey[500],
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
              Container(
                height: 40,
                padding: const EdgeInsets.symmetric(horizontal: 8),
                decoration: BoxDecoration(
                  color: _isDarkMode ? const Color(0xFF16213E) : Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: _isDarkMode
                        ? Colors.white.withValues(alpha: 0.15)
                        : Colors.grey.withValues(alpha: 0.25),
                  ),
                ),
                child: PopupMenuButton<AppLanguage>(
                  initialValue: _language,
                  tooltip: _tr(
                    en: 'Select language',
                    sw: 'Chagua lugha',
                    fr: 'Choisir la langue',
                    ar: 'اختر اللغة',
                  ),
                  color: _isDarkMode ? const Color(0xFF1F2A44) : Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  onSelected: (value) =>
                      ref.read(settingsProvider.notifier).setLanguage(value),
                  itemBuilder: (context) => AppLanguage.values
                      .map(
                        (lang) => PopupMenuItem<AppLanguage>(
                          value: lang,
                          child: Row(
                            children: [
                              SizedBox(
                                width: 20,
                                height: 13,
                                child: switch (lang) {
                                  AppLanguage.swahili => const TanzaniaFlag(),
                                  AppLanguage.french => const FranceFlag(),
                                  AppLanguage.arabic =>
                                    const ArabicLanguageBadge(),
                                  AppLanguage.english => const UKFlag(),
                                },
                              ),
                              const SizedBox(width: 8),
                              Text(
                                '${lang.code} - ${switch (lang) {
                                  AppLanguage.english => 'English',
                                  AppLanguage.swahili => 'Kiswahili',
                                  AppLanguage.french => 'Français',
                                  AppLanguage.arabic => 'العربية',
                                }}',
                              ),
                            ],
                          ),
                        ),
                      )
                      .toList(),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(2),
                        child: SizedBox(
                          width: 20,
                          height: 13,
                          child: _languageFlag(),
                        ),
                      ),
                      const SizedBox(width: 3),
                      Text(
                        _language.code,
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w600,
                          color: _isDarkMode
                              ? Colors.white
                              : const Color(0xFF2C3E50),
                        ),
                      ),
                      Icon(
                        Icons.arrow_drop_down_rounded,
                        size: 14,
                        color: _isDarkMode
                            ? Colors.white
                            : const Color(0xFF2C3E50),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              _buildTopBarButton(
                onTap: () =>
                    ref.read(settingsProvider.notifier).toggleDarkMode(),
                child: Icon(
                  _isDarkMode
                      ? Icons.dark_mode_rounded
                      : Icons.light_mode_rounded,
                  size: 20,
                  color: _isDarkMode
                      ? const Color(0xFF1ABC9C)
                      : const Color(0xFFF39C12),
                ),
              ),
              const SizedBox(width: 8),
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

          // ── Hero stats banner ────────────────────────────────────────
          SliverToBoxAdapter(
            child: _HeroStats(
              isDarkMode: _isDarkMode,
              isSwahili: _isSwahili,
              surfaceColor: _surfaceColor,
            ),
          ),

          // ── Section header ───────────────────────────────────────────
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 20, 16, 4),
              child: Row(
                children: [
                  Container(
                    width: 4,
                    height: 22,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1ABC9C), Color(0xFF3498DB)],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _tr(
                            en: 'Our Portfolio',
                            sw: 'Kazi Zetu',
                            fr: 'Notre Portefeuille',
                            ar: 'أعمالنا',
                          ),
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w800,
                            color: _isDarkMode
                                ? Colors.white
                                : const Color(0xFF1A1A2E),
                          ),
                        ),
                        Text(
                          _isSwahili
                              ? '${_projects.length} miradi iliyochaguliwa'
                              : _isFrench
                              ? '${_projects.length} projets en vedette'
                              : _isArabic
                              ? '${_projects.length} مشاريع مميزة'
                              : '${_projects.length} featured projects',
                          style: TextStyle(
                            fontSize: 12,
                            color: _isDarkMode
                                ? Colors.white54
                                : Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── Project cards ────────────────────────────────────────────
          SliverList(
            delegate: SliverChildBuilderDelegate(
              (context, index) => _ProjectCard(
                project: _projects[index],
                index: index,
                total: _projects.length,
                isDarkMode: _isDarkMode,
                isSwahili: _isSwahili,
                callLabel: _tr(
                  en: 'Call',
                  sw: 'Piga Simu',
                  fr: 'Appeler',
                  ar: 'اتصال',
                ),
                onWhatsApp: () => _launchWhatsApp(_projects[index].title),
                onCall: _launchPhone,
                onImageTap: () => _showImageModal(
                  context,
                  _projects[index].image,
                  _projects[index].title,
                ),
              ),
              childCount: _projects.length,
            ),
          ),

          // ── CTA banner ───────────────────────────────────────────────
          SliverToBoxAdapter(
            child: Container(
              margin: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: _isDarkMode
                      ? [const Color(0xFF0D3B34), const Color(0xFF0A2A40)]
                      : [const Color(0xFF1ABC9C), const Color(0xFF2980B9)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF1ABC9C).withValues(alpha: 0.25),
                    blurRadius: 24,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Text(
                    _tr(
                      en: 'Ready to Build Your Dream?',
                      sw: 'Uko Tayari Kujenga Ndoto Yako?',
                      fr: 'Pret a construire votre reve ?',
                      ar: 'هل أنت مستعد لبناء حلمك؟',
                    ),
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 20,
                      fontWeight: FontWeight.w800,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _tr(
                      en: 'Join Wajenzi for professional construction services',
                      sw: 'Jiunge na Wajenzi upate huduma za ujenzi za kitaalamu',
                      fr: 'Rejoignez Wajenzi pour des services de construction professionnels',
                      ar: 'انضم إلى Wajenzi للحصول على خدمات بناء احترافية',
                    ),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.8),
                      fontSize: 13,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _StatBadge(
                        '120+',
                        _tr(
                          en: 'Flagship Projects',
                          sw: 'Miradi ya Kipekee',
                          fr: 'Projets Phares',
                          ar: 'المشاريع الرائدة',
                        ),
                      ),
                      const SizedBox(width: 12),
                      _StatBadge(
                        '50+',
                        _tr(
                          en: 'Experts',
                          sw: 'Wataalamu',
                          fr: 'Experts',
                          ar: 'الخبراء',
                        ),
                      ),
                      const SizedBox(width: 12),
                      _StatBadge(
                        '200+',
                        _tr(
                          en: 'Completed',
                          sw: 'Imekamilika',
                          fr: 'Termines',
                          ar: 'مكتمل',
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => context.go('/login'),
                      icon: const Icon(Icons.arrow_forward_rounded),
                      label: Text(
                        _tr(
                          en: 'Get Started',
                          sw: 'Anza Sasa',
                          fr: 'Commencer',
                          ar: 'ابدأ الآن',
                        ),
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: const Color(0xFF1ABC9C),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── Footer ───────────────────────────────────────────────────
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.only(bottom: 110),
              child: Column(
                children: [
                  Text(
                    'Powered by Moinfotech',
                    style: TextStyle(
                      color: _isDarkMode ? Colors.white24 : Colors.grey[400],
                      fontSize: 11,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'v1.0.0',
                    style: TextStyle(
                      color: _isDarkMode ? Colors.white12 : Colors.grey[300],
                      fontSize: 10,
                    ),
                  ),
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
        language: _language,
        onItemTapped: (index) => setState(() => _selectedMenuIndex = index),
      ),
    );
  }

  void _showImageModal(BuildContext context, String imagePath, String title) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(
          children: [
            Center(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: InteractiveViewer(
                  minScale: 0.5,
                  maxScale: 4.0,
                  child: Image.asset(
                    imagePath,
                    fit: BoxFit.contain,
                    errorBuilder: (_, _, _) => Container(
                      height: 300,
                      color: const Color(0xFF2C3E50),
                      child: const Center(
                        child: Icon(
                          Icons.broken_image,
                          color: Colors.white54,
                          size: 48,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
            Positioned(
              top: 0,
              right: 0,
              child: GestureDetector(
                onTap: () => Navigator.of(ctx).pop(),
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: const BoxDecoration(
                    color: Colors.black54,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.close, color: Colors.white, size: 22),
                ),
              ),
            ),
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  vertical: 10,
                  horizontal: 16,
                ),
                decoration: const BoxDecoration(
                  color: Colors.black54,
                  borderRadius: BorderRadius.vertical(
                    bottom: Radius.circular(16),
                  ),
                ),
                child: Text(
                  title,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Hero Stats ───────────────────────────────────────────────────────────────

class _HeroStats extends StatelessWidget {
  final bool isDarkMode;
  final bool isSwahili;
  final Color surfaceColor;
  const _HeroStats({
    required this.isDarkMode,
    required this.isSwahili,
    required this.surfaceColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 0),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: surfaceColor,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.07)
              : Colors.grey.withValues(alpha: 0.12),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.06),
            blurRadius: 12,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        children: [
          _StatItem(
            icon: Icons.folder_special_rounded,
            value: '120+',
            label: isSwahili ? 'Miradi ya Kipekee' : 'Flagship Projects',
            color: const Color(0xFF1ABC9C),
            isDarkMode: isDarkMode,
          ),
          _Divider(isDarkMode: isDarkMode),
          _StatItem(
            icon: Icons.engineering_rounded,
            value: '50+',
            label: isSwahili ? 'Wataalamu' : 'Experts',
            color: const Color(0xFF3498DB),
            isDarkMode: isDarkMode,
          ),
          _Divider(isDarkMode: isDarkMode),
          _StatItem(
            icon: Icons.verified_rounded,
            value: '200+',
            label: isSwahili ? 'Imekamilika' : 'Completed',
            color: const Color(0xFF2ECC71),
            isDarkMode: isDarkMode,
          ),
          _Divider(isDarkMode: isDarkMode),
          _StatItem(
            icon: Icons.star_rounded,
            value: '4.9',
            label: isSwahili ? 'Ukadiriaji' : 'Rating',
            color: const Color(0xFFF39C12),
            isDarkMode: isDarkMode,
          ),
        ],
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;
  final Color color;
  final bool isDarkMode;
  const _StatItem({
    required this.icon,
    required this.value,
    required this.label,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
              color: isDarkMode ? Colors.white : const Color(0xFF1A1A2E),
            ),
          ),
          Text(
            label,
            style: TextStyle(
              fontSize: 9,
              color: isDarkMode ? Colors.white38 : Colors.grey[500],
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

class _Divider extends StatelessWidget {
  final bool isDarkMode;
  const _Divider({required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 1,
      height: 36,
      color: isDarkMode
          ? Colors.white.withValues(alpha: 0.08)
          : Colors.grey.withValues(alpha: 0.15),
    );
  }
}

// ─── Project Card ─────────────────────────────────────────────────────────────

class _ProjectCard extends StatelessWidget {
  final ProjectShowcase project;
  final int index;
  final int total;
  final bool isDarkMode;
  final bool isSwahili;
  final String callLabel;
  final VoidCallback onWhatsApp;
  final VoidCallback onCall;
  final VoidCallback onImageTap;

  const _ProjectCard({
    required this.project,
    required this.index,
    required this.total,
    required this.isDarkMode,
    required this.isSwahili,
    required this.callLabel,
    required this.onWhatsApp,
    required this.onCall,
    required this.onImageTap,
  });

  Color get _catColor {
    switch (project.category.toUpperCase()) {
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

  IconData get _catIcon {
    switch (project.category.toUpperCase()) {
      case 'COMPLETED':
        return Icons.home_work_rounded;
      case 'IN PROGRESS':
        return Icons.construction_rounded;
      case '3D DESIGN':
        return Icons.view_in_ar_rounded;
      case 'DESIGN':
        return Icons.architecture_rounded;
      default:
        return Icons.business_rounded;
    }
  }

  String _fmt(String price) {
    final n = double.tryParse(price.replaceAll(',', '')) ?? 0;
    if (n >= 1e9) return '${(n / 1e9).toStringAsFixed(1)}B';
    if (n >= 1e6) return '${(n / 1e6).toStringAsFixed(0)}M';
    return price;
  }

  @override
  Widget build(BuildContext context) {
    final catColor = _catColor;
    final surfaceColor = isDarkMode ? const Color(0xFF1A1A2E) : Colors.white;
    final displayPrice = isSwahili
        ? 'TZS ${_fmt(project.priceTZS)}'
        : 'USD ${_fmt(project.priceUSD)}';

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: surfaceColor,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: catColor.withValues(alpha: isDarkMode ? 0.18 : 0.12),
            blurRadius: 24,
            offset: const Offset(0, 8),
          ),
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.3 : 0.06),
            blurRadius: 12,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(22),
        child: Column(
          children: [
            // ── Accent top border ──────────────────────────────────────
            Container(
              height: 3,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [catColor, catColor.withValues(alpha: 0.4)],
                ),
              ),
            ),

            // ── TOP HEADER ────────────────────────────────────────────
            Container(
              padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
              color: surfaceColor,
              child: Row(
                children: [
                  // Gradient icon box
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [catColor, catColor.withValues(alpha: 0.6)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: catColor.withValues(alpha: 0.35),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: Icon(_catIcon, color: Colors.white, size: 22),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          project.title,
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 15,
                            color: isDarkMode
                                ? Colors.white
                                : const Color(0xFF1A1A2E),
                            letterSpacing: -0.2,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Icon(
                              Icons.access_time_rounded,
                              size: 11,
                              color: isDarkMode
                                  ? Colors.white38
                                  : Colors.grey[400],
                            ),
                            const SizedBox(width: 3),
                            Text(
                              project.timeAgo,
                              style: TextStyle(
                                color: isDarkMode
                                    ? Colors.white38
                                    : Colors.grey[500],
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  // Category pill with gradient
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [catColor, catColor.withValues(alpha: 0.75)],
                      ),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: catColor.withValues(alpha: 0.3),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Text(
                      project.category,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.3,
                      ),
                    ),
                  ),
                  if (project.isFeatured) ...[
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.all(5),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFD700).withValues(alpha: 0.15),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.star_rounded,
                        color: Color(0xFFFFD700),
                        size: 16,
                      ),
                    ),
                  ],
                ],
              ),
            ),

            // ── IMAGE ─────────────────────────────────────────────────
            Stack(
              children: [
                GestureDetector(
                  onTap: onImageTap,
                  child: AspectRatio(
                    aspectRatio: 16 / 10,
                    child: Image.asset(
                      project.image,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => _PlaceholderImage(
                        title: project.title,
                        color: catColor,
                        icon: _catIcon,
                      ),
                    ),
                  ),
                ),
                // Subtle bottom fade connecting image to panel
                Positioned(
                  bottom: 0,
                  left: 0,
                  right: 0,
                  height: 40,
                  child: Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          (isDarkMode
                                  ? const Color(0xFF141428)
                                  : const Color(0xFFF4F6F8))
                              .withValues(alpha: 0.7),
                        ],
                      ),
                    ),
                  ),
                ),
                // Counter
                Positioned(
                  bottom: 8,
                  right: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.5),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '${index + 1} / $total',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
                // Zoom hint
                Positioned(
                  bottom: 8,
                  left: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 7,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.4),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.zoom_in_rounded,
                          size: 11,
                          color: Colors.white70,
                        ),
                        SizedBox(width: 3),
                        Text(
                          'Tap to zoom',
                          style: TextStyle(fontSize: 9, color: Colors.white70),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),

            // ── BOTTOM PANEL ──────────────────────────────────────────
            Container(
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
              color: isDarkMode
                  ? const Color(0xFF141428)
                  : const Color(0xFFF4F6F8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Price + likes row
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      // Price pill
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 7,
                        ),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF1ABC9C), Color(0xFF16A085)],
                          ),
                          borderRadius: BorderRadius.circular(24),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(
                                0xFF1ABC9C,
                              ).withValues(alpha: 0.3),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.monetization_on_rounded,
                              size: 14,
                              color: Colors.white,
                            ),
                            const SizedBox(width: 5),
                            Text(
                              displayPrice,
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w800,
                                fontSize: 14,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Spacer(),
                      // Likes
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFFE74C3C).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: const Color(
                              0xFFE74C3C,
                            ).withValues(alpha: 0.2),
                          ),
                        ),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.favorite_rounded,
                              color: Color(0xFFE74C3C),
                              size: 14,
                            ),
                            const SizedBox(width: 5),
                            Text(
                              '${project.likes}',
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 12,
                                color: Color(0xFFE74C3C),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  // Description
                  Text(
                    project.description,
                    style: TextStyle(
                      color: isDarkMode ? Colors.white60 : Colors.grey[600],
                      fontSize: 13,
                      height: 1.45,
                    ),
                  ),
                  const SizedBox(height: 10),

                  // Feature chips — horizontal scroll, no wrapping
                  SizedBox(
                    height: 30,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: project.features.length,
                      separatorBuilder: (_, _) => const SizedBox(width: 6),
                      itemBuilder: (_, i) => Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? Colors.white.withValues(alpha: 0.07)
                              : Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: isDarkMode
                                ? Colors.white.withValues(alpha: 0.1)
                                : const Color(
                                    0xFF1ABC9C,
                                  ).withValues(alpha: 0.3),
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(
                              Icons.check_circle_rounded,
                              size: 12,
                              color: Color(0xFF1ABC9C),
                            ),
                            const SizedBox(width: 4),
                            Text(
                              project.features[i],
                              style: TextStyle(
                                color: isDarkMode
                                    ? Colors.white70
                                    : const Color(0xFF2C3E50),
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Action buttons
                  Row(
                    children: [
                      Expanded(
                        child: GestureDetector(
                          onTap: onWhatsApp,
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 13),
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [Color(0xFF25D366), Color(0xFF20B558)],
                              ),
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [
                                BoxShadow(
                                  color: const Color(
                                    0xFF25D366,
                                  ).withValues(alpha: 0.35),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(
                                  Icons.chat_rounded,
                                  color: Colors.white,
                                  size: 16,
                                ),
                                const SizedBox(width: 7),
                                Text(
                                  isSwahili ? 'WhatsApp' : 'WhatsApp',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      GestureDetector(
                        onTap: onCall,
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 20,
                            vertical: 13,
                          ),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Color(0xFF3498DB), Color(0xFF2980B9)],
                            ),
                            borderRadius: BorderRadius.circular(14),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(
                                  0xFF3498DB,
                                ).withValues(alpha: 0.35),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              const Icon(
                                Icons.phone_rounded,
                                color: Colors.white,
                                size: 15,
                              ),
                              const SizedBox(width: 5),
                              Text(
                                callLabel,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
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
          ],
        ),
      ),
    );
  }
}

// ─── Placeholder Image ────────────────────────────────────────────────────────

class _PlaceholderImage extends StatelessWidget {
  final String title;
  final Color color;
  final IconData icon;
  const _PlaceholderImage({
    required this.title,
    required this.color,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [const Color(0xFF2C3E50), color.withValues(alpha: 0.7)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 60, color: Colors.white.withValues(alpha: 0.4)),
            const SizedBox(height: 12),
            Text(
              title,
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.6),
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Stat Badge (CTA section) ─────────────────────────────────────────────────

class _StatBadge extends StatelessWidget {
  final String value;
  final String label;
  const _StatBadge(this.value, this.label);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.w800,
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
}

// ─── Data Model ───────────────────────────────────────────────────────────────

class ProjectShowcase {
  final String image;
  final String title;
  final String priceTZS;
  final String priceUSD;
  final List<String> features;
  final String category;
  final int likes;
  final String timeAgo;
  final String description;
  final bool isFeatured;

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
    this.isFeatured = false,
  });
}
