// ignore_for_file: use_build_context_synchronously
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import 'landing_admin_shared.dart';

const String _endpoint = '/landing-admin/about';

/// Landing CMS admin — About section (singleton).
/// GET `/landing-admin/about` returns one record; PUT updates it. Localized
/// fields (tagline, story, mission, vision, working_hours) are stored per
/// language via the `lang` query param; plain fields (address, phone, email,
/// founded_year) are not localized.
class AboutAdminScreen extends ConsumerStatefulWidget {
  const AboutAdminScreen({super.key});

  @override
  ConsumerState<AboutAdminScreen> createState() => _AboutAdminScreenState();
}

class _AboutAdminScreenState extends ConsumerState<AboutAdminScreen> {
  final _formKey = GlobalKey<FormState>();
  final _foundedYear = TextEditingController();
  final _tagline = TextEditingController();
  final _story = TextEditingController();
  final _mission = TextEditingController();
  final _vision = TextEditingController();
  final _address = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _workingHours = TextEditingController();

  bool _submitting = false;
  bool _hydrated = false;
  String? _hydratedFor; // language tag we hydrated for (en|sw)
  String? _lastSavedIso;

  @override
  void dispose() {
    _foundedYear.dispose();
    _tagline.dispose();
    _story.dispose();
    _mission.dispose();
    _vision.dispose();
    _address.dispose();
    _phone.dispose();
    _email.dispose();
    _workingHours.dispose();
    super.dispose();
  }

  void _hydrate(Map<String, dynamic> data, String lang) {
    _foundedYear.text = data['founded_year']?.toString() ?? '';
    _tagline.text = data['tagline']?.toString() ?? '';
    _story.text = data['story']?.toString() ?? '';
    _mission.text = data['mission']?.toString() ?? '';
    _vision.text = data['vision']?.toString() ?? '';
    _address.text = data['address']?.toString() ?? '';
    _phone.text = data['phone']?.toString() ?? '';
    _email.text = data['email']?.toString() ?? '';
    _workingHours.text = data['working_hours']?.toString() ?? '';
    _lastSavedIso = data['updated_at']?.toString();
    _hydrated = true;
    _hydratedFor = lang;
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final lang = isSwahili ? 'sw' : 'en';
    final key = landingKey(_endpoint, isSwahili);
    final asyncData = ref.watch(landingAdminObjectProvider(key));
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);

    // Re-hydrate the controllers if the language switched (or first load).
    asyncData.whenData((data) {
      if (!_hydrated || _hydratedFor != lang) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) setState(() => _hydrate(data, lang));
        });
      }
    });

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Kuhusu' : 'About'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          _hydrated = false;
          ref.invalidate(landingAdminObjectProvider(key));
        },
        child: asyncData.when(
          loading: () =>
              const Center(child: CircularProgressIndicator()),
          error: (e, _) => ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(24),
            children: [
              const SizedBox(height: 80),
              Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
              const SizedBox(height: 16),
              Text(
                landingAdminErrorMessage(e),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Center(
                child: ElevatedButton(
                  onPressed: () =>
                      ref.invalidate(landingAdminObjectProvider(key)),
                  child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
                ),
              ),
            ],
          ),
          data: (_) => _form(context, isSwahili, isDarkMode, lang, key),
        ),
      ),
    );
  }

  Widget _form(
    BuildContext context,
    bool isSwahili,
    bool isDarkMode,
    String lang,
    LandingListKey key,
  ) {
    return Form(
      key: _formKey,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDarkMode
                  ? const Color(0xFF2A2A3E)
                  : AppColors.brandYellow.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                const Icon(Icons.info_outline, color: AppColors.brandBlue),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    isSwahili
                        ? 'Maandishi yatahifadhiwa kwa lugha ya $lang. Anwani, simu na barua pepe ni sawa kwa lugha zote.'
                        : 'Localized text saves under "$lang". Address, phone and email are language-neutral.',
                    style: const TextStyle(fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          _sectionHeader(
            isSwahili ? 'Maelezo Makuu' : 'Headline',
            isDarkMode,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _foundedYear,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Mwaka wa kuanzishwa' : 'Founded year',
              border: const OutlineInputBorder(),
            ),
            keyboardType: TextInputType.number,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _tagline,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Kauli mbiu' : 'Tagline',
              border: const OutlineInputBorder(),
            ),
            maxLines: 2,
          ),
          const SizedBox(height: 24),
          _sectionHeader(
            isSwahili ? 'Hadithi yetu' : 'Our Story',
            isDarkMode,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _story,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Hadithi' : 'Story',
              border: const OutlineInputBorder(),
              alignLabelWithHint: true,
            ),
            maxLines: 6,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _mission,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Dhima' : 'Mission',
              border: const OutlineInputBorder(),
              alignLabelWithHint: true,
            ),
            maxLines: 4,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _vision,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Maono' : 'Vision',
              border: const OutlineInputBorder(),
              alignLabelWithHint: true,
            ),
            maxLines: 4,
          ),
          const SizedBox(height: 24),
          _sectionHeader(
            isSwahili ? 'Mawasiliano' : 'Contact',
            isDarkMode,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _address,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Anwani' : 'Address',
              border: const OutlineInputBorder(),
            ),
            maxLines: 2,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _phone,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Simu' : 'Phone',
              border: const OutlineInputBorder(),
            ),
            keyboardType: TextInputType.phone,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _email,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Barua pepe' : 'Email',
              border: const OutlineInputBorder(),
            ),
            keyboardType: TextInputType.emailAddress,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _workingHours,
            decoration: InputDecoration(
              labelText: isSwahili ? 'Masaa ya kazi' : 'Working hours',
              border: const OutlineInputBorder(),
            ),
            maxLines: 2,
          ),
          const SizedBox(height: 24),
          if (_lastSavedIso != null) ...[
            Text(
              isSwahili
                  ? 'Imehifadhiwa mwisho: ${_formatTimestamp(_lastSavedIso!)}'
                  : 'Last saved: ${_formatTimestamp(_lastSavedIso!)}',
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontSize: 12,
              ),
            ),
            const SizedBox(height: 12),
          ],
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton.icon(
              onPressed: _submitting ? null : () => _submit(key, lang),
              icon: _submitting
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.save_rounded),
              label: Text(
                _submitting
                    ? (isSwahili ? 'Inahifadhi...' : 'Saving...')
                    : (isSwahili ? 'Hifadhi' : 'Save'),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionHeader(String text, bool isDarkMode) {
    return Row(
      children: [
        Container(
          width: 4,
          height: 20,
          decoration: BoxDecoration(
            color: AppColors.brandYellow,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 8),
        Text(
          text,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: isDarkMode ? Colors.white : AppColors.brandBlue,
          ),
        ),
      ],
    );
  }

  String _formatTimestamp(String iso) {
    try {
      final dt = DateTime.parse(iso).toLocal();
      final y = dt.year.toString().padLeft(4, '0');
      final m = dt.month.toString().padLeft(2, '0');
      final d = dt.day.toString().padLeft(2, '0');
      final hh = dt.hour.toString().padLeft(2, '0');
      final mm = dt.minute.toString().padLeft(2, '0');
      return '$y-$m-$d $hh:$mm';
    } catch (_) {
      return iso;
    }
  }

  Future<void> _submit(LandingListKey key, String lang) async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    final isSwahili = ref.read(isSwahiliProvider);

    final payload = <String, dynamic>{
      'founded_year': _foundedYear.text.trim(),
      'tagline': _tagline.text.trim(),
      'story': _story.text.trim(),
      'mission': _mission.text.trim(),
      'vision': _vision.text.trim(),
      'address': _address.text.trim(),
      'phone': _phone.text.trim(),
      'email': _email.text.trim(),
      'working_hours': _workingHours.text.trim(),
      'lang': lang,
    };

    try {
      final api = ref.read(apiClientProvider);
      await api.put(_endpoint, data: payload);
      if (!mounted) return;
      _hydrated = false;
      ref.invalidate(landingAdminObjectProvider(key));
      showLandingSnack(
        context,
        isSwahili ? 'Imehifadhiwa' : 'About section saved',
      );
    } catch (e) {
      if (!mounted) return;
      showLandingSnack(context, landingAdminErrorMessage(e), error: true);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }
}
