<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE imprest_requests
            MODIFY COLUMN status ENUM('CREATED','SUBMITTED','PENDING','APPROVED','REJECTED','REFILLED','COMPLETED')
            NOT NULL DEFAULT 'SUBMITTED'
        ");

        $type = 'App\\Models\\ImprestRequest';

        $rows = DB::table('imprest_requests as i')
            ->leftJoin('process_approval_statuses as s', function ($join) use ($type) {
                $join->on('s.approvable_id', '=', 'i.id')
                     ->where('s.approvable_type', $type);
            })
            ->select('i.id', 'i.status as imprest_status', 's.status as approval_status')
            ->get();

        foreach ($rows as $row) {
            $currentUpper = strtoupper($row->imprest_status ?? '');

            if (in_array($currentUpper, ['COMPLETED', 'REFILLED'], true)) {
                continue;
            }

            $target = match ($row->approval_status) {
                'Approved'  => 'APPROVED',
                'Rejected'  => 'REJECTED',
                'Submitted' => 'SUBMITTED',
                'Pending'   => 'PENDING',
                default     => 'SUBMITTED',
            };

            if ($currentUpper !== $target) {
                DB::table('imprest_requests')
                    ->where('id', $row->id)
                    ->update(['status' => $target, 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('imprest_requests')->where('status', 'SUBMITTED')->update(['status' => 'CREATED']);

        DB::statement("
            ALTER TABLE imprest_requests
            MODIFY COLUMN status ENUM('CREATED','PENDING','APPROVED','REJECTED','REFILLED','COMPLETED')
            NOT NULL DEFAULT 'CREATED'
        ");
    }
};
