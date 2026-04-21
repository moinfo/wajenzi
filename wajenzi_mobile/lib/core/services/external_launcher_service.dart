import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../config/app_config.dart';

class ExternalLauncherService {
  const ExternalLauncherService._();

  static LaunchMode get _defaultMode =>
      kIsWeb ? LaunchMode.platformDefault : LaunchMode.externalApplication;
  static LaunchMode get _inAppMode =>
      kIsWeb ? LaunchMode.platformDefault : LaunchMode.inAppBrowserView;

  static Future<bool> openUri(Uri uri) {
    return _tryOpenUri(uri, mode: _defaultMode);
  }

  static Future<bool> _tryOpenUri(Uri uri, {LaunchMode? mode}) async {
    try {
      final supported = await canLaunchUrl(uri);
      if (!supported) return false;
      return await launchUrl(uri, mode: mode ?? _defaultMode);
    } on PlatformException {
      return false;
    }
  }

  static Future<bool> openUriInApp(Uri uri) {
    return _tryOpenUri(uri, mode: _inAppMode);
  }

  static Future<bool> openPortalPath(String path) {
    return openUri(Uri.parse(AppConfig.portalUrl(path)));
  }

  static Future<bool> openPortalPathInApp(String path) {
    return openUriInApp(Uri.parse(AppConfig.portalUrl(path)));
  }

  static String _normalizePhoneNumber(String raw) {
    final trimmed = raw.trim();
    if (trimmed.isEmpty) return '';

    final hasLeadingPlus = trimmed.startsWith('+');
    final digitsOnly = trimmed.replaceAll(RegExp(r'[^0-9]'), '');
    if (digitsOnly.isEmpty) return '';

    return hasLeadingPlus ? '+$digitsOnly' : digitsOnly;
  }

  static Future<bool> openMenuUrl(
    String? url, {
    String fallbackPath = '/dashboard',
  }) {
    final normalizedUrl = url?.trim() ?? '';
    if (normalizedUrl.isEmpty) {
      return openPortalPath(fallbackPath);
    }

    final uri = Uri.tryParse(normalizedUrl);
    if (uri == null) {
      return openPortalPath(fallbackPath);
    }

    if (uri.hasScheme) {
      return openUri(uri);
    }

    final normalizedPath = normalizedUrl.startsWith('/')
        ? normalizedUrl
        : '/$normalizedUrl';
    return openPortalPath(normalizedPath);
  }

  static Future<bool> openMenuUrlInApp(
    String? url, {
    String fallbackPath = '/dashboard',
  }) {
    final normalizedUrl = url?.trim() ?? '';
    if (normalizedUrl.isEmpty) {
      return openPortalPathInApp(fallbackPath);
    }

    final uri = Uri.tryParse(normalizedUrl);
    if (uri == null) {
      return openPortalPathInApp(fallbackPath);
    }

    if (uri.hasScheme) {
      return openUriInApp(uri);
    }

    final normalizedPath = normalizedUrl.startsWith('/')
        ? normalizedUrl
        : '/$normalizedUrl';
    return openPortalPathInApp(normalizedPath);
  }

  static Future<bool> openWhatsApp(
    String message, {
    String fallbackPath = '/services',
  }) {
    final configuredPhone = AppConfig.whatsAppNumber.trim().isNotEmpty
        ? AppConfig.whatsAppNumber
        : AppConfig.companyPhoneNumber;
    final phone = _normalizePhoneNumber(configuredPhone);
    if (phone.isEmpty) {
      return openPortalPath(fallbackPath);
    }

    final encodedMessage = Uri.encodeComponent(message);
    final whatsappPhone = phone.replaceFirst('+', '');
    final nativeUri = Uri.parse(
      'whatsapp://send?phone=$whatsappPhone&text=$encodedMessage',
    );
    final waMeUri = Uri.parse(
      'https://wa.me/$whatsappPhone?text=$encodedMessage',
    );
    final webUri = Uri.parse(
      'https://api.whatsapp.com/send?phone=$whatsappPhone&text=$encodedMessage',
    );

    final primaryUri = kIsWeb ? waMeUri : nativeUri;
    return _tryOpenUri(primaryUri, mode: _defaultMode).then((opened) async {
      if (opened) return true;

      final waMeOpened = await openUri(waMeUri);
      if (waMeOpened) return true;

      final webOpened = await openUri(webUri);
      if (webOpened) return true;

      return openPortalPath(fallbackPath);
    });
  }

  static Future<bool> callCompany({String fallbackPath = '/services'}) {
    final phone = _normalizePhoneNumber(AppConfig.companyPhoneNumber);
    if (phone.isEmpty) {
      return openPortalPath(fallbackPath);
    }

    return openUri(Uri.parse('tel:$phone'));
  }
}
