// Lightweight DTOs for the design pricing & site-visit calculator features.
// Kept as plain Dart classes (no codegen) to stay close to the project's
// existing screen-level patterns and avoid build_runner churn.

class CurrencyDto {
  final int id;
  final String name;
  final String symbol;
  final String? code;
  final double rateToUsd;
  final bool isActive;

  const CurrencyDto({
    required this.id,
    required this.name,
    required this.symbol,
    required this.code,
    required this.rateToUsd,
    required this.isActive,
  });

  factory CurrencyDto.fromJson(Map<String, dynamic> json) => CurrencyDto(
        id: (json['id'] as num).toInt(),
        name: json['name']?.toString() ?? '',
        symbol: json['symbol']?.toString() ?? '',
        code: json['code']?.toString(),
        rateToUsd: _asDouble(json['rate_to_usd']),
        isActive: json['is_active'] == true || json['is_active'] == 1,
      );

  Map<String, dynamic> toCreatePayload() => {
        'name': name,
        'symbol': symbol,
        if (code != null && code!.isNotEmpty) 'code': code,
        'rate_to_usd': rateToUsd,
        'is_active': isActive,
      };
}

class DesignPackageDto {
  final int id;
  final String name;
  final String riseType; // low | high
  final double priceUsd;
  final List<String> includedServices;
  final int sortOrder;
  final bool isActive;

  const DesignPackageDto({
    required this.id,
    required this.name,
    required this.riseType,
    required this.priceUsd,
    required this.includedServices,
    required this.sortOrder,
    required this.isActive,
  });

  factory DesignPackageDto.fromJson(Map<String, dynamic> json) => DesignPackageDto(
        id: (json['id'] as num).toInt(),
        name: json['name']?.toString() ?? '',
        riseType: json['rise_type']?.toString() ?? 'low',
        priceUsd: _asDouble(json['price_usd']),
        includedServices: ((json['included_services'] as List?) ?? const [])
            .map((e) => e.toString())
            .toList(),
        sortOrder: ((json['sort_order'] as num?) ?? 0).toInt(),
        isActive: json['is_active'] == true || json['is_active'] == 1,
      );
}

class DesignAddonDto {
  final int id;
  final String name;
  final double priceLowUsd;
  final double priceHighUsd;
  final int sortOrder;
  final bool isActive;

  const DesignAddonDto({
    required this.id,
    required this.name,
    required this.priceLowUsd,
    required this.priceHighUsd,
    required this.sortOrder,
    required this.isActive,
  });

  factory DesignAddonDto.fromJson(Map<String, dynamic> json) => DesignAddonDto(
        id: (json['id'] as num).toInt(),
        name: json['name']?.toString() ?? '',
        priceLowUsd: _asDouble(json['price_low_usd']),
        priceHighUsd: _asDouble(json['price_high_usd']),
        sortOrder: ((json['sort_order'] as num?) ?? 0).toInt(),
        isActive: json['is_active'] == true || json['is_active'] == 1,
      );
}

class DesignSpecialStructureDto {
  final int id;
  final String name;
  final double rateTzsPerSqm;
  final int sortOrder;
  final bool isActive;

  const DesignSpecialStructureDto({
    required this.id,
    required this.name,
    required this.rateTzsPerSqm,
    required this.sortOrder,
    required this.isActive,
  });

  factory DesignSpecialStructureDto.fromJson(Map<String, dynamic> json) =>
      DesignSpecialStructureDto(
        id: (json['id'] as num).toInt(),
        name: json['name']?.toString() ?? '',
        rateTzsPerSqm: _asDouble(json['rate_tzs_per_sqm']),
        sortOrder: ((json['sort_order'] as num?) ?? 0).toInt(),
        isActive: json['is_active'] == true || json['is_active'] == 1,
      );
}

class SiteVisitLocationDto {
  final int id;
  final String name;
  final double baseCostTzs;
  final double presetTravelTzs;
  final double presetLocalTzs;
  final double presetAllowanceTzs;
  final double presetFoodTzs;
  final double presetAccommodationTzs;
  final int sortOrder;
  final bool isActive;

  const SiteVisitLocationDto({
    required this.id,
    required this.name,
    required this.baseCostTzs,
    required this.presetTravelTzs,
    required this.presetLocalTzs,
    required this.presetAllowanceTzs,
    required this.presetFoodTzs,
    required this.presetAccommodationTzs,
    required this.sortOrder,
    required this.isActive,
  });

  factory SiteVisitLocationDto.fromJson(Map<String, dynamic> json) => SiteVisitLocationDto(
        id: (json['id'] as num).toInt(),
        name: json['name']?.toString() ?? '',
        baseCostTzs: _asDouble(json['base_cost_tzs']),
        presetTravelTzs: _asDouble(json['preset_travel_tzs']),
        presetLocalTzs: _asDouble(json['preset_local_tzs']),
        presetAllowanceTzs: _asDouble(json['preset_allowance_tzs']),
        presetFoodTzs: _asDouble(json['preset_food_tzs']),
        presetAccommodationTzs: _asDouble(json['preset_accommodation_tzs']),
        sortOrder: ((json['sort_order'] as num?) ?? 0).toInt(),
        isActive: json['is_active'] == true || json['is_active'] == 1,
      );
}

class DesignPricingLookups {
  final double tzsRatePerUsd;
  final List<DesignPackageDto> lowPackages;
  final List<DesignPackageDto> highPackages;
  final List<DesignAddonDto> addons;
  final List<DesignSpecialStructureDto> specialStructures;
  final List<CurrencyDto> currencies;
  final List<Map<String, dynamic>> locations;

  const DesignPricingLookups({
    required this.tzsRatePerUsd,
    required this.lowPackages,
    required this.highPackages,
    required this.addons,
    required this.specialStructures,
    required this.currencies,
    required this.locations,
  });

  factory DesignPricingLookups.fromJson(Map<String, dynamic> json) {
    return DesignPricingLookups(
      tzsRatePerUsd: _asDouble(json['tzs_rate_per_usd']),
      lowPackages: ((json['low_packages'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => DesignPackageDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      highPackages: ((json['high_packages'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => DesignPackageDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      addons: ((json['addons'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => DesignAddonDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      specialStructures: ((json['special_structures'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => DesignSpecialStructureDto.fromJson(
              Map<String, dynamic>.from(e)))
          .toList(),
      currencies: ((json['currencies'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => CurrencyDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      locations: ((json['locations'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList(),
    );
  }
}

class SiteVisitCalculatorLookups {
  final double tzsRatePerUsd;
  final List<SiteVisitLocationDto> locations;
  final List<CurrencyDto> currencies;

  const SiteVisitCalculatorLookups({
    required this.tzsRatePerUsd,
    required this.locations,
    required this.currencies,
  });

  factory SiteVisitCalculatorLookups.fromJson(Map<String, dynamic> json) {
    return SiteVisitCalculatorLookups(
      tzsRatePerUsd: _asDouble(json['tzs_rate_per_usd']),
      locations: ((json['locations'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => SiteVisitLocationDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      currencies: ((json['currencies'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => CurrencyDto.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }
}

class ComputeResult {
  final Map<String, dynamic> raw;
  ComputeResult(this.raw);

  double? get totalUsd => (raw['total_usd'] as num?)?.toDouble();
  double? get totalTzs => (raw['total_tzs'] as num?)?.toDouble();
  String? get invoiceText => raw['invoice_text']?.toString();
  bool get escalate => raw['escalate'] == true;
  List<Map<String, dynamic>> get breakdown =>
      ((raw['breakdown'] as List?) ?? const [])
          .whereType<Map>()
          .map((e) => Map<String, dynamic>.from(e))
          .toList();
}

double _asDouble(dynamic value) {
  if (value == null) return 0.0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0.0;
}
