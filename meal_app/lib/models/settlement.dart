class Settlement {
  final int id;
  final int periodId;
  final int memberId;
  final String memberName;
  final int totalMeals;
  final double totalExpense;
  final double mealCost;
  final double balance;
  final String status;

  Settlement({
    required this.id,
    required this.periodId,
    required this.memberId,
    required this.memberName,
    required this.totalMeals,
    required this.totalExpense,
    required this.mealCost,
    required this.balance,
    required this.status,
  });

  factory Settlement.fromJson(Map<String, dynamic> json) {
    return Settlement(
      id: int.tryParse(json['id'].toString()) ?? 0,
      periodId: int.tryParse(json['period_id'].toString()) ?? 0,
      memberId: int.tryParse(json['member_id'].toString()) ?? 0,
      memberName: json['member_name']?.toString() ?? '',
      totalMeals: int.tryParse(json['total_meals'].toString()) ?? 0,
      totalExpense: double.tryParse(json['total_expense'].toString()) ?? 0,
      mealCost: double.tryParse(json['meal_cost'].toString()) ?? 0,
      balance: double.tryParse(json['balance'].toString()) ?? 0,
      status: json['status']?.toString() ?? 'settled',
    );
  }

  bool get isCredit => status == 'credit';
  bool get isDue => status == 'due';
  bool get isSettled => status == 'settled';
}
