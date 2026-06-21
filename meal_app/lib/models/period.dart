class MealPeriod {
  final int id;
  final String periodName;
  final int month;
  final int year;
  final String startDate;
  final String endDate;
  final int isActive;
  final double mealRate;
  final double totalExpense;
  final int totalMeals;
  final List<dynamic> members;

  MealPeriod({
    required this.id,
    required this.periodName,
    required this.month,
    required this.year,
    required this.startDate,
    required this.endDate,
    this.isActive = 1,
    this.mealRate = 0,
    this.totalExpense = 0,
    this.totalMeals = 0,
    this.members = const [],
  });

  factory MealPeriod.fromJson(Map<String, dynamic> json) {
    return MealPeriod(
      id: int.tryParse(json['id'].toString()) ?? 0,
      periodName: json['period_name']?.toString() ?? '',
      month: int.tryParse(json['month'].toString()) ?? 0,
      year: int.tryParse(json['year'].toString()) ?? 0,
      startDate: json['start_date']?.toString() ?? '',
      endDate: json['end_date']?.toString() ?? '',
      isActive: int.tryParse(json['is_active'].toString()) ?? 0,
      mealRate: double.tryParse(json['meal_rate'].toString()) ?? 0,
      totalExpense: double.tryParse(json['total_expense'].toString()) ?? 0,
      totalMeals: int.tryParse(json['total_meals'].toString()) ?? 0,
      members: json['members'] ?? [],
    );
  }
}
