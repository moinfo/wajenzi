import 'package:drift/drift.dart';

class SiteDailyReports extends Table {
  IntColumn get id => integer().autoIncrement()();
  IntColumn get serverId => integer().nullable()();
  DateTimeColumn get reportDate => dateTime()();
  IntColumn get siteId => integer()();
  IntColumn get supervisorId => integer().nullable()();
  IntColumn get preparedById => integer()();
  IntColumn get progressPercentage => integer().nullable()();
  TextColumn get nextSteps => text().nullable()();
  TextColumn get challenges => text().nullable()();
  TextColumn get status => text().withDefault(const Constant('draft'))();
  TextColumn get workActivitiesJson => text().nullable()(); // JSON array
  TextColumn get materialsUsedJson => text().nullable()(); // JSON array
  TextColumn get paymentsJson => text().nullable()(); // JSON array
  TextColumn get laborNeededJson => text().nullable()(); // JSON array
  BoolColumn get isSynced => boolean().withDefault(const Constant(false))();
  DateTimeColumn get createdAt => dateTime().withDefault(currentDateAndTime)();
  DateTimeColumn get updatedAt => dateTime().withDefault(currentDateAndTime)();
}
