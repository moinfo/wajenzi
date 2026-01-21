import 'dart:io';
import 'package:drift/drift.dart';
import 'package:drift/native.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as p;
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'tables/sync_queue_table.dart';
import 'tables/users_table.dart';
import 'tables/attendances_table.dart';
import 'tables/site_daily_reports_table.dart';
import 'tables/expenses_table.dart';
import 'tables/projects_table.dart';
import 'tables/sites_table.dart';

part 'database.g.dart';

final databaseProvider = Provider<AppDatabase>((ref) {
  return AppDatabase();
});

@DriftDatabase(tables: [
  SyncQueue,
  Users,
  Attendances,
  SiteDailyReports,
  Expenses,
  Projects,
  Sites,
])
class AppDatabase extends _$AppDatabase {
  AppDatabase() : super(_openConnection());

  @override
  int get schemaVersion => 1;

  @override
  MigrationStrategy get migration {
    return MigrationStrategy(
      onCreate: (Migrator m) async {
        await m.createAll();
      },
      onUpgrade: (Migrator m, int from, int to) async {
        // Handle migrations here
      },
    );
  }

  // Sync Queue Operations
  Future<int> addToSyncQueue(SyncQueueCompanion entry) {
    return into(syncQueue).insert(entry);
  }

  Future<List<SyncQueueData>> getPendingSyncItems() {
    return (select(syncQueue)
          ..where((t) => t.status.equals('pending'))
          ..orderBy([(t) => OrderingTerm.asc(t.priority)]))
        .get();
  }

  Future<void> markSyncItemComplete(int id) {
    return (update(syncQueue)..where((t) => t.id.equals(id)))
        .write(const SyncQueueCompanion(status: Value('completed')));
  }

  Future<void> markSyncItemFailed(int id, String error) {
    return (update(syncQueue)..where((t) => t.id.equals(id))).write(
      SyncQueueCompanion(
        status: const Value('failed'),
        errorMessage: Value(error),
        retryCount: const Value(1), // Increment retry count
      ),
    );
  }

  Future<void> clearCompletedSyncItems() {
    return (delete(syncQueue)..where((t) => t.status.equals('completed'))).go();
  }
}

LazyDatabase _openConnection() {
  return LazyDatabase(() async {
    final dbFolder = await getApplicationDocumentsDirectory();
    final file = File(p.join(dbFolder.path, 'wajenzi.db'));
    return NativeDatabase.createInBackground(file);
  });
}
