import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/features/onboarding/bloc/onboarding_cubit.dart';
import 'package:bookmi_app/features/onboarding/bloc/onboarding_state.dart';
import 'package:bookmi_app/features/onboarding/data/repositories/onboarding_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class TalentOnboardingPage extends StatelessWidget {
  const TalentOnboardingPage({required this.repository, super.key});

  final OnboardingRepository repository;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => OnboardingCubit(repository: repository)..loadStatus(),
      child: const _TalentOnboardingView(),
    );
  }
}

class _TalentOnboardingView extends StatelessWidget {
  const _TalentOnboardingView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BookmiColors.backgroundDeep,
      body: BlocBuilder<OnboardingCubit, OnboardingState>(
        builder: (context, state) {
          return switch (state) {
            OnboardingInitial() || OnboardingLoading() => const Center(
              child: CircularProgressIndicator(color: Colors.white),
            ),
            OnboardingError(:final message) => _ErrorView(message: message),
            OnboardingLoaded() => _OnboardingContent(state: state),
          };
        },
      ),
    );
  }
}

// ─────────────────────────────────────────────
// Error view
// ─────────────────────────────────────────────

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.error_outline,
              color: BookmiColors.error,
              size: 48,
            ),
            const SizedBox(height: 16),
            Text(
              message,
              style: const TextStyle(color: Colors.white70),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: () => context.read<OnboardingCubit>().loadStatus(),
              child: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────
// Main onboarding content
// ─────────────────────────────────────────────

class _OnboardingContent extends StatelessWidget {
  const _OnboardingContent({required this.state});

  final OnboardingLoaded state;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(
            child: _OnboardingHeader(state: state),
          ),
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
            sliver: SliverList.separated(
              itemCount: OnboardingStep.values.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final step = OnboardingStep.values[index];
                return _StepCard(
                  step: step,
                  isCompleted: state.isCompleted(step),
                  isNext: state.nextStep == step,
                );
              },
            ),
          ),
          if (state.isFullyComplete) ...[
            SliverToBoxAdapter(
              child: _CompletionBanner(),
            ),
          ],
          const SliverPadding(padding: EdgeInsets.only(bottom: 32)),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────
// Header: title + animated progress bar
// ─────────────────────────────────────────────

class _OnboardingHeader extends StatelessWidget {
  const _OnboardingHeader({required this.state});

  final OnboardingLoaded state;

  @override
  Widget build(BuildContext context) {
    final progress = state.completedCount / state.totalSteps;

    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Back button
          GestureDetector(
            onTap: () => Navigator.of(context).maybePop(),
            child: const Icon(
              Icons.arrow_back_ios,
              color: Colors.white70,
              size: 20,
            ),
          ),
          const SizedBox(height: 24),
          // Gamified stars row
          Row(
            children: List.generate(state.totalSteps, (i) {
              final filled = i < state.completedCount;
              return Padding(
                padding: const EdgeInsets.only(right: 6),
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 300),
                  child: Icon(
                    filled ? Icons.star_rounded : Icons.star_outline_rounded,
                    key: ValueKey(filled),
                    color: filled
                        ? BookmiColors.brandBlueLight
                        : Colors.white24,
                    size: 28,
                  ),
                ),
              );
            }),
            mainAxisAlignment: MainAxisAlignment.center,
          ),
          const SizedBox(height: 20),
          const Text(
            'Configurez votre profil',
            style: TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '${state.completedCount} étape${state.completedCount != 1 ? "s" : ""} sur ${state.totalSteps} complétée${state.completedCount != 1 ? "s" : ""}',
            style: const TextStyle(color: Colors.white60, fontSize: 13),
          ),
          const SizedBox(height: 16),
          // Animated progress bar
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 0, end: progress),
              duration: const Duration(milliseconds: 600),
              curve: Curves.easeOutCubic,
              builder: (_, value, __) => LinearProgressIndicator(
                value: value,
                minHeight: 10,
                backgroundColor: Colors.white12,
                valueColor: const AlwaysStoppedAnimation<Color>(
                  BookmiColors.brandBlueLight,
                ),
              ),
            ),
          ),
          const SizedBox(height: 8),
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              '${state.profileCompletionPct}% complet',
              style: const TextStyle(
                color: BookmiColors.brandBlueLight,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─────────────────────────────────────────────
// Step card
// ─────────────────────────────────────────────

class _StepCard extends StatelessWidget {
  const _StepCard({
    required this.step,
    required this.isCompleted,
    required this.isNext,
  });

  final OnboardingStep step;
  final bool isCompleted;
  final bool isNext;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      decoration: BoxDecoration(
        color: isCompleted
            ? BookmiColors.glassWhite
            : isNext
            ? Colors.white.withOpacity(0.12)
            : Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isNext
              ? BookmiColors.brandBlueLight.withOpacity(0.6)
              : isCompleted
              ? BookmiColors.success.withOpacity(0.4)
              : Colors.white12,
          width: isNext ? 1.5 : 1,
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            // Step icon or checkmark
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 300),
              child: isCompleted
                  ? Container(
                      key: const ValueKey('check'),
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: BookmiColors.success.withOpacity(0.2),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.check_circle_rounded,
                        color: BookmiColors.success,
                        size: 28,
                      ),
                    )
                  : Container(
                      key: ValueKey('icon_${step.name}'),
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: isNext
                            ? BookmiColors.brandBlueLight.withOpacity(0.15)
                            : Colors.white.withOpacity(0.08),
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          step.icon,
                          style: const TextStyle(fontSize: 22),
                        ),
                      ),
                    ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    step.label,
                    style: TextStyle(
                      color: isCompleted ? Colors.white70 : Colors.white,
                      fontSize: 15,
                      fontWeight: isNext ? FontWeight.bold : FontWeight.w500,
                      decoration: isCompleted
                          ? TextDecoration.lineThrough
                          : TextDecoration.none,
                      decorationColor: Colors.white38,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    step.description,
                    style: const TextStyle(
                      color: Colors.white54,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            if (!isCompleted)
              Icon(
                Icons.arrow_forward_ios_rounded,
                color: isNext ? BookmiColors.brandBlueLight : Colors.white24,
                size: 14,
              ),
          ],
        ),
      ),
    );
  }
}

// ─────────────────────────────────────────────
// Completion banner
// ─────────────────────────────────────────────

class _CompletionBanner extends StatefulWidget {
  @override
  State<_CompletionBanner> createState() => _CompletionBannerState();
}

class _CompletionBannerState extends State<_CompletionBanner>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 700),
    );
    _scaleAnimation = Tween<double>(begin: 0.7, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.elasticOut),
    );
    _fadeAnimation = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeIn),
    );
    _controller.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
      child: FadeTransition(
        opacity: _fadeAnimation,
        child: ScaleTransition(
          scale: _scaleAnimation,
          child: Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF00C853), Color(0xFF2196F3)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              children: [
                const Text(
                  '🎉',
                  style: TextStyle(fontSize: 40),
                ),
                const SizedBox(height: 12),
                const Text(
                  'Profil complet !',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Félicitations ! Votre profil est prêt à recevoir des réservations.',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.white70, fontSize: 13),
                ),
                const SizedBox(height: 16),
                FilledButton(
                  style: FilledButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: const Color(0xFF00C853),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  onPressed: () => Navigator.of(context).maybePop(),
                  child: const Text(
                    'Commencer',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
