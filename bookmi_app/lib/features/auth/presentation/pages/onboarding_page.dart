import 'dart:async';

import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/components/glass_card.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/core/design_system/tokens/spacing.dart';
import 'package:bookmi_app/features/auth/presentation/widgets/auth_button.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:hive_ce/hive.dart';

class OnboardingPage extends StatefulWidget {
  const OnboardingPage({super.key});

  @override
  State<OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<OnboardingPage> {
  final _controller = PageController();
  int _currentPage = 0;

  static const _slides = [
    _SlideData(
      icon: Icons.search_rounded,
      title: 'Découvrez les meilleurs talents',
      description:
          'Parcourez notre annuaire de DJs, musiciens, humoristes et '
          "bien d'autres artistes près de chez vous.",
    ),
    _SlideData(
      icon: Icons.calendar_month_rounded,
      title: 'Réservez en toute simplicité',
      description:
          'Choisissez votre talent, sélectionnez la date et réservez '
          'en quelques clics. Simple et rapide.',
    ),
    _SlideData(
      icon: Icons.shield_rounded,
      title: 'Paiement sécurisé',
      description:
          'Payez en toute confiance via Mobile Money ou '
          "carte bancaire. Votre argent est protégé jusqu'à "
          'la prestation.',
    ),
  ];

  void _onComplete() {
    unawaited(
      Hive.box<dynamic>('settings').put('has_seen_onboarding', true),
    );
    context.go(RoutePaths.login);
  }

  void _onNext() {
    if (_currentPage < _slides.length - 1) {
      unawaited(
        _controller.nextPage(
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeInOut,
        ),
      );
    } else {
      _onComplete();
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
      body: DecoratedBox(
        decoration: const BoxDecoration(gradient: BookmiColors.gradientHero),
        child: SafeArea(
          child: Column(
            children: [
              // Skip button
              Align(
                alignment: Alignment.topRight,
                child: TextButton(
                  onPressed: _onComplete,
                  child: Text(
                    'Passer',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.7),
                    ),
                  ),
                ),
              ),
              // Slides
              Expanded(
                child: PageView.builder(
                  controller: _controller,
                  itemCount: _slides.length,
                  onPageChanged: (index) =>
                      setState(() => _currentPage = index),
                  itemBuilder: (context, index) =>
                      _SlideWidget(data: _slides[index]),
                ),
              ),
              // Dots + Button
              Padding(
                padding: const EdgeInsets.all(BookmiSpacing.spaceLg),
                child: Column(
                  children: [
                    // Dots
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(
                        _slides.length,
                        (index) => AnimatedContainer(
                          duration: const Duration(milliseconds: 300),
                          margin: const EdgeInsets.symmetric(
                            horizontal: BookmiSpacing.spaceXs,
                          ),
                          width: index == _currentPage ? 24 : 8,
                          height: 8,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(4),
                            color: index == _currentPage
                                ? BookmiColors.brandBlueLight
                                : Colors.white.withValues(alpha: 0.3),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: BookmiSpacing.spaceLg),
                    // Button
                    AuthButton(
                      label: _currentPage == _slides.length - 1
                          ? 'Commencer'
                          : 'Suivant',
                      onPressed: _onNext,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SlideData {
  const _SlideData({
    required this.icon,
    required this.title,
    required this.description,
  });

  final IconData icon;
  final String title;
  final String description;
}

class _SlideWidget extends StatelessWidget {
  const _SlideWidget({required this.data});

  final _SlideData data;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: BookmiSpacing.spaceLg,
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            data.icon,
            size: 100,
            color: BookmiColors.brandBlueLight,
          ),
          const SizedBox(height: BookmiSpacing.spaceXl),
          GlassCard(
            child: Column(
              children: [
                Text(
                  data.title,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: BookmiSpacing.spaceBase),
                Text(
                  data.description,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                    color: Colors.white.withValues(alpha: 0.8),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
