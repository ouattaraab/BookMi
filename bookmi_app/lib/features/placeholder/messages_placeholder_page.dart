import 'package:flutter/material.dart';

class MessagesPlaceholderPage extends StatelessWidget {
  const MessagesPlaceholderPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text(
          'Messages — Coming soon',
          style: TextStyle(fontSize: 18),
        ),
      ),
    );
  }
}
