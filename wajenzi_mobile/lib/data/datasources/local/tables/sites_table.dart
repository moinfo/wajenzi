import 'package:drift/drift.dart';

class Sites extends Table {
  IntColumn get id => integer()();
  IntColumn get projectId => integer()();
  TextColumn get name => text()();
  TextColumn get location => text().nullable()();
  TextColumn get address => text().nullable()();
  RealColumn get latitude => real().nullable()();
  RealColumn get longitude => real().nullable()();
  TextColumn get status => text().nullable()();
  DateTimeColumn get syncedAt => dateTime().nullable()();

  @override
  Set<Column> get primaryKey => {id};
}
