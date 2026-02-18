import 'package:bookmi_app/core/design_system/components/talent_card.dart';
import 'package:bookmi_app/core/design_system/theme/gpu_tier_provider.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_bloc.dart';
import 'package:bookmi_app/features/favorites/bloc/favorites_state.dart';
import 'package:bookmi_app/features/favorites/widgets/favorite_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mocktail/mocktail.dart';

class MockFavoritesBloc extends Mock implements FavoritesBloc {
  @override
  Stream<FavoritesState> get stream =>
      Stream.value(const FavoritesLoaded(favoriteIds: {}));
}

void main() {
  late MockFavoritesBloc mockFavoritesBloc;

  setUp(() {
    mockFavoritesBloc = MockFavoritesBloc();
    when(() => mockFavoritesBloc.state).thenReturn(
      const FavoritesLoaded(favoriteIds: {}),
    );
    GpuTierProvider.tierForTesting = GpuTier.low;
  });

  tearDown(GpuTierProvider.resetForTesting);

  Widget buildTestCard({
    int id = 1,
    String stageName = 'DJ Test',
    String categoryName = 'DJ',
    Color categoryColor = Colors.purple,
    String city = 'Abidjan',
    int cachetAmount = 15000000,
    double averageRating = 4.5,
    bool isVerified = true,
    String photoUrl = '',
    VoidCallback? onTap,
  }) {
    return MaterialApp(
      home: Scaffold(
        body: BlocProvider<FavoritesBloc>.value(
          value: mockFavoritesBloc,
          child: SizedBox(
            width: 200,
            height: 300,
            child: TalentCard(
              id: id,
              stageName: stageName,
              categoryName: categoryName,
              categoryColor: categoryColor,
              city: city,
              cachetAmount: cachetAmount,
              averageRating: averageRating,
              isVerified: isVerified,
              photoUrl: photoUrl,
              onTap: onTap,
            ),
          ),
        ),
      ),
    );
  }

  group('TalentCard', () {
    testWidgets('displays stage name', (tester) async {
      await tester.pumpWidget(buildTestCard());
      expect(find.text('DJ Test'), findsOneWidget);
    });

    testWidgets('displays category name', (tester) async {
      await tester.pumpWidget(buildTestCard());
      expect(find.text('DJ'), findsOneWidget);
    });

    testWidgets('displays formatted cachet', (tester) async {
      await tester.pumpWidget(buildTestCard());
      expect(find.text('150 000 FCFA'), findsOneWidget);
    });

    testWidgets('displays rating', (tester) async {
      await tester.pumpWidget(buildTestCard());
      expect(find.text('4.5'), findsOneWidget);
      expect(find.byIcon(Icons.star), findsOneWidget);
    });

    testWidgets('shows verified badge when isVerified is true', (tester) async {
      await tester.pumpWidget(
        buildTestCard(),
      );
      expect(find.byIcon(Icons.check), findsOneWidget);
    });

    testWidgets('hides verified badge when isVerified is false', (
      tester,
    ) async {
      await tester.pumpWidget(
        buildTestCard(isVerified: false),
      );
      expect(find.byIcon(Icons.check), findsNothing);
    });

    testWidgets('triggers onTap when tapped', (tester) async {
      var tapped = false;
      await tester.pumpWidget(
        buildTestCard(onTap: () => tapped = true),
      );

      await tester.tap(find.byType(GestureDetector).first);
      await tester.pumpAndSettle();
      expect(tapped, isTrue);
    });

    testWidgets('contains FavoriteButton', (tester) async {
      await tester.pumpWidget(buildTestCard());
      expect(
        find.byType(FavoriteButton),
        findsOneWidget,
      );
    });

    testWidgets('is wrapped in Hero widget', (tester) async {
      await tester.pumpWidget(buildTestCard());
      expect(find.byType(Hero), findsOneWidget);
    });
  });

  group('TalentCard.formatCachet', () {
    test('formats 15000000 centimes to "150 000 FCFA"', () {
      expect(
        TalentCard.formatCachet(15000000),
        '150 000 FCFA',
      );
    });

    test('formats 0 centimes to "0 FCFA"', () {
      expect(TalentCard.formatCachet(0), '0 FCFA');
    });

    test('formats 100 centimes to "1 FCFA"', () {
      expect(TalentCard.formatCachet(100), '1 FCFA');
    });

    test('formats 500000 centimes to "5 000 FCFA"', () {
      expect(TalentCard.formatCachet(500000), '5 000 FCFA');
    });
  });
}
