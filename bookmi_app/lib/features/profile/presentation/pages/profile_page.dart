import 'package:bookmi_app/features/auth/bloc/auth_bloc.dart';
import 'package:bookmi_app/features/auth/bloc/auth_event.dart';
import 'package:bookmi_app/features/auth/bloc/auth_state.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

// ── Design tokens ─────────────────────────────────────────────────
const _primary = Color(0xFF3B9DF2);
const _secondary = Color(0xFF00274D);
const _muted = Color(0xFFF8FAFC);
const _mutedFg = Color(0xFF64748B);
const _border = Color(0xFFE2E8F0);
const _success = Color(0xFF14B8A6);
const _destructive = Color(0xFFEF4444);
const _warning = Color(0xFFFBBF24);

class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        final user = state is AuthAuthenticated ? state.user : null;
        final roles = state is AuthAuthenticated ? state.roles : <String>[];
        final firstName = user?.firstName ?? 'Utilisateur';
        final lastName = user?.lastName ?? '';
        final email = user?.email ?? '';
        final memberSince = _formatMemberSince(user?.phoneVerifiedAt);
        final initials = user != null
            ? '${firstName.isNotEmpty ? firstName[0] : ''}${lastName.isNotEmpty ? lastName[0] : ''}'.toUpperCase()
            : 'U';
        final isTalent = roles.contains('talent');

        return Scaffold(
          backgroundColor: _muted,
          body: CustomScrollView(
            slivers: [
              // Header navy
              SliverToBoxAdapter(
                child: _ProfileHeader(
                  firstName: firstName,
                  lastName: lastName,
                  email: email,
                  initials: initials,
                  memberSince: memberSince,
                  isTalent: isTalent,
                ),
              ),
              // Dashboard card
              SliverToBoxAdapter(
                child: _DashboardCard(isTalent: isTalent),
              ),
              // General section
              SliverToBoxAdapter(
                child: _GeneralSection(isTalent: isTalent),
              ),
              // Logout button
              SliverToBoxAdapter(
                child: _LogoutButton(),
              ),
              // Bottom spacing
              const SliverToBoxAdapter(
                child: SizedBox(height: 100),
              ),
            ],
          ),
        );
      },
    );
  }

  static String _formatMemberSince(String? isoDate) {
    if (isoDate == null) return 'Janvier 2025';
    final date = DateTime.tryParse(isoDate);
    if (date == null) return 'Janvier 2025';
    const months = [
      '', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
      'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
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
  });

  final String firstName;
  final String lastName;
  final String email;
  final String initials;
  final String memberSince;
  final bool isTalent;

  @override
  Widget build(BuildContext context) {
    return Container(
      color: _secondary,
      padding: EdgeInsets.only(
        top: MediaQuery.of(context).padding.top + 16,
        left: 16,
        right: 16,
        bottom: 28,
      ),
      child: Column(
        children: [
          // Top bar
          Row(
            children: [
              Text(
                'Mon Profil',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
              const Spacer(),
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.settings_outlined,
                  color: Colors.white,
                  size: 20,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          // Avatar + info
          Stack(
            alignment: Alignment.center,
            children: [
              // Avatar
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [_primary, Color(0xFF1565C0)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white.withValues(alpha: 0.3), width: 2),
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
              ),
              // Verified badge
              Positioned(
                bottom: 0,
                right: 0,
                child: Container(
                  width: 22,
                  height: 22,
                  decoration: const BoxDecoration(
                    color: _success,
                    shape: BoxShape.circle,
                    border: Border.fromBorderSide(BorderSide(color: Colors.white, width: 2)),
                  ),
                  child: const Icon(Icons.check, size: 12, color: Colors.white),
                ),
              ),
            ],
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
          // Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 5),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [_warning, _warning.withValues(alpha: 0.8)],
              ),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.star, size: 13, color: Colors.white),
                const SizedBox(width: 5),
                Text(
                  isTalent ? 'Talent Elite' : 'Client Gold',
                  style: GoogleFonts.manrope(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
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

// ── Dashboard card ────────────────────────────────────────────────
class _DashboardCard extends StatelessWidget {
  const _DashboardCard({required this.isTalent});
  final bool isTalent;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
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
          // Stats grid
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  label: isTalent ? 'Revenus (Mars)' : 'Réservations',
                  value: isTalent ? '0 FCFA' : '0',
                  icon: isTalent ? Icons.account_balance_wallet_outlined : Icons.calendar_today_outlined,
                  color: _primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  label: 'Vues Profil',
                  value: '0',
                  icon: Icons.visibility_outlined,
                  color: _success,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Mini chart
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
              _MiniBarChart(),
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
                  onTap: () {},
                ),
              ),
              if (isTalent) ...[
                const SizedBox(width: 10),
                Expanded(
                  child: _ActionButton(
                    label: 'Accès Manager',
                    icon: Icons.manage_accounts_outlined,
                    isPrimary: true,
                    onTap: () {},
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
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: _secondary,
            ),
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
  @override
  Widget build(BuildContext context) {
    final bars = [0.2, 0.5, 0.35, 0.8, 0.6, 0.4];
    final months = ['Oct', 'Nov', 'Déc', 'Jan', 'Fév', 'Mar'];

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: List.generate(bars.length, (i) {
        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 28,
              height: 50 * bars[i],
              decoration: BoxDecoration(
                color: i == bars.length - 1 ? _primary : _primary.withValues(alpha: 0.25),
                borderRadius: BorderRadius.circular(6),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              months[i],
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
            Icon(icon, size: 16, color: isPrimary ? Colors.white : _secondary),
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
  const _GeneralSection({required this.isTalent});
  final bool isTalent;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
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
            onTap: () {},
          ),
          _Divider(),
          _MenuItem(
            icon: Icons.favorite_border,
            label: 'Mes talents favoris',
            onTap: () {},
          ),
          _Divider(),
          _MenuItem(
            icon: Icons.payment_outlined,
            label: 'Moyens de paiement',
            onTap: () {},
          ),
          _Divider(),
          _MenuItem(
            icon: Icons.verified_user_outlined,
            label: 'Vérification d\'identité',
            trailing: _VerifiedBadge(),
            onTap: () {},
          ),
          if (isTalent) ...[
            _Divider(),
            _MenuItem(
              icon: Icons.bar_chart_outlined,
              label: 'Statistiques talent',
              onTap: () {},
            ),
          ],
          _Divider(),
          _MenuItem(
            icon: Icons.help_outline,
            label: 'Aide et support',
            onTap: () {},
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
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
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
            trailing ?? const Icon(Icons.chevron_right, size: 18, color: _mutedFg),
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
            border: Border.all(color: _destructive.withValues(alpha: 0.2)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.logout_rounded, size: 18, color: _destructive),
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
