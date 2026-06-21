import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/app_provider.dart';
import '../models/settlement.dart';
import '../utils/theme.dart';
import '../utils/constants.dart';

class ReportScreen extends StatefulWidget {
  const ReportScreen({super.key});

  @override
  State<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends State<ReportScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AppProvider>().loadReport();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Report'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => context.read<AppProvider>().loadReport(),
          ),
        ],
      ),
      body: Consumer<AppProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.report == null) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.report == null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.assessment_rounded, size: 64, color: AppTheme.textLight),
                  const SizedBox(height: 16),
                  Text('No report available', style: TextStyle(color: AppTheme.textSecondary, fontSize: 16)),
                  const SizedBox(height: 8),
                  ElevatedButton(
                    onPressed: () => provider.loadReport(),
                    child: const Text('Load Report'),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => provider.loadReport(),
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSummaryCard(provider),
                  const SizedBox(height: 20),
                  _buildSettlementsSection(provider.settlements),
                  const SizedBox(height: 20),
                  _buildExpensesSection(provider),
                  const SizedBox(height: 20),
                  _buildDailyMealsSection(provider),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildSummaryCard(AppProvider provider) {
    final report = provider.report!;
    final summary = Map<String, dynamic>.from(report['summary'] ?? {});
    final period = Map<String, dynamic>.from(report['period'] ?? {});

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppTheme.primary, Color(0xFF7C3AED)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            period['period_name'] ?? 'Report',
            style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _buildSummaryItem('Total Meals', '${summary['total_meals'] ?? 0}'),
              _buildSummaryItem('Meal Rate', '${AppConstants.currencySymbol}${double.tryParse(summary['meal_rate'].toString())?.toStringAsFixed(1) ?? '0'}'),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              _buildSummaryItem('Members', '${summary['member_count'] ?? 0}'),
              _buildSummaryItem('Total Expense', '${AppConstants.currencySymbol}${double.tryParse(summary['total_expense'].toString())?.toStringAsFixed(0) ?? '0'}'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryItem(String label, String value) {
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              label,
              style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 13),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSettlementsSection(List<Settlement> settlements) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Settlements', Icons.account_balance_rounded),
        const SizedBox(height: 12),
        if (settlements.isEmpty)
          const Card(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: Text('No settlements yet')),
            ),
          )
        else
          ...settlements.map((s) => _buildSettlementCard(s)),
      ],
    );
  }

  Widget _buildSettlementCard(Settlement s) {
    final isCredit = s.isCredit;
    final isDue = s.isDue;
    final color = isCredit ? AppTheme.secondary : (isDue ? AppTheme.danger : AppTheme.textLight);
    final statusText = isCredit ? 'Will Take' : (isDue ? 'Will Give' : 'Settled');
    final statusIcon = isCredit
        ? Icons.arrow_downward_rounded
        : (isDue ? Icons.arrow_upward_rounded : Icons.check_circle_rounded);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundColor: color.withOpacity(0.1),
                child: Text(
                  s.memberName.isNotEmpty ? s.memberName[0].toUpperCase() : '?',
                  style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 16),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      s.memberName,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
                    ),
                    Text(
                      '${s.totalMeals} meals • Paid ${AppConstants.currencySymbol}${s.totalExpense.toStringAsFixed(0)}',
                      style: TextStyle(color: AppTheme.textSecondary, fontSize: 12),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${AppConstants.currencySymbol}${s.balance.abs().toStringAsFixed(0)}',
                    style: TextStyle(
                      color: color,
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(statusIcon, size: 12, color: color),
                        const SizedBox(width: 4),
                        Text(statusText, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Breakdown bar
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppTheme.surface,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                _buildBreakdownItem('Meal Cost', '${AppConstants.currencySymbol}${s.mealCost.toStringAsFixed(0)}'),
                Container(width: 1, height: 24, color: AppTheme.borderColor),
                _buildBreakdownItem('Paid', '${AppConstants.currencySymbol}${s.totalExpense.toStringAsFixed(0)}'),
                Container(width: 1, height: 24, color: AppTheme.borderColor),
                _buildBreakdownItem(
                  'Balance',
                  '${s.balance >= 0 ? '+' : ''}${AppConstants.currencySymbol}${s.balance.toStringAsFixed(0)}',
                  color: color,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBreakdownItem(String label, String value, {Color? color}) {
    return Expanded(
      child: Column(
        children: [
          Text(value, style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14, color: color ?? AppTheme.textPrimary)),
          Text(label, style: TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
        ],
      ),
    );
  }

  Widget _buildExpensesSection(AppProvider provider) {
    final expenses = provider.report?['expenses'] as List? ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Expenses', Icons.receipt_long_rounded),
        const SizedBox(height: 12),
        if (expenses.isEmpty)
          const Card(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: Text('No expenses recorded')),
            ),
          )
        else
          Card(
            child: ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: expenses.length,
              separatorBuilder: (_, __) => const Divider(height: 1, indent: 16),
              itemBuilder: (context, index) {
                final e = expenses[index];
                final isOther = e['member_id'] == null;
                return ListTile(
                  dense: true,
                  leading: Icon(
                    isOther ? Icons.shopping_cart_rounded : Icons.person_rounded,
                    color: isOther ? AppTheme.accent : AppTheme.primary,
                    size: 20,
                  ),
                  title: Text(e['member_name'] ?? 'Unknown', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
                  subtitle: Text(
                    '${e['expense_date'] ?? ''}${(e['description'] ?? '').isNotEmpty ? ' • ${e['description']}' : ''}',
                    style: TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: Text(
                    '${AppConstants.currencySymbol}${double.tryParse(e['amount'].toString())?.toStringAsFixed(0) ?? '0'}',
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                  ),
                );
              },
            ),
          ),
      ],
    );
  }

  Widget _buildDailyMealsSection(AppProvider provider) {
    final dailyMeals = provider.report?['daily_meals'] as Map<String, dynamic>? ?? {};

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionTitle('Daily Meals', Icons.restaurant_rounded),
        const SizedBox(height: 12),
        if (dailyMeals.isEmpty)
          const Card(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: Text('No meal records')),
            ),
          )
        else
          ...dailyMeals.entries.map((entry) {
            final date = entry.key;
            final meals = entry.value as List;
            final nonZeroMeals = meals.where((m) => (m['meal_count'] ?? 0) > 0).toList();

            if (nonZeroMeals.isEmpty) return const SizedBox.shrink();

            return Container(
              margin: const EdgeInsets.only(bottom: 10),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppTheme.borderColor),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    date,
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14, color: AppTheme.textPrimary),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 6,
                    children: nonZeroMeals.map<Widget>((m) {
                      return Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: AppTheme.primary.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          '${m['member_name']}: ${m['meal_count']}',
                          style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500, color: AppTheme.primaryDark),
                        ),
                      );
                    }).toList(),
                  ),
                ],
              ),
            );
          }),
      ],
    );
  }

  Widget _buildSectionTitle(String title, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 20, color: AppTheme.primary),
        const SizedBox(width: 8),
        Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600, color: AppTheme.textPrimary)),
      ],
    );
  }
}
