import 'package:flutter/material.dart';

class BookingsPlaceholderPage extends StatelessWidget {
  const BookingsPlaceholderPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text(
          'Réservations — Coming soon',
          style: TextStyle(fontSize: 18),
        ),
      ),
    );
  }
}
