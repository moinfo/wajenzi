import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';

import '../config/app_config.dart';

class ExternalLauncherService {
  const ExternalLauncherService._();

  static LaunchMode get _defaultMode =>
      kIsWeb ? LaunchMode.platformDefault : LaunchMode.externalApplication;
  static LaunchMode get _inAppMode =>
      kIsWeb ? LaunchMode.platformDefault : LaunchMode.inAppBrowserView;

  static Future<bool> openUri(Uri uri) {
    return launchUrl(uri, mode: _defaultMode);
  }

  static Future<bool> openUriInApp(Uri uri) {
    return launchUrl(uri, mode: _inAppMode);
  }

  static Future<bool> openPortalPath(String path) {
    return openUri(Uri.parse(AppConfig.portalUrl(path)));
  }

  static Future<bool> openPortalPathInApp(String path) {
    return openUriInApp(Uri.parse(AppConfig.portalUrl(path)));
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
    final phone = AppConfig.whatsAppNumber.trim();
    if (phone.isEmpty) {
      return openPortalPath(fallbackPath);
    }

    final encodedMessage = Uri.encodeComponent(message);
    return openUri(Uri.parse('https://wa.me/$phone?text=$encodedMessage'));
  }

  static Future<bool> callCompany({String fallbackPath = '/services'}) {
    final phone = AppConfig.companyPhoneNumber.trim();
    if (phone.isEmpty) {
      return openPortalPath(fallbackPath);
    }

    return openUri(Uri.parse('tel:$phone'));
  }
}
