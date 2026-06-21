class Member {
  final int id;
  final String name;
  final String phone;
  final String email;
  final int isActive;
  final String createdAt;

  Member({
    required this.id,
    required this.name,
    this.phone = '',
    this.email = '',
    this.isActive = 1,
    this.createdAt = '',
  });

  factory Member.fromJson(Map<String, dynamic> json) {
    return Member(
      id: int.tryParse(json['id'].toString()) ?? 0,
      name: json['name']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      isActive: int.tryParse(json['is_active'].toString()) ?? 1,
      createdAt: json['created_at']?.toString() ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'email': email,
      'is_active': isActive,
    };
  }
}
