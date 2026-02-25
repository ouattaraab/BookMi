import 'package:bookmi_app/core/design_system/tokens/colors.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

/// Full-screen WebView that hosts the Paystack checkout page.
///
/// Security model:
/// - Only loads URLs in the `paystack.com` domain.
/// - Any navigation to a non-Paystack URL is intercepted.
/// - When Paystack redirects to the callback URL after payment, the page
///   pops with the Paystack [reference] query param so the caller can poll
///   the backend for the transaction result.
/// - If the user closes the WebView without completing payment, pops with `null`.
class PaystackWebViewPage extends StatefulWidget {
  const PaystackWebViewPage({required this.authorizationUrl, super.key});

  final String authorizationUrl;

  @override
  State<PaystackWebViewPage> createState() => _PaystackWebViewPageState();
}

class _PaystackWebViewPageState extends State<PaystackWebViewPage> {
  late final WebViewController _controller;
  bool _loading = true;
  bool _popped = false; // guard against double-pop

  @override
  void initState() {
    super.initState();

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0xFF0A0F1E))
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (mounted) setState(() => _loading = true);
          },
          onPageFinished: (_) {
            if (mounted) setState(() => _loading = false);
          },
          onWebResourceError: (error) {
            // Ignore non-fatal errors (e.g. iframes, trackers blocked)
          },
          onNavigationRequest: (NavigationRequest req) {
            final uri = Uri.tryParse(req.url);
            if (uri == null) return NavigationDecision.prevent;

            final host = uri.host.toLowerCase();

            // Allow the Paystack checkout domain.
            if (host == 'checkout.paystack.com' ||
                host.endsWith('.paystack.com') ||
                host == 'paystack.com') {
              return NavigationDecision.navigate;
            }

            // Any redirect away from Paystack = callback / completion.
            // Extract the payment reference and close the WebView.
            _handleCallbackRedirect(uri);
            return NavigationDecision.prevent;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.authorizationUrl));
  }

  void _handleCallbackRedirect(Uri uri) {
    if (_popped) return;
    _popped = true;

    // Paystack appends ?reference=xxx&trxref=xxx to the callback URL.
    final reference =
        uri.queryParameters['reference'] ?? uri.queryParameters['trxref'];

    // Pop back to the caller with the reference (or null if missing).
    if (mounted) {
      Navigator.of(context).pop(reference);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0F1E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0A0F1E),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.white),
          tooltip: 'Annuler le paiement',
          onPressed: () {
            if (!_popped) {
              _popped = true;
              Navigator.of(context).pop(null);
            }
          },
        ),
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.lock_outline, color: Color(0xFF4CAF50), size: 16),
            const SizedBox(width: 6),
            const Text(
              'Paiement sécurisé',
              style: TextStyle(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        centerTitle: true,
        actions: [
          if (_loading)
            const Padding(
              padding: EdgeInsets.only(right: 16),
              child: Center(
                child: SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: BookmiColors.brandBlueLight,
                  ),
                ),
              ),
            ),
        ],
      ),
      body: Stack(
        children: [
          WebViewWidget(controller: _controller),
          if (_loading)
            const Center(
              child: CircularProgressIndicator(
                color: BookmiColors.brandBlueLight,
              ),
            ),
        ],
      ),
    );
  }
}
