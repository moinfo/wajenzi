import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../models/calculators/calculator_models.dart';

/// Thin wrapper around `ApiClient` for calculator/catalog endpoints.
///
/// Returns parsed DTOs / raw lists; lets the repository layer enforce policy.
class CalculatorsRemoteDataSource {
  final ApiClient _api;
  CalculatorsRemoteDataSource(this._api);

  // ── Currencies ─────────────────────────────────────────────────────────
  Future<List<CurrencyDto>> listCurrencies({bool activeOnly = false}) async {
    final res = await _api.get(
      '/currencies',
      queryParameters: activeOnly ? {'active_only': '1'} : null,
    );
    return _items(res.data)
        .map((e) => CurrencyDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<Map<String, dynamic>> createCurrency(Map<String, dynamic> body) async {
    final res = await _api.post('/currencies', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> updateCurrency(int id, Map<String, dynamic> body) async {
    final res = await _api.put('/currencies/$id', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> deleteCurrency(int id) => _api.delete('/currencies/$id');

  // ── Design packages ────────────────────────────────────────────────────
  Future<List<DesignPackageDto>> listDesignPackages({bool activeOnly = false, String? riseType}) async {
    final qp = <String, dynamic>{};
    if (activeOnly) qp['active_only'] = '1';
    if (riseType != null && riseType.isNotEmpty) qp['rise_type'] = riseType;
    final res = await _api.get('/design-service-packages', queryParameters: qp.isEmpty ? null : qp);
    return _items(res.data)
        .map((e) => DesignPackageDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<Map<String, dynamic>> createDesignPackage(Map<String, dynamic> body) async {
    final res = await _api.post('/design-service-packages', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> updateDesignPackage(int id, Map<String, dynamic> body) async {
    final res = await _api.put('/design-service-packages/$id', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> deleteDesignPackage(int id) => _api.delete('/design-service-packages/$id');

  // ── Design add-ons ─────────────────────────────────────────────────────
  Future<List<DesignAddonDto>> listDesignAddons({bool activeOnly = false}) async {
    final res = await _api.get('/design-service-addons',
        queryParameters: activeOnly ? {'active_only': '1'} : null);
    return _items(res.data)
        .map((e) => DesignAddonDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<Map<String, dynamic>> createDesignAddon(Map<String, dynamic> body) async {
    final res = await _api.post('/design-service-addons', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> updateDesignAddon(int id, Map<String, dynamic> body) async {
    final res = await _api.put('/design-service-addons/$id', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> deleteDesignAddon(int id) => _api.delete('/design-service-addons/$id');

  // ── Design special structures ──────────────────────────────────────────
  Future<List<DesignSpecialStructureDto>> listSpecialStructures({bool activeOnly = false}) async {
    final res = await _api.get('/design-special-structures',
        queryParameters: activeOnly ? {'active_only': '1'} : null);
    return _items(res.data)
        .map((e) => DesignSpecialStructureDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<Map<String, dynamic>> createSpecialStructure(Map<String, dynamic> body) async {
    final res = await _api.post('/design-special-structures', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> updateSpecialStructure(int id, Map<String, dynamic> body) async {
    final res = await _api.put('/design-special-structures/$id', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> deleteSpecialStructure(int id) => _api.delete('/design-special-structures/$id');

  // ── Site visit locations ───────────────────────────────────────────────
  Future<List<SiteVisitLocationDto>> listSiteVisitLocations({bool activeOnly = false}) async {
    final res = await _api.get('/site-visit-locations',
        queryParameters: activeOnly ? {'active_only': '1'} : null);
    return _items(res.data)
        .map((e) => SiteVisitLocationDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<Map<String, dynamic>> createSiteVisitLocation(Map<String, dynamic> body) async {
    final res = await _api.post('/site-visit-locations', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> updateSiteVisitLocation(int id, Map<String, dynamic> body) async {
    final res = await _api.put('/site-visit-locations/$id', data: body);
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<void> deleteSiteVisitLocation(int id) => _api.delete('/site-visit-locations/$id');

  // ── Calculators ────────────────────────────────────────────────────────
  Future<DesignPricingLookups> designPricingLookups() async {
    final res = await _api.get('/calculators/design-pricing');
    final body = Map<String, dynamic>.from(res.data as Map);
    final data = Map<String, dynamic>.from(body['data'] as Map? ?? const {});
    return DesignPricingLookups.fromJson(data);
  }

  Future<ComputeResult> designPricingCompute(Map<String, dynamic> body) async {
    final res = await _api.post('/calculators/design-pricing/compute', data: body);
    final json = Map<String, dynamic>.from(res.data as Map);
    final data = Map<String, dynamic>.from(json['data'] as Map? ?? const {});
    return ComputeResult(data);
  }

  Future<SiteVisitCalculatorLookups> siteVisitLookups() async {
    final res = await _api.get('/calculators/site-visit');
    final body = Map<String, dynamic>.from(res.data as Map);
    final data = Map<String, dynamic>.from(body['data'] as Map? ?? const {});
    return SiteVisitCalculatorLookups.fromJson(data);
  }

  Future<ComputeResult> siteVisitCompute(Map<String, dynamic> body) async {
    final res = await _api.post('/calculators/site-visit/compute', data: body);
    final json = Map<String, dynamic>.from(res.data as Map);
    final data = Map<String, dynamic>.from(json['data'] as Map? ?? const {});
    return ComputeResult(data);
  }

  List<Map> _items(dynamic raw) {
    if (raw is! Map) return const [];
    final list = raw['data'];
    if (list is! List) return const [];
    return list.whereType<Map>().toList();
  }
}

final calculatorsRemoteDataSourceProvider =
    Provider<CalculatorsRemoteDataSource>((ref) {
  return CalculatorsRemoteDataSource(ref.watch(apiClientProvider));
});
