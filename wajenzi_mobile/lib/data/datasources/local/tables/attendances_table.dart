import 'package:drift/drift.dart';

class Attendances extends Table {
  IntColumn get id => integer().autoIncrement()();
  IntColumn get serverId => integer().nullable()();
  IntColumn get userId => integer()();
  DateTimeColumn get recordTime => dateTime()();
  TextColumn get type => text()(); // in, out
  RealColumn get latitude => real().nullable()();
  RealColumn get longitude => real().nullable()();
  TextColumn get ip => text().nullable()();
  TextColumn get comment => text().nullable()();
  BoolColumn get isSynced => boolean().withDefault(const Constant(false))();
  DateTimeColumn get createdAt => dateTime().withDefault(currentDateAndTime)();
}
