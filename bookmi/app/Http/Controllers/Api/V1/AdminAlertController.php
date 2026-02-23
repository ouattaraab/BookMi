<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AdminAlert;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAlertController extends BaseController
{
    public function __construct(private readonly AlertService $alerts)
    {
    }

    /**
     * GET /api/v1/admin/alerts
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdminAlert::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->severity, fn ($q, $sv) => $q->where('severity', $sv))
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse($query);
    }

    /**
     * POST /api/v1/admin/alerts/{alert}/resolve
     */
    public function resolve(Request $request, AdminAlert $alert): JsonResponse
    {
        $this->alerts->resolve($request->user(), $alert);

        return $this->successResponse(['message' => 'Alerte résolue.']);
    }

    /**
     * POST /api/v1/admin/alerts/{alert}/dismiss
     */
    public function dismiss(Request $request, AdminAlert $alert): JsonResponse
    {
        $this->alerts->dismiss($request->user(), $alert);

        return $this->successResponse(['message' => 'Alerte ignorée.']);
    }
}
