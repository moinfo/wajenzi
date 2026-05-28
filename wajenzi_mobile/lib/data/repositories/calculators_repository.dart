import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../datasources/remote/calculators_remote_datasource.dart';
import '../models/calculators/calculator_models.dart';

/// Repository facade for the calculator/catalog feature set.
///
/// Currently a thin pass-through to [CalculatorsRemoteDataSource]; placed in
/// its own layer so we can add caching / offline behaviour without touching
/// screen code later.
class CalculatorsRepository {
  final CalculatorsRemoteDataSource _remote;
  CalculatorsRepository(this._remote);

  // Currencies
  Future<List<CurrencyDto>> currencies({bool activeOnly = false}) =>
      _remote.listCurrencies(activeOnly: activeOnly);
  Future<Map<String, dynamic>> createCurrency(Map<String, dynamic> b) =>
      _remote.createCurrency(b);
  Future<Map<String, dynamic>> updateCurrency(int id, Map<String, dynamic> b) =>
      _remote.updateCurrency(id, b);
  Future<void> deleteCurrency(int id) => _remote.deleteCurrency(id);

  // Design packages
  Future<List<DesignPackageDto>> designPackages({bool activeOnly = false, String? riseType}) =>
      _remote.listDesignPackages(activeOnly: activeOnly, riseType: riseType);
  Future<Map<String, dynamic>> createDesignPackage(Map<String, dynamic> b) =>
      _remote.createDesignPackage(b);
  Future<Map<String, dynamic>> updateDesignPackage(int id, Map<String, dynamic> b) =>
      _remote.updateDesignPackage(id, b);
  Future<void> deleteDesignPackage(int id) => _remote.deleteDesignPackage(id);

  // Design add-ons
  Future<List<DesignAddonDto>> designAddons({bool activeOnly = false}) =>
      _remote.listDesignAddons(activeOnly: activeOnly);
  Future<Map<String, dynamic>> createDesignAddon(Map<String, dynamic> b) =>
      _remote.createDesignAddon(b);
  Future<Map<String, dynamic>> updateDesignAddon(int id, Map<String, dynamic> b) =>
      _remote.updateDesignAddon(id, b);
  Future<void> deleteDesignAddon(int id) => _remote.deleteDesignAddon(id);

  // Special structures
  Future<List<DesignSpecialStructureDto>> specialStructures({bool activeOnly = false}) =>
      _remote.listSpecialStructures(activeOnly: activeOnly);
  Future<Map<String, dynamic>> createSpecialStructure(Map<String, dynamic> b) =>
      _remote.createSpecialStructure(b);
  Future<Map<String, dynamic>> updateSpecialStructure(int id, Map<String, dynamic> b) =>
      _remote.updateSpecialStructure(id, b);
  Future<void> deleteSpecialStructure(int id) => _remote.deleteSpecialStructure(id);

  // Site visit locations
  Future<List<SiteVisitLocationDto>> siteVisitLocations({bool activeOnly = false}) =>
      _remote.listSiteVisitLocations(activeOnly: activeOnly);
  Future<Map<String, dynamic>> createSiteVisitLocation(Map<String, dynamic> b) =>
      _remote.createSiteVisitLocation(b);
  Future<Map<String, dynamic>> updateSiteVisitLocation(int id, Map<String, dynamic> b) =>
      _remote.updateSiteVisitLocation(id, b);
  Future<void> deleteSiteVisitLocation(int id) => _remote.deleteSiteVisitLocation(id);

  // Calculators
  Future<DesignPricingLookups> designPricingLookups() => _remote.designPricingLookups();
  Future<ComputeResult> designPricingCompute(Map<String, dynamic> body) =>
      _remote.designPricingCompute(body);
  Future<SiteVisitCalculatorLookups> siteVisitLookups() => _remote.siteVisitLookups();
  Future<ComputeResult> siteVisitCompute(Map<String, dynamic> body) =>
      _remote.siteVisitCompute(body);
}

final calculatorsRepositoryProvider = Provider<CalculatorsRepository>((ref) {
  return CalculatorsRepository(ref.watch(calculatorsRemoteDataSourceProvider));
});
