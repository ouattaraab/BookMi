import 'package:flutter/material.dart';

class SearchPlaceholderPage extends StatelessWidget {
  const SearchPlaceholderPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text(
          'Recherche — Coming soon',
          style: TextStyle(fontSize: 18),
        ),
      ),
    );
  }
}
