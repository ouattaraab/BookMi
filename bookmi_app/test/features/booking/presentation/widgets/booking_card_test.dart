import 'package:bookmi_app/features/booking/data/models/booking_model.dart';
import 'package:bookmi_app/features/booking/presentation/widgets/booking_card.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

BookingModel _makeBooking({
  String status = 'pending',
  bool isExpress = false,
}) => BookingModel(
  id: 1,
  status: status,
  clientName: 'Client',
  talentStageName: 'DJ Alpha',
  talentProfileId: 1,
  packageName: 'Pack Standard',
  packageType: 'standard',
  eventDate: '2026-06-15',
  eventLocation: 'Abidjan',
  cachetAmount: 100000,
  travelCost: 0,
  commissionAmount: 15000,
  totalAmount: 115000,
  expressFeee: 0,
  isExpress: isExpress,
  contractAvailable: false,
  hasClientReview: false,
  hasTalentReview: false,
);

Widget _wrap(Widget child) => MaterialApp(
  home: Scaffold(
    backgroundColor: const Color(0xFF1A2744),
    body: Padding(
      padding: const EdgeInsets.all(16),
      child: child,
    ),
  ),
);

void main() {
  group('BookingCard', () {
    testWidgets('displays talent name and package', (tester) async {
      await tester.pumpWidget(
        _wrap(BookingCard(booking: _makeBooking(), onTap: () {})),
      );
      expect(find.text('DJ Alpha'), findsOneWidget);
      expect(find.text('Pack Standard'), findsOneWidget);
    });

    testWidgets('displays status badge for pending', (tester) async {
      await tester.pumpWidget(
        _wrap(
          BookingCard(
            booking: _makeBooking(status: 'pending'),
            onTap: () {},
          ),
        ),
      );
      expect(find.text('En attente'), findsOneWidget);
    });

    testWidgets('displays status badge for accepted', (tester) async {
      await tester.pumpWidget(
        _wrap(
          BookingCard(
            booking: _makeBooking(status: 'accepted'),
            onTap: () {},
          ),
        ),
      );
      expect(find.text('Validée'), findsOneWidget);
    });

    testWidgets('displays status badge for confirmed', (tester) async {
      await tester.pumpWidget(
        _wrap(
          BookingCard(
            booking: _makeBooking(status: 'confirmed'),
            onTap: () {},
          ),
        ),
      );
      expect(find.text('Confirmée'), findsOneWidget);
    });

    testWidgets('displays Express badge when isExpress is true', (
      tester,
    ) async {
      await tester.pumpWidget(
        _wrap(
          BookingCard(
            booking: _makeBooking(isExpress: true),
            onTap: () {},
          ),
        ),
      );
      expect(find.text('Express'), findsOneWidget);
    });

    testWidgets('does not display Express badge when isExpress is false', (
      tester,
    ) async {
      await tester.pumpWidget(
        _wrap(
          BookingCard(
            booking: _makeBooking(isExpress: false),
            onTap: () {},
          ),
        ),
      );
      expect(find.text('Express'), findsNothing);
    });

    testWidgets('calls onTap when info row is tapped', (tester) async {
      var tapped = false;
      await tester.pumpWidget(
        _wrap(BookingCard(booking: _makeBooking(), onTap: () => tapped = true)),
      );
      await tester.tap(find.text('DJ Alpha'));
      expect(tapped, isTrue);
    });

    testWidgets('formats date correctly', (tester) async {
      await tester.pumpWidget(
        _wrap(BookingCard(booking: _makeBooking(), onTap: () {})),
      );
      // 2026-06-15 → '15 juin. 2026'
      expect(find.text('15 juin. 2026'), findsOneWidget);
    });
  });
}
