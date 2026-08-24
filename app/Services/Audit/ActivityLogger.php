<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActivityLogger
{
    public function log($action, $auditableType, $auditableId, array $oldValues = [], array $newValues = [], $companyId = null)
    {
        if (!Schema::hasTable('activity_logs')) {
            return;
        }

        $request = app()->bound('request') ? request() : null;

        DB::table('activity_logs')->insert([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => empty($oldValues) ? null : json_encode($oldValues),
            'new_values' => empty($newValues) ? null : json_encode($newValues),
            'ip_address' => $request ? $request->ip() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
