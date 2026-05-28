import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../providers/settings_provider.dart';

/// Tiny i18n helper used across cluster A screens.
///
/// Mirrors how `departments_screen.dart` resolves strings: switch on
/// `AppLanguage`, fallback to English. Pass only the languages you actually
/// have translations for; missing ones fall back to English.
String tr(
  AppLanguage lang, {
  required String en,
  String? sw,
  String? fr,
  String? ar,
}) {
  return switch (lang) {
    AppLanguage.swahili => sw ?? en,
    AppLanguage.french => fr ?? en,
    AppLanguage.arabic => ar ?? en,
    AppLanguage.english => en,
  };
}

/// Normalises errors thrown by Dio / our API into a human-friendly message.
///
/// Looks for `response.data.message` (our standard envelope) then falls back to
/// the error's toString().
String calcErrorMessage(Object error, {AppLanguage lang = AppLanguage.english}) {
  try {
    final dyn = error as dynamic;
    final data = dyn.response?.data;
    if (data is Map) {
      final message = data['message']?.toString();
      if (message != null && message.isNotEmpty) return message;
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
    }
  } catch (_) {}
  final fallback = error.toString();
  if (fallback.startsWith('Exception: ')) return fallback.substring(11);
  return tr(lang,
      en: 'Something went wrong. Please try again.',
      sw: 'Hitilafu imetokea. Tafadhali jaribu tena.',
      fr: 'Une erreur est survenue. Veuillez réessayer.',
      ar: 'حدث خطأ. يرجى المحاولة مرة أخرى.');
}

/// A surface card that respects dark mode.
class CalcCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;
  final bool isDarkMode;
  final Color? color;
  final VoidCallback? onTap;

  const CalcCard({
    super.key,
    required this.child,
    required this.isDarkMode,
    this.padding = const EdgeInsets.all(16),
    this.color,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final bg = color ?? (isDarkMode ? const Color(0xFF2A2A3E) : Colors.white);
    final radius = BorderRadius.circular(14);
    final content = Padding(padding: padding, child: child);
    return Material(
      color: bg,
      borderRadius: radius,
      child: InkWell(
        onTap: onTap,
        borderRadius: radius,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: radius,
            border: Border.all(
              color: isDarkMode ? Colors.white12 : Colors.black12,
              width: 0.5,
            ),
          ),
          child: content,
        ),
      ),
    );
  }
}

/// A two-column key/value row used in calculator breakdowns.
class CalcKvRow extends StatelessWidget {
  final String label;
  final String value;
  final bool emphasised;

  const CalcKvRow({
    super.key,
    required this.label,
    required this.value,
    this.emphasised = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: emphasised ? FontWeight.w700 : FontWeight.w500,
                color: emphasised ? null : Colors.grey[600],
              ),
            ),
          ),
          const SizedBox(width: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: emphasised ? 16 : 13,
              fontWeight: emphasised ? FontWeight.w800 : FontWeight.w600,
              color: emphasised ? AppColors.primary : null,
            ),
          ),
        ],
      ),
    );
  }
}

/// Section header with a subtle accent line, used inside calculator screens.
class CalcSectionTitle extends StatelessWidget {
  final String label;
  final Widget? trailing;
  const CalcSectionTitle({super.key, required this.label, this.trailing});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(2, 16, 2, 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: 3,
            height: 16,
            margin: const EdgeInsets.only(right: 8),
            decoration: BoxDecoration(
              color: AppColors.brandYellow,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                letterSpacing: 0.4,
              ),
            ),
          ),
          ?trailing,
        ],
      ),
    );
  }
}

/// Formats a value in a chosen display currency from a USD base.
String formatFromUsd(
  double usdValue, {
  required String displaySymbol,
  required String displayCode,
  required double displayRatePerUsd,
}) {
  final amt = usdValue * displayRatePerUsd;
  return '$displaySymbol ${_thousands(amt.round())}';
}

/// Formats a TZS base value into the chosen display currency.
String formatFromTzs(
  double tzsValue, {
  required String displayCode,
  required String displaySymbol,
  required double displayRatePerUsd,
  required double tzsRatePerUsd,
}) {
  if (displayCode.toUpperCase() == 'TZS') {
    return 'TZS ${_thousands(tzsValue.round())}';
  }
  if (tzsRatePerUsd <= 0) return '$displaySymbol ${_thousands(tzsValue.round())}';
  final usd = tzsValue / tzsRatePerUsd;
  return formatFromUsd(usd,
      displaySymbol: displaySymbol,
      displayCode: displayCode,
      displayRatePerUsd: displayRatePerUsd);
}

String _thousands(int value) {
  final s = value.toString();
  final buf = StringBuffer();
  for (var i = 0; i < s.length; i++) {
    if (i != 0 && (s.length - i) % 3 == 0) buf.write(',');
    buf.write(s[i]);
  }
  return buf.toString();
}

/// Common loading / error / empty scaffolding used by list screens.
class CalcAsyncBody<T> extends ConsumerWidget {
  final AsyncValue<T> async;
  final Widget Function(T data) builder;
  final VoidCallback onRetry;
  final IconData emptyIcon;
  final String emptyText;
  final bool Function(T data)? isEmpty;

  const CalcAsyncBody({
    super.key,
    required this.async,
    required this.builder,
    required this.onRetry,
    required this.emptyIcon,
    required this.emptyText,
    this.isEmpty,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lang = ref.watch(currentLanguageProvider);
    return async.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline, size: 56, color: Colors.grey[400]),
              const SizedBox(height: 12),
              Text(calcErrorMessage(e, lang: lang), textAlign: TextAlign.center),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: onRetry,
                child: Text(tr(lang, en: 'Retry', sw: 'Jaribu tena', fr: 'Réessayer', ar: 'إعادة')),
              ),
            ],
          ),
        ),
      ),
      data: (data) {
        final empty = (isEmpty?.call(data)) ?? false;
        if (empty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(emptyIcon, size: 64, color: Colors.grey[400]),
                  const SizedBox(height: 12),
                  Text(
                    emptyText,
                    style: TextStyle(fontSize: 15, color: Colors.grey[600]),
                  ),
                ],
              ),
            ),
          );
        }
        return builder(data);
      },
    );
  }
}
