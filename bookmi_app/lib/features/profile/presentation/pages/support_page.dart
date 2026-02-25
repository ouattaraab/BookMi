import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

const _secondary = Color(0xFFE8F0FF);
const _muted = Color(0xFF112044);
const _mutedFg = Color(0xFF8FA3C0);
const _primary = Color(0xFF3B9DF2);
const _border = Color(0x1AFFFFFF);

class SupportPage extends StatelessWidget {
  const SupportPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _muted,
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D1B38),
        foregroundColor: Colors.white,
        elevation: 0,
        title: Text(
          'Aide et support',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            color: Colors.white,
            fontSize: 16,
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Contact section
          _SectionHeader(title: 'Nous contacter'),
          _ContactCard(
            icon: Icons.email_outlined,
            title: 'Email support',
            subtitle: 'support@bookmi.click',
            color: _primary,
            onTap: () => _launchUrl('mailto:support@bookmi.click'),
          ),
          const SizedBox(height: 8),
          _ContactCard(
            icon: Icons.chat_bubble_outline,
            title: 'WhatsApp',
            subtitle: 'Réponse en moins de 24h',
            color: const Color(0xFF25D366),
            onTap: () =>
                _launchUrl('https://wa.me/message/bookmi-support'),
          ),
          const SizedBox(height: 20),
          // FAQ section
          _SectionHeader(title: 'Questions fréquentes'),
          _FaqItem(
            question: 'Comment faire une réservation ?',
            answer:
                'Trouvez un talent sur la page Découverte, consultez '
                'son profil et ses forfaits, puis appuyez sur '
                '"Réserver". Choisissez la date, le lieu et envoyez '
                'votre demande.',
          ),
          _FaqItem(
            question: 'Comment le paiement fonctionne-t-il ?',
            answer:
                'Le paiement est effectué via Mobile Money (Orange '
                'Money, Wave, MTN MoMo). Le montant est sécurisé '
                'jusqu\'à confirmation de la prestation.',
          ),
          _FaqItem(
            question: 'Puis-je annuler une réservation ?',
            answer:
                'Oui, vous pouvez annuler une réservation non encore '
                'acceptée. Pour les réservations acceptées, '
                'contactez le talent directement via la messagerie.',
          ),
          _FaqItem(
            question: 'Comment devenir talent sur BookMi ?',
            answer:
                'Inscrivez-vous avec le rôle "Talent", complétez '
                'votre profil, ajoutez des photos et des forfaits. '
                'Soumettez vos documents pour la vérification.',
          ),
          _FaqItem(
            question: 'Comment gagner de l\'argent en tant que talent ?',
            answer:
                'Recevez des demandes de réservation, acceptez celles '
                'qui vous conviennent. Après la prestation confirmée, '
                'le paiement est transféré sur votre compte Mobile Money.',
          ),
          const SizedBox(height: 20),
          // App version
          Center(
            child: Text(
              'BookMi v1.0.0',
              style: GoogleFonts.manrope(
                fontSize: 12,
                color: _mutedFg.withValues(alpha: 0.6),
              ),
            ),
          ),
          const SizedBox(height: 4),
          Center(
            child: Text(
              '© 2025 BookMi — Tous droits réservés',
              style: GoogleFonts.manrope(
                fontSize: 11,
                color: _mutedFg.withValues(alpha: 0.4),
              ),
            ),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  static Future<void> _launchUrl(String url) async {
    final uri = Uri.tryParse(url);
    if (uri != null) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Text(
        title,
        style: GoogleFonts.plusJakartaSans(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: _mutedFg,
          letterSpacing: 0.4,
        ),
      ),
    );
  }
}

class _ContactCard extends StatelessWidget {
  const _ContactCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFF0D1B38),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: _border),
          boxShadow: [
            BoxShadow(
              color: const Color(0x06FFFFFF),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, size: 20, color: color),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: GoogleFonts.manrope(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: _secondary,
                    ),
                  ),
                  Text(
                    subtitle,
                    style: GoogleFonts.manrope(
                      fontSize: 12,
                      color: _mutedFg,
                    ),
                  ),
                ],
              ),
            ),
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

class _FaqItem extends StatefulWidget {
  const _FaqItem({
    required this.question,
    required this.answer,
  });

  final String question;
  final String answer;

  @override
  State<_FaqItem> createState() => _FaqItemState();
}

class _FaqItemState extends State<_FaqItem> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: const Color(0xFF0D1B38),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _border),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: ExpansionTile(
          tilePadding:
              const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
          childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
          onExpansionChanged: (v) => setState(() => _expanded = v),
          trailing: Icon(
            _expanded ? Icons.remove : Icons.add,
            size: 18,
            color: _primary,
          ),
          title: Text(
            widget.question,
            style: GoogleFonts.manrope(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: _secondary,
            ),
          ),
          children: [
            Text(
              widget.answer,
              style: GoogleFonts.manrope(
                fontSize: 13,
                color: _mutedFg,
                height: 1.55,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
