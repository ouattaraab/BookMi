import 'package:bloc_test/bloc_test.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_event.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_state.dart';
import 'package:bookmi_app/features/favorites/widgets/favorite_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockFavoritesBloc extends MockBloc<FavoritesEvent, FavoritesState>
    implements FavoritesBloc {}

void main() {
  late MockFavoritesBloc mockBloc;

  setUp(() {
    mockBloc = MockFavoritesBloc();
  });

  Widget buildSubject({int talentId = 42}) {
    return MaterialApp(
      home: Scaffold(
        body: BlocProvider<FavoritesBloc>.value(
          value: mockBloc,
          child: FavoriteButton(talentId: talentId),
        ),
      ),
    );
  }

  group('FavoriteButton', () {
    testWidgets('shows outlined heart when not favorited', (tester) async {
      when(() => mockBloc.state).thenReturn(
        const FavoritesLoaded(favoriteIds: {}),
      );

      await tester.pumpWidget(buildSubject());

      expect(find.byIcon(Icons.favorite_border), findsOneWidget);
      expect(find.byIcon(Icons.favorite), findsNothing);
    });

    testWidgets('shows filled heart when favorited', (tester) async {
      when(() => mockBloc.state).thenReturn(
        const FavoritesLoaded(favoriteIds: {42}),
      );

      await tester.pumpWidget(buildSubject());

      expect(find.byIcon(Icons.favorite), findsOneWidget);
      expect(find.byIcon(Icons.favorite_border), findsNothing);
    });

    testWidgets('dispatches FavoriteToggled on tap', (tester) async {
      when(() => mockBloc.state).thenReturn(
        const FavoritesLoaded(favoriteIds: {}),
      );

      await tester.pumpWidget(buildSubject());
      await tester.tap(find.byType(FavoriteButton));

      verify(() => mockBloc.add(const FavoriteToggled(42))).called(1);
    });

    testWidgets('shows grey color when not favorited', (tester) async {
      when(() => mockBloc.state).thenReturn(
        const FavoritesLoaded(favoriteIds: {}),
      );

      await tester.pumpWidget(buildSubject());

      final icon = tester.widget<Icon>(find.byType(Icon));
      expect(icon.color, Colors.grey);
    });

    testWidgets('shows red color when favorited', (tester) async {
      when(() => mockBloc.state).thenReturn(
        const FavoritesLoaded(favoriteIds: {42}),
      );

      await tester.pumpWidget(buildSubject());

      final icon = tester.widget<Icon>(find.byType(Icon));
      expect(icon.color, Colors.red);
    });

    testWidgets('shows outlined heart when state is not Loaded', (
      tester,
    ) async {
      when(() => mockBloc.state).thenReturn(const FavoritesInitial());

      await tester.pumpWidget(buildSubject());

      expect(find.byIcon(Icons.favorite_border), findsOneWidget);
    });
  });
}
