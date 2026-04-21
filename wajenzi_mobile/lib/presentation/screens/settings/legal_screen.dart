import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../providers/settings_provider.dart';

/// Reusable screen for legal/policy documents.
class LegalScreen extends ConsumerWidget {
  final String _titleEn;
  final String _titleSw;
  final String? _titleFr;
  final String? _titleAr;
  final List<_Section> Function(AppLanguage language) _sections;

  const LegalScreen._({
    required String titleEn,
    required String titleSw,
    String? titleFr,
    String? titleAr,
    required List<_Section> Function(AppLanguage language) sections,
  })  : _titleEn = titleEn,
        _titleSw = titleSw,
        _titleFr = titleFr,
        _titleAr = titleAr,
        _sections = sections;

  static Widget privacyPolicy() => LegalScreen._(
        titleEn: 'Privacy Policy',
        titleSw: 'Sera ya Faragha',
        titleFr: 'Politique de confidentialite',
        titleAr: 'سياسة الخصوصية',
        sections: _privacySections,
      );

  static Widget termsOfService() => LegalScreen._(
        titleEn: 'Terms of Service',
        titleSw: 'Masharti ya Huduma',
        titleFr: 'Conditions d\'utilisation',
        titleAr: 'شروط الخدمة',
        sections: _termsSections,
      );

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final language = ref.watch(currentLanguageProvider);
    final sectionList = _sections(language);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          switch (language) {
            AppLanguage.swahili => _titleSw,
            AppLanguage.french => _titleFr ?? _titleEn,
            AppLanguage.arabic => _titleAr ?? _titleEn,
            AppLanguage.english => _titleEn,
          },
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Header
          Center(
            child: Column(
              children: [
                Image.asset('assets/images/logo.png', height: 48),
                const SizedBox(height: 8),
                const Text(
                  'WAJENZI PROFESSIONAL CO. LTD',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                    letterSpacing: 1,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  switch (language) {
                    AppLanguage.swahili => 'Imesasishwa: Februari 2026',
                    AppLanguage.french => 'Derniere mise a jour: Fevrier 2026',
                    AppLanguage.arabic => 'آخر تحديث: فبراير 2026',
                    AppLanguage.english => 'Last updated: February 2026',
                  },
                  style: TextStyle(fontSize: 12, color: AppColors.textHint),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Sections
          ...sectionList.map((s) => _SectionWidget(section: s)),
        ],
      ),
    );
  }
}

class _Section {
  final String title;
  final String body;
  const _Section(this.title, this.body);
}

class _SectionWidget extends StatelessWidget {
  final _Section section;
  const _SectionWidget({required this.section});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            section.title,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Text(
            section.body,
            style: TextStyle(
              fontSize: 14,
              height: 1.6,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Privacy Policy Content ───────────────────────────

List<_Section> _privacySections(AppLanguage language) => switch (language) {
      AppLanguage.swahili => [
        const _Section(
          '1. Utangulizi',
          'Wajenzi Professional Co. Ltd ("Wajenzi", "sisi") inaheshimu faragha yako. Sera hii inaeleza jinsi tunavyokusanya, kutumia, na kulinda taarifa zako unapotumia programu yetu ya simu na huduma za portal ya mteja.',
        ),
        const _Section(
          '2. Taarifa Tunazokusanya',
          '• Taarifa za kibinafsi: jina, barua pepe, nambari ya simu, anwani\n'
              '• Taarifa za mradi: hati za mradi, picha za maendeleo, ripoti\n'
              '• Taarifa za kifaa: aina ya kifaa, mfumo wa uendeshaji, kitambulisho cha kifaa\n'
              '• Taarifa za matumizi: kurasa unazofungua, vitendo ndani ya programu',
        ),
        const _Section(
          '3. Jinsi Tunavyotumia Taarifa Zako',
          '• Kutoa na kudumisha huduma zetu\n'
              '• Kukutumia taarifa za miradi na ankara\n'
              '• Kuboresha programu yetu na uzoefu wa mtumiaji\n'
              '• Kukutumia arifa kuhusu hali ya mradi\n'
              '• Kutimiza wajibu wetu wa kisheria',
        ),
        const _Section(
          '4. Usalama wa Data',
          'Tunatumia hatua za kiufundi na shirika zinazofaa kulinda taarifa zako za kibinafsi dhidi ya ufikiaji, mabadiliko, ufichuzi, au uharibifu usioidhinishwa. Taarifa zote za mawasiliano zimesimbwa kwa njia fiche.',
        ),
        const _Section(
          '5. Kushiriki Data na Wahusika wa Tatu',
          'Hatushiriki taarifa zako za kibinafsi na wahusika wa tatu isipokuwa: watoa huduma wanaotuwezesha kutoa huduma zetu, tunapohitajika kisheria, au kwa idhini yako ya wazi.',
        ),
        const _Section(
          '6. Haki Zako',
          'Una haki ya kufikia, kurekebisha, au kufuta taarifa zako za kibinafsi wakati wowote kupitia mipangilio ya wasifu wako au kwa kuwasiliana nasi moja kwa moja.',
        ),
        const _Section(
          '7. Wasiliana Nasi',
          'Kwa maswali kuhusu sera hii, tafadhali wasiliana nasi:\nBarua pepe: info@wajenziprofessional.co.tz\nSimu: +255 793 444 400',
        ),
      ]
      ,
      AppLanguage.french => [
        const _Section(
          '1. Introduction',
          'Wajenzi Professional Co. Ltd ("Wajenzi", "nous") respecte votre vie privee. Cette politique explique comment nous collectons, utilisons et protégeons vos informations lorsque vous utilisez notre application mobile et nos services de portail client.',
        ),
        const _Section(
          '2. Informations que nous collectons',
          '• Informations personnelles: nom, e-mail, numero de telephone, adresse\n'
              '• Informations sur le projet: documents, images d\'avancement, rapports\n'
              '• Informations sur l\'appareil: type d\'appareil, systeme d\'exploitation, identifiant\n'
              '• Informations d\'utilisation: pages visitees, actions dans l\'application',
        ),
        const _Section(
          '3. Comment nous utilisons vos informations',
          '• Fournir et maintenir nos services\n'
              '• Vous envoyer les mises a jour de projet et les factures\n'
              '• Ameliorer notre application et l\'experience utilisateur\n'
              '• Vous envoyer des notifications sur l\'etat du projet\n'
              '• Respecter nos obligations legales',
        ),
        const _Section(
          '4. Securite des donnees',
          'Nous utilisons des mesures techniques et organisationnelles appropriees pour proteger vos informations personnelles contre tout acces, modification, divulgation ou destruction non autorises. Toutes les communications sont chiffrees en transit.',
        ),
        const _Section(
          '5. Partage des donnees avec des tiers',
          'Nous ne partageons pas vos informations personnelles avec des tiers sauf avec les prestataires qui nous aident a fournir nos services, lorsque la loi l\'exige, ou avec votre consentement explicite.',
        ),
        const _Section(
          '6. Vos droits',
          'Vous avez le droit d\'acceder, de corriger ou de supprimer vos informations personnelles a tout moment via les parametres de votre profil ou en nous contactant directement.',
        ),
        const _Section(
          '7. Contactez-nous',
          'Pour toute question concernant cette politique, veuillez nous contacter:\nE-mail: info@wajenziprofessional.co.tz\nTelephone: +255 793 444 400',
        ),
      ],
      _ => [
        const _Section(
          '1. Introduction',
          'Wajenzi Professional Co. Ltd ("Wajenzi", "we") respects your privacy. This policy explains how we collect, use, and protect your information when you use our mobile application and client portal services.',
        ),
        const _Section(
          '2. Information We Collect',
          '• Personal information: name, email, phone number, address\n'
              '• Project information: project documents, progress images, reports\n'
              '• Device information: device type, operating system, device identifier\n'
              '• Usage information: pages you visit, actions within the app',
        ),
        const _Section(
          '3. How We Use Your Information',
          '• To provide and maintain our services\n'
              '• To send you project updates and invoices\n'
              '• To improve our app and user experience\n'
              '• To send you notifications about project status\n'
              '• To comply with our legal obligations',
        ),
        const _Section(
          '4. Data Security',
          'We use appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. All communications are encrypted in transit.',
        ),
        const _Section(
          '5. Third-Party Data Sharing',
          'We do not share your personal information with third parties except: service providers who help us deliver our services, when required by law, or with your explicit consent.',
        ),
        const _Section(
          '6. Your Rights',
          'You have the right to access, correct, or delete your personal information at any time through your profile settings or by contacting us directly.',
        ),
        const _Section(
          '7. Contact Us',
          'For questions about this policy, please contact us:\nEmail: info@wajenziprofessional.co.tz\nPhone: +255 793 444 400',
        ),
      ],
    };

// ─── Terms of Service Content ─────────────────────────

List<_Section> _termsSections(AppLanguage language) => switch (language) {
      AppLanguage.swahili => [
        const _Section(
          '1. Kukubali Masharti',
          'Kwa kutumia programu ya Wajenzi, unakubali masharti haya ya huduma. Ikiwa hukubaliani na masharti haya, tafadhali usitumie programu yetu.',
        ),
        const _Section(
          '2. Maelezo ya Huduma',
          'Programu ya Wajenzi inakupa fursa ya kufuatilia miradi yako ya ujenzi, kuangalia maendeleo, kupitia ankara na malipo, kuona nyaraka, na kuwasiliana na timu ya mradi wako.',
        ),
        const _Section(
          '3. Akaunti ya Mtumiaji',
          'Unahusika na kudumisha usalama wa akaunti yako na nenosiri lako. Tuzo yoyote inayofanywa chini ya akaunti yako ni jukumu lako. Tafadhali tutaarifu mara moja kuhusu matumizi yoyote yasiyoidhinishwa.',
        ),
        const _Section(
          '4. Matumizi Yanayokubalika',
          '• Hutashiriki taarifa za kuingia kwako na mtu mwingine\n'
              '• Hutajaribu kufikia maeneo yasiyoidhinishwa ya mfumo\n'
              '• Hutatumia programu kwa madhumuni yoyote yasiyokuwa halali\n'
              '• Hutaingilia kati au kuharibu utendaji wa programu',
        ),
        const _Section(
          '5. Miliki ya Maudhui',
          'Nyaraka za mradi, picha, na ripoti zilizoshirikiwa kupitia programu ni mali ya wahusika husika. Wajenzi haidai umiliki wa maudhui yako lakini inaweza kutumia kwa kutoa huduma.',
        ),
        const _Section(
          '6. Ukomo wa Dhima',
          'Wajenzi Professional Co. Ltd haitawajibika kwa hasara zozote zisizo za moja kwa moja, za bahati mbaya, maalum, au za matokeo zinazotokana na matumizi yako ya programu.',
        ),
        const _Section(
          '7. Mabadiliko ya Masharti',
          'Tunaweza kusasisha masharti haya mara kwa mara. Tutakuarifu kuhusu mabadiliko yoyote muhimu kupitia programu au barua pepe.',
        ),
        const _Section(
          '8. Sheria Inayotumika',
          'Masharti haya yanatafsiriwa kulingana na sheria za Jamhuri ya Muungano wa Tanzania.',
        ),
      ]
      ,
      AppLanguage.french => [
        const _Section(
          '1. Acceptation des conditions',
          'En utilisant l\'application Wajenzi, vous acceptez ces conditions d\'utilisation. Si vous n\'etes pas d\'accord avec ces conditions, veuillez ne pas utiliser notre application.',
        ),
        const _Section(
          '2. Description du service',
          'L\'application Wajenzi vous permet de suivre vos projets de construction, de surveiller l\'avancement, de consulter les factures et paiements, de voir les documents et de communiquer avec votre equipe de projet.',
        ),
        const _Section(
          '3. Compte utilisateur',
          'Vous etes responsable de la securite de votre compte et de votre mot de passe. Toute activite effectuee sous votre compte releve de votre responsabilite. Veuillez nous signaler immediatement toute utilisation non autorisee.',
        ),
        const _Section(
          '4. Utilisation acceptable',
          '• Vous ne partagerez pas vos identifiants de connexion\n'
              '• Vous ne tenterez pas d\'acceder a des zones non autorisees du systeme\n'
              '• Vous n\'utiliserez pas l\'application a des fins illegales\n'
              '• Vous ne perturberez pas le fonctionnement de l\'application',
        ),
        const _Section(
          '5. Propriete du contenu',
          'Les documents, images et rapports de projet partages via l\'application restent la propriete de leurs proprietaires respectifs. Wajenzi ne revendique pas la propriete de votre contenu mais peut l\'utiliser pour fournir ses services.',
        ),
        const _Section(
          '6. Limitation de responsabilite',
          'Wajenzi Professional Co. Ltd ne pourra etre tenu responsable des dommages indirects, accessoires, speciaux ou consecutifs decoulant de votre utilisation de l\'application.',
        ),
        const _Section(
          '7. Modifications des conditions',
          'Nous pouvons mettre a jour ces conditions de temps a autre. Nous vous informerons de tout changement important via l\'application ou par e-mail.',
        ),
        const _Section(
          '8. Droit applicable',
          'Ces conditions sont regies et interpretees conformement aux lois de la Republique-Unie de Tanzanie.',
        ),
      ],
      _ => [
        const _Section(
          '1. Acceptance of Terms',
          'By using the Wajenzi app, you agree to these terms of service. If you do not agree with these terms, please do not use our application.',
        ),
        const _Section(
          '2. Service Description',
          'The Wajenzi app provides you with the ability to track your construction projects, monitor progress, review invoices and payments, view documents, and communicate with your project team.',
        ),
        const _Section(
          '3. User Account',
          'You are responsible for maintaining the security of your account and password. Any activity performed under your account is your responsibility. Please notify us immediately of any unauthorized use.',
        ),
        const _Section(
          '4. Acceptable Use',
          '• You will not share your login credentials with anyone\n'
              '• You will not attempt to access unauthorized areas of the system\n'
              '• You will not use the app for any unlawful purposes\n'
              '• You will not interfere with or disrupt the app\'s functionality',
        ),
        const _Section(
          '5. Content Ownership',
          'Project documents, images, and reports shared through the app remain the property of their respective owners. Wajenzi does not claim ownership of your content but may use it to provide services.',
        ),
        const _Section(
          '6. Limitation of Liability',
          'Wajenzi Professional Co. Ltd shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the application.',
        ),
        const _Section(
          '7. Changes to Terms',
          'We may update these terms from time to time. We will notify you of any significant changes through the app or email.',
        ),
        const _Section(
          '8. Governing Law',
          'These terms are governed by and construed in accordance with the laws of the United Republic of Tanzania.',
        ),
      ],
    };
