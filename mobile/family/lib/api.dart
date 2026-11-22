import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class ApiFailure implements Exception {
  final String message;
  final int status;
  const ApiFailure(this.message, this.status);
  @override
  String toString() => message;
}

class FamilyApi {
  final http.Client client;
  final String baseUrl;
  final FlutterSecureStorage storage;
  String? token;
  String? signOutWarning;
  VoidCallback? onExpired;
  FamilyApi({
    http.Client? client,
    String? baseUrl,
    this.storage = const FlutterSecureStorage(),
  }) : client = client ?? http.Client(),
       baseUrl =
           baseUrl ??
           const String.fromEnvironment(
             'API_BASE_URL',
             defaultValue: 'https://nursery.mancydigitals.com',
           );

  bool get validServer {
    final u = Uri.tryParse(baseUrl);
    if (u == null ||
        u.host.isEmpty ||
        u.userInfo.isNotEmpty ||
        u.hasQuery ||
        u.hasFragment) {
      return false;
    }
    if (u.scheme == 'https') return true;
    return const bool.fromEnvironment('ALLOW_LOCAL_HTTP') &&
        u.scheme == 'http' &&
        ['127.0.0.1', 'localhost', '10.0.2.2'].contains(u.host);
  }

  Future<void> restore() async {
    if (!kIsWeb) {
      token = await storage.read(key: 'nursery.family.token.$baseUrl');
    }
  }

  Future<void> forget() async {
    token = null;
    if (!kIsWeb) await storage.delete(key: 'nursery.family.token.$baseUrl');
  }

  Future<Map<String, dynamic>> request(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    if (!validServer) {
      throw const ApiFailure('A secure Nursery server address is required.', 0);
    }
    final headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
    final uri = Uri.parse(
      '${baseUrl.replaceAll(RegExp(r'/$'), '')}/api/v1/family/$path',
    );
    final response =
        await (body == null
                ? client.get(uri, headers: headers)
                : client.post(uri, headers: headers, body: jsonEncode(body)))
            .timeout(const Duration(seconds: 20));
    Map<String, dynamic> data = {};
    if (response.body.isNotEmpty) {
      try {
        data = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {
        throw ApiFailure(
          'The server returned an unreadable response.',
          response.statusCode,
        );
      }
    }
    if (response.statusCode >= 400) {
      if (response.statusCode == 401) {
        await forget();
        onExpired?.call();
      }
      final errors = data['errors'];
      String message =
          data['message']?.toString() ?? 'The request could not be completed.';
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        message = first is List ? first.first.toString() : first.toString();
      }
      throw ApiFailure(
        message.length > 400 ? message.substring(0, 400) : message,
        response.statusCode,
      );
    }
    return data;
  }

  Future<void> login(String code, String email, String password) async {
    signOutWarning = null;
    final data = await request(
      'login',
      body: {
        'nursery_code': code.trim(),
        'email': email.trim(),
        'password': password,
        'device_name': 'Nursery Family Flutter',
      },
    );
    token = data['token'] as String;
    if (!kIsWeb) {
      await storage.write(key: 'nursery.family.token.$baseUrl', value: token);
    }
  }

  Future<void> logout() async {
    signOutWarning = null;
    try {
      if (token != null) await request('logout', body: {});
    } catch (_) {
      signOutWarning = 'Signed out on this device. Server session revocation could not be confirmed. Contact the nursery if you suspect other access.';
    } finally {
      await forget();
    }
  }

  void dispose() {
    client.close();
  }
}
