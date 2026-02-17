import 'package:bookmi_app/app/routes/app_router.dart';
import 'package:bookmi_app/core/design_system/theme/bookmi_theme.dart';
import 'package:bookmi_app/l10n/l10n.dart';
import 'package:flutter/material.dart';

class App extends StatelessWidget {
  const App({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      routerConfig: appRouter,
      theme: BookmiTheme.light,
      darkTheme: BookmiTheme.dark,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
    );
  }
}
