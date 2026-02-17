import 'package:flutter/material.dart';

class ProfilePlaceholderPage extends StatelessWidget {
  const ProfilePlaceholderPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text(
          'Profil — Coming soon',
          style: TextStyle(fontSize: 18),
        ),
      ),
    );
  }
}
