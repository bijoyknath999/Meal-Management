import 'package:flutter/material.dart';
import '../utils/theme.dart';

class AppLogo extends StatelessWidget {
  final double size;
  final bool showText;

  const AppLogo({super.key, this.size = 120, this.showText = true});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        CustomPaint(
          size: Size(size, size),
          painter: _LogoPainter(),
        ),
        if (showText) ...[
          const SizedBox(height: 16),
          Text(
            'Meal Management',
            style: TextStyle(
              fontSize: size * 0.2,
              fontWeight: FontWeight.bold,
              color: AppTheme.textPrimary,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Track meals, expenses & settlements',
            style: TextStyle(
              fontSize: size * 0.1,
              color: AppTheme.textSecondary,
            ),
          ),
        ],
      ],
    );
  }
}

class _LogoPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;

    // Background circle with gradient
    final bgPaint = Paint()
      ..shader = const LinearGradient(
        colors: [AppTheme.primary, AppTheme.primaryDark],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      ).createShader(Rect.fromCircle(center: center, radius: radius));
    canvas.drawCircle(center, radius, bgPaint);

    // Bowl body (white arc)
    final bowlPaint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = size.width * 0.06
      ..strokeCap = StrokeCap.round;

    final bowlRect = Rect.fromCenter(
      center: Offset(center.dx, center.dy + size.height * 0.15),
      width: size.width * 0.55,
      height: size.height * 0.35,
    );
    canvas.drawArc(bowlRect, 0, 3.14159, false, bowlPaint);

    // Bowl bottom line
    final bottomPaint = Paint()
      ..color = Colors.white
      ..strokeWidth = size.width * 0.05
      ..strokeCap = StrokeCap.round;

    canvas.drawLine(
      Offset(center.dx - size.width * 0.18, center.dy + size.height * 0.15),
      Offset(center.dx + size.width * 0.18, center.dy + size.height * 0.15),
      bottomPaint,
    );

    // Steam lines
    final steamPaint = Paint()
      ..color = Colors.white.withOpacity(0.7)
      ..style = PaintingStyle.stroke
      ..strokeWidth = size.width * 0.025
      ..strokeCap = StrokeCap.round;

    // Steam line 1 (left)
    final steam1 = Path()
      ..moveTo(center.dx - size.width * 0.12, center.dy - size.height * 0.08)
      ..quadraticBezierTo(
        center.dx - size.width * 0.15, center.dy - size.height * 0.18,
        center.dx - size.width * 0.1, center.dy - size.height * 0.28,
      );
    canvas.drawPath(steam1, steamPaint);

    // Steam line 2 (center)
    final steam2 = Path()
      ..moveTo(center.dx, center.dy - size.height * 0.1)
      ..quadraticBezierTo(
        center.dx + size.width * 0.05, center.dy - size.height * 0.2,
        center.dx, center.dy - size.height * 0.3,
      );
    canvas.drawPath(steam2, steamPaint);

    // Steam line 3 (right)
    final steam3 = Path()
      ..moveTo(center.dx + size.width * 0.12, center.dy - size.height * 0.08)
      ..quadraticBezierTo(
        center.dx + size.width * 0.15, center.dy - size.height * 0.18,
        center.dx + size.width * 0.1, center.dy - size.height * 0.28,
      );
    canvas.drawPath(steam3, steamPaint);

    // Small spoon
    final spoonPaint = Paint()
      ..color = Colors.white.withOpacity(0.85)
      ..style = PaintingStyle.stroke
      ..strokeWidth = size.width * 0.03
      ..strokeCap = StrokeCap.round;

    final spoon = Path()
      ..moveTo(center.dx + size.width * 0.2, center.dy + size.height * 0.05)
      ..quadraticBezierTo(
        center.dx + size.width * 0.28, center.dy - size.height * 0.05,
        center.dx + size.width * 0.22, center.dy - size.height * 0.15,
      );
    canvas.drawPath(spoon, spoonPaint);

    // Spoon head
    final spoonHead = Paint()
      ..color = Colors.white.withOpacity(0.85)
      ..style = PaintingStyle.fill;
    canvas.drawOval(
      Rect.fromCenter(
        center: Offset(center.dx + size.width * 0.21, center.dy - size.height * 0.17),
        width: size.width * 0.06,
        height: size.width * 0.04,
      ),
      spoonHead,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
