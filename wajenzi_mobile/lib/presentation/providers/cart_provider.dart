import 'package:flutter_riverpod/flutter_riverpod.dart';

// Cart item model
class CartItem {
  final String id;
  final String name;
  final String image;
  final String category;
  final double priceTZS;
  final double priceUSD;
  final String description;
  final DateTime addedAt;

  CartItem({
    required this.id,
    required this.name,
    required this.image,
    required this.category,
    required this.priceTZS,
    required this.priceUSD,
    required this.description,
    DateTime? addedAt,
  }) : addedAt = addedAt ?? DateTime.now();

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CartItem && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;
}

// Cart state
class CartState {
  final List<CartItem> items;

  const CartState({this.items = const []});

  int get itemCount => items.length;

  double get totalTZS => items.fold(0, (sum, item) => sum + item.priceTZS);

  double get totalUSD => items.fold(0, (sum, item) => sum + item.priceUSD);

  bool containsItem(String id) => items.any((item) => item.id == id);

  CartState copyWith({List<CartItem>? items}) {
    return CartState(items: items ?? this.items);
  }
}

// Cart notifier
class CartNotifier extends StateNotifier<CartState> {
  CartNotifier() : super(const CartState());

  void addItem(CartItem item) {
    if (!state.containsItem(item.id)) {
      state = state.copyWith(items: [...state.items, item]);
    }
  }

  void removeItem(String id) {
    state = state.copyWith(
      items: state.items.where((item) => item.id != id).toList(),
    );
  }

  void clearCart() {
    state = const CartState();
  }

  bool isInCart(String id) => state.containsItem(id);
}

// Providers
final cartProvider = StateNotifierProvider<CartNotifier, CartState>((ref) {
  return CartNotifier();
});

// Convenience providers
final cartItemCountProvider = Provider<int>((ref) {
  return ref.watch(cartProvider).itemCount;
});

final cartTotalTZSProvider = Provider<double>((ref) {
  return ref.watch(cartProvider).totalTZS;
});

final cartTotalUSDProvider = Provider<double>((ref) {
  return ref.watch(cartProvider).totalUSD;
});
