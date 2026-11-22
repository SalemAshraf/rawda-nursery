// ignore_for_file: deprecated_member_use
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:nursery_family/api.dart';
import 'package:nursery_family/main.dart';

void main() {
  test('Known labels translate without rewriting staff free text', () {
    String ar(String a, String e) => a;
    expect(moodLabel('happy', ar), 'سعيد');
    expect(moodLabel('happy after drawing', ar), 'happy after drawing');
    expect(moodLabel(' HAPPY ', ar), ' HAPPY ');
    expect(statusLabel('sent', ar), 'صادرة');
    expect(statusLabel('custom status', ar), 'custom status');
  });
  setUp(() => FlutterSecureStorage.setMockInitialValues({}));
  test('Only secure server URLs are accepted in release configuration', () {
    expect(
      FamilyApi(baseUrl: 'https://nursery.example.test').validServer,
      true,
    );
    expect(
      FamilyApi(baseUrl: 'http://untrusted.example.test').validServer,
      false,
    );
    expect(
      FamilyApi(baseUrl: 'https://name:secret@example.test').validServer,
      false,
    );
  });
  test(
    'API login uses versioned endpoint and sends bearer only after login',
    () async {
      final seen = <http.Request>[];
      final api = FamilyApi(
        baseUrl: 'https://nursery.example.test',
        client: MockClient((r) async {
          seen.add(r);
          return http.Response(
            jsonEncode(
              r.url.path.endsWith('/login')
                  ? {'token': 'synthetic-token'}
                  : {'children': []},
            ),
            200,
          );
        }),
      );
      await api.login('demo-code', 'sample@example.test', 'synthetic-password');
      await api.request('me');
      expect(seen.first.url.path, '/api/v1/family/login');
      expect(seen.first.headers['Authorization'], isNull);
      expect(seen.last.headers['Authorization'], 'Bearer synthetic-token');
      api.dispose();
    },
  );
  test('Unauthorized response clears bearer token', () async {
    final api = FamilyApi(
      baseUrl: 'https://nursery.example.test',
      client: MockClient(
        (r) async => http.Response('{"message":"Unauthenticated."}', 401),
      ),
    );
    api.token = 'synthetic-token';
    await expectLater(api.request('me'), throwsA(isA<ApiFailure>()));
    expect(api.token, isNull);
    api.dispose();
  });
  testWidgets('Arabic family login switches to English without overflow', (
    tester,
  ) async {
    await tester.binding.setSurfaceSize(const Size(360, 900));
    final api = FamilyApi(
      baseUrl: 'https://nursery.example.test',
      client: MockClient((r) async => http.Response('{}', 200)),
    );
    await tester.pumpWidget(FamilyApp(api: api));
    await tester.pumpAndSettle();
    expect(find.text('أهلاً بالأسرة'), findsOneWidget);
    await tester.tap(find.text('عربي / English'));
    await tester.pumpAndSettle();
    expect(find.text('Welcome, family'), findsOneWidget);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
    await tester.binding.setSurfaceSize(null);
  });
  testWidgets(
    'Connected-client view presents linked child and announcement response',
    (tester) async {
      final api = FamilyApi(
        baseUrl: 'https://nursery.example.test',
        client: MockClient(
          (r) async => http.Response(
            jsonEncode({
              'account': {'name': 'Sample family'},
              'nursery': {'name': 'Synthetic nursery'},
              'children': [
                {'id': 1, 'name': 'Synthetic child'},
              ],
              'announcements': [
                {
                  'id': 1,
                  'title': 'Sample announcement',
                  'body': 'No real child data',
                },
              ],
            }),
            200,
          ),
        ),
      );
      api.token = 'synthetic-token';
      await tester.pumpWidget(
        MaterialApp(
          home: HomePage(
            api: api,
            l: (a, e) => e,
            toggle: () {},
            signedOut: () {},
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Synthetic child'), findsOneWidget);
      expect(find.text('Sample announcement'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      api.dispose();
    },
  );
}
