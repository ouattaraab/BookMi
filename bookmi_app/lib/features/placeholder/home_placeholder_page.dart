import 'package:flutter/material.dart';

class HomePlaceholderPage extends StatelessWidget {
  const HomePlaceholderPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text(
          'Accueil — Coming soon',
          style: TextStyle(fontSize: 18),
        ),
      ),
    );
  }
}
