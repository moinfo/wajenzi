import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/cart_provider.dart';
import '../../providers/settings_provider.dart';
import '../../widgets/curved_bottom_nav.dart';
import '../../widgets/landing_top_bar.dart';

// Format large numbers to abbreviated form
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

class CartScreen extends ConsumerStatefulWidget {
  const CartScreen({super.key});

  @override
  ConsumerState<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends ConsumerState<CartScreen> {
  // Use global settings from provider
  AppLanguage get _language => ref.watch(currentLanguageProvider);
  bool get _isDarkMode => ref.watch(isDarkModeProvider);
  bool get _isSwahili => ref.watch(isSwahiliProvider);
  bool get _isFrench => _language == AppLanguage.french;
  bool get _isArabic => _language == AppLanguage.arabic;

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

  String _tr({
    required String en,
    String? sw,
    String? fr,
    String? ar,
  }) {
    if (_isSwahili) return sw ?? en;
    if (_isFrench) return fr ?? en;
    if (_isArabic) return ar ?? en;
    return en;
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

  // Get formatted price based on language
  String _getFormattedPrice(double priceTZS, double priceUSD) {
    if (_isSwahili) {
      return 'TZS ${_formatNumber(priceTZS)}';
    } else {
      return 'USD ${_formatNumber(priceUSD)}';
    }
  }

  // Launch WhatsApp with cart items
  Future<void> _launchWhatsAppWithCart(List<CartItem> items) async {
    final itemsList = items.map((item) => '- ${item.name}').join('\n');
    final totalPrice = _isSwahili
        ? 'TZS ${_formatNumber(ref.read(cartTotalTZSProvider))}'
        : 'USD ${_formatNumber(ref.read(cartTotalUSDProvider))}';

    final message = _tr(
      en:
          'Hello! I am interested in learning more about the following projects:\n\n$itemsList\n\nTotal Value: $totalPrice',
      sw:
          'Habari! Napenda kupata taarifa zaidi kuhusu miradi ifuatayo:\n\n$itemsList\n\nJumla: $totalPrice',
      fr:
          'Bonjour ! Je souhaite en savoir plus sur les projets suivants :\n\n$itemsList\n\nValeur totale : $totalPrice',
      ar:
          'مرحبًا! أرغب في معرفة المزيد عن المشاريع التالية:\n\n$itemsList\n\nالقيمة الإجمالية: $totalPrice',
    );

    final opened = await ExternalLauncherService.openWhatsApp(message);

    if (!opened) {
      if (mounted) {
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
  }

  @override
  Widget build(BuildContext context) {
    final cartState = ref.watch(cartProvider);
    final cartNotifier = ref.read(cartProvider.notifier);

    return Scaffold(
      backgroundColor: _bgColor,
      extendBody: true,
      appBar: LandingTopBar(
        isDarkMode: _isDarkMode,
        language: _language,
        onDarkModeToggle: () =>
            ref.read(settingsProvider.notifier).toggleDarkMode(),
        onLanguageToggle: () =>
            ref.read(settingsProvider.notifier).toggleLanguage(),
        flagWidget: _languageFlag(),
      ),
      body: cartState.items.isEmpty
          ? _buildEmptyCart()
          : _buildCartContent(cartState, cartNotifier),
      bottomNavigationBar: CurvedBottomNav(
        selectedIndex: 0, // Cart is not in main nav, default to Home
        isDarkMode: _isDarkMode,
        isSwahili: _isSwahili,
        language: _language,
      ),
    );
  }

  Widget _buildEmptyCart() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 120,
            height: 120,
            decoration: BoxDecoration(
              color: const Color(0xFF1ABC9C).withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.shopping_cart_outlined,
              size: 60,
              color: Color(0xFF1ABC9C),
            ),
          ),
          const SizedBox(height: 24),
          Text(
            _tr(
              en: 'Your Cart is Empty',
              sw: 'Kikapu Kitupu',
              fr: 'Votre panier est vide',
              ar: 'سلة التسوق فارغة',
            ),
            style: TextStyle(
              color: _textPrimaryColor,
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            _tr(
              en: 'Add projects you\'re interested in to your cart',
              sw: 'Ongeza miradi unayopenda kwenye kikapu chako',
              fr: 'Ajoutez au panier les projets qui vous interessent',
              ar: 'أضف المشاريع التي تهمك إلى سلتك',
            ),
            style: TextStyle(color: _textSecondaryColor, fontSize: 14),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          ElevatedButton.icon(
            onPressed: () => context.go('/'),
            icon: const Icon(Icons.explore_rounded),
            label: Text(
              _tr(
                en: 'Explore Projects',
                sw: 'Tafuta Miradi',
                fr: 'Explorer les projets',
                ar: 'استكشف المشاريع',
              ),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF1ABC9C),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCartContent(CartState cartState, CartNotifier cartNotifier) {
    return Column(
      children: [
        // Header
        Container(
          padding: const EdgeInsets.all(20),
          child: Row(
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
                    _tr(
                      en: 'Your Cart',
                      sw: 'Kikapu Chako',
                      fr: 'Votre panier',
                      ar: 'سلتك',
                    ),
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    _isSwahili
                        ? '${cartState.itemCount} ${cartState.itemCount == 1 ? 'mradi' : 'miradi'}'
                        : _isFrench
                            ? '${cartState.itemCount} ${cartState.itemCount == 1 ? 'projet' : 'projets'}'
                            : _isArabic
                                ? '${cartState.itemCount} ${cartState.itemCount == 1 ? 'مشروع' : 'مشاريع'}'
                                : '${cartState.itemCount} ${cartState.itemCount == 1 ? 'project' : 'projects'}',
                    style: TextStyle(color: _textSecondaryColor, fontSize: 14),
                  ),
                ],
              ),
              const Spacer(),
              if (cartState.items.isNotEmpty)
                TextButton.icon(
                  onPressed: () {
                    showDialog(
                      context: context,
                      builder: (context) => AlertDialog(
                        backgroundColor: _cardBgColor,
                        title: Text(
                          _tr(
                            en: 'Clear Cart?',
                            sw: 'Futa Kikapu?',
                            fr: 'Vider le panier ?',
                            ar: 'مسح السلة؟',
                          ),
                          style: TextStyle(color: _textPrimaryColor),
                        ),
                        content: Text(
                          _tr(
                            en:
                                'Are you sure you want to remove all projects from your cart?',
                            sw:
                                'Una uhakika unataka kufuta miradi yote kwenye kikapu?',
                            fr:
                                'Voulez-vous vraiment supprimer tous les projets de votre panier ?',
                            ar:
                                'هل أنت متأكد أنك تريد إزالة جميع المشاريع من سلتك؟',
                          ),
                          style: TextStyle(color: _textSecondaryColor),
                        ),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(context),
                            child: Text(
                              _tr(
                                en: 'Cancel',
                                sw: 'Hapana',
                                fr: 'Annuler',
                                ar: 'إلغاء',
                              ),
                              style: const TextStyle(color: Color(0xFF7F8C8D)),
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              cartNotifier.clearCart();
                              Navigator.pop(context);
                            },
                            child: Text(
                              _tr(
                                en: 'Clear',
                                sw: 'Futa',
                                fr: 'Vider',
                                ar: 'مسح',
                              ),
                              style: const TextStyle(color: Color(0xFFE74C3C)),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                  icon: const Icon(
                    Icons.delete_outline,
                    color: Color(0xFFE74C3C),
                    size: 20,
                  ),
                  label: Text(
                    _tr(
                      en: 'Clear All',
                      sw: 'Futa Yote',
                      fr: 'Tout vider',
                      ar: 'مسح الكل',
                    ),
                    style: const TextStyle(
                      color: Color(0xFFE74C3C),
                      fontSize: 12,
                    ),
                  ),
                ),
            ],
          ),
        ),

        // Cart items list
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: cartState.items.length,
            itemBuilder: (context, index) {
              final item = cartState.items[index];
              return _buildCartItem(item, cartNotifier);
            },
          ),
        ),

        // Bottom summary & checkout
        Container(
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 110),
          decoration: BoxDecoration(
            color: _cardBgColor,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 20,
                offset: const Offset(0, -5),
              ),
            ],
          ),
          child: Column(
            children: [
              // Total
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    _tr(
                      en: 'Total Value',
                      sw: 'Jumla ya Thamani',
                      fr: 'Valeur totale',
                      ar: 'القيمة الإجمالية',
                    ),
                    style: TextStyle(color: _textSecondaryColor, fontSize: 14),
                  ),
                  Text(
                    _isSwahili
                        ? 'TZS ${_formatNumber(cartState.totalTZS)}'
                        : 'USD ${_formatNumber(cartState.totalUSD)}',
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              // WhatsApp Inquiry Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _launchWhatsAppWithCart(cartState.items),
                  icon: const Icon(Icons.chat_rounded, size: 22),
                  label: Text(
                    _tr(
                      en: 'Ask via WhatsApp',
                      sw: 'Uliza kupitia WhatsApp',
                      fr: 'Demander via WhatsApp',
                      ar: 'اسأل عبر واتساب',
                    ),
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
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildCartItem(CartItem item, CartNotifier cartNotifier) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: _cardBgColor,
        borderRadius: BorderRadius.circular(16),
        border: _isDarkMode
            ? Border.all(color: const Color(0xFF1ABC9C).withValues(alpha: 0.2))
            : null,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          // Image
          ClipRRect(
            borderRadius: const BorderRadius.horizontal(
              left: Radius.circular(16),
            ),
            child: SizedBox(
              width: 100,
              height: 100,
              child: Image.asset(
                item.image,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => Container(
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      colors: [Color(0xFF1ABC9C), Color(0xFF16A085)],
                    ),
                  ),
                  child: const Icon(
                    Icons.business_rounded,
                    size: 40,
                    color: Colors.white38,
                  ),
                ),
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
                  Text(
                    item.name,
                    style: TextStyle(
                      color: _textPrimaryColor,
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item.category,
                    style: const TextStyle(
                      color: Color(0xFF1ABC9C),
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1ABC9C).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      _getFormattedPrice(item.priceTZS, item.priceUSD),
                      style: const TextStyle(
                        color: Color(0xFF1ABC9C),
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          // Remove button
          IconButton(
            onPressed: () => cartNotifier.removeItem(item.id),
            icon: Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: const Color(0xFFE74C3C).withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.close_rounded,
                color: Color(0xFFE74C3C),
                size: 18,
              ),
            ),
          ),
          const SizedBox(width: 8),
        ],
      ),
    );
  }
}
