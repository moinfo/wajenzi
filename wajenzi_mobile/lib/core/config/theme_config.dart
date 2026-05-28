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
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primary,
        brightness: Brightness.light,
        primary: AppColors.primary,
        secondary: AppColors.secondary,
        error: AppColors.error,
      ),
      scaffoldBackgroundColor: AppColors.background,
      appBarTheme: const AppBarTheme(
        centerTitle: true,
        elevation: 0,
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      cardTheme: CardThemeData(
        elevation: 2,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
        color: AppColors.surface,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: AppColors.textHint.withValues(alpha: 0.5)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide(color: AppColors.textHint.withValues(alpha: 0.5)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        labelStyle: const TextStyle(color: AppColors.primary),
        floatingLabelStyle: const TextStyle(color: AppColors.primary),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      textTheme: AppType.body(const TextTheme(
        headlineLarge: TextStyle(
          fontWeight: FontWeight.bold,
          color: AppColors.textPrimary,
        ),
        headlineMedium: TextStyle(
          fontWeight: FontWeight.bold,
          color: AppColors.textPrimary,
        ),
        titleLarge: TextStyle(
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
        ),
        bodyLarge: TextStyle(color: AppColors.textPrimary),
        bodyMedium: TextStyle(color: AppColors.textSecondary),
      )),
    );
  }

  static ThemeData get darkTheme {
    const surface = Color(0xFF1E1E2E);
    const surfaceVariant = Color(0xFF252537);
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primary,
        brightness: Brightness.dark,
        primary: AppColors.primary,
        secondary: AppColors.secondary,
        error: AppColors.error,
        surface: surface,
        onSurface: Color(0xFFE8E8F0),
      ),
      scaffoldBackgroundColor: const Color(0xFF13131F),
      appBarTheme: const AppBarTheme(
        centerTitle: true,
        elevation: 0,
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surfaceVariant,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: Color(0xFF3A3A50)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: Color(0xFF3A3A50)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        labelStyle: const TextStyle(color: AppColors.primary),
        floatingLabelStyle: const TextStyle(color: AppColors.primary),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      expansionTileTheme: const ExpansionTileThemeData(
        backgroundColor: surface,
        collapsedBackgroundColor: surface,
        iconColor: Color(0xFFE8E8F0),
        collapsedIconColor: Color(0xFFE8E8F0),
        textColor: Color(0xFFE8E8F0),
        collapsedTextColor: Color(0xFFE8E8F0),
      ),
      popupMenuTheme: const PopupMenuThemeData(color: surfaceVariant),
      textTheme: AppType.body(const TextTheme(
        headlineLarge: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE8E8F0)),
        headlineMedium: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFE8E8F0)),
        titleLarge: TextStyle(fontWeight: FontWeight.w600, color: Color(0xFFE8E8F0)),
        bodyLarge: TextStyle(color: Color(0xFFE8E8F0)),
        bodyMedium: TextStyle(color: Color(0xFFB0B0C8)),
      )),
    );
  }
}
