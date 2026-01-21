<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ExpenseCategory;
use App\Models\LeaveType;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\Site;
use App\Models\SiteDailyReport;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Push offline changes to server.
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'changes' => 'required|array',
            'changes.*.table' => 'required|string',
            'changes.*.operation' => 'required|in:create,update,delete',
            'changes.*.local_id' => 'required|string',
            'changes.*.server_id' => 'nullable|integer',
            'changes.*.data' => 'required|array',
            'changes.*.timestamp' => 'required|date',
        ]);

        $user = $request->user();
        $syncLog = SyncLog::start($user->id, $request->device_id, SyncLog::TYPE_PUSH);

        $synced = [];
        $conflicts = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($request->changes as $change) {
                $result = $this->processChange($user, $change);

                if ($result['status'] === 'synced') {
                    $synced[] = $result;
                } elseif ($result['status'] === 'conflict') {
                    $conflicts[] = $result;
                } else {
                    $failed[] = $result;
                }
            }

            DB::commit();

            $syncLog->complete(
                count($synced),
                count($failed),
                !empty($failed) ? $failed : null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'synced' => $synced,
                    'conflicts' => $conflicts,
                    'failed' => $failed,
                ],
                'server_time' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $syncLog->fail(['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pull changes from server.
     */
    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'last_sync' => 'nullable|date',
            'tables' => 'nullable|array',
        ]);

        $user = $request->user();
        $lastSync = $request->last_sync ? Carbon::parse($request->last_sync) : null;
        $tables = $request->tables ?? ['attendances', 'site_daily_reports', 'expenses', 'projects'];

        $syncLog = SyncLog::start($user->id, $request->device_id, SyncLog::TYPE_PULL);

        $data = [];
        $totalRecords = 0;

        try {
            foreach ($tables as $table) {
                $records = $this->getChangedRecords($user, $table, $lastSync);
                $data[$table] = $records;
                $totalRecords += count($records);
            }

            $syncLog->complete($totalRecords);

            return response()->json([
                'success' => true,
                'data' => $data,
                'server_time' => now()->toISOString(),
                'meta' => [
                    'total_records' => $totalRecords,
                ],
            ]);
        } catch (\Exception $e) {
            $syncLog->fail(['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Pull failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reference data for offline use.
     */
    public function referenceData(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get user's active projects
        $projects = $user->projects()
            ->wherePivot('status', 'active')
            ->with(['sites', 'client'])
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'project_name' => $p->project_name,
                'document_number' => $p->document_number,
                'status' => $p->status,
                'client' => $p->client ? [
                    'id' => $p->client->id,
                    'name' => $p->client->first_name . ' ' . $p->client->last_name,
                ] : null,
                'sites' => $p->sites->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'location' => $s->location,
                ]),
            ]);

        // Get all sites for user's projects
        $projectIds = $user->projects()->wherePivot('status', 'active')->pluck('projects.id');
        $sites = Site::whereIn('project_id', $projectIds)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'project_id' => $s->project_id,
                'name' => $s->name,
                'location' => $s->location,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
            ]);

        // Get expense categories
        $expenseCategories = ExpenseCategory::all()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);

        // Get leave types
        $leaveTypes = LeaveType::all()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'days_allowed' => $t->days_allowed,
        ]);

        // Get team members for user's projects
        $teamMembers = User::whereHas('projects', function ($q) use ($projectIds) {
            $q->whereIn('projects.id', $projectIds);
        })->get()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'designation' => $u->designation,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'projects' => $projects,
                'sites' => $sites,
                'expense_categories' => $expenseCategories,
                'leave_types' => $leaveTypes,
                'team_members' => $teamMembers,
            ],
            'server_time' => now()->toISOString(),
        ]);
    }

    /**
     * Process a single change from offline sync.
     */
    private function processChange(User $user, array $change): array
    {
        $result = [
            'local_id' => $change['local_id'],
            'table' => $change['table'],
            'operation' => $change['operation'],
        ];

        try {
            switch ($change['table']) {
                case 'attendances':
                    $result = array_merge($result, $this->syncAttendance($user, $change));
                    break;
                case 'site_daily_reports':
                    $result = array_merge($result, $this->syncSiteDailyReport($user, $change));
                    break;
                case 'expenses':
                    $result = array_merge($result, $this->syncExpense($user, $change));
                    break;
                default:
                    $result['status'] = 'failed';
                    $result['error'] = 'Unknown table: ' . $change['table'];
            }
        } catch (\Exception $e) {
            $result['status'] = 'failed';
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Sync attendance record.
     */
    private function syncAttendance(User $user, array $change): array
    {
        $data = $change['data'];

        if ($change['operation'] === 'create') {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'record_time' => $data['record_time'],
                'type' => $data['type'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'ip' => $data['ip'] ?? null,
                'comment' => $data['comment'] ?? null,
            ]);

            return [
                'status' => 'synced',
                'server_id' => $attendance->id,
            ];
        }

        return ['status' => 'failed', 'error' => 'Unsupported operation'];
    }

    /**
     * Sync site daily report.
     */
    private function syncSiteDailyReport(User $user, array $change): array
    {
        $data = $change['data'];

        if ($change['operation'] === 'create') {
            $report = SiteDailyReport::create([
                'report_date' => $data['report_date'],
                'site_id' => $data['site_id'],
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'prepared_by' => $user->id,
                'progress_percentage' => $data['progress_percentage'] ?? null,
                'next_steps' => $data['next_steps'] ?? null,
                'challenges' => $data['challenges'] ?? null,
                'status' => 'draft',
            ]);

            return [
                'status' => 'synced',
                'server_id' => $report->id,
            ];
        }

        if ($change['operation'] === 'update' && $change['server_id']) {
            $report = SiteDailyReport::find($change['server_id']);

            if (!$report) {
                return ['status' => 'failed', 'error' => 'Report not found'];
            }

            // Check for conflicts - server wins for approved reports
            if (in_array($report->status, ['approved', 'rejected'])) {
                return [
                    'status' => 'conflict',
                    'resolution' => 'server_wins',
                    'server_data' => $report->toArray(),
                ];
            }

            $report->update($data);

            return [
                'status' => 'synced',
                'server_id' => $report->id,
            ];
        }

        return ['status' => 'failed', 'error' => 'Unsupported operation'];
    }

    /**
     * Sync expense record.
     */
    private function syncExpense(User $user, array $change): array
    {
        $data = $change['data'];

        if ($change['operation'] === 'create') {
            $expense = ProjectExpense::create([
                'project_id' => $data['project_id'],
                'expense_category_id' => $data['category_id'] ?? $data['expense_category_id'],
                'description' => $data['description'],
                'amount' => $data['amount'],
                'expense_date' => $data['expense_date'],
                'created_by' => $user->id,
                'status' => 'draft',
            ]);

            return [
                'status' => 'synced',
                'server_id' => $expense->id,
            ];
        }

        return ['status' => 'failed', 'error' => 'Unsupported operation'];
    }

    /**
     * Get changed records for a table since last sync.
     */
    private function getChangedRecords(User $user, string $table, ?Carbon $lastSync): array
    {
        $query = match ($table) {
            'attendances' => Attendance::where('user_id', $user->id),
            'site_daily_reports' => SiteDailyReport::where('prepared_by', $user->id)
                ->orWhere('supervisor_id', $user->id),
            'expenses' => ProjectExpense::where('created_by', $user->id),
            'projects' => $user->projects()->wherePivot('status', 'active'),
            default => null,
        };

        if (!$query) {
            return [];
        }

        if ($lastSync) {
            $query->where('updated_at', '>', $lastSync);
        }

        return $query->get()->toArray();
    }
}
