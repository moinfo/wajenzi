import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Fades a widget in while it slides up a little. [index] staggers a list so
/// its rows appear one after another instead of all at once.
class FadeSlideIn extends StatelessWidget {
  final Widget child;
  final int index;
  final Duration duration;
  final double offset;

  const FadeSlideIn({
    super.key,
    required this.child,
    this.index = 0,
    this.duration = const Duration(milliseconds: 380),
    this.offset = 14,
  });

  @override
  Widget build(BuildContext context) {
    // Stagger by stretching the duration for later rows (capped, so long lists
    // do not feel slow).
    final extra = Duration(milliseconds: (index.clamp(0, 8)) * 55);

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: duration + extra,
      curve: Curves.easeOutCubic,
      builder: (context, t, child) => Opacity(
        opacity: t.clamp(0.0, 1.0),
        child: Transform.translate(offset: Offset(0, offset * (1 - t)), child: child),
      ),
      child: child,
    );
  }
}

/// A number that counts up to its value when it appears (and when it changes).
class AnimatedNumber extends StatelessWidget {
  final double value;
  final TextStyle? style;
  final int decimals;
  final Duration duration;

  const AnimatedNumber({
    super.key,
    required this.value,
    this.style,
    this.decimals = 0,
    this.duration = const Duration(milliseconds: 900),
  });

  @override
  Widget build(BuildContext context) {
    final format = NumberFormat(decimals == 0 ? '#,##0' : '#,##0.${'0' * decimals}', 'en');

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: value),
      duration: duration,
      curve: Curves.easeOutCubic,
      builder: (context, v, _) => Text(format.format(v), style: style),
    );
  }
}

/// Cross-fades between loading, content and error states instead of snapping.
class SmoothSwitcher extends StatelessWidget {
  final Widget child;
  const SmoothSwitcher({super.key, required this.child});

  @override
  Widget build(BuildContext context) => AnimatedSwitcher(
        duration: const Duration(milliseconds: 250),
        switchInCurve: Curves.easeOut,
        switchOutCurve: Curves.easeIn,
        child: child,
      );
}
