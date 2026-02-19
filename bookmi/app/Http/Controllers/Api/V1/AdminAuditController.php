<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditController extends BaseController
{
    /**
     * GET /api/v1/admin/audit
     * View complete audit trail with filters (Story 8.10).
     */
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::query()
            ->with('causer:id,first_name,last_name,email')
            ->when($request->causer_id, fn ($q, $id) => $q->where('causer_id', $id))
            ->when($request->action, fn ($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->when($request->model, fn ($q, $m) => $q->where('subject_type', 'like', "%{$m}%"))
            ->when($request->from, fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->where('created_at', '<=', $to))
            ->latest()
            ->paginate(50);

        return $this->paginatedResponse($logs);
    }
}
