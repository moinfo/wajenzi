import 'package:drift/drift.dart';

class Projects extends Table {
  IntColumn get id => integer()();
  TextColumn get documentNumber => text().nullable()();
  TextColumn get projectName => text()();
  TextColumn get status => text().nullable()();
  DateTimeColumn get startDate => dateTime().nullable()();
  DateTimeColumn get expectedEndDate => dateTime().nullable()();
  IntColumn get clientId => integer().nullable()();
  TextColumn get clientName => text().nullable()();
  TextColumn get teamRole => text().nullable()();
  DateTimeColumn get syncedAt => dateTime().nullable()();

  @override
  Set<Column> get primaryKey => {id};
}
