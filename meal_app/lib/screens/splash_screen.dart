import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/app_provider.dart';
import '../widgets/app_logo.dart';
import 'main_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  double _opacity = 0;
  double _scale = 0.8;

  @override
  void initState() {
    super.initState();

    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );

    // Start animation
    Future.delayed(const Duration(milliseconds: 100), () {
      if (mounted) setState(() { _opacity = 1; _scale = 1; });
    });

    _controller.forward();
    _initialize();
  }

  Future<void> _initialize() async {
    final provider = context.read<AppProvider>();
    await provider.loadDashboard();
    await Future.delayed(const Duration(milliseconds: 1500));

    if (mounted) {
      Navigator.of(context).pushReplacement(
        PageRouteBuilder(
          pageBuilder: (_, __, ___) => const MainScreen(),
          transitionsBuilder: (_, animation, __, child) {
            return FadeTransition(opacity: animation, child: child);
          },
          transitionDuration: const Duration(milliseconds: 400),
        ),
      );
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: AnimatedOpacity(
          opacity: _opacity,
          duration: const Duration(milliseconds: 800),
          curve: Curves.easeOut,
          child: AnimatedScale(
            scale: _scale,
            duration: const Duration(milliseconds: 800),
            curve: Curves.easeOutBack,
            child: const AppLogo(size: 140, showText: true),
          ),
        ),
      ),
    );
  }
}
