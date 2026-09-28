import 'package:flutter/material.dart';

import 'app.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  // Firebase.initializeApp() + Hive.initFlutter() are wired up in Phase 2
  // (Data layer) once a real Firebase project exists for this flavor.
  runApp(const FinChowkApp());
}
