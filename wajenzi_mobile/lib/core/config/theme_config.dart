import 'package:flutter/cupertino.dart' show CupertinoPageTransitionsBuilder;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Brand typography. Body = Plus Jakarta Sans (refined grotesque),
/// display/headings = Sora (geometric, architectural). Distinctive, on-theme.
class AppType {
  static TextStyle display(
    double size, {
    FontWeight weight = FontWeight.w800,
    Color? color,
    double letterSpacing = -0.5,
    double? height,
  }) =>
      GoogleFonts.sora(
        fontSize: size,
        fontWeight: weight,
        color: color,
        letterSpacing: letterSpacing,
        height: height,
      );

  static TextTheme body(TextTheme base) =>
      GoogleFonts.plusJakartaSansTextTheme(base);
}

class AppColors {
  // ── Official Wajenzi brand palette ──────────────────────────────────
  static const Color brandBlue = Color(0xFF193340); // Dark blue (primary)
  static const Color brandGreen = Color(0xFF3BA154); // Green (accent/success)
  static const Color brandYellow = Color(0xFFFECC04); // Yellow (highlight)

  // Primary = brand dark blue (app bars, buttons, chrome)
  static const Color primary = brandBlue;
  static const Color primaryDark = Color(0xFF122833);
  static const Color primaryLight = Color(0xFF2A4A5A);

  // Gradient Colors (brand dark-blue → green)
  static const Color gradientStart = brandBlue;
  static const Color gradientEnd = brandGreen;

  // Secondary = brand green (accents, secondary actions)
  static const Color secondary = brandGreen;
  static const Color secondaryDark = Color(0xFF2E8043);
  static const Color secondaryLight = Color(0xFF5BC077);

  // Neutral Colors
  static const Color background = Color(0xFFF8F9FA);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color error = Color(0xFFE74C3C);
  static const Color success = brandGreen;
  static const Color warning = brandYellow;
  static const Color info = brandBlue;

  // Text Colors
  static const Color textPrimary = brandBlue;
  static const Color textSecondary = Color(0xFF7F8C8D);
  static const Color textHint = Color(0xFFBDC3C7);

  // ── Dark-mode safe colors ───────────────────────────────────────────
  // The brand navy is the app-bar color; as text or icon color it vanishes on
  // the dark surfaces, so screens ask for these when they need a color that
  // reads in the current mode.
  static const Color darkText = Color(0xFFE8E8F0);
  static const Color darkTextSoft = Color(0xFFB4BED0);
  static const Color darkAccent = Color(0xFF7CC4E8);

  /// Main text color: navy on light, near-white on dark.
  static Color text(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark ? darkText : textPrimary;

  /// Secondary text color that stays readable on both.
  static Color textSoft(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark ? darkTextSoft : textSecondary;

  /// Accent for icons and links: brand navy on light, light blue on dark.
  static Color accent(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark ? darkAccent : primary;

  // Status Colors
  static const Color draft = Color(0xFF95A5A6);
  static const Color pending = brandYellow;
  static const Color approved = brandGreen;
  static const Color rejected = Color(0xFFE74C3C);

  // Gradient
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [gradientStart, gradientEnd],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );

  static const LinearGradient backgroundGradient = LinearGradient(
    colors: [brandBlue, brandGreen],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}

class AppTheme {
  static ThemeData get lightTheme => _build(Brightness.light);
  static ThemeData get darkTheme => _build(Brightness.dark);

  static ThemeData _build(Brightness brightness) {
    final dark = brightness == Brightness.dark;

    // Dark surfaces and the text colors that read on them.
    const surface = Color(0xFF1E1E2E);
    const surfaceVariant = Color(0xFF252537);
    const outlineDark = Color(0xFF3A3A50);

    final primary = dark ? AppColors.darkAccent : AppColors.primary;
    final onSurface = dark ? AppColors.darkText : AppColors.textPrimary;
    final softText = dark ? AppColors.darkTextSoft : AppColors.textSecondary;
    final cardColor = dark ? surface : AppColors.surface;
    final fieldFill = dark ? surfaceVariant : Colors.white;
    final fieldBorder = dark ? outlineDark : AppColors.textHint.withValues(alpha: 0.6);
    final divider = dark ? const Color(0xFF343448) : const Color(0xFFE3E8F0);

    final scheme = ColorScheme.fromSeed(
      seedColor: AppColors.primary,
      brightness: brightness,
      primary: primary,
      onPrimary: dark ? const Color(0xFF0B2230) : Colors.white,
      secondary: AppColors.secondary,
      error: AppColors.error,
      surface: dark ? surface : null,
      onSurface: dark ? AppColors.darkText : null,
    );

    OutlineInputBorder border(Color c, [double w = 1]) => OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: c, width: w),
        );

    final baseText = AppType.body(
      TextTheme(
        headlineLarge: TextStyle(fontWeight: FontWeight.bold, color: onSurface),
        headlineMedium: TextStyle(fontWeight: FontWeight.bold, color: onSurface),
        titleLarge: TextStyle(fontWeight: FontWeight.w600, color: onSurface),
        bodyLarge: TextStyle(color: onSurface),
        bodyMedium: TextStyle(color: dark ? const Color(0xFFC4C4DA) : AppColors.textSecondary),
      ),
    );
    // Every other style (titles, labels, captions) follows the mode too.
    final textTheme = baseText.apply(
      bodyColor: onSurface,
      displayColor: onSurface,
    ).copyWith(
      bodyMedium: baseText.bodyMedium,
      bodySmall: baseText.bodySmall?.copyWith(color: softText),
    );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: dark ? const Color(0xFF13131F) : AppColors.background,
      dividerColor: divider,
      dividerTheme: DividerThemeData(color: divider, thickness: 1, space: 1),
      // Screens slide and fade into each other instead of snapping.
      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: FadeUpwardsPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
        },
      ),
      appBarTheme: const AppBarTheme(
        centerTitle: true,
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(bottom: Radius.circular(16)),
        ),
      ),
      cardTheme: CardThemeData(
        elevation: dark ? 0 : 1.5,
        color: cardColor,
        surfaceTintColor: Colors.transparent,
        shadowColor: const Color(0x1A193340),
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: BorderSide(color: divider),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          backgroundColor: dark ? const Color(0xFF2F7FA8) : AppColors.primary,
          foregroundColor: Colors.white,
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          backgroundColor: dark ? const Color(0xFF2F7FA8) : AppColors.primary,
          foregroundColor: Colors.white,
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          foregroundColor: dark ? AppColors.darkAccent : AppColors.primary,
          side: BorderSide(color: dark ? const Color(0xFF4B6A80) : AppColors.primary.withValues(alpha: 0.35)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: dark ? AppColors.darkAccent : AppColors.primary,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: fieldFill,
        border: border(fieldBorder),
        enabledBorder: border(fieldBorder),
        focusedBorder: border(primary, 2),
        errorBorder: border(AppColors.error),
        focusedErrorBorder: border(AppColors.error, 2),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        // Labels, hints and icons that stay readable on the dark fields.
        labelStyle: TextStyle(color: softText),
        floatingLabelStyle: TextStyle(color: primary),
        hintStyle: TextStyle(color: dark ? const Color(0xFF8792A8) : AppColors.textHint),
        prefixIconColor: softText,
        suffixIconColor: softText,
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: dark ? const Color(0xFF2F7FA8) : AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 3,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      listTileTheme: ListTileThemeData(
        iconColor: softText,
        textColor: onSurface,
        subtitleTextStyle: TextStyle(color: softText, fontSize: 13),
      ),
      iconTheme: IconThemeData(color: dark ? AppColors.darkTextSoft : null),
      expansionTileTheme: ExpansionTileThemeData(
        backgroundColor: cardColor,
        collapsedBackgroundColor: cardColor,
        iconColor: onSurface,
        collapsedIconColor: softText,
        textColor: onSurface,
        collapsedTextColor: onSurface,
        shape: const Border(),
        collapsedShape: const Border(),
      ),
      chipTheme: ChipThemeData(
        backgroundColor: dark ? surfaceVariant : const Color(0xFFEFF3F8),
        labelStyle: TextStyle(color: onSurface),
        side: BorderSide(color: divider),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      ),
      dataTableTheme: DataTableThemeData(
        dataTextStyle: TextStyle(color: onSurface, fontSize: 13),
        dividerThickness: 1,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: dark ? surfaceVariant : Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      ),
      datePickerTheme: DatePickerThemeData(
        backgroundColor: dark ? surfaceVariant : Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        headerBackgroundColor: dark ? surface : AppColors.primary,
        headerForegroundColor: dark ? AppColors.darkText : Colors.white,
        dayForegroundColor: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) return scheme.onPrimary;
          if (states.contains(WidgetState.disabled)) return softText.withValues(alpha: 0.4);
          return onSurface;
        }),
        weekdayStyle: TextStyle(color: softText, fontWeight: FontWeight.w600),
        yearForegroundColor: WidgetStatePropertyAll(onSurface),
        todayForegroundColor: WidgetStatePropertyAll(primary),
        todayBorder: BorderSide(color: primary),
        rangeSelectionBackgroundColor: primary.withValues(alpha: 0.25),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: dark ? surfaceVariant : Colors.white,
        surfaceTintColor: Colors.transparent,
        showDragHandle: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: dark ? const Color(0xFF30304A) : const Color(0xFF263B47),
        contentTextStyle: const TextStyle(color: Colors.white),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      popupMenuTheme: PopupMenuThemeData(
        color: dark ? surfaceVariant : Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
      progressIndicatorTheme: ProgressIndicatorThemeData(color: primary),
      switchTheme: SwitchThemeData(
        thumbColor: WidgetStateProperty.resolveWith(
          (s) => s.contains(WidgetState.selected) ? Colors.white : softText,
        ),
        trackColor: WidgetStateProperty.resolveWith(
          (s) => s.contains(WidgetState.selected)
              ? AppColors.secondary
              : (dark ? const Color(0xFF3A3A50) : const Color(0xFFD5DBE4)),
        ),
      ),
      textTheme: textTheme,
    );
  }
}
