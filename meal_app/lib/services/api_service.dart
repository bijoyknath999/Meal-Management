import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String? _token;

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ${_token ?? AppConstants.apiKey}',
      };

  void setToken(String token) {
    _token = token;
  }

  // Generic request handler
  Future<Map<String, dynamic>> _request(
    String method,
    String endpoint, {
    Map<String, dynamic>? body,
    Map<String, String>? queryParams,
  }) async {
    try {
      final uri = Uri.parse('${AppConstants.baseUrl}/$endpoint')
          .replace(queryParameters: queryParams);

      http.Response response;

      switch (method) {
        case 'GET':
          response = await http.get(uri, headers: _headers);
          break;
        case 'POST':
          response = await http.post(uri, headers: _headers, body: jsonEncode(body));
          break;
        case 'PUT':
          response = await http.put(uri, headers: _headers, body: jsonEncode(body));
          break;
        case 'DELETE':
          response = await http.delete(uri, headers: _headers);
          break;
        default:
          throw Exception('Unsupported method: $method');
      }

      final data = jsonDecode(response.body);

      if (response.statusCode >= 200 && response.statusCode < 300) {
        return data is Map<String, dynamic> ? data : {'data': data};
      } else {
        throw Exception(data['error'] ?? 'Request failed with status ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Network error: $e');
    }
  }

  // ============ AUTH ============
  Future<Map<String, dynamic>> login(String username, String password) async {
    final data = await _request('POST', 'auth/login', body: {
      'username': username,
      'password': password,
    });
    if (data['data']?['token'] != null) {
      setToken(data['data']['token']);
    }
    return data;
  }

  // ============ DASHBOARD ============
  Future<Map<String, dynamic>> getDashboard() async {
    return await _request('GET', 'dashboard');
  }

  // ============ MEMBERS ============
  Future<List<dynamic>> getMembers({bool? active}) async {
    final params = active != null ? {'active': active ? '1' : '0'} : null;
    final data = await _request('GET', 'members', queryParams: params);
    return data['data'] ?? [];
  }

  Future<Map<String, dynamic>> getMember(int id) async {
    return await _request('GET', 'members/$id');
  }

  Future<Map<String, dynamic>> createMember(Map<String, dynamic> member) async {
    return await _request('POST', 'members', body: member);
  }

  Future<Map<String, dynamic>> updateMember(int id, Map<String, dynamic> member) async {
    return await _request('PUT', 'members/$id', body: member);
  }

  Future<Map<String, dynamic>> deleteMember(int id) async {
    return await _request('DELETE', 'members/$id');
  }

  // ============ PERIODS ============
  Future<List<dynamic>> getPeriods() async {
    final data = await _request('GET', 'periods');
    return data['data'] ?? [];
  }

  Future<Map<String, dynamic>> getActivePeriod() async {
    return await _request('GET', 'periods/active');
  }

  Future<Map<String, dynamic>> getPeriod(int id) async {
    return await _request('GET', 'periods/$id');
  }

  Future<Map<String, dynamic>> createPeriod(Map<String, dynamic> period) async {
    return await _request('POST', 'periods', body: period);
  }

  Future<Map<String, dynamic>> updatePeriod(int id, Map<String, dynamic> period) async {
    return await _request('PUT', 'periods/$id', body: period);
  }

  Future<Map<String, dynamic>> activatePeriod(int id) async {
    return await _request('PUT', 'periods/$id/activate');
  }

  Future<Map<String, dynamic>> updatePeriodMembers(int id, List<int> memberIds) async {
    return await _request('PUT', 'periods/$id/members', body: {'members': memberIds});
  }

  Future<Map<String, dynamic>> deletePeriod(int id) async {
    return await _request('DELETE', 'periods/$id');
  }

  // ============ MEALS ============
  Future<Map<String, dynamic>> getMeals({int? periodId, String? date}) async {
    final params = <String, String>{};
    if (periodId != null) params['period_id'] = periodId.toString();
    if (date != null) params['date'] = date;
    return await _request('GET', 'meals', queryParams: params);
  }

  Future<Map<String, dynamic>> saveMeals(int periodId, String date, List<Map<String, dynamic>> meals) async {
    return await _request('POST', 'meals', body: {
      'period_id': periodId,
      'date': date,
      'meals': meals,
    });
  }

  // ============ EXPENSES ============
  Future<Map<String, dynamic>> getExpenses({int? periodId}) async {
    final params = periodId != null ? {'period_id': periodId.toString()} : null;
    return await _request('GET', 'expenses', queryParams: params);
  }

  Future<Map<String, dynamic>> createExpense(Map<String, dynamic> expense) async {
    return await _request('POST', 'expenses', body: expense);
  }

  Future<Map<String, dynamic>> updateExpense(int id, Map<String, dynamic> expense) async {
    return await _request('PUT', 'expenses/$id', body: expense);
  }

  Future<Map<String, dynamic>> deleteExpense(int id) async {
    return await _request('DELETE', 'expenses/$id');
  }

  // ============ REPORTS ============
  Future<Map<String, dynamic>> getReport({int? periodId}) async {
    final params = periodId != null ? {'period_id': periodId.toString()} : null;
    return await _request('GET', 'reports', queryParams: params);
  }

  // ============ SETTLEMENTS ============
  Future<Map<String, dynamic>> getSettlements({int? periodId}) async {
    final params = periodId != null ? {'period_id': periodId.toString()} : null;
    return await _request('GET', 'settlements', queryParams: params);
  }

  Future<Map<String, dynamic>> recalculateSettlements(int periodId) async {
    return await _request('POST', 'settlements', body: {'period_id': periodId});
  }
}
