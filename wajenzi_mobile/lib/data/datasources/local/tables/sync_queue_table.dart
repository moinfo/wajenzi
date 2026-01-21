import 'package:drift/drift.dart';

class SyncQueue extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get targetTable => text()(); // e.g., 'attendance', 'expense', 'site_daily_report'
  TextColumn get operation => text()(); // create, update, delete
  TextColumn get recordId => text()();
  TextColumn get payload => text()(); // JSON data
  IntColumn get priority => integer().withDefault(const Constant(0))();
  DateTimeColumn get createdAt => dateTime().withDefault(currentDateAndTime)();
  TextColumn get status => text().withDefault(const Constant('pending'))();
  IntColumn get retryCount => integer().withDefault(const Constant(0))();
  TextColumn get errorMessage => text().nullable()();
}
