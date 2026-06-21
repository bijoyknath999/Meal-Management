import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/app_provider.dart';
import '../utils/theme.dart';
import '../utils/constants.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AppProvider>().loadDashboard();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => context.read<AppProvider>().loadDashboard(),
          ),
        ],
      ),
      body: Consumer<AppProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.dashboard == null) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.error != null && provider.dashboard == null) {
            return _buildError(provider);
          }

          return _buildDashboard(provider);
        },
      ),
    );
  }

  Widget _buildError(AppProvider provider) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.cloud_off_rounded, size: 64, color: AppTheme.textLight),
            const SizedBox(height: 16),
            Text('Connection Error',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.w600, color: AppTheme.textPrimary)),
            const SizedBox(height: 8),
            Text(provider.error ?? 'Unknown error',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppTheme.textSecondary)),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () => provider.loadDashboard(),
              icon: const Icon(Icons.refresh),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDashboard(AppProvider provider) {
    final dashboard = provider.dashboard;
    final period = provider.activePeriod;
    final stats = Map<String, dynamic>.from(dashboard?['stats'] ?? {});
    final todayMeals = (dashboard?['today_meals'] as List?)?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
    final recentExpenses = (dashboard?['recent_expenses'] as List?)?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];
    final members = (dashboard?['members'] as List?)?.map((e) => Map<String, dynamic>.from(e)).toList() ?? [];

    return RefreshIndicator(
      onRefresh: () => provider.loadDashboard(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Period Header
            if (period != null) _buildPeriodHeader(period),
            if (period == null) _buildNoPeriod(),
            const SizedBox(height: 20),

            // Stats Grid
            _buildStatsGrid(stats, members.length),
            const SizedBox(height: 24),

            // Today's Meals
            _buildSectionTitle('Today\'s Meals', Icons.restaurant_rounded),
            const SizedBox(height: 12),
            _buildTodayMeals(todayMeals, members),
            const SizedBox(height: 24),

            // Recent Expenses
            _buildSectionTitle('Recent Expenses', Icons.receipt_long_rounded),
            const SizedBox(height: 12),
            _buildRecentExpenses(recentExpenses),
          ],
        ),
      ),
    );
  }

  Widget _buildPeriodHeader(dynamic period) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppTheme.primary, AppTheme.primaryDark],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  period.periodName ?? 'Active Period',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${period.startDate ?? ''} - ${period.endDate ?? ''}',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.8),
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.2),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                Text(
                  '${AppConstants.currencySymbol}${(period.mealRate ?? 0).toStringAsFixed(1)}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  'per meal',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.8),
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNoPeriod() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: AppTheme.accent.withOpacity(0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.accent.withOpacity(0.3)),
      ),
      child: const Row(
        children: [
          Icon(Icons.info_outline, color: AppTheme.accent),
          SizedBox(width: 12),
          Expanded(
            child: Text(
              'No active period. Create one from the web dashboard to get started.',
              style: TextStyle(color: AppTheme.textSecondary),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsGrid(Map<String, dynamic> stats, int memberCount) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.4,
      children: [
        _buildStatCard('Members', '$memberCount', Icons.people_rounded, AppTheme.primary),
        _buildStatCard(
          'Meals Today',
          '${stats['total_meals_today'] ?? 0}',
          Icons.restaurant_rounded,
          AppTheme.secondary,
        ),
        _buildStatCard(
          'Total Expense',
          '${AppConstants.currencySymbol}${double.tryParse(stats['total_expense'].toString())?.toStringAsFixed(0) ?? '0'}',
          Icons.account_balance_wallet_rounded,
          AppTheme.accent,
        ),
        _buildStatCard(
          'Meal Rate',
          '${AppConstants.currencySymbol}${double.tryParse(stats['meal_rate'].toString())?.toStringAsFixed(1) ?? '0'}',
          Icons.trending_up_rounded,
          AppTheme.danger,
        ),
      ],
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, size: 16, color: color),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: AppTheme.textPrimary,
            ),
          ),
          Text(
            title,
            style: TextStyle(
              fontSize: 11,
              color: AppTheme.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 20, color: AppTheme.primary),
        const SizedBox(width: 8),
        Text(
          title,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: AppTheme.textPrimary,
          ),
        ),
      ],
    );
  }

  Widget _buildTodayMeals(List<dynamic> todayMeals, List<dynamic> members) {
    if (members.isEmpty) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Center(
            child: Text('No members in this period', style: TextStyle(color: AppTheme.textSecondary)),
          ),
        ),
      );
    }

    final mealMap = <int, int>{};
    for (var meal in todayMeals) {
      final memberId = meal['member_id'] is int ? meal['member_id'] : int.parse(meal['member_id'].toString());
      final count = meal['meal_count'] is int ? meal['meal_count'] : int.parse(meal['meal_count'].toString());
      mealMap[memberId] = count;
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: members.map<Widget>((member) {
            final id = member['id'] is int ? member['id'] : int.parse(member['id'].toString());
            final name = member['name'] ?? '';
            final count = mealMap[id] ?? 0;

            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 16,
                    backgroundColor: AppTheme.primary.withOpacity(0.1),
                    child: Text(
                      name.isNotEmpty ? name[0].toUpperCase() : '?',
                      style: const TextStyle(
                        color: AppTheme.primary,
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      name,
                      style: const TextStyle(
                        fontWeight: FontWeight.w500,
                        color: AppTheme.textPrimary,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: count > 0 ? AppTheme.secondary.withOpacity(0.1) : AppTheme.borderColor,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      '$count meals',
                      style: TextStyle(
                        color: count > 0 ? AppTheme.secondary : AppTheme.textLight,
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ],
              ),
            );
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildRecentExpenses(List<dynamic> recentExpenses) {
    if (recentExpenses.isEmpty) {
      return const Card(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Center(
            child: Text('No expenses yet', style: TextStyle(color: AppTheme.textSecondary)),
          ),
        ),
      );
    }

    return Card(
      child: ListView.separated(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        itemCount: recentExpenses.length,
        separatorBuilder: (_, __) => const Divider(height: 1, indent: 16),
        itemBuilder: (context, index) {
          final expense = recentExpenses[index];
          final isOther = expense['member_id'] == null;

          return ListTile(
            leading: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: isOther ? AppTheme.accent.withOpacity(0.1) : AppTheme.primary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(
                isOther ? Icons.shopping_cart_rounded : Icons.person_rounded,
                color: isOther ? AppTheme.accent : AppTheme.primary,
                size: 20,
              ),
            ),
            title: Text(
              expense['member_name'] ?? 'Unknown',
              style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 14),
            ),
            subtitle: Text(
              expense['description'] ?? '',
              style: TextStyle(color: AppTheme.textSecondary, fontSize: 12),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            trailing: Text(
              '${AppConstants.currencySymbol}${double.tryParse(expense['amount'].toString())?.toStringAsFixed(0) ?? '0'}',
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 15,
                color: AppTheme.textPrimary,
              ),
            ),
          );
        },
      ),
    );
  }
}
