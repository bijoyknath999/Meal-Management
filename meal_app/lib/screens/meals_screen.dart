import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/app_provider.dart';
import '../utils/theme.dart';

class MealsScreen extends StatefulWidget {
  const MealsScreen({super.key});

  @override
  State<MealsScreen> createState() => _MealsScreenState();
}

class _MealsScreenState extends State<MealsScreen> {
  DateTime _selectedDate = DateTime.now();
  Map<int, int> _mealCounts = {};
  List<dynamic> _members = [];
  bool _hasChanges = false;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadMeals());
  }

  Future<void> _loadMeals() async {
    final provider = context.read<AppProvider>();
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
    final data = await provider.loadMeals(date: dateStr);

    if (data != null && mounted) {
      final meals = data['meals'] as List? ?? [];
      setState(() {
        _members = meals;
        _mealCounts = {};
        for (var m in meals) {
          final id = m['member_id'] is int ? m['member_id'] : int.parse(m['member_id'].toString());
          final count = m['meal_count'] is int ? m['meal_count'] : int.parse(m['meal_count'].toString());
          _mealCounts[id] = count;
        }
        _hasChanges = false;
      });
    }
  }

  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: Theme.of(context).colorScheme.copyWith(primary: AppTheme.primary),
          ),
          child: child!,
        );
      },
    );

    if (picked != null && picked != _selectedDate) {
      setState(() => _selectedDate = picked);
      _loadMeals();
    }
  }

  void _increment(int memberId) {
    setState(() {
      _mealCounts[memberId] = (_mealCounts[memberId] ?? 0) + 1;
      _hasChanges = true;
    });
  }

  void _decrement(int memberId) {
    setState(() {
      final current = _mealCounts[memberId] ?? 0;
      if (current > 0) {
        _mealCounts[memberId] = current - 1;
        _hasChanges = true;
      }
    });
  }

  void _setAll(int value) {
    setState(() {
      for (var m in _members) {
        final id = m['member_id'] is int ? m['member_id'] : int.parse(m['member_id'].toString());
        _mealCounts[id] = value;
      }
      _hasChanges = true;
    });
  }

  Future<void> _saveMeals() async {
    if (_isSaving) return;
    setState(() => _isSaving = true);

    final provider = context.read<AppProvider>();
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);

    final meals = _mealCounts.entries
        .map((e) => {'member_id': e.key, 'meal_count': e.value})
        .toList();

    final success = await provider.saveMeals(dateStr, meals);

    if (mounted) {
      setState(() => _isSaving = false);

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success ? 'Meals saved successfully!' : 'Failed to save meals'),
          backgroundColor: success ? AppTheme.secondary : AppTheme.danger,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );

      if (success) {
        setState(() => _hasChanges = false);
        provider.loadDashboard();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Daily Meals'),
        actions: [
          if (_hasChanges)
            TextButton.icon(
              onPressed: _isSaving ? null : _saveMeals,
              icon: _isSaving
                  ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: AppTheme.primary))
                  : const Icon(Icons.save_rounded),
              label: const Text('Save'),
              style: TextButton.styleFrom(foregroundColor: AppTheme.primary),
            ),
        ],
      ),
      body: Column(
        children: [
          // Date Selector
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: Colors.white,
            child: Row(
              children: [
                IconButton(
                  onPressed: () {
                    setState(() => _selectedDate = _selectedDate.subtract(const Duration(days: 1)));
                    _loadMeals();
                  },
                  icon: const Icon(Icons.chevron_left_rounded),
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: _selectDate,
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.calendar_today_rounded, size: 18, color: AppTheme.primary),
                          const SizedBox(width: 8),
                          Text(
                            DateFormat('EEE, dd MMM yyyy').format(_selectedDate),
                            style: const TextStyle(
                              fontWeight: FontWeight.w600,
                              color: AppTheme.primary,
                              fontSize: 15,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                IconButton(
                  onPressed: () {
                    setState(() => _selectedDate = _selectedDate.add(const Duration(days: 1)));
                    _loadMeals();
                  },
                  icon: const Icon(Icons.chevron_right_rounded),
                ),
              ],
            ),
          ),

          // Quick Actions
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: Row(
              children: [
                _buildQuickBtn('All 1', () => _setAll(1)),
                const SizedBox(width: 8),
                _buildQuickBtn('All 2', () => _setAll(2)),
                const SizedBox(width: 8),
                _buildQuickBtn('All 3', () => _setAll(3)),
                const SizedBox(width: 8),
                _buildQuickBtn('Clear', () => _setAll(0)),
              ],
            ),
          ),

          // Meal Cards
          Expanded(
            child: Consumer<AppProvider>(
              builder: (context, provider, child) {
                if (provider.isLoading && _members.isEmpty) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (_members.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.restaurant_rounded, size: 64, color: AppTheme.textLight),
                        const SizedBox(height: 16),
                        Text('No members found', style: TextStyle(color: AppTheme.textSecondary, fontSize: 16)),
                        const SizedBox(height: 8),
                        Text(
                          'Create a period with members first',
                          style: TextStyle(color: AppTheme.textLight, fontSize: 14),
                        ),
                      ],
                    ),
                  );
                }

                return ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  itemCount: _members.length,
                  itemBuilder: (context, index) {
                    final member = _members[index];
                    final memberId = member['member_id'] is int
                        ? member['member_id']
                        : int.parse(member['member_id'].toString());
                    final name = member['member_name'] ?? '';
                    final count = _mealCounts[memberId] ?? 0;

                    return _buildMealCard(memberId, name, count);
                  },
                );
              },
            ),
          ),

          // Save Button
          if (_hasChanges)
            Container(
              padding: const EdgeInsets.all(16),
              color: Colors.white,
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isSaving ? null : _saveMeals,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    backgroundColor: AppTheme.primary,
                  ),
                  child: _isSaving
                      ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                      : const Text(
                          'Save Meals',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                        ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildQuickBtn(String label, VoidCallback onTap) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: AppTheme.borderColor),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: AppTheme.textSecondary,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildMealCard(int memberId, String name, int count) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: count > 0 ? AppTheme.primary.withOpacity(0.3) : AppTheme.borderColor,
        ),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: count > 0 ? AppTheme.primary : AppTheme.borderColor,
            child: Text(
              name.isNotEmpty ? name[0].toUpperCase() : '?',
              style: TextStyle(
                color: count > 0 ? Colors.white : AppTheme.textLight,
                fontWeight: FontWeight.w600,
                fontSize: 18,
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Text(
              name,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
                color: AppTheme.textPrimary,
              ),
            ),
          ),
          // Counter
          Container(
            decoration: BoxDecoration(
              color: AppTheme.surface,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildCounterBtn(
                  icon: Icons.remove_rounded,
                  onTap: () => _decrement(memberId),
                  color: count > 0 ? AppTheme.danger : AppTheme.textLight,
                ),
                Container(
                  width: 48,
                  alignment: Alignment.center,
                  child: Text(
                    '$count',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: count > 0 ? AppTheme.primary : AppTheme.textLight,
                    ),
                  ),
                ),
                _buildCounterBtn(
                  icon: Icons.add_rounded,
                  onTap: () => _increment(memberId),
                  color: AppTheme.secondary,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCounterBtn({
    required IconData icon,
    required VoidCallback onTap,
    required Color color,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.all(8),
        child: Icon(icon, color: color, size: 24),
      ),
    );
  }
}
