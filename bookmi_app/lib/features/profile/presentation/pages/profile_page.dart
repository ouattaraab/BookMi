import 'package:bookmi_app/app/routes/route_names.dart';
import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:bookmi_app/features/profile/presentation/pages/guest_profile_page.dart';
import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:bookmi_app/features/auth/data/models/auth_user.dart';
import 'package:bookmi_app/features/profile/bloc/profile_bloc.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

// ── Design tokens (dark) ─────────────────────────────────────────
const _primary = Color(0xFF2196F3);
const _secondary = Colors.white;
const _mutedFg = Color(0xFF94A3B8);
const _border = Color(0x1AFFFFFF);
const _success = Color(0xFF14B8A6);
const _destructive = Color(0xFFEF4444);

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  bool _statsRequested = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_statsRequested) {
      _statsRequested = true;
      final authState = context.read<AuthBloc>().state;
      if (authState is AuthAuthenticated) {
        final isTalent = authState.roles.contains('talent');
        context.read<ProfileBloc>().add(
          ProfileStatsFetched(isTalent: isTalent),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, authState) {
        if (authState is! AuthAuthenticated) {
          return const GuestProfilePage();
        }
        // Explicitly typed as nullable to preserve existing null-safe usages below.
        final AuthUser? user = authState.user;
        final roles = authState.roles;
        final firstName = user?.firstName ?? 'Utilisateur';
        final lastName = user?.lastName ?? '';
        final email = user?.email ?? '';
        final memberSince = _formatMemberSince(user?.phoneVerifiedAt);
        final initials = user != null
            ? '${firstName.isNotEmpty ? firstName[0] : ''}'
                      '${lastName.isNotEmpty ? lastName[0] : ''}'
                  .toUpperCase()
            : 'U';
        final isTalent = roles.contains('talent');
        final avatarUrl = user?.avatarUrl;

        return BlocBuilder<ProfileBloc, ProfileState>(
          builder: (context, profileState) {
            final stats = profileState is ProfileLoaded
                ? profileState.stats
                : null;

            return Scaffold(
              backgroundColor: Colors.transparent,
              body: RefreshIndicator(
                onRefresh: () async {
                  context.read<ProfileBloc>().add(
                    ProfileStatsFetched(isTalent: isTalent),
                  );
                },
                child: CustomScrollView(
                  slivers: [
                    SliverToBoxAdapter(
                      child: _ProfileHeader(
                        firstName: firstName,
                        lastName: lastName,
                        email: email,
                        initials: initials,
                        memberSince: memberSince,
                        isTalent: isTalent,
                        nombrePrestations: stats?.nombrePrestations ?? 0,
                        avatarUrl: avatarUrl,
                        isClientVerified: user?.isClientVerified ?? false,
                      ),
                    ),
                    SliverToBoxAdapter(
                      child: _DashboardCard(
                        isTalent: isTalent,
                        stats: stats,
                        isLoading: profileState is ProfileLoading,
                        onAgendaTap: () => context.goNamed(
                          RouteNames.bookings,
                        ),
                        onManagerTap: isTalent
                            ? () {
                                // Manager access — placeholder
                              }
                            : null,
                      ),
                    ),
                    if (isTalent &&
                        stats != null &&
                        stats.talentLevel.isNotEmpty)
                      SliverToBoxAdapter(
                        child: _TalentLevelCard(
                          talentLevel: stats.talentLevel,
                          totalBookings: stats.totalBookings > 0
                              ? stats.totalBookings
                              : stats.nombrePrestations,
                        ),
                      ),
                    SliverToBoxAdapter(
                      child: _GeneralSection(
                        isTalent: isTalent,
                        isPhoneVerified: user?.phoneVerifiedAt != null,
                      ),
                    ),
                    SliverToBoxAdapter(
                      child: _LogoutButton(),
                    ),
                    const SliverToBoxAdapter(
                      child: SizedBox(height: 100),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  static String _formatMemberSince(String? isoDate) {
    if (isoDate == null) return 'Récemment';
    final date = DateTime.tryParse(isoDate);
    if (date == null) return 'Récemment';
    const months = [
      '',
      'Janvier',
      'Février',
      'Mars',
      'Avril',
      'Mai',
      'Juin',
      'Juillet',
      'Août',
      'Septembre',
      'Octobre',
      'Novembre',
      'Décembre',
    ];
    return '${months[date.month]} ${date.year}';
  }
}

// ── Profile header ────────────────────────────────────────────────
class _ProfileHeader extends StatelessWidget {
  const _ProfileHeader({
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.initials,
    required this.memberSince,
    required this.isTalent,
    required this.nombrePrestations,
    required this.isClientVerified,
    this.avatarUrl,
  });

  final String firstName;
  final String lastName;
  final String email;
  final String initials;
  final String memberSince;
  final bool isTalent;
  final int nombrePrestations;
  final bool isClientVerified;
  final String? avatarUrl;

  String get _badgeLabel {
    if (!isTalent) return 'Client BookMi';
    if (nombrePrestations >= 20) return 'Talent Elite';
    if (nombrePrestations >= 5) return 'Talent Actif';
    return 'Talent';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Colors.transparent,
      padding: const EdgeInsets.only(
        top: 20,
        left: 20,
        right: 20,
        bottom: 28,
      ),
      child: Column(
        children: [
          // Settings button aligned right
          Align(
            alignment: Alignment.centerRight,
            child: GestureDetector(
              onTap: () => context.pushNamed(
                RouteNames.profilePersonalInfo,
              ),
              child: Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.08),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.12),
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Icons.settings_outlined,
                  color: Colors.white,
                  size: 18,
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          // Avatar
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.3),
                width: 2,
              ),
            ),
            child: ClipOval(
              child: avatarUrl != null
                  ? CachedNetworkImage(
                      imageUrl: avatarUrl!,
                      width: 80,
                      height: 80,
                      fit: BoxFit.cover,
                      errorWidget: (_, __, ___) => _AvatarFallback(
                        initials: initials,
                      ),
                    )
                  : _AvatarFallback(initials: initials),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            '$firstName $lastName',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Membre depuis $memberSince',
            style: GoogleFonts.manrope(
              fontSize: 13,
              color: Colors.white.withValues(alpha: 0.6),
            ),
          ),
          const SizedBox(height: 10),
          // Badge — dynamic
          Container(
            padding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 5,
            ),
            decoration: BoxDecoration(
              color: BookmiColors.brandBlueLight,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: BookmiColors.brandBlueLight.withValues(alpha: 0.4),
                  blurRadius: 14,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.star, size: 13, color: Colors.white),
                const SizedBox(width: 5),
                Text(
                  _badgeLabel,
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
          if (!isTalent && isClientVerified) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: const Color(0xFF4CAF50).withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: const Color(0xFF4CAF50).withValues(alpha: 0.4),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.verified_user_outlined,
                    size: 13,
                    color: Color(0xFF4CAF50),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'Client vérifié',
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      color: const Color(0xFF4CAF50),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ── Avatar fallback (initials) ────────────────────────────────────
class _AvatarFallback extends StatelessWidget {
  const _AvatarFallback({required this.initials});
  final String initials;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 80,
      height: 80,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [_primary, Color(0xFF1565C0)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          initials,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 28,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

// ── Dashboard card ────────────────────────────────────────────────
class _DashboardCard extends StatelessWidget {
  const _DashboardCard({
    required this.isTalent,
    required this.stats,
    required this.isLoading,
    required this.onAgendaTap,
    this.onManagerTap,
  });

  final bool isTalent;
  final ProfileStats? stats;
  final bool isLoading;
  final VoidCallback onAgendaTap;
  final VoidCallback? onManagerTap;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat('#,###', 'fr_FR');
    final now = DateTime.now();
    const months = [
      '',
      'Jan',
      'Fév',
      'Mar',
      'Avr',
      'Mai',
      'Jun',
      'Jul',
      'Aoû',
      'Sep',
      'Oct',
      'Nov',
      'Déc',
    ];
    final currentMonthLabel = months[now.month];

    // Stats values
    final stat1Value = isLoading
        ? '…'
        : isTalent
        ? '${fmt.format(stats?.revenusMoisCourant ?? 0)} FCFA'
        : '${stats?.bookingCount ?? 0}';
    final stat1Label = isTalent
        ? 'Revenus ($currentMonthLabel)'
        : 'Réservations';
    final stat2Value = isLoading
        ? '…'
        : isTalent
        ? '${stats?.bookingCount ?? 0}'
        : '${stats?.favoriteCount ?? 0}';
    final stat2Label = isTalent ? 'Réservations' : 'Talents favoris';

    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Tableau de bord',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: _secondary,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: stat1Label,
                  value: stat1Value,
                  icon: isTalent
                      ? Icons.account_balance_wallet_outlined
                      : Icons.calendar_today_outlined,
                  color: _primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  label: stat2Label,
                  value: stat2Value,
                  icon: isTalent ? Icons.star_outline : Icons.favorite_border,
                  color: _success,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Chart
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Activité des 6 derniers mois',
                style: GoogleFonts.manrope(
                  fontSize: 12,
                  color: _mutedFg,
                ),
              ),
              const SizedBox(height: 10),
              _MiniBarChart(
                mensuels: (isTalent && stats != null) ? stats!.mensuels : [],
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Action buttons
          Row(
            children: [
              Expanded(
                child: _ActionButton(
                  label: 'Mon agenda',
                  icon: Icons.calendar_month_outlined,
                  onTap: onAgendaTap,
                ),
              ),
              if (isTalent) ...[
                const SizedBox(width: 10),
                Expanded(
                  child: _ActionButton(
                    label: 'Accès Manager',
                    icon: Icons.manage_accounts_outlined,
                    isPrimary: true,
                    onTap: onManagerTap ?? () {},
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.07),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: color),
          const SizedBox(height: 8),
          Text(
            value,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: _secondary,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 11,
              color: _mutedFg,
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniBarChart extends StatelessWidget {
  const _MiniBarChart({required this.mensuels});
  final List<Map<String, dynamic>> mensuels;

  static const _fallbackBars = [0.2, 0.5, 0.35, 0.8, 0.6, 0.4];
  static const _fallbackMonths = [
    'Oct',
    'Nov',
    'Déc',
    'Jan',
    'Fév',
    'Mar',
  ];

  @override
  Widget build(BuildContext context) {
    if (mensuels.isEmpty) {
      // Fallback static chart when no data
      return Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: List.generate(_fallbackBars.length, (i) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 28,
                height: 50 * _fallbackBars[i],
                decoration: BoxDecoration(
                  color: i == _fallbackBars.length - 1
                      ? _primary
                      : _primary.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(6),
                ),
              ),
              const SizedBox(height: 4),
              Text(
                _fallbackMonths[i],
                style: GoogleFonts.manrope(
                  fontSize: 10,
                  color: _mutedFg,
                ),
              ),
            ],
          );
        }),
      );
    }

    final maxRevenue = mensuels
        .map((m) => (m['revenus'] as int?) ?? 0)
        .fold(0, (a, b) => a > b ? a : b);

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: mensuels.asMap().entries.map((entry) {
        final i = entry.key;
        final m = entry.value;
        final rev = (m['revenus'] as int?) ?? 0;
        final ratio = maxRevenue > 0 ? (rev / maxRevenue) : 0.0;
        final moisStr = m['mois'] as String? ?? '';
        final label = _shortMonth(moisStr);
        final isLast = i == mensuels.length - 1;

        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 28,
              height: (50 * ratio).clamp(4.0, 50.0),
              decoration: BoxDecoration(
                color: isLast ? _primary : _primary.withValues(alpha: 0.25),
                borderRadius: BorderRadius.circular(6),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 10,
                color: _mutedFg,
              ),
            ),
          ],
        );
      }).toList(),
    );
  }

  static String _shortMonth(String yyyyMm) {
    if (yyyyMm.length < 7) return yyyyMm;
    final parts = yyyyMm.split('-');
    if (parts.length < 2) return yyyyMm;
    const names = [
      '',
      'Jan',
      'Fév',
      'Mar',
      'Avr',
      'Mai',
      'Jun',
      'Jul',
      'Aoû',
      'Sep',
      'Oct',
      'Nov',
      'Déc',
    ];
    final month = int.tryParse(parts[1]) ?? 0;
    return month >= 1 && month <= 12 ? names[month] : yyyyMm;
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.label,
    required this.icon,
    required this.onTap,
    this.isPrimary = false,
  });

  final String label;
  final IconData icon;
  final VoidCallback onTap;
  final bool isPrimary;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isPrimary ? _primary : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isPrimary ? _primary : _border,
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 16,
              color: isPrimary ? Colors.white : _secondary,
            ),
            const SizedBox(width: 6),
            Text(
              label,
              style: GoogleFonts.manrope(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: isPrimary ? Colors.white : _secondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── General section ───────────────────────────────────────────────
class _GeneralSection extends StatelessWidget {
  const _GeneralSection({
    required this.isTalent,
    required this.isPhoneVerified,
  });

  final bool isTalent;
  final bool isPhoneVerified;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Text(
              'Général',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: _mutedFg,
                letterSpacing: 0.5,
              ),
            ),
          ),
          _MenuItem(
            icon: Icons.person_outline,
            label: 'Informations personnelles',
            onTap: () => context.pushNamed(
              RouteNames.profilePersonalInfo,
            ),
          ),
          if (isTalent) ...[
            _Divider(),
            _MenuItem(
              icon: Icons.edit_note_outlined,
              label: 'Description & Réseaux sociaux',
              onTap: () => context.pushNamed(
                RouteNames.profileTalentInfo,
              ),
            ),
            _Divider(),
            _MenuItem(
              icon: Icons.photo_library_outlined,
              label: 'Gestion portfolio',
              onTap: () => context.pushNamed(
                RouteNames.profilePortfolioManager,
              ),
            ),
            _Divider(),
            _MenuItem(
              icon: Icons.inventory_2_outlined,
              label: 'Gestion packages',
              onTap: () => context.pushNamed(
                RouteNames.profilePackageManager,
              ),
            ),
          ],
          _Divider(),
          _MenuItem(
            icon: Icons.verified_user_outlined,
            label: "Vérification d'identité",
            trailing: isPhoneVerified ? _VerifiedBadge() : _UnverifiedBadge(),
            onTap: () => context.pushNamed(
              RouteNames.profileIdentityVerification,
            ),
          ),
          if (isTalent) ...[
            _Divider(),
            _MenuItem(
              icon: Icons.bar_chart_outlined,
              label: 'Statistiques talent',
              onTap: () => context.pushNamed(
                RouteNames.profileTalentStatistics,
              ),
            ),
            _Divider(),
            _MenuItem(
              icon: Icons.account_balance_wallet_outlined,
              label: 'Mes revenus',
              onTap: () => context.pushNamed(
                RouteNames.profileTalentEarnings,
              ),
            ),
            _Divider(),
            _MenuItem(
              icon: Icons.payment_outlined,
              label: 'Moyens de paiement',
              onTap: () => context.pushNamed(
                RouteNames.profilePaymentMethods,
              ),
            ),
          ],
          if (!isTalent) ...[
            _Divider(),
            _MenuItem(
              icon: Icons.favorite_border,
              label: 'Mes talents favoris',
              onTap: () => context.pushNamed(RouteNames.profileFavorites),
            ),
          ],
          _Divider(),
          _MenuItem(
            icon: Icons.help_outline,
            label: 'Aide et support',
            onTap: () => context.pushNamed(RouteNames.profileSupport),
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}

class _MenuItem extends StatelessWidget {
  const _MenuItem({
    required this.icon,
    required this.label,
    required this.onTap,
    this.trailing,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: _primary.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, size: 18, color: _primary),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                label,
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: _secondary,
                ),
              ),
            ),
            trailing ??
                const Icon(
                  Icons.chevron_right,
                  size: 18,
                  color: _mutedFg,
                ),
          ],
        ),
      ),
    );
  }
}

class _Divider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return const Divider(
      color: _border,
      height: 1,
      indent: 64,
      endIndent: 16,
    );
  }
}

class _VerifiedBadge extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: _success.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _success.withValues(alpha: 0.3)),
      ),
      child: Text(
        'Validé',
        style: GoogleFonts.manrope(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: _success,
        ),
      ),
    );
  }
}

class _UnverifiedBadge extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.orange.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: Colors.orange.withValues(alpha: 0.3),
        ),
      ),
      child: Text(
        'À vérifier',
        style: GoogleFonts.manrope(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: Colors.orange,
        ),
      ),
    );
  }
}

// ── Talent level card ─────────────────────────────────────────────
class _TalentLevelCard extends StatelessWidget {
  const _TalentLevelCard({
    required this.talentLevel,
    required this.totalBookings,
  });

  final String talentLevel;
  final int totalBookings;

  static const _levels = ['nouveau', 'confirme', 'populaire', 'elite'];

  static const _levelLabels = {
    'nouveau': 'Nouveau',
    'confirme': 'Confirmé',
    'populaire': 'Populaire',
    'elite': 'Élite',
  };

  static const _levelMins = {
    'nouveau': 0,
    'confirme': 6,
    'populaire': 21,
    'elite': 51,
  };

  static const _levelColors = {
    'nouveau': Color(0xFF94A3B8),
    'confirme': Color(0xFF2196F3),
    'populaire': Color(0xFF9C27B0),
    'elite': Color(0xFFFFB300),
  };

  @override
  Widget build(BuildContext context) {
    final level = talentLevel.isNotEmpty ? talentLevel : 'nouveau';
    final currentIdx = _levels.indexOf(level).clamp(0, 3);
    final nextIdx = currentIdx < 3 ? currentIdx + 1 : -1;
    final nextLevel = nextIdx >= 0 ? _levels[nextIdx] : null;
    final currentMin = _levelMins[level] ?? 0;
    final nextMin = nextLevel != null ? (_levelMins[nextLevel] ?? 0) : null;
    final color = _levelColors[level] ?? const Color(0xFF94A3B8);
    final label = _levelLabels[level] ?? level;
    final nextLabel = nextLevel != null
        ? (_levelLabels[nextLevel] ?? nextLevel)
        : null;

    double progress;
    int remaining;
    if (nextMin != null) {
      final range = nextMin - currentMin;
      progress = range > 0
          ? ((totalBookings - currentMin) / range).clamp(0.0, 1.0)
          : 1.0;
      remaining = (nextMin - totalBookings).clamp(0, nextMin);
    } else {
      progress = 1.0;
      remaining = 0;
    }

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                Icons.workspace_premium_outlined,
                size: 18,
                color: color,
              ),
              const SizedBox(width: 8),
              Text(
                'Niveau talent',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: _mutedFg,
                  letterSpacing: 0.5,
                ),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: color.withValues(alpha: 0.3)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.star, size: 12, color: color),
                    const SizedBox(width: 4),
                    Text(
                      label,
                      style: GoogleFonts.manrope(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: color,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: progress,
              backgroundColor: Colors.white.withValues(alpha: 0.08),
              valueColor: AlwaysStoppedAnimation<Color>(color),
              minHeight: 6,
            ),
          ),
          const SizedBox(height: 8),
          if (nextLabel != null)
            Text(
              '$totalBookings réservations · encore $remaining pour atteindre $nextLabel',
              style: GoogleFonts.manrope(fontSize: 12, color: _mutedFg),
            )
          else
            Text(
              '$totalBookings réservations · Niveau maximum atteint',
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: color,
                fontWeight: FontWeight.w600,
              ),
            ),
        ],
      ),
    );
  }
}

// ── Logout button ─────────────────────────────────────────────────
class _LogoutButton extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: GestureDetector(
        onTap: () {
          showDialog<void>(
            context: context,
            builder: (ctx) => AlertDialog(
              title: Text(
                'Déconnexion',
                style: GoogleFonts.plusJakartaSans(
                  fontWeight: FontWeight.w700,
                  color: _secondary,
                ),
              ),
              content: Text(
                'Êtes-vous sûr de vouloir vous déconnecter ?',
                style: GoogleFonts.manrope(color: _mutedFg),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(ctx).pop(),
                  child: Text(
                    'Annuler',
                    style: GoogleFonts.manrope(color: _mutedFg),
                  ),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.of(ctx).pop();
                    context.read<AuthBloc>().add(const AuthLogoutRequested());
                  },
                  child: Text(
                    'Déconnecter',
                    style: GoogleFonts.manrope(
                      color: _destructive,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          );
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            color: _destructive.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: _destructive.withValues(alpha: 0.2),
            ),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.logout_rounded,
                size: 18,
                color: _destructive,
              ),
              const SizedBox(width: 8),
              Text(
                'Se déconnecter',
                style: GoogleFonts.manrope(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: _destructive,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
