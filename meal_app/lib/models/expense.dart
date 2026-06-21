class Expense {
  final int id;
  final int periodId;
  final int? memberId;
  final double amount;
  final String expenseDate;
  final String description;
  final String memberName;
  final String createdBy;

  Expense({
    required this.id,
    required this.periodId,
    this.memberId,
    required this.amount,
    required this.expenseDate,
    this.description = '',
    this.memberName = 'Other',
    this.createdBy = '',
  });

  factory Expense.fromJson(Map<String, dynamic> json) {
    return Expense(
      id: int.tryParse(json['id'].toString()) ?? 0,
      periodId: int.tryParse(json['period_id'].toString()) ?? 0,
      memberId: json['member_id'] != null ? int.tryParse(json['member_id'].toString()) : null,
      amount: double.tryParse(json['amount'].toString()) ?? 0,
      expenseDate: json['expense_date']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      memberName: json['member_name']?.toString() ?? 'Other',
      createdBy: json['created_by']?.toString() ?? '',
    );
  }
}
