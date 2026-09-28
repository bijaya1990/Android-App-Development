import 'package:flutter_test/flutter_test.dart';

import 'package:finchowk/app.dart';

void main() {
  testWidgets('FinChowk app boots to the Phase 0 placeholder screen', (WidgetTester tester) async {
    await tester.pumpWidget(const FinChowkApp());
    await tester.pumpAndSettle();

    expect(find.text('FinChowk — Phase 1 design system complete'), findsOneWidget);
  });
}
