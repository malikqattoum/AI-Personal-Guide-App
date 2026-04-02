import 'package:flutter/material.dart';

class AppColors {
  // Primary Colors
  static const Color primary = Color(0xFF6C63FF);
  static const Color primaryLight = Color(0xFF9D97FF);
  static const Color primaryDark = Color(0xFF4B44B2);

  // Secondary Colors
  static const Color secondary = Color(0xFFFF6B6B);
  static const Color secondaryLight = Color(0xFFFF9B9B);
  static const Color secondaryDark = Color(0xFFCC5555);

  // Accent Colors
  static const Color accent = Color(0xFF00D9A5);
  static const Color accentLight = Color(0xFF5FFFDA);
  static const Color accentDark = Color(0xFF00A87A);

  // Neutral Colors
  static const Color background = Color(0xFFF8F9FE);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color cardBackground = Color(0xFFFFFFFF);

  // Text Colors
  static const Color textPrimary = Color(0xFF1A1A2E);
  static const Color textSecondary = Color(0xFF6B6B80);
  static const Color textHint = Color(0xFF9E9EB3);

  // Chat Bubble Colors
  static const Color userBubble = Color(0xFF6C63FF);
  static const Color assistantBubble = Color(0xFFE8E8F0);

  // Status Colors
  static const Color success = Color(0xFF00D9A5);
  static const Color error = Color(0xFFFF6B6B);
  static const Color warning = Color(0xFFFFB84D);
  static const Color info = Color(0xFF4DA6FF);

  // Gradient
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primary, primaryLight],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
