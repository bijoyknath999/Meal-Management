import 'package:flutter/material.dart';
import '../models/member.dart';
import '../models/period.dart';
import '../models/expense.dart';
import '../models/settlement.dart';
import '../services/api_service.dart';

class AppProvider with ChangeNotifier {
  final ApiService _api = ApiService();

  // State
  bool _isLoading = false;
  String? _error;
  MealPeriod? _activePeriod;
  List<Member> _members = [];
  List<MealPeriod> _periods = [];
  List<Expense> _expenses = [];
  List<Settlement> _settlements = [];
  Map<String, dynamic>? _dashboard;
  Map<String, dynamic>? _report;

  // Getters
  bool get isLoading => _isLoading;
  String? get error => _error;
  MealPeriod? get activePeriod => _activePeriod;
  List<Member> get members => _members;
  List<MealPeriod> get periods => _periods;
  List<Expense> get expenses => _expenses;
  List<Settlement> get settlements => _settlements;
  Map<String, dynamic>? get dashboard => _dashboard;
  Map<String, dynamic>? get report => _report;

  void _setLoading(bool value) {
    _isLoading = value;
    notifyListeners();
  }

  void _setError(String? value) {
    _error = value;
    notifyListeners();
  }

  // ============ DASHBOARD ============
  Future<void> loadDashboard() async {
    _setLoading(true);
    _setError(null);
    try {
      final data = await _api.getDashboard();
      _dashboard = Map<String, dynamic>.from(data['data'] ?? {});
      if (_dashboard?['active_period'] != null) {
        _activePeriod = MealPeriod.fromJson(Map<String, dynamic>.from(_dashboard!['active_period']));
      }
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  // ============ MEMBERS ============
  Future<void> loadMembers() async {
    _setLoading(true);
    _setError(null);
    try {
      final data = await _api.getMembers();
      _members = (data as List).map((m) => Member.fromJson(m)).toList();
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> createMember(Map<String, dynamic> member) async {
    _setLoading(true);
    _setError(null);
    try {
      await _api.createMember(member);
      await loadMembers();
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  Future<bool> updateMember(int id, Map<String, dynamic> member) async {
    _setLoading(true);
    _setError(null);
    try {
      await _api.updateMember(id, member);
      await loadMembers();
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  Future<bool> deleteMember(int id) async {
    _setLoading(true);
    _setError(null);
    try {
      await _api.deleteMember(id);
      await loadMembers();
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  // ============ PERIODS ============
  Future<void> loadPeriods() async {
    _setLoading(true);
    _setError(null);
    try {
      final data = await _api.getPeriods();
      _periods = (data as List).map((p) => MealPeriod.fromJson(p)).toList();
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> createPeriod(Map<String, dynamic> period) async {
    _setLoading(true);
    _setError(null);
    try {
      await _api.createPeriod(period);
      await loadPeriods();
      await loadActivePeriod();
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  Future<void> loadActivePeriod() async {
    try {
      final data = await _api.getActivePeriod();
      _activePeriod = MealPeriod.fromJson(Map<String, dynamic>.from(data['data']));
      notifyListeners();
    } catch (e) {
      _activePeriod = null;
      notifyListeners();
    }
  }

  // ============ MEALS ============
  Future<Map<String, dynamic>?> loadMeals({String? date}) async {
    _setLoading(true);
    _setError(null);
    try {
      final data = await _api.getMeals(date: date);
      _setLoading(false);
      return data['data'];
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return null;
    }
  }

  Future<bool> saveMeals(String date, List<Map<String, dynamic>> meals) async {
    _setLoading(true);
    _setError(null);
    try {
      final periodId = _activePeriod?.id ?? 0;
      await _api.saveMeals(periodId, date, meals);
      _setLoading(false);
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  // ============ EXPENSES ============
  Future<void> loadExpenses() async {
    _setLoading(true);
    _setError(null);
    try {
      final data = await _api.getExpenses();
      _expenses = (data['data']['expenses'] as List)
          .map((e) => Expense.fromJson(e))
          .toList();
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> createExpense(Map<String, dynamic> expense) async {
    _setLoading(true);
    _setError(null);
    try {
      await _api.createExpense(expense);
      await loadExpenses();
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  Future<bool> updateExpense(int id, Map<String, dynamic> expense) async {
    _setLoading(true);
    _setError(null);
    try {
      await _api.updateExpense(id, expense);
      await loadExpenses();
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  Future<bool> deleteExpense(int id) async {
    _setLoading(true);
    _setError(null);
    try {
      await _api.deleteExpense(id);
      await loadExpenses();
      return true;
    } catch (e) {
      _setError(e.toString());
      _setLoading(false);
      return false;
    }
  }

  // ============ REPORTS ============
  Future<void> loadReport() async {
    _setLoading(true);
    _setError(null);
    try {
      final data = await _api.getReport();
      _report = Map<String, dynamic>.from(data['data'] ?? {});
      if (_report?['settlements'] != null) {
        _settlements = (_report!['settlements'] as List)
            .map((s) => Settlement.fromJson(s))
            .toList();
      }
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }

  // ============ SETTLEMENTS ============
  Future<void> loadSettlements() async {
    _setLoading(true);
    _setError(null);
    try {
      final data = await _api.getSettlements();
      _settlements = (data['data']['settlements'] as List)
          .map((s) => Settlement.fromJson(s))
          .toList();
      notifyListeners();
    } catch (e) {
      _setError(e.toString());
    } finally {
      _setLoading(false);
    }
  }
}
