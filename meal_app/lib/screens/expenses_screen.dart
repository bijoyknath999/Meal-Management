import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/app_provider.dart';
import '../models/expense.dart';
import '../utils/theme.dart';
import '../utils/constants.dart';

class ExpensesScreen extends StatefulWidget {
  const ExpensesScreen({super.key});

  @override
  State<ExpensesScreen> createState() => _ExpensesScreenState();
}

class _ExpensesScreenState extends State<ExpensesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<AppProvider>();
      provider.loadExpenses();
      if (provider.members.isEmpty) provider.loadMembers();
    });
  }

  void _showAddExpense() {
    final provider = context.read<AppProvider>();
    _showExpenseSheet(context, provider.members, null);
  }

  void _showEditExpense(Expense expense) {
    final provider = context.read<AppProvider>();
    _showExpenseSheet(context, provider.members, expense);
  }

  void _showExpenseSheet(BuildContext context, List<dynamic> members, Expense? expense) {
    final isEdit = expense != null;
    int? selectedMemberId = expense?.memberId;
    final amountCtrl = TextEditingController(text: expense?.amount.toStringAsFixed(0) ?? '');
    final descCtrl = TextEditingController(text: expense?.description ?? '');
    DateTime selectedDate = expense != null ? DateTime.parse(expense.expenseDate) : DateTime.now();

    bool isSaving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) => Container(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(ctx).viewInsets.bottom,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppTheme.borderColor,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  isEdit ? 'Edit Expense' : 'Add Expense',
                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 20),

                // Member selector
                DropdownButtonFormField<int>(
                  value: selectedMemberId,
                  decoration: const InputDecoration(
                    labelText: 'Paid By',
                    prefixIcon: Icon(Icons.person_rounded),
                  ),
                  items: [
                    const DropdownMenuItem(value: null, child: Text('Other (Needs)')),
                    ...members.map((m) => DropdownMenuItem(
                          value: m.id,
                          child: Text(m.name),
                        )),
                  ],
                  onChanged: (val) => setSheetState(() => selectedMemberId = val),
                ),
                const SizedBox(height: 14),

                // Amount
                TextFormField(
                  controller: amountCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Amount',
                    prefixIcon: Icon(Icons.attach_money_rounded),
                    prefixText: '${AppConstants.currencySymbol} ',
                  ),
                ),
                const SizedBox(height: 14),

                // Date
                GestureDetector(
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: ctx,
                      initialDate: selectedDate,
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2030),
                    );
                    if (picked != null) {
                      setSheetState(() => selectedDate = picked);
                    }
                  },
                  child: InputDecorator(
                    decoration: const InputDecoration(
                      labelText: 'Date',
                      prefixIcon: Icon(Icons.calendar_today_rounded),
                    ),
                    child: Text(DateFormat('dd MMM yyyy').format(selectedDate)),
                  ),
                ),
                const SizedBox(height: 14),

                // Description
                TextFormField(
                  controller: descCtrl,
                  maxLines: 2,
                  decoration: const InputDecoration(
                    labelText: 'Description',
                    prefixIcon: Icon(Icons.description_rounded),
                  ),
                ),
                const SizedBox(height: 24),

                // Actions
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.pop(ctx),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: const Text('Cancel'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      flex: 2,
                      child: ElevatedButton(
                        onPressed: isSaving
                            ? null
                            : () async {
                                final amount = double.tryParse(amountCtrl.text) ?? 0;
                                if (amount <= 0) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Enter a valid amount')),
                                  );
                                  return;
                                }

                                setSheetState(() => isSaving = true);

                                final provider = context.read<AppProvider>();
                                final data = {
                                  'member_id': selectedMemberId ?? 0,
                                  'amount': amount,
                                  'expense_date': DateFormat('yyyy-MM-dd').format(selectedDate),
                                  'description': descCtrl.text.trim(),
                                };

                                bool success;
                                if (isEdit) {
                                  success = await provider.updateExpense(expense!.id, data);
                                } else {
                                  success = await provider.createExpense(data);
                                }

                                if (ctx.mounted) {
                                  Navigator.pop(ctx);
                                }

                                if (mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text(success
                                          ? 'Expense ${isEdit ? 'updated' : 'added'}!'
                                          : 'Failed to save expense'),
                                      backgroundColor: success ? AppTheme.secondary : AppTheme.danger,
                                      behavior: SnackBarBehavior.floating,
                                    ),
                                  );
                                  if (success) provider.loadDashboard();
                                }
                              },
                        style: ElevatedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        child: isSaving
                            ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                            : Text(isEdit ? 'Update' : 'Add Expense'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Expenses')),
      floatingActionButton: FloatingActionButton(
        onPressed: _showAddExpense,
        backgroundColor: AppTheme.primary,
        child: const Icon(Icons.add_rounded, color: Colors.white),
      ),
      body: Consumer<AppProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.expenses.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.expenses.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.receipt_long_rounded, size: 64, color: AppTheme.textLight),
                  const SizedBox(height: 16),
                  Text('No expenses yet', style: TextStyle(color: AppTheme.textSecondary, fontSize: 16)),
                  const SizedBox(height: 8),
                  Text('Tap + to add an expense', style: TextStyle(color: AppTheme.textLight)),
                ],
              ),
            );
          }

          final expenses = provider.expenses;
          final total = expenses.fold<double>(0, (sum, e) => sum + e.amount);

          return Column(
            children: [
              // Total bar
              Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [AppTheme.accent, Color(0xFFF97316)],
                  ),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.account_balance_wallet_rounded, color: Colors.white),
                    const SizedBox(width: 12),
                    const Text('Total Expenses',
                        style: TextStyle(color: Colors.white, fontSize: 15)),
                    const Spacer(),
                    Text(
                      '${AppConstants.currencySymbol}${total.toStringAsFixed(0)}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),

              // List
              Expanded(
                child: RefreshIndicator(
                  onRefresh: () => provider.loadExpenses(),
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: expenses.length,
                    itemBuilder: (context, index) {
                      return _buildExpenseTile(expenses[index], provider);
                    },
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildExpenseTile(Expense expense, AppProvider provider) {
    final isOther = expense.memberId == null;

    return Dismissible(
      key: Key('expense_${expense.id}'),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        margin: const EdgeInsets.only(bottom: 10),
        decoration: BoxDecoration(
          color: AppTheme.danger,
          borderRadius: BorderRadius.circular(14),
        ),
        child: const Icon(Icons.delete_rounded, color: Colors.white),
      ),
      confirmDismiss: (direction) async {
        return await showDialog<bool>(
          context: context,
          builder: (ctx) => AlertDialog(
            title: const Text('Delete Expense?'),
            content: const Text('This action cannot be undone.'),
            actions: [
              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
              TextButton(
                onPressed: () => Navigator.pop(ctx, true),
                style: TextButton.styleFrom(foregroundColor: AppTheme.danger),
                child: const Text('Delete'),
              ),
            ],
          ),
        );
      },
      onDismissed: (_) async {
        await provider.deleteExpense(expense.id);
        if (mounted) {
          provider.loadDashboard();
        }
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppTheme.borderColor),
        ),
        child: ListTile(
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          leading: Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: isOther ? AppTheme.accent.withOpacity(0.1) : AppTheme.primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(
              isOther ? Icons.shopping_cart_rounded : Icons.person_rounded,
              color: isOther ? AppTheme.accent : AppTheme.primary,
              size: 22,
            ),
          ),
          title: Row(
            children: [
              Text(
                expense.memberName,
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
              ),
              if (isOther) ...[
                const SizedBox(width: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppTheme.accent.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text('Needs', style: TextStyle(fontSize: 10, color: AppTheme.accent)),
                ),
              ],
            ],
          ),
          subtitle: Text(
            '${DateFormat('dd MMM').format(DateTime.parse(expense.expenseDate))}${expense.description.isNotEmpty ? ' • ${expense.description}' : ''}',
            style: TextStyle(color: AppTheme.textSecondary, fontSize: 13),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          trailing: Text(
            '${AppConstants.currencySymbol}${expense.amount.toStringAsFixed(0)}',
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: AppTheme.textPrimary,
            ),
          ),
          onTap: () => _showEditExpense(expense),
        ),
      ),
    );
  }
}
