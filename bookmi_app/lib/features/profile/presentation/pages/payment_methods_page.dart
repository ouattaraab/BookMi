import 'package:bookmi_app/core/design_system/components/mobile_money_selector.dart';
import 'package:bookmi_app/features/profile/data/models/payout_method_model.dart';
import 'package:bookmi_app/features/profile/data/models/withdrawal_request_model.dart';
import 'package:bookmi_app/features/profile/data/repositories/payout_method_repository.dart';
import 'package:bookmi_app/features/profile/presentation/cubits/payment_method_cubit.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';

const _bg = Color(0xFF112044);
const _surface = Color(0xFF1A2E5A);
const _surfaceLight = Color(0xFF1E3566);
const _primary = Color(0xFF3B9DF2);
const _textPrimary = Color(0xFFE8F0FF);
const _textMuted = Color(0xFF8FA3C0);
const _border = Color(0x1AFFFFFF);
const _success = Color(0xFF14B8A6);
const _warning = Color(0xFFF59E0B);
const _danger = Color(0xFFEF4444);

class PaymentMethodsPage extends StatelessWidget {
  const PaymentMethodsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => PaymentMethodCubit(
        repository: context.read<PayoutMethodRepository>(),
      )..load(),
      child: const _PaymentMethodsView(),
    );
  }
}

class _PaymentMethodsView extends StatefulWidget {
  const _PaymentMethodsView();

  @override
  State<_PaymentMethodsView> createState() => _PaymentMethodsViewState();
}

class _PaymentMethodsViewState extends State<_PaymentMethodsView>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<PaymentMethodCubit, PaymentMethodState>(
      listener: (context, state) {
        switch (state) {
          case PaymentMethodSaved():
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                  'Compte enregistré — en attente de validation par l\'administration.',
                  style: GoogleFonts.manrope(color: Colors.white),
                ),
                backgroundColor: const Color(0xFF14B8A6),
                behavior: SnackBarBehavior.floating,
              ),
            );
            context.read<PaymentMethodCubit>().load();
          case PaymentMethodDeleted():
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                  'Compte de paiement supprimé.',
                  style: GoogleFonts.manrope(color: Colors.white),
                ),
                backgroundColor: _success,
                behavior: SnackBarBehavior.floating,
              ),
            );
            context.read<PaymentMethodCubit>().load();
          case PaymentMethodWithdrawalCreated():
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                  'Demande de reversement soumise avec succès.',
                  style: GoogleFonts.manrope(color: Colors.white),
                ),
                backgroundColor: _success,
                behavior: SnackBarBehavior.floating,
              ),
            );
            context.read<PaymentMethodCubit>().load();
          case PaymentMethodError(:final message):
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                  message,
                  style: GoogleFonts.manrope(color: Colors.white),
                ),
                backgroundColor: _danger,
                behavior: SnackBarBehavior.floating,
              ),
            );
          default:
            break;
        }
      },
      child: Scaffold(
        backgroundColor: _bg,
        appBar: AppBar(
          backgroundColor: const Color(0xFF0D1B38),
          foregroundColor: Colors.white,
          elevation: 0,
          title: Text(
            'Moyen de paiement',
            style: GoogleFonts.plusJakartaSans(
              fontWeight: FontWeight.w700,
              color: Colors.white,
              fontSize: 16,
            ),
          ),
          bottom: TabBar(
            controller: _tabController,
            indicatorColor: _primary,
            labelColor: Colors.white,
            unselectedLabelColor: _textMuted,
            labelStyle: GoogleFonts.manrope(
              fontWeight: FontWeight.w600,
              fontSize: 13,
            ),
            tabs: const [
              Tab(text: 'Mon compte'),
              Tab(text: 'Reversements'),
            ],
          ),
        ),
        body: BlocBuilder<PaymentMethodCubit, PaymentMethodState>(
          builder: (context, state) {
            if (state is PaymentMethodLoading ||
                state is PaymentMethodSaving ||
                state is PaymentMethodDeleting ||
                state is PaymentMethodWithdrawalCreating) {
              return const Center(
                child: CircularProgressIndicator(color: _primary),
              );
            }

            if (state is PaymentMethodError && state is! PaymentMethodLoaded) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.error_outline,
                        color: _danger,
                        size: 48,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Impossible de charger vos informations',
                        style: GoogleFonts.plusJakartaSans(
                          color: _textPrimary,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        state.message,
                        style: GoogleFonts.manrope(
                          color: _textMuted,
                          fontSize: 13,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 24),
                      ElevatedButton(
                        onPressed: () =>
                            context.read<PaymentMethodCubit>().load(),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _primary,
                        ),
                        child: Text(
                          'Réessayer',
                          style: GoogleFonts.manrope(color: Colors.white),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }

            final loaded = state is PaymentMethodLoaded ? state : null;

            return TabBarView(
              controller: _tabController,
              children: [
                _AccountTab(payoutMethod: loaded?.payoutMethod),
                _WithdrawalsTab(
                  payoutMethod: loaded?.payoutMethod,
                  requests: loaded?.withdrawalRequests ?? [],
                  hasActiveRequest: loaded?.hasActiveRequest ?? false,
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

// ─── Tab 1 : Compte de paiement ──────────────────────────────────────────────

class _AccountTab extends StatefulWidget {
  const _AccountTab({required this.payoutMethod});

  final PayoutMethodModel? payoutMethod;

  @override
  State<_AccountTab> createState() => _AccountTabState();
}

class _AccountTabState extends State<_AccountTab> {
  String? _selectedMethod;
  final _phoneController = TextEditingController();
  final _accountController = TextEditingController();
  final _bankCodeController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  /// True when the user clicked "Ajouter un nouveau compte" from verified state.
  bool _showingAddForm = false;

  static const _mobileMethods = [
    'orange_money',
    'wave',
    'mtn_momo',
    'moov_money',
  ];

  bool get _isMobileMethod =>
      _selectedMethod != null && _mobileMethods.contains(_selectedMethod);

  @override
  void initState() {
    super.initState();
    _init();
  }

  @override
  void didUpdateWidget(_AccountTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.payoutMethod != widget.payoutMethod) {
      final pm = widget.payoutMethod;
      if (pm == null || !pm.hasAccount) {
        // Account was deleted — clear form state
        setState(() {
          _selectedMethod = null;
          _phoneController.clear();
          _accountController.clear();
          _bankCodeController.clear();
          _showingAddForm = false;
        });
      } else {
        _init();
        // If account is no longer verified (e.g. after saving new account),
        // dismiss the "add form" overlay automatically.
        if (!pm.isVerified) {
          setState(() => _showingAddForm = false);
        }
      }
    }
  }

  void _init() {
    final pm = widget.payoutMethod;
    if (pm == null || !pm.hasAccount) return;
    setState(() {
      _selectedMethod = pm.payoutMethod;
      if (_mobileMethods.contains(pm.payoutMethod)) {
        _phoneController.text = pm.phone;
      } else {
        _accountController.text = pm.accountNumber;
        _bankCodeController.text =
            (pm.payoutDetails?['bank_code'] as String?) ?? '';
      }
    });
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _accountController.dispose();
    _bankCodeController.dispose();
    super.dispose();
  }

  void _showAddForm() {
    setState(() {
      _showingAddForm = true;
      // Clear form for fresh account entry
      _selectedMethod = null;
      _phoneController.clear();
      _accountController.clear();
      _bankCodeController.clear();
    });
  }

  void _save() {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedMethod == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Veuillez sélectionner une méthode de paiement.',
            style: GoogleFonts.manrope(color: Colors.white),
          ),
          backgroundColor: _danger,
        ),
      );
      return;
    }

    final details = _isMobileMethod
        ? {'phone': _phoneController.text.trim()}
        : {
            'account_number': _accountController.text.trim(),
            'bank_code': _bankCodeController.text.trim(),
          };

    context.read<PaymentMethodCubit>().updatePayoutMethod(
      payoutMethod: _selectedMethod!,
      payoutDetails: details,
    );
  }

  void _confirmDelete() {
    showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: _surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: Text(
          'Supprimer le compte de paiement',
          style: GoogleFonts.plusJakartaSans(
            color: _textPrimary,
            fontWeight: FontWeight.w700,
          ),
        ),
        content: Text(
          'Êtes-vous sûr de vouloir supprimer ce compte ? '
          'Vous devrez en enregistrer un nouveau pour effectuer des demandes de reversement.',
          style: GoogleFonts.manrope(
            color: _textMuted,
            fontSize: 13,
            height: 1.5,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              'Annuler',
              style: GoogleFonts.manrope(color: _textMuted),
            ),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              'Supprimer',
              style: GoogleFonts.manrope(
                color: _danger,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    ).then((confirmed) {
      if (confirmed == true) {
        context.read<PaymentMethodCubit>().deletePayoutMethod();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final pm = widget.payoutMethod;
    final isVerified = pm?.isVerified ?? false;

    // Verified state — show summary card instead of the form
    if (isVerified && !_showingAddForm) {
      return _buildVerifiedView(pm!);
    }

    // All other states — show form with appropriate context banner
    return _buildFormView(pm);
  }

  Widget _buildVerifiedView(PayoutMethodModel pm) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _BalanceCard(balance: pm.availableBalance),
          const SizedBox(height: 20),

          _StatusBanner(
            icon: Icons.verified_outlined,
            color: _success,
            text:
                'Compte validé — vous pouvez effectuer des demandes de reversement.',
          ),
          const SizedBox(height: 20),

          _AccountSummaryCard(pm: pm),
          const SizedBox(height: 24),

          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: _showAddForm,
              icon: const Icon(Icons.add, size: 18),
              label: Text(
                'Ajouter un nouveau compte',
                style: GoogleFonts.manrope(fontWeight: FontWeight.w600),
              ),
              style: OutlinedButton.styleFrom(
                foregroundColor: _primary,
                side: const BorderSide(color: _primary),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ),
          const SizedBox(height: 10),

          SizedBox(
            width: double.infinity,
            child: TextButton.icon(
              onPressed: _confirmDelete,
              icon: Icon(Icons.delete_outline, color: _danger, size: 18),
              label: Text(
                'Supprimer ce compte',
                style: GoogleFonts.manrope(
                  color: _danger,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFormView(PayoutMethodModel? pm) {
    final isPending = pm?.isPending ?? false;
    final isRejected = pm?.isRejected ?? false;
    final isAddingNew = (pm?.isVerified ?? false) && _showingAddForm;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Balance card (if profile loaded)
          if (pm != null) ...[
            _BalanceCard(balance: pm.availableBalance),
            const SizedBox(height: 20),
          ],

          // Cancel button when adding new account from verified state
          if (isAddingNew) ...[
            TextButton.icon(
              onPressed: () => setState(() => _showingAddForm = false),
              icon: const Icon(Icons.arrow_back, size: 16),
              label: Text(
                'Annuler',
                style: GoogleFonts.manrope(fontWeight: FontWeight.w500),
              ),
              style: TextButton.styleFrom(
                foregroundColor: _textMuted,
                padding: EdgeInsets.zero,
              ),
            ),
            const SizedBox(height: 12),
          ],

          // Context banner
          if (isPending) ...[
            _StatusBanner(
              icon: Icons.access_time_rounded,
              color: _warning,
              text:
                  'En attente de validation par l\'administration. Vous recevrez une notification dès que votre compte sera validé.',
            ),
            const SizedBox(height: 20),
          ] else if (isRejected) ...[
            _StatusBanner(
              icon: Icons.cancel_outlined,
              color: _danger,
              text: pm?.rejectionReason != null
                  ? 'Compte refusé — Motif : ${pm!.rejectionReason}. Corrigez les informations ci-dessous et enregistrez à nouveau.'
                  : 'Compte refusé. Corrigez les informations ci-dessous et enregistrez à nouveau.',
            ),
            const SizedBox(height: 20),
          ],

          // Form title
          Text(
            'Choisir un mode de réception',
            style: GoogleFonts.plusJakartaSans(
              color: _textPrimary,
              fontWeight: FontWeight.w700,
              fontSize: 15,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Sélectionnez la méthode sur laquelle vous souhaitez recevoir vos reversements.',
            style: GoogleFonts.manrope(
              color: _textMuted,
              fontSize: 13,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 16),

          // Mobile Money selector
          MobileMoneySelector(
            selectedMethod: _mobileMethods.contains(_selectedMethod)
                ? _selectedMethod
                : null,
            onMethodSelected: (method) =>
                setState(() => _selectedMethod = method),
          ),

          const SizedBox(height: 12),

          // Virement bancaire toggle
          GestureDetector(
            onTap: () => setState(() => _selectedMethod = 'bank_transfer'),
            child: Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 16,
                vertical: 14,
              ),
              decoration: BoxDecoration(
                color: _selectedMethod == 'bank_transfer'
                    ? _primary.withValues(alpha: 0.15)
                    : _surfaceLight,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: _selectedMethod == 'bank_transfer'
                      ? _primary
                      : _border,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.account_balance_outlined,
                    color: _selectedMethod == 'bank_transfer'
                        ? _primary
                        : _textMuted,
                    size: 20,
                  ),
                  const SizedBox(width: 12),
                  Text(
                    'Virement bancaire',
                    style: GoogleFonts.manrope(
                      color: _selectedMethod == 'bank_transfer'
                          ? _primary
                          : _textMuted,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  const Spacer(),
                  if (_selectedMethod == 'bank_transfer')
                    const Icon(
                      Icons.check_circle,
                      color: _primary,
                      size: 18,
                    ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 20),

          // Détails du compte
          if (_selectedMethod != null) ...[
            Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _isMobileMethod
                        ? 'Numéro de téléphone'
                        : 'Coordonnées bancaires',
                    style: GoogleFonts.plusJakartaSans(
                      color: _textPrimary,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 10),

                  if (_isMobileMethod)
                    _InputField(
                      controller: _phoneController,
                      hint: '+225 07 XX XX XX XX',
                      keyboardType: TextInputType.phone,
                      validator: (v) {
                        if (v == null || v.isEmpty) {
                          return 'Numéro requis';
                        }
                        if (!RegExp(r'^\+?[0-9]{8,15}$').hasMatch(v)) {
                          return 'Numéro invalide';
                        }
                        return null;
                      },
                    )
                  else ...[
                    _InputField(
                      controller: _accountController,
                      hint: 'Numéro de compte (IBAN / RIB)',
                      validator: (v) =>
                          v == null || v.isEmpty ? 'Champ requis' : null,
                    ),
                    const SizedBox(height: 10),
                    _InputField(
                      controller: _bankCodeController,
                      hint: 'Code banque / SWIFT / BIC',
                      validator: (v) =>
                          v == null || v.isEmpty ? 'Champ requis' : null,
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 24),

            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _save,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _primary,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  'Enregistrer le compte',
                  style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
              ),
            ),

            if (!isPending && !isRejected && !isAddingNew) ...[
              const SizedBox(height: 16),
              _InfoNote(
                text:
                    'Votre compte sera soumis à une vérification par l\'administration avant de pouvoir effectuer des demandes de reversement. '
                    'Toute modification réinitialise la validation.',
              ),
            ],
          ],
        ],
      ),
    );
  }
}

// ─── Tab 2 : Reversements ────────────────────────────────────────────────────

class _WithdrawalsTab extends StatefulWidget {
  const _WithdrawalsTab({
    required this.payoutMethod,
    required this.requests,
    required this.hasActiveRequest,
  });

  final PayoutMethodModel? payoutMethod;
  final List<WithdrawalRequestModel> requests;
  final bool hasActiveRequest;

  @override
  State<_WithdrawalsTab> createState() => _WithdrawalsTabState();
}

class _WithdrawalsTabState extends State<_WithdrawalsTab> {
  final _amountController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  void _requestWithdrawal() {
    if (!_formKey.currentState!.validate()) return;
    final amount = int.tryParse(
      _amountController.text.trim().replaceAll(' ', ''),
    );
    if (amount == null || amount < 1000) return;

    showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: _surface,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: Text(
          'Confirmer la demande',
          style: GoogleFonts.plusJakartaSans(
            color: _textPrimary,
            fontWeight: FontWeight.w700,
          ),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Montant demandé :',
              style: GoogleFonts.manrope(color: _textMuted, fontSize: 13),
            ),
            const SizedBox(height: 4),
            Text(
              '${_formatAmount(amount)} XOF',
              style: GoogleFonts.plusJakartaSans(
                color: _textPrimary,
                fontWeight: FontWeight.w700,
                fontSize: 22,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              'Le montant reçu sera net des frais de transfert de la plateforme de paiement.',
              style: GoogleFonts.manrope(
                color: _textMuted,
                fontSize: 12,
                height: 1.5,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(
              'Annuler',
              style: GoogleFonts.manrope(color: _textMuted),
            ),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: _primary,
              foregroundColor: Colors.white,
            ),
            child: Text(
              'Confirmer',
              style: GoogleFonts.manrope(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    ).then((confirmed) {
      if (confirmed == true) {
        context.read<PaymentMethodCubit>().createWithdrawalRequest(
          amount: amount,
        );
        _amountController.clear();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final pm = widget.payoutMethod;
    final balance = pm?.availableBalance ?? 0;
    final isVerified = pm?.isVerified ?? false;
    final hasRequests = widget.requests.isNotEmpty;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Balance ────────────────────────────────────────────────────
          _BalanceCard(balance: balance),

          // ── History (shown first, only when requests exist) ─────────────
          if (hasRequests) ...[
            const SizedBox(height: 20),
            Text(
              'Historique des reversements',
              style: GoogleFonts.plusJakartaSans(
                color: _textPrimary,
                fontWeight: FontWeight.w700,
                fontSize: 15,
              ),
            ),
            const SizedBox(height: 12),
            ...widget.requests.map(
              (r) => _WithdrawalRequestTile(request: r),
            ),
          ],

          // ── New request section ─────────────────────────────────────────
          const SizedBox(height: 20),
          if (!isVerified) ...[
            _InfoNote(
              icon: Icons.lock_outline,
              text:
                  'Votre compte de paiement n\'a pas encore été validé par l\'administration. '
                  'Enregistrez votre compte dans l\'onglet "Mon compte".',
            ),
          ] else if (widget.hasActiveRequest) ...[
            _InfoNote(
              icon: Icons.hourglass_top_rounded,
              iconColor: _warning,
              text:
                  'Vous avez déjà une demande en cours. Attendez son traitement avant d\'en soumettre une nouvelle.',
            ),
          ] else if (balance <= 0) ...[
            _InfoNote(
              icon: Icons.account_balance_wallet_outlined,
              text:
                  'Votre solde disponible est de 0 XOF. Il sera alimenté après confirmation de vos prestations par vos clients.',
            ),
          ] else ...[
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: _surfaceLight,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: _border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    hasRequests
                        ? 'Nouvelle demande de reversement'
                        : 'Demander un reversement',
                    style: GoogleFonts.plusJakartaSans(
                      color: _textPrimary,
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Form(
                    key: _formKey,
                    child: _InputField(
                      controller: _amountController,
                      hint: 'Montant en XOF (min. 1 000)',
                      keyboardType: TextInputType.number,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                      ],
                      validator: (v) {
                        final n = int.tryParse(v?.trim() ?? '');
                        if (n == null) return 'Montant invalide';
                        if (n < 1000) {
                          return 'Minimum 1 000 XOF';
                        }
                        if (n > balance) {
                          return 'Dépasse votre solde disponible';
                        }
                        return null;
                      },
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Le montant reçu sera net des frais de transfert.',
                    style: GoogleFonts.manrope(
                      color: _textMuted,
                      fontSize: 12,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _requestWithdrawal,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _success,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                      child: Text(
                        'Demander un reversement',
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                        ),
                      ),
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

// ─── Widgets réutilisables ───────────────────────────────────────────────────

class _StatusBanner extends StatelessWidget {
  const _StatusBanner({
    required this.icon,
    required this.color,
    required this.text,
  });

  final IconData icon;
  final Color color;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.4)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.manrope(
                color: color,
                fontSize: 12,
                fontWeight: FontWeight.w600,
                height: 1.5,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AccountSummaryCard extends StatelessWidget {
  const _AccountSummaryCard({required this.pm});

  final PayoutMethodModel pm;

  @override
  Widget build(BuildContext context) {
    final isMobile = pm.payoutMethod != 'bank_transfer';
    final accountInfo = isMobile ? pm.phone : pm.accountNumber;
    final bankCode = pm.payoutDetails?['bank_code'] as String?;
    final verifiedAt = pm.payoutMethodVerifiedAt;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: _surfaceLight,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _success.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: _methodColor(pm.payoutMethod).withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  pm.payoutMethod == 'bank_transfer'
                      ? Icons.account_balance_outlined
                      : Icons.phone_android_outlined,
                  color: _methodColor(pm.payoutMethod),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                _methodLabel(pm.payoutMethod),
                style: GoogleFonts.plusJakartaSans(
                  color: _textPrimary,
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          const Divider(color: _border, height: 1),
          const SizedBox(height: 14),
          _SummaryRow(
            label: isMobile ? 'Numéro' : 'N° de compte',
            value: accountInfo.isNotEmpty ? accountInfo : '—',
          ),
          if (!isMobile && bankCode != null && bankCode.isNotEmpty) ...[
            const SizedBox(height: 8),
            _SummaryRow(label: 'Code banque', value: bankCode),
          ],
          if (verifiedAt != null) ...[
            const SizedBox(height: 8),
            _SummaryRow(
              label: 'Validé le',
              value: _formatDateStr(verifiedAt),
            ),
          ],
        ],
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: GoogleFonts.manrope(color: _textMuted, fontSize: 13),
        ),
        Text(
          value,
          style: GoogleFonts.manrope(
            color: _textPrimary,
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({required this.balance});
  final int balance;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2180D9), Color(0xFF1A5FAF)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Solde disponible',
            style: GoogleFonts.manrope(
              color: Colors.white.withValues(alpha: 0.7),
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '${_formatAmount(balance)} XOF',
            style: GoogleFonts.plusJakartaSans(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              fontSize: 28,
            ),
          ),
        ],
      ),
    );
  }
}

class _WithdrawalRequestTile extends StatelessWidget {
  const _WithdrawalRequestTile({required this.request});
  final WithdrawalRequestModel request;

  @override
  Widget build(BuildContext context) {
    final statusColor = _statusColor(request.status);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _border),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: Icon(
              _statusIcon(request.status),
              color: statusColor,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${_formatAmount(request.amount)} XOF',
                  style: GoogleFonts.plusJakartaSans(
                    color: _textPrimary,
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _formatDate(request.createdAt),
                  style: GoogleFonts.manrope(
                    color: _textMuted,
                    fontSize: 12,
                  ),
                ),
                if (request.note != null && request.note!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    request.note!,
                    style: GoogleFonts.manrope(
                      color: _danger,
                      fontSize: 11,
                      fontStyle: FontStyle.italic,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: statusColor.withValues(alpha: 0.3),
              ),
            ),
            child: Text(
              request.statusLabel,
              style: GoogleFonts.manrope(
                color: statusColor,
                fontSize: 11,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Color _statusColor(String status) => switch (status) {
    'pending' => _warning,
    'approved' => const Color(0xFF3B9DF2),
    'processing' => const Color(0xFF8B5CF6),
    'completed' => _success,
    'rejected' => _danger,
    _ => _textMuted,
  };

  IconData _statusIcon(String status) => switch (status) {
    'pending' => Icons.hourglass_top_rounded,
    'approved' => Icons.thumb_up_outlined,
    'processing' => Icons.sync_rounded,
    'completed' => Icons.check_circle_outline,
    'rejected' => Icons.cancel_outlined,
    _ => Icons.circle_outlined,
  };

  String _formatDate(String iso) {
    try {
      final dt = DateTime.parse(iso).toLocal();
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
    } catch (_) {
      return iso;
    }
  }
}

class _InputField extends StatelessWidget {
  const _InputField({
    required this.controller,
    required this.hint,
    this.keyboardType,
    this.validator,
    this.inputFormatters,
  });

  final TextEditingController controller;
  final String hint;
  final TextInputType? keyboardType;
  final String? Function(String?)? validator;
  final List<TextInputFormatter>? inputFormatters;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      inputFormatters: inputFormatters,
      style: GoogleFonts.manrope(color: _textPrimary, fontSize: 14),
      validator: validator,
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: GoogleFonts.manrope(color: _textMuted, fontSize: 14),
        filled: true,
        fillColor: _surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: _border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: _border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: _primary),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: _danger),
        ),
        errorStyle: GoogleFonts.manrope(
          color: _danger,
          fontSize: 11,
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 14,
        ),
      ),
    );
  }
}

class _InfoNote extends StatelessWidget {
  const _InfoNote({
    required this.text,
    this.icon = Icons.info_outline,
    this.iconColor = _textMuted,
  });

  final String text;
  final IconData icon;
  final Color iconColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: _border),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: iconColor, size: 16),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: GoogleFonts.manrope(
                color: _textMuted,
                fontSize: 12,
                height: 1.5,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Helper functions ────────────────────────────────────────────────────────

String _methodLabel(String? method) => switch (method) {
  'orange_money' => 'Orange Money',
  'wave' => 'Wave',
  'mtn_momo' => 'MTN MoMo',
  'moov_money' => 'Moov Money',
  'bank_transfer' => 'Virement bancaire',
  _ => method ?? '—',
};

Color _methodColor(String? method) => switch (method) {
  'orange_money' => const Color(0xFFFF6B00),
  'wave' => const Color(0xFF0075FF),
  'mtn_momo' => const Color(0xFFFFCC00),
  'moov_money' => const Color(0xFF00A859),
  _ => _primary,
};

String _formatDateStr(String? iso) {
  if (iso == null) return '—';
  try {
    final dt = DateTime.parse(iso).toLocal();
    return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
  } catch (_) {
    return iso;
  }
}

String _formatAmount(int amount) {
  final s = amount.toString();
  final buffer = StringBuffer();
  for (var i = 0; i < s.length; i++) {
    if (i > 0 && (s.length - i) % 3 == 0) buffer.write(' ');
    buffer.write(s[i]);
  }
  return buffer.toString();
}
